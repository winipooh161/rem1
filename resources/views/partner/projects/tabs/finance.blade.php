<h5 class="mb-3">Информация о договоре и финансах</h5>
<div class="row">
    <div class="col-12 col-md-6 mb-3 mb-md-0">
        <div class="table-responsive-mobile">
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <th width="40%">Дата договора:</th>
                        <td>{{ $project->contract_date ? $project->contract_date->format('d.m.Y') : 'Не указана' }}</td>
                    </tr>
                    <tr>
                        <th>Номер договора:</th>
                        <td>{{ $project->contract_number ?? 'Не указан' }}</td>
                    </tr>
                    <tr>
                        <th>Дата начала работ:</th>
                        <td>{{ $project->work_start_date ? $project->work_start_date->format('d.m.Y') : 'Не указана' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="table-responsive-mobile">
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <th width="40%">Сумма на работы:</th>
                        <td>{{ number_format($project->work_amount, 2, '.', ' ') }} ₽</td>
                    </tr>
                    <tr>
                        <th>Сумма на материалы:</th>
                        <td>{{ number_format($project->materials_amount, 2, '.', ' ') }} ₽</td>
                    </tr>
                    <tr>
                        <th>Общая сумма:</th>
                        <td>{{ number_format($project->work_amount + $project->materials_amount, 2, '.', ' ') }} ₽</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно для редактирования элемента -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editItemModalLabel">Редактировать запись</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form id="editItemForm">
                    <input type="hidden" id="edit-item-id" name="id">
                    <input type="hidden" id="edit-item-type" name="type">
                    
                    <div class="mb-3">
                        <label for="edit-name" class="form-label">Наименование</label>
                        <input type="text" class="form-control" id="edit-name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-total-amount" class="form-label">Сумма (₽)</label>
                        <input type="number" class="form-control" id="edit-total-amount" name="total_amount" min="0" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-paid-amount" class="form-label">Оплачено</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="edit-paid-amount" name="paid_amount" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-payment-date" class="form-label">Дата оплаты</label>
                        <input type="date" class="form-control" id="edit-payment-date" name="payment_date">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="saveEditItemBtn">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для подтверждения удаления -->
<div class="modal fade" id="deleteItemModal" tabindex="-1" aria-labelledby="deleteItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteItemModalLabel">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить этот элемент?</p>
                <p id="delete-item-name" class="fw-bold"></p>
                <input type="hidden" id="delete-item-id">
                <input type="hidden" id="delete-item-type">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Удалить</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Адаптивные стили для мобильных устройств */
@media (max-width: 768px) {
    .schedule-controls .row > div {
        margin-bottom: 10px;
    }
    
    .accordion-button {
        padding: 0.75rem;
    }
    
    .accordion-body {
        padding: 0.75rem 0.5rem; overflow: auto;
    }
    
    
    .input-group {
        flex-wrap: wrap;
    }
    
    .input-group > .form-control,
    .input-group > .input-group-text,
    .input-group > .btn {
        flex: 1 1 auto;
        width: auto;
        margin-bottom: 5px;
    }
}

@media (max-width: 768px) {
    .input-group > .btn {
        flex: 1 1 auto;
        width: auto;
        margin-bottom: 5px;
    }
}
</style>

<div class="container-fluid">
    <h5 class="mb-4">Финансы объекта</h5>
    
    <!-- Табы для разных видов финансов -->
    <ul class="nav nav-tabs mb-3" id="financeTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="main-work-tab" data-bs-toggle="tab" data-bs-target="#main-work" type="button" role="tab">
                Основные работы
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="main-materials-tab" data-bs-toggle="tab" data-bs-target="#main-materials" type="button" role="tab">
                Основные материалы
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="additional-work-tab" data-bs-toggle="tab" data-bs-target="#additional-work" type="button" role="tab">
                Дополнительные работы
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="additional-materials-tab" data-bs-toggle="tab" data-bs-target="#additional-materials" type="button" role="tab">
                Дополнительные материалы
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="transportation-tab" data-bs-toggle="tab" data-bs-target="#transportation" type="button" role="tab">
                Транспортировка
            </button>
        </li>
    </ul>
    
    <!-- Содержимое табов -->
    <div class="tab-content" id="financeTabsContent">
        <!-- Таб для основных работ -->
        <div class="tab-pane fade show active" id="main-work" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Основные работы</h6>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal" data-type="main_work">
                    <i class="fas fa-plus"></i> Добавить
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover" id="main-work-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th>Наименование</th>
                            <th width="15%">Сумма (₽)</th>
                            <th width="15%">Оплачено (₽)</th>
                            <th width="15%">Дата оплаты</th>
                            <th width="15%">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Данные будут загружены через AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Аналогичные табы для других типов финансов -->
        <div class="tab-pane fade" id="main-materials" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Основные материалы</h6>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal" data-type="main_material">
                    <i class="fas fa-plus"></i> Добавить
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover" id="main-materials-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th>Наименование</th>
                            <th width="15%">Сумма (₽)</th>
                            <th width="15%">Оплачено (₽)</th>
                            <th width="15%">Дата оплаты</th>
                            <th width="15%">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Данные будут загружены через AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="tab-pane fade" id="additional-work" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Дополнительные работы</h6>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal" data-type="additional_work">
                    <i class="fas fa-plus"></i> Добавить
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover" id="additional-work-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th>Наименование</th>
                            <th width="15%">Сумма (₽)</th>
                            <th width="15%">Оплачено (₽)</th>
                            <th width="15%">Дата оплаты</th>
                            <th width="15%">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Данные будут загружены через AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="tab-pane fade" id="additional-materials" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Дополнительные материалы</h6>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal" data-type="additional_material">
                    <i class="fas fa-plus"></i> Добавить
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover" id="additional-materials-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th>Наименование</th>
                            <th width="15%">Сумма (₽)</th>
                            <th width="15%">Оплачено (₽)</th>
                            <th width="15%">Дата оплаты</th>
                            <th width="15%">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Данные будут загружены через AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="tab-pane fade" id="transportation" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6>Транспортировка</h6>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal" data-type="transportation">
                    <i class="fas fa-plus"></i> Добавить
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover" id="transportation-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th>Наименование</th>
                            <th width="15%">Сумма (₽)</th>
                            <th width="15%">Оплачено (₽)</th>
                            <th width="15%">Дата оплаты</th>
                            <th width="15%">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Данные будут загружены через AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления элемента -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить элемент</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addItemForm">
                    <input type="hidden" id="add-item-type" name="type" value="main_work">
                    
                    <div class="mb-3">
                        <label for="add-name" class="form-label">Наименование</label>
                        <input type="text" class="form-control" id="add-name" name="name" required>
                        <div class="invalid-feedback">Пожалуйста, укажите наименование</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add-total-amount" class="form-label">Сумма (₽)</label>
                        <input type="number" class="form-control" id="add-total-amount" name="total_amount" min="0" step="0.01" required>
                        <div class="invalid-feedback">Пожалуйста, укажите сумму</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add-paid-amount" class="form-label">Оплачено (₽)</label>
                        <input type="number" class="form-control" id="add-paid-amount" name="paid_amount" min="0" step="0.01" required>
                        <div class="invalid-feedback">Пожалуйста, укажите оплаченную сумму</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add-payment-date" class="form-label">Дата оплаты</label>
                        <div class="input-group">
                            <input type="text" class="form-control datepicker" id="add-payment-date" name="payment_date" placeholder="ДД.ММ.ГГГГ" autocomplete="off">
                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="saveAddItemBtn">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<!-- Добавление календарика для даты оплаты -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Проверяем, загружена ли уже библиотека flatpickr
        if (typeof flatpickr !== 'undefined') {
            initFlatpickr();
        } else {
            // Подключаем необходимые ресурсы, если они еще не загружены
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
            document.head.appendChild(link);
            
            var script1 = document.createElement('script');
            script1.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
            document.body.appendChild(script1);
            
            var script2 = document.createElement('script');
            script2.src = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js';
            script2.onload = function() {
                initFlatpickr();
            };
            document.body.appendChild(script2);
        }
        
        function initFlatpickr() {
            flatpickr('.datepicker', {
                locale: 'ru',
                dateFormat: 'd.m.Y',
                allowInput: true
            });
        }
        
        // Валидация формы при отправке
        document.getElementById('saveAddItemBtn').addEventListener('click', function() {
            const form = document.getElementById('addItemForm');
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (isValid) {
                // Здесь вызывается существующий код сохранения формы
                // Он должен быть в вашем основном JS коде
            }
        });
        
        // Убираем класс is-invalid при изменении поля
        document.querySelectorAll('#addItemForm input').forEach(input => {
            input.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                }
            });
        });
    });
