@extends('layouts.app')

@section('head')
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
        /* Адаптивные стили для мобильных устройств */
        @media (max-width: 767px) {
            .card {
                margin-bottom: 1rem;
            }
            .form-group {
                margin-bottom: 0.75rem;
            }
            .btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
                margin-bottom: 0.5rem;
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
            .table-responsive {
                font-size: 0.875rem;
            }
            #workItemsList {
                max-height: 300px;
                overflow-y: auto;
            }
            #workSearchInput, #sectionFilterSelect, #customWorkName, #workUnitSelect {
                font-size: 0.875rem;
                height: calc(1.5em + 0.75rem + 2px);
            }
        }
    </style>
@endsection

@section('content')
<!-- Подключаем библиотеки для работы с Excel в браузере -->
<link href="https://cdn.jsdelivr.net/npm/handsontable@9.0.2/dist/handsontable.full.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/handsontable@9.0.2/dist/handsontable.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<!-- Подключаем стили для редактора смет -->
<link href="{{ asset('css/estimates/excel-editor.css') }}" rel="stylesheet">

<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <h1 class="h3 mb-2 mb-md-0">{{ isset($estimate) ? 'Редактирование сметы' : 'Создание сметы' }}</h1>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('partner.estimates.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Назад к списку
            </a>
        </div>
    </div>
    
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            @include('partner.estimates.partials.excel-editor')
        </div>
        <div class="col-md-12 mb-3">
            @include('partner.estimates.partials.estimate-info-form')
        </div>
    </div>
</div>

<!-- Модальное окно выбора раздела -->
<div class="modal fade" id="sectionSelectorModal" tabindex="-1" aria-labelledby="sectionSelectorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sectionSelectorModalLabel">Выбор раздела</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="sectionSelect" class="form-label">Выберите раздел из шаблона</label>
                    <select class="form-select" id="sectionSelect">
                        <option value="">Создать новый раздел...</option>
                        <!-- Опции будут загружены через JS -->
                    </select>
                </div>
                <div class="mb-3">
                    <label for="customSectionName" class="form-label">Название раздела</label>
                    <input type="text" class="form-control" id="customSectionName" placeholder="Введите название раздела">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="confirmAddSection">Добавить раздел</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно выбора типа работы -->
<div class="modal fade" id="workTypeSelectorModal" tabindex="-1" aria-labelledby="workTypeSelectorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="workTypeSelectorModalLabel">Выбор типа работы</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="sectionFilterSelect" class="form-label">Фильтровать по разделу</label>
                    <select class="form-select" id="sectionFilterSelect">
                        <option value="">Все разделы</option>
                        <!-- Опции будут загружены через JS -->
                    </select>
                </div>
                <div class="mb-3">
                    <input type="text" class="form-control" id="workSearchInput" placeholder="Поиск работы...">
                </div>
                <div class="list-group" id="workItemsList" style="max-height: 300px; overflow-y: auto;">
                    <!-- Список работ будет загружен через JS -->
                </div>
                <div class="mt-3">
                    <label for="customWorkName" class="form-label">Или введите свой вариант</label>
                    <input type="text" class="form-control" id="customWorkName" placeholder="Введите название работы">
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="workUnitSelect" class="form-label">Единица измерения</label>
                        <select class="form-select" id="workUnitSelect">
                            <option value="раб">раб</option>
                            <option value="шт">шт</option>
                            <option value="м2">м²</option>
                            <option value="м.п.">м.п.</option>
                            <option value="компл">компл</option>
                            <option value="точка">точка</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="workQuantity" class="form-label">Количество</label>
                        <input type="number" class="form-control" id="workQuantity" value="1">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="confirmAddWork">Добавить работу</button>
            </div>
        </div>
    </div>
</div>

<!-- Подключаем JS компоненты для работы с Excel -->
<script src="{{ asset('js/estimates/excel-formula-manager.js') }}"></script>
<script src="{{ asset('js/estimates/excel-row-manager.js') }}"></script>
<script src="{{ asset('js/estimates/excel-sheet-manager.js') }}"></script>
<script src="{{ asset('js/estimates/excel-editor.js') }}"></script>

<script>
// Инициализация данных для модальных окон
let templateSections = [];
let templateWorks = [];

// Функция для заполнения списка разделов в модальном окне
function populateSectionSelects() {
    const sectionSelect = document.getElementById('sectionSelect');
    const sectionFilterSelect = document.getElementById('sectionFilterSelect');
    
    if (!sectionSelect || !sectionFilterSelect) return;
    
    // Очищаем селекты перед заполнением
    sectionSelect.innerHTML = '<option value="">Создать новый раздел...</option>';
    sectionFilterSelect.innerHTML = '<option value="">Все разделы</option>';
    
    // Заполняем данными из нашего шаблона
    fetch('/partner/excel-templates/sections-data')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.sections) {
                templateSections = data.sections;
                templateWorks = data.works || [];
                
                // Заполняем селекты опциями
                templateSections.forEach((section, index) => {
                    sectionSelect.innerHTML += `<option value="${index}">${section.title}</option>`;
                    sectionFilterSelect.innerHTML += `<option value="${index}">${section.title}</option>`;
                });
            }
        })
        .catch(error => console.error('Ошибка при загрузке разделов:', error));
}

