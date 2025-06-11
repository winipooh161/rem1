document.addEventListener('DOMContentLoaded', function() {
    /**
     * Инициализация автозаполнения для полей с адресами
     */
    function initAddressAutocomplete() {
        // Получаем все поля для ввода адреса
        const addressInputs = document.querySelectorAll('input[name="address"]');
        
        // Если элементы найдены, загружаем данные городов и настраиваем автозаполнение
        if (addressInputs.length > 0) {
            loadCities().then(cities => {
                addressInputs.forEach(input => {
                    setupAutocomplete(input, cities);
                });
            }).catch(error => {
                console.error('Ошибка загрузки данных городов:', error);
            });
        }
    }

    /**
     * Загрузка данных городов из JSON файла
     */
    async function loadCities() {
        try {
            const response = await fetch('/cities.json');
            if (!response.ok) {
                throw new Error(`HTTP ошибка! Статус: ${response.status}`);
            }
            const cities = await response.json();
            return cities;
        } catch (error) {
            console.error('Ошибка при загрузке городов:', error);
            throw error;
        }
    }

    /**
     * Настройка автозаполнения для поля ввода
     */
    function setupAutocomplete(inputElement, citiesData) {
        // Создаем элемент для списка подсказок
        const suggestionsContainer = document.createElement('div');
        suggestionsContainer.className = 'address-suggestions';
        inputElement.parentNode.appendChild(suggestionsContainer);
        
        // Подготавливаем данные для автозаполнения
        const addressOptions = citiesData.map(item => ({
            value: `${item.city}, ${item.region}`,
            city: item.city,
            region: item.region
        }));

        // Обработчик ввода текста
        inputElement.addEventListener('input', function() {
            const inputValue = this.value.toLowerCase();
            
            // Если поле пустое - скрываем подсказки
            if (!inputValue) {
                suggestionsContainer.innerHTML = '';
                suggestionsContainer.style.display = 'none';
                return;
            }
            
            // Фильтруем варианты
            const filteredOptions = addressOptions.filter(option => 
                option.city.toLowerCase().includes(inputValue) || 
                option.region.toLowerCase().includes(inputValue) ||
                option.value.toLowerCase().includes(inputValue)
            ).slice(0, 10); // Ограничиваем количество подсказок
            
            // Отображаем подсказки
            if (filteredOptions.length > 0) {
                suggestionsContainer.innerHTML = '';
                filteredOptions.forEach(option => {
                    const suggestion = document.createElement('div');
                    suggestion.className = 'suggestion-item';
                    suggestion.textContent = option.value;
                    suggestion.addEventListener('click', function() {
                        inputElement.value = option.value;
                        suggestionsContainer.innerHTML = '';
                        suggestionsContainer.style.display = 'none';
                    });
                    suggestionsContainer.appendChild(suggestion);
                });
                suggestionsContainer.style.display = 'block';
            } else {
                suggestionsContainer.innerHTML = '';
                suggestionsContainer.style.display = 'none';
            }
        });

        // Скрываем подсказки при клике вне поля
        document.addEventListener('click', function(e) {
            if (e.target !== inputElement && e.target !== suggestionsContainer) {
                suggestionsContainer.innerHTML = '';
                suggestionsContainer.style.display = 'none';
            }
        });

        // Показываем все доступные города при фокусе на пустом поле
        inputElement.addEventListener('focus', function() {
            if (!this.value) {
                suggestionsContainer.innerHTML = '';
                const topCities = [
                    { city: 'Москва', region: 'Москва и Московская обл.' },
                    { city: 'Санкт-Петербург', region: 'Санкт-Петербург и область' },
                    { city: 'Екатеринбург', region: 'Свердловская обл.' },
                    { city: 'Новосибирск', region: 'Новосибирская обл.' },
                    { city: 'Казань', region: 'Татарстан' }
                ];
                
                // Добавляем популярные города в начале списка
                topCities.forEach(city => {
                    const suggestion = document.createElement('div');
                    suggestion.className = 'suggestion-item popular';
                    suggestion.textContent = `${city.city}, ${city.region}`;
                    suggestion.addEventListener('click', function() {
                        inputElement.value = this.textContent;
                        suggestionsContainer.innerHTML = '';
                        suggestionsContainer.style.display = 'none';
                    });
                    suggestionsContainer.appendChild(suggestion);
                });
                
                // Разделитель между популярными и другими городами
                const divider = document.createElement('div');
                divider.className = 'suggestion-divider';
                divider.textContent = 'Начните вводить название города...';
                suggestionsContainer.appendChild(divider);
                
                suggestionsContainer.style.display = 'block';
            }
        });
    }

    // Запускаем инициализацию при загрузке страницы
    initAddressAutocomplete();

    // Также добавляем слушатель для динамически добавленных полей
    // (например, при AJAX загрузке форм)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                // Проверяем, не добавились ли новые поля адреса
                const newAddressInputs = document.querySelectorAll('input[name="address"]:not(.autocomplete-initialized)');
                if (newAddressInputs.length > 0) {
                    loadCities().then(cities => {
                        newAddressInputs.forEach(input => {
                            input.classList.add('autocomplete-initialized');
                            setupAutocomplete(input, cities);
                        });
                    });
                }
            }
        });
    });

    // Запускаем наблюдатель за DOM
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
