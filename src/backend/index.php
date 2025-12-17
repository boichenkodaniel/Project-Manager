<?php
// index.php — точка входа API (TaskManager Backend)

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');



session_start();

// Подключение всех модулей
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/models/ProjectModel.php';
require_once __DIR__ . '/controllers/ProjectController.php';
require_once __DIR__ . '/models/TaskModel.php';
require_once __DIR__ . '/controllers/TaskController.php';
require_once __DIR__ . '/models/IssueModel.php';
require_once __DIR__ . '/controllers/IssueController.php';
require_once __DIR__ . '/models/NotificationModel.php';
require_once __DIR__ . '/controllers/NotificationController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';


try {
    // Инициализация контроллеров
    $authCtrl = new AuthController();
    $userCtrl = new UserController();
    $projectCtrl = new ProjectController();
    $taskCtrl = new TaskController();
    $issueCtrl = new IssueController();
    $notificationCtrl = new NotificationController();

    $action = $_GET['action'] ?? 'auth.me';
    $id = $_GET['id'] ?? null;

    $parts = explode('.', $action, 2);
    $namespace = $parts[0];
    $method = $parts[1] ?? 'index';

    switch ($namespace) {
        case 'auth':
            switch ($method) {
                case 'login': $authCtrl->login(); break;
                case 'logout': $authCtrl->logout(); break;
                case 'me': $authCtrl->me(); break;
                default: throw new Exception("Неизвестный метод: auth.$method", 404);
            }
            break;

        case 'user':
            switch ($method) {
                case 'index': $userCtrl->index(); break;
                case 'get': $userCtrl->get($id); break;
                case 'create': $userCtrl->create(); break;
                case 'update': $userCtrl->update($id); break;
                case 'delete': $userCtrl->delete($id); break;
                default: throw new Exception("Неизвестный метод: user.$method", 404);
            }
            break;

        case 'projects':
            switch ($method) {
                case 'index': $projectCtrl->index(); break;
                case 'get': $projectCtrl->get($id); break;
                case 'create': $projectCtrl->create(); break;
                case 'update': $projectCtrl->update($id); break;
                case 'delete': $projectCtrl->delete($id); break;
                default: throw new Exception("Неизвестный метод: projects.$method", 404);
            }
            break;

        case 'task':
            switch ($method) {
                case 'index': $taskCtrl->index(); break;
                case 'get': $taskCtrl->get($id); break;
                case 'create': $taskCtrl->create(); break;
                case 'update': $taskCtrl->update($id); break;
                case 'delete': $taskCtrl->delete($id); break;
                case 'byProject': $taskCtrl->byProject($id); break;
                case 'byExecutor': $taskCtrl->byExecutor($id); break;
                default: throw new Exception("Неизвестный метод: task.$method", 404);
            }
            break;

        case 'issue':
            switch ($method) {
                case 'index': $issueCtrl->index(); break;
                case 'get': $issueCtrl->get($id); break;
                case 'create': $issueCtrl->create(); break;
                case 'update': $issueCtrl->update($id); break;
                case 'delete': $issueCtrl->delete($id); break;
                case 'byProject': $issueCtrl->byProject($id); break;
                default: throw new Exception("Неизвестный метод: issue.$method", 404);
            }
            break;

        case 'notification':
            switch ($method) {
                case 'index': $notificationCtrl->index(); break;
                case 'markAsRead': $notificationCtrl->markAsRead($id); break;
                case 'markAllAsRead': $notificationCtrl->markAllAsRead(); break;
                case 'delete': $notificationCtrl->delete($id); break;
                default: throw new Exception("Неизвестный метод: notification.$method", 404);
            }
            break;

        default:
            throw new Exception("Неизвестная сущность: $namespace", 404);
    }

} catch (Exception $e) {
    $code = $e->getCode();
    if ($code < 400 || $code >= 600) $code = 500;

    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}