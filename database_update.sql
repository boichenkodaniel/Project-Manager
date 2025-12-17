-- Обновление таблицы User: добавление полей login и password
ALTER TABLE "User" 
ADD COLUMN IF NOT EXISTS login VARCHAR(100) UNIQUE,
ADD COLUMN IF NOT EXISTS password VARCHAR(255);

-- Обновление ролей: добавление роли "Руководитель проектов"
-- (Если используется ENUM, нужно будет обновить тип, иначе просто обновить существующие записи)

-- Создание таблицы issue (проблемы/замечания)
CREATE TABLE IF NOT EXISTS "issue" (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'Открыта',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    project_id INTEGER REFERENCES "project"(id) ON DELETE CASCADE,
    created_by INTEGER REFERENCES "User"(id) ON DELETE SET NULL,
    assigned_to INTEGER REFERENCES "User"(id) ON DELETE SET NULL
);

-- Создание таблицы notification (уведомления)
CREATE TABLE IF NOT EXISTS "notification" (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES "User"(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info', -- info, warning, error, success
    related_entity_type VARCHAR(50), -- project, task, issue
    related_entity_id INTEGER,
    is_read BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Создание индексов для улучшения производительности
CREATE INDEX IF NOT EXISTS idx_issue_project_id ON "issue"(project_id);
CREATE INDEX IF NOT EXISTS idx_issue_created_by ON "issue"(created_by);
CREATE INDEX IF NOT EXISTS idx_notification_user_id ON "notification"(user_id);
CREATE INDEX IF NOT EXISTS idx_notification_is_read ON "notification"(is_read);
CREATE INDEX IF NOT EXISTS idx_user_login ON "User"(login);

-- Обновление статусов задач (если нужно)
-- Статусы: 'В работе', 'На проверке', 'Выполнена'

-- Проверка и добавление колонки clientid в таблицу project (если её нет)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'project' AND column_name = 'clientid'
    ) THEN
        ALTER TABLE "project" ADD COLUMN clientid INTEGER REFERENCES "User"(id) ON DELETE SET NULL;
    END IF;
END $$;

