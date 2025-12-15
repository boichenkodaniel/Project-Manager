<?php
// controllers/TaskController.php

class TaskController {
    private $model;

    public function __construct() {
        $this->model = new TaskModel();
    }

    private function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function index() {
        try {
            $tasks = $this->model->getAllTasks();
            foreach ($tasks as &$task) {
                $task['TaskByID'] = $task['TaskBy'];
                $task['TaskToID'] = $task['TaskTo'];
}
            $this->json(['success' => true, 'data' => $tasks]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function get($id) {
        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID задачи'], 400);
        }
        $task = $this->model->getTaskById($id);
        if ($task) {
            $this->json(['success' => true, 'data' => $task]);
        } else {
            $this->json(['success' => false, 'error' => 'Задача не найдена'], 404);
        }
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $this->json(['success' => false, 'error' => 'Тело запроса должно быть JSON'], 400);

        foreach (['Title', 'ProjectID', 'TaskBy'] as $field) {
            if (empty($input[$field])) {
                $this->json(['success' => false, 'error' => "Поле '$field' обязательно"], 400);
            }
        }

        try {
            $id = $this->model->createTask(
                $input['Title'],
                $input['Description'] ?? null,
                $input['ProjectID'],
                $input['ExecutorID'] ?? null,
                $input['TaskBy'],
                $input['StartDate'] ?? null,
                $input['EndDate'] ?? null,
                $input['Status'] ?? 'К выполнению'
            );
            $this->json(['success' => true, 'data' => ['id' => $id] + $input], 201);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function update($id) {
        if (!$id || !is_numeric($id)) $this->json(['success' => false, 'error' => 'Некорректный ID задачи'], 400);

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $this->json(['success' => false, 'error' => 'Тело запроса должно быть JSON'], 400);

        try {
            $updated = $this->model->updateTask($id, $input);
            if ($updated) {
                $this->json(['success' => true, 'message' => 'Задача обновлена']);
            } else {
                $this->json(['success' => false, 'error' => 'Задача не найдена или изменения не внесены'], 404);
            }
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function delete($id) {
        if (!$id || !is_numeric($id)) $this->json(['success' => false, 'error' => 'Некорректный ID задачи'], 400);

        try {
            $deleted = $this->model->deleteTask($id);
            if ($deleted) $this->json(['success' => true, 'message' => 'Задача удалена']);
            else $this->json(['success' => false, 'error' => 'Задача не найдена'], 404);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function byProject($projectID) {
        if (!$projectID || !is_numeric($projectID)) $this->json(['success' => false, 'error' => 'Некорректный ID проекта'], 400);
        $tasks = $this->model->getTasksByProject($projectID);
        $this->json(['success' => true, 'data' => $tasks]);
    }

    public function byExecutor($executorID) {
        if ($executorID === null || $executorID === '') $this->json(['success' => false, 'error' => 'ID исполнителя обязателен'], 400);
        $tasks = $this->model->getTasksByExecutor($executorID);
        $this->json(['success' => true, 'data' => $tasks]);
    }
}
