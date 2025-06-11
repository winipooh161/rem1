<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientMiddleware
{
    /**
     * Проверяет, что пользователь является клиентом или администратором.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
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
