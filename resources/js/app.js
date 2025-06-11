import './bootstrap';
import $ from 'jquery';

// Глобальный доступ к jQuery
window.$ = window.jQuery = $;

// Проверка загрузки Bootstrap и инициализация необходимых компонентов
document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap !== 'undefined') {
        console.log('Bootstrap доступен и готов к использованию');
    } else {
        console.warn('Bootstrap не определен! Проверьте импорты в bootstrap.js');
    }
});

// Функция для инициализации всех компонентов Bootstrap
function initializeBootstrapComponents() {
    if (typeof bootstrap !== 'undefined') {
        // Для dropdown НЕ создаем новые экземпляры, позволяем Bootstrap сделать это автоматически
        // Bootstrap сам инициализирует все dropdown по атрибуту data-bs-toggle="dropdown"
        
        // Для поповеров и тултипов продолжаем делать явную инициализацию
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        if (popoverTriggerList.length > 0) {
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        }
        
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        if (tooltipTriggerList.length > 0) {
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
        
        console.log('Bootstrap компоненты успешно инициализированы');
    }
}

// Функция для инициализации боковой панели
function initializeSidebar() {
    const sidebarCollapseShow = document.getElementById('sidebarCollapseShow');
    if (sidebarCollapseShow) {
        sidebarCollapseShow.addEventListener('click', function(e) {
            console.log('Кнопка меню нажата');
            // Добавляем класс для показа боковой панели на мобильных устройствах
            document.getElementById('sidebar').classList.add('active');
            
            // Добавляем затемняющий фон
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            overlay.addEventListener('click', function() {
                document.getElementById('sidebar').classList.remove('active');
                document.body.removeChild(overlay);
            });
            document.body.appendChild(overlay);
        });
    }
    
    // Обработчик для скрытия боковой панели
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('active');
            
            // Удаляем затемняющий фон, если он существует
            const overlay = document.querySelector('.sidebar-overlay');
            if (overlay) {
                document.body.removeChild(overlay);
            }
        });
    }
}

// Инициализируем компоненты при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    initializeBootstrapComponents();
    initializeSidebar();
});

// Импортируем наш новый скрипт автозаполнения адресов
import './address-autocomplete.js';

// Импортируем остальные скрипты
import './project-filters.js';
import './project-file-upload.js';
import './project-tabs.js';
import './mask.js';


