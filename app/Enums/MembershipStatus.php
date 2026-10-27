<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
}
