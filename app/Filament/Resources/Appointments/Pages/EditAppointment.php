<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (! $record instanceof Appointment) {
            throw new \LogicException('The appointment resource received an unexpected model.');
        }

        $startAt = $record->start_at->setTimezone(AppointmentService::TIMEZONE);

        $data['appointment_date'] = $startAt->toDateString();
        $data['start_time'] = $startAt->format('H:i');
        $data['duration_minutes'] = app(AppointmentService::class)->durationMinutesFor($record);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Appointment) {
            throw new \LogicException('The appointment resource received an unexpected model.');
        }

        try {
            return app(AppointmentService::class)->updateFromAdmin($record, $data);
        } catch (ValidationException $exception) {
            $errors = collect($exception->errors())
                ->mapWithKeys(fn (array $messages, string $field): array => [
                    'data.'.($field === 'start_at' ? 'start_time' : $field) => $messages,
                ])
                ->all();

            throw ValidationException::withMessages($errors);
        }
    }
}
