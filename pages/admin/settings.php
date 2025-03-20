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
            'notification_email' => sanitize($_POST['notification_email'] ?? ''),
            // Novas configurações de tema
            'primary_color' => sanitize($_POST['primary_color_hidden'] ?? $_POST['primary_color'] ?? '#0d6efd'),
            'secondary_color' => sanitize($_POST['secondary_color_hidden'] ?? $_POST['secondary_color'] ?? '#6c757d'),
            'header_color' => sanitize($_POST['header_color_hidden'] ?? $_POST['header_color'] ?? '#ffffff'),
            'logo_url' => sanitize($_POST['logo_url'] ?? ''),
            'dark_mode' => isset($_POST['dark_mode']) ? '1' : '0'
        ];
        
        $conn = getConnection();
        
        // Iniciar uma transação para garantir que todas as configurações sejam salvas
        $conn->beginTransaction();
        
        try {
            // Debug: Registrar os valores que serão salvos
            error_log("Configurações de tema a serem salvas: " . json_encode([
                'primary_color' => $settings['primary_color'],
                'secondary_color' => $settings['secondary_color'],
                'header_color' => $settings['header_color'],
                'logo_url' => $settings['logo_url'],
                'dark_mode' => $settings['dark_mode']
            ]));
            
            foreach ($settings as $key => $value) {
                updateSetting($key, $value);
                if (in_array($key, ['primary_color', 'secondary_color', 'header_color', 'logo_url', 'dark_mode'])) {
                    error_log("Salvando configuração $key = $value");
                }
            }
            
            // Atualizar a versão do tema para forçar o recarregamento do CSS
            $theme_version = time();
            updateSetting('theme_version', $theme_version);
            error_log("Nova versão do tema: $theme_version");
            
            $conn->commit();
            $message = 'Configurações salvas com sucesso. O tema foi atualizado.';
            
            // Limpar cache de navegador através de JavaScript
            echo '<script>
                if (window.localStorage) {
                    localStorage.removeItem("theme_preview");
                }
            </script>';
            
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
    'notification_email' => getSetting('notification_email') ?: '',
    'primary_color' => getSetting('primary_color') ?: '#0d6efd',
    'secondary_color' => getSetting('secondary_color') ?: '#6c757d',
    'header_color' => getSetting('header_color') ?: '#ffffff',
    'logo_url' => getSetting('logo_url') ?: '',
    'dark_mode' => getSetting('dark_mode') ?: '0'
];

