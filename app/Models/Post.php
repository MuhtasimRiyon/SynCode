<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tags' => 'array', 'featured' => 'boolean', 'published_at' => 'datetime'];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->belongsToMany(User::class, 'likes')->withTimestamps();
    }

    public function bookmarks()
    {
        return $this->belongsToMany(User::class, 'bookmarks')->withTimestamps();
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')->where('published_at', '<=', now())->whereHas('author', fn ($q) => $q->where('suspended', false));
    }

    public function card(): array
    {
        return ['id' => $this->id, 'user_id' => $this->user_id, 'slug' => $this->slug, 'title' => $this->title, 'excerpt' => $this->excerpt,
            'content' => $this->content, 'category' => $this->category, 'tags' => $this->tags ?? [], 'cover' => $this->cover, 'cover_path' => $this->cover_path,
            'featured' => $this->featured, 'author' => $this->author->name, 'initials' => Str::upper(Str::substr($this->author->name, 0, 2)),
            'color' => 'green', 'time' => max(1, (int) ceil(str_word_count($this->content) / 220)).' min read',
            'date' => ($this->published_at ?? $this->created_at)->format('M j'), 'likes' => $this->likes_count ?? $this->likes()->count(),
            'comments' => $this->comments_count ?? $this->comments()->count(), 'status' => $this->status, 'reads' => $this->reads];
    }
}
