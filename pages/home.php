<?php
$page_title = "ConCamp - Contratos Premiados";
$body_class = "home-page";
$is_homepage = true;

// Verificar se há mensagem de sucesso da simulação
$simulation_success = isset($_GET['simulation_success']) && $_GET['simulation_success'] === 'true';
$simulation_data = isset($_SESSION['simulation_data']) ? $_SESSION['simulation_data'] : null;

if ($simulation_success && $simulation_data) {
    // Limpar dados da sessão após exibir
    unset($_SESSION['simulation_data']);
}
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content">
                <h1 class="display-4 fw-bold mb-4">Realize o sonho do seu veículo com a ConCamp</h1>
                <p class="lead mb-4">Contratos premiados para aquisição de carros e motos, com sorteios mensais que podem quitar 100% do seu contrato.</p>
                <a href="index.php?route=simulador" class="btn btn-primary btn-lg">Faça uma simulação</a>
            </div>
            
            <?php if ($simulation_success && $simulation_data): ?>
            <div class="col-lg-6 mt-5 mt-lg-0">
                <div class="card border-0 shadow-lg fade-in">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <h3>Simulação realizada com sucesso!</h3>
                            <p class="text-muted">Enviamos os detalhes para o seu WhatsApp.</p>
                        </div>
                        
                        <h5 class="border-bottom pb-2 mb-3">Resumo da sua simulação:</h5>
                        
                        <div class="row mb-2">
                            <div class="col-5 fw-bold">Tipo:</div>
                            <div class="col-7"><?php echo $simulation_data['plan_type']; ?></div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-5 fw-bold">Crédito:</div>
                            <div class="col-7">R$ <?php echo formatCurrency($simulation_data['plan_credit']); ?></div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-5 fw-bold">Prazo:</div>
                            <div class="col-7"><?php echo $simulation_data['plan_term']; ?> meses</div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-5 fw-bold">Primeira parcela:</div>
                            <div class="col-7">R$ <?php echo formatCurrency($simulation_data['first_installment']); ?></div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-5 fw-bold">Demais parcelas:</div>
                            <div class="col-7">R$ <?php echo formatCurrency($simulation_data['other_installments']); ?></div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <p>Em breve, um de nossos consultores entrará em contato com você para mais detalhes.</p>
                            <a href="index.php?route=simulador" class="btn btn-outline-primary">Fazer nova simulação</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Como funciona o Contrato Premiado?</h2>
            <p class="text-muted">Um jeito diferente e inteligente de comprar seu veículo</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <h4 class="mt-4 mb-3">1. Escolha seu plano</h4>
                        <p class="text-muted">Selecione o valor do crédito e o prazo que melhor se adequa ao seu orçamento.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="feature-card card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <h4 class="mt-4 mb-3">2. Pague as parcelas</h4>
                        <p class="text-muted">Efetue o pagamento mensal das parcelas conforme o plano escolhido.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="feature-card card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="feature-icon mx-auto">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h4 class="mt-4 mb-3">3. Concorra aos sorteios</h4>
                        <p class="text-muted">Após a 2ª parcela, você já concorre mensalmente à quitação do seu contrato pela Loteria Federal.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Steps Section -->
