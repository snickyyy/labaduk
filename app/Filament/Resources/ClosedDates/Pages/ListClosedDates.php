<?php

namespace App\Filament\Resources\ClosedDates\Pages;

use App\Filament\Resources\ClosedDates\ClosedDateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClosedDates extends ListRecords
{
    protected static string $resource = ClosedDateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
