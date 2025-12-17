<?php
// controllers/AuthController.php

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    private function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // POST /?action=auth.login
    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $this->json(['success' => false, 'error' => 'Тело запроса должно быть в формате JSON'], 400);
        }

        $login = $input['login'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($login) || empty($password)) {
            $this->json(['success' => false, 'error' => 'Логин и пароль обязательны'], 400);
        }

        try {
            $user = $this->userModel->getUserByLogin($login);
            
            if (!$user || !isset($user['password'])) {
                $this->json(['success' => false, 'error' => 'Неверный логин или пароль'], 401);
            }

            if (!$this->userModel->verifyPassword($password, $user['password'])) {
                $this->json(['success' => false, 'error' => 'Неверный логин или пароль'], 401);
            }

            // Сохраняем в сессию
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_login'] = $user['login'];
            $_SESSION['user_fullname'] = $user['fullname'];

            // Возвращаем данные пользователя (без пароля)
            unset($user['password']);
            $this->json([
                'success' => true,
                'message' => 'Успешный вход',
                'data' => $user
            ]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => 'Ошибка входа: ' . $e->getMessage()], 500);
        }
    }

    // POST /?action=auth.logout
    public function logout() {
        session_destroy();
        $this->json(['success' => true, 'message' => 'Выход выполнен']);
    }

    // GET /?action=auth.me
    public function me() {
        if (!isset($_SESSION['user_id'])) {
            $this->json(['success' => false, 'error' => 'Не авторизован'], 401);
        }

        try {
            $user = $this->userModel->getUserById($_SESSION['user_id']);
            if ($user) {
                unset($user['password']);
                $this->json(['success' => true, 'data' => $user]);
            } else {
                $this->json(['success' => false, 'error' => 'Пользователь не найден'], 404);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}

