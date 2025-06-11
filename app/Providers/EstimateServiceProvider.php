<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\EstimateTemplateService;
use App\Services\MaterialsEstimateTemplateService;

class EstimateServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Регистрируем сервисы для работы с шаблонами смет
        $this->app->singleton(MaterialsEstimateTemplateService::class, function ($app) {
            return new MaterialsEstimateTemplateService();
        });
        
        $this->app->singleton(EstimateTemplateService::class, function ($app) {
            return new EstimateTemplateService(
                $app->make(MaterialsEstimateTemplateService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Настраиваем расположение шаблонов
        $this->publishes([
            __DIR__.'/../resources/templates' => storage_path('app/templates'),
        ], 'estimate-templates');
        
        // Гарантируем наличие директории для шаблонов
        $this->ensureTemplatesDirectoryExists();
    }
    
    /**
     * Проверяет и создает директорию для шаблонов смет при необходимости
     */
    protected function ensureTemplatesDirectoryExists()
    {
        $templateDir = storage_path('app/templates/estimates');
        
        if (!file_exists($templateDir)) {
            mkdir($templateDir, 0755, true);
        }
    }
}
