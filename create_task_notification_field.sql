-- Adicionar campo para controlar notificações de tarefas vencidas
ALTER TABLE follow_ups ADD COLUMN IF NOT EXISTS notified TINYINT NOT NULL DEFAULT 0;

-- Adicionar índice para consulta eficiente
ALTER TABLE follow_ups ADD INDEX IF NOT EXISTS idx_task_notification (type, due_date, notified);