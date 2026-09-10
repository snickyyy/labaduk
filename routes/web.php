<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/landing.php';

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
