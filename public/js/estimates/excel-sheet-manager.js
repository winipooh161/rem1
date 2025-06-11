/**
 * Функция загрузки данных Excel с сервера
 * @param {string} url URL для загрузки файла Excel
 */
function loadExcelFile(url) {
    console.log('Загрузка файла Excel с:', url);
    showLoading(true);
    
    if (!url) {
        console.error('URL для загрузки данных не определен');
        showLoading(false);
        createNewExcelWorkbook();
        return;
    }
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                if (response.status === 500) {
                    // Получим текст ошибки для более точного диагностирования проблемы
                    return response.json().then(errorData => {
                        throw new Error(`Ошибка сервера: ${errorData.message || response.statusText}`);
                    }).catch(e => {
                        throw new Error(`Внутренняя ошибка сервера (500). Пожалуйста, обратитесь к администратору.`);
                    });
                } else {
                    throw new Error(`Ошибка загрузки: ${response.status} ${response.statusText}`);
                }
            }
            return response.json();
        })
        .then(data => {
            console.log('Данные Excel получены');
            if (data.success) {
                try {
                    const base64Data = data.data;
                    const binaryString = window.atob(base64Data);
                    const bytes = new Uint8Array(binaryString.length);
                    for (let i = 0; i < binaryString.length; i++) {
                        bytes[i] = binaryString.charCodeAt(i);
                    }
                    
                    // Разбор файла Excel
                    workbook = XLSX.read(bytes, {type: 'array'});
                    
                    // Обновляем структуру файла
                    fileStructure = data.structure || fileStructure;
                    
                    // Получаем список листов
                    sheets = [];
                    workbook.SheetNames.forEach(sheetName => {
                        const worksheet = workbook.Sheets[sheetName];
                        const sheetData = XLSX.utils.sheet_to_json(worksheet, {header: 1});
                        
                        // Определяем непустые столбцы
                        const nonEmptyColumns = detectNonEmptyColumns(sheetData, 5);
                        
                        // Сохраняем информацию о непустых столбцах для этого листа
                        sheets.push({
                            name: sheetName,
                            data: sheetData,
                            nonEmptyColumns: nonEmptyColumns
                        });
                    });
                    
                    // Обновляем вкладки листов
                    updateSheetTabs();
                    
                    // Загружаем данные первого листа
                    currentSheetIndex = 0;
                    loadSheetData(0);
                } catch (error) {
                    console.error('Ошибка при разборе Excel:', error);
                    alert('Ошибка при разборе файла Excel: ' + error.message);
                    createNewExcelWorkbook();
                }
            } else {
                console.warn('Ошибка загрузки данных Excel:', data.message);
                alert('Ошибка при загрузке данных: ' + data.message);
                // Если файл не найден или поврежден, создаем новый
                createNewExcelWorkbook();
            }
        })
        .catch(error => {
            console.error('Ошибка при загрузке файла Excel:', error);
            alert('Произошла ошибка при загрузке файла: ' + error.message);
            // В случае любой ошибки создаем новый пустой файл
            createNewExcelWorkbook();
        })
        .finally(() => {
            showLoading(false);
        });
}

/**
 * Создание нового рабочего документа Excel с учетом структуры
 */
