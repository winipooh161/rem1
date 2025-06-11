
// Импортируем jQuery (если не установлен, установите через npm install jquery)
try {
    // Пробуем импортировать jQuery
    const jQuery = require('jquery');
    window.$ = window.jQuery = jQuery;
    console.log('jQuery успешно импортирован в mask.js');
} catch (e) {
    console.error('Ошибка импорта jQuery:', e);
}

// Немедленно вызываемая функция для обработки маски телефона
(function() {
    function applyPhoneMask() {
        console.log('Applying phone mask...');
        
        // Выбираем все поля телефона
        const phoneInputs = document.querySelectorAll('input[name="phone"]');
        
        if (phoneInputs.length > 0) {
            phoneInputs.forEach(input => {
                try {
                    // Очищаем предыдущую маску, если есть
                    if (input.inputmask) {
                        input.inputmask.remove();
                    }
                    
                    // Создаём и применяем маску
                    const im = new Inputmask({
                        mask: "+7 (999) 999-99-99",
                        clearMaskOnLostFocus: false,
                        showMaskOnHover: true,
                        showMaskOnFocus: true,
                        placeholder: "_",
                        oncomplete: function() {
                            // Отправка события для валидации формы
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        },
                        onBeforePaste: function(pastedValue) {
                            let cleaned = pastedValue.replace(/[^0-9]/g, '');
                            if (cleaned.startsWith('7') || cleaned.startsWith('8')) {
                                cleaned = cleaned.substring(1);
                            }
                            return cleaned;
                        }
                    });
                    
                    im.mask(input);
                    input.dataset.masked = "true";
                    
                    // Устанавливаем placeholder
                    if (!input.value) {
                        input.placeholder = "+7 (___) ___-__-__";
                    }
                } catch (e) {
                    console.error('Error applying mask:', e);
                }
            });
        }
    }
    
    // Функция для валидации полей имени (только русские буквы)
    function setupNameValidation() {
        console.log('Setting up name field validation...');
        
        // Находим все поля с name="name"
        const nameInputs = document.querySelectorAll('input[name="name"]');
        
        if (nameInputs.length > 0) {
            nameInputs.forEach(input => {
                console.log('Adding name validation to:', input.id || 'unnamed field');
                
                // Устанавливаем подсказку и атрибуты валидации
                input.setAttribute('pattern', '[А-Яа-яЁё\\s]+');
                input.setAttribute('title', 'Только русские буквы');
                
                if (!input.placeholder) {
                    input.placeholder = "Введите ваше имя (только русские буквы)";
                }
                
                // Удаляем предыдущие обработчики, если они были
                input.removeEventListener('input', nameInputHandler);
                input.removeEventListener('blur', nameBlurHandler);
                
                // Добавляем обработчик события input для мгновенной валидации
                input.addEventListener('input', nameInputHandler);
                
                // Добавляем обработчик при потере фокуса для валидации
                input.addEventListener('blur', nameBlurHandler);
                
                // Добавляем подсказку под полем ввода, если её нет
                if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('form-text')) {
                    const helpText = document.createElement('div');
                    helpText.classList.add('form-text');
                    helpText.textContent = 'Используйте только русские буквы и пробелы';
                    input.parentNode.insertBefore(helpText, input.nextSibling);
                }
            });
        }
    }
    
    // Функция для обработки ввода в поле имени - только русские буквы, никаких цифр
    function nameInputHandler(e) {
        const value = e.target.value;
        // Фильтруем всё, кроме русских букв и пробелов (удаляем цифры и другие символы)
        const filteredValue = value.replace(/[^А-Яа-яЁё\s]/g, '');
        
        if (value !== filteredValue) {
            e.target.value = filteredValue;
        }
    }
    
    // Функция для проверки при потере фокуса
    function nameBlurHandler(e) {
        const value = e.target.value.trim();
        
        // Проверяем, содержит ли значение только допустимые символы (русские буквы и пробелы)
        if (value && !/^[А-Яа-яЁё\s]+$/.test(value)) {
            e.target.classList.add('is-invalid');
        } else {
            e.target.classList.remove('is-invalid');
        }
    }

    // Настраиваем наблюдатель за изменениями DOM
    function initObserver() {
        const observer = new MutationObserver(mutations => {
            let shouldApplyMasks = false;
            
            mutations.forEach(mutation => {
                if (mutation.addedNodes.length) {
                    shouldApplyMasks = true;
                }
            });
            
            if (shouldApplyMasks) {
                applyPhoneMask();
                setupNameValidation();
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Проверка готовности DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            applyPhoneMask();
            setupNameValidation();
            initObserver();
        });
    } else {
        applyPhoneMask();
        setupNameValidation();
        initObserver();
    }
    
    // Повторное применение масок при полной загрузке страницы
    window.addEventListener('load', () => {
        applyPhoneMask();
        setupNameValidation();
    });
    
    if (typeof $ === 'undefined' && typeof jQuery !== 'undefined') {
        window.$ = jQuery;
    }
    
    if (typeof $ !== 'undefined') {
        // Применение маски для телефонных номеров
        $('input[name="phone"]').mask('+7 (999) 999-99-99');
    } else {
        console.error('jQuery не загружен. Маска для телефонов не будет работать.');
    }
});
