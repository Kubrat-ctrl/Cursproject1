<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VisitResource;
use App\Models\WorkoutBooking;
use App\Services\VisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class VisitController extends Controller
{
    public function __construct(private readonly VisitService $visits)
    {
    }

    /**
     * Отметка посещения. Доступна не раньше чем за 30 минут до начала занятия
     *
     * Доступ: тренер (своё занятие) или администратор.
     *
     * @group Посещения
     * @authenticated
     * @urlParam booking integer required Идентификатор записи. Example: 15
     * @response 201 {"data": {"id": 7, "checked_in_at": "2026-11-10T09:45:00+05:00"}}
     * @response 401 {"message": "Требуется авторизация"}
     * @response 403 {"message": "Недостаточно прав для этого действия"}
     * @response 404 {"message": "Запись на занятие не найдена"}
     * @response 409 {"message": "Посещение уже отмечено"}
     * @response 422 {"message": "Отмечать посещение можно не раньше чем за 30 минут до начала занятия"}
     */
    public function store(WorkoutBooking $booking): JsonResponse
    {
        Gate::authorize('markVisit', $booking);

        $visit = $this->visits->mark($booking);

        return (new VisitResource($visit))->response()->setStatusCode(201);
    }
}
