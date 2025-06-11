<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
        
        // Обработка исключений для API запросов
        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                Log::error('API Error: ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]);

                // Аутентификация
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Необходима авторизация'
                    ], 401);
                }
                
                // Валидация
                if ($e instanceof ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ошибка валидации данных',
                        'errors' => $e->errors()
                    ], 422);
                }
                
                // Общие ошибки
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Произошла ошибка на сервере',
                    'error_type' => get_class($e)
                ], $statusCode);
            }
            
            return null;
        });
        
        // Добавляем обработчик для всех исключений при Ajax-запросах
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                Log::error('API Error: ' . $e->getMessage(), [
                    'exception' => $e,
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_id' => $request->user() ? $request->user()->id : null
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Произошла ошибка на сервере: ' . $e->getMessage(),
                    'error_type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ], 500);
            }
        });
    }
}
