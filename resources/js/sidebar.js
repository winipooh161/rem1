/**
 * Скрипт для управления боковой панелью с адаптацией для мобильных устройств
 */

// Инициализация боковой панели при загрузке DOM
document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    handleMediaQueryChange(getMediaQuery());
    
    // Адаптивное поведение в зависимости от размера экрана
    window.addEventListener('resize', function() {
        handleMediaQueryChange(getMediaQuery());
    });
});

// Функция для получения текущего состояния медиа-запроса
function getMediaQuery() {
    return window.matchMedia('(max-width: 768px)').matches;
}

// Функция для инициализации поведения боковой панели
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const sidebarCollapseShow = document.getElementById('sidebarCollapseShow');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    
    if (!sidebar || !content) return;
    
    // Кнопка для открытия боковой панели на мобильных
    if (sidebarCollapseShow) {
        sidebarCollapseShow.addEventListener('click', function(e) {
            e.preventDefault();
            toggleSidebar(true);
            
            // Запрещаем прокрутку основного контента при открытом меню на мобильных
            if (window.innerWidth <= 768) {
                document.body.classList.add('sidebar-open');
            }
        });
    }
    
    // Кнопка для закрытия боковой панели
    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function(e) {
            e.preventDefault();
            toggleSidebar(false);
            
            // Возвращаем прокрутку основному контенту
            document.body.classList.remove('sidebar-open');
        });
    }
    
    // Закрытие боковой панели при клике вне её на мобильных
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768 && 
            sidebar.classList.contains('active') &&
            !sidebar.contains(event.target) && 
            event.target !== sidebarCollapseShow && 
            !sidebarCollapseShow?.contains(event.target)) {
            
            toggleSidebar(false);
            document.body.classList.remove('sidebar-open');
        }
    });
    
    // Реализация навигации по вложенным пунктам меню
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Получаем целевой элемент и переключаем его состояние
            const targetId = this.getAttribute('href') || this.getAttribute('data-bs-target');
            if (targetId) {
                const target = document.querySelector(targetId);
                if (target) {
                    target.classList.toggle('show');
                    this.setAttribute('aria-expanded', target.classList.contains('show'));
                    
                    // На мобильных устройствах плавно прокручиваем до элемента
                    if (window.innerWidth <= 768 && target.classList.contains('show')) {
                        setTimeout(() => {
                            this.scrollIntoView({
                                behavior: 'smooth',
                                block: 'nearest'
                            });
                        }, 300);
                    }
                }
            }
        });
    });
    
    // Активируем текущий пункт меню и его родителей
    highlightActiveMenuItem();
}

// Функция для переключения состояния боковой панели
function toggleSidebar(show) {
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    
    if (!sidebar || !content) return;
    
    if (show === undefined) {
        sidebar.classList.toggle('active');
        content.classList.toggle('active');
    } else if (show) {
        sidebar.classList.add('active');
        content.classList.add('active');
    } else {
        sidebar.classList.remove('active');
        content.classList.remove('active');
    }
}

// Функция для обработки изменения медиа-запроса
function handleMediaQueryChange(isMobile) {
    const sidebar = document.getElementById('sidebar');
    
    if (!sidebar) return;
    
    if (isMobile) {
        sidebar.classList.remove('active'); // Всегда скрываем сайдбар на мобильных при загрузке
        
        // Улучшаем взаимодействие с ссылками в сайдбаре на мобильных
        const sidebarLinks = sidebar.querySelectorAll('a:not(.dropdown-toggle)');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                setTimeout(() => {
                    toggleSidebar(false);
                    document.body.classList.remove('sidebar-open');
                }, 150);
            });
        });
    }
}

// Функция для подсветки активных пунктов меню
function highlightActiveMenuItem() {
    const currentPath = window.location.pathname;
    const menuItems = document.querySelectorAll('#sidebar a');
    
    menuItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href && currentPath.includes(href) && href !== '#' && href !== '/') {
            item.closest('li')?.classList.add('active');
            
            // Если это вложенный пункт, раскрываем родительское меню
            const parentCollapse = item.closest('.collapse');
            if (parentCollapse) {
                parentCollapse.classList.add('show');
                const parentToggle = document.querySelector(`[data-bs-target="#${parentCollapse.id}"], [href="#${parentCollapse.id}"]`);
                if (parentToggle) {
                    parentToggle.setAttribute('aria-expanded', 'true');
                    parentToggle.classList.remove('collapsed');
                }
            }
        }
    });
}
