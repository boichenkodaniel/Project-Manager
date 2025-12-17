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
        AuthMiddleware::requireAuth();
        
        try {
            $tasks = $this->model->getAllTasks();
            
            // Фильтрация по ролям
            if (isset($_SESSION['user_role'])) {
                $userRole = $_SESSION['user_role'];
                $userId = $_SESSION['user_id'];
                
                if ($userRole === 'Клиент') {
                    // Клиенты видят задачи только из своих проектов
                    $pdo = Database::getInstance();
                    $stmt = $pdo->prepare('SELECT id FROM "project" WHERE clientid = :user_id');
                    $stmt->execute(['user_id' => $userId]);
                    $projectIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $tasks = array_filter($tasks, function($task) use ($projectIds) {
                        return in_array($task['ProjectID'], $projectIds);
                    });
                } elseif ($userRole === 'Исполнитель') {
                    // Исполнители видят только свои задачи
                    $tasks = array_filter($tasks, function($task) use ($userId) {
                        return $task['TaskTo'] == $userId || $task['TaskBy'] == $userId;
                    });
                }
                // Администратор и Руководитель проектов видят все задачи
            }
            
            foreach ($tasks as &$task) {
                $task['TaskByID'] = $task['TaskBy'];
                $task['TaskToID'] = $task['TaskTo'];
            }
            
            $this->json(['success' => true, 'data' => array_values($tasks)]);
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
        // Только руководитель проектов и админ могут создавать задачи
        AuthMiddleware::requireRole(['Руководитель проектов', 'Администратор']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $this->json(['success' => false, 'error' => 'Тело запроса должно быть JSON'], 400);

        foreach (['Title', 'ProjectID'] as $field) {
            if (empty($input[$field])) {
                $this->json(['success' => false, 'error' => "Поле '$field' обязательно"], 400);
            }
        }

        // Используем текущего пользователя как создателя задачи
        $taskBy = $_SESSION['user_id'];

        try {
            $id = $this->model->createTask(
                $input['Title'],
                $input['Description'] ?? null,
                $input['ProjectID'],
                $input['ExecutorID'] ?? null,
                $taskBy,
                $input['StartDate'] ?? null,
                $input['EndDate'] ?? null,
                $input['Status'] ?? 'В работе'
            );
            
            // Отправляем уведомления участникам проекта
            $notificationModel = new NotificationModel();
            $notificationModel->notifyTaskParticipants(
                $id,
                'Новая задача создана',
                "Создана задача: {$input['Title']}",
                'info',
                $_SESSION['user_id']
            );
            
            // Если назначен исполнитель, отправляем ему отдельное уведомление
            if (!empty($input['ExecutorID'])) {
                $notificationModel->createNotification(
                    $input['ExecutorID'],
                    'Вам назначена задача',
                    "Вам назначена задача: {$input['Title']}",
                    'warning',
                    'task',
                    $id
                );
            }
            
            $this->json(['success' => true, 'data' => ['id' => $id] + $input], 201);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function update($id) {
        AuthMiddleware::requireAuth();
        
        if (!$id || !is_numeric($id)) $this->json(['success' => false, 'error' => 'Некорректный ID задачи'], 400);

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $this->json(['success' => false, 'error' => 'Тело запроса должно быть JSON'], 400);

        // Проверка прав: исполнитель может обновлять только свои задачи, руководитель и админ - любые
        $task = $this->model->getTaskById($id);
        if (!$task) {
            $this->json(['success' => false, 'error' => 'Задача не найдена'], 404);
        }

        $userRole = $_SESSION['user_role'] ?? '';
        $isExecutor = $task['TaskTo'] == $_SESSION['user_id'];
        $isCreator = $task['TaskBy'] == $_SESSION['user_id'];
        
        if ($userRole !== 'Администратор' && $userRole !== 'Руководитель проектов') {
            if (!$isExecutor && !$isCreator) {
                $this->json(['success' => false, 'error' => 'Доступ запрещён'], 403);
            }
        }

        try {
            $oldStatus = $task['Status'];
            $updated = $this->model->updateTask($id, $input);
            
            if ($updated) {
                // Отправляем уведомления при изменении статуса
                if (isset($input['Status']) && $input['Status'] !== $oldStatus) {
                    $notificationModel = new NotificationModel();
                    $statusMessages = [
                        'В работе' => 'Задача взята в работу',
                        'На проверке' => 'Задача отправлена на проверку',
                        'Выполнена' => 'Задача выполнена'
                    ];
                    $message = $statusMessages[$input['Status']] ?? "Статус задачи изменён на: {$input['Status']}";
                    
                    $notificationModel->notifyTaskParticipants(
                        $id,
                        'Изменение статуса задачи',
                        $message,
                        'info',
                        $_SESSION['user_id']
                    );
                }
                
                // Уведомление при назначении нового исполнителя
                if (isset($input['TaskTo']) && $input['TaskTo'] != $task['TaskTo']) {
                    $notificationModel = new NotificationModel();
                    $notificationModel->createNotification(
                        $input['TaskTo'],
                        'Вам назначена задача',
                        "Вам назначена задача: {$task['Title']}",
                        'warning',
                        'task',
                        $id
                    );
                }
                
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
