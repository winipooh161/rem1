<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectFileController extends Controller
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
     * Download a project file.
     *
     * @param  \App\Models\ProjectFile  $file
     * @return \Illuminate\Http\Response
     */
    public function download(ProjectFile $file)
    {
        // Проверяем, что файл принадлежит объекту клиента
        $project = Project::findOrFail($file->project_id);
        
        if ($project->phone !== auth()->user()->phone) {
            abort(403, 'У вас нет доступа к этому файлу.');
        }
        
        $path = 'project_files/' . $file->project_id . '/' . $file->filename;
        
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Файл не найден.');
        }
        
        return Storage::disk('public')->download($path, $file->original_name);
    }
}
