<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ \App\Support\SiteSettings::get('description','A home for curious developers. Learn, build, and share.') }}">
    <title>@yield('title', \App\Support\SiteSettings::get('seoTitle','Your daily dose of discovery')) · {{ \App\Support\SiteSettings::get('siteName','SynCode') }}</title>
    <link rel="stylesheet" href="{{ asset('css/community.css') }}?v={{ filemtime(public_path('css/community.css')) }}">
    <script src="{{ asset('js/community.js') }}?v={{ filemtime(public_path('js/community.js')) }}" defer></script>
</head>
<body class="{{ auth()->guest() ? 'public-layout' : '' }}" data-page="{{ $page }}" data-authenticated="{{ auth()->check() ? 'true' : 'false' }}" data-user-name="{{ auth()->user()?->name }}" data-login-url="{{ route('login', ['redirect' => request()->fullUrl()]) }}">
<header class="topbar">
    <div class="brand-wrap">@auth<button class="icon-button mobile-menu" aria-label="Toggle navigation" aria-expanded="false" data-menu><x-icon name="menu"/></button>@endauth<a class="brand" href="{{ route('home') }}"><span class="brand-icon"><x-icon name="code" size="23"/></span>@if(\App\Support\SiteSettings::get('siteName','SynCode') === 'SynCode')Syn<span>Code</span>@else{{ \App\Support\SiteSettings::get('siteName') }}@endif<span class="brand-dot">.</span></a></div>
    @if(auth()->check() || $page === 'feed')
    <form action="{{ route('feed') }}" class="global-search" role="search"><x-icon name="search" size="18"/><input type="search" name="q" value="{{ request('q') }}" placeholder="Search stories, topics, and people…" aria-label="Search stories"><kbd>/</kbd></form>
    @endif
    @auth
    <div class="top-actions">
    <a class="write-button" href="{{ route('editor') }}"><x-icon name="edit" size="17"/><span>Write a story</span></a><a class="notification-button icon-button" href="{{ route('notifications') }}" aria-label="Notifications"><x-icon name="bell"/>@if(\Illuminate\Support\Facades\DB::table('community_notifications')->where('user_id',auth()->id())->whereNull('read_at')->exists())<i></i>@endif</a><a class="avatar green" href="{{ route('profile') }}" aria-label="Your profile">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</a>
    <form action="{{ route('logout') }}" method="POST">@csrf<button class="text-button" type="submit">Sign out</button></form>
    </div>
    @endauth
</header>
@auth
<aside class="sidebar" id="navigation">
    <div class="signed-in-user"><strong>{{ auth()->user()->name }}</strong><span class="tag">{{ auth()->user()->roleLabel() }}</span></div>
    <span class="nav-label">COMMUNITY</span>
    @foreach([['feed','home','My feed'],['explore','compass','Explore']] as [$route,$icon,$label])
    <a class="nav-item {{ $page === $route ? 'active' : '' }}" href="{{ route($route) }}"><x-icon :name="$icon"/>{{ $label }}@if($route === 'notifications')<span class="count">3</span>@endif</a>
    @endforeach
    @foreach([['bookmarks','bookmark','Bookmarks'],['notifications','bell','Notifications']] as [$route,$icon,$label])
    <a class="nav-item {{ $page === $route ? 'active' : '' }}" href="{{ route($route) }}"><x-icon :name="$icon"/>{{ $label }}</a>
    @endforeach
    <span class="nav-label space">YOUR WORKSPACE</span>
    @foreach([['dashboard','grid','Dashboard'],['stories','edit','My stories'],['profile','user','My profile'],['settings','settings','Settings']] as [$route,$icon,$label])
    <a class="nav-item {{ $page === $route ? 'active' : '' }}" href="{{ route($route) }}"><x-icon :name="$icon"/>{{ $label }}</a>
    @endforeach
    <div class="sidebar-divider"></div><div class="label-row"><span class="nav-label">TOPICS TO EXPLORE</span><a href="{{ route('explore') }}" aria-label="All topics">+</a></div>
    @foreach(\Illuminate\Support\Facades\DB::table('taxonomies')->where('type','Category')->orderBy('name')->pluck('name') as $topic)
    <a class="topic-link" href="{{ route('feed',['topic'=>$topic]) }}"><span class="topic-dot mint"></span>{{ $topic }}</a>
    @endforeach
    <div class="sidebar-bottom"><div class="grow-card"><span class="little-spark">✧</span><strong>A little better, every day.</strong><p>Your next breakthrough starts with something you share.</p><a href="{{ route('editor') }}">Share what you learned <span>↗</span></a></div>@can('manage-community')<a class="nav-item admin-link {{ $page === 'admin' ? 'active' : '' }}" href="{{ route('admin') }}"><x-icon name="shield"/>{{ auth()->user()->role === 'moderator' ? 'Moderation workspace' : 'Admin workspace' }} <span>↗</span></a>@endcan<div class="sidebar-footer">© {{ date('Y') }} SynCode <span>Built for the curious.</span></div></div>
</aside>
@endauth
<script>
window.communityState = {{ \Illuminate\Support\Js::from([
    'base' => url('/'),
    'postIds' => collect(array_merge($posts, $page === 'article' ? [$post] : []))->pluck('id','slug'),
    'bookmarks' => auth()->check() ? \Illuminate\Support\Facades\DB::table('bookmarks')->join('posts','posts.id','=','bookmarks.post_id')->where('bookmarks.user_id',auth()->id())->pluck('posts.slug') : [],
    'likes' => auth()->check() ? \Illuminate\Support\Facades\DB::table('likes')->join('posts','posts.id','=','likes.post_id')->where('likes.user_id',auth()->id())->pluck('posts.slug') : [],
    'following' => auth()->check() ? auth()->user()->following()->pluck('users.id')->map(fn($id)=>(string)$id) : [],
    'preferences' => auth()->user()?->preferences ?? [],
]) }};
</script>
<main class="main-content">@if(session('status'))<div class="panel" role="status">{{ session('status') }}</div>@endif @if($errors->any())<div class="panel" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@yield('content')<footer class="page-footer"><span>Keep learning. Keep building. Keep sharing.</span><span>{{ \App\Support\SiteSettings::get('siteName','SynCode') }} community</span></footer></main>
<div class="toast" role="status" aria-live="polite" hidden></div>

</body>
</html>
