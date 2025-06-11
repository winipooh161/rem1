<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectFileController extends Controller
{
    /**
     * Загрузить новый файл для проекта.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Project $project)
    {
        // Проверяем права доступа
        $this->authorize('update', $project);

        // Валидация запроса
        $request->validate([
            'file' => 'required|file|max:10240', // Максимум 10MB
            'file_type' => 'required|in:design,scheme,document,contract,other',
            'description' => 'nullable|string|max:255',
        ]);

        // Получаем файл из запроса
        $file = $request->file('file');
        
        // Генерируем уникальное имя файла
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // Сохраняем файл в хранилище
        $path = $file->storeAs(
            'project_files/' . $project->id,
            $filename,
            'public'
        );
        
        if (!$path) {
            return response()->json(['error' => 'Не удалось загрузить файл.'], 500);
        }
        
        // Создаем запись в базе данных
        $projectFile = new ProjectFile([
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'file_type' => $request->file_type,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'description' => $request->description,
        ]);
        
        $project->files()->save($projectFile);
        
        // Возвращаем успешный ответ с данными файла
        return response()->json([
            'file' => $projectFile,
            'success' => 'Файл успешно загружен',
        ]);
    }

    /**
     * Скачать файл проекта.
     *
     * @param  \App\Models\ProjectFile  $projectFile
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(ProjectFile $projectFile)
    {
        // Проверяем права доступа
        $this->authorize('view', $projectFile->project);
        
        $path = storage_path('app/public/project_files/' . $projectFile->project_id . '/' . $projectFile->filename);
        
        if (!file_exists($path)) {
            abort(404, 'Файл не найден.');
        }
        
        return response()->download($path, $projectFile->original_name);
    }

    /**
     * Удалить файл проекта.
     *
     * @param  \App\Models\ProjectFile  $projectFile
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroy(ProjectFile $projectFile)
    {
        // Проверяем права доступа
        $this->authorize('update', $projectFile->project);
        
        // Получаем проект и тип файла перед удалением
        $project = $projectFile->project;
        $fileType = $projectFile->file_type;
        
        // Удаляем файл из хранилища
        Storage::disk('public')->delete('project_files/' . $project->id . '/' . $projectFile->filename);
        
        // Удаляем запись из базы данных
        $projectFile->delete();
        
        // Если запрос AJAX, возвращаем JSON
        if (request()->ajax()) {
            return response()->json(['success' => 'Файл успешно удален']);
        }
        
        // Иначе перенаправляем обратно с сообщением
        return redirect()->back()->with('success', 'Файл успешно удален');
    }
}
