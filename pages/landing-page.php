<?php
/**
 * Template para Landing Pages de Vendedores - Modelo AIDA
 */

// Obter o nome da landing page da URL
$landing_page_name = $_GET['name'] ?? '';

// Buscar dados do vendedor
$conn = getConnection();
$stmt = $conn->prepare("SELECT * FROM users WHERE landing_page_name = :name AND role = 'seller' AND status = 'active'");
$stmt->execute(['name' => $landing_page_name]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seller) {
    // Vendedor não encontrado ou landing page inválida
    include_once __DIR__ . '/404.php';
    exit;
}

// Dados do vendedor para a landing page
$seller_name = $seller['name'];
$seller_id = $seller['id'];
$seller_whatsapp_token = $seller['whatsapp_token']; // Usado na action do formulário
$seller_phone = $seller['phone'] ?? '';

// Buscar depoimentos e ganhadores do vendedor
$stmt = $conn->prepare("SELECT * FROM testimonials WHERE seller_id = :seller_id AND status = 'active' ORDER BY created_at DESC LIMIT 6");
$stmt->execute(['seller_id' => $seller_id]);
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT * FROM winners WHERE seller_id = :seller_id AND status = 'active' ORDER BY created_at DESC LIMIT 6");
$stmt->execute(['seller_id' => $seller_id]);
$winners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar conteúdo personalizado
$stmt = $conn->prepare("SELECT * FROM seller_lp_content WHERE seller_id = :seller_id LIMIT 1");
$stmt->execute(['seller_id' => $seller_id]);
$custom_content = $stmt->fetch(PDO::FETCH_ASSOC);

// Definir conteúdos com valores padrão se não houver personalização
$headline = $custom_content['headline'] ?? "Conquiste seu veículo sem esperar pela sorte!";
$subheadline = $custom_content['subheadline'] ?? "Contratos premiados com parcelas que cabem no seu bolso.";
$cta_text = $custom_content['cta_text'] ?? "Quero simular agora!";
$benefit_title = $custom_content['benefit_title'] ?? "Por que escolher contratos premiados?";
$featured_car = $custom_content['featured_car'] ?? null;
$seller_photo = $custom_content['seller_photo'] ?? null;
$footer_bg_color = $custom_content['footer_bg_color'] ?? "#343a40";
$footer_text_color = $custom_content['footer_text_color'] ?? "rgba(255,255,255,0.7)";

// Benefícios
$benefit_1_title = $custom_content['benefit_1_title'] ?? "Parcelas Menores";
$benefit_1_text = $custom_content['benefit_1_text'] ?? "Até 50% mais baratas que financiamentos tradicionais, sem juros abusivos.";
$benefit_2_title = $custom_content['benefit_2_title'] ?? "Segurança Garantida";
$benefit_2_text = $custom_content['benefit_2_text'] ?? "Contratos registrados e empresas autorizadas pelo Banco Central.";
$benefit_3_title = $custom_content['benefit_3_title'] ?? "Contemplação Acelerada";
$benefit_3_text = $custom_content['benefit_3_text'] ?? "Estratégias exclusivas para aumentar suas chances de contemplação rápida.";

// Título da página (personalizado com o nome do vendedor)
$page_title = "Contrato Premiado - $seller_name";
$body_class = "landing-page";

