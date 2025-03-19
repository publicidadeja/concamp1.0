<?php
$page_title = "Dashboard";
$body_class = "dashboard-page";

// Obter usuário atual
$current_user = getCurrentUser();
$user_id = $current_user['id'];
$is_admin = isAdmin();

// Obter estatísticas para o dashboard
$stats = getDashboardStats($user_id, $is_admin);

// Formatar dados para os gráficos
$status_stats_json = json_encode($stats['status_stats'] ?? []);
$type_stats_json = json_encode($stats['type_stats'] ?? []);
$recent_stats_json = json_encode($stats['recent_stats'] ?? []);

// Obter leads recentes (últimos 5)
$recent_leads = getLeads(
    $is_admin ? [] : ['seller_id' => $user_id],
    1,
    5
)['leads'];

// Obter tarefas pendentes
$pending_tasks = getUserPendingTasks($user_id);
?>

<div class="row mb-4">
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="stats-card primary">
            <div class="stats-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stats-number">
                <?php echo array_sum($stats['status_stats'] ?? []); ?>
            </div>
            <div class="stats-title">Total de Leads</div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="stats-card success">
            <div class="stats-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stats-number">
                <?php echo $stats['status_stats']['converted'] ?? 0; ?>
            </div>
            <div class="stats-title">Convertidos</div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="stats-card warning">
            <div class="stats-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stats-number">
                <?php echo $stats['tasks_count'] ?? 0; ?>
            </div>
            <div class="stats-title">Tarefas Pendentes</div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="stats-card info">
            <div class="stats-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stats-number">
                <?php 
                $total = array_sum($stats['status_stats'] ?? []);
                $converted = $stats['status_stats']['converted'] ?? 0;
                echo $total > 0 ? round(($converted / $total) * 100) : 0; 
                ?>%
            </div>
            <div class="stats-title">Taxa de Conversão</div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-8 mb-4">
        <div class="chart-container">
            <h5>Leads Recentes (Últimos 7 dias)</h5>
            <canvas id="recentLeadsChart" height="100" data-stats='<?php echo $recent_stats_json; ?>'></canvas>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="chart-container">
            <h5>Leads por Status</h5>
            <canvas id="leadsStatusChart" height="200" data-stats='<?php echo $status_stats_json; ?>'></canvas>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-6 mb-4">
        <div class="chart-container">
            <h5>Leads por Tipo</h5>
            <canvas id="leadsTypeChart" height="200" data-stats='<?php echo $type_stats_json; ?>'></canvas>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="table-container">
            <div class="table-title">
                <h5>Tarefas Pendentes</h5>
                <a href="index.php?route=tasks" class="btn btn-sm btn-primary">Ver Todas</a>
            </div>
            
            <?php if (empty($pending_tasks)): ?>
                <div class="alert alert-info">Nenhuma tarefa pendente.</div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($pending_tasks as $task): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo sanitize($task['content']); ?></h6>
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i> <?php echo sanitize($task['lead_name']); ?>
                                    <?php if (!empty($task['due_date'])): ?>
                                        <span class="ms-2"><i class="far fa-calendar-alt me-1"></i> <?php echo formatDate($task['due_date']); ?></span>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <button class="btn btn-sm btn-success complete-task-btn" data-task-id="<?php echo $task['id']; ?>">
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="table-container">
            <div class="table-title">
                <h5>Leads Recentes</h5>
                <a href="index.php?route=leads" class="btn btn-sm btn-primary">Ver Todos</a>
            </div>
            
            <?php if (empty($recent_leads)): ?>
                <div class="alert alert-info">Nenhum lead encontrado.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Contato</th>
                                <th>Tipo</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_leads as $lead): ?>
                                <tr>
                                    <td><?php echo sanitize($lead['name']); ?></td>
                                    <td>
                                        <i class="fas fa-phone me-1"></i> <?php echo sanitize($lead['phone']); ?>
                                    </td>
                                    <td>
                                        <?php if ($lead['plan_type'] == 'car'): ?>
                                            <span class="badge bg-primary">Carro</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Moto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>R$ <?php echo formatCurrency($lead['plan_credit']); ?></td>
                                    <td>
                                        <span class="badge lead-status-badge badge-<?php echo $lead['status']; ?>" data-lead-id="<?php echo $lead['id']; ?>">
                                            <?php 
                                            switch ($lead['status']) {
                                                case 'new':
                                                    echo 'Novo';
                                                    break;
                                                case 'contacted':
                                                    echo 'Contatado';
                                                    break;
                                                case 'negotiating':
                                                    echo 'Negociando';
                                                    break;
                                                case 'converted':
                                                    echo 'Convertido';
                                                    break;
                                                case 'lost':
                                                    echo 'Perdido';
                                                    break;
                                                default:
                                                    echo $lead['status'];
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($lead['created_at']); ?></td>
                                    <td>
                                        <div class="table-action">
                                            <a href="index.php?route=lead-detail&id=<?php echo $lead['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="Ver detalhes">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="https://wa.me/<?php echo $lead['phone']; ?>" target="_blank" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="WhatsApp">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
