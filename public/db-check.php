<?php
// Скрипт для проверки подключения к базе данных

// Включаем отображение всех ошибок для диагностики
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Проверка подключения к базе данных</h1>";

// Загружаем переменные окружения из файла .env
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Удаляем кавычки из значения, если они есть
        if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
            $value = substr($value, 1, -1);
        }
        
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

// Получаем настройки базы данных из переменных окружения
$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? '';
$username = $_ENV['DB_USERNAME'] ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';

echo "<h2>Настройки подключения:</h2>";
echo "<ul>";
echo "<li>Хост: {$host}</li>";
echo "<li>Порт: {$port}</li>";
echo "<li>База данных: {$database}</li>";
echo "<li>Пользователь: {$username}</li>";
echo "<li>Пароль: ***скрыт***</li>";
echo "</ul>";

// Пробуем подключиться к базе данных напрямую через PDO
try {
    echo "<h2>Пытаемся подключиться через PDO:</h2>";
    
    $dsn = "mysql:host={$host};port={$port};dbname={$database}";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Подключение успешно установлено!</p>";
    
    // Пробуем выполнить тестовый запрос
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Таблицы в базе данных:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>{$table}</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Ошибка подключения: " . $e->getMessage() . "</p>";
    
    // Пробуем подключиться без указания базы данных
    try {
        echo "<h2>Пробуем подключение без указания базы данных:</h2>";
        $dsn = "mysql:host={$host};port={$port}";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<p style='color: green;'>✓ Подключение к серверу MySQL успешно, но база данных '{$database}' может не существовать.</p>";
        
        // Проверяем существование базы данных
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p>Доступные базы данных:</p>";
        echo "<ul>";
        foreach ($databases as $db) {
            echo "<li>{$db}" . ($db == $database ? " ← выбранная база данных" : "") . "</li>";
        }
        echo "</ul>";
        
        if (!in_array($database, $databases)) {
            echo "<p style='color: orange;'>⚠ База данных '{$database}' не найдена на сервере.</p>";
        }
        
    } catch (PDOException $e2) {
        echo "<p style='color: red;'>✗ Не удалось подключиться к серверу MySQL: " . $e2->getMessage() . "</p>";
    }
}

// Проверяем доступность сервера MySQL через соединения сокета
try {
    echo "<h2>Пробуем подключение через сокет:</h2>";
    
    $mysqli = @new mysqli($host, $username, $password, $database, $port);
    
    if ($mysqli->connect_error) {
        throw new Exception($mysqli->connect_error);
    }
    
    echo "<p style='color: green;'>✓ Подключение через mysqli успешно!</p>";
    $mysqli->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Ошибка подключения через mysqli: " . $e->getMessage() . "</p>";
    
    // Рекомендации по исправлению
    echo "<h2>Возможные решения:</h2>";
    echo "<ol>";
    echo "<li>Проверьте, запущен ли MySQL сервер</li>";
    echo "<li>Убедитесь, что указан правильный порт (стандартный порт MySQL: 3306)</li>";
    echo "<li>Попробуйте изменить хост с '127.0.0.1' на 'localhost' или наоборот</li>";
    echo "<li>Проверьте правильность имени пользователя и пароля</li>";
    echo "<li>Убедитесь, что база данных '{$database}' существует</li>";
    echo "<li>Проверьте, что пользователь '{$username}' имеет доступ к базе данных</li>";
    echo "</ol>";
}
?>
