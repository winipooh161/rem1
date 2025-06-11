<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Days+One&family=Onest:wght@100..900&display=swap" rel="stylesheet">
    
    <!-- jQuery должен быть подключен до Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" 
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" 
            crossorigin="anonymous"></script>
            
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz"
            crossorigin="anonymous"
            onload="console.log('Bootstrap JS loaded successfully')" 
            onerror="console.error('Failed to load Bootstrap JS')"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js', ])
</head>
<body>
    <div class="wrapper">
        <!-- Боковая навигационная панель -->
        @include('layouts.partials.sidebar')

        <!-- Основной контент -->
        <div id="content">
            <!-- Верхняя навигационная панель -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapseShow" class="btn btn-light d-inline-block d-md-none me-2" onclick="console.log('Клик по кнопке меню')">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a class="navbar-brand d-none d-md-inline" href="{{ url('/') }}">
                        {{ config('app.name', 'Laravel') }}
                    </a>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link" href="#" role="button">
                                    <i class="fas fa-bell"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <main class="">
                @yield('content')
            </main>
        </div>
    </div>


    <!-- Обязательно разместить yield для скриптов в конце body -->
    @yield('scripts')

    <!-- Подключение скриптов -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Исправление для выпадающих меню -->
    <script src="{{ asset('js/dropdown-fix.js') }}"></script>
    
    @stack('scripts')
</body>
</html>