function createNewExcelWorkbook() {
    try {
        console.log('Создание новой книги Excel с учетом структуры');
        
        // Получаем структуру файла (если она определена)
        const structure = fileStructure || {
            columnCount: 10,
            readOnlyColumns: [5, 8, 9],
            hasHeaders: true
        };
        
        // Создаем базовый шаблон для сметы
        workbook = XLSX.utils.book_new();
        
        // Добавляем первый лист "Смета"
        const sheetName = 'Смета';
        
        // Создаем структуру сметы с нужным количеством колонок
        const headerRow = [];
        for (let i = 0; i < structure.columnCount; i++) {
            headerRow.push('');  // Сначала создаем пустые ячейки
        }
        
        // Заполняем только заголовки таблицы, без дополнительной информации вначале
        const sheetData = [];
        
        // Добавляем пустые строки для сохранения структуры
        for (let i = 0; i < 5; i++) {
            sheetData.push([...headerRow]);
        }
        
        // Заголовки таблицы в зависимости от количества колонок
        const tableHeaders = [...headerRow];
        
        // Стандартные заголовки, которые всегда присутствуют
        tableHeaders[0] = '№';
        tableHeaders[1] = 'Позиция';
        tableHeaders[2] = 'Ед. изм.';
        tableHeaders[3] = 'Кол-во';
        tableHeaders[4] = 'Цена';
        
        // Остальные заголовки в зависимости от структуры
        if (structure.columnCount >= 6) tableHeaders[5] = 'Стоимость';
        if (structure.columnCount >= 7) tableHeaders[6] = 'Наценка, %';
        if (structure.columnCount >= 8) tableHeaders[7] = 'Скидка, %';
        if (structure.columnCount >= 9) tableHeaders[8] = 'Цена для заказчика';
        if (structure.columnCount >= 10) tableHeaders[9] = 'Стоимость для заказчика';
        
        // Добавляем заголовки таблицы
        sheetData.push(tableHeaders);
        
        // Добавляем итоговую строку
        const totalRow = [...headerRow];
        totalRow[1] = 'ИТОГО:';
        
        // Устанавливаем формулы для итоговых колонок
        if (structure.readOnlyColumns && structure.columnCount > 5) {
            structure.readOnlyColumns.forEach(colIndex => {
                if (colIndex < structure.columnCount) {
                    totalRow[colIndex] = 0; // Начальное значение для суммы
                }
            });
        }
        
        sheetData.push(totalRow);
        
        // Создаем лист и добавляем в книгу
        const worksheet = XLSX.utils.aoa_to_sheet(sheetData);
        XLSX.utils.book_append_sheet(workbook, worksheet, sheetName);
        
        // Заполняем данные для отображения
        sheets = [{
            name: sheetName,
            data: sheetData
        }];
        
        // Обновляем вкладки листов
        updateSheetTabs();
        
        // Загружаем данные первого листа в редактор
        loadSheetData(0);
        
        console.log('New workbook created with structure', structure);
        
        // Сразу же применяем формулы и пересчитываем итоги
        recalculateAll();
    } catch (error) {
        console.error('Ошибка при создании книги Excel:', error);
        alert('Произошла ошибка при создании нового файла сметы: ' + error.message);
    }
}

/**
 * Загрузка данных листа в редактор
 * @param {number} sheetIndex Индекс листа для загрузки
 */
function loadSheetData(sheetIndex) {
    if (!sheets[sheetIndex]) {
        console.error('Sheet not found:', sheetIndex);
        return;
    }
    
    const sheetData = sheets[sheetIndex].data;
    
    // Получаем непустые столбцы для текущего листа
    const nonEmptyColumns = sheets[sheetIndex].nonEmptyColumns || detectNonEmptyColumns(sheetData, 5);
    
    // Создаем конфигурацию для скрытия пустых столбцов
    const hiddenColumnsConfig = createHiddenColumnsConfig(
        nonEmptyColumns, 
        sheetData[0] ? sheetData[0].length : 10
    );
    
    // Обновляем конфигурацию Handsontable для скрытия пустых столбцов
    if (hot && hot.getPlugin('hiddenColumns')) {
        hot.getPlugin('hiddenColumns').hideColumns(hiddenColumnsConfig.columns);
        hot.render();
    }
    
    // Загружаем данные в редактор
    hot.loadData(sheetData);
    
    // Применяем форматирование только к строке заголовков (5-я строка, индекс 4)
    for (let col = 0; col < (sheetData[0] ? sheetData[0].length : 10); col++) {
        hot.setCellMeta(4, col, 'className', 'htBold htCenter');
    }
    
    // Принудительно вызываем рендеринг и пересчет формул для ВСЕХ строк
    setTimeout(() => {
        hot.render();
        
        // Проходим по всем строкам и удостоверяемся, что у них есть формулы
        const totalRow = hot.countRows() - 1;
        
        // Пройдем по всем строкам данных (начиная с 6-й строки, исключая заголовки)
        for (let row = 5; row < totalRow; row++) {
            // Сначала проверим, есть ли данные в строке
            const name = hot.getDataAtCell(row, 1);
            if (name) {
                // Проверим стиль строки (заголовок раздела или обычная строка)
                if (name === name.toUpperCase() && name.length > 3) {
                    // Форматируем как заголовок раздела
                    for (let col = 0; col <= 9; col++) {
                        hot.setCellMeta(row, col, 'className', 'htGroupHeader');
                    }
                } else {
                    // Форматируем числовые колонки жирным шрифтом
                    hot.setCellMeta(row, 5, 'className', 'htBold'); // Стоимость
                    hot.setCellMeta(row, 9, 'className', 'htBold'); // Стоимость для заказчика
                    
                    // Для колонок с формулами делаем readOnly
                    for (let col of [5, 8, 9]) {
                        hot.setCellMeta(row, col, 'readOnly', true);
                    }
                }
                
                // Проверяем наличие формул и при необходимости добавляем их
                enforceFormulasInRow(row);
            }
        }
        
        // Пересчитываем итоговые формулы
        recalculateTotals();
        
        // Обновляем рендеринг
        hot.render();
    }, 100);
}

