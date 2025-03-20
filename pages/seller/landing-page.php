<?php
/**
 * Configuração da Landing Page e Personalização de Conteúdo
 */

// Título da página
$page_title = 'Configurar Minha Landing Page';

// Verificar permissão (apenas vendedores)
if (!hasPermission('seller')) {
    include_once __DIR__ . '/../access-denied.php';
    exit;
}

$user = getCurrentUser();
$user_id = $user['id'];

// Obter conexão com o banco de dados
$conn = getConnection();

// Verificar se o vendedor já tem conteúdo personalizado
$stmt = $conn->prepare("SELECT * FROM seller_lp_content WHERE seller_id = :seller_id LIMIT 1");
$stmt->execute(['seller_id' => $user_id]);
$custom_content = $stmt->fetch(PDO::FETCH_ASSOC);

// Inicializar valores padrão se não houver personalização
$headline = $custom_content['headline'] ?? "Conquiste seu veículo sem esperar pela sorte!";
$subheadline = $custom_content['subheadline'] ?? "Contratos premiados com parcelas que cabem no seu bolso.";
$cta_text = $custom_content['cta_text'] ?? "Quero simular agora!";
$benefit_title = $custom_content['benefit_title'] ?? "Por que escolher contratos premiados?";
$featured_car = $custom_content['featured_car'] ?? '';
$footer_bg_color = $custom_content['footer_bg_color'] ?? "#343a40";
$footer_text_color = $custom_content['footer_text_color'] ?? "rgba(255,255,255,0.7)";

// Obter Pixel do Facebook do vendedor
$facebook_pixel = $user['facebook_pixel'] ?? '';

// Buscar depoimentos do vendedor
$stmt = $conn->prepare("SELECT * FROM testimonials WHERE seller_id = :seller_id AND status = 'active' ORDER BY created_at DESC");
$stmt->execute(['seller_id' => $user_id]);
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar ganhadores do vendedor
$stmt = $conn->prepare("SELECT * FROM winners WHERE seller_id = :seller_id AND status = 'active' ORDER BY created_at DESC");
$stmt->execute(['seller_id' => $user_id]);
$winners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar ações
$message = '';
$error = '';

// Atualizar cores do rodapé
if (isset($_POST['action']) && $_POST['action'] === 'update_footer_colors') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $footer_bg_color = sanitize($_POST['footer_bg_color'] ?? '#343a40');
        $footer_text_color = sanitize($_POST['footer_text_color'] ?? 'rgba(255,255,255,0.7)');
        
        // Verificar se já existe um registro
        if ($custom_content) {
            // Atualizar registro existente
            $stmt = $conn->prepare("UPDATE seller_lp_content SET 
                footer_bg_color = :footer_bg_color, 
                footer_text_color = :footer_text_color, 
                updated_at = NOW() 
                WHERE seller_id = :seller_id");
        } else {
            // Criar novo registro
            $stmt = $conn->prepare("INSERT INTO seller_lp_content 
                (seller_id, footer_bg_color, footer_text_color, created_at, updated_at) 
                VALUES 
                (:seller_id, :footer_bg_color, :footer_text_color, NOW(), NOW())");
        }
        
        $result = $stmt->execute([
            'seller_id' => $user_id,
            'footer_bg_color' => $footer_bg_color,
            'footer_text_color' => $footer_text_color
        ]);
        
        if ($result) {
            $message = 'Cores do rodapé atualizadas com sucesso!';
        } else {
            $error = 'Erro ao atualizar cores do rodapé.';
        }
    }
}

