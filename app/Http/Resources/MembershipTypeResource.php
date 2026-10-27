<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\MembershipType */
class MembershipTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'duration_days' => $this->duration_days,
            'visits_limit' => $this->visits_limit,
            'is_active' => $this->is_active,
        ];
    }
}
