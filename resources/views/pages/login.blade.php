@extends('layouts.app')
@section('title', 'Welcome back')
@section('content')
<section class="auth-panel panel">
    <span class="brand-icon"><x-icon name="code" size="28"/></span>
    <h1>Good to see you again<span class="accent">.</span></h1>
    <p>Sign in to join the conversation.</p>
    @if(request()->has('redirect'))
    <p class="auth-notice" role="status">Sign in to like or comment. After signing in, you’ll return to your article.</p>
    @endif
    @if($errors->any())<div class="auth-errors" role="alert">{{ $errors->first() }}</div>@endif
    @if(config('demo.enabled'))
    <div class="demo-login">
        <strong>Try a demo account</strong>
        <p>Choose a role to fill in the credentials, then click Sign in.</p>
        <form class="demo-login-buttons" method="GET" action="{{ route('login') }}">
            @if(old('redirect', request('redirect')))
            <input type="hidden" name="redirect" value="{{ old('redirect', request('redirect')) }}">
            @endif
            @foreach(\App\Support\DemoAccounts::users() as $account)
            <button class="button secondary compact" type="submit" name="demo" value="{{ $account['role'] }}" data-demo-login data-email="{{ $account['email'] }}" data-password="{{ \App\Support\DemoAccounts::PASSWORD }}" aria-pressed="{{ ($demoAccount['role'] ?? null) === $account['role'] ? 'true' : 'false' }}">{{ $account['label'] }}</button>
            @endforeach
        </form>
        <small role="status" data-demo-status>@if($demoAccount){{ $demoAccount['label'] }} credentials ready. Click Sign in.@endif</small>
    </div>
    @endif
    <form id="login-form" method="POST" action="{{ route('login.store') }}">
        @csrf
        <input type="hidden" name="redirect" value="{{ old('redirect', request('redirect')) }}">
        <label>Email address<input name="email" type="email" required autocomplete="email" value="{{ old('email', $demoAccount['email'] ?? '') }}" placeholder="you@example.com"></label>
        <label>Password<input name="password" type="password" required autocomplete="current-password" value="{{ $demoAccount ? \App\Support\DemoAccounts::PASSWORD : '' }}" placeholder="Your password"></label>
        <label class="checkbox-label"><input name="remember" type="checkbox" value="1" @checked(old('remember'))>Remember me</label>
        <button class="button full" type="submit">Sign in</button>
    </form>
    <p>New around here? <a class="accent" href="{{ route('register', ['redirect' => old('redirect', request('redirect'))]) }}">Create an account →</a></p>
    <a class="subtle-link" href="{{ route('home') }}#stories">← Back to stories</a>
</section>
@endsection
