<?php
// Página de acesso negado
$page_title = 'Acesso Negado';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle text-danger" style="font-size: 60px;"></i>
                    </div>
                    <h1 class="h3 mb-3">Acesso Negado</h1>
                    <p class="mb-4">Você não tem permissão para acessar esta página. Entre em contato com um administrador se precisar de acesso.</p>
                    <div class="d-flex justify-content-center">
                        <a href="<?php echo url('index.php?route=dashboard'); ?>" class="btn btn-primary me-2">
                            <i class="fas fa-home me-2"></i>Ir para o Dashboard
                        </a>
                        <a href="<?php echo url('index.php?route=logout'); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-sign-out-alt me-2"></i>Sair
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
