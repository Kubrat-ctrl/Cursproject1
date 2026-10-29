<?php

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\ScheduleItem;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutBooking;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Все запросы к /api/* получают ответ в JSON, даже без заголовка Accept
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        // 422: ошибка валидации
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Ошибка валидации',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // 401: нет или недействителен токен
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Требуется авторизация'], 401);
            }
        });

        // 403: недостаточно прав
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Недостаточно прав для этого действия'], 403);
            }
        });

        // 404: запись или маршрут не найдены
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $previous = $e->getPrevious();

                if ($previous instanceof ModelNotFoundException) {
                    $messages = [
                        Workout::class => 'Вид занятия не найден',
                        MembershipType::class => 'Тип абонемента не найден',
                        ScheduleItem::class => 'Занятие не найдено',
                        Membership::class => 'Абонемент не найден',
                        WorkoutBooking::class => 'Запись на занятие не найдена',
                        User::class => 'Пользователь не найден',
                    ];

                    return response()->json([
                        'message' => $messages[$previous->getModel()] ?? 'Запись не найдена',
                    ], 404);
                }

                return response()->json(['message' => 'Маршрут не найден'], 404);
            }
        });

        // 405: метод не поддерживается
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Метод не поддерживается для этого адреса'], 405);
            }
        });

        // 409: нарушение внешнего ключа или уникальности, не пойманное заранее
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*')) {
                $code = $e->errorInfo[1] ?? null;

                if ($code === 1451) {
                    return response()->json([
                        'message' => 'Запись нельзя удалить: на неё ссылаются другие данные',
                    ], 409);
                }
                if ($code === 1062) {
                    return response()->json(['message' => 'Такая запись уже существует'], 409);
                }
            }
        });

        // Остальные HTTP-ошибки: abort(409, '...'), abort(422, '...') и т. д.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*')) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() !== ''
                    ? $e->getMessage()
                    : (Response::$statusTexts[$status] ?? 'Ошибка');

                return response()->json(['message' => $message], $status, $e->getHeaders());
            }
        });

        // 500: любая непредвиденная ошибка; без стека и SQL, когда APP_DEBUG=false
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') && ! config('app.debug')) {
                return response()->json(['message' => 'Внутренняя ошибка сервера'], 500);
            }
        });
    })->create();