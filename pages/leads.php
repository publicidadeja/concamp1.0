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

<div class="container-fluid">
    <div class="page-header mb-3">
        <h1>Gerenciar Leads</h1>
    </div>

    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            <p class="lead-count">
                Total de leads: <strong><?php echo $leads_data['total']; ?></strong>
            </p>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="fas fa-filter me-2"></i> Filtrar
                </button>
                <a href="index.php?route=leads" class="btn btn-outline-secondary">
                    <i class="fas fa-sync-alt me-2"></i> Limpar Filtros
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($filters)): ?>
    <div class="filters-active mb-3">
        <div class="alert alert-info rounded-sm mb-0">
            <strong class="me-2"><i class="fas fa-filter"></i> Filtros ativos:</strong>

            <?php if (isset($filters['status'])): ?>
                <span class="badge bg-secondary me-1 filter-badge">
                    Status:
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
                <span class="badge bg-secondary me-1 filter-badge">
                    Tipo:
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
                <span class="badge bg-secondary me-1 filter-badge">
                    Vendedor: <?php echo $seller_name; ?>
                </span>
            <?php endif; ?>

            <?php if (isset($filters['search'])): ?>
                <span class="badge bg-secondary me-1 filter-badge">
                    Pesquisa: <?php echo $filters['search']; ?>
                </span>
            <?php endif; ?>

            <?php if (isset($filters['date_from'])): ?>
                <span class="badge bg-secondary me-1 filter-badge">
                    De: <?php echo formatDate($filters['date_from']); ?>
                </span>
            <?php endif; ?>

            <?php if (isset($filters['date_to'])): ?>
                <span class="badge bg-secondary me-1 filter-badge">
                    Até: <?php echo formatDate($filters['date_to']); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Listagem de Leads em Formato de Cards (Mobile) - INÍCIO -->
    <div class="leads-list-mobile">
        <?php if (empty($leads)): ?>
            <div class="alert alert-info">Nenhum lead encontrado.</div>
        <?php else: ?>
            <ul class="list-unstyled">
                <?php foreach ($leads as $lead): ?>
                    <li class="lead-item">
                        <div class="card shadow-sm rounded-sm mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo sanitize($lead['name']); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <?php if ($lead['plan_type'] == 'car'): ?>
                                        <span class="badge bg-primary me-1">Carro</span>
                                    <?php else: ?>
                                        <span class="badge bg-info me-1">Moto</span>
                                    <?php endif; ?>
                                    <span class="badge status-badge bg-<?php
                                    switch ($lead['status']) {
                                        case 'new': echo 'primary'; break;
                                        case 'contacted': echo 'info'; break;
                                        case 'negotiating': echo 'warning'; break;
                                        case 'converted': echo 'success'; break;
                                        case 'lost': echo 'danger'; break;
                                        default: echo 'secondary';
                                    }
                                    ?>"><?php
                                    switch ($lead['status']) {
                                        case 'new': echo 'Novo'; break;
                                        case 'contacted': echo 'Contatado'; break;
                                        case 'negotiating': echo 'Negociando'; break;
                                        case 'converted': echo 'Convertido'; break;
                                        case 'lost': echo 'Perdido'; break;
                                        default: echo $lead['status'];
                                    }
                                    ?></span>
                                </h6>
                                <p class="card-text">
                                    <i class="fas fa-phone me-1"></i> <?php echo sanitize($lead['phone']); ?><br>
                                    <?php if (!empty($lead['email'])): ?>
                                        <i class="fas fa-envelope me-1"></i> <?php echo sanitize($lead['email']); ?><br>
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        Tipo: <?php echo $lead['plan_type'] == 'car' ? 'Carro' : 'Moto'; ?>, Termo: <?php echo $lead['plan_term']; ?> meses, Valor: R$ <?php echo formatCurrency($lead['plan_credit']); ?>
                                        <?php if ($lead['plan_model']): ?>, Modelo: <?php echo sanitize($lead['plan_model']); ?><?php endif; ?>
                                        <br>
                                        Data: <?php echo formatDate($lead['created_at']); ?>
                                        <?php if ($lead['seller_name']): ?>
                                            <br>Vendedor: <?php echo sanitize($lead['seller_name']); ?>
                                        <?php endif; ?>
                                    </small>
                                </p>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php?route=lead-detail&id=<?php echo $lead['id']; ?>" class="btn btn-sm btn-info action-button" data-bs-toggle="tooltip" title="Ver detalhes">
                                        <i class="fas fa-eye"></i> Detalhes
                                    </a>
                                    <a href="https://wa.me/<?php echo $lead['phone']; ?>" target="_blank" class="btn btn-sm btn-success action-button" data-bs-toggle="tooltip" title="WhatsApp">
                                        <i class="fab fa-whatsapp"></i> WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <!-- Listagem de Leads em Formato de Cards (Mobile) - FIM -->

    <!-- Tabela de Leads (Desktop/Tablet) - MANTER, MAS VAMOS ESCONDER EM MOBILE -->
    <div class="table-responsive leads-table-desktop">
        <?php if (empty($leads)): ?>
            <div class="alert alert-info">Nenhum lead encontrado.</div>
        <?php else: ?>
            <table class="table table-hover bg-white rounded shadow-sm">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Contato</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th class="text-center">Ações</th>
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
                                    <span class="badge status-badge bg-<?php // Classe 'status-badge' para estilo CSS
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
                                    <div class="small text-muted mt-1 seller-info">
                                        <i class="fas fa-user-tie me-1"></i> <?php echo sanitize($lead['seller_name']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span class="date-cell"><?php echo formatDate($lead['created_at']); ?></span></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1 action-buttons">
                                    <a href="index.php?route=lead-detail&id=<?php echo $lead['id']; ?>" class="btn btn-sm btn-info action-button" data-bs-toggle="tooltip" title="Ver detalhes">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="https://wa.me/<?php echo $lead['phone']; ?>" target="_blank" class="btn btn-sm btn-success action-button" data-bs-toggle="tooltip" title="WhatsApp">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if ($pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="index.php?route=leads&page=<?php echo $page - 1; ?><?php echo http_build_query(array_filter($filters)); ?>" aria-label="Anterior">
                            <span aria-hidden="true">«</span>
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
                        <a class="page-link" href="index.php?route=leads&page=<?php echo $page + 1; ?><?php echo http_build_query(array_filter($filters)); ?>" aria-label="Próximo">
                            <span aria-hidden="true">»</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-sm">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">Filtrar Leads</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="index.php" method="get" class="filter-form" data-route="leads">
                    <input type="hidden" name="route" value="leads">

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select rounded-sm" id="status" name="status">
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
                        <select class="form-select rounded-sm" id="plan_type" name="plan_type">
                            <option value="">Todos</option>
                            <option value="car" <?php echo (isset($filters['plan_type']) && $filters['plan_type'] === 'car') ? 'selected' : ''; ?>>Carro</option>
                            <option value="motorcycle" <?php echo (isset($filters['plan_type']) && $filters['plan_type'] === 'motorcycle') ? 'selected' : ''; ?>>Moto</option>
                        </select>
                    </div>

                    <?php if ($is_admin && !empty($sellers)): ?>
                    <div class="mb-3">
                        <label for="seller_id" class="form-label">Vendedor</label>
                        <select class="form-select rounded-sm" id="seller_id" name="seller_id">
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
                        <input type="text" class="form-control rounded-sm" id="search" name="search" placeholder="Nome, e-mail ou telefone" value="<?php echo isset($filters['search']) ? $filters['search'] : ''; ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_from" class="form-label">Data Inicial</label>
                            <input type="date" class="form-control rounded-sm" id="date_from" name="date_from" value="<?php echo isset($filters['date_from']) ? $filters['date_from'] : ''; ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="date_to" class="form-label">Data Final</label>
                            <input type="date" class="form-control rounded-sm" id="date_to" name="date_to" value="<?php echo isset($filters['date_to']) ? $filters['date_to'] : ''; ?>">
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary rounded-sm">Aplicar Filtros</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>