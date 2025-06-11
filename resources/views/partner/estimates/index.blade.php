@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2">Сметы</h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="{{ route('partner.estimates.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i>Создать смету
            </a>
        </div>
    </div>

    <!-- Панель фильтров -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i> Фильтры
        </div>
        <div class="card-body">
            <form method="GET" id="filter-form" class="row">
                <!-- Фильтр по объекту -->
                <div class="col-md-3 mb-3">
                    <label for="project_id" class="form-label">Объект</label>
                    <select class="form-select" id="project_id" name="project_id">
                        <option value="">Все объекты</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                {{ $project->client_name }} ({{ $project->address }})
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Фильтр по статусу -->
                <div class="col-md-2 mb-3">
                    <label for="status" class="form-label">Статус</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Все статусы</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Черновик</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>На рассмотрении</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Утверждена</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Отклонена</option>
                    </select>
                </div>
                
                <!-- Поиск -->
                <div class="col-md-4 mb-3">
                    <label for="search" class="form-label">Поиск</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Название или описание" value="{{ request('search') }}">
                </div>
                
                <!-- Кнопки фильтра -->
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Применить
                    </button>
                    <a href="{{ route('partner.estimates.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Сбросить
                    </a>
                </div>
            </form>
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

    <!-- Блок со списком смет -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Список смет</h5>
            <div>
                <a href="{{ route('partner.estimates.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i>Создать смету
                </a>
            </div>
        </div>
        <div class="card-body">
            @include('partner.estimates.partials.estimates-list')
        </div>
    </div>

    <!-- Пагинация -->
    <div class="d-flex justify-content-center mt-4">
        {{ $estimates->links() }}
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Инициализация всех дропдаунов вручную
        if (typeof bootstrap !== 'undefined') {
            const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
            dropdownElementList.forEach(function(dropdownToggleEl) {
                new bootstrap.Dropdown(dropdownToggleEl);
            });
        }
        
        // Обработчик для кнопок удаления внутри дропдаунов
        const deleteForms = document.querySelectorAll('.delete-form');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const estimateName = this.getAttribute('data-name');
                
                if (confirm(`Вы действительно хотите удалить смету "${estimateName}"?`)) {
                    this.submit();
                }
            });
        });
        
        // Обеспечиваем корректную работу кнопок внутри дропдауна
        document.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (!this.classList.contains('delete-btn')) {
                    // Для всех кнопок кроме удаления, перейти по ссылке
                    if (this.tagName === 'A' && this.href) {
                        window.location.href = this.href;
                    }
                    // В противном случае событие будет обработано формой или другим обработчиком
                }
            });
        });
        
        // Фиксируем позиционирование дропдауна, чтобы предотвратить проблемы с кликами
        document.querySelectorAll('.dropdown').forEach(dropdown => {
            dropdown.addEventListener('show.bs.dropdown', function() {
                const dropdownMenu = this.querySelector('.dropdown-menu');
                if (dropdownMenu) {
                    dropdownMenu.style.position = 'absolute';
                    dropdownMenu.style.inset = 'auto auto auto auto';
                    dropdownMenu.style.transform = 'translate(-80%, 0)';
                }
            });
        });
    });
</script>
@endpush
