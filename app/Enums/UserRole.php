<?php

namespace App\Enums;

enum UserRole: string
{
    case Client = 'client';
    case Trainer = 'trainer';
    case Admin = 'admin';
}
