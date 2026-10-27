<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Workout */
class WorkoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'duration_minutes' => $this->duration_minutes,
            'level' => $this->level->value,
            'image_path' => $this->image_path,
            'is_active' => $this->is_active,
        ];
    }
}
