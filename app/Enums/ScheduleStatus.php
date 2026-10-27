<?php

namespace App\Enums;

enum ScheduleStatus: string
{
    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';
}
