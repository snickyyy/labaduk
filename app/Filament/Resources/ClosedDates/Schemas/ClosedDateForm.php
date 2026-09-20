<?php

namespace App\Filament\Resources\ClosedDates\Schemas;

use App\Services\AppointmentService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClosedDateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')
                ->label('Дата')
                ->native(false)
                ->minDate(now(AppointmentService::TIMEZONE)->toDateString())
                ->unique()
                ->required(),
            TextInput::make('reason')
                ->label('Причина')
                ->maxLength(255),
        ]);
    }
}
