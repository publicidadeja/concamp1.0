<?php
$page_title = "Detalhes do Lead";
$body_class = "lead-detail-page";

// Obter ID do lead
$lead_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$lead_id) {
    echo '<div class="alert alert-danger">ID do lead não fornecido.</div>';
    return;
}

// Obter dados do lead
$lead = getLeadById($lead_id);

if (!$lead) {
    echo '<div class="alert alert-danger">Lead não encontrado.</div>';
    return;
}

// Obter usuário atual
$current_user = getCurrentUser();
$user_id = $current_user['id'];
$is_admin = isAdmin();

// Verificar permissão (apenas admin ou vendedor atribuído pode ver)
if (!$is_admin && $lead['seller_id'] != $user_id) {
    echo '<div class="alert alert-danger">Você não tem permissão para visualizar este lead.</div>';
    return;
}

// Obter página atual da URL
$timeline_page = isset($_GET['timeline_page']) ? intval($_GET['timeline_page']) : 1;
$messages_page = isset($_GET['messages_page']) ? intval($_GET['messages_page']) : 1;

// Definir itens por página
$items_per_page = 10;

// Obter follow-ups e mensagens com paginação
$follow_up_data = getLeadFollowUps($lead_id, $timeline_page, $items_per_page);
$message_data = getLeadMessages($lead_id, $messages_page, $items_per_page);

// Extrair os arrays para uso na view
$follow_ups = $follow_up_data['follow_ups'];
$messages = $message_data['messages'];

// Obter vendedores para atribuição
$sellers = getUsersByRole('seller');

// Obter templates de mensagens
$message_templates = getMessageTemplates();

// Formatar nome do lead para iniciais
$initials = '';
$name_parts = explode(' ', $lead['name']);
if (count($name_parts) >= 2) {
    $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[count($name_parts) - 1], 0, 1));
} else {
    $initials = strtoupper(substr($lead['name'], 0, 2));
}

// Formatar CSRF token
$csrf_token = createCsrfToken();

// Definir título da página com nome do lead
$page_title = "Lead: " . $lead['name'];
?>

<div class="lead-detail-header">
    <div class="lead-avatar">
        <?php echo $initials; ?>
    </div>
    <div class="lead-info">
        <h4><?php echo sanitize($lead['name']); ?></h4>
        <p class="text-muted">
            <i class="fas fa-phone me-1"></i> <?php echo sanitize($lead['phone']); ?>
            <?php if (!empty($lead['email'])): ?>
                <span class="ms-3"><i class="fas fa-envelope me-1"></i> <?php echo sanitize($lead['email']); ?></span>
            <?php endif; ?>
            <span class="ms-3"><i class="fas fa-map-marker-alt me-1"></i> <?php echo sanitize($lead['city']); ?>/<?php echo sanitize($lead['state']); ?></span>
        </p>
    </div>
</div>

<div class="lead-actions">
    <a href="https://wa.me/<?php echo $lead['phone']; ?>" target="_blank" class="btn btn-success">
        <i class="fab fa-whatsapp me-2"></i> WhatsApp
    </a>
    
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendMessageModal">
        <i class="fas fa-paper-plane me-2"></i> Enviar Mensagem
    </button>
    
    <?php if (empty($lead['seller_id']) || $is_admin): ?>
    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#assignSellerModal">
        <i class="fas fa-user-plus me-2"></i> Atribuir Vendedor
    </button>
    <?php endif; ?>
    
    <div class="dropdown ms-auto">
        <button class="btn btn-secondary dropdown-toggle" type="button" id="statusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            Status: 
            <span class="badge badge-<?php echo $lead['status']; ?>">
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
        </button>
        <ul class="dropdown-menu" aria-labelledby="statusDropdown">
            <li><a class="dropdown-item update-status-btn" href="#" data-lead-id="<?php echo $lead['id']; ?>" data-status="new">Novo</a></li>
            <li><a class="dropdown-item update-status-btn" href="#" data-lead-id="<?php echo $lead['id']; ?>" data-status="contacted">Contatado</a></li>
            <li><a class="dropdown-item update-status-btn" href="#" data-lead-id="<?php echo $lead['id']; ?>" data-status="negotiating">Negociando</a></li>
            <li><a class="dropdown-item update-status-btn" href="#" data-lead-id="<?php echo $lead['id']; ?>" data-status="converted">Convertido</a></li>
            <li><a class="dropdown-item update-status-btn" href="#" data-lead-id="<?php echo $lead['id']; ?>" data-status="lost">Perdido</a></li>
        </ul>
    </div>
