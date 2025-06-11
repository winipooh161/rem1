<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Функция для проверки подключения к базе данных
 * 
 * @return bool
 */
function checkDatabaseConnection()
{
    try {
        $connection = DB::connection();
        $connection->getPdo();
        return true;
    } catch (\Exception $e) {
        Log::error('Database connection failed: ' . $e->getMessage());
        
        // Логируем текущие настройки (без пароля)
        $config = config('database.connections.mysql');
        Log::info('Current DB settings: ' . json_encode([
            'driver' => $config['driver'] ?? 'not set',
            'host' => $config['host'] ?? 'not set',
            'port' => $config['port'] ?? 'not set',
            'database' => $config['database'] ?? 'not set',
            'username' => $config['username'] ?? 'not set',
        ]));
        
        return false;
    }
}
