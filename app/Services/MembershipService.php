<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\MembershipStatus;
use App\Exceptions\BusinessException;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
            throw BusinessException::conflict('Этот тип абонемента снят с продажи');
        }

        $membership = Membership::create([
            'user_id' => $user->id,
            'membership_type_id' => $type->id,
            'starts_on' => today(),
            'ends_on' => today()->addDays($type->duration_days),
            'price_paid' => $type->price,
            'status' => MembershipStatus::Active,
        ])->refresh();

        Log::info('Куплен абонемент', [
            'membership_id' => $membership->id,
            'user_id' => $user->id,
            'membership_type_id' => $type->id,
            'price_paid' => $membership->price_paid,
        ]);

        return $membership->load('membershipType')
            ->loadCount(['bookings' => fn ($q) => $q->where('status', BookingStatus::Booked->value)]);
    }
}
