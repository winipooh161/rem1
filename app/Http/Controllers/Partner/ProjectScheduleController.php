<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProjectScheduleController extends Controller
{
    /**
     * Получает файл расписания для проекта.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function getFile(Project $project)
    {
        $this->authorize('view', $project);
        
        $filePath = "project_schedules/{$project->id}/schedule.xlsx";
        
        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Файл не найден'
            ], 404);
        }
        
        return response()->download(
            Storage::disk('public')->path($filePath),
            "График_проекта_{$project->id}.xlsx"
        );
    }
    
    /**
     * Сохраняет файл расписания для проекта.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function saveFile(Request $request, Project $project)
    {
        $this->authorize('update', $project);
        
        try {
            if (!$request->hasFile('excel_file')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Файл не был отправлен'
                ], 400);
            }
            
            $file = $request->file('excel_file');
            
            // Создаем директорию для хранения файлов расписаний
            $directory = "project_schedules/{$project->id}";
            Storage::disk('public')->makeDirectory($directory);
            
            // Сохраняем файл
            $path = $file->storeAs($directory, 'schedule.xlsx', 'public');
            
            if (!$path) {
                throw new \Exception('Не удалось сохранить файл');
            }
            
            return response()->json([
                'success' => true,
                'message' => 'График успешно сохранен',
                'path' => $path
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при сохранении файла расписания', [
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при сохранении файла: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Создает шаблон расписания для проекта.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function createTemplate(Project $project)
    {
        $this->authorize('update', $project);
        
        try {
            // Создаем новую книгу Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('План-график');
            
            // Заголовки
            $headers = ['Наименование', 'Статус', 'Начало', 'Конец', 'Дней', 'Комментарий'];
            $sheet->fromArray($headers, null, 'A1');
            
            // Стилизация заголовков
            $sheet->getStyle('A1:F1')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);
            
            // Автоширина колонок
            foreach(range('A','F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Создаем директорию и сохраняем файл
            $directory = "project_schedules/{$project->id}";
            Storage::disk('public')->makeDirectory($directory);
            
            $filePath = storage_path('app/public/' . $directory . '/schedule.xlsx');
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);
            
            return response()->json([
                'success' => true,
                'message' => 'Шаблон расписания создан'
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при создании шаблона расписания', [
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании шаблона: ' . $e->getMessage()
            ], 500);
        }
    }
}
