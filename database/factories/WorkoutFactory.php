<?php

namespace Database\Factories;

use App\Enums\WorkoutLevel;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workout>
 */
class WorkoutFactory extends Factory
{
    protected $model = Workout::class;

    public function definition(): array
    {
        $faker = fake('ru_RU');

        return [
            'name' => $faker->unique()->randomElement([
                'Аквааэробика',
                'Боди-памп',
                'Бодибалет',
                'Здоровая спина',
                'Кардио-микс',
                'Силовая круговая',
                'Сайкл',
                'Табата',
                'Тайский бокс',
                'Хатха-йога',
            ]),
            'description' => $faker->sentence(8),
            'duration_minutes' => $faker->randomElement([45, 50, 55, 60]),
            'level' => $faker->randomElement(WorkoutLevel::cases()),
            'image_path' => null,
            'is_active' => true,
        ];
    }
}
