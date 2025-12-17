-- Исправление структуры таблицы project
-- Выполните этот скрипт для добавления колонки clientid

-- Проверка и добавление колонки clientid в таблицу project
DO $$ 
BEGIN
    -- Проверяем, существует ли колонка clientid
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'project' 
        AND column_name = 'clientid'
    ) THEN
        -- Добавляем колонку clientid
        ALTER TABLE "project" 
        ADD COLUMN clientid INTEGER REFERENCES "User"(id) ON DELETE SET NULL;
        
        RAISE NOTICE 'Колонка clientid успешно добавлена в таблицу project';
    ELSE
        RAISE NOTICE 'Колонка clientid уже существует в таблице project';
    END IF;
END $$;

-- Проверка структуры таблицы
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'project'
ORDER BY ordinal_position;

