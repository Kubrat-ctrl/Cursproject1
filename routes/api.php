<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\MembershipTypeController;
use App\Http\Controllers\Api\ProfileController;
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

    // ---------- Открытые маршруты (без токена) ----------
    Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::apiResource('workouts', WorkoutController::class)
        ->only(['index', 'show']);
    Route::apiResource('membership-types', MembershipTypeController::class)
        ->only(['index', 'show'])
        ->parameters(['membership-types' => 'membership_type']);
    Route::apiResource('schedule-items', ScheduleItemController::class)
        ->only(['index', 'show'])
        ->parameters(['schedule-items' => 'schedule_item']);

    // ---------- Нужен токен ----------
    Route::middleware('auth:sanctum')->group(function () {

        // Любой авторизованный пользователь
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');

        // Администратор
        Route::middleware('role:admin')->group(function () {
            Route::apiResource('workouts', WorkoutController::class)
                ->only(['store', 'update', 'destroy']);
            Route::apiResource('membership-types', MembershipTypeController::class)
                ->only(['store', 'update', 'destroy'])
                ->parameters(['membership-types' => 'membership_type']);
            Route::apiResource('schedule-items', ScheduleItemController::class)
                ->only(['store', 'update'])
                ->parameters(['schedule-items' => 'schedule_item']);
            Route::post('schedule-items/{schedule_item}/cancel', [ScheduleItemController::class, 'cancel'])
                ->name('schedule-items.cancel');

            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');

            Route::get('reports/memberships-sales', [ReportController::class, 'membershipsSales'])
                ->name('reports.memberships-sales');
        });

        // Клиент
        Route::middleware('role:client')->group(function () {
            Route::get('memberships', [MembershipController::class, 'index'])->name('memberships.index');
            Route::post('memberships', [MembershipController::class, 'store'])->name('memberships.store');

            Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
            Route::post('schedule-items/{schedule_item}/bookings', [BookingController::class, 'store'])
                ->name('schedule-items.bookings.store');
        });

        // Клиент и администратор (чужие записи отсекает политика)
        Route::delete('bookings/{booking}', [BookingController::class, 'destroy'])
            ->middleware('role:client,admin')
            ->name('bookings.destroy');

        // Тренер и администратор (чужие занятия отсекает политика)
        Route::middleware('role:trainer,admin')->group(function () {
            Route::get('schedule-items/{schedule_item}/bookings', [ScheduleItemBookingController::class, 'index'])
                ->name('schedule-items.bookings.index');
            Route::post('bookings/{booking}/visit', [VisitController::class, 'store'])
                ->name('bookings.visit');
        });

        // Тренер
        Route::get('trainer/schedule-items', [TrainerScheduleController::class, 'index'])
            ->middleware('role:trainer')
            ->name('trainer.schedule-items.index');
    });
});