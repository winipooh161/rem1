@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>{{ __('Мой профиль') }}</h4>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-4 mb-4 mb-md-0">
                            <div class="text-center">
                                <img src="{{ $user->getAvatarUrl() }}" alt="{{ $user->name }}" class="img-fluid rounded-circle img-thumbnail" style="width: 200px; height: 200px; object-fit: cover;">
                                <h4 class="mt-3">{{ $user->name }}</h4>
                                <div class="badge bg-secondary mb-3">{{ ucfirst($user->role) }}</div>
                                <div class="d-grid gap-2 mt-3">
                                    <a href="{{ route('profile.edit') }}" class="btn btn-primary">
                                        <i class="fas fa-edit me-2"></i>{{ __('Редактировать профиль') }}
                                    </a>
                                    <a href="{{ route('profile.change-password') }}" class="btn btn-outline-primary">
                                        <i class="fas fa-key me-2"></i>{{ __('Изменить пароль') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>{{ __('Контактная информация') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="text-muted">{{ __('Имя') }}:</label>
                                        <p class="lead">{{ $user->name }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted">{{ __('Номер телефона') }}:</label>
                                        <p class="lead">{{ $user->phone }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted">{{ __('Email') }}:</label>
                                        <p class="lead">{{ $user->email ?? 'Не указан' }}</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="text-muted">{{ __('Дата регистрации') }}:</label>
                                        <p class="lead">{{ $user->created_at->format('d.m.Y') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
