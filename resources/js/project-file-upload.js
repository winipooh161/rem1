/**
 * Обработка загрузки файлов для проектов
 */
document.addEventListener('DOMContentLoaded', function() {
    // Функция для обработки загрузки файлов
    function setupFileUpload(buttonId, formId) {
        const uploadButton = document.getElementById(buttonId);
        if (!uploadButton) return;

        uploadButton.addEventListener('click', function() {
            const form = document.getElementById(formId);
            const formData = new FormData(form);
            const modal = uploadButton.closest('.modal');
            const progressContainer = modal.querySelector('.upload-progress');
            const progressBar = progressContainer.querySelector('.progress-bar');
            const progressInfo = progressContainer.querySelector('.progress-info');
            
            // Показываем прогресс загрузки
            form.classList.add('d-none');
            progressContainer.classList.remove('d-none');
            progressBar.style.width = '0%';
            progressInfo.textContent = 'Подготовка к загрузке...';
            
            // Отключаем кнопки
            const buttons = modal.querySelectorAll('.modal-footer button');
            buttons.forEach(btn => btn.disabled = true);
            
            // Запрос на загрузку файла
            axios.post(form.action, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                onUploadProgress: function(progressEvent) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    progressBar.style.width = percentCompleted + '%';
                    progressInfo.textContent = `Загрузка: ${percentCompleted}%`;
                }
            })
            .then(function(response) {
                // Обрабатываем успешную загрузку
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.remove('progress-bar-striped');
                progressBar.classList.add('bg-success');
                progressInfo.textContent = 'Файл успешно загружен!';
                
                // Перезагружаем страницу через 1 секунду
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            })
            .catch(function(error) {
                // Обрабатываем ошибку
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.remove('progress-bar-striped');
                progressBar.classList.add('bg-danger');
                
                if (error.response && error.response.data && error.response.data.error) {
                    progressInfo.textContent = 'Ошибка: ' + error.response.data.error;
                } else {
                    progressInfo.textContent = 'Произошла ошибка при загрузке файла.';
                }
                
                // Включаем кнопки
                buttons.forEach(btn => btn.disabled = false);
                
                // Возвращаем форму через 2 секунды
                setTimeout(function() {
                    progressContainer.classList.add('d-none');
                    form.classList.remove('d-none');
                }, 2000);
            });
        });
    }
    
    // Настройка обработчиков для каждого типа файлов
    setupFileUpload('uploadDesignButton', 'uploadDesignForm');
    setupFileUpload('uploadSchemeButton', 'uploadSchemeForm');
    setupFileUpload('uploadDocumentButton', 'uploadDocumentForm');
    setupFileUpload('uploadContractButton', 'uploadContractForm');
    setupFileUpload('uploadOtherButton', 'uploadOtherForm');
    
    // Обработчик удаления файлов
    document.querySelectorAll('.delete-file').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Вы уверены, что хотите удалить этот файл? Это действие невозможно отменить.')) {
                return;
            }
            
            const fileId = this.getAttribute('data-file-id');
            const fileItem = document.querySelector(`.file-item[data-file-id="${fileId}"]`);
            
            axios.delete(`/partner/project-files/${fileId}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(function(response) {
                // Анимация удаления элемента
                fileItem.style.opacity = '0';
                fileItem.style.transform = 'scale(0.8)';
                fileItem.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    fileItem.remove();
                    
                    // Проверяем, остались ли ещё файлы в контейнере
                    const container = document.querySelector('.files-container');
                    if (container && container.children.length === 0) {
                        // Если файлов не осталось, перезагружаем страницу
                        window.location.reload();
                    }
                }, 300);
            })
            .catch(function(error) {
                alert('Произошла ошибка при удалении файла.');
                console.error(error);
            });
        });
    });
});
