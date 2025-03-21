<?php
$page_title = "Gerenciar Leads";
$body_class = "leads-page";

// Obter usuário atual
$current_user = getCurrentUser();
$user_id = $current_user['id'];
$is_admin = isAdmin();

// Paginação
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 15;

// Filtros
$filters = [];

// Filtrar por status
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = sanitize($_GET['status']);
}

// Filtrar por tipo de plano
if (isset($_GET['plan_type']) && !empty($_GET['plan_type'])) {
    $filters['plan_type'] = sanitize($_GET['plan_type']);
}

// Filtrar por vendedor (apenas para admin)
if ($is_admin && isset($_GET['seller_id']) && !empty($_GET['seller_id'])) {
    $filters['seller_id'] = intval($_GET['seller_id']);
}

// Pesquisa
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = sanitize($_GET['search']);
}

// Filtrar por data
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $filters['date_from'] = sanitize($_GET['date_from']);
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $filters['date_to'] = sanitize($_GET['date_to']);
}

// Se não for admin, mostrar apenas leads do vendedor
if (!$is_admin) {
    $filters['seller_id'] = $user_id;
}

// Obter leads com paginação
$leads_data = getLeads($filters, $page, $per_page);
$leads = $leads_data['leads'];
$pages = $leads_data['pages'];

// Obter vendedores (apenas para admin)
$sellers = $is_admin ? getUsersByRole('seller') : [];

?>

<div class="row mb-4">
    <div class="col-md-6">
        <h4>Total de leads: <?php echo $leads_data['total']; ?></h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
            <i class="fas fa-filter me-2"></i> Filtrar
        </a>
        
        <a href="index.php?route=leads" class="btn btn-outline-secondary ms-2">
            <i class="fas fa-sync-alt me-2"></i> Limpar Filtros
        </a>
    </div>
</div>

