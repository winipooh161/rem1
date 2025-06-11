@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-2">Калькулятор строительных материалов</h1>
            <p class="text-muted">Рассчитайте необходимое количество материалов для вашего проекта</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Расчет материалов</h5>
        </div>
        <div class="card-body">
            <form id="calculator-form">
                @csrf
                <div id="materials-container">
                    <div class="material-row mb-4" data-index="0">
                        <div class="row align-items-end">
                            <div class="col-md-4 col-12 mb-md-0 mb-2">
                                <label class="form-label">Материал</label>
                                <select name="calculations[0][material_id]" class="form-select material-select" required>
                                    <option value="">Выберите материал</option>
                                    @foreach($materials as $id => $material)
                                        <option value="{{ $id }}">{{ $material['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 col-6 mb-md-0 mb-2">
                                <label class="form-label">Объем, м²/м.п.</label>
                                <input type="number" step="0.01" min="0" name="calculations[0][volume]" 
                                       class="form-control volume-input" placeholder="0" required>
                            </div>
                            <div class="col-md-3 col-6 mb-md-0 mb-2">
                                <label class="form-label">Слой</label>
                                <input type="number" step="0.01" min="0" name="calculations[0][layer]" 
                                       class="form-control layer-input" placeholder="0" value="1">
                                <small class="form-text text-muted layer-hint"></small>
                            </div>
                            <div class="col-md-2 col-12 text-md-start text-end mt-md-0 mt-2">
                                <button type="button" class="btn btn-outline-danger remove-material w-md-auto w-100" title="Удалить" disabled>
                                    <i class="fas fa-trash"></i> <span class="d-inline d-md-none">Удалить</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="button" id="add-material" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>Добавить материал
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-calculator me-1"></i>Рассчитать
                        </button>
                        <button type="button" id="export-pdf" class="btn btn-outline-danger" style="display: none;">
                            <i class="fas fa-file-pdf me-1"></i>Скачать PDF
                        </button>
                        <button type="button" class="btn btn-outline-warning" onclick="clearSavedData()">
                            <i class="fas fa-broom me-1"></i>Очистить
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="showPricesModal()">
                            <i class="fas fa-ruble-sign me-1"></i>Цены
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Результаты расчета -->
    <div id="results-container" class="card mt-4" style="display: none;">
        <div class="card-header">
            <h5 class="mb-0">Результаты расчета</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Материал</th>
                            <th>Объем</th>
                            <th>Слой</th>
                            <th>Расход</th>
                            <th>Упаковок + 10%</th>
                            <th>Цена за упаковку</th>
                            <th>Общая стоимость</th>
                        </tr>
                    </thead>
                    <tbody id="results-tbody">
                    </tbody>
                    <tfoot>
                        <tr class="table-warning">
                            <th colspan="6" class="text-end">ИТОГО:</th>
                            <th id="total-cost">0 ₽</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно управления ценами -->
<div class="modal fade" id="pricesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Управление ценами материалов</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="price-search" placeholder="Поиск материала...">
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th>ID</th>
                                <th>Наименование материала</th>
                                <th>Единица</th>
                                <th width="150">Цена за упаковку, ₽</th>
                                <th width="100">Действия</th>
                            </tr>
                        </thead>
                        <tbody id="prices-table-body">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-warning" onclick="resetPrices()">Сбросить все цены</button>
                <button type="button" class="btn btn-primary" onclick="savePrices()">Сохранить изменения</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- Подключаем библиотеку Select2 для поиска -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// Ждем полной загрузки всех библиотек
window.addEventListener('load', function() {
    // Проверяем загрузку jQuery и Select2
    if (typeof jQuery === 'undefined') {
        console.error('jQuery не загружен');
        alert('Ошибка: jQuery не загружен');
        return;
    }
    
    if (typeof jQuery.fn.select2 === 'undefined') {
        console.error('Select2 не загружен');
        // Пытаемся загрузить Select2 динамически
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
        script.onload = function() {
            console.log('Select2 загружен динамически');
            initializeCalculator();
        };
        document.head.appendChild(script);
        return;
    }
    
    // Если все библиотеки загружены, инициализируем калькулятор
    initializeCalculator();
});

function initializeCalculator() {
    let materialIndex = 0;
    const materialsData = @json($materials);
    const STORAGE_KEY = 'calculator_materials_data';
    const PRICES_STORAGE_KEY = 'calculator_material_prices';
    
    // Объект для хранения цен материалов
    let materialPrices = {};
    
    // Загружаем цены при инициализации
    loadMaterialPrices();
    
    // Инициализируем Select2 для первого селекта
    initializeSelect2($('.material-select'));
    
    // Загружаем сохраненные данные при инициализации
    loadSavedData();
    
    // Функция инициализации Select2
    function initializeSelect2(elements) {
        try {
            elements.select2({
                placeholder: 'Поиск материала...',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "Материал не найден";
                    },
                    searching: function() {
                        return "Поиск...";
                    }
                }
            });
        } catch (error) {
            console.error('Ошибка инициализации Select2:', error);
            // Fallback - оставляем обычный select
        }
    }
    
    // Добавление нового материала
    document.getElementById('add-material').addEventListener('click', function() {
        materialIndex++;
        const container = document.getElementById('materials-container');
        const newRow = createMaterialRow(materialIndex);
        container.appendChild(newRow);
        
        // Инициализируем Select2 для нового селекта
        const newSelect = newRow.querySelector('.material-select');
        try {
            initializeSelect2($(newSelect));
        } catch (error) {
            console.warn('Не удалось инициализировать Select2 для нового элемента:', error);
        }
        
        updateRemoveButtons();
        saveDataToStorage();
    });

    // Обработка изменения материала
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('material-select')) {
            updateLayerHint(e.target);
            saveDataToStorage();
        }
    });

    // Обработка изменения значений в полях
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('volume-input') || e.target.classList.contains('layer-input')) {
            saveDataToStorage();
        }
    });

    // Удаление материала
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-material')) {
            const row = e.target.closest('.material-row');
            const select = row.querySelector('.material-select');
            
            // Уничтожаем Select2 перед удалением элемента
            try {
                if ($(select).hasClass('select2-hidden-accessible')) {
                    $(select).select2('destroy');
                }
            } catch (error) {
                console.warn('Ошибка при уничтожении Select2:', error);
            }
            
            row.remove();
            updateRemoveButtons();
            saveDataToStorage();
        }
    });

    // Отправка формы
    document.getElementById('calculator-form').addEventListener('submit', function(e) {
        e.preventDefault();
        calculateMaterials();
    });

    // Создание строки материала
    function createMaterialRow(index) {
        const div = document.createElement('div');
        div.className = 'material-row mb-4';
        div.setAttribute('data-index', index);
        
        // Создаем опции для селекта
        let optionsHtml = '<option value="">Выберите материал</option>';
        Object.values(materialsData).forEach(material => {
            optionsHtml += `<option value="${material.id}">${material.name}</option>`;
        });
        
        div.innerHTML = `
            <div class="row align-items-end">
                <div class="col-md-4 col-12 mb-md-0 mb-2">
                    <label class="form-label">Материал</label>
                    <select name="calculations[${index}][material_id]" class="form-select material-select" required>
                        ${optionsHtml}
                    </select>
                </div>
                <div class="col-md-3 col-6 mb-md-0 mb-2">
                    <label class="form-label">Объем, м²/м.п.</label>
                    <input type="number" step="0.01" min="0" name="calculations[${index}][volume]" 
                           class="form-control volume-input" placeholder="0" required>
                </div>
                <div class="col-md-3 col-6 mb-md-0 mb-2">
                    <label class="form-label">Слой</label>
                    <input type="number" step="0.01" min="0" name="calculations[${index}][layer]" 
                           class="form-control layer-input" placeholder="0" value="1">
                    <small class="form-text text-muted layer-hint"></small>
                </div>
                <div class="col-md-2 col-12 text-md-start text-end mt-md-0 mt-2">
                    <button type="button" class="btn btn-outline-danger remove-material w-md-auto w-100" title="Удалить">
                        <i class="fas fa-trash"></i> <span class="d-inline d-md-none">Удалить</span>
                    </button>
                </div>
            </div>
        `;
        
        return div;
    }

    // Функция сохранения данных в локальное хранилище
    function saveDataToStorage() {
        const materialsDataToSave = [];
        
        document.querySelectorAll('.material-row').forEach((row, index) => {
            const materialSelect = row.querySelector('.material-select');
            const volumeInput = row.querySelector('.volume-input');
            const layerInput = row.querySelector('.layer-input');
            
            materialsDataToSave.push({
                material_id: materialSelect.value,
                volume: volumeInput.value,
                layer: layerInput.value,
                disabled: layerInput.disabled
            });
        });
        
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            materials: materialsDataToSave,
            timestamp: Date.now()
        }));
    }
    
    // Функция загрузки сохраненных данных
    function loadSavedData() {
        try {
            const savedData = localStorage.getItem(STORAGE_KEY);
            if (!savedData) return;
            
            const data = JSON.parse(savedData);
            
            // Проверяем, что данные не старше 24 часов
            const dayInMs = 24 * 60 * 60 * 1000;
            if (Date.now() - data.timestamp > dayInMs) {
                localStorage.removeItem(STORAGE_KEY);
                return;
            }
            
            if (data.materials && data.materials.length > 0) {
                // Очищаем текущий контейнер (кроме первой строки)
                const container = document.getElementById('materials-container');
                const firstRow = container.querySelector('.material-row');
                
                // Загружаем данные в первую строку
                if (data.materials[0]) {
                    loadDataToRow(firstRow, data.materials[0]);
                }
                
                // Добавляем остальные строки
                for (let i = 1; i < data.materials.length; i++) {
                    materialIndex++;
                    const newRow = createMaterialRow(materialIndex);
                    container.appendChild(newRow);
                    
                    // Инициализируем Select2 для нового селекта
                    const newSelect = newRow.querySelector('.material-select');
                    try {
                        initializeSelect2($(newSelect));
                    } catch (error) {
                        console.warn('Не удалось инициализировать Select2 при загрузке данных:', error);
                    }
                    
                    loadDataToRow(newRow, data.materials[i]);
                }
                
                updateRemoveButtons();
                showNotification('Данные калькулятора восстановлены из сохраненных', 'info');
            }
        } catch (error) {
            console.error('Ошибка при загрузке сохраненных данных:', error);
            localStorage.removeItem(STORAGE_KEY);
        }
    }
    
    // Функция загрузки данных в конкретную строку
    function loadDataToRow(row, data) {
        const materialSelect = row.querySelector('.material-select');
        const volumeInput = row.querySelector('.volume-input');
        const layerInput = row.querySelector('.layer-input');
        
        if (data.material_id) {
            try {
                $(materialSelect).val(data.material_id).trigger('change');
            } catch (error) {
                // Fallback для обычного select
                materialSelect.value = data.material_id;
                materialSelect.dispatchEvent(new Event('change'));
            }
            updateLayerHint(materialSelect);
        }
        
        if (data.volume) {
            volumeInput.value = data.volume;
        }
        
        if (data.layer && !layerInput.disabled) {
            layerInput.value = data.layer;
        }
    }
    
    // Глобальные функции для использования в onclick
    window.clearSavedData = function() {
        if (confirm('Вы уверены, что хотите очистить все сохраненные данные калькулятора?')) {
            localStorage.removeItem(STORAGE_KEY);
            
            // Сбрасываем форму к изначальному состоянию
            const container = document.getElementById('materials-container');
            const rows = container.querySelectorAll('.material-row');
            
            // Уничтожаем Select2 для всех селектов
            rows.forEach(row => {
                const select = row.querySelector('.material-select');
                try {
                    if ($(select).hasClass('select2-hidden-accessible')) {
                        $(select).select2('destroy');
                    }
                } catch (error) {
                    console.warn('Ошибка при уничтожении Select2:', error);
                }
            });
            
            // Удаляем все строки кроме первой
            for (let i = 1; i < rows.length; i++) {
                rows[i].remove();
            }
            
            // Очищаем первую строку
            const firstRow = container.querySelector('.material-row');
            const firstSelect = firstRow.querySelector('.material-select');
            try {
                $(firstSelect).val('').trigger('change');
                initializeSelect2($(firstSelect));
            } catch (error) {
                firstSelect.value = '';
                firstSelect.dispatchEvent(new Event('change'));
            }
            
            firstRow.querySelector('.volume-input').value = '';
            firstRow.querySelector('.layer-input').value = '1';
            firstRow.querySelector('.layer-input').disabled = false;
            firstRow.querySelector('.layer-hint').textContent = '';
            
            // Скрываем результаты
            document.getElementById('results-container').style.display = 'none';
            document.getElementById('export-pdf').style.display = 'none';
            
            updateRemoveButtons();
            showNotification('Сохраненные данные очищены', 'success');
        }
    };
    
    // Функция показа модального окна управления ценами
    window.showPricesModal = function() {
        populatePricesTable();
        const modal = new bootstrap.Modal(document.getElementById('pricesModal'));
        modal.show();
    };
    
    // Функция сохранения цен
    window.savePrices = function() {
        const priceInputs = document.querySelectorAll('.price-input');
        const newPrices = {};
        
        priceInputs.forEach(input => {
            const materialId = input.dataset.materialId;
            const price = parseFloat(input.value) || 0;
            newPrices[materialId] = price;
        });
        
        // Сохраняем в объект
        materialPrices = { ...materialPrices, ...newPrices };
        
        // Сохраняем в localStorage
        localStorage.setItem(PRICES_STORAGE_KEY, JSON.stringify(materialPrices));
        
        // Отправляем на сервер
        fetch('{{ route("partner.calculator.save-prices") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({ prices: newPrices })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Цены успешно сохранены', 'success');
                bootstrap.Modal.getInstance(document.getElementById('pricesModal')).hide();
            } else {
                showNotification('Ошибка при сохранении цен', 'danger');
            }
        })
        .catch(error => {
            console.error('Ошибка при сохранении цен:', error);
            showNotification('Ошибка при сохранении цен', 'danger');
        });
    };
    
    // Функция сброса всех цен
    window.resetPrices = function() {
        if (confirm('Вы уверены, что хотите сбросить все цены к значениям по умолчанию?')) {
            materialPrices = {};
            localStorage.removeItem(PRICES_STORAGE_KEY);
            populatePricesTable();
            showNotification('Цены сброшены к значениям по умолчанию', 'info');
        }
    };
    
    // Функция сброса одной цены
    window.resetSinglePrice = function(materialId) {
        delete materialPrices[materialId];
        localStorage.setItem(PRICES_STORAGE_KEY, JSON.stringify(materialPrices));
        
        // Обновляем поле ввода
        const input = document.querySelector(`input[data-material-id="${materialId}"]`);
        if (input) {
            // Загружаем дефолтную цену
            fetch('{{ route("partner.calculator.get-prices") }}', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.prices[materialId]) {
                    input.value = data.prices[materialId];
                }
            });
        }
    };
    
    // Функция показа уведомлений
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }

    // Обновление подсказки для поля "Слой"
    function updateLayerHint(selectElement) {
        const materialId = selectElement.value;
        const layerHint = selectElement.closest('.material-row').querySelector('.layer-hint');
        const layerInput = selectElement.closest('.material-row').querySelector('.layer-input');
        
        if (materialId && materialsData[materialId]) {
            const material = materialsData[materialId];
            
            switch(material.calculation_type) {
                case 'area_layer':
                    if (materialId == 12) { // Наливной пол
                        layerHint.textContent = 'Толщина в мм';
                    } else if (materialId == 4) { // Штукатурка
                        layerHint.textContent = 'Толщина слоя в мм';
                    } else if (materialId == 10) { // Пескобетон
                        layerHint.textContent = 'Толщина слоя в мм';
                    } else if (materialId == 11) { // Керамзит
                        layerHint.textContent = 'Толщина слоя в мм';
                    } else if (materialId == 13) { // Гипсокартон
                        layerHint.textContent = 'Количество слоев';
                    } else if (materialId == 52) { // Газосиликатные блоки
                        layerHint.textContent = 'Толщина стены в мм';
                    } else {
                        layerHint.textContent = 'Толщина слоя в мм';
                    }
                    layerInput.disabled = false;
                    layerInput.required = true;
                    layerInput.value = layerInput.value || '1';
                    break;
                    
                case 'linear_layer':
                    if (materialId == 14) { // Монтаж лобика
                        layerHint.textContent = 'Количество слоев';
                    } else {
                        layerHint.textContent = 'Количество слоев';
                    }
                    layerInput.disabled = false;
                    layerInput.required = true;
                    layerInput.value = layerInput.value || '1';
                    break;
                    
                case 'area':
                   
                    layerInput.disabled = true;
                    layerInput.required = false;
                    layerInput.value = 1;
                    break;
                    
                case 'linear':
                
                    layerInput.disabled = true;
                    layerInput.required = false;
                    layerInput.value = 1;
                    break;
                    
                default:
                    layerHint.textContent = '';
                    layerInput.disabled = false;
                    layerInput.required = true;
            }
        } else {
            layerHint.textContent = '';
            layerInput.disabled = false;
            layerInput.required = true;
        }
    }

    // Обновление кнопок удаления
    function updateRemoveButtons() {
        const removeButtons = document.querySelectorAll('.remove-material');
        removeButtons.forEach(button => {
            button.disabled = removeButtons.length <= 1;
        });
    }

    // Расчет материалов
    function calculateMaterials() {
        const calculations = [];
        document.querySelectorAll('.material-row').forEach((row, index) => {
            const materialSelect = row.querySelector('.material-select');
            const volumeInput = row.querySelector('.volume-input');
            const layerInput = row.querySelector('.layer-input');
            
            if (materialSelect.value && volumeInput.value) {
                const materialId = parseInt(materialSelect.value);
                const material = materialsData[materialId];
                
                const calculation = {
                    material_id: materialId,
                    volume: volumeInput.value
                };
                
                if (!layerInput.disabled && layerInput.value && 
                    ['area_layer', 'linear_layer'].includes(material.calculation_type)) {
                    calculation.layer = layerInput.value;
                } else {
                    calculation.layer = 1;
                }
                
                calculations.push(calculation);
            }
        });
        
        if (calculations.length === 0) {
            alert('Добавьте хотя бы один материал для расчета');
            return;
        }
        
        const requestData = {
            calculations: calculations,
            _token: document.querySelector('input[name="_token"]').value
        };
        
        fetch('{{ route("partner.calculator.calculate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayResults(data.results);
            } else {
                alert('Ошибка при расчете материалов: ' + (data.message || 'Неизвестная ошибка'));
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
            alert('Произошла ошибка при расчете');
        });
    }

    // Отображение результатов с ценами
    function displayResults(results) {
        const tbody = document.getElementById('results-tbody');
        tbody.innerHTML = '';
        
        let totalCost = 0;
        
        results.forEach(result => {
            const row = document.createElement('tr');
            
            const volumeText = ['linear', 'linear_layer'].includes(result.material.calculation_type)
                ? result.volume + ' м.п.' 
                : result.volume + ' м²';
            
            const layerText = ['area_layer', 'linear_layer'].includes(result.material.calculation_type)
                ? result.layer
                : '—';
            
            const consumptionText = result.consumption.toFixed(1) + ' ' + result.unit;
            const packagesText = result.packages + ' ' + (result.material.package_unit || 'упак.');
            
            // Получаем цену материала
            const materialPrice = materialPrices[result.material.id] || 0;
            const materialCost = result.packages * materialPrice;
            totalCost += materialCost;
            
            row.innerHTML = `
                <td>${result.material.name}</td>
                <td>${volumeText}</td>
                <td>${layerText}</td>
                <td>${consumptionText}</td>
                <td>${packagesText}</td>
                <td class="text-end">${materialPrice.toLocaleString('ru-RU')} ₽</td>
                <td class="text-end font-weight-bold">${materialCost.toLocaleString('ru-RU')} ₽</td>
            `;
            
            tbody.appendChild(row);
        });
        
        // Обновляем общую стоимость
        document.getElementById('total-cost').textContent = totalCost.toLocaleString('ru-RU') + ' ₽';
        
        document.getElementById('results-container').style.display = 'block';
        document.getElementById('export-pdf').style.display = 'inline-block';
        scrollToResults();
    }

    // Функция загрузки цен материалов
    function loadMaterialPrices() {
        // Сначала пытаемся загрузить из localStorage
        const savedPrices = localStorage.getItem(PRICES_STORAGE_KEY);
        if (savedPrices) {
            try {
                materialPrices = JSON.parse(savedPrices);
            } catch (error) {
                console.error('Ошибка при загрузке цен:', error);
                materialPrices = {};
            }
        }
        
        // Затем загружаем с сервера
        fetch('{{ route("partner.calculator.get-prices") }}', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Объединяем серверные и локальные цены
                materialPrices = { ...data.prices, ...materialPrices };
                // Сохраняем в localStorage
                localStorage.setItem(PRICES_STORAGE_KEY, JSON.stringify(materialPrices));
            }
        })
        .catch(error => {
            console.error('Ошибка при загрузке цен с сервера:', error);
        });
    }
    
    // Функция заполнения таблицы цен
    function populatePricesTable() {
        const tbody = document.getElementById('prices-table-body');
        tbody.innerHTML = '';
        
        Object.values(materialsData).forEach(material => {
            const currentPrice = materialPrices[material.id] || 0;
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${material.id}</td>
                <td>${material.name}</td>
                <td>${material.package_unit}</td>
                <td>
                    <input type="number" 
                           class="form-control price-input" 
                           data-material-id="${material.id}"
                           value="${currentPrice}" 
                           min="0" 
                           step="0.01">
                </td>
                <td>
                    <button type="button" 
                            class="btn btn-sm btn-outline-success" 
                            onclick="resetSinglePrice(${material.id})"
                            title="Сбросить цену">
                        <i class="fas fa-undo"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(row);
        });
    }
    
    // Функция поиска в таблице цен
    document.addEventListener('input', function(e) {
        if (e.target.id === 'price-search') {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#prices-table-body tr');
            
            rows.forEach(row => {
                const materialName = row.cells[1].textContent.toLowerCase();
                if (materialName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    });

    // Экспорт в PDF
    document.getElementById('export-pdf').addEventListener('click', function() {
        const calculations = [];
        document.querySelectorAll('.material-row').forEach((row, index) => {
            const materialSelect = row.querySelector('.material-select');
            const volumeInput = row.querySelector('.volume-input');
            const layerInput = row.querySelector('.layer-input');
            
            if (materialSelect.value && volumeInput.value) {
                const calculation = {
                    material_id: materialSelect.value,
                    volume: volumeInput.value
                };
                
                if (!layerInput.disabled && layerInput.value) {
                    calculation.layer = layerInput.value;
                } else {
                    calculation.layer = 1;
                }
                
                calculations.push(calculation);
            }
        });
        
        if (calculations.length === 0) {
            alert('Нет данных для экспорта. Сначала выполните расчет.');
            return;
        }
        
        const exportForm = document.createElement('form');
        exportForm.method = 'POST';
        exportForm.action = '{{ route("partner.calculator.export-pdf") }}';
        exportForm.style.display = 'none';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('input[name="_token"]').value;
        exportForm.appendChild(csrfToken);
        
        calculations.forEach((calculation, index) => {
            Object.keys(calculation).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `calculations[${index}][${key}]`;
                input.value = calculation[key];
                exportForm.appendChild(input);
            });
        });
        
        document.body.appendChild(exportForm);
        exportForm.submit();
        document.body.removeChild(exportForm);
    });

    // Инициализация
    updateLayerHint(document.querySelector('.material-select'));
    updateRemoveButtons();
    
    // Проверка мобильного устройства для улучшения UX
    function isMobile() {
        return window.innerWidth < 768;
    }
    
    // Если мобильное устройство, прокрутим к результатам с большим отступом
    function scrollToResults() {
        if (isMobile()) {
            document.getElementById('results-container').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start',
                inline: 'nearest'
            });
        } else {
            document.getElementById('results-container').scrollIntoView({ behavior: 'smooth' });
        }
    }
}
</script>

