<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Services\EstimateTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class EstimateExcelController extends Controller
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
     * Экспортирует смету в файл Excel
     */
    public function export(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        // Проверяем, существует ли файл
        if (!$estimate->file_path || !Storage::disk('public')->exists($estimate->file_path)) {
            // Если файла нет, создаем его
            $this->createInitialExcelFile($estimate);
        } else {
            // Если файл существует, убедимся что формулы в нем правильные
            $this->enhanceExistingFileFormatting($estimate);
        }
        
        // Получаем путь к файлу
        $filePath = storage_path('app/public/' . $estimate->file_path);
        
        // Формируем имя файла для загрузки
        $fileName = $estimate->file_name ?? ('Смета_' . $estimate->id . '.xlsx');
        
        return response()->download($filePath, $fileName);
    }
    
    /**
     * Получает данные из Excel-файла сметы
     */
    public function getData(Estimate $estimate)
    {
        $this->authorize('view', $estimate);
        
        try {
            $filePath = storage_path('app/public/' . $estimate->file_path);
            
            // Проверка наличия файла
            if (!file_exists($filePath) || !is_file($filePath)) {
                // Если файла нет, создаем его
                $this->createInitialExcelFile($estimate);
                $filePath = storage_path('app/public/' . $estimate->file_path);
                
                if (!file_exists($filePath)) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Не удалось создать файл сметы'
                    ], 404);
                }
            }
            
            // Если запрашивается только структура файла
            if (request()->has('structure')) {
                // Определяем структуру файла
                $structure = $this->getExcelFileStructure($filePath, $estimate->type);
                
                return response()->json([
                    'success' => true,
                    'structure' => $structure
                ]);
            }
            
            // Безопасное чтение файла в бинарном режиме
            $excelData = @file_get_contents($filePath);
            
            if ($excelData === false) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Ошибка при чтении файла сметы'
                ], 500);
            }
            
            // Проверяем, что файл действительно является Excel-файлом
            // Excel файлы начинаются с сигнатуры PK
            if (substr($excelData, 0, 2) !== 'PK') {
                \Log::warning('Файл не является валидным Excel-файлом: ' . $filePath);
                
                // Пытаемся пересоздать файл
                $this->createInitialExcelFile($estimate);
                $filePath = storage_path('app/public/' . $estimate->file_path);
                $excelData = @file_get_contents($filePath);
                
                if ($excelData === false || substr($excelData, 0, 2) !== 'PK') {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Файл не является валидным Excel-документом'
                    ], 500);
                }
            }
            
            // Если файл слишком большой для обработки в браузере
            if (strlen($excelData) > 10 * 1024 * 1024) { // более 10МБ
                return response()->json([
                    'success' => false, 
                    'message' => 'Файл слишком большой для отображения в браузере'
                ], 413); // 413 - Payload Too Large
            }
            
            // Кодируем данные в base64 для передачи через JSON
            $base64Data = base64_encode($excelData);
            
            // Дополнительно определяем структуру для первичной инициализации
            $structure = $this->getExcelFileStructure($filePath, $estimate->type);
            
            return response()->json([
                'success' => true, 
                'data' => $base64Data,
                'structure' => $structure
            ]);
        } 
        catch (\Exception $e) {
            \Log::error('Ошибка при получении данных Excel: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false, 
                'message' => 'Произошла ошибка: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Сохраняет данные Excel из редактора
     */
    public function saveExcelData(Request $request, Estimate $estimate)
    {
        $this->authorize('update', $estimate);
        
        // Более подробная проверка данных с логированием
        if (!$request->has('excel_data')) {
            \Log::warning('Запрос не содержит поля excel_data');
            return response()->json([
                'success' => false,
                'message' => 'Данные Excel не предоставлены (поле отсутствует)'
            ], 422);
        }
        
        if (empty($request->excel_data)) {
            \Log::warning('Поле excel_data пустое');
            return response()->json([
                'success' => false,
                'message' => 'Данные Excel не предоставлены (поле пустое)'
            ], 422);
        }
        
        \Log::info('Получены Excel данные размером: ' . strlen($request->excel_data));
        
        try {
            // Декодируем данные из base64
            $base64Data = $request->excel_data;
            $binaryData = base64_decode($base64Data, true);
            
            if ($binaryData === false) {
                \Log::warning('Некорректное base64 кодирование данных');
                return response()->json([
                    'success' => false,
                    'message' => 'Некорректный формат данных Base64'
                ], 422);
            }
            
            // Проверяем минимальный размер файла Excel
            if (strlen($binaryData) < 100) {
                \Log::warning('Слишком маленький размер данных: ' . strlen($binaryData) . ' байт');
                return response()->json([
                    'success' => false,
                    'message' => 'Недостаточный размер данных, возможно файл поврежден'
                ], 422);
            }
            
            // Определяем путь для сохранения файла
            if (!$estimate->file_path) {
                $filePath = "estimates/" . ($estimate->project_id ?? 'no_project') . "/{$estimate->id}.xlsx";
                Storage::disk('public')->makeDirectory("estimates/" . ($estimate->project_id ?? 'no_project'));
            } else {
                $filePath = $estimate->file_path;
            }
            
            // Создаем директорию, если она не существует
            $dir = dirname(storage_path('app/public/' . $filePath));
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Сохраняем файл
            Storage::disk('public')->put($filePath, $binaryData);
            
            // Обновляем информацию о файле
            $fileSize = Storage::disk('public')->size($filePath);
            $fileName = $estimate->file_name ?: 'Смета_' . $estimate->id . '.xlsx';
            $now = now();
            
            $estimate->update([
                'file_path' => $filePath,
                'file_updated_at' => $now,
                'file_size' => $fileSize,
                'file_name' => $fileName
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Файл успешно сохранен',
                'updated_at' => $now->format('d.m.Y H:i'),
                'filesize' => $fileSize
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Ошибка при сохранении Excel-данных: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при сохранении файла: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Загружает файл Excel для сметы
     */
    public function upload(Request $request, Estimate $estimate)
    {
        $this->authorize('update', $estimate);
        
        // Валидация входных данных
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB максимум
        ]);
        
        try {
            // Получаем загружаемый файл
            $file = $request->file('file');
            
            // Проверяем, что файл действительно является Excel
            if (!in_array($file->getClientMimeType(), ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) {
                return back()->with('error', 'Файл должен быть в формате Excel (.xlsx или .xls)');
            }
            
            // Определяем путь для сохранения файла
            $filePath = "estimates/" . ($estimate->project_id ?? 'no_project') . "/{$estimate->id}.xlsx";
            
            // Создаем директорию, если она не существует
            Storage::disk('public')->makeDirectory("estimates/" . ($estimate->project_id ?? 'no_project'));
            
            // Сохраняем файл с оригинальным названием
            $fileName = $file->getClientOriginalName();
            $file->storeAs('public/' . dirname($filePath), basename($filePath));
            
            // Обновляем информацию о файле
            $fileSize = Storage::disk('public')->size($filePath);
            $estimate->update([
                'file_path' => $filePath,
                'file_updated_at' => now(),
                'file_size' => $fileSize,
                'file_name' => $fileName
            ]);
            
            // Улучшаем форматирование файла
            $this->enhanceExistingFileFormatting($estimate);
            
            return back()->with('success', 'Файл успешно загружен и обработан');
        } catch (\Exception $e) {
            \Log::error('Ошибка при загрузке Excel файла: ' . $e->getMessage());
            return back()->with('error', 'Ошибка при загрузке файла: ' . $e->getMessage());
        }
    }

    /**
     * Определяет структуру Excel-файла
     * @param string $filePath Путь к файлу
     * @param string $estimateType Тип сметы
     * @return array Структура файла
     */
    protected function getExcelFileStructure($filePath, $estimateType)
    {
        try {
            // Загружаем файл
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            
            // Определяем количество колонок
            $highestColumn = $sheet->getHighestColumn();
            $columnCount = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            
            // Определяем защищенные колонки (формулы) на основе типа сметы
            $readOnlyColumns = [];
            
            // Стандартный набор для всех типов смет
            switch ($estimateType) {
                case 'main':
                    // Стоимость и цены для заказчика (с формулами)
                    $readOnlyColumns = [5, 8, 9];
                    break;
                    
                case 'materials':
                    // Материалы могут иметь другие столбцы с формулами
                    $readOnlyColumns = [6, 9, 10];
                    break;
                    
                case 'additional':
                    // Дополнительная смета
                    $readOnlyColumns = [5, 8, 9];
                    break;
                    
                default:
                    // По умолчанию
                    $readOnlyColumns = [];
                    
                    // Ищем столбцы с формулами, анализируя строки данных
                    $rowCount = min($sheet->getHighestRow(), 20); // Анализируем до 20 строк
                    
                    for ($col = 1; $col <= $columnCount; $col++) {
                        $hasFormulas = false;
                        
                        for ($row = 6; $row <= $rowCount; $row++) {
                            $cell = $sheet->getCellByColumnAndRow($col, $row);
                            if ($cell->isFormula()) {
                                $hasFormulas = true;
                                break;
                            }
                        }
                        
                        if ($hasFormulas) {
                            $readOnlyColumns[] = $col - 1; // Колонки в JS начинаются с 0
                        }
                    }
            }
            
            // Определяем ширины колонок
            $columnWidths = [];
            for ($i = 1; $i <= $columnCount; $i++) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                $columnWidths[$i-1] = $sheet->getColumnDimension($columnLetter)->getWidth() * 7.5; // Примерный перевод из единиц Excel в пиксели
            }
            
            return [
                'columnCount' => $columnCount,
                'readOnlyColumns' => $readOnlyColumns,
                'hasHeaders' => true, // Предполагаем, что в файле есть заголовки
                'columnWidths' => $columnWidths
            ];
        } catch (\Exception $e) {
            \Log::error('Ошибка при определении структуры Excel-файла: ' . $e->getMessage());
            
            // Возвращаем стандартную структуру в случае ошибки
            return [
                'columnCount' => 10,
                'readOnlyColumns' => [5, 8, 9],
                'hasHeaders' => true
            ];
        }
    }

    /**
     * Создает исходный Excel файл для сметы с заданной структурой
     */
    public function createInitialExcelFile(Estimate $estimate)
    {
        // Определяем тип сметы и соответствующий шаблон
        $type = $estimate->type;
        
        // Получаем путь к файлу шаблона
        $templatePath = ExcelTemplateController::getEstimateTemplatePath($type);
        
        // Проверяем существование шаблона
        if (!file_exists($templatePath)) {
            // Если шаблон не найден, создаем директорию для шаблонов
            $templateDir = storage_path('app/templates/estimates');
            if (!File::isDirectory($templateDir)) {
                File::makeDirectory($templateDir, 0755, true);
            }
            
            // Создаем базовый шаблон с помощью PhpSpreadsheet и сохраняем его
            $this->createDefaultTemplate($type, $templatePath);
        }
        
        // Создаем директорию для сохранения файла сметы
        $directory = 'estimates/' . ($estimate->project_id ?? 'no_project');
        Storage::disk('public')->makeDirectory($directory);
        
        // Путь к новому файлу сметы
        $filePath = $directory . '/' . $estimate->id . '.xlsx';
        $fullPath = storage_path('app/public/' . $filePath);
        
        // Копируем шаблон в директорию смет пользователя
        copy($templatePath, $fullPath);
        
        // Загружаем файл для обновления метаданных
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Обновляем информацию о документе с учетом типа сметы
        $sheet->setCellValue('B2', $estimate->project ? $estimate->project->address : 'Не указан');
        $sheet->setCellValue('B3', $estimate->project ? $estimate->project->client_name : 'Не указан');
        $sheet->setCellValue('B4', Carbon::now()->format('d.m.Y'));
        
        // Сохраняем файл с метаданными
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($fullPath);
        
        // Обновляем информацию о файле в базе данных
        $fileSize = filesize($fullPath);
        $fileName = '';
        
        // Формируем название файла в зависимости от типа сметы
        switch ($estimate->type) {
            case 'main':
                $fileName = 'Работы_Смета_производства_работ_2025.xlsx';
                break;
            case 'additional':
                $fileName = 'Дополнительная_смета_' . $estimate->id . '.xlsx';
                break;
            case 'materials':
                $fileName = 'Материалы_Черновые_материалы_2025.xlsx';
                break;
            default:
                $fileName = 'Смета_' . $estimate->id . '.xlsx';
        }
        
        $estimate->update([
            'file_path' => $filePath,
            'file_updated_at' => now(),
            'file_size' => $fileSize,
            'file_name' => $fileName
        ]);

        return true;
    }

    /**
     * Создает и сохраняет базовый шаблон сметы
     * @param string $type Тип сметы
     * @param string $savePath Путь для сохранения файла
     */
    protected function createDefaultTemplate($type, $savePath)
    {
        return $this->estimateTemplateService->createDefaultTemplate($type, $savePath);
    }

    /**
     * Улучшает форматирование существующего файла Excel перед экспортом
     */
    protected function enhanceExistingFileFormatting(Estimate $estimate)
    {
        try {
            $filePath = storage_path('app/public/' . $estimate->file_path);
            
            // Загружаем существующий файл
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            
            // Получаем все листы и форматируем каждый из них
            $sheetCount = $spreadsheet->getSheetCount();
            
            for ($i = 0; $i < $sheetCount; $i++) {
                // Переключаемся на текущий лист
                $sheet = $spreadsheet->getSheet($i);
                
                // Проверяем и корректируем формулы для текущего листа
                $this->enhanceSheetFormatting($sheet);
                
                // Применяем расширенное форматирование для текущего листа
                $this->estimateTemplateService->formatSpreadsheet($spreadsheet, true, $i);
            }
            
            // Возвращаемся к первому листу
            $spreadsheet->setActiveSheetIndex(0);
            
            // Сохраняем файл с улучшенным форматированием, сохраняя формулы
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save($filePath);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Ошибка при улучшении форматирования Excel: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Улучшает форматирование и формулы для конкретного листа
     */
    protected function enhanceSheetFormatting($sheet)
    {
        try {
            // Проверяем и корректируем формулы в файле
            $lastRow = $sheet->getHighestRow();
            $headerRow = 5; // Строка с заголовками
            $startDataRow = $headerRow + 1;
            $totalRow = null; // Индекс итоговой строки
            
            // Ищем строку с итогами
            for ($row = $startDataRow; $row <= $lastRow; $row++) {
                $value = $sheet->getCell('B' . $row)->getValue();
                if (is_string($value) && stripos($value, 'ИТОГО') !== false) {
                    $totalRow = $row;
                    break;
                }
            }
            
            // Если нашли итоговую строку, устанавливаем правильные формулы только для стоимости
            if ($totalRow) {
                // Очищаем ячейки, где не нужны итоговые значения
                $sheet->setCellValue('D' . $totalRow, '');  // Количество - не суммируем
                $sheet->setCellValue('E' . $totalRow, '');  // Цена - не суммируем
                
                // Устанавливаем формулу суммирования для стоимости
                $sheet->setCellValue('F' . $totalRow, "=SUM(F{$startDataRow}:F" . ($totalRow-1) . ")");
                
                // Очищаем ячейки для остальных колонок
                $sheet->setCellValue('G' . $totalRow, '');  // Наценка - не суммируем
                $sheet->setCellValue('H' . $totalRow, '');  // Скидка - не суммируем
                $sheet->setCellValue('I' . $totalRow, '');  // Цена для заказчика - не суммируем
                
                // Устанавливаем формулу суммирования для стоимости заказчика
                $sheet->setCellValue('J' . $totalRow, "=SUM(J{$startDataRow}:J" . ($totalRow-1) . ")");
            }
            
            // Для всех данных проверяем и устанавливаем корректные формулы
            if ($totalRow) {
                for ($row = $startDataRow; $row < $totalRow; $row++) {
                    // Проверяем, есть ли данные в этой строке
                    $hasData = $sheet->getCell('B' . $row)->getValue() != '';
                    if ($hasData) {
                        // Устанавливаем формулу для стоимости
                        $sheet->setCellValue('F' . $row, "=D{$row}*E{$row}");
                        
                        // Устанавливаем формулу для цены для заказчика
                        $sheet->setCellValue('I' . $row, "=E{$row}*(1+G{$row}/100)*(1-H{$row}/100)");
                        
                        // Устанавливаем формулу для стоимости для заказчика
                        $sheet->setCellValue('J' . $row, "=D{$row}*I{$row}");
                    }
                }
            }
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Ошибка при улучшении форматирования листа: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Экспорт сметы в формате Excel
     *
     * @param Estimate $estimate
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportOld(Estimate $estimate)
    {
        // Проверка доступа
        $this->authorize('view', $estimate);

        try {
            // Получаем путь к файлу
            $filePath = storage_path('app/estimates/' . $estimate->id . '/excel.xlsx');

            // Проверка существования файла
            if (!Storage::disk('local')->exists('estimates/' . $estimate->id . '/excel.xlsx')) {
                return back()->with('error', 'Файл сметы не найден.');
            }

            // Создаем копию файла с правильными формулами
            $tempFilePath = storage_path('app/temp/' . uniqid('estimate_') . '.xlsx');
            
            // Убедимся, что директория существует
            if (!file_exists(dirname($tempFilePath))) {
                mkdir(dirname($tempFilePath), 0755, true);
            }
            
            // Загружаем исходный файл
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            
            // Проходим по всем листам
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Проходим по всем ячейкам и фиксируем формулы
                for ($row = 6; $row <= $highestRow; $row++) {
                    for ($col = 'F'; $col <= 'J'; $col++) {
                        $cellCoordinate = $col . $row;
                        $cellValue = $sheet->getCell($cellCoordinate)->getValue();
                        
                        // Если значение начинается с '=', это формула
                        if (is_string($cellValue) && strpos($cellValue, '=') === 0) {
                            // Устанавливаем значение как формулу
                            $sheet->getCell($cellCoordinate)->setValueExplicit(
                                $cellValue,
                                DataType::TYPE_FORMULA
                            );
                            
                            // Устанавливаем числовой формат для формул
                            $sheet->getStyle($cellCoordinate)->getNumberFormat()
                                ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        } 
                        // Если это число, убедимся что оно обрабатывается как число
                        elseif (is_numeric($cellValue)) {
                            $sheet->getCell($cellCoordinate)->setValueExplicit(
                                $cellValue,
                                DataType::TYPE_NUMERIC
                            );
                            
                            // Устанавливаем числовой формат
                            $sheet->getStyle($cellCoordinate)->getNumberFormat()
                                ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        }
                    }
                }
                
                // Убедимся, что итоговые строки имеют правильные формулы
                for ($row = 6; $row <= $highestRow; $row++) {
                    // Если это итоговая строка (имеет "ИТОГО" в колонке B)
                    $cellValue = $sheet->getCell('B' . $row)->getValue();
                    if (is_string($cellValue) && strpos($cellValue, 'ИТОГО') !== false) {
                        // Диапазон для суммирования (от начала до текущей строки)
                        $startRow = 6;
                        $endRow = $row - 1;
                        
                        // Формула для колонки F (Стоимость)
                        $sheet->setCellValue(
                            'F' . $row, 
                            "=SUM(F$startRow:F$endRow)"
                        );
                        
                        // Формула для колонки J (Стоимость для заказчика)
                        $sheet->setCellValue(
                            'J' . $row, 
                            "=SUM(J$startRow:J$endRow)"
                        );
                        
                        // Устанавливаем числовой формат для итоговых сумм
                        $sheet->getStyle('F' . $row)->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        $sheet->getStyle('J' . $row)->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    }
                }
                
                // Убедимся, что формулы в обычных строках правильные
                for ($row = 6; $row <= $highestRow; $row++) {
                    $cellValue = $sheet->getCell('B' . $row)->getValue();
                    // Пропускаем строки заголовков разделов и итоговую строку
                    if (!is_string($cellValue) || strpos($cellValue, 'ИТОГО') !== false || 
                        $sheet->getCell('C' . $row)->getValue() === '') {
                        continue;
                    }
                    
                    // Формула для колонки F (Стоимость = Количество * Цена)
                    $sheet->setCellValue(
                        'F' . $row, 
                        "=D$row*E$row"
                    );
                    
                    // Формула для колонки I (Цена для заказчика)
                    $sheet->setCellValue(
                        'I' . $row, 
                        "=E$row*(1+G$row/100)*(1-H$row/100)"
                    );
                    
                    // Формула для колонки J (Стоимость для заказчика)
                    $sheet->setCellValue(
                        'J' . $row, 
                        "=D$row*I$row"
                    );
                    
                    // Устанавливаем числовой формат
                    $sheet->getStyle($row)->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                }
            }
            
            // Сохраняем файл с правильными формулами
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false); // Важно! Не пересчитываем формулы
            $writer->save($tempFilePath);
            
            // Возвращаем файл для скачивания
            $fileName = $estimate->name . '.xlsx';
            $fileName = preg_replace('/[^a-zA-Zа-яА-Я0-9_\- ]/u', '', $fileName);
            $fileName = str_replace(' ', '_', $fileName);
            
            return response()->download($tempFilePath, $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Ошибка при экспорте Excel файла: ' . $e->getMessage(), [
                'estimate_id' => $estimate->id,
                'exception' => $e
            ]);
            
            return back()->with('error', 'Произошла ошибка при экспорте: ' . $e->getMessage());
        }
    }
}
