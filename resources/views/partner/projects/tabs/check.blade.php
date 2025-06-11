<h5 class="mb-3">Проверка объекта</h5>

<div class="alert alert-info mb-4">
    <div class="d-flex align-items-center">
    </div>
</div>

<div class="check-list-container mb-4">
    <!-- Список проверок загружается динамически через JavaScript -->
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Функция для проверки наличия элемента
    function checkElement(element) {
        return document.querySelector(element) !== null;
    }
    
    // Загружаем список проверок при загрузке страницы
    const projectId = {{ $project->id }};
    loadCheckItems(projectId);
    
    // Функция для загрузки списка проверок
    function loadCheckItems(projectId) {
        fetch(`/partner/projects/${projectId}/checks`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка загрузки данных');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderCheckItems(data.items);
            } else {
                showError('Не удалось загрузить список проверок');
            }
        })
        .catch(error => {
            console.error('Ошибка при загрузке проверок:', error);
            showError('Произошла ошибка при загрузке списка проверок');
        });
    }
    
    // Функция для отображения списка проверок
    function renderCheckItems(items) {
        const container = document.querySelector('.check-list-container');
        if (!container) return;
        
        // Очищаем контейнер перед добавлением новых элементов
        container.innerHTML = '';
        
        if (items.length === 0) {
            container.innerHTML = '<div class="alert alert-info">Нет доступных проверок</div>';
            return;
        }
        
        // Создаем список проверок
        const listGroup = document.createElement('div');
        listGroup.className = 'list-group';
        
        items.forEach((item, index) => {
            const listItem = document.createElement('div');
            listItem.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center check-card-clickable';
            listItem.setAttribute('data-check-id', item.id);
            listItem.style.cursor = 'pointer';
            
            listItem.innerHTML = `
                <div>
                    <h6 class="mb-1">${item.title || `Проверка #${item.id}`}</h6>
                    <small class="text-muted">Нажмите для просмотра деталей</small>
                </div>
                <div class="d-flex align-items-center">
                    ${item.all_completed ? 
                        '<span class="badge bg-success me-2">Выполнено</span>' : 
                        '<span class="badge bg-warning me-2">Не завершено</span>'}
                    <i class="fas fa-chevron-right"></i>
                </div>
            `;
            
            // Добавляем обработчик клика для всей карточки
            listItem.addEventListener('click', function(e) {
                // Предотвращаем всплытие события
                e.preventDefault();
                e.stopPropagation();
                
                // Добавляем визуальную обратную связь
                listItem.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    listItem.style.transform = 'scale(1)';
                }, 150);
                
                loadCheckDetails(projectId, item.id);
            });
            
            // Добавляем эффекты наведения
            listItem.addEventListener('mouseenter', function() {
                listItem.style.backgroundColor = '#f8f9fa';
                listItem.style.transition = 'all 0.2s ease';
            });
            
            listItem.addEventListener('mouseleave', function() {
                listItem.style.backgroundColor = '';
            });
            
            listGroup.appendChild(listItem);
        });
        
        container.appendChild(listGroup);
    }
    
    // Функция для загрузки деталей проверки
    function loadCheckDetails(projectId, checkId) {
        // Показываем индикатор загрузки
        const container = document.querySelector('.check-list-container');
        if (container) {
            container.innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <p class="mt-2">Загрузка деталей проверки...</p>
                </div>
            `;
        }
        
        fetch(`/partner/projects/${projectId}/checks/${checkId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка загрузки данных проверки');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Отображаем детали проверки
                if (container) {
                    container.innerHTML = data.html;
                    
                    // Инициализируем обработчики для чекбоксов и комментариев
                    initializeCheckHandlers(projectId, checkId);
                    
                    // Добавляем кнопку "Назад к списку"
                    const backButton = document.createElement('button');
                    backButton.className = 'btn btn-outline-secondary mt-3';
                    backButton.innerHTML = '<i class="fas fa-arrow-left me-2"></i>Назад к списку';
                    backButton.addEventListener('click', function() {
                        loadCheckItems(projectId);
                    });
                    
                    container.appendChild(backButton);
                }
            } else {
                showError(data.message || 'Не удалось загрузить детали проверки');
            }
        })
        .catch(error => {
            console.error('Ошибка при загрузке деталей проверки:', error);
            showError('Произошла ошибка при загрузке деталей проверки');
        });
    }
    
    // Инициализация обработчиков для чекбоксов и комментариев
    function initializeCheckHandlers(projectId, checkId) {
        // Обработчики для чекбоксов
        document.querySelectorAll('.check-item-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const itemId = this.getAttribute('data-id');
                const category = this.getAttribute('data-category');
                
                updateCheckStatus(projectId, itemId, category, this.checked);
            });
        });
        
        // Обработчик для комментария
        const commentTextarea = document.getElementById(`comment${checkId}`);
        if (commentTextarea) {
            // Сохраняем комментарий при потере фокуса
            commentTextarea.addEventListener('blur', function() {
                saveComment(projectId, checkId, this.value);
            });
        }
    }
    
    // Функция для обновления статуса чекбокса
    function updateCheckStatus(projectId, checkId, category, checked) {
        fetch(`/partner/projects/${projectId}/checks/${checkId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                checked: checked,
                category: category
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Ошибка при обновлении статуса');
            }
        })
        .catch(error => {
            console.error('Ошибка при обновлении статуса:', error);
        });
    }
    
    // Функция для сохранения комментария
    function saveComment(projectId, checkId, comment) {
        fetch(`/partner/projects/${projectId}/checks/${checkId}/comment`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                comment: comment
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Ошибка при сохранении комментария');
            }
        })
        .catch(error => {
            console.error('Ошибка при сохранении комментария:', error);
        });
    }
    
    // Функция для отображения ошибок
    function showError(message) {
        const container = document.querySelector('.check-list-container');
        if (container) {
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    ${message}
                </div>
                <button class="btn btn-outline-primary" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt me-2"></i>Обновить страницу
                </button>
            `;
        }
    }
});
</script>

<style>
/* Адаптивные стили для мобильных устройств */
@media (max-width: 576px) {
    .check-item .btn {
        padding: 0.375rem 0.5rem;
        font-size: 0.875rem;
    }
    
    .check-item h6 {
        font-size: 0.95rem;
        margin-bottom: 0.5rem;
    }
    
    .click_checkbox {
        width: 22px;
        height: 22px;
    }
    
    .check-content {
        padding: 0.75rem !important;
    }
    
    .check-content .card-header h5 {
        font-size: 1.1rem;
    }
    
    .check-content .form-check-input {
        width: 1.25rem;
        height: 1.25rem;
        margin-top: 0.1rem;
    }
    
    .check-content .card-body {
        padding: 1rem 0.75rem;
    }
}

/* Анимация для плавного отображения содержимого проверки */
.check-content {
    transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
    overflow: hidden;
    border: 1px solid #ddd;
    border-radius: 0.25rem;
    margin-top: 8px;
    background-color: #fff;
}
</style>

