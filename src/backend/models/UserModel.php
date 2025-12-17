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

    // Получить пользователей по ролям
    public function getUsersByRoles($roles) {
        if (empty($roles)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM \"User\" WHERE role IN ($placeholders) ORDER BY id");
        $stmt->execute($roles);
        return $stmt->fetchAll();
    }

    // Получить пользователя по ID
    public function getUserById($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM "User" WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    // Получить пользователя по логину
    public function getUserByLogin($login) {
        $stmt = $this->pdo->prepare('SELECT * FROM "User" WHERE login = :login');
        $stmt->execute(['login' => $login]);
        return $stmt->fetch();
    }

    // Получить пользователя по email
    public function getUserByEmail($email) {
        $stmt = $this->pdo->prepare('SELECT * FROM "User" WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    // Проверить пароль
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    // Добавить пользователя
    public function createUser($fullname, $email, $role, $login = null, $password = null) {
        // Если логин не указан, генерируем его на основе email
        if (empty($login)) {
            $login = $this->generateLoginFromEmail($email);
        }
        
        // Если пароль не указан, генерируем временный пароль
        if (empty($password)) {
            $password = $this->generateTemporaryPassword();
        }
        
        $stmt = $this->pdo->prepare('
            INSERT INTO "User" (fullname, email, role, login, password)
            VALUES (:fullname, :email, :role, :login, :password)
            RETURNING id, fullname, email, role, login, created_at
        ');
        
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt->execute([
            'fullname' => $fullname,
            'email' => $email,
            'role' => $role,
            'login' => $login,
            'password' => $passwordHash
        ]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Логирование для отладки
        error_log("Пользователь создан: ID {$user['id']}, Логин: {$user['login']}, Email: {$user['email']}");
        
        return $user;
    }

    // Генерировать логин из email
    private function generateLoginFromEmail($email) {
        $login = explode('@', $email)[0];
        // Убираем недопустимые символы и добавляем номер, если нужно
        $login = preg_replace('/[^a-zA-Z0-9_]/', '', $login);

        // Проверяем уникальность
        $counter = 1;
        $originalLogin = $login;
        while ($this->getUserByLogin($login)) {
            $login = $originalLogin . $counter;
            $counter++;
        }
        
        return $login;
    }
    
    // Генерировать временный пароль
    private function generateTemporaryPassword() {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    }


    // Обновить пользователя
    public function updateUser($id, $fullname, $email, $role, $login = null, $password = null) {
        $fields = ['fullname = :fullname', 'email = :email', 'role = :role'];
        $params = [
            'id' => $id,
            'fullname' => $fullname,
            'email' => $email,
            'role' => $role
        ];

        if ($login !== null) {
            $fields[] = 'login = :login';
            $params['login'] = $login;
        }

        if ($password !== null) {
            $fields[] = 'password = :password';
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql = 'UPDATE "User" SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // Удалить пользователя
    public function deleteUser($id) {

    // 2. Удаляем задачи, где пользователь исполнитель
    $this->pdo->prepare('DELETE FROM "task" WHERE "taskto" = :id')->execute(['id' => $id]);

    // 3. Удаляем проекты, где пользователь менеджер
    $this->pdo->prepare('DELETE FROM "project" WHERE "clientid" = :id')->execute(['id' => $id]);

    // 5. И наконец — удаляем пользователя
    $stmt = $this->pdo->prepare('DELETE FROM "User" WHERE "id" = :id');
    $stmt->execute(['id' => $id]);

    return $stmt->rowCount() > 0;
}
}