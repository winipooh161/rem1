<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFinanceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

class ProjectFinanceController extends Controller
{
    /**
     * Получить все финансовые элементы проекта.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Project $project)
    {
        try {
            $this->authorize('view', $project);

            $items = $project->financeItems()
                ->orderBy('position')
                ->orderBy('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $items
            ]);
        } catch (Exception $e) {
            Log::error('Ошибка при получении финансовых элементов: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке данных: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Сохранить новый финансовый элемент.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Project $project)
    {
        try {
            $this->authorize('update', $project);

            // Валидация данных запроса
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|string|in:main_work,main_material,additional_work,additional_material,transportation',
                'total_amount' => 'required|numeric|min:0',
                'paid_amount' => 'required|numeric|min:0',
                'payment_date' => 'nullable|date',
            ]);

            // Добавляем идентификатор проекта к данным
            $validated['project_id'] = $project->id;
            
            // Определяем позицию (добавляем в конец)
            $lastPosition = $project->financeItems()
                ->where('type', $validated['type'])
                ->max('position') ?? 0;
            
            $validated['position'] = $lastPosition + 1;

            // Создаем новый элемент
            $item = ProjectFinanceItem::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Элемент успешно добавлен',
                'data' => $item,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Ошибка при сохранении финансового элемента: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'request' => $request->all(),
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при сохранении: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить конкретный финансовый элемент.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $item = ProjectFinanceItem::findOrFail($id);
            $this->authorize('view', $item->project);

            return response()->json([
                'success' => true,
                'data' => $item
            ]);
        } catch (Exception $e) {
            Log::error('Ошибка при получении финансового элемента: ' . $e->getMessage(), [
                'item_id' => $id,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке элемента: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обновить существующий финансовый элемент.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $item = ProjectFinanceItem::findOrFail($id);
            $this->authorize('update', $item->project);

            // Валидация данных запроса
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'type' => 'sometimes|required|string|in:main_work,main_material,additional_work,additional_material,transportation',
                'total_amount' => 'sometimes|required|numeric|min:0',
                'paid_amount' => 'sometimes|required|numeric|min:0',
                'payment_date' => 'nullable|date',
            ]);

            $item->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Элемент успешно обновлен',
                'data' => $item
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Ошибка при обновлении финансового элемента: ' . $e->getMessage(), [
                'item_id' => $id,
                'request' => $request->all(),
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить финансовый элемент.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $item = ProjectFinanceItem::findOrFail($id);
            $this->authorize('update', $item->project);
            
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Элемент успешно удален'
            ]);
        } catch (Exception $e) {
            Log::error('Ошибка при удалении финансового элемента: ' . $e->getMessage(), [
                'item_id' => $id,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обновить позиции элементов финансов (для сортировки перетаскиванием).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePositions(Request $request, Project $project)
    {
        try {
            $this->authorize('update', $project);

            $request->validate([
                'positions' => 'required|array',
                'positions.*.id' => 'required|integer|exists:project_finance_items,id',
                'positions.*.position' => 'required|integer|min:1',
            ]);

            DB::beginTransaction();

            foreach ($request->positions as $item) {
                ProjectFinanceItem::where('id', $item['id'])
                    ->where('project_id', $project->id)
                    ->update(['position' => $item['position']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Позиции успешно обновлены'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Ошибка при обновлении позиций финансовых элементов: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'request' => $request->all(),
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении позиций: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Экспорт финансовых данных проекта.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function export(Project $project)
    {
        try {
            $this->authorize('view', $project);
            
            // Заглушка для экспорта данных
            // Здесь должна быть реализация экспорта в Excel/PDF
            
            return response()->json([
                'success' => true,
                'message' => 'Функция экспорта находится в разработке'
            ]);
        } catch (Exception $e) {
            Log::error('Ошибка при экспорте финансовых данных: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при экспорте данных: ' . $e->getMessage()
            ], 500);
        }
    }
}
