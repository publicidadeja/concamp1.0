<?php
/**
 * Configuração do Conteúdo da Landing Page Padrão
 */

// Título da página
$page_title = 'Configurar Conteúdo da Landing Page';

// Verificar permissão
if (!isAdmin()) {
    include_once __DIR__ . '/../access-denied.php';
    exit;
}

// Obter conexão com o banco de dados
$conn = getConnection();

// Primeiro, encontrar um usuário administrador existente para usar como referência
$stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$admin_user = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não encontrar admin, usar o primeiro usuário disponível
if (!$admin_user) {
    $stmt = $conn->prepare("SELECT id FROM users LIMIT 1");
    $stmt->execute();
    $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ID padrão para usar como referência
$reference_id = $admin_user ? $admin_user['id'] : 1;

// Verificar se já existe conteúdo personalizado para a landing page padrão
$stmt = $conn->prepare("SELECT * FROM seller_lp_content WHERE seller_id = :seller_id LIMIT 1");
$stmt->execute(['seller_id' => $reference_id]);
$custom_content = $stmt->fetch(PDO::FETCH_ASSOC);

// Inicializar valores padrão se não houver personalização
$headline = $custom_content['headline'] ?? "Conquiste seu veículo sem esperar pela sorte!";
$subheadline = $custom_content['subheadline'] ?? "Contratos premiados com parcelas que cabem no seu bolso.";
$cta_text = $custom_content['cta_text'] ?? "Quero simular agora!";
$benefit_title = $custom_content['benefit_title'] ?? "Por que escolher contratos premiados?";
$featured_car = $custom_content['featured_car'] ?? '';
$footer_bg_color = $custom_content['footer_bg_color'] ?? "#343a40";
$footer_text_color = $custom_content['footer_text_color'] ?? "rgba(255,255,255,0.7)";

// Benefícios
$benefit_1_title = $custom_content['benefit_1_title'] ?? "Parcelas Menores";
$benefit_1_text = $custom_content['benefit_1_text'] ?? "Até 50% mais baratas que financiamentos tradicionais, sem juros abusivos.";
$benefit_2_title = $custom_content['benefit_2_title'] ?? "Segurança Garantida";
$benefit_2_text = $custom_content['benefit_2_text'] ?? "Contratos registrados e empresas autorizadas pelo Banco Central.";
$benefit_3_title = $custom_content['benefit_3_title'] ?? "Contemplação Acelerada";
$benefit_3_text = $custom_content['benefit_3_text'] ?? "Estratégias exclusivas para aumentar suas chances de contemplação rápida.";

// Buscar depoimentos da landing page padrão
$stmt = $conn->prepare("SELECT * FROM testimonials WHERE seller_id = :seller_id AND status = 'active' ORDER BY created_at DESC");
$stmt->execute(['seller_id' => $reference_id]);
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar ganhadores para a landing page padrão
$stmt = $conn->prepare("SELECT * FROM winners WHERE seller_id = :seller_id AND status = 'active' ORDER BY created_at DESC");
$stmt->execute(['seller_id' => $reference_id]);
$winners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar ações
$message = '';
$error = '';

// Atualizar pixel do Facebook
if (isset($_POST['action']) && $_POST['action'] === 'update_facebook_pixel') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $facebook_pixel = $_POST['facebook_pixel'] ?? '';
        
        // Salvar o Pixel nas configurações
        $pixel_key = 'facebook_pixel_' . $reference_id;
        setSetting($pixel_key, $facebook_pixel);
        
        $message = 'Pixel do Facebook salvo com sucesso!';
    }
}