// Atualizar dados básicos da Landing Page
if (isset($_POST['action']) && $_POST['action'] === 'update_landing_page') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $landing_page_name = sanitize($_POST['landing_page_name'] ?? '');
        $whatsapp_token = sanitize($_POST['whatsapp_token'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $facebook_pixel = sanitize($_POST['facebook_pixel'] ?? '');

        // Validar nome da landing page (único e alfanumérico)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $landing_page_name)) {
            $error = 'O nome da landing page deve conter apenas letras, números, hífens e underscores.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE landing_page_name = :name AND id != :id");
            $stmt->execute(['name' => $landing_page_name, 'id' => $user_id]);
            if ($stmt->rowCount() > 0) {
                $error = 'Este nome de landing page já está em uso. Escolha outro.';
            } else {
                // Atualizar dados do usuário
                $result = updateUser($user_id, [
                    'landing_page_name' => $landing_page_name,
                    'whatsapp_token' => $whatsapp_token,
                    'phone' => $phone,
                    'facebook_pixel' => $facebook_pixel
                ]);

                if ($result['success']) {
                    $message = 'Dados básicos da landing page atualizados com sucesso!';
                    // Atualizar dados do usuário na sessão
                    $user = getCurrentUser();
                } else {
                    $error = $result['error'] ?? 'Erro ao atualizar dados.';
                }
            }
        }
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
        
        // Verificar se já existe um registro
        if ($custom_content) {
            // Atualizar registro existente
            $stmt = $conn->prepare("UPDATE seller_lp_content SET 
                headline = :headline, 
                subheadline = :subheadline, 
                cta_text = :cta_text, 
                benefit_title = :benefit_title, 
                updated_at = NOW() 
                WHERE seller_id = :seller_id");
        } else {
            // Criar novo registro
            $stmt = $conn->prepare("INSERT INTO seller_lp_content 
                (seller_id, headline, subheadline, cta_text, benefit_title, created_at, updated_at) 
                VALUES 
                (:seller_id, :headline, :subheadline, :cta_text, :benefit_title, NOW(), NOW())");
        }
        
        $result = $stmt->execute([
            'seller_id' => $user_id,
            'headline' => $headline,
            'subheadline' => $subheadline,
            'cta_text' => $cta_text,
            'benefit_title' => $benefit_title
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
                        'seller_id' => $user_id,
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

// Processar upload de foto do vendedor
if (isset($_POST['action']) && $_POST['action'] === 'upload_seller_photo') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        if (isset($_FILES['seller_photo_image']) && $_FILES['seller_photo_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/seller_photos/';
            
            // Criar diretório se não existir
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['seller_photo_image']['name']);
            $targetFile = $uploadDir . $fileName;
            
            // Verificar o tipo de arquivo (apenas imagens)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = $_FILES['seller_photo_image']['type'];
            
            if (in_array($fileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES['seller_photo_image']['tmp_name'], $targetFile)) {
                    $relativeFilePath = 'uploads/seller_photos/' . $fileName;
                    
                    // Atualizar o caminho da imagem no banco de dados
                    if ($custom_content) {
                        $stmt = $conn->prepare("UPDATE seller_lp_content SET seller_photo = :seller_photo, updated_at = NOW() WHERE seller_id = :seller_id");
                    } else {
                        $stmt = $conn->prepare("INSERT INTO seller_lp_content (seller_id, seller_photo, created_at, updated_at) VALUES (:seller_id, :seller_photo, NOW(), NOW())");
                    }
                    
                    $result = $stmt->execute([
                        'seller_id' => $user_id,
                        'seller_photo' => $relativeFilePath
                    ]);
                    
                    if ($result) {
                        $seller_photo = $relativeFilePath;
                        $message = 'Sua foto foi atualizada com sucesso!';
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
                
                $result = $stmt->execute([
                    'seller_id' => $user_id,
                    'name' => $name,
                    'city' => $city,
                    'content' => $content,
                    'photo' => $photoPath
                ]);
                
                if ($result) {
                    $message = 'Depoimento adicionado com sucesso!';
                    
                    // Atualizar a lista de depoimentos
                    $stmt = $conn->prepare("SELECT * FROM testimonials WHERE seller_id = :seller_id AND status = 'active' ORDER BY created_at DESC");
                    $stmt->execute(['seller_id' => $user_id]);
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
                    'seller_id' => $user_id,
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
                    $stmt->execute(['seller_id' => $user_id]);
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
            // Verificar se o depoimento pertence ao vendedor
            $stmt = $conn->prepare("SELECT id FROM testimonials WHERE id = :id AND seller_id = :seller_id");
            $stmt->execute(['id' => $testimonial_id, 'seller_id' => $user_id]);
            
            if ($stmt->rowCount() > 0) {
                // Marcar como inativo (soft delete)
                $stmt = $conn->prepare("UPDATE testimonials SET status = 'inactive' WHERE id = :id");
                $result = $stmt->execute(['id' => $testimonial_id]);
                
                if ($result) {
                    $message = 'Depoimento removido com sucesso!';
                    
                    // Atualizar a lista de depoimentos
                    $stmt = $conn->prepare("SELECT * FROM testimonials WHERE seller_id = :seller_id AND status = 'active' ORDER BY created_at DESC");
                    $stmt->execute(['seller_id' => $user_id]);
                    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error = 'Erro ao remover depoimento.';
                }
            } else {
                $error = 'Depoimento não encontrado ou não pertence a você.';
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
            // Verificar se o ganhador pertence ao vendedor
            $stmt = $conn->prepare("SELECT id FROM winners WHERE id = :id AND seller_id = :seller_id");
            $stmt->execute(['id' => $winner_id, 'seller_id' => $user_id]);
            
            if ($stmt->rowCount() > 0) {
                // Marcar como inativo (soft delete)
                $stmt = $conn->prepare("UPDATE winners SET status = 'inactive' WHERE id = :id");
                $result = $stmt->execute(['id' => $winner_id]);
                
                if ($result) {
                    $message = 'Ganhador removido com sucesso!';
                    
                    // Atualizar a lista de ganhadores
                    $stmt = $conn->prepare("SELECT * FROM winners WHERE seller_id = :seller_id AND status = 'active' ORDER BY created_at DESC");
                    $stmt->execute(['seller_id' => $user_id]);
                    $winners = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error = 'Erro ao remover ganhador.';
                }
            } else {
                $error = 'Ganhador não encontrado ou não pertence a você.';
            }
        } else {
            $error = 'ID do ganhador inválido.';
        }
    }
}

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

<!-- Tabs para organizar a configuração da Landing Page -->
<ul class="nav nav-tabs mb-4" id="landingPageTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab" aria-controls="basic" aria-selected="true">
            <i class="fas fa-cog me-2"></i>Configurações Básicas
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="content-tab" data-bs-toggle="tab" data-bs-target="#content" type="button" role="tab" aria-controls="content" aria-selected="false">
            <i class="fas fa-edit me-2"></i>Conteúdo
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="testimonials-tab" data-bs-toggle="tab" data-bs-target="#testimonials" type="button" role="tab" aria-controls="testimonials" aria-selected="false">
            <i class="fas fa-comment-dots me-2"></i>Depoimentos
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="winners-tab" data-bs-toggle="tab" data-bs-target="#winners" type="button" role="tab" aria-controls="winners" aria-selected="false">
            <i class="fas fa-trophy me-2"></i>Ganhadores
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button" role="tab" aria-controls="appearance" aria-selected="false">
            <i class="fas fa-palette me-2"></i>Aparência
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tracking-tab" data-bs-toggle="tab" data-bs-target="#tracking" type="button" role="tab" aria-controls="tracking" aria-selected="false">
            <i class="fas fa-chart-line me-2"></i>Rastreamento
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview" type="button" role="tab" aria-controls="preview" aria-selected="false">
            <i class="fas fa-eye me-2"></i>Pré-visualização
        </button>
    </li>
</ul>

<div class="tab-content" id="landingPageTabsContent">
    <!-- Tab: Configurações Básicas -->
    <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Configurações Básicas da Landing Page</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo url('index.php?route=seller-landing-page'); ?>">
                    <input type="hidden" name="action" value="update_landing_page">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="mb-3">
                        <label for="landing_page_name" class="form-label">Nome da Landing Page</label>
                        <input type="text" class="form-control" id="landing_page_name" name="landing_page_name" value="<?php echo $user['landing_page_name'] ?? ''; ?>" required>
                        <small class="form-text text-muted">
                            Escolha um nome único para a sua landing page (ex: seunome). A URL será: <?php echo url('index.php?route=lp/'); ?><span id="lp-preview">seunome</span>
                        </small>
                        <?php if (!empty($user['landing_page_name'])): ?>
                            <hr class="my-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-0">Sua Landing Page:</p>
                                    <a href="<?php echo url('index.php?route=lp/' . $user['landing_page_name']); ?>" target="_blank" class="text-decoration-none">
                                        <?php echo url('index.php?route=lp/' . $user['landing_page_name']); ?>
                                    </a>
                                </div>
                                <a href="<?php echo url('index.php?route=lp/' . $user['landing_page_name']); ?>" target="_blank" class="btn btn-success">
                                    <i class="fas fa-external-link-alt me-2"></i>Abrir Landing Page
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Seu WhatsApp</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $user['phone'] ?? ''; ?>">
                        <small class="form-text text-muted">
                            Informe seu número com DDD para o botão de WhatsApp na landing page (ex: 11988887777).
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="whatsapp_token" class="form-label">Seu Token do WhatsApp</label>
                        <input type="text" class="form-control" id="whatsapp_token" name="whatsapp_token" value="<?php echo $user['whatsapp_token'] ?? ''; ?>">
                        <small class="form-text text-muted">
                            Informe o token do seu dispositivo WhatsApp para enviar mensagens personalizadas.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary">Salvar Configurações Básicas</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tab: Conteúdo -->
    <div class="tab-pane fade" id="content" role="tabpanel" aria-labelledby="content-tab">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Personalizar Conteúdo da Landing Page</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo url('index.php?route=seller-landing-page'); ?>" class="mb-4">
                    <input type="hidden" name="action" value="update_lp_content">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="mb-3">
                        <label for="headline" class="form-label">Título Principal</label>
                        <input type="text" class="form-control" id="headline" name="headline" value="<?php echo htmlspecialchars($headline); ?>" maxlength="80">
                        <small class="form-text text-muted">
                            Título chamativo que aparecerá em destaque na sua landing page (limite de 80 caracteres).
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="subheadline" class="form-label">Subtítulo</label>
                        <input type="text" class="form-control" id="subheadline" name="subheadline" value="<?php echo htmlspecialchars($subheadline); ?>" maxlength="120">
                        <small class="form-text text-muted">
                            Texto complementar que aparece abaixo do título principal (limite de 120 caracteres).
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="cta_text" class="form-label">Texto do Botão Principal</label>
                        <input type="text" class="form-control" id="cta_text" name="cta_text" value="<?php echo htmlspecialchars($cta_text); ?>" maxlength="30">
                        <small class="form-text text-muted">
                            Texto do botão de ação principal (limite de 30 caracteres).
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="benefit_title" class="form-label">Título da Seção de Benefícios</label>
                        <input type="text" class="form-control" id="benefit_title" name="benefit_title" value="<?php echo htmlspecialchars($benefit_title); ?>" maxlength="50">
                        <small class="form-text text-muted">
                            Título que aparecerá na seção de benefícios (limite de 50 caracteres).
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary">Salvar Conteúdo</button>
                </form>

                <hr class="my-4">

                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-3">Sua Foto</h6>
                        <form method="post" action="<?php echo url('index.php?route=seller-landing-page'); ?>" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_seller_photo">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                            <?php 
                            $seller_photo = $custom_content['seller_photo'] ?? '';
                            if (!empty($seller_photo)): 
                            ?>
                            <div class="mb-3">
                                <label class="form-label">Foto Atual:</label>
                                <div class="mb-3">
                                    <img src="<?php echo url($seller_photo); ?>" alt="Foto do Vendedor" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="seller_photo_image" class="form-label">Upload de Sua Foto</label>
                                <input type="file" class="form-control" id="seller_photo_image" name="seller_photo_image" accept="image/*">
                                <small class="form-text text-muted">
                                    Selecione uma foto profissional para exibir na sua landing page (JPG, PNG ou GIF).
                                </small>
                            </div>

                            <button type="submit" class="btn btn-primary">Atualizar Foto</button>
                        </form>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="mb-3">Veículo em Destaque</h6>
                        <form method="post" action="<?php echo url('index.php?route=seller-landing-page'); ?>" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_featured_car">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                            <?php if (!empty($featured_car)): ?>
                            <div class="mb-3">
                                <label class="form-label">Imagem Atual:</label>
                                <div class="mb-3">
                                    <img src="<?php echo url($featured_car); ?>" alt="Veículo em destaque" class="img-thumbnail" style="max-width: 300px;">
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="featured_car_image" class="form-label">Upload de Nova Imagem</label>
                                <input type="file" class="form-control" id="featured_car_image" name="featured_car_image" accept="image/*">
                                <small class="form-text text-muted">
                                    Selecione uma imagem do veículo em destaque para sua landing page (JPG, PNG ou GIF).
                                </small>
                            </div>

                            <button type="submit" class="btn btn-primary">Atualizar Imagem</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Depoimentos -->
    <div class="tab-pane fade" id="testimonials" role="tabpanel" aria-labelledby="testimonials-tab">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Gerenciar Depoimentos</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTestimonialModal">
                    <i class="fas fa-plus me-1"></i>Adicionar Depoimento
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($testimonials)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Você ainda não tem depoimentos cadastrados. Adicione depoimentos de clientes satisfeitos para aumentar a credibilidade da sua landing page.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Cidade</th>
                                <th>Depoimento</th>
                                <th>Foto</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testimonials as $testimonial): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($testimonial['name']); ?></td>
                                <td><?php echo htmlspecialchars($testimonial['city'] ?? '-'); ?></td>
                                <td>
                                    <?php 
                                    $content = htmlspecialchars($testimonial['content']);
                                    echo strlen($content) > 50 ? substr($content, 0, 50) . '...' : $content;
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($testimonial['photo'])): ?>
                                    <img src="<?php echo url($testimonial['photo']); ?>" alt="Foto" width="50" height="50" class="rounded-circle">
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Sem foto</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($testimonial['created_at'])); ?></td>
                                <td>
                                    <form method="post" action="<?php echo url('index.php?route=seller-landing-page'); ?>" class="d-inline">
                                        <input type="hidden" name="action" value="delete_testimonial">
                                        <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este depoimento?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tab: Ganhadores -->
    <div class="tab-pane fade" id="winners" role="tabpanel" aria-labelledby="winners-tab">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Gerenciar Ganhadores</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addWinnerModal">
                    <i class="fas fa-plus me-1"></i>Adicionar Ganhador
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($winners)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Você ainda não tem ganhadores cadastrados. Adicione clientes contemplados para demonstrar resultados concretos na sua landing page.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Veículo</th>
                                <th>Valor do Crédito</th>
                                <th>Data da Contemplação</th>
                                <th>Foto</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($winners as $winner): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($winner['name']); ?></td>
                                <td><?php echo htmlspecialchars($winner['vehicle_model']); ?></td>
                                <td>R$ <?php echo number_format($winner['credit_amount'], 2, ',', '.'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($winner['contemplation_date'])); ?></td>
                                <td>
                                    <?php if (!empty($winner['photo'])): ?>
                                    <img src="<?php echo url($winner['photo']); ?>" alt="Foto" width="50" height="50" class="rounded-circle">
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Sem foto</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" action="<?php echo url('index.php?route=seller-landing-page'); ?>" class="d-inline">
                                        <input type="hidden" name="action" value="delete_winner">
                                        <input type="hidden" name="winner_id" value="<?php echo $winner['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este ganhador?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tab: Aparência -->
    <div class="tab-pane fade" id="appearance" role="tabpanel" aria-labelledby="appearance-tab">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Personalizar Aparência</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo url('index.php?route=seller-landing-page'); ?>" class="mb-4">
                    <input type="hidden" name="action" value="update_footer_colors">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <h6 class="border-bottom pb-2 mb-4">Cores do Rodapé</h6>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="footer_bg_color" class="form-label">Cor de Fundo do Rodapé</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="footer_bg_color" name="footer_bg_color" value="<?php echo htmlspecialchars($footer_bg_color); ?>" title="Escolha a cor de fundo do rodapé">
                                <input type="text" class="form-control" id="footer_bg_color_hex" value="<?php echo htmlspecialchars($footer_bg_color); ?>">
                            </div>
                            <small class="form-text text-muted">
                                Cor de fundo do rodapé da sua landing page.
                            </small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="footer_text_color" class="form-label">Cor do Texto do Rodapé</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="footer_text_color" name="footer_text_color" value="<?php echo htmlspecialchars($footer_text_color); ?>" title="Escolha a cor do texto do rodapé">
                                <input type="text" class="form-control" id="footer_text_color_hex" value="<?php echo htmlspecialchars($footer_text_color); ?>">
                            </div>
                            <small class="form-text text-muted">
                                Cor do texto do rodapé da sua landing page.
                            </small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="card mb-3" style="background-color: <?php echo htmlspecialchars($footer_bg_color); ?>; color: <?php echo htmlspecialchars($footer_text_color); ?>; padding: 15px; border-radius: 5px;">
                            <h6>Prévia do Rodapé</h6>
                            <p style="margin-bottom: 0;">Este é um exemplo de como ficará o texto no seu rodapé com as cores selecionadas.</p>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Salvar Cores do Rodapé</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tab: Rastreamento -->
    <div class="tab-pane fade" id="tracking" role="tabpanel" aria-labelledby="tracking-tab">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Configurações de Rastreamento</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo url('index.php?route=seller-landing-page'); ?>" class="mb-4">
                    <input type="hidden" name="action" value="update_landing_page">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="landing_page_name" value="<?php echo htmlspecialchars($user['landing_page_name'] ?? ''); ?>">
                    <input type="hidden" name="whatsapp_token" value="<?php echo htmlspecialchars($user['whatsapp_token'] ?? ''); ?>">
                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">

                    <div class="mb-3">
                        <label for="facebook_pixel" class="form-label">Pixel do Facebook</label>
                        <textarea class="form-control" id="facebook_pixel" name="facebook_pixel" rows="5" placeholder="Cole aqui seu código de Pixel do Facebook"><?php echo htmlspecialchars($facebook_pixel); ?></textarea>
                        <small class="form-text text-muted">
                            Cole o código completo do seu Pixel do Facebook. Isso permitirá rastrear conversões e criar públicos personalizados para suas campanhas de anúncios.
                        </small>
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Como configurar o Pixel do Facebook:</h6>
                        <ol class="mb-0">
                            <li>Acesse o <a href="https://business.facebook.com/events_manager" target="_blank">Gerenciador de Eventos do Facebook</a></li>
                            <li>Selecione ou crie um Pixel</li>
                            <li>Clique em "Configurar" e selecione "Instalar código manualmente"</li>
                            <li>Copie o código base do Pixel (começando com <code>&lt;script&gt;</code>)</li>
                            <li>Cole o código completo no campo acima</li>
                        </ol>
                    </div>

                    <button type="submit" class="btn btn-primary">Salvar Configurações de Rastreamento</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tab: Pré-visualização -->
    <div class="tab-pane fade" id="preview" role="tabpanel" aria-labelledby="preview-tab">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Pré-visualizar Landing Page</h5>
                <?php if (!empty($user['landing_page_name'])): ?>
                <a href="<?php echo url('index.php?route=lp/' . $user['landing_page_name']); ?>" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt me-1"></i>Abrir em Nova Aba
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($user['landing_page_name'])): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>Você precisa configurar o nome da sua landing page na aba "Configurações Básicas" antes de visualizá-la.
                </div>
                <?php else: ?>
                <div class="ratio ratio-16x9">
                    <iframe src="<?php echo url('index.php?route=lp/' . $user['landing_page_name']); ?>" allowfullscreen></iframe>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Adicionar Depoimento -->
<div class="modal fade" id="addTestimonialModal" tabindex="-1" aria-labelledby="addTestimonialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo url('index.php?route=seller-landing-page'); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_testimonial">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="addTestimonialModalLabel">Adicionar Novo Depoimento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="testimonial_name" class="form-label">Nome do Cliente *</label>
                        <input type="text" class="form-control" id="testimonial_name" name="testimonial_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="testimonial_city" class="form-label">Cidade</label>
                        <input type="text" class="form-control" id="testimonial_city" name="testimonial_city">
                    </div>
                    <div class="mb-3">
                        <label for="testimonial_content" class="form-label">Depoimento *</label>
                        <textarea class="form-control" id="testimonial_content" name="testimonial_content" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="testimonial_photo" class="form-label">Foto do Cliente</label>
                        <input type="file" class="form-control" id="testimonial_photo" name="testimonial_photo" accept="image/*">
                        <small class="form-text text-muted">
                            Uma foto do cliente ajuda a aumentar a credibilidade do depoimento.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar Depoimento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Adicionar Ganhador -->
<div class="modal fade" id="addWinnerModal" tabindex="-1" aria-labelledby="addWinnerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo url('index.php?route=seller-landing-page'); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_winner">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="addWinnerModalLabel">Adicionar Novo Ganhador</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="winner_name" class="form-label">Nome do Ganhador *</label>
                        <input type="text" class="form-control" id="winner_name" name="winner_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="winner_vehicle" class="form-label">Modelo do Veículo *</label>
                        <input type="text" class="form-control" id="winner_vehicle" name="winner_vehicle" required>
                    </div>
                    <div class="mb-3">
                        <label for="winner_amount" class="form-label">Valor do Crédito *</label>
                        <input type="text" class="form-control" id="winner_amount" name="winner_amount" placeholder="R$ 0,00" required>
                    </div>
                    <div class="mb-3">
                        <label for="winner_date" class="form-label">Data da Contemplação</label>
                        <input type="date" class="form-control" id="winner_date" name="winner_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="winner_photo" class="form-label">Foto do Ganhador com o Veículo</label>
                        <input type="file" class="form-control" id="winner_photo" name="winner_photo" accept="image/*">
                        <small class="form-text text-muted">
                            Uma foto do cliente com o veículo aumenta a credibilidade do resultado.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar Ganhador</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview do nome da landing page
    const lpNameInput = document.getElementById('landing_page_name');
    const lpPreview = document.getElementById('lp-preview');

    lpNameInput.addEventListener('input', function() {
        lpPreview.textContent = this.value;
    });
    
    // Máscara para valor do crédito
    if (document.getElementById('winner_amount')) {
        IMask(document.getElementById('winner_amount'), {
            mask: 'R$ num',
            blocks: {
                num: {
                    mask: Number,
                    thousandsSeparator: '.',
                    radix: ',',
                    scale: 2,
                    padFractionalZeros: true,
                    normalizeZeros: true,
                    min: 0
                }
            }
        });
    }
    
    // Máscara para telefone
    if (document.getElementById('phone')) {
        IMask(document.getElementById('phone'), {
            mask: '(00) 00000-0000'
        });
    }
    
    // Sincronizar inputs de cores
    const syncColorInputs = (colorInput, hexInput) => {
        colorInput.addEventListener('input', () => {
            hexInput.value = colorInput.value;
            updateFooterPreview();
        });
        
        hexInput.addEventListener('input', () => {
            if (/^#[0-9A-F]{6}$/i.test(hexInput.value)) {
                colorInput.value = hexInput.value;
                updateFooterPreview();
            }
        });
    };
    
    const footerBgColor = document.getElementById('footer_bg_color');
    const footerBgColorHex = document.getElementById('footer_bg_color_hex');
    const footerTextColor = document.getElementById('footer_text_color');
    const footerTextColorHex = document.getElementById('footer_text_color_hex');
    
    if (footerBgColor && footerBgColorHex) {
        syncColorInputs(footerBgColor, footerBgColorHex);
    }
    
    if (footerTextColor && footerTextColorHex) {
        syncColorInputs(footerTextColor, footerTextColorHex);
    }
    
    // Atualizar prévia do rodapé
    const updateFooterPreview = () => {
        const preview = document.querySelector('.card[style*="background-color"]');
        if (preview) {
            preview.style.backgroundColor = footerBgColor.value;
            preview.style.color = footerTextColor.value;
        }
    };
});
</script>
