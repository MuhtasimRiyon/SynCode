@extends('layouts.app')
@section('title', 'Create an account')
@section('content')
<section class="auth-panel panel">
    <span class="brand-icon"><x-icon name="code" size="28"/></span>
    <h1>Join the conversation<span class="accent">.</span></h1>
    <p>Create an account to like, comment, and share your learning.</p>
    @if($errors->any())<div class="auth-errors" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <input type="hidden" name="redirect" value="{{ old('redirect', request('redirect')) }}">
        <label>Your name<input name="name" required maxlength="80" autocomplete="name" value="{{ old('name') }}" placeholder="What should we call you?"></label>
        <label>Email address<input name="email" type="email" required autocomplete="email" value="{{ old('email') }}" placeholder="you@example.com"></label>
        <label>Password<input name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="At least 8 characters"></label>
        <label>Confirm password<input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" placeholder="Repeat your password"></label>
        <label class="checkbox-label"><input name="respectful" type="checkbox" value="1" required @checked(old('respectful'))>I agree to keep this community respectful.</label>
        <button class="button full" type="submit">Create account</button>
    </form>
    <p>Already part of the community? <a class="accent" href="{{ route('login', ['redirect' => old('redirect', request('redirect'))]) }}">Sign in →</a></p>
    <a class="subtle-link" href="{{ route('home') }}#stories">← Back to stories</a>
</section>
@endsection
