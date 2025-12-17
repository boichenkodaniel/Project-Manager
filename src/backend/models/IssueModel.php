<?php
// models/IssueModel.php

class IssueModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // Получить все issues
    public function getAllIssues() {
        $sql = '
            SELECT 
                i.id,
                i.title,
                i.description,
                i.status,
                i.created_at,
                i.project_id,
                i.created_by,
                i.assigned_to,
                p.title AS project_title,
                u_creator.fullname AS creator_fullname,
                u_assigned.fullname AS assigned_fullname
            FROM "issue" i
            LEFT JOIN "project" p ON p.id = i.project_id
            LEFT JOIN "User" u_creator ON u_creator.id = i.created_by
            LEFT JOIN "User" u_assigned ON u_assigned.id = i.assigned_to
            ORDER BY i.created_at DESC
        ';
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получить issue по ID
    public function getIssueById($id) {
        $sql = '
            SELECT 
                i.id,
                i.title,
                i.description,
                i.status,
                i.created_at,
                i.project_id,
                i.created_by,
                i.assigned_to,
                p.title AS project_title,
                u_creator.fullname AS creator_fullname,
                u_assigned.fullname AS assigned_fullname
            FROM "issue" i
            LEFT JOIN "project" p ON p.id = i.project_id
            LEFT JOIN "User" u_creator ON u_creator.id = i.created_by
            LEFT JOIN "User" u_assigned ON u_assigned.id = i.assigned_to
            WHERE i.id = :id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Создать issue
    public function createIssue($title, $description, $projectId, $createdBy, $assignedTo = null, $status = 'Открыта') {
        $stmt = $this->pdo->prepare('
            INSERT INTO "issue" (title, description, project_id, created_by, assigned_to, status)
            VALUES (:title, :description, :project_id, :created_by, :assigned_to, :status)
            RETURNING id
        ');
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'project_id' => $projectId,
            'created_by' => $createdBy,
            'assigned_to' => $assignedTo,
            'status' => $status
        ]);
        return $stmt->fetchColumn();
    }

    // Обновить issue
    public function updateIssue($id, $data) {
        $fields = [];
        $params = ['id' => $id];

        foreach (['title', 'description', 'status', 'assigned_to'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            throw new InvalidArgumentException("Нет данных для обновления");
        }

        $sql = 'UPDATE "issue" SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // Удалить issue
    public function deleteIssue($id) {
        $stmt = $this->pdo->prepare('DELETE FROM "issue" WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    // Получить issues по проекту
    public function getIssuesByProject($projectId) {
        $stmt = $this->pdo->prepare('
            SELECT i.*, u_creator.fullname AS creator_fullname, u_assigned.fullname AS assigned_fullname
            FROM "issue" i
            LEFT JOIN "User" u_creator ON u_creator.id = i.created_by
            LEFT JOIN "User" u_assigned ON u_assigned.id = i.assigned_to
            WHERE i.project_id = :project_id
            ORDER BY i.created_at DESC
        ');
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получить issues по создателю
    public function getIssuesByCreator($userId) {
        $stmt = $this->pdo->prepare('
            SELECT i.*, p.title AS project_title, u_assigned.fullname AS assigned_fullname
            FROM "issue" i
            LEFT JOIN "project" p ON p.id = i.project_id
            LEFT JOIN "User" u_assigned ON u_assigned.id = i.assigned_to
            WHERE i.created_by = :user_id
            ORDER BY i.created_at DESC
        ');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

