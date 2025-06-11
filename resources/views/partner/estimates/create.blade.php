@extends('layouts.app')

@section('content')
<!-- Добавляем библиотеки для работы с Excel в браузере -->
<link href="https://cdn.jsdelivr.net/npm/handsontable@9.0.2/dist/handsontable.full.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/handsontable@9.0.2/dist/handsontable.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <h1 class="h3 mb-2 mb-md-0">Создание сметы</h1>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('partner.estimates.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Назад к списку
            </a>
        </div>
    </div>
    
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <!-- Левая колонка с основной информацией -->
        <div class="col-md-4 mb-4">
            <!-- Карточка с основной информацией -->
            <div class="card mb-4">
                <div class="card-header">Основная информация</div>
                <div class="card-body">
                    <form action="{{ route('partner.estimates.store') }}" method="POST" id="estimateForm">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Название сметы <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="project_id" class="form-label">Объект</label>
                            <select class="form-select @error('project_id') is-invalid @enderror" id="project_id" name="project_id">
                                <option value="">Выберите объект</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                        {{ $project->client_name }} ({{ $project->address }})
                                    </option>
                                @endforeach
                            </select>
                            @error('project_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="type" class="form-label">Тип сметы <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="main" {{ old('type', 'main') == 'main' ? 'selected' : '' }}>Основная смета (Работы)</option>
                                <option value="additional" {{ old('status') == 'additional' ? 'selected' : '' }}>Дополнительная смета</option>
                                <option value="materials" {{ old('status') == 'materials' ? 'selected' : '' }}>Смета по материалам</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Статус</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Черновик</option>
                                <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>На рассмотрении</option>
                                <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Утверждена</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Примечания</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Скрытое поле для сохранения данных Excel -->
                        <input type="hidden" name="excel_data" id="excelDataInput">
                        
                        <!-- Кнопка отправки формы -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="button" id="submitBtn" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Создать смету
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Блок информации о шаблонах -->
            <div class="card">
                <div class="card-header">Информация</div>
                <div class="card-body">
                    <p class="mb-2">После создания сметы вы сможете:</p>
                    <ul class="mb-0">
                        <li>Редактировать данные в интерактивном Excel-редакторе</li>
                        <li>Скачать смету в формате Excel</li>
                        <li>Загрузить собственный файл сметы</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработчик кнопки создания сметы
    document.getElementById('submitBtn').addEventListener('click', function() {
        // Проверка валидации формы
        const form = document.getElementById('estimateForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        // Обновление названия сметы на основе выбранного типа
        const typeSelector = document.getElementById('type');
        const nameInput = document.getElementById('name');
        
        // Если имя сметы не было задано вручную, устанавливаем его на основе типа
        if (!nameInput.value.trim()) {
            switch (typeSelector.value) {
                case 'main':
                    nameInput.value = 'Работы | Смета производства работ 2025';
                    break;
                case 'additional':
                    nameInput.value = 'Дополнительная смета';
                    break;
                case 'materials':
                    nameInput.value = 'Материалы | Черновые материалы 2025';
                    break;
            }
        }
        
        // Отправляем форму для создания сметы
        // Контроллер создаст смету и скопирует шаблон Excel
        form.submit();
    });
    
    // Автоматическое обновление названия при изменении типа сметы
    document.getElementById('type').addEventListener('change', function() {
        const nameInput = document.getElementById('name');
        // Обновляем название только если пользователь еще не ввел своё
        if (!nameInput.dataset.userModified) {
            switch (this.value) {
                case 'main':
                    nameInput.value = 'Работы | Смета производства работ 2025';
                    break;
                case 'additional':
                    nameInput.value = 'Дополнительная смета';
                    break;
                case 'materials':
                    nameInput.value = 'Материалы | Черновые материалы 2025';
                    break;
            }
        }
    });
    
    // Отслеживаем изменения в поле названия
    document.getElementById('name').addEventListener('input', function() {
        if (this.value.trim() !== '') {
            this.dataset.userModified = 'true';
        } else {
            delete this.dataset.userModified;
        }
    });
});
</script>

@endsection
