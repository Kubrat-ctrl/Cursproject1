<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Журнал запросов к API: метод, адрес, пользователь, код ответа и время выполнения.
 * Тело запроса и заголовок Authorization не записываются, поэтому пароли и токены
 * в журнал не попадают.
 */
class LogApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        $response = $next($request);

        $status = $response->getStatusCode();

        Log::log($status >= 500 ? 'error' : 'info', 'API запрос', [
            'method' => $request->method(),
            'url' => $request->path(),
            'user_id' => $request->user()?->id,
            'status' => $status,
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 1),
        ]);

        return $response;
    }
}
