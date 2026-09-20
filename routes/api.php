<?php

use App\Http\Controllers\Api\AppointmentController;
use Illuminate\Support\Facades\Route;

Route::get('/appointments/slots', [AppointmentController::class, 'slots'])
    ->middleware('throttle:appointments-read')
    ->name('appointments.slots');

Route::post('/appointments', [AppointmentController::class, 'store'])
    ->middleware('throttle:appointments-write')
    ->name('appointments.store');
