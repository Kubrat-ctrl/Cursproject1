<?php

namespace App\Models;

use App\Enums\ScheduleStatus;
use Database\Factories\ScheduleItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ScheduleItem extends Model
{
    /** @use HasFactory<ScheduleItemFactory> */
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'trainer_id',
        'starts_at',
        'ends_at',
        'room',
        'capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'integer',
            'status' => ScheduleStatus::class,
        ];
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /** Тренер — пользователь с ролью trainer (внешний ключ trainer_id). */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(WorkoutBooking::class);
    }

    /** Клиенты, записанные на занятие (M:N через workout_bookings). */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workout_bookings', 'schedule_item_id', 'user_id')
            ->using(WorkoutBooking::class)
            ->withPivot(['id', 'membership_id', 'status', 'cancelled_at'])
            ->withTimestamps();
    }

    /** Фильтры по тренеру, виду занятия и диапазону дат. */
    public function scopeFilter(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['trainer_id'] ?? null, fn ($q, $v) => $q->where('trainer_id', $v))
            ->when($f['workout_id'] ?? null, fn ($q, $v) => $q->where('workout_id', $v))
            ->when($f['date_from'] ?? null, fn ($q, $v) => $q->where('starts_at', '>=', Carbon::parse($v)->startOfDay()))
            ->when($f['date_to'] ?? null, fn ($q, $v) => $q->where('starts_at', '<=', Carbon::parse($v)->endOfDay()));
    }

    /** Поиск по названию вида занятия или имени тренера; % и _ в строке экранируются. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $q, string $term) {
            $like = '%'.addcslashes($term, '%_\\').'%';

            $q->where(fn (Builder $q) => $q
                ->whereHas('workout', fn ($w) => $w->where('name', 'like', $like))
                ->orWhereHas('trainer', fn ($t) => $t->where('name', 'like', $like)));
        });
    }
}
