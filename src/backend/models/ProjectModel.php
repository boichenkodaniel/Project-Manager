<?php
// models/ProjectModel.php

class ProjectModel {
    private $pdo;

    // Допустимые статусы
    private const ALLOWED_STATUSES = ['Черновик', 'В работе', 'Завершён', 'Отменён'];

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // Получить все проекты
    public function getAllProjects() {
        $sql = '
            SELECT 
                id,
                title,
                detaileddescription,
                startdate,
                plannedenddate,
                status,
                clientid
            FROM "project"
            ORDER BY startdate DESC
        ';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    // Получить проект по ID
    public function getProjectById($id) {
        $sql = '
            SELECT 
                id,
                title,
                detaileddescription,
                startdate,
                plannedenddate,
                status,
                clientid
            FROM "project"
            WHERE id = :id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    // Создать проект
    public function createProject($title, $description, $startDate, $plannedEndDate, $status, $clientId) {
        if (!in_array($status, self::ALLOWED_STATUSES)) {
            throw new InvalidArgumentException("Недопустимый статус: $status");
        }

        // Проверка существования клиента
        $clientExists = $this->pdo->prepare('SELECT 1 FROM "User" WHERE ID = :id');
        $clientExists->execute(['id' => $clientId]);
        if (!$clientExists->fetch()) {
            throw new InvalidArgumentException("Клиент с ID $clientId не найден");
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO "project" (
                title, detaileddescription, startdate, plannedenddate, status, clientid
            ) VALUES (
                :title, :description, :startDate, :plannedEndDate, :status, :clientId
            )
            RETURNING id
        ');

        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'startDate' => $startDate ?: null,
            'plannedEndDate' => $plannedEndDate ?: null,
            'status' => $status,
            'clientId' => $clientId
        ]);

        return $stmt->fetchColumn();
    }

    // Обновить проект
    public function updateProject($id, $title = null, $description = null, $startDate = null, $plannedEndDate = null, $status = null, $clientId = null) {
        if ($status && !in_array($status, self::ALLOWED_STATUSES)) {
            throw new InvalidArgumentException("Недопустимый статус: $status");
        }

        if ($clientId) {
            $clientExists = $this->pdo->prepare('SELECT 1 FROM "User" WHERE ID = :id');
            $clientExists->execute(['id' => $clientId]);
            if (!$clientExists->fetch()) {
                throw new InvalidArgumentException("Клиент с ID $clientId не найден");
            }
        }

        $fields = [];
        $params = ['id' => $id];

        if ($title !== null) { $fields[] = 'title = :title'; $params['title'] = $title; }
        if ($description !== null) { $fields[] = 'detaileddescription = :description'; $params['description'] = $description; }
        if ($startDate !== null) { $fields[] = 'startdate = :startDate'; $params['startDate'] = $startDate ?: null; }
        if ($plannedEndDate !== null) { $fields[] = 'plannedenddate = :plannedEndDate'; $params['plannedEndDate'] = $plannedEndDate ?: null; }
        if ($status !== null) { $fields[] = 'status = :status'; $params['status'] = $status; }
        if ($clientId !== null) { $fields[] = 'clientid = :clientId'; $params['clientId'] = $clientId; }

        if (empty($fields)) {
            throw new InvalidArgumentException("Нет данных для обновления");
        }

        $sql = 'UPDATE "project" SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // Удалить проект
    public function deleteProject($id) {
        $stmt = $this->pdo->prepare('DELETE FROM "project" WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
