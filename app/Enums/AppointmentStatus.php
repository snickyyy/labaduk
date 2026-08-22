<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AppointmentStatus: string implements HasColor, HasLabel
{
    case CREATED = 'CREATED';
    case SUCCESSFUL = 'SUCCESSFUL';
    case PIDOR = 'PIDOR';
    case RESCHEDULED = 'RESCHEDULED';
    case CANCELED = 'CANCELED';

    public function getLabel(): string
    {
        return match ($this) {
            self::CREATED => 'Создана',
            self::SUCCESSFUL => 'Успешна',
            self::PIDOR => 'PIDOR',
            self::RESCHEDULED => 'Перенесена',
            self::CANCELED => 'Отменена',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CREATED => 'warning',
            self::SUCCESSFUL => 'success',
            self::PIDOR => 'danger',
            self::RESCHEDULED => 'info',
            self::CANCELED => 'gray',
        };
    }
}
