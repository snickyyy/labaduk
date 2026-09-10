<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_the_default_landing_locale(): void
    {
        $response = $this->get(route('home'));

        $response->assertRedirect(route('landing.home', ['locale' => 'en']));
    }
}
