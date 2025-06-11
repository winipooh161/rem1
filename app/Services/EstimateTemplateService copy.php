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
     * Список всех разделов работ и их элементов
     *
     * @var array
     */
    private static $workSections = [
        [
            'title' => 'Демонтажные работы + временные коммуникации',
            'items' => [
                ['name' => 'Демонтаж внутрипольного конвектора', 'unit' => 'раб'],
                ['name' => 'Демонтаж радиаторов', 'unit' => 'раб'],
                ['name' => 'Демонтаж труб (Отопление)', 'unit' => 'раб'],
                ['name' => 'Демонтаж утеплителя', 'unit' => 'раб'],
                ['name' => 'Демонтаж Вентиляционных труб', 'unit' => 'раб'],
                ['name' => 'Демонтаж перегородок 1-й ряд', 'unit' => 'раб'],
                ['name' => 'Комплексный, полный демонтаж объекта (со сбором, выносом и утилизацией мусора)', 'unit' => 'раб'],
                ['name' => 'Комплексный, полный демонтаж объекта (напольное покрытие, стяжка пола, настенное покрытие до основания, откосы штукатурные, покрытие потолка, трубы сантехнические, электрика, двери, плитка настенная, краска масленная)', 'unit' => 'раб'],
                ['name' => 'Устройство временного водоснабжения и канализации(до 5 выводов)', 'unit' => 'раб'],
                ['name' => 'Защита окон и дверей изолирующим матариалом(пленка картон до 15 шт)', 'unit' => 'раб'],
                ['name' => 'Устройство временного электроснабжения(до 15 точек)', 'unit' => 'раб'],
                ['name' => 'Укрытие холла (зона лифтов)', 'unit' => 'раб'],
            ]
        ],
        [
            'title' => 'Возведение перегородок',
            'items' => [
                ['name' => 'Формирование дверных проемов при монтаже перегородок', 'unit' => 'раб'],
                ['name' => 'Возведение перегородок из пеноблока/газоблока/ПГП', 'unit' => 'раб'],
            ]
        ],
        [
            'title' => 'Оштукатуривание стен',
            'items' => [
                ['name' => 'Обработка стен Бетонконтактом (Кистью)', 'unit' => 'раб'],
                ['name' => 'Оштукатуривание стен до 3 см', 'unit' => 'раб'],
                ['name' => 'Оштукатуривание откосов оконных', 'unit' => 'раб'],
            ]
        ],
        [
            'title' => 'Электромонтажные работы',
            'items' => [
                ['name' => 'Монтаж и сборка электрощита до 12 модулей', 'unit' => 'раб'],
                ['name' => 'Штробление ниши под электрощит + подводящий шлейф (ПГП)', 'unit' => 'раб'],
                ['name' => 'Монтаж слаботочного электрощита, коммутация + штробление', 'unit' => 'раб'],
                ['name' => 'Коммутация интернета в подрозетнике (вариант без слаботочного щита)', 'unit' => 'раб'],
                ['name' => 'Монтаж подрозетников (штробление и установка) по ПГП/газоблоку', 'unit' => 'раб'],
                ['name' => 'Монтаж подрозетников (штробление и установка) по бетону', 'unit' => 'раб'],
                ['name' => 'Монтаж подрозетника ГКЛ', 'unit' => 'раб'],
                ['name' => 'Монтаж подрозетников (штробление и установка) по кирпичу', 'unit' => 'раб'],
                ['name' => 'Монтаж распаячных узлов в подрозетниках', 'unit' => 'раб'],
                ['name' => 'Монтаж коробки уравнивания потенциалов (КУП) с подключением к главному щиту', 'unit' => 'раб'],
                ['name' => 'Монтаж электропроводки и слаботочных сетей', 'unit' => 'раб'],
                ['name' => 'Монтаж электропроводки под датчики протечки', 'unit' => 'раб'],
                ['name' => 'Штробление стен под эл. кабель (пеноблок и ПГБ)', 'unit' => 'раб'],
                ['name' => 'Штробление под электропроводку (по бетону)', 'unit' => 'раб'],
                ['name' => 'Штробление стен под эл. Кабель (кирпич)', 'unit' => 'раб'],
                ['name' => 'Монтаж встраиваемого кабель канала для мультимидийных систем (1.5 метра)', 'unit' => 'раб'],
            ]
        ],
        // ...и другие разделы...
    ];

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
        
        // Используем встроенный список работ вместо внешнего файла
        $sections = self::$workSections;
        
        // Проходим по разделам и добавляем их в список работ
        $maxItems = 3; // Максимальное количество работ из каждого раздела для примеров
        
        foreach ($sections as $section) {
            // Добавляем заголовок раздела
            $works[] = [$section['title'], '', '', '', '', '', '', '', ''];
            
            // Добавляем работы из раздела
            $count = 0;
            foreach ($section['items'] as $item) {
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
                
                $count++;
                if ($count >= $maxItems) {
                    break;
                }
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
            $sheet->getStyle('A1:J1')->getFont()->setBold(true)->setSize(16);
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