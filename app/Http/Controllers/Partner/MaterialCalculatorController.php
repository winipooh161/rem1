<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class MaterialCalculatorController extends Controller
{
    /**
     * Отображает страницу калькулятора материалов
     */
    public function index()
    {
        $materials = $this->getMaterialsData();
        return view('partner.calculator.index', compact('materials'));
    }

    /**
     * Вычисляет количество материалов
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'calculations' => 'required|array',
            'calculations.*.material_id' => 'required|integer',
            'calculations.*.volume' => 'required|numeric|min:0',
            'calculations.*.layer' => 'nullable|numeric|min:0', // Изменено на nullable
        ]);

        $materials = $this->getMaterialsData();
        $results = [];

        foreach ($request->calculations as $calc) {
            $materialId = $calc['material_id'];
            $volume = floatval($calc['volume']);
            $layer = isset($calc['layer']) ? floatval($calc['layer']) : 1; // Значение по умолчанию

            if (isset($materials[$materialId])) {
                $material = $materials[$materialId];
                
                // Для материалов, где слой не нужен, устанавливаем layer = 1
                if (in_array($material['calculation_type'], ['area', 'linear'])) {
                    $layer = 1;
                }
                
                // Расчет расхода
                $consumption = $this->calculateConsumption($material, $volume, $layer);
                
                // Расчет упаковок с запасом 10%
                $packagesWithReserve = $this->calculatePackages($consumption, $material['package_size']);

                $results[] = [
                    'material' => $material,
                    'volume' => $volume,
                    'layer' => $layer,
                    'consumption' => $consumption,
                    'packages' => $packagesWithReserve,
                    'unit' => $material['unit']
                ];
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'results' => $results
            ]);
        }

        return view('partner.calculator.results', compact('results'));
    }

    /**
     * Возвращает данные о материалах
     */
    private function getMaterialsData()
    {
        return [
            4 => [
                'id' => 4,
                'name' => 'Штукатурка гипсовая Ротбанд 30 кг',
                'consumption_per_unit' => 1.8, // кг на м² при слое 1мм
                'package_size' => 30, // кг в упаковке
                'unit' => 'кг',
                'package_unit' => 'мешков',
                'calculation_type' => 'area_layer'
            ],
            5 => [
                'id' => 5,
                'name' => 'Маяк',
                'consumption_per_unit' => 0.3, // шт на м.п.
                'package_size' => 1, // шт в упаковке
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'linear'
            ],
            6 => [
                'id' => 6,
                'name' => 'Шпатлевка Ветонит ЛР 25 кг',
                'consumption_per_unit' => 2.2, // кг на м²
                'package_size' => 25, // кг в упаковке
                'unit' => 'кг',
                'package_unit' => 'мешков',
                'calculation_type' => 'area'
            ],
            7 => [
                'id' => 7,
                'name' => 'Шпатлевка Ротбанд Паста 18 кг',
                'consumption_per_unit' => 0.8, // кг на м²
                'package_size' => 18, // кг в упаковке
                'unit' => 'кг',
                'package_unit' => 'ведер',
                'calculation_type' => 'area'
            ],
            8 => [
                'id' => 8,
                'name' => 'Стеклохолст Oscar 50 г/м2, 50 м2',
                'consumption_per_unit' => 1, // м² на м²
                'package_size' => 50, // м² в рулоне
                'unit' => 'м²',
                'package_unit' => 'рулонов',
                'calculation_type' => 'area'
            ],
            9 => [
                'id' => 9,
                'name' => 'Клей Oscar для стеклохолста 10 кг',
                'consumption_per_unit' => 0.3, // кг на м²
                'package_size' => 10, // кг в упаковке
                'unit' => 'кг',
                'package_unit' => 'ведер',
                'calculation_type' => 'area'
            ],
            10 => [
                'id' => 10,
                'name' => 'Пескобетон м-300 40 кг',
                'consumption_per_unit' => 2, // кг на м² при слое 1мм
                'package_size' => 40, // кг в мешке
                'unit' => 'кг',
                'package_unit' => 'мешков',
                'calculation_type' => 'area_layer'
            ],
            11 => [
                'id' => 11,
                'name' => 'Керамзит фракции до 10мм, мешок',
                'consumption_per_unit' => 0.01, // м³ на м² при слое 1мм
                'package_size' => 1, // мешок
                'unit' => 'мешков',
                'package_unit' => 'мешков',
                'calculation_type' => 'area_layer'
            ],
            12 => [
                'id' => 12,
                'name' => 'Наливной пол (мешок 20 кг) - слой указать в миллиметрах',
                'consumption_per_unit' => 1.5, // кг на м² при слое 1мм
                'package_size' => 20, // кг в мешке
                'unit' => 'кг',
                'package_unit' => 'мешков',
                'calculation_type' => 'area_layer'
            ],
            13 => [
                'id' => 13,
                'name' => 'Гипсокартон Knauf 2500х1200 мм влагостойкий',
                'consumption_per_unit' => 1, // лист на 3 м²
                'package_size' => 1, // лист
                'unit' => 'листов',
                'package_unit' => 'листов',
                'calculation_type' => 'area_layer',
                'coverage_per_package' => 3 // м² покрывает один лист
            ],
            14 => [
                'id' => 14,
                'name' => 'Монтаж лобика из ГКЛВ / Откосы',
                'consumption_per_unit' => 0.24, // листов на м.п.
                'package_size' => 1, // лист
                'unit' => 'листов',
                'package_unit' => 'листов',
                'calculation_type' => 'linear_layer'
            ],
            15 => [
                'id' => 15,
                'name' => 'Профиль поперечный 27х60',
                'consumption_per_unit' => 1.3, // шт на м²
                'package_size' => 1, // шт
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'area'
            ],
            16 => [
                'id' => 16,
                'name' => 'Профиль кнауф 27*28',
                'consumption_per_unit' => 0.8, // шт на м²
                'package_size' => 1, // шт
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'area'
            ],
            17 => [
                'id' => 17,
                'name' => 'Подвес прямой усиленный Кнауф',
                'consumption_per_unit' => 4, // шт на м²
                'package_size' => 1, // шт
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'area'
            ],
            18 => [
                'id' => 18,
                'name' => 'Краб соединительный кнауф',
                'consumption_per_unit' => 2, // шт на м²
                'package_size' => 1, // шт
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'area'
            ],
            19 => [
                'id' => 19,
                'name' => 'Саморезы для ГКЛ Knauf 16х2,5',
                'consumption_per_unit' => 0.08, // кг на м²
                'package_size' => 1, // кг в упаковке
                'unit' => 'кг',
                'package_unit' => 'кг',
                'calculation_type' => 'area'
            ],
            20 => [
                'id' => 20,
                'name' => 'Саморезы для ГКЛ Knauf 19х3,5',
                'consumption_per_unit' => 0.08, // кг на м²
                'package_size' => 1, // кг в упаковке
                'unit' => 'кг',
                'package_unit' => 'кг',
                'calculation_type' => 'area'
            ],
            21 => [
                'id' => 21,
                'name' => 'Саморезы для ГКЛ Knauf 25х3,5',
                'consumption_per_unit' => 0.08, // кг на м²
                'package_size' => 1, // кг в упаковке
                'unit' => 'кг',
                'package_unit' => 'кг',
                'calculation_type' => 'area'
            ],
            22 => [
                'id' => 22,
                'name' => 'Пенополистерол',
                'consumption_per_unit' => 1.4, // листов на м²
                'package_size' => 6, // листов в упаковке
                'unit' => 'листов',
                'package_unit' => 'упаковок',
                'calculation_type' => 'area'
            ],
            23 => [
                'id' => 23,
                'name' => 'Шумоизоляция Соноплат / ГКЛ 600х1200',
                'consumption_per_unit' => 1, // м² на м²
                'package_size' => 0.72, // м² в листе
                'unit' => 'листов',
                'package_unit' => 'листов',
                'calculation_type' => 'area'
            ],
            24 => [
                'id' => 24,
                'name' => 'Клей для плитки',
                'consumption_per_unit' => 0.2, // мешков на м²
                'package_size' => 1, // мешок
                'unit' => 'мешков',
                'package_unit' => 'мешков',
                'calculation_type' => 'area'
            ],
            25 => [
                'id' => 25,
                'name' => 'Саморезы клопы 13х4,2 мм',
                'consumption_per_unit' => 0.04, // кг на м²
                'package_size' => 1, // кг в упаковке
                'unit' => 'кг',
                'package_unit' => 'кг',
                'calculation_type' => 'area'
            ],
            26 => [
                'id' => 26,
                'name' => 'Газоблок 600х250х100 d500',
                'consumption_per_unit' => 6.7, // шт на м²
                'package_size' => 1, // шт
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'area'
            ],
            27 => [
                'id' => 27,
                'name' => 'Клей для газоблоков',
                'consumption_per_unit' => 0.3, // мешков на м²
                'package_size' => 1, // мешок
                'unit' => 'мешков',
                'package_unit' => 'шт',
                'calculation_type' => 'area'
            ],
            
            // Новые 50 позиций материалов
            28 => [
                'id' => 28,
                'name' => 'Грунтовка глубокого проникновения 10л',
                'consumption_per_unit' => 0.15, // л на м²
                'package_size' => 10,
                'unit' => 'л',
                'package_unit' => 'канистр',
                'calculation_type' => 'area'
            ],
            29 => [
                'id' => 29,
                'name' => 'Краска водоэмульсионная 15кг',
                'consumption_per_unit' => 0.18, // кг на м²
                'package_size' => 15,
                'unit' => 'кг',
                'package_unit' => 'ведер',
                'calculation_type' => 'area'
            ],
            30 => [
                'id' => 30,
                'name' => 'Плитка керамическая 30х30см',
                'consumption_per_unit' => 11.5, // шт на м²
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'area'
            ],
            31 => [
                'id' => 31,
                'name' => 'Ламинат 32 класс упаковка',
                'consumption_per_unit' => 1.1, // уп на м²
                'package_size' => 1,
                'unit' => 'упаковок',
                'package_unit' => 'упаковок',
                'calculation_type' => 'area'
            ],
            32 => [
                'id' => 32,
                'name' => 'Линолеум коммерческий 2м',
                'consumption_per_unit' => 0.6, // м.п. на м²
                'package_size' => 1,
                'unit' => 'м.п.',
                'package_unit' => 'м.п.',
                'calculation_type' => 'area'
            ],
            33 => [
                'id' => 33,
                'name' => 'Обои флизелиновые рулон 10м',
                'consumption_per_unit' => 0.19, // рулонов на м²
                'package_size' => 1,
                'unit' => 'рулонов',
                'package_unit' => 'рулонов',
                'calculation_type' => 'area'
            ],
            34 => [
                'id' => 34,
                'name' => 'Клей для обоев 500г',
                'consumption_per_unit' => 0.05, // кг на м²
                'package_size' => 0.5,
                'unit' => 'кг',
                'package_unit' => 'упаковок',
                'calculation_type' => 'area'
            ],
            35 => [
                'id' => 35,
                'name' => 'Плинтус напольный 2.5м',
                'consumption_per_unit' => 0.42, // шт на м.п.
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'linear'
            ],
            36 => [
                'id' => 36,
                'name' => 'Уголок пластиковый 2.7м',
                'consumption_per_unit' => 0.37, // шт на м.п.
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'linear'
            ],
            37 => [
                'id' => 37,
                'name' => 'Дюбель-гвоздь 6х60мм',
                'consumption_per_unit' => 8, // шт на м²
                'package_size' => 100,
                'unit' => 'шт',
                'package_unit' => 'упаковок',
                'calculation_type' => 'area'
            ],
            38 => [
                'id' => 38,
                'name' => 'Дюбель-гвоздь 8х80мм',
                'consumption_per_unit' => 6, // шт на м²
                'package_size' => 50,
                'unit' => 'шт',
                'package_unit' => 'упаковок',
                'calculation_type' => 'area'
            ],
            39 => [
                'id' => 39,
                'name' => 'Анкер клиновой 10х100мм',
                'consumption_per_unit' => 4, // шт на м²
                'package_size' => 25,
                'unit' => 'шт',
                'package_unit' => 'упаковок',
                'calculation_type' => 'area'
            ],
            40 => [
                'id' => 40,
                'name' => 'Лента малярная 50мм х 50м',
                'consumption_per_unit' => 1.2, // м на м.п.
                'package_size' => 50,
                'unit' => 'м',
                'package_unit' => 'рулонов',
                'calculation_type' => 'linear'
            ],
            41 => [
                'id' => 41,
                'name' => 'Пленка защитная 4х5м',
                'consumption_per_unit' => 1.1, // м² на м²
                'package_size' => 20,
                'unit' => 'м²',
                'package_unit' => 'рулонов',
                'calculation_type' => 'area'
            ],
            42 => [
                'id' => 42,
                'name' => 'Затирка для швов 2кг',
                'consumption_per_unit' => 0.3, // кг на м²
                'package_size' => 2,
                'unit' => 'кг',
                'package_unit' => 'упаковок',
                'calculation_type' => 'area'
            ],
            43 => [
                'id' => 43,
                'name' => 'Герметик силиконовый 310мл',
                'consumption_per_unit' => 0.15, // туб на м.п.
                'package_size' => 1,
                'unit' => 'туб',
                'package_unit' => 'туб',
                'calculation_type' => 'linear'
            ],
            44 => [
                'id' => 44,
                'name' => 'Пена монтажная 750мл',
                'consumption_per_unit' => 0.08, // баллонов на м.п.
                'package_size' => 1,
                'unit' => 'баллонов',
                'package_unit' => 'баллонов',
                'calculation_type' => 'linear'
            ],
            45 => [
                'id' => 45,
                'name' => 'Утеплитель минвата 50мм',
                'consumption_per_unit' => 1.05, // м² на м²
                'package_size' => 5.76,
                'unit' => 'м²',
                'package_unit' => 'упаковок',
                'calculation_type' => 'area'
            ],
            46 => [
                'id' => 46,
                'name' => 'Пароизоляция Изоспан В',
                'consumption_per_unit' => 1.1, // м² на м²
                'package_size' => 70,
                'unit' => 'м²',
                'package_unit' => 'рулонов',
                'calculation_type' => 'area'
            ],
            47 => [
                'id' => 47,
                'name' => 'Мембрана гидроизоляционная',
                'consumption_per_unit' => 1.1, // м² на м²
                'package_size' => 75,
                'unit' => 'м²',
                'package_unit' => 'рулонов',
                'calculation_type' => 'area'
            ],
            48 => [
                'id' => 48,
                'name' => 'Кирпич керамический одинарный',
                'consumption_per_unit' => 51, // шт на м²
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'area'
            ],
            49 => [
                'id' => 49,
                'name' => 'Раствор кладочный М150 25кг',
                'consumption_per_unit' => 0.65, // мешков на м²
                'package_size' => 1,
                'unit' => 'мешков',
                'package_unit' => 'мешков',
                'calculation_type' => 'area'
            ],
            50 => [
                'id' => 50,
                'name' => 'Арматура А500С диаметр 12мм',
                'consumption_per_unit' => 8.5, // кг на м²
                'package_size' => 11.9,
                'unit' => 'кг',
                'package_unit' => 'прутков',
                'calculation_type' => 'area'
            ],
            51 => [
                'id' => 51,
                'name' => 'Проволока вязальная 1.2мм',
                'consumption_per_unit' => 0.02, // кг на м²
                'package_size' => 5,
                'unit' => 'кг',
                'package_unit' => 'бухт',
                'calculation_type' => 'area'
            ],
            52 => [
                'id' => 52,
                'name' => 'Блоки газосиликатные 200х300х600',
                'consumption_per_unit' => 27.8, // шт на м³
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'area_layer'
            ],
            53 => [
                'id' => 53,
                'name' => 'Сетка армирующая фасадная 5х5мм',
                'consumption_per_unit' => 1.1, // м² на м²
                'package_size' => 50,
                'unit' => 'м²',
                'package_unit' => 'рулонов',
                'calculation_type' => 'area'
            ],
            54 => [
                'id' => 54,
                'name' => 'Грунтовка адгезионная Бетоноконтакт 20кг',
                'consumption_per_unit' => 0.35, // кг на м²
                'package_size' => 20,
                'unit' => 'кг',
                'package_unit' => 'ведер',
                'calculation_type' => 'area'
            ],
            55 => [
                'id' => 55,
                'name' => 'Кабель ВВГнг 3х2.5',
                'consumption_per_unit' => 1.2, // м на м.п.
                'package_size' => 100,
                'unit' => 'м',
                'package_unit' => 'бухт',
                'calculation_type' => 'linear'
            ],
            56 => [
                'id' => 56,
                'name' => 'Гофра для кабеля 20мм',
                'consumption_per_unit' => 1.1, // м на м.п.
                'package_size' => 50,
                'unit' => 'м',
                'package_unit' => 'бухт',
                'calculation_type' => 'linear'
            ],
            57 => [
                'id' => 57,
                'name' => 'Розетка двойная с заземлением',
                'consumption_per_unit' => 1, // шт на точку
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'linear'
            ],
            58 => [
                'id' => 58,
                'name' => 'Выключатель одноклавишный',
                'consumption_per_unit' => 1, // шт на точку
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'linear'
            ],
            59 => [
                'id' => 59,
                'name' => 'Автоматический выключатель 16А',
                'consumption_per_unit' => 1, // шт на группу
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'linear'
            ],
            60 => [
                'id' => 60,
                'name' => 'Труба полипропиленовая 20мм',
                'consumption_per_unit' => 1.15, // м на м.п.
                'package_size' => 4,
                'unit' => 'м',
                'package_unit' => 'прутков',
                'calculation_type' => 'linear'
            ],
            61 => [
                'id' => 61,
                'name' => 'Фитинг полипропиленовый угол 20мм',
                'consumption_per_unit' => 0.25, // шт на м.п.
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'linear'
            ],
            62 => [
                'id' => 62,
                'name' => 'Смеситель для раковины',
                'consumption_per_unit' => 1, // шт на точку
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'linear'
            ],
            63 => [
                'id' => 63,
                'name' => 'Унитаз-компакт напольный',
                'consumption_per_unit' => 1, // шт на точку
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'linear'
            ],
            64 => [
                'id' => 64,
                'name' => 'Раковина накладная 60см',
                'consumption_per_unit' => 1, // шт на точку
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'linear'
            ],
            65 => [
                'id' => 65,
                'name' => 'Ванна акриловая 170х70см',
                'consumption_per_unit' => 1, // шт на точку
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'linear'
            ],
            66 => [
                'id' => 66,
                'name' => 'Радиатор биметаллический 500мм',
                'consumption_per_unit' => 1.2, // секций на м²
                'package_size' => 1,
                'unit' => 'секций',
                'package_unit' => 'секций',
                'calculation_type' => 'area'
            ],
            67 => [
                'id' => 67,
                'name' => 'Дверь межкомнатная 80см',
                'consumption_per_unit' => 1, // шт на проем
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'linear'
            ],
            68 => [
                'id' => 68,
                'name' => 'Дверная коробка комплект',
                'consumption_per_unit' => 1, // комплект на проем
                'package_size' => 1,
                'unit' => 'комплект',
                'package_unit' => 'комплектов',
                'calculation_type' => 'linear'
            ],
            69 => [
                'id' => 69,
                'name' => 'Наличник дверной комплект',
                'consumption_per_unit' => 1, // комплект на проем
                'package_size' => 1,
                'unit' => 'комплект',
                'package_unit' => 'комплектов',
                'calculation_type' => 'linear'
            ],
            70 => [
                'id' => 70,
                'name' => 'Окно ПВХ 1500х1000мм',
                'consumption_per_unit' => 1, // шт на проем
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'linear'
            ],
            71 => [
                'id' => 71,
                'name' => 'Откос пластиковый 250мм',
                'consumption_per_unit' => 0.34, // м на м.п.
                'package_size' => 3,
                'unit' => 'м',
                'package_unit' => 'прутков',
                'calculation_type' => 'linear'
            ],
            72 => [
                'id' => 72,
                'name' => 'Подоконник ПВХ 300мм',
                'consumption_per_unit' => 1.05, // м на м.п.
                'package_size' => 6,
                'unit' => 'м',
                'package_unit' => 'прутков',
                'calculation_type' => 'linear'
            ],
            73 => [
                'id' => 73,
                'name' => 'Светильник потолочный LED 18Вт',
                'consumption_per_unit' => 0.1, // шт на м²
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'area'
            ],
            74 => [
                'id' => 74,
                'name' => 'Лампа LED Е27 12Вт',
                'consumption_per_unit' => 1, // шт на светильник
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'linear'
            ],
            75 => [
                'id' => 75,
                'name' => 'Потолок натяжной матовый',
                'consumption_per_unit' => 1.05, // м² на м²
                'package_size' => 1,
                'unit' => 'м²',
                'package_unit' => 'м²',
                'calculation_type' => 'area'
            ],
            76 => [
                'id' => 76,
                'name' => 'Профиль для натяжного потолка',
                'consumption_per_unit' => 1.1, // м на м.п.
                'package_size' => 2.5,
                'unit' => 'м',
                'package_unit' => 'прутков',
                'calculation_type' => 'linear'
            ],
            77 => [
                'id' => 77,
                'name' => 'Лестница чердачная 120х60см',
                'consumption_per_unit' => 1, // шт на проем
                'package_size' => 1,
                'unit' => 'шт',
                'package_unit' => 'шт',
                'calculation_type' => 'linear'
            ],
        ];
    }

    /**
     * Вычисляет расход материала
     */
    private function calculateConsumption($material, $volume, $layer)
    {
        switch ($material['calculation_type']) {
            case 'area_layer':
                // Для материалов, где расход зависит от площади и толщины слоя
                return $volume * $layer * $material['consumption_per_unit'];
            
            case 'linear_layer':
                // Для материалов, где расход зависит от длины и количества слоев
                return $volume * $layer * $material['consumption_per_unit'];
            
            case 'area':
                // Для материалов, где расход зависит только от площади
                if (isset($material['coverage_per_package'])) {
                    // Для материалов типа ГКЛ, где указано покрытие одной упаковки
                    return $volume / $material['coverage_per_package'];
                }
                return $volume * $material['consumption_per_unit'];
            
            case 'linear':
                // Для материалов, где расход зависит только от длины
                return $volume * $material['consumption_per_unit'];
            
            default:
                return $volume * $material['consumption_per_unit'];
        }
    }

    /**
     * Вычисляет количество упаковок с запасом 10%
     */
    private function calculatePackages($consumption, $packageSize)
    {
        $consumptionWithReserve = $consumption * 1.1; // +10% запас
        return ceil($consumptionWithReserve / $packageSize);
    }

    /**
     * Возвращает примерную стоимость материала
     */
    private function getEstimatedPrice($materialId)
    {
        // Примерные цены за упаковку (можно вынести в конфиг или БД)
        $prices = [
            4 => 450,   // Штукатурка гипсовая Ротбанд 30 кг
            5 => 85,    // Маяк
            6 => 380,   // Шпатлевка Ветонит ЛР 25 кг
            7 => 520,   // Шпатлевка Ротбанд Паста 18 кг
            8 => 2200,  // Стеклохолст Oscar 50 г/м2, 50 м2
            9 => 320,   // Клей Oscar для стеклохолста 10 кг
            10 => 280,  // Пескобетон м-300 40 кг
            11 => 180,  // Керамзит фракции до 10мм, мешок
            12 => 380,  // Наливной пол (мешок 20 кг)
            13 => 420,  // Гипсокартон Knauf 2500х1200 мм влагостойкий
            14 => 0,    // Монтаж лобика из ГКЛВ / Откосы (услуга)
            15 => 180,  // Профиль поперечный 27х60
            16 => 160,  // Профиль кнауф 27*28
            17 => 25,   // Подвес прямой усиленный Кнауф
            18 => 30,   // Краб соединительный кнауф
            19 => 150,  // Саморезы для ГКЛ Knauf 16х2,5
            20 => 160,  // Саморезы для ГКЛ Knauf 19х3,5
            21 => 170,  // Саморезы для ГКЛ Knauf 25х3,5
            22 => 850,  // Пенополистерол
            23 => 320,  // Шумоизоляция Соноплат / ГКЛ 600х1200
            24 => 420,  // Клей для плитки
            25 => 140,  // Саморезы клопы 13х4,2 мм
            26 => 85,   // Газоблок 600х250х100 d500
            27 => 280,  // Клей для газоблоков
            28 => 500,  // Грунтовка глубокого проникновения 10л
            29 => 600,  // Краска водоэмульсионная 15кг
            30 => 300,  // Плитка керамическая 30х30см
            31 => 1200, // Ламинат 32 класс упаковка
            32 => 700,  // Линолеум коммерческий 2м
            33 => 1500, // Обои флизелиновые рулон 10м
            34 => 250,  // Клей для обоев 500г
            35 => 350,  // Плинтус напольный 2.5м
            36 => 300,  // Уголок пластиковый 2.7м
            37 => 100,  // Дюбель-гвоздь 6х60мм
            38 => 80,   // Дюбель-гвоздь 8х80мм
            39 => 120,  // Анкер клиновой 10х100мм
            40 => 150,  // Лента малярная 50мм х 50м
            41 => 100,  // Пленка защитная 4х5м
            42 => 200,  // Затирка для швов 2кг
            43 => 250,  // Герметик силиконовый 310мл
            44 => 300,  // Пена монтажная 750мл
            45 => 500,  // Утеплитель минвата 50мм
            46 => 700,  // Пароизоляция Изоспан В
            47 => 800,  // Мембрана гидроизоляционная
            48 => 20,   // Кирпич керамический одинарный
            49 => 10,   // Раствор кладочный М150 25кг
            50 => 300,  // Арматура А500С диаметр 12мм
            51 => 50,   // Проволока вязальная 1.2мм
            52 => 100,  // Блоки газосиликатные 200х300х600
            53 => 1500, // Сетка армирующая фасадная 5х5мм
            54 => 300,  // Грунтовка адгезионная Бетоноконтакт 20кг
            55 => 100,  // Кабель ВВГнг 3х2.5
            56 => 80,   // Гофра для кабеля 20мм
            57 => 300,  // Розетка двойная с заземлением
            58 => 200,  // Выключатель одноклавишный
            59 => 150,  // Автоматический выключатель 16А
            60 => 100,  // Труба полипропиленовая 20мм
            61 => 50,   // Фитинг полипропиленовый угол 20мм
            62 => 300,  // Смеситель для раковины
            63 => 300,  // Унитаз-компакт напольный
            64 => 300,  // Раковина накладная 60см
            65 => 500,  // Ванна акриловая 170х70см
            66 => 1000, // Радиатор биметаллический 500мм
            67 => 300,  // Дверь межкомнатная 80см
            68 => 500,  // Дверная коробка комплект
            69 => 200,  // Наличник дверной комплект
            70 => 500,  // Окно ПВХ 1500х1000мм
            71 => 150,  // Откос пластиковый 250мм
            72 => 200,  // Подоконник ПВХ 300мм
            73 => 100,  // Светильник потолочный LED 18Вт
            74 => 50,   // Лампа LED Е27 12Вт
            75 => 300,  // Потолок натяжной матовый
            76 => 100,  // Профиль для натяжного потолка
            77 => 500,  // Лестница чердачная 120х60см
        ];

        return $prices[$materialId] ?? 0;
    }

    /**
     * Сохраняет пользовательские цены материалов
     */
    public function savePrices(Request $request)
    {
        $request->validate([
            'prices' => 'required|array',
            'prices.*' => 'required|numeric|min:0',
        ]);

        // Сохраняем цены в сессии пользователя
        session(['material_prices' => $request->prices]);

        return response()->json([
            'success' => true,
            'message' => 'Цены успешно сохранены'
        ]);
    }

    /**
     * Получает пользовательские цены материалов
     */
    public function getPrices(Request $request)
    {
        // Получаем сохраненные цены из сессии
        $userPrices = session('material_prices', []);
        
        // Объединяем с дефолтными ценами
        $materials = $this->getMaterialsData();
        $allPrices = [];
        
        foreach ($materials as $id => $material) {
            $allPrices[$id] = isset($userPrices[$id]) 
                ? $userPrices[$id] 
                : $this->getEstimatedPrice($id);
        }

        return response()->json([
            'success' => true,
            'prices' => $allPrices
        ]);
    }

    /**
     * Возвращает примерную стоимость материала с учетом пользовательских цен
     */
    private function getEstimatedPriceWithCustom($materialId)
    {
        // Получаем пользовательские цены из сессии
        $userPrices = session('material_prices', []);
        
        // Если есть пользовательская цена, используем её
        if (isset($userPrices[$materialId])) {
            return floatval($userPrices[$materialId]);
        }
        
        // Иначе используем дефолтную цену
        return $this->getEstimatedPrice($materialId);
    }

    /**
     * Генерирует PDF с результатами расчета
     */
    public function exportPdf(Request $request)
    {
        $request->validate([
            'calculations' => 'required|array',
            'calculations.*.material_id' => 'required|integer',
            'calculations.*.volume' => 'required|numeric|min:0',
            'calculations.*.layer' => 'nullable|numeric|min:0',
        ]);

        $materials = $this->getMaterialsData();
        $results = [];
        $totalCost = 0; // Общая стоимость материалов

        foreach ($request->calculations as $calc) {
            $materialId = $calc['material_id'];
            $volume = floatval($calc['volume']);
            $layer = isset($calc['layer']) ? floatval($calc['layer']) : 1;

            if (isset($materials[$materialId])) {
                $material = $materials[$materialId];
                
                // Для материалов, где слой не нужен, устанавливаем layer = 1
                if (in_array($material['calculation_type'], ['area', 'linear'])) {
                    $layer = 1;
                }
                
                // Расчет расхода
                $consumption = $this->calculateConsumption($material, $volume, $layer);
                
                // Расчет упаковок с запасом 10%
                $packagesWithReserve = $this->calculatePackages($consumption, $material['package_size']);

                // Примерная стоимость с учетом пользовательских цен
                $estimatedPrice = $this->getEstimatedPriceWithCustom($materialId);
                $materialCost = $packagesWithReserve * $estimatedPrice;
                $totalCost += $materialCost;

                $results[] = [
                    'material' => $material,
                    'volume' => $volume,
                    'layer' => $layer,
                    'consumption' => $consumption,
                    'packages' => $packagesWithReserve,
                    'unit' => $material['unit'],
                    'estimated_price' => $estimatedPrice,
                    'total_cost' => $materialCost
                ];
            }
        }

        // Данные для PDF
        $data = [
            'results' => $results,
            'total_cost' => $totalCost,
            'calculation_date' => Carbon::now()->format('d.m.Y H:i'),
            'user_name' => auth()->user()->name ?? 'Пользователь',
            'company_name' => 'Строительная компания', // Можно настроить
        ];

        // Генерируем PDF
        $pdf = Pdf::loadView('partner.calculator.pdf', $data);
        $pdf->setPaper('A4', 'portrait');
        
        // Возвращаем PDF для скачивания
        $fileName = 'Расчет_материалов_' . Carbon::now()->format('d-m-Y_H-i') . '.pdf';
        
        return $pdf->download($fileName);
    }
}
