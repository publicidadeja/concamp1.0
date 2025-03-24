<?php
/**
 * Landing Page padrão do sistema - Modelo AIDA
 */

// Título da página
$page_title = "Contratos Premiados - Conquiste seu veículo agora";
$body_class = "landing-page";

// Obter conteúdo personalizado do banco de dados (usando seller_id = 0 para a landing page padrão)
$conn = getConnection();
$stmt = $conn->prepare("SELECT * FROM seller_lp_content WHERE seller_id = 0 LIMIT 1");
$stmt->execute();
$custom_content = $stmt->fetch(PDO::FETCH_ASSOC);

// Definir conteúdos (usando valores do banco ou padrões)
$headline = $custom_content['headline'] ?? "Conquiste seu veículo sem esperar pela sorte!";
$subheadline = $custom_content['subheadline'] ?? "Contratos premiados com parcelas que cabem no seu bolso.";
$cta_text = $custom_content['cta_text'] ?? "Quero simular agora!";
$benefit_title = $custom_content['benefit_title'] ?? "Por que escolher contratos premiados?";
$featured_car = $custom_content['featured_car'] ?? '';

// Buscar depoimentos da landing page padrão
$stmt = $conn->prepare("SELECT * FROM testimonials WHERE seller_id = 0 AND status = 'active' ORDER BY created_at DESC LIMIT 3");
$stmt->execute();
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar ganhadores para a landing page padrão
$stmt = $conn->prepare("SELECT * FROM winners WHERE seller_id = 0 AND status = 'active' ORDER BY created_at DESC LIMIT 3");
$stmt->execute();
$winners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter planos disponíveis
$car_plans = getPlans('car');
$motorcycle_plans = getPlans('motorcycle');

// Obter prazos disponíveis
$car_terms = getAvailableTerms('car');
$motorcycle_terms = getAvailableTerms('motorcycle');

