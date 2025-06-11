<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Models\EstimateItem;
use Illuminate\Http\Request;use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EstimateItemController extends Controller
{
    protected $estimateExcelController;
    
    /**
     * Конструктор с внедрением зависимостей
     */
    public function __construct(EstimateExcelController $estimateExcelController)
    {
        $this->estimateExcelController = $estimateExcelController;
    }
    
    /**
     * Добавляет новый элемент в смету
     */
    public function addRow(Request $request, Estimate $estimate)
    {
        $this->authorize('update', $estimate);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'quantity' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'markup' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'is_header' => 'boolean',
            'after_position' => 'nullable|integer|min:0',
        ]);
        
        // Расчет стоимости
        $quantity = $request->quantity;
        $price = $request->price;
        $markup = $request->markup ?? 0;
        $discount = $request->discount ?? 0;
        
        $cost = $quantity * $price;
        $clientPrice = $price * (1 + $markup/100) * (1 - $discount/100);
        $clientCost = $quantity * $clientPrice;
        
        // Определение позиции для новой строки
        $position = 1;
        if ($request->after_position) {
            $position = $request->after_position + 1;
            // Сдвигаем все элементы после указанной позиции
            EstimateItem::where('estimate_id', $estimate->id)
                ->where('position', '>=', $position)
                ->increment('position');
        } else {
            // Если позиция не указана, добавляем в конец
            $maxPosition = EstimateItem::where('estimate_id', $estimate->id)
                ->max('position');
            $position = $maxPosition ? $maxPosition + 1 : 1;
        }
        
        // Определение номера позиции для отображения
        $maxPositionNumber = EstimateItem::where('estimate_id', $estimate->id)
            ->where('is_section_header', false)
            ->max('position_number');
        $positionNumber = $maxPositionNumber ? $maxPositionNumber + 1 : 1;
        
        // Создаем новый элемент
        $item = new EstimateItem([
            'estimate_id' => $estimate->id,
            'position_number' => $request->is_header ? null : $positionNumber,
            'name' => $request->name,
            'unit' => $request->unit ?? '',
            'quantity' => $quantity,
            'price' => $price,
            'cost' => $cost,
            'markup_percent' => $markup,
            'discount_percent' => $discount,
            'client_price' => $clientPrice,
            'client_cost' => $clientCost,
            'position' => $position,
            'is_section_header' => $request->is_header ?? false,
        ]);
        $item->save();
        
        // Пересчитываем общую сумму сметы
        $totalAmount = EstimateItem::where('estimate_id', $estimate->id)
            ->sum('client_cost');
        $estimate->update(['total_amount' => $totalAmount]);
        
        // Обновляем Excel-файл, если он существует
        $this->updateExcelWithItem($estimate, $item);
        
        return response()->json([
            'success' => true,
            'item' => $item,
            'total_amount' => $totalAmount
        ]);
    }
    
    /**
     * Обновляет таблицу сметы в базе данных
     */
    public function updateTable(Request $request, Estimate $estimate)
    {
        $this->authorize('update', $estimate);
        
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'nullable|integer|exists:estimate_items,id',
            'items.*.name' => 'required|string|max:255',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.markup_percent' => 'nullable|numeric',
            'items.*.discount_percent' => 'nullable|numeric',
            'items.*.is_section_header' => 'nullable|boolean',
        ]);
        
        try {
            // Пересчитываем данные для каждого элемента
            $totalAmount = 0;
            $position = 1;
            
            foreach ($request->items as $itemData) {
                $quantity = $itemData['quantity'];
                $price = $itemData['price'];
                $markup = $itemData['markup_percent'] ?? 0;
                $discount = $itemData['discount_percent'] ?? 0;
                
                $cost = $quantity * $price;
                $clientPrice = $price * (1 + $markup/100) * (1 - $discount/100);
                $clientCost = $quantity * $clientPrice;
                $totalAmount += $clientCost;
                
                if (!empty($itemData['id'])) {
                    // Обновляем существующий элемент
                    $item = EstimateItem::find($itemData['id']);
                    if ($item && $item->estimate_id == $estimate->id) {
                        $item->update([
                            'name' => $itemData['name'],
                            'unit' => $itemData['unit'] ?? '',
                            'quantity' => $quantity,
                            'price' => $price,
                            'cost' => $cost,
                            'markup_percent' => $markup,
                            'discount_percent' => $discount,
                            'client_price' => $clientPrice,
                            'client_cost' => $clientCost,
                            'position' => $position,
                            'is_section_header' => $itemData['is_section_header'] ?? false,
                        ]);
                    }
                } else {
                    // Создаем новый элемент
                    $positionNumber = EstimateItem::where('estimate_id', $estimate->id)
                        ->where('is_section_header', false)
                        ->max('position_number') + 1;
                    
                    EstimateItem::create([
                        'estimate_id' => $estimate->id,
                        'position_number' => $itemData['is_section_header'] ?? false ? null : $positionNumber,
                        'name' => $itemData['name'],
                        'unit' => $itemData['unit'] ?? '',
                        'quantity' => $quantity,
                        'price' => $price,
                        'cost' => $cost,
                        'markup_percent' => $markup,
                        'discount_percent' => $discount,
                        'client_price' => $clientPrice,
                        'client_cost' => $clientCost,
                        'position' => $position,
                        'is_section_header' => $itemData['is_section_header'] ?? false,
                    ]);
                }
                
                $position++;
            }
            
            // Удаляем элементы, которых нет в запросе
            $itemIds = array_filter(array_column($request->items, 'id'));
            if (!empty($itemIds)) {
                EstimateItem::where('estimate_id', $estimate->id)
                    ->whereNotIn('id', $itemIds)
                    ->delete();
            }
            
            // Обновляем общую сумму сметы
            $estimate->update(['total_amount' => $totalAmount]);
            
            // Обновляем Excel-файл
            $this->updateExcelFromItems($estimate);
            
            return response()->json([
                'success' => true,
                'message' => 'Смета успешно обновлена',
                'total_amount' => $totalAmount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении сметы: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Обновляет Excel-файл на основе элементов из базы данных
     */
    public function updateExcelFromItems(Estimate $estimate)
    {
        // Получаем все элементы сметы
        $items = EstimateItem::where('estimate_id', $estimate->id)
            ->orderBy('position')
            ->get();
        
        if ($items->isEmpty()) {
            return false;
        }
        
        // Создаем новую книгу Excel или загружаем существующую
        $filePath = storage_path('app/public/' . $estimate->file_path);
        
        try {
            if (file_exists($filePath)) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            } else {
                // Если файла нет, создаем новый с учетом типа сметы
                $this->estimateExcelController->createInitialExcelFile($estimate);
                $filePath = storage_path('app/public/' . $estimate->file_path);
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            }
            
            $sheet = $spreadsheet->getActiveSheet();
            
            // Находим строку с заголовками
            $headerRow = 5;
            $startDataRow = $headerRow + 1;
            
            // Очищаем существующие данные, оставляя заголовки
            $lastRow = $sheet->getHighestRow();
            if ($lastRow > $startDataRow) {
                $sheet->removeRows($startDataRow, $lastRow - $startDataRow + 1);
            }
            
            // Добавляем данные на основе элементов из базы
            $currentRow = $startDataRow;
            foreach ($items as $item) {
                $sheet->setCellValue('A' . $currentRow, $item->position_number);
                $sheet->setCellValue('B' . $currentRow, $item->name);
                $sheet->setCellValue('C' . $currentRow, $item->unit);
                $sheet->setCellValue('D' . $currentRow, $item->quantity);
                $sheet->setCellValue('E' . $currentRow, $item->price);
                
                // Добавляем формулы для автоматического расчета
                // Формула для стоимости: =D{row}*E{row}
                $sheet->setCellValue('F' . $currentRow, "=D{$currentRow}*E{$currentRow}");
                
                $sheet->setCellValue('G' . $currentRow, $item->markup_percent);
                $sheet->setCellValue('H' . $currentRow, $item->discount_percent);
                
                // Формула для цены для заказчика: =E{row}*(1+G{row}/100)*(1-H{row}/100)
                $sheet->setCellValue('I' . $currentRow, "=E{$currentRow}*(1+G{$currentRow}/100)*(1-H{$currentRow}/100)");
                
                // Формула для стоимости для заказчика: =D{row}*I{row}
                $sheet->setCellValue('J' . $currentRow, "=D{$currentRow}*I{$currentRow}");
                
                // Если это заголовок раздела, форматируем его с улучшенным стилем
                if ($item->is_section_header) {
                    $sheet->getStyle('B' . $currentRow)->getFont()->setBold(true);
                    $sheet->getStyle('A' . $currentRow . ':J' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F8F9FA');
                }
                
                $currentRow++;
            }
            
            // Добавляем итоговую строку с формулами суммирования только для столбцов стоимости
            $sheet->setCellValue('B' . $currentRow, 'ИТОГО:');
            // Очищаем ячейки, где не нужны итоги
            $sheet->setCellValue('D' . $currentRow, '');  // Количество - не суммируем
            $sheet->setCellValue('E' . $currentRow, '');  // Цена - не суммируем
            // Суммирование значений колонки F (стоимость)
            $sheet->setCellValue('F' . $currentRow, "=SUM(F{$startDataRow}:F" . ($currentRow-1) . ")");
            // Очищаем остальные ячейки
            $sheet->setCellValue('G' . $currentRow, '');  // Наценка - не суммируем
            $sheet->setCellValue('H' . $currentRow, '');  // Скидка - не суммируем
            $sheet->setCellValue('I' . $currentRow, '');  // Цена для заказчика - не суммируем
            // Суммирование значений колонки J (стоимость для заказчика)
            $sheet->setCellValue('J' . $currentRow, "=SUM(J{$startDataRow}:J" . ($currentRow-1) . ")");

            // Сохраняем файл, сохраняя формулы
            $writer = new Xlsx($spreadsheet);
            // Отключаем предварительный расчет формул для сохранения их в файле
            $writer->setPreCalculateFormulas(false);
            $writer->save($filePath);
            
            // Обновляем информацию о файле в БД
            $fileSize = filesize($filePath);
            $estimate->update([
                'file_updated_at' => now(),
                'file_size' => $fileSize
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Ошибка при обновлении Excel из элементов: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Обновляет Excel-файл с новым элементом
     */
    public function updateExcelWithItem(Estimate $estimate, EstimateItem $item)
    {
        // Проверяем, существует ли файл
        if (!$estimate->file_path || !Storage::disk('public')->exists($estimate->file_path)) {
            // Если файла нет, создаем его с помощью сервиса
            $success = $this->estimateExcelController->createInitialExcelFile($estimate);
            if (!$success) return false;
        }
        
        // Обновляем файл со всеми элементами
        return $this->updateExcelFromItems($estimate);
    }
}
