<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\MembershipStatus;
use App\Enums\ScheduleStatus;
use App\Exceptions\BusinessException;
use App\Models\Membership;
use App\Models\ScheduleItem;
use App\Models\User;
use App\Models\WorkoutBooking;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     * Запись на занятие (ключевой сценарий, п. 1.5).
     * Строка занятия блокируется на время транзакции: при одновременных запросах
     * число записей не превысит число мест.
     */
    public function book(User $user, ScheduleItem $scheduleItem): WorkoutBooking
    {
        try {
            return DB::transaction(function () use ($user, $scheduleItem) {
                $item = ScheduleItem::query()
                    ->whereKey($scheduleItem->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($item->status !== ScheduleStatus::Scheduled || $item->starts_at->isPast()) {
                    throw BusinessException::unprocessable('Запись закрыта: занятие отменено или уже началось');
                }

                $booking = WorkoutBooking::query()
                    ->where('user_id', $user->id)
                    ->where('schedule_item_id', $item->id)
                    ->first();

                if ($booking?->status === BookingStatus::Booked) {
                    throw BusinessException::conflict('Вы уже записаны на это занятие');
                }

                $membership = $this->findMembership($user, $item);

                $taken = WorkoutBooking::query()
                    ->where('schedule_item_id', $item->id)
                    ->where('status', BookingStatus::Booked->value)
                    ->count();
                if ($taken >= $item->capacity) {
                    throw BusinessException::conflict('На занятие нет свободных мест');
                }

                if ($booking) {
                    // Повторная запись после отмены: пара «клиент — занятие» уникальна.
                    $booking->forceFill([
                        'membership_id' => $membership->id,
                        'status' => BookingStatus::Booked,
                        'cancelled_at' => null,
                    ])->save();
                } else {
                    $booking = WorkoutBooking::create([
                        'user_id' => $user->id,
                        'schedule_item_id' => $item->id,
                        'membership_id' => $membership->id,
                        'status' => BookingStatus::Booked,
                    ]);
                }

                Log::info('Клиент записан на занятие', [
                    'booking_id' => $booking->id,
                    'user_id' => $user->id,
                    'schedule_item_id' => $item->id,
                    'membership_id' => $membership->id,
                ]);

                return $booking->load(['scheduleItem.workout', 'membership.membershipType']);
            });
        } catch (BusinessException $e) {
            Log::warning('Отказ в записи на занятие', [
                'user_id' => $user->id,
                'schedule_item_id' => $scheduleItem->id,
                'reason' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Отмена записи не позднее чем за 2 часа до начала. Запись остаётся в истории
     * со статусом cancelled, посещение возвращается на абонемент: в остаток входят
     * только записи booked.
     */
    public function cancel(WorkoutBooking $booking): WorkoutBooking
    {
        try {
            return DB::transaction(function () use ($booking) {
                $booking = WorkoutBooking::query()
                    ->with('scheduleItem')
                    ->whereKey($booking->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $booking->status->canTransitionTo(BookingStatus::Cancelled)) {
                    throw BusinessException::conflict('Запись уже отменена');
                }
                if ($booking->visit()->exists()) {
                    throw BusinessException::conflict('Нельзя отменить запись: посещение уже отмечено');
                }
                if (now()->addHours(2)->greaterThan($booking->scheduleItem->starts_at)) {
                    throw BusinessException::unprocessable('Отменить запись можно не позднее чем за 2 часа до начала');
                }

                $booking->forceFill([
                    'status' => BookingStatus::Cancelled,
                    'cancelled_at' => now(),
                ])->save();

                Log::info('Запись отменена клиентом', [
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'schedule_item_id' => $booking->schedule_item_id,
                ]);

                return $booking;
            });
        } catch (BusinessException $e) {
            Log::warning('Отказ в отмене записи', ['booking_id' => $booking->id, 'reason' => $e->getMessage()]);

            throw $e;
        }
    }

    /** Подходящий абонемент: действует на дату занятия и имеет остаток посещений. */
    private function findMembership(User $user, ScheduleItem $item): Membership
    {
        $date = $item->starts_at->toDateString();

        $memberships = Membership::query()
            ->with('membershipType')
            ->where('user_id', $user->id)
            ->where('status', MembershipStatus::Active->value)
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date)
            ->orderBy('ends_on')
            ->lockForUpdate()
            ->get();

        if ($memberships->isEmpty()) {
            throw BusinessException::unprocessable('Нет действующего абонемента на дату занятия');
        }

        foreach ($memberships as $membership) {
            $limit = $membership->membershipType->visits_limit;
            $used = WorkoutBooking::query()
                ->where('membership_id', $membership->id)
                ->where('status', BookingStatus::Booked->value)
                ->count();

            if ($limit === null || $used < $limit) {
                return $membership;
            }
        }

        throw BusinessException::unprocessable('Лимит посещений по абонементу исчерпан');
    }
}
