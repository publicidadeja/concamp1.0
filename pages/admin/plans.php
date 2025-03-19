<?php
/**
 * Gerenciamento de planos
 */

// Título da página
$page_title = 'Gerenciar Planos';

// Verificar permissão
if (!isAdmin()) {
    include_once __DIR__ . '/../access-denied.php';
    exit;
}

// Processar ações
$message = '';
$error = '';

// Função auxiliar para processar os dados do formulário de plano
function processPlanFormData($post_data) {
    return [
        'name' => sanitize($post_data['name'] ?? ''),
        'plan_type' => sanitize($post_data['plan_type'] ?? ''),
        'credit_value' => floatval(str_replace(['.', ','], ['', '.'], $post_data['credit_value'] ?? 0)),
        'term' => intval($post_data['term'] ?? 0),
        'first_installment' => floatval(str_replace(['.', ','], ['', '.'], $post_data['first_installment'] ?? 0)),
        'other_installments' => floatval(str_replace(['.', ','], ['', '.'], $post_data['other_installments'] ?? 0)),
        'admin_fee' => floatval(str_replace(['.', ','], ['', '.'], $post_data['admin_fee'] ?? 0)),
        'active' => (isset($post_data['active']) && $post_data['active'] == '1') ? 1 : 0
    ];
}

// Criar plano
if (isset($_POST['action']) && $_POST['action'] === 'create_plan') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $plan_data = processPlanFormData($_POST);
        
        // Validar dados
        if (empty($plan_data['name']) || empty($plan_data['plan_type']) || 
            $plan_data['credit_value'] <= 0 || $plan_data['term'] <= 0 || 
            $plan_data['first_installment'] <= 0 || $plan_data['other_installments'] <= 0) {
            $error = 'Preencha todos os campos obrigatórios com valores válidos.';
        } else {
            // Inserir no banco de dados
            $conn = getConnection();
            $sql = "INSERT INTO plans (
                name, plan_type, credit_value, term, 
                first_installment, other_installments, admin_fee, active
            ) VALUES (
                :name, :plan_type, :credit_value, :term, 
                :first_installment, :other_installments, :admin_fee, :active
            )";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                'name' => $plan_data['name'],
                'plan_type' => $plan_data['plan_type'],
                'credit_value' => $plan_data['credit_value'],
                'term' => $plan_data['term'],
                'first_installment' => $plan_data['first_installment'],
                'other_installments' => $plan_data['other_installments'],
                'admin_fee' => $plan_data['admin_fee'],
                'active' => $plan_data['active']
            ]);
            
            if ($result) {
                $message = 'Plano criado com sucesso.';
            } else {
                $error = 'Erro ao criar plano.';
            }
        }
    }
}

// Atualizar plano
if (isset($_POST['action']) && $_POST['action'] === 'update_plan') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $plan_id = intval($_POST['plan_id'] ?? 0);
        $plan_data = processPlanFormData($_POST);
        
        // Validar dados
        if ($plan_id <= 0) {
            $error = 'ID de plano inválido.';
        } elseif (empty($plan_data['name']) || empty($plan_data['plan_type']) || 
                  $plan_data['credit_value'] <= 0 || $plan_data['term'] <= 0 || 
                  $plan_data['first_installment'] <= 0 || $plan_data['other_installments'] <= 0) {
            $error = 'Preencha todos os campos obrigatórios com valores válidos.';
        } else {
            // Atualizar no banco de dados
            $conn = getConnection();
            $sql = "UPDATE plans SET 
                name = :name, 
                plan_type = :plan_type, 
                credit_value = :credit_value, 
                term = :term, 
                first_installment = :first_installment, 
                other_installments = :other_installments,
                admin_fee = :admin_fee, 
                active = :active
                WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                'id' => $plan_id,
                'name' => $plan_data['name'],
                'plan_type' => $plan_data['plan_type'],
                'credit_value' => $plan_data['credit_value'],
                'term' => $plan_data['term'],
                'first_installment' => $plan_data['first_installment'],
                'other_installments' => $plan_data['other_installments'],
                'admin_fee' => $plan_data['admin_fee'],
                'active' => $plan_data['active']
            ]);
            
            if ($result) {
                $message = 'Plano atualizado com sucesso.';
            } else {
                $error = 'Erro ao atualizar plano.';
            }
        }
    }
}

// Excluir plano
if (isset($_POST['action']) && $_POST['action'] === 'delete_plan') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $plan_id = intval($_POST['plan_id'] ?? 0);
        
        if ($plan_id <= 0) {
            $error = 'ID de plano inválido.';
        } else {
            // Verificar se o plano está sendo usado por algum lead
            $conn = getConnection();
            $stmt = $conn->prepare("SELECT COUNT(*) FROM leads WHERE plan_id = :plan_id");
            $stmt->execute(['plan_id' => $plan_id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                // Apenas desativar o plano se estiver em uso
                $stmt = $conn->prepare("UPDATE plans SET active = 0 WHERE id = :id");
                $result = $stmt->execute(['id' => $plan_id]);
                
                if ($result) {
                    $message = 'Plano desativado com sucesso. Não foi possível excluir porque está associado a leads.';
                } else {
                    $error = 'Erro ao desativar plano.';
                }
            } else {
                // Excluir o plano se não estiver em uso
                $stmt = $conn->prepare("DELETE FROM plans WHERE id = :id");
                $result = $stmt->execute(['id' => $plan_id]);
                
                if ($result) {
                    $message = 'Plano excluído com sucesso.';
                } else {
                    $error = 'Erro ao excluir plano.';
                }
            }
        }
    }
}