// Обработчик изменения выбранного раздела
document.getElementById('sectionSelect').addEventListener('change', function() {
    const customSectionName = document.getElementById('customSectionName');
    if (this.value !== "") {
        // Если выбран раздел из списка, заполняем поле названия
        const selectedSection = templateSections[parseInt(this.value)];
        customSectionName.value = selectedSection.title;
    } else {
        // Если выбрано "Создать новый раздел", очищаем поле
        customSectionName.value = '';
    }
});

// Обработчик фильтра разделов
document.getElementById('sectionFilterSelect').addEventListener('change', function() {
    filterWorkItems();
});

// Обработчик поиска работ
document.getElementById('workSearchInput').addEventListener('input', function() {
    filterWorkItems();
});

// Функция фильтрации списка работ
function filterWorkItems() {
    const sectionFilter = document.getElementById('sectionFilterSelect').value;
    const searchQuery = document.getElementById('workSearchInput').value.toLowerCase();
    const workItemsList = document.getElementById('workItemsList');
    
    workItemsList.innerHTML = '';
    
    let filteredWorks = [];
    
    if (sectionFilter === "") {
        // Если фильтр раздела не выбран, показываем все работы
        filteredWorks = templateWorks;
    } else {
        // Иначе фильтруем работы по выбранному разделу
        const selectedSectionIndex = parseInt(sectionFilter);
        const selectedSection = templateSections[selectedSectionIndex];
        filteredWorks = selectedSection.items || [];
    }
    
    // Применяем поисковый фильтр
    filteredWorks = filteredWorks.filter(work => 
        work.name.toLowerCase().includes(searchQuery)
    );
    
    // Отображаем отфильтрованные работы
    filteredWorks.forEach((work, index) => {
        const listItem = document.createElement('a');
        listItem.href = '#';
        listItem.className = 'list-group-item list-group-item-action';
        listItem.textContent = work.name;
        listItem.dataset.index = index;
        listItem.dataset.name = work.name;
        listItem.dataset.unit = work.unit || 'раб';
        
        listItem.addEventListener('click', function(e) {
            e.preventDefault();
            // Заполняем поля значениями выбранной работы
            document.getElementById('customWorkName').value = this.dataset.name;
            document.getElementById('workUnitSelect').value = this.dataset.unit;
            
            // Снимаем выделение со всех элементов и выделяем текущий
            document.querySelectorAll('#workItemsList a.active').forEach(el => {
                el.classList.remove('active');
            });
            this.classList.add('active');
        });
        
        workItemsList.appendChild(listItem);
    });
}

// Изменяем обработчик кнопки добавления раздела
document.addEventListener('DOMContentLoaded', function() {
    populateSectionSelects();
    
    // Переопределяем обработчик клика по кнопке "Раздел"
    const addSectionBtn = document.getElementById('addSectionBtn');
    if (addSectionBtn) {
        addSectionBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Показываем модальное окно выбора раздела
            const modal = new bootstrap.Modal(document.getElementById('sectionSelectorModal'));
            modal.show();
        }, { capture: true });
    }
    
    // Обработчик подтверждения добавления раздела
    document.getElementById('confirmAddSection').addEventListener('click', function() {
        const sectionName = document.getElementById('customSectionName').value.trim();
        if (sectionName) {
            // Закрываем модальное окно
            bootstrap.Modal.getInstance(document.getElementById('sectionSelectorModal')).hide();
            
            // Вызываем существующую функцию addNewSection с новым именем раздела
            if (typeof addNewSection === 'function') {
                addNewSection(sectionName);
            }
        } else {
            alert('Пожалуйста, введите название раздела');
        }
    });
    
    // Переопределяем обработчик клика по кнопке "Строка"
    const addRowBtn = document.getElementById('addRowBtn');
    if (addRowBtn) {
        addRowBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Показываем модальное окно выбора типа работы
            const modal = new bootstrap.Modal(document.getElementById('workTypeSelectorModal'));
            filterWorkItems(); // Инициализируем список работ
            modal.show();
        }, { capture: true });
    }
    
    // Обработчик подтверждения добавления работы
    document.getElementById('confirmAddWork').addEventListener('click', function() {
        const workName = document.getElementById('customWorkName').value.trim();
        const workUnit = document.getElementById('workUnitSelect').value;
        const workQuantity = document.getElementById('workQuantity').value;
        
        if (workName) {
            // Закрываем модальное окно
            bootstrap.Modal.getInstance(document.getElementById('workTypeSelectorModal')).hide();
            
            // Вызываем существующую функцию addNewRow с параметрами
            if (typeof addNewRow === 'function') {
                addNewRow(workName, workUnit, workQuantity);
            }
        } else {
            alert('Пожалуйста, введите название работы');
        }
    });
});
</script>

@if(isset($estimate))
<script>
    // Инициализация с существующим файлом
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof initExcelEditor === 'function') {
            // Добавляем проверку на наличие ID сметы
            var estimateId = "{{ $estimate->id ?? '' }}";
            if (estimateId) {
                var dataUrl = "{{ route('partner.estimates.getData', ['estimate' => $estimate->id ?? 0]) }}";
                console.log('Initializing Excel editor with URL:', dataUrl);
                initExcelEditor(dataUrl);
            } else {
                console.error('Estimate ID is missing');
                initExcelEditor(null);
            }
        }
    });
</script>
@else
<script>
    // Инициализация с новым файлом
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof initExcelEditor === 'function') {
            console.log('Initializing new Excel workbook');
            initExcelEditor(null);
        }
    });
</script>
@endif

@endsection
