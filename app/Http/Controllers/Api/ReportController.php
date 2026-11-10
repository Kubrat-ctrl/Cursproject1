<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PopularWorkoutsRequest;
use App\Http\Requests\SalesReportRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    /**
     * Продажи абонементов за период: количество и выручка по типам
     *
     * Доступ: администратор.
     *
     * @group Отчёты
     * @authenticated
     * @queryParam from date required Начало периода. Example: 2026-11-01
     * @queryParam to date required Конец периода. Example: 2026-11-30
     * @response 200 {"data": {"from": "2026-11-01", "to": "2026-11-30", "total_sold": 2, "total_revenue": 7000, "items": [{"membership_type_id": 2, "membership_type": "Месяц, 12 посещений", "sold": 2, "revenue": 7000}]}}
     * @response 401 {"message": "Требуется авторизация"}
     * @response 403 {"message": "Недостаточно прав для этого действия"}
     * @response 422 {"message": "Ошибка валидации", "errors": {"field": ["Сообщение об ошибке"]}}
     */
    public function membershipsSales(SalesReportRequest $request): JsonResponse
    {
        $data = $request->validated();

        return response()->json([
            'data' => $this->reports->membershipsSales($data['from'], $data['to']),
        ]);
    }

    /**
     * Число записей по статусам
     *
     * Доступ: администратор.
     *
     * @group Отчёты
     * @authenticated
     * @response 200 {"data": {"booked": 24, "cancelled": 6}}
     * @response 401 {"message": "Требуется авторизация"}
     * @response 403 {"message": "Недостаточно прав для этого действия"}
     */
    public function bookingsByStatus(): JsonResponse
    {
        return response()->json(['data' => $this->reports->bookingsByStatus()]);
    }

    public function popularWorkouts(PopularWorkoutsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : now()->subDays(30)->startOfDay();
        $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : now()->endOfDay();

        return response()->json([
            'data' => $this->reports->popularWorkouts($from, $to, $data['limit'] ?? 10),
        ]);
    }
}
