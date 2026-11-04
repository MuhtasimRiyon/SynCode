<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\User;
use App\Support\DemoAccounts;
use App\Support\DemoContent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CleanDemoData extends Command
{
    protected $signature = 'syncode:demo-clean {--apply : Back up MySQL and remove the identified unchanged sample data}';

    protected $description = 'Preview or remove known demo data while preserving edited stories and unrelated users';

    public function handle(): int
    {
        $postIds = [];
        $userIds = [];
        foreach (DemoContent::posts() as $sample) {
            $user = User::where('email', Str::slug($sample['author']).'@syncode.test')->where('name', $sample['author'])->first();
            if (! $user) {
                continue;
            }
            $userIds[] = $user->id;
            $post = Post::where('user_id', $user->id)->where('slug', $sample['slug'])->where('title', $sample['title'])->where('excerpt', $sample['excerpt'])->where('content', DemoContent::body($sample))->whereNull('cover_path')->first();
            if ($post) {
                $postIds[] = $post->id;
                $this->line('Sample story: '.$post->title);
            }
        }
        foreach (DemoAccounts::users() as $account) {
            $user = User::where('email', $account['email'])->where('name', $account['name'])->where('role', $account['role'])->first();
            if ($user && Hash::check(DemoAccounts::PASSWORD, $user->password)) {
                $userIds[] = $user->id;
            }
        }
        $userIds = array_values(array_unique($userIds));
        $this->info(count($postIds).' unchanged sample stories; '.count($userIds).' known sample accounts identified.');
        if (! $this->option('apply')) {
            $this->line('Preview only. Run with --apply to back up and clean.');

            return self::SUCCESS;
        }
        if (! $postIds && ! $userIds) {
            $this->info('No matching demo data remains.');

            return self::SUCCESS;
        }
        if ($this->call('syncode:backup') !== self::SUCCESS) {
            return self::FAILURE;
        }
        DB::transaction(function () use ($postIds, $userIds) {
            $slugs = Post::whereIn('id', $postIds)->pluck('slug');
            DB::table('community_notifications')->whereIn('post_slug', $slugs)->delete();
            Post::whereIn('id', $postIds)->delete();
            foreach (User::whereIn('id', $userIds)->get() as $user) {
                if ($user->posts()->exists() || DB::table('comments')->where('user_id', $user->id)->exists()) {
                    // Preserve any real contributions; remove the demo login's access.
                    if (in_array($user->email, array_column(DemoAccounts::users(), 'email'))) {
                        $user->forceFill(['password' => Str::random(64), 'remember_token' => null, 'suspended' => true])->save();
                    }
                    $this->warn('Preserved account with remaining contributions: '.$user->email);

                    continue;
                }
                DB::table('sessions')->where('user_id', $user->id)->delete();
                $user->delete();
            }
        });
        $this->info('Sample cleanup complete. Real/edited stories and unrelated users were preserved.');

        return self::SUCCESS;
    }
}
