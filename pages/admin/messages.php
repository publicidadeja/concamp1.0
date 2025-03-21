<?php
/**
 * Gerenciamento de modelos de mensagens
 */

// Título da página
$page_title = 'Modelos de Mensagens';

// Verificar permissão
if (!isAdmin()) {
    include_once __DIR__ . '/../access-denied.php';
    exit;
}

// Processar ações
$message = '';
$error = '';

// Criar modelo de mensagem
if (isset($_POST['action']) && $_POST['action'] === 'create_template') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        $content = $_POST['content'] ?? '';
        $active = isset($_POST['active']) ? 1 : 0;
        
        if (empty($name) || empty($content)) {
            $error = 'Preencha todos os campos obrigatórios.';
        } else {
            // Inserir no banco de dados
            $conn = getConnection();
            $sql = "INSERT INTO message_templates (name, category, content, active) VALUES (:name, :category, :content, :active)";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                'name' => $name,
                'category' => $category,
                'content' => $content,
                'active' => $active
            ]);
            
            if ($result) {
                $message = 'Modelo de mensagem criado com sucesso.';
            } else {
                $error = 'Erro ao criar modelo de mensagem.';
            }
        }
    }
}

// Atualizar modelo de mensagem
if (isset($_POST['action']) && $_POST['action'] === 'update_template') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $template_id = intval($_POST['template_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        $content = $_POST['content'] ?? '';
        $active = isset($_POST['active']) ? 1 : 0;
        
        if ($template_id <= 0 || empty($name) || empty($content)) {
            $error = 'Preencha todos os campos obrigatórios.';
        } else {
            // Atualizar no banco de dados
            $conn = getConnection();
            $sql = "UPDATE message_templates SET name = :name, category = :category, content = :content, active = :active WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                'id' => $template_id,
                'name' => $name,
                'category' => $category,
                'content' => $content,
                'active' => $active
            ]);
            
            if ($result) {
                $message = 'Modelo de mensagem atualizado com sucesso.';
            } else {
                $error = 'Erro ao atualizar modelo de mensagem.';
            }
        }
    }
}

// Excluir modelo de mensagem
if (isset($_POST['action']) && $_POST['action'] === 'delete_template') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $template_id = intval($_POST['template_id'] ?? 0);
        
        if ($template_id <= 0) {
            $error = 'ID de modelo inválido.';
        } else {
            // Verificar se o modelo está sendo usado
            $conn = getConnection();
            $stmt = $conn->prepare("SELECT COUNT(*) FROM sent_messages WHERE template_id = :template_id");
            $stmt->execute(['template_id' => $template_id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                // Apenas desativar o modelo se estiver em uso
                $stmt = $conn->prepare("UPDATE message_templates SET active = 0 WHERE id = :id");
                $result = $stmt->execute(['id' => $template_id]);
                
                if ($result) {
                    $message = 'Modelo desativado com sucesso. Não foi possível excluir porque está sendo usado.';
                } else {
                    $error = 'Erro ao desativar modelo de mensagem.';
                }
            } else {
                // Excluir o modelo se não estiver em uso
                $stmt = $conn->prepare("DELETE FROM message_templates WHERE id = :id");
                $result = $stmt->execute(['id' => $template_id]);
                
                if ($result) {
                    $message = 'Modelo de mensagem excluído com sucesso.';
                } else {
                    $error = 'Erro ao excluir modelo de mensagem.';
                }
            }
        }
    }
}

// Obter modelos de mensagens com base nos filtros
$filter_category = sanitize($_GET['category'] ?? '');
$filter_active = isset($_GET['active']) ? sanitize($_GET['active']) : '';
$search = sanitize($_GET['search'] ?? '');

$conn = getConnection();
$sql = "SELECT * FROM message_templates WHERE 1=1";
$params = [];

if (!empty($filter_category)) {
    $sql .= " AND category = :category";
    $params['category'] = $filter_category;
}

if ($filter_active !== '') {
    $sql .= " AND active = :active";
    $params['active'] = $filter_active;
}

