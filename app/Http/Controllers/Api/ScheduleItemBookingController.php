<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkoutBookingResource;
use App\Models\ScheduleItem;
use App\Services\BookingService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ScheduleItemBookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings)
    {
    }

    /**
     * Список записавшихся на занятие
     *
     * Доступ: тренер (своё занятие) или администратор.
     *
     * @group Записи на занятия
     * @authenticated
     * @urlParam schedule_item integer required Идентификатор занятия. Example: 3
     * @response 200 {"data": [{"id": 15, "status": "booked", "cancelled_at": null, "schedule_item": {"id": 3, "starts_at": "2026-11-10T10:00:00+05:00", "room": "Зал 1"}}]}
     * @response 401 {"message": "Требуется авторизация"}
     * @response 403 {"message": "Недостаточно прав для этого действия"}
     * @response 404 {"message": "Занятие не найдено"}
     */
    public function index(ScheduleItem $schedule_item): AnonymousResourceCollection
    {
        Gate::authorize('viewBookings', $schedule_item);

        return WorkoutBookingResource::collection(
            $this->bookings->listForScheduleItem($schedule_item)
        );
    }
}
