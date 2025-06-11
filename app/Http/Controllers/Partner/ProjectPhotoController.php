<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectPhotoController extends Controller
{
    /**
     * Отображает фотоотчет проекта.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function index(Project $project)
    {
        $this->authorize('view', $project);
        
        $categories = $this->getPhotoCategories();
        $projectCategories = $project->getPhotoCategories();
        
        return view('partner.projects.photos.index', compact('project', 'categories', 'projectCategories'));
    }

    /**
     * Загружает новую фотографию для проекта.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Project $project)
    {
        $request->validate([
            'photos' => 'required|array',
            'photos.*' => 'required|image', // Поддержка всех форматов изображений без ограничения по размеру
            'category' => 'required|string',
            'comment' => 'nullable|string|max:255',
        ]);
        
        // Проверяем права доступа к проекту
        $this->authorize('update', $project);
        
        // Создаем директорию для хранения фотографий проекта, если она не существует
        $photoPath = "project_photos/{$project->id}";
        if (!Storage::disk('public')->exists($photoPath)) {
            Storage::disk('public')->makeDirectory($photoPath);
        }
        
        $savedPhotos = 0;
        $errors = [];
        
        // Обрабатываем каждое загруженное фото
        foreach ($request->file('photos') as $photo) {
            try {
                // Генерируем уникальное имя файла
                $filename = Str::uuid() . '.' . $photo->getClientOriginalExtension();
                
                // Сохраняем фото на диск
                $photo->storeAs($photoPath, $filename, 'public');
                
                // Создаем запись в БД
                ProjectPhoto::create([
                    'project_id' => $project->id,
                    'filename' => $filename,
                    'original_name' => $photo->getClientOriginalName(),
                    'category' => $request->category,
                    'comment' => $request->comment,
                    'size' => $photo->getSize(),
                    'mime_type' => $photo->getMimeType(),
                ]);
                
                $savedPhotos++;
            } catch (\Exception $e) {
                $errors[] = "Ошибка загрузки файла {$photo->getClientOriginalName()}: {$e->getMessage()}";
            }
        }
        
        if ($savedPhotos > 0) {
            $message = "Успешно загружено {$savedPhotos} " . $this->pluralizePhotos($savedPhotos);
            if (count($errors) > 0) {
                return redirect()->back()->with('success', $message)
                    ->with('warning', 'Некоторые файлы не удалось загрузить. Проверьте формат файлов.');
            }
            return redirect()->back()->with('success', $message);
        }
        
        return redirect()->back()->with('error', 'Не удалось загрузить фотографии. ' . implode(' ', $errors));
    }
    
    /**
     * Возвращает правильное окончание для слова "фотография" в зависимости от числа
     * 
     * @param int $count
     * @return string
     */
    private function pluralizePhotos($count)
    {
        if ($count % 10 === 1 && $count % 100 !== 11) {
            return 'фотография';
        } elseif ($count % 10 >= 2 && $count % 10 <= 4 && ($count % 100 < 10 || $count % 100 >= 20)) {
            return 'фотографии';
        }
        return 'фотографий';
    }
    
    /**
     * Удаляет фотографию из проекта.
     *
     * @param  \App\Models\ProjectPhoto  $projectPhoto
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroy(ProjectPhoto $projectPhoto)
    {
        $this->authorize('update', $projectPhoto->project);
        
        $project = $projectPhoto->project;
        
        Storage::disk('public')->delete('project_photos/' . $project->id . '/' . $projectPhoto->filename);
        
        $projectPhoto->delete();
        
        if (request()->expectsJson()) {
            return response()->json(['success' => 'Фотография успешно удалена']);
        }
        
        return redirect()->back()->with('success', 'Фотография успешно удалена');
    }

    /**
     * Получить список категорий фотоотчета.
     *
     * @return array
     */
    public function getPhotoCategories()
    {
        return [
            'Демонтаж',
            'Завоз инструмента и заезд ремонтной бригады',
            'Подготовка объекта',
            'Демонтажные работы',
            'Возведение перегородок',
            'Оштукатуривание стен',
            'Звукоизоляция пола (Шуманет)',
            'Электромонтажные работы',
            'Сантехнические работы',
            'Отопление',
            'Стяжка',
            'Балкон',
            'Конструкции ГКЛ (перегородки, откосы, короба, ниши и т.п.)',
            'Напольная плитка + теплый пол',
            'Душевой поддон + трап',
            'Настенная плитка',
            'Монтаж ГКЛ потолка',
            'Подготовка потолка под отделку (шпаклевание, ошкуривание)',
            'Подготовка стен под финишную отделку (шпаклевание)',
            'Подготовка откосов и ниш (шпаклевание)',
            'Финишная отделка стен',
            'Финишная отделка потолка',
            'Финишная отделка откосов',
            'Декоративная отделка',
            'Напольное покрытие',
            'Сборка мебели',
            'Монтаж электрики чистовой (розетки, выключатели, освещение)',
            'Монтаж сантехники - чистовой (смесители, душевые стойки, раковины)',
            'Плиточный фартук',
            'Плинтус и стыки',
            'Финишная доработка объекта (герметизация, очистка, подключение оборудования)',
            'Завершенный ремонт',
            'Другое'
        ];
    }
}