if (!empty($search)) {
    $sql .= " AND (name LIKE :search OR content LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

$sql .= " ORDER BY category, name";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter categorias disponíveis
$stmt = $conn->prepare("SELECT DISTINCT category FROM message_templates ORDER BY category");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Gerar token CSRF
$csrf_token = generateCsrfToken();
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
                    <h5 class="mb-0">Modelos de Mensagens</h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
                        <i class="fas fa-plus"></i> Novo Modelo
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <form method="get" action="<?php echo url('index.php'); ?>" class="form-inline">
                            <input type="hidden" name="route" value="admin-messages">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <select name="category" class="form-select form-select-sm">
                                        <option value="">Todas as categorias</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category; ?>" <?php echo $filter_category === $category ? 'selected' : ''; ?>>
                                            <?php echo $category; ?>
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
                                <div class="col-md-4">
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="search" class="form-control" placeholder="Pesquisar..." value="<?php echo $search; ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <a href="<?php echo url('index.php?route=admin-messages'); ?>" class="btn btn-secondary btn-sm w-100">Limpar</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tabela de Modelos -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>Conteúdo</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($templates)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Nenhum modelo de mensagem encontrado.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($templates as $template): ?>
                            <tr>
                                <td><?php echo $template['name']; ?></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $template['category']; ?></span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 400px;"><?php echo htmlspecialchars($template['content']); ?></div>
                                </td>
                                <td>
                                    <?php if ($template['active']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-template-btn" 
                                                data-id="<?php echo $template['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($template['name'], ENT_QUOTES); ?>"
                                                data-category="<?php echo htmlspecialchars($template['category'], ENT_QUOTES); ?>"
                                                data-content="<?php echo htmlspecialchars($template['content'], ENT_QUOTES); ?>"
                                                data-active="<?php echo $template['active']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-template-btn"
                                                data-id="<?php echo $template['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($template['name'], ENT_QUOTES); ?>">
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

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Ajuda com Placeholders</h5>
            </div>
            <div class="card-body">
                <p>Os modelos de mensagem podem conter placeholders que serão substituídos pelos dados reais do lead quando a mensagem for enviada. Aqui estão os placeholders disponíveis:</p>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Placeholder</th>
                                <th>Descrição</th>
                                <th>Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>{nome}</code></td>
                                <td>Nome do lead</td>
                                <td>João Silva</td>
                            </tr>
                            <tr>
                                <td><code>{tipo_veiculo}</code></td>
                                <td>Tipo de veículo (carro ou moto)</td>
                                <td>Carro</td>
                            </tr>
                            <tr>
                                <td><code>{valor_credito}</code></td>
                                <td>Valor do crédito formatado</td>
                                <td>R$ 50.000,00</td>
                            </tr>
                            <tr>
                                <td><code>{prazo}</code></td>
                                <td>Prazo em meses</td>
                                <td>60 meses</td>
                            </tr>
                            <tr>
                                <td><code>{valor_primeira}</code></td>
                                <td>Valor da primeira parcela formatado</td>
                                <td>R$ 1.200,00</td>
                            </tr>
                            <tr>
                                <td><code>{valor_demais}</code></td>
                                <td>Valor das demais parcelas formatado</td>
                                <td>R$ 980,00</td>
                            </tr>
                            <tr>
                                <td><code>{nome_consultor}</code></td>
                                <td>Nome do consultor/vendedor</td>
                                <td>Maria Oliveira</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Dica:</strong> Para facilitar a comunicação, crie modelos para diferentes estágios do processo de vendas, como boas-vindas, acompanhamento e fechamento.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Criação de Modelo -->
<div class="modal fade" id="createTemplateModal" tabindex="-1" aria-labelledby="createTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="<?php echo url('index.php?route=admin-messages'); ?>">
                <input type="hidden" name="action" value="create_template">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="createTemplateModalLabel">Criar Novo Modelo de Mensagem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome do Modelo*</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <small class="form-text text-muted">Um nome descritivo para identificar este modelo.</small>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Categoria*</label>
                        <input type="text" class="form-control" id="category" name="category" required list="categories">
                        <datalist id="categories">
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category; ?>">
                            <?php endforeach; ?>
                            <option value="boas-vindas">
                            <option value="acompanhamento">
                            <option value="negociacao">
                            <option value="fechamento">
                            <option value="recuperacao">
                        </datalist>
                        <small class="form-text text-muted">Categoria para agrupar modelos semelhantes.</small>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Conteúdo da Mensagem*</label>
                        <textarea class="form-control" id="content" name="content" rows="6" required></textarea>
                        <small class="form-text text-muted">
                            Use os placeholders disponíveis para personalizar a mensagem.
                        </small>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="active" name="active" value="1" checked>
                        <label class="form-check-label" for="active">Modelo Ativo</label>
                    </div>
                    
                    <div class="alert alert-secondary">
                        <h6>Prévia da Mensagem</h6>
                        <div id="preview" class="p-3 bg-light rounded">
                            <p class="mb-0 preview-content">A mensagem será exibida aqui...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Modelo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Edição de Modelo -->
<div class="modal fade" id="editTemplateModal" tabindex="-1" aria-labelledby="editTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="<?php echo url('index.php?route=admin-messages'); ?>">
                <input type="hidden" name="action" value="update_template">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="template_id" id="edit_template_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="editTemplateModalLabel">Editar Modelo de Mensagem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nome do Modelo*</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category" class="form-label">Categoria*</label>
                        <input type="text" class="form-control" id="edit_category" name="category" required list="edit_categories">
                        <datalist id="edit_categories">
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category; ?>">
                            <?php endforeach; ?>
                            <option value="boas-vindas">
                            <option value="acompanhamento">
                            <option value="negociacao">
                            <option value="fechamento">
                            <option value="recuperacao">
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label for="edit_content" class="form-label">Conteúdo da Mensagem*</label>
                        <textarea class="form-control" id="edit_content" name="content" rows="6" required></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_active" name="active" value="1">
                        <label class="form-check-label" for="edit_active">Modelo Ativo</label>
                    </div>
                    
                    <div class="alert alert-secondary">
                        <h6>Prévia da Mensagem</h6>
                        <div id="edit_preview" class="p-3 bg-light rounded">
                            <p class="mb-0 preview-content">A mensagem será exibida aqui...</p>
                        </div>
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

<!-- Modal de Exclusão de Modelo -->
<div class="modal fade" id="deleteTemplateModal" tabindex="-1" aria-labelledby="deleteTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo url('index.php?route=admin-messages'); ?>">
                <input type="hidden" name="action" value="delete_template">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="template_id" id="delete_template_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteTemplateModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o modelo <strong id="delete_template_name"></strong>?</p>
                    <p>Esta ação não poderá ser desfeita. Se o modelo já foi utilizado em mensagens enviadas, ele será apenas desativado.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Excluir Modelo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Função para atualizar prévia da mensagem
    function updatePreview(content, previewElement) {
        if (!content) {
            previewElement.textContent = 'A mensagem será exibida aqui...';
            return;
        }
        
        // Substituir placeholders com exemplos
        let preview = content
            .replace(/{nome}/g, 'João Silva')
            .replace(/{tipo_veiculo}/g, 'Carro')
            .replace(/{valor_credito}/g, 'R$ 50.000,00')
            .replace(/{prazo}/g, '60 meses')
            .replace(/{valor_primeira}/g, 'R$ 1.200,00')
            .replace(/{valor_demais}/g, 'R$ 980,00')
            .replace(/{nome_consultor}/g, 'Maria Oliveira');
        
        // Aplicar quebras de linha
        preview = preview.replace(/\n/g, '<br>');
        
        previewElement.innerHTML = preview;
    }
    
    // Configurar prévia no formulário de criação
    const contentField = document.getElementById('content');
    const previewElement = document.querySelector('#preview .preview-content');
    
    if (contentField && previewElement) {
        contentField.addEventListener('input', function() {
            updatePreview(this.value, previewElement);
        });
    }
    
    // Configurar prévia no formulário de edição
    const editContentField = document.getElementById('edit_content');
    const editPreviewElement = document.querySelector('#edit_preview .preview-content');
    
    if (editContentField && editPreviewElement) {
        editContentField.addEventListener('input', function() {
            updatePreview(this.value, editPreviewElement);
        });
    }
    
    // Configurar botões de edição
    const editButtons = document.querySelectorAll('.edit-template-btn');
    editButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const templateId = this.getAttribute('data-id');
            const templateName = this.getAttribute('data-name');
            const templateCategory = this.getAttribute('data-category');
            const templateContent = this.getAttribute('data-content');
            const templateActive = this.getAttribute('data-active') === '1';
            
            // Preencher o formulário de edição
            document.getElementById('edit_template_id').value = templateId;
            document.getElementById('edit_name').value = templateName;
            document.getElementById('edit_category').value = templateCategory;
            document.getElementById('edit_content').value = templateContent;
            document.getElementById('edit_active').checked = templateActive;
            
            // Atualizar prévia
            updatePreview(templateContent, editPreviewElement);
            
            // Abrir o modal
            const editModal = new bootstrap.Modal(document.getElementById('editTemplateModal'));
            editModal.show();
        });
    });
    
    // Configurar botões de exclusão
    const deleteButtons = document.querySelectorAll('.delete-template-btn');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const templateId = this.getAttribute('data-id');
            const templateName = this.getAttribute('data-name');
            
            // Preencher o formulário de exclusão
            document.getElementById('delete_template_id').value = templateId;
            document.getElementById('delete_template_name').textContent = templateName;
            
            // Abrir o modal
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteTemplateModal'));
            deleteModal.show();
        });
    });
});
</script>
