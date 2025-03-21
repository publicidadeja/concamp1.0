<?php
/**
 * Gerenciamento de integrações com APIs externas
 */

// Título da página
$page_title = 'Integrações';

// Verificar permissão
if (!isAdmin()) {
    include_once __DIR__ . '/../access-denied.php';
    exit;
}

// Processar ações
$message = '';
$error = '';

// Salvar configurações de WhatsApp
if (isset($_POST['action']) && $_POST['action'] === 'save_whatsapp') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $whatsapp_token = $_POST['whatsapp_token'] ?? '';
        
        if (empty($whatsapp_token)) {
            $error = 'O token de API do WhatsApp é obrigatório.';
        } else {
            // Atualizar token no banco de dados
            $result = updateSetting('whatsapp_token', $whatsapp_token);
            
            if ($result) {
                $message = 'Configurações de WhatsApp salvas com sucesso.';
            } else {
                $error = 'Erro ao salvar configurações de WhatsApp.';
            }
        }
    }
}

// Obter configurações atuais
$whatsapp_token = getSetting('whatsapp_token') ?: '';

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
                <h5 class="mb-0">Integração com WhatsApp</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Informações sobre a API</h6>
                    <p class="mb-2">Esta integração permite o envio de mensagens e arquivos de mídia para seus leads via WhatsApp.</p>
                    <hr>
                    <p class="mb-1"><strong>Endpoint:</strong> https://api2.publicidadeja.com.br/api/messages/send</p>
                    <p class="mb-1"><strong>Método:</strong> POST</p>
                    <p class="mb-2"><strong>Autenticação:</strong> Bearer Token</p>
                    <p class="mb-0">Para obter um token de API, entre em contato com o serviço de API.</p>
                </div>
                
                <form method="post" action="<?php echo url('index.php?route=admin-integrations'); ?>">
                    <input type="hidden" name="action" value="save_whatsapp">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-3">
                        <label for="whatsapp_token" class="form-label">Token de API WhatsApp*</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="whatsapp_token" name="whatsapp_token" value="<?php echo htmlspecialchars($whatsapp_token); ?>" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggle-token">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Token de autenticação para a API. Mantenha este token seguro e nunca compartilhe.</div>
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

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Testar Mensagem de Texto</h5>
            </div>
            <div class="card-body">
                <form id="testTextForm">
                    <div class="mb-3">
                        <label for="test_phone" class="form-label">Número de Telefone (com DDD)*</label>
                        <input type="text" class="form-control" id="test_phone" placeholder="Ex: 5599999999999" required>
                        <div class="form-text">O número deve incluir o código do país (55 para Brasil).</div>
                    </div>
                    <div class="mb-3">
                        <label for="test_message" class="form-label">Mensagem de Texto*</label>
                        <textarea class="form-control" id="test_message" rows="3" required>Olá! Esta é uma mensagem de teste do sistema ConCamp.</textarea>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary" id="testTextBtn">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Mensagem
                        </button>
                    </div>
                </form>
                <div class="mt-3" id="testTextResult" style="display: none;"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Testar Envio de Mídia</h5>
            </div>
            <div class="card-body">
                <form id="testMediaForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="media_phone" class="form-label">Número de Telefone (com DDD)*</label>
                        <input type="text" class="form-control" id="media_phone" placeholder="Ex: 5599999999999" required>
                        <div class="form-text">O número deve incluir o código do país (55 para Brasil).</div>
                    </div>
                    <div class="mb-3">
                        <label for="media_file" class="form-label">Arquivo de Mídia*</label>
                        <input type="file" class="form-control" id="media_file" name="media_file" required>
                        <div class="form-text">Suporta imagens (JPG, PNG, GIF), documentos (PDF), áudio (MP3) e vídeo (MP4).</div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary" id="testMediaBtn">
                            <i class="fas fa-file-upload me-2"></i>Enviar Mídia
                        </button>
                    </div>
                </form>
                <div class="mt-3" id="testMediaResult" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle para exibir/ocultar token
    const tokenInput = document.getElementById('whatsapp_token');
    const toggleButton = document.getElementById('toggle-token');
    
    if (toggleButton) {
        toggleButton.addEventListener('click', function() {
            const type = tokenInput.getAttribute('type') === 'password' ? 'text' : 'password';
            tokenInput.setAttribute('type', type);
            
            // Alternar ícone
            const icon = this.querySelector('i');
            if (type === 'text') {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }
    
    // Teste de mensagem de texto
    const testTextForm = document.getElementById('testTextForm');
    const testTextBtn = document.getElementById('testTextBtn');
    const testTextResult = document.getElementById('testTextResult');
    
    if (testTextForm) {
        testTextForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const phone = document.getElementById('test_phone').value;
            const message = document.getElementById('test_message').value;
            
            if (!phone || !message) {
                testTextResult.innerHTML = '<div class="alert alert-danger">Preencha todos os campos.</div>';
                testTextResult.style.display = 'block';
                return;
            }
            
            // Alterar estado do botão
            testTextBtn.disabled = true;
            testTextBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';
            
            // Fazer requisição AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo url('api/test-whatsapp.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                testTextBtn.disabled = false;
                testTextBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Enviar Mensagem';
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            testTextResult.innerHTML = '<div class="alert alert-success">Mensagem enviada com sucesso!</div>';
                        } else {
                            testTextResult.innerHTML = '<div class="alert alert-danger">Erro ao enviar mensagem: ' + (response.error || 'Erro desconhecido') + '</div>';
                        }
                    } catch (e) {
                        testTextResult.innerHTML = '<div class="alert alert-danger">Erro ao processar resposta do servidor.</div>';
                    }
                } else {
                    testTextResult.innerHTML = '<div class="alert alert-danger">Erro na comunicação com o servidor.</div>';
                }
                
                testTextResult.style.display = 'block';
            };
            
            xhr.onerror = function() {
                testTextBtn.disabled = false;
                testTextBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Enviar Mensagem';
                testTextResult.innerHTML = '<div class="alert alert-danger">Erro na comunicação com o servidor.</div>';
                testTextResult.style.display = 'block';
            };
            
            xhr.send('phone=' + encodeURIComponent(phone) + '&message=' + encodeURIComponent(message));
        });
    }
    
    // Teste de envio de mídia
    const testMediaForm = document.getElementById('testMediaForm');
    const testMediaBtn = document.getElementById('testMediaBtn');
    const testMediaResult = document.getElementById('testMediaResult');
    
    if (testMediaForm) {
        testMediaForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const phone = document.getElementById('media_phone').value;
            const mediaFile = document.getElementById('media_file').files[0];
            
            if (!phone || !mediaFile) {
                testMediaResult.innerHTML = '<div class="alert alert-danger">Preencha todos os campos.</div>';
                testMediaResult.style.display = 'block';
                return;
            }
            
            // Alterar estado do botão
            testMediaBtn.disabled = true;
            testMediaBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';
            
            // Preparar FormData
            const formData = new FormData();
            formData.append('phone', phone);
            formData.append('media', mediaFile);
            
            // Fazer requisição AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo url('api/test-whatsapp-media.php'); ?>', true);
            xhr.onload = function() {
                testMediaBtn.disabled = false;
                testMediaBtn.innerHTML = '<i class="fas fa-file-upload me-2"></i>Enviar Mídia';
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            testMediaResult.innerHTML = '<div class="alert alert-success">Mídia enviada com sucesso!</div>';
                        } else {
                            testMediaResult.innerHTML = '<div class="alert alert-danger">Erro ao enviar mídia: ' + (response.error || 'Erro desconhecido') + '</div>';
                        }
                    } catch (e) {
                        testMediaResult.innerHTML = '<div class="alert alert-danger">Erro ao processar resposta do servidor.</div>';
                    }
                } else {
                    testMediaResult.innerHTML = '<div class="alert alert-danger">Erro na comunicação com o servidor.</div>';
                }
                
                testMediaResult.style.display = 'block';
            };
            
            xhr.onerror = function() {
                testMediaBtn.disabled = false;
                testMediaBtn.innerHTML = '<i class="fas fa-file-upload me-2"></i>Enviar Mídia';
                testMediaResult.innerHTML = '<div class="alert alert-danger">Erro na comunicação com o servidor.</div>';
                testMediaResult.style.display = 'block';
            };
            
            xhr.send(formData);
        });
    }
});
</script>
