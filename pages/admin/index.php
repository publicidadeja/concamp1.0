<?php
/**
 * Página principal do painel administrativo
 */

// Título da página
$page_title = 'Painel Administrativo';

// Verificar permissão
if (!isAdmin()) {
    include_once __DIR__ . '/../access-denied.php';
    exit;
}

// Obter estatísticas
$conn = getConnection();

// Total de usuários
$stmt = $conn->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$total_users = $stmt->fetchColumn();

// Total de leads
$stmt = $conn->prepare("SELECT COUNT(*) FROM leads");
$stmt->execute();
$total_leads = $stmt->fetchColumn();

// Total de planos
$stmt = $conn->prepare("SELECT COUNT(*) FROM plans");
$stmt->execute();
$total_plans = $stmt->fetchColumn();

// Total de mensagens enviadas
$stmt = $conn->prepare("SELECT COUNT(*) FROM sent_messages");
$stmt->execute();
$total_messages = $stmt->fetchColumn();

// Leads por status
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM leads GROUP BY status");
$stmt->execute();
$leads_by_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Bem-vindo ao Painel Administrativo</h5>
                <p class="card-text">
                    Aqui você pode gerenciar usuários, planos, configurações e ver relatórios do sistema.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Total de Usuários -->
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card shadow-sm border-left-primary h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Usuários</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light">
                <a href="<?php echo url('index.php?route=admin-users'); ?>" class="text-primary">
                    Ver detalhes <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Total de Leads -->
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card shadow-sm border-left-success h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Leads</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_leads; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light">
                <a href="<?php echo url('index.php?route=leads'); ?>" class="text-success">
                    Ver detalhes <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Total de Planos -->
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card shadow-sm border-left-warning h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Planos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_plans; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-list-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light">
                <a href="<?php echo url('index.php?route=admin-plans'); ?>" class="text-warning">
                    Ver detalhes <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Total de Mensagens -->
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card shadow-sm border-left-info h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Mensagens</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_messages; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-comments fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light">
                <a href="#" class="text-info">
                    Ver detalhes <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Leads por Status -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">Leads por Status</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Quantidade</th>
                                <th>Percentual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $status_labels = [
                                'new' => 'Novo',
                                'contacted' => 'Contactado',
                                'negotiating' => 'Em Negociação',
                                'converted' => 'Convertido',
                                'lost' => 'Perdido'
                            ];
                            
                            $status_counts = [];
                            foreach ($leads_by_status as $lead_status) {
                                $status_counts[$lead_status['status']] = $lead_status['count'];
                            }
                            
                            foreach ($status_labels as $status_key => $status_label) {
                                $count = $status_counts[$status_key] ?? 0;
                                $percentage = $total_leads > 0 ? round(($count / $total_leads) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo $status_label; ?></td>
                                <td><?php echo $count; ?></td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-<?php echo getStatusColor($status_key); ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $percentage; ?>%" 
                                             aria-valuenow="<?php echo $percentage; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?php echo $percentage; ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Links Rápidos -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">Links Rápidos</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <a href="<?php echo url('index.php?route=admin-users'); ?>" class="btn btn-light btn-block text-left w-100">
                            <i class="fas fa-user-cog mr-2"></i> Gerenciar Usuários
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="<?php echo url('index.php?route=admin-plans'); ?>" class="btn btn-light btn-block text-left w-100">
                            <i class="fas fa-list-alt mr-2"></i> Gerenciar Planos
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="<?php echo url('index.php?route=admin-settings'); ?>" class="btn btn-light btn-block text-left w-100">
                            <i class="fas fa-sliders-h mr-2"></i> Configurações
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="<?php echo url('index.php?route=admin-reports'); ?>" class="btn btn-light btn-block text-left w-100">
                            <i class="fas fa-chart-bar mr-2"></i> Relatórios
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Função auxiliar para mapear status para classes de cor Bootstrap
 */
function getStatusColor($status) {
    switch ($status) {
        case 'new':
            return 'primary';
        case 'contacted':
            return 'info';
        case 'negotiating':
            return 'warning';
        case 'converted':
            return 'success';
        case 'lost':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>
