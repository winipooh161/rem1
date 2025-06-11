<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOrClientMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        // Предоставляем доступ, если пользователь администратор или клиент
        if (auth()->user()->isAdmin() || auth()->user()->isClient()) {
            return $next($request);
        }
        
        return redirect()->route('home')->with('error', 'У вас нет доступа к этому разделу');
    }
}
