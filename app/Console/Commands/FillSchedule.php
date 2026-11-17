<?php

namespace App\Console\Commands;

use App\Enums\ScheduleStatus;
use App\Enums\UserRole;
use App\Models\ScheduleItem;
use App\Models\User;
use App\Models\Workout;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FillSchedule extends Command
{
    protected $signature = 'schedule:fill {--to=2026-12-28 : Последний день (Y-m-d)}';

    protected $description = 'Добавляет занятия в расписание с сегодняшнего дня до указанной даты (повторный запуск безопасен)';

    /** Время занятий по местному времени клуба (Казахстан, UTC+5). */
    private const TZ = 'Asia/Almaty';

    public function handle(): int
    {
        $trainers = User::where('role', UserRole::Trainer->value)->orderBy('id')->get();
        $workouts = Workout::orderBy('id')->get();

        if ($trainers->isEmpty() || $workouts->isEmpty()) {
            $this->error('Нет тренеров или видов занятий. Сначала выполните php artisan migrate --seed.');

            return self::FAILURE;
        }

        $now = Carbon::now(self::TZ);
        $last = Carbon::parse($this->option('to'), self::TZ)->endOfDay();
        $created = 0;
        $n = 0;

        for ($day = $now->copy()->startOfDay(); $day->lte($last); $day->addDay()) {
            // Воскресенье: 2 занятия, остальные дни: 4
            $hours = $day->isSunday() ? [10, 16] : [9, 12, 16, 19];

            foreach ($hours as $slot => $hour) {
                $n++;
                $local = $day->copy()->setTime($hour, 0);
                if ($local->lte($now)) {
                    continue; // прошедшее время не создаём
                }

                $workout = $workouts[$n % $workouts->count()];
                $startsAt = $local->copy()->setTimezone(config('app.timezone'));
                $endsAt = $startsAt->copy()->addMinutes($workout->duration_minutes);

                // Пропускаем, если в это время уже есть занятие (так не бывает пересечений)
                $busy = ScheduleItem::where('status', '!=', ScheduleStatus::Cancelled)
                    ->where('starts_at', '<', $endsAt)
                    ->where('ends_at', '>', $startsAt)
                    ->exists();
                if ($busy) {
                    continue;
                }

                ScheduleItem::create([
                    'workout_id' => $workout->id,
                    'trainer_id' => $trainers[$n % $trainers->count()]->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'room' => 'Зал '.(($slot % 2) + 1),
                    'capacity' => random_int(10, 15),
                    'status' => ScheduleStatus::Scheduled,
                ]);
                $created++;
            }
        }

        $this->info("Добавлено занятий: {$created}. Расписание заполнено до {$last->format('d.m.Y')}.");

        return self::SUCCESS;
    }
}