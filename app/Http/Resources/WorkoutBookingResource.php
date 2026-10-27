<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\WorkoutBooking */
class WorkoutBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => new UserResource($this->whenLoaded('user')),
            'schedule_item' => new ScheduleItemResource($this->whenLoaded('scheduleItem')),
            'membership' => new MembershipResource($this->whenLoaded('membership')),
            'visit' => new VisitResource($this->whenLoaded('visit')),
        ];
    }
}
