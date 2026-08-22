<?php

namespace App\Filament\Resources\Availabilities\Schemas;

use App\Enums\DayOfWeek;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class AvailabilityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('day_of_week')
                    ->label('День недели')
                    ->options(DayOfWeek::class)
                    ->required(),
                TimePicker::make('start_time')
                    ->label('Начало')
                    ->required(),
                TimePicker::make('end_time')
                    ->label('Конец')
                    ->required()
                    ->rules(['after:start_time']),
            ]);
    }
}
