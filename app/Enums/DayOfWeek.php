<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DayOfWeek: string implements HasLabel
{
    case MONDAY = 'MONDAY';
    case TUESDAY = 'TUESDAY';
    case WEDNESDAY = 'WEDNESDAY';
    case THURSDAY = 'THURSDAY';
    case FRIDAY = 'FRIDAY';
    case SATURDAY = 'SATURDAY';
    case SUNDAY = 'SUNDAY';

    public function getLabel(): string
    {
        return match ($this) {
            self::MONDAY => 'Понедельник',
            self::TUESDAY => 'Вторник',
            self::WEDNESDAY => 'Среда',
            self::THURSDAY => 'Четверг',
            self::FRIDAY => 'Пятница',
            self::SATURDAY => 'Суббота',
            self::SUNDAY => 'Воскресенье',
        };
    }
}
