<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case CREATED = 'CREATED';
    case SUCCESSFUL = 'SUCCESSFUL';
    case PIDOR = 'PIDOR';
    case RESCHEDULED = 'RESCHEDULED';
    case CANCELED = 'CANCELED';
}
