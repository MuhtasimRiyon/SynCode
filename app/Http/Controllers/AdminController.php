<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function moderate(Request $request, Post $post)
    {
        Gate::authorize('manage-community');
        $data = $request->validate(['status' => ['sometimes', Rule::in(['published', 'rejected', 'draft'])], 'featured' => ['sometimes', 'boolean']]);
        if (isset($data['status'])) {
            $data['published_at'] = $data['status'] === 'published' ? ($post->published_at?->isFuture() ? $post->published_at : now()) : null;
            if ($data['status'] === 'published' && $data['published_at']->isFuture()) {
                $data['status'] = 'scheduled';
            }
            if ($post->user_id !== $request->user()->id) {
                DB::table('community_notifications')->insert(['user_id' => $post->user_id, 'message' => 'Your story was '.$data['status'].'.', 'post_slug' => $post->slug, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        $post->update($data);

        return back()->with('status', 'Story updated.');
    }

    public function member(Request $request, User $user)
    {
        Gate::authorize('manage-users');
        $data = $request->validate(['role' => ['required', Rule::in(['author', 'moderator', 'super_admin'])], 'suspended' => ['sometimes', 'boolean']]);
        $data['suspended'] = $request->boolean('suspended');
        DB::transaction(function () use ($request, $user, $data) {
            $admins = User::where('role', 'super_admin')->where('suspended', false)->lockForUpdate()->get();
            abort_if($user->id === $request->user()->id && ($data['role'] !== 'super_admin' || $data['suspended']), 422, 'You cannot remove your own administrator access.');
            abort_if($user->role === 'super_admin' && ! $user->suspended && $admins->count() <= 1 && ($data['role'] !== 'super_admin' || $data['suspended']), 422, 'Keep at least one active Super Admin.');
            $user->forceFill($data)->save();
        });

        return back()->with('status', 'Member updated.');
    }

    public function resolve(int $report)
    {
        Gate::authorize('manage-community');
        abort_unless(DB::table('reports')->where('id', $report)->exists(), 404);
        DB::table('reports')->where('id', $report)->update(['resolved_at' => now(), 'updated_at' => now()]);

        return back()->with('status', 'Report resolved.');
    }

    public function taxonomy(Request $request)
    {
        Gate::authorize('configure-site');
        $data = $request->validate(['name' => ['required', 'string', 'max:50'], 'type' => ['required', Rule::in(['Category', 'Tag'])]]);
        DB::table('taxonomies')->insertOrIgnore($data + ['created_at' => now(), 'updated_at' => now()]);

        return back()->with('status', 'Taxonomy saved.');
    }

    public function series(Request $request)
    {
        Gate::authorize('configure-site');
        $data = $request->validate(['title' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string', 'max:1000']]);
        DB::table('series')->insert($data + ['created_at' => now(), 'updated_at' => now()]);

        return back()->with('status', 'Series created.');
    }

    public function settings(Request $request)
    {
        Gate::authorize('configure-site');
        $data = $request->validate(['siteName' => ['required', 'string', 'max:80'], 'description' => ['nullable', 'string', 'max:500'], 'seoTitle' => ['nullable', 'string', 'max:180']]);
        DB::transaction(function () use ($request, $data) {
            foreach ($data + ['moderation' => $request->boolean('moderation'), 'maintenance' => $request->boolean('maintenance')] as $key => $value) {
                SiteSettings::set($key, $value);
            }
        });

        return back()->with('status','Site settings saved.');
    }
}
