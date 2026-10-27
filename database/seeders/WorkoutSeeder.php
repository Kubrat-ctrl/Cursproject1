<?php

namespace Database\Seeders;

use App\Enums\WorkoutLevel;
use App\Models\Workout;
use Illuminate\Database\Seeder;

class WorkoutSeeder extends Seeder
{
    public function run(): void
    {
        $workouts = [
            ['Йога', 'Мягкая практика на гибкость и дыхание', 60, WorkoutLevel::Beginner],
            ['Пилатес', 'Укрепление мышц кора', 55, WorkoutLevel::Beginner],
            ['Кроссфит', 'Функциональная тренировка высокой интенсивности', 60, WorkoutLevel::Advanced],
            ['Зумба', 'Танцевальная аэробика', 45, WorkoutLevel::Beginner],
            ['Стретчинг', 'Растяжка всех групп мышц', 50, WorkoutLevel::Intermediate],
            ['Табата', 'Короткие интервалы с высокой нагрузкой', 45, WorkoutLevel::Intermediate],
            ['Здоровая спина', 'Упражнения для осанки и снятия напряжения', 50, WorkoutLevel::Beginner],
            ['Тайский бокс', 'Техника ударов и работа в парах', 60, WorkoutLevel::Advanced],
        ];

        foreach ($workouts as [$name, $description, $minutes, $level]) {
            Workout::create([
                'name' => $name,
                'description' => $description,
                'duration_minutes' => $minutes,
                'level' => $level,
                'is_active' => true,
            ]);
        }
    }
}
