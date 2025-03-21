<?php
/**
 * Script para verificar tarefas pendentes e gerar notificações
 * Este script deve ser executado diariamente via cron job
 */

// Incluir arquivo de configuração
require_once(__DIR__ . '/includes/config.php');
require_once(__DIR__ . '/includes/functions.php');

// Verificar se a tabela follow_ups existe
$conn = getConnection();
$stmt = $conn->prepare("SHOW TABLES LIKE 'follow_ups'");
$stmt->execute();
$table_exists = $stmt->rowCount() > 0;

if (!$table_exists) {
    echo "Tabela 'follow_ups' não existe. Execute o script de instalação primeiro.\n";
    exit(1);
}

// Verificar se o campo 'notified' existe
try {
    $stmt = $conn->prepare("SHOW COLUMNS FROM follow_ups LIKE 'notified'");
    $stmt->execute();
    $column_exists = $stmt->rowCount() > 0;
    
    if (!$column_exists) {
        echo "Coluna 'notified' não existe. Adicionando...\n";
        $sql = file_get_contents(__DIR__ . '/create_task_notification_field.sql');
        $conn->exec($sql);
        echo "Coluna 'notified' adicionada com sucesso.\n";
    }
} catch (PDOException $e) {
    echo "Erro ao verificar/criar a coluna 'notified': " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Verificar tarefas pendentes e gerar notificações
 * Tipos de notificações:
 * 1. Tarefas vencidas (data de vencimento < hoje)
 * 2. Tarefas que vencem hoje
 * 3. Tarefas que vencem amanhã
 */
function checkPendingTasks() {
    $conn = getConnection();
    $count = 0;
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    // 1. Tarefas vencidas (passadas) que não foram notificadas
    $sql = "SELECT f.*, l.name as lead_name, l.id as lead_id
            FROM follow_ups f
            JOIN leads l ON f.lead_id = l.id
            WHERE f.type = 'task'
            AND (f.status IS NULL OR f.status = 'pending')
            AND f.due_date < :today
            AND f.notified = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $overdue_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($overdue_tasks as $task) {
        $title = "Tarefa Atrasada";
        $message = "A tarefa \"" . substr($task['content'], 0, 50) . (strlen($task['content']) > 50 ? "..." : "") . 
                  "\" para o lead " . $task['lead_name'] . " está atrasada. Vencimento: " . 
                  date('d/m/Y', strtotime($task['due_date']));
        
        createNotification(
            $task['user_id'],
            $title,
            $message,
            'task',
            'fas fa-exclamation-circle',
            'danger',
            $task['id'],
            'task',
            "index.php?route=lead-detail&id={$task['lead_id']}"
        );
        
        // Marcar como notificado
        $updateSql = "UPDATE follow_ups SET notified = 1 WHERE id = :id";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute(['id' => $task['id']]);
        $count++;
        
        echo "Notificação criada para tarefa atrasada ID: {$task['id']} - Lead: {$task['lead_name']}\n";
    }
    
    // 2. Tarefas que vencem hoje e não foram notificadas
    $sql = "SELECT f.*, l.name as lead_name, l.id as lead_id
            FROM follow_ups f
            JOIN leads l ON f.lead_id = l.id
            WHERE f.type = 'task'
            AND (f.status IS NULL OR f.status = 'pending')
            AND f.due_date = :today
            AND f.notified = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $today_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($today_tasks as $task) {
        $title = "Tarefa Vence Hoje";
        $message = "A tarefa \"" . substr($task['content'], 0, 50) . (strlen($task['content']) > 50 ? "..." : "") . 
                  "\" para o lead " . $task['lead_name'] . " vence hoje!";
        
        createNotification(
            $task['user_id'],
            $title,
            $message,
            'task',
            'fas fa-clock',
            'warning',
            $task['id'],
            'task',
            "index.php?route=lead-detail&id={$task['lead_id']}"
        );
        
        // Marcar como notificado
        $updateSql = "UPDATE follow_ups SET notified = 1 WHERE id = :id";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute(['id' => $task['id']]);
        $count++;
        
        echo "Notificação criada para tarefa de hoje ID: {$task['id']} - Lead: {$task['lead_name']}\n";
    }
    
    // 3. Tarefas que vencem amanhã e não foram notificadas
    $sql = "SELECT f.*, l.name as lead_name, l.id as lead_id
            FROM follow_ups f
            JOIN leads l ON f.lead_id = l.id
            WHERE f.type = 'task'
            AND (f.status IS NULL OR f.status = 'pending')
            AND f.due_date = :tomorrow
            AND f.notified = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':tomorrow', $tomorrow);
    $stmt->execute();
    $tomorrow_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tomorrow_tasks as $task) {
        $title = "Tarefa Vence Amanhã";
        $message = "A tarefa \"" . substr($task['content'], 0, 50) . (strlen($task['content']) > 50 ? "..." : "") . 
                  "\" para o lead " . $task['lead_name'] . " vence amanhã!";
        
        createNotification(
            $task['user_id'],
            $title,
            $message,
            'task',
            'fas fa-bell',
            'info',
            $task['id'],
            'task',
            "index.php?route=lead-detail&id={$task['lead_id']}"
        );
        
        // Marcar como notificado
        $updateSql = "UPDATE follow_ups SET notified = 1 WHERE id = :id";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute(['id' => $task['id']]);
        $count++;
        
        echo "Notificação criada para tarefa de amanhã ID: {$task['id']} - Lead: {$task['lead_name']}\n";
    }
    
    return $count;
}

// Executar a verificação
$count = checkPendingTasks();
echo "Geradas {$count} notificações para tarefas pendentes.\n";