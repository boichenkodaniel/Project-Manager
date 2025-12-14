<?php
// models/TaskResourceModel.php

class TaskResourceModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // Назначить ресурс на задачу
    public function assignResourceToTask($taskID, $resourceID, $notes = null) {
        // Проверка существования задачи и ресурса
        if (!$this->taskExists($taskID)) {
            throw new InvalidArgumentException("Задача с ID $taskID не найдена");
        }
        if (!$this->resourceExists($resourceID)) {
            throw new InvalidArgumentException("Ресурс с ID $resourceID не найден");
        }

        // Проверка на дубль
        $exists = $this->pdo->prepare('
            SELECT 1 FROM "TaskResource" 
            WHERE TaskID = :taskID AND ResourceID = :resourceID
        ');
        $exists->execute(['taskID' => $taskID, 'resourceID' => $resourceID]);
        if ($exists->fetch()) {
            throw new InvalidArgumentException("Ресурс уже назначен на эту задачу");
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO "TaskResource" (TaskID, ResourceID, Notes)
            VALUES (:taskID, :resourceID, :notes)
        ');

        $stmt->execute([
            'taskID' => $taskID,
            'resourceID' => $resourceID,
            'notes' => $notes
        ]);

        return true;
    }

    // Удалить связь
    public function removeResourceFromTask($taskID, $resourceID) {
        $stmt = $this->pdo->prepare('
            DELETE FROM "TaskResource"
            WHERE TaskID = :taskID AND ResourceID = :resourceID
        ');
        $stmt->execute(['taskID' => $taskID, 'resourceID' => $resourceID]);
        return $stmt->rowCount() > 0;
    }

    // Получить все ресурсы задачи
    public function getResourcesByTask($taskID) {
        $sql = '
            SELECT 
                tr.TaskID,
                tr.ResourceID,
                tr.AssignmentDate,
                tr.Notes,
                r.Name AS resource_name,
                r.Type AS resource_type,
                r.Status AS resource_status
            FROM "TaskResource" tr
            JOIN "Resource" r ON r.ID = tr.ResourceID
            WHERE tr.TaskID = :taskID
            ORDER BY tr.AssignmentDate DESC
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['taskID' => $taskID]);
        return $stmt->fetchAll();
    }

    // Получить все задачи ресурса
    public function getTasksByResource($resourceID) {
        $sql = '
            SELECT 
                tr.TaskID,
                tr.ResourceID,
                tr.AssignmentDate,
                tr.Notes,
                t.Title AS task_title,
                t.Status AS task_status,
                p.Title AS project_title
            FROM "TaskResource" tr
            JOIN "Task" t ON t.ID = tr.TaskID
            LEFT JOIN "Project" p ON p.ID = t.ProjectID
            WHERE tr.ResourceID = :resourceID
            ORDER BY tr.AssignmentDate DESC
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['resourceID' => $resourceID]);
        return $stmt->fetchAll();
    }

    // Получить полный список связей (для админки)
    public function getAllAssignments() {
        $sql = '
            SELECT 
                tr.TaskID,
                tr.ResourceID,
                tr.AssignmentDate,
                tr.Notes,
                t.Title AS task_title,
                r.Name AS resource_name
            FROM "TaskResource" tr
            JOIN "Task" t ON t.ID = tr.TaskID
            JOIN "Resource" r ON r.ID = tr.ResourceID
            ORDER BY tr.AssignmentDate DESC
        ';
        return $this->pdo->query($sql)->fetchAll();
    }

    // Вспомогательные методы
    private function taskExists($id) {
        $stmt = $this->pdo->prepare('SELECT 1 FROM "Task" WHERE ID = :id');
        $stmt->execute(['id' => $id]);
        return (bool)$stmt->fetch();
    }

    private function resourceExists($id) {
        $stmt = $this->pdo->prepare('SELECT 1 FROM "Resource" WHERE ID = :id');
        $stmt->execute(['id' => $id]);
        return (bool)$stmt->fetch();
    }
}