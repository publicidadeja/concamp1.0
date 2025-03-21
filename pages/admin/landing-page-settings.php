<?php
/**
 * Configurações da Landing Page
 */

// Título da página
$page_title = 'Configurações da Landing Page';

// Verificar permissão
if (!isAdmin()) {
    include_once __DIR__ . '/../access-denied.php';
    exit;
}

// Processar ações
$message = '';
$error = '';

// Salvar configurações de cores da landing page
if (isset($_POST['action']) && $_POST['action'] === 'save_lp_settings') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $settings = [
            'lp_hero_bg_color' => sanitize($_POST['lp_hero_bg_color'] ?? '#00053c'),
            'lp_hero_text_color' => sanitize($_POST['lp_hero_text_color'] ?? '#ffffff'),
            'lp_primary_color' => sanitize($_POST['lp_primary_color'] ?? '#00053c'),
            'lp_secondary_color' => sanitize($_POST['lp_secondary_color'] ?? '#4e5055'),
            'lp_accent_color' => sanitize($_POST['lp_accent_color'] ?? '#ff9800'),
            'lp_benefits_bg_color' => sanitize($_POST['lp_benefits_bg_color'] ?? '#f9f9f9'),
            'lp_testimonials_bg_color' => sanitize($_POST['lp_testimonials_bg_color'] ?? '#ffffff'),
            'lp_winners_bg_color' => sanitize($_POST['lp_winners_bg_color'] ?? '#f5f7fa'),
            'lp_urgency_bg_color' => sanitize($_POST['lp_urgency_bg_color'] ?? '#00053c'),
            'lp_footer_bg_color' => sanitize($_POST['lp_footer_bg_color'] ?? '#343a40'),
            'lp_footer_text_color' => sanitize($_POST['lp_footer_text_color'] ?? '#ffffff'),
            'lp_button_style' => sanitize($_POST['lp_button_style'] ?? 'rounded')
        ];
        
        $conn = getConnection();
        
        // Iniciar uma transação para garantir que todas as configurações sejam salvas
        $conn->beginTransaction();
        
        try {
            foreach ($settings as $key => $value) {
                updateSetting($key, $value);
            }
            
            // Atualizar a versão do tema para forçar o recarregamento do CSS
            $theme_version = time();
            updateSetting('lp_theme_version', $theme_version);
            
            // Gerar CSS personalizado para landing page
            generateLandingPageCSS($settings);
            
            $conn->commit();
            $message = 'Configurações da landing page salvas com sucesso!';
            
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = 'Erro ao salvar configurações: ' . $e->getMessage();
        }
    }
}