// Ação do formulário - usando a rota para processamento padrão de simulações
$form_action = url('index.php?route=process-simulation');
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
    
    <!-- Admin Facebook Pixel (para landing page padrão) -->
    <?php
    // Verificar se a coluna facebook_pixel existe
    try {
        // Buscar configuração de pixel do Facebook das configurações globais
        $pixelCode = getSetting('facebook_pixel_code', '');
        if (!empty($pixelCode)) {
            echo $pixelCode;
        }
    } catch (Exception $e) {
        // Em caso de erro, simplesmente não exibe o pixel
        error_log("Erro ao buscar código do Facebook Pixel: " . $e->getMessage());
    }
    ?>
    
    <style>
        /* Estilos específicos para Landing Page AIDA */
        body.landing-page {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
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
        
        /* DESIRE (Urgência) */
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
            background-image: linear-gradient(120deg, #fdfbfb 0%, #ebedee 100%);
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
            overflow: hidden;
        }
        
        .lp-form-container::before {
            content: "";
            position: absolute;
            top: -50px;
            right: -50px;
            width: 100px;
            height: 100px;
            background: var(--lp-accent, #ff9800);
            opacity: 0.1;
            border-radius: 50%;
            z-index: 0;
        }
        
        .lp-form-badge {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--lp-accent, #ff9800);
            color: var(--lp-accent-text, black);
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            z-index: 5;
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
            height: 55px;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            padding: 12px 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0,0,0,0.02);
        }
        
        .lp-form-control:focus {
            border-color: var(--lp-primary, var(--bs-primary));
            box-shadow: 0 0 0 3px rgba(var(--lp-primary-rgb, 0, 5, 60), 0.1);
            transform: translateY(-2px);
        }
        
        .lp-submit-btn {
            width: 100%;
            padding: 16px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 15px;
            margin-top: 15px;
            border: none;
            background-color: var(--lp-primary, var(--bs-primary));
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .lp-submit-btn::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 0;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s ease;
            z-index: -1;
        }
        
        .lp-submit-btn:hover {
            background-color: var(--lp-primary-dark, #000421);
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        .lp-submit-btn:hover::after {
            height: 100%;
        }
        
        .lp-form-secure {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #777;
        }
        
        .lp-form-secure i {
            color: var(--lp-primary, var(--bs-primary));
            margin-right: 5px;
        }
        
        /* Estilos para as opções de veículos */
        .vehicle-card {
            cursor: pointer;
            border-radius: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            border: 2px solid transparent;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .vehicle-card::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--lp-accent, #ff9800);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .vehicle-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .vehicle-card:hover::after {
            transform: scaleX(1);
        }
        
        .vehicle-card.selected {
            border-color: var(--lp-accent, #ff9800);
            background-color: rgba(var(--lp-accent-rgb, 255, 152, 0), 0.05);
        }
        
        .vehicle-card i {
            color: var(--lp-primary, var(--bs-primary));
            transition: all 0.3s ease;
        }
        
        .vehicle-card.selected i {
            color: var(--lp-accent, #ff9800);
            transform: scale(1.1);
        }
        
        /* Estilos para os cartões de prazo e crédito */
        .term-card, .credit-card {
            cursor: pointer;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .term-card:hover, .credit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .term-card.selected, .credit-card.selected {
            border-color: var(--lp-accent, #ff9800);
            background-color: rgba(var(--lp-accent-rgb, 255, 152, 0), 0.05);
        }
        
        .term-value, .credit-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--lp-primary, var(--bs-primary));
            margin-bottom: 5px;
        }
        
        .credit-card .installment {
            font-weight: 600;
            color: var(--lp-accent, #ff9800);
        }
        
        /* Estilos para os botões de voltar */
        .btn-back {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            background-color: #f5f5f5;
            color: #666;
            border: none;
        }
        
        .btn-back:hover {
            background-color: #e0e0e0;
            color: #333;
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
        
        /* Animação para validação de formulário */
        .animate-shake {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        
        /* Estilos para campos inválidos */
        .is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.25) !important;
        }
        
        .validation-message {
            font-size: 0.8rem;
            margin-top: 0.25rem;
            color: #dc3545;
        }
    </style>
</head>
<body class="<?php echo $body_class; ?>">
    <!-- ATTENTION: Hero Section -->
    <section class="lp-hero">
        <div class="container-fluid px-0">            
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
                            <img src="<?php echo !empty($featured_car) ? url($featured_car) : url('assets/img/car-hero.jpg'); ?>" alt="Veículo em destaque" class="img-fluid rounded shadow">
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
                        <h3 class="lp-benefit-title">Parcelas Menores</h3>
                        <p>Até 50% mais baratas que financiamentos tradicionais, sem juros abusivos.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="lp-benefit-card">
                        <div class="lp-benefit-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="lp-benefit-title">Segurança Garantida</h3>
                        <p>Contratos registrados e empresas autorizadas pelo Banco Central.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="lp-benefit-card">
                        <div class="lp-benefit-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h3 class="lp-benefit-title">Contemplação Acelerada</h3>
                        <p>Estratégias exclusivas para aumentar suas chances de contemplação rápida.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- INTEREST: Depoimentos -->
    <?php if (count($testimonials) > 0): ?>
    <section class="lp-testimonials py-5">
        <div class="container-fluid">
            <h2 class="text-center mb-5" data-aos="fade-up">O que nossos clientes dizem</h2>
            <div class="row px-md-5">
                <?php foreach ($testimonials as $index => $testimonial): ?>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="<?php echo 100 * $index; ?>">
                    <div class="lp-testimonial-card">
                        <?php if (!empty($testimonial['photo'])): ?>
                        <div class="mb-4 text-center">
                            <img src="<?php echo url($testimonial['photo']); ?>" alt="<?php echo htmlspecialchars($testimonial['name']); ?>" class="rounded-circle" width="80" height="80" style="object-fit: cover;">
                        </div>
                        <?php endif; ?>
                        <div class="lp-testimonial-text">"<?php echo htmlspecialchars($testimonial['content']); ?>"</div>
                        <div class="lp-testimonial-name"><?php echo htmlspecialchars($testimonial['name']); ?></div>
                        <?php if (!empty($testimonial['city'])): ?>
                        <div class="lp-testimonial-role"><?php echo htmlspecialchars($testimonial['city']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- DESIRE: Clientes Contemplados -->
    <?php if (count($winners) > 0): ?>
    <section class="lp-winners py-5">
        <div class="container-fluid">
            <h2 class="text-center mb-5" data-aos="fade-up">Nossos clientes contemplados</h2>
            <div class="row px-md-5">
                <?php foreach ($winners as $index => $winner): ?>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="<?php echo 100 * $index; ?>">
                    <div class="lp-winner-card card shadow-sm h-100">
                        <?php if (!empty($winner['photo'])): ?>
                        <div class="card-img-top text-center pt-4">
                            <img src="<?php echo url($winner['photo']); ?>" alt="<?php echo htmlspecialchars($winner['name']); ?>" style="max-height:200px; max-width:100%;" class="img-fluid rounded">
                        </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($winner['name']); ?></h5>
                            <p class="card-text">
                                <strong>Veículo:</strong> <?php echo htmlspecialchars($winner['vehicle_model']); ?><br>
                                <strong>Crédito:</strong> R$ <?php echo formatCurrency($winner['credit_amount']); ?><br>
                                <strong>Data:</strong> <?php echo date('d/m/Y', strtotime($winner['contemplation_date'])); ?>
                            </p>
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
            
            <?php if (isset($_GET['simulation_success']) && isset($_SESSION['simulation_data'])): ?>
            <div class="row justify-content-center mb-4">
                <div class="col-lg-8">
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle me-2"></i>Simulação realizada com sucesso!</h5>
                        <p class="mb-0">Obrigado por realizar sua simulação, <?php echo htmlspecialchars($_SESSION['simulation_data']['name']); ?>! 
                        Em breve entraremos em contato com mais informações sobre o seu <?php echo htmlspecialchars($_SESSION['simulation_data']['plan_type']); ?> 
                        no valor de R$ <?php echo formatCurrency($_SESSION['simulation_data']['plan_credit']); ?>.</p>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['simulation_data']); ?>
            <?php endif; ?>
            
            <div class="row justify-content-center">
                <div class="col-lg-8" data-aos="fade-up" data-aos-delay="200">
                    <div class="lp-form-container">
                        <div class="lp-form-badge">Resposta em até 24h</div>
                        <form id="simulator-form" method="post" action="<?php echo $form_action; ?>">
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
    <footer class="lp-footer" style="background-color: <?php echo htmlspecialchars($custom_content['footer_bg_color'] ?? '#343a40'); ?>; color: <?php echo htmlspecialchars($custom_content['footer_text_color'] ?? 'rgba(255,255,255,0.7)'); ?>;">
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
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <strong>Central de Atendimento:</strong> <?php echo getSetting('contact_phone') ?: '(00) 0000-0000'; ?>
                            </div>
                        </div>
                        <div class="lp-footer-contact-item">
                            <div class="lp-footer-contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <strong>E-mail:</strong> <?php echo getSetting('contact_email') ?: 'contato@example.com'; ?>
                            </div>
                        </div>
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