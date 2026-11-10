<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleItemIndexRequest;
use App\Http\Resources\ScheduleItemResource;
use App\Services\ScheduleItemService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TrainerScheduleController extends Controller
{
    public function __construct(private readonly ScheduleItemService $items)
    {
    }

    /**
     * Занятия текущего тренера
     *
     * Доступ: тренер.
     *
     * @group Расписание
     * @authenticated
     * @queryParam date_from date Начало периода. Example: 2026-11-10
     * @queryParam date_to date Конец периода. Example: 2026-11-16
     * @queryParam per_page integer От 1 до 50. Example: 15
     * @response 200 {"data": [{"id": 3, "starts_at": "2026-11-10T10:00:00+05:00", "ends_at": "2026-11-10T11:00:00+05:00", "room": "Зал 1", "capacity": 12, "status": "scheduled", "bookings_count": 4, "workout": {"id": 1, "name": "Сайкл"}, "trainer": {"id": 2, "name": "Игорь Соколов"}}], "links": {"first": "...", "last": "...", "prev": null, "next": "..."}, "meta": {"current_page": 1, "last_page": 1, "per_page": 15, "total": 1}}
     * @response 401 {"message": "Требуется авторизация"}
     * @response 403 {"message": "Недостаточно прав для этого действия"}
     * @response 422 {"message": "Ошибка валидации", "errors": {"field": ["Сообщение об ошибке"]}}
     */
    public function index(ScheduleItemIndexRequest $request): AnonymousResourceCollection
    {
        $trainer = $request->user();

        $filters = $request->validated();
        $filters['trainer_id'] = $trainer->id;

        return ScheduleItemResource::collection($this->items->list($filters));
    }
}
