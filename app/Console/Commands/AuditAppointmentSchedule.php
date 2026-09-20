<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AuditAppointmentSchedule extends Command
{
    protected $signature = 'appointments:audit-schedule';

    protected $description = 'Report existing appointments that conflict with the current scheduling rules';

    public function handle(): int
    {
        /** @var Collection<int, Appointment> $appointments */
        $appointments = Appointment::query()->orderBy('start_at')->get();
        $issues = collect();

        foreach ($appointments as $appointment) {
            $start = CarbonImmutable::instance($appointment->start_at)->setTimezone(AppointmentService::TIMEZONE);
            $end = $appointment->end_at
                ? CarbonImmutable::instance($appointment->end_at)->setTimezone(AppointmentService::TIMEZONE)
                : $start->addHour();
            $duration = (int) $start->diffInMinutes($end, false);

            if (! $appointment->end_at) {
                $issues->push($this->issue($appointment, 'legacy_missing_end', 'end_at is NULL; treated as a one-hour interval'));
            } elseif ($end->lessThanOrEqualTo($start)) {
                $issues->push($this->issue($appointment, 'invalid_interval', 'end_at is not after start_at'));
            }

            if ($start->dayOfWeekIso > 5) {
                $issues->push($this->issue($appointment, 'weekend', 'appointment is on Saturday or Sunday'));
            }

            if (! in_array($duration, config('appointments.durations'), true)) {
                $issues->push($this->issue($appointment, 'duration', "duration is {$duration} minutes"));
            }

            $workdayStart = $start->startOfDay()->setTimeFromTimeString((string) config('appointments.workday_start'));
            $workdayEnd = $start->startOfDay()->setTimeFromTimeString((string) config('appointments.workday_end'));

            if ($start->lessThan($workdayStart) || $start->greaterThanOrEqualTo($workdayEnd) || $end->greaterThan($workdayEnd)) {
                $issues->push($this->issue($appointment, 'outside_hours', 'interval is outside 14:00–18:00 Europe/Berlin'));
            }
        }

        $blocking = $appointments
            ->filter(fn (Appointment $appointment): bool => in_array($appointment->status, AppointmentStatus::blocking(), true))
            ->values();

        foreach ($blocking as $index => $first) {
            $firstEnd = $first->end_at
                ? CarbonImmutable::instance($first->end_at)
                : CarbonImmutable::instance($first->start_at)->addHour();

            foreach ($blocking->slice($index + 1) as $second) {
                if (CarbonImmutable::instance($second->start_at)->greaterThanOrEqualTo($firstEnd)) {
                    break;
                }

                $issues->push($this->issue(
                    $first,
                    'active_overlap',
                    "overlaps active appointment {$second->id}",
                ));
            }
        }

        if ($issues->isEmpty()) {
            $this->info('No appointment schedule issues found.');

            return self::SUCCESS;
        }

        $this->table(['Appointment', 'Start (UTC)', 'Issue', 'Details'], $issues->all());
        $this->warn("Found {$issues->count()} issue(s). No records were changed.");

        return $issues->contains(fn (array $issue): bool => in_array($issue[2], ['invalid_interval', 'active_overlap'], true))
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function issue(Appointment $appointment, string $type, string $details): array
    {
        return [
            $appointment->id,
            $appointment->start_at->utc()->toDateTimeString(),
            $type,
            $details,
        ];
    }
}
