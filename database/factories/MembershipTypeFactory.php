<?php

namespace Database\Factories;

use App\Models\MembershipType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipType>
 */
class MembershipTypeFactory extends Factory
{
    protected $model = MembershipType::class;

    public function definition(): array
    {
        $faker = fake('ru_RU');

        return [
            'name' => $faker->unique()->randomElement([
                'Утренний безлимит',
                'Вечерний безлимит',
                'Квартал безлимит',
                'Полгода безлимит',
                'Месяц, 4 посещения',
                'Месяц, 12 посещений',
                'Студенческий',
                'Семейный',
            ]),
            'price' => $faker->numberBetween(10, 300) * 100,
            'duration_days' => $faker->randomElement([30, 90, 180, 365]),
            'visits_limit' => $faker->randomElement([null, 4, 8, 12]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
