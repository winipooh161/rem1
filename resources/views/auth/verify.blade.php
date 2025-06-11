@extends('layouts.auth')

@section('content')
<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Левая колонка с формой -->
        <div class="col-md-6 bg-white">
            <div class="verify-form-container d-flex align-items-center py-5">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-10 col-xl-8 mx-auto">
                            <h3 class="display-6 mb-4">{{ __('Подтверждение номера телефона') }}</h3>
                            <div class="card border-0 shadow rounded-3">
                                <div class="card-body p-4 p-sm-5">
                                    @if (session('resent'))
                                        <div class="alert alert-success" role="alert">
                                            {{ __('Новый код подтверждения был отправлен на ваш номер телефона.') }}
                                        </div>
                                    @endif

                                    <p class="mb-4">{{ __('Прежде чем продолжить, пожалуйста, проверьте код подтверждения на вашем телефоне.') }}</p>
                                    <p class="mb-4">{{ __('Если вы не получили код') }}</p>

                                    <div class="d-grid gap-2">
                                        <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-block">
                                                {{ __('Отправить код повторно') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Правая колонка с фоновым изображением -->
        <div class="col-md-6 d-none d-md-block p-0">
            <div class="bg-image h-100" style="background-image: url('https://images.unsplash.com/photo-1603712227287-c7b0859ce00a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1287&q=80'); background-size: cover; background-position: center;"></div>
        </div>
    </div>
</div>
<style>
.verify-form-container {
    min-height: calc(100vh - 72px);
}
.bg-image {
    min-height: calc(100vh - 72px);
}
</style>
@endsection
