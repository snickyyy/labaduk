<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_ordinary_user_cannot_access_admin_panel_or_resources(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertForbidden();
        $this->get('/admin/appointments')->assertForbidden();
        $this->get('/admin/availabilities')->assertForbidden();
        $this->get('/admin/closed-dates')->assertForbidden();
    }

    public function test_ordinary_user_cannot_grant_themselves_admin_privileges_by_mass_assignment(): void
    {
        $user = User::factory()->create();

        $user->fill(['is_admin' => true])->save();

        $this->assertFalse($user->refresh()->is_admin);
        $this->assertNotContains('is_admin', $user->getFillable());
    }

    public function test_administrator_can_access_admin_panel(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin')->assertOk();
    }

    public function test_availabilities_resource_pages_render(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin/availabilities')
            ->assertOk()
            ->assertSee('Понедельник')
            ->assertSee('Пятница')
            ->assertDontSee('Воскресенье');
        $this->get('/admin/availabilities/create')->assertNotFound();
    }

    public function test_appointments_resource_pages_render(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin/appointments')->assertOk();
        $this->get('/admin/appointments/create')->assertOk();
    }

    public function test_closed_dates_resource_pages_render(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin/closed-dates')->assertOk();
        $this->get('/admin/closed-dates/create')->assertOk();
    }
}
