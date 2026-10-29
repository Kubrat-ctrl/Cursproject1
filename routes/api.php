<?php

use App\Http\Controllers\Api\MembershipTypeController;
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
    Route::apiResource('workouts', WorkoutController::class);
    Route::apiResource('membership-types', MembershipTypeController::class)
        ->parameters(['membership-types' => 'membership_type']);
});
