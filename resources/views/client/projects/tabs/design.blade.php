<div class="mb-4">
    <h5 class="mb-3">Дизайн-проект</h5>

    @if($project->designFiles->isEmpty())
        <div class="alert alert-info">
            <div class="d-flex">
                <i class="fas fa-info-circle me-2 fa-lg"></i>
                <div>
                    Файлы дизайн-проекта еще не загружены.
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
                                            <a href="{{ route('partner.project-files.download', $file->id) }}" class="btn btn-sm btn-outline-primary w-100">
                                                <i class="fas fa-download me-1"></i>Скачать
                                            </a>
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
