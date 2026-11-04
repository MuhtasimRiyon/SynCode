<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Support\DemoAccounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CommunityController extends Controller
{
    private function posts(int $limit = 6): array
    {
        return Post::published()->with('author')->withCount(['likes', 'comments'])->latest('published_at')->limit($limit)->get()->map->card()->all();
    }

    public function landing()
    {
        return view('pages.landing', ['posts' => $this->posts()]);
    }

    public function home(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'topic' => ['nullable', 'string', 'max:50'],
            'tab' => ['nullable', Rule::in(['all', 'latest', 'trending', 'following'])],
            'series' => ['nullable', 'integer'],
        ]);
        if (($filters['tab'] ?? '') === 'following' && ! $request->user()) {
            return redirect()->guest(route('login', ['redirect' => $request->fullUrl()]));
        }
        $query = Post::published()->with('author')->withCount(['likes', 'comments']);
        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('excerpt', 'like', $term)->orWhere('content', 'like', $term)->orWhere('tags', 'like', $term)->orWhereHas('author', fn ($q) => $q->where('name', 'like', $term)));
        }
        if (! empty($filters['topic'])) {
            $query->where('category', $filters['topic']);
        }
        if (! empty($filters['series'])) {
            $query->where('series_id', $filters['series']);
        }
        if (($filters['tab'] ?? '') === 'following') {
            $query->whereIn('user_id', $request->user()->following()->select('users.id'));
        }
        if (($filters['tab'] ?? '') === 'trending') {
            $query->orderByDesc('likes_count');
        }
        $pagination = $query->latest('published_at')->orderByDesc('id')->paginate(12)->withQueryString();

        return view('pages.feed', [
            'posts' => $pagination->getCollection()->map->card()->all(), 'pagination' => $pagination, 'page' => 'feed',
            'people' => User::where('suspended', false)->whereHas('posts', fn ($q) => $q->published())->limit(5)->get(),
        ]);
    }

    public function article(Request $request, string $slug)
    {
        $record = Post::with('author')->where('slug', $slug)->firstOrFail();
        $public = Post::published()->whereKey($record->id)->exists();
        abort_unless($public || ($request->user() && ($request->user()->id === $record->user_id || $request->user()->can('manage-community'))), 404);
        if ($public && ! $request->session()->has('read.'.$record->id)) {
            $record->increment('reads');
            $request->session()->put('read.'.$record->id, true);
        }

        return view('pages.article', ['post' => $record->card(), 'record' => $record, 'posts' => $this->posts(), 'page' => 'article', 'comments' => $record->comments()->with('author')->oldest()->get()]);
    }

    public function screen(Request $request)
    {
        $page = $request->route()->getName();
        if ($request->user() && in_array($page, ['login', 'register'])) {
            return redirect()->route($request->user()->homeRoute());
        }
        if (! $request->user() && in_array($page, ['bookmarks', 'profile', 'notifications', 'settings', 'stories', 'dashboard', 'editor', 'admin'])) {
            return redirect()->guest(route('login', ['redirect' => $request->fullUrl()]));
        }
        if ($page === 'admin') {
            Gate::authorize('manage-community');
        }
        $data = ['posts' => [], 'page' => $page, 'demoAccount' => $page === 'login' && config('demo.enabled') ? collect(DemoAccounts::users())->firstWhere('role', $request->query('demo')) : null];
        if ($request->user()) {
            $user = $request->user();
            $data['myStories'] = in_array($page, ['stories', 'dashboard']) ? $user->posts()->with('author')->withCount(['likes', 'comments'])->latest('updated_at')->get() : collect();
            $data['profileUser'] = $page === 'profile' && $request->filled('user') ? User::where('suspended', false)->findOrFail($request->integer('user')) : $user;
            $data['notifications'] = $page === 'notifications' ? DB::table('community_notifications')->where('user_id', $user->id)->latest()->limit(100)->get() : collect();
            if ($page === 'bookmarks') {
                $data['posts'] = Post::published()->whereHas('bookmarks', fn ($q) => $q->where('users.id', $user->id))->with('author')->withCount(['likes', 'comments'])->latest('published_at')->get()->map->card()->all();
            }
            if ($page === 'profile') {
                $data['posts'] = $data['profileUser']->posts()->published()->with('author')->withCount(['likes', 'comments'])->latest('published_at')->get()->map->card()->all();
            }
            $data['editing'] = null;
            if ($page === 'editor' && $request->filled('post')) {
                $data['editing'] = Post::findOrFail($request->integer('post'));
                abort_unless($data['editing']->user_id === $user->id, 403);
            }
        }
        $data['categories'] = DB::table('taxonomies')->where('type', 'Category')->orderBy('name')->pluck('name');
        $data['seriesList'] = DB::table('series')->orderBy('title')->get();
        if ($page === 'admin') {
            $data['allStories'] = Post::with('author')->withCount(['likes', 'comments'])->latest()->get();
            $data['members'] = User::latest()->get();
            $data['reports'] = DB::table('reports')->join('users', 'users.id', '=', 'reports.user_id')->join('posts', 'posts.id', '=', 'reports.post_id')->select('reports.*', 'users.name', 'posts.slug', 'posts.title')->latest('reports.created_at')->get();
            $data['taxonomies'] = DB::table('taxonomies')->orderBy('type')->orderBy('name')->get();
        }
        $view = in_array($page, ['dashboard', 'editor', 'admin', 'login', 'register']) ? $page : 'account';

        return view('pages.'.$view,$data);
    }
}
