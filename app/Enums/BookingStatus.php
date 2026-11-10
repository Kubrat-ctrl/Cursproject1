<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Booked = 'booked';
    case Cancelled = 'cancelled';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Booked => [self::Cancelled],
            // Повторная запись на то же занятие использует прежнюю строку таблицы.
            self::Cancelled => [self::Booked],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }
}
