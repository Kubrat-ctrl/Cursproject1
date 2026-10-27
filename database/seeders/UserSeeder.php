<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Известные учётные записи для тестирования. Пароль 'password'
        // хешируется автоматически приведением 'hashed'.
        User::factory()->admin()->create([
            'name' => 'Анна Воронова',
            'email' => 'admin@fitclub.test',
            'password' => 'password',
            'phone' => '+77010000001',
        ]);

        User::factory()->trainer()->create([
            'name' => 'Игорь Соколов',
            'email' => 'sokolov@fitclub.test',
            'password' => 'password',
            'phone' => '+77010000002',
        ]);
        User::factory()->trainer()->create([
            'name' => 'Мария Лебедева',
            'email' => 'lebedeva@fitclub.test',
            'password' => 'password',
            'phone' => '+77010000003',
        ]);
        User::factory()->trainer()->create();

        $clients = [
            ['Олег Петров', 'petrov@fitclub.test', '+77010000004'],
            ['Елена Смирнова', 'smirnova@fitclub.test', '+77010000005'],
            ['Дмитрий Орлов', 'orlov@fitclub.test', null],
            ['Светлана Ким', 'kim@fitclub.test', '+77010000007'],
        ];
        foreach ($clients as [$name, $email, $phone]) {
            User::factory()->client()->create([
                'name' => $name,
                'email' => $email,
                'password' => 'password',
                'phone' => $phone,
            ]);
        }

        // Остальные клиенты — случайные данные из фабрики.
        User::factory()->client()->count(6)->create();
    }
}
