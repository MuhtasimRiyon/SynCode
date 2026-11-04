<?php

use App\Models\Post;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('stories:publish', function () {
    $count = Post::where('status', 'scheduled')->where('published_at', '<=', now())->update(['status' => 'published']);
    $this->info("Published {$count} scheduled stories.");
})->purpose('Publish approved scheduled stories');
Schedule::command('stories:publish')->everyMinute()->withoutOverlapping();
