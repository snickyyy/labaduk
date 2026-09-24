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

    public function test_new_landing_locales_are_available(): void
    {
        $this->get('/de')
            ->assertOk()
            ->assertSee('<html lang="de">', false)
            ->assertSee('Das Riff, das');

        $this->get('/ua')
            ->assertOk()
            ->assertSee('<html lang="ua">', false)
            ->assertSee('Риф, який');
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