// Obter lista de planos
$filter_type = sanitize($_GET['type'] ?? '');
$filter_term = sanitize($_GET['term'] ?? '');
$filter_active = isset($_GET['active']) ? sanitize($_GET['active']) : '';

$conn = getConnection();
$sql = "SELECT * FROM plans WHERE 1=1";
$params = [];

if (!empty($filter_type)) {
    $sql .= " AND plan_type = :type";
    $params['type'] = $filter_type;
}

if (!empty($filter_term)) {
    $sql .= " AND term = :term";
    $params['term'] = $filter_term;
}

if ($filter_active !== '') {
    $sql .= " AND active = :active";
    $params['active'] = $filter_active;
}

$sql .= " ORDER BY plan_type, term, credit_value";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter prazos disponíveis para filtros
$stmt = $conn->prepare("SELECT DISTINCT term FROM plans ORDER BY term");
$stmt->execute();
$available_terms = $stmt->fetchAll(PDO::FETCH_COLUMN);

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

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Lista de Planos</h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createPlanModal">
                        <i class="fas fa-plus"></i> Novo Plano
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <form method="get" action="<?php echo url('index.php'); ?>" class="form-inline">
                            <input type="hidden" name="route" value="admin-plans">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <select name="type" class="form-select form-select-sm">
                                        <option value="">Todos os tipos</option>
                                        <option value="car" <?php echo $filter_type === 'car' ? 'selected' : ''; ?>>Carro</option>
                                        <option value="motorcycle" <?php echo $filter_type === 'motorcycle' ? 'selected' : ''; ?>>Moto</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="term" class="form-select form-select-sm">
                                        <option value="">Todos os prazos</option>
                                        <?php foreach ($available_terms as $term): ?>
                                        <option value="<?php echo $term; ?>" <?php echo $filter_term == $term ? 'selected' : ''; ?>>
                                            <?php echo $term; ?> meses
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="active" class="form-select form-select-sm">
                                        <option value="">Todos os status</option>
                                        <option value="1" <?php echo $filter_active === '1' ? 'selected' : ''; ?>>Ativos</option>
                                        <option value="0" <?php echo $filter_active === '0' ? 'selected' : ''; ?>>Inativos</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                                        <a href="<?php echo url('index.php?route=admin-plans'); ?>" class="btn btn-secondary btn-sm">Limpar</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tabela de Planos -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Valor Crédito</th>
                                <th>Prazo</th>
                                <th>1ª Parcela</th>
                                <th>Demais</th>
                                <th>Taxa Admin.</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($plans)): ?>
                            <tr>
                                <td colspan="9" class="text-center">Nenhum plano encontrado.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($plans as $plan): ?>
                            <tr>
                                <td><?php echo $plan['name']; ?></td>
                                <td>
                                    <?php echo $plan['plan_type'] === 'car' ? 'Carro' : 'Moto'; ?>
                                </td>
                                <td>R$ <?php echo formatCurrency($plan['credit_value']); ?></td>
                                <td><?php echo $plan['term']; ?> meses</td>
                                <td>R$ <?php echo formatCurrency($plan['first_installment']); ?></td>
                                <td>R$ <?php echo formatCurrency($plan['other_installments']); ?></td>
                                <td>R$ <?php echo formatCurrency($plan['admin_fee']); ?></td>
                                <td>
                                    <?php if ($plan['active']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-plan-btn" 
                                                data-id="<?php echo $plan['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($plan['name'], ENT_QUOTES); ?>"
                                                data-type="<?php echo $plan['plan_type']; ?>"
                                                data-credit="<?php echo $plan['credit_value']; ?>"
                                                data-term="<?php echo $plan['term']; ?>"
                                                data-first="<?php echo $plan['first_installment']; ?>"
                                                data-other="<?php echo $plan['other_installments']; ?>"
                                                data-fee="<?php echo $plan['admin_fee']; ?>"
                                                data-active="<?php echo $plan['active']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-plan-btn"
                                                data-id="<?php echo $plan['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($plan['name'], ENT_QUOTES); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Criação de Plano -->
<div class="modal fade" id="createPlanModal" tabindex="-1" aria-labelledby="createPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo url('index.php?route=admin-plans'); ?>">
                <input type="hidden" name="action" value="create_plan">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="createPlanModalLabel">Criar Novo Plano</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome do Plano*</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="plan_type" class="form-label">Tipo de Veículo*</label>
                        <select class="form-select" id="plan_type" name="plan_type" required>
                            <option value="car">Carro</option>
                            <option value="motorcycle">Moto</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="credit_value" class="form-label">Valor do Crédito*</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control money" id="credit_value" name="credit_value" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="term" class="form-label">Prazo (meses)*</label>
                        <input type="number" class="form-control" id="term" name="term" min="12" max="120" required>
                    </div>
                    <div class="mb-3">
                        <label for="first_installment" class="form-label">Valor da 1ª Parcela*</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control money" id="first_installment" name="first_installment" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="other_installments" class="form-label">Valor das Demais Parcelas*</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control money" id="other_installments" name="other_installments" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="admin_fee" class="form-label">Taxa de Administração</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control money" id="admin_fee" name="admin_fee" value="0,00">
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="active" name="active" value="1" checked>
                        <label class="form-check-label" for="active">Plano Ativo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Plano</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Edição de Plano -->
<div class="modal fade" id="editPlanModal" tabindex="-1" aria-labelledby="editPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo url('index.php?route=admin-plans'); ?>">
                <input type="hidden" name="action" value="update_plan">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="plan_id" id="edit_plan_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="editPlanModalLabel">Editar Plano</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nome do Plano*</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_plan_type" class="form-label">Tipo de Veículo*</label>
                        <select class="form-select" id="edit_plan_type" name="plan_type" required>
                            <option value="car">Carro</option>
                            <option value="motorcycle">Moto</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_credit_value" class="form-label">Valor do Crédito*</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control money" id="edit_credit_value" name="credit_value" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_term" class="form-label">Prazo (meses)*</label>
                        <input type="number" class="form-control" id="edit_term" name="term" min="12" max="120" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_first_installment" class="form-label">Valor da 1ª Parcela*</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control money" id="edit_first_installment" name="first_installment" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_other_installments" class="form-label">Valor das Demais Parcelas*</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control money" id="edit_other_installments" name="other_installments" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_admin_fee" class="form-label">Taxa de Administração</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control money" id="edit_admin_fee" name="admin_fee">
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_active" name="active" value="1">
                        <label class="form-check-label" for="edit_active">Plano Ativo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Exclusão de Plano -->
<div class="modal fade" id="deletePlanModal" tabindex="-1" aria-labelledby="deletePlanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo url('index.php?route=admin-plans'); ?>">
                <input type="hidden" name="action" value="delete_plan">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="plan_id" id="delete_plan_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="deletePlanModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o plano <strong id="delete_plan_name"></strong>?</p>
                    <p>Esta ação não poderá ser desfeita. Se o plano estiver sendo utilizado por algum lead, ele será apenas desativado.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Excluir Plano</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar máscaras de moeda
    const moneyInputs = document.querySelectorAll('.money');
    moneyInputs.forEach(function(input) {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (parseFloat(value) / 100).toFixed(2).replace('.', ',');
            e.target.value = value;
        });
    });
    
    // Configurar botões de edição
    const editButtons = document.querySelectorAll('.edit-plan-btn');
    editButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const planId = this.getAttribute('data-id');
            const planName = this.getAttribute('data-name');
            const planType = this.getAttribute('data-type');
            const creditValue = parseFloat(this.getAttribute('data-credit')).toFixed(2).replace('.', ',');
            const term = this.getAttribute('data-term');
            const firstInstallment = parseFloat(this.getAttribute('data-first')).toFixed(2).replace('.', ',');
            const otherInstallments = parseFloat(this.getAttribute('data-other')).toFixed(2).replace('.', ',');
            const adminFee = parseFloat(this.getAttribute('data-fee')).toFixed(2).replace('.', ',');
            const active = this.getAttribute('data-active') === '1';
            
            // Preencher o formulário de edição
            document.getElementById('edit_plan_id').value = planId;
            document.getElementById('edit_name').value = planName;
            document.getElementById('edit_plan_type').value = planType;
            document.getElementById('edit_credit_value').value = creditValue;
            document.getElementById('edit_term').value = term;
            document.getElementById('edit_first_installment').value = firstInstallment;
            document.getElementById('edit_other_installments').value = otherInstallments;
            document.getElementById('edit_admin_fee').value = adminFee;
            document.getElementById('edit_active').checked = active;
            
            // Abrir o modal
            const editModal = new bootstrap.Modal(document.getElementById('editPlanModal'));
            editModal.show();
        });
    });
    
    // Configurar botões de exclusão
    const deleteButtons = document.querySelectorAll('.delete-plan-btn');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const planId = this.getAttribute('data-id');
            const planName = this.getAttribute('data-name');
            
            // Preencher o formulário de exclusão
            document.getElementById('delete_plan_id').value = planId;
            document.getElementById('delete_plan_name').textContent = planName;
            
            // Abrir o modal
            const deleteModal = new bootstrap.Modal(document.getElementById('deletePlanModal'));
            deleteModal.show();
        });
    });
});
</script>
