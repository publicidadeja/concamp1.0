<?php
/**
 * Página 404 - Não encontrado
 */

// Título da página
$page_title = 'Página não encontrada';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="error-container mt-5">
                <h1 class="display-1">404</h1>
                <h2 class="mb-4">Página não encontrada</h2>
                <p class="lead">A página que você está procurando não existe ou foi movida.</p>
                <div class="mt-4">
                    <a href="<?php echo url('index.php'); ?>" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i> Voltar para a página inicial
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
