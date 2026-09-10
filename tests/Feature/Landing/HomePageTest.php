<?php

namespace Tests\Feature\Landing;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_english_landing_page_is_rendered_with_localized_content(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('The riff that')
            ->assertSee('Technical notes')
            ->assertSee('Course timeline')
            ->assertSee('/images/landing/hero-512.webp', false)
            ->assertDontSee('/api/');
    }

    public function test_unknown_landing_locale_returns_not_found(): void
    {
        $this->get('/fr')->assertNotFound();
    }
}
