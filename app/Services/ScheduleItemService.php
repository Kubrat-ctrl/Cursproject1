<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\ScheduleStatus;
use App\Models\ScheduleItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleItemService
{
    /** Расписание с фильтрами и постраничным выводом, по возрастанию времени начала. */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return ScheduleItem::query()
            ->with(['workout', 'trainer'])
            ->withCount(['bookings' => fn ($q) => $q->where('status', BookingStatus::Booked->value)])
            ->when($filters['trainer_id'] ?? null, fn ($q, $id) => $q->where('trainer_id', $id))
            ->when($filters['workout_id'] ?? null, fn ($q, $id) => $q->where('workout_id', $id))
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->whereDate('starts_at', $date))
            ->orderBy('starts_at')
            ->paginate(15)
            ->withQueryString();
    }

    /** Занятие со связями и числом занятых мест. */
    public function details(ScheduleItem $item): ScheduleItem
    {
        return $item->load(['workout', 'trainer'])
            ->loadCount(['bookings' => fn ($q) => $q->where('status', BookingStatus::Booked->value)]);
    }

    public function create(array $data): ScheduleItem
    {
        $this->assertTrainerIsFree($data['trainer_id'], $data['starts_at'], $data['ends_at']);

        $item = ScheduleItem::create($data)->refresh();

        return $this->details($item);
    }

    public function update(ScheduleItem $item, array $data): ScheduleItem
    {
        if ($item->status === ScheduleStatus::Cancelled) {
            abort(409, 'Отменённое занятие изменять нельзя.');
        }

        $trainerId = $data['trainer_id'] ?? $item->trainer_id;
        $startsAt = $data['starts_at'] ?? $item->starts_at;
        $endsAt = $data['ends_at'] ?? $item->ends_at;

        if ($this->toAppTime($endsAt)->lte($this->toAppTime($startsAt))) {
            throw ValidationException::withMessages([
                'ends_at' => ['Время окончания должно быть позже времени начала.'],
            ]);
        }

        if (isset($data['capacity'])) {
            $booked = $item->bookings()->where('status', BookingStatus::Booked->value)->count();
            if ($data['capacity'] < $booked) {
                abort(409, "Нельзя уменьшить число мест: на занятие уже записано {$booked}.");
            }
        }

        $this->assertTrainerIsFree($trainerId, $startsAt, $endsAt, $item->id);

        $item->update($data);

        return $this->details($item->refresh());
    }

    /** Отмена занятия: все действующие записи отменяются, посещения возвращаются на абонементы. */
    public function cancel(ScheduleItem $item): ScheduleItem
    {
        if ($item->status === ScheduleStatus::Cancelled) {
            abort(409, 'Занятие уже отменено.');
        }
        if ($item->starts_at->isPast()) {
            abort(409, 'Нельзя отменить занятие, которое уже началось.');
        }

        DB::transaction(function () use ($item) {
            $item->update(['status' => ScheduleStatus::Cancelled]);

            $item->bookings()
                ->where('status', BookingStatus::Booked->value)
                ->update([
                    'status' => BookingStatus::Cancelled->value,
                    'cancelled_at' => now(),
                ]);
        });

        return $this->details($item->refresh());
    }

    /** Занятия одного тренера не должны пересекаться по времени. */
    private function assertTrainerIsFree(int $trainerId, $startsAt, $endsAt, ?int $ignoreId = null): void
    {
        $busy = ScheduleItem::query()
            ->where('trainer_id', $trainerId)
            ->where('status', ScheduleStatus::Scheduled->value)
            ->when($ignoreId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->where('starts_at', '<', $this->toAppTime($endsAt))
            ->where('ends_at', '>', $this->toAppTime($startsAt))
            ->exists();

        if ($busy) {
            abort(409, 'У тренера уже есть занятие в это время.');
        }
    }

    private function toAppTime($value): Carbon
    {
        return Carbon::parse($value)->setTimezone(config('app.timezone'));
    }
}