</script>

<style>
    .is-invalid {
        border-color: #dc3545;
    }
    
    .invalid-feedback {
        display: none;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 80%;
        color: #dc3545;
    }
    
    .is-invalid + .invalid-feedback,
    .is-invalid ~ .invalid-feedback {
        display: block;
    }
</style>

<!-- Модальное окно для редактирования элемента -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редактировать элемент</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editItemForm">
                    <input type="hidden" id="edit-item-id" name="id">
                    <input type="hidden" id="edit-item-type" name="type">
                    
                    <div class="mb-3">
                        <label for="edit-name" class="form-label">Наименование</label>
                        <input type="text" class="form-control" id="edit-name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-total-amount" class="form-label">Сумма (₽)</label>
                        <input type="number" class="form-control" id="edit-total-amount" name="total_amount" min="0" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-paid-amount" class="form-label">Оплачено (₽)</label>
                        <input type="number" class="form-control" id="edit-paid-amount" name="paid_amount" min="0" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-payment-date" class="form-label">Дата оплаты</label>
                        <input type="date" class="form-control" id="edit-payment-date" name="payment_date">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="saveEditItemBtn">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для подтверждения удаления -->
<div class="modal fade" id="deleteItemModal" tabindex="-1" aria-labelledby="deleteItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteItemModalLabel">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить этот элемент?</p>
                <p id="delete-item-name" class="fw-bold"></p>
                <input type="hidden" id="delete-item-id">
                <input type="hidden" id="delete-item-type">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Удалить</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Загрузка финансовых данных
        loadFinanceData();
        
        // Переключение табов - загружает данные для активного таба
        document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(event) {
                const target = event.target.getAttribute('data-bs-target').replace('#', '');
                const type = getTypeFromTabId(target);
                displayFinanceItems(type);
            });
        });
        
        // При открытии модального окна для добавления, устанавливаем тип элемента
        document.getElementById('addItemModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const type = button.getAttribute('data-type');
            document.getElementById('add-item-type').value = type;
            
            // Очистка формы
            document.getElementById('addItemForm').reset();
        });
        
        // Обработчик сохранения нового элемента
        document.getElementById('saveAddItemBtn').addEventListener('click', function() {
            const form = document.getElementById('addItemForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const formData = new FormData(form);
            const type = document.getElementById('add-item-type').value;
            
            // Отправка данных на сервер
            fetch('{{ route('partner.projects.finance.store', $project) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Обновление данных в текущей вкладке
                    loadFinanceData().then(() => {
                        displayFinanceItems(type);
                    });
                    
                    // Правильно закрываем модальное окно
                    const modalElement = document.getElementById('addItemModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    
                    if (modal) {
                        modal.hide();
                        // Удаляем modal-backdrop вручную после закрытия модального окна
                        setTimeout(() => {
                            const backdrops = document.querySelectorAll('.modal-backdrop');
                            backdrops.forEach(backdrop => {
                                backdrop.remove();
                            });
                            document.body.classList.remove('modal-open');
                            document.body.style.overflow = '';
                            document.body.style.paddingRight = '';
                        }, 300);
                    }
                    
                    showAlert('success', 'Элемент успешно добавлен');
                } else {
                    showAlert('danger', 'Ошибка при сохранении: ' + (data.message || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Произошла ошибка при сохранении');
            });
        });
        
        // Обработчик открытия модального окна редактирования
        document.addEventListener('click', function(event) {
            // Обработка кнопки редактирования
            if (event.target.closest('.edit-item-btn')) {
                const btn = event.target.closest('.edit-item-btn');
                const itemId = btn.dataset.id;
                const itemType = btn.dataset.type;
                const itemName = btn.dataset.name;
                const itemTotal = btn.dataset.total;
                const itemPaid = btn.dataset.paid;
                const itemPaymentDate = btn.dataset.date || '';
                
                // Заполнение формы редактирования
                document.getElementById('edit-item-id').value = itemId;
                document.getElementById('edit-item-type').value = itemType;
                document.getElementById('edit-name').value = itemName;
                document.getElementById('edit-total-amount').value = itemTotal;
                document.getElementById('edit-paid-amount').value = itemPaid;
                document.getElementById('edit-payment-date').value = itemPaymentDate;
                
                // Открытие модального окна
                const modal = new bootstrap.Modal(document.getElementById('editItemModal'));
                modal.show();
            }
        });
        
        // Обработчик сохранения отредактированного элемента
        document.getElementById('saveEditItemBtn').addEventListener('click', function() {
            const form = document.getElementById('editItemForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const itemId = document.getElementById('edit-item-id').value;
            
            // Создаем данные формы
            const formData = new FormData(form);
            
            // Логгирование данных для диагностики
            console.log('Данные перед отправкой:', {
                name: formData.get('name'),
                type: formData.get('type'),
                total_amount: formData.get('total_amount'),
                paid_amount: formData.get('paid_amount'),
                payment_date: formData.get('payment_date')
            });
            
            formData.append('_method', 'PUT'); // Используем PUT метод через _method
            
            // Отправка данных на сервер
            fetch(`{{ url('partner/projects-finance') }}/${itemId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    if (response.status === 403) {
                        throw new Error('Доступ запрещен. Проверьте CSRF-токен или права доступа.');
                    }
                    return response.text().then(text => {
                        try {
                            // Попытка распарсить как JSON
                            const json = JSON.parse(text);
                            throw new Error(json.message || 'Ошибка сервера');
                        } catch (e) {
                            // Если не JSON, возвращаем общую ошибку
                            console.error('Ответ сервера не в формате JSON:', text);
                            throw new Error('Неверный формат ответа сервера');
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Обновление данных в текущей вкладке
                    loadFinanceData().then(() => {
                        displayFinanceItems(itemType);
                    });
                    
                    // Закрыть модальное окно
                    const modalElement = document.getElementById('editItemModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                    
                    showAlert('success', 'Элемент успешно обновлен');
                } else {
                    showAlert('danger', 'Ошибка при сохранении: ' + (data.message || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Произошла ошибка при сохранении: ' + error.message);
            });
        });
        
        // Обработчик открытия модального окна удаления
        document.addEventListener('click', function(event) {
            // Обработка кнопки удаления
            if (event.target.closest('.delete-item-btn')) {
                const btn = event.target.closest('.delete-item-btn');
                const itemId = btn.dataset.id;
                const itemName = btn.dataset.name;
                const itemType = btn.dataset.type;
                
                document.getElementById('delete-item-id').value = itemId;
                document.getElementById('delete-item-name').textContent = itemName;
                document.getElementById('delete-item-type').value = itemType;
                
                // Открытие модального окна подтверждения удаления
                const modal = new bootstrap.Modal(document.getElementById('deleteItemModal'));
                modal.show();
            }
        });
        
        // Обработчик подтверждения удаления
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            const itemId = document.getElementById('delete-item-id').value;
            const itemType = document.getElementById('delete-item-type').value;
            
            // Создаем форму для отправки DELETE запроса
            const form = document.createElement('form');
            form.style.display = 'none';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);
            
            document.body.appendChild(form);
            const formData = new FormData(form);
            document.body.removeChild(form);
            
            // Отправка запроса на удаление
            fetch(`{{ url('partner/projects-finance') }}/${itemId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            const json = JSON.parse(text);
                            throw new Error(json.message || 'Ошибка сервера');
                        } catch (e) {
                            console.error('Ответ сервера не в формате JSON:', text);
                            throw new Error('Неверный формат ответа сервера');
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Обновление данных в текущей вкладке
                    loadFinanceData().then(() => {
                        displayFinanceItems(itemType);
                    });
                    
                    // Закрыть модальное окно
                    const modalElement = document.getElementById('deleteItemModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                    
                    showAlert('success', 'Элемент успешно удален');
                } else {
                    showAlert('danger', 'Ошибка при удалении: ' + (data.message || 'Неизвестная ошибка'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Произошла ошибка при удалении: ' + error.message);
            });
        });
        
        // Добавим обработчик событий для модальных окон, чтобы удалять backdrop при скрытии
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('hidden.bs.modal', function() {
                // Удаляем modal-backdrop вручную после закрытия модального окна
                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => {
                        backdrop.remove();
                    });
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }, 300);
            });
        });

        // Функция для загрузки финансовых данных
        function loadFinanceData() {
            return fetch('{{ route('partner.projects.finance.index', $project) }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Группировка по типам
                        const items = data.data;
                        const grouped = {
                            main_work: [],
                            main_material: [],
                            additional_work: [],
                            additional_material: [],
                            transportation: []
                        };
                        
                        for (const item of items) {
                            if (grouped[item.type]) {
                                grouped[item.type].push(item);
                            }
                        }
                        
                        // Сохраняем данные глобально для использования в других функциях
                        window.financeData = grouped;
                        
                        // Отображаем данные для активного таба
                        const activeTab = document.querySelector('.nav-link.active').getAttribute('data-bs-target').replace('#', '');
                        const activeType = getTypeFromTabId(activeTab);
                        displayFinanceItems(activeType);
                        
                        return grouped;
                    } else {
                        console.error('Ошибка при загрузке данных:', data.message || 'Неизвестная ошибка');
                        showAlert('danger', 'Ошибка при загрузке данных');
                        return {};
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'Ошибка при загрузке данных');
                    return {};
                });
        }
        
        // Функция для отображения элементов определенного типа
        function displayFinanceItems(type) {
            const items = window.financeData[type] || [];
            const tableId = getTableIdFromType(type);
            const tableBody = document.querySelector(`#${tableId} tbody`);
            
            if (!tableBody) {
                console.error(`Таблица ${tableId} не найдена`);
                return;
            }
            
            tableBody.innerHTML = '';
            
            if (items.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = '<td colspan="6" class="text-center">Нет данных</td>';
                tableBody.appendChild(tr);
                return;
            }
            
            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                const tr = document.createElement('tr');
                
                // Форматирование даты
                let paymentDateFormatted = '';
                if (item.payment_date) {
                    const date = new Date(item.payment_date);
                    paymentDateFormatted = date.toLocaleDateString('ru-RU');
                }
                
                tr.innerHTML = `
                    <td>${i + 1}</td>
                    <td>${item.name}</td>
                    <td>${parseFloat(item.total_amount).toLocaleString('ru-RU')} ₽</td>
                    <td>${parseFloat(item.paid_amount).toLocaleString('ru-RU')} ₽</td>
                    <td>${paymentDateFormatted}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary edit-item-btn"
                                data-id="${item.id}"
                                data-type="${item.type}"
                                data-name="${item.name}"
                                data-total="${item.total_amount}"
                                data-paid="${item.paid_amount}"
                                data-date="${item.payment_date || ''}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger delete-item-btn"
                                data-id="${item.id}"
                                data-name="${item.name}"
                                data-type="${item.type}">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                `;
                
                tableBody.appendChild(tr);
            }
        }
        
        // Функция для отображения уведомлений
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            // Найти место для вставки уведомления
            const container = document.querySelector('.container-fluid');
            container.insertBefore(alertDiv, container.firstChild);
            
            // Автоматическое скрытие через 5 секунд
            setTimeout(() => {
                const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
                alert.close();
            }, 5000);
        }
        
        // Вспомогательные функции для преобразования ID табов и типов финансов
        function getTypeFromTabId(tabId) {
            const map = {
                'main-work': 'main_work',
                'main-materials': 'main_material',
                'additional-work': 'additional_work',
                'additional-materials': 'additional_material',
                'transportation': 'transportation'
            };
            return map[tabId] || 'main_work';
        }
        
        function getTableIdFromType(type) {
            const map = {
                'main_work': 'main-work-table',
                'main_material': 'main-materials-table',
                'additional_work': 'additional-work-table',
                'additional_material': 'additional-materials-table',
                'transportation': 'transportation-table'
            };
            return map[type] || 'main-work-table';
        }
    });
</script>
