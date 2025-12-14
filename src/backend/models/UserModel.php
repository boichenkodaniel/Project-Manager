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
        $stmt = $this->pdo->prepare('SELECT * FROM "User" WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    // Добавить пользователя
    public function createUser( $fullname, $email, $role) {
    $stmt = $this->pdo->prepare('
        INSERT INTO "User" (fullname, email, role)
        VALUES ( :fullname, :email, :role)
        RETURNING id, fullname, email, role
    ');
    $stmt->execute([
        'fullname' => $fullname,
        'email' => $email,
        'role' => $role
    ]);
    return $stmt->fetch(PDO::FETCH_ASSOC); // вернёт объект с id, fullname, email, role
}


    // Обновить пользователя
    public function updateUser($id, $fullname, $email, $role) {
        $sql = 'UPDATE "User" SET fullname = :fullname, email = :email, role = :role';
        $params = [
            'id' => $id,
            'fullname' => $fullname,
            'email' => $email,
            'role' => $role
        ];


        $sql .= " WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // Удалить пользователя
    public function deleteUser($id) {
    // 1. Удаляем отчёты (если ON DELETE CASCADE — не нужно, но для надёжности)
    $this->pdo->prepare('DELETE FROM "report" WHERE "userid" = :id')->execute(['id' => $id]);

    // 2. Удаляем задачи, где пользователь исполнитель
    $this->pdo->prepare('DELETE FROM "task" WHERE "executorid" = :id')->execute(['id' => $id]);

    // 3. Удаляем проекты, где пользователь менеджер
    $this->pdo->prepare('DELETE FROM "project" WHERE "managerid" = :id')->execute(['id' => $id]);

    // 4. Удаляем назначения ресурсов (TaskResource)
    $this->pdo->prepare('DELETE FROM "taskresource" WHERE "resourceid" IN (
        SELECT "id" FROM "resource" WHERE "id" IN (
            SELECT "resourceid" FROM "taskresource" WHERE "taskid" IN (
                SELECT "id" FROM "task" WHERE "executorid" = :id
            )
        )
    )')->execute(['id' => $id]);

    // 5. И наконец — удаляем пользователя
    $stmt = $this->pdo->prepare('DELETE FROM "User" WHERE "id" = :id');
    $stmt->execute(['id' => $id]);

    return $stmt->rowCount() > 0;
}
}