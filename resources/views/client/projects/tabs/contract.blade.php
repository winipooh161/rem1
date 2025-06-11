<h5 class="mb-3">Договор</h5>

<div class="contract-container mb-4">
    @if($project->contractFiles->isEmpty())
        <div class="alert alert-info">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-2 fa-lg"></i>
                <div>
                    В этом разделе будут отображаться документы договора и приложения к нему.
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
                                    <a href="{{ route('partner.project-files.download', $file->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
/* Адаптивные стили для мобильных устройств */
@media (max-width: 576px) {
    .contract-file-card .card-body {
        padding: 0.75rem;
    }
    
    .contract-file-card .file-icon i {
        font-size: 1.5rem;
    }
    
    .contract-file-card h6 {
        font-size: 0.9rem;
        max-width: 170px;
    }
    
    .contract-file-card .btn-sm {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
    }
}
</style>
