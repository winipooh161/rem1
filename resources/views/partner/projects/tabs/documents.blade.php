<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-3">
        <h5>Рабочая документация</h5>
        <button type="button" class="btn btn-primary btn-sm mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
            <i class="fas fa-upload me-1"></i>Загрузить документы
        </button>
    </div>

    @if($project->documentFiles->isEmpty())
        <div class="alert alert-info">
            <div class="d-flex">
                <i class="fas fa-info-circle me-2 fa-lg"></i>
                <div>
                    В этом разделе будет отображаться рабочая документация и документы по объекту. 
                    Нажмите на кнопку "Загрузить документы", чтобы добавить документы.
                </div>
            </div>
        </div>
    @else
        <!-- Фильтры по типам документов -->
        <div class="mb-3">
            <div class="document-filter btn-group flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-secondary active" data-filter="all">
                    Все
                </button>
                @php
                    $docTypes = $project->documentFiles->pluck('document_type')->unique();
                @endphp
                
                @foreach($docTypes as $type)
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-filter="{{ $type }}">
                        {{ ucfirst($type) }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Список документов -->
        <div class="documents-container">
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                @foreach($project->documentFiles as $file)
                    <div class="col document-item" data-type="{{ $file->document_type }}">
                        <div class="card h-100 document-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="document-icon me-3">
                                        <i class="far {{ $file->file_icon }} fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-title mb-0 text-truncate" title="{{ $file->original_name }}">
                                            {{ $file->original_name }}
                                        </h6>
                                        <div class="document-meta small text-muted">
                                            <span class="document-type">{{ ucfirst($file->document_type) }}</span>
                                            <span class="mx-1">•</span>
                                            <span class="document-date">{{ $file->created_at->format('d.m.Y') }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                @if($file->description)
                                    <p class="card-text small mb-3">{{ $file->description }}</p>
                                @endif
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-secondary">{{ $file->size_formatted }}</span>
                                    <div class="document-actions">
                                        @if($file->is_image)
                                            <a href="{{ $file->file_url }}" class="btn btn-sm btn-outline-info me-1" target="_blank" title="Просмотр">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('partner.project-files.download', $file->id) }}" class="btn btn-sm btn-outline-primary me-1" title="Скачать">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <form action="{{ route('partner.project-files.destroy', $file->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Вы уверены?')" title="Удалить">
                                                <i class="fas fa-trash"></i>
                                            </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<!-- Модальное окно загрузки документов -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Загрузка рабочей документации</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('partner.project-files.store', $project) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="file_type" value="document">
                    
                    <div class="mb-3">
                        <label for="documentFile" class="form-label">Выберите файл</label>
                        <input type="file" class="form-control" id="documentFile" name="file" required>
                        <div class="form-text">Поддерживаются все форматы файлов: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, TXT, ZIP, RAR и др. без ограничений по размеру.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="documentType" class="form-label">Тип документа</label>
                        <select class="form-select" id="documentType" name="document_type">
                            <option value="specifications">Спецификации</option>
                            <option value="instructions">Инструкции</option>
                            <option value="protocols">Протоколы</option>
                            <option value="technical">Техническая документация</option>
                            <option value="other">Другое</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="documentDescription" class="form-label">Описание (необязательно)</label>
                        <textarea class="form-control" id="documentDescription" name="description" rows="3" placeholder="Добавьте краткое описание документа"></textarea>
                    </div>
                    
                    <div class="d-flex flex-column flex-md-row justify-content-end">
                        <button type="button" class="btn btn-secondary mb-2 mb-md-0 me-md-2 w-100 w-md-auto" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary w-100 w-md-auto">Загрузить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Обработчики для фильтрации документов
        const filterButtons = document.querySelectorAll('.document-filter button');
        const documentItems = document.querySelectorAll('.document-item');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Снимаем active со всех кнопок
                filterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Добавляем active текущей кнопке
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                
                // Фильтруем документы
                documentItems.forEach(item => {
                    if (filter === 'all' || item.dataset.type === filter) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    });
</script>

<style>
/* Стили для адаптивного отображения на мобильных */
.document-filter {
    overflow-x: auto;
    white-space: nowrap;
    padding-bottom: 5px;
    margin-bottom: 10px;
}

.document-filter::-webkit-scrollbar {
    display: none;
}

@media (max-width: 576px) {
    .document-card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .document-card .card-body {
        padding: 0.75rem;
    }
    
    .document-card h6 {
        font-size: 0.9rem;
        max-width: 180px;
    }
    
    .document-card .document-icon i {
        font-size: 1.5rem;
    }
    
    .document-card .btn-sm {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
    }
    
    .document-filter .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    /* Улучшенный контейнер фильтров */
    .document-filter {
        display: flex;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}
</style>
