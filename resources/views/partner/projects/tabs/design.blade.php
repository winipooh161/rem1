<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-3">
        <h5>Дизайн-проект</h5>
        <button type="button" class="btn btn-primary btn-sm mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#uploadDesignModal">
            <i class="fas fa-upload me-1"></i>Загрузить файлы дизайна
        </button>
    </div>

    @if($project->designFiles->isEmpty())
        <div class="alert alert-info">
            <div class="d-flex">
                <i class="fas fa-info-circle me-2 fa-lg"></i>
                <div>
                    В этом разделе будут отображаться файлы дизайн-проекта. 
                    Нажмите на кнопку "Загрузить файлы дизайна", чтобы добавить файлы визуализации и дизайна.
                </div>
            </div>
        </div>
    @else
        <!-- Категории дизайн-файлов на вкладках -->
        <ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto hide-scroll" id="designTabs" role="tablist">
            @php
                $designCategories = $project->designFiles->pluck('document_type')->unique();
                $firstCategory = true;
            @endphp
            
            @foreach($designCategories as $category)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $firstCategory ? 'active' : '' }}" 
                            id="design-tab-{{ $loop->index }}" 
                            data-bs-toggle="tab" 
                            data-bs-target="#design-{{ $loop->index }}" 
                            type="button" 
                            role="tab">
                        {{ ucfirst($category) }}
                    </button>
                </li>
                @php $firstCategory = false; @endphp
            @endforeach
        </ul>
        
        <!-- Содержимое вкладок с дизайн-файлами -->
        <div class="tab-content" id="designTabContent">
            @php $firstCategory = true; @endphp
            
            @foreach($designCategories as $category)
                <div class="tab-pane fade {{ $firstCategory ? 'show active' : '' }}" 
                     id="design-{{ $loop->index }}" 
                     role="tabpanel" 
                     aria-labelledby="design-tab-{{ $loop->index }}">
                    
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
                        @foreach($project->designFiles->where('document_type', $category) as $file)
                            <div class="col">
                                <div class="card h-100 design-file-card">
                                    @if($file->is_image)
                                        <div class="card-img-top design-preview">
                                            <a href="{{ $file->file_url }}" target="_blank" data-lightbox="design-{{ $category }}" data-title="{{ $file->original_name }}">
                                                <img src="{{ $file->file_url }}" class="img-fluid" alt="{{ $file->original_name }}">
                                            </a>
                                        </div>
                                    @else
                                        <div class="card-img-top design-preview d-flex align-items-center justify-content-center bg-light">
                                            <i class="{{ $file->file_icon }} fa-3x text-secondary"></i>
                                        </div>
                                    @endif
                                    
                                    <div class="card-body p-3">
                                        <h6 class="card-title text-truncate" title="{{ $file->original_name }}">
                                            {{ $file->original_name }}
                                        </h6>
                                        
                                        <p class="card-text small text-muted mb-2">
                                            <span>{{ $file->size_formatted }}</span>
                                            <span class="mx-1">•</span>
                                            <span>{{ $file->created_at->format('d.m.Y') }}</span>
                                        </p>
                                        
                                        @if($file->description)
                                            <p class="card-text small mb-3">{{ $file->description }}</p>
                                        @endif
                                        
                                        <div class="d-flex">
                                            <a href="{{ route('partner.project-files.download', $file->id) }}" class="btn btn-sm btn-outline-primary me-2">
                                                <i class="fas fa-download me-1"></i>Скачать
                                            </a>
                                            <form action="{{ route('partner.project-files.destroy', $file->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Вы уверены?')">
                                                    <i class="fas fa-trash me-1"></i>Удалить
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @php $firstCategory = false; @endphp
            @endforeach
        </div>
    @endif
</div>

<!-- Модальное окно загрузки файлов дизайна -->
<div class="modal fade" id="uploadDesignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Загрузка файлов дизайна</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('partner.project-files.store', $project) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="file_type" value="design">
                    
                    <div class="mb-3">
                        <label for="designFile" class="form-label">Выберите файл</label>
                        <input type="file" class="form-control" id="designFile" name="file" required>
                        <div class="form-text">Поддерживаются все форматы файлов: JPG, PNG, PDF, TIFF, SVG, PSD, AI, WEBP, DWG, RAW и др. без ограничений по размеру.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="designType" class="form-label">Категория файла</label>
                        <select class="form-select" id="designType" name="document_type">
                            <option value="visualization">Визуализация</option>
                            <option value="drawings">Чертежи</option>
                            <option value="3d">3D модели</option>
                            <option value="materials">Материалы</option>
                            <option value="other">Другое</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="designDescription" class="form-label">Описание (необязательно)</label>
                        <textarea class="form-control" id="designDescription" name="description" rows="3" placeholder="Добавьте краткое описание файла"></textarea>
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

<style>
/* Стили для адаптивного отображения на мобильных */
.design-preview {
    height: 160px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.design-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

@media (max-width: 576px) {
    .design-file-card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .design-preview {
        height: 140px; /* Уменьшаем высоту для мобильных */
    }
    
    .design-file-card .card-body {
        padding: 0.75rem;
    }
    
    .design-file-card h6 {
        font-size: 0.9rem;
    }
    
    .design-file-card .btn-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.4rem;
    }
    
    /* Улучшенные вкладки для мобильных устройств */
    #designTabs {
        padding-bottom: 2px;
    }
    
    #designTabs .nav-link {
        font-size: 0.85rem;
        padding: 0.5rem 0.75rem;
    }
}
</style>
