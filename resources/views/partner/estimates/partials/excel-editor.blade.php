<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>Редактирование сметы</div>
        <div>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" id="toggleColumnsBtn" title="Показать/скрыть пустые столбцы">
                    <i class="fas fa-columns"></i> Все столбцы
                </button>
                <button type="button" class="btn btn-outline-secondary" id="recalcAllBtn" title="Пересчитать все формулы">
                    <i class="fas fa-calculator"></i> Пересчитать
                </button>
                <button type="button" class="btn btn-outline-secondary" id="addRowBtn" title="Добавить строку">
                    <i class="fas fa-plus"></i> Строка
                </button>
                <button type="button" class="btn btn-outline-secondary" id="addSectionBtn" title="Добавить заголовок раздела">
                    <i class="fas fa-heading"></i> Раздел
                </button>
                <button type="button" class="btn btn-outline-secondary" id="updateNumberingBtn" title="Обновить нумерацию">
                    <i class="fas fa-sort-numeric-down"></i> Номерация
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- Контейнер для выбора листов Excel -->
        <div class="bg-light border-bottom p-2 d-flex justify-content-between align-items-center" id="sheetTabsContainer">
            <div id="sheetTabs" class="overflow-auto flex-grow-1"></div>
            <div class="ms-2">
                <button type="button" class="btn btn-sm btn-outline-success" id="addSheetBtn" title="Добавить лист">
                    <i class="fas fa-plus"></i> Лист
                </button>
            </div>
        </div>

        <!-- Индикатор загрузки -->
        <div id="loadingIndicator" class="position-absolute top-50 start-50 translate-middle bg-white p-3 rounded shadow" style="display: none; z-index: 1000;">
            <div class="d-flex align-items-center">
                <div class="spinner-border text-primary me-2" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
                <span>Загрузка данных...</span>
            </div>
        </div>

        <!-- Контейнер для редактора Excel -->
        <div id="excelEditor" style="height: 600px; width: 100%; overflow: hidden;"></div>
    </div>
    <div class="card-footer d-flex justify-content-end">
        <button type="button" id="submitBtn" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>{{ isset($estimate) ? 'Сохранить смету' : 'Создать смету' }}
        </button>
    </div>
</div>

<script>
// Функция для отображения/скрытия индикатора загрузки
function showLoading(show) {
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (loadingIndicator) {
        loadingIndicator.style.display = show ? 'block' : 'none';
    }
}

// Обработчик кнопки сохранения
document.getElementById('submitBtn').addEventListener('click', function() {
    if (typeof saveExcelToServer === 'function') {
        saveExcelToServer();
    } else {
        console.error('Function saveExcelToServer is not defined');
    }
});

// Обработчик кнопки добавления строки
document.getElementById('addRowBtn').addEventListener('click', function() {
    if (typeof addNewRow === 'function') {
        addNewRow();
    } else {
        console.error('Function addNewRow is not defined');
    }
});

// Обработчик кнопки добавления раздела
document.getElementById('addSectionBtn').addEventListener('click', function() {
    if (typeof addNewSection === 'function') {
        addNewSection();
    } else {
        console.error('Function addNewSection is not defined');
    }
});

// Обработчик кнопки обновления нумерации
document.getElementById('updateNumberingBtn').addEventListener('click', function() {
    if (typeof updateAllRowNumbers === 'function') {
        updateAllRowNumbers();
    } else {
        console.error('Function updateAllRowNumbers is not defined');
    }
});

// Обработчик кнопки пересчета формул
document.getElementById('recalcAllBtn').addEventListener('click', function() {
    if (typeof recalculateAllFormulas === 'function') {
        recalculateAllFormulas();
    } else {
        console.error('Function recalculateAllFormulas is not defined');
    }
});

// Обработчик кнопки добавления листа
document.getElementById('addSheetBtn').addEventListener('click', function() {
    if (typeof addNewSheet === 'function') {
        addNewSheet();
    } else {
        console.error('Function addNewSheet is not defined');
    }
});

// Кнопка сохранения в боковой панели (если существует)
const saveExcelBtn = document.getElementById('saveExcelBtn');
if (saveExcelBtn) {
    saveExcelBtn.addEventListener('click', function() {
        if (typeof saveExcelToServer === 'function') {
            saveExcelToServer();
        } else {
            console.error('Function saveExcelToServer is not defined');
        }
    });
}

// Функция обновления индикатора статуса изменений
function updateStatusIndicator() {
    const statusIndicator = document.getElementById('statusIndicator');
    if (statusIndicator) {
        statusIndicator.style.display = isFileModified ? 'block' : 'none';
    }
    
    // Обновляем надписи на кнопке сохранения
    const saveBtn = document.getElementById('saveExcelBtn');
    if (saveBtn) {
        if (isFileModified) {
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Сохранить изменения*';
            saveBtn.classList.add('btn-primary');
            saveBtn.classList.remove('btn-outline-primary');
        } else {
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Сохранить изменения';
            saveBtn.classList.add('btn-outline-primary');
            saveBtn.classList.remove('btn-primary');
        }
    }
}
</script>
