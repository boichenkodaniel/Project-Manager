<?php
// controllers/ProjectController.php

class ProjectController {
    private $model;

    public function __construct() {
        $this->model = new ProjectModel();
    }

    private function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // GET ?action=projects.index
    public function index() {
        try {
            $projects = $this->model->getAllProjects();
            $this->json(['success' => true, 'data' => $projects]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка получения проектов: ' . $e->getMessage()], 500);
        }
    }

    // GET ?action=projects.get&id=1
    public function get($id) {
        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID проекта'], 400);
        }

        try {
            $project = $this->model->getProjectById($id);
            if ($project) {
                $this->json(['success' => true, 'data' => $project]);
            } else {
                $this->json(['success' => false, 'error' => 'Проект не найден'], 404);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // POST ?action=projects.create
    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->json(['success' => false, 'error' => 'Тело запроса должно быть в формате JSON'], 400);
        }

        $required = ['Title', 'Status', 'ManagerID'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || ($field !== 'DetailedDescription' && trim($input[$field]) === '')) {
                $this->json(['success' => false, 'error' => "Поле '$field' обязательно"], 400);
            }
        }

        try {
            $id = $this->model->createProject(
                $input['Title'],
                $input['DetailedDescription'] ?? null,
                $input['StartDate'] ?? null,
                $input['PlannedEndDate'] ?? null,
                $input['ActualEndDate'] ?? null,
                $input['Status'],
                $input['ManagerID']
            );
            $this->json(['success' => true, 'message' => 'Проект создан', 'id' => $id], 201);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка создания проекта: ' . $e->getMessage()], 500);
        }
    }

    // POST ?action=projects.update&id=1
    public function update($id) {
        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID проекта'], 400);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->json(['success' => false, 'error' => 'Тело запроса должно быть в формате JSON'], 400);
        }

        try {
            $updated = $this->model->updateProject(
                $id,
                $input['Title'] ?? null,
                $input['DetailedDescription'] ?? null,
                $input['StartDate'] ?? null,
                $input['PlannedEndDate'] ?? null,
                $input['ActualEndDate'] ?? null,
                $input['Status'] ?? null,
                $input['ManagerID'] ?? null
            );

            if ($updated) {
                $this->json(['success' => true, 'message' => 'Проект обновлён']);
            } else {
                $this->json(['success' => false, 'error' => 'Проект не найден или изменения не внесены'], 400);
            }
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка обновления: ' . $e->getMessage()], 500);
        }
    }

    // GET ?action=projects.delete&id=1
    public function delete($id) {
        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID проекта'], 400);
        }

        try {
            $deleted = $this->model->deleteProject($id);
            if ($deleted) {
                $this->json(['success' => true, 'message' => 'Проект удалён']);
            } else {
                $this->json(['success' => false, 'error' => 'Проект не найден'], 404);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка удаления: ' . $e->getMessage()], 500);
        }
    }
}