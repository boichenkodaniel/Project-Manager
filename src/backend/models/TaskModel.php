<?php
// models/TaskModel.php

class TaskModel {
    private $pdo;

    private const ALLOWED_PRIORITIES = ['Высокий', 'Средний', 'Низкий'];
    private const ALLOWED_STATUSES = ['К выполнению', 'В работе', 'На проверке', 'Выполнена'];

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // Получить все задачи (с проектом и исполнителем)
    public function getAllTasks($withRelations = true) {
        $sql = '
            SELECT 
                t.ID,
                t.Title,
                t.Description,
                t.ProjectID,
                t.ExecutorID,
                t.CreationDate,
                t.PlannedStartDate,
                t.PlannedEndDate,
                t.ActualStartDate,
                t.ActualEndDate,
                t.Priority,
                t.Status
        ';

        if ($withRelations) {
            $sql .= ',
                p.Title AS project_title,
                u.fullname AS executor_fullname,
                u.login AS executor_login
            ';
        }

        $sql .= '
            FROM "Task" t
            LEFT JOIN "Project" p ON p.ID = t.ProjectID
            LEFT JOIN "User" u ON u.ID = t.ExecutorID
            ORDER BY t.CreationDate DESC, t.Priority DESC
        ';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    // Получить задачу по ID
    public function getTaskById($id) {
        $sql = '
            SELECT 
                t.ID,
                t.Title,
                t.Description,
                t.ProjectID,
                t.ExecutorID,
                t.CreationDate,
                t.PlannedStartDate,
                t.PlannedEndDate,
                t.ActualStartDate,
                t.ActualEndDate,
                t.Priority,
                t.Status,
                p.Title AS project_title,
                u.fullname AS executor_fullname,
                u.login AS executor_login
            FROM "Task" t
            LEFT JOIN "Project" p ON p.ID = t.ProjectID
            LEFT JOIN "User" u ON u.ID = t.ExecutorID
            WHERE t.ID = :id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    // Создать задачу
    public function createTask($title, $description, $projectID, $executorID, $plannedStart, $plannedEnd, $actualStart, $actualEnd, $priority, $status) {
        // Валидация статуса и приоритета
        if ($priority && !in_array($priority, self::ALLOWED_PRIORITIES)) {
            throw new InvalidArgumentException("Недопустимый приоритет: $priority");
        }
        if ($status && !in_array($status, self::ALLOWED_STATUSES)) {
            throw new InvalidArgumentException("Недопустимый статус: $status");
        }

        // Проверка существования ProjectID
        $projExists = $this->pdo->prepare('SELECT 1 FROM "Project" WHERE ID = :id');
        $projExists->execute(['id' => $projectID]);
        if (!$projExists->fetch()) {
            throw new InvalidArgumentException("Проект с ID $projectID не найден");
        }

        // Проверка ExecutorID (может быть NULL)
        if ($executorID !== null) {
            $userExists = $this->pdo->prepare('SELECT 1 FROM "User" WHERE ID = :id');
            $userExists->execute(['id' => $executorID]);
            if (!$userExists->fetch()) {
                throw new InvalidArgumentException("Исполнитель с ID $executorID не найден");
            }
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO "Task" (
                Title, Description, ProjectID, ExecutorID,
                PlannedStartDate, PlannedEndDate, ActualStartDate, ActualEndDate,
                Priority, Status
            ) VALUES (
                :title, :description, :projectID, :executorID,
                :plannedStart, :plannedEnd, :actualStart, :actualEnd,
                :priority, :status
            )
            RETURNING ID
        ');

        $stmt->execute([
            'title' => $title,
            'description' => $description ?? null,
            'projectID' => $projectID,
            'executorID' => $executorID,
            'plannedStart' => $plannedStart ?: null,
            'plannedEnd' => $plannedEnd ?: null,
            'actualStart' => $actualStart ?: null,
            'actualEnd' => $actualEnd ?: null,
            'priority' => $priority ?: 'Средний',
            'status' => $status ?: 'К выполнению'
        ]);

        return $stmt->fetchColumn();
    }

    // Обновить задачу
    public function updateTask($id, $title, $description, $projectID, $executorID, $plannedStart, $plannedEnd, $actualStart, $actualEnd, $priority, $status) {
        // Валидация
        if ($priority !== null && !in_array($priority, self::ALLOWED_PRIORITIES)) {
            throw new InvalidArgumentException("Недопустимый приоритет: $priority");
        }
        if ($status !== null && !in_array($status, self::ALLOWED_STATUSES)) {
            throw new InvalidArgumentException("Недопустимый статус: $status");
        }

        // Проверки существования (если поля меняются)
        if ($projectID !== null) {
            $projExists = $this->pdo->prepare('SELECT 1 FROM "Project" WHERE ID = :id');
            $projExists->execute(['id' => $projectID]);
            if (!$projExists->fetch()) {
                throw new InvalidArgumentException("Проект с ID $projectID не найден");
            }
        }

        if ($executorID !== null) {
            if ($executorID !== '') { // явно задан
                $userExists = $this->pdo->prepare('SELECT 1 FROM "User" WHERE ID = :id');
                $userExists->execute(['id' => $executorID]);
                if (!$userExists->fetch()) {
                    throw new InvalidArgumentException("Исполнитель с ID $executorID не найден");
                }
            } else {
                $executorID = null; // пустая строка → null
            }
        }

        // Подготовка UPDATE
        $fields = [];
        $params = ['id' => $id];

        if ($title !== null) $fields[] = 'Title = :title'; $params['title'] = $title;
        if ($description !== null) $fields[] = 'Description = :description'; $params['description'] = $description;
        if ($projectID !== null) $fields[] = 'ProjectID = :projectID'; $params['projectID'] = $projectID;
        if ($executorID !== null) $fields[] = 'ExecutorID = :executorID'; $params['executorID'] = $executorID;
        if ($plannedStart !== null) $fields[] = 'PlannedStartDate = :plannedStart'; $params['plannedStart'] = $plannedStart ?: null;
        if ($plannedEnd !== null) $fields[] = 'PlannedEndDate = :plannedEnd'; $params['plannedEnd'] = $plannedEnd ?: null;
        if ($actualStart !== null) $fields[] = 'ActualStartDate = :actualStart'; $params['actualStart'] = $actualStart ?: null;
        if ($actualEnd !== null) $fields[] = 'ActualEndDate = :actualEnd'; $params['actualEnd'] = $actualEnd ?: null;
        if ($priority !== null) $fields[] = 'Priority = :priority'; $params['priority'] = $priority;
        if ($status !== null) $fields[] = 'Status = :status'; $params['status'] = $status;

        if (empty($fields)) {
            throw new InvalidArgumentException("Нет данных для обновления");
        }

        $sql = 'UPDATE "Task" SET ' . implode(', ', $fields) . ' WHERE ID = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // Удалить задачу
    public function deleteTask($id) {
        $stmt = $this->pdo->prepare('DELETE FROM "Task" WHERE ID = :id');
        return $stmt->execute(['id' => $id]);
    }

    // ✅ Дополнительно: получить задачи по проекту
    public function getTasksByProject($projectID) {
        $sql = '
            SELECT 
                t.ID, t.Title, t.Description, t.Priority, t.Status,
                t.PlannedStartDate, t.PlannedEndDate, t.ActualEndDate,
                u.fullname AS executor_fullname
            FROM "Task" t
            LEFT JOIN "User" u ON u.ID = t.ExecutorID
            WHERE t.ProjectID = :projectID
            ORDER BY 
                CASE t.Status 
                    WHEN \'К выполнению\' THEN 1
                    WHEN \'В работе\' THEN 2
                    WHEN \'На проверке\' THEN 3
                    WHEN \'Выполнена\' THEN 4
                    ELSE 5
                END,
                t.Priority DESC
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['projectID' => $projectID]);
        return $stmt->fetchAll();
    }

    // ✅ Дополнительно: получить задачи по исполнителю
    public function getTasksByExecutor($executorID) {
        $sql = '
            SELECT 
                t.ID, t.Title, t.Status, t.Priority,
                p.Title AS project_title,
                t.PlannedEndDate
            FROM "Task" t
            LEFT JOIN "Project" p ON p.ID = t.ProjectID
            WHERE t.ExecutorID = :executorID
            ORDER BY t.PlannedEndDate ASC NULLS LAST
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['executorID' => $executorID]);
        return $stmt->fetchAll();
    }
}