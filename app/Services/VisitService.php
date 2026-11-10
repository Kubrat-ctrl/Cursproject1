<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\ScheduleStatus;
use App\Exceptions\BusinessException;
use App\Models\Visit;
use App\Models\WorkoutBooking;
use Illuminate\Support\Facades\Log;

class VisitService
{
    /** Отметка посещения тренером. */
    public function mark(WorkoutBooking $booking): Visit
    {
        $booking->loadMissing('scheduleItem');
        $item = $booking->scheduleItem;

        if ($booking->status !== BookingStatus::Booked) {
            throw BusinessException::conflict('Посещение можно отметить только для действующей записи');
        }
        if ($item->status === ScheduleStatus::Cancelled) {
            throw BusinessException::conflict('Занятие отменено');
        }
        if ($booking->visit()->exists()) {
            throw BusinessException::conflict('Посещение уже отмечено');
        }
        if ($item->starts_at->copy()->subMinutes(30)->isFuture()) {
            throw BusinessException::unprocessable('Отмечать посещение можно не раньше чем за 30 минут до начала занятия');
        }

        $visit = new Visit();
        $visit->workout_booking_id = $booking->id;
        $visit->checked_in_at = now();
        $visit->save();

        Log::info('Отмечено посещение', ['visit_id' => $visit->id, 'booking_id' => $booking->id]);

        return $visit->refresh();
    }
}
