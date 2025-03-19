<?php
/**
 * Gerenciamento de configurações do sistema
 */

// Título da página
$page_title = 'Configurações do Sistema';

// Verificar permissão
if (!isAdmin()) {
    include_once __DIR__ . '/../access-denied.php';
    exit;
}

// Processar ações
$message = '';
$error = '';

// Salvar configurações
if (isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $settings = [
            'site_name' => sanitize($_POST['site_name'] ?? ''),
            'company_name' => sanitize($_POST['company_name'] ?? ''),
            'contact_email' => sanitize($_POST['contact_email'] ?? ''),
            'contact_phone' => sanitize($_POST['contact_phone'] ?? ''),
            'whatsapp_token' => $_POST['whatsapp_token'] ?? '',
            'whatsapp_api_url' => sanitize($_POST['whatsapp_api_url'] ?? ''),
            'default_consultant' => sanitize($_POST['default_consultant'] ?? ''),
            'lead_expiration_days' => intval($_POST['lead_expiration_days'] ?? 30),
            'enable_auto_assign' => isset($_POST['enable_auto_assign']) ? '1' : '0',
            'auto_reply_new_leads' => isset($_POST['auto_reply_new_leads']) ? '1' : '0',
            'notification_email' => sanitize($_POST['notification_email'] ?? '')
        ];
        
        $conn = getConnection();
        
        // Iniciar uma transação para garantir que todas as configurações sejam salvas
        $conn->beginTransaction();
        
        try {
            foreach ($settings as $key => $value) {
                updateSetting($key, $value);
            }
            
            $conn->commit();
            $message = 'Configurações salvas com sucesso.';
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Erro ao salvar configurações: ' . $e->getMessage();
        }
    }
}

