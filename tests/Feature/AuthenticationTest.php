<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DemoContent;
use Database\Seeders\CommunitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_to_the_article_with_workspace_navigation(): void
    {
        config(['demo.enabled' => true, 'demo.content_enabled' => true]);
        $this->seed(CommunitySeeder::class);
        $user = User::factory()->create(['password' => 'a-valid-password']);
        $article = route('article', DemoContent::posts()[0]['slug']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'a-valid-password',
            'redirect' => $article.'#comments',
        ])->assertRedirect($article.'#comments');

        $this->assertAuthenticatedAs($user);
        $this->get($article)->assertOk()
            ->assertSee('id="navigation"', false)
            ->assertSee('YOUR WORKSPACE')
            ->assertSee('data-comment', false)
            ->assertDontSee('Write a comment');
    }

    public function test_incorrect_credentials_leave_the_reader_logged_out(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_registration_creates_an_account_and_returns_to_the_article(): void
    {
        $article = route('article', DemoContent::posts()[0]['slug']);

        $this->post(route('register.store'), [
            'name' => 'A Reader',
            'email' => 'reader@example.com',
            'password' => 'a-valid-password',
            'password_confirmation' => 'a-valid-password',
            'respectful' => '1',
            'redirect' => $article,
        ])->assertRedirect($article);

        $user = User::where('email', 'reader@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('a-valid-password', $user->password));
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_registration_rejects_duplicate_email_and_unconfirmed_password(): void
    {
        User::factory()->create(['email' => 'reader@example.com']);

        $this->post(route('register.store'), [
            'name' => 'A Reader',
            'email' => 'reader@example.com',
            'password' => 'a-valid-password',
            'password_confirmation' => 'different-password',
            'respectful' => '1',
        ])->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_login_never_redirects_to_an_external_site(): void
    {
        $user = User::factory()->create(['password' => 'a-valid-password']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'a-valid-password',
            'redirect' => 'https://example.com/posts/story',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_logout_hides_the_workspace_again(): void
    {
        config(['demo.enabled' => true, 'demo.content_enabled' => true]);
        $this->seed(CommunitySeeder::class);
        $this->actingAs(User::factory()->create())->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->get(route('article', DemoContent::posts()[0]['slug']))
            ->assertOk()->assertDontSee('id="navigation"', false);
    }
}
