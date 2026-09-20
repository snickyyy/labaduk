<?php

namespace App\Services;

use App\DTOs\CreateAppointmentDTO;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ClosedDate;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public const TIMEZONE = 'Europe/Berlin';

    /**
     * Create a public appointment. Client-supplied end times are deliberately ignored.
     *
     * @throws ValidationException
     */
    public function create(CreateAppointmentDTO $appointment): Appointment
    {
        return $this->persist(
            attributes: $appointment->toArray(),
            durationMinutes: $appointment->durationMinutes,
            status: AppointmentStatus::CREATED,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function createFromAdmin(array $data): Appointment
    {
        return $this->persist(
            attributes: $this->customerAttributes($data),
            durationMinutes: (int) ($data['duration_minutes'] ?? 0),
            status: $this->statusFrom($data['status'] ?? AppointmentStatus::CREATED),
            startAt: $this->adminStartAt($data),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function updateFromAdmin(Appointment $appointment, array $data): Appointment
    {
        return $this->persist(
            attributes: $this->customerAttributes($data),
            durationMinutes: (int) ($data['duration_minutes'] ?? 0),
            status: $this->statusFrom($data['status'] ?? $appointment->status),
            startAt: $this->adminStartAt($data),
            existing: $appointment,
        );
    }

    public function cancel(Appointment $appointment): Appointment
    {
        $appointment->status = AppointmentStatus::CANCELED;
        $appointment->save();

        return $appointment->refresh();
    }

    /**
     * Return all half-hour starts for a working day. A start is available when its
     * durations array is non-empty; unavailable starts remain present for the UI.
     *
     * @return Collection<int, array{start_at: CarbonImmutable, time: string, durations: list<int>, is_available: bool, is_past: bool}>
     */
    public function slotsForDate(CarbonImmutable $date, ?string $ignoreAppointmentId = null): Collection
    {
        $date = $date->setTimezone(self::TIMEZONE)->startOfDay();

        if (! $this->isWorkingDay($date)) {
            return collect();
        }

        $closed = $this->isClosedDate($date);
        $now = CarbonImmutable::now(self::TIMEZONE);
        $start = $date->setTimeFromTimeString((string) config('appointments.workday_start'));
        $workdayEnd = $date->setTimeFromTimeString((string) config('appointments.workday_end'));
        $step = (int) config('appointments.slot_minutes');
        $appointments = $this->blockingAppointmentsForWindow(
            $start->utc(),
            $workdayEnd->utc(),
            $ignoreAppointmentId,
        );
        $slots = [];

        while ($start->lessThan($workdayEnd)) {
            $isPast = $start->lessThanOrEqualTo($now);
            $durations = [];

            if (! $closed && ! $isPast) {
                foreach ($this->allowedDurations() as $duration) {
                    $end = $start->addMinutes($duration);

                    if ($end->greaterThan($workdayEnd)) {
                        continue;
                    }

                    if (! $this->collectionHasOverlap($appointments, $start->utc(), $end->utc())) {
                        $durations[] = $duration;
                    }
                }
            }

            $slots[] = [
                'start_at' => $start,
                'time' => $start->format('H:i'),
                'durations' => $durations,
                'is_available' => $durations !== [],
                'is_past' => $isPast,
            ];

            $start = $start->addMinutes($step);
        }

        return collect($slots);
    }

    /**
     * @return array<string, string>
     */
    public function startTimeOptions(?string $date, ?string $ignoreAppointmentId = null): array
    {
        $localDate = $this->parseDate($date);

        if (! $localDate) {
            return [];
        }

        return $this->slotsForDate($localDate, $ignoreAppointmentId)
            ->filter(fn (array $slot): bool => $slot['is_available'])
            ->mapWithKeys(fn (array $slot): array => [$slot['time'] => $slot['time']])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function durationOptions(?string $date, ?string $time, ?string $ignoreAppointmentId = null): array
    {
        if (! $time) {
            return [];
        }

        $localDate = $this->parseDate($date);

        if (! $localDate) {
            return [];
        }

        $slot = $this->slotsForDate($localDate, $ignoreAppointmentId)
            ->firstWhere('time', $time);

        if (! $slot) {
            return [];
        }

        return collect($slot['durations'])
            ->mapWithKeys(fn (int $duration): array => [$duration => $this->durationLabel($duration)])
            ->all();
    }

    public function durationMinutesFor(Appointment $appointment): int
    {
        if (! $appointment->end_at) {
            return 60;
        }

        return (int) $appointment->start_at->diffInMinutes($appointment->end_at);
    }

    public function isClosedDate(CarbonInterface|string $date): bool
    {
        $dateString = $date instanceof CarbonInterface
            ? $date->setTimezone(self::TIMEZONE)->toDateString()
            : $date;

        return ClosedDate::query()->whereDate('date', $dateString)->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function createClosedDate(array $data): ClosedDate
    {
        $date = $this->parseDate((string) ($data['date'] ?? ''));

        if (! $date) {
            throw ValidationException::withMessages(['date' => __('Введите корректную дату.')]);
        }

        try {
            return DB::transaction(function () use ($date, $data): ClosedDate {
                $this->lockSchedulingDate($date->utc());
                $start = $date->startOfDay()->utc();
                $end = $date->addDay()->startOfDay()->utc();
                $activeCount = $this->blockingAppointmentsForWindow($start, $end, null)->count();

                if ($activeCount > 0) {
                    throw ValidationException::withMessages([
                        'date' => __('На эту дату уже есть активные записи (:count). Сначала отмените или перенесите их.', [
                            'count' => $activeCount,
                        ]),
                    ]);
                }

                return ClosedDate::create([
                    'date' => $date->toDateString(),
                    'reason' => $data['reason'] ?? null,
                ]);
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((string) ($exception->errorInfo[0] ?? $exception->getCode()), ['23505', '23000'], true)) {
                throw ValidationException::withMessages([
                    'date' => __('Эта дата уже закрыта.'),
                ]);
            }

            throw $exception;
        }
    }

    public function durationLabel(int $duration): string
    {
        return match ($duration) {
            30 => '30 минут',
            60 => '1 час',
            90 => '1 час 30 минут',
            120 => '2 часа',
            default => (string) $duration,
        };
    }

    /**
     * @return array<int, int>
     */
    private function allowedDurations(): array
    {
        return array_map('intval', (array) config('appointments.durations'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    private function persist(
        array $attributes,
        int $durationMinutes,
        AppointmentStatus $status,
        ?CarbonImmutable $startAt = null,
        ?Appointment $existing = null,
    ): Appointment {
        $startAt ??= CarbonImmutable::instance($attributes['start_at'])->utc();
        unset($attributes['start_at'], $attributes['duration_minutes']);
        $endAt = $startAt->addMinutes($durationMinutes);

        $this->assertBookableInterval($startAt, $endAt, $durationMinutes);

        try {
            return DB::transaction(function () use ($attributes, $durationMinutes, $status, $startAt, $endAt, $existing): Appointment {
                $this->lockSchedulingDate($startAt);
                $this->assertBookableInterval($startAt, $endAt, $durationMinutes);

                if ($status->blocksTime() && $this->hasOverlap($startAt, $endAt, $existing?->id)) {
                    throw $this->slotAlreadyBooked();
                }

                $values = [
                    ...$attributes,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'status' => $status,
                ];

                if ($existing) {
                    $existing->fill($values)->save();

                    return $existing->refresh();
                }

                return Appointment::create($values);
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((string) ($exception->errorInfo[0] ?? $exception->getCode()), ['23P01', '23505'], true)) {
                throw $this->slotAlreadyBooked();
            }

            throw $exception;
        }
    }

    /**
     * @throws ValidationException
     */
    private function assertBookableInterval(CarbonImmutable $startAt, CarbonImmutable $endAt, int $durationMinutes): void
    {
        $localStart = $startAt->setTimezone(self::TIMEZONE);
        $localEnd = $endAt->setTimezone(self::TIMEZONE);
        $workdayStart = $localStart->startOfDay()->setTimeFromTimeString((string) config('appointments.workday_start'));
        $workdayEnd = $localStart->startOfDay()->setTimeFromTimeString((string) config('appointments.workday_end'));

        if (! in_array($durationMinutes, $this->allowedDurations(), true)) {
            throw ValidationException::withMessages([
                'duration_minutes' => __('Выберите допустимую длительность.'),
            ]);
        }

        if (
            ! $this->isWorkingDay($localStart)
            || $localStart->minute % (int) config('appointments.slot_minutes') !== 0
            || $localStart->second !== 0
            || $localStart->micro !== 0
            || $localStart->lessThan($workdayStart)
            || $localStart->greaterThanOrEqualTo($workdayEnd)
            || $localEnd->greaterThan($workdayEnd)
            || ! $localEnd->isSameDay($localStart)
        ) {
            throw ValidationException::withMessages([
                'start_at' => __('Выбранный интервал не входит в рабочее время.'),
            ]);
        }

        if ($localStart->lessThanOrEqualTo(CarbonImmutable::now(self::TIMEZONE))) {
            throw ValidationException::withMessages([
                'start_at' => __('Нельзя забронировать прошедшее время.'),
            ]);
        }

        if ($this->isClosedDate($localStart)) {
            throw ValidationException::withMessages([
                'start_at' => __('Выбранная дата закрыта для бронирования.'),
            ]);
        }
    }

    private function hasOverlap(CarbonImmutable $startAt, CarbonImmutable $endAt, ?string $ignoreAppointmentId): bool
    {
        return $this->blockingAppointmentsForWindow($startAt, $endAt, $ignoreAppointmentId)
            ->contains(fn (Appointment $appointment): bool => $this->effectiveEndAt($appointment)->greaterThan($startAt));
    }

    /**
     * @return Collection<int, Appointment>
     */
    private function blockingAppointmentsForWindow(
        CarbonImmutable $startAt,
        CarbonImmutable $endAt,
        ?string $ignoreAppointmentId,
    ): Collection {
        return Appointment::query()
            ->whereIn('status', AppointmentStatus::blockingValues())
            ->where('start_at', '<', $endAt)
            ->where(function (Builder $query) use ($startAt): void {
                $query->where('end_at', '>', $startAt)
                    ->orWhere(function (Builder $query) use ($startAt): void {
                        $query->whereNull('end_at')
                            ->where('start_at', '>', $startAt->subHour());
                    });
            })
            ->when($ignoreAppointmentId, fn (Builder $query) => $query->whereKeyNot($ignoreAppointmentId))
            ->get();
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     */
    private function collectionHasOverlap(Collection $appointments, CarbonImmutable $startAt, CarbonImmutable $endAt): bool
    {
        return $appointments->contains(fn (Appointment $appointment): bool => CarbonImmutable::instance($appointment->start_at)->lessThan($endAt)
            && $this->effectiveEndAt($appointment)->greaterThan($startAt)
        );
    }

    private function effectiveEndAt(Appointment $appointment): CarbonImmutable
    {
        return $appointment->end_at
            ? CarbonImmutable::instance($appointment->end_at)
            : CarbonImmutable::instance($appointment->start_at)->addHour();
    }

    private function isWorkingDay(CarbonInterface $date): bool
    {
        return $date->dayOfWeekIso >= 1 && $date->dayOfWeekIso <= 5;
    }

    private function lockSchedulingDate(CarbonImmutable $startAt): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::select('select pg_advisory_xact_lock(hashtext(?))', [
            'appointments:'.$startAt->setTimezone(self::TIMEZONE)->toDateString(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function customerAttributes(array $data): array
    {
        return collect($data)->only([
            'first_name',
            'last_name',
            'email',
            'phone_number',
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function adminStartAt(array $data): CarbonImmutable
    {
        $date = $data['appointment_date'] ?? null;
        $time = $data['start_time'] ?? null;

        if (! is_string($date) || ! is_string($time)) {
            throw ValidationException::withMessages([
                'appointment_date' => __('Выберите дату и время.'),
            ]);
        }

        try {
            $startAt = CarbonImmutable::createFromFormat('!Y-m-d H:i', "{$date} {$time}", self::TIMEZONE);
        } catch (\Throwable) {
            $startAt = null;
        }

        if (! $startAt) {
            throw ValidationException::withMessages([
                'appointment_date' => __('Выберите корректную дату и время.'),
            ]);
        }

        return $startAt->utc();
    }

    private function statusFrom(AppointmentStatus|string $status): AppointmentStatus
    {
        return $status instanceof AppointmentStatus ? $status : AppointmentStatus::from($status);
    }

    private function parseDate(?string $date): ?CarbonImmutable
    {
        if (! $date) {
            return null;
        }

        try {
            $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $date, self::TIMEZONE);

            return $parsed && $parsed->toDateString() === $date ? $parsed : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function slotAlreadyBooked(): ValidationException
    {
        return ValidationException::withMessages([
            'start_at' => __('Выбранное время уже занято. Обновите доступность и выберите другой интервал.'),
        ]);
    }
}
