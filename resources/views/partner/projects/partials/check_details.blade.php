<div class="check-details">
    @if(isset($details['title']))
        <h5 class="mb-3">{{ $details['title'] }}</h5>
    @else
        <h5 class="mb-3">Проверка #{{ $check_id }}</h5>
    @endif

    @if(!empty($details['checkboxes']))
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Пункты проверки</h6>
            </div>
            <div class="card-body">
                <div class="checkbox-list">
                    @foreach($details['checkboxes'] as $checkbox)
                        <div class="form-check mb-2">
                            <input class="form-check-input check-item-checkbox" 
                                   type="checkbox" 
                                   id="check-{{ $checkbox['id'] }}" 
                                   data-id="{{ $checkbox['id'] }}"
                                   data-category="{{ $check_id }}"
                                   {{ isset($checkbox['checked']) && $checkbox['checked'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="check-{{ $checkbox['id'] }}">
                                {{ $checkbox['text'] }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if(!empty($details['photos']))
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Фото документация</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($details['photos'] as $photo)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input check-item-checkbox" 
                                               type="checkbox" 
                                               id="photo-{{ isset($photo['id']) ? $photo['id'] : 'item-'.$loop->index }}" 
                                               data-id="{{ isset($photo['id']) ? $photo['id'] : 'item-'.$loop->index }}"
                                               data-category="{{ $check_id }}"
                                               {{ isset($photo['checked']) && $photo['checked'] ? 'checked' : '' }}>
                                        <label class="form-check-label" for="photo-{{ isset($photo['id']) ? $photo['id'] : 'item-'.$loop->index }}">
                                            {{ $photo['caption'] }}
                                        </label>
                                    </div>
                                    
                                    @if(isset($photo['image']))
                                        <div class="mt-2">
                                            <img src="{{ $photo['image'] }}" class="img-fluid rounded" alt="{{ $photo['caption'] }}">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">Комментарии</h6>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="comment{{ $check_id }}" class="form-label">Комментарий к проверке</label>
                <textarea class="form-control" id="comment{{ $check_id }}" rows="3">{{ $details['comment'] ?? '' }}</textarea>
            </div>
        </div>
    </div>
</div>

<style>
.check-details .card {
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.check-details .card-header {
    background-color: rgba(0,0,0,0.03);
}
@media (max-width: 768px) {
    .check-details .card-body {
        padding: 1rem;
    }
}
</style>
