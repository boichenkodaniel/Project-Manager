<?php
// models/NotificationModel.php

class NotificationModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // Создать уведомление
    // $message — полный смысловой текст уведомления; для совместимости с БД дублируем его и в title
    public function createNotification($userId, $message, $type = 'info', $relatedEntityType = null, $relatedEntityId = null) {
        $stmt = $this->pdo->prepare('
            INSERT INTO "notification" (user_id, title, message, type, related_entity_type, related_entity_id, is_read)
            VALUES (:user_id, :title, :message, :type, :related_entity_type, :related_entity_id, false)
            RETURNING id
        ');
        $stmt->execute([
            'user_id' => $userId,
            // Чтобы не ломать ограничение NOT NULL по столбцу title, дублируем текст
            'title' => $message,
            'message' => $message,
            'type' => $type,
            'related_entity_type' => $relatedEntityType,
            'related_entity_id' => $relatedEntityId
        ]);
        return $stmt->fetchColumn();
    }

    // Получить уведомления пользователя
    public function getUserNotifications($userId, $unreadOnly = false) {
        $sql = '
            SELECT * FROM "notification"
            WHERE user_id = :user_id
        ';
        if ($unreadOnly) {
            $sql .= ' AND is_read = false';
        }
        $sql .= ' ORDER BY created_at DESC LIMIT 50';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Отметить уведомление как прочитанное
    public function markAsRead($id) {
        $stmt = $this->pdo->prepare('UPDATE "notification" SET is_read = true WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    // Отметить все уведомления пользователя как прочитанные
    public function markAllAsRead($userId) {
        $stmt = $this->pdo->prepare('UPDATE "notification" SET is_read = true WHERE user_id = :user_id AND is_read = false');
        return $stmt->execute(['user_id' => $userId]);
    }

    // Удалить уведомление
    public function deleteNotification($id) {
        $stmt = $this->pdo->prepare('DELETE FROM "notification" WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    // Отправить уведомления участникам проекта
    public function notifyProjectParticipants($projectId, $message, $type = 'info', $excludeUserId = null) {
        // Получаем всех участников проекта (через задачи)
        $stmt = $this->pdo->prepare('
            SELECT DISTINCT u.id
            FROM "User" u
            INNER JOIN "task" t ON t.taskto = u.id OR t.taskby = u.id
            WHERE t.projectid = :project_id
            UNION
            SELECT clientid FROM "project" WHERE id = :project_id
        ');
        $stmt->execute(['project_id' => $projectId]);
        $participants = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $notifications = [];
        foreach ($participants as $userId) {
            if ($excludeUserId && $userId == $excludeUserId) continue;
            $notifications[] = $this->createNotification($userId, $message, $type, 'project', $projectId);
        }
        return $notifications;
    }

    // Отправить уведомление участникам задачи
    // $excludeUserId — создатель задачи, $excludeExecutorId — исполнитель (чтобы, например, не дублировать уведомление о назначении)
    public function notifyTaskParticipants($taskId, $message, $type = 'info', $excludeUserId = null, $excludeExecutorId = null) {
        // Получаем участников задачи
        $stmt = $this->pdo->prepare('
            SELECT DISTINCT u.id
            FROM "User" u
            INNER JOIN "task" t ON (t.taskto = u.id OR t.taskby = u.id)
            WHERE t.id = :task_id
        ');
        $stmt->execute(['task_id' => $taskId]);
        $participants = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $notifications = [];
        foreach ($participants as $userId) {
            if ($excludeUserId && $userId == $excludeUserId) continue;
            if ($excludeExecutorId && $userId == $excludeExecutorId) continue;
            $notifications[] = $this->createNotification($userId, $message, $type, 'task', $taskId);
        }
        return $notifications;
    }
}

