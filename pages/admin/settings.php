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
            'favicon_url' => sanitize($_POST['favicon_url'] ?? ''),
            'dark_mode' => isset($_POST['dark_mode']) ? '1' : '0',
            // Configurações do PWA
            'pwa_enabled' => isset($_POST['pwa_enabled']) ? '1' : '0',
            'pwa_name' => sanitize($_POST['pwa_name'] ?? 'ConCamp - Sistema de Gestão de Contratos Premiados'),
            'pwa_short_name' => sanitize($_POST['pwa_short_name'] ?? 'ConCamp'),
            'pwa_description' => sanitize($_POST['pwa_description'] ?? 'Sistema para gerenciamento de contratos premiados de carros e motos'),
            'pwa_theme_color' => sanitize($_POST['pwa_theme_color_hidden'] ?? $_POST['pwa_theme_color'] ?? '#0d6efd'),
            'pwa_background_color' => sanitize($_POST['pwa_background_color_hidden'] ?? $_POST['pwa_background_color'] ?? '#ffffff'),
            'pwa_icon_url' => sanitize($_POST['pwa_icon_url'] ?? '')
        ];
        
        $conn = getConnection();
        
        try {
            // Debug: Registrar os valores que serão salvos
            error_log("Configurações de tema a serem salvas: " . json_encode([
                'primary_color' => $settings['primary_color'],
                'secondary_color' => $settings['secondary_color'],
                'header_color' => $settings['header_color'],
                'logo_url' => $settings['logo_url'],
                'dark_mode' => $settings['dark_mode']
            ]));
            
            // Iniciar uma transação para garantir que todas as configurações sejam salvas
            $conn->beginTransaction();
            
            foreach ($settings as $key => $value) {
                updateSetting($key, $value);
                if (in_array($key, ['primary_color', 'secondary_color', 'header_color', 'logo_url', 'dark_mode'])) {
                    error_log("Salvando configuração $key = $value");
                }
                
                // Salvar também o token do WhatsApp no perfil do admin atual
                if ($key === 'whatsapp_token' && !empty($value)) {
                    $current_user = getCurrentUser();
                    if ($current_user && $current_user['role'] === 'admin') {
                        $admin_id = $current_user['id'];
                        error_log("Salvando token WhatsApp também para o admin ID: $admin_id");
                        
                        // Atualizar no perfil do usuário admin
                        $stmt = $conn->prepare("UPDATE users SET whatsapp_token = :token, updated_at = NOW() WHERE id = :id");
                        $stmt->execute([
                            'token' => $value,
                            'id' => $admin_id
                        ]);
                    }
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
            // Verificar se há uma transação ativa antes de tentar fazer rollback
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
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
    'favicon_url' => getSetting('favicon_url') ?: '',
    'dark_mode' => getSetting('dark_mode') ?: '0',
    // Configurações do PWA
    'pwa_enabled' => getSetting('pwa_enabled') ?: '0',
    'pwa_name' => getSetting('pwa_name') ?: 'ConCamp - Sistema de Gestão de Contratos Premiados',
    'pwa_short_name' => getSetting('pwa_short_name') ?: 'ConCamp',
    'pwa_description' => getSetting('pwa_description') ?: 'Sistema para gerenciamento de contratos premiados de carros e motos',
    'pwa_theme_color' => getSetting('pwa_theme_color') ?: '#0d6efd',
    'pwa_background_color' => getSetting('pwa_background_color') ?: '#ffffff',
    'pwa_icon_url' => getSetting('pwa_icon_url') ?: ''
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
                    
                    
                    <!-- Seção de Favicon -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">Favicon e Ícones</h6>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="favicon_url" class="form-label">URL do Favicon</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="favicon_url" name="favicon_url" value="<?php echo htmlspecialchars($settings['favicon_url']); ?>" placeholder="assets/img/icons/favicon.ico">
                                <button class="btn btn-outline-secondary" type="button" id="preview_favicon_btn">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Caminho relativo para o arquivo de favicon que aparece na aba do navegador.</small>
                            
                            <div id="favicon_url_preview" class="mt-2 text-center d-none">
                                <p class="mb-1">Favicon atual:</p>
                                <img src="#" alt="Favicon atual" class="img-fluid" style="max-height: 32px;">
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="favicon_upload" class="form-label">Carregar Novo Favicon</label>
                            <input type="file" class="form-control" id="favicon_upload" accept="image/png, image/x-icon, image/svg+xml">
                            <small class="form-text text-muted">Envie um arquivo de imagem para usar como favicon (ICO, PNG ou SVG).</small>
                            <div id="favicon_upload_progress" class="progress mt-2 d-none">
                                <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div id="favicon_preview" class="mt-2 text-center d-none">
                                <p class="mb-1">Pré-visualização do favicon:</p>
                                <img src="#" alt="Favicon preview" class="img-fluid" style="max-height: 32px;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Seção do PWA -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">Progressive Web App (PWA)</h6>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="pwa_enabled" name="pwa_enabled" value="1" <?php echo $settings['pwa_enabled'] === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="pwa_enabled">Ativar Progressive Web App (PWA)</label>
                            </div>
                            <small class="form-text text-muted">Permite que o sistema seja instalado como um aplicativo em dispositivos móveis e desktops.</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="pwa_name" class="form-label">Nome do Aplicativo</label>
                            <input type="text" class="form-control" id="pwa_name" name="pwa_name" value="<?php echo htmlspecialchars($settings['pwa_name']); ?>">
                            <small class="form-text text-muted">Nome completo do aplicativo exibido na instalação.</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="pwa_short_name" class="form-label">Nome Curto</label>
                            <input type="text" class="form-control" id="pwa_short_name" name="pwa_short_name" value="<?php echo htmlspecialchars($settings['pwa_short_name']); ?>">
                            <small class="form-text text-muted">Nome curto exibido abaixo do ícone na tela inicial.</small>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="pwa_description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="pwa_description" name="pwa_description" rows="2"><?php echo htmlspecialchars($settings['pwa_description']); ?></textarea>
                            <small class="form-text text-muted">Descrição breve do aplicativo exibida durante a instalação.</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="pwa_theme_color" class="form-label">Cor do Tema</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="pwa_theme_color" name="pwa_theme_color" value="<?php echo htmlspecialchars($settings['pwa_theme_color']); ?>">
                                <input type="hidden" name="pwa_theme_color_hidden" id="pwa_theme_color_hidden" value="<?php echo htmlspecialchars($settings['pwa_theme_color']); ?>">
                                <input type="text" class="form-control" id="pwa_theme_color_text" value="<?php echo htmlspecialchars($settings['pwa_theme_color']); ?>">
                            </div>
                            <small class="form-text text-muted">Cor do tema exibida na barra de título do aplicativo.</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="pwa_background_color" class="form-label">Cor de Fundo</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="pwa_background_color" name="pwa_background_color" value="<?php echo htmlspecialchars($settings['pwa_background_color']); ?>">
                                <input type="hidden" name="pwa_background_color_hidden" id="pwa_background_color_hidden" value="<?php echo htmlspecialchars($settings['pwa_background_color']); ?>">
                                <input type="text" class="form-control" id="pwa_background_color_text" value="<?php echo htmlspecialchars($settings['pwa_background_color']); ?>">
                            </div>
                            <small class="form-text text-muted">Cor de fundo exibida durante o carregamento do aplicativo.</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="pwa_icon_url" class="form-label">URL do Ícone PWA</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="pwa_icon_url" name="pwa_icon_url" value="<?php echo htmlspecialchars($settings['pwa_icon_url']); ?>" placeholder="assets/img/icons/icon-512x512.png">
                                <button class="btn btn-outline-secondary" type="button" id="preview_pwa_icon_btn">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Caminho relativo para o arquivo de ícone do PWA (recomendado: 512x512px).</small>
                            
                            <div id="pwa_icon_url_preview" class="mt-2 text-center d-none">
                                <p class="mb-1">Ícone PWA atual:</p>
                                <img src="#" alt="Ícone PWA atual" class="img-fluid" style="max-height: 80px;">
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="pwa_icon_upload" class="form-label">Carregar Novo Ícone PWA</label>
                            <input type="file" class="form-control" id="pwa_icon_upload" accept="image/png, image/jpeg, image/svg+xml">
                            <small class="form-text text-muted">Envie um arquivo de imagem para usar como ícone do PWA (PNG, JPG ou SVG).</small>
                            <div id="pwa_icon_upload_progress" class="progress mt-2 d-none">
                                <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div id="pwa_icon_preview" class="mt-2 text-center d-none">
                                <p class="mb-1">Pré-visualização do ícone PWA:</p>
                                <img src="#" alt="Ícone PWA preview" class="img-fluid" style="max-height: 80px;">
                            </div>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> 
                                O sistema gerará automaticamente ícones em vários tamanhos a partir do ícone principal. Para mais informações, consulte a <a href="/PWA.md" target="_blank">documentação do PWA</a>.
                            </div>
                            
                            <button type="button" class="btn btn-outline-secondary mt-3" id="testPwaBtn">
                                <i class="fas fa-vial me-2"></i>Testar Funcionamento do PWA
                            </button>
                            
                            <div id="pwaTestResult" class="mt-3" style="display: none;"></div>
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
    // Função utilitária para gerenciar uploads de imagens
    const setupImageUpload = function(uploadInput, urlInput, previewElement, progressElement, uploadApiUrl, previewButtonId, previewUrlElement) {
        const uploadHandler = function() {
            const file = uploadInput.files[0];
            if (!file) return;
            
            console.log('Arquivo selecionado:', file.name, file.type, file.size, 'bytes');
            
            // Verificar tipo e tamanho do arquivo
            const validTypes = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon'];
            const maxSize = 2 * 1024 * 1024; // 2MB
            
            if (!validTypes.includes(file.type)) {
                alert('Por favor, selecione uma imagem válida (JPG, PNG, SVG ou ICO).');
                uploadInput.value = '';
                return;
            }
            
            if (file.size > maxSize) {
                alert('O arquivo é muito grande. O tamanho máximo é 2MB.');
                uploadInput.value = '';
                return;
            }
            
            // Mostrar pré-visualização
            const fileReader = new FileReader();
            fileReader.onload = function(e) {
                const previewImg = previewElement.querySelector('img');
                previewImg.src = e.target.result;
                previewElement.classList.remove('d-none');
            };
            fileReader.readAsDataURL(file);
            
            // Enviar arquivo para o servidor
            const formData = new FormData();
            formData.append('image', file);
            // Usamos o API de teste simplificado
            const useSimplifiedApi = true;
            const finalApiUrl = useSimplifiedApi ? '<?php echo url('api/upload-simplified.php'); ?>' : uploadApiUrl;
            
            console.log('Enviando para:', finalApiUrl);
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', finalApiUrl, true);
            
            // Monitorar progresso do upload
            const progressBar = progressElement.querySelector('.progress-bar');
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percentComplete + '%';
                    progressBar.setAttribute('aria-valuenow', percentComplete);
                    progressBar.textContent = percentComplete + '%';
                    progressElement.classList.remove('d-none');
                    console.log('Progresso do upload:', percentComplete + '%');
                }
            };
            
            xhr.onload = function() {
                console.log('Resposta do servidor:', xhr.status, xhr.responseText);
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        console.log('Resposta parseada:', response);
                        
                        if (response.success) {
                            // Atualizar o campo URL com o caminho do arquivo enviado
                            urlInput.value = response.file_path;
                            
                            // Atualizar a prévia do URL também
                            if (previewUrlElement) {
                                const previewUrlImg = previewUrlElement.querySelector('img');
                                previewUrlImg.src = '<?php echo url(''); ?>' + '/' + response.file_path;
                                previewUrlElement.classList.remove('d-none');
                            }
                            
                            // Mostrar botão de pré-visualização
                            const previewBtn = document.getElementById(previewButtonId);
                            if (previewBtn) {
                                previewBtn.classList.remove('d-none');
                            }
                            
                            alert('Imagem enviada com sucesso! Clique em Salvar Configurações para aplicar.');
                        } else {
                            alert('Erro ao enviar imagem: ' + (response.error || 'Erro desconhecido'));
                        }
                    } catch (e) {
                        console.error('Erro ao processar JSON:', e);
                        alert('Erro ao processar resposta do servidor: ' + e.message);
                    }
                } else {
                    alert('Erro na comunicação com o servidor. Status: ' + xhr.status);
                }
                
                // Ocultar barra de progresso
                progressElement.classList.add('d-none');
            };
            
            xhr.onerror = function(e) {
                console.error('Erro na requisição:', e);
                alert('Erro na comunicação com o servidor.');
                progressElement.classList.add('d-none');
            };
            
            xhr.send(formData);
        };
        
        // Adicionar handler ao input de upload
        if (uploadInput) {
            uploadInput.addEventListener('change', uploadHandler);
        }
        
        // Adicionar handler para visualizar a imagem atual
        const previewBtn = document.getElementById(previewButtonId);
        if (previewBtn && previewUrlElement) {
            previewBtn.addEventListener('click', function() {
                const imageUrl = urlInput.value.trim();
                if (imageUrl) {
                    // Exibir imagem de prévia
                    const previewImg = previewUrlElement.querySelector('img');
                    previewImg.src = '<?php echo url(''); ?>' + '/' + imageUrl;
                    previewUrlElement.classList.remove('d-none');
                } else {
                    alert('Informe um caminho de arquivo válido para a imagem.');
                }
            });
        }
        
        // Verificar se já existe uma imagem e mostrar botão de prévia
        if (previewBtn) {
            if (urlInput.value.trim()) {
                previewBtn.classList.remove('d-none');
            } else {
                previewBtn.classList.add('d-none');
            }
            
            // Mostrar/ocultar botão de prévia quando o usuário altera o campo
            urlInput.addEventListener('input', function() {
                if (this.value.trim()) {
                    previewBtn.classList.remove('d-none');
                } else {
                    previewBtn.classList.add('d-none');
                    if (previewUrlElement) {
                        previewUrlElement.classList.add('d-none');
                    }
                }
            });
        }
    };
    
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
    
    // Configurar upload de imagens (logo, favicon, ícone PWA)
    
    // Logo
    setupImageUpload(
        document.getElementById('logo_upload'),
        document.getElementById('logo_url'),
        document.getElementById('logo_preview'),
        document.getElementById('logo_upload_progress'),
        '<?php echo url('api/upload-logo.php'); ?>',
        'preview_logo_btn',
        document.getElementById('logo_url_preview')
    );
    
    // Favicon
    setupImageUpload(
        document.getElementById('favicon_upload'),
        document.getElementById('favicon_url'),
        document.getElementById('favicon_preview'),
        document.getElementById('favicon_upload_progress'),
        '<?php echo url('api/upload-favicon.php'); ?>',
        'preview_favicon_btn',
        document.getElementById('favicon_url_preview')
    );
    
    // Ícone PWA
    setupImageUpload(
        document.getElementById('pwa_icon_upload'),
        document.getElementById('pwa_icon_url'),
        document.getElementById('pwa_icon_preview'),
        document.getElementById('pwa_icon_upload_progress'),
        '<?php echo url('api/upload-pwa-icon.php'); ?>',
        'preview_pwa_icon_btn',
        document.getElementById('pwa_icon_url_preview')
    );
    
    // Sincronizar cores do PWA
    syncColorFields(
        document.getElementById('pwa_theme_color'),
        document.getElementById('pwa_theme_color_text'),
        document.getElementById('pwa_theme_color_hidden')
    );
    
    syncColorFields(
        document.getElementById('pwa_background_color'),
        document.getElementById('pwa_background_color_text'),
        document.getElementById('pwa_background_color_hidden')
    );
    
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
    
    // Teste de PWA
    const testPwaBtn = document.getElementById('testPwaBtn');
    const pwaTestResult = document.getElementById('pwaTestResult');
    
    if (testPwaBtn) {
        testPwaBtn.addEventListener('click', function() {
            // Alterar estado do botão
            testPwaBtn.disabled = true;
            testPwaBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Testando...';
            
            // Fazer requisição AJAX - usamos GET para evitar problemas com o CSRF
            const xhr = new XMLHttpRequest();
            xhr.open('GET', '<?php echo url('api/test-pwa-direct.php'); ?>', true);
            xhr.onload = function() {
                testPwaBtn.disabled = false;
                testPwaBtn.innerHTML = '<i class="fas fa-vial me-2"></i>Testar Funcionamento do PWA';
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            let resultHtml = '<div class="card border-0 shadow-sm">';
                            resultHtml += '<div class="card-header bg-white"><h6 class="mb-0">Resultado do Teste do PWA</h6></div>';
                            resultHtml += '<div class="card-body">';
                            
                            // Status geral
                            if (response.has_issues) {
                                resultHtml += '<div class="alert alert-warning">';
                                resultHtml += '<strong><i class="fas fa-exclamation-triangle me-2"></i>Foram encontrados problemas que podem afetar o funcionamento do PWA.</strong>';
                                resultHtml += '</div>';
                            } else {
                                resultHtml += '<div class="alert alert-success">';
                                resultHtml += '<strong><i class="fas fa-check-circle me-2"></i>Todos os testes passaram com sucesso!</strong>';
                                resultHtml += '</div>';
                            }
                            
                            // Diretórios
                            resultHtml += '<h6 class="mt-4 mb-3 border-bottom pb-2">Verificação de Diretórios</h6>';
                            resultHtml += '<ul class="list-group mb-3">';
                            resultHtml += '<li class="list-group-item d-flex justify-content-between align-items-center">';
                            resultHtml += 'Diretório de ícones existe';
                            resultHtml += response.tests.directories.icons_dir_exists ? 
                                '<span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>' : 
                                '<span class="badge bg-danger rounded-pill"><i class="fas fa-times"></i></span>';
                            resultHtml += '</li>';
                            
                            resultHtml += '<li class="list-group-item d-flex justify-content-between align-items-center">';
                            resultHtml += 'Diretório de ícones tem permissão de escrita';
                            resultHtml += response.tests.directories.icons_dir_writable ? 
                                '<span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>' : 
                                '<span class="badge bg-danger rounded-pill"><i class="fas fa-times"></i></span>';
                            resultHtml += '</li>';
                            resultHtml += '</ul>';
                            
                            // Arquivos
                            resultHtml += '<h6 class="mt-4 mb-3 border-bottom pb-2">Verificação de Arquivos</h6>';
                            resultHtml += '<ul class="list-group mb-3">';
                            
                            resultHtml += '<li class="list-group-item d-flex justify-content-between align-items-center">';
                            resultHtml += 'Arquivo manifest.json existe';
                            resultHtml += response.tests.files.manifest_exists ? 
                                '<span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>' : 
                                '<span class="badge bg-danger rounded-pill"><i class="fas fa-times"></i></span>';
                            resultHtml += '</li>';
                            
                            resultHtml += '<li class="list-group-item d-flex justify-content-between align-items-center">';
                            resultHtml += 'Arquivo .htaccess existe';
                            resultHtml += response.tests.files.htaccess_exists ? 
                                '<span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>' : 
                                '<span class="badge bg-danger rounded-pill"><i class="fas fa-times"></i></span>';
                            resultHtml += '</li>';
                            
                            resultHtml += '<li class="list-group-item d-flex justify-content-between align-items-center">';
                            resultHtml += 'Regra de reescrita no .htaccess configurada';
                            resultHtml += response.tests.files.htaccess_has_rewrite ? 
                                '<span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>' : 
                                '<span class="badge bg-danger rounded-pill"><i class="fas fa-times"></i></span>';
                            resultHtml += '</li>';
                            
                            resultHtml += '<li class="list-group-item d-flex justify-content-between align-items-center">';
                            resultHtml += 'Service Worker existe';
                            resultHtml += response.tests.files.service_worker_exists ? 
                                '<span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>' : 
                                '<span class="badge bg-danger rounded-pill"><i class="fas fa-times"></i></span>';
                            resultHtml += '</li>';
                            
                            resultHtml += '<li class="list-group-item d-flex justify-content-between align-items-center">';
                            resultHtml += 'Script PWA existe';
                            resultHtml += response.tests.files.pwa_script_exists ? 
                                '<span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>' : 
                                '<span class="badge bg-danger rounded-pill"><i class="fas fa-times"></i></span>';
                            resultHtml += '</li>';
                            resultHtml += '</ul>';
                            
                            // Extensões PHP
                            resultHtml += '<h6 class="mt-4 mb-3 border-bottom pb-2">Verificação de Extensões PHP</h6>';
                            resultHtml += '<ul class="list-group mb-3">';
                            resultHtml += '<li class="list-group-item d-flex justify-content-between align-items-center">';
                            resultHtml += 'Extensão GD ativada';
                            resultHtml += response.tests.php_extensions.gd_enabled ? 
                                '<span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>' : 
                                '<span class="badge bg-warning rounded-pill"><i class="fas fa-exclamation"></i></span>';
                            resultHtml += '</li>';
                            
                            resultHtml += '<li class="list-group-item d-flex justify-content-between align-items-center">';
                            resultHtml += 'Extensão Imagick ativada';
                            resultHtml += response.tests.php_extensions.imagick_enabled ? 
                                '<span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>' : 
                                '<span class="badge bg-warning rounded-pill"><i class="fas fa-exclamation"></i></span>';
                            resultHtml += '</li>';
                            resultHtml += '</ul>';
                            
                            // Configurações
                            resultHtml += '<h6 class="mt-4 mb-3 border-bottom pb-2">Verificação de Configurações</h6>';
                            resultHtml += '<ul class="list-group mb-3">';
                            resultHtml += '<li class="list-group-item d-flex justify-content-between align-items-center">';
                            resultHtml += 'PWA ativado nas configurações';
                            resultHtml += response.tests.settings.pwa_enabled ? 
                                '<span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>' : 
                                '<span class="badge bg-warning rounded-pill"><i class="fas fa-exclamation"></i></span>';
                            resultHtml += '</li>';
                            
                            resultHtml += '<li class="list-group-item d-flex justify-content-between align-items-center">';
                            resultHtml += 'Ícone PWA configurado e existe';
                            resultHtml += response.tests.settings.pwa_icon_exists ? 
                                '<span class="badge bg-success rounded-pill"><i class="fas fa-check"></i></span>' : 
                                '<span class="badge bg-warning rounded-pill"><i class="fas fa-exclamation"></i></span>';
                            resultHtml += '</li>';
                            resultHtml += '</ul>';
                            
                            // Problemas encontrados
                            if (response.issues && response.issues.length > 0) {
                                resultHtml += '<h6 class="mt-4 mb-3 border-bottom pb-2">Problemas Encontrados</h6>';
                                resultHtml += '<div class="alert alert-danger">';
                                resultHtml += '<ul class="mb-0">';
                                response.issues.forEach(issue => {
                                    resultHtml += '<li>' + issue + '</li>';
                                });
                                resultHtml += '</ul>';
                                resultHtml += '</div>';
                            }
                            
                            resultHtml += '</div></div>';
                            
                            pwaTestResult.innerHTML = resultHtml;
                        } else {
                            pwaTestResult.innerHTML = '<div class="alert alert-danger">Erro ao testar PWA: ' + (response.error || 'Erro desconhecido') + '</div>';
                        }
                    } catch (e) {
                        pwaTestResult.innerHTML = '<div class="alert alert-danger">Erro ao processar resposta do servidor: ' + e.message + '</div>';
                    }
                } else {
                    pwaTestResult.innerHTML = '<div class="alert alert-danger">Erro na comunicação com o servidor.</div>';
                }
                
                pwaTestResult.style.display = 'block';
            };
            
            xhr.onerror = function() {
                testPwaBtn.disabled = false;
                testPwaBtn.innerHTML = '<i class="fas fa-vial me-2"></i>Testar Funcionamento do PWA';
                pwaTestResult.innerHTML = '<div class="alert alert-danger">Erro na comunicação com o servidor.</div>';
                pwaTestResult.style.display = 'block';
            };
            
            // Testar sem enviar token CSRF já que desabilitamos a verificação no endpoint
            xhr.send();
        });
    }
});
</script>
