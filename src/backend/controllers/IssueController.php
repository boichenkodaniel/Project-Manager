<?php
// controllers/IssueController.php

class IssueController {
    private $model;

    public function __construct() {
        $this->model = new IssueModel();
    }

    private function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function index() {
        AuthMiddleware::requireAuth();
        
        try {
            $issues = $this->model->getAllIssues();
            
            // Фильтрация по ролям
            if (isset($_SESSION['user_role'])) {
                $userRole = $_SESSION['user_role'];
                $userId = $_SESSION['user_id'];
                
                if ($userRole === 'Клиент') {
                    // Клиенты видят issues только из своих проектов
                    $pdo = Database::getInstance();
                    $stmt = $pdo->prepare('SELECT id FROM "project" WHERE clientid = :user_id');
                    $stmt->execute(['user_id' => $userId]);
                    $projectIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $issues = array_filter($issues, function($issue) use ($projectIds) {
                        return in_array($issue['project_id'], $projectIds);
                    });
                } elseif ($userRole === 'Исполнитель') {
                    // Исполнители видят issues из проектов, где они участвуют, или созданные ими
                    $pdo = Database::getInstance();
                    $stmt = $pdo->prepare('SELECT DISTINCT projectid FROM "task" WHERE taskto = :user_id OR taskby = :user_id');
                    $stmt->execute(['user_id' => $userId]);
                    $projectIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $issues = array_filter($issues, function($issue) use ($projectIds, $userId) {
                        return in_array($issue['project_id'], $projectIds) || $issue['created_by'] == $userId;
                    });
                }
                // Администратор и Руководитель проектов видят все issues
            }
            
            $this->json(['success' => true, 'data' => array_values($issues)]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function get($id) {
        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID'], 400);
        }

        try {
            $issue = $this->model->getIssueById($id);
            if ($issue) {
                $this->json(['success' => true, 'data' => $issue]);
            } else {
                $this->json(['success' => false, 'error' => 'Issue не найден'], 404);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function create() {
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Не авторизован'], 401);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->json(['success' => false, 'error' => 'Тело запроса должно быть JSON'], 400);
        }

        $required = ['title', 'project_id'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->json(['success' => false, 'error' => "Поле '$field' обязательно"], 400);
            }
        }

        try {
            $id = $this->model->createIssue(
                $input['title'],
                $input['description'] ?? null,
                $input['project_id'],
                $_SESSION['user_id'],
                $input['assigned_to'] ?? null,
                $input['status'] ?? 'Открыта'
            );

            // Отправляем уведомления участникам проекта
            $notificationModel = new NotificationModel();
            $notificationModel->notifyProjectParticipants(
                $input['project_id'],
                'Новая проблема в проекте',
                "Создана проблема: {$input['title']}",
                'warning',
                $_SESSION['user_id']
            );

            $issue = $this->model->getIssueById($id);
            $this->json(['success' => true, 'data' => $issue], 201);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function update($id) {
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Не авторизован'], 401);
        }

        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID'], 400);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->json(['success' => false, 'error' => 'Тело запроса должно быть JSON'], 400);
        }

        try {
            $updated = $this->model->updateIssue($id, $input);
            if ($updated) {
                $issue = $this->model->getIssueById($id);
                $this->json(['success' => true, 'data' => $issue]);
            } else {
                $this->json(['success' => false, 'error' => 'Issue не найден'], 404);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function delete($id) {
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Не авторизован'], 401);
        }

        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID'], 400);
        }

        try {
            $deleted = $this->model->deleteIssue($id);
            if ($deleted) {
                $this->json(['success' => true, 'message' => 'Issue удалён']);
            } else {
                $this->json(['success' => false, 'error' => 'Issue не найден'], 404);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function byProject($projectId) {
        if (!$projectId || !is_numeric($projectId)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID проекта'], 400);
        }

        try {
            $issues = $this->model->getIssuesByProject($projectId);
            $this->json(['success' => true, 'data' => $issues]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}

