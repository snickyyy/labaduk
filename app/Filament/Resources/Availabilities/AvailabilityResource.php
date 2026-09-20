<?php

namespace App\Filament\Resources\Availabilities;

use App\Filament\Resources\Availabilities\Pages\ListAvailabilities;
use App\Filament\Resources\Availabilities\Schemas\AvailabilityForm;
use App\Filament\Resources\Availabilities\Tables\AvailabilitiesTable;
use App\Models\Availability;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AvailabilityResource extends Resource
{
    protected static ?string $model = Availability::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Расписание';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Часы работы';

    protected static ?string $pluralModelLabel = 'Часы работы';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function form(Schema $schema): Schema
    {
        return AvailabilityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AvailabilitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAvailabilities::route('/'),
        ];
    }
}
