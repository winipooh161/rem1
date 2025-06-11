<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class ProjectCheckController extends Controller
{
    /**
     * Получить список проверок проекта
     */
    public function listChecks(Project $project)
    {
        $this->authorize('view', $project);
        
        Log::debug('Запрос на получение списка проверок проекта', [
            'project_id' => $project->id
        ]);

        $checksList = $this->getChecksList();
        $checks = [];
        
        // Преобразуем ассоциативный массив в индексированный с добавлением id
        foreach ($checksList as $key => $value) {
            $value['id'] = $key; // Добавляем id в каждый элемент
            $checks[] = $value;
            
            $index = count($checks) - 1; // Индекс только что добавленного элемента
            
            // Для каждой категории проверяем количество выполненных чекбоксов
            $completed = 0;
            $total = 0;
            
            // Проверяем чекбоксы в категории
            if (isset($value['checkboxes'])) {
                $total = count($value['checkboxes']);
                
                foreach ($value['checkboxes'] as $checkbox) {
                    // Проверяем, отмечен ли чекбокс в БД
                    $checkItem = ProjectCheck::where('project_id', $project->id)
                        ->where('check_id', $checkbox['id'])
                        ->where('category', $key) // Используем ключ как category
                        ->first();
                    
                    if ($checkItem && $checkItem->status) {
                        $completed++;
                    }
                }
            } elseif (isset($value['photos'])) {
                foreach ($value['photos'] as $photo) {
                    if (isset($photo['id'])) {
                        $total++;
                        
                        $checkItem = ProjectCheck::where('project_id', $project->id)
                            ->where('check_id', $photo['id'])
                            ->where('category', $key) // Используем ключ как category
                            ->first();
                        
                        if ($checkItem && $checkItem->status) {
                            $completed++;
                        }
                    }
                }
            }
            
            // Добавляем информацию о завершенности всех чекбоксов
            $checks[$index]['all_completed'] = ($total > 0 && $completed === $total);
        }
        
        return response()->json([
            'success' => true,
            'items' => $checks
        ]);
    }

    /**
     * Отображение деталей проверки
     */
    public function show(Project $project, $check_id)
    {
        try {
            $this->authorize('view', $project);
            
            Log::debug('Запрос на получение детальной информации о проверке', [
                'project_id' => $project->id,
                'check_id' => $check_id
            ]);
            
            $details = $this->getCheckDetails($check_id);
            
            if (!$details) {
                return response()->json([
                    'success' => false,
                    'message' => 'Детали проверки не найдены'
                ], 404);
            }
            
            // Загружаем сохраненные данные для чекбоксов
            if (isset($details['checkboxes'])) {
                foreach ($details['checkboxes'] as &$checkbox) {
                    $checkItem = ProjectCheck::where('project_id', $project->id)
                        ->where('check_id', $checkbox['id'])
                        ->where('category', $check_id)
                        ->first();
                    
                    if ($checkItem) {
                        $checkbox['checked'] = (bool)$checkItem->status;
                        Log::debug('Состояние чекбокса: ' . $checkbox['id'], [
                            'checked' => (bool)$checkItem->status,
                            'status' => $checkItem->status
                        ]);
                    } else {
                        $checkbox['checked'] = false;
                    }
                }
            }
            
            // Загружаем сохраненные данные для фото-элементов
            if (isset($details['photos'])) {
                foreach ($details['photos'] as &$photo) {
                    if (isset($photo['id'])) {
                        $checkItem = ProjectCheck::where('project_id', $project->id)
                            ->where('check_id', $photo['id'])
                            ->where('category', $check_id)
                            ->first();
                        
                        if ($checkItem) {
                            $photo['checked'] = (bool)$checkItem->status;
                            Log::debug('Состояние фото-чекбокса: ' . $photo['id'], [
                                'checked' => (bool)$checkItem->status,
                                'status' => $checkItem->status
                            ]);
                        } else {
                            $photo['checked'] = false;
                        }
                    }
                }
            }
            
            // Получаем комментарий к проверке
            $comment = ProjectCheck::where('project_id', $project->id)
                ->where('category', $check_id)
                ->where('check_id', 0) // Используем check_id=0 для комментария к категории
                ->first();
            
            if ($comment) {
                $details['comment'] = $comment->comment;
            }
            
            // Генерируем HTML для отображения
            $html = View::make('partner.projects.partials.check_details', [
                'details' => $details,
                'project' => $project,
                'check_id' => $check_id
            ])->render();
            
            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при получении данных проверки', [
                'project_id' => $project->id, 
                'check_id' => $check_id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при загрузке данных проверки: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обновление статуса чекбокса
     */
    public function update(Request $request, Project $project, $check_id)
    {
        $this->authorize('update', $project);
        
        Log::info('Запрос на изменение статуса чекбокса проверки', [
            'project_id' => $project->id,
            'check_id' => $check_id,
            'status' => $request->checked,
            'category' => $request->input('category', 0)
        ]);

        $category = $request->input('category', 0);
        
        // Создаем или обновляем запись о проверке
        ProjectCheck::updateOrCreate(
            [
                'project_id' => $project->id,
                'check_id' => $check_id,
                'category' => $category
            ],
            [
                'status' => $request->checked,
                'user_id' => Auth::id()
            ]
        );
        
        // Проверяем, выполнены ли все чекбоксы в категории
        $all_completed = $this->checkAllCompleted($project->id, $category);
        
        return response()->json([
            'success' => true,
            'all_completed' => $all_completed
        ]);
    }

    /**
     * Обновление комментария к проверке
     */
    public function updateComment(Request $request, Project $project, $check_id)
    {
        $this->authorize('update', $project);
        
        // Используем check_id=0 для комментария к категории
        ProjectCheck::updateOrCreate(
            [
                'project_id' => $project->id,
                'check_id' => 0,
                'category' => $check_id
            ],
            [
                'comment' => $request->comment,
                'user_id' => Auth::id()
            ]
        );
        
        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Проверка, все ли чекбоксы в категории выполнены
     */
    private function checkAllCompleted($project_id, $category)
    {
        $details = $this->getCheckDetails($category);
        
        if (!$details) return false;
        
        $totalCheckboxes = 0;
        $completedCheckboxes = 0;
        
        // Подсчитываем чекбоксы в категории
        if (isset($details['checkboxes'])) {
            $totalCheckboxes = count($details['checkboxes']);
            
            foreach ($details['checkboxes'] as $checkbox) {
                $checkItem = ProjectCheck::where('project_id', $project_id)
                    ->where('check_id', $checkbox['id'])
                    ->where('category', $category)
                    ->first();
                
                if ($checkItem && $checkItem->status) {
                    $completedCheckboxes++;
                }
            }
        } elseif (isset($details['photos'])) {
            // Аналогично для фото-элементов
            foreach ($details['photos'] as $photo) {
                if (isset($photo['id'])) {
                    $totalCheckboxes++;
                    
                    $checkItem = ProjectCheck::where('project_id', $project_id)
                        ->where('check_id', $photo['id'])
                        ->where('category', $category)
                        ->first();
                    
                    if ($checkItem && $checkItem->status) {
                        $completedCheckboxes++;
                    }
                }
            }
        }
        
        return ($totalCheckboxes > 0 && $completedCheckboxes === $totalCheckboxes);
    }

    /**
     * Список доступных проверок
     */
    private function getChecksList()
    {
        return [
          1 => [
                'title' => 'Контроль качества этапа',
                'photos' => [
                    [
                        'id' => 100,
                        'checked' => false,
                        'image' => asset('images/Stage_Quality/1.png'),
                        'caption' => 'Создать Онлайн-чат по объекту (Вотсап или Телеграм)'
                    ],
                    [
                        'id' => 101,
                        'checked' => false,
                        'image' => asset('images/Stage_Quality/2.png'),
                        'caption' => 'Заполнить информацию по объекту в Личном кабинете (добавить скрин в чат в Вотсап)'
                    ],
                    [
                        'id' => 102,
                        'checked' => false,
                        'image' => asset('images/Stage_Quality/3.png'),
                        'caption' => 'Собрать информацию от УК (по файлу опроснику), добавить в чат объекта'
                    ],
                    [
                        'id' => 103,
                        'checked' => false,
                        'image' => asset('images/Stage_Quality/4.png'),
                        'caption' => 'Распечатать правила для объекта (разместить на объекте)'
                    ],
                    [
                        'id' => 104,
                        'checked' => false,
                        'image' => asset('images/Stage_Quality/5.png'),
                        'caption' => 'Распечатать наклейку, наклеить на входную дверь, СНАРУЖИ!'
                    ],
                    [
                        'id' => 105,
                        'checked' => false,
                        'image' => asset('images/Stage_Quality/6.png'),
                        'caption' => 'Распечатать последовательность этапов на А3'
                    ],
                    [
                        'id' => 106,
                        'checked' => false,
                        'image' => asset('images/Stage_Quality/7.png'),
                        'caption' => 'Укрыть холл коридора, дверь, окна по инструкции'
                    ],
                    [
                        'id' => 107,
                        'checked' => false,
                        'image' => asset('images/Stage_Quality/8.png'),
                        'caption' => 'Распечатать Дизайн-проект на А3, в сшитом формате'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 108, 'checked' => false, 'text' => 'Завести папку по объекту для документов, подшить оригинал подписанного Договора'],
                    ['id' => 109, 'checked' => false, 'text' => 'Создать объект в Личном Кабинете dsc24.ru'],
                    ['id' => 110, 'checked' => false, 'text' => 'Создать карточку объекта в Битрикс'],
                    ['id' => 111, 'checked' => false, 'text' => 'Добавить в карточку объекта в Битриксе - Линейный план-график в Эксель'],
                    ['id' => 112, 'checked' => false, 'text' => 'Добавить в карточку объекта в Битрикс  - Структуру выплат в Эксель'],
                    ['id' => 113, 'checked' => false, 'text' => 'Отправить Заказчику доступ к сайту Личного кабинета'],
                    ['id' => 114, 'checked' => false, 'text' => 'Добавить дизайнера наблюдателем к объекту в Личном кабинете'],
                    ['id' => 115, 'checked' => false, 'text' => 'Ознакомить Бригаду с проектом и Журналом контроля качества'],
                    ['id' => 116, 'checked' => false, 'text' => 'Доставить первую партию материалов на объект'],
                    ['id' => 117, 'checked' => false, 'text' => 'Начать первый этап ремонтных работ'],
                ],
                'comment' => ''
           ],
               2 => [
                'title' => 'Возведение перегородок',
                'photos' => [
                    [
                        'id' => 200,
                        'checked' => false,
                        'image' => asset('images/Construction_partitions/1.png'),
                        'caption' => 'Диагональ всех проемов до 5 мм'
                    ],
                    [
                        'id' => 201,
                        'checked' => false,
                        'image' => asset('images/Construction_partitions/2.png'),
                        'caption' => 'Проверка вертикали перегородок по уровню до 5 мм'
                    ],
                    [
                        'id' => 202,
                        'checked' => false,
                        'image' => asset('images/Construction_partitions/3.png'),
                        'caption' => 'Проверка всех углов 90 градусов (проверить все углы по проекту)'
                    ],
                    [
                        'id' => 203,
                        'checked' => false,
                        'image' => asset('images/Construction_partitions/4.png'),
                        'caption' => 'Наличие арматуры или кладочной сетки (через каждые 3 ряда)'
                    ],
                    [
                        'id' => 204,
                        'checked' => false,
                        'image' => asset('images/Construction_partitions/5.png'),
                        'caption' => 'Наличие крепежных уголков или арматуры'
                    ],
                    [
                        'id' => 205,
                        'checked' => false,
                        'image' => asset('images/Construction_partitions/6.png'),
                        'caption' => 'Отсутствие просветов в швах'
                    ],
                    [
                        'id' => 206,
                        'checked' => false,
                        'image' => asset('images/Construction_partitions/7.png'),
                        'caption' => 'Установлены перемычки в проёмах'
                    ],
                    [
                        'id' => 207,
                        'checked' => false,
                        'image' => asset('images/Construction_partitions/8.png'),
                        'caption' => 'Тонкошовная кладка'
                    ],
                    [
                        'id' => 208,
                        'checked' => false,
                        'image' => asset('images/Construction_partitions/9.png'),
                        'caption' => 'Примыкание к перекрытию заполнено пеной'
                    ],
                    [
                        'id' => 209,
                        'checked' => false,
                        'image' => asset('images/Construction_partitions/10.png'),
                        'caption' => 'Разбежка блоков в укладке не менее 0,4 длины блока'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 210, 'checked' => false, 'text' => 'Проверить качество доставленного материала от поставщика (блоки, клей и т.п.)'],
                    ['id' => 211, 'checked' => false, 'text' => 'Проверить поверхность основания перекрытия, отсутствие мусора и загрязнения'],
                    ['id' => 212, 'checked' => false, 'text' => 'При возведении перегородок УЧЕСТЬ РАЗМЕРЫ НИШ ПОД ШКАФЫ (РАЗМЕРЫ УКАЗАНЫ С УЧЕТОМ ШТУКАТУРКИ) !!!'],
                    ['id' => 213, 'checked' => false, 'text' => 'Проверить разметку под возведение перегородок (соответствие с Дизайн-проектом или техническим Дизайном)'],
                    ['id' => 214, 'checked' => false, 'text' => 'Проверить грунтование основания перед установкой первого ряда'],
                    ['id' => 215, 'checked' => false, 'text' => 'Проверить установку первого ряда на ЦПС (цементно-песчаную смесь)'],
                    ['id' => 216, 'checked' => false, 'text' => 'Проверить уровень первого ряда (горизонталь и вертикаль по уровню)'],
                    ['id' => 217, 'checked' => false, 'text' => 'Проверить толщину вертикального шва (тонкошовная кладка)'],
                    ['id' => 218, 'checked' => false, 'text' => 'Проверить монтаж кладочной сетки или армирование (арматура 6 – 8 мм) через каждые 2 ряда кладки'],
                    ['id' => 219, 'checked' => false, 'text' => 'Проверить установку металлических уголков, крепление к внешним стенам (или засверливание арматурой)'],
                    ['id' => 220, 'checked' => false, 'text' => 'Проверить горизонтальный шов (притертый, тонкий шов толщиной не более 2 -3 мм'],
                    ['id' => 221, 'checked' => false, 'text' => 'Проверить разбежку вертикальных швов (фото)'],
                    ['id' => 222, 'checked' => false, 'text' => 'Проверить углы 90 градусов в соответствии с Дизайн проектом'],
                    ['id' => 223, 'checked' => false, 'text' => 'Проверить плоскость перегородок 2х-метровым правилом, на стадии возведения на высоту от 1,5 метра от основания (зазор не более 3 мм)'],
                    ['id' => 224, 'checked' => false, 'text' => 'ДВЕРНЫЕ ПРОЁМЫ ЧЕТКО ПО ПРОЕКТУ !!!!! ПРОВЕРКА НОЛЬ ОТ ЧИСТОВОГО ПОЛА КОРИДОРА !!!'],
                    ['id' => 225, 'checked' => false, 'text' => 'Проверить монтаж перемычек в проемах (установка уголков или армирование металлическими прутами толщиной 10 – 12 мм'],
                    ['id' => 226, 'checked' => false, 'text' => 'Проверить обработку металлических перемычек антикоррозийным средством'],
                    ['id' => 227, 'checked' => false, 'text' => 'Проверить вертикаль правилом по уровню плоскости всех перегородок'],
                    ['id' => 228, 'checked' => false, 'text' => 'Проверить диагональ всех проемов правилом в соответствии с фото'],
                    ['id' => 229, 'checked' => false, 'text' => 'Проверить заполненность швов, отсутствие просветов в швах'],
                    ['id' => 230, 'checked' => false, 'text' => 'Проверить вертикальное примыкание перегородок к перекрытию (запененный шов)'],
                    ['id' => 231, 'checked' => false, 'text' => 'СДЕЛАТЬ КОНТРОЛЬНЫЙ ЗАМЕР ВСЕХ ПОМЕЩЕНИЙ, ЗАПИСАТЬ ИТОГОВЫЕ РАЗМЕРЫ НА БУМАЖНОМ ПРОЕКТЕ, ОТПРАВИТЬ ФОТО В ГРУППУ !!!'],
                    ['id' => 232, 'checked' => false, 'text' => 'Сделать фото-отчет этапа перегородок, добавить в личный кабинет Заказчику'],
                    ['id' => 233, 'checked' => false, 'text' => 'ЭТАП ПРИНИМАЕТСЯ ПОСЛЕ УБОРКИ ОБЪЕКТА'],
                ],
                'comment' => ''
            ],
            3 => [
                'title' => 'Штукатурные работы',
                'photos' => [
                    [
                        'id' => 300,
                        'checked' => false,
                        'image' => asset('images/Plastering_Work/1.png'),
                        'caption' => 'Проверка качества штукатурки'
                    ],
                    [
                        'id' => 301,
                        'checked' => false,
                        'image' => asset('images/Plastering_Work/2.png'),
                        'caption' => 'Проверка наличия маяков'
                    ],
                    [
                        'id' => 302,
                        'checked' => false,
                        'image' => asset('images/Plastering_Work/3.png'),
                        'caption' => 'Проверка углов 90 градусов'
                    ],
                    [
                        'id' => 303,
                        'checked' => false,
                        'image' => asset('images/Plastering_Work/4.png'),
                        'caption' => 'Проверка плоскости стен'
                    ],
                    [
                        'id' => 304,
                        'checked' => false,
                        'image' => asset('images/Plastering_Work/5.png'),
                        'caption' => 'Проверка диагоналей проемов'
                    ],
                    [
                        'id' => 305,
                        'checked' => false,
                        'image' => asset('images/Plastering_Work/6.png'),
                        'caption' => 'Проверка вертикали перегородок'
                    ],
                    [
                        'id' => 306,
                        'checked' => false,
                        'image' => asset('images/Plastering_Work/7.png'),
                        'caption' => 'Проверка размеров ниш под шкафы'
                    ],
                    [
                        'id' => 307,
                        'checked' => false,
                        'image' => asset('images/Plastering_Work/8.png'),
                        'caption' => 'Проверка качества малярных уголков'
                    ],
                    [
                        'id' => 308,
                        'checked' => false,
                        'image' => asset('images/Plastering_Work/9.png'),
                        'caption' => 'Проверка заполнения штукатуркой всей плоскости стены'
                    ],
                    [
                        'id' => 309,
                        'checked' => false,
                        'image' => asset('images/Plastering_Work/10.png'),
                        'caption' => 'Проверка оштукатуренных углов 90 градусов'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 310, 'checked' => false, 'text' => 'Обработка стен (бетоноконтакт)'],
                    ['id' => 311, 'checked' => false, 'text' => 'Обработка стен (грунтовка)'],
                    ['id' => 312, 'checked' => false, 'text' => 'Проверка установки маяков + углы 90 град'],
                    ['id' => 313, 'checked' => false, 'text' => 'Равномерное нанесение смеси (проверка)'],
                    ['id' => 314, 'checked' => false, 'text' => 'Полное нанесение (у пола и потолка)'],
                    ['id' => 315, 'checked' => false, 'text' => 'Удаление маяков (заделка швов)'],
                    ['id' => 316, 'checked' => false, 'text' => 'Проверить плоскости до 2,5 мм на 3 метра'],
                    ['id' => 317, 'checked' => false, 'text' => 'Проверить диагонали всех проемов до 2,5 мм на 3 метра'],
                    ['id' => 318, 'checked' => false, 'text' => 'Проверить все Углы 90 градусов'],
                    ['id' => 319, 'checked' => false, 'text' => 'Проверка вертикали'],
                    ['id' => 320, 'checked' => false, 'text' => 'Проверка размеров Санузлов (проект)'],
                    ['id' => 321, 'checked' => false, 'text' => 'Согласовать с подрядчиком проведение штукатурных работ (механизированная или ручная), определить углы 90 градусов'],
                    ['id' => 322, 'checked' => false, 'text' => 'Проверить доставку материала в соответствии с накладной и его качество'],
                    ['id' => 323, 'checked' => false, 'text' => 'Проверить обработку стен грунтовкой (или бетоноконтактом)'],
                    ['id' => 324, 'checked' => false, 'text' => 'Проверить установку маяков, по уровню, углы 90 градусов'],
                    ['id' => 325, 'checked' => false, 'text' => 'Проверить размеры дверных и оконных откосов на симметричность (одинаковые по размерам)'],
                    ['id' => 326, 'checked' => false, 'text' => 'Проверить качество малярных уголков (отсутствие ржавчины)'],
                    ['id' => 327, 'checked' => false, 'text' => 'Проверить заполнение штукатуркой всей плоскости стены (отсутствие зазоров у потолка и у пола)'],
                    ['id' => 328, 'checked' => false, 'text' => 'Проверить оштукатуренные углы 90 градусов'],
                    ['id' => 329, 'checked' => false, 'text' => 'Проверить оштукатуривание откосов (при условии их оштукатуривания по проекту) угол между окном и откосом по проекту'],
                    ['id' => 330, 'checked' => false, 'text' => 'Проверить удаление 100% всех маяков'],
                    ['id' => 331, 'checked' => false, 'text' => 'Проверить качество оштукатуренной плоскости стен (отсутствие глубоких царапин, ям и кратеров) поверхность однородная'],
                    ['id' => 332, 'checked' => false, 'text' => 'Проверить вертикаль перегородок после оштукатуривания ЛАЗЕРНЫМ ОСЕПОСТРОИТЕЛЕМ !!!'],
                    ['id' => 333, 'checked' => false, 'text' => 'Проверить диагонали проемов по правилу'],
                    ['id' => 334, 'checked' => false, 'text' => 'ПРОВЕРКА НИШ ПОД МЕБЕЛЬ ЛАЗЕРОМ С ФОТООТЧЕТОМ (В СООТВЕТСТВИИ С ВИЗУАЛИЗАЦИЕЙ НА ПРОЕКТЕ) !!!'],
                    ['id' => 335, 'checked' => false, 'text' => 'Проверить оштукатуривание санузлов цементно-песчаной штукатуркой'],
                    ['id' => 336, 'checked' => false, 'text' => 'СДЕЛАТЬ КОНТРОЛЬНЫЙ ЗАМЕР ВСЕХ ПОМЕЩЕНИЙ И ЗАПИСАТЬ РАЗМЕРЫ НА БУМАЖНОМ ПРОЕКТЕ (СОГЛАСОВАТЬ С ДИЗАЙНЕРОМ)'],
                    ['id' => 338, 'checked' => false, 'text' => 'ЭТАП ПРИНИМАЕТСЯ ТОЛЬКО ПОСЛЕ УБОРКИ ОБЪЕКТА'],
                ],
                'comment' => ''
            ],
            4 => [
                'title' => 'Монтаж шуманет',
                'photos' => [
                    [
                        'id' => 400,
                        'checked' => false,
                        'image' => asset('images/Shumanet/1.png'),
                        'caption' => 'Проверка монтажа шуманет'
                    ],
                    [
                        'id' => 401,
                        'checked' => false,
                        'image' => asset('images/Shumanet/2.png'),
                        'caption' => 'Проверка нахлеста на стены'
                    ],
                    [
                        'id' => 402,
                        'checked' => false,
                        'image' => asset('images/Shumanet/3.png'),
                        'caption' => 'Проверка спайки нахлестов газовой горелкой'
                    ],
                    [
                        'id' => 403,
                        'checked' => false,
                        'image' => asset('images/Shumanet/4.png'),
                        'caption' => 'Проверка обертывания коммуникаций'
                    ],
                    [
                        'id' => 404,
                        'checked' => false,
                        'image' => asset('images/Shumanet/5.png'),
                        'caption' => 'Проверка монтажа трасс коммуникаций'
                    ],
                    [
                        'id' => 405,
                        'checked' => false,
                        'image' => asset('images/Shumanet/6.png'),
                        'caption' => 'Проверка крепления трасс через изоляцию'
                    ],
                    [
                        'id' => 406,
                        'checked' => false,
                        'image' => asset('images/Shumanet/7.png'),
                        'caption' => 'Проверка доставки материалов на объект'
                    ],
                    [
                        'id' => 407,
                        'checked' => false,
                        'image' => asset('images/Shumanet/8.png'),
                        'caption' => 'Проверка чистоты перекрытия'
                    ],
                    [
                        'id' => 408,
                        'checked' => false,
                        'image' => asset('images/Shumanet/9.png'),
                        'caption' => 'Проверка монтажа шуманет в каждом помещении'
                    ],
                    [
                        'id' => 409,
                        'checked' => false,
                        'image' => asset('images/Shumanet/10.png'),
                        'caption' => 'Проверка качественной спайки листов шуманет'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 410, 'checked' => false, 'text' => 'Уборка поверхности пола'],
                    ['id' => 411, 'checked' => false, 'text' => 'Нахлест на стены выше уровня стяжки'],
                    ['id' => 412, 'checked' => false, 'text' => 'Спаять нахлесты материала газовой горелкой'],
                    ['id' => 413, 'checked' => false, 'text' => 'Обернуть коммуникации'],
                    ['id' => 414, 'checked' => false, 'text' => 'Произвести монтаж трасс коммуникаций'],
                    ['id' => 415, 'checked' => false, 'text' => 'Крепление трасс через изоляцию без использования дюбеля'],
                    ['id' => 416, 'checked' => false, 'text' => 'Доставить материалы на объект проверить по накладной'],
                    ['id' => 417, 'checked' => false, 'text' => 'Донести до монтажника требования к качеству монтажа'],
                    ['id' => 418, 'checked' => false, 'text' => 'Проверить чистоту перекрытия, без мусора'],
                    ['id' => 419, 'checked' => false, 'text' => 'Проверить монтаж шуманет в каждом помещении (нахлест листов, заход на стены не менее 150 мм)'],
                    ['id' => 420, 'checked' => false, 'text' => 'Проверить качественную спайку листов шуманет'],
                    ['id' => 421, 'checked' => false, 'text' => 'Проверить монтаж трасс инженерных систем (крепление трасс сделано спайкой)'],
                    ['id' => 422, 'checked' => false, 'text' => 'Проверить обход коммуникаций (металлические трубы отопления, проходящие сквозь перекрытия и т.п.)'],
                    ['id' => 423, 'checked' => false, 'text' => 'Проверить монтаж шуманета в углах помещения (подрезка внутренних углов)'],
                    ['id' => 424, 'checked' => false, 'text' => 'Монтажник УБИРАЕТ МУСОР после своей работы и передаёт чистое помещение'],
                    ['id' => 425, 'checked' => false, 'text' => 'Сделать фото-отчет этапа укладки шуманета и загрузить фото в личный кабинет Заказчика'],
                ],
                'comment' => ''
            ],
            5 => [
                'title' => 'Электрика черновая',
                'photos' => [
                    [
                        'id' => 500,
                        'checked' => false,
                        'image' => asset('images/Electrical_Installation/1.png'),
                        'caption' => 'Равномерная укладка кабеля 40-60 (см) между креплением. По полу'
                    ],
                    [
                        'id' => 501,
                        'checked' => false,
                        'image' => asset('images/Electrical_Installation/2.png'),
                        'caption' => 'Равномерная укладка кабеля 40 (см) между клипсами (max). По потолку'
                    ],
                    [
                        'id' => 502,
                        'checked' => false,
                        'image' => asset('images/Electrical_Installation/3.png'),
                        'caption' => 'Горизонталь подрозетников проверить по лазерному нивелиру'
                    ],
                    [
                        'id' => 503,
                        'checked' => false,
                        'image' => asset('images/Electrical_Installation/4.png'),
                        'caption' => 'Подрозетники не выступают За плоскость стен более 1 мм'
                    ],
                    [
                        'id' => 504,
                        'checked' => false,
                        'image' => asset('images/Electrical_Installation/5.png'),
                        'caption' => 'Проверить проходные выключатели'
                    ],
                    [
                        'id' => 505,
                        'checked' => false,
                        'image' => asset('images/Electrical_Installation/6.png'),
                        'caption' => 'Проверить подрозетник под ТП и закладную гофру под датчик'
                    ],
                    [
                        'id' => 506,
                        'checked' => false,
                        'image' => asset('images/Electrical_Installation/7.png'),
                        'caption' => 'Аккуратная сборка щита собранная через концевики'
                    ],
                    [
                        'id' => 507,
                        'checked' => false,
                        'image' => asset('images/Electrical_Installation/8.png'),
                        'caption' => 'Проверить освещение во всех помещениях'
                    ],
                    [
                        'id' => 508,
                        'checked' => false,
                        'image' => asset('images/Electrical_Installation/9.png'),
                        'caption' => 'Выполнена 100% МАРКИРОВКА щита и кабеля (в розетках, выкл, выводах и т.п.) + таблица маркировки'
                    ],
                    
                ],
                'checkboxes' => [

                    ['id' => 523, 'checked' => false, 'text' => 'Проверить качество монтажа шлейфов по потолку (отсутствие провисания, диагоналей, равномерная укладка, надежность крепежа)'],
                    ['id' => 524, 'checked' => false, 'text' => 'Проверить качество монтажа шлейфов по полу (диагоналей, равномерная укладка, надежность крепежа)'],
                    ['id' => 525, 'checked' => false, 'text' => 'Проверить ровность штробления в перегородках под кабель'],
                    ['id' => 526, 'checked' => false, 'text' => 'ПРОВЕРИТЬ УСТАНОВКУ ЗАКЛАДНЫХ ГОФР 16 ММ 2 ШТ (ИЛИ МЕТАЛЛОПЛАСТ 16 ММ) ПОД ДАТЧИК ТЕПЛОГО ПОЛА И ПОД ПИТАЮЩИЙ КАБЕЛЬ !!!'],
                    ['id' => 527, 'checked' => false, 'text' => 'Проверить установку подрозетников (размеры по проекту, горизонталь, не выступают за плоскость стены)'],
                    ['id' => 528, 'checked' => false, 'text' => 'Проверить проброс кабеля на проходные и перекрестные выключатели'],
                    ['id' => 529, 'checked' => false, 'text' => 'ПРЕДУСМОТРЕТЬ РАСПОЛОЖЕНИЕ БЛОКОВ ПИТАНИЯ ДЛЯ LED ИЛИ ОТДЕЛЬНОГО СЛАБОТОЧНОГО ЭЛЕКТРОЩИТА ДЛЯ ПОДСВЕТКИ !!!'],
                    ['id' => 530, 'checked' => false, 'text' => 'Проверить маркировку ВСЕХ электрокабелей'],
                    ['id' => 531, 'checked' => false, 'text' => 'Проверить наличие кабеля для системы от протечек'],
                    ['id' => 532, 'checked' => false, 'text' => 'Проверить наличие кабелей для ВСЕХ датчиков от протечки (3-х жильные) в соответствии со схемой проекта'],
                    ['id' => 533, 'checked' => false, 'text' => 'Проверить позиционирование электрощита и слаботочного щита (размеры по проекту и согласовать с Дизайнером)'],
                    ['id' => 534, 'checked' => false, 'text' => 'ПРОВЕРИТЬ СБОРКУ ЩИТА В СООТВЕТСТВИИ СО СХЕМОЙ (ПО ГРУППАМ)'],
                    ['id' => 535, 'checked' => false, 'text' => 'Проверить аккуратную укладку проводов в электрощите (монтаж кабелей за планками модулей)'],
                    ['id' => 536, 'checked' => false, 'text' => 'Проверить установку стандартных шин в электрощите (а не перемычек)'],
                    ['id' => 537, 'checked' => false, 'text' => 'Проверить сборку в электрощите, контакты кабеля (монтаж выполнен через концевики)'],
                    ['id' => 538, 'checked' => false, 'text' => 'Проверить подключение электрощита к вводному кабелю'],
                    ['id' => 539, 'checked' => false, 'text' => 'ПРОВЕРИТЬ РАБОТОСПОСОБНОСТЬ ВСЕХ ГРУПП (ПОДКЛЮЧИТЬ К ГРУППЕ ПАТРОН С ЛАМПОЙ И ВЗВЕСТИ АВТОМАТ) !!!'],
                    ['id' => 540, 'checked' => false, 'text' => 'Проверить подключение модуля системы от протечек'],
                    ['id' => 541, 'checked' => false, 'text' => 'ПРОВЕРКА МОНТАЖА БЛОКОВ ПИТАНИЯ И КАБЕЛЕЙ ДЛЯ ВСЕХ LED ПОДСВЕТОК !!!'],
                    ['id' => 542, 'checked' => false, 'text' => 'Проверить что сделана маркировка электрощита (бирки)'],
                    ['id' => 543, 'checked' => false, 'text' => 'Проверить монтаж КУП в санузлах и подключение к электрощиту и общему контуру заземления (коробка уравнивания потенциалов)'],
                    ['id' => 544, 'checked' => false, 'text' => 'Проверить что подрядчик заполнил таблицу маркировки автоматов (на бумаге по шаблону)'],
                    ['id' => 545, 'checked' => false, 'text' => 'ПОДРЯДЧИК ПОСЛЕ ЗАВЕРШЕНИЯ РАБОТ УБИРАЕТ МУСОР ЗА СОБОЙ И ПЕРЕДАЁТ ОСТАВШИЙСЯ МАТЕРИАЛ !!!'],
                    ['id' => 546, 'checked' => false, 'text' => 'Сделать фото-отчет этапа электромонтажа (10 -15 фото)'],
                    ['id' => 547, 'checked' => false, 'text' => 'Сделать схему или согласовать план трассировки электрокабелей + отопления и сантехники (по полу)'],
                ],
                'comment' => ''
            ],
            6 => [
                'title' => 'Сантехника черновая',
                'photos' => [
                    [
                        'id' => 600,
                        'checked' => false,
                        'image' => asset('images/Plumbing_Work/1.png'),
                        'caption' => 'Равномерная укладка трубы. Надежное крепление. По полу'
                    ],
                    [
                        'id' => 601,
                        'checked' => false,
                        'image' => asset('images/Plumbing_Work/2.png'),
                        'caption' => 'Размеры точек по проекту. Монтаж по уровню (соответствие с проектом)'
                    ],
                    [
                        'id' => 602,
                        'checked' => false,
                        'image' => asset('images/Plumbing_Work/3.png'),
                        'caption' => 'Расположение сложных систем водоснабжения (скрытый душ) соответствие с проектом'
                    ],
                    [
                        'id' => 603,
                        'checked' => false,
                        'image' => asset('images/Plumbing_Work/4.png'),
                        'caption' => 'Расположение датчиков (соответствие с проектом)'
                    ],
                    [
                        'id' => 604,
                        'checked' => false,
                        'image' => asset('images/Plumbing_Work/5.png'),
                        'caption' => 'Проверить сборку гидроузла (ровная сборка). ПО УРОВНЮ'
                    ],
                    [
                        'id' => 605,
                        'checked' => false,
                        'image' => asset('images/Plumbing_Work/6.png'),
                        'caption' => 'Результаты гидроиспытания (опрессовать систему)'
                    ],
                    [
                        'id' => 606,
                        'checked' => false,
                        'image' => asset('images/Plumbing_Work/7.png'),
                        'caption' => 'Проверить расположение всех точек в соответствии с Планом сантехники'
                    ],
                    [
                        'id' => 607,
                        'checked' => false,
                        'image' => asset('images/Plumbing_Work/8.png'),
                        'caption' => 'Подключить краны и проверить работу автоматики'
                    ],
                    [
                        'id' => 608,
                        'checked' => false,
                        'image' => asset('images/Plumbing_Work/9.png'),
                        'caption' => 'Подписать коллектор (потребители) и точки'
                    ],
                   
                ],
                'checkboxes' => [

                    ['id' => 619, 'checked' => false, 'text' => 'Пересечение труб и кабеля (электрические кабели уложены поверх труб отопления или водоснабжения)'],
                    ['id' => 620, 'checked' => false, 'text' => 'Сделана схема трассировки (отопления, водоснабжения, электрокабелей) и находится на объекте + схема расположения датчиков протечки'],
                    ['id' => 621, 'checked' => false, 'text' => 'МОНТАЖНИКИ ПО САНТЕХНИКЕ ОЗНАКОМЛЕНЫ СО СХЕМАМИ И ЗНАЮТ С КАКИМ КАЧЕСТВОМ ДОЛЖЕН БЫТЬ ВЫПОЛНЕН МОНТАЖ'],
                    ['id' => 622, 'checked' => false, 'text' => 'ПРОВЕРИТЬ СООТВЕТСТВИЕ ВСЕХ ВОДЯНЫХ ТОЧЕК ПРОЕКТУ, ПРОВЕРИТЬ ВСЕ РАЗМЕРЫ ТОЧЕК !!!'],
                    ['id' => 623, 'checked' => false, 'text' => 'БОЙЛЕР: установочное место, учесть выходы с гребенки (гор+хол), учесть канализационный выход для слива с бойлера'],
                    ['id' => 624, 'checked' => false, 'text' => 'Проверить отсутствие выступов трубопроводов в штробе из плоскости стен'],
                    ['id' => 625, 'checked' => false, 'text' => 'Проверить надежность крепления алюминиевых листов для облагораживания ниши (при их наличии)'],
                    ['id' => 626, 'checked' => false, 'text' => 'Проверить аккуратную сборку гидроузла, надежность крепления коллекторов, проверить горизонталь коллектора по уровню'],
                    ['id' => 627, 'checked' => false, 'text' => 'Проверить надежное крепление трубопроводов в штробе и по полу (потолку)'],
                    ['id' => 628, 'checked' => false, 'text' => 'Монтаж резьбовых соединений выполнять только на Анаэробный герметик (красный)'],
                    ['id' => 629, 'checked' => false, 'text' => 'Проверить герметичность всех резьбовых соединений и натяжные фитинги'],
                    ['id' => 630, 'checked' => false, 'text' => 'Проверить монтаж всех скрытых смесителей (гиг. души, тропические души и т.п.)'],
                    ['id' => 631, 'checked' => false, 'text' => 'Проверить вывод патрубка канализации для слива с фильтров тонкой очистки'],
                    ['id' => 632, 'checked' => false, 'text' => 'ПРОВЕРИТЬ ВЫВОД ПАТРУБКА КАНАЛИЗИИ ДЛЯ МОНТАЖА ДРЕНАЖНЫХ ТРУБОК КОНДИЦИОНЕРА (Согласовать с кондиционерщиком)'],
                    ['id' => 633, 'checked' => false, 'text' => 'Проверить надежное крепление шлейфов по полу (потолку) без диагоналей и провисаний'],
                    ['id' => 634, 'checked' => false, 'text' => 'ПРОВЕРИТЬ МОНТАЖ КАБЕЛЯ ДЛЯ ДАТЧИКОВ ПРОТЕЧКИ (ПРОВЕРИТЬ КОЛИЧЕСТВО ПО ПРОЕКТУ)'],
                    ['id' => 635, 'checked' => false, 'text' => 'Водорозетки установлены на монтажные планки'],
                    ['id' => 636, 'checked' => false, 'text' => 'Все трубопроводы находятся в утеплителе (или гофре)'],
                    ['id' => 637, 'checked' => false, 'text' => 'ВЫВОДЫ С ГРЕБЕНОК ПРОМАРКИРОВАНЫ ПО ПОТРЕБИТЕЛЯМ'],
                    ['id' => 638, 'checked' => false, 'text' => 'Подключить модуль управления системы от протечек и проверить работоспособность кранов с электроприводом'],
                    ['id' => 639, 'checked' => false, 'text' => 'Проверить монтаж розетки и выключателя + монтаж светильника в сантехническом шкафу'],
                    ['id' => 640, 'checked' => false, 'text' => 'Проверить установку инсталляции (надежное крепление, расположение по проекту, высота в соответствии с инструкцией)'],
                    ['id' => 641, 'checked' => false, 'text' => 'Проверить установку заглушек на все водорозетки, подготовить систему к опрессовке'],
                    ['id' => 642, 'checked' => false, 'text' => 'ОПРЕССОВАТЬ СИСТЕМУ РАБОЧИМ ДАВЛЕНИЕМ, ПРОВЕРИТЬ НАЛИЧИЕ ПРОТЕЧЕК, УСТРАНИТЬ !!!'],
                    ['id' => 643, 'checked' => false, 'text' => 'Опрессовать систему гидравлическим аппаратом до 7 МПа, превышающим рабочее давление на 30%, выдержать 12 часов, проверить протечки'],
                    ['id' => 644, 'checked' => false, 'text' => 'ЗАЛИТЬ ТРАП !!!'],
                    ['id' => 645, 'checked' => false, 'text' => 'Убрать мусор из ниш сантехнических стояков (закрыть алюминиевым листом или другим влагостойким материалом)'],
                    ['id' => 646, 'checked' => false, 'text' => 'ПОДРЯДЧИК, ПОСЛЕ ЗАВЕРШЕНИЯ РАБОТ УБИРАЕТ ЗА СОБОЙ ВЕСЬ МУСОР и собирает в мешки, остатки материала передаёт ответственному за объект'],
                ],
                'comment' => ''
            ],
            7 => [
                'title' => 'Звукоизоляция стояков',
                'photos' => [
                    [
                        'id' => 700,
                        'checked' => false,
                        'image' => asset('images/Pipe_Soundproofing/1.png'),
                        'caption' => 'Привезти материал для звукоизоляции, комплект'
                    ],
                    [
                        'id' => 701,
                        'checked' => false,
                        'image' => asset('images/Pipe_Soundproofing/2.png'),
                        'caption' => 'Состав комплекта'
                    ],
                    [
                        'id' => 702,
                        'checked' => false,
                        'image' => asset('images/Pipe_Soundproofing/3.png'),
                        'caption' => 'Очистить трубу от пыли, достать мембрану, срезать 50 мм по длинному краю, нанести клей'
                    ],
                    [
                        'id' => 703,
                        'checked' => false,
                        'image' => asset('images/Pipe_Soundproofing/4.png'),
                        'caption' => 'Нанести также клей на трубу и обернуть мембраной внахлест (50 мм)'
                    ],
                    [
                        'id' => 704,
                        'checked' => false,
                        'image' => asset('images/Pipe_Soundproofing/5.png'),
                        'caption' => 'Металлизированным скотчем проклеить стыки, стянуть стяжками'
                    ],
                   
                ],
                'checkboxes' => [
                    ['id' => 710, 'checked' => false, 'text' => 'Первый фрагмент мембраны срезается 50 мм изолирующего слоя по длинной стороне полотна'],
                    ['id' => 711, 'checked' => false, 'text' => 'Второй и последующие фрагменты мембраны срезаются по длинной 50 мм и по короткой 50 мм для соединения внахлест'],
                    ['id' => 712, 'checked' => false, 'text' => 'После нанесения клей должен просохнуть 15-20 мин, после чего мембрану оборачивают вокруг трубы'],
                ],
                'comment' => ''
            ],
            8 => [
                'title' => 'Отопление',
                'photos' => [
                    [
                        'id' => 800,
                        'checked' => false,
                        'image' => asset('images/Heating/1.png'),
                        'caption' => 'Равномерная укладка трубы. Надежное крепление. По полу'
                    ],
                    [
                        'id' => 801,
                        'checked' => false,
                        'image' => asset('images/Heating/2.png'),
                        'caption' => 'Проверить сборку гидроузла (соответствие с проектом)'
                    ],
                    [
                        'id' => 802,
                        'checked' => false,
                        'image' => asset('images/Heating/3.png'),
                        'caption' => 'Проверить маркировку шкафа отопления (подписать выводы на отопление)'
                    ],
                    [
                        'id' => 803,
                        'checked' => false,
                        'image' => asset('images/Heating/4.png'),
                        'caption' => 'Проверить нижнее подключение (для настенных радиаторов)'
                    ],
                    [
                        'id' => 804,
                        'checked' => false,
                        'image' => asset('images/Heating/5.png'),
                        'caption' => 'Проверить расположение радиаторов по проекту'
                    ],
                    [
                        'id' => 805,
                        'checked' => false,
                        'image' => asset('images/Heating/6.png'),
                        'caption' => 'Проверить заделку штроб'
                    ],
                    [
                        'id' => 806,
                        'checked' => false,
                        'image' => asset('images/Heating/7.png'),
                        'caption' => 'Провести гидроиспытание системы на 24 часа'
                    ],
                    [
                        'id' => 807,
                        'checked' => false,
                        'image' => asset('images/Heating/8.png'),
                        'caption' => 'Проверить горизонталь радиаторов по уровню'
                    ],
                    
                ],
                'checkboxes' => [
                    ['id' => 818, 'checked' => false, 'text' => 'Сделать схему трассировки труб отопления по полу (отметить на проекте)'],
                    ['id' => 819, 'checked' => false, 'text' => 'Донести до подрядчика требования к качеству монтажа, проект отопления от Дизайнера, передать план-график работ (сроки работ)'],
                    ['id' => 820, 'checked' => false, 'text' => 'Проверить доставку материалов по накладной, качество материалов'],
                    ['id' => 821, 'checked' => false, 'text' => 'Проверить монтаж щита коллектора отопления в соответствии с проектом (место расположения и размеры согласовать с Дизайнером)'],
                    ['id' => 822, 'checked' => false, 'text' => 'ПРОВЕРИТЬ НИШУ ПОД ОТОПЛЕНИЕ, ОТДЕЛАННУЮ НЕРЖАВЕЙКОЙ'],
                    ['id' => 823, 'checked' => false, 'text' => 'Проверить трассировку труб и надежность крепления трасс к полу (крепление через каждые 50 – 60 см)'],
                    ['id' => 824, 'checked' => false, 'text' => 'ПРИВЕЗТИ РАДИАТОРЫ НА ОБЪЕКТ'],
                    ['id' => 825, 'checked' => false, 'text' => 'Проверить разметку под монтаж настенных радиаторов (размеры по проекту)'],
                    ['id' => 826, 'checked' => false, 'text' => 'Проверить сборку гидроузла в коллекторном щите, надежность крепления'],
                    ['id' => 827, 'checked' => false, 'text' => 'Проверить монтаж подводящих трубок к радиатору, соответствие радиаторам (нижнее подключение, монтаж из стены)'],
                    ['id' => 828, 'checked' => false, 'text' => 'Проверить монтаж радиаторов (горизонталь по уровню, надежное крепление, подключение к подводящим трассам)'],
                    ['id' => 829, 'checked' => false, 'text' => 'Проверить опрессовку системы отопления давлением на 7 МПа больше от рабочего'],
                    ['id' => 830, 'checked' => false, 'text' => 'ЗАДЕЛКУ ШТРОБ ПРОИЗВОДИМ НА ЭТАПЕ МАЛЯРНЫХ РАБОТ (Оставляем не заделанные)'],
                    ['id' => 831, 'checked' => false, 'text' => 'ПРОВЕРИТЬ СИСТЕМУ ОТОПЛЕНИЯ ПОСЛЕ ЗАПУСКА, РАВНУЮ ТЕМПЕРАТУРУ ВСЕХ РАДИАТОРОВ, ОТСУТСТВИЕ ЗАВОЗДУШЕННОСТИ'],
                    ['id' => 832, 'checked' => false, 'text' => 'Проверить подключение внутрипольных конвекторов (при наличии) + расположение по проекту (размеры)'],
                    ['id' => 833, 'checked' => false, 'text' => 'Проверить наличие электро-кабеля для вентиляторов конвекторов (при наличии)'],
                    ['id' => 834, 'checked' => false, 'text' => 'ПРОВЕРИТЬ УРОВЕНЬ ВНУТРИПОЛЬНЫХ КОНВЕКТОРОВ (УСТАНОВЛЕНЫ В ОДНОЙ ПЛОСКОСТИ С УРОВНЕМ ЧИСТОГО ПОЛА)'],
                    ['id' => 835, 'checked' => false, 'text' => 'ПОДРЯДЧИК ПОСЛЕ ЗАВЕРШЕНИЯ РАБОТ УБИРАЕТ МУСОР ЗА СОБОЙ и передаёт чистый объект'],
                    ['id' => 836, 'checked' => false, 'text' => 'Сделать фото-отчет этапа монтажа отопления (10-15 фото)'],
                ],
                'comment' => ''
            ],
            9 => [
                'title' => 'Стяжка',
                'photos' => [
                    [
                        'id' => 900,
                        'checked' => false,
                        'image' => asset('images/Floor/1.png'),
                        'caption' => 'Проверить наличие плана-стяжки (из проекта или обозначить высоты)'
                    ],
                    [
                        'id' => 901,
                        'checked' => false,
                        'image' => asset('images/Floor/2.png'),
                        'caption' => 'Проверить уровень «чистого пола»'
                    ],
                    [
                        'id' => 902,
                        'checked' => false,
                        'image' => asset('images/Floor/3.png'),
                        'caption' => 'Проверить перепады по помещениям (разные покрытия)'
                    ],
                    [
                        'id' => 903,
                        'checked' => false,
                        'image' => asset('images/Floor/4.png'),
                        'caption' => 'Проверить компенсационные швы'
                    ],
                    [
                        'id' => 904,
                        'checked' => false,
                        'image' => asset('images/Floor/5.png'),
                        'caption' => 'Проверить монтаж демпферной ленты'
                    ],
                    [
                        'id' => 905,
                        'checked' => false,
                        'image' => asset('images/Floor/6.png'),
                        'caption' => 'Проверить зазоры под правилом (до 3 мм на 2,5 метра)'
                    ],
                    [
                        'id' => 906,
                        'checked' => false,
                        'image' => asset('images/Floor/7.png'),
                        'caption' => 'Проверить горизонталь всей стяжки (лазерным нивелиром)'
                    ],
                    [
                        'id' => 907,
                        'checked' => false,
                        'image' => asset('images/Floor/8.png'),
                        'caption' => 'Уборка мусора подрядчиком (подъезд, лифт, холл)'
                    ],
                   
                ],
                'checkboxes' => [
                    ['id' => 918, 'checked' => false, 'text' => 'Подготовить план стяжки'],
                    ['id' => 919, 'checked' => false, 'text' => 'Довести до подрядчика критерии качества к полусухой стяжке'],
                    ['id' => 920, 'checked' => false, 'text' => 'Проверить разметку и уровень стяжки (с учетом укладки напольного покрытия в одну плоскость с коридорной плиткой)'],
                    ['id' => 921, 'checked' => false, 'text' => 'Проверить укрытие пола холла и лифтовой зоны подрядчиком'],
                    ['id' => 922, 'checked' => false, 'text' => 'Проверить монтаж демпферной ленты по периметру помещения'],
                    ['id' => 923, 'checked' => false, 'text' => 'Проверить перепады в помещениях под разные покрытия'],
                    ['id' => 924, 'checked' => false, 'text' => 'Проверить наличие компенсационных швов'],
                    ['id' => 925, 'checked' => false, 'text' => 'Проверить плоскость поверхности стяжки правилом (зазор до 3 мм - правило 2,5 метра)'],
                    ['id' => 926, 'checked' => false, 'text' => 'Проверить горизонталь всей поверхности стяжки лазерным нивелиром (отклонение 1 – 2 мм)'],
                    ['id' => 927, 'checked' => false, 'text' => 'УБОРКА подрядчика за собой, после завершения работ (без уборки, не оплачивается работа в полном объеме)'],
                    ['id' => 928, 'checked' => false, 'text' => 'Сделать фото-отчет этапа стяжки, загрузить в личный кабинет Личного кабинета'],
                ],
                'comment' => ''
            ],
            10 => [
                'title' => 'Вентиляция',
                'photos' => [
                    [
                        'id' => 1000,
                        'checked' => false,
                        'image' => asset('images/Ventilation/1.png'),
                        'caption' => 'Разметить трассу вентиляции ко всем точкам по проекту'
                    ],
                    [
                        'id' => 1001,
                        'checked' => false,
                        'image' => asset('images/Ventilation/2.png'),
                        'caption' => 'Составить список комплектующих для вентиляции'
                    ],
                    [
                        'id' => 1002,
                        'checked' => false,
                        'image' => asset('images/Ventilation/3.png'),
                        'caption' => 'Список комплектующих вентиляции для кухонной вытяжки'
                    ],
                    [
                        'id' => 1003,
                        'checked' => false,
                        'image' => asset('images/Ventilation/4.png'),
                        'caption' => 'Места соединений труб и переходов заизолировать АЛЮМИНИЕВЫМ СКОТЧЕМ!'
                    ],
                    [
                        'id' => 1004,
                        'checked' => false,
                        'image' => asset('images/Ventilation/5.png'),
                        'caption' => 'Надежно закрепить вентиляционный короб на кронштейны'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 1005, 'checked' => false, 'text' => 'ПРОВЕРИТЬ ВСЮ ТРАССУ ВЕНТИЛЯЦИИ ПЕРЕД МОНТАЖОМ ПОТОЛКА!'],
                    ['id' => 1006, 'checked' => false, 'text' => 'Сделать фото фиксацию всей трассы вентиляции, скрытые места'],
                ],
                'comment' => ''
            ],
            11 => [
                'title' => 'Звукоизоляция потолка',
                'photos' => [
                    [
                        'id' => 1100,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Soundproofing/1.png'),
                        'caption' => 'Оклеиваем лентой СтопЗвук V100 периметр стены с помощью виброгерметика Сонетик или клея Баутгер'
                    ],
                    [
                        'id' => 1101,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Soundproofing/2.png'),
                        'caption' => 'Монтаж термозвукоизола (Клей + ТЗИ)'
                    ],
                    [
                        'id' => 1102,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Soundproofing/3.png'),
                        'caption' => 'Фиксируем профиль ПН через ленту, Используем виброшайбу'
                    ],
                    [
                        'id' => 1103,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Soundproofing/4.png'),
                        'caption' => 'Крепим Виброподвесы с шагом 400 поперек и 600 вдоль комнаты'
                    ],
                    [
                        'id' => 1104,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Soundproofing/5.png'),
                        'caption' => 'Крепим профиль потолочный ПП + краб'
                    ],
                    [
                        'id' => 1105,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Soundproofing/6.png'),
                        'caption' => 'Наклеиваем демпферную ленту на поверхность профиля, заполняем пространство плитами СтопЗвук БП'
                    ],
                    [
                        'id' => 1106,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Soundproofing/7.png'),
                        'caption' => 'Обшиваем каркас листами АкустикГипс ГКЛЗ и промазываем соединение виброакустическим герметиком'
                    ],
                    [
                        'id' => 1107,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Soundproofing/8.png'),
                        'caption' => 'Обшиваем вторым слоем АкустикГипс ГКЛЗ'
                    ],
                    [
                        'id' => 1108,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Soundproofing/9.png'),
                        'caption' => 'Удаляем излишки ленты СтопЗвук V100 и герметизируем периметр виброакустическим герметиком'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 1109, 'checked' => false, 'text' => 'Проверить список закупаемых материалов в соответствии с применяемой системой звукоизоляции'],
                    ['id' => 1110, 'checked' => false, 'text' => 'Доставить материалы на объект, принято по накладной'],
                    ['id' => 1111, 'checked' => false, 'text' => 'Подготовить потолок к монтажу звукоизоляции (убрать мусор, срезать остатки проволоки и арматуры, провода и т.п.)'],
                    ['id' => 1112, 'checked' => false, 'text' => 'Проверить монтаж звукоизоляционной ленты по периметру потолка'],
                    ['id' => 1113, 'checked' => false, 'text' => 'Проверить монтаж термозвукоизола (надежное крепление, отсутствие провисания, нахлест без зазоров)'],
                    ['id' => 1114, 'checked' => false, 'text' => 'Проверить разметку горизонта потолка, направляющий профиль, (зазор от перекрытия, разметка выполнена по лазерному осепостроителю)'],
                    ['id' => 1115, 'checked' => false, 'text' => 'Проверить монтаж профиля через вибро-шайбу'],
                    ['id' => 1116, 'checked' => false, 'text' => 'Проверить монтаж вибро-подвесов (шаг потолочных профилей, ячейка 400мм – поперек, 600 мм – вдоль комнаты)'],
                    ['id' => 1117, 'checked' => false, 'text' => 'Проверить надежность крепления потолочных профилей (краб и соединители, не болтаются, саморезы надежно закручены)'],
                    ['id' => 1118, 'checked' => false, 'text' => 'Проверить монтаж звукоизоляционной базальтовой плиты (отсутствие зазоров и отверстий)'],
                    ['id' => 1119, 'checked' => false, 'text' => 'Проверить монтаж звукоизоляционной ленты поверх потолочных профилей (перед монтажом ГКЛ)'],
                    ['id' => 1120, 'checked' => false, 'text' => 'Проверить монтаж первого слоя ГКЛ (надежный крепеж, шаг саморезов до 200мм, стыки ГКЛ промазаны акустическим герметиком)'],
                    ['id' => 1121, 'checked' => false, 'text' => 'Проверить монтаж второго слоя ГКЛ, листы крепятся в разбежку, шаг между саморезами до 200 мм'],
                    ['id' => 1122, 'checked' => false, 'text' => 'Проверить что излишки ленты срезаны по периметру потолка и стык обработан акустическим герметиком'],
                    ['id' => 1123, 'checked' => false, 'text' => 'Проверить, что сделана расшивка кромок стыков ГКЛ'],
                    ['id' => 1124, 'checked' => false, 'text' => 'МОНТАЖНИКИ УБИРАЮТ ЗА СОБОЙ МУСОР И СДАЮТ ЭТАП РАБОТ С ЧИСТЫМ ОБЪЕКТОМ'],
                ],
                'comment' => ''
            ],
            12 => [
                'title' => 'Каркас ГКЛ потолка',
                'photos' => [
                    [
                        'id' => 1200,
                        'checked' => false,
                        'image' => asset('images/Drywall_Ceiling_Frame/1.png'),
                        'caption' => 'Проверка конструкции (ячейка 40х40 или 60х60) на 2 слоя'
                    ],
                    [
                        'id' => 1201,
                        'checked' => false,
                        'image' => asset('images/Drywall_Ceiling_Frame/2.png'),
                        'caption' => 'Проверка плоскости по уровню (лазерный нивелир)'
                    ],
                    [
                        'id' => 1202,
                        'checked' => false,
                        'image' => asset('images/Drywall_Ceiling_Frame/3.png'),
                        'caption' => 'Проверить надежность крепления соединителей Краб'
                    ],
                    [
                        'id' => 1203,
                        'checked' => false,
                        'image' => asset('images/Drywall_Ceiling_Frame/4.png'),
                        'caption' => 'Проверить надежность крепления подвесов и удлинителей'
                    ],
                    [
                        'id' => 1204,
                        'checked' => false,
                        'image' => asset('images/Drywall_Ceiling_Frame/5.png'),
                        'caption' => 'Проверить Размеры всех опусков под мебель'
                    ],
                    [
                        'id' => 1205,
                        'checked' => false,
                        'image' => asset('images/Drywall_Ceiling_Frame/6.png'),
                        'caption' => 'Проверить размеры всех скрытых ниш'
                    ],
                    [
                        'id' => 1206,
                        'checked' => false,
                        'image' => asset('images/Drywall_Ceiling_Frame/7.png'),
                        'caption' => 'Проверить монтаж закладных под трековые светильники (Размеры)'
                    ],
                    [
                        'id' => 1207,
                        'checked' => false,
                        'image' => asset('images/Drywall_Ceiling_Frame/8.png'),
                        'caption' => 'Проверить плоскость потолка (правило + уровень)'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 1208, 'checked' => false, 'text' => 'ИЗУЧИТЬ ВСЁ ЧИСТОВОЕ ОСВЕЩЕНИЕ!!!!'],
                    ['id' => 1209, 'checked' => false, 'text' => 'ВЫПОЛНИТЬ МОНТАЖ КОРОБОВ ВЕНТИЛЯЦИИ!!!'],
                    ['id' => 1210, 'checked' => false, 'text' => 'Проверить минимально возможный опуск потолка (сделать опуск потолка от перекрытия как можно меньше) – но учесть высоту треков и светильников'],
                    ['id' => 1211, 'checked' => false, 'text' => 'Проверить размер ячейки каркаса – не меньше 600х600 мм'],
                    ['id' => 1212, 'checked' => false, 'text' => 'Проверить надежность крепления (Краб, Подвесы и Соединители) – не должны болтаться и быть перекошенными'],
                    ['id' => 1213, 'checked' => false, 'text' => 'Проверить размеры под скрытые ниши, С УЧЕТОМ ГКЛ (по Проекту) – УЧЕСТЬ РАЗМЕР КАРНИЗОВ И ЛЕПНИНЫ'],
                    ['id' => 1214, 'checked' => false, 'text' => 'Проверить размеры опусков под мебель С УЧЕТОМ ГКЛ (по Проекту) – УЧЕСТЬ РАЗМЕР КАРНИЗОВ И ЛЕПНИНЫ'],
                    ['id' => 1215, 'checked' => false, 'text' => 'ПРОВЕРКА ПЛОСКОСТИ КАРКАСА ПО ЛАЗЕРНОМУ ОСЕПОСТРОИТЕЛЮ!!! ЗОНА МЕБЕЛИ!!! (закрепить на магнитной штанге)'],
                    ['id' => 1216, 'checked' => false, 'text' => 'Проверить размеры закладных под трековые светильники (по Проекту)'],
                    ['id' => 1217, 'checked' => false, 'text' => 'Проверить плоскость потолка на горизонт (правило + пузырьковый уровень)'],
                    ['id' => 1218, 'checked' => false, 'text' => 'Проверить плоскость продольных и поперечных профилей (3- метровым правилом)'],
                    ['id' => 1219, 'checked' => false, 'text' => 'ПРОВЕРИТЬ ВСТРОЕННЫЕ СВЕТИЛЬНИКИ И ТОЛЩИНУ ПОСЛЕДНЕГО СЛОЯ ГКЛ ПОД НИХ !!!'],
                    ['id' => 1220, 'checked' => false, 'text' => 'Проверить электрические кабели - выводы (которые должны находиться под потолком)'],
                    ['id' => 1221, 'checked' => false, 'text' => 'Проверить монтаж закладных под лючки (при наличии)'],
                ],
                'comment' => ''
            ],
            
            13 => [
                'title' => 'Монтаж ГКЛ потолка',
                'photos' => [
                    [
                        'id' => 1300,
                        'checked' => false,
                        'image' => asset('images/Drywall_Ceiling_Installation/1.png'),
                        'caption' => 'Проверка конструкции Каркас (ячейка 40х40 см) на 2 слоя'
                    ],
                    [
                        'id' => 1301,
                        'checked' => false,
                        'image' => asset('images/Drywall_Ceiling_Installation/2.png'),
                        'caption' => 'Проверка плоскости по уровню (лазерный нивелир)'
                    ],
                    [
                        'id' => 1302,
                        'checked' => false,
                        'image' => asset('images/Drywall_Ceiling_Installation/3.png'),
                        'caption' => 'Проверить разбежку листов ГКЛ'
                    ],
                    [
                        'id' => 1303,
                        'checked' => false,
                        'image' => asset('images/Drywall_Ceiling_Installation/4.png'),
                        'caption' => 'Проверить крепеж (шаг 17см) И монтаж сложных конструкций'
                    ],
                    [
                        'id' => 1304,
                        'checked' => false,
                        'image' => asset('images/Drywall_Ceiling_Installation/5.png'),
                        'caption' => 'Аккуратные стыковочные кромки'
                    ],
                    [
                        'id' => 1305,
                        'checked' => false,
                        'image' => asset('images/Drywall_Ceiling_Installation/6.png'),
                        'caption' => 'Проверка расшивки примыкающих кромок'
                    ],
                    [
                        'id' => 1306,
                        'checked' => false,
                        'image' => asset('images/Drywall_Ceiling_Installation/7.png'),
                        'caption' => 'Проверить монтаж 2-х слоев ГКЛ'
                    ],
                    
                ],
                'checkboxes' => [
                    ['id' => 1307, 'checked' => false, 'text' => 'ПРОВЕРИТЬ ВСТРОЕННЫЕ СВЕТИЛЬНИКИ И ТОЛЩИНУ ПОСЛЕДНЕГО СЛОЯ ГКЛ ПОД НИХ !!!'],
                    ['id' => 1308, 'checked' => false, 'text' => 'Проверка плоскости первого ряда гипсокартона'],
                    ['id' => 1309, 'checked' => false, 'text' => 'Проверить разбежку листов гипсокартона в шахматном порядке'],
                    ['id' => 1310, 'checked' => false, 'text' => 'Проверить ниши под карниз (крепление и размеры по проекту)'],
                    ['id' => 1311, 'checked' => false, 'text' => 'Проверить закладные под тяжелые люстры и светильники'],
                    ['id' => 1312, 'checked' => false, 'text' => 'ПРОВЕРИТЬ ПЛОСКОСТЬ ВСТРОЕННОГО СВЕТИЛЬНИКА И ГКЛ ПОТОЛКА'],
                    ['id' => 1313, 'checked' => false, 'text' => 'Проверить выпуск электрических выводов для потолочного освещения и светодиодных лент'],
                    ['id' => 1314, 'checked' => false, 'text' => 'Проверить шаг крепления саморезов (17-20 см между крепежом)'],
                    ['id' => 1315, 'checked' => false, 'text' => 'Проверить полки под светодиодные ленты'],
                    ['id' => 1316, 'checked' => false, 'text' => 'Проверить монтаж скрытых люков под покраску (установка в одну плоскость с потолком)'],
                    ['id' => 1317, 'checked' => false, 'text' => 'Проверить монтаж второго слоя потолка, разбежку и надежность крепежа'],
                    ['id' => 1318, 'checked' => false, 'text' => 'Проверить саморезы (шляпка утоплена в ГКЛ на 1-2 мм)'],
                    ['id' => 1319, 'checked' => false, 'text' => 'Проверить расшивку кромок стыковки листов ГКЛ'],
                    ['id' => 1320, 'checked' => false, 'text' => 'Проверить разметку и выпуски проводов освещения'],
                    ['id' => 1321, 'checked' => false, 'text' => 'Проверить примыкание потолка ГКЛ к стене'],
                ],
                'comment' => ''
            ],
            14 => [
                'title' => 'Балкон',
                'photos' => [
                    [
                        'id' => 1400,
                        'checked' => false,
                        'image' => asset('images/Balcony/1.png'),
                        'caption' => 'Стандартный пирог утепления'
                    ],
                    [
                        'id' => 1401,
                        'checked' => false,
                        'image' => asset('images/Balcony/2.png'),
                        'caption' => 'Демонтаж обшивки балкона и старого утеплителя'
                    ],
                    [
                        'id' => 1402,
                        'checked' => false,
                        'image' => asset('images/Balcony/3.png'),
                        'caption' => 'Проверить монтаж каркаса для пирога утепления'
                    ],
                    [
                        'id' => 1403,
                        'checked' => false,
                        'image' => asset('images/Balcony/4.png'),
                        'caption' => 'Проверить плотное заполнение утеплителем, без щелей'
                    ],
                    [
                        'id' => 1404,
                        'checked' => false,
                        'image' => asset('images/Balcony/5.png'),
                        'caption' => 'Проверить обшивку балкона ГКЛ листами'
                    ],
                    [
                        'id' => 1405,
                        'checked' => false,
                        'image' => asset('images/Balcony/6.png'),
                        'caption' => 'Проверить монтаж каркаса для потолка'
                    ],
                    [
                        'id' => 1406,
                        'checked' => false,
                        'image' => asset('images/Balcony/7.png'),
                        'caption' => 'Проверить установку подоконников + черновую шпаклевку'
                    ],
                    [
                        'id' => 1407,
                        'checked' => false,
                        'image' => asset('images/Balcony/8.png'),
                        'caption' => 'Проверить финишную шпаклёвку и покраску потолка и стен'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 1408, 'checked' => false, 'text' => 'Доставить необходимый материал для утепления'],
                    ['id' => 1409, 'checked' => false, 'text' => 'ЗАКАЗАТЬ ПОДОКОНИКИ!!!'],
                    ['id' => 1410, 'checked' => false, 'text' => 'Выполнить демонтаж старого покрытия балкона/лоджии'],
                    ['id' => 1411, 'checked' => false, 'text' => 'Проверить по проекту наличие электрики на балконе и теплого пола'],
                    ['id' => 1412, 'checked' => false, 'text' => 'Выполнить монтаж электропроводки в необходимые точки балкона'],
                    ['id' => 1413, 'checked' => false, 'text' => 'Проверить надежность крепления каркаса и плоскость по уровню'],
                    ['id' => 1414, 'checked' => false, 'text' => 'ПРОВЕРИТЬ ПЛОТНОСТЬ УСТАНОВКИ УТЕПЛИТЕЛЯ - ОТСУТСТВИЕ ЩЕЛЕЙ'],
                    ['id' => 1415, 'checked' => false, 'text' => 'Проверить монтаж ГКЛ и разделку кромок на стыках листов !'],
                    ['id' => 1416, 'checked' => false, 'text' => 'УСТАНОВИТЬ ПОДОКОНИКИ !!!!'],
                    ['id' => 1417, 'checked' => false, 'text' => 'Проверить черновое шпаклевание и стеклохолст (отсутствие отслоений)'],
                    ['id' => 1418, 'checked' => false, 'text' => 'Проверить финишное шпаклевание под прожектор (без волн, ям и неровностей)'],
                    ['id' => 1419, 'checked' => false, 'text' => 'Сделать пробные выкрасы, согласовать с дизайнером/заказчиком'],
                    ['id' => 1420, 'checked' => false, 'text' => 'Проверить качество покраски / углы, стыки красок'],
                    ['id' => 1421, 'checked' => false, 'text' => 'Проверить состояние окон, очистить загрязнения (растворитель Cosmofen)'],
                ],
                'comment' => ''
            ],
            
            15 => [
                'title' => 'Звукоизоляция стен бескаркасная',
                'photos' => [
                    [
                        'id' => 1500,
                        'checked' => false,
                        'image' => asset('images/Frameless_Wall_Soundproofing/1.png'),
                        'caption' => 'Оклеиваем лентой СтопЗвук V100 периметр стены с помощью виброгерметика Сонетик или клея Баутгер'
                    ],
                    [
                        'id' => 1501,
                        'checked' => false,
                        'image' => asset('images/Frameless_Wall_Soundproofing/2.png'),
                        'caption' => 'Срезаем часть панели Соноплат. Устанавливаем вертикально, слева направо. Наносим герметик на четверть панели'
                    ],
                    [
                        'id' => 1502,
                        'checked' => false,
                        'image' => asset('images/Frameless_Wall_Soundproofing/3.png'),
                        'caption' => 'Сверлим отверстие в стене через Соноплат. Дюбель располагаем пазом вниз. На одну панель используем 10-12 дюбелей.'
                    ],
                    [
                        'id' => 1503,
                        'checked' => false,
                        'image' => asset('images/Frameless_Wall_Soundproofing/4.png'),
                        'caption' => 'Швы между панелями Соноплат проклеиваем лентой'
                    ],
                    [
                        'id' => 1504,
                        'checked' => false,
                        'image' => asset('images/Frameless_Wall_Soundproofing/5.png'),
                        'caption' => 'Обшиваем слоем АкустикГипс ГКЛЗ. Листы по горизонтали стыкуем без зазора.'
                    ],
                    [
                        'id' => 1505,
                        'checked' => false,
                        'image' => asset('images/Frameless_Wall_Soundproofing/6.png'),
                        'caption' => 'Обрезать излишки звукоизоляционной ленты Обработать стык аккустическим герметиком'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 1506, 'checked' => false, 'text' => 'Проверить комплектность материалов для звукоизоляции стен по смете'],
                    ['id' => 1507, 'checked' => false, 'text' => 'Доставить материалы на объект и принять по накладной'],
                    ['id' => 1508, 'checked' => false, 'text' => 'Проверить монтаж звукоизоляционной ленты по периметру стены'],
                    ['id' => 1509, 'checked' => false, 'text' => 'Проверить монтаж звукоизоляционной панели по инструкции'],
                    ['id' => 1510, 'checked' => false, 'text' => 'Проверить надежное крепление панели к стене'],
                    ['id' => 1511, 'checked' => false, 'text' => 'Проверить проклейку швов между панелями (специальная лента)'],
                    ['id' => 1512, 'checked' => false, 'text' => 'Проверить, что излишки звукоизоляционной ленты по периметру - обрезаны'],
                    ['id' => 1513, 'checked' => false, 'text' => 'Проверить, что стык панелей и стены обработан акустическим герметиком'],
                    ['id' => 1514, 'checked' => false, 'text' => 'ЭТАП ПРИНИМАЕТСЯ ПОСЛЕ УБОРКИ ОБЪЕКТА'],
                ],
                'comment' => ''
            ],
            
            16 => [
                'title' => 'Звукоизоляция стен каркасная',
                'photos' => [
                    [
                        'id' => 1600,
                        'checked' => false,
                        'image' => asset('images/Frame_Wall_Soundproofing/1.png'),
                        'caption' => 'Оклеиваем лентой СтопЗвук V100 периметр стены с помощью виброгерметика Сонетик или клея Баутгер'
                    ],
                    [
                        'id' => 1601,
                        'checked' => false,
                        'image' => asset('images/Frame_Wall_Soundproofing/2.png'),
                        'caption' => 'Наклеиваем ТермоЗвукоИзол на клей Баутгер'
                    ],
                    [
                        'id' => 1602,
                        'checked' => false,
                        'image' => asset('images/Frame_Wall_Soundproofing/3.png'),
                        'caption' => 'Фиксируем профиль направляющий ПН через ленту СтопЗвук V100 с шагом 250-300 мм.'
                    ],
                    [
                        'id' => 1603,
                        'checked' => false,
                        'image' => asset('images/Frame_Wall_Soundproofing/4.png'),
                        'caption' => 'Крепим Виброподвесы Сонокреп с шагом 400 по горизонтали и 600 по вертикали.'
                    ],
                    [
                        'id' => 1604,
                        'checked' => false,
                        'image' => asset('images/Frame_Wall_Soundproofing/5.png'),
                        'caption' => 'Крепим профиль ПП к виброподвесам и связываем между собой крабом'
                    ],
                    [
                        'id' => 1605,
                        'checked' => false,
                        'image' => asset('images/Frame_Wall_Soundproofing/6.png'),
                        'caption' => 'Устанавливаем Подрозетник АкустикГипс Бокс в заранее подготовленный каркас из профиля.'
                    ],
                    [
                        'id' => 1606,
                        'checked' => false,
                        'image' => asset('images/Frame_Wall_Soundproofing/7.png'),
                        'caption' => 'Наклеиваем демпферную ленту на поверхность профиля, заполняем пространство плитами СтопЗвук БП.'
                    ],
                    [
                        'id' => 1607,
                        'checked' => false,
                        'image' => asset('images/Frame_Wall_Soundproofing/8.png'),
                        'caption' => 'Обшиваем листами АкустикГипс ГКЛЗ заполнить соединения листов виброакустическим герметиком'
                    ],
                    [
                        'id' => 1608,
                        'checked' => false,
                        'image' => asset('images/Frame_Wall_Soundproofing/9.png'),
                        'caption' => 'Обшиваем вторым слоем АкустикГипс ГКЛЗ.'
                    ],
                    [
                        'id' => 1609,
                        'checked' => false,
                        'image' => asset('images/Frame_Wall_Soundproofing/10.png'),
                        'caption' => 'Удаляем излишки ленты СтопЗвук V100 и герметизируем периметр герметиком'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 1610, 'checked' => false, 'text' => 'Проверить комплектность материалов для звукоизоляции стен по смете'],
                    ['id' => 1611, 'checked' => false, 'text' => 'Доставить материалы на объект и принять по накладной'],
                    ['id' => 1612, 'checked' => false, 'text' => 'Проверить монтаж звукоизоляционной ленты по периметру стены'],
                    ['id' => 1613, 'checked' => false, 'text' => 'Проверить монтаж термозвукоизола на клей'],
                    ['id' => 1614, 'checked' => false, 'text' => 'Проверить надежный монтаж направляющего профиля через вибро-шайбы'],
                    ['id' => 1615, 'checked' => false, 'text' => 'Проверить монтаж Виброподвесов с шагом 400 мм – по горизонтали и 600 мм по вертикали'],
                    ['id' => 1616, 'checked' => false, 'text' => 'Проверить надежность крепления ПП профиля (соединители и крабы, не болтаются, саморезы надежно закреплены)'],
                    ['id' => 1617, 'checked' => false, 'text' => 'Проверить монтаж ЗВУКОИЗОЛЯЦИОННОГО подрозетника в заранее подготовленный каркас из профиля'],
                    ['id' => 1618, 'checked' => false, 'text' => 'Проверить звукоизоляционную ленту (наклеена на профиль)'],
                    ['id' => 1619, 'checked' => false, 'text' => 'Проверить надежный монтаж листов ГКЛ первого слоя (шляпки саморезов утоплены 1-2 мм, стыки листов обработаны акустическим герметиком'],
                    ['id' => 1620, 'checked' => false, 'text' => 'Проверить разметку подразетников на стене (выпуски проводов и т.п.)'],
                    ['id' => 1621, 'checked' => false, 'text' => 'Проверить надежность монтажа второго стоя листов ГКЛ (саморезы утоплены в ГКЛ 1-2 мм)'],
                    ['id' => 1622, 'checked' => false, 'text' => 'Проверить подрозетники (отверстия высверлены, кабель выпущен)'],
                    ['id' => 1623, 'checked' => false, 'text' => 'Проверить что излишки ленты по периметру стены удалены и стык обработан акустическим герметиком'],
                    ['id' => 1624, 'checked' => false, 'text' => 'ЭТАП ПРИНИМАЕТСЯ ПОСЛЕ УБОРКИ ОБЪЕКТА'],
                ],
                'comment' => ''

            ],
            17 => [
                 'title' => 'Шпаклевание потолка',
                'photos' => [
                    [
                        'id' => 1700,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Plastering/1.png'),
                        'caption' => 'Обильное и качественное грунтование поверхности !!!'
                    ],
                    [
                        'id' => 1701,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Plastering/2.png'),
                        'caption' => 'Проверить проклейку лентой стыков, саморезов'
                    ],
                    [
                        'id' => 1702,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Plastering/3.png'),
                        'caption' => 'Проверить монтаж малярной сетки в слой черновой шпаклёвки'
                    ],
                    [
                        'id' => 1703,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Plastering/4.png'),
                        'caption' => 'Проверить качество чернового шпаклевания в 2 слоя, без просветов'
                    ],
                    [
                        'id' => 1704,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Plastering/5.png'),
                        'caption' => 'Проверить отсутствие дефектов на встроенных светильниках'
                    ],
                    [
                        'id' => 1705,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Plastering/6.png'),
                        'caption' => 'Проверить качество поклейки стеклохолста на потолок, отслоение'
                    ],
                    [
                        'id' => 1706,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Plastering/7.png'),
                        'caption' => 'Проверить качество финишного шпаклевания, 2+ слоя'
                    ],
                    [
                        'id' => 1707,
                        'checked' => false,
                        'image' => asset('images/Ceiling_Plastering/8.png'),
                        'caption' => 'Проверить качество шпаклевания потолочных карнизов и ниш'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 1710, 'checked' => false, 'text' => 'Проверить комплектность материалов для шпаклевания стен'],
                    ['id' => 1711, 'checked' => false, 'text' => 'Доставить материалы на объект и принять по накладной'],
                    ['id' => 1712, 'checked' => false, 'text' => 'Проверить черновое шпаклевание 2 слоя (отсутствие зазоров под правилом, равномерность шпаклевание, отсутствие ям и шишек, равномерность)'],
                    ['id' => 1713, 'checked' => false, 'text' => 'Проверить шпаклевание сложной формы (зона скрытых карнизов, ниши под подсветку, разные уровни ГКЛ'],
                    ['id' => 1714, 'checked' => false, 'text' => 'Проверить встроенные светильники (которые устанавливаются перед шпаклеванием), они не должны выступать за плоскость потолка'],
                    ['id' => 1715, 'checked' => false, 'text' => 'Проверить качественное грунтование перед наклейкой стехлохолста'],
                    ['id' => 1716, 'checked' => false, 'text' => 'В клей для стеклохолста рекомендуется добавить 15% клея ПВА'],
                    ['id' => 1717, 'checked' => false, 'text' => 'Клей обильно наносится на потолок, накладывается стеклохолст, разглаживается шпателем и поверх еще раз наносится клей, излишки клея убираются'],
                    ['id' => 1718, 'checked' => false, 'text' => 'СДЕЛАТЬ КОНТРОЛЬНУЮ ОКЛЕЙКУ СТЕКЛОХОЛСТОМ ПОТОЛКА ОДНОЙ КОМНАТЫ – ПРОВЕРИТЬ, ПОСЛЕ ОКЛЕИТЬ ОСТАВШИЕСЯ ПОТОЛКИ'],
                    ['id' => 1719, 'checked' => false, 'text' => 'Стыки стеклохолста на потолке с ГКЛ – БЕЗ ПОДРЕЗАНИЯ, клеится только в стык'],
                    ['id' => 1720, 'checked' => false, 'text' => 'Проверить стеклохолст (отслоения, стыки, пузыри и т.п.)'],
                    ['id' => 1721, 'checked' => false, 'text' => 'Проверить зону примыкания, трещины'],
                    ['id' => 1722, 'checked' => false, 'text' => 'Проверить финишное шпаклевание под прожектор (отсутствие волн и переходов, трещин, отслоений и пузырей, отсутствие кратеров и царапин)'],
                    ['id' => 1723, 'checked' => false, 'text' => 'Проверить отсутствие просвета стеклохолста'],
                    ['id' => 1724, 'checked' => false, 'text' => 'Проверить углы и зону примыкания потолка и стены (ровный угол)'],
                    ['id' => 1725, 'checked' => false, 'text' => 'Проверить зону скрытых ниш'],
                    ['id' => 1726, 'checked' => false, 'text' => 'ЭТАП ПРИНИМАЕТСЯ ПОСЛЕ УБОРКИ ОБЪЕКТА'],
                ],
                'comment' => ''

            ],
            18 => [
                'title' => 'Шпаклевание стен',
                'photos' => [
                    [
                        'id' => 1800,
                        'checked' => false,
                        'image' => asset('images/Wall_Plastering/1.png'),
                        'caption' => 'Обильное и качественное грунтование поверхности !!!'
                    ],
                    [
                        'id' => 1801,
                        'checked' => false,
                        'image' => asset('images/Wall_Plastering/2.png'),
                        'caption' => 'Клей для стеклохолста + 15% КЛЕЙ ПВА'
                    ],
                    [
                        'id' => 1802,
                        'checked' => false,
                        'image' => asset('images/Wall_Plastering/3.png'),
                        'caption' => 'Обильно нанести клей на поверхность'
                    ],
                    [
                        'id' => 1803,
                        'checked' => false,
                        'image' => asset('images/Wall_Plastering/4.png'),
                        'caption' => 'Стеклохолст наложен в стык на поверхность'
                    ],
                    [
                        'id' => 1804,
                        'checked' => false,
                        'image' => asset('images/Wall_Plastering/5.png'),
                        'caption' => 'Разглаженная поверхность стеклохолста (без пузырей)'
                    ],
                    [
                        'id' => 1805,
                        'checked' => false,
                        'image' => asset('images/Wall_Plastering/6.png'),
                        'caption' => 'Клей нанесен сверху стеклохолста валиком'
                    ],
                    [
                        'id' => 1806,
                        'checked' => false,
                        'image' => asset('images/Wall_Plastering/7.png'),
                        'caption' => 'Подрезать нахлест стеклохолста (Штукатурка)'
                    ],
                    [
                        'id' => 1807,
                        'checked' => false,
                        'image' => asset('images/Wall_Plastering/8.png'),
                        'caption' => 'При необходимости поклейки по ГКЛ КЛЕИТЬ ТОЛЬКО В СТЫК БЕЗ ПОДРЕЗАНИЯ'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 1810, 'checked' => false, 'text' => 'Проверить комплектность материалов для шпаклевания стен'],
                    ['id' => 1811, 'checked' => false, 'text' => 'Доставить материалы на объект и принять по накладной'],
                    ['id' => 1812, 'checked' => false, 'text' => 'Проверить черновое шпаклевание 2 слоя (отсутствие зазоров под правилом, равномерность шпаклевание, отсутствие ям и шишек, равномерность)'],
                    ['id' => 1813, 'checked' => false, 'text' => 'Проверить шпаклевание сложной формы (зона скрытых карнизов, ниши под подсветку, разные уровни ГКЛ'],
                    ['id' => 1814, 'checked' => false, 'text' => 'Проверить качественное грунтование перед наклейкой стехлохолста'],
                    ['id' => 1815, 'checked' => false, 'text' => 'В клей для стеклохолста рекомендуется добавить 15% клея ПВА'],
                    ['id' => 1816, 'checked' => false, 'text' => 'Клей обильно наносится на стену, накладывается стеклохолст, разглаживается шпателем и поверх еще раз наносится клей, излишки клея убирается'],
                    ['id' => 1817, 'checked' => false, 'text' => 'СДЕЛАТЬ КОНТРОЛЬНУЮ ОКЛЕЙКУ СТЕКЛОХОЛСТОМ ОДНОЙ СТЕНЫ – ПРОВЕРИТЬ, ПОСЛЕ ОКЛЕИТЬ ОСТАВШИЕСЯ СТЕНЫ'],
                    ['id' => 1818, 'checked' => false, 'text' => 'Стыки стеклохолста на стенах с ГКЛ – БЕЗ ПОДРЕЗАНИЯ, клеится только в стык'],
                    ['id' => 1819, 'checked' => false, 'text' => 'Проверить стеклохолст (отслоения, стыки, пузыри и т.п.)'],
                    ['id' => 1820, 'checked' => false, 'text' => 'Проверить зону откосов, примыкания, трещины'],
                    ['id' => 1821, 'checked' => false, 'text' => 'Проверить финишное шпаклевание под прожектор (отсутствие волн и переходов, трещин, отслоений и пузырей, отсутствие кратеров и царапин)'],
                    ['id' => 1822, 'checked' => false, 'text' => 'Проверить шпаклевание углов (наружных и внутренних) без волн, качественно ошкурено'],
                    ['id' => 1823, 'checked' => false, 'text' => 'Проверить откосы (примыкание к окнам, углы)'],
                    ['id' => 1824, 'checked' => false, 'text' => 'Проверить что все подрозетники вырезаны!!!'],
                    ['id' => 1825, 'checked' => false, 'text' => 'Проверить ЧИСТОТУ ПОДРОЗЕТНИКОВ'],
                    ['id' => 1826, 'checked' => false, 'text' => 'Проверить качество шпаклевания в зоне окон (косой свет из окна) – проверять днем!!!'],
                    ['id' => 1827, 'checked' => false, 'text' => 'ЭТАП ПРИНИМАЕТСЯ ПОСЛЕ УБОРКИ ОБЪЕКТА'],
                ],
                'comment' => ''
            ],
            
       19 => [
                'title' => 'Теплый пол',
                'photos' => [
                    [
                        'id' => 1900,
                        'checked' => false,
                        'image' => asset('images/Underfloor_Heating/1.png'),
                        'caption' => 'НАНЕСТИ ОБМАЗОЧНУЮ ГИДРОИЗОЛЯЦИЮ !!!'
                    ],
                    [
                        'id' => 1901,
                        'checked' => false,
                        'image' => asset('images/Underfloor_Heating/2.png'),
                        'caption' => 'Установить датчик ТП (гофра трубка д=20 мм)'
                    ],
                    [
                        'id' => 1902,
                        'checked' => false,
                        'image' => asset('images/Underfloor_Heating/3.png'),
                        'caption' => 'Подключить к терморегулятору ТП (Проверить работоспособность)'
                    ],
                    [
                        'id' => 1903,
                        'checked' => false,
                        'image' => asset('images/Underfloor_Heating/4.png'),
                        'caption' => 'Покрыть мат слоем плиточного клея (или наливным полом)'
                    ],
                    [
                        'id' => 1904,
                        'checked' => false,
                        'image' => asset('images/Underfloor_Heating/5.png'),
                        'caption' => 'Уложить напольную плитку'
                    ],
                    [
                        'id' => 1905,
                        'checked' => false,
                        'image' => asset('images/Underfloor_Heating/6.png'),
                        'caption' => 'Равномерность и целостность затирки по всей плоскости'
                    ],
                    [
                        'id' => 1906,
                        'checked' => false,
                        'image' => asset('images/Underfloor_Heating/7.png'),
                        'caption' => 'Определить место для расположения терморегулятора ТП'
                    ],
                    [
                        'id' => 1907,
                        'checked' => false,
                        'image' => asset('images/Underfloor_Heating/8.png'),
                        'caption' => 'Уложить кабельный мат'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 1910, 'checked' => false, 'text' => 'Доставить маты теплого пола и терморегуляторы, принять по накладной'],
                    ['id' => 1911, 'checked' => false, 'text' => 'Проверить укладку мата теплого пола в соответствии с проектом'],
                    ['id' => 1912, 'checked' => false, 'text' => 'ПРОВЕРИТЬ РАБОТОСПОСОБНОСТЬ ТЕПЛОГО ПОЛА (Подключить к терморегулятору и удостовериться в отсутствии короткого замыкания)'],
                    ['id' => 1913, 'checked' => false, 'text' => 'ПРОВЕРИТЬ ЗАКЛАДНУЮ ПОД ДАТЧИК ТЕПЛОГО ПОЛА (МИН 20 ММ)'],
                    ['id' => 1914, 'checked' => false, 'text' => 'ПРОВЕРИТЬ РАБОТОСПОСОБНОСТЬ ТЕПЛОГО ПОЛА (Подключить к терморегулятору и удостовериться в отсутствии короткого замыкания)'],
                    ['id' => 1915, 'checked' => false, 'text' => 'Вывезти мусор'],
                ],
                'comment' => ''
            ],
            
            20 => [
                'title' => 'Трап',
                'photos' => [
                    [
                        'id' => 2000,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/1.png'),
                        'caption' => 'Установить трап по уровню и высоте, сделать разметку подиума трапа'
                    ],
                    [
                        'id' => 2001,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/2.png'),
                        'caption' => 'Установить демпферную ленту по периметру стены'
                    ],
                    [
                        'id' => 2002,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/3.png'),
                        'caption' => 'Установить опалубку под подиум трапа'
                    ],
                    [
                        'id' => 2003,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/4.png'),
                        'caption' => 'Сделать заливку трапа на 90%, оставить место под разуклонку'
                    ],
                    [
                        'id' => 2004,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/5.png'),
                        'caption' => 'Уложить теплый пол (при необходимости)'
                    ],
                    [
                        'id' => 2005,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/6.png'),
                        'caption' => 'Выполнить демонтаж опалубки'
                    ],
                    [
                        'id' => 2006,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/7.png'),
                        'caption' => 'Установить направляющие маяки для разуклонки'
                    ],
                    [
                        'id' => 2007,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/8.png'),
                        'caption' => 'Проверить уровень уклона 1 см на 1 метр'
                    ],
                    [
                        'id' => 2008,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/9.png'),
                        'caption' => 'Проверить уровень плитки и трапа (плитка на 0.5 - 1 мм выше уровня трапа)'
                    ],
                    [
                        'id' => 2009,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/10.png'),
                        'caption' => 'Дозалить плоскости разуклонки по маякам'
                    ],
                    [
                        'id' => 2010,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/11.png'),
                        'caption' => 'Еще раз проверить уровень плитки и трапа( при необходимости зачистить неровности)'
                    ],
                    [
                        'id' => 2011,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/12.png'),
                        'caption' => 'Проверить качество среза (сделать пробный рез)'
                    ],
                    [
                        'id' => 2012,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/13.png'),
                        'caption' => 'Установить ленту гидроизоляционную и нанести обмазочную гидроизоляцию'
                    ],
                    [
                        'id' => 2013,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/14.png'),
                        'caption' => 'Уложить плитку на экран лотка трапа'
                    ],
                    [
                        'id' => 2014,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/15.png'),
                        'caption' => 'Выполнить укладку всего трапа, проверить разуклонку'
                    ],
                    [
                        'id' => 2015,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/16.png'),
                        'caption' => 'Проверить резку 45 угла, наличие сколов, равномерность швов'
                    ],
                    [
                        'id' => 2016,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/17.png'),
                        'caption' => 'Завершить укладку, выполнить затирку (двухкомпонентную - эпоксидную)'
                    ],
                    [
                        'id' => 2017,
                        'checked' => false,
                        'image' => asset('images/Floor_Drain/18.png'),
                        'caption' => 'Защитить углы 45 от сколов'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 2020, 'checked' => false, 'text' => 'Установить трап по уровню и высоте, сделать разметку подиума трапа'],
                    ['id' => 2021, 'checked' => false, 'text' => 'Установить демпферную ленту по периметру стены'],
                    ['id' => 2022, 'checked' => false, 'text' => 'Установить опалубку под подиум трапа'],
                    ['id' => 2023, 'checked' => false, 'text' => 'Сделать заливку трапа на 90%, оставить место под разуклонку'],
                    ['id' => 2024, 'checked' => false, 'text' => 'Уложить теплый пол (при необходимости)'],
                    ['id' => 2025, 'checked' => false, 'text' => 'Выполнить демонтаж опалубки'],
                    ['id' => 2026, 'checked' => false, 'text' => 'Установить направляющие маяки для разуклонки'],
                    ['id' => 2027, 'checked' => false, 'text' => 'Проверить уровень уклона 1 см на 1 метр'],
                    ['id' => 2028, 'checked' => false, 'text' => 'Проверить уровень плитки и трапа (плитка на 0.5 - 1 мм выше уровня трапа)'],
                    ['id' => 2029, 'checked' => false, 'text' => 'Дозалить плоскости разуклонки по маякам'],
                    ['id' => 2030, 'checked' => false, 'text' => 'Еще раз проверить уровень плитки и трапа (при необходимости зачистить неровности)'],
                    ['id' => 2031, 'checked' => false, 'text' => 'Проверить качество среза (сделать пробный рез)'],
                    ['id' => 2032, 'checked' => false, 'text' => 'Установить ленту гидроизоляционную и нанести обмазочную гидроизоляцию'],
                    ['id' => 2033, 'checked' => false, 'text' => 'Уложить плитку на экран лотка трапа'],
                    ['id' => 2034, 'checked' => false, 'text' => 'Выполнить укладку всего трапа, проверить разуклонку'],
                    ['id' => 2035, 'checked' => false, 'text' => 'Проверить резку 45 угла, наличие сколов, равномерность швов'],
                    ['id' => 2036, 'checked' => false, 'text' => 'Завершить укладку, выполнить затирку (двухкомпонентную - эпоксидную)'],
                    ['id' => 2037, 'checked' => false, 'text' => 'Защитить углы 45 от сколов'],
                ],
                'comment' => ''
            ],
            
            21 => [
                'title' => 'Напольная плитка',
                'photos' => [
                    [
                        'id' => 2100,
                        'checked' => false,
                        'image' => asset('images/Floor_Tiles/1.png'),
                        'caption' => 'НАНЕСТИ ОБМАЗОЧНУЮ ГИДРОИЗОЛЯЦИЮ !!!'
                    ],
                    [
                        'id' => 2101,
                        'checked' => false,
                        'image' => asset('images/Floor_Tiles/2.png'),
                        'caption' => 'Проверить рисунок/раскладку плитки (с дизайнером проекта, Заказчиком)'
                    ],
                    [
                        'id' => 2102,
                        'checked' => false,
                        'image' => asset('images/Floor_Tiles/3.png'),
                        'caption' => 'Проверить использование СВП + крестов'
                    ],
                    [
                        'id' => 2103,
                        'checked' => false,
                        'image' => asset('images/Floor_Tiles/4.png'),
                        'caption' => 'Проверить ровность шва (разбежку швов)'
                    ],
                    [
                        'id' => 2104,
                        'checked' => false,
                        'image' => asset('images/Floor_Tiles/5.png'),
                        'caption' => 'Проверка плоскости плитки (отсутствие выступающих граней)'
                    ],
                    [
                        'id' => 2105,
                        'checked' => false,
                        'image' => asset('images/Floor_Tiles/6.png'),
                        'caption' => 'Проверка горизонтали (по уровню)'
                    ],
                    [
                        'id' => 2106,
                        'checked' => false,
                        'image' => asset('images/Floor_Tiles/7.png'),
                        'caption' => 'Проверить примыкание в проемах (под дверным полотном – по проекту)'
                    ],
                    [
                        'id' => 2107,
                        'checked' => false,
                        'image' => asset('images/Floor_Tiles/8.png'),
                        'caption' => 'Проверить кромку реза плитки (отсутствие сколов)'
                    ],
                    [
                        'id' => 2108,
                        'checked' => false,
                        'image' => asset('images/Floor_Tiles/9.png'),
                        'caption' => 'Проверить затирку (равномерное нанесение)'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 2110, 'checked' => false, 'text' => 'Подготовить схему раскладки плитки для плиточника'],
                    ['id' => 2111, 'checked' => false, 'text' => 'Донести до плиточника раскладку, согласовать расположения швов под дверными проёмами'],
                    ['id' => 2112, 'checked' => false, 'text' => 'НАНЕСТИ ОБМАЗОЧНУЮ ГИДРОИЗОЛЯЦИЮ !!! В ЗОНЕ ДУШЕВОЙ ДО ПОТОЛКА!'],
                    ['id' => 2113, 'checked' => false, 'text' => 'ПОЛНОСТЬЮ РАЗЛОЖИТЬ ПЛИТКУ НА СУХУЮ НА ПОЛ В СООТВЕТСТВИИ С РАСКЛАДКой – УТВЕРДИТЬ РАСКЛАДКУ С ДИЗАЙНЕРОМ!!!'],
                    ['id' => 2114, 'checked' => false, 'text' => 'Проверить рисунок, разложить предварительно на полу (если рисунок состоит из нескольких плиток)'],
                    ['id' => 2115, 'checked' => false, 'text' => 'Проверить первый рез плитки, отсутствие сколов (при их наличии заменить диск или плиткорез)'],
                    ['id' => 2116, 'checked' => false, 'text' => 'Проверить использование СВП при укладке'],
                    ['id' => 2117, 'checked' => false, 'text' => 'Проверить равномерность шва, отсутствие смещения швов и швов с разной шириной'],
                    ['id' => 2118, 'checked' => false, 'text' => 'Проверить плоскость плитки правилом, отсутствие выступов граней плитки'],
                    ['id' => 2119, 'checked' => false, 'text' => 'Проверить горизонтальную плоскость по уровню'],
                    ['id' => 2120, 'checked' => false, 'text' => 'Проверить стык под дверными проёмами (стыки двух напольных покрытий)'],
                    ['id' => 2121, 'checked' => false, 'text' => 'Проверить чистоту поверхности плитки, МАСТЕР ПРОТИРАЕТ ПЛИТКУ, УБИРАЕТ ЗА СОБОЙ МУСОР'],
                    ['id' => 2122, 'checked' => false, 'text' => 'Проверить качество затирки (равномерность затирки, отсутствие пустот и вмятин)'],
                    ['id' => 2123, 'checked' => false, 'text' => 'Укрыть поверхность плитки (оргалит, плотная бумага, укрывной материал)'],
                    ['id' => 2124, 'checked' => false, 'text' => 'Вывезти мусор и обрезки плитки'],
                ],
                'comment' => ''
            ],
            
            22 => [
                'title' => 'Настенная плитка',
                'photos' => [
                    [
                        'id' => 2200,
                        'checked' => false,
                        'image' => asset('images/Wall_Tiles/1.png'),
                        'caption' => 'Проверить плоскости по уровню (вертикаль, горизонталь)'
                    ],
                    [
                        'id' => 2201,
                        'checked' => false,
                        'image' => asset('images/Wall_Tiles/2.png'),
                        'caption' => 'Проверка единого шва и горизонтали по уровню'
                    ],
                    [
                        'id' => 2202,
                        'checked' => false,
                        'image' => asset('images/Wall_Tiles/3.png'),
                        'caption' => 'Монтаж среднего и крупного формата – строго с СВП'
                    ],
                    [
                        'id' => 2203,
                        'checked' => false,
                        'image' => asset('images/Wall_Tiles/4.png'),
                        'caption' => 'Проверка подрезки углов под 45 градусов'
                    ],
                    [
                        'id' => 2204,
                        'checked' => false,
                        'image' => asset('images/Wall_Tiles/5.png'),
                        'caption' => 'Прислать фото срезанной кромки (контрольный ПЕРВЫЙ рез!!!)'
                    ],
                    [
                        'id' => 2205,
                        'checked' => false,
                        'image' => asset('images/Wall_Tiles/6.png'),
                        'caption' => 'Проверка швов (без смещения)'
                    ],
                    [
                        'id' => 2206,
                        'checked' => false,
                        'image' => asset('images/Wall_Tiles/7.png'),
                        'caption' => 'Проверить подбор рисунка в соответствии с Дизайн проектом'
                    ],
                    [
                        'id' => 2207,
                        'checked' => false,
                        'image' => asset('images/Wall_Tiles/8.png'),
                        'caption' => 'Проверить равномерность и целостность затирки'
                    ],
                    [
                        'id' => 2208,
                        'checked' => false,
                        'image' => asset('images/Wall_Tiles/9.png'),
                        'caption' => 'Защитить углы 45 от сколов, закрепить уголки'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 2210, 'checked' => false, 'text' => 'Подготовить схему раскладки плитки для плиточника, закрепить схемы раскладок в санузлах (если их несколько)'],
                    ['id' => 2211, 'checked' => false, 'text' => 'Донести до плиточника раскладку, согласовать и определить где какой тип плитки'],
                    ['id' => 2212, 'checked' => false, 'text' => 'Проверить, что правильно установлена ванна (при наличии)'],
                    ['id' => 2213, 'checked' => false, 'text' => 'Проверить рисунок, разложить предварительно на полу (если рисунок состоит из нескольких плиток)'],
                    ['id' => 2214, 'checked' => false, 'text' => 'Проверить первый рез плитки, отсутствие сколов (при их наличии заменить диск или плиткорез)'],
                    ['id' => 2215, 'checked' => false, 'text' => 'Проверить использование СВП при укладке'],
                    ['id' => 2216, 'checked' => false, 'text' => 'Проверить вертикаль первых рядов плитки пузырьковым уровнем'],
                    ['id' => 2217, 'checked' => false, 'text' => 'Проверить подрезку углов 45 градусов (сколы, зазоры, отшлифованные грани)'],
                    ['id' => 2218, 'checked' => false, 'text' => 'Проверить монтаж скрытых люков (открытие/закрытие, зазоры вокруг дверцы, отсутствие грязи и клея внутри люка)'],
                    ['id' => 2219, 'checked' => false, 'text' => 'Проверить плоскость плитки правилом, отсутствие выступов граней плитки'],
                    ['id' => 2220, 'checked' => false, 'text' => 'Проверить равномерность шва, отсутствие смещения швов и швов с разной шириной'],
                    ['id' => 2221, 'checked' => false, 'text' => 'Проверить горизонтальную плоскость по уровню, горизонтальность швов, отсутствие завалов швов'],
                    ['id' => 2222, 'checked' => false, 'text' => 'Проверить чистоту поверхности плитки, МАСТЕР ПРОТИРАЕТ ПЛИТКУ, УБИРАЕТ ЗА СОБОЙ МУСОР'],
                    ['id' => 2223, 'checked' => false, 'text' => 'Проверить качество затирки (равномерность затирки, отсутствие пустот и вмятин)'],
                    ['id' => 2224, 'checked' => false, 'text' => 'Вывезти мусор и обрезки плитки'],
                    ['id' => 2225, 'checked' => false, 'text' => 'ВЫПОЛНИТЬ МОНТАЖ КОРОБА ВЕНТИЛЯЦИИ !!!'],
                ],
                'comment' => ''
            ],
            
            23 => [
                'title' => 'Напольное покрытие',
                'photos' => [
                    [
                        'id' => 2300,
                        'checked' => false,
                        'image' => asset('images/Floor_Covering/1.png'),
                        'caption' => 'Проверить выполнение распила только торцовкой (ОБЯЗАТЕЛЬНО)'
                    ],
                    [
                        'id' => 2301,
                        'checked' => false,
                        'image' => asset('images/Floor_Covering/2.png'),
                        'caption' => 'Проверить ровность поверхности стяжки 3-х метровым правилом'
                    ],
                    [
                        'id' => 2302,
                        'checked' => false,
                        'image' => asset('images/Floor_Covering/3.png'),
                        'caption' => 'Проверить формирование компенсационных швов по проекту'
                    ],
                    [
                        'id' => 2303,
                        'checked' => false,
                        'image' => asset('images/Floor_Covering/4.png'),
                        'caption' => 'Проверить зазор между стенами и напольным покрытием 5 - 8 мм'
                    ],
                    [
                        'id' => 2304,
                        'checked' => false,
                        'image' => asset('images/Floor_Covering/5.png'),
                        'caption' => 'Проверить отсутствие зазоров в замковых соединениях покрытия'
                    ],
                    [
                        'id' => 2305,
                        'checked' => false,
                        'image' => asset('images/Floor_Covering/6.png'),
                        'caption' => 'Проверить отсутствие сколов на торцах срезанных досок'
                    ],
                    [
                        'id' => 2306,
                        'checked' => false,
                        'image' => asset('images/Floor_Covering/7.png'),
                        'caption' => 'Проверить отсутствие подвижности покрытия (вздутия, люфт)'
                    ],
                    [
                        'id' => 2307,
                        'checked' => false,
                        'image' => asset('images/Floor_Covering/8.png'),
                        'caption' => 'Проверить стыки узлов примыкания доски с другими поверхностями'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 2310, 'checked' => false, 'text' => 'Проверить наличие материала и комплектующих на объекте'],
                    ['id' => 2311, 'checked' => false, 'text' => 'Проверить наличие ТОРЦОВКИ и вспомогательного инструмента у мастер'],
                    ['id' => 2312, 'checked' => false, 'text' => 'Ознакомить мастера с раскладкой напольного покрытия, ознакомить с качеством, ПРЕДУПРЕДИТЬ ЧТОБЫ НЕ ПОВРЕДИЛ СТЕНЫ'],
                    ['id' => 2313, 'checked' => false, 'text' => 'Проверить наличие стыковочных профилей (для стыка двух покрытий)'],
                    ['id' => 2314, 'checked' => false, 'text' => 'ПРОВЕРИТЬ ПЕРВЫЙ РЕЗ НА ТОРЦОВКЕ (Отсутствие сколов)'],
                    ['id' => 2315, 'checked' => false, 'text' => 'Проверить при укладке – компенсационный зазор между стеной и покрытием 6-10 мм'],
                    ['id' => 2316, 'checked' => false, 'text' => 'Проверить отсутствие зазора в замковых соединениях покрытия'],
                    ['id' => 2317, 'checked' => false, 'text' => 'Проверить зазор (Примыкание конвектора отопления и доски)'],
                    ['id' => 2318, 'checked' => false, 'text' => 'Проверить уровень напольного покрытия и конвектора отопления (в один уровень, БЕЗ ПЕРЕПАДОВ)'],
                    ['id' => 2319, 'checked' => false, 'text' => 'Проверить отсутствие подвижности покрытия (вздутия, люфт, скрипы)'],
                    ['id' => 2320, 'checked' => false, 'text' => 'Проверить стык двух покрытий (надежность соединения в профиле или качественное заполнение герметиком или пробковый компенсатор)'],
                    ['id' => 2321, 'checked' => false, 'text' => 'Проверить отсутствие повреждения стен (после полной укладки покрытия)'],
                    ['id' => 2322, 'checked' => false, 'text' => 'ЭТАП ПРИНИМАЕТСЯ ПОСЛЕ УБОРКИ ОБЪЕКТА (пылесос)'],
                    ['id' => 2323, 'checked' => false, 'text' => 'После уборки необходимо укрыть напольное покрытие, защитить от повреждения (расстелить Ватин, укрыть оргалитом, заклеить стыки арм скотчем)'],
                    ['id' => 2324, 'checked' => false, 'text' => 'Поверхность пола укрыта'],
                ],
                'comment' => ''
            ],
            
            24 => [
                'title' => 'Укрытие напольного покрытия',
                'photos' => [
                    [
                        'id' => 2400,
                        'checked' => false,
                        'image' => asset('images/Floor_Covering_Protection/1.png'),
                        'caption' => 'Укрыть подложкой или Ватином (заклеить стыки скотчем)'
                    ],
                    [
                        'id' => 2401,
                        'checked' => false,
                        'image' => asset('images/Floor_Covering_Protection/2.png'),
                        'caption' => 'Укрыть оргалитом (заклеить стыки армированным скотчем)'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 2410, 'checked' => false, 'text' => 'Укрыть подложкой или Ватином (заклеить стыки скотчем)'],
                    ['id' => 2411, 'checked' => false, 'text' => 'Укрыть оргалитом (заклеить стыки армированным скотчем)'],
                ],
                'comment' => ''
            ],
            
            25 => [
                'title' => 'Покраска',
                'photos' => [
                    [
                        'id' => 2500,
                        'checked' => false,
                        'image' => asset('images/Painting/1.png'),
                        'caption' => 'Проверка равномерной покраски'
                    ],
                    [
                        'id' => 2501,
                        'checked' => false,
                        'image' => asset('images/Painting/2.png'),
                        'caption' => 'Проверка по освещенности (по направленному свету)'
                    ],
                    [
                        'id' => 2502,
                        'checked' => false,
                        'image' => asset('images/Painting/3.png'),
                        'caption' => 'Проверка примыкающих кромок'
                    ],
                    [
                        'id' => 2503,
                        'checked' => false,
                        'image' => asset('images/Painting/4.png'),
                        'caption' => 'Проверка углов'
                    ],
                    [
                        'id' => 2504,
                        'checked' => false,
                        'image' => asset('images/Painting/5.png'),
                        'caption' => 'Проверка «вспененной краски»'
                    ],
                    [
                        'id' => 2505,
                        'checked' => false,
                        'image' => asset('images/Painting/6.png'),
                        'caption' => 'Проверка «кратеров»'
                    ],
                    [
                        'id' => 2506,
                        'checked' => false,
                        'image' => asset('images/Painting/7.png'),
                        'caption' => 'Проверка «непрокрасов» Пятен, царапин, трещин'
                    ],
                    [
                        'id' => 2507,
                        'checked' => false,
                        'image' => asset('images/Painting/8.png'),
                        'caption' => 'Фотоотчет проверки'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 2510, 'checked' => false, 'text' => 'Проверить наличие Краски и расходных материалов на объекте'],
                    ['id' => 2511, 'checked' => false, 'text' => 'Сделать контрольные выкрасы на стенах - СОГЛАСОВАТЬ С ДИЗАЙНЕРОМ / ЗАКАЗЧИКОМ'],
                    ['id' => 2512, 'checked' => false, 'text' => 'Покрасить одну стену – ПРОВЕРИТЬ КАЧЕСТВО ПОКРАСКИ'],
                    ['id' => 2513, 'checked' => false, 'text' => 'Проверить равномерность покраски по всей поверхности'],
                    ['id' => 2514, 'checked' => false, 'text' => 'Проверить отсутствие расплывов при боковом освещении'],
                    ['id' => 2515, 'checked' => false, 'text' => 'Проверить отсутствие «вспененной краски»'],
                    ['id' => 2516, 'checked' => false, 'text' => 'Проверить отсутствие кратеров'],
                    ['id' => 2517, 'checked' => false, 'text' => 'Проверить отсутствие непрокрасов'],
                    ['id' => 2518, 'checked' => false, 'text' => 'Проверить отсутствие пятен'],
                    ['id' => 2519, 'checked' => false, 'text' => 'Проверить отсутствие трещин и царапин'],
                    ['id' => 2520, 'checked' => false, 'text' => 'Проверить примыкание к молдингам, багетам, карнизам'],
                    ['id' => 2521, 'checked' => false, 'text' => 'Проверить примыкание двух красок'],
                    ['id' => 2522, 'checked' => false, 'text' => 'Проверить покраску углов (внутренних и внешних)'],
                    ['id' => 2523, 'checked' => false, 'text' => 'Проверить покраску всех стен помещения'],
                    ['id' => 2524, 'checked' => false, 'text' => 'Проверить покраску откосов и их примыкания'],
                    ['id' => 2525, 'checked' => false, 'text' => 'Контрольная проверка под прожектор'],
                    ['id' => 2526, 'checked' => false, 'text' => 'ЭТАП ПРИНИМАЕТСЯ ПОСЛЕ УБОРКИ ОБЪЕКТА'],
                ],
                'comment' => ''
            ],
            
            26 => [
                'title' => 'Обои',
                'photos' => [
                    [
                        'id' => 2600,
                        'checked' => false,
                        'image' => asset('images/Wallpaper/1.png'),
                        'caption' => 'Проверить готовность стен для поклейки (качественное шпаклевание под обои)'
                    ],
                    [
                        'id' => 2601,
                        'checked' => false,
                        'image' => asset('images/Wallpaper/2.png'),
                        'caption' => 'Проверить что все стены качественно прогрунтованы'
                    ],
                    [
                        'id' => 2602,
                        'checked' => false,
                        'image' => asset('images/Wallpaper/3.png'),
                        'caption' => 'Проверить качество стыков'
                    ],
                    [
                        'id' => 2603,
                        'checked' => false,
                        'image' => asset('images/Wallpaper/4.png'),
                        'caption' => 'Проверить отсутствие пузырей и замятостей на поверхности'
                    ],
                    [
                        'id' => 2604,
                        'checked' => false,
                        'image' => asset('images/Wallpaper/5.png'),
                        'caption' => 'Проверить вырезы под розетки, выключатели и выводы'
                    ],
                    [
                        'id' => 2605,
                        'checked' => false,
                        'image' => asset('images/Wallpaper/6.png'),
                        'caption' => 'Проверить качество проклейки углов(внутренних и наружних)'
                    ],
                    [
                        'id' => 2606,
                        'checked' => false,
                        'image' => asset('images/Wallpaper/7.png'),
                        'caption' => 'Проверить отсутствие отслоений на стыках и примыканиях'
                    ],
                    [
                        'id' => 2607,
                        'checked' => false,
                        'image' => asset('images/Wallpaper/8.png'),
                        'caption' => 'Проверить оклеивание за радиаторами и под подоконником'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 2610, 'checked' => false, 'text' => 'Проверить наличие Обоев на объекте'],
                    ['id' => 2611, 'checked' => false, 'text' => 'Проверить наличие расходных материалов для поклейка (клей, валики и др.)'],
                    ['id' => 2612, 'checked' => false, 'text' => 'Проверить качество оклейки первой стены!!!'],
                    ['id' => 2613, 'checked' => false, 'text' => 'ЗАКРЫТЬ ОКНА!'],
                    ['id' => 2614, 'checked' => false, 'text' => 'Проверить отсутствие всех вышеперечисленных дефектов'],
                    ['id' => 2615, 'checked' => false, 'text' => 'Проверить отсутствие ПЯТЕН и СЛЕДОВ КЛЕЯ на поверхности обоев'],
                    ['id' => 2616, 'checked' => false, 'text' => 'ЭТАП ПРИНИМАЕТСЯ ПОСЛЕ УБОРКИ ОБЪЕКТА'],
                ],
                'comment' => ''
            ],
            
            27 => [
                'title' => 'Скрытый плинтус (при наличии)',
                'photos' => [
                    [
                        'id' => 2700,
                        'checked' => false,
                        'image' => asset('images/Hidden_Baseboard/1.png'),
                        'caption' => 'Отметить лазером на стене НИЖНИЙ край скрытого плинтуса равный уровню ЧИСТОВОГО ПОЛА'
                    ],
                    [
                        'id' => 2701,
                        'checked' => false,
                        'image' => asset('images/Hidden_Baseboard/2.png'),
                        'caption' => 'Закрепить деревянный брус или фанеру вместо алюминиевого профиля плинтуса'
                    ],
                    [
                        'id' => 2702,
                        'checked' => false,
                        'image' => asset('images/Hidden_Baseboard/3.png'),
                        'caption' => 'Установить маяки и Оштукатурить стены'
                    ],
                    [
                        'id' => 2703,
                        'checked' => false,
                        'image' => asset('images/Hidden_Baseboard/4.png'),
                        'caption' => 'Удалить брус после оштукатуривания стен'
                    ],
                    [
                        'id' => 2704,
                        'checked' => false,
                        'image' => asset('images/Hidden_Baseboard/5.png'),
                        'caption' => 'Проложить инженерные трассы (трубы, кабель) с учётом паза скрытого плинтуса!!!'
                    ],
                    [
                        'id' => 2705,
                        'checked' => false,
                        'image' => asset('images/Hidden_Baseboard/6.png'),
                        'caption' => 'Установить алюминиевый профиль скрытого плинтуса с максимальной точностью!!!'
                    ],
                    [
                        'id' => 2706,
                        'checked' => false,
                        'image' => asset('images/Hidden_Baseboard/7.png'),
                        'caption' => 'Выполнить шпаклевание стен, подготовить стены под отделку, выполнить отделку стен'
                    ],
                    [
                        'id' => 2707,
                        'checked' => false,
                        'image' => asset('images/Hidden_Baseboard/8.png'),
                        'caption' => 'Уложить напольное покрытие, контролируя зазор под декоративную вставку плинтуса'
                    ],
                    [
                        'id' => 2708,
                        'checked' => false,
                        'image' => asset('images/Hidden_Baseboard/9.png'),
                        'caption' => 'Установить декоративную накладку скрытого плинтуса'
                    ],
                ],
                'checkboxes' => [
                    ['id' => 2710, 'checked' => false, 'text' => 'Отметить лазером на стене НИЖНИЙ край скрытого плинтуса равный уровню ЧИСТОВОГО ПОЛА'],
                    ['id' => 2711, 'checked' => false, 'text' => 'Закрепить деревянный брус или фанеру вместо алюминиевого профиля плинтуса'],
                    ['id' => 2712, 'checked' => false, 'text' => 'Установить маяки и Оштукатурить стены'],
                    ['id' => 2713, 'checked' => false, 'text' => 'Удалить брус после оштукатуривания стен'],
                    ['id' => 2714, 'checked' => false, 'text' => 'Проложить инженерные трассы (трубы, кабель) с учётом паза скрытого плинтуса!!!'],
                    ['id' => 2715, 'checked' => false, 'text' => 'Установить алюминиевый профиль скрытого плинтуса с максимальной точностью!!!'],
                    ['id' => 2716, 'checked' => false, 'text' => 'Выполнить шпаклевание стен, подготовить стены под отделку, выполнить отделку стен'],
                    ['id' => 2717, 'checked' => false, 'text' => 'Уложить напольное покрытие, контролируя зазор под декоративную вставку плинтуса'],
                    ['id' => 2718, 'checked' => false, 'text' => 'Установить декоративную накладку скрытого плинтуса'],
                ],
                'comment' => ''
            ],
        ];
    }

    /**
     * Получение деталей проверки по ID
     */
    private function getCheckDetails($check_id)
    {
        $checks = $this->getChecksList();
        
        // Проверяем существование ключа напрямую, так как ключи массива и есть ID проверок
        if (isset($checks[$check_id])) {
            // Добавляем ID в данные для унификации работы с ними
            $checks[$check_id]['id'] = $check_id;
            return $checks[$check_id];
        }
        
        return null;
    }
}
