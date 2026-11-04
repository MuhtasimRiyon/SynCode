<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_shows_the_public_landing_page_without_workspace_navigation(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Small discoveries.')
            ->assertSee(route('feed'))
            ->assertDontSee('id="navigation"', false);
    }

    public function test_community_feed_has_its_own_route(): void
    {
        $this->get('/feed')->assertOk()
            ->assertDontSee('id="navigation"', false)
            ->assertSee('Learn something.');
    }
}
