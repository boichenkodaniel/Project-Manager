<?php
// controllers/ReportController.php

class ReportController {
    private $model;

    public function __construct() {
        $this->model = new ReportModel();
    }

    private function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function index() {
        try {
            $reports = $this->model->getAllReports();
            $this->json(['success' => true, 'data' => $reports]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка получения отчётов: ' . $e->getMessage()], 500);
        }
    }

    public function get($id) {
        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID отчёта'], 400);
        }

        try {
            $report = $this->model->getReportById($id);
            if ($report) {
                $this->json(['success' => true, 'data' => $report]);
            } else {
                $this->json(['success' => false, 'error' => 'Отчёт не найден'], 404);
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

        $required = ['ReportType', 'UserID'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || trim((string)$input[$field]) === '') {
                $this->json(['success' => false, 'error' => "Поле '$field' обязательно"], 400);
            }
        }

        try {
            $id = $this->model->createReport(
                $input['ReportType'],
                $input['GenerationPeriod'] ?? null,
                $input['GenerationDate'] ?? null,
                $input['UserID']
            );
            $this->json(['success' => true, 'message' => 'Отчёт создан', 'id' => $id], 201);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка создания отчёта: ' . $e->getMessage()], 500);
        }
    }

    public function delete($id) {
        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID отчёта'], 400);
        }

        try {
            $deleted = $this->model->deleteReport($id);
            if ($deleted) {
                $this->json(['success' => true, 'message' => 'Отчёт удалён']);
            } else {
                $this->json(['success' => false, 'error' => 'Отчёт не найден'], 404);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка удаления: ' . $e->getMessage()], 500);
        }
    }

    // ✅ Отчёты по пользователю
    public function byUser($userId) {
        if (!$userId || !is_numeric($userId)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID пользователя'], 400);
        }

        try {
            $reports = $this->model->getReportsByUser($userId);
            $this->json(['success' => true, 'data' => $reports]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ Фильтрация: ?action=reports.filter&type=ежедневный&period=декабрь
    public function filter() {
        $type = $_GET['type'] ?? null;
        $period = $_GET['period'] ?? null;
        $userId = $_GET['userId'] ?? null;

        try {
            $reports = $this->model->getReportsFiltered($type, $period, $userId);
            $this->json(['success' => true, 'data' => $reports]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // НОВЫЙ: Получить статистику по задачам для графика
    public function getTasksStats() {
        AuthMiddleware::requireAuth(); // Только авторизованные пользователи
        try {
            $period = $_GET['period'] ?? 'week'; // По умолчанию за неделю
            $stats = $this->model->getTasksStats($period);
            $this->json(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка получения статистики задач: ' . $e->getMessage()], 500);
        }
    }

    // НОВЫЙ: Получить статистику по Issues для графика
    public function getIssuesStats() {
        AuthMiddleware::requireAuth(); // Только авторизованные пользователи
        try {
            $stats = $this->model->getIssuesStats();
            $this->json(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка получения статистики Issues: ' . $e->getMessage()], 500);
        }
    }
}