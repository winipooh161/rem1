@extends('layouts.auth')

@section('content')
<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Левая колонка с формой -->
        <div class="col-md-6 bg-white">
            <div class="login-form-container d-flex align-items-center py-5">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-10 col-xl-8 mx-auto">
                            <h3 class="display-6 mb-4">{{ __('Вход') }}</h3>
                            <div class="card border-0 shadow rounded-3">
                                <div class="card-body p-4 p-sm-5">
                                    @if ($errors->has('db_error'))
                                    <div class="alert alert-danger mb-3">
                                        {{ $errors->first('db_error') }}
                                    </div>
                                    @endif
                                    <form method="POST" action="{{ route('login') }}">
                                        @csrf

                                        <div class="mb-3">
                                            <label for="phone" class="form-label">{{ __('Номер телефона') }}</label>
                                            <input id="phone" type="tel" class="form-control maskphone @error('phone')  is-invalid @enderror" name="phone" value="{{ old('phone') }}" required autocomplete="phone" autofocus>
                                            @error('phone')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="form-label">{{ __('Пароль') }}</label>
                                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                                            @error('password')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>

                                        <div class="mb-3 form-check">
                                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="remember">
                                                {{ __('Запомнить меня') }}
                                            </label>
                                        </div>

                                        <div class="d-grid gap-2 mb-3">
                                            <button type="submit" class="btn btn-primary btn-block">
                                                {{ __('Войти') }}
                                            </button>
                                        </div>

                                        <div class="text-center">
                                            @if (Route::has('password.request'))
                                                <a class="text-decoration-none" href="{{ route('password.request') }}">
                                                    {{ __('Забыли пароль?') }}
                                                </a>
                                            @endif
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
            <div class="bg-image h-100" style="background-image: url('https://images.unsplash.com/photo-1556156653-e5a7c69cc263?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=987&q=80'); background-size: cover; background-position: center;"></div>
        </div>
    </div>
</div>
<style>
.login-form-container {
    min-height: calc(100vh - 72px);
}
.bg-image {
    min-height: calc(100vh - 72px);
}
</style>
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



@endsection
