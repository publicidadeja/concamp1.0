<?php
/**
 * Gerenciamento de usuários
 */

// Título da página
$page_title = 'Gerenciar Usuários';

// Verificar permissão
if (!isAdmin()) {
    include_once __DIR__ . '/../access-denied.php';
    exit;
}

// Processar ações
$message = '';
$error = '';

// Criar usuário
if (isset($_POST['action']) && $_POST['action'] === 'create_user') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = sanitize($_POST['role'] ?? 'seller');
        
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Preencha todos os campos obrigatórios.';
        } else {
            $result = createUser($name, $email, $password, $role);
            
            if ($result['success']) {
                $message = 'Usuário criado com sucesso.';
            } else {
                $error = $result['error'] ?? 'Erro ao criar usuário.';
            }
        }
    }
}

// Atualizar usuário
if (isset($_POST['action']) && $_POST['action'] === 'update_user') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $id = intval($_POST['user_id'] ?? 0);
        $data = [
            'name' => sanitize($_POST['name'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'role' => sanitize($_POST['role'] ?? ''),
            'status' => sanitize($_POST['status'] ?? '')
        ];
        
        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }
        
        if ($id <= 0) {
            $error = 'ID de usuário inválido.';
        } else {
            $result = updateUser($id, $data);
            
            if ($result['success']) {
                $message = 'Usuário atualizado com sucesso.';
            } else {
                $error = $result['error'] ?? 'Erro ao atualizar usuário.';
            }
        }
    }
}

// Obter lista de usuários
$filter_role = sanitize($_GET['role'] ?? '');
$filter_status = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');

$filters = [];
if (!empty($filter_role)) {
    $filters['role'] = $filter_role;
}
if (!empty($filter_status)) {
    $filters['status'] = $filter_status;
}
if (!empty($search)) {
    $filters['search'] = $search;
}

$users = getUsers($filters);

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
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Lista de Usuários</h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="fas fa-plus"></i> Novo Usuário
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <form method="get" action="<?php echo url('index.php'); ?>" class="form-inline">
                            <input type="hidden" name="route" value="admin-users">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <select name="role" class="form-select form-select-sm">
                                        <option value="">Todas as funções</option>
                                        <option value="admin" <?php echo $filter_role === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                        <option value="manager" <?php echo $filter_role === 'manager' ? 'selected' : ''; ?>>Gerente</option>
                                        <option value="seller" <?php echo $filter_role === 'seller' ? 'selected' : ''; ?>>Vendedor</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">Todos os status</option>
                                        <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Ativo</option>
                                        <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inativo</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="search" class="form-control" placeholder="Pesquisar..." value="<?php echo $search; ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <a href="<?php echo url('index.php?route=admin-users'); ?>" class="btn btn-secondary btn-sm w-100">Limpar</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tabela de Usuários -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Função</th>
                                <th>Status</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Nenhum usuário encontrado.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['name']; ?></td>
                                <td><?php echo $user['email']; ?></td>
                                <td>
                                    <?php
                                    switch ($user['role']) {
                                        case 'admin':
                                            echo '<span class="badge bg-danger">Administrador</span>';
                                            break;
                                        case 'manager':
                                            echo '<span class="badge bg-warning text-dark">Gerente</span>';
                                            break;
                                        case 'seller':
                                            echo '<span class="badge bg-primary">Vendedor</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">Desconhecido</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    switch ($user['status']) {
                                        case 'active':
                                            echo '<span class="badge bg-success">Ativo</span>';
                                            break;
                                        case 'inactive':
                                            echo '<span class="badge bg-secondary">Inativo</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">Desconhecido</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo formatDateTime($user['created_at']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-user-btn" 
                                            data-id="<?php echo $user['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>"
                                            data-role="<?php echo $user['role']; ?>"
                                            data-status="<?php echo $user['status']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
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
    
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Estatísticas</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <h6>Usuários por Função</h6>
                        <div class="progress mb-1" style="height: 20px;">
                            <?php
                            $role_counts = [];
                            $total_users = count($users);
                            
                            // Contar usuários por função
                            foreach ($users as $user) {
                                $role_counts[$user['role']] = ($role_counts[$user['role']] ?? 0) + 1;
                            }
                            
                            // Calcular percentuais
                            $admin_percent = $total_users > 0 ? round(($role_counts['admin'] ?? 0) / $total_users * 100) : 0;
                            $manager_percent = $total_users > 0 ? round(($role_counts['manager'] ?? 0) / $total_users * 100) : 0;
                            $seller_percent = $total_users > 0 ? round(($role_counts['seller'] ?? 0) / $total_users * 100) : 0;
                            ?>
                            <div class="progress-bar bg-danger" style="width: <?php echo $admin_percent; ?>%" role="progressbar">
                                Admin (<?php echo $role_counts['admin'] ?? 0; ?>)
                            </div>
                            <div class="progress-bar bg-warning text-dark" style="width: <?php echo $manager_percent; ?>%" role="progressbar">
                                Gerente (<?php echo $role_counts['manager'] ?? 0; ?>)
                            </div>
                            <div class="progress-bar bg-primary" style="width: <?php echo $seller_percent; ?>%" role="progressbar">
                                Vendedor (<?php echo $role_counts['seller'] ?? 0; ?>)
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <h6>Usuários por Status</h6>
                        <div class="progress" style="height: 20px;">
                            <?php
                            $status_counts = [];
                            
                            // Contar usuários por status
                            foreach ($users as $user) {
                                $status_counts[$user['status']] = ($status_counts[$user['status']] ?? 0) + 1;
                            }
                            
                            // Calcular percentuais
                            $active_percent = $total_users > 0 ? round(($status_counts['active'] ?? 0) / $total_users * 100) : 0;
                            $inactive_percent = $total_users > 0 ? round(($status_counts['inactive'] ?? 0) / $total_users * 100) : 0;
                            ?>
                            <div class="progress-bar bg-success" style="width: <?php echo $active_percent; ?>%" role="progressbar">
                                Ativo (<?php echo $status_counts['active'] ?? 0; ?>)
                            </div>
                            <div class="progress-bar bg-secondary" style="width: <?php echo $inactive_percent; ?>%" role="progressbar">
                                Inativo (<?php echo $status_counts['inactive'] ?? 0; ?>)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Criação de Usuário -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo url('index.php?route=admin-users'); ?>">
                <input type="hidden" name="action" value="create_user">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">Criar Novo Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome Completo*</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail*</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha*</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Função*</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="seller">Vendedor</option>
                            <option value="manager">Gerente</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Usuário</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Edição de Usuário -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo url('index.php?route=admin-users'); ?>">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Editar Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nome Completo*</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">E-mail*</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Senha (deixe em branco para manter a mesma)</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                        <small class="form-text text-muted">Preencha apenas se desejar alterar a senha.</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Função*</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="seller">Vendedor</option>
                            <option value="manager">Gerente</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status*</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="active">Ativo</option>
                            <option value="inactive">Inativo</option>
                        </select>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar botões de edição
    const editButtons = document.querySelectorAll('.edit-user-btn');
    
    editButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            const userName = this.getAttribute('data-name');
            const userEmail = this.getAttribute('data-email');
            const userRole = this.getAttribute('data-role');
            const userStatus = this.getAttribute('data-status');
            
            // Preencher o formulário de edição
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_name').value = userName;
            document.getElementById('edit_email').value = userEmail;
            document.getElementById('edit_role').value = userRole;
            document.getElementById('edit_status').value = userStatus;
            
            // Abrir o modal
            const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
            editModal.show();
        });
    });
});
</script>
