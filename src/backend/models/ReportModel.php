<?php
// models/ReportModel.php

class ReportModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // Получить все отчёты (с именем пользователя)
    public function getAllReports() {
        $sql = '
            SELECT 
                r.ID,
                r.ReportType,
                r.GenerationPeriod,
                r.GenerationDate,
                r.UserID,
                u.fullname AS user_fullname,
                u.login AS user_login
            FROM "Report" r
            LEFT JOIN "User" u ON u.ID = r.UserID
            ORDER BY r.GenerationDate DESC, r.ID DESC
        ';
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    // Получить отчёт по ID
    public function getReportById($id) {
        $sql = '
            SELECT 
                r.ID,
                r.ReportType,
                r.GenerationPeriod,
                r.GenerationDate,
                r.UserID,
                u.fullname AS user_fullname,
                u.login AS user_login
            FROM "Report" r
            LEFT JOIN "User" u ON u.ID = r.UserID
            WHERE r.ID = :id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    // Создать отчёт
    public function createReport($reportType, $generationPeriod, $generationDate, $userId) {
        // Валидация обязательных полей
        if (!$reportType) {
            throw new InvalidArgumentException("Тип отчёта обязателен");
        }

        // Проверка существования пользователя
        $userExists = $this->pdo->prepare('SELECT 1 FROM "User" WHERE ID = :id');
        $userExists->execute(['id' => $userId]);
        if (!$userExists->fetch()) {
            throw new InvalidArgumentException("Пользователь с ID $userId не найден");
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO "Report" (ReportType, GenerationPeriod, GenerationDate, UserID)
            VALUES (:reportType, :generationPeriod, :generationDate, :userId)
            RETURNING ID
        ');

        $stmt->execute([
            'reportType' => $reportType,
            'generationPeriod' => $generationPeriod ?: null,
            'generationDate' => $generationDate ?: date('Y-m-d'), // DEFAULT CURRENT_DATE
            'userId' => $userId
        ]);

        return $stmt->fetchColumn();
    }

    // Удалить отчёт
    public function deleteReport($id) {
        $stmt = $this->pdo->prepare('DELETE FROM "Report" WHERE ID = :id');
        return $stmt->execute(['id' => $id]);
    }

    // ✅ Дополнительно: получить отчёты по пользователю
    public function getReportsByUser($userId) {
        $sql = '
            SELECT 
                r.ID,
                r.ReportType,
                r.GenerationPeriod,
                r.GenerationDate
            FROM "Report" r
            WHERE r.UserID = :userId
            ORDER BY r.GenerationDate DESC
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll();
    }

    // ✅ Дополнительно: фильтрация по типу и периоду
    public function getReportsFiltered($type = null, $period = null, $userId = null) {
        $sql = '
            SELECT 
                r.ID,
                r.ReportType,
                r.GenerationPeriod,
                r.GenerationDate,
                u.fullname AS user_fullname
            FROM "Report" r
            LEFT JOIN "User" u ON u.ID = r.UserID
            WHERE 1=1
        ';
        $params = [];

        if ($type) {
            $sql .= ' AND r.ReportType ILIKE :type';
            $params['type'] = "%$type%";
        }
        if ($period) {
            $sql .= ' AND r.GenerationPeriod ILIKE :period';
            $params['period'] = "%$period%";
        }
        if ($userId) {
            $sql .= ' AND r.UserID = :userId';
            $params['userId'] = $userId;
        }

        $sql .= ' ORDER BY r.GenerationDate DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}