<h5 class="mb-3">Фотоотчет по объекту</h5>
    
@if($project->photos->isEmpty())
    <div class="alert alert-info">
        Пока не загружено ни одной фотографии.
    </div>
@else
    <ul class="nav nav-tabs mb-3" id="photosTab" role="tablist">
        @foreach($project->getPhotoCategories() as $index => $category)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $index === 0 ? 'active' : '' }}" 
                        id="photo-tab-{{ $index }}"
                        data-bs-toggle="tab"
                        data-bs-target="#photo-content-{{ $index }}"
                        type="button"
                        role="tab"
                        aria-controls="photo-content-{{ $index }}"
                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                    {{ $category }}
                </button>
            </li>
        @endforeach
    </ul>
    
    <div class="tab-content" id="photosTabContent">
        @foreach($project->getPhotoCategories() as $index => $category)
            <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                 id="photo-content-{{ $index }}"
                 role="tabpanel"
                 aria-labelledby="photo-tab-{{ $index }}">
                
                <div class="row g-3">
                    @foreach($project->getPhotosByCategory($category) as $photo)
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <a href="{{ $photo->photo_url }}" target="_blank" class="photo-link" data-lightbox="category-{{ $index }}">
                                    <img src="{{ $photo->photo_url }}" class="card-img-top img-fluid" style="height: 200px; object-fit: cover;" alt="Фото объекта">
                                </a>
                                <div class="card-body">
                                    <p class="card-text small text-muted mb-1">
                                        {{ $photo->created_at->format('d.m.Y H:i') }}
                                    </p>
                                    @if($photo->comment)
                                        <p class="card-text">{{ $photo->comment }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif

<style>
/* Улучшения для мобильных устройств */
@media (max-width: 576px) {
    .photo-link img {
        height: 150px !important;
    }
    
    #photosTab {
        overflow-x: auto;
        flex-wrap: nowrap;
        white-space: nowrap;
    }
    
    #photosTab .nav-link {
        font-size: 0.9rem;
        padding: 0.5rem 0.75rem;
    }
}
</style>
