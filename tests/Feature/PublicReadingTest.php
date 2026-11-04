<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DemoContent;
use Database\Seeders\CommunitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicReadingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['demo.enabled' => true, 'demo.content_enabled' => true]);
        $this->seed(CommunitySeeder::class);
    }

    public function test_guests_can_open_a_story_from_the_public_landing_page(): void
    {
        $post = DemoContent::posts()[0];
        $url = route('article', $post['slug']);

        $landing = $this->get('/')->assertOk()
            ->assertSee(route('login'))
            ->assertSee(route('register'))
            ->assertSee('Login')
            ->assertSee('Register');
        foreach (DemoContent::posts() as $story) {
            $landing->assertSee(route('article', $story['slug']));
        }
        $this->get($url)->assertOk()
            ->assertSee($post['title'])
            ->assertSee('Start with the problem')
            ->assertDontSee('id="navigation"', false)
            ->assertDontSee('id="login-dialog"', false)
            ->assertDontSee('YOUR WORKSPACE')
            ->assertDontSee('class="top-actions"', false)
            ->assertDontSee('data-follow=', false)
            ->assertSee('Write a comment')
            ->assertDontSee('aria-label="Your profile"', false)
            ->assertSee('data-authenticated="false"', false)
            ->assertSee(e(route('login', ['redirect' => $url])), false);
    }

    public function test_authenticated_readers_are_recognized(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('article', DemoContent::posts()[0]['slug']))
            ->assertOk()
            ->assertSee('data-authenticated="true"', false)
            ->assertSee('YOUR WORKSPACE')
            ->assertDontSee('id="login-dialog"', false);
    }

    public function test_personal_screens_require_login_but_browsing_is_public(): void
    {
        foreach (['bookmarks', 'profile', 'notifications', 'settings', 'stories', 'dashboard', 'write', 'admin'] as $screen) {
            $url = url('/'.$screen);
            $this->get($url)->assertRedirect(route('login', ['redirect' => $url]));
        }

        $this->get('/feed')->assertOk();
        $this->get('/explore')->assertOk();
    }

    public function test_login_explains_why_a_reader_was_redirected(): void
    {
        $this->get(route('login', ['redirect' => route('article', DemoContent::posts()[0]['slug'])]))
            ->assertOk()
            ->assertSee('Sign in to like or comment.')
            ->assertDontSee('id="navigation"', false)
            ->assertDontSee('class="top-actions"', false);
    }

    public function test_unknown_stories_return_not_found(): void
    {
        $this->get('/posts/unknown-story')->assertNotFound();
    }
}
