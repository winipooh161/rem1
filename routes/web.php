<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Partner\PartnerController;
use App\Http\Controllers\Partner\ProjectController;
use App\Http\Controllers\Partner\ProjectFileController;
use App\Http\Controllers\Partner\ProjectFinanceController;
use App\Http\Controllers\Partner\ProjectScheduleController;
use App\Http\Controllers\Partner\ProjectPhotoController;
use App\Http\Controllers\Partner\ExcelTemplateController;
use App\Http\Controllers\Partner\EstimateController;
use App\Http\Controllers\Partner\EstimateExcelController;
use App\Http\Controllers\Partner\EstimateItemController;
use App\Http\Controllers\Partner\EmployeeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Partner\ProjectCheckController;
use App\Http\Controllers\Estimator\EstimatorController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('home');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

// Маршруты для администраторов
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');
    
    // Маршруты управления пользователями
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->except(['create', 'store'])->names([
        'index' => 'admin.users.index',
        'show' => 'admin.users.show',
        'edit' => 'admin.users.edit',
        'update' => 'admin.users.update',
        'destroy' => 'admin.users.destroy',
    ]);
    
    // Другие маршруты администратора
    
    // Маршрут для обновления шаблонов смет (доступен только админам)
    Route::get('/refresh-estimate-templates', function() {
        if (!auth()->user() || !auth()->user()->is_admin) {
            return redirect()->route('home')->with('error', 'У вас нет доступа к этой функции');
        }
        
        Artisan::call('estimates:generate-templates');
        return redirect()->back()->with('success', 'Шаблоны смет успешно обновлены!');
    })->name('admin.refresh-estimate-templates');
});

