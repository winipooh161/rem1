<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EstimateController extends Controller
{
    protected $excelController;
    
    /**
     * Конструктор с внедрением зависимостей
     */
    public function __construct(EstimateExcelController $excelController)
    {
        $this->excelController = $excelController;
    }
    
    /**
     * Отображает список смет
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Estimate::query();
        
        // Если пользователь администратор, показываем все сметы
        if ($user->isAdmin()) {
            $query->with('project');
        } 
        // Если пользователь сметчик, показываем только его собственные сметы
        elseif ($user->isEstimator()) {
            // Сметчик видит только сметы, созданные им самим
            $query->where('user_id', $user->id)->with('project');
        } 
        // Если пользователь партнер, показываем сметы его проектов + сметы его сметчиков
        else {
            // Получаем ID проектов партнера
            $projectIds = Project::where('partner_id', $user->id)->pluck('id');
            
            // Получаем ID сметчиков партнера
            $estimatorIds = \App\Models\User::where('partner_id', $user->id)
                ->where('role', 'estimator')
                ->pluck('id');
            
            // Показываем сметы проектов партнера ИЛИ сметы, созданные его сметчиками
            $query->where(function($q) use ($projectIds, $estimatorIds, $user) {
                $q->whereIn('project_id', $projectIds)
                  ->orWhereIn('user_id', $estimatorIds)
                  ->orWhere('user_id', $user->id); // плюс собственные сметы партнера
            })->with('project');
        }
        
        // Применяем фильтры
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Получение списка смет с пагинацией
        $estimates = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Получаем список проектов для фильтра в зависимости от роли
        if ($user->isAdmin()) {
            $projects = Project::orderBy('client_name')->get(['id', 'client_name', 'address']);
        } elseif ($user->isEstimator()) {
            // Сметчик видит только назначенные ему проекты от своего партнера
            $projects = Project::where('estimator_id', $user->id)
                            ->whereHas('partner', function($query) use ($user) {
                                $query->where('id', $user->partner_id);
                            })
                            ->orderBy('client_name')
                            ->get(['id', 'client_name', 'address']);
        } else {
            // Партнер видит все свои проекты
            $projects = Project::where('partner_id', $user->id)
                            ->orderBy('client_name')
                            ->get(['id', 'client_name', 'address']);
        }
        
        // Для AJAX-запросов возвращаем только часть представления
        if ($request->ajax() || $request->wantsJson()) {
            return view('partner.estimates.partials.estimates-list', compact('estimates'))->render();
        }
        
        return view('partner.estimates.index', compact('estimates', 'projects'));
    }

    /**
     * Показывает форму для создания новой сметы
     */
    public function create()
    {
        $user = Auth::user();
        
        // Получаем проекты для выпадающего списка в зависимости от роли пользователя
        if ($user->isAdmin()) {
            $projects = Project::orderBy('client_name')->get();
        } elseif ($user->isEstimator()) {
            // Сметчик видит только назначенные ему проекты от своего партнера
            $projects = Project::where('estimator_id', $user->id)
                            ->whereHas('partner', function($query) use ($user) {
                                $query->where('id', $user->partner_id);
                            })
                            ->orderBy('client_name')
                            ->get();
        } else {
            // Партнеры видят все свои проекты
            $projects = Project::where('partner_id', $user->id)
                            ->orderBy('client_name')
                            ->get();
        }
        
        return view('partner.estimates.create', compact('projects'));
    }

    /**
     * Сохраняет новую смету в хранилище
     */
    public function store(Request $request)
    {
        // Валидация входных данных
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'type' => 'required|in:main,additional,materials',
            'status' => 'required|in:draft,pending,approved', 
            'notes' => 'nullable|string',
        ]);
        
        // Создание новой сметы
        $estimate = new Estimate();
        $estimate->name = $validatedData['name'];
        $estimate->project_id = $validatedData['project_id'];
        $estimate->type = $validatedData['type'];
        $estimate->status = $validatedData['status'];
        $estimate->description = $validatedData['notes'] ?? null;
        $estimate->user_id = Auth::id();
        $estimate->total_amount = 0; // Изначально сумма 0
        $estimate->save();
        
        // Создаем шаблон Excel для сметы через специализированный контроллер
        $this->excelController->createInitialExcelFile($estimate);
        
        return redirect()->route('partner.estimates.edit', $estimate)
                         ->with('success', 'Смета успешно создана. Теперь вы можете заполнить ее данными.');
    }

    /**
     * Отображает указанную смету
     */
    public function show(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        // Получаем данные Excel, если файл существует
        $excelData = null;
        
        return view('partner.estimates.show', compact('estimate', 'excelData'));
    }

    /**
     * Показывает форму для редактирования указанной сметы
     */
    public function edit(Estimate $estimate)
    {
        $this->authorize('update', $estimate);
        
        $user = Auth::user();
        
        // Получаем проекты для выпадающего списка в зависимости от роли пользователя
        if ($user->isAdmin()) {
            $projects = Project::orderBy('client_name')->get();
        } elseif ($user->isEstimator()) {
            // Сметчик видит только назначенные ему проекты от своего партнера
            $projects = Project::where('estimator_id', $user->id)
                            ->whereHas('partner', function($query) use ($user) {
                                $query->where('id', $user->partner_id);
                            })
                            ->orderBy('client_name')
                            ->get();
        } else {
            // Партнеры видят все свои проекты
            $projects = Project::where('partner_id', $user->id)
                            ->orderBy('client_name')
                            ->get();
        }
        
        return view('partner.estimates.edit', compact('estimate', 'projects'));
    }

    /**
     * Обновляет указанную смету в хранилище
     */
    public function update(Request $request, Estimate $estimate)
    {
        $this->authorize('update', $estimate);
        
        // Валидация входных данных
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'type' => 'required|in:main,additional,materials',
            'status' => 'required|in:draft,sent,approved,rejected',
            'notes' => 'nullable|string',
        ]);
        
        // Обновление сметы
        $estimate->name = $validatedData['name'];
        $estimate->project_id = $validatedData['project_id'];
        $estimate->type = $validatedData['type'];
        $estimate->status = $validatedData['status'];
        $estimate->description = $validatedData['notes'] ?? null;
        $estimate->save();
        
        return redirect()->route('partner.estimates.show', $estimate)
                         ->with('success', 'Смета успешно обновлена.');
    }

    /**
     * Удаляет указанную смету из хранилища
     */
    public function destroy(Estimate $estimate)
    {
        $this->authorize('delete', $estimate);
        
        // Удаляем файл Excel, если он существует
        if ($estimate->file_path && Storage::disk('public')->exists($estimate->file_path)) {
            Storage::disk('public')->delete($estimate->file_path);
        }
        
        // Удаляем смету и связанные элементы
        $estimate->items()->delete();
        $estimate->delete();
        
        return redirect()->route('partner.estimates.index')
                         ->with('success', 'Смета успешно удалена.');
    }
}
