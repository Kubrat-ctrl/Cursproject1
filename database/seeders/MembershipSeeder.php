<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Database\Seeder;

class MembershipSeeder extends Seeder
{
    public function run(): void
    {
        $clients = User::where('role', UserRole::Client->value)->orderBy('id')->get();
        $types = MembershipType::orderBy('id')->get();

        foreach ($clients as $index => $client) {
            // Первые клиенты получают типы по порядку (в том числе разовый и годовой),
            // остальные — месячный, на 8 посещений или квартальный.
            $type = $index < 4
                ? $types[$index]
                : $types->whereIn('id', [1, 2, 5])->values()->random();

            // Разовый абонемент куплен сегодня, остальные — несколько дней назад,
            // чтобы они покрывали и уже прошедшие занятия.
            $startsOn = $type->duration_days === 1 ? now() : now()->subDays(5 + $index % 4);

            Membership::factory()
                ->forType($type, $startsOn)
                ->create(['user_id' => $client->id]);
        }

        // Отменённый абонемент одного из клиентов (в записях не участвует).
        Membership::factory()
            ->forType($types[0], now()->subDays(60))
            ->cancelled()
            ->create(['user_id' => $clients->last()->id]);
    }
}
