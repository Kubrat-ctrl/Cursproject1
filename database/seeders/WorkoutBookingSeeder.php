<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\MembershipStatus;
use App\Enums\ScheduleStatus;
use App\Models\Membership;
use App\Models\ScheduleItem;
use App\Models\WorkoutBooking;
use Illuminate\Database\Seeder;

class WorkoutBookingSeeder extends Seeder
{
    public function run(): void
    {
        $items = ScheduleItem::where('status', ScheduleStatus::Scheduled->value)->orderBy('starts_at')->get();
        $memberships = Membership::with('membershipType')
            ->where('status', MembershipStatus::Active->value)
            ->orderBy('id')
            ->get();

        $past = $items->filter(fn ($item) => $item->starts_at->isPast());
        $future = $items->reject(fn ($item) => $item->starts_at->isPast());

        // 1. Прошедшие занятия: записываем по 6 клиентов (по ним потом отметятся посещения).
        foreach ($past as $item) {
            $count = 0;
            foreach ($memberships as $membership) {
                if ($count < 6 && $this->book($membership, $item)) {
                    $count++;
                }
            }
        }

        // 2. Занятие на 2 места заполняем целиком.
        $small = $future->firstWhere('capacity', 2);
        if ($small) {
            foreach ($memberships as $membership) {
                $this->book($membership, $small);
            }
        }

        // 3. Каждый клиент записывается ещё на несколько будущих занятий.
        //    Проверки срока, лимита и мест выполняет метод book().
        foreach ($memberships as $membership) {
            foreach ($future->shuffle()->take(3) as $item) {
                $this->book($membership, $item);
            }
        }

        // 4. Одна отменённая запись.
        $membership = $memberships[1];
        foreach ($future as $item) {
            if ($this->book($membership, $item, BookingStatus::Cancelled)) {
                break;
            }
        }
    }

    /**
     * Создаёт запись, если абонемент действует в день занятия, не исчерпан лимит
     * посещений, есть свободные места и клиент ещё не записан на это занятие.
     */
    private function book(Membership $membership, ScheduleItem $item, BookingStatus $status = BookingStatus::Booked): bool
    {
        $date = $item->starts_at->toDateString();
        if ($date < $membership->starts_on->toDateString() || $date > $membership->ends_on->toDateString()) {
            return false;
        }

        $alreadyBooked = WorkoutBooking::where('user_id', $membership->user_id)
            ->where('schedule_item_id', $item->id)
            ->exists();
        if ($alreadyBooked) {
            return false;
        }

        if ($status === BookingStatus::Booked) {
            $limit = $membership->membershipType->visits_limit;
            $used = $membership->bookings()->where('status', BookingStatus::Booked->value)->count();
            if ($limit !== null && $used >= $limit) {
                return false;
            }

            $taken = WorkoutBooking::where('schedule_item_id', $item->id)
                ->where('status', BookingStatus::Booked->value)
                ->count();
            if ($taken >= $item->capacity) {
                return false;
            }
        }

        WorkoutBooking::create([
            'user_id' => $membership->user_id,
            'schedule_item_id' => $item->id,
            'membership_id' => $membership->id,
            'status' => $status,
            'cancelled_at' => $status === BookingStatus::Cancelled ? now() : null,
        ]);

        return true;
    }
}
