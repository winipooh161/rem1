@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <h1 class="h3 mb-2 mb-md-0">Шаблоны Excel</h1>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('partner.estimates.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>К сметам
            </a>
        </div>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
        </div>
    @endif
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-file-excel me-1"></i> Доступные шаблоны
        </div>
        <div class="card-body">
            <p class="text-muted mb-4">
                Выберите и скачайте шаблон, чтобы использовать его для создания своей сметы.
                Шаблоны содержат предварительно настроенные формулы и форматирование для быстрого создания смет.
            </p>
            
            <div class="row">
                @foreach($templates as $template)
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex mb-3">
                                    <div class="me-3">
                                        <div class="p-3 bg-light rounded">
                                            <i class="fas {{ $template['icon'] }} fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-1">{{ $template['name'] }}</h5>
                                        <p class="text-muted small mb-0">Тип: {{ $template['type'] }}</p>
                                    </div>
                                </div>
                                <p class="card-text">{{ $template['description'] }}</p>
                            </div>
                            <div class="card-footer bg-transparent border-top-0">
                                <div class="d-grid">
                                    <a href="{{ route('partner.excel-templates.estimate', $template['type']) }}" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-download me-1"></i>Скачать шаблон
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <i class="fas fa-info-circle me-1"></i> Информация
        </div>
        <div class="card-body">
            <h5 class="card-title">Особенности работы с шаблонами</h5>
            <p class="card-text">Все шаблоны содержат предварительно настроенные формулы для автоматического расчета стоимости работ и материалов.</p>
            
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="mb-3">
                        <h6><i class="fas fa-calculator text-primary me-2"></i>Автоматический расчет</h6>
                        <p class="text-muted small">Стоимость работ и материалов рассчитывается автоматически на основе количества, цены, наценки и скидки.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <h6><i class="fas fa-file-upload text-primary me-2"></i>Импорт в систему</h6>
                        <p class="text-muted small">После заполнения шаблона вы можете импортировать его в систему для дальнейшего использования.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <h6><i class="fas fa-edit text-primary me-2"></i>Редактирование онлайн</h6>
                        <p class="text-muted small">Вы также можете создать смету в системе и редактировать её онлайн без скачивания файла.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
