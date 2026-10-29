<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\MembershipTypeController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ScheduleItemBookingController;
use App\Http\Controllers\Api\ScheduleItemController;
use App\Http\Controllers\Api\TrainerScheduleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VisitController;
use App\Http\Controllers\Api\WorkoutController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/health', function () {
    try {
        DB::select('select 1');
        $database = 'ok';
        $status = 200;
    } catch (\Throwable $e) {
        $database = 'error';
        $status = 503;
    }

    return response()->json([
        'app' => config('app.name'),
        'time' => now()->toIso8601String(),
        'database' => $database,
    ], $status);
});

// Версия API v1: все адреса начинаются с /api/v1, имена маршрутов — с v1.
Route::prefix('v1')->name('v1.')->group(function () {
    // Защита маршрутов по ролям (auth:sanctum и проверка роли) добавляется в ПР8:
    // эти маршруты будут вынесены в Route::middleware([...])->group(...).

    // Виды занятий и типы абонементов (ПР6)
    Route::apiResource('workouts', WorkoutController::class);
    Route::apiResource('membership-types', MembershipTypeController::class)
        ->parameters(['membership-types' => 'membership_type']);

    // Расписание (ПР7)
    Route::apiResource('schedule-items', ScheduleItemController::class)
        ->except(['destroy'])
        ->parameters(['schedule-items' => 'schedule_item']);
    Route::post('schedule-items/{schedule_item}/cancel', [ScheduleItemController::class, 'cancel'])
        ->name('schedule-items.cancel');
    Route::get('schedule-items/{schedule_item}/bookings', [ScheduleItemBookingController::class, 'index'])
        ->name('schedule-items.bookings.index');
    Route::post('schedule-items/{schedule_item}/bookings', [BookingController::class, 'store'])
        ->name('schedule-items.bookings.store');

    // Абонементы, записи и посещения (ПР7)
    Route::get('memberships', [MembershipController::class, 'index'])->name('memberships.index');
    Route::post('memberships', [MembershipController::class, 'store'])->name('memberships.store');

    Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::delete('bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
    Route::post('bookings/{booking}/visit', [VisitController::class, 'store'])->name('bookings.visit');

    Route::get('trainer/schedule-items', [TrainerScheduleController::class, 'index'])
        ->name('trainer.schedule-items.index');

    // Пользователи и отчёт (ПР7)
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');

    Route::get('reports/memberships-sales', [ReportController::class, 'membershipsSales'])
        ->name('reports.memberships-sales');
});