/**
 * Исправление проблемы с двойным кликом в выпадающем меню
 * 
 * Этот скрипт предотвращает проблему, когда один клик на кнопке 
 * выпадающего меню считается дважды, из-за чего меню не открывается
 */
document.addEventListener('DOMContentLoaded', function() {
    // Удаляем все существующие обработчики событий с кнопок выпадающих меню
    document.querySelectorAll('.dropdown-toggle').forEach(function(button) {
        // Клонируем элемент, чтобы удалить все обработчики событий
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
    });
    
    // Инициализируем все выпадающие меню заново
    document.querySelectorAll('.dropdown-toggle').forEach(function(button) {
        // Добавляем атрибут data-bs-auto-close="true", чтобы меню закрывалось по клику вне него
        button.setAttribute('data-bs-auto-close', 'true');
        
        // Создаем новый экземпляр Dropdown с нашими настройками
        const dropdown = new bootstrap.Dropdown(button, {
            autoClose: true
        });
        
        // Добавляем обработчик клика, который предотвращает всплытие события
        button.addEventListener('click', function(event) {
            // Останавливаем всплытие события, чтобы избежать двойной обработки
            event.stopPropagation();
        });
    });
    
    // Обрабатываем нажатия на элементы в dropdown-menu
    document.querySelectorAll('.dropdown-item:not(.delete-btn)').forEach(function(item) {
        item.addEventListener('click', function(event) {
            // Если это не кнопка удаления, то предотвращаем всплытие
            if (!this.classList.contains('delete-btn')) {
                event.stopPropagation();
            }
        });
    });
    
    // Для кнопок удаления добавляем подтверждение
    document.querySelectorAll('.delete-form').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const itemName = this.getAttribute('data-name') || 'этот элемент';
            
            if (confirm(`Вы действительно хотите удалить ${itemName}?`)) {
                this.submit();
            }
        });
    });
});
