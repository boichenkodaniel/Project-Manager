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
        AuthMiddleware::requireAuth();
        
        try {
            $projects = $this->model->getAllProjects();
            
            // Фильтрация по ролям
            if (isset($_SESSION['user_role'])) {
                if ($_SESSION['user_role'] === 'Клиент') {
                    // Клиенты видят только свои проекты
                    $projects = array_filter($projects, function($p) {
                        return $p['clientid'] == $_SESSION['user_id'];
                    });
                } elseif ($_SESSION['user_role'] === 'Исполнитель') {
                    // Исполнители видят проекты, в которых участвуют через задачи
                    $pdo = Database::getInstance();
                    $stmt = $pdo->prepare('SELECT DISTINCT projectid FROM "task" WHERE taskto = :user_id OR taskby = :user_id');
                    $stmt->execute(['user_id' => $_SESSION['user_id']]);
                    $projectIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $projects = array_filter($projects, function($p) use ($projectIds) {
                        return in_array($p['id'], $projectIds);
                    });
                } elseif ($_SESSION['user_role'] === 'Руководитель') {
                    // Руководитель видит только проекты, которые он создал
                    $projects = array_filter($projects, function($p) {
                        return isset($p['managerid']) && $p['managerid'] == $_SESSION['user_id'];
                    });
                }
                // Администратор и Руководитель проектов видят все проекты
            }
            
            $this->json(['success' => true, 'data' => array_values($projects)]);
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
        // Только руководитель проектов, руководитель и админ могут создавать проекты
        AuthMiddleware::requireRole(['Руководитель проектов', 'Руководитель', 'Администратор']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->json(['success' => false, 'error' => 'Тело запроса должно быть в формате JSON'], 400);
        }

        $required = ['title', 'status', 'clientid'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || trim($input[$field]) === '') {
                $this->json(['success' => false, 'error' => "Поле '$field' обязательно"], 400);
            }
        }

        try {
            $id = $this->model->createProject(
                $input['title'],
                $input['detaileddescription'] ?? null,
                $input['startdate'] ?? null,
                $input['plannedenddate'] ?? null,
                $input['status'],
                $input['clientid'],
                $_SESSION['user_id'] // managerid = текущий пользователь
            );
            
            // Отправляем уведомление клиенту проекта
            $notificationModel = new NotificationModel();
            $notificationModel->createNotification(
                $input['clientid'],
                'Новый проект создан',
                "Создан проект: {$input['title']}",
                'info',
                'project',
                $id
            );

            $project = $this->model->getProjectById($id);
            $this->json(['success' => true, 'message' => 'Проект создан', 'data' => $project], 201);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка создания проекта: ' . $e->getMessage()], 500);
        }
    }

    // POST ?action=projects.update&id=1
    public function update($id) {
        // Только руководитель проектов, руководитель и админ могут обновлять проекты
        AuthMiddleware::requireRole(['Руководитель проектов', 'Руководитель', 'Администратор']);
        
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
                $input['title'] ?? null,
                $input['detaileddescription'] ?? null,
                $input['startdate'] ?? null,
                $input['plannedenddate'] ?? null,
                $input['status'] ?? null,
                $input['clientid'] ?? null,
                $input['managerid'] ?? null
            );

            if ($updated) {
                $project = $this->model->getProjectById($id);
                $this->json(['success' => true, 'message' => 'Проект обновлён', 'data' => $project]);
            } else {
                $this->json(['success' => false, 'error' => 'Проект не найден или изменений нет'], 400);
            }
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка обновления: ' . $e->getMessage()], 500);
        }
    }

    // GET ?action=projects.delete&id=1
    public function delete($id) {
        // Только руководитель проектов, руководитель и админ могут удалять проекты
        AuthMiddleware::requireRole(['Руководитель проектов', 'Руководитель', 'Администратор']);
        
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
