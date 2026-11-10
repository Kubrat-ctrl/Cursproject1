<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleItemIndexRequest;
use App\Http\Requests\StoreScheduleItemRequest;
use App\Http\Requests\UpdateScheduleItemRequest;
use App\Http\Resources\ScheduleItemCollection;
use App\Http\Resources\ScheduleItemResource;
use App\Models\ScheduleItem;
use App\Services\ScheduleItemService;
use Illuminate\Http\JsonResponse;

class ScheduleItemController extends Controller
{
    public function __construct(private readonly ScheduleItemService $items)
    {
    }

    /**
     * Расписание: фильтры, поиск, сортировка, пагинация
     *
     * Доступ: любой, в том числе гость.
     *
     * @group Расписание
     * @queryParam trainer_id integer Фильтр по тренеру. Example: 2
     * @queryParam workout_id integer Фильтр по виду занятия. Example: 1
     * @queryParam date_from date Начало периода. Example: 2026-11-10
     * @queryParam date_to date Конец периода, не раньше date_from. Example: 2026-11-16
     * @queryParam search string Часть названия вида занятия или имени тренера. Example: йога
     * @queryParam sort string Поле сортировки: starts_at, capacity, room. Example: starts_at
     * @queryParam order string asc или desc. Example: asc
     * @queryParam per_page integer От 1 до 50, по умолчанию 15. Example: 10
     * @queryParam page integer Номер страницы. Example: 1
     * @response 200 {"data": [{"id": 3, "starts_at": "2026-11-10T10:00:00+05:00", "ends_at": "2026-11-10T11:00:00+05:00", "room": "Зал 1", "capacity": 12, "status": "scheduled", "bookings_count": 4, "workout": {"id": 1, "name": "Сайкл"}, "trainer": {"id": 2, "name": "Игорь Соколов"}}], "links": {"first": "...", "last": "...", "prev": null, "next": "..."}, "meta": {"current_page": 1, "last_page": 1, "per_page": 15, "total": 1}}
     * @response 422 {"message": "Ошибка валидации", "errors": {"sort": ["Сортировать можно по полям: starts_at, capacity, room."]}}
     */
    public function index(ScheduleItemIndexRequest $request): ScheduleItemCollection
    {
        return new ScheduleItemCollection($this->items->list($request->validated()));
    }

    /**
     * Одно занятие с числом занятых мест
     *
     * Доступ: любой, в том числе гость.
     *
     * @group Расписание
     * @urlParam schedule_item integer required Идентификатор занятия. Example: 3
     * @response 200 {"data": {"id": 3, "starts_at": "2026-11-10T10:00:00+05:00", "ends_at": "2026-11-10T11:00:00+05:00", "room": "Зал 1", "capacity": 12, "status": "scheduled", "bookings_count": 4, "workout": {"id": 1, "name": "Сайкл"}, "trainer": {"id": 2, "name": "Игорь Соколов"}}}
     * @response 404 {"message": "Занятие не найдено"}
     */
    public function show(ScheduleItem $schedule_item): ScheduleItemResource
    {
        return new ScheduleItemResource($this->items->details($schedule_item));
    }

    public function store(StoreScheduleItemRequest $request): JsonResponse
    {
        $item = $this->items->create($request->validated());

        return (new ScheduleItemResource($item))->response()->setStatusCode(201);
    }

    public function update(UpdateScheduleItemRequest $request, ScheduleItem $schedule_item): ScheduleItemResource
    {
        return new ScheduleItemResource($this->items->update($schedule_item, $request->validated()));
    }

    /**
     * Отмена занятия: активные записи отменяются, посещения возвращаются на абонементы
     *
     * Доступ: администратор.
     *
     * @group Расписание
     * @authenticated
     * @urlParam schedule_item integer required Идентификатор занятия. Example: 3
     * @response 200 {"data": {"id": 3, "starts_at": "2026-11-10T10:00:00+05:00", "ends_at": "2026-11-10T11:00:00+05:00", "room": "Зал 1", "capacity": 12, "status": "scheduled", "bookings_count": 4, "workout": {"id": 1, "name": "Сайкл"}, "trainer": {"id": 2, "name": "Игорь Соколов"}}}
     * @response 401 {"message": "Требуется авторизация"}
     * @response 403 {"message": "Недостаточно прав для этого действия"}
     * @response 404 {"message": "Занятие не найдено"}
     * @response 409 {"message": "Занятие уже отменено"}
     * @response 422 {"message": "Нельзя отменить занятие, которое уже началось"}
     */
    public function cancel(ScheduleItem $schedule_item): ScheduleItemResource
    {
        return new ScheduleItemResource($this->items->cancel($schedule_item));
    }
}
