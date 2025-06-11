<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class MaterialsEstimateTemplateService
{
    /**
     * Создает шаблон сметы материалов и сохраняет его по указанному пути
     *
     * @param string $savePath Путь для сохранения файла шаблона
     * @return bool Результат операции
     */
    public function createTemplate($savePath)
    {
        // Создаем экземпляр Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Материалы');

        // Настраиваем свойства документа
        $spreadsheet->getProperties()
            ->setCreator('Ремонтная компания')
            ->setLastModifiedBy('Система смет')
            ->setTitle('Смета на материалы')
            ->setSubject('Смета на материалы')
            ->setDescription('Шаблон сметы на материалы');

        // Заголовок сметы
        $sheet->setCellValue('A1', 'СМЕТА НА МАТЕРИАЛЫ');
        $sheet->setCellValue('A2', 'Объект:');
        $sheet->setCellValue('A3', 'Заказчик:');
        $sheet->setCellValue('A4', 'Дата составления:');
        $sheet->setCellValue('B4', Carbon::now()->format('d.m.Y'));

        // Заголовки таблицы (строка 5)
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

        // Добавляем итоговую строку
        $sheet->setCellValue('B6', 'ИТОГО:');
        $sheet->setCellValue('F6', '=SUM(F5:F5)'); // Формула будет корректно работать при добавлении строк
        $sheet->setCellValue('J6', '=SUM(J5:J5)'); // Формула будет корректно работать при добавлении строк

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
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Форматирование итоговой строки
        $sheet->getStyle('A6:J6')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F0F0'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
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

        // Сохраняем файл
        $writer = new Xlsx($spreadsheet);
        
        // Создаем директорию при необходимости
        $directory = dirname($savePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Сохраняем файл
        $writer->save($savePath);
        
        return true;
    }

    /**
     * Добавляет примеры материалов для черновых работ
     * 
     * @param Spreadsheet $spreadsheet Объект таблицы
     * @return void
     */
    public function addMaterialsExamples(Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        
        // Список часто используемых материалов
        $materials = [
            ['Цемент ПЦ-400 Д0', 'мешок', 0, 380, '=D7*E7', 10, 0, '=E7*(1+G7/100)*(1-H7/100)', '=D7*I7'],
            ['Песок строительный', 'м³', 0, 850, '=D8*E8', 5, 0, '=E8*(1+G8/100)*(1-H8/100)', '=D8*I8'],
            ['Грунтовка глубокого проникновения', 'л', 0, 150, '=D9*E9', 15, 0, '=E9*(1+G9/100)*(1-H9/100)', '=D9*I9'],
            ['Штукатурка гипсовая', 'кг', 0, 15, '=D10*E10', 20, 0, '=E10*(1+G10/100)*(1-H10/100)', '=D10*I10'],
            ['Шпаклевка финишная', 'кг', 0, 60, '=D11*E11', 15, 0, '=E11*(1+G11/100)*(1-H11/100)', '=D11*I11'],
        ];
        
        // Добавляем материалы в таблицу
        $row = 7;
        foreach ($materials as $index => $material) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $material[0]);
            $sheet->setCellValue('C' . $row, $material[1]);
            $sheet->setCellValue('D' . $row, $material[2]);
            $sheet->setCellValue('E' . $row, $material[3]);
            $sheet->setCellValue('F' . $row, $material[4]);
            $sheet->setCellValue('G' . $row, $material[5]);
            $sheet->setCellValue('H' . $row, $material[6]);
            $sheet->setCellValue('I' . $row, $material[7]);
            $sheet->setCellValue('J' . $row, $material[8]);
            $row++;
        }
        
        // Обновляем формулу итогов
        $lastRow = $row - 1;
        $sheet->setCellValue('F' . $row, '=SUM(F7:F' . $lastRow . ')');
        $sheet->setCellValue('J' . $row, '=SUM(J7:J' . $lastRow . ')');
        
        // Форматируем все строки с данными
        $sheet->getStyle('A7:J' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);
    }
}
