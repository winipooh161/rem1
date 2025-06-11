// Глобальные переменные для работы с Excel
let hot;
let currentSheetIndex = 0;
let workbook = null;
let sheets = [];
let isApplyingStyles = false;
let isFileModified = false;
let fileStructure = null;

/**
 * Инициализация редактора Excel
 * @param {string} dataUrl Опциональный URL для загрузки данных
 */
function initExcelEditor(dataUrl = null) {
    console.log('Initializing Excel editor');
    
    const container = document.getElementById('excelEditor');
    
    // Проверяем наличие контейнера
    if (!container) {
        console.error('Excel editor container not found!');
        return;
    }
    
    console.log('Container found, initializing Handsontable');
    
    // Устанавливаем фиксированную высоту
    container.style.height = '600px';
    
    // Если есть URL данных, сначала загружаем структуру файла
    if (dataUrl) {
        console.log('Loading existing file structure');
        loadExcelFileStructure(dataUrl, function(structure) {
            initHandsontableWithStructure(structure, dataUrl); // Передаем URL данных вторым параметром
        });
    } else {
        // Если это новый файл, используем стандартную структуру
        initHandsontableWithStructure({
            columnCount: 10,
            readOnlyColumns: [5, 8, 9], // Индексы колонок, которые нельзя редактировать (формулы)
            hasHeaders: true
        }, null); // Передаем null вместо URL
    }
}

/**
 * Загружает структуру Excel-файла с сервера
 * @param {string} url URL для загрузки данных
 * @param {function} callback Функция обратного вызова с результатом
 */
function loadExcelFileStructure(url, callback) {
    if (!url) {
        // Если URL не предоставлен, используем стандартную структуру
        callback({
            columnCount: 10,
            readOnlyColumns: [5, 8, 9],
            hasHeaders: true
        });
        return;
    }

    fetch(url + '?structure=true')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Ошибка загрузки: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Структура Excel получена');
                
                // Определяем структуру файла
                const structure = {
                    columnCount: data.structure.columnCount || 10,
                    readOnlyColumns: data.structure.readOnlyColumns || [5, 8, 9],
                    readOnlyCells: data.structure.readOnlyCells || [],
                    hasHeaders: data.structure.hasHeaders !== false,
                    columnWidths: data.structure.columnWidths || []
                };
                
                fileStructure = structure; // Сохраняем структуру глобально
                callback(structure);
            } else {
                console.error('Ошибка получения структуры:', data.message);
                // Используем стандартную структуру в случае ошибки
                callback({
                    columnCount: 10,
                    readOnlyColumns: [5, 8, 9],
                    hasHeaders: true
                });
            }
        })
        .catch(error => {
            console.error('Ошибка при определении структуры файла:', error);
            // Используем стандартную структуру в случае ошибки
            callback({
                columnCount: 10,
                readOnlyColumns: [5, 8, 9],
                hasHeaders: true
            });
        });
}

/**
 * Инициализирует редактор Handsontable с учетом структуры файла
 * @param {Object} structure Структура файла
 * @param {string|null} dataUrl URL для загрузки данных или null для нового файла
 */
