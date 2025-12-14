<?php
// models/ProjectModel.php

class ProjectModel {
    private $pdo;

    // Допустимые статусы — согласно типу project_status в БД
    private const ALLOWED_STATUSES = ['Черновик', 'В работе', 'Завершён', 'Отменён'];

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // Получить все проекты (+ имя менеджера)
    public function getAllProjects($withManager = true) {
        $sql = '
            SELECT 
                p.ID,
                p.Title,
                p.DetailedDescription,
                p.StartDate,
                p.PlannedEndDate,
                p.ActualEndDate,
                p.Status,
                p.ManagerID
        ';

        if ($withManager) {
            $sql .= ',
                u.fullname AS manager_fullname,
                u.login AS manager_login
            ';
        }

        $sql .= '
            FROM "Project" p
            LEFT JOIN "User" u ON u.ID = p.ManagerID
            ORDER BY p.StartDate DESC
        ';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    // Получить проект по ID
    public function getProjectById($id) {
        $sql = '
            SELECT 
                p.ID,
                p.Title,
                p.DetailedDescription,
                p.StartDate,
                p.PlannedEndDate,
                p.ActualEndDate,
                p.Status,
                p.ManagerID,
                u.fullname AS manager_fullname,
                u.login AS manager_login
            FROM "Project" p
            LEFT JOIN "User" u ON u.ID = p.ManagerID
            WHERE p.ID = :id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    // Создать проект
    public function createProject($title, $description, $startDate, $plannedEndDate, $actualEndDate, $status, $managerId) {
        if (!in_array($status, self::ALLOWED_STATUSES)) {
            throw new InvalidArgumentException("Недопустимый статус: $status");
        }

        // Проверим, что ManagerID существует (опционально — можно вынести в контроллер)
        $userExists = $this->pdo->prepare('SELECT 1 FROM "User" WHERE ID = :id');
        $userExists->execute(['id' => $managerId]);
        if (!$userExists->fetch()) {
            throw new InvalidArgumentException("Менеджер с ID $managerId не найден");
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO "Project" (
                Title, DetailedDescription, StartDate, PlannedEndDate, ActualEndDate, Status, ManagerID
            ) VALUES (
                :title, :description, :startDate, :plannedEndDate, :actualEndDate, :status, :managerId
            )
            RETURNING ID
        ');

        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'startDate' => $startDate ?: null,
            'plannedEndDate' => $plannedEndDate ?: null,
            'actualEndDate' => $actualEndDate ?: null,
            'status' => $status,
            'managerId' => $managerId
        ]);

        return $stmt->fetchColumn();
    }

    // Обновить проект
    public function updateProject($id, $title, $description, $startDate, $plannedEndDate, $actualEndDate, $status, $managerId) {
        if ($status && !in_array($status, self::ALLOWED_STATUSES)) {
            throw new InvalidArgumentException("Недопустимый статус: $status");
        }

        // Проверка существования менеджера
        if ($managerId) {
            $userExists = $this->pdo->prepare('SELECT 1 FROM "User" WHERE ID = :id');
            $userExists->execute(['id' => $managerId]);
            if (!$userExists->fetch()) {
                throw new InvalidArgumentException("Менеджер с ID $managerId не найден");
            }
        }

        $fields = [];
        $params = ['id' => $id];

        if ($title !== null) { $fields[] = 'Title = :title'; $params['title'] = $title; }
        if ($description !== null) { $fields[] = 'DetailedDescription = :description'; $params['description'] = $description; }
        if ($startDate !== null) { $fields[] = 'StartDate = :startDate'; $params['startDate'] = $startDate ?: null; }
        if ($plannedEndDate !== null) { $fields[] = 'PlannedEndDate = :plannedEndDate'; $params['plannedEndDate'] = $plannedEndDate ?: null; }
        if ($actualEndDate !== null) { $fields[] = 'ActualEndDate = :actualEndDate'; $params['actualEndDate'] = $actualEndDate ?: null; }
        if ($status !== null) { $fields[] = 'Status = :status'; $params['status'] = $status; }
        if ($managerId !== null) { $fields[] = 'ManagerID = :managerId'; $params['managerId'] = $managerId; }

        if (empty($fields)) {
            throw new InvalidArgumentException("Нет данных для обновления");
        }

        $sql = 'UPDATE "Project" SET ' . implode(', ', $fields) . ' WHERE ID = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // Удалить проект
    public function deleteProject($id) {
        $stmt = $this->pdo->prepare('DELETE FROM "Project" WHERE ID = :id');
        return $stmt->execute(['id' => $id]);
    }
}