@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">{{ __('Панель управления администратора') }}</div>

                <div class="card-body">
                    <div class="alert alert-success" role="alert">
                        Вы вошли как администратор!
                    </div>
                    
                    <div class="list-group mt-4">
                        <a href="#" class="list-group-item list-group-item-action">
                            Управление пользователями
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            Управление контентом
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            Настройки сайта
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
