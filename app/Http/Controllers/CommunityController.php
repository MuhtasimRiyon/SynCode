<?php

namespace App\Http\Controllers;

use App\Support\DemoAccounts;
use App\Support\DemoContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommunityController extends Controller
{
    public function landing()
    {
        return view('pages.landing', ['posts' => DemoContent::posts()]);
    }

    public function home()
    {
        return view('pages.feed', ['posts' => DemoContent::posts(), 'page' => 'feed']);
    }

    public function article(string $slug)
    {
        $posts = DemoContent::posts();
        $post = collect($posts)->firstWhere('slug', $slug);
        abort_unless($post, 404);

        return view('pages.article', compact('post', 'posts') + ['page' => 'article']);
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
        $view = in_array($page, ['dashboard', 'editor', 'admin', 'login', 'register']) ? $page : 'account';

        $demoAccount = $page === 'login' && config('demo.enabled')
            ? collect(DemoAccounts::users())->firstWhere('role', $request->query('demo'))
            : null;

        return view('pages.'.$view, ['posts' => DemoContent::posts(), 'page' => $page, 'demoAccount' => $demoAccount]);
    }
}
