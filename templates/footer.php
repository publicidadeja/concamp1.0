<?php
// Verificar se é página do painel
$current_route = $_GET['route'] ?? 'home';
$is_dashboard = !in_array($current_route, ['home', 'login', 'register', 'forgot-password', 'reset-password', 'simulador', 'process-simulation', '404']);

// Verificar se devemos pular o footer (para landing pages com footer próprio)
$skip_global_footer = isset($skip_global_footer) && $skip_global_footer === true;
?>

<?php if ($is_dashboard): ?>
                </div> <!-- End .content-area -->
            </div> <!-- End .container-fluid -->
        </div> <!-- End .main-content -->
    </div> <!-- End .dashboard-container -->

    <!-- Toast para notificações -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050">
        <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto" id="toastTitle">Notificação</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage">

            </div>
        </div>
    </div>

<?php elseif (!$skip_global_footer): ?>
    </main>

    <!-- Site Footer -->
    <footer class="site-footer bg-light py-5"> <!-- Rodapé com fundo claro e padding vertical -->
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0"> <!-- Coluna para informações da ConCamp -->
                    <h5 class="mb-3">ConCamp</h5> <!-- Título da coluna -->
                    <p class="text-muted">
                        Especialistas em contratos premiados para aquisição de veículos desde 2002.
                    </p>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0"> <!-- Coluna para links rápidos -->
                    <h5 class="mb-3">Links Rápidos</h5> <!-- Título da coluna -->
                    <ul class="list-unstyled">
                        <li><a href="<?php echo url('/'); ?>" class="nav-link p-0 text-muted">Início</a></li> <!-- Link "Início" -->
                        <li><a href="<?php echo url('index.php?route=simulador'); ?>" class="nav-link p-0 text-muted">Simulador</a></li> <!-- Link "Simulador" -->
                        <?php if (isLoggedIn()): ?>
                        <li><a href="<?php echo url('index.php?route=dashboard'); ?>" class="nav-link p-0 text-muted">Meu Painel</a></li> <!-- Link "Meu Painel" -->
                        <?php else: ?>
                        <li><a href="<?php echo url('index.php?route=login'); ?>" class="nav-link p-0 text-muted">Entrar</a></li> <!-- Link "Entrar" -->
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-lg-4"> <!-- Coluna para informações de contato -->
                    <h5 class="mb-3">Contato</h5> <!-- Título da coluna -->
                    <ul class="list-unstyled">
                        <li class="mb-2"> <!-- Item de contato: Endereço -->
                            <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                            <span class="text-muted">Rua Exemplo, 123</span>
                        </li>
                        <li class="mb-2"> <!-- Item de contato: Telefone -->
                            <i class="fas fa-phone me-2 text-muted"></i>
                            <span class="text-muted">(11) 1234-5678</span>
                        </li>
                        <li class="mb-2"> <!-- Item de contato: Email -->
                            <i class="fas fa-envelope me-2 text-muted"></i>
                            <span class="text-muted">contato@concamp.com.br</span>
                        </li>
                    </ul>
                </div>
            </div>
            <hr class="my-4"> <!-- Linha horizontal separadora -->
            <div class="footer-bottom d-flex justify-content-between align-items-center"> <!-- Container inferior do rodapé com alinhamento flexível -->
                <p class="text-muted mb-0 small">
                    © <?php echo date('Y'); ?> ConCamp. Todos os direitos reservados.
                </p> <!-- Direitos autorais -->
                <div class="footer-social-icons"> <!-- Container para ícones sociais -->
                    <a href="#" class="text-muted me-3" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a> <!-- Ícone do Facebook -->
                    <a href="#" class="text-muted me-3" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a> <!-- Ícone do Instagram -->
                    <a href="#" class="text-muted" aria-label="WhatsApp">
                        <i class="fab fa-whatsapp"></i>
                    </a> <!-- Ícone do WhatsApp -->
                </div>
            </div>
        </div>
    </footer>
<?php endif; ?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?php echo url('/assets/js/app.js'); ?>"></script>

<?php if ($is_dashboard): ?>
<!-- Configurações JavaScript -->
<script>
    // Definir constantes do sistema
    const APP_URL = '<?php echo APP_URL; ?>';
</script>

<!-- Dashboard JS -->
<script src="<?php echo url('/assets/js/dashboard.js'); ?>"></script>

<!-- Notifications JS -->
<script src="<?php echo url('/assets/js/notifications.js'); ?>"></script>

<!-- ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php endif; ?>

<?php if ($is_logged_in): ?>
<!-- Script para o menu móvel -->
<script>
// Adicionar classe ativa para o menu móvel baseado na rota
document.addEventListener('DOMContentLoaded', function() {
    // Detectar menu móvel
    const mobileMenu = document.querySelector('.mobile-bottom-menu');
    if (!mobileMenu) return;

    // Obter a rota atual
    const currentRoute = '<?php echo $current_route ?? ($route ?? "home"); ?>';

    // Destacar o item ativo no menu móvel
    const menuItems = mobileMenu.querySelectorAll('.mobile-bottom-menu-item');
    menuItems.forEach(item => {
        // Verificar se este é o item ativo baseado na URL atual
        if (item.href && item.href.includes(currentRoute)) {
            item.classList.add('active');
        }

        // Verificar para leads e lead-detail
        if ((currentRoute === 'leads' || currentRoute === 'lead-detail') &&
            item.href && item.href.includes('route=leads')) {
            item.classList.add('active');
        }

        // Verificar para rotas admin
        if (currentRoute.startsWith('admin-') &&
            item.href && item.href.includes('admin-settings')) {
            item.classList.add('active');
        }
    });
});
</script>
<?php endif; ?>

<!-- PWA Script -->
<script src="<?php echo url('/assets/js/pwa.js'); ?>"></script>

<?php if (isset($extra_js)): ?>
<!-- Extra JS -->
<?php echo $extra_js; ?>
<?php endif; ?>

</body>
</html>