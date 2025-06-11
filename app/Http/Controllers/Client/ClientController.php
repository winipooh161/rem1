<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('client');
    }
    
    /**
     * Показать панель управления клиента.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Получаем проекты клиента
        $projects = $user->clientProjects;
        $activeProjects = $projects->where('status', 'active')->count();
        $completedProjects = $projects->where('status', 'completed')->count();
        
        return view('client.dashboard', compact('projects', 'activeProjects', 'completedProjects'));
    }
}
