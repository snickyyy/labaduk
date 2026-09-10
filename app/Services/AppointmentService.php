<?php

namespace App\Services;

use App\DTOs\CreateAppointmentDTO;
use App\Enums\AppointmentStatus;
use App\Enums\DayOfWeek;
use App\Models\Appointment;
use App\Models\Availability;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    /**
     * Создать запись клиента, если время попадает в окно из availabilities.
     *
     * Запись должна целиком находиться внутри одного окна доступности
     * соответствующего дня недели (границы окна включаются).
     *
     * @throws ValidationException
     */
    public function create(CreateAppointmentDTO $appointment): Appointment
    {
        $day = DayOfWeek::from(strtoupper($appointment->startAt->englishDayOfWeek));

        $windows = Availability::query()
            ->where('day_of_week', $day->value)
            ->get();

        $fitsWindow = $appointment->startAt->isSameDay($appointment->endAt)
            && $windows->contains(function (Availability $window) use ($appointment): bool {
                return $appointment->startAt->format('H:i') >= $window->start_time->format('H:i')
                    && $appointment->endAt->format('H:i') <= $window->end_time->format('H:i');
            });

        if (! $fitsWindow) {
            throw ValidationException::withMessages([
                'start_at' => __('Выбранное время недоступно для записи.'),
            ]);
        }

        return Appointment::create([
            ...$appointment->toArray(),
            'status' => AppointmentStatus::CREATED,
        ]);
    }
}