// Definir uma variável global para evitar o rodapé duplicado
// Essa variável será verificada em templates/footer.php
$skip_global_footer = true;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo url('assets/css/style.css'); ?>" rel="stylesheet">
    
    <!-- Tema com cores fixas -->
    <link href="<?php echo url('assets/css/hardcoded-theme.css?v=' . time()); ?>" rel="stylesheet">
    
    <!-- CSS personalizado da Landing Page -->
    <link href="<?php echo url('assets/css/landing-page-theme.css?v=' . getSetting('lp_theme_version', time())); ?>" rel="stylesheet">
    
    <!-- Animações AOS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- IMask para máscaras de input -->
    <script src="https://unpkg.com/imask"></script>
    
    <!-- Facebook Pixel Code -->
    <?php
    // Verificar se existe configuração de pixel para este vendedor
    $pixelKey = 'facebook_pixel_' . $seller_id;
    $pixelCode = getSetting($pixelKey, '');
    if (!empty($pixelCode)) {
        echo $pixelCode;
    }
    ?>
    
    <style>
        /* Estilos específicos para Landing Page AIDA */
        body.landing-page {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }
        
        /* Garantir que container-fluid seja realmente full-width */
        .container-fluid {
            max-width: 100% !important;
            width: 100% !important;
            padding-left: 15px;
            padding-right: 15px;
        }
        
        /* ATTENTION (Hero section) */
        .lp-hero {
            position: relative;
            padding: 100px 0 80px;
            background: linear-gradient(135deg, var(--lp-hero-bg, var(--bs-primary)) 0%, var(--lp-primary-dark, #0056b3) 100%);
            color: var(--lp-hero-text, white);
            overflow: hidden;
        }
        
        .lp-hero::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('<?php echo url("assets/img/pattern.png"); ?>');
            opacity: 0.1;
            z-index: 0;
        }
        
        .lp-hero-content {
            position: relative;
            z-index: 1;
        }
        
        .lp-hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .lp-hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .lp-hero-image img {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .lp-cta-btn {
            padding: 15px 30px;
            font-size: 1.2rem;
            font-weight: 600;
            text-transform: uppercase;
            border-radius: 50px;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            background-color: var(--lp-accent, #ff9800) !important;
            color: var(--lp-accent-text, #212529) !important;
        }
        
        .lp-cta-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            background-color: var(--lp-accent-dark, #e68a00) !important;
        }
        
        .lp-badge {
            position: absolute;
            top: -15px;
            right: -15px;
            background: var(--lp-accent, #ff5722);
            color: var(--lp-accent-text, white);
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            z-index: 2;
            transform: rotate(5deg);
        }
        
        /* INTEREST (Benefícios e depoimentos) */
        .lp-benefits {
            padding: 80px 0;
            background-color: var(--lp-benefits-bg-color, #f9f9f9);
        }
        
        .lp-benefits h2 {
            font-weight: 700;
            margin-bottom: 50px;
            text-align: center;
        }
        
        .lp-benefit-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .lp-benefit-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .lp-benefit-icon {
            width: 70px;
            height: 70px;
            background-color: rgba(var(--lp-primary-rgb, 0, 5, 60), 0.1);
            color: var(--lp-primary, var(--bs-primary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 2rem;
        }
        
        .lp-benefit-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .lp-testimonials {
            padding: 80px 0;
            background-color: var(--lp-testimonials-bg-color, white);
        }
        
        .lp-testimonials h2 {
            font-weight: 700;
            margin-bottom: 50px;
            text-align: center;
        }
        
        .lp-testimonial-card {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            position: relative;
            height: 100%;
        }
        
        .lp-testimonial-card::before {
            content: """;
            position: absolute;
            top: 10px;
            left: 20px;
            font-size: 5rem;
            color: rgba(var(--lp-primary-rgb, 0, 5, 60), 0.1);
            font-family: serif;
            line-height: 1;
        }
        
        .lp-testimonial-text {
            position: relative;
            z-index: 1;
            font-style: italic;
            margin-bottom: 20px;
        }
        
        .lp-testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .lp-testimonial-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
        }
        
        .lp-testimonial-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .lp-testimonial-info h4 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .lp-testimonial-info p {
            margin: 0;
            font-size: 0.9rem;
            color: #777;
        }
        
        /* DESIRE (Ganhadores e Urgência) */
        .lp-winners {
            padding: 80px 0;
            background: linear-gradient(135deg, var(--lp-winners-bg-color, #f5f7fa) 0%, var(--lp-winners-bg-color-dark, #e4e8f0) 100%);
        }
        
        .lp-winners h2 {
            font-weight: 700;
            margin-bottom: 50px;
            text-align: center;
        }
        
        .lp-winner-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            height: 100%;
        }
        
        .lp-winner-image {
            height: 200px;
            overflow: hidden;
        }
        
        .lp-winner-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .lp-winner-card:hover .lp-winner-image img {
            transform: scale(1.1);
        }
        
        .lp-winner-content {
            padding: 20px;
        }
        
        .lp-winner-title {
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .lp-winner-desc {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .lp-winner-date {
            color: #999;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
        }
        
        .lp-winner-date i {
            margin-right: 5px;
        }
        
        .lp-urgency {
            padding: 60px 0;
            background-color: var(--lp-urgency-bg-color, var(--lp-primary, var(--bs-primary)));
            color: var(--lp-urgency-text-color, white);
            position: relative;
            overflow: hidden;
        }
        
        .lp-urgency::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('<?php echo url("assets/img/pattern.png"); ?>');
            opacity: 0.05;
            z-index: 0;
        }
        
        .lp-urgency-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        
        .lp-urgency h2 {
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .lp-urgency p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .lp-countdown {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .lp-countdown-item {
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
            padding: 15px;
            margin: 0 10px;
            min-width: 80px;
            text-align: center;
            color: var(--lp-urgency-text-color, white);
        }
        
        .lp-countdown-number {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .lp-countdown-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            opacity: 0.7;
        }
        
        /* ACTION (Formulário) */
        .lp-action {
            padding: 80px 0;
            background-color: white;
        }
        
        .lp-action h2 {
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .lp-action p {
            font-size: 1.1rem;
            margin-bottom: 40px;
            text-align: center;
            color: #666;
        }
        
        .lp-form-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .lp-form-badge {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: #ff5722;
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        
        .lp-form-group {
            margin-bottom: 25px;
        }
        
        .lp-form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #555;
        }
        
        .lp-form-control {
            height: 50px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 10px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .lp-form-control:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.1);
        }
        
        .lp-submit-btn {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            margin-top: 15px;
            border: none;
            background-color: var(--bs-primary);
            color: white;
            transition: all 0.3s ease;
        }
        
        .lp-submit-btn:hover {
            background-color: #0056b3;
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        
        .lp-form-secure {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #777;
        }
        
        .lp-form-secure i {
            color: var(--bs-primary);
            margin-right: 5px;
        }
        
        /* Footer */
        .lp-footer {
            padding: 40px 0;
            background-color: var(--lp-footer-bg-color, #343a40);
            color: var(--lp-footer-text-color, rgba(255,255,255,0.7));
        }
        
        .lp-footer-logo {
            margin-bottom: 20px;
        }
        
        .lp-footer-desc {
            margin-bottom: 30px;
        }
        
        .lp-footer-contact {
            margin-bottom: 20px;
        }
        
        .lp-footer-contact-item {
            display: flex;
            margin-bottom: 15px;
        }
        
        .lp-footer-contact-icon {
            color: var(--lp-primary, var(--bs-primary));
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .lp-footer-social {
            display: flex;
            margin-top: 20px;
        }
        
        .lp-footer-social-item {
            width: 40px;
            height: 40px;
            background-color: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .lp-footer-social-item:hover {
            background-color: var(--lp-primary, var(--bs-primary));
            transform: translateY(-5px);
        }
        
        .lp-footer-bottom {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.9rem;
        }
        
        /* WhatsApp Button */
        .lp-whatsapp-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background-color: #25D366;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            z-index: 999;
            transition: all 0.3s ease;
        }
        
        .lp-whatsapp-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        /* Responsividade */
        @media (max-width: 991.98px) {
            .lp-hero h1 {
                font-size: 2.5rem;
            }
            
            .lp-hero-image {
                margin-top: 30px;
            }
            
            .lp-form-container {
                padding: 30px;
            }
        }
        
        @media (max-width: 767.98px) {
            .lp-hero {
                padding: 60px 0;
            }
            
            .lp-hero h1 {
                font-size: 2rem;
            }
            
            .lp-hero p {
                font-size: 1.1rem;
            }
            
            .lp-cta-btn {
                padding: 12px 25px;
                font-size: 1rem;
            }
            
            .lp-benefits, .lp-testimonials, .lp-winners, .lp-action {
                padding: 50px 0;
            }
            
            .lp-countdown-item {
                min-width: 60px;
                padding: 10px;
            }
            
            .lp-countdown-number {
                font-size: 1.5rem;
            }
            
            .lp-form-container {
                padding: 20px;
            }
        }
        
        /* Animações */
        .fade-up {
            animation: fadeUp 0.6s ease-out;
        }
        
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body class="<?php echo $body_class; ?>">
    <!-- Botão de WhatsApp fixo -->
    <?php if (!empty($seller_phone)): ?>
    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $seller_phone); ?>" target="_blank" class="lp-whatsapp-btn">
        <i class="fab fa-whatsapp"></i>
    </a>
    <?php endif; ?>

    <!-- ATTENTION: Hero Section -->
    <section class="lp-hero">
        <div class="container-fluid px-0">
            <!-- Barra de informações do vendedor -->
            <div class="lp-seller-bar bg-dark text-white py-2">
                <div class="container-fluid px-md-5">
                    <div class="row align-items-center">
                        <div class="col-md-6 d-flex align-items-center">
                            <?php if (!empty($seller_photo)): ?>
                            <div class="lp-seller-photo me-3">
                                <img src="<?php echo url($seller_photo); ?>" alt="<?php echo htmlspecialchars($seller_name); ?>" class="rounded-circle" width="50" height="50" style="object-fit: cover; border: 2px solid #fff;">
                            </div>
                            <?php endif; ?>
                            <div class="lp-seller-info">
                                <p class="mb-0"><span class="d-none d-md-inline">Seu consultor:</span> <strong><?php echo htmlspecialchars($seller_name); ?></strong></p>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end mt-2 mt-md-0">
                            <?php if (!empty($seller_phone)): ?>
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $seller_phone); ?>" class="btn btn-sm btn-success rounded-pill">
                                <i class="fab fa-whatsapp me-1"></i> Falar agora <span class="d-none d-md-inline"><?php echo htmlspecialchars($seller_phone); ?></span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Conteúdo principal do Hero -->
            <div class="container-fluid px-md-5">
                <div class="row align-items-center py-5">
                    <div class="col-lg-6 lp-hero-content" data-aos="fade-up">
                        <h1><?php echo htmlspecialchars($headline); ?></h1>
                        <p><?php echo htmlspecialchars($subheadline); ?></p>
                        <a href="#simulador" class="btn btn-warning lp-cta-btn pulse"><?php echo htmlspecialchars($cta_text); ?></a>
                    </div>
                    <div class="col-lg-6 lp-hero-image" data-aos="fade-left" data-aos-delay="200">
                        <div class="position-relative">
                            <?php if (!empty($featured_car)): ?>
                            <img src="<?php echo url($featured_car); ?>" alt="Veículo em destaque" class="img-fluid rounded shadow">
                            <?php else: ?>
                            <img src="<?php echo url('assets/img/car-hero.jpg'); ?>" alt="Veículo em destaque" class="img-fluid rounded shadow">
                            <?php endif; ?>
                            <div class="lp-badge">Sem juros abusivos!</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- INTEREST: Benefícios -->
    <section class="lp-benefits">
        <div class="container-fluid">
            <h2 data-aos="fade-up"><?php echo htmlspecialchars($benefit_title); ?></h2>
            <div class="row px-md-5">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="lp-benefit-card">
                        <div class="lp-benefit-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h3 class="lp-benefit-title"><?php echo htmlspecialchars($benefit_1_title); ?></h3>
                        <p><?php echo htmlspecialchars($benefit_1_text); ?></p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="lp-benefit-card">
                        <div class="lp-benefit-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="lp-benefit-title"><?php echo htmlspecialchars($benefit_2_title); ?></h3>
                        <p><?php echo htmlspecialchars($benefit_2_text); ?></p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="lp-benefit-card">
                        <div class="lp-benefit-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h3 class="lp-benefit-title"><?php echo htmlspecialchars($benefit_3_title); ?></h3>
                        <p><?php echo htmlspecialchars($benefit_3_text); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- INTEREST: Depoimentos -->
    <?php if (count($testimonials) > 0): ?>
    <section class="lp-testimonials">
        <div class="container-fluid">
            <h2 data-aos="fade-up">O que nossos clientes dizem</h2>
            <div class="row px-md-5">
                <?php foreach ($testimonials as $index => $testimonial): ?>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="lp-testimonial-card">
                        <p class="lp-testimonial-text"><?php echo htmlspecialchars($testimonial['content']); ?></p>
                        <div class="lp-testimonial-author">
                            <div class="lp-testimonial-avatar">
                                <?php if (!empty($testimonial['photo'])): ?>
                                <img src="<?php echo url($testimonial['photo']); ?>" alt="<?php echo htmlspecialchars($testimonial['name']); ?>">
                                <?php else: ?>
                                <img src="<?php echo url('assets/img/avatar-default.jpg'); ?>" alt="<?php echo htmlspecialchars($testimonial['name']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="lp-testimonial-info">
                                <h4><?php echo htmlspecialchars($testimonial['name']); ?></h4>
                                <p><?php echo htmlspecialchars($testimonial['city']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- DESIRE: Ganhadores -->
    <?php if (count($winners) > 0): ?>
    <section class="lp-winners">
        <div class="container-fluid">
            <h2 data-aos="fade-up">Clientes Contemplados</h2>
            <div class="row px-md-5">
                <?php foreach ($winners as $index => $winner): ?>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="lp-winner-card">
                        <div class="lp-winner-image">
                            <?php if (!empty($winner['photo']) && file_exists(__DIR__ . '/../' . $winner['photo'])): ?>
                            <img src="<?php echo url($winner['photo']); ?>" alt="<?php echo htmlspecialchars($winner['name']); ?>">
                            <?php else: ?>
                            <div style="width: 100%; height: 100%; background-color: #f5f5f5; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                                <div style="font-size: 2rem; margin-bottom: 10px; color: #aaa;">🚗</div>
                                <div style="color: #666; text-align: center; padding: 0 20px;">Veículo Contemplado</div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="lp-winner-content">
                            <h4 class="lp-winner-title"><?php echo htmlspecialchars($winner['name']); ?></h4>
                            <p class="lp-winner-desc"><?php echo htmlspecialchars($winner['vehicle_model']); ?> - R$ <?php echo number_format($winner['credit_amount'], 2, ',', '.'); ?></p>
                            <div class="lp-winner-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('d/m/Y', strtotime($winner['contemplation_date'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- DESIRE: Urgência -->
    <section class="lp-urgency">
        <div class="container-fluid">
            <div class="lp-urgency-content">
                <h2 data-aos="fade-up">Não perca mais tempo!</h2>
                <p data-aos="fade-up" data-aos-delay="100">As melhores condições estão disponíveis por tempo limitado.</p>
                <div class="lp-countdown" data-aos="fade-up" data-aos-delay="200">
                    <div class="lp-countdown-item">
                        <div class="lp-countdown-number" id="countdown-days">7</div>
                        <div class="lp-countdown-label">dias</div>
                    </div>
                    <div class="lp-countdown-item">
                        <div class="lp-countdown-number" id="countdown-hours">24</div>
                        <div class="lp-countdown-label">horas</div>
                    </div>
                    <div class="lp-countdown-item">
                        <div class="lp-countdown-number" id="countdown-minutes">60</div>
                        <div class="lp-countdown-label">minutos</div>
                    </div>
                    <div class="lp-countdown-item">
                        <div class="lp-countdown-number" id="countdown-seconds">60</div>
                        <div class="lp-countdown-label">segundos</div>
                    </div>
                </div>
                <a href="#simulador" class="btn btn-warning lp-cta-btn" data-aos="fade-up" data-aos-delay="300">Simular Agora!</a>
            </div>
        </div>
    </section>

    <!-- ACTION: Formulário de Simulação -->
    <section class="lp-action" id="simulador">
        <div class="container-fluid">
            <h2 data-aos="fade-up">Faça sua simulação personalizada</h2>
            <p data-aos="fade-up" data-aos-delay="100">Descubra quanto você pode economizar com um contrato premiado</p>
            
            <!-- Definir a action do formulário para o vendedor específico -->
            <?php 
            $form_action = url('index.php?route=process-seller-simulation');
            
            // Obter planos disponíveis
            $car_plans = getPlans('car');
            $motorcycle_plans = getPlans('motorcycle');
            
            // Obter prazos disponíveis
            $car_terms = getAvailableTerms('car');
            $motorcycle_terms = getAvailableTerms('motorcycle');
            ?>
            
            <div class="row justify-content-center">
                <div class="col-lg-8" data-aos="fade-up" data-aos-delay="200">
                    <div class="lp-form-container">
                        <div class="lp-form-badge">Resposta em até 24h</div>
                        <form id="simulator-form" method="post" action="<?php echo $form_action; ?>">
                            <!-- Campo oculto para o ID do vendedor -->
                            <input type="hidden" name="seller_id" value="<?php echo $seller_id; ?>">
                            
                            <!-- Etapa 1: Tipo de veículo -->
                            <div class="step" id="step-1">
                                <h5 class="mb-4">Qual tipo de veículo você deseja?</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="card vehicle-card" data-vehicle="car">
                                            <div class="card-body text-center">
                                                <i class="fas fa-car fa-3x mb-3"></i>
                                                <h5>Carro</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="card vehicle-card" data-vehicle="motorcycle">
                                            <div class="card-body text-center">
                                                <i class="fas fa-motorcycle fa-3x mb-3"></i>
                                                <h5>Moto</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="plan_type" id="plan_type">
                            </div>
                            
                            <!-- Etapa 2: Prazo -->
                            <div class="step" id="step-2" style="display: none;">
                                <h5 class="mb-4">Escolha o prazo desejado:</h5>
                                <div class="row term-options">
                                    <!-- Opções de prazo carregadas dinamicamente -->
                                </div>
                                <input type="hidden" name="plan_term" id="plan_term">
                                <div class="mt-3">
                                    <button type="button" class="btn btn-secondary btn-back">Voltar</button>
                                </div>
                            </div>
                            
                            <!-- Etapa 3: Valor do crédito -->
                            <div class="step" id="step-3" style="display: none;">
                                <h5 class="mb-4">Escolha o valor do crédito:</h5>
                                <div class="row credit-options">
                                    <!-- Opções de crédito carregadas dinamicamente -->
                                </div>
                                <input type="hidden" name="plan_id" id="plan_id">
                                <input type="hidden" name="plan_credit" id="plan_credit">
                                <input type="hidden" name="first_installment" id="first_installment">
                                <input type="hidden" name="other_installments" id="other_installments">
                                <div class="mt-3">
                                    <button type="button" class="btn btn-secondary btn-back">Voltar</button>
                                </div>
                            </div>
                            
                            <!-- Etapa 4: Resultado e dados do usuário -->
                            <div class="step" id="step-4" style="display: none;">
                                <h5 class="mb-4">Resultado da Simulação</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0">Detalhes do Plano</h6>
                                            </div>
                                            <div class="card-body">
                                                <p><strong>Tipo:</strong> <span id="result-type"></span></p>
                                                <p><strong>Crédito:</strong> R$ <span id="result-credit"></span></p>
                                                <p><strong>Prazo:</strong> <span id="result-term"></span> meses</p>
                                                <p><strong>Primeira parcela:</strong> R$ <span id="result-first"></span></p>
                                                <p><strong>Demais parcelas:</strong> R$ <span id="result-others"></span></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0">Seus Dados</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="name" class="form-label">Nome completo *</label>
                                                    <input type="text" class="form-control lp-form-control" id="name" name="name" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="email" class="form-label">E-mail</label>
                                                    <input type="email" class="form-control lp-form-control" id="email" name="email">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="phone" class="form-label">Telefone (WhatsApp) *</label>
                                                    <input type="text" class="form-control lp-form-control" id="phone" name="phone" required>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-8 mb-3">
                                                        <label for="city" class="form-label">Cidade *</label>
                                                        <input type="text" class="form-control lp-form-control" id="city" name="city" required>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label for="state" class="form-label">Estado *</label>
                                                        <select class="form-select lp-form-control" id="state" name="state" required>
                                                            <option value="">Selecione</option>
                                                            <option value="AC">AC</option>
                                                            <option value="AL">AL</option>
                                                            <option value="AP">AP</option>
                                                            <option value="AM">AM</option>
                                                            <option value="BA">BA</option>
                                                            <option value="CE">CE</option>
                                                            <option value="DF">DF</option>
                                                            <option value="ES">ES</option>
                                                            <option value="GO">GO</option>
                                                            <option value="MA">MA</option>
                                                            <option value="MT">MT</option>
                                                            <option value="MS">MS</option>
                                                            <option value="MG">MG</option>
                                                            <option value="PA">PA</option>
                                                            <option value="PB">PB</option>
                                                            <option value="PR">PR</option>
                                                            <option value="PE">PE</option>
                                                            <option value="PI">PI</option>
                                                            <option value="RJ">RJ</option>
                                                            <option value="RN">RN</option>
                                                            <option value="RS">RS</option>
                                                            <option value="RO">RO</option>
                                                            <option value="RR">RR</option>
                                                            <option value="SC">SC</option>
                                                            <option value="SP">SP</option>
                                                            <option value="SE">SE</option>
                                                            <option value="TO">TO</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="plan_model" class="form-label">Modelo desejado</label>
                                                    <input type="text" class="form-control lp-form-control" id="plan_model" name="plan_model">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-secondary btn-back">Voltar</button>
                                    <button type="submit" class="btn btn-primary lp-submit-btn">Quero ser contactado!</button>
                                </div>
                                <p class="lp-form-secure"><i class="fas fa-lock"></i> Seus dados estão seguros e não serão compartilhados.</p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="lp-footer" style="background-color: <?php echo htmlspecialchars($footer_bg_color); ?>; color: <?php echo htmlspecialchars($footer_text_color); ?>;">
        <div class="container-fluid">
            <div class="row px-md-5">
                <div class="col-md-6">
                    <div class="lp-footer-logo">
                        <h3><?php echo getSetting('site_name') ?: 'ConCamp'; ?></h3>
                    </div>
                    <p class="lp-footer-desc">Especialistas em contratos premiados para aquisição de veículos desde 2002.</p>
                    <div class="lp-footer-contact">
                        <div class="lp-footer-contact-item">
                            <div class="lp-footer-contact-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <strong>Consultor:</strong> <?php echo htmlspecialchars($seller_name); ?>
                            </div>
                        </div>
                        <?php if (!empty($seller_phone)): ?>
                        <div class="lp-footer-contact-item">
                            <div class="lp-footer-contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <strong>Telefone:</strong> <?php echo htmlspecialchars($seller_phone); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="lp-footer-social">
                        <a href="#" class="lp-footer-social-item">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="lp-footer-social-item">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="lp-footer-social-item">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
                <div class="col-md-6 mt-4 mt-md-0">
                    <h5>Sobre os Contratos Premiados</h5>
                    <p>Os contratos premiados são a maneira mais inteligente e econômica para adquirir seu veículo novo ou usado. Com parcelas muito menores que as de um financiamento tradicional, você pode realizar o sonho do seu carro sem comprometer o orçamento.</p>
                    <p>Somos uma empresa autorizada pelo Banco Central do Brasil e todos os nossos contratos são registrados em cartório para sua total segurança.</p>
                </div>
            </div>
            <div class="lp-footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo getSetting('site_name') ?: 'ConCamp'; ?>. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS - Animate On Scroll Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Dados para o JavaScript -->
    <script>
        const carPlans = <?php echo json_encode($car_plans); ?>;
        const motorcyclePlans = <?php echo json_encode($motorcycle_plans); ?>;
        const carTerms = <?php echo json_encode($car_terms); ?>;
        const motorcycleTerms = <?php echo json_encode($motorcycle_terms); ?>;
    </script>

    <!-- Incluir o JavaScript do simulador -->
    <script src="<?php echo url('assets/js/simulador.js'); ?>"></script>

    <!-- JavaScript da Landing Page -->
    <script>
        // Inicializar AOS (Animate On Scroll)
        AOS.init({
            duration: 800,
            once: true
        });
        
        // Countdown
        function updateCountdown() {
            // Data final (7 dias a partir de agora)
            const today = new Date();
            const endDate = new Date();
            endDate.setDate(today.getDate() + 7);
            
            // Calcular o tempo restante
            const timeRemaining = endDate - today;
            
            // Converter para dias, horas, minutos, segundos
            const days = Math.floor(timeRemaining / (1000 * 60 * 60 * 24));
            const hours = Math.floor((timeRemaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeRemaining % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeRemaining % (1000 * 60)) / 1000);
            
            // Atualizar o DOM
            document.getElementById('countdown-days').textContent = days;
            document.getElementById('countdown-hours').textContent = hours;
            document.getElementById('countdown-minutes').textContent = minutes;
            document.getElementById('countdown-seconds').textContent = seconds;
        }
        
        // Atualizar a cada segundo
        setInterval(updateCountdown, 1000);
        updateCountdown();
        
        // Rolagem suave para as âncoras
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Máscara para telefone
        const phoneMask = IMask(document.getElementById('phone'), {
            mask: '(00) 00000-0000'
        });
    </script>
</body>
</html>
