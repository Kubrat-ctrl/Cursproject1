<?php

namespace App\Services;

use App\Models\Workout;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * Логика работы с видами занятий. Контроллер только передаёт сюда данные
 * из Form Request и возвращает результат через API Resource.
 */
class WorkoutService
{
    /** Список видов занятий по 15 на странице с необязательными фильтрами. */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Workout::query()
            ->when(isset($filters['level']), fn ($query) => $query->where('level', $filters['level']))
            ->when(isset($filters['is_active']), fn ($query) => $query->where(
                'is_active',
                filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN)
            ))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data, ?UploadedFile $image = null): Workout
    {
        $data = Arr::except($data, ['image']);
        $data['is_active'] ??= true;

        if ($image) {
            $data['image_path'] = $image->store('workouts', 'public');
        }

        // refresh() подгружает значения по умолчанию, заданные в БД.
        return Workout::create($data)->refresh();
    }

    public function update(Workout $workout, array $data, ?UploadedFile $image = null): Workout
    {
        $data = Arr::except($data, ['image']);

        if ($image) {
            $this->deleteImage($workout);
            $data['image_path'] = $image->store('workouts', 'public');
        }

        $workout->update($data);

        return $workout->refresh();
    }

    public function delete(Workout $workout): void
    {
        // Внешний ключ schedule_items.workout_id защищён restrictOnDelete (ПР3),
        // поэтому конфликт проверяем заранее и возвращаем 409, а не ошибку БД.
        if ($workout->scheduleItems()->exists()) {
            abort(409, 'Нельзя удалить вид занятия, который есть в расписании. Отключите его: is_active = false.');
        }

        $this->deleteImage($workout);
        $workout->delete();
    }

    private function deleteImage(Workout $workout): void
    {
        if ($workout->image_path) {
            Storage::disk('public')->delete($workout->image_path);
        }
    }
}
