<?php

namespace App\Http\Resources;

use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Appointment
 */
class AppointmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $startAt = $this->start_at->setTimezone(AppointmentService::TIMEZONE);
        $endAt = $this->end_at?->setTimezone(AppointmentService::TIMEZONE);

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'start_at' => $startAt->toIso8601String(),
            'end_at' => $endAt?->toIso8601String(),
            'duration_minutes' => $endAt ? (int) $startAt->diffInMinutes($endAt) : null,
            'timezone' => AppointmentService::TIMEZONE,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
