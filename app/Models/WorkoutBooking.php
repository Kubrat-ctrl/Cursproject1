<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\WorkoutBookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Запись клиента на занятие: промежуточная модель связи «многие ко многим»
 * между User и ScheduleItem. У таблицы есть собственный id и своя связь hasOne.
 */
class WorkoutBooking extends Pivot
{
    /** @use HasFactory<WorkoutBookingFactory> */
    use HasFactory;

    protected $table = 'workout_bookings';

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'schedule_item_id',
        'membership_id',
        'status',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scheduleItem(): BelongsTo
    {
        return $this->belongsTo(ScheduleItem::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function visit(): HasOne
    {
        return $this->hasOne(Visit::class);
    }
}
