<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\ScheduleStatus;
use App\Exceptions\BusinessException;
use App\Models\ScheduleItem;
use App\Models\WorkoutBooking;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ScheduleItemService
{
    /** Расписание: фильтры, поиск, сортировка по белому списку и пагинация. */
    public function list(array $p): LengthAwarePaginator
    {
        return ScheduleItem::query()
            ->with(['workout', 'trainer'])
            ->withCount(['bookings' => fn ($q) => $q->where('status', BookingStatus::Booked->value)])
            ->filter($p)
            ->search($p['search'] ?? null)
            ->orderBy($p['sort'] ?? 'starts_at', $p['order'] ?? 'asc')
            ->orderBy('id')
            ->paginate($p['per_page'] ?? 15)
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
            throw BusinessException::conflict('Отменённое занятие изменять нельзя');
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
                throw BusinessException::conflict("Нельзя уменьшить число мест: на занятие уже записано {$booked}");
            }
        }

        $this->assertTrainerIsFree($trainerId, $startsAt, $endsAt, $item->id);

        $item->update($data);

        return $this->details($item->refresh());
    }

    /** Отмена занятия: все действующие записи отменяются, посещения возвращаются на абонементы. */
    public function cancel(ScheduleItem $scheduleItem): ScheduleItem
    {
        $item = DB::transaction(function () use ($scheduleItem) {
            $item = ScheduleItem::query()
                ->whereKey($scheduleItem->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $item->status->canTransitionTo(ScheduleStatus::Cancelled)) {
                throw BusinessException::conflict('Занятие уже отменено');
            }
            if ($item->starts_at->isPast()) {
                throw BusinessException::unprocessable('Нельзя отменить занятие, которое уже началось');
            }

            $item->update(['status' => ScheduleStatus::Cancelled]);

            $cancelledBookings = WorkoutBooking::query()
                ->where('schedule_item_id', $item->id)
                ->where('status', BookingStatus::Booked->value)
                ->update([
                    'status' => BookingStatus::Cancelled->value,
                    'cancelled_at' => now(),
                ]);

            Log::warning('Занятие отменено администратором', [
                'schedule_item_id' => $item->id,
                'cancelled_bookings' => $cancelledBookings,
            ]);

            return $item;
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
            throw BusinessException::conflict('У тренера уже есть занятие в это время');
        }
    }

    private function toAppTime($value): Carbon
    {
        return Carbon::parse($value)->setTimezone(config('app.timezone'));
    }
}
