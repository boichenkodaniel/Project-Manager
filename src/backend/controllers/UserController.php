<?php
// controllers/UserController.php

class UserController {
    private $model;

    public function __construct() {
        $this->model = new UserModel();
    }

    // Вспомогательный метод для отправки JSON
    private function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // GET /?action=index → список пользователей
    public function index() {
        try {
            $users = $this->model->getAllUsers();
            $this->json(['success' => true, 'data' => $users]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка получения данных: ' . $e->getMessage()], 500);
        }
    }

    // GET /?action=get&id=1 → один пользователь
    public function get($id) {
        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID'], 400);
        }

        try {
            $user = $this->model->getUserById($id);
            if ($user) {
                $this->json(['success' => true, 'data' => $user]);
            } else {
                $this->json(['success' => false, 'error' => 'Пользователь не найден'], 404);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // POST /?action=create → добавить пользователя
    public function create() {
    $input = json_decode(file_get_contents('php://input'), true);

    $required = ['fullname', 'email', 'role'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || trim($input[$field]) === '') {
            $this->json(['success' => false, 'error' => "Поле '$field' обязательно"], 400);
        }
    }

    try {
        $user = $this->model->createUser(
            $input['fullname'],
            $input['email'],
            $input['role']
        );

        $this->json([
            'success' => true,
            'data' => $user
        ], 201);

    } catch (Exception $e) {
        $this->json(['success' => false, 'error' => 'Ошибка создания: ' . $e->getMessage()], 500);
    }
}


    // POST /?action=update&id=1 → обновить пользователя
    public function update($id) {
        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID'], 400);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->json(['success' => false, 'error' => 'Некорректные данные (ожидается JSON)'], 400);
        }

        $fields = [ 'fullname', 'email', 'role'];
        foreach ($fields as $field) {
            if (!isset($input[$field]) || trim($input[$field]) === '') {
                $this->json(['success' => false, 'error' => "Поле '$field' обязательно"], 400);
            }
        }

        try {
            $updated = $this->model->updateUser(
                $id,
                $input['fullname'],
                $input['email'],
                $input['role']
            );
            if ($updated) {
                $this->json(['success' => true, 'message' => 'Пользователь обновлён']);
            } else {
                $this->json(['success' => false, 'error' => 'Не удалось обновить пользователя'], 400);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка обновления: ' . $e->getMessage()], 500);
        }
    }

    // GET /?action=delete&id=1 → удалить пользователя
    public function delete($id) {
        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'error' => 'Некорректный ID'], 400);
        }

        try {
            $deleted = $this->model->deleteUser($id);
            if ($deleted) {
                $this->json(['success' => true, 'message' => 'Пользователь удалён']);
            } else {
                $this->json(['success' => false, 'error' => 'Пользователь не найден'], 404);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка удаления: ' . $e->getMessage()], 500);
        }
    }
}