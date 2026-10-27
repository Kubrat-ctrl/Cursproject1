<?php

namespace App\Models;

use Database\Factories\VisitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    /** @use HasFactory<VisitFactory> */
    use HasFactory;

    protected $fillable = [
        'workout_booking_id',
        'checked_in_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
        ];
    }

    public function workoutBooking(): BelongsTo
    {
        return $this->belongsTo(WorkoutBooking::class);
    }
}
