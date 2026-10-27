<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Booked = 'booked';
    case Cancelled = 'cancelled';
}