<style>
/* Стили для Select2 */
.select2-container {
    font-size: 0.875rem;
}

.select2-container--default .select2-selection--single {
    height: 38px;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #495057;
    line-height: 36px;
    padding-left: 12px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}

.select2-dropdown {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #007bff;
    color: white;
}

/* Адаптивность для мобильных устройств */
@media (max-width: 768px) {
    .select2-container {
        width: 100% !important;
    }
    
    .select2-dropdown {
        width: 100% !important;
    }
    
    /* Увеличенные размеры кнопок для сенсорных экранов */
    .btn {
        padding: 0.5rem 0.75rem;
        font-size: 1rem;
    }
    
    /* Адаптивная таблица результатов */
    #results-container .table {
        min-width: 650px;
    }
    
    /* Красиво сворачиваем кнопки */
    .btn .fas {
        margin-right: 3px;
    }
    
    /* Увеличенный размер шрифта для лучшей читаемости */
    label, input, select, button, .form-text {
        font-size: 1rem;
    }
    
    /* Модальное окно цен */
    #pricesModal .modal-dialog {
        margin: 0.5rem;
        max-width: 100%;
    }
    
    /* Стили для карточек */
    .card {
        border-radius: 0.5rem;
    }
    
    /* Увеличенные поля для улучшения читаемости */
    .card-body {
        padding: 1rem;
    }
    
    /* Улучшенное отображение слоя */
    .layer-hint {
        display: block;
        margin-top: 0.25rem;
    }
    
    /* Лучшие отступы в таблице */
    .table th, .table td {
        padding: 0.75rem;
    }
}

