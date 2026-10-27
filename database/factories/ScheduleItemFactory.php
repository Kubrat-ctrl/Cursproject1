<?php

namespace Database\Factories;

use App\Enums\ScheduleStatus;
use App\Models\ScheduleItem;
use App\Models\User;
use App\Models\Workout;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleItem>
 */
class ScheduleItemFactory extends Factory
{
    protected $model = ScheduleItem::class;

    public function definition(): array
    {
        $faker = fake('ru_RU');
        $startsAt = Carbon::instance($faker->dateTimeBetween('+1 day', '+14 days'))
            ->setMinute(0)->setSecond(0);

        return [
            'workout_id' => Workout::factory(),
            'trainer_id' => User::factory()->trainer(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'room' => $faker->randomElement(['Зал 1', 'Зал 2', 'Зал 3']),
            'capacity' => $faker->numberBetween(8, 20),
            'status' => ScheduleStatus::Scheduled,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => ScheduleStatus::Cancelled]);
    }
}
