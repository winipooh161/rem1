<div class="mb-4">
    <h5 class="mb-3">Схемы и чертежи</h5>

    @if($project->schemeFiles->isEmpty())
        <div class="alert alert-info">
            В этом разделе будут отображаться схемы и чертежи по объекту.
        </div>
    @else
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 files-container">
            @foreach($project->schemeFiles as $file)
                <div class="col file-item" data-file-id="{{ $file->id }}">
                    <div class="card h-100">
                        @if($file->is_image)
                            <div class="card-img-top file-preview" style="height: 140px; background-image: url('{{ asset('storage/project_files/' . $project->id . '/' . $file->filename) }}'); background-size: cover; background-position: center;"></div>
                        @else
                            <div class="card-img-top text-center bg-light d-flex align-items-center justify-content-center" style="height: 140px;">
                                <i class="{{ $file->file_icon }} fa-3x text-secondary"></i>
                            </div>
                        @endif
                        <div class="card-body">
                            <h6 class="card-title text-truncate" title="{{ $file->original_name }}">{{ $file->original_name }}</h6>
                            <p class="card-text text-muted small mb-2">
                                <span>{{ number_format($file->size / 1024, 2) }} KB</span>
                                <span class="ms-2">{{ $file->created_at->format('d.m.Y H:i') }}</span>
                            </p>
                            <p class="card-text small text-muted">
                                {{ $file->description }}
                            </p>
                        </div>
                        <div class="card-footer">
                            <a href="{{ $file->download_url }}" class="btn btn-sm btn-outline-primary w-100" download>
                                <i class="fas fa-download me-1"></i>Скачать
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
@media (max-width: 576px) {
    .file-preview {
        height: 120px !important;
    }
    
    .file-item .card {
        margin-bottom: 10px;
    }
    
    .file-item .card-body {
        padding: 0.75rem;
    }
    
    .file-item h6 {
        font-size: 0.9rem;
    }
}
</style>
