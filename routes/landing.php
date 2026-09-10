<?php

use App\Http\Controllers\Landing\HomeController;
use App\Http\Middleware\SetLandingLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('landing.home', [
        'locale' => config('landing.default_locale'),
    ]);
})->name('home');

Route::prefix('{locale}')
    ->whereIn('locale', config('landing.supported_locales'))
    ->middleware(SetLandingLocale::class)
    ->name('landing.')
    ->group(function (): void {
        Route::get('/', HomeController::class)->name('home');
    });
