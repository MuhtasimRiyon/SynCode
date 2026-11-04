<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Database\Seeders\CommunitySeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_seeding_does_not_create_users_or_sample_posts(): void
    {
        config(['demo.enabled' => false]);
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('taxonomies', 6);
        $this->get(route('home'))->assertOk()->assertSee('A new community starts here.');
        $this->get(route('login'))->assertOk()->assertDontSee('data-demo-login');
    }

    public function test_role_test_accounts_can_be_enabled_without_recreating_sample_blogs(): void
    {
        config(['demo.enabled' => true, 'demo.content_enabled' => false]);
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('posts', 0);
        $this->get(route('login'))->assertOk()->assertSee('data-demo-login', false)
            ->assertSee('Super Admin')->assertSee('Moderator')->assertSee('Author');
    }

    public function test_production_seeders_refuse_demo_content_even_when_demo_config_is_overridden(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config(['demo.enabled' => true, 'demo.content_enabled' => true]);
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertSuccessful();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('taxonomies', 6);
    }

    public function test_administrator_is_created_only_through_explicit_details_and_a_hashed_strong_password(): void
    {
        $this->artisan('syncode:admin', ['--name' => 'Real Owner', '--email' => 'owner@example.org'])
            ->expectsQuestion('Password (minimum 12 characters)', 'Owner-Password-2026')
            ->expectsQuestion('Confirm password', 'Owner-Password-2026')
            ->expectsOutput('Super Admin created. Sign in with the email and password you supplied.')
            ->assertSuccessful();
        $user = User::where('email', 'owner@example.org')->firstOrFail();
        $this->assertSame('super_admin', $user->role);
        $this->assertTrue(Hash::check('Owner-Password-2026', $user->password));
        $this->assertFalse($user->suspended);
    }

    public function test_admin_bootstrap_rejects_weak_passwords_and_does_not_overwrite_an_existing_user(): void
    {
        $existing = User::factory()->create(['email' => 'owner@example.org']);
        $this->artisan('syncode:admin', ['--name' => 'New Owner', '--email' => $existing->email])
            ->expectsQuestion('Password (minimum 12 characters)', 'weak')
            ->expectsQuestion('Confirm password', 'weak')
            ->assertFailed();
        $this->assertDatabaseCount('users', 1);
        $this->assertSame('author', $existing->fresh()->role);
        $this->assertSame($existing->name, $existing->fresh()->name);
    }

    public function test_cleanup_preview_preserves_everything_and_apply_refuses_without_a_successful_mysql_backup(): void
    {
        config(['demo.enabled' => true, 'demo.content_enabled' => true]);
        $this->seed(DatabaseSeeder::class);
        $post = Post::firstOrFail();
        $post->update(['content' => 'Real author changes']);
        $real = User::factory()->create();
        $this->artisan('syncode:demo-clean')->expectsOutputToContain('Preview only')->assertSuccessful();
        $this->assertDatabaseCount('posts', 5);
        $this->assertDatabaseCount('users', 8);
        $this->artisan('syncode:demo-clean', ['--apply' => true])->expectsOutputToContain('requires MySQL/MariaDB')->assertFailed();
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'content' => 'Real author changes']);
        $this->assertDatabaseHas('users', ['id' => $real->id]);
        $this->assertDatabaseCount('posts', 5);
        $this->assertDatabaseCount('users', 8);
    }

    public function test_local_requests_get_security_headers_and_authenticated_pages_cannot_be_cached(): void
    {
        $this->get(route('feed'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_production_check_does_not_accept_local_configuration_as_a_live_release(): void
    {
        $this->artisan('syncode:production-check', ['--config-only' => true])
            ->expectsOutput('FAIL APP_ENV is production')->assertFailed();
    }

    public function test_feed_paginates_and_searches_the_database_beyond_the_first_page(): void
    {
        $author = User::factory()->create();
        foreach (range(1, 16) as $index) {
            $author->posts()->create([
                'slug' => 'entry-'.$index, 'title' => 'Discovery '.$index, 'category' => $index === 1 ? 'Database' : 'Laravel',
                'content' => $index === 1 ? 'An uncommon keyword: telescope' : 'A practical lesson.',
                'tags' => $index === 1 ? ['indexing'] : [], 'status' => 'published', 'published_at' => now()->subDays(17 - $index),
            ]);
        }
        $this->get(route('feed'))->assertOk()->assertViewHas('pagination', fn ($p) => $p->total() === 16 && $p->count() === 12)->assertDontSee('Discovery 1</a>', false);
        $this->get(route('feed', ['q' => 'telescope']))->assertOk()->assertViewHas('pagination', fn ($p) => $p->total() === 1)->assertSee('Discovery 1');
        $this->get(route('feed', ['q' => 'indexing']))->assertOk()->assertViewHas('pagination', fn ($p) => $p->total() === 1);
        $this->get(route('feed', ['topic' => 'Database']))->assertOk()->assertViewHas('pagination', fn ($p) => $p->total() === 1);
        $this->get(route('feed', ['page' => 2]))->assertOk()->assertSee('Discovery 1');
    }

    public function test_following_and_trending_filters_run_on_the_server(): void
    {
        config(['demo.enabled' => true, 'demo.content_enabled' => true]);
        $this->seed(CommunitySeeder::class);
        $reader = User::factory()->create();
        $first = Post::firstOrFail();
        $reader->following()->attach($first->user_id);
        $first->likes()->attach($reader->id);
        $this->get(route('feed', ['tab' => 'following']))->assertRedirect();
        $this->actingAs($reader)->get(route('feed', ['tab' => 'following']))->assertOk()->assertViewHas('posts', fn ($posts) => collect($posts)->every(fn ($p) => $p['user_id'] === $first->user_id));
        $this->get(route('feed', ['tab' => 'trending']))->assertOk()->assertViewHas('posts', fn ($posts) => $posts[0]['id'] === $first->id);
    }
}
