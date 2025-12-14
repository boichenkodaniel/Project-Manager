<?php
// models/UserModel.php

class UserModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // Получить всех пользователей
    public function getAllUsers() {
        $stmt = $this->pdo->query('SELECT * FROM "User" ORDER BY id');
        return $stmt->fetchAll();
    }

    // Получить пользователя по ID
    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM user WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    // Добавить пользователя
    public function createUser($login, $password, $fullname, $email, $role) {
        $stmt = $this->pdo->prepare("
            INSERT INTO user (login, password, fullname, email, role)
            VALUES (:login, :password, :fullname, :email, :role)
            RETURNING id
        ");
        $stmt->execute([
            'login' => $login,
            'password' => password_hash($password, PASSWORD_BCRYPT), // Всегда хешируйте пароль!
            'fullname' => $fullname,
            'email' => $email,
            'role' => $role
        ]);
        return $stmt->fetchColumn(); // возвращает id нового пользователя
    }

    // Обновить пользователя
    public function updateUser($id, $login, $password, $fullname, $email, $role) {
        $sql = "UPDATE user SET login = :login, fullname = :fullname, email = :email, role = :role";
        $params = [
            'id' => $id,
            'login' => $login,
            'fullname' => $fullname,
            'email' => $email,
            'role' => $role
        ];

        if ($password) {
            $sql .= ", password = :password";
            $params['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // Удалить пользователя
    public function deleteUser($id) {
        $stmt = $this->pdo->prepare("DELETE FROM user WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}