<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\AppointmentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                DateTimePicker::make('start_at')
                    ->label('Начало')
                    ->seconds(false)
                    ->required(),
                DateTimePicker::make('end_at')
                    ->label('Конец')
                    ->seconds(false)
                    ->required()
                    ->rules(['after:start_at']),
                Select::make('status')
                    ->label('Статус')
                    ->options(AppointmentStatus::class)
                    ->default(AppointmentStatus::CREATED)
                    ->required(),
            ]);
    }
}