/* Дополнительные стили для маленьких экранов */
@media (max-width: 576px) {
    /* Скрываем лишние заголовки в таблице результатов и показываем их как подписи */
    .table-responsive {
        overflow-x: auto;
    }
    
    /* Адаптивное модальное окно цен */
    #pricesModal .price-input {
        max-width: 100px;
    }
    
    /* Адаптивная панель инструментов */
    .gap-2 {
        gap: 0.5rem !important;
    }
    
    /* Улучшенная навигация по странице */
    html {
        scroll-padding-top: 4rem;
    }
}

/* Для улучшения тап-области на мобильных */
.w-md-auto {
    width: auto;
}
</style>

<script>
// Ждем полной загрузки всех библиотек
window.addEventListener('load', function() {
    // Проверяем загрузку jQuery и Select2
    if (typeof jQuery === 'undefined') {
        console.error('jQuery не загружен');
        alert('Ошибка: jQuery не загружен');
        return;
    }
    
    if (typeof jQuery.fn.select2 === 'undefined') {
        console.error('Select2 не загружен');
        // Пытаемся загрузить Select2 динамически
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
        script.onload = function() {
            console.log('Select2 загружен динамически');
            initializeCalculator();
        };
        document.head.appendChild(script);
        return;
    }
    
    // Если все библиотеки загружены, инициализируем калькулятор
    initializeCalculator();
});

