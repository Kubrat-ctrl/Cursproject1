<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Порядок важен: сначала таблицы, на которые ссылаются внешние ключи.
     *
     * Учётные записи для проверки (пароль у всех — password):
     *   admin@fitclub.test   — администратор
     *   sokolov@fitclub.test — тренер
     *   petrov@fitclub.test  — клиент
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            MembershipTypeSeeder::class,
            WorkoutSeeder::class,
            MembershipSeeder::class,
            ScheduleItemSeeder::class,
            WorkoutBookingSeeder::class,
            VisitSeeder::class,
        ]);
    }
}
