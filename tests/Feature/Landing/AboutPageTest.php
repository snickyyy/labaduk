<?php

namespace Tests\Feature\Landing;

use Tests\TestCase;

class AboutPageTest extends TestCase
{
    public function test_about_page_renders_the_current_member_profiles(): void
    {
        $this->get('/en/about')
            ->assertOk()
            ->assertSee('About')
            ->assertSee('Members')
            ->assertSee('Labaduk')
            ->assertSee('Nastya')
            ->assertSee('Bogdan')
            ->assertSee('Fynn Opitz')
            ->assertSee('Sepultura do Brasil! Um, dois, três, quatro!')
            ->assertSee('Authentische, handgemachte Musik')
            ->assertDontSee('Kakoito Hui II')
            ->assertSee('/images/members/labaduk.jpg', false)
            ->assertSee('/images/members/mongol.jpg', false)
            ->assertSee('Book a visit');
    }
}