function initializeCalculator() {
    let materialIndex = 0;
    const materialsData = @json($materials);
    const STORAGE_KEY = 'calculator_materials_data';
    const PRICES_STORAGE_KEY = 'calculator_material_prices';
    
    // Объект для хранения цен материалов
    let materialPrices = {};
    
    // Загружаем цены при инициализации
    loadMaterialPrices();
    
    // Инициализируем Select2 для первого селекта
    initializeSelect2($('.material-select'));
    
    // Загружаем сохраненные данные при инициализации
    loadSavedData();
    
    // Функция инициализации Select2
    function initializeSelect2(elements) {
        try {
            elements.select2({
                placeholder: 'Поиск материала...',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "Материал не найден";
                    },
                    searching: function() {
                        return "Поиск...";
                    }
                }
            });
        } catch (error) {
            console.error('Ошибка инициализации Select2:', error);
            // Fallback - оставляем обычный select
        }
    }
    
    // Добавление нового материала
    document.getElementById('add-material').addEventListener('click', function() {
        materialIndex++;
        const container = document.getElementById('materials-container');
        const newRow = createMaterialRow(materialIndex);
        container.appendChild(newRow);
        
        // Инициализируем Select2 для нового селекта
        const newSelect = newRow.querySelector('.material-select');
        try {
            initializeSelect2($(newSelect));
        } catch (error) {
            console.warn('Не удалось инициализировать Select2 для нового элемента:', error);
        }
        
        updateRemoveButtons();
        saveDataToStorage();
    });

    // Обработка изменения материала
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('material-select')) {
            updateLayerHint(e.target);
            saveDataToStorage();
        }
    });

    // Обработка изменения значений в полях
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('volume-input') || e.target.classList.contains('layer-input')) {
            saveDataToStorage();
        }
    });

    // Удаление материала
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-material')) {
            const row = e.target.closest('.material-row');
            const select = row.querySelector('.material-select');
            
            // Уничтожаем Select2 перед удалением элемента
            try {
                if ($(select).hasClass('select2-hidden-accessible')) {
                    $(select).select2('destroy');
                }
            } catch (error) {
                console.warn('Ошибка при уничтожении Select2:', error);
            }
            
            row.remove();
            updateRemoveButtons();
            saveDataToStorage();
        }
    });

    // Отправка формы
    document.getElementById('calculator-form').addEventListener('submit', function(e) {
        e.preventDefault();
        calculateMaterials();
    });

    // Создание строки материала - обновляем для адаптивности
    function createMaterialRow(index) {
        const div = document.createElement('div');
        div.className = 'material-row mb-4';
        div.setAttribute('data-index', index);
        
        // Создаем опции для селекта
        let optionsHtml = '<option value="">Выберите материал</option>';
        Object.values(materialsData).forEach(material => {
            optionsHtml += `<option value="${material.id}">${material.name}</option>`;
        });
        
        div.innerHTML = `
            <div class="row align-items-end">
                <div class="col-md-4 col-12 mb-md-0 mb-2">
                    <label class="form-label">Материал</label>
                    <select name="calculations[${index}][material_id]" class="form-select material-select" required>
                        ${optionsHtml}
                    </select>
                </div>
                <div class="col-md-3 col-6 mb-md-0 mb-2">
                    <label class="form-label">Объем, м²/м.п.</label>
                    <input type="number" step="0.01" min="0" name="calculations[${index}][volume]" 
                           class="form-control volume-input" placeholder="0" required>
                </div>
                <div class="col-md-3 col-6 mb-md-0 mb-2">
                    <label class="form-label">Слой</label>
                    <input type="number" step="0.01" min="0" name="calculations[${index}][layer]" 
                           class="form-control layer-input" placeholder="0" value="1">
                    <small class="form-text text-muted layer-hint"></small>
                </div>
                <div class="col-md-2 col-12 text-md-start text-end mt-md-0 mt-2">
                    <button type="button" class="btn btn-outline-danger remove-material w-md-auto w-100" title="Удалить">
                        <i class="fas fa-trash"></i> <span class="d-inline d-md-none">Удалить</span>
                    </button>
                </div>
            </div>
        `;
        
        return div;
    }

    // Функция сохранения данных в локальное хранилище
    function saveDataToStorage() {
        const materialsDataToSave = [];
        
        document.querySelectorAll('.material-row').forEach((row, index) => {
            const materialSelect = row.querySelector('.material-select');
            const volumeInput = row.querySelector('.volume-input');
            const layerInput = row.querySelector('.layer-input');
            
            materialsDataToSave.push({
                material_id: materialSelect.value,
                volume: volumeInput.value,
                layer: layerInput.value,
                disabled: layerInput.disabled
            });
        });
        
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            materials: materialsDataToSave,
            timestamp: Date.now()
        }));
    }
    
    // Функция загрузки сохраненных данных
    function loadSavedData() {
        try {
            const savedData = localStorage.getItem(STORAGE_KEY);
            if (!savedData) return;
            
            const data = JSON.parse(savedData);
            
            // Проверяем, что данные не старше 24 часов
            const dayInMs = 24 * 60 * 60 * 1000;
            if (Date.now() - data.timestamp > dayInMs) {
                localStorage.removeItem(STORAGE_KEY);
                return;
            }
            
            if (data.materials && data.materials.length > 0) {
                // Очищаем текущий контейнер (кроме первой строки)
                const container = document.getElementById('materials-container');
                const firstRow = container.querySelector('.material-row');
                
                // Загружаем данные в первую строку
                if (data.materials[0]) {
                    loadDataToRow(firstRow, data.materials[0]);
                }
                
                // Добавляем остальные строки
                for (let i = 1; i < data.materials.length; i++) {
                    materialIndex++;
                    const newRow = createMaterialRow(materialIndex);
                    container.appendChild(newRow);
                    
                    // Инициализируем Select2 для нового селекта
                    const newSelect = newRow.querySelector('.material-select');
                    try {
                        initializeSelect2($(newSelect));
                    } catch (error) {
                        console.warn('Не удалось инициализировать Select2 при загрузке данных:', error);
                    }
                    
                    loadDataToRow(newRow, data.materials[i]);
                }
                
                updateRemoveButtons();
                showNotification('Данные калькулятора восстановлены из сохраненных', 'info');
            }
        } catch (error) {
            console.error('Ошибка при загрузке сохраненных данных:', error);
            localStorage.removeItem(STORAGE_KEY);
        }
    }
    
    // Функция загрузки данных в конкретную строку
    function loadDataToRow(row, data) {
        const materialSelect = row.querySelector('.material-select');
        const volumeInput = row.querySelector('.volume-input');
        const layerInput = row.querySelector('.layer-input');
        
        if (data.material_id) {
            try {
                $(materialSelect).val(data.material_id).trigger('change');
            } catch (error) {
                // Fallback для обычного select
                materialSelect.value = data.material_id;
                materialSelect.dispatchEvent(new Event('change'));
            }
            updateLayerHint(materialSelect);
        }
        
        if (data.volume) {
            volumeInput.value = data.volume;
        }
        
        if (data.layer && !layerInput.disabled) {
            layerInput.value = data.layer;
        }
    }
    
    // Глобальные функции для использования в onclick
    window.clearSavedData = function() {
        if (confirm('Вы уверены, что хотите очистить все сохраненные данные калькулятора?')) {
            localStorage.removeItem(STORAGE_KEY);
            
            // Сбрасываем форму к изначальному состоянию
            const container = document.getElementById('materials-container');
            const rows = container.querySelectorAll('.material-row');
            
            // Уничтожаем Select2 для всех селектов
            rows.forEach(row => {
                const select = row.querySelector('.material-select');
                try {
                    if ($(select).hasClass('select2-hidden-accessible')) {
                        $(select).select2('destroy');
                    }
                } catch (error) {
                    console.warn('Ошибка при уничтожении Select2:', error);
                }
            });
            
            // Удаляем все строки кроме первой
            for (let i = 1; i < rows.length; i++) {
                rows[i].remove();
            }
            
            // Очищаем первую строку
            const firstRow = container.querySelector('.material-row');
            const firstSelect = firstRow.querySelector('.material-select');
            try {
                $(firstSelect).val('').trigger('change');
                initializeSelect2($(firstSelect));
            } catch (error) {
                firstSelect.value = '';
                firstSelect.dispatchEvent(new Event('change'));
            }
            
            firstRow.querySelector('.volume-input').value = '';
            firstRow.querySelector('.layer-input').value = '1';
            firstRow.querySelector('.layer-input').disabled = false;
            firstRow.querySelector('.layer-hint').textContent = '';
            
            // Скрываем результаты
            document.getElementById('results-container').style.display = 'none';
            document.getElementById('export-pdf').style.display = 'none';
            
            updateRemoveButtons();
            showNotification('Сохраненные данные очищены', 'success');
        }
    };
    
    // Функция показа модального окна управления ценами
    window.showPricesModal = function() {
        populatePricesTable();
        const modal = new bootstrap.Modal(document.getElementById('pricesModal'));
        modal.show();
    };
    
    // Функция сохранения цен
    window.savePrices = function() {
        const priceInputs = document.querySelectorAll('.price-input');
        const newPrices = {};
        
        priceInputs.forEach(input => {
            const materialId = input.dataset.materialId;
            const price = parseFloat(input.value) || 0;
            newPrices[materialId] = price;
        });
        
        // Сохраняем в объект
        materialPrices = { ...materialPrices, ...newPrices };
        
        // Сохраняем в localStorage
        localStorage.setItem(PRICES_STORAGE_KEY, JSON.stringify(materialPrices));
        
        // Отправляем на сервер
        fetch('{{ route("partner.calculator.save-prices") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({ prices: newPrices })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Цены успешно сохранены', 'success');
                bootstrap.Modal.getInstance(document.getElementById('pricesModal')).hide();
            } else {
                showNotification('Ошибка при сохранении цен', 'danger');
            }
        })
        .catch(error => {
            console.error('Ошибка при сохранении цен:', error);
            showNotification('Ошибка при сохранении цен', 'danger');
        });
    };
    
    // Функция сброса всех цен
    window.resetPrices = function() {
        if (confirm('Вы уверены, что хотите сбросить все цены к значениям по умолчанию?')) {
            materialPrices = {};
            localStorage.removeItem(PRICES_STORAGE_KEY);
            populatePricesTable();
            showNotification('Цены сброшены к значениям по умолчанию', 'info');
        }
    };
    
    // Функция сброса одной цены
    window.resetSinglePrice = function(materialId) {
        delete materialPrices[materialId];
        localStorage.setItem(PRICES_STORAGE_KEY, JSON.stringify(materialPrices));
        
        // Обновляем поле ввода
        const input = document.querySelector(`input[data-material-id="${materialId}"]`);
        if (input) {
            // Загружаем дефолтную цену
            fetch('{{ route("partner.calculator.get-prices") }}', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.prices[materialId]) {
                    input.value = data.prices[materialId];
                }
            });
        }
    };
    
    // Функция показа уведомлений
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }

    // Обновление подсказки для поля "Слой"
    function updateLayerHint(selectElement) {
        const materialId = selectElement.value;
        const layerHint = selectElement.closest('.material-row').querySelector('.layer-hint');
        const layerInput = selectElement.closest('.material-row').querySelector('.layer-input');
        
        if (materialId && materialsData[materialId]) {
            const material = materialsData[materialId];
            
            switch(material.calculation_type) {
                case 'area_layer':
                    if (materialId == 12) { // Наливной пол
                        layerHint.textContent = 'Толщина в мм';
                    } else if (materialId == 4) { // Штукатурка
                        layerHint.textContent = 'Толщина слоя в мм';
                    } else if (materialId == 10) { // Пескобетон
                        layerHint.textContent = 'Толщина слоя в мм';
                    } else if (materialId == 11) { // Керамзит
                        layerHint.textContent = 'Толщина слоя в мм';
                    } else if (materialId == 13) { // Гипсокартон
                        layerHint.textContent = 'Количество слоев';
                    } else if (materialId == 52) { // Газосиликатные блоки
                        layerHint.textContent = 'Толщина стены в мм';
                    } else {
                        layerHint.textContent = 'Толщина слоя в мм';
                    }
                    layerInput.disabled = false;
                    layerInput.required = true;
                    layerInput.value = layerInput.value || '1';
                    break;
                    
                case 'linear_layer':
                    if (materialId == 14) { // Монтаж лобика
                        layerHint.textContent = 'Количество слоев';
                    } else {
                        layerHint.textContent = 'Количество слоев';
                    }
                    layerInput.disabled = false;
                    layerInput.required = true;
                    layerInput.value = layerInput.value || '1';
                    break;
                    
                case 'area':
                   
                    layerInput.disabled = true;
                    layerInput.required = false;
                    layerInput.value = 1;
                    break;
                    
                case 'linear':
                
                    layerInput.disabled = true;
                    layerInput.required = false;
                    layerInput.value = 1;
                    break;
                    
                default:
                    layerHint.textContent = '';
                    layerInput.disabled = false;
                    layerInput.required = true;
            }
        } else {
            layerHint.textContent = '';
            layerInput.disabled = false;
            layerInput.required = true;
        }
    }

    // Обновление кнопок удаления
    function updateRemoveButtons() {
        const removeButtons = document.querySelectorAll('.remove-material');
        removeButtons.forEach(button => {
            button.disabled = removeButtons.length <= 1;
        });
    }

    // Расчет материалов
    function calculateMaterials() {
        const calculations = [];
        document.querySelectorAll('.material-row').forEach((row, index) => {
            const materialSelect = row.querySelector('.material-select');
            const volumeInput = row.querySelector('.volume-input');
            const layerInput = row.querySelector('.layer-input');
            
            if (materialSelect.value && volumeInput.value) {
                const materialId = parseInt(materialSelect.value);
                const material = materialsData[materialId];
                
                const calculation = {
                    material_id: materialId,
                    volume: volumeInput.value
                };
                
                if (!layerInput.disabled && layerInput.value && 
                    ['area_layer', 'linear_layer'].includes(material.calculation_type)) {
                    calculation.layer = layerInput.value;
                } else {
                    calculation.layer = 1;
                }
                
                calculations.push(calculation);
            }
        });
        
        if (calculations.length === 0) {
            alert('Добавьте хотя бы один материал для расчета');
            return;
        }
        
        const requestData = {
            calculations: calculations,
            _token: document.querySelector('input[name="_token"]').value
        };
        
        fetch('{{ route("partner.calculator.calculate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayResults(data.results);
            } else {
                alert('Ошибка при расчете материалов: ' + (data.message || 'Неизвестная ошибка'));
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
            alert('Произошла ошибка при расчете');
        });
    }

    // Отображение результатов с ценами
    function displayResults(results) {
        const tbody = document.getElementById('results-tbody');
        tbody.innerHTML = '';
        
        let totalCost = 0;
        
        results.forEach(result => {
            const row = document.createElement('tr');
            
            const volumeText = ['linear', 'linear_layer'].includes(result.material.calculation_type)
                ? result.volume + ' м.п.' 
                : result.volume + ' м²';
            
            const layerText = ['area_layer', 'linear_layer'].includes(result.material.calculation_type)
                ? result.layer
                : '—';
            
            const consumptionText = result.consumption.toFixed(1) + ' ' + result.unit;
            const packagesText = result.packages + ' ' + (result.material.package_unit || 'упак.');
            
            // Получаем цену материала
            const materialPrice = materialPrices[result.material.id] || 0;
            const materialCost = result.packages * materialPrice;
            totalCost += materialCost;
            
            row.innerHTML = `
                <td>${result.material.name}</td>
                <td>${volumeText}</td>
                <td>${layerText}</td>
                <td>${consumptionText}</td>
                <td>${packagesText}</td>
                <td class="text-end">${materialPrice.toLocaleString('ru-RU')} ₽</td>
                <td class="text-end font-weight-bold">${materialCost.toLocaleString('ru-RU')} ₽</td>
            `;
            
            tbody.appendChild(row);
        });
        
        // Обновляем общую стоимость
        document.getElementById('total-cost').textContent = totalCost.toLocaleString('ru-RU') + ' ₽';
        
        document.getElementById('results-container').style.display = 'block';
        document.getElementById('export-pdf').style.display = 'inline-block';
        scrollToResults();
    }

    // Функция загрузки цен материалов
    function loadMaterialPrices() {
        // Сначала пытаемся загрузить из localStorage
        const savedPrices = localStorage.getItem(PRICES_STORAGE_KEY);
        if (savedPrices) {
            try {
                materialPrices = JSON.parse(savedPrices);
            } catch (error) {
                console.error('Ошибка при загрузке цен:', error);
                materialPrices = {};
            }
        }
        
        // Затем загружаем с сервера
        fetch('{{ route("partner.calculator.get-prices") }}', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Объединяем серверные и локальные цены
                materialPrices = { ...data.prices, ...materialPrices };
                // Сохраняем в localStorage
                localStorage.setItem(PRICES_STORAGE_KEY, JSON.stringify(materialPrices));
            }
        })
        .catch(error => {
            console.error('Ошибка при загрузке цен с сервера:', error);
        });
    }
    
    // Функция заполнения таблицы цен
    function populatePricesTable() {
        const tbody = document.getElementById('prices-table-body');
        tbody.innerHTML = '';
        
        Object.values(materialsData).forEach(material => {
            const currentPrice = materialPrices[material.id] || 0;
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${material.id}</td>
                <td>${material.name}</td>
                <td>${material.package_unit}</td>
                <td>
                    <input type="number" 
                           class="form-control price-input" 
                           data-material-id="${material.id}"
                           value="${currentPrice}" 
                           min="0" 
                           step="0.01">
                </td>
                <td>
                    <button type="button" 
                            class="btn btn-sm btn-outline-success" 
                            onclick="resetSinglePrice(${material.id})"
                            title="Сбросить цену">
                        <i class="fas fa-undo"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(row);
        });
    }
    
    // Функция поиска в таблице цен
    document.addEventListener('input', function(e) {
        if (e.target.id === 'price-search') {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#prices-table-body tr');
            
            rows.forEach(row => {
                const materialName = row.cells[1].textContent.toLowerCase();
                if (materialName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    });

    // Экспорт в PDF
    document.getElementById('export-pdf').addEventListener('click', function() {
        const calculations = [];
        document.querySelectorAll('.material-row').forEach((row, index) => {
            const materialSelect = row.querySelector('.material-select');
            const volumeInput = row.querySelector('.volume-input');
            const layerInput = row.querySelector('.layer-input');
            
            if (materialSelect.value && volumeInput.value) {
                const calculation = {
                    material_id: materialSelect.value,
                    volume: volumeInput.value
                };
                
                if (!layerInput.disabled && layerInput.value) {
                    calculation.layer = layerInput.value;
                } else {
                    calculation.layer = 1;
                }
                
                calculations.push(calculation);
            }
        });
        
        if (calculations.length === 0) {
            alert('Нет данных для экспорта. Сначала выполните расчет.');
            return;
        }
        
        const exportForm = document.createElement('form');
        exportForm.method = 'POST';
        exportForm.action = '{{ route("partner.calculator.export-pdf") }}';
        exportForm.style.display = 'none';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('input[name="_token"]').value;
        exportForm.appendChild(csrfToken);
        
        calculations.forEach((calculation, index) => {
            Object.keys(calculation).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `calculations[${index}][${key}]`;
                input.value = calculation[key];
                exportForm.appendChild(input);
            });
        });
        
        document.body.appendChild(exportForm);
        exportForm.submit();
        document.body.removeChild(exportForm);
    });

    // Инициализация
    updateLayerHint(document.querySelector('.material-select'));
    updateRemoveButtons();
    
    // Проверка мобильного устройства для улучшения UX
    function isMobile() {
        return window.innerWidth < 768;
    }
    
    // Если мобильное устройство, прокрутим к результатам с большим отступом
    function scrollToResults() {
        if (isMobile()) {
            document.getElementById('results-container').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start',
                inline: 'nearest'
            });
        } else {
            document.getElementById('results-container').scrollIntoView({ behavior: 'smooth' });
        }
    }
}
</script>
@endpush
@endsection
