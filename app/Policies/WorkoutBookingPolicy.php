<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkoutBooking;

class WorkoutBookingPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /** Отмена записи: только своя. */
    public function delete(User $user, WorkoutBooking $booking): bool
    {
        return $booking->user_id === $user->id;
    }

    /** Отметка посещения: только на своём занятии. */
    public function markVisit(User $user, WorkoutBooking $booking): bool
    {
        return $booking->scheduleItem->trainer_id === $user->id;
    }
}