<h5 class="mb-3">Договор</h5>

<div class="contract-container mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-3">
        <h5>Договор и приложения</h5>
        <button type="button" class="btn btn-primary btn-sm mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#uploadContractModal">
            <i class="fas fa-upload me-1"></i>Загрузить документы
        </button>
    </div>
    
    @if($project->contractFiles->isEmpty())
        <div class="alert alert-info">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-2 fa-lg"></i>
                <div>
                    В этом разделе будут отображаться документы договора и приложения к нему. 
                    Нажмите на кнопку "Загрузить документы", чтобы добавить файлы договора.
                </div>
            </div>
        </div>
    @else
        <!-- Контейнер для файлов договора -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
            @foreach($project->contractFiles as $file)
                <div class="col">
                    <div class="card h-100 contract-file-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="file-icon me-3">
                                    <i class="far {{ $file->is_image ? 'fa-file-image' : 'fa-file-pdf' }} fa-2x text-primary"></i>
                                </div>
                                <div class="file-info">
                                    <h6 class="mb-0 text-truncate" title="{{ $file->original_name }}">
                                        {{ $file->original_name }}
                                    </h6>
                                    <small class="text-muted">{{ $file->created_at->format('d.m.Y H:i') }}</small>
                                </div>
                            </div>
                            
                            @if($file->description)
                                <p class="card-text small mb-3">{{ $file->description }}</p>
                            @endif
                            
                            <div class="d-flex justify-content-between">
                                <span class="badge bg-secondary">{{ $file->size_formatted }}</span>
                                <div>
                                    @if($file->is_image)
                                        <a href="{{ $file->file_url }}" class="btn btn-sm btn-outline-info me-1" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('partner.project-files.download', $file->id) }}" class="btn btn-sm btn-outline-primary me-1">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <form action="{{ route('partner.project-files.destroy', $file->id) }}" method="POST" class="d-inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Вы уверены?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Модальное окно для загрузки договора -->
<div class="modal fade" id="uploadContractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Загрузка документов договора</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('partner.project-files.store', $project) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="file_type" value="contract">
                    
                    <div class="mb-3">
                        <label for="contractFile" class="form-label">Выберите файл</label>
                        <input type="file" class="form-control" id="contractFile" name="file" required>
                        <div class="form-text">Поддерживаются все форматы файлов: PDF, DOC, DOCX, JPG, PNG, TIFF, XLS, XLSX и др. без ограничений по размеру.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="documentType" class="form-label">Тип документа</label>
                        <select class="form-select" id="documentType" name="document_type">
                            <option value="contract">Договор</option>
                            <option value="annex">Приложение к договору</option>
                            <option value="act">Акт приёмки работ</option>
                            <option value="other">Другое</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fileDescription" class="form-label">Описание (необязательно)</label>
                        <textarea class="form-control" id="fileDescription" name="description" rows="3" placeholder="Добавьте краткое описание файла"></textarea>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Загрузить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Адаптивные стили для мобильных устройств */
@media (max-width: 576px) {
    .contract-file-card .card-body {
        padding: 0.75rem;
    }
    
    .contract-file-card .file-icon i {
        font-size: 1.5rem; /* Уменьшаем размер иконки */
    }
    
    .contract-file-card h6 {
        font-size: 0.9rem;
        max-width: 170px; /* Ограничиваем ширину, чтобы текст обрезался */
    }
    
    .contract-file-card .btn-sm {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
    }
    
    .modal-footer {
        flex-direction: column;
    }
    
    .modal-footer .btn {
        width: 100%;
        margin: 0.25rem 0;
    }
}
</style>
