<?php

namespace App\Services;

use App\Enums\MembershipStatus;
use App\Models\Membership;

class ReportService
{
    /** Продажи абонементов за период: количество и выручка по типам. */
    public function membershipsSales(string $from, string $to): array
    {
        $rows = Membership::query()
            ->join('membership_types', 'membership_types.id', '=', 'memberships.membership_type_id')
            ->where('memberships.status', MembershipStatus::Active->value)
            ->whereBetween('memberships.starts_on', [$from, $to])
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
}
