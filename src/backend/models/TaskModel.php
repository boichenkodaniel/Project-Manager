<?php
// models/TaskModel.php

class TaskModel {
    private $pdo;

    private const ALLOWED_STATUSES = ['К выполнению', 'В работе', 'На проверке', 'Выполнена'];

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // Получить все задачи с проектом и исполнителем
public function getAllTasks() {
    $sql = '
        SELECT 
            t.ID,
            t.Title,
            t.Description,
            t.ProjectID,
            t.TaskTo,
            t.TaskBy,
            t.Status,
            t.StartDate,
            t.EndDate,
            p.Title AS project_title,
            u_executor.fullname AS executor_fullname,
            u_creator.fullname AS creator_fullname
        FROM "task" t
        LEFT JOIN "project" p ON p.ID = t.ProjectID
        LEFT JOIN "User" u_executor ON u_executor.ID = t.TaskTo
        LEFT JOIN "User" u_creator ON u_creator.ID = t.TaskBy
        ORDER BY t.ID DESC
    ';

    $stmt = $this->pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    // Получить задачу по ID
    public function getTaskById($id) {
        $stmt = $this->pdo->prepare('
            SELECT 
                t.ID,
                t.Title,
                t.Description,
                t.ProjectID,
                t.TaskTo,
                t.TaskBy,
                t.Status,
                t.StartDate,
                t.EndDate,
                p.Title AS project_title,
                u.fullname AS executor_fullname
            FROM "task" t
            LEFT JOIN "project" p ON p.ID = t.ProjectID
            LEFT JOIN "User" u ON u.ID = t.TaskTo
            WHERE t.ID = :id
        ');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Создать задачу
    public function createTask($title, $description, $projectID, $TaskTo, $taskBy, $startDate = null, $endDate = null, $status = 'К выполнению') {
        if (!in_array($status, self::ALLOWED_STATUSES)) {
            throw new InvalidArgumentException("Недопустимый статус: $status");
        }

        // Проверка существования проекта
        $stmt = $this->pdo->prepare('SELECT 1 FROM "project" WHERE ID = :id');
        $stmt->execute(['id' => $projectID]);
        if (!$stmt->fetch()) throw new InvalidArgumentException("Проект с ID $projectID не найден");

        // Проверка исполнителя (может быть NULL)
        if ($TaskTo !== null) {
            $stmt = $this->pdo->prepare('SELECT 1 FROM "User" WHERE ID = :id');
            $stmt->execute(['id' => $TaskTo]);
            if (!$stmt->fetch()) throw new InvalidArgumentException("Исполнитель с ID $TaskTo не найден");
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO "task" (Title, Description, ProjectID, TaskTo, TaskBy, StartDate, EndDate, Status)
            VALUES (:title, :description, :projectID, :TaskTo, :taskBy, :startDate, :endDate, :status)
            RETURNING ID
        ');

        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'projectID' => $projectID,
            'TaskTo' => $TaskTo,
            'taskBy' => $taskBy,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => $status
        ]);

        return $stmt->fetchColumn();
    }

    // Обновить задачу
    public function updateTask($id, $data) {
        $fields = [];
        $params = ['id' => $id];

        foreach (['Title', 'Description', 'ProjectID', 'TaskTo', 'TaskBy', 'StartDate', 'EndDate', 'Status'] as $field) {
            if (isset($data[$field])) {
                if ($field === 'Status' && !in_array($data[$field], self::ALLOWED_STATUSES)) {
                    throw new InvalidArgumentException("Недопустимый статус: {$data[$field]}");
                }
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            throw new InvalidArgumentException("Нет данных для обновления");
        }

        $sql = 'UPDATE "task" SET ' . implode(', ', $fields) . ' WHERE ID = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // Удалить задачу
    public function deleteTask($id) {
        $stmt = $this->pdo->prepare('DELETE FROM "task" WHERE ID = :id');
        return $stmt->execute(['id' => $id]);
    }

    // Получить задачи по проекту
    public function getTasksByProject($projectID) {
        $stmt = $this->pdo->prepare('
            SELECT t.ID, t.Title, t.Description, t.Status, t.StartDate, t.EndDate, u.fullname AS executor_fullname
            FROM "task" t
            LEFT JOIN "User" u ON u.ID = t.TaskTo
            WHERE t.ProjectID = :projectID
            ORDER BY t.Status, t.EndDate ASC
        ');
        $stmt->execute(['projectID' => $projectID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получить задачи по исполнителю
    public function getTasksByExecutor($TaskTo) {
        $stmt = $this->pdo->prepare('
            SELECT t.ID, t.Title, t.Description, t.Status, t.StartDate, t.EndDate, p.Title AS project_title
            FROM "task" t
            LEFT JOIN "Project" p ON p.ID = t.ProjectID
            WHERE t.TaskTo = :TaskTo
            ORDER BY t.EndDate ASC
        ');
        $stmt->execute(['TaskTo' => $TaskTo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
