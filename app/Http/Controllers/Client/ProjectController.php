<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProjectController extends Controller
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
     * Display a listing of the client's projects.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $filters = $request->only(['search', 'status', 'work_type', 'branch']);
        
        // Получаем проекты, связанные с номером телефона клиента
        $query = Project::where('phone', $user->phone);
        
        // Применяем фильтры
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('contract_number', 'like', "%{$search}%");
            });
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['work_type'])) {
            $query->where('work_type', $filters['work_type']);
        }
        
        if (!empty($filters['branch'])) {
            $query->where('branch', $filters['branch']);
        }
        
        $projects = $query->orderBy('updated_at', 'desc')
                         ->paginate(9)
                         ->withQueryString();
        
        return view('client.projects.index', compact('projects', 'filters'));
    }
    
    /**
     * Display the specified project details.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Project $project)
    {
        // Проверяем, что проект принадлежит клиенту
        if ($project->phone !== $request->user()->phone) {
            abort(403, 'У вас нет доступа к этому объекту.');
        }
        
        // Получаем данные расписания, если они есть
        $scheduleItems = [];
        $scheduleMetadata = null;
        
        $scheduleFile = $this->getScheduleFile($project->id);
        if ($scheduleFile) {
            $scheduleData = $this->getScheduleData($project->id);
            if ($scheduleData && isset($scheduleData['data'])) {
                $scheduleItems = $scheduleData['data'];
                $scheduleMetadata = $scheduleData['metadata'] ?? null;
            }
        }
        
        return view('client.projects.show', compact('project', 'scheduleItems', 'scheduleMetadata'));
    }
    
    /**
     * Get schedule file path if exists.
     *
     * @param int $projectId
     * @return string|null
     */
    protected function getScheduleFile($projectId)
    {
        $paths = [
            'project_schedules/' . $projectId . '/schedule.xlsx',
            'project_schedules/' . $projectId . '/schedule.xls'
        ];
        
        foreach ($paths as $path) {
            if (Storage::disk('public')->exists($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Get schedule data from storage.
     *
     * @param int $projectId
     * @return array|null
     */
    protected function getScheduleData($projectId)
    {
        $path = 'project_schedules/' . $projectId . '/data.json';
        
        if (Storage::disk('public')->exists($path)) {
            $jsonData = Storage::disk('public')->get($path);
            return json_decode($jsonData, true);
        }
        
        return null;
    }
}
