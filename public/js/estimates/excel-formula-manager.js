/**
 * Принудительное добавление формул в строку
 * @param {number} row Индекс строки
 */
function enforceFormulasInRow(row) {
    if (!hot) return;
    
    // Получаем значения из ячеек
    const quantity = parseFloat(hot.getDataAtCell(row, 3)) || 0;
    const price = parseFloat(hot.getDataAtCell(row, 4)) || 0;
    const markup = parseFloat(hot.getDataAtCell(row, 6)) || 0;
    const discount = parseFloat(hot.getDataAtCell(row, 7)) || 0;
    
    // Всегда пересчитываем значения, даже если они уже есть
    // Стоимость = кол-во * цена
    const cost = quantity * price;
    
    // Расчет цены для заказчика = цена * (1 + наценка/100) * (1 - скидка/100)
    const priceForClient = price * (1 + markup/100) * (1 - discount/100);
    
    // Расчет стоимости для заказчика = кол-во * цена для заказчика
    const amountForClient = quantity * priceForClient;
    
    // Устанавливаем значения в ячейки
    hot.setDataAtCell(row, 5, cost, 'calculate');
    hot.setDataAtCell(row, 8, priceForClient, 'calculate');
    hot.setDataAtCell(row, 9, amountForClient, 'calculate');
}

/**
 * Пересчет значений одной строки
 * @param {number} row Индекс строки
 */
function recalculateRow(row) {
    if (!hot) return;
    
    try {
        const totalRow = hot.countRows() - 1;
        
        // Пропускаем итоговую строку
        if (row === totalRow) return;
        
        // Получаем значения из ячеек
        const quantity = parseFloat(hot.getDataAtCell(row, 3)) || 0;
        const price = parseFloat(hot.getDataAtCell(row, 4)) || 0;
        
        // Расчет стоимости = кол-во * цена
        const cost = quantity * price;
        hot.setDataAtCell(row, 5, cost, 'calculate');
        
        const markup = parseFloat(hot.getDataAtCell(row, 6)) || 0;
        const discount = parseFloat(hot.getDataAtCell(row, 7)) || 0;
        
        // Расчет цены для заказчика = цена * (1 + наценка/100) * (1 - скидка/100)
        const priceForClient = price * (1 + markup/100) * (1 - discount/100);
        
        // Расчет стоимости для заказчика = кол-во * цена для заказчика
        const amountForClient = quantity * priceForClient;
        
        // Устанавливаем значения в ячейки (включая стоимость)
        hot.setDataAtCell(row, 8, priceForClient, 'calculate');
        hot.setDataAtCell(row, 9, amountForClient, 'calculate');
        
        // После изменения одной строки пересчитываем итоги
        recalculateTotals();
    } catch (error) {
        console.error('Error recalculating row:', error);
    }
}

/**
 * Пересчет всех строк
 */
function recalculateAll() {
    if (!hot) return;
    
    try {
        // Пересчитываем каждую строку, кроме итоговой
        const totalRow = hot.countRows() - 1;
        
        for (let row = 5; row < totalRow; row++) {
            recalculateRow(row);
        }
        
        // В конце пересчитываем итоги
        recalculateTotals();
    } catch (error) {
        console.error('Error recalculating all rows:', error);
    }
}

/**
 * Пересчет итогов
 */
function recalculateTotals() {
    if (!hot) return;
    
    try {
        const totalRow = hot.countRows() - 1;
        
        // Инициализируем суммы только для столбцов со стоимостью
        const totals = {
            5: 0, // Стоимость (сумма)
            9: 0  // Стоимость для заказчика (сумма)
        };
        
        // Считаем суммы только по нужным колонкам
        for (let row = 5; row < totalRow; row++) {
            // Проверяем, есть ли что-то в строке (наличие значения в столбце "Позиция")
            const hasPosition = hot.getDataAtCell(row, 1) !== null && hot.getDataAtCell(row, 1) !== '';
            
            if (hasPosition) {
                // Суммируем только значения колонок "Стоимость" и "Стоимость для заказчика"
                totals[5] += parseFloat(hot.getDataAtCell(row, 5)) || 0;
                totals[9] += parseFloat(hot.getDataAtCell(row, 9)) || 0;
            }
        }
        
        // Устанавливаем итоговые значения только для нужных столбцов
        hot.setDataAtCell(totalRow, 5, totals[5], 'calculate');
        hot.setDataAtCell(totalRow, 9, totals[9], 'calculate');
        
        // Очищаем ячейки в столбцах, где итоги не нужны
        hot.setDataAtCell(totalRow, 3, '', 'calculate'); // Кол-во
        hot.setDataAtCell(totalRow, 4, '', 'calculate'); // Цена
        hot.setDataAtCell(totalRow, 6, '', 'calculate'); // Наценка, %
        hot.setDataAtCell(totalRow, 7, '', 'calculate'); // Скидка, %
        hot.setDataAtCell(totalRow, 8, '', 'calculate'); // Цена для заказчика
    } catch (error) {
        console.error('Error recalculating totals:', error);
    }
}

/**
 * Принудительный пересчет всех формул
 */
function recalculateAllFormulas() {
    if (hot) {
        showLoading(true);
        setTimeout(() => {
            try {
                // Принудительно пересчитываем все строки
                const totalRow = hot.countRows() - 1;
                for (let row = 5; row < totalRow; row++) {
                    const name = hot.getDataAtCell(row, 1);
                    if (name) {
                        enforceFormulasInRow(row);
                    }
                }
                recalculateTotals();
                hot.render();
                alert('Все формулы успешно пересчитаны');
                isFileModified = true;
                updateStatusIndicator();
            } catch (error) {
                console.error('Error during recalculation:', error);
                alert('Ошибка при пересчете: ' + error.message);
            } finally {
                showLoading(false);
            }
        }, 200);
    }
}
