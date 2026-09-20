<?php

namespace App\Filament\Resources\Availabilities\Tables;

use App\Enums\DayOfWeek;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AvailabilitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('day_of_week')
                    ->label('День недели')
                    ->badge(),
                TextColumn::make('start_time')
                    ->label('Начало')
                    ->time('H:i'),
                TextColumn::make('end_time')
                    ->label('Конец')
                    ->time('H:i'),
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('day_of_week')
                    ->label('День недели')
                    ->options(collect([
                        DayOfWeek::MONDAY,
                        DayOfWeek::TUESDAY,
                        DayOfWeek::WEDNESDAY,
                        DayOfWeek::THURSDAY,
                        DayOfWeek::FRIDAY,
                    ])->mapWithKeys(fn (DayOfWeek $day): array => [
                        $day->value => $day->getLabel(),
                    ])->all()),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
