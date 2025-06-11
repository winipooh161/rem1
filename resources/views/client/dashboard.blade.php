@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Личный кабинет клиента</h1>
            <p class="text-muted">Добро пожаловать, {{ Auth::user()->name }}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Всего объектов</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $projects->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Активные объекты</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activeProjects }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hammer fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Завершенные объекты</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $completedProjects }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Мои объекты</h6>
                    <a href="{{ route('client.projects.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-list me-1"></i>Все объекты
                    </a>
                </div>
                <div class="card-body">
                    @if($projects->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">У вас пока нет объектов</h5>
                            <p>Объекты появятся здесь, когда партнер создаст их с вашим номером телефона</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Название</th>
                                        <th>Адрес</th>
                                        <th>Статус</th>
                                        <th>Дата обновления</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($projects->take(5) as $project)
                                        <tr>
                                            <td>{{ $project->client_name }}</td>
                                            <td>{{ $project->address }}</td>
                                            <td>
                                                <span class="badge {{ $project->status == 'active' ? 'bg-success' : ($project->status == 'paused' ? 'bg-warning' : ($project->status == 'completed' ? 'bg-info' : 'bg-secondary')) }}">
                                                    {{ ucfirst($project->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $project->updated_at->format('d.m.Y') }}</td>
                                            <td>
                                                <a href="{{ route('client.projects.show', $project) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