</div>

<div class="row">
    <!-- Coluna da esquerda -->
    <div class="col-lg-4 mb-4">
        <!-- Informações do Lead -->
        <div class="lead-detail-section">
            <h5>Informações do Lead</h5>
            
            <div class="detail-item">
                <div class="label">Data de Criação</div>
                <div class="value"><?php echo formatDateTime($lead['created_at']); ?></div>
            </div>
            
            <div class="detail-item">
                <div class="label">Vendedor Responsável</div>
                <div class="value" id="lead-seller-info">
                    <?php if (!empty($lead['seller_name'])): ?>
                        <?php echo sanitize($lead['seller_name']); ?>
                    <?php else: ?>
                        <span class="text-muted">Não atribuído</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="detail-item">
                <div class="label">Status</div>
                <div class="value">
                    <span class="badge badge-<?php echo $lead['status']; ?>">
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
                </div>
            </div>
        </div>
        
        <!-- Informações do Plano -->
        <div class="lead-detail-section">
            <h5>Detalhes do Plano</h5>
            
            <div class="detail-item">
                <div class="label">Tipo de Veículo</div>
                <div class="value">
                    <?php if ($lead['plan_type'] == 'car'): ?>
                        <span class="badge bg-primary">Carro</span>
                    <?php else: ?>
                        <span class="badge bg-info">Moto</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($lead['plan_model'])): ?>
            <div class="detail-item">
                <div class="label">Modelo</div>
                <div class="value"><?php echo sanitize($lead['plan_model']); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="detail-item">
                <div class="label">Valor do Crédito</div>
                <div class="value">R$ <?php echo formatCurrency($lead['plan_credit']); ?></div>
            </div>
            
            <div class="detail-item">
                <div class="label">Prazo</div>
                <div class="value"><?php echo $lead['plan_term']; ?> meses</div>
            </div>
            
            <div class="detail-item">
                <div class="label">Primeira Parcela</div>
                <div class="value">R$ <?php echo formatCurrency($lead['first_installment']); ?></div>
            </div>
            
            <div class="detail-item">
                <div class="label">Demais Parcelas</div>
                <div class="value">R$ <?php echo formatCurrency($lead['other_installments']); ?></div>
            </div>
            
            <div class="detail-item">
                <div class="label">Total a Pagar</div>
                <div class="value">R$ <?php echo formatCurrency(calculateTotalValue($lead['first_installment'], $lead['other_installments'], $lead['plan_term'])); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Coluna da direita -->
    <div class="col-lg-8">
        <!-- Abas para Follow-ups, Tarefas e Mensagens -->
        <?php 
        // Determinar qual aba deve estar ativa, com base no parâmetro da URL ou por padrão
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'timeline';
        ?>
        
        <ul class="nav nav-tabs" id="leadTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab === 'timeline' ? 'active' : ''; ?>" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline" type="button" role="tab" aria-controls="timeline" aria-selected="<?php echo $active_tab === 'timeline' ? 'true' : 'false'; ?>">
                    Timeline
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab === 'messages' ? 'active' : ''; ?>" id="messages-tab" data-bs-toggle="tab" data-bs-target="#messages" type="button" role="tab" aria-controls="messages" aria-selected="<?php echo $active_tab === 'messages' ? 'true' : 'false'; ?>">
                    Mensagens
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab === 'notes' ? 'active' : ''; ?>" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab" aria-controls="notes" aria-selected="<?php echo $active_tab === 'notes' ? 'true' : 'false'; ?>">
                    Adicionar Nota/Tarefa
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="leadTabsContent">
            <!-- Timeline Tab -->
            <div class="tab-pane fade <?php echo $active_tab === 'timeline' ? 'show active' : ''; ?>" id="timeline" role="tabpanel" aria-labelledby="timeline-tab">
                <div class="lead-detail-section">
                    <?php if ($follow_up_data['total'] > 0): ?>
                    <div class="items-count">
                        Mostrando <?php echo count($follow_ups); ?> de <?php echo $follow_up_data['total']; ?> registros 
                        (Página <?php echo $timeline_page; ?> de <?php echo $follow_up_data['total_pages']; ?>)
                    </div>
                    <?php endif; ?>
                    
                    <div class="timeline">
                        <?php if (empty($follow_ups)): ?>
                            <div class="alert alert-info">Nenhum registro na timeline.</div>
                        <?php else: ?>
                            <?php foreach ($follow_ups as $item): ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <h6 class="timeline-title">
                                                <?php 
                                                switch ($item['type']) {
                                                    case 'note': echo 'Nota'; break;
                                                    case 'task': 
                                                        echo 'Tarefa';
                                                        if ($item['status'] === 'completed') {
                                                            echo ' <span class="badge bg-success">Concluída</span>';
                                                        } else {
                                                            echo ' <span class="badge bg-warning text-dark">Pendente</span>';
                                                        }
                                                        break;
                                                    case 'reminder': echo 'Lembrete'; break;
                                                    default: echo ucfirst($item['type']);
                                                }
                                                ?>
                                            </h6>
                                            <span class="timeline-date"><?php echo formatDateTime($item['created_at']); ?></span>
                                        </div>
                                        <div class="timeline-body">
                                            <?php echo nl2br(sanitize($item['content'])); ?>
                                            
                                            <?php if ($item['type'] === 'task' && !empty($item['due_date'])): ?>
                                                <div class="mt-2">
                                                    <i class="far fa-calendar-alt me-1"></i> Data limite: <?php echo formatDate($item['due_date']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="timeline-footer">
                                            Por: <?php echo sanitize($item['user_name']); ?>
                                            
                                            <?php if ($item['type'] === 'task' && $item['status'] !== 'completed'): ?>
                                                <button class="btn btn-sm btn-success float-end complete-task-btn" data-task-id="<?php echo $item['id']; ?>">
                                                    <i class="fas fa-check me-1"></i> Concluir
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if ($follow_up_data['total_pages'] > 1): ?>
                        <!-- Paginação para Timeline -->
                        <nav aria-label="Navegação de timeline" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($timeline_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?route=lead-detail&id=<?php echo $lead_id; ?>&timeline_page=<?php echo $timeline_page - 1; ?>&messages_page=<?php echo $messages_page; ?>&tab=timeline" aria-label="Anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $follow_up_data['total_pages']; $i++): ?>
                                <li class="page-item <?php echo $i === $timeline_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?route=lead-detail&id=<?php echo $lead_id; ?>&timeline_page=<?php echo $i; ?>&messages_page=<?php echo $messages_page; ?>&tab=timeline">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($timeline_page < $follow_up_data['total_pages']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?route=lead-detail&id=<?php echo $lead_id; ?>&timeline_page=<?php echo $timeline_page + 1; ?>&messages_page=<?php echo $messages_page; ?>&tab=timeline" aria-label="Próximo">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Messages Tab -->
            <div class="tab-pane fade <?php echo $active_tab === 'messages' ? 'show active' : ''; ?>" id="messages" role="tabpanel" aria-labelledby="messages-tab">
                <div class="lead-detail-section">
                    <?php if ($message_data['total'] > 0): ?>
                    <div class="items-count">
                        Mostrando <?php echo count($messages); ?> de <?php echo $message_data['total']; ?> mensagens
                        (Página <?php echo $messages_page; ?> de <?php echo $message_data['total_pages']; ?>)
                    </div>
                    <?php endif; ?>
                    
                    <div id="message-history">
                        <?php if (empty($messages)): ?>
                            <div class="alert alert-info">Nenhuma mensagem enviada.</div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="message-item mb-3 p-3 bg-light rounded">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-bold">Enviado por: <?php echo sanitize($message['user_name']); ?></span>
                                        <span class="text-muted small"><?php echo formatDateTime($message['created_at']); ?></span>
                                    </div>
                                    <div class="message-content">
                                        <?php echo nl2br(sanitize($message['message'])); ?>
                                    </div>
                                    <?php if (!empty($message['media_url'])): ?>
                                        <div class="message-media mt-2">
                                            <a href="<?php echo sanitize($message['media_url']); ?>" target="_blank" class="text-primary">
                                                <i class="fas fa-paperclip me-1"></i> Ver anexo
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if ($message_data['total_pages'] > 1): ?>
                        <!-- Paginação para Mensagens -->
                        <nav aria-label="Navegação de mensagens" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($messages_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?route=lead-detail&id=<?php echo $lead_id; ?>&timeline_page=<?php echo $timeline_page; ?>&messages_page=<?php echo $messages_page - 1; ?>&tab=messages" aria-label="Anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $message_data['total_pages']; $i++): ?>
                                <li class="page-item <?php echo $i === $messages_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?route=lead-detail&id=<?php echo $lead_id; ?>&timeline_page=<?php echo $timeline_page; ?>&messages_page=<?php echo $i; ?>&tab=messages">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($messages_page < $message_data['total_pages']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?route=lead-detail&id=<?php echo $lead_id; ?>&timeline_page=<?php echo $timeline_page; ?>&messages_page=<?php echo $messages_page + 1; ?>&tab=messages" aria-label="Próximo">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Notes Tab -->
            <div class="tab-pane fade <?php echo $active_tab === 'notes' ? 'show active' : ''; ?>" id="notes" role="tabpanel" aria-labelledby="notes-tab">
                <div class="lead-detail-section">
                    <form id="followUpForm" data-lead-id="<?php echo $lead_id; ?>">
                        <div class="mb-3">
                            <label for="followup_type" class="form-label">Tipo</label>
                            <select class="form-select" id="followup_type" name="followup_type" required>
                                <option value="note">Nota</option>
                                <option value="task">Tarefa</option>
                                <option value="reminder">Lembrete</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="followup_content" class="form-label">Conteúdo</label>
                            <textarea class="form-control" id="followup_content" name="followup_content" rows="4" required></textarea>
                        </div>
                        
                        <div class="mb-3" id="due_date_group" style="display: none;">
                            <label for="due_date" class="form-label">Data Limite</label>
                            <input type="date" class="form-control" id="due_date" name="due_date">
                        </div>
                        
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <button type="submit" class="btn btn-primary">Adicionar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para envio de mensagem -->
<div class="modal fade" id="sendMessageModal" tabindex="-1" aria-labelledby="sendMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sendMessageModalLabel">Enviar Mensagem para <?php echo sanitize($lead['name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php 
                // Verificar se o vendedor tem token de WhatsApp configurado
                $has_whatsapp_token = false;
                
                if (!$is_admin && $user_id == $lead['seller_id']) {
                    // Verificar token do vendedor atual
                    $has_whatsapp_token = !empty($current_user['whatsapp_token']);
                } else if ($is_admin && !empty($lead['seller_id'])) {
                    // Verificar token do vendedor atribuído
                    $seller = getUserById($lead['seller_id']);
                    $has_whatsapp_token = $seller && !empty($seller['whatsapp_token']);
                }
                
                // Exibir aviso se não tiver token configurado
                if (!$has_whatsapp_token):
                ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Atenção:</strong> Não há token de WhatsApp configurado para este vendedor. As mensagens serão apenas registradas no sistema, mas não serão enviadas via WhatsApp.
                    <?php if (!$is_admin): ?>
                    <hr>
                    <p class="mb-0">Configure seu token de WhatsApp na página <a href="<?php echo url('index.php?route=seller-landing-page'); ?>" target="_blank">Minha Landing Page</a>.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <form id="sendMessageForm" data-lead-id="<?php echo $lead_id; ?>">
                    <div class="mb-3">
                        <label for="message_template" class="form-label">Modelo de Mensagem</label>
                        <select class="form-select" id="message_template" name="message_template">
                            <option value="">Selecione um modelo</option>
                            <?php foreach ($message_templates as $template): ?>
                                <option value="<?php echo $template['id']; ?>" data-content="<?php echo htmlspecialchars($template['content']); ?>">
                                    <?php echo sanitize($template['name']); ?> (<?php echo ucfirst($template['category']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message_content" class="form-label">Mensagem</label>
                        <textarea class="form-control" id="message_content" name="message_content" rows="6" required></textarea>
                        <div class="form-text">
                            Você pode usar variáveis como {nome}, {tipo_veiculo}, {valor_credito}, {prazo}, {valor_primeira}, {valor_demais} e {nome_consultor}.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message_media" class="form-label">Mídia (opcional)</label>
                        <input type="file" class="form-control" id="message_media" name="message_media">
                        <div class="form-text">Você pode anexar uma imagem ou PDF (máx. 5MB).</div>
                    </div>
                    
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary">
                            <?php if ($has_whatsapp_token): ?>
                            <i class="fab fa-whatsapp me-2"></i>Enviar via WhatsApp
                            <?php else: ?>
                            <i class="fas fa-save me-2"></i>Registrar Mensagem
                            <?php endif; ?>
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para atribuir vendedor -->
<div class="modal fade" id="assignSellerModal" tabindex="-1" aria-labelledby="assignSellerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignSellerModalLabel">Atribuir Vendedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="assignSellerForm" data-lead-id="<?php echo $lead_id; ?>">
                    <div class="mb-3">
                        <label for="seller_id" class="form-label">Vendedor</label>
                        <select class="form-select" id="seller_id" name="seller_id" required>
                            <option value="">Selecione um vendedor</option>
                            <?php foreach ($sellers as $seller): ?>
                                <option value="<?php echo $seller['id']; ?>" <?php echo ($lead['seller_id'] == $seller['id']) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($seller['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary">Atribuir</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilo para limitar a altura de mensagens longas e adicionar rolagem */
.message-content {
    max-height: 150px;
    overflow-y: auto;
    margin-bottom: 10px;
}

/* Destaque para itens da timeline */
.timeline-item {
    border-left: 3px solid #e9ecef;
    padding-left: 15px;
    margin-bottom: 15px;
}

/* Melhorias na paginação */
.pagination {
    margin-top: 20px;
    margin-bottom: 10px;
}

.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Contador de total de itens */
.items-count {
    text-align: center;
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/ocultar campo de data limite para tarefas
    const followupType = document.getElementById('followup_type');
    const dueDateGroup = document.getElementById('due_date_group');
    
    if (followupType && dueDateGroup) {
        followupType.addEventListener('change', function() {
            dueDateGroup.style.display = this.value === 'task' ? 'block' : 'none';
        });
    }
    
    // Processar variáveis no template de mensagem
    const templateSelect = document.getElementById('message_template');
    const messageContent = document.getElementById('message_content');
    
    if (templateSelect && messageContent) {
        templateSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption && selectedOption.dataset.content) {
                // Obter template original
                let template = selectedOption.dataset.content;
                
                // Substituir variáveis
                template = template.replace(/{nome}/g, '<?php echo addslashes($lead['name']); ?>');
                template = template.replace(/{tipo_veiculo}/g, '<?php echo $lead['plan_type'] == 'car' ? 'Carro' : 'Moto'; ?>');
                template = template.replace(/{valor_credito}/g, 'R$ <?php echo formatCurrency($lead['plan_credit']); ?>');
                template = template.replace(/{prazo}/g, '<?php echo $lead['plan_term']; ?> meses');
                template = template.replace(/{valor_primeira}/g, 'R$ <?php echo formatCurrency($lead['first_installment']); ?>');
                template = template.replace(/{valor_demais}/g, 'R$ <?php echo formatCurrency($lead['other_installments']); ?>');
                template = template.replace(/{nome_consultor}/g, '<?php echo addslashes($current_user['name']); ?>');
                
                // Atualizar campo de mensagem
                messageContent.value = template;
            }
        });
    }
});
</script>
