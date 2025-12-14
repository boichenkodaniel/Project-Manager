<?php
// models/ResourceModel.php

class ResourceModel {
    private $pdo;

    // Возможные статусы (можно вынести в ENUM позже)
    private const ALLOWED_STATUSES = ['Доступен', 'Занят', 'В ремонте', 'Списан'];

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // Получить все ресурсы
    public function getAllResources() {
        $sql = '
            SELECT ID, Name, Type, Description, Status
            FROM "Resource"
            ORDER BY Type, Name
        ';
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    // Получить ресурс по ID
    public function getResourceById($id) {
        $stmt = $this->pdo->prepare('
            SELECT ID, Name, Type, Description, Status
            FROM "Resource"
            WHERE ID = :id
        ');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    // Создать ресурс
    public function createResource($name, $type, $description, $status) {
        if (!$name || !$type) {
            throw new InvalidArgumentException("Поля 'Name' и 'Type' обязательны");
        }

        if ($status && !in_array($status, self::ALLOWED_STATUSES)) {
            throw new InvalidArgumentException("Недопустимый статус: $status. Допустимые: " . implode(', ', self::ALLOWED_STATUSES));
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO "Resource" (Name, Type, Description, Status)
            VALUES (:name, :type, :description, :status)
            RETURNING ID
        ');

        $stmt->execute([
            'name' => $name,
            'type' => $type,
            'description' => $description ?? null,
            'status' => $status ?: 'Доступен'
        ]);

        return $stmt->fetchColumn();
    }

    // Обновить ресурс
    public function updateResource($id, $name, $type, $description, $status) {
        if ($status !== null && $status !== '' && !in_array($status, self::ALLOWED_STATUSES)) {
            throw new InvalidArgumentException("Недопустимый статус: $status");
        }

        $fields = [];
        $params = ['id' => $id];

        if ($name !== null) { $fields[] = 'Name = :name'; $params['name'] = $name; }
        if ($type !== null) { $fields[] = 'Type = :type'; $params['type'] = $type; }
        if ($description !== null) { $fields[] = 'Description = :description'; $params['description'] = $description; }
        if ($status !== null) { $fields[] = 'Status = :status'; $params['status'] = $status ?: 'Доступен'; }

        if (empty($fields)) {
            throw new InvalidArgumentException("Нет данных для обновления");
        }

        $sql = 'UPDATE "Resource" SET ' . implode(', ', $fields) . ' WHERE ID = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // Удалить ресурс
    public function deleteResource($id) {
        $stmt = $this->pdo->prepare('DELETE FROM "Resource" WHERE ID = :id');
        return $stmt->execute(['id' => $id]);
    }

    // ✅ Фильтрация: по типу, статусу, части имени
    public function filterResources($name = null, $type = null, $status = null) {
        $sql = 'SELECT ID, Name, Type, Description, Status FROM "Resource" WHERE 1=1';
        $params = [];

        if ($name) {
            $sql .= ' AND Name ILIKE :name';
            $params['name'] = "%$name%";
        }
        if ($type) {
            $sql .= ' AND Type ILIKE :type';
            $params['type'] = "%$type%";
        }
        if ($status) {
            $sql .= ' AND Status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY Type, Name';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ✅ Статистика по статусам
    public function getStatusSummary() {
        $sql = '
            SELECT Status, COUNT(*) AS count
            FROM "Resource"
            GROUP BY Status
            ORDER BY count DESC
        ';
        return $this->pdo->query($sql)->fetchAll();
    }
}