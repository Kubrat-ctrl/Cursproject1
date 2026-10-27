<?php

namespace App\Models;

use App\Enums\WorkoutLevel;
use Database\Factories\WorkoutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workout extends Model
{
    /** @use HasFactory<WorkoutFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'duration_minutes',
        'level',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'level' => WorkoutLevel::class,
            'is_active' => 'boolean',
        ];
    }

    public function scheduleItems(): HasMany
    {
        return $this->hasMany(ScheduleItem::class);
    }
}
