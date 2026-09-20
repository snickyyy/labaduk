<?php

namespace Tests\Feature\Api;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ClosedDate;
use App\Services\AppointmentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateAppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-14 10:00:00', AppointmentService::TIMEZONE));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    private function at(string $dateTime): CarbonImmutable
    {
        return CarbonImmutable::parse($dateTime, AppointmentService::TIMEZONE);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(CarbonImmutable $start, array $overrides = []): array
    {
        return [
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'email' => 'ivan@example.com',
            'phone_number' => '+49123456789',
            'start_at' => $start->toIso8601String(),
            'duration_minutes' => 30,
            ...$overrides,
        ];
    }

    private function existing(
        string $start,
        string $end,
        AppointmentStatus $status = AppointmentStatus::CREATED,
    ): Appointment {
        return Appointment::create([
            'first_name' => 'Existing',
            'last_name' => 'Visitor',
            'email' => 'existing@example.com',
            'phone_number' => '+49000000000',
            'start_at' => $this->at($start)->utc(),
            'end_at' => $this->at($end)->utc(),
            'status' => $status,
        ]);
    }

    public function test_appointment_is_created_with_server_calculated_end_in_utc(): void
    {
        $start = $this->at('2026-09-21 14:30');

        $this->postJson('/api/appointments', $this->payload($start, [
            'duration_minutes' => '90',
            'end_at' => $this->at('2026-09-21 17:59')->toIso8601String(),
        ]))
            ->assertCreated()
            ->assertJsonPath('data.status', AppointmentStatus::CREATED->value)
            ->assertJsonPath('data.start_at', $start->toIso8601String())
            ->assertJsonPath('data.end_at', $start->addMinutes(90)->toIso8601String())
            ->assertJsonPath('data.duration_minutes', 90)
            ->assertJsonPath('data.timezone', AppointmentService::TIMEZONE);

        $this->assertDatabaseHas('appointments', [
            'start_at' => $start->utc()->format('Y-m-d H:i:s'),
            'end_at' => $start->addMinutes(90)->utc()->format('Y-m-d H:i:s'),
        ]);
    }

    #[DataProvider('workingDayProvider')]
    public function test_only_weekdays_are_bookable(string $dateTime, bool $allowed): void
    {
        $response = $this->postJson('/api/appointments', $this->payload($this->at($dateTime)));

        $allowed
            ? $response->assertCreated()
            : $response->assertUnprocessable()->assertJsonValidationErrors('start_at');
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function workingDayProvider(): array
    {
        return [
            'Monday' => ['2026-09-21 14:00', true],
            'Friday' => ['2026-09-25 14:00', true],
            'Saturday' => ['2026-09-26 14:00', false],
            'Sunday' => ['2026-09-27 14:00', false],
        ];
    }

    #[DataProvider('startTimeProvider')]
    public function test_start_time_must_be_inside_hours_and_on_a_half_hour(string $time, int $duration, bool $allowed): void
    {
        $response = $this->postJson('/api/appointments', $this->payload(
            $this->at("2026-09-21 {$time}"),
            ['duration_minutes' => $duration],
        ));

        $allowed
            ? $response->assertCreated()
            : $response->assertUnprocessable()->assertJsonValidationErrors('start_at');
    }

    /**
     * @return array<string, array{string, int, bool}>
     */
    public static function startTimeProvider(): array
    {
        return [
            'before opening' => ['13:30', 30, false],
            'opening' => ['14:00', 120, true],
            'not aligned 14:10' => ['14:10', 30, false],
            'not aligned 15:45' => ['15:45', 30, false],
            'last half hour' => ['17:30', 30, true],
            'closing is not a start' => ['18:00', 30, false],
            'runs past closing' => ['16:30', 120, false],
            'last hour runs past closing' => ['17:30', 60, false],
        ];
    }

    #[DataProvider('durationProvider')]
    public function test_only_configured_durations_are_accepted(int $duration, bool $allowed): void
    {
        $response = $this->postJson('/api/appointments', $this->payload(
            $this->at('2026-09-21 14:00'),
            ['duration_minutes' => $duration],
        ));

        $allowed
            ? $response->assertCreated()
            : $response->assertUnprocessable()->assertJsonValidationErrors('duration_minutes');
    }

    /**
     * @return array<string, array{int, bool}>
     */
    public static function durationProvider(): array
    {
        return [
            '30 minutes' => [30, true],
            '60 minutes' => [60, true],
            '90 minutes' => [90, true],
            '120 minutes' => [120, true],
            'zero' => [0, false],
            '15 minutes' => [15, false],
            '45 minutes' => [45, false],
            '150 minutes' => [150, false],
            'negative' => [-30, false],
        ];
    }

    #[DataProvider('overlapProvider')]
    public function test_half_open_interval_overlap_rules(string $start, int $duration, bool $allowed): void
    {
        $this->existing('2026-09-21 15:00', '2026-09-21 16:30');

        $response = $this->postJson('/api/appointments', $this->payload(
            $this->at($start),
            ['duration_minutes' => $duration],
        ));

        $allowed
            ? $response->assertCreated()
            : $response->assertUnprocessable()->assertJsonValidationErrors('start_at');
    }

    /**
     * @return array<string, array{string, int, bool}>
     */
    public static function overlapProvider(): array
    {
        return [
            'ends at existing start' => ['2026-09-21 14:00', 60, true],
            'overlaps from before' => ['2026-09-21 14:30', 60, false],
            'same start' => ['2026-09-21 15:00', 60, false],
            'inside existing' => ['2026-09-21 15:30', 30, false],
            'overlaps existing end' => ['2026-09-21 16:00', 60, false],
            'starts at existing end' => ['2026-09-21 16:30', 90, true],
            'contains existing' => ['2026-09-21 14:30', 120, false],
        ];
    }

    #[DataProvider('nonBlockingStatusProvider')]
    public function test_released_appointments_do_not_block_time(AppointmentStatus $status): void
    {
        $this->existing('2026-09-21 15:00', '2026-09-21 16:00', $status);

        $this->postJson('/api/appointments', $this->payload(
            $this->at('2026-09-21 15:00'),
            ['duration_minutes' => 60],
        ))->assertCreated();
    }

    /**
     * @return array<string, array{AppointmentStatus}>
     */
    public static function nonBlockingStatusProvider(): array
    {
        return [
            'canceled' => [AppointmentStatus::CANCELED],
            'rescheduled' => [AppointmentStatus::RESCHEDULED],
        ];
    }

    #[DataProvider('blockingStatusProvider')]
    public function test_all_active_statuses_block_time(AppointmentStatus $status): void
    {
        $this->existing('2026-09-21 15:00', '2026-09-21 16:00', $status);

        $this->postJson('/api/appointments', $this->payload(
            $this->at('2026-09-21 15:30'),
        ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_at');
    }

    /**
     * @return array<string, array{AppointmentStatus}>
     */
    public static function blockingStatusProvider(): array
    {
        return [
            'created' => [AppointmentStatus::CREATED],
            'successful' => [AppointmentStatus::SUCCESSFUL],
            'other active status' => [AppointmentStatus::PIDOR],
        ];
    }

    public function test_closed_date_is_not_bookable(): void
    {
        ClosedDate::create(['date' => '2026-09-21', 'reason' => 'Holiday']);

        $this->postJson('/api/appointments', $this->payload($this->at('2026-09-21 14:00')))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_at');
    }

    public function test_past_start_is_not_bookable_using_server_time(): void
    {
        CarbonImmutable::setTestNow($this->at('2026-09-21 15:10'));

        $this->postJson('/api/appointments', $this->payload($this->at('2026-09-21 15:00')))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_at');

        $this->postJson('/api/appointments', $this->payload($this->at('2026-09-21 15:30')))
            ->assertCreated();
    }

    public function test_availability_returns_durations_without_personal_data(): void
    {
        $this->existing('2026-09-21 15:00', '2026-09-21 16:30');

        $response = $this->getJson('/api/appointments/slots?date=2026-09-21')
            ->assertOk()
            ->assertJsonPath('data.date', '2026-09-21')
            ->assertJsonPath('data.timezone', AppointmentService::TIMEZONE)
            ->assertJsonPath('data.is_closed', false);

        $slots = collect($response->json('data.slots'))->keyBy('time');

        $this->assertSame([30, 60], $slots['14:00']['durations']);
        $this->assertSame([30], $slots['14:30']['durations']);
        $this->assertSame([], $slots['15:00']['durations']);
        $this->assertSame([30, 60, 90], $slots['16:30']['durations']);
        $this->assertSame([30], $slots['17:30']['durations']);
        $response->assertJsonMissing(['email' => 'existing@example.com']);
    }

    public function test_weekend_and_closed_date_availability_have_no_bookable_slots(): void
    {
        ClosedDate::create(['date' => '2026-09-21']);

        $this->getJson('/api/appointments/slots?date=2026-09-21')
            ->assertOk()
            ->assertJsonPath('data.is_closed', true)
            ->assertJsonCount(8, 'data.slots')
            ->assertJsonPath('data.slots.0.durations', []);

        $this->getJson('/api/appointments/slots?date=2026-09-26')
            ->assertOk()
            ->assertJsonCount(0, 'data.slots');
    }

    #[DataProvider('dstProvider')]
    public function test_berlin_timezone_is_converted_correctly_across_dst(string $local, string $storedUtc): void
    {
        $start = $this->at($local);

        $this->postJson('/api/appointments', $this->payload($start))->assertCreated();

        $this->assertDatabaseHas('appointments', ['start_at' => $storedUtc]);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function dstProvider(): array
    {
        return [
            'summer time after spring transition' => ['2027-03-29 14:00', '2027-03-29 12:00:00'],
            'winter time after autumn transition' => ['2027-11-01 14:00', '2027-11-01 13:00:00'],
        ];
    }

    public function test_status_cannot_be_set_by_public_client(): void
    {
        $this->postJson('/api/appointments', $this->payload($this->at('2026-09-21 14:00'), [
            'status' => AppointmentStatus::CANCELED->value,
        ]))->assertCreated();

        $this->assertDatabaseHas('appointments', ['status' => AppointmentStatus::CREATED->value]);
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
                'duration_minutes',
            ]);
    }

    public function test_appointment_creation_is_rate_limited(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/appointments', [])->assertUnprocessable();
        }

        $this->postJson('/api/appointments', [])
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }
}
