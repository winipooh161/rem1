<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProjectController extends Controller
{
    /**
     * Отображает список объектов партнера
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Базовый запрос
        $query = Project::where('partner_id', $user->id);
        
        // Фильтрация по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Фильтрация по поиску
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('client_phone', 'like', "%{$search}%")
                  ;
            });
        }
        
        // Сортировка
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        
        $query->orderBy($sort, $direction);
        
        // Получаем список проектов с пагинацией
        $projects = $query->paginate(10);
        
        // Для AJAX запросов возвращаем только HTML
        if ($request->ajax()) {
            return view('partner.projects.partials.projects-list', compact('projects'))->render();
        }
        
        return view('partner.projects.index', compact('projects'));
    }

    /**
     * Отображает форму для создания нового объекта
     */
    public function create()
    {
        $user = Auth::user();
        
        // Получаем список сметчиков партнера
        $estimators = User::where('partner_id', $user->id)
            ->where('role', 'estimator')
            ->orderBy('name')
            ->get();
            
        return view('partner.projects.create', compact('estimators'));
    }

    /**
     * Сохраняет новый объект в хранилище
     */
    public function store(Request $request)
    {
        // Валидация входных данных
        $request->validate([
            'client_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            
            // Детализированный адрес объекта
            'city' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:200',
            'house_number' => 'nullable|string|max:20',
            'entrance' => 'nullable|string|max:10',
            'apartment_number' => 'nullable|string|max:50',
            
            'area' => 'nullable|numeric|min:0',
            'object_type' => 'nullable|in:apartment,house,office,commercial',
            'work_type' => 'required|in:repair,design,construction',
            'status' => 'required|in:new,in_progress,on_hold,completed,cancelled',
            'estimator_id' => 'nullable|exists:users,id',
            'contract_date' => 'nullable|date',
            'contract_number' => 'nullable|string|max:100',
            'work_start_date' => 'nullable|date',
            'work_amount' => 'nullable|numeric|min:0',
            'materials_amount' => 'nullable|numeric|min:0',
            'camera_link' => 'nullable|url|max:500',
            'schedule_link' => 'nullable|url|max:500',
            'contact_phones' => 'nullable|string|max:1000',
            
            // Паспортные данные клиента
            'passport_series' => 'nullable|string|max:10',
            'passport_number' => 'nullable|string|max:20',
            'passport_issued_by' => 'nullable|string|max:500',
            'passport_issued_date' => 'nullable|date',
            'passport_code' => 'nullable|string|max:20',
            
            // Адрес прописки клиента
            'registration_city' => 'nullable|string|max:100',
            'registration_street' => 'nullable|string|max:200',
            'registration_house' => 'nullable|string|max:20',
            'registration_apartment' => 'nullable|string|max:10',
            'registration_postal_code' => 'nullable|string|max:10',
            
            // Дополнительные данные клиента
            'client_birth_date' => 'nullable|date',
            'client_birth_place' => 'nullable|string|max:300',
            'client_email' => 'nullable|email|max:100',
            
            'description' => 'nullable|string|max:1000',
        ]);
        
        // Проверяем, что сметчик принадлежит текущему партнеру
        if ($request->estimator_id) {
            $estimator = User::find($request->estimator_id);
            if (!$estimator || $estimator->partner_id !== Auth::id() || !$estimator->isEstimator()) {
                return back()->withErrors(['estimator_id' => 'Выбранный сметчик не принадлежит вашей организации']);
            }
        }
        
        // Создание нового проекта
        $project = new Project();
        $project->partner_id = Auth::id();
        $project->client_name = $request->client_name;
        $project->phone = $request->phone;
        $project->address = $request->address;
        
        // Детализированный адрес объекта
        $project->city = $request->city;
        $project->street = $request->street;
        $project->house_number = $request->house_number;
        $project->entrance = $request->entrance;
        $project->apartment_number = $request->apartment_number;
        
        $project->area = $request->area;
        $project->object_type = $request->object_type;
        $project->work_type = $request->work_type;
        $project->status = $request->status;
        $project->estimator_id = $request->estimator_id;
        $project->contract_date = $request->contract_date;
        $project->contract_number = $request->contract_number;
        $project->work_start_date = $request->work_start_date;
        $project->work_amount = $request->work_amount ?? 0;
        $project->materials_amount = $request->materials_amount ?? 0;
        $project->camera_link = $request->camera_link;
        $project->contact_phones = $request->contact_phones;
        
        // Паспортные данные клиента
        $project->passport_series = $request->passport_series;
        $project->passport_number = $request->passport_number;
        $project->passport_issued_by = $request->passport_issued_by;
        $project->passport_issued_date = $request->passport_issued_date;
        $project->passport_code = $request->passport_code;
        
        // Адрес прописки клиента
        $project->registration_city = $request->registration_city;
        $project->registration_street = $request->registration_street;
        $project->registration_house = $request->registration_house;
        $project->registration_apartment = $request->registration_apartment;
        $project->registration_postal_code = $request->registration_postal_code;
        
        // Дополнительные данные клиента
        $project->client_birth_date = $request->client_birth_date;
        $project->client_birth_place = $request->client_birth_place;
        $project->client_email = $request->client_email;
        
        $project->description = $request->description;
        $project->save();
        
        return redirect()->route('partner.projects.show', $project)
                         ->with('success', 'Объект успешно создан.');
    }

    /**
     * Отображает указанный объект
     */
    public function show(Project $project)
    {
        // Проверка авторизации
        $this->authorize('view', $project);
        
        // Загружаем связанные сметы
        $estimates = $project->estimates()->orderBy('created_at', 'desc')->get();
        
        // Загружаем связанные файлы
        $files = $project->files()->orderBy('created_at', 'desc')->get();
        
        return view('partner.projects.show', compact('project', 'estimates', 'files'));
    }

    /**
     * Отображает форму для редактирования объекта
     */
    public function edit(Project $project)
    {
        // Проверка авторизации
        $this->authorize('update', $project);
        
        $user = Auth::user();
        
        // Получаем список сметчиков партнера
        $estimators = User::where('partner_id', $user->id)
            ->where('role', 'estimator')
            ->orderBy('name')
            ->get();
        
        return view('partner.projects.edit', compact('project', 'estimators'));
    }

    /**
     * Обновляет указанный объект в хранилище
     */
    public function update(Request $request, Project $project)
    {
        // Проверка авторизации
        $this->authorize('update', $project);
        
        // Валидация входных данных (аналогично store методу)
        $request->validate([
            'client_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            
            // Детализированный адрес объекта
            'city' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:200',
            'house_number' => 'nullable|string|max:20',
            'entrance' => 'nullable|string|max:10',
            'apartment_number' => 'nullable|string|max:50',
            
            'area' => 'nullable|numeric|min:0',
            'object_type' => 'nullable|in:apartment,house,office,commercial',
            'work_type' => 'required|in:repair,design,construction',
            'status' => 'required|in:new,in_progress,on_hold,completed,cancelled',
            'estimator_id' => 'nullable|exists:users,id',
            'contract_date' => 'nullable|date',
            'contract_number' => 'nullable|string|max:100',
            'work_start_date' => 'nullable|date',
            'work_amount' => 'nullable|numeric|min:0',
            'materials_amount' => 'nullable|numeric|min:0',
            'camera_link' => 'nullable|url|max:500',
            'schedule_link' => 'nullable|url|max:500',
            'contact_phones' => 'nullable|string|max:1000',
            
            // Паспортные данные клиента
            'passport_series' => 'nullable|string|max:10',
            'passport_number' => 'nullable|string|max:20',
            'passport_issued_by' => 'nullable|string|max:500',
            'passport_issued_date' => 'nullable|date',
            'passport_code' => 'nullable|string|max:20',
            
            // Адрес прописки клиента
            'registration_city' => 'nullable|string|max:100',
            'registration_street' => 'nullable|string|max:200',
            'registration_house' => 'nullable|string|max:20',
            'registration_apartment' => 'nullable|string|max:10',
            'registration_postal_code' => 'nullable|string|max:10',
            
            // Дополнительные данные клиента
            'client_birth_date' => 'nullable|date',
            'client_birth_place' => 'nullable|string|max:300',
            'client_email' => 'nullable|email|max:100',
            
            'description' => 'nullable|string|max:1000',
        ]);
        
        // Проверяем, что сметчик принадлежит текущему партнеру
        if ($request->estimator_id) {
            $estimator = User::find($request->estimator_id);
            if (!$estimator || $estimator->partner_id !== Auth::id()) {
                return back()->withErrors(['estimator_id' => 'Выбранный сметчик не принадлежит вашей организации']);
            }
        }
        
        // Обновление проекта
        $project->client_name = $request->client_name;
        $project->phone = $request->phone;
        $project->address = $request->address;
        
        // Детализированный адрес объекта
        $project->city = $request->city;
        $project->street = $request->street;
        $project->house_number = $request->house_number;
        $project->entrance = $request->entrance;
        $project->apartment_number = $request->apartment_number;
        
        $project->area = $request->area;
        $project->object_type = $request->object_type;
        $project->work_type = $request->work_type;
        $project->status = $request->status;
        $project->estimator_id = $request->estimator_id;
        $project->contract_date = $request->contract_date;
        $project->contract_number = $request->contract_number;
        $project->work_start_date = $request->work_start_date;
        $project->work_amount = $request->work_amount ?? 0;
        $project->materials_amount = $request->materials_amount ?? 0;
        $project->camera_link = $request->camera_link;
        $project->contact_phones = $request->contact_phones;
        
        // Паспортные данные клиента
        $project->passport_series = $request->passport_series;
        $project->passport_number = $request->passport_number;
        $project->passport_issued_by = $request->passport_issued_by;
        $project->passport_issued_date = $request->passport_issued_date;
        $project->passport_code = $request->passport_code;
        
        // Адрес прописки клиента
        $project->registration_city = $request->registration_city;
        $project->registration_street = $request->registration_street;
        $project->registration_house = $request->registration_house;
        $project->registration_apartment = $request->registration_apartment;
        $project->registration_postal_code = $request->registration_postal_code;
        
        // Дополнительные данные клиента
        $project->client_birth_date = $request->client_birth_date;
        $project->client_birth_place = $request->client_birth_place;
        $project->client_email = $request->client_email;
        
        $project->description = $request->description;
        $project->save();
        
        return redirect()->route('partner.projects.show', $project)
                         ->with('success', 'Объект успешно обновлен.');
    }

    /**
     * Удаляет указанный объект из хранилища
     */
    public function destroy(Project $project)
    {
        // Проверка авторизации
        $this->authorize('delete', $project);
        
        // Удаление связанных файлов
        foreach ($project->files as $file) {
            if (Storage::disk('public')->exists($file->path)) {
                Storage::disk('public')->delete($file->path);
            }
            $file->delete();
        }
        
        // Удаление связанных смет
        foreach ($project->estimates as $estimate) {
            if ($estimate->file_path && Storage::disk('public')->exists($estimate->file_path)) {
                Storage::disk('public')->delete($estimate->file_path);
            }
            // Удаление элементов сметы
            $estimate->items()->delete();
            $estimate->delete();
        }
        
        // Удаление проекта
        $project->delete();
        
        return redirect()->route('partner.projects.index')
                         ->with('success', 'Объект успешно удален вместе со всеми связанными файлами и сметами.');
    }

    /**
     * Загружает файл для проекта
     */
    public function uploadFile(Request $request, Project $project)
    {
        // Проверка авторизации
        $this->authorize('update', $project);
        
        // Валидация входных данных
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB максимум
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
        
        // Загрузка файла
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            
            // Генерируем уникальное имя файла
            $filename = time() . '_' . $file->getClientOriginalName();
            
            // Путь для хранения
            $path = $file->storeAs(
                'projects/' . $project->id . '/files',
                $filename,
                'public'
            );
            
            // Сохраняем информацию о файле в базе данных
            $projectFile = new ProjectFile();
            $projectFile->project_id = $project->id;
            $projectFile->name = $request->name;
            $projectFile->description = $request->description;
            $projectFile->original_name = $file->getClientOriginalName();
            $projectFile->path = $path;
            $projectFile->size = $file->getSize();
            $projectFile->mime_type = $file->getMimeType();
            $projectFile->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Файл успешно загружен',
                'file' => [
                    'id' => $projectFile->id,
                    'name' => $projectFile->name,
                    'size' => $projectFile->size,
                    'url' => Storage::url($path),
                ]
            ]);
        }
        
        return response()->json(['success' => false, 'message' => 'Ошибка при загрузке файла'], 400);
    }

    /**
     * Удаляет файл проекта
     */
    public function deleteFile(ProjectFile $file)
    {
        // Проверка авторизации
        $this->authorize('delete', $file->project);
        
        // Удаляем файл из хранилища
        if (Storage::disk('public')->exists($file->path)) {
            Storage::disk('public')->delete($file->path);
        }
        
        // Удаляем запись из базы данных
        $file->delete();
        
        return response()->json(['success' => true]);
    }
}
