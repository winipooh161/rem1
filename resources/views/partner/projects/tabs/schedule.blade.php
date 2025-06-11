<div class="schedule-container mb-4">
    <!-- Информация о сроках -->
    <div class="schedule-info mb-4">
        <div class="card">
            <div class="card-body">
                <h5>План график проекта</h5>
                <div class="d-flex flex-wrap align-items-center mb-3">
                    <div class="me-4 mb-2 mb-md-0">
                        <strong>Срок ремонта:</strong> 
                        <span id="project-duration">184 дней, 26.3 недель, 6.1 месяца</span>
                    </div>
                    <div class="d-flex flex-wrap">
                        <a href="#" id="download-schedule" class="btn btn-outline-primary btn-sm me-2 mb-2 mb-md-0">
                            <i class="fas fa-download me-1"></i> Скачать
                        </a>
                        <button type="button" class="btn btn-outline-secondary btn-sm me-2 mb-2 mb-md-0" 
                                data-bs-toggle="modal" data-bs-target="#scheduleUrlModal">
                            <i class="fas fa-link me-1"></i> Указать ссылку на линейный график
                        </button>
                        <button type="button" id="create-template" class="btn btn-outline-success btn-sm mb-2 mb-md-0">
                            <i class="fas fa-file-excel me-1"></i> Создать шаблон
                        </button>
                    </div>
                </div>
                
                @if(isset($project->schedule_link) && $project->schedule_link)
                <div class="mb-3">
                    <strong>Внешний линейный график:</strong> 
                    <a href="{{ $project->schedule_link }}" target="_blank">{{ $project->schedule_link }}</a>
                </div>
                @endif
                
                <!-- Фильтры -->
                <div class="schedule-filters card mt-3">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-8">
                                <div class="d-flex flex-wrap">
                                    <div class="input-group input-group-sm me-2 mb-2" style="max-width: 200px;">
                                        <span class="input-group-text">С</span>
                                        <input type="date" class="form-control" id="date-from" value="{{ date('Y-m-d') }}">
                                    </div>
                                    <div class="input-group input-group-sm me-2 mb-2" style="max-width: 200px;">
                                        <span class="input-group-text">По</span>
                                        <input type="date" class="form-control" id="date-to" value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                                    </div>
                                    <button class="btn btn-sm btn-primary mb-2" id="apply-filter">Применить</button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex flex-wrap justify-content-md-end">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="filter-completed" checked>
                                        <label class="form-check-label" for="filter-completed">
                                            <span class="badge bg-success">Завершено</span>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="filter-in-progress" checked>
                                        <label class="form-check-label" for="filter-in-progress">
                                            <span class="badge bg-primary">В работе</span>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="filter-pending" checked>
                                        <label class="form-check-label" for="filter-pending">
                                            <span class="badge bg-warning">Ожидание</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Excel редактор -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Редактирование графика работ</strong>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-success" id="save-excel">
                    <i class="fas fa-save me-1"></i> Сохранить изменения
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="loading-indicator" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
                <p class="mt-2">Загрузка редактора...</p>
            </div>
            <div id="excel-editor" style="width: 100%; height: 600px; overflow: auto; display: none;"></div>
        </div>
    </div>
</div>

<!-- Модальное окно для указания ссылки на линейный график -->
<div class="modal fade" id="scheduleUrlModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ссылка на линейный график</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('partner.projects.update', $project) }}" method="POST" id="schedule-link-form">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="schedule_link" class="form-label">Укажите ссылку на внешний график:</label>
                        <input type="url" class="form-control" id="schedule_link" name="schedule_link" 
                               value="{{ $project->schedule_link ?? '' }}" placeholder="https://...">
                        <div class="form-text">Например, ссылка на Google Sheets или Microsoft Project Online</div>
                    </div>
                    <input type="hidden" name="update_type" value="schedule_link">
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Подключаем библиотеки для Excel в браузере -->
<!-- Важно: добавляем версии, чтобы исключить проблемы с кэшированием -->
<link href="https://cdn.jsdelivr.net/npm/handsontable@12.4.0/dist/handsontable.full.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/handsontable@12.4.0/dist/handsontable.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exceljs@4.3.0/dist/exceljs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>

