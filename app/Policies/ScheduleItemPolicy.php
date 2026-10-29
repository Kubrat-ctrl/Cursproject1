<?php

namespace App\Policies;

use App\Models\ScheduleItem;
use App\Models\User;

class ScheduleItemPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /** Список записавшихся: только на своём занятии. */
    public function viewBookings(User $user, ScheduleItem $scheduleItem): bool
    {
        return $scheduleItem->trainer_id === $user->id;
    }
}