<?php

namespace Database\Seeders;

use App\Enums\ScheduleStatus;
use App\Enums\UserRole;
use App\Models\ScheduleItem;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Database\Seeder;

class ScheduleItemSeeder extends Seeder
{
    public function run(): void
    {
        $trainers = User::where('role', UserRole::Trainer->value)->orderBy('id')->get();
        $workouts = Workout::orderBy('id')->get();

        // Два уже прошедших занятия: по ним в VisitSeeder появятся отметки посещения.
        foreach ([-2, -1] as $i => $offset) {
            $this->make($workouts[$i], $trainers[$i % $trainers->count()], now()->addDays($offset)->setTime(10, 0), 'Зал 1', 12);
        }

        // Расписание на ближайшие 6 дней: утреннее и вечернее занятие каждый день.
        // В каждый момент времени проходит одно занятие, поэтому занятия тренера не пересекаются.
        for ($day = 1; $day <= 6; $day++) {
            foreach ([10, 19] as $slot => $hour) {
                $trainer = $trainers[($day + $slot) % $trainers->count()];
                $workout = $workouts[($day * 2 + $slot) % $workouts->count()];

                // Занятие на 2 места — для проверки ошибки «мест нет».
                $capacity = ($day === 2 && $slot === 1) ? 2 : random_int(10, 15);

                // Одно занятие отменено.
                $status = ($day === 6 && $slot === 0) ? ScheduleStatus::Cancelled : ScheduleStatus::Scheduled;

                $this->make($workout, $trainer, now()->addDays($day)->setTime($hour, 0), 'Зал '.($slot + 1), $capacity, $status);
            }
        }
    }

    private function make(Workout $workout, User $trainer, $startsAt, string $room, int $capacity, ScheduleStatus $status = ScheduleStatus::Scheduled): void
    {
        ScheduleItem::create([
            'workout_id' => $workout->id,
            'trainer_id' => $trainer->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes($workout->duration_minutes),
            'room' => $room,
            'capacity' => $capacity,
            'status' => $status,
        ]);
    }
}
