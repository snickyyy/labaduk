<?php

namespace App\Filament\Resources\ClosedDates\Pages;

use App\Filament\Resources\ClosedDates\ClosedDateResource;
use App\Services\AppointmentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateClosedDate extends CreateRecord
{
    protected static string $resource = ClosedDateResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(AppointmentService::class)->createClosedDate($data);
        } catch (ValidationException $exception) {
            $errors = collect($exception->errors())
                ->mapWithKeys(fn (array $messages, string $field): array => ["data.{$field}" => $messages])
                ->all();

            throw ValidationException::withMessages($errors);
        }
    }
}
