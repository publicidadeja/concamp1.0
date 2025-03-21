<?php
/**
 * Script para criar a tabela de notificações se não existir
 */

// Iniciar buffer de saída para evitar problemas com headers já enviados
ob_start();

// Verificar autenticação
if (!isLoggedIn() || !isAdmin()) {
    ob_end_clean(); // Limpar buffer antes de redirecionar
    redirect('index.php?route=login');
}

$page_title = "Criar Tabela de Notificações";
$body_class = "admin-page";

// Obter conexão com o banco de dados
$conn = getConnection();

// Executar o script SQL
$success = false;
$error = null;

try {
    // Verificar se a tabela já existe
    $stmt = $conn->prepare("SHOW TABLES LIKE 'notifications'");
    $stmt->execute();
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        // Ler o conteúdo do arquivo SQL
        $sql = file_get_contents(__DIR__ . '/../create_notifications_table.sql');
        
        // Executar o script SQL
        $conn->exec($sql);
        
        $success = true;
    } else {
        $success = true;
        $table_already_exists = true;
    }
} catch (PDOException $e) {
    $error = "Erro ao criar a tabela: " . $e->getMessage();
}

// Template
include_once(__DIR__ . '/../templates/header.php');
?>

<div class="container my-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="mb-0">Criar Tabela de Notificações</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <strong>Erro!</strong> <?php echo $error; ?>
                        </div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success">
                            <?php if (isset($table_already_exists)): ?>
                                <strong>Sucesso!</strong> A tabela de notificações já existe.
                            <?php else: ?>
                                <strong>Sucesso!</strong> A tabela de notificações foi criada com sucesso.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="<?php echo url('index.php?route=dashboard'); ?>" class="btn btn-primary">Voltar para o Dashboard</a>
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