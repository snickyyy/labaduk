<?php

namespace App\Services;

use App\DTOs\CreateAppointmentDTO;
use App\Enums\AppointmentStatus;
use App\Enums\DayOfWeek;
use App\Models\Appointment;
use App\Models\Availability;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    private const SLOT_DURATION_IN_HOURS = 1;

    /**
     * Создать часовую запись клиента, если её начало попадает в окно из availabilities.
     *
     * Время начала должно приходиться на начало часа. Двое клиентов не могут
     * занять один и тот же слот — это также гарантируется уникальным индексом БД.
     *
     * @throws ValidationException
     */
    public function create(CreateAppointmentDTO $appointment): Appointment
    {
        if (! $this->isBookableStart($appointment->startAt)) {
            throw ValidationException::withMessages([
                'start_at' => __('Выбранное время недоступно для записи.'),
            ]);
        }

        if (! $this->isSlotAvailable($appointment->startAt)) {
            throw $this->slotAlreadyBooked();
        }

        try {
            return Appointment::create([
                ...$appointment->toArray(),
                'status' => AppointmentStatus::CREATED,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Между предварительной проверкой и INSERT другой клиент мог занять слот.
            throw $this->slotAlreadyBooked();
        }
    }

    /**
     * @return Collection<int, array{start_at: CarbonImmutable, is_booked: bool, is_past: bool}>
     */
    public function slotsForDate(CarbonImmutable $date): Collection
    {
        $date = $date->utc()->startOfDay();
        $day = DayOfWeek::from(strtoupper($date->englishDayOfWeek));
        $bookedStartTimes = Appointment::query()
            ->whereBetween('start_at', [$date, $date->endOfDay()])
            ->pluck('start_at')
            ->map(fn (string $startAt): string => CarbonImmutable::parse($startAt, 'UTC')->format('Y-m-d H:i:s'))
            ->flip();
        $now = CarbonImmutable::now('UTC');

        return Availability::query()
            ->where('day_of_week', $day->value)
            ->get()
            ->flatMap(function (Availability $window) use ($date, $bookedStartTimes, $now): array {
                $start = $date->setTimeFromTimeString($window->start_time->format('H:i'));
                $end = $date->setTimeFromTimeString($window->end_time->format('H:i'));
                $slots = [];

                while ($start->addHours(self::SLOT_DURATION_IN_HOURS)->lessThanOrEqualTo($end)) {
                    $slots[] = [
                        'start_at' => $start,
                        'is_booked' => $bookedStartTimes->has($start->format('Y-m-d H:i:s')),
                        'is_past' => $start->lessThanOrEqualTo($now),
                    ];
                    $start = $start->addHours(self::SLOT_DURATION_IN_HOURS);
                }

                return $slots;
            })
            ->unique(fn (array $slot): string => $slot['start_at']->toIso8601String())
            ->sortBy(fn (array $slot): CarbonImmutable => $slot['start_at'])
            ->values();
    }

    private function isBookableStart(CarbonImmutable $startAt): bool
    {
        if ($startAt->minute !== 0 || $startAt->second !== 0 || $startAt->lessThanOrEqualTo(CarbonImmutable::now('UTC'))) {
            return false;
        }

        $day = DayOfWeek::from(strtoupper($startAt->englishDayOfWeek));

        return Availability::query()
            ->where('day_of_week', $day->value)
            ->get()
            ->contains(function (Availability $window) use ($startAt): bool {
                $windowStart = $startAt->startOfDay()->setTimeFromTimeString($window->start_time->format('H:i'));
                $windowEnd = $startAt->startOfDay()->setTimeFromTimeString($window->end_time->format('H:i'));

                return $startAt->greaterThanOrEqualTo($windowStart)
                    && $startAt->addHours(self::SLOT_DURATION_IN_HOURS)->lessThanOrEqualTo($windowEnd);
            });
    }

    private function isSlotAvailable(CarbonImmutable $startAt): bool
    {
        return ! Appointment::query()
            ->where('start_at', $startAt->toDateTimeString())
            ->exists();
    }

    private function slotAlreadyBooked(): ValidationException
    {
        return ValidationException::withMessages([
            'start_at' => __('Это время уже занято. Выберите другой свободный час.'),
        ]);
    }
}
