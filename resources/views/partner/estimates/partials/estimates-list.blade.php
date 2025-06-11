<table class="table table-hover table-striped align-middle mb-0">
    <thead class="table-light">
        <tr>
            <th scope="col" width="5%">ID</th>
            <th scope="col" width="25%">Название</th>
            <th scope="col" width="20%">Объект</th>
            <th scope="col" width="10%">Тип</th>
            <th scope="col" width="10%">Сумма, ₽</th>
            <th scope="col" width="10%">Статус</th>
            <th scope="col" width="10%">Дата</th>
            <th scope="col" width="10%">Действия</th>
        </tr>
    </thead>
    <tbody>
        @forelse($estimates as $estimate)
            <tr>
                <td>{{ $estimate->id }}</td>
                <td>
                    <a href="{{ route('partner.estimates.edit', $estimate) }}" class="text-decoration-none fw-bold">
                        {{ $estimate->name }}
                    </a>
                    @if($estimate->description)
                        <p class="text-muted small mb-0">{{ Str::limit($estimate->description, 50) }}</p>
                    @endif
                </td>
                <td>
                    @if($estimate->project)
                        <a href="{{ route('partner.projects.show', $estimate->project) }}" class="text-decoration-none">
                            {{ $estimate->project->address }}
                        </a>
                        <p class="text-muted small mb-0">{{ $estimate->project->client_name }}</p>
                    @else
                        <span class="text-muted">Не привязана</span>
                    @endif
                </td>
                <td>
                    @switch($estimate->type)
                        @case('main')
                            <span class="badge bg-primary">Основная</span>
                            @break
                        @case('additional')
                            <span class="badge bg-info">Дополнительная</span>
                            @break
                        @case('materials')
                            <span class="badge bg-warning text-dark">Материалы</span>
                            @break
                        @default
                            <span class="badge bg-secondary">{{ $estimate->type }}</span>
                    @endswitch
                </td>
                <td class="text-end">
                    @if($estimate->total_amount > 0)
                        {{ number_format($estimate->total_amount, 2, '.', ' ') }}
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @switch($estimate->status)
                        @case('draft')
                            <span class="badge bg-secondary">Черновик</span>
                            @break
                        @case('pending')
                            <span class="badge bg-warning text-dark">На рассмотрении</span>
                            @break
                        @case('approved')
                            <span class="badge bg-success">Утверждена</span>
                            @break
                        @case('rejected')
                            <span class="badge bg-danger">Отклонена</span>
                            @break
                        @default
                            <span class="badge bg-secondary">{{ $estimate->status }}</span>
                    @endswitch
                </td>
                <td class="date-format">
                    {{ $estimate->updated_at }}
                </td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                type="button" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false">
                            <i class="fas fa-cogs me-1"></i> Действия
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('partner.estimates.edit', $estimate->id) }}">
                                    <i class="fas fa-edit me-2"></i>Редактировать
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('partner.estimates.export', $estimate->id) }}">
                                    <i class="fas fa-download me-2"></i>Скачать Excel
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('partner.estimates.show', $estimate->id) }}">
                                    <i class="fas fa-eye me-2"></i>Просмотреть
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('partner.estimates.destroy', $estimate->id) }}" method="POST" class="d-inline delete-form" data-name="{{ $estimate->name }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-trash me-2"></i>Удалить
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center py-4">
                    <div class="d-flex flex-column align-items-center">
                        <i class="fas fa-file-excel fa-3x text-muted mb-3"></i>
                        <h5>Нет сохраненных смет</h5>
                        <p class="text-muted">Создайте свою первую смету, нажав кнопку "Создать смету"</p>
                        <a href="{{ route('partner.estimates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-1"></i>Создать смету
                        </a>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Подтверждение удаления сметы
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = this.dataset.name;
            
            if (confirm(`Вы уверены, что хотите удалить смету "${name}"? Это действие нельзя отменить.`)) {
                this.submit();
            }
        });
    });
});
</script>
