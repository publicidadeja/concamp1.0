<?php
/**
 * Configuração da Landing Page e Token do Vendedor
 */

// Título da página
$page_title = 'Minha Landing Page';

// Verificar permissão (apenas vendedores)
if (!hasPermission('seller')) {
    include_once __DIR__ . '/../access-denied.php';
    exit;
}

$user = getCurrentUser();
$user_id = $user['id'];

// Processar ações
$message = '';
$error = '';

if (isset($_POST['action']) && $_POST['action'] === 'update_landing_page') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $landing_page_name = sanitize($_POST['landing_page_name'] ?? '');
        $whatsapp_token = sanitize($_POST['whatsapp_token'] ?? '');

        // Validar nome da landing page (único e alfanumérico)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $landing_page_name)) {
            $error = 'O nome da landing page deve conter apenas letras, números, hífens e underscores.';
        } else {
            $conn = getConnection();
            $stmt = $conn->prepare("SELECT id FROM users WHERE landing_page_name = :name AND id != :id");
            $stmt->execute(['name' => $landing_page_name, 'id' => $user_id]);
            if ($stmt->rowCount() > 0) {
                $error = 'Este nome de landing page já está em uso. Escolha outro.';
            } else {
                // Atualizar dados do usuário
                $result = updateUser($user_id, [
                    'landing_page_name' => $landing_page_name,
                    'whatsapp_token' => $whatsapp_token
                ]);

                if ($result['success']) {
                    $message = 'Landing page e token atualizados com sucesso!';
                    // Atualizar dados do usuário na sessão
                    $user = getCurrentUser();
                } else {
                    $error = $result['error'] ?? 'Erro ao atualizar dados.';
                }
            }
        }
    }
}

// Gerar token CSRF
$csrf_token = createCsrfToken();
?>

<!-- Mensagens de feedback -->
<?php if (!empty($message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Configurar Minha Landing Page</h5>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo url('index.php?route=seller-landing-page'); ?>">
            <input type="hidden" name="action" value="update_landing_page">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="mb-3">
                <label for="landing_page_name" class="form-label">Nome da Landing Page</label>
                <input type="text" class="form-control" id="landing_page_name" name="landing_page_name" value="<?php echo $user['landing_page_name'] ?? ''; ?>" required>
                <small class="form-text text-muted">
                    Escolha um nome único para a sua landing page (ex: seunome). A URL será: <?php echo url('lp/'); ?><span id="lp-preview">seunome</span>
                </small>
            </div>

            <div class="mb-3">
                <label for="whatsapp_token" class="form-label">Seu Token do WhatsApp</label>
                <input type="text" class="form-control" id="whatsapp_token" name="whatsapp_token" value="<?php echo $user['whatsapp_token'] ?? ''; ?>">
                <small class="form-text text-muted">
                    Informe o token do seu dispositivo WhatsApp para enviar mensagens personalizadas.
                </small>
            </div>

            <button type="submit" class="btn btn-primary">Salvar</button>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const lpNameInput = document.getElementById('landing_page_name');
    const lpPreview = document.getElementById('lp-preview');

    lpNameInput.addEventListener('input', function() {
        lpPreview.textContent = this.value;
    });
});
</script>