function initHandsontableWithStructure(structure, dataUrl) {
    const container = document.getElementById('excelEditor');
    
    try {
        // Создаем экземпляр Handsontable с учетом структуры файла
        hot = new Handsontable(container, {
            licenseKey: 'non-commercial-and-evaluation',
            rowHeaders: true,
            colHeaders: true,
            contextMenu: {
                items: {
                    'row_above': {name: 'Вставить строку выше'},
                    'row_below': {name: 'Вставить строку ниже'},
                    'col_left': {name: 'Вставить столбец слева'},
                    'col_right': {name: 'Вставить столбец справа'},
                    'remove_row': {name: 'Удалить строку'},
                    'remove_col': {name: 'Удалить столбец'},
                    'separator1': Handsontable.plugins.ContextMenu.SEPARATOR,
                    'copy': {name: 'Копировать'},
                    'cut': {name: 'Вырезать'},
                    'separator2': Handsontable.plugins.ContextMenu.SEPARATOR,
                    'alignment': {
                        name: 'Выравнивание',
                        submenu: {
                            items: [
                                {key: 'alignment:left', name: 'По левому краю'},
                                {key: 'alignment:center', name: 'По центру'},
                                {key: 'alignment:right', name: 'По правому краю'},
                                {key: 'alignment:justify', name: 'По ширине'},
                                {key: 'alignment:top', name: 'По верхнему краю'},
                                {key: 'alignment:middle', name: 'По середине'},
                                {key: 'alignment:bottom', name: 'По нижнему краю'}
                            ]
                        }
                    },
                    'merge_cells': {name: 'Объединить ячейки'},
                    'unmerge_cells': {name: 'Разъединить ячейки'},
                    'borders': {name: 'Границы'}
                }
            },
            height: '100%',
            width: '100%',
            rowHeights: 30,
            rowHeaderWidth: 50,
            
            // Фиксируем только первую строку при прокрутке
            fixedRowsTop: 1,
            
            colWidths: function(index) {
                // Используем пользовательские ширины колонок, если они доступны
                if (structure.columnWidths && structure.columnWidths[index] !== undefined) {
                    return structure.columnWidths[index];
                }
                
                // Иначе используем стандартные ширины
                if (index === 0) return 80;       // №
                if (index === 1) return 250;      // Наименование/Позиция
                if (index === 2) return 70;       // Ед.изм.
                if (index === 3) return 80;       // Кол-во
                if (index === 4) return 100;      // Цена
                
                // Динамически определяем ширину остальных колонок в зависимости от общего количества
                const remainingCols = structure.columnCount - 5;
                if (remainingCols > 0 && index >= 5) {
                    const commonWidth = 100;  // Стандартная ширина
                    
                    // Специальные колонки могут иметь особую ширину
                    if (index === structure.columnCount - 2) return 120;  // Предпоследняя колонка
                    if (index === structure.columnCount - 1) return 140;  // Последняя колонка
                    
                    return commonWidth;
                }
                
                return 100; // Стандартная ширина для остальных колонок
            },
            manualColumnResize: true,
            manualRowResize: true,
            stretchH: 'all',
            autoColumnSize: { samplingRatio: 0.1 },
            autoRowSize: { syncLimit: 200 },
            mergeCells: true,
            
            // Добавляем плагин для скрытия столбцов
            hiddenColumns: {
                columns: [],
                indicators: true
            },
            
            // Настройки для форматирования ячеек
            cells: function(row, col) {
                const cellProperties = {};
                
                // Форматирование только для строки заголовков столбцов (5-я строка, индекс 4)
                if (row === 4) {
                    // Строка с заголовками колонок
                    cellProperties.renderer = headerRenderer;
                    cellProperties.className = 'htBold htCenter';
                }
                
                // Базовое форматирование по типу данных для разных колонок
                if (col === 3 || (structure.columnCount > 5 && [3, 4, 5, structure.columnCount-2, structure.columnCount-1].includes(col))) {
                    cellProperties.numericFormat = {
                        pattern: '0,0.00',
                        culture: 'ru-RU'
                    };
                    cellProperties.type = 'numeric';
                }
                
                // Процентные колонки (если они есть)
                const percentColumns = structure.readOnlyColumns ? 
                    Array.from(Array(structure.columnCount).keys())
                        .filter(i => !structure.readOnlyColumns.includes(i) && i > 5 && i < structure.columnCount - 2) : 
                    [];
                        
                if (percentColumns.includes(col)) {
                    cellProperties.numericFormat = {
                        pattern: '0.00',
                        culture: 'ru-RU'
                    };
                    cellProperties.type = 'numeric';
                }
                
                // Выделяем жирным шрифтом ячейки в колонках Стоимость и Стоимость для заказчика
                if (col === 5 || col === 9) {
                    cellProperties.className = (cellProperties.className || '') + ' htBold';
                }
                
                // Защита ячеек с формулами от редактирования
                if (structure.readOnlyColumns && structure.readOnlyColumns.includes(col)) {
                    cellProperties.readOnly = true;
                    cellProperties.className = (cellProperties.className || '') + ' htCalculatedCell';
                }
                
                // Дополнительная защита отдельных ячеек
                if (structure.readOnlyCells) {
                    const cellKey = `${row},${col}`;
                    if (structure.readOnlyCells.includes(cellKey)) {
                        cellProperties.readOnly = true;
                        cellProperties.className = (cellProperties.className || '') + ' htReadOnlyCell';
                    }
                }
                
                // Выделение итоговой строки
                const lastRow = hot ? hot.countRows() - 1 : 0;
                if (row === lastRow) {
                    cellProperties.className = (cellProperties.className || '') + ' htTotalRow htBold';
                    cellProperties.readOnly = col !== 1; // Разрешаем редактировать только текст "ИТОГО"
                }
                
                return cellProperties;
            },
            // Обработчик изменений данных в ячейках
            afterChange: function(changes, source) {
                if (changes && source !== 'loadData' && source !== 'numbering' && source !== 'calculate') {
                    changes.forEach(([row, col, oldValue, newValue]) => {
                        if (oldValue !== newValue) {
                            // Пересчитываем строку при изменениях в колонках D, E, G, H (количество, цена, наценка, скидка)
                            if (col === 3 || col === 4 || col === 6 || col === 7) {
                                recalculateRow(row);
                            }
                            isFileModified = true;
                            updateStatusIndicator();
                        }
                    });
                }
            },
            // Другие обработчики
            afterRemoveRow: function() {
                recalculateTotals();
                isFileModified = true;
                updateStatusIndicator();
            },
            afterCreateRow: function() {
                isFileModified = true;
                updateStatusIndicator();
            }
        });
        
        console.log('Handsontable initialized with structure:', structure);
        
        // Загрузка данных или создание нового файла
        if (dataUrl) {
            console.log('Loading existing file data');
            loadExcelFile(dataUrl);
        } else {
            console.log('Creating new Excel workbook');
            createNewExcelWorkbook();
        }
        
        // Инициализируем обработчики событий для кнопок
        initEventHandlers();
        
    } catch (error) {
        console.error('Error initializing Handsontable:', error);
        alert('Произошла ошибка при инициализации редактора сметы: ' + error.message);
    }
}

