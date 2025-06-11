<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Services\EstimateTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class ExcelTemplateController extends Controller
{
    protected $estimateTemplateService;
    
    /**
     * Конструктор контроллера
     */
    public function __construct(EstimateTemplateService $estimateTemplateService)
    {
        $this->estimateTemplateService = $estimateTemplateService;
    }
    
    /**
     * Отображает список доступных шаблонов
     */
    public function index()
    {
        $templates = [
            [
                'name' => 'Основная смета (работы)',
                'type' => 'main',
                'description' => 'Шаблон для создания полной сметы на выполнение работ',
                'icon' => 'fa-file-alt'
            ],
            [
                'name' => 'Дополнительная смета',
                'type' => 'additional',
                'description' => 'Шаблон для создания сметы на дополнительные работы',
                'icon' => 'fa-file-plus'
            ],
            [
                'name' => 'Смета на материалы',
                'type' => 'materials',
                'description' => 'Шаблон для создания сметы на материалы',
                'icon' => 'fa-toolbox'
            ]
        ];
        
        return view('partner.excel-templates.index', compact('templates'));
    }
    
    /**
     * Скачивание шаблона сметы
     * 
     * @param Request $request Запрос
     * @param string $type Тип шаблона
     * @return \Illuminate\Http\Response Ответ с файлом или перенаправлением
     */
    public function downloadEstimateTemplate(Request $request, $type = 'main')
    {
        // Проверка корректности типа шаблона
        if (!in_array($type, ['main', 'additional', 'materials'])) {
            return redirect()->route('partner.excel-templates.index')
                ->with('error', 'Указан недопустимый тип шаблона');
        }
        
        // Получаем путь к шаблону
        $templatePath = self::getEstimateTemplatePath($type);
        
        // Проверяем, существует ли шаблон
        if (!file_exists($templatePath)) {
            // Если шаблон не найден, создаем его
            $savePath = storage_path('app/templates/estimates/' . $type . '.xlsx');
            
            // Создаем директорию, если она не существует
            $directory = dirname($savePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Создаем шаблон
            $this->estimateTemplateService->createDefaultTemplate($type, $savePath);
            
            // Проверяем, создался ли шаблон
            if (!file_exists($savePath)) {
                return redirect()->route('partner.excel-templates.index')
                    ->with('error', 'Не удалось создать шаблон сметы');
            }
            
            $templatePath = $savePath;
        }
        
        // Определяем имя файла для скачивания
        $fileName = match($type) {
            'main' => 'Шаблон_основной_сметы.xlsx',
            'additional' => 'Шаблон_дополнительной_сметы.xlsx',
            'materials' => 'Шаблон_сметы_на_материалы.xlsx',
            default => 'Шаблон_сметы.xlsx'
        };
        
        // Отдаем файл пользователю
        return response()->download($templatePath, $fileName);
    }
    
    /**
     * Получает путь к шаблону сметы
     * 
     * @param string $type Тип шаблона
     * @return string Путь к файлу шаблона
     */
    public static function getEstimateTemplatePath($type = 'main')
    {
        return storage_path('app/templates/estimates/' . $type . '.xlsx');
    }
    
    /**
     * Возвращает данные о разделах и работах для использования в модальных окнах
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSectionsData()
    {
        $estimateService = app(EstimateTemplateService::class);
        $sections = $estimateService->getWorkSections();
        
        // Формируем массив работ из всех разделов
        $allWorks = [];
        foreach ($sections as $section) {
            foreach ($section['items'] as $item) {
                $allWorks[] = $item;
            }
        }
        
        return response()->json([
            'success' => true,
            'sections' => $sections,
            'works' => $allWorks
        ]);
    }
}