// Atualizar conteúdo personalizado
if (isset($_POST['action']) && $_POST['action'] === 'update_lp_content') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $headline = sanitize($_POST['headline'] ?? '');
        $subheadline = sanitize($_POST['subheadline'] ?? '');
        $cta_text = sanitize($_POST['cta_text'] ?? '');
        $benefit_title = sanitize($_POST['benefit_title'] ?? '');
        
        // Benefícios
        $benefit_1_title = sanitize($_POST['benefit_1_title'] ?? '');
        $benefit_1_text = sanitize($_POST['benefit_1_text'] ?? '');
        $benefit_2_title = sanitize($_POST['benefit_2_title'] ?? '');
        $benefit_2_text = sanitize($_POST['benefit_2_text'] ?? '');
        $benefit_3_title = sanitize($_POST['benefit_3_title'] ?? '');
        $benefit_3_text = sanitize($_POST['benefit_3_text'] ?? '');
        
        // Verificar se já existe um registro
        if ($custom_content) {
            // Atualizar registro existente
            $stmt = $conn->prepare("UPDATE seller_lp_content SET 
                headline = :headline, 
                subheadline = :subheadline, 
                cta_text = :cta_text, 
                benefit_title = :benefit_title,
                benefit_1_title = :benefit_1_title,
                benefit_1_text = :benefit_1_text,
                benefit_2_title = :benefit_2_title,
                benefit_2_text = :benefit_2_text,
                benefit_3_title = :benefit_3_title,
                benefit_3_text = :benefit_3_text,
                updated_at = NOW() 
                WHERE seller_id = :seller_id");
                $stmt->bindParam(':seller_id', $reference_id, PDO::PARAM_INT);
        } else {
            // Criar novo registro
            $stmt = $conn->prepare("INSERT INTO seller_lp_content 
                (seller_id, headline, subheadline, cta_text, benefit_title,
                benefit_1_title, benefit_1_text, benefit_2_title, benefit_2_text, benefit_3_title, benefit_3_text,
                created_at, updated_at) 
                VALUES 
                (:seller_id, :headline, :subheadline, :cta_text, :benefit_title,
                :benefit_1_title, :benefit_1_text, :benefit_2_title, :benefit_2_text, :benefit_3_title, :benefit_3_text,
                NOW(), NOW())");
                $stmt->bindParam(':seller_id', $reference_id, PDO::PARAM_INT);
        }
        
        // Precisamos incluir todos os parâmetros na execução
        $result = $stmt->execute([
            'seller_id' => $reference_id,
            'headline' => $headline,
            'subheadline' => $subheadline,
            'cta_text' => $cta_text,
            'benefit_title' => $benefit_title,
            'benefit_1_title' => $benefit_1_title,
            'benefit_1_text' => $benefit_1_text,
            'benefit_2_title' => $benefit_2_title,
            'benefit_2_text' => $benefit_2_text,
            'benefit_3_title' => $benefit_3_title,
            'benefit_3_text' => $benefit_3_text
        ]);
        
        if ($result) {
            $message = 'Conteúdo da landing page atualizado com sucesso!';
        } else {
            $error = 'Erro ao atualizar conteúdo.';
        }
    }
}

// Processar upload de imagem de veículo em destaque
if (isset($_POST['action']) && $_POST['action'] === 'upload_featured_car') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        if (isset($_FILES['featured_car_image']) && $_FILES['featured_car_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/seller_cars/';
            
            // Criar diretório se não existir
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['featured_car_image']['name']);
            $targetFile = $uploadDir . $fileName;
            
            // Verificar o tipo de arquivo (apenas imagens)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = $_FILES['featured_car_image']['type'];
            
            if (in_array($fileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES['featured_car_image']['tmp_name'], $targetFile)) {
                    $relativeFilePath = 'uploads/seller_cars/' . $fileName;
                    
                    // Atualizar o caminho da imagem no banco de dados
                    if ($custom_content) {
                        $stmt = $conn->prepare("UPDATE seller_lp_content SET featured_car = :featured_car, updated_at = NOW() WHERE seller_id = :seller_id");
                    } else {
                        $stmt = $conn->prepare("INSERT INTO seller_lp_content (seller_id, featured_car, created_at, updated_at) VALUES (:seller_id, :featured_car, NOW(), NOW())");
                    }
                    
                    $result = $stmt->execute([
                        'seller_id' => $reference_id,
                        'featured_car' => $relativeFilePath
                    ]);
                    
                    if ($result) {
                        $featured_car = $relativeFilePath;
                        $message = 'Imagem do veículo em destaque atualizada com sucesso!';
                    } else {
                        $error = 'Erro ao atualizar imagem no banco de dados.';
                    }
                } else {
                    $error = 'Erro ao fazer upload da imagem.';
                }
            } else {
                $error = 'Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.';
            }
        }
    }
}