/**
 * Специальный рендерер для заголовков таблицы
 */
function headerRenderer(instance, td, row, col, prop, value, cellProperties) {
    Handsontable.renderers.TextRenderer.apply(this, arguments);
    
    // Применяем стили для заголовков
    td.style.fontWeight = 'bold';
    td.style.backgroundColor = '#f8f9fa';
    td.style.color = '#333';
    td.style.textAlign = 'center';
    td.style.verticalAlign = 'middle';
    
    // Добавляем подчеркивание внизу ячейки
    td.style.borderBottom = '1px solid #ddd';
}

/**
 * Обновление индикатора статуса изменений
 */
function updateStatusIndicator() {
    const saveBtn = document.getElementById('saveExcelBtn');
    
    if (!saveBtn) return;
    
    if (isFileModified) {
        saveBtn.classList.remove('btn-outline-primary');
        saveBtn.classList.add('btn-warning');
        saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Сохранить изменения*';
    } else {
        saveBtn.classList.remove('btn-warning');
        saveBtn.classList.add('btn-outline-primary');
        saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Сохранить изменения';
    }
}

/**
 * Инициализация обработчиков событий
 */
function initEventHandlers() {
    // Устанавливаем флаг, чтобы предотвратить повторную инициализацию обработчиков
    if (window.excelEditorsEventsInitialized) {
        console.log('События Excel-редактора уже инициализированы, пропускаем');
        return;
    }
    
    // Устанавливаем флаг, что обработчики инициализированы
    window.excelEditorsEventsInitialized = true;
    
    console.log('Initializing Excel editor event handlers');
    
    // Флаг для предотвращения множественных вызовов функции сохранения
    let isSaving = false;
    
    // Функция-обертка для сохранения с дебаунсингом
    const safeSaveExcelToServer = function() {
        // Проверяем, не выполняется ли уже сохранение
        if (isSaving) {
            console.log('Сохранение уже выполняется, запрос проигнорирован');
            return;
        }
        
        // Устанавливаем флаг, что началось сохранение
        isSaving = true;
        
        // Добавляем таймер для сброса флага через 10 секунд,
        // на случай если функция сохранения не выполнится корректно
        const resetTimeout = setTimeout(() => {
            isSaving = false;
        }, 10000);
        
        try {
            // Вызываем функцию сохранения
            if (typeof saveExcelToServer === 'function') {
                saveExcelToServer();
            } else {
                console.error('Function saveExcelToServer is not defined');
            }
        } catch (error) {
            console.error('Error while saving excel file:', error);
            alert('Произошла ошибка при сохранении файла: ' + error.message);
        } finally {
            // Сбрасываем флаг и таймер
            clearTimeout(resetTimeout);
            setTimeout(() => {
                isSaving = false;
            }, 2000);
        }
    };
    
    // Удаляем все существующие обработчики для кнопок перед добавлением новых
    const removeExistingHandlers = () => {
        document.querySelectorAll('#recalcAllBtn, #addRowBtn, #addSectionBtn, #updateNumberingBtn, #addSheetBtn, #saveExcelBtn, #submitBtn')
            .forEach(el => {
                const elClone = el.cloneNode(true);
                el.parentNode.replaceChild(elClone, el);
            });
    };
    
    // Удаляем существующие обработчики, чтобы избежать дублирования
    removeExistingHandlers();
    
    // Делегирование событий для кнопок в редакторе Excel
    document.addEventListener('click', function(e) {
        // Используем делегирование событий для обработки кликов по кнопкам
        if (e.target.id === 'recalcAllBtn' || e.target.closest('#recalcAllBtn')) {
            if (typeof recalculateAllFormulas === 'function') recalculateAllFormulas();
            e.preventDefault();
            e.stopPropagation();
        }
        else if (e.target.id === 'addRowBtn' || e.target.closest('#addRowBtn')) {
            if (typeof addNewRow === 'function') addNewRow();
            e.preventDefault();
            e.stopPropagation();
        }
        else if (e.target.id === 'addSectionBtn' || e.target.closest('#addSectionBtn')) {
            if (typeof addNewSection === 'function') addNewSection();
            e.preventDefault();
            e.stopPropagation();
        }
        else if (e.target.id === 'updateNumberingBtn' || e.target.closest('#updateNumberingBtn')) {
            if (typeof updateAllRowNumbers === 'function') updateAllRowNumbers();
            e.preventDefault();
            e.stopPropagation();
        }
        else if (e.target.id === 'addSheetBtn' || e.target.closest('#addSheetBtn')) {
            if (typeof addNewSheet === 'function') addNewSheet();
            e.preventDefault();
            e.stopPropagation();
        }
        else if (e.target.id === 'saveExcelBtn' || e.target.closest('#saveExcelBtn') || 
                 e.target.id === 'submitBtn' || e.target.closest('#submitBtn')) {
            safeSaveExcelToServer(); // Используем безопасную функцию-обертку
            e.preventDefault();
            e.stopPropagation();
        }
    }, { capture: true });
    
    console.log('Excel editor event handlers initialized');
}

