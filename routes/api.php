<?php

use App\Http\Controllers\Api\AppointmentController;
use Illuminate\Support\Facades\Route;

Route::get('/appointments/slots', [AppointmentController::class, 'slots'])
    ->name('appointments.slots');

Route::post('/appointments', [AppointmentController::class, 'store'])
    ->name('appointments.store');