// Adicionar novo depoimento
if (isset($_POST['action']) && $_POST['action'] === 'add_testimonial') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $name = sanitize($_POST['testimonial_name'] ?? '');
        $city = sanitize($_POST['testimonial_city'] ?? '');
        $content = sanitize($_POST['testimonial_content'] ?? '');
        
        if (empty($name) || empty($content)) {
            $error = 'Nome e depoimento são obrigatórios.';
        } else {
            $photoPath = null;
            
            // Processar upload de foto do cliente
            if (isset($_FILES['testimonial_photo']) && $_FILES['testimonial_photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../uploads/testimonials/';
                
                // Criar diretório se não existir
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['testimonial_photo']['name']);
                $targetFile = $uploadDir . $fileName;
                
                // Verificar o tipo de arquivo (apenas imagens)
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $fileType = $_FILES['testimonial_photo']['type'];
                
                if (in_array($fileType, $allowedTypes)) {
                    if (move_uploaded_file($_FILES['testimonial_photo']['tmp_name'], $targetFile)) {
                        $photoPath = 'uploads/testimonials/' . $fileName;
                    } else {
                        $error = 'Erro ao fazer upload da foto.';
                    }
                } else {
                    $error = 'Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.';
                }
            }
            
            // Inserir depoimento no banco de dados
            if (empty($error)) {
                $stmt = $conn->prepare("INSERT INTO testimonials 
                    (seller_id, name, city, content, photo, status, created_at) 
                    VALUES 
                    (:seller_id, :name, :city, :content, :photo, 'active', NOW())");
                
                // Executar com todos os parâmetros incluindo seller_id
                
                $result = $stmt->execute([
                    'seller_id' => $reference_id,
                    'name' => $name,
                    'city' => $city,
                    'content' => $content,
                    'photo' => $photoPath
                ]);
                
                if ($result) {
                    $message = 'Depoimento adicionado com sucesso!';
                    
                    // Atualizar a lista de depoimentos
                    $stmt = $conn->prepare("SELECT * FROM testimonials WHERE seller_id = :seller_id AND status = 'active' ORDER BY created_at DESC");
                    $stmt->execute(['seller_id' => $reference_id]);
                    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error = 'Erro ao adicionar depoimento.';
                }
            }
        }
    }
}

// Adicionar novo ganhador
if (isset($_POST['action']) && $_POST['action'] === 'add_winner') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $name = sanitize($_POST['winner_name'] ?? '');
        $vehicle_model = sanitize($_POST['winner_vehicle'] ?? '');
        $credit_amount = floatval(str_replace(['R$', '.', ','], ['', '', '.'], $_POST['winner_amount'] ?? 0));
        $contemplation_date = $_POST['winner_date'] ?? date('Y-m-d');
        
        if (empty($name) || empty($vehicle_model) || $credit_amount <= 0) {
            $error = 'Nome, modelo do veículo e valor do crédito são obrigatórios.';
        } else {
            $photoPath = null;
            
            // Processar upload de foto do ganhador
            if (isset($_FILES['winner_photo']) && $_FILES['winner_photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../uploads/winners/';
                
                // Criar diretório se não existir
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['winner_photo']['name']);
                $targetFile = $uploadDir . $fileName;
                
                // Verificar o tipo de arquivo (apenas imagens)
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $fileType = $_FILES['winner_photo']['type'];
                
                if (in_array($fileType, $allowedTypes)) {
                    if (move_uploaded_file($_FILES['winner_photo']['tmp_name'], $targetFile)) {
                        $photoPath = 'uploads/winners/' . $fileName;
                    } else {
                        $error = 'Erro ao fazer upload da foto.';
                    }
                } else {
                    $error = 'Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.';
                }
            }
            
            // Inserir ganhador no banco de dados
            if (empty($error)) {
                $stmt = $conn->prepare("INSERT INTO winners 
                    (seller_id, name, vehicle_model, credit_amount, contemplation_date, photo, status, created_at) 
                    VALUES 
                    (:seller_id, :name, :vehicle_model, :credit_amount, :contemplation_date, :photo, 'active', NOW())");
                
                $result = $stmt->execute([
                    'seller_id' => $reference_id,
                    'name' => $name,
                    'vehicle_model' => $vehicle_model,
                    'credit_amount' => $credit_amount,
                    'contemplation_date' => $contemplation_date,
                    'photo' => $photoPath
                ]);
                
                if ($result) {
                    $message = 'Ganhador adicionado com sucesso!';
                    
                    // Atualizar a lista de ganhadores
                    $stmt = $conn->prepare("SELECT * FROM winners WHERE seller_id = :seller_id AND status = 'active' ORDER BY created_at DESC");
                    $stmt->execute(['seller_id' => $reference_id]);
                    $winners = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error = 'Erro ao adicionar ganhador.';
                }
            }
        }
    }
}

