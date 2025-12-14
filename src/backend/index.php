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
require_once __DIR__ . '/models/ReportModel.php';
require_once __DIR__ . '/controllers/ReportController.php';
require_once __DIR__ . '/models/ResourceModel.php';
require_once __DIR__ . '/controllers/ResourceController.php';
require_once __DIR__ . '/models/TaskResourceModel.php';        // ← новое
require_once __DIR__ . '/controllers/TaskResourceController.php'; // ← новое

try {
    // Инициализация контроллеров
    $userCtrl = new UserController();
    $projectCtrl = new ProjectController();
    $taskCtrl = new TaskController();
    $reportCtrl = new ReportController();
    $resourceCtrl = new ResourceController();
    $taskResourceCtrl = new TaskResourceController();  // ← новое

    $action = $_GET['action'] ?? 'user.index';
    $id = $_GET['id'] ?? null;

    $parts = explode('.', $action, 2);
    $namespace = $parts[0];
    $method = $parts[1] ?? 'index';

    switch ($namespace) {
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

        case 'tasks':
            switch ($method) {
                case 'index': $taskCtrl->index(); break;
                case 'get': $taskCtrl->get($id); break;
                case 'create': $taskCtrl->create(); break;
                case 'update': $taskCtrl->update($id); break;
                case 'delete': $taskCtrl->delete($id); break;
                case 'byProject': $taskCtrl->byProject($id); break;
                case 'byExecutor': $taskCtrl->byExecutor($id); break;
                default: throw new Exception("Неизвестный метод: tasks.$method", 404);
            }
            break;

        case 'reports':
            switch ($method) {
                case 'index': $reportCtrl->index(); break;
                case 'get': $reportCtrl->get($id); break;
                case 'create': $reportCtrl->create(); break;
                case 'delete': $reportCtrl->delete($id); break;
                case 'byUser': $reportCtrl->byUser($id); break;
                case 'filter': $reportCtrl->filter(); break;
                default: throw new Exception("Неизвестный метод: reports.$method", 404);
            }
            break;

        case 'resources':
            switch ($method) {
                case 'index': $resourceCtrl->index(); break;
                case 'get': $resourceCtrl->get($id); break;
                case 'create': $resourceCtrl->create(); break;
                case 'update': $resourceCtrl->update($id); break;
                case 'delete': $resourceCtrl->delete($id); break;
                case 'filter': $resourceCtrl->filter(); break;
                case 'summary': $resourceCtrl->summary(); break;
                default: throw new Exception("Неизвестный метод: resources.$method", 404);
            }
            break;

        case 'taskresources':
            switch ($method) {
                case 'assign': $taskResourceCtrl->assign(); break;
                case 'remove': $taskResourceCtrl->remove(); break;
                case 'byTask': $taskResourceCtrl->byTask($id); break;
                case 'byResource': $taskResourceCtrl->byResource($id); break;
                case 'all': $taskResourceCtrl->all(); break;
                default: throw new Exception("Неизвестный метод: taskresources.$method", 404);
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