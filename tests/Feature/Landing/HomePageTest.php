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
            ->assertSee('Come play')
            ->assertSee('No experience needed')
            ->assertSee('/images/landing/studio-room-1280.webp', false)
            ->assertSee('/images/landing/open-room-1120.webp', false)
            ->assertDontSee('/api/');
    }

    public function test_unknown_landing_locale_returns_not_found(): void
    {
        $this->get('/fr')->assertNotFound();
    }

    public function test_new_landing_locales_are_available(): void
    {
        $this->get('/de')
            ->assertOk()
            ->assertSee('<html lang="de">', false)
            ->assertSee('Komm vorbei');

        $this->get('/ua')
            ->assertOk()
            ->assertSee('<html lang="ua">', false)
            ->assertSee('Заходь');
    }

    public function test_language_selector_links_to_each_supported_locale(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('data-language-select', false)
            ->assertSee('value="'.route('landing.home', ['locale' => 'de']).'"', false)
            ->assertSee('value="'.route('landing.home', ['locale' => 'ua']).'"', false)
            ->assertSee('>English</option>', false);
    }
}
