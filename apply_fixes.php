<?php
// Скрипт для применения исправлений базы данных

require_once 'src/backend/config/db.php';

try {
    $pdo = Database::getInstance();
    
    // Проверяем структуру таблицы project
    $stmt = $pdo->query("
        SELECT column_name, data_type, is_nullable 
        FROM information_schema.columns 
        WHERE table_name = 'project' 
        ORDER BY ordinal_position
    ");
    
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== Текущая структура таблицы 'project' ===\n";
    foreach ($columns as $col) {
        echo "- {$col['column_name']} ({$col['data_type']}) - nullable: {$col['is_nullable']}\n";
    }
    echo "\n";
    
    // Проверяем, есть ли уже колонка clientid
    $hasClientId = false;
    foreach ($columns as $col) {
        if ($col['column_name'] === 'clientid') {
            $hasClientId = true;
            break;
        }
    }
    
    if (!$hasClientId) {
        echo "Добавляем колонку clientid...\n";
        
        // Добавляем колонку clientid
        $pdo->exec('ALTER TABLE "project" ADD COLUMN clientid INTEGER REFERENCES "User"(id) ON DELETE SET NULL');
        
        echo "✅ Колонка clientid успешно добавлена!\n";
    } else {
        echo "ℹ️ Колонка clientid уже существует.\n";
    }
    
    // Проверяем пользователей с ролью "Клиент"
    echo "\n=== Проверка клиентов в базе ===\n";
    $stmt = $pdo->query('SELECT id, fullname, email, role FROM "User" WHERE role = \'Клиент\'');
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($clients)) {
        echo "⚠️ ВНИМАНИЕ: В базе нет пользователей с ролью 'Клиент'!\n";
        echo "Создаем тестового клиента...\n";
        
        // Создаем тестового клиента
        $stmt = $pdo->prepare('
            INSERT INTO "User" (fullname, email, login, password, role) 
            VALUES (?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            'Тестовый Клиент',
            'client@example.com', 
            'client',
            password_hash('password', PASSWORD_DEFAULT),
            'Клиент'
        ]);
        
        echo "✅ Тестовый клиент создан (логин: client, пароль: password)\n";
    } else {
        echo "Найдено клиентов: " . count($clients) . "\n";
        foreach ($clients as $client) {
            echo "- ID {$client['id']}: {$client['fullname']} ({$client['email']})\n";
        }
    }
    
    echo "\n=== Исправления применены успешно! ===\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}