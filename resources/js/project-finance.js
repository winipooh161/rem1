/**
 * Скрипт для управления финансами проекта
 */
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем наличие необходимых элементов
    const projectTabs = document.getElementById('projectTabs');
    if (!projectTabs) return;
    
    const projectId = projectTabs.getAttribute('data-project-id');
    if (!projectId) return;
    
    // Функция загрузки данных финансов
    function loadFinanceData() {
        // Показываем индикаторы загрузки
        const tables = document.querySelectorAll('#mainWorksTable, #mainMaterialsTable, #additionalWorksTable, #additionalMaterialsTable, #transportationTable');
        tables.forEach(table => {
            table.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status">
                            <span class="visually-hidden">Загрузка...</span>
                        </div>
                        <span class="ms-2">Загрузка данных...</span>
                    </td>
                </tr>
            `;
        });
        
        // Делаем запрос к API
        fetch(`/partner/projects/${projectId}/finance`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Ошибка при загрузке данных');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Разбиваем данные по категориям
                    const items = data.data;
                    const categorized = {
                        'main_work': [],
                        'main_material': [],
                        'additional_work': [],
                        'additional_material': [],
                        'transportation': []
                    };
                    
                    // Распределяем элементы по категориям
                    items.forEach(item => {
                        if (categorized[item.type]) {
                            categorized[item.type].push(item);
                        }
                    });
                    
                    // Отображаем данные в таблицах
                    renderTable('mainWorksTable', categorized.main_work);
                    renderTable('mainMaterialsTable', categorized.main_material);
                    renderTable('additionalWorksTable', categorized.additional_work);
                    renderTable('additionalMaterialsTable', categorized.additional_material);
                    renderTable('transportationTable', categorized.transportation);
                } else {
                    showAlert('Не удалось загрузить данные', 'danger');
                }
            })
            .catch(error => {
                console.error('Ошибка загрузки данных:', error);
                showAlert('Произошла ошибка при загрузке данных', 'danger');
                
                // Отображаем сообщение об ошибке в таблицах
                tables.forEach(table => {
                    table.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center text-danger">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                Не удалось загрузить данные
                            </td>
                        </tr>
                    `;
                });
            });
    }
    
    // Функция для отрисовки таблицы
    function renderTable(tableId, items) {
        const tbody = document.getElementById(tableId);
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">Нет элементов</td></tr>';
            return;
        }
        
        // Сортировка по позиции
        items.sort((a, b) => a.position - b.position);
        
        // Создание строк таблицы
        items.forEach((item, index) => {
            const row = document.createElement('tr');
            row.setAttribute('data-item-id', item.id);
            
            const totalFormatted = parseFloat(item.total_amount).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const paidFormatted = parseFloat(item.paid_amount).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${escapeHtml(item.name)}</td>
                <td>${totalFormatted}</td>
                <td>${paidFormatted}</td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary edit-finance-item" data-type="${item.type}" data-id="${item.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger delete-finance-item" data-type="${item.type}" data-id="${item.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            
            tbody.appendChild(row);
        });
        
        // Добавляем обработчики событий для кнопок
        setupEditButtons(tbody);
        setupDeleteButtons(tbody);
    }
    
    // Функция для настройки кнопок редактирования
    function setupEditButtons(container) {
        container.querySelectorAll('.edit-finance-item').forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.getAttribute('data-id');
                showEditModal(itemId);
            });
        });
    }
    
    // Функция для настройки кнопок удаления
    function setupDeleteButtons(container) {
        container.querySelectorAll('.delete-finance-item').forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.getAttribute('data-id');
                deleteItem(itemId);
            });
        });
    }
    
    // Функция для отображения модального окна редактирования
    function showEditModal(itemId) {
        // Получаем ссылку на модальное окно
        const modalElement = document.getElementById('financeItemModal');
        if (!modalElement) return;
        
        const modal = new bootstrap.Modal(modalElement);
        const form = document.getElementById('financeItemForm');
        const idInput = document.getElementById('financeItemId');
        
        // Запрос данных элемента
        fetch(`/partner/finance-items/${itemId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Ошибка при загрузке данных элемента');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const item = data.data;
                    
                    // Заполняем форму данными
                    document.getElementById('financeItemType').value = item.type;
                    idInput.value = item.id;
                    document.getElementById('itemName').value = item.name;
                    document.getElementById('itemTotal').value = item.total_amount;
                    document.getElementById('itemPaid').value = item.paid_amount;
                    
                    // Устанавливаем заголовок
                    document.getElementById('financeItemModalLabel').textContent = 'Редактирование элемента';
                    
                    // Показываем модальное окно
                    modal.show();
                } else {
                    showAlert('Не удалось загрузить данные элемента', 'danger');
                }
            })
            .catch(error => {
                console.error('Ошибка загрузки данных элемента:', error);
                showAlert('Произошла ошибка при загрузке данных элемента', 'danger');
            });
    }
    
    // Функция для удаления элемента
    function deleteItem(itemId) {
        if (!confirm('Вы уверены, что хотите удалить этот элемент?')) {
            return;
        }
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        fetch(`/partner/finance-items/${itemId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка при удалении элемента');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showAlert('Элемент успешно удалён', 'success');
                // Перезагружаем данные
                loadFinanceData();
            } else {
                showAlert(data.message || 'Не удалось удалить элемент', 'danger');
            }
        })
        .catch(error => {
            console.error('Ошибка удаления элемента:', error);
            showAlert('Произошла ошибка при удалении элемента', 'danger');
        });
    }
    
    // Функция для отображения уведомлений
    function showAlert(message, type) {
        const alertContainer = document.getElementById('financeAlertContainer');
        if (!alertContainer) return;
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        alertContainer.appendChild(alert);
        
        // Автоматическое скрытие через 5 секунд
        setTimeout(() => {
            if (alert.parentNode === alertContainer) {
                alert.classList.remove('show');
                setTimeout(() => {
                    if (alert.parentNode === alertContainer) {
                        alertContainer.removeChild(alert);
                    }
                }, 150);
            }
        }, 5000);
    }
    
    // Вспомогательная функция для экранирования HTML
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    // Обработка кнопок добавления элементов
    document.querySelectorAll('.add-item').forEach(button => {
        button.addEventListener('click', function() {
            const type = this.getAttribute('data-type');
            showAddModal(type);
        });
    });
    
    // Функция для отображения модального окна добавления
    function showAddModal(type) {
        const modalElement = document.getElementById('financeItemModal');
        if (!modalElement) return;
        
        const modal = new bootstrap.Modal(modalElement);
        const form = document.getElementById('financeItemForm');
        
        // Сбрасываем форму и устанавливаем тип
        form.reset();
        document.getElementById('financeItemType').value = type;
        document.getElementById('financeItemId').value = '';
        
        // Устанавливаем заголовок
        document.getElementById('financeItemModalLabel').textContent = 'Добавление элемента';
        
        // Показываем модальное окно
        modal.show();
    }
    
    // Обработчик сохранения формы
    const saveButton = document.getElementById('savefinanceItem');
    if (saveButton) {
        saveButton.addEventListener('click', function() {
            const form = document.getElementById('financeItemForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const formData = {
                name: document.getElementById('itemName').value,
                total_amount: document.getElementById('itemTotal').value,
                paid_amount: document.getElementById('itemPaid').value,
                type: document.getElementById('financeItemType').value
            };
            
            const itemId = document.getElementById('financeItemId').value;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            
            if (itemId) {
                // Обновление существующего элемента
                fetch(`/partner/finance-items/${itemId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Ошибка при обновлении элемента');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Закрываем модальное окно
                        const modalElement = document.getElementById('financeItemModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalElement);
                        modalInstance.hide();
                        
                        // Показываем сообщение об успехе
                        showAlert('Элемент успешно обновлен', 'success');
                        
                        // Перезагружаем данные
                        loadFinanceData();
                    } else {
                        showAlert(data.message || 'Не удалось обновить элемент', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Ошибка обновления элемента:', error);
                    showAlert('Произошла ошибка при обновлении элемента', 'danger');
                });
            } else {
                // Создание нового элемента
                fetch(`/partner/projects/${projectId}/finance/items`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Ошибка при создании элемента');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Закрываем модальное окно
                        const modalElement = document.getElementById('financeItemModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalElement);
                        modalInstance.hide();
                        
                        // Показываем сообщение об успехе
                        showAlert('Элемент успешно добавлен', 'success');
                        
                        // Перезагружаем данные
                        loadFinanceData();
                    } else {
                        showAlert(data.message || 'Не удалось добавить элемент', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Ошибка создания элемента:', error);
                    showAlert('Произошла ошибка при добавлении элемента', 'danger');
                });
            }
        });
    }
    
    // Загружаем данные при открытии вкладки с финансами
    const financeTab = document.getElementById('finance-tab');
    if (financeTab) {
        financeTab.addEventListener('shown.bs.tab', function() {
            loadFinanceData();
        });
        
        // Также загружаем данные, если вкладка финансов активна при загрузке страницы
        if (financeTab.classList.contains('active')) {
            loadFinanceData();
        }
    }
});
