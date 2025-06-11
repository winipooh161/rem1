<!-- Карточка с основной информацией -->
<div class="card mb-4">
    <div class="card-header">Основная информация</div>
    <div class="card-body">
        <form action="{{ isset($estimate) ? route('partner.estimates.update', $estimate) : route('partner.estimates.store') }}" 
              method="POST" id="estimateForm">
            @csrf
            @if(isset($estimate))
                @method('PUT')
                <!-- Добавляем скрытые поля для идентификации сметы -->
                <input type="hidden" name="estimate_id" value="{{ $estimate->id }}">
                <!-- Добавляем метатег с URL для сохранения Excel -->
                <meta name="excel-save-url" content="{{ route('partner.estimates.saveExcel', $estimate) }}">
            @endif
            
            <div class="mb-3">
                <label for="name" class="form-label">Название сметы <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" 
                       value="{{ $estimate->name ?? old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="mb-3">
                <label for="project_id" class="form-label">Объект</label>
                <select class="form-select @error('project_id') is-invalid @enderror" id="project_id" name="project_id">
                    <option value="">Выберите объект</option>
                    @foreach($projects ?? [] as $project)
                        <option value="{{ $project->id }}" 
                                {{ (isset($estimate) && $estimate->project_id == $project->id) || old('project_id') == $project->id ? 'selected' : '' }}>
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
                    <option value="main" {{ (isset($estimate) && $estimate->type == 'main') || old('type', 'main') == 'main' ? 'selected' : '' }}>
                        Основная смета (Работы)
                    </option>
                    <option value="additional" {{ (isset($estimate) && $estimate->type == 'additional') || old('type') == 'additional' ? 'selected' : '' }}>
                        Дополнительная смета
                    </option>
                    <option value="materials" {{ (isset($estimate) && $estimate->type == 'materials') || old('type') == 'materials' ? 'selected' : '' }}>
                        Смета по материалам
                    </option>
                </select>
                @error('type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="mb-3">
                <label for="status" class="form-label">Статус</label>
                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                    <option value="draft" {{ (isset($estimate) && $estimate->status == 'draft') || old('status', 'draft') == 'draft' ? 'selected' : '' }}>
                        Черновик
                    </option>
                    <option value="pending" {{ (isset($estimate) && $estimate->status == 'pending') || old('status') == 'pending' ? 'selected' : '' }}>
                        На рассмотрении
                    </option>
                    <option value="approved" {{ (isset($estimate) && $estimate->status == 'approved') || old('status') == 'approved' ? 'selected' : '' }}>
                        Утверждена
                    </option>
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Примечания</label>
                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ $estimate->description ?? old('notes') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <!-- Кнопки управления -->
            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('partner.estimates.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>К списку смет
                </a>
                
                <div>
                    <button type="button" id="saveExcelBtn" class="btn btn-primary me-2">
                        <i class="fas fa-save me-1"></i>Сохранить
                    </button>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>Обновить информацию
                    </button>
                </div>
            </div>
            
            <!-- Скрытое поле для сохранения данных Excel -->
            <input type="hidden" name="excel_data" id="excelDataInput">
        </form>
    </div>
</div>


</div>
<!-- Скрипты для работы с формой и файлом -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация выпадающего меню при загрузке страницы
    const excelDropdownBtn = document.getElementById('excelDropdownBtn');
    if (excelDropdownBtn && typeof bootstrap !== 'undefined') {
        new bootstrap.Dropdown(excelDropdownBtn);
    }
    
    // Обработка формы загрузки файла
    const uploadForm = document.getElementById('uploadExcelForm');
    if (uploadForm) {
        const fileInput = document.getElementById('excelFile');
        
        // Автоматическая отправка формы при выборе файла
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                uploadForm.submit();
            }
        });
    }
});
</script>
