@extends('layouts.auth')

@section('content')
<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Левая колонка с формой -->
        <div class="col-md-6 bg-white">
            <div class="register-form-container d-flex align-items-center py-5">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-10 col-xl-8 mx-auto">
                            <h3 class="display-6 mb-4">{{ __('Регистрация') }}</h3>
                            <div class="card border-0 shadow rounded-3">
                                <div class="card-body p-4 p-sm-5">
                                    @if ($errors->has('db_error'))
                                    <div class="alert alert-danger mb-3">
                                        {{ $errors->first('db_error') }}
                                    </div>
                                    @endif
                                    <form method="POST" action="{{ route('register') }}" id="registerForm">
                                        @csrf

                                        <div class="mb-3">
                                            <label for="name" class="form-label">{{ __('Имя') }} <small class="text-muted">(только русские буквы)</small></label>
                                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                                                name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                                            @error('name')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="phone" class="form-label">{{ __('Номер телефона') }}</label>
                                            <input id="phone" type="tel" class="form-control maskphone @error('phone') is-invalid @enderror" 
                                                name="phone" value="{{ old('phone') }}" required autocomplete="phone"
                                                placeholder="+7 (___) ___-__-__">
                                            @error('phone')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="email" class="form-label">{{ __('Email адрес') }} ({{ __('Необязательно') }})</label>
                                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                                                name="email" value="{{ old('email') }}" autocomplete="email"
                                                placeholder="example@domain.com">
                                            @error('email')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="form-label">{{ __('Пароль') }}</label>
                                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                                                name="password" required autocomplete="new-password"
                                                placeholder="Введите пароль">
                                            @error('password')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="password-confirm" class="form-label">{{ __('Подтверждение пароля') }}</label>
                                            <input id="password-confirm" type="password" class="form-control" 
                                                name="password_confirmation" required autocomplete="new-password"
                                                placeholder="Повторите пароль">
                                        </div>

                                        <div class="d-grid gap-2 mb-3">
                                            <button type="submit" class="btn btn-primary">
                                                {{ __('Зарегистрироваться') }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Правая колонка с фоновым изображением -->
        <div class="col-md-6 d-none d-md-block p-0">
            <div class="bg-image h-100" style="background-image: url('https://images.unsplash.com/photo-1556912173-3bb406ef7e77?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1287&q=80'); background-size: cover; background-position: center;"></div>
        </div>
    </div>
</div>
<script>
 
document.addEventListener("DOMContentLoaded", function () {
    var inputs = document.querySelectorAll("input.maskphone");
    for (var i = 0; i < inputs.length; i++) {
        var input = inputs[i];
        input.addEventListener("input", mask);
        input.addEventListener("focus", mask);
        input.addEventListener("blur", mask);
        
        // Вызываем маску при загрузке страницы для полей с существующим значением
        if (input.value && input.value.length > 0) {
            mask.call(input);
        }
    }
    function mask(event) {
        var blank = "+_ (___) ___-__-__";
        var i = 0;
        var val = this.value.replace(/\D/g, "").replace(/^8/, "7").replace(/^9/, "79");
        this.value = blank.replace(/./g, function (char) {
            if (/[_\d]/.test(char) && i < val.length) return val.charAt(i++);
            return i >= val.length ? "" : char;
        });
        if (event.type == "blur") {
            if (this.value.length == 2) this.value = "";
        } else {
            setCursorPosition(this, this.value.length);
        }
    }
    function setCursorPosition(elem, pos) {
        elem.focus();
        if (elem.setSelectionRange) {
            elem.setSelectionRange(pos, pos);
            return;
        }
        if (elem.createTextRange) {
            var range = elem.createTextRange();
            range.collapse(true);
            range.moveEnd("character", pos);
            range.moveStart("character", pos);
            range.select();
            return;
        }
    }
});
</script>



<style>
.register-form-container {
    min-height: calc(100vh - 72px);
}
.bg-image {
    min-height: calc(100vh - 72px);
}
</style>
@endsection
