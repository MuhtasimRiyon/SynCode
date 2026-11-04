@props(['post'])
<article class="post-card" data-post data-category="{{ $post['category'] }}" data-slug="{{ $post['slug'] }}" data-likes="{{ $post['likes'] }}" data-author="{{ $post['user_id'] }}">
<div class="post-body"><div class="author-line"><a href="{{ route('profile',['user'=>$post['user_id']]) }}" class="avatar small {{ $post['color'] }}">{{ $post['initials'] }}</a><a href="{{ route('profile',['user'=>$post['user_id']]) }}" class="author-name">{{ $post['author'] }}</a><span class="muted">· {{ $post['date'] }}</span>@if($post['featured'])<span class="featured-label">✧ Featured</span>@endif</div>
<a class="post-title" href="{{ route('article', $post['slug']) }}">{{ $post['title'] }}</a><p class="post-excerpt">{{ $post['excerpt'] }}</p>
<div class="post-tags">@foreach($post['tags'] as $tag)<a href="{{ route('feed', ['q' => $tag]) }}">#{{ $tag }}</a>@endforeach</div>
<div class="post-meta"><button class="text-button" data-like="{{ $post['slug'] }}" aria-label="Like story" aria-pressed="false"><x-icon name="heart" size="17"/><span>{{ $post['likes'] }}</span></button><a href="{{ route('article', $post['slug']) }}#comments"><x-icon name="comment" size="16"/>{{ $post['comments'] }}</a><span class="reading-time">{{ $post['time'] }}</span><button class="bookmark-button icon-button" data-bookmark="{{ $post['slug'] }}" aria-label="Save {{ $post['title'] }}" aria-pressed="false"><x-icon name="bookmark" size="18"/></button></div></div>
<a href="{{ route('article', $post['slug']) }}" class="post-art art-{{ $post['cover'] }}" aria-label="Read {{ $post['title'] }}">
@if($post['cover_path'])<img src="{{ asset('storage/'.$post['cover_path']) }}" alt="{{ $post['title'] }}" style="width:100%;height:100%;object-fit:cover">
@elseif($post['cover'] === 'laravel')<span class="art-kicker">THE ART OF LESS</span><div class="laravel-mark">L<span>↗</span></div><span class="art-caption">small actions.<br>big possibilities.</span><span class="art-bottom">LARAVEL / ARCHITECTURE</span>
@elseif($post['cover'] === 'javascript')<span class="art-kicker">UNDER THE HOOD</span><span class="js-mark">JS<span>{ }</span></span><span class="art-caption">Making it<br>click.</span>
@elseif($post['cover'] === 'database')<span class="art-kicker">LESS WAIT. MORE BUILD.</span><span class="db-mark">40<span>×</span><i>faster queries</i></span><div class="art-bars"><b></b><b></b><b></b><b></b><b></b></div>
@elseif($post['cover'] === 'learning')<span class="art-kicker">THE DAILY COMMIT</span><span class="learn-mark">Day 12<span>↗</span></span><span class="art-caption">Progress over<br>perfection.</span>
@else<span class="art-kicker">DESIGN FIELD NOTES</span><div class="design-mark"><span></span><span></span><span></span></div><span class="art-caption">Room to breathe.</span>@endif
</a></article>