// Маршруты для партнеров
Route::middleware(['auth', 'partner'])->prefix('partner')->name('partner.')->group(function () {
    Route::get('/', [PartnerController::class, 'index'])->name('dashboard');
    
    // Маршруты для управления объектами
    Route::resource('projects', ProjectController::class);
    
    // Маршруты для файлов проектов
    Route::prefix('projects/{project}')->group(function () {
        Route::post('/files', [ProjectController::class, 'uploadFile'])->name('project-files.store');
        Route::delete('/files/{file}', [ProjectController::class, 'deleteFile'])->name('project-files.destroy');
        Route::get('/files/{file}/download', [ProjectFileController::class, 'download'])->name('project-files.download');
    });
    
    // Маршруты для работы со сметами
    Route::resource('estimates', App\Http\Controllers\Partner\EstimateController::class);
    
    // Маршруты для управления сотрудниками
    Route::resource('employees', EmployeeController::class)->except(['create', 'edit']);
    
    // Маршруты для Excel-файлов смет
    Route::get('estimates/{estimate}/export', 'App\Http\Controllers\Partner\EstimateExcelController@export')
        ->name('estimates.export');
    Route::get('estimates/{estimate}/data', [App\Http\Controllers\Partner\EstimateExcelController::class, 'getData'])->name('estimates.getData');
    Route::post('estimates/{estimate}/saveExcel', [App\Http\Controllers\Partner\EstimateExcelController::class, 'saveExcelData'])->name('estimates.saveExcel');
    Route::post('estimates/{estimate}/upload', [App\Http\Controllers\Partner\EstimateExcelController::class, 'upload'])->name('estimates.upload');
    
    // Маршруты для управления элементами смет
    Route::post('estimates/{estimate}/items/add', [App\Http\Controllers\Partner\EstimateItemController::class, 'addRow'])->name('estimates.items.add');
    Route::put('estimates/{estimate}/items/table', [App\Http\Controllers\Partner\EstimateItemController::class, 'updateTable'])->name('estimates.items.table');
    
    // Маршруты для Excel шаблонов
    Route::get('excel-templates', [App\Http\Controllers\Partner\ExcelTemplateController::class, 'index'])->name('excel-templates.index');
    Route::get('excel-templates/estimate/{type}', [App\Http\Controllers\Partner\ExcelTemplateController::class, 'downloadEstimateTemplate'])->name('excel-templates.estimate');
  
    // Маршруты для работы с проверками проекта
    Route::prefix('projects/{project}/checks')->group(function () {
        Route::get('/', [ProjectCheckController::class, 'listChecks'])->name('projects.checks.list'); // Новый маршрут для получения списка
        Route::get('/index', [ProjectCheckController::class, 'index'])->name('projects.checks.index'); // Добавлен маршрут
        Route::get('/{check_id}', [ProjectCheckController::class, 'show'])->name('projects.checks.show');
        Route::put('/{check_id}', [ProjectCheckController::class, 'update'])->name('projects.checks.update');
        Route::put('/{check_id}/comment', [ProjectCheckController::class, 'updateComment'])->name('projects.checks.comment');
    });
    
    // Маршруты для работы с фотоотчетом
    Route::prefix('projects/{project}/photos')->group(function () {
        Route::get('/', [ProjectPhotoController::class, 'index'])->name('projects.photos.index');
        Route::post('/', [ProjectPhotoController::class, 'store'])->name('projects.photos.store');
    });
    
    Route::delete('project-photos/{projectPhoto}', [ProjectPhotoController::class, 'destroy'])
        ->name('project-photos.destroy');
    
    // Маршруты для работы с Excel-шаблонами
    Route::prefix('excel-templates')->name('excel-templates.')->group(function () {
        // Маршрут для скачивания шаблона сметы
        Route::get('/estimate/{type}', [ExcelTemplateController::class, 'downloadEstimateTemplate'])->name('estimate');
        
        // Маршрут для получения данных о разделах и работах
        Route::get('/sections-data', [ExcelTemplateController::class, 'getSectionsData'])->name('sections-data');
    });
    
    // Маршруты для работы с графиком проектов
    Route::prefix('projects/{project}/schedule')->group(function () {
        Route::get('/file', [ProjectScheduleController::class, 'getFile'])->name('projects.schedule-file');
        Route::post('/file', [ProjectScheduleController::class, 'saveFile'])->name('projects.schedule-file.store');
        Route::post('/template', [ProjectScheduleController::class, 'createTemplate'])->name('projects.schedule-template');
    });
    
    // Маршруты для калькулятора материалов
    Route::get('/calculator', [App\Http\Controllers\Partner\MaterialCalculatorController::class, 'index'])->name('calculator.index');
    Route::post('/calculator/calculate', [App\Http\Controllers\Partner\MaterialCalculatorController::class, 'calculate'])->name('calculator.calculate');
    Route::post('/calculator/export-pdf', [App\Http\Controllers\Partner\MaterialCalculatorController::class, 'exportPdf'])->name('calculator.export-pdf');
    Route::post('/calculator/save-prices', [App\Http\Controllers\Partner\MaterialCalculatorController::class, 'savePrices'])->name('calculator.save-prices');
    Route::get('/calculator/get-prices', [App\Http\Controllers\Partner\MaterialCalculatorController::class, 'getPrices'])->name('calculator.get-prices');

    // ...existing code...
});

// Маршруты для профиля пользователя
Route::middleware(['auth'])->prefix('profile')->group(function () {
    Route::get('/', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/change-password', [ProfileController::class, 'showChangePasswordForm'])->name('profile.change-password');
    Route::put('/change-password', [ProfileController::class, 'changePassword'])->name('profile.update-password');
});

// Клиентские маршруты - обновленный middleware для доступа администраторов
Route::prefix('client')->name('client.')->middleware(['auth', 'admin.or.client'])->group(function () {
    Route::get('/', [App\Http\Controllers\Client\ClientController::class, 'index'])->name('dashboard');
    
    // Маршруты для проектов клиента
    Route::get('/projects', [App\Http\Controllers\Client\ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [App\Http\Controllers\Client\ProjectController::class, 'show'])->name('projects.show');
    
    // Маршрут для скачивания файлов
    Route::get('/project-files/{file}/download', [App\Http\Controllers\Client\ProjectFileController::class, 'download'])->name('project-files.download');
});

// Маршруты для проверок объектов в панели партнера
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Маршруты для проверок объектов
    Route::get('/projects/{project}/checks', [App\Http\Controllers\Partner\ProjectCheckController::class, 'listChecks'])->name('projects.checks');
    Route::get('/projects/{project}/checks/{check_id}', [App\Http\Controllers\Partner\ProjectCheckController::class, 'show'])->name('projects.check.show');
    Route::post('/projects/{project}/checks/{check_id}', [App\Http\Controllers\Partner\ProjectCheckController::class, 'update'])->name('projects.check.update');
    Route::post('/projects/{project}/checks/{check_id}/comment', [App\Http\Controllers\Partner\ProjectCheckController::class, 'updateComment'])->name('projects.check.comment');
});

