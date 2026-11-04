<?php

namespace App\Http\Middleware;

use App\Models\Post;
use App\Support\SiteSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommunityAccess
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->suspended) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson() ? response()->json(['message' => 'Your account is suspended.'], 403) : redirect()->route('login')->withErrors(['email' => 'Your account is suspended.']);
        }
        if (SiteSettings::get('maintenance', false) && ! $request->user()?->can('configure-site') && ! $request->routeIs('login', 'login.store', 'logout')) {
            abort(503, 'The community is undergoing maintenance.');
        }
        Post::where('status', 'scheduled')->where('published_at', '<=', now())->update(['status' => 'published']);

        return $next($request);
    }
}
