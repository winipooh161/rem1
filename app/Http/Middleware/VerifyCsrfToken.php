<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\Log;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Убедитесь, что здесь нет исключений для маршрутов finance
    ];

    /**
     * Переопределение для улучшенного логирования CSRF ошибок
     */
    protected function tokensMatch($request)
    {
        $match = parent::tokensMatch($request);
        
        if (!$match && !$this->inExceptArray($request)) {
            Log::warning('CSRF token mismatch', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'headers' => [
                    'User-Agent' => $request->header('User-Agent'),
                    'X-CSRF-TOKEN' => $request->header('X-CSRF-TOKEN'),
                    'X-Requested-With' => $request->header('X-Requested-With'),
                ],
                'has_session' => $request->hasSession(),
                'has_cookie' => $request->cookies->has($this->config['cookie'])
            ]);
        }
        
        return $match;
    }
}
