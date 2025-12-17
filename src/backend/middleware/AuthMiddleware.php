<?php
// middleware/AuthMiddleware.php

class AuthMiddleware {
    // Проверка авторизации
    public static function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Не авторизован'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Проверка роли
    public static function requireRole($allowedRoles) {
        self::requireAuth();
        
        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }

        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Доступ запрещён'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Проверка прав на проект (руководитель проекта или админ)
    public static function requireProjectManager($projectId) {
        self::requireAuth();

        // Админ имеет доступ ко всему
        if ($_SESSION['user_role'] === 'Администратор') {
            return;
        }

        // Руководитель проектов имеет доступ ко всем проектам
        if ($_SESSION['user_role'] === 'Руководитель проектов') {
            return;
        }

        // Проверяем, является ли пользователь клиентом проекта
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT clientid FROM "project" WHERE id = :id');
        $stmt->execute(['id' => $projectId]);
        $project = $stmt->fetch();

        if ($project && $project['clientid'] == $_SESSION['user_id']) {
            return;
        }

        // Проверяем, является ли пользователь участником проекта через задачи
        $stmt = $pdo->prepare('
            SELECT 1 FROM "task" 
            WHERE projectid = :project_id 
            AND (taskto = :user_id OR taskby = :user_id)
            LIMIT 1
        ');
        $stmt->execute([
            'project_id' => $projectId,
            'user_id' => $_SESSION['user_id']
        ]);

        if ($stmt->fetch()) {
            return;
        }

        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Доступ к проекту запрещён'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