/**
 * Обновление вкладок листов
 */
function updateSheetTabs() {
    const tabsContainer = document.getElementById('sheetTabs');
    if (!tabsContainer) return;
    
    tabsContainer.innerHTML = '';
    
    sheets.forEach((sheet, index) => {
        const tab = document.createElement('button');
        tab.className = 'btn btn-sm ' + (index === currentSheetIndex ? 'btn-primary' : 'btn-outline-secondary');
        tab.textContent = sheet.name;
        tab.style.marginRight = '5px';
        
        tab.addEventListener('click', () => {
            currentSheetIndex = index;
            loadSheetData(index);
            updateSheetTabs();
        });
        
        tabsContainer.appendChild(tab);
    });
}

/**
 * Добавление нового листа
 */
function addNewSheet() {
    const sheetName = prompt('Введите название нового листа:', 'Новый лист ' + (sheets.length + 1));
    if (!sheetName) return;

    // Создаем новый лист со стандартной структурой
    const newSheetData = [
        ['', sheetName, '', '', '', '', '', '', '', ''],
        ['Дата:', new Date().toLocaleDateString('ru-RU'), '', '', '', '', '', '', '', ''],
        ['', '', '', '', '', '', '', '', '', ''],
        ['', '', '', '', '', '', '', '', '', ''],
        ['№', 'Позиция', 'Ед. изм.', 'Кол-во', 'Цена', 'Стоимость', 'Наценка, %', 'Скидка, %', 'Цена для заказчика', 'Стоимость для заказчика'],
    ];

    // Добавляем итоговую строку
    newSheetData.push([
        '', 'ИТОГО:', '', '', '', 0, '', '', '', 0
    ]);

    // Добавляем лист в рабочую книгу
    XLSX.utils.book_append_sheet(workbook, XLSX.utils.aoa_to_sheet(newSheetData), sheetName);

    // Добавляем в массив листов
    sheets.push({ name: sheetName, data: newSheetData });

    // Переключаемся на новый лист
    currentSheetIndex = sheets.length - 1;

    // Обновляем вкладки и отображение
    updateSheetTabs();
    loadSheetData(currentSheetIndex);

    // Флаг изменения
    isFileModified = true;
    updateStatusIndicator();
}

/**
 * Сохранение Excel файла на сервер
 */
