<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Membership */
class MembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'starts_on' => $this->starts_on->toDateString(),
            'ends_on' => $this->ends_on->toDateString(),
            'price_paid' => $this->price_paid,
            'status' => $this->status->value,
            // Связанные данные попадают в ответ, только если они загружены через with()/load().
            'membership_type' => new MembershipTypeResource($this->whenLoaded('membershipType')),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
