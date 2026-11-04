<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use App\Support\DemoContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Laravel', 'JavaScript', 'PHP', 'Database', 'Daily Learning', 'UI / UX'] as $name) {
            DB::table('taxonomies')->updateOrInsert(['name' => $name, 'type' => 'Category'], ['created_at' => now(), 'updated_at' => now()]);
        }
        if (app()->isProduction() || ! config('demo.enabled') || ! config('demo.content_enabled')) {
            return;
        }
        foreach (DemoContent::posts() as $data) {
            $email = Str::slug($data['author']).'@syncode.test';
            $user = User::firstOrCreate(['email' => $email], ['name' => $data['author'], 'password' => Str::random(40)]);
            Post::firstOrCreate(['slug' => $data['slug']], ['user_id' => $user->id, 'title' => $data['title'], 'excerpt' => $data['excerpt'],
                'content' => DemoContent::body($data),
                'category' => $data['category'], 'tags' => $data['tags'], 'cover' => $data['cover'], 'featured' => $data['featured'], 'status' => 'published', 'published_at' => now()->subDays(5)]);
        }
    }
}
