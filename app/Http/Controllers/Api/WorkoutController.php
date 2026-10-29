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

    /** GET /api/v1/workouts */
    public function index(Request $request): AnonymousResourceCollection
    {
        return WorkoutResource::collection(
            $this->workouts->paginate($request->only(['level', 'is_active']))
        );
    }

    /** GET /api/v1/workouts/{workout}; при несуществующем id Laravel вернёт 404. */
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

    /** DELETE /api/v1/workouts/{workout} */
    public function destroy(Workout $workout): Response
    {
        $this->workouts->delete($workout);

        return response()->noContent();
    }
}
