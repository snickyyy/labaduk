<?php

namespace Tests\Feature\Landing;

use Tests\TestCase;

class AboutPageTest extends TestCase
{
    public function test_about_page_renders_member_profiles_from_the_members_directory(): void
    {
        $this->get('/en/about')
            ->assertOk()
            ->assertSee('About')
            ->assertSee('Members')
            ->assertSee('Labaduk')
            ->assertSee('Mongol')
            ->assertSee('/images/members/labaduk.jpg', false)
            ->assertSee('/images/members/mongol.jpg', false)
            ->assertSee('Book a visit');
    }
}
