/**
 * Скрипт для управления вкладками проекта с поддержкой запоминания активной вкладки
 */
document.addEventListener('DOMContentLoaded', function () {
    const tabsContainer = document.getElementById('projectTabs');
    
    if (!tabsContainer) return; // Выходим, если нет контейнера с вкладками
    
    const projectId = tabsContainer.getAttribute('data-project-id');
    const tabLinks = tabsContainer.querySelectorAll('[data-bs-toggle="tab"]');
    
    // Ключ для localStorage (уникальный для каждого проекта)
    const storageKey = `project_${projectId}_active_tab`;
    
    // Функция для активации вкладки
    const activateTab = (tabId) => {
        const tabToActivate = document.getElementById(tabId);
        if (tabToActivate) {
            // Используем Bootstrap Tab API для активации вкладки
            const tab = new bootstrap.Tab(tabToActivate);
            tab.show();
            
            // Также обновляем URL, чтобы сохранить состояние в истории браузера
            const newUrl = new URL(window.location);
            newUrl.searchParams.set('tab', tabId.replace('-tab', ''));
            window.history.replaceState({}, '', newUrl);
            
            // Сохраняем ID вкладки в localStorage
            localStorage.setItem(storageKey, tabId);
            
            // Для отладки
            console.log(`Активирована вкладка: ${tabId}`);
        } else {
            console.warn(`Вкладка с ID ${tabId} не найдена`);
        }
    };
    
    // Слушаем событие 'shown.bs.tab', которое срабатывает после показа вкладки
    tabsContainer.addEventListener('shown.bs.tab', function (event) {
        const tabId = event.target.id;
        
        // Обновляем URL с параметром tab без перезагрузки страницы
        const newUrl = new URL(window.location);
        newUrl.searchParams.set('tab', tabId.replace('-tab', ''));
        window.history.replaceState({}, '', newUrl);
        
        // Сохраняем ID вкладки в localStorage
        localStorage.setItem(storageKey, tabId);
    });
    
    // Определяем, какую вкладку активировать при загрузке страницы
    const determineActiveTab = () => {
        // 1. Проверяем параметр в URL - приоритет высший
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        
        if (tabParam) {
            return `${tabParam}-tab`;
        }
        
        // 2. Проверяем localStorage - второй приоритет
        const savedTab = localStorage.getItem(storageKey);
        if (savedTab) {
            return savedTab;
        }
        
        // 3. Если ничего не найдено, используем первую вкладку или уже активную
        const activeTabElement = tabsContainer.querySelector('.nav-link.active');
        if (activeTabElement) {
            return activeTabElement.id;
        }
        
        // Если нет активной вкладки, возвращаем первую
        const firstTab = tabsContainer.querySelector('.nav-link');
        return firstTab ? firstTab.id : null;
    };
    
    // Активируем определенную вкладку
    const tabToActivate = determineActiveTab();
    if (tabToActivate) {
        setTimeout(() => {
            activateTab(tabToActivate);
        }, 0);
    }
    
    // Также обрабатываем прямые клики на вкладки для обеспечения согласованности
    tabLinks.forEach(link => {
        link.addEventListener('click', function() {
            const tabId = this.id;
            localStorage.setItem(storageKey, tabId);
        });
    });
});