// Remover depoimento
if (isset($_POST['action']) && $_POST['action'] === 'delete_testimonial') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $testimonial_id = intval($_POST['testimonial_id'] ?? 0);
        
        if ($testimonial_id > 0) {
            // Verificar se o depoimento pertence à landing page padrão
            $stmt = $conn->prepare("SELECT id FROM testimonials WHERE id = :id AND seller_id = :seller_id");
            $stmt->execute(['id' => $testimonial_id, 'seller_id' => $reference_id]);
            
            if ($stmt->rowCount() > 0) {
                // Marcar como inativo (soft delete)
                $stmt = $conn->prepare("UPDATE testimonials SET status = 'inactive' WHERE id = :id");
                $result = $stmt->execute(['id' => $testimonial_id]);
                
                if ($result) {
                    $message = 'Depoimento removido com sucesso!';
                    
                    // Atualizar a lista de depoimentos
                    $stmt = $conn->prepare("SELECT * FROM testimonials WHERE seller_id = :seller_id AND status = 'active' ORDER BY created_at DESC");
                    $stmt->execute(['seller_id' => $reference_id]);
                    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error = 'Erro ao remover depoimento.';
                }
            } else {
                $error = 'Depoimento não encontrado.';
            }
        } else {
            $error = 'ID do depoimento inválido.';
        }
    }
}

