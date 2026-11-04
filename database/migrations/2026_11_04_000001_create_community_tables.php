<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('username', 40)->nullable()->unique();
            $t->text('bio')->nullable();
            $t->string('location', 100)->nullable();
            $t->json('preferences')->nullable();
            $t->boolean('suspended')->default(false);
        });
        Schema::create('taxonomies', function (Blueprint $t) {
            $t->id();
            $t->string('name', 50);
            $t->string('type', 20);
            $t->timestamps();
            $t->unique(['name', 'type']);
        });
        Schema::create('series', function (Blueprint $t) {
            $t->id();
            $t->string('title', 180);
            $t->text('description')->nullable();
            $t->timestamps();
        });
        Schema::create('posts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('series_id')->nullable()->constrained('series')->nullOnDelete();
            $t->string('slug')->unique();
            $t->string('title', 180);
            $t->text('excerpt')->nullable();
            $t->longText('content');
            $t->string('category', 50);
            $t->json('tags')->nullable();
            $t->string('cover')->default('learning');
            $t->string('cover_path')->nullable();
            $t->string('status', 20)->default('draft')->index();
            $t->boolean('featured')->default(false);
            $t->unsignedBigInteger('reads')->default(0);
            $t->timestamp('published_at')->nullable()->index();
            $t->timestamps();
        });
        foreach (['likes', 'bookmarks'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->foreignId('post_id')->constrained()->cascadeOnDelete();
                $t->timestamps();
                $t->unique(['user_id', 'post_id']);
            });
        }
        Schema::create('follows', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['user_id', 'author_id']);
        });
        Schema::create('comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('post_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->text('body');
            $t->timestamps();
        });
        Schema::create('community_notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('message');
            $t->string('post_slug')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        Schema::create('reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('post_id')->constrained()->cascadeOnDelete();
            $t->foreignId('comment_id')->nullable()->constrained()->cascadeOnDelete();
            $t->text('reason');
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });
        Schema::create('site_settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
        });
        Schema::create('newsletter_subscriptions', function (Blueprint $t) {
            $t->id();
            $t->string('email')->unique();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['newsletter_subscriptions', 'site_settings', 'reports', 'community_notifications', 'comments', 'follows', 'bookmarks', 'likes', 'posts', 'series', 'taxonomies'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['username', 'bio', 'location', 'preferences', 'suspended']));
    }
};
