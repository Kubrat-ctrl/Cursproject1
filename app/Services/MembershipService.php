<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Support\Collection;

class MembershipService
{
    /** Абонементы клиента с числом использованных посещений (действующие записи). */
    public function listFor(User $user): Collection
    {
        return Membership::query()
            ->where('user_id', $user->id)
            ->with('membershipType')
            ->withCount(['bookings' => fn ($q) => $q->where('status', BookingStatus::Booked->value)])
            ->orderByDesc('ends_on')
            ->get();
    }

    /** Покупка: срок действия и цена берутся из типа абонемента. */
    public function purchase(User $user, MembershipType $type): Membership
    {
        if (! $type->is_active) {
            abort(409, 'Этот тип абонемента снят с продажи.');
        }

        $membership = Membership::create([
            'user_id' => $user->id,
            'membership_type_id' => $type->id,
            'starts_on' => today(),
            'ends_on' => today()->addDays($type->duration_days),
            'price_paid' => $type->price,
            'status' => MembershipStatus::Active,
        ])->refresh();

        return $membership->load('membershipType')
            ->loadCount(['bookings' => fn ($q) => $q->where('status', BookingStatus::Booked->value)]);
    }
}
