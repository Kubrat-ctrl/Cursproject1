<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    public function definition(): array
    {
        $startsOn = Carbon::instance(fake()->dateTimeBetween('-10 days', 'now'))->startOfDay();

        return [
            'user_id' => User::factory()->client(),
            'membership_type_id' => MembershipType::factory(),
            'starts_on' => $startsOn->toDateString(),
            'ends_on' => $startsOn->copy()->addDays(30)->toDateString(),
            'price_paid' => 3500,
            'status' => MembershipStatus::Active,
        ];
    }

    /**
     * Абонемент выбранного типа: срок и цена берутся из типа
     * (как при покупке — значения фиксируются на момент покупки).
     */
    public function forType(MembershipType $type, ?Carbon $startsOn = null): static
    {
        $startsOn = ($startsOn ?? now())->copy()->startOfDay();

        return $this->state(fn () => [
            'membership_type_id' => $type->id,
            'starts_on' => $startsOn->toDateString(),
            'ends_on' => $startsOn->copy()->addDays($type->duration_days)->toDateString(),
            'price_paid' => $type->price,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => MembershipStatus::Cancelled]);
    }
}
