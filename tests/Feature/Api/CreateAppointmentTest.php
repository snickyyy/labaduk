<?php

namespace Tests\Feature\Api;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAppointmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Миграция availabilities сидирует окна: пн–пт 13:00–22:00, сб–вс 10:00–22:00 (UTC).
     */
    private function next(int $day, int $hour, int $minute = 0): CarbonImmutable
    {
        return CarbonImmutable::now('UTC')->next($day)->setTime($hour, $minute);
    }

    private function payload(CarbonImmutable $start, array $overrides = []): array
    {
        return [
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'email' => 'ivan@example.com',
            'phone_number' => '+79991234567',
            'start_at' => $start->toIso8601String(),
            ...$overrides,
        ];
    }

    public function test_appointment_can_be_created_within_availability_window(): void
    {
        $start = $this->next(Carbon::MONDAY, 14);

        $response = $this->postJson('/api/appointments', $this->payload($start));

        $response->assertCreated()
            ->assertJsonPath('data.first_name', 'Иван')
            ->assertJsonPath('data.status', AppointmentStatus::CREATED->value)
            ->assertJsonPath('data.start_at', $start->toIso8601String());

        $this->assertDatabaseHas('appointments', [
            'email' => 'ivan@example.com',
            'start_at' => $start->format('Y-m-d H:i:s'),
            'end_at' => null,
            'status' => AppointmentStatus::CREATED->value,
        ]);
    }

    public function test_appointment_cannot_be_created_before_availability_window(): void
    {
        $start = $this->next(Carbon::MONDAY, 10);

        $response = $this->postJson('/api/appointments', $this->payload($start));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('start_at');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_appointment_cannot_be_created_after_availability_window(): void
    {
        $start = $this->next(Carbon::WEDNESDAY, 22);

        $this->postJson('/api/appointments', $this->payload($start))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_at');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_appointment_can_be_created_in_the_last_hour_of_an_availability_window(): void
    {
        $start = $this->next(Carbon::FRIDAY, 21);

        $this->postJson('/api/appointments', $this->payload($start))
            ->assertCreated();
    }

    public function test_appointment_can_be_created_on_weekend_window(): void
    {
        $start = $this->next(Carbon::SUNDAY, 10);

        $this->postJson('/api/appointments', $this->payload($start))
            ->assertCreated();
    }

    public function test_appointment_must_start_on_the_hour(): void
    {
        $start = $this->next(Carbon::MONDAY, 14, 30);

        $this->postJson('/api/appointments', $this->payload($start))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_at');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_input_with_timezone_offset_is_converted_to_utc(): void
    {
        $start = $this->next(Carbon::TUESDAY, 16)->setTimezone('Europe/Moscow');

        $this->postJson('/api/appointments', $this->payload($start, [
            'first_name' => 'Пётр',
            'last_name' => 'Петров',
            'email' => 'petr@example.com',
            'phone_number' => '+79997654321',
        ]))->assertCreated();

        $this->assertDatabaseHas('appointments', [
            'start_at' => $start->utc()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_status_cannot_be_set_by_client(): void
    {
        $start = $this->next(Carbon::MONDAY, 14);

        $this->postJson('/api/appointments', $this->payload($start, [
            'status' => AppointmentStatus::CANCELED->value,
        ]))->assertCreated();

        $this->assertDatabaseHas('appointments', [
            'status' => AppointmentStatus::CREATED->value,
        ]);
    }

    public function test_validation_errors_are_returned(): void
    {
        $this->postJson('/api/appointments', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'start_at',
            ]);

        $this->postJson('/api/appointments', $this->payload($this->next(Carbon::MONDAY, 14, 30)))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_at');
    }

    public function test_appointment_cannot_be_created_twice_for_the_same_hour(): void
    {
        $start = $this->next(Carbon::MONDAY, 14);

        $this->postJson('/api/appointments', $this->payload($start))
            ->assertCreated();

        $this->postJson('/api/appointments', $this->payload($start, ['email' => 'other@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_at');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_slots_mark_booked_hours_and_available_hours_separately(): void
    {
        $start = $this->next(Carbon::SUNDAY, 10);

        Appointment::create([
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'email' => 'ivan@example.com',
            'phone_number' => '+79991234567',
            'start_at' => $start,
            'status' => AppointmentStatus::CREATED,
        ]);

        $this->getJson('/api/appointments/slots?date='.$start->toDateString())
            ->assertOk()
            ->assertJsonPath('data.date', $start->toDateString())
            ->assertJsonFragment([
                'time' => '10:00',
                'is_booked' => true,
            ])
            ->assertJsonFragment([
                'time' => '11:00',
                'is_booked' => false,
            ]);
    }
}
