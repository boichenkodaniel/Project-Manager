<?php
// Тестовый скрипт для проверки создания пользователя

require_once 'src/backend/config/db.php';
require_once 'src/backend/models/UserModel.php';

try {
    echo "=== Тестирование создания пользователя ===\n";
    
    $pdo = Database::getInstance();
    
    // Тестовые данные
    $testData = [
        'fullname' => 'Тестовый Пользователь',
        'email' => 'test_' . time() . '@example.com',
        'login' => 'testuser_' . time(),
        'password' => 'testpass123',
        'role' => 'Исполнитель'
    ];
    
    echo "Создаем пользователя с данными:\n";
    print_r($testData);
    
    // Создаем пользователя
    $userModel = new UserModel();
    $newUser = $userModel->createUser(
        $testData['fullname'],
        $testData['email'],
        $testData['role'],
        $testData['login'],
        $testData['password']
    );
    
    echo "\n✅ Пользователь успешно создан:\n";
    print_r($newUser);
    
    // Проверяем, что пользователь действительно создался
    $stmt = $pdo->prepare('SELECT * FROM "User" WHERE id = ?');
    $stmt->execute([$newUser['id']]);
    $savedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nПроверка сохранения в БД:\n";
    echo "- ID: " . $savedUser['id'] . "\n";
    echo "- Имя: " . $savedUser['fullname'] . "\n";
    echo "- Email: " . $savedUser['email'] . "\n";
    echo "- Логин: " . $savedUser['login'] . "\n";
    echo "- Роль: " . $savedUser['role'] . "\n";
    echo "- Пароль захеширован: " . (!empty($savedUser['password']) ? 'Да' : 'Нет') . "\n";
    
    // Удаляем тестового пользователя
    $stmt = $pdo->prepare('DELETE FROM "User" WHERE id = ?');
    $stmt->execute([$newUser['id']]);
    
    echo "\n🧹 Тестовый пользователь удален\n";
    echo "=== Тест завершен успешно ===\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}