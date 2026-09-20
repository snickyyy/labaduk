<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_and_routes_are_unavailable(): void
    {
        $this->assertFalse(Features::enabled(Features::registration()));
        $this->assertFalse(Route::has('register'));
        $this->assertFalse(Route::has('register.store'));

        $this->get('/register')->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_posting_to_old_registration_endpoint_cannot_create_a_user(): void
    {
        $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_filament_and_api_registration_endpoints_are_unavailable(): void
    {
        $this->get('/admin/register')->assertNotFound();
        $this->post('/admin/register', [])->assertNotFound();
        $this->getJson('/api/register')->assertNotFound();
        $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_page_has_no_registration_link(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Sign up')
            ->assertDontSee('Create account')
            ->assertDontSee('/register', escape: false);

        $this->assertDatabaseCount('users', 0);
    }
}
