@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
        <h1 class="h3 mb-3 mb-md-0">Объекты</h1>
        <a href="{{ route('partner.projects.create') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Создать объект
        </a>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <!-- Фильтры и поиск - улучшенная версия для мобильных -->
    <div class="card mb-4">
        <div class="card-header p-2 d-flex justify-content-between align-items-center">
            <h5 class="m-0">Фильтры</h5>
            <button class="btn btn-sm btn-outline-secondary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i class="fas fa-sliders-h"></i>
            </button>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body p-2 p-md-3">
                <form action="{{ route('partner.projects.index') }}" method="GET" id="filterForm">
                    <input type="hidden" name="filter" value="true">
                    
                    <div class="row g-2">
                        <div class="col-12 mb-2">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" name="search" placeholder="Поиск по имени, адресу..." value="{{ $filters['search'] ?? '' }}">
                                <button type="submit" class="btn btn-primary d-md-none">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-6 col-md-4 mb-2">
                            <label class="d-block d-md-none small mb-1">Статус</label>
                            <select class="form-select" name="status" id="status">
                                <option value="">Все статусы</option>
                                <option value="active" {{ isset($filters['status']) && $filters['status'] == 'active' ? 'selected' : '' }}>Активные</option>
                                <option value="completed" {{ isset($filters['status']) && $filters['status'] == 'completed' ? 'selected' : '' }}>Завершенные</option>
                                <option value="paused" {{ isset($filters['status']) && $filters['status'] == 'paused' ? 'selected' : '' }}>Приостановленные</option>
                                <option value="cancelled" {{ isset($filters['status']) && $filters['status'] == 'cancelled' ? 'selected' : '' }}>Отмененные</option>
                            </select>
                        </div>
                        
                        <div class="col-6 col-md-4 mb-2">
                            <label class="d-block d-md-none small mb-1">Тип работ</label>
                            <select class="form-select" name="work_type" id="work_type">
                                <option value="">Все типы работ</option>
                                <option value="repair" {{ isset($filters['work_type']) && $filters['work_type'] == 'repair' ? 'selected' : '' }}>Ремонт</option>
                                <option value="design" {{ isset($filters['work_type']) && $filters['work_type'] == 'design' ? 'selected' : '' }}>Дизайн</option>
                                <option value="construction" {{ isset($filters['work_type']) && $filters['work_type'] == 'construction' ? 'selected' : '' }}>Строительство</option>
                            </select>
                        </div>
                        
                        <div class="col-6 d-md-none">
                            <a href="{{ route('partner.projects.index', ['clear' => true]) }}" class="btn btn-outline-secondary w-100">
                                Сбросить
                            </a>
                        </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-12 text-end d-none d-md-block">
                            <a href="{{ route('partner.projects.index', ['clear' => true]) }}" class="btn btn-outline-secondary">
                                Сбросить
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Информация о примененных фильтрах -->
    @if(!empty(array_filter($filters ?? [])))
        <div class="mb-3 overflow-auto hide-scroll">
            <div class="d-flex align-items-center flex-nowrap">
                <span class="me-2 text-nowrap">Фильтры:</span>
                @if(!empty($filters['search']))
                    <span class="badge bg-light text-dark me-2">Поиск: {{ Str::limit($filters['search'], 15) }}</span>
                @endif
                @if(!empty($filters['status']))
                    <span class="badge bg-light text-dark me-2">Статус: 
                        {{ $filters['status'] == 'active' ? 'Активные' : 
                          ($filters['status'] == 'completed' ? 'Завершенные' : 
                          ($filters['status'] == 'paused' ? 'Приостановленные' : 'Отмененные')) }}
                    </span>
                @endif
                @if(!empty($filters['work_type']))
                    <span class="badge bg-light text-dark me-2">Тип: 
                        {{ $filters['work_type'] == 'repair' ? 'Ремонт' : 
                          ($filters['work_type'] == 'design' ? 'Дизайн' : 'Строительство') }}
                    </span>
                @endif
            </div>
        </div>
    @endif
    
    @if($projects->isEmpty())
        <div class="card">
            <div class="card-body text-center py-4">
                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Объекты не найдены</h5>
                @if(!empty(array_filter($filters ?? [])))
                    <p>Попробуйте изменить параметры фильтрации или <a href="{{ route('partner.projects.index', ['clear' => true]) }}">сбросить все фильтры</a>.</p>
                @else
                    <p>Создайте свой первый объект, нажав на кнопку "Создать объект" выше.</p>
                    <a href="{{ route('partner.projects.create') }}" class="btn btn-outline-primary mt-2">
                        <i class="fas fa-plus-circle me-1"></i>Создать объект
                    </a>
                @endif
            </div>
        </div>
    @else
        <div class="row">
            @foreach($projects as $project)
                <div class="col-12 col-md-6 col-xl-4 mb-3">
                    <div class="card h-100 project-card">
                        <div class="card-header d-flex justify-content-between align-items-center p-2 px-3">
                            <h5 class="card-title mb-0 text-truncate" style="max-width: 70%;">
                                <a href="{{ route('partner.projects.show', $project) }}" class="text-decoration-none text-dark stretched-link">
                                    {{ $project->client_name }}
                                </a>
                            </h5>
                            <span class="badge {{ $project->status == 'active' ? 'bg-success' : ($project->status == 'paused' ? 'bg-warning' : ($project->status == 'completed' ? 'bg-info' : 'bg-secondary')) }}">
                                {{ ucfirst($project->status) }}
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-map-marker-alt text-muted mt-1 me-2"></i>
                                    <div class="text-truncate" style="max-width: 100%;">
                                        {{ $project->address }}{{ $project->apartment_number ? ', кв. ' . $project->apartment_number : '' }}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-phone text-muted me-2"></i>
                                        <div class="text-truncate">{{ $project->phone }}</div>
                                    </div>
                                </div>
                                <div class="col-6 text-end">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <i class="fas fa-ruler-combined text-muted me-2"></i>
                                        {{ $project->area ?? '-' }} м²
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-tools text-muted me-2"></i>
                                        <span class="text-truncate">{{ $project->work_type_text }}</span>
                                    </div>
                                </div>
                                <div class="col-6 text-end">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <i class="fas fa-home text-muted me-2"></i>
                                        <span class="text-truncate">{{ $project->object_type ?? 'Не указан' }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-2">
                            
                            @if($project->contract_date)
                            <div class="small text-muted mb-1">
                                <i class="fas fa-file-signature me-1"></i> Договор: 
                                {{ $project->contract_date->format('d.m.Y') }}, 
                                №{{ $project->contract_number ?? '-' }}
                            </div>
                            @endif
                            
                            <div class="small text-end">
                                <strong>
                                    <i class="fas fa-money-bill-wave text-success me-1"></i>
                                    {{ number_format($project->total_amount, 0, '.', ' ') }} ₽
                                </strong>
                            </div>
                        </div>
                        <div class="card-footer d-flex p-2">
                            <a href="{{ route('partner.projects.edit', $project) }}" class="btn btn-sm btn-outline-secondary flex-grow-1 me-2">
                                <i class="fas fa-edit"></i>
                                <span class="ms-1">Редактировать</span>
                            </a>
                            <a href="{{ route('partner.projects.show', $project) }}" class="btn btn-sm btn-primary flex-grow-1">
                                <i class="fas fa-eye"></i>
                                <span class="ms-1">Просмотр</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="d-flex justify-content-center mt-4">
            <div class="pagination-container overflow-auto px-2 py-1 hide-scroll">
                {{ $projects->links() }}
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Получаем элементы формы
        const filterForm = document.getElementById('filterForm');
        const filterSelects = filterForm.querySelectorAll('select');
        const searchInput = filterForm.querySelector('input[name="search"]');
        
        // Авто-отправка формы при изменении селектов
        filterSelects.forEach(function(select) {
            select.addEventListener('change', function() {
                filterForm.submit();
            });
        });
        
        // Отправка формы поиска после паузы в наборе текста на десктопах
        let typingTimer;
        const doneTypingInterval = 800; // время в мс
        
        searchInput.addEventListener('keyup', function() {
            // Для мобильных устройств не используем автоматическую отправку
            if (window.innerWidth > 768) {
                clearTimeout(typingTimer);
                if (searchInput.value) {
                    typingTimer = setTimeout(function() {
                        filterForm.submit();
                    }, doneTypingInterval);
                }
            }
        });
        
        // Сбросить таймер, если пользователь продолжил печатать
        searchInput.addEventListener('keydown', function() {
            clearTimeout(typingTimer);
        });
    });
