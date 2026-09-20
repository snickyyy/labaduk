<?php

namespace Tests\Feature\Api;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrentAppointmentBookingTest extends TestCase
{
    use DatabaseMigrations;

    public function test_two_independent_requests_cannot_book_the_same_interval(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('This concurrency test requires PostgreSQL.');
        }

        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('This concurrency test requires the pcntl extension.');
        }

        $directory = sys_get_temp_dir().'/labaduk-concurrency-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700, true);
        $children = [];
        $startAt = CarbonImmutable::now(AppointmentService::TIMEZONE)
            ->addMonth()
            ->next('Monday')
            ->setTime(14, 0)
            ->toIso8601String();

        foreach ([1, 2] as $number) {
            $processId = pcntl_fork();

            if ($processId === -1) {
                $this->fail('Could not fork a request process.');
            }

            if ($processId === 0) {
                DB::purge();
                file_put_contents("{$directory}/ready-{$number}", 'ready');

                $deadline = microtime(true) + 10;
                while (! file_exists("{$directory}/go") && microtime(true) < $deadline) {
                    usleep(1_000);
                }

                $response = $this->postJson('/api/appointments', [
                    'first_name' => "Visitor {$number}",
                    'last_name' => 'Concurrent',
                    'email' => "visitor{$number}@example.com",
                    'phone_number' => "+4912345678{$number}",
                    'start_at' => $startAt,
                    'duration_minutes' => 60,
                ]);

                file_put_contents("{$directory}/status-{$number}", (string) $response->getStatusCode());
                exit(0);
            }

            $children[] = $processId;
        }

        $deadline = microtime(true) + 10;
        while (
            (! file_exists("{$directory}/ready-1") || ! file_exists("{$directory}/ready-2"))
            && microtime(true) < $deadline
        ) {
            usleep(1_000);
        }

        file_put_contents("{$directory}/go", 'go');

        foreach ($children as $processId) {
            pcntl_waitpid($processId, $status);
            $this->assertSame(0, pcntl_wexitstatus($status));
        }

        $statuses = [
            (int) file_get_contents("{$directory}/status-1"),
            (int) file_get_contents("{$directory}/status-2"),
        ];
        sort($statuses);

        DB::purge();
        DB::reconnect();

        $this->assertSame([201, 422], $statuses);
        $this->assertDatabaseCount('appointments', 1);

        foreach (glob("{$directory}/*") ?: [] as $file) {
            unlink($file);
        }
        rmdir($directory);
    }

    public function test_postgresql_constraint_rejects_overlaps_even_when_service_is_bypassed(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('This database constraint test requires PostgreSQL.');
        }

        $start = CarbonImmutable::now(AppointmentService::TIMEZONE)
            ->addMonth()
            ->next('Monday')
            ->setTime(14, 0)
            ->utc();

        $this->insertAppointment($start, $start->addHour(), AppointmentStatus::CREATED);

        try {
            $this->insertAppointment($start->addMinutes(30), $start->addMinutes(90), AppointmentStatus::CREATED);
            $this->fail('PostgreSQL accepted overlapping active appointments.');
        } catch (QueryException $exception) {
            $this->assertSame('23P01', $exception->errorInfo[0]);
        }

        $this->insertAppointment($start->addMinutes(30), $start->addMinutes(90), AppointmentStatus::CANCELED);
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_postgresql_constraint_treats_legacy_null_end_as_one_hour(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('This database constraint test requires PostgreSQL.');
        }

        $start = CarbonImmutable::now(AppointmentService::TIMEZONE)
            ->addMonth()
            ->next('Monday')
            ->setTime(14, 0)
            ->utc();

        $this->insertAppointment($start, null, AppointmentStatus::CREATED);

        try {
            $this->insertAppointment($start->addMinutes(30), $start->addHour(), AppointmentStatus::CREATED);
            $this->fail('PostgreSQL did not protect a legacy one-hour appointment.');
        } catch (QueryException $exception) {
            $this->assertSame('23P01', $exception->errorInfo[0]);
        }

        Appointment::query()->delete();
    }

    private function insertAppointment(
        CarbonImmutable $startAt,
        ?CarbonImmutable $endAt,
        AppointmentStatus $status,
    ): Appointment {
        return Appointment::create([
            'first_name' => 'Constraint',
            'last_name' => 'Test',
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => '+49123456789',
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => $status,
        ]);
    }
}
