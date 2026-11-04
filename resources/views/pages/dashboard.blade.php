@extends('layouts.app')
@section('title', 'Your workspace')
@section('content')
<div class="page-eyebrow">YOUR WORKSPACE</div><div class="section-heading"><div><h1>Keep the ideas flowing<span class="accent">.</span></h1><p>Welcome back, {{ auth()->user()->name }}. Every story starts with a small discovery.</p></div><a class="button" href="{{ route('editor') }}">Write a story →</a></div>
<div class="metrics">@foreach([['Stories published',$myStories->where('status','published')->count()],['Total reads',$myStories->sum('reads')],['Reactions',$myStories->sum('likes_count')],['Followers',auth()->user()->followers()->count()]] as [$label,$value])<div class="metric"><div>{{ $label }}</div><strong>{{ number_format($value) }}</strong></div>@endforeach</div>
<h2>Your stories</h2>@include('components.story-table')
@endsection
