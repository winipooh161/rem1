<h5 class="mb-3">Фотоотчет по объекту</h5>

<div class="mt-3">
    <form action="{{ route('partner.projects.photos.store', $project->id) }}" method="POST" enctype="multipart/form-data" id="photo-upload-form">
        @csrf
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="photoUpload" class="form-label">Загрузить новые фото</label>
                <input class="form-control" type="file" id="photoUpload" name="photos[]" accept="image/*" multiple required>
                <div class="form-text">Поддерживаемые форматы: JPG, JPEG, PNG, GIF, WEBP, BMP, TIFF, TIF, SVG, ICO, HEIC, HEIF и другие форматы изображений. Можно выбрать несколько файлов.</div>
            </div>
            <div class="col-md-6 mb-3">
                <label for="photoCategory" class="form-label">Категория работ</label>
                <select name="category" id="photoCategory" class="form-control" required>
                    <option value="">Выбрать</option>
                    @foreach($categories = app(\App\Http\Controllers\Partner\ProjectPhotoController::class)->getPhotoCategories() ?? [] as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label for="photoComment" class="form-label">Общий комментарий</label>
            <input type="text" class="form-control" id="photoComment" name="comment" placeholder="Добавьте общее описание для всех загружаемых фотографий (необязательно)">
        </div>
        
        <div class="mb-3">
            <div class="photo-preview-container row" id="photoPreviewContainer"></div>
        </div>
        
        <button type="submit" class="btn btn-primary" id="upload-photo-btn">
            <i class="fas fa-upload me-2"></i>Загрузить фотографии
        </button>
    </form>
</div>

<div class="mt-4">
    <h6>Загруженные фотографии</h6>
    
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
                                        <form action="{{ route('partner.project-photos.destroy', $photo->id) }}" method="POST" class="mt-2">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Вы уверены, что хотите удалить эту фотографию?')">
                                                <i class="fas fa-trash-alt"></i> Удалить
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const photoUpload = document.getElementById('photoUpload');
        const previewContainer = document.getElementById('photoPreviewContainer');
        const maxFiles = 20; // Максимальное количество файлов для загрузки за раз
        
        photoUpload.addEventListener('change', function() {
            previewContainer.innerHTML = '';
            
            // Проверка количества файлов
            if (this.files.length > maxFiles) {
                alert(`Вы можете загрузить не более ${maxFiles} фотографий за один раз`);
                this.value = '';
                return;
            }
            
            // Создание превью для каждого файла
            Array.from(this.files).forEach((file, index) => {
                // Создаем элемент для превью
                const previewCol = document.createElement('div');
                previewCol.className = 'col-6 col-md-3 mb-3';
                
                const previewCard = document.createElement('div');
                previewCard.className = 'card h-100';
                
                // Создаем превью изображения
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewCard.innerHTML = `
                        <img src="${e.target.result}" class="card-img-top" style="height: 120px; object-fit: cover;" alt="Превью фото">
                        <div class="card-body p-2">
                            <p class="card-text small mb-0 text-truncate">${file.name}</p>
                            <p class="card-text small text-muted">${(file.size / 1024).toFixed(1)} KB</p>
                        </div>
                    `;
                }
                reader.readAsDataURL(file);
                
                previewCol.appendChild(previewCard);
                previewContainer.appendChild(previewCol);
            });
        });
        
        // Валидация формы перед отправкой
        const form = document.getElementById('photo-upload-form');
        form.addEventListener('submit', function(event) {
            const files = photoUpload.files;
            const category = document.getElementById('photoCategory').value;
            
            if (files.length === 0) {
                event.preventDefault();
                alert('Пожалуйста, выберите хотя бы одну фотографию');
                return false;
            }
            
            if (!category) {
                event.preventDefault();
                alert('Пожалуйста, выберите категорию работ');
                return false;
            }
            
            // Показываем индикатор загрузки при отправке формы
            const uploadBtn = document.getElementById('upload-photo-btn');
            uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Загрузка...';
            uploadBtn.disabled = true;
        });
    });
</script>
@endpush

<style>
.photo-preview-container {
    margin-top: 10px;
}

/* Стили для улучшения отображения на мобильных устройствах */
@media (max-width: 576px) {
    .photo-preview-container .card {
        margin-bottom: 10px;
    }
    
    .photo-preview-container .card-img-top {
        height: 100px !important;
    }
}
</style>
