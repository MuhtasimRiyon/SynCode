<?php

namespace App\Http\Controllers;

use App\Support\DemoContent;
use Illuminate\Http\Request;

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
        $view = in_array($page, ['dashboard', 'editor', 'admin', 'login', 'register']) ? $page : 'account';

        return view('pages.'.$view, ['posts' => DemoContent::posts(), 'page' => $page]);
    }
}
