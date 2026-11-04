<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Support\SiteSettings;
use Database\Seeders\CommunitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommunityFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['demo.enabled' => false]);
        $this->seed(CommunitySeeder::class);
    }

    private function story(User $author, string $status = 'published'): Post
    {
        return $author->posts()->create(['slug' => 'story-'.uniqid(), 'title' => 'A useful discovery', 'excerpt' => 'An introduction', 'content' => '## A lesson'."\n\n".'Real story content.', 'category' => 'Laravel', 'tags' => ['laravel'], 'status' => $status, 'published_at' => $status === 'published' ? now() : null]);
    }

    private function payload(array $extra = []): array
    {
        return array_replace(['title' => 'My next story', 'excerpt' => 'A short introduction', 'content' => 'The actual story body.', 'category' => 'Laravel', 'tags' => 'laravel, testing', 'intent' => 'submit'], $extra);
    }

    public function test_author_can_save_edit_submit_and_delete_own_story_but_cannot_edit_another_authors_story(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($author)->post(route('stories.store'), $this->payload(['intent' => 'draft']))->assertRedirect(route('stories'));
        $post = $author->posts()->firstOrFail();
        $this->assertSame('draft', $post->status);
        $this->get(route('editor', ['post' => $post->id]))->assertOk()->assertSee($post->title);
        $this->get(route('article', $post->slug))->assertOk()->assertSee('Private preview');
        $this->actingAs($other)->put(route('stories.update', $post), $this->payload())->assertForbidden();
        $this->get(route('editor', ['post' => $post->id]))->assertForbidden();
        $this->get(route('article', $post->slug))->assertNotFound();
        $this->delete(route('stories.destroy', $post))->assertForbidden();
        $this->actingAs($author)->put(route('stories.update', $post), $this->payload(['title' => 'Updated title']))->assertRedirect(route('stories'));
        $this->assertSame('pending', $post->fresh()->status);
        $this->assertSame(['laravel', 'testing'], $post->fresh()->tags);
        $this->delete(route('stories.destroy', $post))->assertRedirect();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_pending_story_is_private_until_moderator_approval_and_content_is_rendered_safely(): void
    {
        $author = User::factory()->create();
        $moderator = User::factory()->create(['role' => 'moderator']);
        $post = $this->story($author, 'pending');
        $post->update(['content' => '# Unique heading'."\n\n".'<script>alert("xss")</script>'."\n\n".'[unsafe](javascript:alert(1))']);
        $this->get(route('article', $post->slug))->assertNotFound();
        $this->actingAs($author)->patch(route('admin.posts', $post), ['status' => 'published'])->assertForbidden();
        $this->actingAs($moderator)->patch(route('admin.posts', $post), ['status' => 'published'])->assertRedirect();
        $this->assertSame('published', $post->fresh()->status);
        $this->get(route('article', $post->slug))->assertOk()->assertSee('Unique heading')->assertDontSee('<script>alert', false)->assertDontSee('href="javascript:', false);
        $this->assertDatabaseHas('community_notifications', ['user_id' => $author->id, 'post_slug' => $post->slug]);
    }

    public function test_reactions_persist_per_account_and_counts_include_every_reader(): void
    {
        $author = User::factory()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $post = $this->story($author);
        $this->postJson(route('reaction', [$post, 'like']))->assertUnauthorized();
        $this->actingAs($first)->postJson(route('reaction', [$post, 'like']))->assertOk()->assertJson(['active' => true, 'count' => 1]);
        $this->postJson(route('reaction', [$post, 'bookmark']))->assertOk()->assertJson(['active' => true]);
        $this->get(route('bookmarks'))->assertOk()->assertSee($post->slug);
        $this->actingAs($second)->postJson(route('reaction', [$post, 'like']))->assertOk()->assertJson(['active' => true, 'count' => 2]);
        $this->assertDatabaseMissing('bookmarks', ['user_id' => $second->id]);
        $this->actingAs($first)->postJson(route('reaction', [$post, 'like']))->assertOk()->assertJson(['active' => false, 'count' => 1]);
        $this->postJson(route('reaction', [$post, 'bookmark']))->assertOk()->assertJson(['active' => false]);
        $private = $this->story($author, 'draft');
        $this->postJson(route('reaction', [$private, 'like']))->assertNotFound();
    }

    public function test_follow_comments_reports_and_notifications_use_database_and_enforce_ownership(): void
    {
        $author = User::factory()->create();
        $reader = User::factory()->create();
        $other = User::factory()->create();
        $mod = User::factory()->create(['role' => 'moderator']);
        $post = $this->story($author);
        $this->actingAs($reader)->postJson(route('follow', $author))->assertOk()->assertJson(['active' => true]);
        $this->assertDatabaseHas('follows', ['user_id' => $reader->id, 'author_id' => $author->id]);
        $this->postJson(route('follow', $reader))->assertUnprocessable();
        $this->post(route('comments.store', $post), ['body' => 'A helpful comment.'])->assertRedirect();
        $comment = $post->comments()->firstOrFail();
        $this->get(route('article', $post->slug))->assertOk()->assertSee('A helpful comment.');
        $this->post(route('reports.store', $post), ['reason' => 'Needs review', 'comment_id' => $comment->id])->assertRedirect();
        $this->actingAs($other)->delete(route('comments.destroy', $comment))->assertForbidden();
        $this->actingAs($author)->post(route('notifications.read'))->assertRedirect();
        $this->assertSame(0, DB::table('community_notifications')->where('user_id', $author->id)->whereNull('read_at')->count());
        $report = DB::table('reports')->first();
        $this->actingAs($mod)->post(route('admin.reports', $report->id))->assertRedirect();
        $this->assertNotNull(DB::table('reports')->value('resolved_at'));
        $this->delete(route('comments.destroy', $comment))->assertRedirect();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_profile_settings_persist_and_username_cannot_be_taken_from_another_user(): void
    {
        $one = User::factory()->create(['username' => 'taken']);
        $two = User::factory()->create();
        $this->actingAs($two)->put(route('preferences.update'), ['name' => 'Changed', 'username' => 'taken'])->assertSessionHasErrors('username');
        $this->put(route('preferences.update'), ['name' => 'Changed', 'username' => 'unique_name', 'bio' => 'A developer', 'location' => 'Dhaka', 'comfortable' => '1', 'role' => 'super_admin'])->assertRedirect();
        $this->assertSame('Changed', $two->fresh()->name);
        $this->assertSame('author', $two->fresh()->role);
        $this->assertTrue($two->fresh()->preferences['comfortable']);
        $this->get(route('profile'))->assertOk()->assertSee('A developer');
        $this->get(route('settings'))->assertOk()->assertSee('unique_name');
    }

    public function test_suspension_blocks_existing_sessions_and_new_logins_and_admin_cannot_remove_own_access(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create(['password' => 'valid-password']);
        $this->actingAs($admin)->patch(route('admin.users', $admin), ['role' => 'author'])->assertUnprocessable();
        $this->patch(route('admin.users', $user), ['role' => 'author', 'suspended' => '1'])->assertRedirect();
        $this->actingAs($user->refresh())->postJson(route('follow', $admin))->assertForbidden();
        $this->assertGuest();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'valid-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_moderators_cannot_manage_users_taxonomy_series_or_site_configuration(): void
    {
        $mod = User::factory()->create(['role' => 'moderator']);
        $member = User::factory()->create();
        $this->actingAs($mod)->patch(route('admin.users', $member), ['role' => 'super_admin'])->assertForbidden();
        $this->post(route('admin.taxonomy'), ['name' => 'New', 'type' => 'Category'])->assertForbidden();
        $this->post(route('admin.series'), ['title' => 'New series'])->assertForbidden();
        $this->put(route('admin.settings'), ['siteName' => 'Changed'])->assertForbidden();
    }

    public function test_admin_settings_taxonomy_series_newsletter_and_maintenance_are_effective(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->post(route('newsletter'), ['email' => 'reader@example.com'])->assertRedirect();
        $this->post(route('newsletter'), ['email' => 'reader@example.com'])->assertRedirect();
        $this->assertDatabaseCount('newsletter_subscriptions', 1);
        $this->actingAs($admin)->post(route('admin.taxonomy'), ['name' => 'Testing', 'type' => 'Category'])->assertRedirect();
        $this->post(route('admin.series'), ['title' => 'Testing series', 'description' => 'Lessons'])->assertRedirect();
        $this->assertDatabaseHas('series', ['title' => 'Testing series']);
        $this->get(route('editor'))->assertOk()->assertSee('Testing series');
        $this->put(route('admin.settings'), ['siteName' => 'Our community', 'description' => 'New description', 'seoTitle' => 'Learning hub', 'maintenance' => '1'])->assertRedirect();
        $this->get(route('admin'))->assertOk()->assertSee('Our community');
        $this->post(route('logout'))->assertRedirect();
        $this->get(route('feed'))->assertStatus(503);
        $this->get(route('login'))->assertOk();
        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])->assertRedirect();
        $this->put(route('admin.settings'), ['siteName' => 'Our community'])->assertRedirect();
        $this->get(route('feed'))->assertOk();
    }

    public function test_scheduling_requires_approval_and_publishes_only_after_the_due_time(): void
    {
        $author = User::factory()->create();
        $mod = User::factory()->create(['role' => 'moderator']);
        $this->actingAs($author)->post(route('stories.store'), $this->payload(['intent' => 'schedule', 'published_at' => now()->addHour()->format('Y-m-d H:i:s')]))->assertRedirect();
        $post = $author->posts()->firstOrFail();
        $this->assertSame('pending', $post->status);
        $this->artisan('stories:publish')->assertSuccessful();
        $this->assertSame('pending', $post->fresh()->status);
        $this->actingAs($mod)->patch(route('admin.posts', $post), ['status' => 'published'])->assertRedirect();
        $this->assertSame('scheduled', $post->fresh()->status);
        $this->travel(2)->hours();
        $this->get(route('feed'))->assertOk()->assertSee($post->title);
        $this->assertSame('published', $post->fresh()->status);
        $this->travelBack();
        SiteSettings::set('moderation', false);
        $this->actingAs($author)->post(route('stories.store'), $this->payload())->assertRedirect();
        $this->assertSame('published', $author->posts()->latest('id')->first()->status);
    }

    public function test_cover_upload_validation_and_deletion_clean_up_files_and_relations(): void
    {
        Storage::fake('public');
        $author = User::factory()->create();
        $this->actingAs($author)->post(route('stories.store'), $this->payload(['cover' => UploadedFile::fake()->create('bad.svg', 10, 'image/svg+xml')]))->assertSessionHasErrors('cover');
        $file = UploadedFile::fake()->image('cover.png');
        $this->post(route('stories.store'), $this->payload(['cover' => $file, 'intent' => 'draft']))->assertRedirect();
        $post = $author->posts()->firstOrFail();
        Storage::disk('public')->assertExists($post->cover_path);
        $this->delete(route('stories.destroy', $post))->assertRedirect();
        Storage::disk('public')->assertMissing($post->cover_path);
    }

    public function test_read_counts_are_deduplicated_per_session_and_series_feed_filters_stories(): void
    {
        $author = User::factory()->create();
        $post = $this->story($author);
        $series = DB::table('series')->insertGetId(['title' => 'First series', 'created_at' => now(), 'updated_at' => now()]);
        $post->update(['series_id' => $series]);
        $other = $this->story($author);
        $other->update(['title' => 'Unrelated story']);
        $this->get(route('article',$post->slug))->assertOk();
        $this->get(route('article',$post->slug))->assertOk();
        $this->assertSame(1,$post->fresh()->reads);
        $this->get(route('feed',['series' => $series]))->assertOk()->assertSee($post->title)->assertDontSee($other->title);
    }
}
