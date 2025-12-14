<?php
// controllers/TaskResourceController.php

class TaskResourceController {
    private $model;

    public function __construct() {
        $this->model = new TaskResourceModel();
    }

    private function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // POST ?action=taskresources.assign
    public function assign() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->json(['success' => false, 'error' => 'Тело запроса должно быть в формате JSON'], 400);
        }

        $required = ['TaskID', 'ResourceID'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || !is_numeric($input[$field])) {
                $this->json(['success' => false, 'error' => "Поле '$field' должно быть числом"], 400);
            }
        }

        try {
            $this->model->assignResourceToTask(
                $input['TaskID'],
                $input['ResourceID'],
                $input['Notes'] ?? null
            );
            $this->json(['success' => true, 'message' => 'Ресурс успешно назначен на задачу']);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка назначения: ' . $e->getMessage()], 500);
        }
    }

    // GET ?action=taskresources.remove&taskID=1&resourceID=5
    public function remove() {
        $taskID = $_GET['taskID'] ?? null;
        $resourceID = $_GET['resourceID'] ?? null;

        if (!$taskID || !is_numeric($taskID) || !$resourceID || !is_numeric($resourceID)) {
            $this->json(['success' => false, 'error' => 'Параметры taskID и resourceID обязательны и должны быть числами'], 400);
        }

        try {
            $removed = $this->model->removeResourceFromTask($taskID, $resourceID);
            if ($removed) {
                $this->json(['success' => true, 'message' => 'Связь удалена']);
            } else {
                $this->json(['success' => false, 'error' => 'Связь не найдена'], 404);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка удаления: ' . $e->getMessage()], 500);
        }
    }

    // GET ?action=taskresources.byTask&id=3
    public function byTask($taskID) {
        if (!$taskID || !is_numeric($taskID)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID задачи'], 400);
        }

        try {
            $resources = $this->model->getResourcesByTask($taskID);
            $this->json(['success' => true, 'data' => $resources]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // GET ?action=taskresources.byResource&id=2
    public function byResource($resourceID) {
        if (!$resourceID || !is_numeric($resourceID)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID ресурса'], 400);
        }

        try {
            $tasks = $this->model->getTasksByResource($resourceID);
            $this->json(['success' => true, 'data' => $tasks]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // GET ?action=taskresources.all
    public function all() {
        try {
            $assignments = $this->model->getAllAssignments();
            $this->json(['success' => true, 'data' => $assignments]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка получения списка связей: ' . $e->getMessage()], 500);
        }
    }
}