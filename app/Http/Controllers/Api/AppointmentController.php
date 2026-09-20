<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CreateAppointmentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Services\AppointmentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function slots(Request $request, AppointmentService $service): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $validated['date'], 'UTC');

        return response()->json([
            'data' => [
                'date' => $date->toDateString(),
                'slots' => $service->slotsForDate($date)
                    ->map(fn (array $slot): array => [
                        'start_at' => $slot['start_at']->toIso8601String(),
                        'time' => $slot['start_at']->format('H:i'),
                        'is_booked' => $slot['is_booked'],
                        'is_past' => $slot['is_past'],
                    ]),
            ],
        ]);
    }

    public function store(StoreAppointmentRequest $request, AppointmentService $service): JsonResponse
    {
        $appointment = $service->create(
            CreateAppointmentDTO::fromRequest($request),
        );

        return AppointmentResource::make($appointment)
            ->response()
            ->setStatusCode(201);
    }
}
