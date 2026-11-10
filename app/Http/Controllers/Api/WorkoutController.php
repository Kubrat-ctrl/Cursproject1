<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkoutRequest;
use App\Http\Requests\UpdateWorkoutRequest;
use App\Http\Resources\WorkoutResource;
use App\Models\Workout;
use App\Services\WorkoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WorkoutController extends Controller
{
    // Сервис подключается внедрением зависимости: контейнер Laravel создаёт его сам.
    public function __construct(private readonly WorkoutService $workouts)
    {
    }

    /**
     * Список видов занятий (с пагинацией)
     *
     * Доступ: любой, в том числе гость.
     *
     * @group Виды занятий
     * @queryParam level string Фильтр по уровню: beginner, intermediate, advanced. Example: beginner
     * @queryParam page integer Номер страницы. Example: 1
     * @response 200 {"data": [{"id": 1, "name": "Сайкл", "description": "Кардио на велотренажёрах", "duration_minutes": 50, "level": "intermediate", "image_path": null, "image_url": null, "is_active": true}], "links": {"first": "...", "last": "...", "prev": null, "next": "..."}, "meta": {"current_page": 1, "last_page": 1, "per_page": 15, "total": 1}}
     * @response 422 {"message": "Ошибка валидации", "errors": {"field": ["Сообщение об ошибке"]}}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return WorkoutResource::collection(
            $this->workouts->paginate($request->only(['level', 'is_active']))
        );
    }

    /**
     * Один вид занятия
     *
     * Доступ: любой, в том числе гость.
     *
     * @group Виды занятий
     * @urlParam workout integer required Идентификатор вида занятия. Example: 1
     * @response 200 {"data": {"id": 1, "name": "Сайкл", "description": "Кардио на велотренажёрах", "duration_minutes": 50, "level": "intermediate", "image_path": null, "image_url": null, "is_active": true}}
     * @response 404 {"message": "Вид занятия не найден"}
     */
    public function show(Workout $workout): WorkoutResource
    {
        return new WorkoutResource($workout);
    }

    /** POST /api/v1/workouts */
    public function store(StoreWorkoutRequest $request): JsonResponse
    {
        $workout = $this->workouts->create($request->validated(), $request->file('image'));

        return (new WorkoutResource($workout))->response()->setStatusCode(201);
    }

    /** PUT|PATCH /api/v1/workouts/{workout} */
    public function update(UpdateWorkoutRequest $request, Workout $workout): WorkoutResource
    {
        return new WorkoutResource(
            $this->workouts->update($workout, $request->validated(), $request->file('image'))
        );
    }

    /**
     * Удаление вида занятия
     *
     * Доступ: администратор.
     *
     * @group Виды занятий
     * @authenticated
     * @urlParam workout integer required Идентификатор. Example: 1
     * @response 204 
     * @response 401 {"message": "Требуется авторизация"}
     * @response 403 {"message": "Недостаточно прав для этого действия"}
     * @response 404 {"message": "Вид занятия не найден"}
     * @response 409 {"message": "Нельзя удалить вид занятия, который есть в расписании. Отключите его: is_active = false"}
     */
    public function destroy(Workout $workout): Response
    {
        $this->workouts->delete($workout);

        return response()->noContent();
    }
}