// Obter configurações atuais
$settings = [
    'site_name' => getSetting('site_name') ?: 'ConCamp',
    'company_name' => getSetting('company_name') ?: 'ConCamp - Contratos Premiados',
    'contact_email' => getSetting('contact_email') ?: '',
    'contact_phone' => getSetting('contact_phone') ?: '',
    'whatsapp_token' => getSetting('whatsapp_token') ?: '',
    'whatsapp_api_url' => getSetting('whatsapp_api_url') ?: '',
    'default_consultant' => getSetting('default_consultant') ?: '',
    'lead_expiration_days' => getSetting('lead_expiration_days') ?: '30',
    'enable_auto_assign' => getSetting('enable_auto_assign') ?: '0',
    'auto_reply_new_leads' => getSetting('auto_reply_new_leads') ?: '0',
    'notification_email' => getSetting('notification_email') ?: ''
];

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

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Configurações do Sistema</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo url('index.php?route=admin-settings'); ?>">
                    <input type="hidden" name="action" value="save_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">Informações Gerais</h6>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="site_name" class="form-label">Nome do Site</label>
                            <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>">
                            <small class="form-text text-muted">Nome exibido na aba do navegador e em outros lugares.</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="company_name" class="form-label">Nome da Empresa</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($settings['company_name']); ?>">
                            <small class="form-text text-muted">Nome completo da empresa para documentos e comunicações.</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="contact_email" class="form-label">E-mail de Contato</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email']); ?>">
                            <small class="form-text text-muted">E-mail principal para contato com clientes.</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="contact_phone" class="form-label">Telefone de Contato</label>
                            <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone']); ?>">
                            <small class="form-text text-muted">Telefone principal para contato com clientes.</small>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">Integração WhatsApp</h6>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="whatsapp_token" class="form-label">Token de API WhatsApp</label>
                            <input type="password" class="form-control" id="whatsapp_token" name="whatsapp_token" value="<?php echo htmlspecialchars($settings['whatsapp_token']); ?>">
                            <small class="form-text text-muted">Token de autenticação para a API do WhatsApp.</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="whatsapp_api_url" class="form-label">URL da API WhatsApp</label>
                            <input type="text" class="form-control" id="whatsapp_api_url" name="whatsapp_api_url" value="<?php echo htmlspecialchars($settings['whatsapp_api_url']); ?>">
                            <small class="form-text text-muted">URL base para envio de mensagens via WhatsApp.</small>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">Configurações de Leads</h6>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="default_consultant" class="form-label">Nome do Consultor Padrão</label>
                            <input type="text" class="form-control" id="default_consultant" name="default_consultant" value="<?php echo htmlspecialchars($settings['default_consultant']); ?>">
                            <small class="form-text text-muted">Nome do consultor exibido em mensagens quando nenhum vendedor estiver atribuído.</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="lead_expiration_days" class="form-label">Dias para Expiração de Lead</label>
                            <input type="number" class="form-control" id="lead_expiration_days" name="lead_expiration_days" min="1" max="365" value="<?php echo htmlspecialchars($settings['lead_expiration_days']); ?>">
                            <small class="form-text text-muted">Quantidade de dias sem interação para considerar um lead como "frio".</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enable_auto_assign" name="enable_auto_assign" value="1" <?php echo $settings['enable_auto_assign'] === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enable_auto_assign">Ativar atribuição automática de leads</label>
                            </div>
                            <small class="form-text text-muted">Distribuir leads automaticamente entre os vendedores ativos.</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="auto_reply_new_leads" name="auto_reply_new_leads" value="1" <?php echo $settings['auto_reply_new_leads'] === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="auto_reply_new_leads">Enviar resposta automática para novos leads</label>
                            </div>
                            <small class="form-text text-muted">Enviar mensagem de boas-vindas quando um novo lead é criado.</small>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">Notificações</h6>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="notification_email" class="form-label">E-mail para Notificações</label>
                            <input type="email" class="form-control" id="notification_email" name="notification_email" value="<?php echo htmlspecialchars($settings['notification_email']); ?>">
                            <small class="form-text text-muted">E-mail para receber notificações do sistema (deixe em branco para desativar).</small>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Testar Integração WhatsApp</h5>
            </div>
            <div class="card-body">
                <form id="testWhatsAppForm">
                    <div class="mb-3">
                        <label for="test_phone" class="form-label">Número de Telefone (com DDD)</label>
                        <input type="text" class="form-control" id="test_phone" placeholder="Ex: 11999999999" required>
                    </div>
                    <div class="mb-3">
                        <label for="test_message" class="form-label">Mensagem de Teste</label>
                        <textarea class="form-control" id="test_message" rows="3" required>Olá! Esta é uma mensagem de teste do sistema ConCamp.</textarea>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary" id="testWhatsAppBtn">
                            <i class="fab fa-whatsapp me-2"></i>Enviar Mensagem de Teste
                        </button>
                    </div>
                </form>
                <div class="mt-3" id="testResult" style="display: none;"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Gerenciamento de Cache</h5>
            </div>
            <div class="card-body">
                <p>O sistema mantém um cache de algumas informações para melhorar o desempenho. Se você fez alterações e não estiver vendo os resultados, tente limpar o cache.</p>
                
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="clearCacheBtn">
                        <i class="fas fa-broom me-2"></i>Limpar Cache do Sistema
                    </button>
                </div>
                <div class="mt-3" id="cacheResult" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Teste de WhatsApp
    const testWhatsAppForm = document.getElementById('testWhatsAppForm');
    const testWhatsAppBtn = document.getElementById('testWhatsAppBtn');
    const testResult = document.getElementById('testResult');
    
    if (testWhatsAppForm) {
        testWhatsAppForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const phone = document.getElementById('test_phone').value;
            const message = document.getElementById('test_message').value;
            
            if (!phone || !message) {
                testResult.innerHTML = '<div class="alert alert-danger">Preencha todos os campos.</div>';
                testResult.style.display = 'block';
                return;
            }
            
            // Alterar estado do botão
            testWhatsAppBtn.disabled = true;
            testWhatsAppBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';
            
            // Fazer requisição AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo url('api/test-whatsapp.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                testWhatsAppBtn.disabled = false;
                testWhatsAppBtn.innerHTML = '<i class="fab fa-whatsapp me-2"></i>Enviar Mensagem de Teste';
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            testResult.innerHTML = '<div class="alert alert-success">Mensagem enviada com sucesso!</div>';
                        } else {
                            testResult.innerHTML = '<div class="alert alert-danger">Erro ao enviar mensagem: ' + (response.error || 'Erro desconhecido') + '</div>';
                        }
                    } catch (e) {
                        testResult.innerHTML = '<div class="alert alert-danger">Erro ao processar resposta do servidor.</div>';
                    }
                } else {
                    testResult.innerHTML = '<div class="alert alert-danger">Erro na comunicação com o servidor.</div>';
                }
                
                testResult.style.display = 'block';
            };
            
            xhr.onerror = function() {
                testWhatsAppBtn.disabled = false;
                testWhatsAppBtn.innerHTML = '<i class="fab fa-whatsapp me-2"></i>Enviar Mensagem de Teste';
                testResult.innerHTML = '<div class="alert alert-danger">Erro na comunicação com o servidor.</div>';
                testResult.style.display = 'block';
            };
            
            xhr.send('phone=' + encodeURIComponent(phone) + '&message=' + encodeURIComponent(message));
        });
    }
    
    // Limpar cache
    const clearCacheBtn = document.getElementById('clearCacheBtn');
    const cacheResult = document.getElementById('cacheResult');
    
    if (clearCacheBtn) {
        clearCacheBtn.addEventListener('click', function() {
            // Alterar estado do botão
            clearCacheBtn.disabled = true;
            clearCacheBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Limpando...';
            
            // Fazer requisição AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo url('api/clear-cache.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                clearCacheBtn.disabled = false;
                clearCacheBtn.innerHTML = '<i class="fas fa-broom me-2"></i>Limpar Cache do Sistema';
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            cacheResult.innerHTML = '<div class="alert alert-success">Cache limpo com sucesso!</div>';
                        } else {
                            cacheResult.innerHTML = '<div class="alert alert-danger">Erro ao limpar cache: ' + (response.error || 'Erro desconhecido') + '</div>';
                        }
                    } catch (e) {
                        cacheResult.innerHTML = '<div class="alert alert-danger">Erro ao processar resposta do servidor.</div>';
                    }
                } else {
                    cacheResult.innerHTML = '<div class="alert alert-danger">Erro na comunicação com o servidor.</div>';
                }
                
                cacheResult.style.display = 'block';
            };
            
            xhr.onerror = function() {
                clearCacheBtn.disabled = false;
                clearCacheBtn.innerHTML = '<i class="fas fa-broom me-2"></i>Limpar Cache do Sistema';
                cacheResult.innerHTML = '<div class="alert alert-danger">Erro na comunicação com o servidor.</div>';
                cacheResult.style.display = 'block';
            };
            
            xhr.send('csrf_token=<?php echo $csrf_token; ?>');
        });
    }
});
</script>
