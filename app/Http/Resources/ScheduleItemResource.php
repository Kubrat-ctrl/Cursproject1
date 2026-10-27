<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ScheduleItem */
class ScheduleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'room' => $this->room,
            'capacity' => $this->capacity,
            'status' => $this->status->value,
            'workout' => new WorkoutResource($this->whenLoaded('workout')),
            'trainer' => new UserResource($this->whenLoaded('trainer')),
            // Число записей появляется, если запрос был withCount('bookings').
            'bookings_count' => $this->whenCounted('bookings'),
            'bookings' => WorkoutBookingResource::collection($this->whenLoaded('bookings')),
        ];
    }
}
