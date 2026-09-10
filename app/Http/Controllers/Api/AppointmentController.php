<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CreateAppointmentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
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