</script>

<style>
/* Дополнительные стили для мобильных устройств */
@media (max-width: 576px) {
    .project-card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-radius: 8px;
    }
    
    .project-card .card-header {
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }
    
    .project-card .card-footer {
        border-bottom-left-radius: 8px;
        border-bottom-right-radius: 8px;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.35em 0.65em;
    }
    
    /* Улучшения для фильтров */
    #filterCollapse label {
        font-weight: 500;
        color: #495057;
    }
    
    .form-select, .form-control, .btn {
        font-size: 16px; /* Оптимальный размер для предотвращения масштабирования на iOS */
    }
    
    /* Пагинация */
    .pagination {
        flex-wrap: nowrap;
        overflow-x: auto;
    }
    
    .page-item {
        white-space: nowrap;
    }
    
    /* Растянутые ссылки */
    .stretched-link::after {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 1;
        content: "";
    }
}

/* Улучшения для сетки карточек */
@media (max-width: 768px) {
    .row {
        margin-left: -8px;
        margin-right: -8px;
    }
    
    .row > [class*="col-"] {
        padding-left: 8px;
        padding-right: 8px;
    }
    
    /* Улучшенный стиль заголовков */
    h5.card-title {
        font-size: 1rem;
        font-weight: 600;
    }
}

/* Улучшенный стиль контейнера пагинации */
.pagination-container {
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 5px;
}
</style>
@endsection
