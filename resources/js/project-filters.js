/**
 * Скрипт для управления фильтрацией проектов
 */
document.addEventListener('DOMContentLoaded', function() {
    // Получаем форму фильтрации, если она существует на странице
    const filterForm = document.getElementById('filterForm');
    if (!filterForm) return;
    
    // Управление отображением активных фильтров
    const updateFilterBadges = () => {
        const filterBadgesContainer = document.getElementById('active-filters');
        if (!filterBadgesContainer) return;
        
        // Очищаем контейнер
        filterBadgesContainer.innerHTML = '';
        
        // Получаем все активные фильтры
        const activeFilters = [];
        
        // Проверяем поиск
        const searchInput = filterForm.querySelector('input[name="search"]');
        if (searchInput && searchInput.value.trim()) {
            activeFilters.push({
                type: 'search',
                label: `Поиск: ${searchInput.value.trim()}`,
                value: searchInput.value.trim()
            });
        }
        
        // Проверяем выбранные селекты
        filterForm.querySelectorAll('select').forEach(select => {
            if (select.value) {
                const selectedOption = select.options[select.selectedIndex];
                activeFilters.push({
                    type: select.name,
                    label: `${select.previousElementSibling?.textContent || ''}: ${selectedOption.textContent}`,
                    value: select.value
                });
            }
        });
        
        // Если есть активные фильтры, показываем их
        if (activeFilters.length > 0) {
            filterBadgesContainer.innerHTML = '<span class="me-2">Активные фильтры:</span>';
            
            activeFilters.forEach(filter => {
                const badge = document.createElement('span');
                badge.className = 'badge bg-light text-dark me-2 mb-1';
                badge.textContent = filter.label;
                filterBadgesContainer.appendChild(badge);
            });
        }
    };
    
    // Вызываем функцию при загрузке страницы
    updateFilterBadges();
    
    // Обработчики для фильтров на странице проектов
    const filterSelects = filterForm.querySelectorAll('select');
    const searchInput = filterForm.querySelector('input[name="search"]');
    
    // Авто-отправка формы при изменении селектов
    filterSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            filterForm.submit();
        });
    });
    
    // Отправка формы поиска после паузы в наборе текста на десктопах
    let typingTimer;
    const doneTypingInterval = 800; // время в мс
    
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            // Для мобильных устройств не используем автоматическую отправку
            if (window.innerWidth > 768) {
                clearTimeout(typingTimer);
                if (searchInput.value) {
                    typingTimer = setTimeout(function() {
                        filterForm.submit();
                    }, doneTypingInterval);
                }
            }
        });
        
        // Сбросить таймер, если пользователь продолжил печатать
        searchInput.addEventListener('keydown', function() {
            clearTimeout(typingTimer);
        });
    }

    // Обработчики для AJAX-фильтров (для страницы смет)
    const ajaxFilterForm = document.querySelector('.ajax-filter-form');
    if (ajaxFilterForm) {
        const ajaxFilters = ajaxFilterForm.querySelectorAll('.ajax-filter');
        
        ajaxFilters.forEach(function(filter) {
            filter.addEventListener('change', function() {
                ajaxFilterForm.submit();
            });
        });
    }
});
