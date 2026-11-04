<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DemoAccounts;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_is_repeatable_and_stores_hashed_passwords(): void
    {
        config(['demo.enabled' => true]);
        $this->seed(DemoUserSeeder::class);
        $this->seed(DemoUserSeeder::class);

        $this->assertDatabaseCount('users', 3);
        foreach (DemoAccounts::users() as $account) {
            $user = User::where('email', $account['email'])->firstOrFail();
            $this->assertSame($account['role'], $user->role);
            $this->assertTrue(Hash::check(DemoAccounts::PASSWORD, $user->password));
        }
    }

    public function test_each_demo_account_can_login_and_open_its_workspace(): void
    {
        config(['demo.enabled' => true]);
        $this->seed(DemoUserSeeder::class);

        foreach (DemoAccounts::users() as $account) {
            $user = User::where('email', $account['email'])->firstOrFail();
            $this->post(route('login.store'), [
                'email' => $account['email'],
                'password' => DemoAccounts::PASSWORD,
            ])->assertRedirect(route($user->homeRoute()));
            $this->assertAuthenticatedAs($user);
            $this->get(route($user->homeRoute()))->assertOk()->assertSee($account['label']);
            $this->post(route('logout'))->assertRedirect(route('home'));
            $this->assertGuest();
        }
    }

    public function test_authors_cannot_open_or_see_admin_workspace(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $this->actingAs($author)->get(route('admin'))->assertForbidden();
        $this->get(route('dashboard'))->assertOk()->assertDontSee('admin-link');
    }

    public function test_moderators_only_see_content_and_moderation_controls(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);
        $this->actingAs($moderator)->get(route('admin'))->assertOk()
            ->assertSee('data-admin-panel="content"', false)
            ->assertSee('data-admin-panel="moderation"', false)
            ->assertDontSee('data-admin-panel="users"', false)
            ->assertDontSee('data-admin-panel="configuration"', false)
            ->assertDontSee('data-admin-tab="users"', false)
            ->assertDontSee('data-admin-tab="configuration"', false);
        $this->assertFalse($moderator->can('manage-users'));
        $this->assertFalse($moderator->can('configure-site'));
    }

    public function test_super_admins_have_full_workspace_access(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->get(route('admin'))->assertOk()
            ->assertSee('data-admin-panel="users"', false)
            ->assertSee('data-admin-panel="configuration"', false);
        $this->assertTrue($admin->can('manage-users'));
        $this->assertTrue($admin->can('configure-site'));
    }

    public function test_registration_always_creates_an_author_even_when_an_admin_role_is_submitted(): void
    {
        $this->post(route('register.store'), [
            'name' => 'New Author',
            'email' => 'new@example.com',
            'password' => 'a-valid-password',
            'password_confirmation' => 'a-valid-password',
            'respectful' => '1',
            'role' => 'super_admin',
        ])->assertRedirect(route('dashboard'));

        $this->assertSame('author', User::where('email', 'new@example.com')->firstOrFail()->role);
        $this->get(route('admin'))->assertForbidden();
    }

    public function test_demo_controls_and_seeding_can_be_disabled(): void
    {
        config(['demo.enabled' => false]);
        $this->seed(DemoUserSeeder::class);
        $this->assertDatabaseCount('users', 0);
        $this->get(route('login'))->assertOk()->assertDontSee('data-demo-login');

        config(['demo.enabled' => true]);
        $this->get(route('login'))->assertOk()
            ->assertSee('data-demo-login', false)
            ->assertSee('admin@syncode.test')
            ->assertSee('moderator@syncode.test')
            ->assertSee('author@syncode.test');
    }

    public function test_demo_credentials_can_be_filled_without_javascript(): void
    {
        config(['demo.enabled' => true]);

        foreach (DemoAccounts::users() as $account) {
            $this->get(route('login', ['demo' => $account['role']]))->assertOk()
                ->assertSee('value="'.$account['email'].'"', false)
                ->assertSee('value="'.DemoAccounts::PASSWORD.'"', false);
        }

        $this->get(route('login', ['demo' => 'unknown']))->assertOk()
            ->assertDontSee('type="password" required autocomplete="current-password" value="'.DemoAccounts::PASSWORD.'"', false);

        config(['demo.enabled' => false]);
        $this->get(route('login', ['demo' => 'super_admin']))->assertOk()
            ->assertDontSee('admin@syncode.test')
            ->assertDontSee(DemoAccounts::PASSWORD);
    }
}
