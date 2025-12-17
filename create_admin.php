<?php
// Скрипт для создания первого администратора
// Запустите: php create_admin.php

require_once __DIR__ . '/src/backend/config/db.php';
require_once __DIR__ . '/src/backend/models/UserModel.php';

echo "=== Создание администратора ===\n\n";

// Параметры администратора (можно изменить)
$adminData = [
    'fullname' => 'Администратор',
    'email' => 'admin@example.com',
    'login' => 'admin',
    'password' => 'admin123', // Рекомендуется изменить после первого входа
    'role' => 'Администратор'
];

try {
    $userModel = new UserModel();
    
    // Проверяем, существует ли уже пользователь с таким логином
    $existing = $userModel->getUserByLogin($adminData['login']);
    if ($existing) {
        echo "❌ Пользователь с логином '{$adminData['login']}' уже существует!\n";
        echo "Используйте существующие данные для входа или измените логин в скрипте.\n";
        exit(1);
    }
    
    // Проверяем email
    $existingEmail = $userModel->getUserByEmail($adminData['email']);
    if ($existingEmail) {
        echo "❌ Пользователь с email '{$adminData['email']}' уже существует!\n";
        exit(1);
    }
    
    // Создаём администратора
    $user = $userModel->createUser(
        $adminData['fullname'],
        $adminData['email'],
        $adminData['role'],
        $adminData['login'],
        $adminData['password']
    );
    
    echo "✅ Администратор успешно создан!\n\n";
    echo "Данные для входа:\n";
    echo "Логин: {$adminData['login']}\n";
    echo "Пароль: {$adminData['password']}\n";
    echo "Email: {$adminData['email']}\n\n";
    echo "⚠️  ВАЖНО: Измените пароль после первого входа!\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}

