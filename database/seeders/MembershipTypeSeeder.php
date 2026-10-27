<?php

namespace Database\Seeders;

use App\Models\MembershipType;
use Illuminate\Database\Seeder;

class MembershipTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['Месяц безлимит', 3500, 30, null],
            ['Месяц, 8 посещений', 2400, 30, 8],
            ['Разовое посещение', 500, 1, 1],
            ['Год безлимит', 30000, 365, null],
            ['Квартал безлимит', 9000, 90, null],
            ['Утренний безлимит', 2800, 30, null],
        ];

        foreach ($types as [$name, $price, $days, $limit]) {
            MembershipType::create([
                'name' => $name,
                'price' => $price,
                'duration_days' => $days,
                'visits_limit' => $limit,
                'is_active' => true,
            ]);
        }
    }
}