// Remover ganhador
if (isset($_POST['action']) && $_POST['action'] === 'delete_winner') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $winner_id = intval($_POST['winner_id'] ?? 0);
        
        if ($winner_id > 0) {
            // Verificar se o ganhador pertence à landing page padrão
            $stmt = $conn->prepare("SELECT id FROM winners WHERE id = :id AND seller_id = :seller_id");
            $stmt->execute(['id' => $winner_id, 'seller_id' => $reference_id]);
            
            if ($stmt->rowCount() > 0) {
                // Marcar como inativo (soft delete)
                $stmt = $conn->prepare("UPDATE winners SET status = 'inactive' WHERE id = :id");
                $result = $stmt->execute(['id' => $winner_id]);
                
                if ($result) {
                    $message = 'Ganhador removido com sucesso!';
                    
                    // Atualizar a lista de ganhadores
                    $stmt = $conn->prepare("SELECT * FROM winners WHERE seller_id = :seller_id AND status = 'active' ORDER BY created_at DESC");
                    $stmt->execute(['seller_id' => $reference_id]);
                    $winners = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error = 'Erro ao remover ganhador.';
                }
            } else {
                $error = 'Ganhador não encontrado.';
            }
        } else {
            $error = 'ID do ganhador inválido.';
        }
    }
}

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
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Conteúdo Textual da Landing Page</h5>
                <a href="<?php echo url('index.php'); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye me-1"></i>Ver Landing Page
                </a>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo url('index.php?route=admin-landing-page-content'); ?>">
                    <input type="hidden" name="action" value="update_lp_content">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        Personalize os textos principais da landing page padrão. Estes textos serão exibidos na página inicial do site.
                    </div>
                    
                    <div class="mb-3">
                        <label for="headline" class="form-label">Título Principal</label>
                        <input type="text" class="form-control" id="headline" name="headline" value="<?php echo htmlspecialchars($headline); ?>" maxlength="100" required>
                        <small class="form-text text-muted">Título chamativo que aparece na parte superior da landing page (máx. 100 caracteres).</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subheadline" class="form-label">Subtítulo</label>
                        <input type="text" class="form-control" id="subheadline" name="subheadline" value="<?php echo htmlspecialchars($subheadline); ?>" maxlength="150" required>
                        <small class="form-text text-muted">Texto complementar ao título principal (máx. 150 caracteres).</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cta_text" class="form-label">Texto do Botão de Ação</label>
                        <input type="text" class="form-control" id="cta_text" name="cta_text" value="<?php echo htmlspecialchars($cta_text); ?>" maxlength="50" required>
                        <small class="form-text text-muted">Texto do botão principal que leva ao formulário (máx. 50 caracteres).</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="benefit_title" class="form-label">Título da Seção de Benefícios</label>
                        <input type="text" class="form-control" id="benefit_title" name="benefit_title" value="<?php echo htmlspecialchars($benefit_title); ?>" maxlength="100" required>
                        <small class="form-text text-muted">Título da seção que apresenta os benefícios (máx. 100 caracteres).</small>
                    </div>
                    
                                    
                    <h5 class="mt-4 mb-3">Conteúdo dos Cards de Benefícios</h5>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-header bg-light">Benefício 1</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="benefit_1_title" class="form-label">Título</label>
                                        <input type="text" class="form-control" id="benefit_1_title" name="benefit_1_title" 
                                               value="<?php echo htmlspecialchars($benefit_1_title); ?>" maxlength="100" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="benefit_1_text" class="form-label">Descrição</label>
                                        <textarea class="form-control" id="benefit_1_text" name="benefit_1_text" 
                                                  rows="3" maxlength="255" required><?php echo htmlspecialchars($benefit_1_text); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-header bg-light">Benefício 2</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="benefit_2_title" class="form-label">Título</label>
                                        <input type="text" class="form-control" id="benefit_2_title" name="benefit_2_title" 
                                               value="<?php echo htmlspecialchars($benefit_2_title); ?>" maxlength="100" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="benefit_2_text" class="form-label">Descrição</label>
                                        <textarea class="form-control" id="benefit_2_text" name="benefit_2_text" 
                                                  rows="3" maxlength="255" required><?php echo htmlspecialchars($benefit_2_text); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-header bg-light">Benefício 3</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="benefit_3_title" class="form-label">Título</label>
                                        <input type="text" class="form-control" id="benefit_3_title" name="benefit_3_title" 
                                               value="<?php echo htmlspecialchars($benefit_3_title); ?>" maxlength="100" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="benefit_3_text" class="form-label">Descrição</label>
                                        <textarea class="form-control" id="benefit_3_text" name="benefit_3_text" 
                                                  rows="3" maxlength="255" required><?php echo htmlspecialchars($benefit_3_text); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salvar Conteúdo
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Gerenciamento de Depoimentos -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Depoimentos de Clientes</h5>
            </div>
            <div class="card-body">
                <!-- Lista de depoimentos existentes -->
                <?php if (count($testimonials) > 0): ?>
                <div class="table-responsive mb-4">
                    <table class="table table-hover border">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th>Depoimento</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testimonials as $testimonial): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($testimonial['photo'])): ?>
                                        <div class="me-2">
                                            <img src="<?php echo url($testimonial['photo']); ?>" alt="<?php echo htmlspecialchars($testimonial['name']); ?>" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($testimonial['name']); ?></strong>
                                            <?php if (!empty($testimonial['city'])): ?>
                                            <div class="small text-muted"><?php echo htmlspecialchars($testimonial['city']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars(substr($testimonial['content'], 0, 70)) . (strlen($testimonial['content']) > 70 ? '...' : ''); ?></td>
                                <td class="text-nowrap"><?php echo date('d/m/Y', strtotime($testimonial['created_at'])); ?></td>
                                <td>
                                    <form method="post" action="<?php echo url('index.php?route=admin-landing-page-content'); ?>" class="d-inline">
                                        <input type="hidden" name="action" value="delete_testimonial">
                                        <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja remover este depoimento?');">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum depoimento cadastrado ainda. Adicione depoimentos para exibir na landing page.
                </div>
                <?php endif; ?>
                
                <!-- Formulário para adicionar novo depoimento -->
                <form method="post" action="<?php echo url('index.php?route=admin-landing-page-content'); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_testimonial">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <h6 class="border-bottom pb-2 mb-3">Adicionar Novo Depoimento</h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="testimonial_name" class="form-label">Nome do Cliente *</label>
                            <input type="text" class="form-control" id="testimonial_name" name="testimonial_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="testimonial_city" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="testimonial_city" name="testimonial_city">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="testimonial_content" class="form-label">Depoimento *</label>
                        <textarea class="form-control" id="testimonial_content" name="testimonial_content" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="testimonial_photo" class="form-label">Foto do Cliente</label>
                        <input type="file" class="form-control" id="testimonial_photo" name="testimonial_photo" accept="image/*">
                        <small class="form-text text-muted">Foto do cliente para exibir junto com o depoimento (opcional).</small>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Adicionar Depoimento
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Gerenciamento de Ganhadores -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Clientes Contemplados</h5>
            </div>
            <div class="card-body">
                <!-- Lista de ganhadores existentes -->
                <?php if (count($winners) > 0): ?>
                <div class="table-responsive mb-4">
                    <table class="table table-hover border">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th>Veículo</th>
                                <th>Valor</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($winners as $winner): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($winner['photo'])): ?>
                                        <div class="me-2">
                                            <img src="<?php echo url($winner['photo']); ?>" alt="<?php echo htmlspecialchars($winner['name']); ?>" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($winner['name']); ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($winner['vehicle_model']); ?></td>
                                <td>R$ <?php echo formatCurrency($winner['credit_amount']); ?></td>
                                <td class="text-nowrap"><?php echo date('d/m/Y', strtotime($winner['contemplation_date'])); ?></td>
                                <td>
                                    <form method="post" action="<?php echo url('index.php?route=admin-landing-page-content'); ?>" class="d-inline">
                                        <input type="hidden" name="action" value="delete_winner">
                                        <input type="hidden" name="winner_id" value="<?php echo $winner['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja remover este ganhador?');">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum cliente contemplado cadastrado ainda. Adicione contemplados para exibir na landing page.
                </div>
                <?php endif; ?>
                
                <!-- Formulário para adicionar novo ganhador -->
                <form method="post" action="<?php echo url('index.php?route=admin-landing-page-content'); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_winner">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <h6 class="border-bottom pb-2 mb-3">Adicionar Novo Contemplado</h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="winner_name" class="form-label">Nome do Cliente *</label>
                            <input type="text" class="form-control" id="winner_name" name="winner_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="winner_vehicle" class="form-label">Modelo do Veículo *</label>
                            <input type="text" class="form-control" id="winner_vehicle" name="winner_vehicle" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="winner_amount" class="form-label">Valor do Crédito *</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control" id="winner_amount" name="winner_amount" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="winner_date" class="form-label">Data da Contemplação *</label>
                            <input type="date" class="form-control" id="winner_date" name="winner_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="winner_photo" class="form-label">Foto do Cliente/Veículo</label>
                        <input type="file" class="form-control" id="winner_photo" name="winner_photo" accept="image/*">
                        <small class="form-text text-muted">Foto do cliente com o veículo contemplado (opcional).</small>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Adicionar Contemplado
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Pixel do Facebook</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo url('index.php?route=admin-landing-page-content'); ?>">
                    <input type="hidden" name="action" value="update_facebook_pixel">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="mb-3">
                        <label for="facebook_pixel" class="form-label">Código do Pixel</label>
                        <textarea class="form-control" id="facebook_pixel" name="facebook_pixel" rows="5" placeholder="Cole aqui o código do seu Pixel do Facebook"><?php 
                            $pixel_key = 'facebook_pixel_' . $reference_id;
                            echo htmlspecialchars(getSetting($pixel_key, '')); 
                        ?></textarea>
                        <small class="form-text text-muted">Cole o código completo do Pixel do Facebook para rastrear conversões e criar públicos para seus anúncios.</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Como configurar o Pixel:</h6>
                        <ol class="mb-0 small">
                            <li>Acesse o <a href="https://business.facebook.com/events_manager" target="_blank">Gerenciador de Eventos do Facebook</a></li>
                            <li>Selecione ou crie um Pixel</li>
                            <li>Clique em "Configurar" e selecione "Instalar código manualmente"</li>
                            <li>Copie o código base do Pixel (começando com <code>&lt;script&gt;</code>)</li>
                            <li>Cole o código completo no campo acima</li>
                        </ol>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salvar Pixel
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Imagem Principal</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo url('index.php?route=admin-landing-page-content'); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_featured_car">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <?php if (!empty($featured_car)): ?>
                    <div class="text-center mb-3">
                        <img src="<?php echo url($featured_car); ?>" alt="Veículo em destaque" class="img-fluid rounded">
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="featured_car_image" class="form-label">Veículo em Destaque</label>
                        <input type="file" class="form-control" id="featured_car_image" name="featured_car_image" accept="image/*">
                        <small class="form-text text-muted">Imagem do veículo que aparecerá em destaque na parte superior da landing page. Recomendado: 800x500px.</small>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Fazer Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Dicas Úteis</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li class="mb-2">Use títulos chamativos e diretos que despertem o interesse.</li>
                    <li class="mb-2">Mantenha o texto do botão de ação curto e persuasivo.</li>
                    <li class="mb-2">As imagens devem ser de alta qualidade e relevantes para seu público.</li>
                    <li class="mb-2">Depoimentos reais aumentam a credibilidade da sua landing page.</li>
                    <li>Exibir clientes contemplados demonstra sucesso e motiva novos leads.</li>
                </ul>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Visualização</h5>
            </div>
            <div class="card-body">
                <p>As alterações feitas nesta página serão refletidas na landing page padrão do sistema, que é exibida como página inicial para todos os visitantes.</p>
                
                <div class="d-grid">
                    <a href="<?php echo url('index.php'); ?>" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt me-2"></i>Ver Landing Page
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview de imagem antes do upload
    const imageInput = document.getElementById('featured_car_image');
    
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imagePreview = document.createElement('img');
                    imagePreview.src = e.target.result;
                    imagePreview.classList.add('img-fluid', 'rounded', 'mb-3');
                    
                    // Remover preview anterior se existir
                    const existingPreview = imageInput.parentElement.querySelector('img');
                    if (existingPreview) {
                        existingPreview.remove();
                    }
                    
                    // Adicionar novo preview
                    imageInput.parentElement.insertBefore(imagePreview, imageInput);
                }
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Sincronizar cores entre o color picker e o input de texto
    const footerBgColorPicker = document.getElementById('footer_bg_color_picker');
    const footerBgColorInput = document.getElementById('footer_bg_color');
    
    if (footerBgColorPicker && footerBgColorInput) {
        footerBgColorPicker.addEventListener('input', function() {
            footerBgColorInput.value = this.value;
        });
        
        footerBgColorInput.addEventListener('input', function() {
            try {
                footerBgColorPicker.value = this.value;
            } catch (e) {
                // Formato inválido para o color picker
            }
        });
    }
    
    const footerTextColorPicker = document.getElementById('footer_text_color_picker');
    const footerTextColorInput = document.getElementById('footer_text_color');
    
    if (footerTextColorPicker && footerTextColorInput) {
        footerTextColorPicker.addEventListener('input', function() {
            // Converter de hex para rgba quando necessário
            let color = this.value;
            // Se for rgba, manter como está
            if (footerTextColorInput.value.startsWith('rgba')) {
                // Extrair os primeiros 3 componentes RGB do hex e criar um rgba
                let r = parseInt(color.slice(1, 3), 16);
                let g = parseInt(color.slice(3, 5), 16);
                let b = parseInt(color.slice(5, 7), 16);
                footerTextColorInput.value = `rgba(${r},${g},${b},0.7)`;
            } else {
                footerTextColorInput.value = color;
            }
        });
        
        footerTextColorInput.addEventListener('input', function() {
            try {
                // Se for rgba, extrair os valores RGB para o colorpicker
                if (this.value.startsWith('rgba')) {
                    let components = this.value.match(/rgba\((\d+),(\d+),(\d+)/);
                    if (components && components.length >= 4) {
                        let r = parseInt(components[1]).toString(16).padStart(2, '0');
                        let g = parseInt(components[2]).toString(16).padStart(2, '0');
                        let b = parseInt(components[3]).toString(16).padStart(2, '0');
                        footerTextColorPicker.value = `#${r}${g}${b}`;
                    }
                } else {
                    footerTextColorPicker.value = this.value;
                }
            } catch (e) {
                // Formato inválido para o color picker
            }
        });
    }
    
    // Máscara para campo de valor monetário
    const amountInput = document.getElementById('winner_amount');
    if (amountInput) {
        amountInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value) {
                value = (parseInt(value) / 100).toFixed(2);
                value = value.replace('.', ',');
                value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                e.target.value = value;
            } else {
                e.target.value = '';
            }
        });
    }
    
    // Ajustar visualização de uploads de imagens
    document.querySelectorAll('input[type="file"]').forEach(function(input) {
        input.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                const input = this;
                
                reader.onload = function(e) {
                    // Verificar se já existe um preview
                    let preview = input.parentElement.querySelector('.preview-image');
                    
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.classList.add('preview-image', 'mt-2');
                        input.parentElement.appendChild(preview);
                    }
                    
                    preview.innerHTML = `
                        <div class="card">
                            <div class="card-body p-2">
                                <img src="${e.target.result}" class="img-fluid rounded" style="max-height: 150px;">
                            </div>
                        </div>
                    `;
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
});
</script>