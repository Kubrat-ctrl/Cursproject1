<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    /**
     * Поля password и remember_token не возвращаются никогда.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'phone' => $this->phone,
            'avatar_path' => $this->avatar_path,
            'blocked' => $this->blocked_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}