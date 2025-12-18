<?php
// models/ProjectModel.php

class ProjectModel {
    private $pdo;

    // Допустимые статусы
    private const ALLOWED_STATUSES = ['Черновик', 'Активен', 'В работе', 'Завершен', 'Отменён'];

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // Получить все проекты
    public function getAllProjects() {
        $sql = '
            SELECT 
                p.id,
                p.title,
                p.detaileddescription,
                p.startdate,
                p.plannedenddate,
                p.status,
                p.clientid,
                p.managerid,
                u.fullname as client_fullname,
                u.email as client_email
            FROM "project" p
            LEFT JOIN "User" u ON p.clientid = u.id
            ORDER BY p.startdate DESC
        ';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    // Получить проект по ID
    public function getProjectById($id) {
        $sql = '
            SELECT 
                p.id,
                p.title,
                p.detaileddescription,
                p.startdate,
                p.plannedenddate,
                p.status,
                p.clientid,
                p.managerid,
                u.fullname as client_fullname,
                u.email as client_email,
                m.fullname as manager_fullname,
                m.email as manager_email
            FROM "project" p
            LEFT JOIN "User" u ON p.clientid = u.id
            LEFT JOIN "User" m ON p.managerid = m.id
            WHERE p.id = :id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    // Создать проект
    public function createProject($title, $description, $startDate, $plannedEndDate, $status, $clientId, $managerId) {
        if (!in_array($status, self::ALLOWED_STATUSES)) {
            throw new InvalidArgumentException("Недопустимый статус: $status");
        }

        // Проверка существования клиента
        $clientExists = $this->pdo->prepare('SELECT 1 FROM "User" WHERE ID = :id');
        $clientExists->execute(['id' => $clientId]);
        if (!$clientExists->fetch()) {
            throw new InvalidArgumentException("Клиент с ID $clientId не найден");
        }

        // Проверка существования менеджера
        $managerExists = $this->pdo->prepare('SELECT 1 FROM "User" WHERE ID = :id');
        $managerExists->execute(['id' => $managerId]);
        if (!$managerExists->fetch()) {
            throw new InvalidArgumentException("Менеджер с ID $managerId не найден");
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO "project" (
                title, detaileddescription, startdate, plannedenddate, status, clientid, managerid
            ) VALUES (
                :title, :description, :startDate, :plannedEndDate, :status, :clientId, :managerId
            )
            RETURNING id
        ');

        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'startDate' => $startDate ?: null,
            'plannedEndDate' => $plannedEndDate ?: null,
            'status' => $status,
            'clientId' => $clientId,
            'managerId' => $managerId
        ]);

        return $stmt->fetchColumn();
    }

    // Обновить проект
    public function updateProject($id, $title = null, $description = null, $startDate = null, $plannedEndDate = null, $status = null, $clientId = null, $managerId = null) {
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

        if ($managerId) {
            $managerExists = $this->pdo->prepare('SELECT 1 FROM "User" WHERE ID = :id');
            $managerExists->execute(['id' => $managerId]);
            if (!$managerExists->fetch()) {
                throw new InvalidArgumentException("Менеджер с ID $managerId не найден");
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
        if ($managerId !== null) { $fields[] = 'managerid = :managerId'; $params['managerId'] = $managerId; }

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

    // Обновить статус проекта на основе статусов задач
    public function updateProjectStatusBasedOnTasks($projectId) {
        // Получаем все задачи проекта
        $stmt = $this->pdo->prepare('
            SELECT Status 
            FROM "task" 
            WHERE ProjectID = :projectId
        ');
        $stmt->execute(['projectId' => $projectId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Получаем текущий статус проекта
        $project = $this->getProjectById($projectId);
        $currentStatus = $project['status'] ?? 'Черновик';
        
        $newStatus = null;
        
        // Если задач нет — проект должен быть в статусе "Черновик"
        if (empty($tasks)) {
            if ($currentStatus !== 'Черновик') {
                $newStatus = 'Черновик';
            }
        } else {
            // Проверяем, все ли задачи выполнены
            $allTasksCompleted = true;
            foreach ($tasks as $taskStatus) {
                if ($taskStatus !== 'Выполнена') {
                    $allTasksCompleted = false;
                    break;
                }
            }

            // Если все задачи выполнены - "Завершен", иначе - "Активен"
            if ($allTasksCompleted && $currentStatus !== 'Завершен' && $currentStatus !== 'Отменён') {
                $newStatus = 'Завершен';
            } elseif (!$allTasksCompleted && $currentStatus !== 'Отменён') {
                $newStatus = 'Активен';
            }
        }
        
        // Обновляем статус проекта, если нужно
        if ($newStatus !== null) {
            return $this->updateProject($projectId, null, null, null, null, $newStatus, null, null);
        }
        
        return false;
    }
}
