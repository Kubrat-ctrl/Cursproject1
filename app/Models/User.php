<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /** Абонементы клиента. */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /** Занятия, которые ведёт тренер (внешний ключ trainer_id). */
    public function scheduleItems(): HasMany
    {
        return $this->hasMany(ScheduleItem::class, 'trainer_id');
    }

    /** Записи клиента на занятия. */
    public function bookings(): HasMany
    {
        return $this->hasMany(WorkoutBooking::class);
    }

    /** Занятия, на которые записан клиент (M:N через workout_bookings). */
    public function bookedItems(): BelongsToMany
    {
        return $this->belongsToMany(ScheduleItem::class, 'workout_bookings', 'user_id', 'schedule_item_id')
            ->using(WorkoutBooking::class)
            ->withPivot(['id', 'membership_id', 'status', 'cancelled_at'])
            ->withTimestamps();
    }
}