// Gerar CSS personalizado para landing page
function generateLandingPageCSS($settings) {
    // Gerar o conteúdo CSS
    $css = "/* 
 * ConCamp - CSS personalizado da Landing Page
 * Gerado automaticamente em " . date('Y-m-d H:i:s') . "
 */

/* Cores principais da landing page */
:root {
    --lp-primary: {$settings['lp_primary_color']};
    --lp-primary-dark: " . adjustBrightness($settings['lp_primary_color'], -0.2) . ";
    --lp-primary-light: " . adjustBrightness($settings['lp_primary_color'], 0.2) . ";
    --lp-secondary: {$settings['lp_secondary_color']};
    --lp-accent: {$settings['lp_accent_color']};
    --lp-hero-bg: {$settings['lp_hero_bg_color']};
    --lp-hero-text: {$settings['lp_hero_text_color']};
    --lp-footer-bg-color: {$settings['lp_footer_bg_color']};
    --lp-footer-text-color: {$settings['lp_footer_text_color']};
}

/* ATTENTION (Hero section) */
.lp-hero {
    background: linear-gradient(135deg, var(--lp-hero-bg) 0%, " . adjustBrightness($settings['lp_hero_bg_color'], -0.2) . " 100%);
    color: {$settings['lp_hero_text_color']};
}

.lp-cta-btn {
    background-color: {$settings['lp_accent_color']} !important;
    border-color: {$settings['lp_accent_color']} !important;
    color: " . getContrastColor($settings['lp_accent_color']) . " !important;
    " . ($settings['lp_button_style'] === 'rounded' ? 'border-radius: 50px;' : 'border-radius: 8px;') . "
}

.lp-cta-btn:hover {
    background-color: " . adjustBrightness($settings['lp_accent_color'], -0.1) . " !important;
    border-color: " . adjustBrightness($settings['lp_accent_color'], -0.1) . " !important;
}

.lp-badge {
    background: {$settings['lp_accent_color']};
    color: " . getContrastColor($settings['lp_accent_color']) . ";
}

/* INTEREST (Benefícios) */
.lp-benefits {
    background-color: {$settings['lp_benefits_bg_color']};
}

.lp-benefit-icon {
    background-color: " . hexToRgba($settings['lp_primary_color'], 0.1) . ";
    color: {$settings['lp_primary_color']};
}

/* INTEREST (Depoimentos) */
.lp-testimonials {
    background-color: {$settings['lp_testimonials_bg_color']};
}

.lp-testimonial-card::before {
    color: " . hexToRgba($settings['lp_primary_color'], 0.1) . ";
}

/* DESIRE (Ganhadores) */
.lp-winners {
    background: linear-gradient(135deg, {$settings['lp_winners_bg_color']} 0%, " . adjustBrightness($settings['lp_winners_bg_color'], -0.1) . " 100%);
}

/* DESIRE (Urgência) */
.lp-urgency {
    background-color: {$settings['lp_urgency_bg_color']};
    color: " . getContrastColor($settings['lp_urgency_bg_color']) . ";
}

.lp-countdown-item {
    background: " . hexToRgba(getContrastColor($settings['lp_urgency_bg_color']), 0.15) . ";
}

/* Footer */
.lp-footer {
    background-color: var(--lp-footer-bg-color);
    color: var(--lp-footer-text-color);
}

.lp-footer-contact-icon,
.lp-footer h5 {
    color: var(--lp-primary) !important;
}

.lp-footer-social-item:hover {
    background-color: var(--lp-primary);
}

/* WhatsApp Button */
.lp-whatsapp-btn {
    background-color: #25D366;
    color: #ffffff;
}

/* Responsividade */
@media (max-width: 991.98px) {
    /* Manter o estilo responsivo */
}
";

    // Caminho para salvar o arquivo CSS
    $cssFilePath = __DIR__ . '/../../assets/css/landing-page-theme.css';
    
    // Salvar o arquivo
    file_put_contents($cssFilePath, $css);
    
    return true;
}

