<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\StoryController;
use App\Http\Middleware\CommunityAccess;
use Illuminate\Support\Facades\Route;

Route::middleware(CommunityAccess::class)->group(function () {
    Route::get('/', [CommunityController::class, 'landing'])->name('home');
    Route::get('/feed', [CommunityController::class, 'home'])->name('feed');
    Route::get('/posts/{slug}', [CommunityController::class, 'article'])->name('article');
    foreach (['explore', 'bookmarks', 'profile', 'notifications', 'settings', 'stories', 'dashboard', 'admin', 'login', 'register'] as $screen) {
        Route::get('/'.$screen, [CommunityController::class, 'screen'])->name($screen);
    }
    Route::get('/write', [CommunityController::class, 'screen'])->name('editor');
    Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:6,1'])->name('login.store');
    Route::post('/register', [AuthController::class, 'register'])->middleware(['guest', 'throttle:6,1'])->name('register.store');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

    Route::post('/newsletter', [StoryController::class, 'newsletter'])->middleware('throttle:10,1')->name('newsletter');
    Route::middleware(['auth', 'throttle:120,1'])->group(function () {
        $story = StoryController::class;
        $admin = AdminController::class;
        Route::post('/stories', [$story, 'save'])->name('stories.store');
        Route::put('/stories/{post}', [$story, 'save'])->name('stories.update');
        Route::delete('/stories/{post}', [$story, 'destroy'])->name('stories.destroy');
        Route::post('/posts/{post}/reactions/{kind}', [$story, 'reaction'])->name('reaction');
        Route::post('/authors/{author}/follow', [$story, 'follow'])->name('follow');
        Route::post('/posts/{post}/comments', [$story, 'comment'])->middleware('throttle:15,1')->name('comments.store');
        Route::delete('/comments/{comment}', [$story, 'deleteComment'])->name('comments.destroy');
        Route::post('/posts/{post}/reports', [$story, 'report'])->middleware('throttle:10,1')->name('reports.store');
        Route::put('/settings', [$story, 'preferences'])->name('preferences.update');
        Route::post('/notifications/read', [$story, 'readNotifications'])->name('notifications.read');
        Route::patch('/admin/posts/{post}', [$admin, 'moderate'])->name('admin.posts');
        Route::patch('/admin/users/{user}', [$admin, 'member'])->name('admin.users');
        Route::post('/admin/reports/{report}/resolve', [$admin, 'resolve'])->name('admin.reports');
        Route::post('/admin/taxonomy', [$admin, 'taxonomy'])->name('admin.taxonomy');
        Route::post('/admin/series', [$admin, 'series'])->name('admin.series');
        Route::put('/admin/settings', [$admin, 'settings'])->name('admin.settings');
    });
});
