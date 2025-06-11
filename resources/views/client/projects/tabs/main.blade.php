<div class="row">
    <div class="col-12 col-md-6 mb-4 mb-md-0">
        <h5 class="mb-3">Основная информация</h5>
        <div class="table-responsive-mobile">
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <th width="30%">Клиент:</th>
                        <td>{{ $project->client_name }}</td>
                    </tr>
                    <tr>
                        <th>Адрес:</th>
                        <td>{{ $project->address }}{{ $project->apartment_number ? ', кв. ' . $project->apartment_number : '' }}</td>
                    </tr>
                    <tr>
                        <th>Площадь объекта:</th>
                        <td>{{ $project->area ?? '-' }} м²</td>
                    </tr>
                    <tr>
                        <th>Тип объекта:</th>
                        <td>{{ $project->object_type ?? 'Не указан' }}</td>
                    </tr>
                    <tr>
                        <th>Тип работ:</th>
                        <td>{{ $project->work_type_text }}</td>
                    </tr>
                    <tr>
                        <th>Филиал:</th>
                        <td>{{ $project->branch ?? 'Не указан' }}</td>
                    </tr>
                    <tr>
                        <th>Статус проекта:</th>
                        <td>
                            <span class="badge {{ $project->status == 'active' ? 'bg-success' : ($project->status == 'paused' ? 'bg-warning' : ($project->status == 'completed' ? 'bg-info' : 'bg-secondary')) }}">
                                {{ $project->status == 'active' ? 'Активен' : ($project->status == 'paused' ? 'Приостановлен' : ($project->status == 'completed' ? 'Завершен' : 'Отменен')) }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="col-12 col-md-6">
        <h5 class="mb-3">Дополнительная информация</h5>
        <div class="mb-4">
            <h6>Ссылки:</h6>
            <div class="mb-2">
                <strong>IP камера:</strong> 
                @if($project->camera_link)
                    <a href="{{ $project->camera_link }}" target="_blank" class="d-inline-block text-truncate" style="max-width: 100%;">{{ $project->camera_link }}</a>
                @else
                    <span class="text-muted">Не указана</span>
                @endif
            </div>
            <div class="mb-2">
                <strong>Линейный график:</strong> 
                @if($project->schedule_link)
                    <a href="{{ $project->schedule_link }}" target="_blank" class="d-inline-block text-truncate" style="max-width: 100%;">{{ $project->schedule_link }}</a>
                @else
                    <span class="text-muted">Не указан</span>
                @endif
            </div>
        </div>
        
        <div>
            <h6>Информация о договоре:</h6>
            <div class="table-responsive-mobile">
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <th width="40%">Дата договора:</th>
                            <td>{{ $project->contract_date ? $project->contract_date->format('d.m.Y') : 'Не указана' }}</td>
                        </tr>
                        <tr>
                            <th>Номер договора:</th>
                            <td>{{ $project->contract_number ?? 'Не указан' }}</td>
                        </tr>
                        <tr>
                            <th>Дата начала работ:</th>
                            <td>{{ $project->work_start_date ? $project->work_start_date->format('d.m.Y') : 'Не указана' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
