<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Visit;
use App\Models\WorkoutBooking;
use Illuminate\Database\Seeder;

class VisitSeeder extends Seeder
{
    public function run(): void
    {
        // Посещения отмечаются только по действующим записям на уже прошедшие занятия.
        $bookings = WorkoutBooking::with('scheduleItem')
            ->where('status', BookingStatus::Booked->value)
            ->whereHas('scheduleItem', fn ($query) => $query->where('starts_at', '<', now()))
            ->orderBy('id')
            ->get();

        foreach ($bookings as $index => $booking) {
            // Каждый четвёртый клиент не пришёл.
            if ($index % 4 === 3) {
                continue;
            }

            Visit::create([
                'workout_booking_id' => $booking->id,
                'checked_in_at' => $booking->scheduleItem->starts_at->copy()->addMinutes(3),
            ]);
        }
    }
}
