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
                        $taskTo = $task['TaskTo'] ?? $task['taskto'] ?? null;
                        $taskBy = $task['TaskBy'] ?? $task['taskby'] ?? null;
                        return $taskTo == $userId || $taskBy == $userId;
                    });
                } elseif ($userRole === 'Руководитель') {
                    // Руководитель видит только задачи из своих проектов
                    $projectModel = new ProjectModel();
                    $tasks = array_filter($tasks, function($task) use ($projectModel, $userId) {
                        if (!isset($task['ProjectID'])) return false;
                        $project = $projectModel->getProjectById($task['ProjectID']);
                        return $project && isset($project['managerid']) && $project['managerid'] == $userId;
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
        // Только руководитель проектов, руководитель и админ могут создавать задачи
        AuthMiddleware::requireRole(['Руководитель проектов', 'Руководитель', 'Администратор']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $this->json(['success' => false, 'error' => 'Тело запроса должно быть JSON'], 400);

        foreach (['Title', 'ProjectID'] as $field) {
            if (empty($input[$field])) {
                $this->json(['success' => false, 'error' => "Поле '$field' обязательно"], 400);
            }
        }

        // Проверка прав: руководитель может создавать задачи только в своих проектах
        if ($_SESSION['user_role'] === 'Руководитель') {
            $projectModel = new ProjectModel();
            $project = $projectModel->getProjectById($input['ProjectID']);
            if (!$project || $project['managerid'] != $_SESSION['user_id']) {
                $this->json(['success' => false, 'error' => 'Вы можете создавать задачи только в своих проектах'], 403);
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
        
        // Если в запросе есть TaskTo и он отличается от текущего в БД, используем значение из запроса для проверки прав
        // Это нужно для случая, когда исполнитель только что взял задачу в работу и TaskTo был установлен
        if (isset($input['TaskTo']) && $task['TaskTo'] != $input['TaskTo']) {
            error_log("TaskTo will be updated from {$task['TaskTo']} to {$input['TaskTo']}, using input value for authorization check");
            // Временно обновляем task['TaskTo'] для проверки прав
            $task['TaskTo'] = $input['TaskTo'];
        }

        $userRole = $_SESSION['user_role'] ?? '';
        $userId = $_SESSION['user_id'] ?? null;
        // Проверяем TaskTo в разных регистрах (может быть TaskTo или taskto из БД)
        $taskTo = $task['TaskTo'] ?? $task['taskto'] ?? null;
        $taskBy = $task['TaskBy'] ?? $task['taskby'] ?? null;
        
        // Детальное логирование для отладки
        error_log("DEBUG: taskTo=" . var_export($taskTo, true) . " (type: " . gettype($taskTo) . "), userId=" . var_export($userId, true) . " (type: " . gettype($userId) . ")");
        
        // Безопасное сравнение ID (обрабатываем null и разные типы)
        // Приводим оба значения к целым числам для надежного сравнения
        $isExecutor = false;
        if ($taskTo !== null && $userId !== null) {
            // Приводим к целым числам для надежного сравнения
            $taskToInt = (int)$taskTo;
            $userIdInt = (int)$userId;
            $isExecutor = $taskToInt === $userIdInt;
            
            if (!$isExecutor) {
                // Пробуем другие способы сравнения
                $isExecutor = (string)$taskTo === (string)$userId || $taskTo == $userId;
            }
            
            error_log("DEBUG: isExecutor check - taskToInt={$taskToInt}, userIdInt={$userIdInt}, isExecutor=" . ($isExecutor ? 'true' : 'false'));
        }
        
        $isCreator = false;
        if ($taskBy !== null && $userId !== null) {
            $isCreator = (int)$taskBy == (int)$userId || 
                        (string)$taskBy === (string)$userId ||
                        $taskBy == $userId;
        }
        
        // Дополнительная проверка: если в запросе есть TaskTo и он совпадает с userId, то это тоже исполнитель
        // Это нужно для случая, когда задача была только что назначена в предыдущем запросе
        if (!$isExecutor && isset($input['TaskTo'])) {
            $inputTaskTo = $input['TaskTo'];
            if ($inputTaskTo == $userId || (int)$inputTaskTo == (int)$userId || (string)$inputTaskTo === (string)$userId) {
                $isExecutor = true;
                error_log("Executor detected from input TaskTo: {$inputTaskTo} == {$userId}");
            }
        }
        
        // Также проверяем, если задача была назначена в предыдущем запросе (TaskTo в БД совпадает с userId)
        // но по какой-то причине сравнение не сработало - используем более мягкое сравнение
        if (!$isExecutor && $taskTo !== null && $userId !== null) {
            // Пробуем еще раз с более мягким сравнением
            $taskToStr = trim((string)$taskTo);
            $userIdStr = trim((string)$userId);
            if ($taskToStr === $userIdStr || (int)$taskToStr === (int)$userIdStr) {
                $isExecutor = true;
                error_log("Executor detected with soft comparison: taskToStr='{$taskToStr}' == userIdStr='{$userIdStr}'");
            }
        }
        
        // Дополнительная проверка: если задача была только что обновлена и TaskTo был установлен
        // но мы еще не перезагрузили задачу из БД, используем значение из предыдущего обновления
        // Это критично для случая, когда исполнитель берет задачу в работу, а затем сразу отправляет на проверку
        if (!$isExecutor && $taskTo === null && isset($input['TaskTo']) && $input['TaskTo'] == $userId) {
            // Это означает, что TaskTo был установлен в предыдущем запросе, но мы еще не перезагрузили задачу
            // В этом случае разрешаем обновление, так как мы знаем, что TaskTo будет установлен
            $isExecutor = true;
            error_log("Executor detected: TaskTo was set in previous update, allowing status update");
        }
        
        // Проверяем, обновляется ли только статус (и возможно другие системные поля)
        $onlyStatusUpdate = isset($input['Status']) && count($input) === 1;
        
        // Логирование для отладки
        error_log("Task update check: userRole={$userRole}, userId={$userId}, taskTo={$taskTo}, taskBy={$taskBy}, isExecutor=" . ($isExecutor ? 'true' : 'false') . ", isCreator=" . ($isCreator ? 'true' : 'false') . ", onlyStatusUpdate=" . ($onlyStatusUpdate ? 'true' : 'false') . ", input=" . json_encode($input));
        
        // Руководитель проектов может обновлять любые задачи
        if ($userRole === 'Руководитель проектов') {
            // Разрешаем обновление - продолжаем выполнение
            error_log("Access granted: Project Manager");
        } elseif ($userRole === 'Руководитель') {
            // Руководитель может обновлять задачи только в своих проектах
            $projectModel = new ProjectModel();
            $project = $projectModel->getProjectById($task['ProjectID']);
            if ($project && isset($project['managerid']) && $project['managerid'] == $userId) {
                // Разрешаем обновление - продолжаем выполнение
                error_log("Access granted: Manager (own project)");
            } else {
                error_log("Access denied: Manager trying to update task in project they don't own");
                $this->json(['success' => false, 'error' => 'Вы можете обновлять задачи только в своих проектах'], 403);
            }
        } elseif ($userRole === 'Администратор') {
            // Администратор может обновлять задачи, но не может принимать их
            if (isset($input['Status']) && $input['Status'] === 'Выполнена') {
                error_log("Access denied: Admin cannot accept tasks");
                $this->json(['success' => false, 'error' => 'Администратор не может принимать задачи. Только руководитель проекта может принимать задачи.'], 403);
            }
            // Разрешаем обновление для администратора (кроме принятия задач)
            error_log("Access granted: Admin (but cannot accept tasks)");
        } elseif ($userRole === 'Исполнитель') {
            // Исполнитель может обновлять свою задачу (особенно статус)
            // Также может взять не назначенную задачу (TaskTo = null) в работу
            $isUnassignedTask = $taskTo === null;
            $isTakingTask = $isUnassignedTask && isset($input['Status']) && $input['Status'] === 'В работе';
            
            // Проверяем, отправляется ли задача на проверку (аналогично "взять в работу")
            // Если задача назначена исполнителю и обновляется только статус на "На проверке" - разрешаем
            $isSendingForReview = isset($input['Status']) && $input['Status'] === 'На проверке' && $onlyStatusUpdate && $isExecutor;
            
            // Проверяем, обновляется ли только статус на допустимые значения для исполнителя
            $isStatusUpdate = isset($input['Status']) && in_array($input['Status'], ['В работе', 'На проверке', 'Выполнена']);
            
            if ($isExecutor) {
                // Разрешаем обновление - продолжаем выполнение
                error_log("Access granted: Executor updating own task (isExecutor=true, taskTo={$taskTo}, userId={$userId})");
            } elseif ($isTakingTask) {
                // Разрешаем взять не назначенную задачу в работу
                // При этом нужно также обновить TaskTo на текущего пользователя
                if (!isset($input['TaskTo'])) {
                    $input['TaskTo'] = $userId;
                }
                error_log("Access granted: Executor taking unassigned task (TaskTo will be set to {$userId})");
            } elseif ($isSendingForReview) {
                // Разрешаем отправить задачу на проверку - логика аналогична "взять в работу"
                // Если $isSendingForReview true, значит $isExecutor уже true (проверено выше)
                error_log("Access granted: Executor sending task for review (isExecutor=true, taskTo={$taskTo}, userId={$userId})");
            } elseif ($isStatusUpdate && $onlyStatusUpdate) {
                // Если обновляется только статус и это допустимый статус для исполнителя
                // Проверяем еще раз с более мягким сравнением, так как после первого обновления
                // TaskTo должен быть установлен, но сравнение могло не сработать из-за типов данных
                $taskMatchesUser = false;
                if ($taskTo !== null) {
                    $taskMatchesUser = (int)$taskTo == (int)$userId || (string)$taskTo === (string)$userId || $taskTo == $userId;
                }
                
                if ($taskMatchesUser) {
                    error_log("Access granted: Executor updating status (onlyStatusUpdate=true, task matches user, taskTo={$taskTo}, userId={$userId})");
                } else {
                    error_log("Access denied: Executor trying to update status but task is not assigned to them. taskTo={$taskTo}, userId={$userId}");
                    $this->json(['success' => false, 'error' => 'Доступ запрещён. Вы можете обновлять только задачи, назначенные вам.'], 403);
                }
            } elseif ($isCreator && $onlyStatusUpdate) {
                // Если создатель и обновляется только статус - разрешаем
                error_log("Access granted: Creator updating task status");
            } else {
                error_log("Access denied: Executor trying to update task. isExecutor=" . ($isExecutor ? 'true' : 'false') . ", isCreator=" . ($isCreator ? 'true' : 'false') . ", isUnassignedTask=" . ($isUnassignedTask ? 'true' : 'false') . ", isTakingTask=" . ($isTakingTask ? 'true' : 'false') . ", isSendingForReview=" . ($isSendingForReview ? 'true' : 'false') . ", isStatusUpdate=" . ($isStatusUpdate ? 'true' : 'false') . ", onlyStatusUpdate=" . ($onlyStatusUpdate ? 'true' : 'false') . ", taskTo={$taskTo}, userId={$userId}, taskToType=" . gettype($taskTo) . ", userIdType=" . gettype($userId));
                $this->json(['success' => false, 'error' => 'Доступ запрещён. Вы можете обновлять только задачи, назначенные вам.'], 403);
            }
        } else {
            // Для остальных ролей (клиенты и т.д.) - только если они создатель
            if ($isCreator && $onlyStatusUpdate) {
                error_log("Access granted: Creator updating task status");
            } elseif ($isExecutor || $isCreator) {
                error_log("Access granted: User is executor or creator");
            } else {
                error_log("Access denied: User is not executor or creator");
                $this->json(['success' => false, 'error' => 'Доступ запрещён'], 403);
            }
        }

        try {
            $oldStatus = $task['Status'];
            
            // Логируем что именно обновляется
            error_log("Updating task {$id} with data: " . json_encode($input));
            
            $updated = $this->model->updateTask($id, $input);
            
            if ($updated) {
                // После обновления получаем актуальную задачу для проверки
                $updatedTask = $this->model->getTaskById($id);
                error_log("Task after update - TaskTo: " . var_export($updatedTask['TaskTo'] ?? 'null', true) . " (type: " . gettype($updatedTask['TaskTo'] ?? null) . "), Status: " . ($updatedTask['Status'] ?? 'null'));
                
                // Обновляем статус проекта на основе статусов задач
                if (isset($updatedTask['ProjectID']) && $updatedTask['ProjectID']) {
                    $projectModel = new ProjectModel();
                    $projectModel->updateProjectStatusBasedOnTasks($updatedTask['ProjectID']);
                }
                
                // Отправляем уведомления при изменении статуса
                if (isset($input['Status']) && $input['Status'] !== $oldStatus) {
                    $notificationModel = new NotificationModel();
                    $projectModel = new ProjectModel();
                    
                    // Получаем информацию о проекте для уведомления руководителя
                    $project = null;
                    if (isset($updatedTask['ProjectID']) && $updatedTask['ProjectID']) {
                        $project = $projectModel->getProjectById($updatedTask['ProjectID']);
                    }
                    
                    // Уведомление руководителю проекта при изменении статуса задачи
                    if ($project && isset($project['managerid']) && $project['managerid']) {
                        $taskTitle = $updatedTask['Title'] ?? 'Задача';
                        $executorName = $updatedTask['executor_fullname'] ?? 'Исполнитель';
                        
                        if ($input['Status'] === 'В работе') {
                            // Руководителю, когда исполнитель принял задачу в работу
                            $notificationModel->createNotification(
                                $project['managerid'],
                                'Задача взята в работу',
                                "Исполнитель {$executorName} принял задачу '{$taskTitle}' в работу",
                                'info',
                                'task',
                                $id
                            );
                        } elseif ($input['Status'] === 'На проверке') {
                            // Руководителю, когда исполнитель отправил задачу на проверку
                            $notificationModel->createNotification(
                                $project['managerid'],
                                'Задача отправлена на проверку',
                                "Исполнитель {$executorName} отправил задачу '{$taskTitle}' на проверку",
                                'warning',
                                'task',
                                $id
                            );
                        }
                    }
                    
                    // Уведомление исполнителю при изменении статуса (если он не сам изменил)
                    if (isset($updatedTask['TaskTo']) && $updatedTask['TaskTo'] && $updatedTask['TaskTo'] != $_SESSION['user_id']) {
                        $taskTitle = $updatedTask['Title'] ?? 'Задача';
                        $statusMessages = [
                            'В работе' => 'Задача взята в работу',
                            'На проверке' => 'Задача отправлена на проверку',
                            'Выполнена' => 'Задача выполнена'
                        ];
                        $message = $statusMessages[$input['Status']] ?? "Статус задачи изменён на: {$input['Status']}";
                        
                        $notificationModel->createNotification(
                            $updatedTask['TaskTo'],
                            'Изменение статуса задачи',
                            "Статус задачи '{$taskTitle}' изменён: {$message}",
                            'info',
                            'task',
                            $id
                        );
                    }
                }
                
                // Уведомление при назначении нового исполнителя
                $oldTaskTo = $task['TaskTo'] ?? $task['taskto'] ?? null;
                if (isset($input['TaskTo']) && $input['TaskTo'] != $oldTaskTo) {
                    $notificationModel = new NotificationModel();
                    $taskTitle = $updatedTask['Title'] ?? $task['Title'] ?? 'Задача';
                    $notificationModel->createNotification(
                        $input['TaskTo'],
                        'Вам назначена задача',
                        "Вам назначена задача: {$taskTitle}",
                        'warning',
                        'task',
                        $id
                    );
                }
                
                // Уведомление при изменении других полей задачи (исполнителю)
                $fieldsToNotify = ['Title', 'Description', 'StartDate', 'EndDate'];
                $hasChanges = false;
                $changedFields = [];
                foreach ($fieldsToNotify as $field) {
                    $oldValue = $task[$field] ?? null;
                    $newValue = $input[$field] ?? null;
                    if (isset($input[$field]) && $newValue != $oldValue) {
                        $hasChanges = true;
                        $fieldNames = [
                            'Title' => 'Название',
                            'Description' => 'Описание',
                            'StartDate' => 'Дата начала',
                            'EndDate' => 'Дата окончания'
                        ];
                        $changedFields[] = $fieldNames[$field] ?? $field;
                    }
                }
                
                if ($hasChanges && isset($updatedTask['TaskTo']) && $updatedTask['TaskTo'] && $updatedTask['TaskTo'] != $_SESSION['user_id']) {
                    $notificationModel = new NotificationModel();
                    $taskTitle = $updatedTask['Title'] ?? 'Задача';
                    $fieldsList = implode(', ', $changedFields);
                    $notificationModel->createNotification(
                        $updatedTask['TaskTo'],
                        'Изменение задачи',
                        "В назначенной вам задаче '{$taskTitle}' произошли изменения в полях: {$fieldsList}",
                        'info',
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
