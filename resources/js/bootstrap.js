import 'bootstrap';
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Добавляем функциональность для определения мобильных устройств 
window.isMobile = function() {
    return window.innerWidth < 768 || 
           navigator.maxTouchPoints > 1 || 
           /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
};

// Настраиваем поведение для мобильных устройств
if (window.isMobile()) {
    // Устанавливаем тайм-аут для уведомлений на мобильных
    window.mobileAlertTimeout = 3000; // 3 секунды вместо стандартных 5
    
    // Увеличиваем область нажатия для интерактивных элементов
    document.addEventListener('DOMContentLoaded', function() {
        // Устанавливаем минимальную высоту кнопок
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(function(btn) {
            if (!btn.classList.contains('btn-lg')) {
                btn.style.minHeight = '44px';
            }
        });
        
        // Восстанавливаем позицию прокрутки при возвращении назад
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'auto';
        }
    });
}

// Инициализируем всплывающие подсказки Bootstrap если они есть
document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap !== 'undefined') {
        // Активируем все всплывающие подсказки
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        if (tooltipTriggerList.length > 0) {
            [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        }
    }
});
