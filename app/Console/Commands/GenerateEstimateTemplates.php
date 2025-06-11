<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EstimateTemplateService;
use Illuminate\Support\Facades\Storage;

class GenerateEstimateTemplates extends Command
{
    /**
     * Название и сигнатура команды.
     *
     * @var string
     */
    protected $signature = 'estimates:generate-templates';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Генерирует шаблоны смет для различных типов работ';

    /**
     * Сервис шаблонов смет
     * 
     * @var EstimateTemplateService
     */
    protected $templateService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Генерация шаблонов смет...');
        
        // Получаем сервис для работы с шаблонами через DI
        $this->templateService = app(EstimateTemplateService::class);

        // Создаем директорию для шаблонов, если её нет
        $templatesPath = storage_path('app/templates/estimates');
        if (!is_dir($templatesPath)) {
            mkdir($templatesPath, 0755, true);
        }

        // Генерируем основной шаблон
        $this->info('Создание шаблона main...');
        if ($this->templateService->createDefaultTemplate('main')) {
            $this->info('Шаблон main успешно создан.');
        } else {
            $this->error('Ошибка при создании шаблона main.');
        }

        // Генерируем шаблон материалов
        $this->info('Создание шаблона materials...');
        if ($this->templateService->createDefaultTemplate('materials')) {
            $this->info('Шаблон materials успешно создан.');
        } else {
            $this->error('Ошибка при создании шаблона materials.');
        }

        // Генерируем шаблон дополнительной сметы
        $this->info('Создание шаблона additional...');
        if ($this->templateService->createDefaultTemplate('additional')) {
            $this->info('Шаблон additional успешно создан.');
        } else {
            $this->error('Ошибка при создании шаблона additional.');
        }

        return 0;
    }
}