// Debug: Verificar as configurações do tema
error_log("Configurações atuais de tema: " . json_encode([
    'primary_color' => getSetting('primary_color'),
    'secondary_color' => getSetting('secondary_color'),
    'header_color' => getSetting('header_color'),
    'logo_url' => getSetting('logo_url'),
    'dark_mode' => getSetting('dark_mode')
]));

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
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">Personalização do Tema</h6>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="primary_color" class="form-label">Cor Principal</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars(getSetting('primary_color') ?: '#0d6efd'); ?>">
                                <input type="hidden" name="primary_color_hidden" id="primary_color_hidden" value="<?php echo htmlspecialchars(getSetting('primary_color') ?: '#0d6efd'); ?>">
                                <input type="text" class="form-control" id="primary_color_text" value="<?php echo htmlspecialchars(getSetting('primary_color') ?: '#0d6efd'); ?>">
                            </div>
                            <small class="form-text text-muted">Cor principal para botões e elementos em destaque.</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="secondary_color" class="form-label">Cor Secundária</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="secondary_color" name="secondary_color" value="<?php echo htmlspecialchars(getSetting('secondary_color') ?: '#6c757d'); ?>">
                                <input type="hidden" name="secondary_color_hidden" id="secondary_color_hidden" value="<?php echo htmlspecialchars(getSetting('secondary_color') ?: '#6c757d'); ?>">
                                <input type="text" class="form-control" id="secondary_color_text" value="<?php echo htmlspecialchars(getSetting('secondary_color') ?: '#6c757d'); ?>">
                            </div>
                            <small class="form-text text-muted">Cor secundária para elementos menos destacados.</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="header_color" class="form-label">Cor do Cabeçalho</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="header_color" name="header_color" value="<?php echo htmlspecialchars(getSetting('header_color') ?: '#ffffff'); ?>">
                                <input type="hidden" name="header_color_hidden" id="header_color_hidden" value="<?php echo htmlspecialchars(getSetting('header_color') ?: '#ffffff'); ?>">
                                <input type="text" class="form-control" id="header_color_text" value="<?php echo htmlspecialchars(getSetting('header_color') ?: '#ffffff'); ?>">
                            </div>
                            <small class="form-text text-muted">Cor de fundo do cabeçalho do site.</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="logo_url" class="form-label">URL do Logo</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="logo_url" name="logo_url" value="<?php echo htmlspecialchars(getSetting('logo_url') ?: ''); ?>" placeholder="assets/img/uploads/logo.png">
                                <button class="btn btn-outline-secondary" type="button" id="preview_logo_btn">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Caminho relativo para o arquivo de logo (deixe em branco para usar o logo padrão).</small>
                            
                            <div id="logo_url_preview" class="mt-2 text-center d-none">
                                <p class="mb-1">Logo atual:</p>
                                <img src="#" alt="Logo atual" class="img-fluid" style="max-height: 100px;">
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="logo_upload" class="form-label">Carregar Novo Logo</label>
                            <input type="file" class="form-control" id="logo_upload" accept="image/png, image/jpeg, image/svg+xml">
                            <small class="form-text text-muted">Envie um arquivo de imagem para usar como logo (PNG, JPG ou SVG).</small>
                            <div id="logo_upload_progress" class="progress mt-2 d-none">
                                <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div id="logo_preview" class="mt-2 text-center d-none">
                                <p class="mb-1">Pré-visualização do logo:</p>
                                <img src="#" alt="Logo preview" class="img-fluid" style="max-height: 100px;">
                            </div>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="dark_mode" name="dark_mode" value="1" <?php echo getSetting('dark_mode') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="dark_mode">Ativar Modo Escuro</label>
                            </div>
                            <small class="form-text text-muted">Ativa o tema escuro em todo o sistema.</small>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" id="resetThemeBtn" class="btn btn-outline-danger me-2">
                            <i class="fas fa-undo me-2"></i>Redefinir para Padrão
                        </button>
                        <button type="button" id="previewThemeBtn" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-eye me-2"></i>Pré-visualizar Tema
                        </button>
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
    // Sincronizar campos de cores e texto
    const syncColorFields = function(colorInput, textInput, hiddenInput) {
        colorInput.addEventListener('input', function() {
            textInput.value = this.value;
            if (hiddenInput) {
                hiddenInput.value = this.value;
            }
        });
        
        textInput.addEventListener('input', function() {
            // Verificar se é uma cor válida (formato hex)
            if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                colorInput.value = this.value;
                if (hiddenInput) {
                    hiddenInput.value = this.value;
                }
            }
        });
    };
    
    // Funcionalidade de pré-visualização do tema
    const previewThemeBtn = document.getElementById('previewThemeBtn');
    if (previewThemeBtn) {
        previewThemeBtn.addEventListener('click', function() {
            // Garantir que os valores dos campos hidden estejam atualizados
            document.getElementById('primary_color_hidden').value = document.getElementById('primary_color').value;
            document.getElementById('secondary_color_hidden').value = document.getElementById('secondary_color').value;
            document.getElementById('header_color_hidden').value = document.getElementById('header_color').value;
            
            // Coletar todas as configurações do tema
            const primaryColor = document.getElementById('primary_color').value;
            const secondaryColor = document.getElementById('secondary_color').value;
            const headerColor = document.getElementById('header_color').value;
            const darkMode = document.getElementById('dark_mode').checked ? '1' : '0';
            
            // Criar um formulário temporário para armazenar as configurações em localStorage
            localStorage.setItem('theme_preview', JSON.stringify({
                primary_color: primaryColor,
                secondary_color: secondaryColor,
                header_color: headerColor,
                dark_mode: darkMode
            }));
            
            // Recarregar a página para aplicar as configurações temporárias
            window.location.reload();
        });
    }
    
    // Verificar se há configurações temporárias salvas
    const savedPreview = localStorage.getItem('theme_preview');
    if (savedPreview) {
        try {
            const previewSettings = JSON.parse(savedPreview);
            
            // Adicionar aviso de modo de pré-visualização
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-info alert-dismissible fade show';
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `
                <strong>Modo de pré-visualização do tema!</strong> 
                Você está vendo uma prévia das configurações de tema. Estas alterações não foram salvas.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-primary apply-preview">Aplicar Estas Configurações</button>
                    <button type="button" class="btn btn-sm btn-secondary ms-2 exit-preview">Sair da Pré-visualização</button>
                </div>
            `;
            
            // Inserir alerta no topo
            document.querySelector('.card-body').prepend(alertDiv);
            
            // Adicionar eventos aos botões
            document.querySelector('.apply-preview').addEventListener('click', function() {
                // Aplicar configurações aos campos do formulário
                document.getElementById('primary_color').value = previewSettings.primary_color;
                document.getElementById('primary_color_text').value = previewSettings.primary_color;
                document.getElementById('primary_color_hidden').value = previewSettings.primary_color;
                
                document.getElementById('secondary_color').value = previewSettings.secondary_color;
                document.getElementById('secondary_color_text').value = previewSettings.secondary_color;
                document.getElementById('secondary_color_hidden').value = previewSettings.secondary_color;
                
                document.getElementById('header_color').value = previewSettings.header_color;
                document.getElementById('header_color_text').value = previewSettings.header_color;
                document.getElementById('header_color_hidden').value = previewSettings.header_color;
                
                document.getElementById('dark_mode').checked = previewSettings.dark_mode === '1';
                
                // Remover configurações temporárias
                localStorage.removeItem('theme_preview');
                
                // Garantir que o formulário tenha a ação correta
                const form = document.querySelector('form[method="post"]');
                form.action = '<?php echo url('index.php?route=admin-settings'); ?>';
                
                // Garantir que o input "action" tenha o valor correto
                let actionInput = form.querySelector('input[name="action"]');
                if (!actionInput) {
                    actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    form.appendChild(actionInput);
                }
                actionInput.value = 'save_settings';
                
                // Verificar se há token CSRF
                const csrfInput = form.querySelector('input[name="csrf_token"]');
                if (!csrfInput || !csrfInput.value) {
                    alert('Erro de segurança: Token CSRF ausente. Por favor, recarregue a página.');
                    return;
                }
                
                // Enviar formulário
                form.submit();
            });
            
            document.querySelector('.exit-preview').addEventListener('click', function() {
                // Remover configurações temporárias
                localStorage.removeItem('theme_preview');
                // Recarregar página
                window.location.reload();
            });
            
            // Sobrescrever funções do PHP que obtêm configurações
            window.overrideSettings = {
                primary_color: previewSettings.primary_color,
                secondary_color: previewSettings.secondary_color,
                header_color: previewSettings.header_color,
                dark_mode: previewSettings.dark_mode
            };
            
            // Adicionar CSS em tempo real para simular alterações do tema
            const style = document.createElement('style');
            style.textContent = `
                :root {
                    --primary: ${previewSettings.primary_color} !important;
                    --secondary: ${previewSettings.secondary_color} !important;
                    --header-bg: ${previewSettings.header_color} !important;
                }
                
                /* Ajustar cores do cabeçalho */
                .navbar {
                    background-color: ${previewSettings.header_color} !important;
                    color: ${getTextColorForBackground(previewSettings.header_color)} !important;
                }
                
                .navbar .navbar-brand, 
                .navbar .nav-link,
                .navbar .navbar-text {
                    color: ${getTextColorForBackground(previewSettings.header_color)} !important;
                }
                
                /* Aplicar cores aos botões */
                .btn-primary {
                    background-color: ${previewSettings.primary_color} !important;
                    border-color: ${previewSettings.primary_color} !important;
                    color: ${getTextColorForBackground(previewSettings.primary_color)} !important;
                }
                
                .btn-secondary {
                    background-color: ${previewSettings.secondary_color} !important;
                    border-color: ${previewSettings.secondary_color} !important;
                    color: ${getTextColorForBackground(previewSettings.secondary_color)} !important;
                }
            `;
            
            // Adicionar estilo ao documento
            document.head.appendChild(style);
        } catch (e) {
            console.error('Erro ao carregar pré-visualização do tema:', e);
            localStorage.removeItem('theme_preview');
        }
    }
    
    // Função para determinar a cor do texto com base na cor de fundo
    function getTextColorForBackground(backgroundColor) {
        // Converter hex para rgb
        const hex = backgroundColor.replace('#', '');
        const r = parseInt(hex.substr(0, 2), 16);
        const g = parseInt(hex.substr(2, 2), 16);
        const b = parseInt(hex.substr(4, 2), 16);
        
        // Calcular luminosidade
        const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        
        // Retornar cor do texto baseada na luminosidade
        return luminance > 0.5 ? '#212529' : '#ffffff';
    }
    
    // Configurar sincronização para cada par de campos
    syncColorFields(
        document.getElementById('primary_color'),
        document.getElementById('primary_color_text'),
        document.getElementById('primary_color_hidden')
    );
    
    syncColorFields(
        document.getElementById('secondary_color'),
        document.getElementById('secondary_color_text'),
        document.getElementById('secondary_color_hidden')
    );
    
    syncColorFields(
        document.getElementById('header_color'),
        document.getElementById('header_color_text'),
        document.getElementById('header_color_hidden')
    );
    
    // Gerenciar upload de logo
    const logoUploadInput = document.getElementById('logo_upload');
    const logoUrlInput = document.getElementById('logo_url');
    const logoPreview = document.getElementById('logo_preview');
    const logoPreviewImg = logoPreview.querySelector('img');
    const logoProgress = document.getElementById('logo_upload_progress');
    const progressBar = logoProgress.querySelector('.progress-bar');
    const previewLogoBtn = document.getElementById('preview_logo_btn');
    const logoUrlPreview = document.getElementById('logo_url_preview');
    const logoUrlPreviewImg = logoUrlPreview.querySelector('img');
    
    // Adicionar handler para visualizar o logo atual
    if (previewLogoBtn) {
        previewLogoBtn.addEventListener('click', function() {
            const logoUrl = logoUrlInput.value.trim();
            if (logoUrl) {
                // Exibir imagem de prévia
                logoUrlPreviewImg.src = '<?php echo url(''); ?>' + '/' + logoUrl;
                logoUrlPreview.classList.remove('d-none');
            } else {
                alert('Informe um caminho de arquivo válido para o logo.');
            }
        });
    }
    
    // Verificar se já existe um logo e mostrar botão de prévia
    if (logoUrlInput.value.trim()) {
        previewLogoBtn.classList.remove('d-none');
    } else {
        previewLogoBtn.classList.add('d-none');
    }
    
    // Mostrar/ocultar botão de prévia quando o usuário altera o campo
    logoUrlInput.addEventListener('input', function() {
        if (this.value.trim()) {
            previewLogoBtn.classList.remove('d-none');
        } else {
            previewLogoBtn.classList.add('d-none');
            logoUrlPreview.classList.add('d-none');
        }
    });
    
    if (logoUploadInput) {
        logoUploadInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            
            // Verificar tipo e tamanho do arquivo
            const validTypes = ['image/jpeg', 'image/png', 'image/svg+xml'];
            const maxSize = 2 * 1024 * 1024; // 2MB
            
            if (!validTypes.includes(file.type)) {
                alert('Por favor, selecione uma imagem válida (JPG, PNG ou SVG).');
                this.value = '';
                return;
            }
            
            if (file.size > maxSize) {
                alert('O arquivo é muito grande. O tamanho máximo é 2MB.');
                this.value = '';
                return;
            }
            
            // Mostrar pré-visualização
            const fileReader = new FileReader();
            fileReader.onload = function(e) {
                logoPreviewImg.src = e.target.result;
                logoPreview.classList.remove('d-none');
            };
            fileReader.readAsDataURL(file);
            
            // Enviar arquivo para o servidor
            const formData = new FormData();
            formData.append('logo', file);
            formData.append('csrf_token', '<?php echo $csrf_token; ?>');
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo url('api/upload-logo.php'); ?>', true);
            
            // Monitorar progresso do upload
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percentComplete + '%';
                    progressBar.setAttribute('aria-valuenow', percentComplete);
                    progressBar.textContent = percentComplete + '%';
                    logoProgress.classList.remove('d-none');
                }
            };
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            // Atualizar o campo URL com o caminho do arquivo enviado
                            logoUrlInput.value = response.file_path;
                            
                            // Atualizar a prévia do logo no campo URL também
                            logoUrlPreviewImg.src = '<?php echo url(''); ?>' + '/' + response.file_path;
                            logoUrlPreview.classList.remove('d-none');
                            
                            // Mostrar botão de pré-visualização
                            previewLogoBtn.classList.remove('d-none');
                            
                            alert('Logo enviado com sucesso! Clique em Salvar Configurações para aplicar.');
                        } else {
                            alert('Erro ao enviar logo: ' + (response.error || 'Erro desconhecido'));
                        }
                    } catch (e) {
                        alert('Erro ao processar resposta do servidor.');
                    }
                } else {
                    alert('Erro na comunicação com o servidor.');
                }
                
                // Ocultar barra de progresso
                logoProgress.classList.add('d-none');
            };
            
            xhr.onerror = function() {
                alert('Erro na comunicação com o servidor.');
                logoProgress.classList.add('d-none');
            };
            
            xhr.send(formData);
        });
    }
    
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
    
    // Redefinir tema para valores padrão
    const resetThemeBtn = document.getElementById('resetThemeBtn');
    
    if (resetThemeBtn) {
        resetThemeBtn.addEventListener('click', function() {
            if (confirm('Tem certeza que deseja redefinir o tema para os valores padrão?')) {
                // Alterar estado do botão
                resetThemeBtn.disabled = true;
                resetThemeBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Redefinindo...';
                
                // Fazer requisição AJAX
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo url('api/theme/reset.php'); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    resetThemeBtn.disabled = false;
                    resetThemeBtn.innerHTML = '<i class="fas fa-undo me-2"></i>Redefinir para Padrão';
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            
                            if (response.success) {
                                // Limpar localStorage
                                localStorage.removeItem('theme_preview');
                                
                                // Mostrar mensagem e recarregar
                                alert('Tema redefinido com sucesso! A página será recarregada.');
                                window.location.reload();
                            } else {
                                alert('Erro ao redefinir tema: ' + (response.error || 'Erro desconhecido'));
                            }
                        } catch (e) {
                            alert('Erro ao processar resposta do servidor.');
                        }
                    } else {
                        alert('Erro na comunicação com o servidor.');
                    }
                };
                
                xhr.onerror = function() {
                    resetThemeBtn.disabled = false;
                    resetThemeBtn.innerHTML = '<i class="fas fa-undo me-2"></i>Redefinir para Padrão';
                    alert('Erro na comunicação com o servidor.');
                };
                
                xhr.send('csrf_token=<?php echo $csrf_token; ?>');
            }
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
                            cacheResult.innerHTML = '<div class="alert alert-success">Cache limpo com sucesso! Recarregando a página...</div>';
                            
                            // Forçar recarregamento do CSS dinâmico
                            const themeCss = document.getElementById('dynamic-theme-css');
                            if (themeCss) {
                                const currentSrc = themeCss.getAttribute('href');
                                const baseSrc = currentSrc.split('?')[0];
                                const newSrc = baseSrc + '?v=' + new Date().getTime();
                                themeCss.setAttribute('href', newSrc);
                            }
                            
                            // Limpar qualquer preview de tema
                            localStorage.removeItem('theme_preview');
                            
                            // Recarregar a página após 1 segundo
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
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
