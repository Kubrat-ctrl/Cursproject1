<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingIndexRequest;
use App\Http\Resources\WorkoutBookingResource;
use App\Models\ScheduleItem;
use App\Models\WorkoutBooking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings)
    {
    }

    /**
     * История записей текущего клиента
     *
     * Доступ: клиент.
     *
     * @group Записи на занятия
     * @authenticated
     * @queryParam status string Фильтр: booked или cancelled. Example: booked
     * @queryParam page integer Номер страницы. Example: 1
     * @response 200 {"data": [{"id": 15, "status": "booked", "cancelled_at": null, "schedule_item": {"id": 3, "starts_at": "2026-11-10T10:00:00+05:00", "room": "Зал 1"}}], "links": {"first": "...", "last": "...", "prev": null, "next": "..."}, "meta": {"current_page": 1, "last_page": 1, "per_page": 15, "total": 1}}
     * @response 401 {"message": "Требуется авторизация"}
     * @response 403 {"message": "Недостаточно прав для этого действия"}
     * @response 422 {"message": "Ошибка валидации", "errors": {"field": ["Сообщение об ошибке"]}}
     */
    public function index(BookingIndexRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();

        return WorkoutBookingResource::collection(
            $this->bookings->paginateFor($user, $request->validated())
        );
    }

    /**
     * Запись на занятие (ключевой сценарий). Нужен действующий абонемент с остатком посещений
     *
     * Доступ: клиент.
     *
     * @group Записи на занятия
     * @authenticated
     * @urlParam schedule_item integer required Идентификатор занятия. Example: 3
     * @response 201 {"data": {"id": 15, "status": "booked", "cancelled_at": null, "schedule_item": {"id": 3, "starts_at": "2026-11-10T10:00:00+05:00", "room": "Зал 1"}}}
     * @response 401 {"message": "Требуется авторизация"}
     * @response 403 {"message": "Недостаточно прав для этого действия"}
     * @response 404 {"message": "Занятие не найдено"}
     * @response 409 {"message": "Вы уже записаны на это занятие"}
     * @response 409 {"message": "На занятие нет свободных мест"}
     * @response 422 {"message": "Нет действующего абонемента на дату занятия"}
     * @response 422 {"message": "Лимит посещений по абонементу исчерпан"}
     * @response 422 {"message": "Запись закрыта: занятие отменено или уже началось"}
     */
    public function store(Request $request, ScheduleItem $schedule_item): JsonResponse
    {
        $user = $request->user();

        $booking = $this->bookings->book($user, $schedule_item);

        return (new WorkoutBookingResource($booking))->response()->setStatusCode(201);
    }

    /**
     * Отмена своей записи не позднее чем за 2 часа до начала
     *
     * Доступ: клиент (своя запись) или администратор.
     *
     * @group Записи на занятия
     * @authenticated
     * @urlParam booking integer required Идентификатор записи. Example: 15
     * @response 204 
     * @response 401 {"message": "Требуется авторизация"}
     * @response 403 {"message": "Недостаточно прав для этого действия"}
     * @response 404 {"message": "Запись на занятие не найдена"}
     * @response 409 {"message": "Запись уже отменена"}
     * @response 422 {"message": "Отменить запись можно не позднее чем за 2 часа до начала"}
     */
    public function destroy(WorkoutBooking $booking): Response
    {
        Gate::authorize('delete', $booking);

        $this->bookings->cancel($booking);

        return response()->noContent();
    }
}