// Função para ajustar o brilho de uma cor hexadecimal
function adjustBrightness($hex, $steps) {
    // Remover o # se presente
    $hex = ltrim($hex, '#');
    
    // Converter para RGB
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Ajustar o brilho
    $r = max(0, min(255, $r + 255 * $steps));
    $g = max(0, min(255, $g + 255 * $steps));
    $b = max(0, min(255, $b + 255 * $steps));
    
    // Converter de volta para hex
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

// Função para converter hex para rgba
function hexToRgba($hex, $alpha = 1) {
    // Remover o # se presente
    $hex = ltrim($hex, '#');
    
    // Converter para RGB
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    return "rgba($r, $g, $b, $alpha)";
}

// Função para obter a cor de contraste com base na luminosidade
function getContrastColor($hex) {
    // Remover o # se presente
    $hex = ltrim($hex, '#');
    
    // Converter para RGB
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Calcular luminosidade
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    
    return $luminance > 0.5 ? '#212529' : '#ffffff';
}

// Obter configurações atuais
$lp_settings = [
    'lp_hero_bg_color' => getSetting('lp_hero_bg_color') ?: '#00053c',
    'lp_hero_text_color' => getSetting('lp_hero_text_color') ?: '#ffffff',
    'lp_primary_color' => getSetting('lp_primary_color') ?: '#00053c',
    'lp_secondary_color' => getSetting('lp_secondary_color') ?: '#4e5055',
    'lp_accent_color' => getSetting('lp_accent_color') ?: '#ff9800',
    'lp_benefits_bg_color' => getSetting('lp_benefits_bg_color') ?: '#f9f9f9',
    'lp_testimonials_bg_color' => getSetting('lp_testimonials_bg_color') ?: '#ffffff',
    'lp_winners_bg_color' => getSetting('lp_winners_bg_color') ?: '#f5f7fa',
    'lp_urgency_bg_color' => getSetting('lp_urgency_bg_color') ?: '#00053c',
    'lp_footer_bg_color' => getSetting('lp_footer_bg_color') ?: '#343a40',
    'lp_footer_text_color' => getSetting('lp_footer_text_color') ?: '#ffffff',
    'lp_button_style' => getSetting('lp_button_style') ?: 'rounded'
];

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
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Configurações de Cores da Landing Page</h5>
                <a href="<?php echo url('index.php'); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye me-1"></i>Ver Landing Page
                </a>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo url('index.php?route=admin-landing-page-settings'); ?>">
                    <input type="hidden" name="action" value="save_lp_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Configure as cores da landing page para personalizar a experiência dos seus clientes. As cores escolhidas aqui serão aplicadas em todas as landing pages de vendedores.
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">Cores Principais</h6>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="lp_primary_color" class="form-label">Cor Principal</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="lp_primary_color" name="lp_primary_color" value="<?php echo htmlspecialchars($lp_settings['lp_primary_color']); ?>">
                                <input type="text" class="form-control" id="lp_primary_color_text" value="<?php echo htmlspecialchars($lp_settings['lp_primary_color']); ?>">
                            </div>
                            <small class="form-text text-muted">Cor principal da landing page (cabeçalhos, botões principais).</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="lp_secondary_color" class="form-label">Cor Secundária</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="lp_secondary_color" name="lp_secondary_color" value="<?php echo htmlspecialchars($lp_settings['lp_secondary_color']); ?>">
                                <input type="text" class="form-control" id="lp_secondary_color_text" value="<?php echo htmlspecialchars($lp_settings['lp_secondary_color']); ?>">
                            </div>
                            <small class="form-text text-muted">Cor secundária (elementos menos destacados).</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="lp_accent_color" class="form-label">Cor de Destaque</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="lp_accent_color" name="lp_accent_color" value="<?php echo htmlspecialchars($lp_settings['lp_accent_color']); ?>">
                                <input type="text" class="form-control" id="lp_accent_color_text" value="<?php echo htmlspecialchars($lp_settings['lp_accent_color']); ?>">
                            </div>
                            <small class="form-text text-muted">Cor de ênfase para botões de chamada para ação (CTA).</small>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">Seções da Landing Page</h6>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="lp_hero_bg_color" class="form-label">Cor de Fundo do Hero</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="lp_hero_bg_color" name="lp_hero_bg_color" value="<?php echo htmlspecialchars($lp_settings['lp_hero_bg_color']); ?>">
                                <input type="text" class="form-control" id="lp_hero_bg_color_text" value="<?php echo htmlspecialchars($lp_settings['lp_hero_bg_color']); ?>">
                            </div>
                            <small class="form-text text-muted">Cor de fundo da seção principal (hero) da landing page.</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="lp_hero_text_color" class="form-label">Cor do Texto do Hero</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="lp_hero_text_color" name="lp_hero_text_color" value="<?php echo htmlspecialchars($lp_settings['lp_hero_text_color']); ?>">
                                <input type="text" class="form-control" id="lp_hero_text_color_text" value="<?php echo htmlspecialchars($lp_settings['lp_hero_text_color']); ?>">
                            </div>
                            <small class="form-text text-muted">Cor do texto da seção principal (hero) da landing page.</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="lp_benefits_bg_color" class="form-label">Fundo da Seção de Benefícios</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="lp_benefits_bg_color" name="lp_benefits_bg_color" value="<?php echo htmlspecialchars($lp_settings['lp_benefits_bg_color']); ?>">
                                <input type="text" class="form-control" id="lp_benefits_bg_color_text" value="<?php echo htmlspecialchars($lp_settings['lp_benefits_bg_color']); ?>">
                            </div>
                            <small class="form-text text-muted">Cor de fundo da seção de benefícios.</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="lp_testimonials_bg_color" class="form-label">Fundo da Seção de Depoimentos</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="lp_testimonials_bg_color" name="lp_testimonials_bg_color" value="<?php echo htmlspecialchars($lp_settings['lp_testimonials_bg_color']); ?>">
                                <input type="text" class="form-control" id="lp_testimonials_bg_color_text" value="<?php echo htmlspecialchars($lp_settings['lp_testimonials_bg_color']); ?>">
                            </div>
                            <small class="form-text text-muted">Cor de fundo da seção de depoimentos.</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="lp_winners_bg_color" class="form-label">Fundo da Seção de Ganhadores</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="lp_winners_bg_color" name="lp_winners_bg_color" value="<?php echo htmlspecialchars($lp_settings['lp_winners_bg_color']); ?>">
                                <input type="text" class="form-control" id="lp_winners_bg_color_text" value="<?php echo htmlspecialchars($lp_settings['lp_winners_bg_color']); ?>">
                            </div>
                            <small class="form-text text-muted">Cor de fundo da seção de clientes contemplados.</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="lp_urgency_bg_color" class="form-label">Fundo da Seção de Urgência</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="lp_urgency_bg_color" name="lp_urgency_bg_color" value="<?php echo htmlspecialchars($lp_settings['lp_urgency_bg_color']); ?>">
                                <input type="text" class="form-control" id="lp_urgency_bg_color_text" value="<?php echo htmlspecialchars($lp_settings['lp_urgency_bg_color']); ?>">
                            </div>
                            <small class="form-text text-muted">Cor de fundo da seção de contagem regressiva.</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="lp_footer_bg_color" class="form-label">Fundo do Rodapé</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="lp_footer_bg_color" name="lp_footer_bg_color" value="<?php echo htmlspecialchars($lp_settings['lp_footer_bg_color']); ?>">
                                <input type="text" class="form-control" id="lp_footer_bg_color_text" value="<?php echo htmlspecialchars($lp_settings['lp_footer_bg_color']); ?>">
                            </div>
                            <small class="form-text text-muted">Cor de fundo do rodapé da landing page.</small>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">Cores do Texto do Rodapé</h6>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="lp_footer_text_color" class="form-label">Cor do Texto do Rodapé</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="lp_footer_text_color" name="lp_footer_text_color" value="<?php echo htmlspecialchars(isset($lp_settings['lp_footer_text_color']) && $lp_settings['lp_footer_text_color'] ? str_replace('rgba', 'rgb', str_replace([',0.7)', ')'], [')', ')'], $lp_settings['lp_footer_text_color'])) : '#ffffff'); ?>">
                                <input type="text" class="form-control" id="lp_footer_text_color_text" value="<?php echo htmlspecialchars($lp_settings['lp_footer_text_color'] ?? '#ffffff'); ?>">
                            </div>
                            <small class="form-text text-muted">Cor do texto no rodapé da landing page. Você pode usar um valor RGBA para transparência.</small>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="border-bottom pb-2 mb-3">Estilos Adicionais</h6>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label d-block">Estilo dos Botões</label>
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="lp_button_style" id="btn-rounded" value="rounded" <?php echo $lp_settings['lp_button_style'] === 'rounded' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary" for="btn-rounded">Arredondados</label>
                                
                                <input type="radio" class="btn-check" name="lp_button_style" id="btn-square" value="square" <?php echo $lp_settings['lp_button_style'] === 'square' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary" for="btn-square">Quadrados</label>
                            </div>
                            <div class="mt-2">
                                <small class="form-text text-muted">Define o estilo dos botões de chamada para ação na landing page.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div id="lp-preview-container" class="border rounded p-3 mb-3" style="display: none;">
                                <h6 class="mb-3">Pré-visualização:</h6>
                                <div class="lp-color-preview d-flex flex-wrap">
                                    <!-- Preview será gerado via JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" id="previewColorsBtn" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-eye me-2"></i>Pré-visualizar Cores
                        </button>
                        <button type="button" id="resetColorsBtn" class="btn btn-outline-danger me-2">
                            <i class="fas fa-undo me-2"></i>Redefinir para Padrão
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sincronizar campos de cores e texto
    function syncColorFields(colorInput, textInput) {
        // Atualizar o campo de texto quando o seletor de cor mudar
        colorInput.addEventListener('input', function() {
            textInput.value = this.value;
            updatePreview();
        });
        
        // Atualizar o seletor de cor quando o campo de texto mudar (se for uma cor válida)
        textInput.addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                colorInput.value = this.value;
                updatePreview();
            }
        });
    }
    
    // Configurar sincronização para todos os pares de campos de cor
    const colorPairs = [
        ['lp_primary_color', 'lp_primary_color_text'],
        ['lp_secondary_color', 'lp_secondary_color_text'],
        ['lp_accent_color', 'lp_accent_color_text'],
        ['lp_hero_bg_color', 'lp_hero_bg_color_text'],
        ['lp_hero_text_color', 'lp_hero_text_color_text'],
        ['lp_benefits_bg_color', 'lp_benefits_bg_color_text'],
        ['lp_testimonials_bg_color', 'lp_testimonials_bg_color_text'],
        ['lp_winners_bg_color', 'lp_winners_bg_color_text'],
        ['lp_urgency_bg_color', 'lp_urgency_bg_color_text'],
        ['lp_footer_bg_color', 'lp_footer_bg_color_text'],
        ['lp_footer_text_color', 'lp_footer_text_color_text']
    ];
    
    colorPairs.forEach(pair => {
        const colorInput = document.getElementById(pair[0]);
        const textInput = document.getElementById(pair[1]);
        if (colorInput && textInput) {
            syncColorFields(colorInput, textInput);
        }
    });
    
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
    
    // Função para ajustar o brilho de uma cor
    function adjustBrightness(hex, steps) {
        hex = hex.replace('#', '');
        
        // Converter para RGB
        let r = parseInt(hex.substr(0, 2), 16);
        let g = parseInt(hex.substr(2, 2), 16);
        let b = parseInt(hex.substr(4, 2), 16);
        
        // Ajustar o brilho
        r = Math.max(0, Math.min(255, r + Math.round(steps * 255)));
        g = Math.max(0, Math.min(255, g + Math.round(steps * 255)));
        b = Math.max(0, Math.min(255, b + Math.round(steps * 255)));
        
        // Converter de volta para hex
        return `#${(r.toString(16).padStart(2, '0'))}${(g.toString(16).padStart(2, '0'))}${(b.toString(16).padStart(2, '0'))}`;
    }
    
    // Pré-visualização de cores
    const previewColorsBtn = document.getElementById('previewColorsBtn');
    const previewContainer = document.getElementById('lp-preview-container');
    const colorPreview = document.querySelector('.lp-color-preview');
    
    function updatePreview() {
        const primaryColor = document.getElementById('lp_primary_color').value;
        const secondaryColor = document.getElementById('lp_secondary_color').value;
        const accentColor = document.getElementById('lp_accent_color').value;
        const heroBgColor = document.getElementById('lp_hero_bg_color').value;
        const heroTextColor = document.getElementById('lp_hero_text_color').value;
        const benefitsBgColor = document.getElementById('lp_benefits_bg_color').value;
        const testimonialsBgColor = document.getElementById('lp_testimonials_bg_color').value;
        const winnersBgColor = document.getElementById('lp_winners_bg_color').value;
        const urgencyBgColor = document.getElementById('lp_urgency_bg_color').value;
        const footerBgColor = document.getElementById('lp_footer_bg_color').value;
        const footerTextColor = document.getElementById('lp_footer_text_color').value;
        
        // Criar os elementos de pré-visualização
        let previewHtml = '';
        
        // Layout da landing page em miniatura
        previewHtml += `
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-body p-0">
                        <div style="width: 100%; height: 30px; background-color: #000; display: flex; align-items: center; justify-content: center;">
                            <span style="color: white; font-size: 10px;">Barra de vendedor</span>
                        </div>
                        <div style="width: 100%; height: 100px; background-color: ${heroBgColor}; color: ${heroTextColor}; display: flex; align-items: center; justify-content: center; position: relative;">
                            <div style="padding: 10px;">
                                <h6 style="margin-bottom: 5px; color: ${heroTextColor};">Hero Section</h6>
                                <span style="font-size: 12px; color: ${heroTextColor};">Texto da hero section</span>
                                <button class="btn btn-sm" style="background-color: ${accentColor}; color: ${getTextColorForBackground(accentColor)}; font-size: 10px; margin-top: 5px; display: block;">CTA</button>
                            </div>
                        </div>
                        <div style="width: 100%; height: 60px; background-color: ${benefitsBgColor}; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 12px;">Benefícios</span>
                        </div>
                        <div style="width: 100%; height: 60px; background-color: ${testimonialsBgColor}; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 12px;">Depoimentos</span>
                        </div>
                        <div style="width: 100%; height: 60px; background-color: ${winnersBgColor}; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 12px;">Ganhadores</span>
                        </div>
                        <div style="width: 100%; height: 60px; background-color: ${urgencyBgColor}; color: ${getTextColorForBackground(urgencyBgColor)}; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 12px;">Urgência</span>
                        </div>
                        <div style="width: 100%; height: 60px; background-color: white; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 12px;">Formulário</span>
                        </div>
                        <div style="width: 100%; height: 60px; background-color: ${footerBgColor}; color: ${footerTextColor}; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 12px;">Rodapé</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Amostras de cores
        previewHtml += `
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-white">Cores Principais</div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div style="width: 30px; height: 30px; background-color: ${primaryColor}; border-radius: 4px; margin-right: 10px;"></div>
                            <div>
                                <div>Primária</div>
                                <small class="text-muted">${primaryColor}</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <div style="width: 30px; height: 30px; background-color: ${secondaryColor}; border-radius: 4px; margin-right: 10px;"></div>
                            <div>
                                <div>Secundária</div>
                                <small class="text-muted">${secondaryColor}</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div style="width: 30px; height: 30px; background-color: ${accentColor}; border-radius: 4px; margin-right: 10px;"></div>
                            <div>
                                <div>Destaque</div>
                                <small class="text-muted">${accentColor}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Amostra do botão
        const buttonStyle = document.querySelector('input[name="lp_button_style"]:checked').value;
        const buttonRadius = buttonStyle === 'rounded' ? '50px' : '8px';
        
        previewHtml += `
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-white">Botão CTA</div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <button class="btn" style="background-color: ${accentColor}; color: ${getTextColorForBackground(accentColor)}; border-radius: ${buttonRadius};">Botão de Ação</button>
                    </div>
                </div>
            </div>
        `;
        
        // Amostra de cores das seções
        previewHtml += `
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-white">Cores das Seções</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <div style="width: 30px; height: 30px; background-color: ${heroBgColor}; border-radius: 4px; margin-right: 10px;"></div>
                                    <div>
                                        <div>Hero</div>
                                        <small class="text-muted">${heroBgColor}</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <div style="width: 30px; height: 30px; background-color: ${benefitsBgColor}; border-radius: 4px; margin-right: 10px;"></div>
                                    <div>
                                        <div>Benefícios</div>
                                        <small class="text-muted">${benefitsBgColor}</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <div style="width: 30px; height: 30px; background-color: ${testimonialsBgColor}; border-radius: 4px; margin-right: 10px;"></div>
                                    <div>
                                        <div>Depoimentos</div>
                                        <small class="text-muted">${testimonialsBgColor}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <div style="width: 30px; height: 30px; background-color: ${winnersBgColor}; border-radius: 4px; margin-right: 10px;"></div>
                                    <div>
                                        <div>Ganhadores</div>
                                        <small class="text-muted">${winnersBgColor}</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <div style="width: 30px; height: 30px; background-color: ${urgencyBgColor}; border-radius: 4px; margin-right: 10px;"></div>
                                    <div>
                                        <div>Urgência</div>
                                        <small class="text-muted">${urgencyBgColor}</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div style="width: 30px; height: 30px; background-color: ${footerBgColor}; border-radius: 4px; margin-right: 10px;"></div>
                                    <div>
                                        <div>Rodapé</div>
                                        <small class="text-muted">${footerBgColor}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        colorPreview.innerHTML = previewHtml;
    }
    
    if (previewColorsBtn) {
        previewColorsBtn.addEventListener('click', function() {
            previewContainer.style.display = 'block';
            updatePreview();
        });
    }
    
    // Atualizar a visualização quando o estilo do botão muda
    document.querySelectorAll('input[name="lp_button_style"]').forEach(radio => {
        radio.addEventListener('change', updatePreview);
    });
    
    // Redefinir cores para o padrão
    const resetColorsBtn = document.getElementById('resetColorsBtn');
    
    if (resetColorsBtn) {
        resetColorsBtn.addEventListener('click', function() {
            if (confirm('Tem certeza que deseja redefinir todas as cores para os valores padrão?')) {
                // Valores padrão
                const defaultColors = {
                    'lp_primary_color': '#00053c',
                    'lp_secondary_color': '#4e5055',
                    'lp_accent_color': '#ff9800',
                    'lp_hero_bg_color': '#00053c',
                    'lp_hero_text_color': '#ffffff',
                    'lp_benefits_bg_color': '#f9f9f9',
                    'lp_testimonials_bg_color': '#ffffff',
                    'lp_winners_bg_color': '#f5f7fa',
                    'lp_urgency_bg_color': '#00053c',
                    'lp_footer_bg_color': '#343a40',
                    'lp_footer_text_color': '#ffffff'
                };
                
                // Aplicar valores padrão a todos os campos
                for (const [id, value] of Object.entries(defaultColors)) {
                    const colorInput = document.getElementById(id);
                    const textInput = document.getElementById(id + '_text');
                    
                    if (colorInput) colorInput.value = value;
                    if (textInput) textInput.value = value;
                }
                
                // Definir estilo do botão para o padrão (arredondado)
                document.getElementById('btn-rounded').checked = true;
                
                // Atualizar a pré-visualização
                updatePreview();
            }
        });
    }
    
    // O botão de preview agora é um link direto para a landing page
});
</script>