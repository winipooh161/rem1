@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-2">
            <div>
                <h4>{{ $project->client_name }}</h4>
                <p class="text-muted mb-2">
                    <span class="badge {{ $project->status == 'active' ? 'bg-success' : ($project->status == 'paused' ? 'bg-warning' : ($project->status == 'completed' ? 'bg-info' : 'bg-secondary')) }}">
                        {{ $project->status == 'active' ? 'Активен' : ($project->status == 'paused' ? 'Приостановлен' : ($project->status == 'completed' ? 'Завершен' : 'Отменен')) }}
                    </span>
                    <span class="d-block d-md-inline mt-1 mt-md-0 ms-md-2">{{ $project->address }}{{ $project->apartment_number ? ', кв. ' . $project->apartment_number : '' }}</span>
                </p>
            </div>
            <div class="mt-3 mt-md-0">
                <div class="action-buttons-mobile d-flex flex-column flex-md-row">
                    <a href="{{ route('partner.projects.edit', $project) }}" class="btn btn-outline-primary mb-2 mb-md-0 me-md-2">
                        <i class="fas fa-edit me-1"></i> Редактировать
                    </a>
                    <button type="button" class="btn btn-outline-danger mb-2 mb-md-0 me-md-2" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal">
                        <i class="fas fa-trash-alt me-1"></i> Удалить
                    </button>
                    <a href="{{ route('partner.projects.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> К списку
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Панель вкладок -->
    <div class="card">
        <div class="card-header p-1 position-relative nav-tabs-wrapper">
            <ul class="nav nav-tabs card-header-tabs scrollable-x hide-scroll" id="projectTabs" role="tablist" data-project-id="{{ $project->id }}">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="main-tab" data-bs-toggle="tab" data-bs-target="#main" type="button" role="tab" aria-controls="main" aria-selected="true">
                        <i class="fas fa-info-circle d-block d-md-none"></i>
                        <span class="d-none d-md-block">Основная</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="finance-tab" data-bs-toggle="tab" data-bs-target="#finance" type="button" role="tab" aria-controls="finance" aria-selected="false">
                        <i class="fas fa-money-bill d-block d-md-none"></i>
                        <span class="d-none d-md-block">Финансы</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule" type="button" role="tab" aria-controls="schedule" aria-selected="false">
                        <i class="fas fa-calendar d-block d-md-none"></i>
                        <span class="d-none d-md-block">График</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="photos-tab" data-bs-toggle="tab" data-bs-target="#photos" type="button" role="tab" aria-controls="photos" aria-selected="false">
                        <i class="fas fa-camera d-block d-md-none"></i>
                        <span class="d-none d-md-block">Фото</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="design-tab" data-bs-toggle="tab" data-bs-target="#design" type="button" role="tab" aria-controls="design" aria-selected="false">
                        <i class="fas fa-paint-brush d-block d-md-none"></i>
                        <span class="d-none d-md-block">Дизайн</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="schemes-tab" data-bs-toggle="tab" data-bs-target="#schemes" type="button" role="tab" aria-controls="schemes" aria-selected="false">
                        <i class="fas fa-sitemap d-block d-md-none"></i>
                        <span class="d-none d-md-block">Схемы</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab" aria-controls="documents" aria-selected="false">
                        <i class="fas fa-file d-block d-md-none"></i>
                        <span class="d-none d-md-block">Документы</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="contract-tab" data-bs-toggle="tab" data-bs-target="#contract" type="button" role="tab" aria-controls="contract" aria-selected="false">
                        <i class="fas fa-file-contract d-block d-md-none"></i>
                        <span class="d-none d-md-block">Договор</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="check-tab" data-bs-toggle="tab" data-bs-target="#check" type="button" role="tab" aria-controls="check" aria-selected="false">
                        <i class="fas fa-check-square d-block d-md-none"></i>
                        <span class="d-none d-md-block">Проверка</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="other-tab" data-bs-toggle="tab" data-bs-target="#other" type="button" role="tab" aria-controls="other" aria-selected="false">
                        <i class="fas fa-ellipsis-h d-block d-md-none"></i>
                        <span class="d-none d-md-block">Прочее</span>
                    </button>
                </li>
            </ul>
            <div class="nav-tabs-scroll-indicator d-md-none"></div>
        </div>
        
        <div class="card-body">
            <div class="tab-content" id="projectTabsContent">
                <!-- Подключение содержимого вкладок из отдельных файлов -->
                <div class="tab-pane fade show active" id="main" role="tabpanel" aria-labelledby="main-tab">
                    @include('partner.projects.tabs.main')
                </div>
                
                <div class="tab-pane fade" id="finance" role="tabpanel" aria-labelledby="finance-tab">
                    @include('partner.projects.tabs.finance')
                </div>
                
                <div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="schedule-tab">
                    @include('partner.projects.tabs.schedule')
                </div>
                
                <div class="tab-pane fade" id="photos" role="tabpanel" aria-labelledby="photos-tab">
                    @include('partner.projects.tabs.photos')
                </div>
                
                <div class="tab-pane fade" id="design" role="tabpanel" aria-labelledby="design-tab">
                    @include('partner.projects.tabs.design')
                </div>
                
                <div class="tab-pane fade" id="schemes" role="tabpanel" aria-labelledby="schemes-tab">
                    @include('partner.projects.tabs.schemes')
                </div>
                
                <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                    @include('partner.projects.tabs.documents')
                </div>
                
                <div class="tab-pane fade" id="contract" role="tabpanel" aria-labelledby="contract-tab">
                    @include('partner.projects.tabs.contract')
                </div>
                
                <div class="tab-pane fade" id="check" role="tabpanel" aria-labelledby="check-tab">
                    @include('partner.projects.tabs.check')
                </div>
                
                <div class="tab-pane fade" id="other" role="tabpanel" aria-labelledby="other-tab">
                    @include('partner.projects.tabs.other')
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно подтверждения удаления -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы действительно хотите удалить объект "{{ $project->client_name }}"?</p>
                <p class="text-danger">Это действие невозможно отменить.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <form action="{{ route('partner.projects.destroy', $project) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Добавляем скрипт для индикации горизонтальной прокрутки на мобильных устройствах
