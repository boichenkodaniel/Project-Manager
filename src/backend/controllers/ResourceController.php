<?php
// controllers/ResourceController.php

class ResourceController {
    private $model;

    public function __construct() {
        $this->model = new ResourceModel();
    }

    private function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function index() {
        try {
            $resources = $this->model->getAllResources();
            $this->json(['success' => true, 'data' => $resources]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка получения ресурсов: ' . $e->getMessage()], 500);
        }
    }

    public function get($id) {
        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID ресурса'], 400);
        }

        try {
            $resource = $this->model->getResourceById($id);
            if ($resource) {
                $this->json(['success' => true, 'data' => $resource]);
            } else {
                $this->json(['success' => false, 'error' => 'Ресурс не найден'], 404);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->json(['success' => false, 'error' => 'Тело запроса должно быть в формате JSON'], 400);
        }

        $required = ['Name', 'Type'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || trim((string)$input[$field]) === '') {
                $this->json(['success' => false, 'error' => "Поле '$field' обязательно"], 400);
            }
        }

        try {
            $id = $this->model->createResource(
                $input['Name'],
                $input['Type'],
                $input['Description'] ?? null,
                $input['Status'] ?? null
            );
            $this->json(['success' => true, 'message' => 'Ресурс создан', 'id' => $id], 201);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка создания ресурса: ' . $e->getMessage()], 500);
        }
    }

    public function update($id) {
        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID ресурса'], 400);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->json(['success' => false, 'error' => 'Тело запроса должно быть в формате JSON'], 400);
        }

        try {
            $updated = $this->model->updateResource(
                $id,
                $input['Name'] ?? null,
                $input['Type'] ?? null,
                $input['Description'] ?? null,
                $input['Status'] ?? null
            );

            if ($updated) {
                $this->json(['success' => true, 'message' => 'Ресурс обновлён']);
            } else {
                $this->json(['success' => false, 'error' => 'Ресурс не найден или изменения не внесены'], 400);
            }
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка обновления: ' . $e->getMessage()], 500);
        }
    }

    public function delete($id) {
        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID ресурса'], 400);
        }

        try {
            $deleted = $this->model->deleteResource($id);
            if ($deleted) {
                $this->json(['success' => true, 'message' => 'Ресурс удалён']);
            } else {
                $this->json(['success' => false, 'error' => 'Ресурс не найден'], 404);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка удаления: ' . $e->getMessage()], 500);
        }
    }

    // ✅ Фильтрация
    public function filter() {
        $name = $_GET['name'] ?? null;
        $type = $_GET['type'] ?? null;
        $status = $_GET['status'] ?? null;

        try {
            $resources = $this->model->filterResources($name, $type, $status);
            $this->json(['success' => true, 'data' => $resources]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Сводка по статусам
    public function summary() {
        try {
            $summary = $this->model->getStatusSummary();
            $this->json(['success' => true, 'data' => $summary]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка получения сводки: ' . $e->getMessage()], 500);
        }
    }
}