// Маршруты для финансовых элементов проекта
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Другие маршруты
    
    // Маршруты для финансов проекта с исправленными методами
    Route::get('projects/{project}/finance', [ProjectFinanceController::class, 'index'])
        ->name('projects.finance.index');
    Route::post('projects/{project}/finance', [ProjectFinanceController::class, 'store'])
        ->name('projects.finance.store');
    Route::get('projects-finance/{id}', [ProjectFinanceController::class, 'show'])
        ->name('projects.finance.show');
    Route::match(['put', 'post'], 'projects-finance/{id}', [ProjectFinanceController::class, 'update'])
        ->name('projects.finance.update');
    Route::delete('projects-finance/{id}', [ProjectFinanceController::class, 'destroy'])
        ->name('projects.finance.destroy');
    Route::post('projects/{project}/finance/positions', [ProjectFinanceController::class, 'updatePositions'])
        ->name('projects.finance.positions');
    Route::get('projects/{project}/finance/export', [ProjectFinanceController::class, 'export'])
        ->name('projects.finance.export');
    
    // Маршруты для финансовых элементов проекта
    Route::get('/projects/{project}/finance-items', 'Partner\ProjectFinanceController@index')->name('projects.finance-items.index');
    Route::post('/projects/{project}/finance-items', 'Partner\ProjectFinanceController@store')->name('projects.finance-items.store');
    Route::get('/finance-items/{item}', 'Partner\ProjectFinanceController@show')->name('finance-items.show');
    Route::put('/finance-items/{item}', 'Partner\ProjectFinanceController@update')->name('finance-items.update');
    Route::delete('/finance-items/{item}', 'Partner\ProjectFinanceController@destroy')->name('finance-items.destroy');
    Route::put('/projects/{project}/finance-items/positions', 'Partner\ProjectFinanceController@updatePositions')->name('projects.finance-items.positions');
    Route::get('/projects/{project}/finance-items/export', 'Partner\ProjectFinanceController@export')->name('projects.finance-items.export');
});

// Маршруты для сметчиков
Route::middleware(['auth', 'estimator'])->prefix('estimator')->name('estimator.')->group(function () {
    Route::get('/', [EstimatorController::class, 'index'])->name('dashboard');
    
    // Маршруты для управления сметами (используем те же контроллеры, что и у партнеров)
    Route::get('/estimates', [EstimateController::class, 'index'])->name('estimates.index');
    Route::get('/estimates/create', [EstimateController::class, 'create'])->name('estimates.create');
    Route::post('/estimates', [EstimateController::class, 'store'])->name('estimates.store');
    Route::get('/estimates/{estimate}', [EstimateController::class, 'show'])->name('estimates.show');
    Route::get('/estimates/{estimate}/edit', [EstimateController::class, 'edit'])->name('estimates.edit');
    Route::put('/estimates/{estimate}', [EstimateController::class, 'update'])->name('estimates.update');
    Route::delete('/estimates/{estimate}', [EstimateController::class, 'destroy'])->name('estimates.destroy');
    
    // Маршруты для Excel-файлов смет
    Route::get('estimates/{estimate}/export', [EstimateExcelController::class, 'export'])->name('estimates.export');
    Route::get('estimates/{estimate}/data', [EstimateExcelController::class, 'getData'])->name('estimates.getData');
    Route::post('estimates/{estimate}/saveExcel', [EstimateExcelController::class, 'saveExcelData'])->name('estimates.saveExcel');
    Route::post('estimates/{estimate}/upload', [EstimateExcelController::class, 'upload'])->name('estimates.upload');
    
    // Маршруты для управления элементами смет
    Route::post('estimates/{estimate}/items/add', [EstimateItemController::class, 'addRow'])->name('estimates.items.add');
    Route::put('estimates/{estimate}/items/table', [EstimateItemController::class, 'updateTable'])->name('estimates.items.table');
});