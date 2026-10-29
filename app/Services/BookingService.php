<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\MembershipStatus;
use App\Enums\ScheduleStatus;
use App\Models\Membership;
use App\Models\ScheduleItem;
use App\Models\User;
use App\Models\WorkoutBooking;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /** Записи клиента (история), новые сверху. */
    public function paginateFor(User $user, array $filters): LengthAwarePaginator
    {
        return WorkoutBooking::query()
            ->where('user_id', $user->id)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->with(['scheduleItem.workout', 'scheduleItem.trainer', 'visit'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /** Список записавшихся на занятие (для тренера). */
    public function listForScheduleItem(ScheduleItem $item): Collection
    {
        return $item->bookings()
            ->where('status', BookingStatus::Booked->value)
            ->with(['user', 'visit'])
            ->get();
    }

    /**
     * Запись на занятие (ключевой сценарий ПР1, п. 1.5).
     * Занятие блокируется на время транзакции, поэтому при одновременных
     * запросах число записей не превысит число мест.
     */
    public function book(User $user, ScheduleItem $scheduleItem): WorkoutBooking
    {
        return DB::transaction(function () use ($user, $scheduleItem) {
            $item = ScheduleItem::query()
                ->whereKey($scheduleItem->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($item->status === ScheduleStatus::Cancelled) {
                abort(409, 'Занятие отменено, запись невозможна.');
            }
            if ($item->starts_at->isPast()) {
                abort(409, 'Занятие уже началось, запись закрыта.');
            }

            $existing = WorkoutBooking::query()
                ->where('user_id', $user->id)
                ->where('schedule_item_id', $item->id)
                ->first();

            if ($existing && $existing->status === BookingStatus::Booked) {
                abort(409, 'Вы уже записаны на это занятие.');
            }

            $taken = $item->bookings()->where('status', BookingStatus::Booked->value)->count();
            if ($taken >= $item->capacity) {
                abort(409, 'На занятие не осталось свободных мест.');
            }

            $membership = $this->findMembership($user, $item);

            if ($existing) {
                // Пара «клиент — занятие» уникальна: отменённая запись просто возобновляется.
                $existing->forceFill([
                    'membership_id' => $membership->id,
                    'status' => BookingStatus::Booked,
                    'cancelled_at' => null,
                ])->save();
            } else {
                // Новая запись: добавляем связь «многие ко многим» через attach.
                $item->clients()->attach($user->id, [
                    'membership_id' => $membership->id,
                    'status' => BookingStatus::Booked->value,
                ]);
            }

            return WorkoutBooking::query()
                ->where('user_id', $user->id)
                ->where('schedule_item_id', $item->id)
                ->with(['scheduleItem.workout', 'membership.membershipType'])
                ->firstOrFail();
        });
    }

    /**
     * Отмена записи не позднее чем за 2 часа до начала.
     * Запись остаётся в истории со статусом cancelled, поэтому detach не используется:
     * посещение возвращается на абонемент, потому что в остаток входят только записи booked.
     */
    public function cancel(WorkoutBooking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $booking = WorkoutBooking::query()
                ->whereKey($booking->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->status === BookingStatus::Cancelled) {
                abort(409, 'Запись уже отменена.');
            }
            if ($booking->visit()->exists()) {
                abort(409, 'Нельзя отменить запись: посещение уже отмечено.');
            }

            $deadline = $booking->scheduleItem->starts_at->copy()->subHours(2);
            if (now()->greaterThan($deadline)) {
                abort(422, 'Отменить запись можно не позднее чем за 2 часа до начала занятия.');
            }

            $booking->forceFill([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
            ])->save();
        });
    }

    /** Подходящий абонемент: действует на дату занятия и имеет остаток посещений. */
    private function findMembership(User $user, ScheduleItem $item): Membership
    {
        $classDate = $item->starts_at->toDateString();

        $membership = Membership::query()
            ->where('user_id', $user->id)
            ->where('status', MembershipStatus::Active->value)
            ->whereDate('starts_on', '<=', $classDate)
            ->whereDate('ends_on', '>=', $classDate)
            ->with('membershipType')
            ->withCount(['bookings' => fn ($q) => $q->where('status', BookingStatus::Booked->value)])
            ->orderBy('ends_on')
            ->lockForUpdate()
            ->get()
            ->first(fn (Membership $m) => $m->membershipType->visits_limit === null
                || $m->bookings_count < $m->membershipType->visits_limit);

        if (! $membership) {
            abort(409, 'Нет действующего абонемента с остатком посещений на дату занятия.');
        }

        return $membership;
    }
}
