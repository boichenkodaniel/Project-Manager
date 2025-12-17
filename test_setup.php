<?php
// Тестовый скрипт для проверки настроек
// Запустите: php test_setup.php

echo "=== Проверка настроек системы ===\n\n";

// 1. Проверка подключения к БД
echo "1. Проверка подключения к БД...\n";
try {
    require_once __DIR__ . '/src/backend/config/db.php';
    $pdo = Database::getInstance();
    echo "   ✅ Подключение к БД успешно\n";
    
    // Проверка таблиц
    $tables = ['User', 'project', 'task', 'issue', 'notification'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT 1 FROM \"$table\" LIMIT 1");
            echo "   ✅ Таблица '$table' существует\n";
        } catch (Exception $e) {
            echo "   ❌ Таблица '$table' не найдена: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Ошибка подключения: " . $e->getMessage() . "\n";
    echo "   Проверьте настройки в src/backend/config/db.php\n";
}

echo "\n";

// 2. Проверка пользователей
echo "2. Проверка пользователей...\n";
try {
    require_once __DIR__ . '/src/backend/models/UserModel.php';
    $userModel = new UserModel();
    $users = $userModel->getAllUsers();
    
    if (count($users) > 0) {
        echo "   ✅ Найдено пользователей: " . count($users) . "\n";
        foreach ($users as $user) {
            echo "      - {$user['fullname']} ({$user['login']}) - {$user['role']}\n";
        }
    } else {
        echo "   ⚠️  Пользователи не найдены. Запустите create_admin.php для создания администратора\n";
    }
} catch (Exception $e) {
    echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Проверка PHP расширений
echo "3. Проверка PHP расширений...\n";
$required = ['pdo', 'pdo_pgsql', 'json', 'session'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ Расширение '$ext' установлено\n";
    } else {
        echo "   ❌ Расширение '$ext' не установлено\n";
    }
}

echo "\n";

// 4. Проверка прав доступа
echo "4. Проверка прав доступа...\n";
$writableDirs = [__DIR__ . '/src/backend'];
foreach ($writableDirs as $dir) {
    if (is_writable($dir)) {
        echo "   ✅ Директория '$dir' доступна для записи\n";
    } else {
        echo "   ⚠️  Директория '$dir' может быть недоступна для записи (сессии могут не работать)\n";
    }
}

echo "\n=== Проверка завершена ===\n";

