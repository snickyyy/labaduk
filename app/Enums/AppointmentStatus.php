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

    /**
     * @return array<int, self>
     */
    public static function blocking(): array
    {
        return [self::CREATED, self::SUCCESSFUL, self::PIDOR];
    }

    /**
     * @return array<int, string>
     */
    public static function blockingValues(): array
    {
        return array_column(self::blocking(), 'value');
    }

    public function blocksTime(): bool
    {
        return in_array($this, self::blocking(), true);
    }

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
