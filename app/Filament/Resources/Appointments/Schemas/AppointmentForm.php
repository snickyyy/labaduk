<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ClosedDate;
use App\Services\AppointmentService;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->label('Имя')
                    ->required(),
                TextInput::make('last_name')
                    ->label('Фамилия')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
                TextInput::make('phone_number')
                    ->label('Телефон')
                    ->tel()
                    ->required(),
                DatePicker::make('appointment_date')
                    ->label('Дата')
                    ->native(false)
                    ->minDate(now(AppointmentService::TIMEZONE)->toDateString())
                    ->disabledDates(fn (): array => ClosedDate::query()
                        ->orderBy('date')
                        ->pluck('date')
                        ->map(fn (string $date): string => CarbonImmutable::parse($date)->toDateString())
                        ->all())
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('start_time', null);
                        $set('duration_minutes', null);
                    })
                    ->required(),
                Select::make('start_time')
                    ->label('Начало')
                    ->options(fn (Get $get, ?Appointment $record): array => app(AppointmentService::class)->startTimeOptions(
                        $get('appointment_date'),
                        $record?->id,
                    ))
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('duration_minutes', null))
                    ->disabled(fn (Get $get): bool => blank($get('appointment_date')))
                    ->required(),
                Select::make('duration_minutes')
                    ->label('Длительность')
                    ->options(fn (Get $get, ?Appointment $record): array => app(AppointmentService::class)->durationOptions(
                        $get('appointment_date'),
                        $get('start_time'),
                        $record?->id,
                    ))
                    ->live()
                    ->disabled(fn (Get $get): bool => blank($get('start_time')))
                    ->required(),
                Placeholder::make('calculated_end_at')
                    ->label('Окончание')
                    ->content(function (Get $get): string {
                        $date = $get('appointment_date');
                        $time = $get('start_time');
                        $duration = (int) $get('duration_minutes');

                        if (! $date || ! $time || ! $duration) {
                            return 'Выберите дату, начало и длительность';
                        }

                        return CarbonImmutable::createFromFormat(
                            '!Y-m-d H:i',
                            "{$date} {$time}",
                            AppointmentService::TIMEZONE,
                        )->addMinutes($duration)->format('d.m.Y H:i');
                    }),
                Select::make('status')
                    ->label('Статус')
                    ->options(AppointmentStatus::class)
                    ->default(AppointmentStatus::CREATED)
                    ->required(),
            ]);
    }
}
