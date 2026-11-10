<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\WorkoutBooking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /** Продажи абонементов за период: количество и выручка по типам. */
    public function membershipsSales(string $from, string $to): array
    {
        $rows = Membership::query()
            ->join('membership_types', 'membership_types.id', '=', 'memberships.membership_type_id')
            ->where('memberships.status', MembershipStatus::Active->value)
            ->whereDate('memberships.starts_on', '>=', $from)
            ->whereDate('memberships.starts_on', '<=', $to)
            ->groupBy('membership_types.id', 'membership_types.name')
            ->selectRaw('membership_types.id as membership_type_id, membership_types.name as membership_type, COUNT(memberships.id) as sold, SUM(memberships.price_paid) as revenue')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'membership_type_id' => (int) $row->membership_type_id,
                'membership_type' => $row->membership_type,
                'sold' => (int) $row->sold,
                'revenue' => round((float) $row->revenue, 2),
            ]);

        return [
            'from' => $from,
            'to' => $to,
            'total_sold' => $rows->sum('sold'),
            'total_revenue' => round($rows->sum('revenue'), 2),
            'items' => $rows->values(),
        ];
    }

    /** Число записей по статусам; статусы без записей возвращаются с нулём. */
    public function bookingsByStatus(): array
    {
        $counts = WorkoutBooking::query()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $result = [];
        foreach (BookingStatus::cases() as $status) {
            $result[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $result;
    }

    /** Рейтинг видов занятий по числу действующих записей за период. */
    public function popularWorkouts(Carbon $from, Carbon $to, int $limit): Collection
    {
        return DB::table('workouts')
            ->join('schedule_items', 'schedule_items.workout_id', '=', 'workouts.id')
            ->leftJoin('workout_bookings', function ($join) {
                $join->on('workout_bookings.schedule_item_id', '=', 'schedule_items.id')
                    ->where('workout_bookings.status', BookingStatus::Booked->value);
            })
            ->whereBetween('schedule_items.starts_at', [$from, $to])
            ->groupBy('workouts.id', 'workouts.name')
            ->selectRaw('workouts.id, workouts.name,
                COUNT(DISTINCT schedule_items.id) AS sessions,
                COUNT(workout_bookings.id) AS bookings,
                ROUND(COUNT(workout_bookings.id) / COUNT(DISTINCT schedule_items.id), 1) AS avg_bookings')
            ->orderByDesc('bookings')
            ->orderBy('workouts.name')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'name' => $r->name,
                'sessions' => (int) $r->sessions,
                'bookings' => (int) $r->bookings,
                'avg_bookings' => (float) $r->avg_bookings,
            ]);
    }
}