<!-- Filtros ativos -->
<?php if (!empty($filters)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <strong>Filtros ativos:</strong>
            
            <?php if (isset($filters['status'])): ?>
                <span class="badge bg-secondary me-2">Status: 
                    <?php 
                    switch ($filters['status']) {
                        case 'new': echo 'Novo'; break;
                        case 'contacted': echo 'Contatado'; break;
                        case 'negotiating': echo 'Negociando'; break;
                        case 'converted': echo 'Convertido'; break;
                        case 'lost': echo 'Perdido'; break;
                        default: echo $filters['status'];
                    }
                    ?>
                </span>
            <?php endif; ?>
            
            <?php if (isset($filters['plan_type'])): ?>
                <span class="badge bg-secondary me-2">Tipo: 
                    <?php echo $filters['plan_type'] == 'car' ? 'Carro' : 'Moto'; ?>
                </span>
            <?php endif; ?>
            
            <?php if (isset($filters['seller_id']) && $is_admin): ?>
                <?php 
                $seller_name = '';
                foreach ($sellers as $seller) {
                    if ($seller['id'] == $filters['seller_id']) {
                        $seller_name = $seller['name'];
                        break;
                    }
                }
                ?>
                <span class="badge bg-secondary me-2">Vendedor: <?php echo $seller_name; ?></span>
            <?php endif; ?>
            
            <?php if (isset($filters['search'])): ?>
                <span class="badge bg-secondary me-2">Pesquisa: <?php echo $filters['search']; ?></span>
            <?php endif; ?>
            
            <?php if (isset($filters['date_from'])): ?>
                <span class="badge bg-secondary me-2">De: <?php echo $filters['date_from']; ?></span>
            <?php endif; ?>
            
            <?php if (isset($filters['date_to'])): ?>
                <span class="badge bg-secondary me-2">Até: <?php echo $filters['date_to']; ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tabela de Leads -->
<div class="table-container">
    <?php if (empty($leads)): ?>
        <div class="alert alert-info">Nenhum lead encontrado.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover" id="leadsTable">
                <thead>
                    <tr>
                        <th>ID</th>
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
                    <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td><?php echo $lead['id']; ?></td>
                            <td><?php echo sanitize($lead['name']); ?></td>
                            <td>
                                <i class="fas fa-phone me-1"></i> <?php echo sanitize($lead['phone']); ?>
                                <?php if (!empty($lead['email'])): ?>
                                    <br>
                                    <i class="fas fa-envelope me-1"></i> <?php echo sanitize($lead['email']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($lead['plan_type'] == 'car'): ?>
                                    <span class="badge bg-primary">Carro</span>
                                <?php else: ?>
                                    <span class="badge bg-info">Moto</span>
                                <?php endif; ?>
                                <div class="small text-muted mt-1">
                                    <?php echo $lead['plan_term']; ?> meses
                                </div>
                            </td>
                            <td>
                                R$ <?php echo formatCurrency($lead['plan_credit']); ?>
                                <div class="small text-muted">
                                    <?php if ($lead['plan_model']): echo sanitize($lead['plan_model']); endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <span class="badge bg-<?php 
                                    switch ($lead['status']) {
                                        case 'new': echo 'primary'; break;
                                        case 'contacted': echo 'info'; break;
                                        case 'negotiating': echo 'warning'; break;
                                        case 'converted': echo 'success'; break;
                                        case 'lost': echo 'danger'; break;
                                        default: echo 'secondary';
                                    }
                                    ?>" data-lead-id="<?php echo $lead['id']; ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <?php 
                                        switch ($lead['status']) {
                                            case 'new': echo 'Novo'; break;
                                            case 'contacted': echo 'Contatado'; break;
                                            case 'negotiating': echo 'Negociando'; break;
                                            case 'converted': echo 'Convertido'; break;
                                            case 'lost': echo 'Perdido'; break;
                                            default: echo $lead['status'];
                                        }
                                        ?>
                                    </span>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item update-status-btn" href="#" data-lead-id="<?php echo $lead['id']; ?>" data-status="new">Novo</a></li>
                                        <li><a class="dropdown-item update-status-btn" href="#" data-lead-id="<?php echo $lead['id']; ?>" data-status="contacted">Contatado</a></li>
                                        <li><a class="dropdown-item update-status-btn" href="#" data-lead-id="<?php echo $lead['id']; ?>" data-status="negotiating">Negociando</a></li>
                                        <li><a class="dropdown-item update-status-btn" href="#" data-lead-id="<?php echo $lead['id']; ?>" data-status="converted">Convertido</a></li>
                                        <li><a class="dropdown-item update-status-btn" href="#" data-lead-id="<?php echo $lead['id']; ?>" data-status="lost">Perdido</a></li>
                                    </ul>
                                </div>
                                
                                <?php if ($lead['seller_name']): ?>
                                    <div class="small text-muted mt-1">
                                        <i class="fas fa-user-tie me-1"></i> <?php echo sanitize($lead['seller_name']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatDate($lead['created_at']); ?></td>
                            <td>
                                <div class="d-flex gap-1">
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
        
        <!-- Paginação -->
        <?php if ($pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="index.php?route=leads&page=<?php echo $page - 1; ?><?php echo http_build_query(array_filter($filters)); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="index.php?route=leads&page=<?php echo $i; ?><?php echo http_build_query(array_filter($filters)); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="index.php?route=leads&page=<?php echo $page + 1; ?><?php echo http_build_query(array_filter($filters)); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal de Filtros -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">Filtrar Leads</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="index.php" method="get" class="filter-form" data-route="leads">
                    <input type="hidden" name="route" value="leads">
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Todos</option>
                            <option value="new" <?php echo (isset($filters['status']) && $filters['status'] === 'new') ? 'selected' : ''; ?>>Novo</option>
                            <option value="contacted" <?php echo (isset($filters['status']) && $filters['status'] === 'contacted') ? 'selected' : ''; ?>>Contatado</option>
                            <option value="negotiating" <?php echo (isset($filters['status']) && $filters['status'] === 'negotiating') ? 'selected' : ''; ?>>Negociando</option>
                            <option value="converted" <?php echo (isset($filters['status']) && $filters['status'] === 'converted') ? 'selected' : ''; ?>>Convertido</option>
                            <option value="lost" <?php echo (isset($filters['status']) && $filters['status'] === 'lost') ? 'selected' : ''; ?>>Perdido</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="plan_type" class="form-label">Tipo de Veículo</label>
                        <select class="form-select" id="plan_type" name="plan_type">
                            <option value="">Todos</option>
                            <option value="car" <?php echo (isset($filters['plan_type']) && $filters['plan_type'] === 'car') ? 'selected' : ''; ?>>Carro</option>
                            <option value="motorcycle" <?php echo (isset($filters['plan_type']) && $filters['plan_type'] === 'motorcycle') ? 'selected' : ''; ?>>Moto</option>
                        </select>
                    </div>
                    
                    <?php if ($is_admin && !empty($sellers)): ?>
                    <div class="mb-3">
                        <label for="seller_id" class="form-label">Vendedor</label>
                        <select class="form-select" id="seller_id" name="seller_id">
                            <option value="">Todos</option>
                            <?php foreach ($sellers as $seller): ?>
                                <option value="<?php echo $seller['id']; ?>" <?php echo (isset($filters['seller_id']) && $filters['seller_id'] == $seller['id']) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($seller['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="search" class="form-label">Pesquisar</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Nome, e-mail ou telefone" value="<?php echo isset($filters['search']) ? $filters['search'] : ''; ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_from" class="form-label">Data Inicial</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo isset($filters['date_from']) ? $filters['date_from'] : ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="date_to" class="form-label">Data Final</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo isset($filters['date_to']) ? $filters['date_to'] : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