function saveExcelToServer() {
    if (!workbook) {
        alert('Нет данных для сохранения');
        return;
    }
    
    showLoading(true);
    
    try {
        // Обновляем данные текущего листа
        const currentSheetData = hot.getData();
        sheets[currentSheetIndex].data = currentSheetData;
        
        // Обновляем данные в рабочей книге
        const worksheet = XLSX.utils.aoa_to_sheet(currentSheetData);
        workbook.Sheets[sheets[currentSheetIndex].name] = worksheet;
        
        // Преобразуем книгу в двоичные данные с использованием промежуточной переменной
        let wbout;
        try {
            wbout = XLSX.write(workbook, { 
                bookType: 'xlsx', 
                type: 'binary',
                cellStyles: true,
                compression: true 
            });
        } catch (writeError) {
            console.error('Error writing Excel workbook:', writeError);
            alert('Ошибка при создании файла Excel: ' + writeError.message);
            showLoading(false);
            return;
        }
        
        // Вспомогательная функция для корректного преобразования строки в массив байтов
        function s2ab(s) {
            const buf = new ArrayBuffer(s.length);
            const view = new Uint8Array(buf);
            for (let i = 0; i < s.length; i++) {
                view[i] = s.charCodeAt(i) & 0xFF;
            }
            return buf;
        }
        
        // Создаем Blob из данных для надежного преобразования в Base64
        const blob = new Blob([s2ab(wbout)], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
        
        // Используем FileReader для преобразования Blob в Base64
        const fileReader = new FileReader();
        fileReader.onload = function(e) {
            // Получаем Base64 данные, удаляя префикс Data URL
            const base64Data = e.target.result.split(',')[1];
            
            // Проверяем, что данные не пустые
            if (!base64Data) {
                console.error('Failed to convert Excel data to Base64');
                alert('Ошибка при подготовке файла к сохранению: данные не могут быть преобразованы');
                showLoading(false);
                return;
            }
            
            // Получаем корректный URL для сохранения - ИСПРАВЛЯЕМ ЗДЕСЬ
            const form = document.querySelector('form#estimateForm');
            let saveUrl;
            
            if (form) {
                // Извлекаем ID сметы из текущего URL страницы
                const urlParts = window.location.pathname.split('/');
                const estimateId = urlParts[urlParts.indexOf('estimates') + 1];
                
                // Формируем правильный URL для сохранения Excel
                saveUrl = `/partner/estimates/${estimateId}/saveExcel`;
                
                console.log('Сформирован URL для сохранения:', saveUrl);
            } else {
                console.error('Форма сметы не найдена');
                alert('Ошибка: форма сметы не найдена на странице');
                showLoading(false);
                return;
            }
            
            // Отладочный вывод
            console.log('Sending Excel data to server, data size:', base64Data.length);
            console.log('Save URL:', saveUrl);
            
            // Отправляем на сервер
            fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    excel_data: base64Data
                })
            })
            .then(response => {
                // Проверяем код ответа перед разбором JSON
                if (!response.ok) {
                    if (response.status === 422) {
                        return response.json().then(data => {
                            throw new Error(data.message || 'Данные Excel не могут быть обработаны сервером');
                        });
                    }
                    throw new Error(`Ошибка сервера: ${response.status} ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Обновляем информацию о файле
                    const fileInfoElement = document.getElementById('fileInfo');
                    if (fileInfoElement) {
                        fileInfoElement.innerHTML = `
                            <p class="mb-1"><strong>Файл сметы:</strong> ${data.fileName || 'Смета.xlsx'}</p>
                            <p class="mb-1"><small>Последнее обновление: ${data.updated_at}</small></p>
                            <p class="mb-1"><small>Размер: ${formatFileSize(data.filesize)}</small></p>
                        `;
                    }
                    
                    // Сбрасываем флаг модификации
                    isFileModified = false;
                    updateStatusIndicator();
                    
                    // Уведомляем пользователя
                    alert('Файл успешно сохранен');
                } else {
                    throw new Error(data.message || 'Неизвестная ошибка при сохранении файла');
                }
            })
            .catch(error => {
                console.error('Ошибка при сохранении файла:', error);
                alert('Произошла ошибка при сохранении файла: ' + error.message);
            })
            .finally(() => {
                showLoading(false);
            });
        };
        
        fileReader.onerror = function() {
            console.error('Ошибка при чтении файла как base64');
            alert('Не удалось преобразовать Excel файл для отправки');
            showLoading(false);
        };
        
        // Запускаем преобразование в base64
        fileReader.readAsDataURL(blob);
        
    } catch (error) {
        console.error('Error preparing file for saving:', error);
        alert('Ошибка при подготовке файла к сохранению: ' + error.message);
        showLoading(false);
    }
}

/**
 * Форматирует размер файла для читаемого отображения
 * @param {number} size Размер в байтах
 * @return {string} Отформатированный размер
 */
function formatFileSize(size) {
    if (!size) return '0 B';
    
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let i = 0;
    
    while (size >= 1024 && i < units.length - 1) {
        size /= 1024;
        i++;
    }
    
    return Math.round(size * 100) / 100 + ' ' + units[i];
}

/**
 * Обнаружение непустых столбцов в данных
 * @param {Array} data Данные листа (массив строк)
 * @param {number} minNonEmptyRows Минимальное количество непустых строк для определения столбца как непустого
 * @returns {Array} Массив индексов непустых столбцов
 */
function detectNonEmptyColumns(data, minNonEmptyRows = 1) {
    const nonEmptyColumns = [];
    const rowCount = data.length;
    
    // Предполагаем, что первая строка - это заголовки
    const columnCount = data[0].length;
    
    for (let col = 0; col < columnCount; col++) {
        let nonEmptyCount = 0;
        
        for (let row = 0; row < rowCount; row++) {
            if (data[row][col] !== null && data[row][col] !== '') {
                nonEmptyCount++;
            }
            
            // Если нашли достаточно непустых ячеек, добавляем столбец в результат
            if (nonEmptyCount >= minNonEmptyRows) {
                nonEmptyColumns.push(col);
                break;
            }
        }
    }
    
    return nonEmptyColumns;
}
