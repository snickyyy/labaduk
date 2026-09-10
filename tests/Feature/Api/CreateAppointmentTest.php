<?php

namespace Tests\Feature\Api;

use App\Enums\AppointmentStatus;
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

    private function payload(CarbonImmutable $start, CarbonImmutable $end, array $overrides = []): array
    {
        return [
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'email' => 'ivan@example.com',
            'phone_number' => '+79991234567',
            'start_at' => $start->toIso8601String(),
            'end_at' => $end->toIso8601String(),
            ...$overrides,
        ];
    }

    public function test_appointment_can_be_created_within_availability_window(): void
    {
        $start = $this->next(Carbon::MONDAY, 14);
        $end = $start->addHour();

        $response = $this->postJson('/api/appointments', $this->payload($start, $end));

        $response->assertCreated()
            ->assertJsonPath('data.first_name', 'Иван')
            ->assertJsonPath('data.status', AppointmentStatus::CREATED->value)
            ->assertJsonPath('data.start_at', $start->toIso8601String());

        $this->assertDatabaseHas('appointments', [
            'email' => 'ivan@example.com',
            'start_at' => $start->format('Y-m-d H:i:s'),
            'end_at' => $end->format('Y-m-d H:i:s'),
            'status' => AppointmentStatus::CREATED->value,
        ]);
    }

    public function test_appointment_cannot_be_created_before_availability_window(): void
    {
        $start = $this->next(Carbon::MONDAY, 10);
        $end = $start->addHour();

        $response = $this->postJson('/api/appointments', $this->payload($start, $end));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('start_at');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_appointment_cannot_be_created_after_availability_window(): void
    {
        $start = $this->next(Carbon::WEDNESDAY, 21);
        $end = $start->addHours(2);

        $this->postJson('/api/appointments', $this->payload($start, $end))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_at');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_appointment_can_be_created_exactly_on_window_boundaries(): void
    {
        $start = $this->next(Carbon::FRIDAY, 13);
        $end = $this->next(Carbon::FRIDAY, 22);

        $this->postJson('/api/appointments', $this->payload($start, $end))
            ->assertCreated();
    }

    public function test_appointment_can_be_created_on_weekend_window(): void
    {
        $start = $this->next(Carbon::SUNDAY, 10);
        $end = $start->addHour();

        $this->postJson('/api/appointments', $this->payload($start, $end))
            ->assertCreated();
    }

    public function test_appointment_cannot_cross_midnight(): void
    {
        $start = $this->next(Carbon::MONDAY, 23, 30);
        $end = $start->addHours(2);

        $this->postJson('/api/appointments', $this->payload($start, $end))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_at');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_input_with_timezone_offset_is_converted_to_utc(): void
    {
        $start = $this->next(Carbon::TUESDAY, 16)->setTimezone('Europe/Moscow');
        $end = $start->addHour();

        $this->postJson('/api/appointments', [
            'first_name' => 'Пётр',
            'last_name' => 'Петров',
            'email' => 'petr@example.com',
            'phone_number' => '+79997654321',
            'start_at' => $start->toIso8601String(),
            'end_at' => $end->toIso8601String(),
        ])->assertCreated();

        $this->assertDatabaseHas('appointments', [
            'start_at' => $start->utc()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_status_cannot_be_set_by_client(): void
    {
        $start = $this->next(Carbon::MONDAY, 14);
        $end = $start->addHour();

        $this->postJson('/api/appointments', $this->payload($start, $end, [
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
                'end_at',
            ]);

        $start = $this->next(Carbon::MONDAY, 14);

        $this->postJson('/api/appointments', $this->payload($start, $start->subHour()))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('end_at');
    }
}
