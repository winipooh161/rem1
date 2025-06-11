@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Информация о пользователе') }}</span>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> {{ __('Назад к списку') }}
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="{{ $user->getAvatarUrl() }}" alt="{{ $user->name }}" class="rounded-circle" width="120" height="120" style="object-fit: cover;">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th style="width: 30%">ID:</th>
                                    <td>{{ $user->id }}</td>
                                </tr>
                                <tr>
                                    <th>Имя:</th>
                                    <td>{{ $user->name }}</td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td>{{ $user->email }}</td>
                                </tr>
                                <tr>
                                    <th>Роль:</th>
                                    <td>
                                        <span class="badge bg-{{ $user->role == 'admin' ? 'danger' : ($user->role == 'partner' ? 'primary' : 'secondary') }}">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Дата регистрации:</th>
                                    <td>{{ $user->created_at->format('d.m.Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Последнее обновление:</th>
                                    <td>{{ $user->updated_at->format('d.m.Y H:i') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary me-2">
                            <i class="fas fa-edit me-1"></i> {{ __('Редактировать') }}
                        </a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить этого пользователя?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i> {{ __('Удалить') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
