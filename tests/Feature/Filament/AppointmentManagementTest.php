<?php

namespace Tests\Feature\Filament;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\ClosedDates\Pages\CreateClosedDate;
use App\Filament\Resources\ClosedDates\Pages\ListClosedDates;
use App\Models\Appointment;
use App\Models\ClosedDate;
use App\Models\User;
use App\Services\AppointmentService;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppointmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-14 10:00', AppointmentService::TIMEZONE));
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array<string, string|int>
     */
    private function formData(array $overrides = []): array
    {
        return [
            'first_name' => 'Admin',
            'last_name' => 'Visitor',
            'email' => 'admin-visitor@example.com',
            'phone_number' => '+49123456789',
            'appointment_date' => '2026-09-21',
            'start_time' => '14:00',
            'duration_minutes' => 60,
            'status' => AppointmentStatus::CREATED->value,
            ...$overrides,
        ];
    }

    private function appointment(string $start, string $end): Appointment
    {
        return Appointment::create([
            'first_name' => 'Existing',
            'last_name' => 'Visitor',
            'email' => 'existing@example.com',
            'phone_number' => '+49000000000',
            'start_at' => CarbonImmutable::parse($start, AppointmentService::TIMEZONE)->utc(),
            'end_at' => CarbonImmutable::parse($end, AppointmentService::TIMEZONE)->utc(),
            'status' => AppointmentStatus::CREATED,
        ]);
    }

    public function test_administrator_can_create_a_valid_appointment(): void
    {
        Livewire::test(CreateAppointment::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('appointments', [
            'email' => 'admin-visitor@example.com',
            'start_at' => '2026-09-21 12:00:00',
            'end_at' => '2026-09-21 13:00:00',
        ]);
    }

    public function test_administrator_cannot_create_an_overlapping_appointment(): void
    {
        $this->appointment('2026-09-21 14:00', '2026-09-21 15:00');

        Livewire::test(CreateAppointment::class)
            ->fillForm($this->formData(['start_time' => '14:30']))
            ->call('create')
            ->assertHasFormErrors(['start_time']);

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_edit_ignores_the_current_appointment_but_rejects_other_conflicts(): void
    {
        $appointment = $this->appointment('2026-09-21 14:00', '2026-09-21 15:00');

        Livewire::test(EditAppointment::class, ['record' => $appointment->id])
            ->fillForm($this->formData())
            ->call('save')
            ->assertHasNoFormErrors();

        $this->appointment('2026-09-21 15:00', '2026-09-21 16:00');

        Livewire::test(EditAppointment::class, ['record' => $appointment->id])
            ->fillForm($this->formData([
                'start_time' => '15:00',
                'duration_minutes' => 30,
            ]))
            ->call('save')
            ->assertHasFormErrors(['start_time']);
    }

    public function test_administrator_can_close_and_reopen_an_empty_date(): void
    {
        Livewire::test(CreateClosedDate::class)
            ->fillForm(['date' => '2026-09-21', 'reason' => 'Holiday'])
            ->call('create')
            ->assertHasNoFormErrors();

        $closedDate = ClosedDate::query()->sole();

        Livewire::test(ListClosedDates::class)
            ->callAction(TestAction::make('delete')->table($closedDate));

        $this->assertDatabaseCount('closed_dates', 0);
    }

    public function test_date_with_an_active_appointment_cannot_be_closed(): void
    {
        $this->appointment('2026-09-21 14:00', '2026-09-21 15:00');

        Livewire::test(CreateClosedDate::class)
            ->fillForm(['date' => '2026-09-21'])
            ->call('create')
            ->assertHasFormErrors(['date']);

        $this->assertDatabaseCount('closed_dates', 0);
    }

    public function test_administrator_cannot_book_a_closed_date(): void
    {
        ClosedDate::create(['date' => '2026-09-21']);

        Livewire::test(CreateAppointment::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasFormErrors(['start_time']);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_administrator_can_cancel_an_existing_appointment(): void
    {
        $appointment = $this->appointment('2026-09-21 14:00', '2026-09-21 15:00');

        Livewire::test(ListAppointments::class)
            ->callAction(TestAction::make('cancel')->table($appointment));

        $this->assertSame(AppointmentStatus::CANCELED, $appointment->refresh()->status);
    }
}
