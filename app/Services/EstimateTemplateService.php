<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class EstimateTemplateService
{
    /**
     * @var MaterialsEstimateTemplateService
     */
    protected $materialsTemplateService;

    /**
     * Конструктор с внедрением зависимости
     *
     * @param MaterialsEstimateTemplateService $materialsTemplateService
     */
    public function __construct(MaterialsEstimateTemplateService $materialsTemplateService = null)
    {
        $this->materialsTemplateService = $materialsTemplateService;
    }
    
    /**
     * Получает список разделов работ
     * 
     * @return array Массив разделов работ и их элементов
     */
    public function getWorkSections()
    {
        $filePath = base_path('app/Services/Data/WorkSectionsList.php');
        
        if (file_exists($filePath)) {
            return require $filePath;
        }
        
        // Возвращаем пустой массив, если файл не найден
        return [];
    }

    /**
     * Создает шаблон сметы в зависимости от типа
     * 
     * @param string $type Тип сметы
     * @param string $savePath Путь для сохранения файла
     * @return bool Результат операции
     */
    public function createDefaultTemplate($type = 'main', $savePath = null)
    {
        // Если путь не указан, используем стандартный
        if (!$savePath) {
            $savePath = storage_path("app/templates/estimates/{$type}.xlsx");
        }
        
        // Создаем директорию при необходимости
        $directory = dirname($savePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // В зависимости от типа сметы используем разные шаблоны
        switch ($type) {
            case 'materials':
                // Используем специальный сервис для материалов, если он доступен
                if ($this->materialsTemplateService) {
                    return $this->materialsTemplateService->createTemplate($savePath);
                }
                // Если специального сервиса нет, используем базовый шаблон
                $spreadsheet = new Spreadsheet();
                $this->createMaterialsTemplate($spreadsheet);
                break;
                
            case 'additional':
                $spreadsheet = new Spreadsheet();
                $this->createAdditionalTemplate($spreadsheet);
                break;
                
            case 'main':
            default:
                $spreadsheet = new Spreadsheet();
                $this->createMainTemplate($spreadsheet);
                break;
        }
        
        // Устанавливаем общие свойства документа
        $spreadsheet->getProperties()
            ->setCreator('Ремонтная компания')
            ->setLastModifiedBy('Система смет')
            ->setTitle('Смета')
            ->setSubject('Смета на ремонтные работы')
            ->setDescription('Смета на ремонтные работы');
            
        // Применяем стандартное форматирование для любого типа сметы
        $this->formatSpreadsheet($spreadsheet);
        
        // Сохраняем файл
        $writer = new Xlsx($spreadsheet);
        $writer->save($savePath);
        
        return true;
    }
    
    /**
     * Создает шаблон основной сметы
     * 
     * @param Spreadsheet $spreadsheet Объект таблицы
     * @return void
     */
    private function createMainTemplate(Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Работы');
        
        // Заголовок сметы
        $sheet->setCellValue('A1', 'СМЕТА НА ПРОВЕДЕНИЕ РАБОТ');
        $sheet->setCellValue('A2', 'Объект:');
        $sheet->setCellValue('A3', 'Заказчик:');
        $sheet->setCellValue('A4', 'Дата составления:');
        $sheet->setCellValue('B4', Carbon::now()->format('d.m.Y'));

        // Заголовки таблицы
        $headers = [
            'A5' => '№',
            'B5' => 'Наименование работ',
            'C5' => 'Ед. изм.',
            'D5' => 'Кол-во',
            'E5' => 'Цена, руб.',
            'F5' => 'Стоимость, руб.',
            'G5' => 'Наценка, %',
            'H5' => 'Скидка, %',
            'I5' => 'Цена для заказчика',
            'J5' => 'Стоимость для заказчика'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        // Итоговая строка будет добавлена после всех работ
        
        // Добавление работ из списка
        $works = $this->getWorksFromTemplateList();
        
        $row = 7;
        $itemNumber = 1;
        
        foreach ($works as $work) {
            // Определяем, является ли это заголовком раздела
            $isHeader = (!isset($work[1]) || empty($work[1]));
            
            if ($isHeader) {
                $sheet->setCellValue('A' . $row, '');
                $sheet->setCellValue('B' . $row, $work[0]);
                
                // Форматируем заголовок раздела
                $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0F0F0'],
                    ],
                ]);
            } else {
                $sheet->setCellValue('A' . $row, $itemNumber++);
                $sheet->setCellValue('B' . $row, $work[0]);
                $sheet->setCellValue('C' . $row, $work[1]);
                $sheet->setCellValue('D' . $row, $work[2]);
                $sheet->setCellValue('E' . $row, $work[3]);
                $sheet->setCellValue('F' . $row, '=D' . $row . '*E' . $row);
                $sheet->setCellValue('G' . $row, $work[5]);
                $sheet->setCellValue('H' . $row, $work[6]);
                $sheet->setCellValue('I' . $row, '=E' . $row . '*(1+G' . $row . '/100)*(1-H' . $row . '/100)');
                $sheet->setCellValue('J' . $row, '=D' . $row . '*I' . $row);
            }
            
            $row++;
        }
        
        // Обновляем формулы итогов
        $lastRow = $row - 1;
        
        $sheet->setCellValue('A' . $row, '');
        $sheet->setCellValue('B' . $row, 'ИТОГО:');
        $sheet->setCellValue('F' . $row, '=SUM(F7:F' . $lastRow . ')');
        $sheet->setCellValue('J' . $row, '=SUM(J7:J' . $lastRow . ')');
        
        // Форматируем итоговую строку
        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F0F0'],
            ],
        ]);
    }
    
    /**
     * Получает работы из списка
     * 
     * @return array Массив работ для сметы
     */
    private function getWorksFromTemplateList()
    {
        $works = [];
        
        // Получаем список разделов работ из внешнего файла
        $sections = $this->getWorkSections();
        
        // Проверяем, что массив секций не пустой
        if (empty($sections)) {
            // Логируем ошибку для отладки
            \Log::warning('Не удалось загрузить секции работ из файла');
            return $works;
        }
        
        foreach ($sections as $section) {
            // Проверяем структуру раздела
            if (!isset($section['title']) || !isset($section['items']) || !is_array($section['items'])) {
                \Log::warning('Неправильный формат раздела в файле WorkSectionsList.php');
                continue;
            }
            
            // Добавляем заголовок раздела
            $works[] = [$section['title'], '', '', '', '', '', '', '', ''];
            
            // Добавляем все работы из раздела
            foreach ($section['items'] as $item) {
                if (!isset($item['name']) || !isset($item['unit'])) {
                    \Log::warning('Неправильный формат работы в файле WorkSectionsList.php');
                    continue;
                }
                
                // Примерные значения для сметы
                $quantity = rand(1, 20);
                $price = rand(100, 2000);
                $markup = rand(10, 25);
                $discount = 0;
                
                $works[] = [
                    $item['name'],
                    $item['unit'],
                    $quantity,
                    $price,
                    '', // Формула будет добавлена динамически
                    $markup,
                    $discount,
                    '', // Формула будет добавлена динамически
                    ''  // Формула будет добавлена динамически
                ];
            }
        }
        
        return $works;
    }
    
    /**
     * Создает шаблон дополнительной сметы
     * 
     * @param Spreadsheet $spreadsheet Объект таблицы
     * @return void
     */
    private function createAdditionalTemplate(Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Дополнительные работы');
        
        // Заголовок сметы
        $sheet->setCellValue('A1', 'ДОПОЛНИТЕЛЬНАЯ СМЕТА');
        $sheet->setCellValue('A2', 'Объект:');
        $sheet->setCellValue('A3', 'Заказчик:');
        $sheet->setCellValue('A4', 'Дата составления:');
        $sheet->setCellValue('B4', Carbon::now()->format('d.m.Y'));

        // Заголовки таблицы
        $headers = [
            'A5' => '№',
            'B5' => 'Наименование работ',
            'C5' => 'Ед. изм.',
            'D5' => 'Кол-во',
            'E5' => 'Цена, руб.',
            'F5' => 'Стоимость, руб.',
            'G5' => 'Наценка, %',
            'H5' => 'Скидка, %',
            'I5' => 'Цена для заказчика',
            'J5' => 'Стоимость для заказчика'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        // Итоговая строка
        $sheet->setCellValue('B6', 'ИТОГО:');
        $sheet->setCellValue('F6', '=SUM(F5:F5)');
        $sheet->setCellValue('J6', '=SUM(J5:J5)');
    }
    
    /**
     * Создает шаблон сметы на материалы
     * 
     * @param Spreadsheet $spreadsheet Объект таблицы
     * @return void
     */
    private function createMaterialsTemplate(Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Материалы');
        
        // Заголовок сметы
        $sheet->setCellValue('A1', 'СМЕТА НА МАТЕРИАЛЫ');
        $sheet->setCellValue('A2', 'Объект:');
        $sheet->setCellValue('A3', 'Заказчик:');
        $sheet->setCellValue('A4', 'Дата составления:');
        $sheet->setCellValue('B4', Carbon::now()->format('d.m.Y'));

        // Заголовки таблицы
        $headers = [
            'A5' => '№',
            'B5' => 'Наименование материала',
            'C5' => 'Ед. изм.',
            'D5' => 'Кол-во',
            'E5' => 'Цена, руб.',
            'F5' => 'Стоимость, руб.',
            'G5' => 'Наценка, %',
            'H5' => 'Скидка, %',
            'I5' => 'Цена для заказчика',
            'J5' => 'Стоимость для заказчика'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        // Итоговая строка
        $sheet->setCellValue('B6', 'ИТОГО:');
        $sheet->setCellValue('F6', '=SUM(F5:F5)');
        $sheet->setCellValue('J6', '=SUM(J5:J5)');
        
        // Если доступен специальный сервис, используем его для добавления примеров материалов
        if ($this->materialsTemplateService) {
            $this->materialsTemplateService->addMaterialsExamples($spreadsheet);
        }
    }
    
    /**
     * Применяет форматирование к таблице
     * 
     * @param Spreadsheet $spreadsheet Объект таблицы
     * @param bool $applyBordersOnly Применять только границы (без изменения структуры)
     * @param int $sheetIndex Индекс листа для форматирования
     * @return void
     */
    public function formatSpreadsheet(Spreadsheet $spreadsheet, $applyBordersOnly = false, $sheetIndex = 0)
    {
        // Выбираем лист для форматирования
        $spreadsheet->setActiveSheetIndex($sheetIndex);
        $sheet = $spreadsheet->getActiveSheet();
        
        if (!$applyBordersOnly) {
            // Форматирование заголовка
            $sheet->getStyle('A1:J1')->getFont()->setBold(true)->setSize(2);
            $sheet->mergeCells('A1:J1');
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Форматирование информации об объекте
            $sheet->getStyle('A2:A4')->getFont()->setBold(true);
            $sheet->getStyle('B2:B4')->getFont()->setItalic(true);
            
            // Форматирование заголовков таблицы
            $sheet->getStyle('A5:J5')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            
            // Устанавливаем ширину столбцов
            $sheet->getColumnDimension('A')->setWidth(5);     // №
            $sheet->getColumnDimension('B')->setWidth(40);    // Наименование
            $sheet->getColumnDimension('C')->setWidth(10);    // Ед. изм.
            $sheet->getColumnDimension('D')->setWidth(10);    // Кол-во
            $sheet->getColumnDimension('E')->setWidth(15);    // Цена
            $sheet->getColumnDimension('F')->setWidth(15);    // Стоимость
            $sheet->getColumnDimension('G')->setWidth(12);    // Наценка, %
            $sheet->getColumnDimension('H')->setWidth(12);    // Скидка, %
            $sheet->getColumnDimension('I')->setWidth(15);    // Цена для заказчика
            $sheet->getColumnDimension('J')->setWidth(15);    // Стоимость для заказчика
            
            // Форматирование итоговой строки
            // Находим итоговую строку (ищем текст "ИТОГО:")
            $lastRow = 6; // По умолчанию это строка 6
            for ($row = 6; $row < 50; $row++) {
                if ($sheet->getCell('B' . $row)->getValue() == 'ИТОГО:') {
                    $lastRow = $row;
                    break;
                }
            }
            
            // Форматируем итоговую строку
            $sheet->getStyle('A' . $lastRow . ':J' . $lastRow)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F0F0F0'],
                ],
            ]);
        }
        
        // Применяем границы ко всем ячейкам в таблице
        // Определяем последнюю используемую строку
        $lastDataRow = $sheet->getHighestRow();
        
        // Применяем границы ко всем ячейкам данных
        $sheet->getStyle('A5:J' . $lastDataRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);
        
        // Жирные границы для заголовков и итогов
        $sheet->getStyle('A5:J5')->applyFromArray([
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        
        // Форматирование цифровых столбцов
        $numericColumns = ['D', 'E', 'F', 'G', 'H', 'I', 'J'];
        foreach ($numericColumns as $column) {
            $sheet->getStyle($column . '6:' . $column . $lastDataRow)
                ->getNumberFormat()
                ->setFormatCode('#,##0.00_-');
        }
        
        // Центрирование в определенных столбцах
        $centerColumns = ['A', 'C', 'D', 'G', 'H'];
        foreach ($centerColumns as $column) {
            $sheet->getStyle($column . '6:' . $column . $lastDataRow)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }
}