document.addEventListener('DOMContentLoaded', function() {
    const tabsContainer = document.querySelector('.nav-tabs');
    const scrollIndicator = document.querySelector('.nav-tabs-scroll-indicator');
    
    if (tabsContainer && scrollIndicator) {
        tabsContainer.addEventListener('scroll', function() {
            const isAtEnd = tabsContainer.scrollLeft + tabsContainer.clientWidth >= tabsContainer.scrollWidth - 5;
            scrollIndicator.style.opacity = isAtEnd ? '0' : '1';
        });
        
        // Проверка при загрузке
        const isAtEnd = tabsContainer.scrollLeft + tabsContainer.clientWidth >= tabsContainer.scrollWidth - 5;
        scrollIndicator.style.opacity = isAtEnd ? '0' : '1';
    }
});
</script>

<style>
/* Дополнительные стили для улучшения мобильного отображения */
@media (max-width: 768px) {
    .action-buttons-mobile {
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    
    .action-buttons-mobile .btn {
        margin-bottom: 0.5rem;
    }
    
    /* Улучшение навигации по вкладкам */
    .nav-tabs .nav-link {
        padding: 0.5rem;
        min-width: 40px;
        text-align: center;
    }
    
    .nav-tabs .nav-link i {
        font-size: 1.2rem;
    }
    
    .card-header {
        padding: 0.5rem 0.25rem !important;
    }
    
    .card-body {
        padding: 1rem 0.75rem;
    }
    
    /* Улучшенный заголовок */
    h4 {
        font-size: 1.4rem;
    }
}
</style>
@endsection
