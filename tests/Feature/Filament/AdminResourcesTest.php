<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_availabilities_resource_pages_render(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/availabilities')
            ->assertOk()
            ->assertSee('Понедельник')
            ->assertSee('Воскресенье');
        $this->get('/admin/availabilities/create')->assertOk();
    }

    public function test_appointments_resource_pages_render(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/appointments')->assertOk();
        $this->get('/admin/appointments/create')->assertOk();
    }
}
