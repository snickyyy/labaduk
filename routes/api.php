<?php

use App\Http\Controllers\Api\AppointmentController;
use Illuminate\Support\Facades\Route;

Route::post('/appointments', [AppointmentController::class, 'store'])
    ->name('appointments.store');
