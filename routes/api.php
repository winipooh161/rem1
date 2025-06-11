<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Partner\ProjectScheduleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API маршруты для работы с графиком проекта
Route::middleware('auth')->group(function () {
    // Получение списка элементов графика с фильтрацией
    Route::get('/projects/{project}/schedule', [ProjectScheduleController::class, 'getItems']);
    
    // Работа с элементами графика
    Route::post('/projects/{project}/schedule/items', [ProjectScheduleController::class, 'storeItem']);
    Route::put('/projects/{project}/schedule/items/{item}', [ProjectScheduleController::class, 'updateItem']);
    Route::delete('/projects/{project}/schedule/items/{item}', [ProjectScheduleController::class, 'destroyItem']);
    
    // Обновление ссылки на график
    Route::put('/projects/{project}/schedule/link', [ProjectScheduleController::class, 'updateLink']);
    
    // Импорт из Excel
    Route::post('/projects/{project}/schedule/import', [ProjectScheduleController::class, 'importFromExcel']);
});
