<?php

namespace Database\Factories;

use App\Models\Visit;
use App\Models\WorkoutBooking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    protected $model = Visit::class;

    public function definition(): array
    {
        return [
            'workout_booking_id' => WorkoutBooking::factory(),
            'checked_in_at' => now(),
        ];
    }
}
