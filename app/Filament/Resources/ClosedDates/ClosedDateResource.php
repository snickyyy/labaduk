<?php

namespace App\Filament\Resources\ClosedDates;

use App\Filament\Resources\ClosedDates\Pages\CreateClosedDate;
use App\Filament\Resources\ClosedDates\Pages\ListClosedDates;
use App\Filament\Resources\ClosedDates\Schemas\ClosedDateForm;
use App\Filament\Resources\ClosedDates\Tables\ClosedDatesTable;
use App\Models\ClosedDate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ClosedDateResource extends Resource
{
    protected static ?string $model = ClosedDate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDateRange;

    protected static string|UnitEnum|null $navigationGroup = 'Расписание';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Закрытая дата';

    protected static ?string $pluralModelLabel = 'Закрытые даты';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function form(Schema $schema): Schema
    {
        return ClosedDateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClosedDatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClosedDates::route('/'),
            'create' => CreateClosedDate::route('/create'),
        ];
    }
}
