<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\ScheduleStatus;
use App\Models\Visit;
use App\Models\WorkoutBooking;

class VisitService
{
    /** Отметка посещения тренером. */
    public function mark(WorkoutBooking $booking): Visit
    {
        $booking->loadMissing('scheduleItem');
        $item = $booking->scheduleItem;

        if ($booking->status !== BookingStatus::Booked) {
            abort(409, 'Посещение можно отметить только для действующей записи.');
        }
        if ($item->status === ScheduleStatus::Cancelled) {
            abort(409, 'Занятие отменено.');
        }
        if ($booking->visit()->exists()) {
            abort(409, 'Посещение уже отмечено.');
        }
        if ($item->starts_at->copy()->subMinutes(30)->isFuture()) {
            abort(422, 'Отмечать посещение можно не раньше чем за 30 минут до начала занятия.');
        }

        $visit = new Visit();
        $visit->workout_booking_id = $booking->id;
        $visit->checked_in_at = now();
        $visit->save();

        return $visit->refresh();
    }
}
