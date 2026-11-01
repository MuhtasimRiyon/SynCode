@extends('layouts.app')
@section('title', 'Welcome back')
@section('content')
<section class="auth-panel panel"><span class="brand-icon"><x-icon name="code" size="28"/></span><span class="page-eyebrow">YOUR COMMUNITY IS HERE</span><h1>Good to see you again<span class="accent">.</span></h1><p>Pick up where your curiosity left off.</p><form data-auth><label>Email address<input type="email" required autocomplete="email" placeholder="you@example.com"></label><label>Password<input type="password" required autocomplete="current-password" minlength="8" placeholder="Your password"></label><label class="checkbox-label"><input type="checkbox">Remember me</label><button class="button full">Sign in</button><p class="field-help">UI preview. Authentication will be connected with Fortify.</p></form><p>New around here? <a class="accent" href="{{ route('register') }}">Create an account →</a></p></section>
@endsection
