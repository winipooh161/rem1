import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                  'resources/js/app.js',
             
            
            ],
            refresh: true,
        }),
    ],
    server: {
        // Разрешаем доступ с внешних хостов
        host: '0.0.0.0',
        // Настраиваем CORS для разрешения запросов с вашего домена
        cors: {
            origin: ['https://remont', 'http://remont'],
        },
        // Важно для прокси-серверов
        hmr: {
            host: 'localhost',
        },
    },
});