/**
 * Переключает режим отображения пустых столбцов
 */
function toggleEmptyColumns() {
    if (!hot || !sheets[currentSheetIndex]) return;
    
    const hiddenColumnsPlugin = hot.getPlugin('hiddenColumns');
    const currentlyHidden = hiddenColumnsPlugin.isHidden(0) ? [] : hiddenColumnsPlugin.getHiddenColumns();
    
    // Если сейчас столбцы скрыты, показываем все
    if (currentlyHidden.length > 0) {
        hiddenColumnsPlugin.showColumns(currentlyHidden);
        document.getElementById('toggleColumnsBtn').innerHTML = '<i class="fas fa-columns"></i> Скрыть пустые';
    } else {
        // Если все столбцы видны, скрываем пустые
        const sheetData = sheets[currentSheetIndex].data;
        const nonEmptyColumns = detectNonEmptyColumns(sheetData, 5);
        const hiddenColumnsConfig = createHiddenColumnsConfig(
            nonEmptyColumns, 
            sheetData[0] ? sheetData[0].length : 10
        );
        
        hiddenColumnsPlugin.hideColumns(hiddenColumnsConfig.columns);
        document.getElementById('toggleColumnsBtn').innerHTML = '<i class="fas fa-columns"></i> Все столбцы';
    }
    
    hot.render(); // Перерисовываем таблицу
}

