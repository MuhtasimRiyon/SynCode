<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoryController extends Controller
{
    private function visible(Post $post): void
    {
        abort_unless(Post::published()->whereKey($post->id)->exists(), 404);
    }

    private function notify(int $user, string $message, ?string $slug = null): void
    {
        if (auth()->id() !== $user) {
            DB::table('community_notifications')->insert(['user_id' => $user, 'message' => Str::limit($message, 250), 'post_slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function save(Request $request, ?Post $post = null)
    {
        if ($post) {
            abort_unless($post->user_id === $request->user()->id, 403);
        }
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'], 'excerpt' => ['nullable', 'string', 'max:350'],
            'content' => ['required', 'string', 'max:100000'], 'category' => ['required', Rule::exists('taxonomies', 'name')->where('type', 'Category')],
            'tags' => ['nullable', 'string', 'max:500'], 'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'intent' => ['required', Rule::in(['draft', 'submit', 'schedule'])],
            'published_at' => ['exclude_unless:intent,schedule', 'required_if:intent,schedule', 'nullable', 'date', 'after:now'],
            'series_id' => ['nullable', 'integer', 'exists:series,id'],
        ]);
        $tags = array_values(array_unique(array_filter(array_map('trim', explode(',', $data['tags'] ?? '')))));
        if (count($tags) > 10 || collect($tags)->contains(fn ($tag) => mb_strlen($tag) > 50)) {
            throw ValidationException::withMessages(['tags' => 'Use up to 10 tags, each at most 50 characters.']);
        }
        $review = SiteSettings::get('moderation', true) && ! $request->user()->can('manage-community');
        $status = match ($data['intent']) {
            'draft' => 'draft','schedule' => $review ? 'pending' : 'scheduled',default => $review ? 'pending' : 'published'
        };
        $oldCover = $post?->cover_path;
        $newCover = $request->hasFile('cover') ? $request->file('cover')->store('covers', 'public') : null;
        try {
            $post = DB::transaction(function () use ($post, $request, $data, $tags, $status, $newCover) {
                $post ??= new Post(['user_id' => $request->user()->id, 'slug' => (Str::slug($data['title']) ?: 'story').'-'.Str::lower(Str::random(10))]);
                $post->fill(collect($data)->only(['title', 'excerpt', 'content', 'category', 'series_id'])->all());
                $post->tags = $tags;
                $post->status = $status;
                $post->published_at = $data['intent'] === 'schedule' ? $data['published_at'] : ($status === 'published' ? now() : null);
                if ($newCover) {
                    $post->cover_path = $newCover;
                }
                $post->save();

                return $post;
            });
        } catch (\Throwable $e) {
            if ($newCover) {
                Storage::disk('public')->delete($newCover);
            } throw $e;
        }
        if ($newCover && $oldCover) {
            Storage::disk('public')->delete($oldCover);
        }

        return redirect()->route('stories')->with('status', $status === 'pending' ? 'Story submitted for review.' : 'Story saved as '.$status.'.');
    }

    public function destroy(Request $request, Post $post)
    {
        abort_unless($post->user_id === $request->user()->id || $request->user()->can('manage-community'), 403);
        $path = $post->cover_path;
        DB::transaction(function () use ($post) {
            DB::table('community_notifications')->where('post_slug', $post->slug)->delete();
            $post->delete();
        });
        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('status', 'Story deleted.');
    }

    public function reaction(Request $request, Post $post, string $kind)
    {
        $this->visible($post);
        abort_unless(in_array($kind, ['like', 'bookmark']), 404);
        $relation = $kind === 'like' ? $post->likes() : $post->bookmarks();
        $active = DB::transaction(function () use ($request, $relation) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $active = ! (clone $relation)->where('users.id', $request->user()->id)->exists();
            if ($active) {
                $relation->syncWithoutDetaching([$request->user()->id]);
            } else {
                $relation->detach($request->user()->id);
            }

            return $active;
        });
        if ($active && $kind === 'like') {
            $this->notify($post->user_id, $request->user()->name.' liked your story.', $post->slug);
        }

        return response()->json(['active' => $active, 'count' => $relation->count()]);
    }

    public function follow(Request $request, User $author)
    {
        abort_if($author->suspended || $author->id === $request->user()->id, 422, 'You cannot follow this account.');
        $active = DB::transaction(function () use ($request, $author) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $active = ! $user->following()->where('users.id', $author->id)->exists();
            if ($active) {
                $user->following()->syncWithoutDetaching([$author->id]);
            } else {
                $user->following()->detach($author->id);
            }

            return $active;
        });
        if ($active) {
            $this->notify($author->id, $request->user()->name.' started following you.');
        }

        return response()->json(['active' => $active]);
    }

    public function comment(Request $request, Post $post)
    {
        $this->visible($post);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $post->comments()->create($data + ['user_id' => $request->user()->id]);
        $this->notify($post->user_id, $request->user()->name.' commented on your story.', $post->slug);

        return redirect()->to(route('article', $post->slug).'#comments')->with('status', 'Comment added.');
    }

    public function deleteComment(Request $request, Comment $comment)
    {
        abort_unless($comment->user_id === $request->user()->id || $request->user()->can('manage-community'), 403);
        $comment->delete();

        return back()->with('status', 'Comment deleted.');
    }

    public function report(Request $request, Post $post)
    {
        $this->visible($post);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000'], 'comment_id' => ['nullable', 'integer', Rule::exists('comments', 'id')->where('post_id', $post->id)]]);
        DB::table('reports')->insert($data + ['user_id' => $request->user()->id, 'post_id' => $post->id, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('status', 'Report sent to moderators.');
    }

    public function preferences(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'username' => ['nullable', 'alpha_dash', 'max:40', Rule::unique('users')->ignore($request->user()->id)], 'bio' => ['nullable', 'string', 'max:300'], 'location' => ['nullable', 'string', 'max:100']]);
        $user = $request->user();
        $user->forceFill($data + ['preferences' => ['comfortable' => $request->boolean('comfortable'), 'reduceMotion' => $request->boolean('reduceMotion')]])->save();

        return back()->with('status', 'Profile and reading preferences saved.');
    }

    public function readNotifications(Request $request)
    {
        DB::table('community_notifications')->where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('status', 'Notifications marked as read.');
    }

    public function newsletter(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        DB::table('newsletter_subscriptions')->insertOrIgnore(['email' => strtolower($data['email']), 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('status', 'Your newsletter signup has been saved.');
    }
}
