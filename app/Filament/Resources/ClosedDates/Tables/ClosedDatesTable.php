<?php

namespace App\Filament\Resources\ClosedDates\Tables;

use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClosedDatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Причина')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Добавлено')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Открыть дату'),
            ])
            ->defaultSort('date');
    }
}
