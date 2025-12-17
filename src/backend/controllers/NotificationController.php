<?php
// controllers/NotificationController.php

class NotificationController {
    private $model;

    public function __construct() {
        $this->model = new NotificationModel();
    }

    private function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Не авторизован'], 401);
        }

        try {
            $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === 'true';
            $notifications = $this->model->getUserNotifications($_SESSION['user_id'], $unreadOnly);
            $this->json(['success' => true, 'data' => $notifications]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function markAsRead($id) {
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Не авторизован'], 401);
        }

        try {
            $this->model->markAsRead($id);
            $this->json(['success' => true, 'message' => 'Уведомление отмечено как прочитанное']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function markAllAsRead() {
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Не авторизован'], 401);
        }

        try {
            $this->model->markAllAsRead($_SESSION['user_id']);
            $this->json(['success' => true, 'message' => 'Все уведомления отмечены как прочитанные']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function delete($id) {
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Не авторизован'], 401);
        }

        try {
            $this->model->deleteNotification($id);
            $this->json(['success' => true, 'message' => 'Уведомление удалено']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}