<section class="steps-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">O caminho para o seu veículo</h2>
            <p class="text-muted">Siga estes passos simples para adquirir seu carro ou moto</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-3">
                <div class="step-item">
                    <div class="step-number">1</div>
                    <h5 class="mb-3">Faça uma simulação</h5>
                    <p class="text-muted">Escolha o tipo de veículo, prazo e valor do crédito desejado.</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="step-item">
                    <div class="step-number">2</div>
                    <h5 class="mb-3">Fale com um consultor</h5>
                    <p class="text-muted">Nossos consultores entrarão em contato para esclarecer todas as suas dúvidas.</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="step-item">
                    <div class="step-number">3</div>
                    <h5 class="mb-3">Assine seu contrato</h5>
                    <p class="text-muted">Após aprovar sua simulação, assine o contrato e efetue o pagamento da primeira parcela.</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="step-item">
                    <div class="step-number">4</div>
                    <h5 class="mb-3">Comece a concorrer</h5>
                    <p class="text-muted">Após a 2ª parcela, você já está concorrendo mensalmente aos sorteios.</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <a href="index.php?route=simulador" class="btn btn-primary btn-lg">Faça sua simulação agora</a>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">O que nossos clientes dizem</h2>
            <p class="text-muted">Depoimentos de quem já foi contemplado</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="testimonial-card card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="testimonial-avatar mx-auto">
                            <img src="assets/img/avatar1.jpg" alt="Cliente 1">
                        </div>
                        <p class="testimonial-text">"Paguei apenas 5 parcelas e fui contemplado no sorteio! Consegui quitar 100% do meu contrato e hoje tenho meu carro novo. Super recomendo!"</p>
                        <h5 class="testimonial-name">Roberto Silva</h5>
                        <p class="testimonial-role">São Paulo, SP</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="testimonial-card card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="testimonial-avatar mx-auto">
                            <img src="assets/img/avatar2.jpg" alt="Cliente 2">
                        </div>
                        <p class="testimonial-text">"Procurei várias opções para comprar minha moto e o contrato premiado da ConCamp foi a melhor alternativa. Atendimento excelente e tudo muito transparente."</p>
                        <h5 class="testimonial-name">Ana Oliveira</h5>
                        <p class="testimonial-role">Rio de Janeiro, RJ</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="testimonial-card card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="testimonial-avatar mx-auto">
                            <img src="assets/img/avatar3.jpg" alt="Cliente 3">
                        </div>
                        <p class="testimonial-text">"No meu caso, não fui sorteado, mas consegui meu carro com parcelas que cabiam no meu orçamento. A experiência com a ConCamp foi excelente do início ao fim."</p>
                        <h5 class="testimonial-name">Carlos Mendes</h5>
                        <p class="testimonial-role">Belo Horizonte, MG</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Perguntas Frequentes</h2>
            <p class="text-muted">Tire suas dúvidas sobre os contratos premiados</p>
        </div>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                O que é um contrato premiado?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Contrato premiado é uma modalidade de aquisição de veículos onde você paga parcelas mensais e concorre mensalmente a sorteios que podem quitar 100% do seu contrato. Não é consórcio nem financiamento.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                Quando começo a concorrer aos sorteios?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Você começa a concorrer aos sorteios mensais após o pagamento da 2ª parcela. Os sorteios são realizados pela Loteria Federal.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                Quais veículos posso adquirir?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Você pode adquirir carros e motos, novos ou usados, de acordo com o valor do crédito contratado. Temos diversas opções de crédito disponíveis para você escolher o veículo ideal.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                Quais são os prazos disponíveis?
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Oferecemos contratos com prazos de 60, 72 ou 80 meses (5, 6 ou 6 anos e 8 meses), dependendo do tipo de veículo e valor do crédito. Você pode escolher o prazo que melhor se adequa ao seu orçamento.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 shadow-sm">
                        <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                A ConCamp é confiável?
                            </button>
                        </h2>
                        <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Sim, a ConCamp existe desde 2002 e já entregou mais de 400 prêmios aos seus clientes. Somos uma empresa sólida no mercado, com milhares de clientes satisfeitos em todo o Brasil.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <p class="mb-4">Ainda tem dúvidas? Fale conosco!</p>
            <a href="#" class="btn btn-outline-primary btn-lg me-2"><i class="fab fa-whatsapp me-2"></i> WhatsApp</a>
            <a href="#" class="btn btn-outline-primary btn-lg"><i class="fas fa-envelope me-2"></i> E-mail</a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-lg">
                    <div class="card-body p-5 text-center">
                        <h2 class="fw-bold mb-4">Pronto para realizar o sonho do seu veículo?</h2>
                        <p class="lead mb-4">Faça uma simulação agora mesmo e descubra como os contratos premiados da ConCamp podem te ajudar.</p>
                        <a href="index.php?route=simulador" class="btn btn-primary btn-lg">Simular agora</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
