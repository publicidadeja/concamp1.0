<?php
/**
 * Script para adicionar o campo notificado na tabela de follow_ups
 */

// Iniciar buffer de saída para evitar problemas com headers já enviados
ob_start();

// Verificar autenticação
if (!isLoggedIn() || !isAdmin()) {
    ob_end_clean(); // Limpar buffer antes de redirecionar
    redirect('index.php?route=login');
}

$page_title = "Atualizar Tabela de Tarefas";
$body_class = "admin-page";

// Obter conexão com o banco de dados
$conn = getConnection();

// Executar o script SQL
$success = false;
$error = null;

try {
    // Verificar se a tabela follow_ups existe
    $stmt = $conn->prepare("SHOW TABLES LIKE 'follow_ups'");
    $stmt->execute();
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        $error = "A tabela de follow_ups não existe. Execute a instalação completa primeiro.";
    } else {
        // Verificar se o campo notified já existe
        $stmt = $conn->prepare("SHOW COLUMNS FROM follow_ups LIKE 'notified'");
        $stmt->execute();
        $column_exists = $stmt->rowCount() > 0;
        
        if ($column_exists) {
            $success = true;
            $field_already_exists = true;
        } else {
            // Ler e executar o arquivo SQL
            $sql = file_get_contents(__DIR__ . '/../create_task_notification_field.sql');
            $conn->exec($sql);
            $success = true;
        }
    }
} catch (PDOException $e) {
    $error = "Erro ao atualizar a tabela: " . $e->getMessage();
}

// Template
include_once(__DIR__ . '/../templates/header.php');
?>

<div class="container my-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="mb-0">Atualizar Tabela de Tarefas</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <strong>Erro!</strong> <?php echo $error; ?>
                        </div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success">
                            <?php if (isset($field_already_exists)): ?>
                                <strong>Sucesso!</strong> O campo de notificação já existe na tabela de tarefas.
                            <?php else: ?>
                                <strong>Sucesso!</strong> O campo de notificação foi adicionado à tabela de tarefas.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <p>Esta atualização permite que o sistema gere notificações automáticas para tarefas pendentes e atrasadas.</p>
                    
                    <div class="mt-3">
                        <a href="<?php echo url('index.php?route=dashboard'); ?>" class="btn btn-primary">Voltar para o Dashboard</a>
                        <a href="<?php echo url('task_notifications.php'); ?>" class="btn btn-success ms-2">Executar Verificação de Tarefas</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once(__DIR__ . '/../templates/footer.php');

// Finalizar o buffer de saída
if (ob_get_level() > 0) {
    ob_end_flush();
}
?>