/**
 * Показать/скрыть индикатор загрузки
 * @param {boolean} show Показать или скрыть индикатор
 */
function showLoading(show) {
    const container = document.getElementById('excelEditor');
    if (!container) return;
    
    if (show) {
        // Создаем и показываем индикатор загрузки
        let loader = document.getElementById('excelLoader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'excelLoader';
            loader.style.position = 'absolute';
            loader.style.top = '50%';
            loader.style.left = '50%';
            loader.style.transform = 'translate(-50%, -50%)';
            loader.style.background = 'rgba(255,255,255,0.8)';
            loader.style.padding = '20px';
            loader.style.borderRadius = '5px';
            loader.style.boxShadow = '0 0 10px rgba(0,0,0,0.2)';
            loader.style.zIndex = '1000';
            loader.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2">Загрузка данных...</div>
                </div>
            `;
            container.parentNode.style.position = 'relative';
            container.parentNode.appendChild(loader);
        }
        container.style.opacity = '0.5';
    } else {
        // Скрываем индикатор загрузки
        const loader = document.getElementById('excelLoader');
        if (loader) loader.remove();
        container.style.opacity = '1';
    }
}

/**
 * Анализирует данные и определяет, какие столбцы не пустые
 * @param {Array} data Двумерный массив данных
 * @param {number} startRow Начальная строка для анализа (обычно пропускаем заголовки)
 * @return {Array} Массив индексов непустых столбцов
 */
function detectNonEmptyColumns(data, startRow = 5) {
    if (!data || data.length === 0) {
        return [];
    }
    
    const columnCount = data[0].length;
    const nonEmptyColumns = [];
    
    // Первые два столбца (№ и Позиция) всегда показываем
    nonEmptyColumns.push(0, 1);
    
    // Проверяем остальные столбцы
    for (let col = 2; col < columnCount; col++) {
        let hasData = false;
        
        // Проверяем данные в столбце, начиная с указанной строки
        for (let row = startRow; row < data.length; row++) {
            if (data[row] && data[row][col] !== null && data[row][col] !== undefined && data[row][col] !== '') {
                hasData = true;
                break;
            }
        }
        
        if (hasData) {
            nonEmptyColumns.push(col);
        }
    }
    
    return nonEmptyColumns;
}

/**
 * Создает конфигурацию для скрытия пустых столбцов
 * @param {Array} nonEmptyColumns Массив индексов непустых столбцов
 * @param {number} totalColumns Общее число столбцов
 * @return {Object} Объект конфигурации hiddenColumns для Handsontable
 */
function createHiddenColumnsConfig(nonEmptyColumns, totalColumns) {
    const hiddenColumns = [];
    
    for (let col = 0; col < totalColumns; col++) {
        if (!nonEmptyColumns.includes(col)) {
            hiddenColumns.push(col);
        }
    }
    
    return {
        columns: hiddenColumns,
        indicators: true
    };
}

// Функция для форматирования размера файла
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
