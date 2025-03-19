<?php
// Verificar se é página do painel
$current_route = $_GET['route'] ?? 'home';
$is_dashboard = !in_array($current_route, ['home', 'login', 'register', 'forgot-password', 'reset-password', 'simulador', 'process-simulation', '404']);
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

<?php else: ?>
    </main>
    
    <!-- Site Footer -->
    <footer class="site-footer py-4 mt-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>ConCamp</h5>
                    <p class="text-muted">Especialistas em contratos premiados para aquisição de veículos desde 2002.</p>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Links Rápidos</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo url('/'); ?>" class="text-decoration-none">Início</a></li>
                        <li><a href="<?php echo url('index.php?route=simulador'); ?>" class="text-decoration-none">Simulador</a></li>
                        <?php if (isLoggedIn()): ?>
                        <li><a href="<?php echo url('index.php?route=dashboard'); ?>" class="text-decoration-none">Meu Painel</a></li>
                        <?php else: ?>
                        <li><a href="<?php echo url('index.php?route=login'); ?>" class="text-decoration-none">Entrar</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contato</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt me-2"></i> Endereço: Rua Exemplo, 123</li>
                        <li><i class="fas fa-phone me-2"></i> Telefone: (11) 1234-5678</li>
                        <li><i class="fas fa-envelope me-2"></i> Email: contato@concamp.com.br</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <p class="small text-muted mb-0">&copy; <?php echo date('Y'); ?> ConCamp. Todos os direitos reservados.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item">
                            <a href="#" class="text-muted">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="#" class="text-muted">
                                <i class="fab fa-instagram"></i>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="#" class="text-muted">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </li>
                    </ul>
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
<!-- Dashboard JS -->
<script src="<?php echo url('/assets/js/dashboard.js'); ?>"></script>

<!-- ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php endif; ?>

<?php if (isset($extra_js)): ?>
<!-- Extra JS -->
<?php echo $extra_js; ?>
<?php endif; ?>

</body>
</html>
