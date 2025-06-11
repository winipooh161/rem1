<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-3">
        <h5>Прочие файлы</h5>
        <button type="button" class="btn btn-primary btn-sm mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#uploadOtherModal">
            <i class="fas fa-upload me-1"></i>Загрузить файлы
        </button>
    </div>

    @if($project->otherFiles->isEmpty())
        <div class="alert alert-info">
            <div class="d-flex">
                <i class="fas fa-info-circle me-2 fa-lg"></i>
                <div>
                    В этом разделе будет отображаться дополнительная информация по объекту.
                    Загрузите любые файлы, которые не подходят для других разделов.
                </div>
            </div>
        </div>
    @else
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 files-container">
            @foreach($project->otherFiles as $file)
                <div class="col file-item">
                    <div class="card h-100 other-file-card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start mb-3">
                                <div class="file-icon me-3">
                                    <i class="{{ $file->file_icon }} fa-2x text-secondary"></i>
                                </div>
                                <div class="file-info flex-grow-1">
                                    <h6 class="mb-1 text-truncate" title="{{ $file->original_name }}">
                                        {{ $file->original_name }}
                                    </h6>
                                    <div class="small text-muted d-flex flex-wrap">
                                        <span class="me-2">{{ $file->size_formatted }}</span>
                                        <span>{{ $file->created_at->format('d.m.Y') }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            @if($file->description)
                                <p class="card-text small mb-3">{{ $file->description }}</p>
                            @endif
                            
                            <div class="d-flex justify-content-end">
                                @if($file->is_image)
                                    <a href="{{ $file->file_url }}" class="btn btn-sm btn-outline-info me-1" target="_blank" title="Просмотр">
                                        <i class="fas fa-eye me-1"></i><span class="d-none d-md-inline">Просмотр</span>
                                    </a>
                                @endif
                                <a href="{{ route('partner.project-files.download', $file->id) }}" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="fas fa-download me-1"></i><span class="d-none d-md-inline">Скачать</span>
                                </a>
                                <form action="{{ route('partner.project-files.destroy', $file->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Вы уверены?')">
                                        <i class="fas fa-trash me-1"></i><span class="d-none d-md-inline">Удалить</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Модальное окно загрузки прочих файлов -->
<div class="modal fade" id="uploadOtherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Загрузка файлов</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('partner.project-files.store', $project) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="file_type" value="other">
                    
                    <div class="mb-3">
                        <label for="otherFile" class="form-label">Выберите файл</label>
                        <input type="file" class="form-control" id="otherFile" name="file" required>
                        <div class="form-text">Поддерживаются все форматы файлов без ограничений по размеру.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fileDescription" class="form-label">Описание (необязательно)</label>
                        <textarea class="form-control" id="fileDescription" name="description" rows="3" placeholder="Добавьте краткое описание файла"></textarea>
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
@media (max-width: 576px) {
    .other-file-card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .other-file-card .file-icon i {
        font-size: 1.5rem;
    }
    
    .other-file-card h6 {
        font-size: 0.9rem;
    }
    
    .other-file-card .btn-sm {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
    }
    
    .alert {
        padding: 0.75rem 1rem;
    }
    
    .alert .fa-lg {
        font-size: 1.25rem;
    }
}
</style>
