<?php

namespace App\Models;

use Database\Factories\MembershipTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipType extends Model
{
    /** @use HasFactory<MembershipTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'duration_days',
        'visits_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_days' => 'integer',
            'visits_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }
}
