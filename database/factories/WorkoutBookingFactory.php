<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Membership;
use App\Models\ScheduleItem;
use App\Models\User;
use App\Models\WorkoutBooking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkoutBooking>
 */
class WorkoutBookingFactory extends Factory
{
    protected $model = WorkoutBooking::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->client(),
            'schedule_item_id' => ScheduleItem::factory(),
            'membership_id' => Membership::factory(),
            'status' => BookingStatus::Booked,
            'cancelled_at' => null,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
