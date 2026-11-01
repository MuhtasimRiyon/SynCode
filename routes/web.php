<?php

use App\Http\Controllers\CommunityController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CommunityController::class, 'landing'])->name('home');
Route::get('/feed', [CommunityController::class, 'home'])->name('feed');
Route::get('/posts/{slug}', [CommunityController::class, 'article'])->name('article');
foreach (['explore', 'bookmarks', 'profile', 'notifications', 'settings', 'stories', 'dashboard', 'admin', 'login', 'register'] as $screen) {
    Route::get('/'.$screen, [CommunityController::class, 'screen'])->name($screen);
}
Route::get('/write', [CommunityController::class, 'screen'])->name('editor');
