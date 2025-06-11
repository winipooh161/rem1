@extends('layouts.app')

@section('head')
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
        /* Адаптивные стили для мобильных устройств */
        @media (max-width: 767px) {
            .card {
                margin-bottom: 1rem;
                border-radius: 0.25rem;
            }
            .form-group {
                margin-bottom: 0.75rem;
            }
            .btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
                margin-bottom: 0.5rem;
                width: 100%;
            }
            .container-fluid {
                padding: 0.5rem;
            }
            .row {
                margin: 0 -0.25rem;
            }
            .col-12, .col-md-6, .col-lg-4 {
                padding: 0 0.25rem;
            }
            h1, h2, h3, h4, h5 {
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            /* Улучшение форм для мобильных */
            .form-control {
                font-size: 1rem;
                height: 40px;
            }
            textarea.form-control {
                height: auto;
            }
            /* Стили для улучшения касания на мобильных */
            .btn, .form-control, select, input[type="radio"] + label, input[type="checkbox"] + label {
                min-height: 40px;
            }
            /* Уменьшаем отступы внутри контейнеров */
            .card-header {
                padding: 0.75rem 1rem;
            }
            .card-body {
                padding: 0.75rem;
            }
        }
    </style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Создание нового объекта</h5>
            <a href="{{ route('partner.projects.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>К списку объектов
            </a>
        </div>
        <div class="card-body">
            <form action="{{ route('partner.projects.store') }}" method="POST" id="createProjectForm">
                @csrf
                
                <!-- Навигация по табам -->
                <ul class="nav nav-tabs mb-4" id="projectTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="required-tab" data-bs-toggle="tab" data-bs-target="#required" type="button" role="tab" aria-controls="required" aria-selected="true">
                            <i class="fas fa-asterisk me-1 text-danger"></i>Обязательные данные
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="client-tab" data-bs-toggle="tab" data-bs-target="#client" type="button" role="tab" aria-controls="client" aria-selected="false">
                            <i class="fas fa-user me-1"></i>Данные клиента
                        </button>
                    </li>
                
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="false">
                            <i class="fas fa-info-circle me-1"></i>Детали объекта
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="team-tab" data-bs-toggle="tab" data-bs-target="#team" type="button" role="tab" aria-controls="team" aria-selected="false">
                            <i class="fas fa-users me-1"></i>Команда и сроки
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="additional-tab" data-bs-toggle="tab" data-bs-target="#additional" type="button" role="tab" aria-controls="additional" aria-selected="false">
                            <i class="fas fa-plus-circle me-1"></i>Дополнительно
                        </button>
                    </li>
                </ul>

                <!-- Содержимое табов -->
                <div class="tab-content" id="projectTabsContent">
                    <!-- Вкладка: Обязательные данные -->
                    <div class="tab-pane fade show active" id="required" role="tabpanel" aria-labelledby="required-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-danger mb-3">
                                    <i class="fas fa-asterisk me-1"></i>Обязательные поля
                                </h6>
                                
                                <div class="mb-3">
                                    <label for="client_name" class="form-label">Имя и фамилия клиента <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('client_name') is-invalid @enderror" id="client_name" name="client_name" value="{{ old('client_name') }}" required>
                                    @error('client_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Телефон клиента <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control maskphone @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" required>
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Адрес объекта <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address') }}" required>
                                    <div class="form-text">Общий адрес (будет дополнен детализированными полями)</div>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-cog me-1"></i>Основные параметры
                                </h6>

                                <div class="mb-3">
                                    <label for="object_type" class="form-label">Тип объекта <span class="text-danger">*</span></label>
                                    <select class="form-select @error('object_type') is-invalid @enderror" id="object_type" name="object_type" required>
                                        <option value="">Выберите тип объекта</option>
                                        <option value="apartment" {{ old('object_type') == 'apartment' ? 'selected' : '' }}>Квартира</option>
                                        <option value="house" {{ old('object_type') == 'house' ? 'selected' : '' }}>Дом</option>
                                        <option value="office" {{ old('object_type') == 'office' ? 'selected' : '' }}>Офис</option>
                                        <option value="commercial" {{ old('object_type') == 'commercial' ? 'selected' : '' }}>Коммерческое помещение</option>
                                    </select>
                                    @error('object_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="work_type" class="form-label">Тип работ <span class="text-danger">*</span></label>
                                    <select class="form-select @error('work_type') is-invalid @enderror" id="work_type" name="work_type" required>
                                        <option value="">Выберите тип работ</option>
                                        <option value="repair" {{ old('work_type') == 'repair' ? 'selected' : '' }}>Ремонт</option>
                                        <option value="design" {{ old('work_type') == 'design' ? 'selected' : '' }}>Дизайн</option>
                                        <option value="construction" {{ old('work_type') == 'construction' ? 'selected' : '' }}>Строительство</option>
                                    </select>
                                    @error('work_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Статус проекта <span class="text-danger">*</span></label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                        <option value="new" {{ old('status', 'new') == 'new' ? 'selected' : '' }}>Новый</option>
                                        <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>В работе</option>
                                        <option value="on_hold" {{ old('status') == 'on_hold' ? 'selected' : '' }}>Приостановлен</option>
                                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Завершен</option>
                                        <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Отменен</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Вкладка: Данные клиента -->
                    <div class="tab-pane fade" id="client" role="tabpanel" aria-labelledby="client-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-info mb-3">
                                    <i class="fas fa-id-card me-1"></i>Паспортные данные
                                </h6>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="passport_series" class="form-label">Серия паспорта</label>
                                        <input type="text" class="form-control @error('passport_series') is-invalid @enderror" id="passport_series" name="passport_series" value="{{ old('passport_series') }}" placeholder="1234">
                                        @error('passport_series')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <label for="passport_number" class="form-label">Номер паспорта</label>
                                        <input type="text" class="form-control @error('passport_number') is-invalid @enderror" id="passport_number" name="passport_number" value="{{ old('passport_number') }}" placeholder="567890">
                                        @error('passport_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="passport_issued_by" class="form-label">Кем выдан</label>
                                    <textarea class="form-control @error('passport_issued_by') is-invalid @enderror" id="passport_issued_by" name="passport_issued_by" rows="2" placeholder="Отделом УФМС России по ...">{{ old('passport_issued_by') }}</textarea>
                                    @error('passport_issued_by')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="passport_issued_date" class="form-label">Дата выдачи</label>
                                        <input type="date" class="form-control @error('passport_issued_date') is-invalid @enderror" id="passport_issued_date" name="passport_issued_date" value="{{ old('passport_issued_date') }}">
                                        @error('passport_issued_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="passport_code" class="form-label">Код подразделения</label>
                                        <input type="text" class="form-control @error('passport_code') is-invalid @enderror" id="passport_code" name="passport_code" value="{{ old('passport_code') }}" placeholder="123-456">
                                        @error('passport_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-success mb-3">
                                    <i class="fas fa-user-circle me-1"></i>Личные данные
                                </h6>
                                
                                <div class="mb-3">
                                    <label for="client_birth_date" class="form-label">Дата рождения</label>
                                    <input type="date" class="form-control @error('client_birth_date') is-invalid @enderror" id="client_birth_date" name="client_birth_date" value="{{ old('client_birth_date') }}">
                                    @error('client_birth_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="client_birth_place" class="form-label">Место рождения</label>
                                    <input type="text" class="form-control @error('client_birth_place') is-invalid @enderror" id="client_birth_place" name="client_birth_place" value="{{ old('client_birth_place') }}" placeholder="г. Москва">
                                    @error('client_birth_place')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="client_email" class="form-label">Email клиента</label>
                                    <input type="email" class="form-control @error('client_email') is-invalid @enderror" id="client_email" name="client_email" value="{{ old('client_email') }}" placeholder="client@example.com">
                                    @error('client_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <h6 class="text-warning mb-3 mt-4">
                                    <i class="fas fa-home me-1"></i>Адрес прописки
                                </h6>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="registration_postal_code" class="form-label">Почтовый индекс</label>
                                        <input type="text" class="form-control @error('registration_postal_code') is-invalid @enderror" id="registration_postal_code" name="registration_postal_code" value="{{ old('registration_postal_code') }}" placeholder="123456">
                                        @error('registration_postal_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="registration_city" class="form-label">Город</label>
                                        <input type="text" class="form-control @error('registration_city') is-invalid @enderror" id="registration_city" name="registration_city" value="{{ old('registration_city') }}" placeholder="Москва">
                                        @error('registration_city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="registration_street" class="form-label">Улица</label>
                                    <input type="text" class="form-control @error('registration_street') is-invalid @enderror" id="registration_street" name="registration_street" value="{{ old('registration_street') }}" placeholder="ул. Тверская">
                                    @error('registration_street')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="registration_house" class="form-label">Дом</label>
                                        <input type="text" class="form-control @error('registration_house') is-invalid @enderror" id="registration_house" name="registration_house" value="{{ old('registration_house') }}" placeholder="12">
                                        @error('registration_house')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="registration_apartment" class="form-label">Квартира</label>
                                        <input type="text" class="form-control @error('registration_apartment') is-invalid @enderror" id="registration_apartment" name="registration_apartment" value="{{ old('registration_apartment') }}" placeholder="45">
                                        @error('registration_apartment')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Вкладка: Адрес объекта -->
                    <div class="tab-pane fade" id="address" role="tabpanel" aria-labelledby="address-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-map-marker-alt me-1"></i>Детализированный адрес объекта
                                </h6>
                                
                                <div class="mb-3">
                                    <label for="city" class="form-label">Город</label>
                                    <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city') }}" placeholder="Москва">
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="street" class="form-label">Улица</label>
                                    <input type="text" class="form-control @error('street') is-invalid @enderror" id="street" name="street" value="{{ old('street') }}" placeholder="ул. Тверская">
                                    @error('street')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="house_number" class="form-label">Номер дома</label>
                                        <input type="text" class="form-control @error('house_number') is-invalid @enderror" id="house_number" name="house_number" value="{{ old('house_number') }}" placeholder="12">
                                        @error('house_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="entrance" class="form-label">Подъезд</label>
                                        <input type="text" class="form-control @error('entrance') is-invalid @enderror" id="entrance" name="entrance" value="{{ old('entrance') }}" placeholder="2">
                                        @error('entrance')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="apartment_number" class="form-label">Номер квартиры/офиса</label>
                                    <input type="text" class="form-control @error('apartment_number') is-invalid @enderror" id="apartment_number" name="apartment_number" value="{{ old('apartment_number') }}" placeholder="45">
                                    @error('apartment_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-info mb-3">
                                    <i class="fas fa-ruler me-1"></i>Характеристики объекта
                                </h6>
                                
                                <div class="mb-3">
                                    <label for="area" class="form-label">Площадь (м²)</label>
                                    <input type="number" step="0.1" min="0" class="form-control @error('area') is-invalid @enderror" id="area" name="area" value="{{ old('area') }}">
                                    @error('area')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="camera_link" class="form-label">Ссылка на камеры наблюдения</label>
                                    <input type="url" class="form-control @error('camera_link') is-invalid @enderror" id="camera_link" name="camera_link" value="{{ old('camera_link') }}" placeholder="https://...">
                                    @error('camera_link')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="contact_phones" class="form-label">Дополнительные телефоны</label>
                                    <textarea class="form-control @error('contact_phones') is-invalid @enderror" id="contact_phones" name="contact_phones" rows="3" placeholder="Укажите дополнительные номера телефонов клиента или его представителей">{{ old('contact_phones') }}</textarea>
                                    <div class="form-text">Например: запасной номер клиента, телефон управляющей компании и т.д.</div>
                                    @error('contact_phones')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Вкладка: Детали объекта -->
                    <div class="tab-pane fade" id="details" role="tabpanel" aria-labelledby="details-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-success mb-3">
                                    <i class="fas fa-ruble-sign me-1"></i>Финансовые показатели
                                </h6>
                                
                                <div class="mb-3">
                                    <label for="work_amount" class="form-label">Стоимость работ, ₽</label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('work_amount') is-invalid @enderror" id="work_amount" name="work_amount" value="{{ old('work_amount') }}">
                                    @error('work_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="materials_amount" class="form-label">Стоимость материалов, ₽</label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('materials_amount') is-invalid @enderror" id="materials_amount" name="materials_amount" value="{{ old('materials_amount') }}">
                                    @error('materials_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="total_display" class="form-label">Общая стоимость, ₽</label>
                                    <input type="text" class="form-control bg-light" id="total_display" readonly placeholder="Будет рассчитана автоматически">
                                    <div class="form-text">Сумма работ и материалов</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-sticky-note me-1"></i>Заметки
                                </h6>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Описание проекта и заметки</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" placeholder="Дополнительная информация о проекте, особые требования клиента, заметки...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Вкладка: Команда и сроки -->
                    <div class="tab-pane fade" id="team" role="tabpanel" aria-labelledby="team-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-warning mb-3">
                                    <i class="fas fa-user-hard-hat me-1"></i>Исполнители
                                </h6>
                                
                                <div class="mb-3">
                                    <label for="estimator_id" class="form-label">Назначить сметчика</label>
                                    <select class="form-select @error('estimator_id') is-invalid @enderror" id="estimator_id" name="estimator_id">
                                        <option value="">Выберите сметчика (необязательно)</option>
                                        @foreach($estimators as $estimator)
                                            <option value="{{ $estimator->id }}" {{ old('estimator_id') == $estimator->id ? 'selected' : '' }}>
                                                {{ $estimator->name }} ({{ $estimator->phone }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Сметчик будет иметь доступ только к назначенным ему объектам</div>
                                    @error('estimator_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-secondary mb-3">
                                    <i class="fas fa-calendar me-1"></i>Временные рамки
                                </h6>
                                
                                <div class="mb-3">
                                    <label for="contract_date" class="form-label">Дата заключения договора</label>
                                    <input type="date" class="form-control @error('contract_date') is-invalid @enderror" id="contract_date" name="contract_date" value="{{ old('contract_date') }}">
                                    @error('contract_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="work_start_date" class="form-label">Дата начала работ</label>
                                    <input type="date" class="form-control @error('work_start_date') is-invalid @enderror" id="work_start_date" name="work_start_date" value="{{ old('work_start_date') }}">
                                    @error('work_start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="contract_number" class="form-label">Номер договора</label>
                                    <input type="text" class="form-control @error('contract_number') is-invalid @enderror" id="contract_number" name="contract_number" value="{{ old('contract_number') }}">
                                    @error('contract_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Вкладка: Дополнительно -->
                    <div class="tab-pane fade" id="additional" role="tabpanel" aria-labelledby="additional-tab">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Информация:</strong> Все основные данные заполнены в предыдущих вкладках. 
                                    Убедитесь, что все обязательные поля заполнены корректно перед сохранением проекта.
                                </div>
                                
                                <div class="card">
                                    <div class="card-body">
                                        <h6>Краткая сводка по проекту:</h6>
                                        <div id="project-summary">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Клиент:</strong> <span id="summary-client">Не указан</span></p>
                                                    <p><strong>Телефон:</strong> <span id="summary-phone">Не указан</span></p>
                                                    <p><strong>Адрес:</strong> <span id="summary-address">Не указан</span></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Тип объекта:</strong> <span id="summary-object-type">Не выбран</span></p>
                                                    <p><strong>Тип работ:</strong> <span id="summary-work-type">Не выбран</span></p>
                                                    <p><strong>Статус:</strong> <span id="summary-status">Не выбран</span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Кнопки управления -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='{{ route('partner.projects.index') }}'">
                                <i class="fas fa-times me-1"></i>Отмена
                            </button>
                            <div>
                                <button type="button" class="btn btn-outline-primary me-2" id="prevTab" style="display: none;">
                                    <i class="fas fa-chevron-left me-1"></i>Назад
                                </button>
                                <button type="button" class="btn btn-primary me-2" id="nextTab">
                                    Далее <i class="fas fa-chevron-right ms-1"></i>
                                </button>
                                <button type="submit" class="btn btn-success" id="submitBtn" style="">
                                    <i class="fas fa-save me-1"></i>Создать объект
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Маска для телефона
    var inputs = document.querySelectorAll("input.maskphone");
    for (var i = 0; i < inputs.length; i++) {
        var input = inputs[i];
        input.addEventListener("input", mask);
        input.addEventListener("focus", mask);
        input.addEventListener("blur", mask);
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
        }
    }

    // Автоматический расчет общей стоимости
    const workAmountInput = document.getElementById('work_amount');
    const materialsAmountInput = document.getElementById('materials_amount');
    const totalDisplayInput = document.getElementById('total_display');

    function calculateTotal() {
        const workAmount = parseFloat(workAmountInput.value) || 0;
        const materialsAmount = parseFloat(materialsAmountInput.value) || 0;
        const total = workAmount + materialsAmount;
        
        if (total > 0) {
            totalDisplayInput.value = new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB'
            }).format(total);
        } else {
            totalDisplayInput.value = '';
        }
    }

    workAmountInput.addEventListener('input', calculateTotal);
    materialsAmountInput.addEventListener('input', calculateTotal);

    // Управление табами
    const tabs = ['required', 'client', 'address', 'details', 'team', 'additional'];
    let currentTab = 0;

    const nextTabBtn = document.getElementById('nextTab');
    const prevTabBtn = document.getElementById('prevTab');
    const submitBtn = document.getElementById('submitBtn');

    function updateTabNavigation() {
        prevTabBtn.style.display = currentTab > 0 ? 'inline-block' : 'none';
        nextTabBtn.style.display = currentTab < tabs.length - 1 ? 'inline-block' : 'none';

    }

    function updateSummary() {
        document.getElementById('summary-client').textContent = document.getElementById('client_name').value || 'Не указан';
        document.getElementById('summary-phone').textContent = document.getElementById('phone').value || 'Не указан';
        document.getElementById('summary-address').textContent = document.getElementById('address').value || 'Не указан';
        
        const objectType = document.getElementById('object_type');
        document.getElementById('summary-object-type').textContent = objectType.options[objectType.selectedIndex].text || 'Не выбран';
        
        const workType = document.getElementById('work_type');
        document.getElementById('summary-work-type').textContent = workType.options[workType.selectedIndex].text || 'Не выбран';
    }

    nextTabBtn.addEventListener('click', function() {
        if (currentTab < tabs.length - 1) {
            currentTab++;
            const nextTabElement = document.querySelector(`#${tabs[currentTab]}-tab`);
            nextTabElement.click();
            updateTabNavigation();
            if (currentTab === tabs.length - 1) {
                updateSummary();
            }
        }
    });

    prevTabBtn.addEventListener('click', function() {
        if (currentTab > 0) {
            currentTab--;
            const prevTabElement = document.querySelector(`#${tabs[currentTab]}-tab`);
            prevTabElement.click();
            updateTabNavigation();
        }
    });

    // Отслеживание переключения табов вручную
    document.querySelectorAll('#projectTabs button[data-bs-toggle="tab"]').forEach((tab, index) => {
        tab.addEventListener('shown.bs.tab', function() {
            currentTab = index;
            updateTabNavigation();
        });
    });

    updateTabNavigation();
});
</script>

@endsection