<script>
// Проверка загрузки библиотек
function checkLibrariesLoaded() {
    if (typeof Handsontable === 'undefined') {
        console.error('Ошибка: Библиотека Handsontable не загружена');
        alert('Ошибка загрузки редактора таблиц. Пожалуйста, обновите страницу.');
        return false;
    }
    if (typeof ExcelJS === 'undefined') {
        console.error('Ошибка: Библиотека ExcelJS не загружена');
        alert('Ошибка загрузки модуля Excel. Пожалуйста, обновите страницу.');
        return false;
    }
    if (typeof saveAs === 'undefined') {
        console.error('Ошибка: Библиотека FileSaver не загружена');
        alert('Ошибка загрузки модуля сохранения файлов. Пожалуйста, обновите страницу.');
        return false;
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    // Проверяем загрузку библиотек
    if (!checkLibrariesLoaded()) return;
    
    console.log('Инициализация Excel-редактора...');
    
    // Отображаем элемент загрузки
    const loadingIndicator = document.getElementById('loading-indicator');
    const container = document.getElementById('excel-editor');
    
    if (!container) {
        console.error('Ошибка: Элемент #excel-editor не найден');
        return;
    }
    
    console.log('Контейнер excel-editor найден:', container);
    
    // Пример данных для таблицы
    const initialData = [
        ['Наименование', 'Статус', 'Начало', 'Конец', 'Дней', 'Комментарий'],
        ['Завоз инструмента и заезд ремонтной бригады', 'Готово', '25.08.2023', '25.08.2023', 0, ''],
        ['Демонтаж перегородок и стен', 'Готово', '26.08.2023', '28.08.2023', 2, ''],
        ['Демонтаж напольного покрытия', 'Готово', '29.08.2023', '30.08.2023', 1, ''],
        ['Демонтаж дверей', 'В работе', '31.08.2023', '31.08.2023', 0, ''],
        ['Демонтаж электрики', 'Ожидание', '01.09.2023', '03.09.2023', 2, ''],
        ['Возведение перегородок', 'Ожидание', '04.09.2023', '10.09.2023', 6, ''],
        ['Электромонтажные работы', 'Ожидание', '11.09.2023', '20.09.2023', 9, ''],
        ['Сантехнические работы', 'Ожидание', '21.09.2023', '30.09.2023', 9, ''],
        ['Выравнивание стен', 'Ожидание', '01.10.2023', '15.10.2023', 14, '']
    ];
    
    try {
        // Создаем экземпляр таблицы Handsontable
        const hot = new Handsontable(container, {
            data: initialData,
            rowHeaders: true,
            colHeaders: true,
            columnSorting: true,
            contextMenu: true,
            manualRowResize: true,
            manualColumnResize: true,
            licenseKey: 'non-commercial-and-evaluation',
            stretchH: 'all',
            autoWrapRow: true,
            height: '100%',
            colHeaders: ['Наименование', 'Статус', 'Начало', 'Конец', 'Дней', 'Комментарий'],
            columns: [
                { type: 'text' },
                { 
                    type: 'dropdown', 
                    source: ['Готово', 'В работе', 'Ожидание', 'Отменено']
                },
                { type: 'date', dateFormat: 'DD.MM.YYYY' },
                { type: 'date', dateFormat: 'DD.MM.YYYY' },
                { type: 'numeric' },
                { type: 'text' }
            ],
            dropdownMenu: true,
            filters: true,
            cell: [
                { row: 0, col: 0, className: 'htCenter htMiddle' },
                { row: 0, col: 1, className: 'htCenter htMiddle' },
                { row: 0, col: 2, className: 'htCenter htMiddle' },
                { row: 0, col: 3, className: 'htCenter htMiddle' },
                { row: 0, col: 4, className: 'htCenter htMiddle' },
                { row: 0, col: 5, className: 'htCenter htMiddle' }
            ],
            afterRender: function() {
                console.log('Таблица отрендерена');
                if (loadingIndicator) loadingIndicator.style.display = 'none';
                container.style.display = 'block'; // Показываем контейнер после рендеринга
            }
        });
        
        console.log('Таблица инициализирована');
        
        // Функция для загрузки Excel файла, если он существует
        function loadExcelFile() {
            console.log('Попытка загрузить файл расписания...');
            
            fetch(`{{ route('partner.projects.schedule-file', $project->id) }}`, {
                headers: {
                    'Accept': 'application/json, application/octet-stream',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    if (response.headers.get('content-type') && response.headers.get('content-type').includes('application/json')) {
                        return response.json().then(data => {
                            console.log('Файл не найден, создаем новый шаблон');
                            hot.loadData(initialData);
                            if (loadingIndicator) loadingIndicator.style.display = 'none';
                            container.style.display = 'block';
                            throw new Error('Файл не найден');
                        });
                    }
                    return response.arrayBuffer();
                } else if (response.status === 404) {
                    console.log('Файл не найден, создаем новый шаблон');
                    hot.loadData(initialData);
                    if (loadingIndicator) loadingIndicator.style.display = 'none';
                    container.style.display = 'block';
                    throw new Error('Файл не найден');
                } else {
                    throw new Error('Ошибка сервера: ' + response.status);
                }
            })
            .then(buffer => {
                // Парсим Excel и заполняем таблицу
                const workbook = new ExcelJS.Workbook();
                return workbook.xlsx.load(buffer).then(workbook => {
                    const worksheet = workbook.getWorksheet(1);
                    
                    if (!worksheet) {
                        console.error('Рабочий лист не найден в файле Excel');
                        hot.loadData(initialData);
                        return;
                    }
                    
                    const data = [];
                    worksheet.eachRow((row, rowIndex) => {
                        const rowData = [];
                        row.eachCell((cell, colIndex) => {
                            rowData.push(cell.value);
                        });
                        data.push(rowData);
                    });
                    
                    if (data.length === 0) {
                        console.log('Файл Excel пуст, используем шаблон');
                        hot.loadData(initialData);
                    } else {
                        hot.loadData(data);
                    }
                });
            })
            .catch(error => {
                console.error('Ошибка загрузки файла:', error);
                if (hot) {
                    hot.loadData(initialData);
                }
            })
            .finally(() => {
                if (loadingIndicator) loadingIndicator.style.display = 'none';
                if (container) container.style.display = 'block';
            });
        }
        
        // Вызываем функцию загрузки файла при инициализации
        loadExcelFile();
        
        // Обработчик кнопки "Создать шаблон"
        document.getElementById('create-template').addEventListener('click', function() {
            if (confirm('Вы уверены, что хотите создать новый пустой шаблон? Текущие данные будут потеряны, если вы их не сохранили.')) {
                // Создаем шаблон для плана-графика работ
                const templateData = [
                    ['Наименование', 'Статус', 'Начало', 'Конец', 'Дней', 'Комментарий'],
                    ['Завоз инструмента и заезд ремонтной бригады', 'В работе', '', '', '', ''],
                    ['(ЗАКУПКА) Общестроительные материалы', 'В работе', '', '', '', ''],
                    ['Подготовка объекта', 'В работе', '', '', '', ''],
                    ['Демонтажные работы + временные коммуникации', 'В работе', '', '', '', ''],
                    ['Возведение перегородок', 'В работе', '', '', '', ''],
                    ['Оштукатуривание стен', 'В работе', '', '', '', ''],
                    ['(ЗАКУПКА) Электромонтажные и сантехнические материалы, отопление', 'В работе', '', '', '', ''],
                    ['Электромонтажные работы', 'В работе', '', '', '', ''],
                    ['Сантехнические работы', 'В работе', '', '', '', ''],
                    ['Стяжка', 'В работе', '', '', '', ''],
                    ['(ЗАКУПКА) Материалы для малярных работ', 'В работе', '', '', '', ''],
                    ['Подготовка стен под финишную отделку', 'В работе', '', '', '', ''],
                    ['Подготовка откосов под отделку', 'В работе', '', '', '', ''],
                    ['(ЗАКУПКА) Краска, расходники к покраске', 'В работе', '', '', '', ''],
                    ['Завершение всех пыльных и грязных работ', 'В работе', '', '', '', ''],
                    ['Финишная отделка стен', 'В работе', '', '', '', ''],
                    ['Конструкции ГКЛ', 'В работе', '', '', '', ''],
                    ['(ЗАКУПКА) Плитка и расходники', 'В работе', '', '', '', ''],
                    ['Финишная отделка откосов', 'В работе', '', '', '', ''],
                    ['Уборка объекта (предчистовая)', 'В работе', '', '', '', ''],
                    ['(ЗАКУПКА) Сантехническое оборудование', 'В работе', '', '', '', ''],
                    ['(ЗАКУПКА) Чистовая электрика', 'В работе', '', '', '', ''],
                    ['(ЗАКУПКА) Напольное покрытие и расходники', 'В работе', '', '', '', ''],
                    ['Душевой поддон + трап', 'В работе', '', '', '', ''],
                    ['Напольные покрытия', 'В работе', '', '', '', ''],
                    ['Плинтус и стыки', 'В работе', '', '', '', ''],
                    ['Монтаж электрики чистовой', 'В работе', '', '', '', ''],
                    ['Монтаж сантехники - чистовой', 'В работе', '', '', '', ''],
                    ['Напольная плитка + теплый пол', 'В работе', '', '', '', ''],
                    ['Настенная плитка + инсталляция', 'В работе', '', '', '', ''],
                    ['Финишная доработка объекта', 'В работе', '', '', '', ''],
                    ['Передача ключей', 'В работе', '', '', '', '']
                ];
                
                // Загружаем шаблон в таблицу
                hot.loadData(templateData);
                
                // Показываем уведомление
                alert('Шаблон плана-графика создан. При необходимости укажите даты и сохраните изменения.');
            }
        });
        
        // Сохранение изменений в Excel
        document.getElementById('save-excel').addEventListener('click', function() {
            const data = hot.getData();
            
            const workbook = new ExcelJS.Workbook();
            workbook.creator = 'Remont Admin';
            workbook.created = new Date();
            
            const worksheet = workbook.addWorksheet('План-график');
            
            data.forEach(rowData => {
                worksheet.addRow(rowData);
            });
            
            worksheet.getRow(1).font = { bold: true };
            worksheet.getRow(1).alignment = { vertical: 'middle', horizontal: 'center' };
            
            worksheet.columns.forEach((column, index) => {
                let maxLength = 0;
                column.eachCell({ includeEmpty: true }, (cell, rowIndex) => {
                    const length = cell.value ? cell.value.toString().length : 10;
                    if (length > maxLength) {
                        maxLength = length;
                    }
                });
                worksheet.getColumn(index + 1).width = Math.min(maxLength + 2, 50);
            });
            
            workbook.xlsx.writeBuffer().then(buffer => {
                const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                
                const formData = new FormData();
                formData.append('excel_file', blob, 'schedule.xlsx');
                formData.append('_token', '{{ csrf_token() }}');
                
                fetch(`{{ route('partner.projects.schedule-file.store', $project->id) }}`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('График успешно сохранен');
                    } else {
                        alert('Ошибка при сохранении: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Ошибка при сохранении файла:', error);
                    alert('Ошибка при сохранении файла');
                });
            });
        });
        
        // Обработчик для скачивания файла
        document.getElementById('download-schedule').addEventListener('click', function(e) {
            e.preventDefault();
            
            const data = hot.getData();
            
            // Создаем новую книгу Excel
            const workbook = new ExcelJS.Workbook();
            workbook.creator = 'Remont Admin';
            workbook.created = new Date();
            
            // Добавляем новый лист
            const worksheet = workbook.addWorksheet('План-график');
            
            // Заполняем данными
            data.forEach(rowData => {
                worksheet.addRow(rowData);
            });
            
            // Стилизуем заголовок
            worksheet.getRow(1).font = { bold: true };
            worksheet.getRow(1).alignment = { vertical: 'middle', horizontal: 'center' };
            
            // Автоматическая ширина столбцов
            worksheet.columns.forEach((column, index) => {
                let maxLength = 0;
                column.eachCell({ includeEmpty: true }, (cell, rowIndex) => {
                    const length = cell.value ? cell.value.toString().length : 10;
                    if (length > maxLength) {
                        maxLength = length;
                    }
                });
                worksheet.getColumn(index + 1).width = Math.min(maxLength + 2, 50);
            });
            
            // Скачиваем файл
            workbook.xlsx.writeBuffer().then(buffer => {
                const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                saveAs(blob, `План-график_{{ $project->id }}_${new Date().toLocaleDateString()}.xlsx`);
            });
        });
        
        // Обработчики фильтров
        document.getElementById('apply-filter').addEventListener('click', function() {
            // Логика фильтрации по датам
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;
            
            // Здесь должна быть логика фильтрации по датам
            console.log('Фильтрация с', dateFrom, 'по', dateTo);
            
            // Пример реализации фильтрации
            hot.getPlugin('filters').clearConditions();
            hot.getPlugin('filters').filter();
        });
        
        // Обработчики чекбоксов для фильтрации по статусам
        document.querySelectorAll('#filter-completed, #filter-in-progress, #filter-pending').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                // Логика фильтрации по статусам
                const showCompleted = document.getElementById('filter-completed').checked;
                const showInProgress = document.getElementById('filter-in-progress').checked;
                const showPending = document.getElementById('filter-pending').checked;
                
                // Здесь должна быть логика фильтрации по статусам
                console.log('Показывать:', { 
                    completed: showCompleted, 
                    inProgress: showInProgress, 
                    pending: showPending 
                });
                
                // Пример реализации фильтрации по статусам
                hot.getPlugin('filters').clearConditions(1);
                
                let conditions = [];
                if (showCompleted) conditions.push('Готово');
                if (showInProgress) conditions.push('В работе');
                if (showPending) conditions.push('Ожидание');
                
                if (conditions.length > 0 && conditions.length < 3) {
                    hot.getPlugin('filters').addCondition(1, 'contains', conditions);
                    hot.getPlugin('filters').filter();
                } else {
                    hot.getPlugin('filters').filter(); // Либо все показать, либо ничего
                }
            });
        });
    } catch (error) {
        console.error('Ошибка при инициализации таблицы:', error);
        if (loadingIndicator) {
            loadingIndicator.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Ошибка загрузки редактора таблиц: ${error.message}
                </div>
                <button class="btn btn-primary mt-2" onclick="window.location.reload()">
                    Обновить страницу
                </button>
            `;
        }
    }
});
</script>

<style>
/* Стили для адаптивности таблицы */
.handsontable {
    font-size: 14px;
}

/* Явно задаем размеры контейнера таблицы */
#excel-editor {
    min-height: 600px;
    border: 1px solid #ddd;
    background-color: #fff;
}

/* Стили для индикатора загрузки */
#loading-indicator {
    min-height: 200px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

/* Стили для заголовка таблицы */
.handsontable .htCore th {
    background-color: #f8f9fa;
    font-weight: bold;
    text-align: center;
}

/* Медиа-запросы для адаптивности */
@media (max-width: 768px) {
    #excel-editor {
        height: 400px;
    }
    
    .handsontable {
        font-size: 12px;
    }
}

/* Устранение артефактов для не-премиум версии */
.hot-display-license-info {
    display: none !important;
}
</style>
