<?php
/**
 * Conteúdo do simulador para uso em múltiplas páginas
 * Usado tanto na página principal de simulação quanto nas landing pages de vendedores
 */

// Definir a action do formulário com base no contexto
$form_action = isset($seller_id) ? url('index.php?route=process-seller-simulation') : url('index.php?route=process-simulation');

// Obter planos disponíveis
$car_plans = getPlans('car');
$motorcycle_plans = getPlans('motorcycle');

// Obter prazos disponíveis
$car_terms = getAvailableTerms('car');
$motorcycle_terms = getAvailableTerms('motorcycle');
?>

<div class="container simulator-container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Simulador de Contrato Premiado</h4>
                    <?php if (isset($seller_name)): ?>
                    <p class="mb-0 small">Consultor: <?php echo htmlspecialchars($seller_name); ?></p>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form id="simulator-form" method="post" action="<?php echo $form_action; ?>">
                        <!-- Campo oculto para o ID do vendedor (quando aplicável) -->
                        <?php if (isset($seller_id)): ?>
                        <input type="hidden" name="seller_id" value="<?php echo $seller_id; ?>">
                        <?php endif; ?>
                        
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
                                <!-- Opções de prazo serão carregadas dinamicamente -->
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
                                <!-- Opções de crédito serão carregadas dinamicamente -->
                            </div>
                            <input type="hidden" name="plan_id" id="plan_id">
                            <input type="hidden" name="plan_credit" id="plan_credit">
                            <input type="hidden" name="first_installment" id="first_installment">
                            <input type="hidden" name="other_installments" id="other_installments">
                            <div class="mt-3">
                                <button type="button" class="btn btn-secondary btn-back">Voltar</button>
                            </div>
                        </div>
                        
                        <!-- Etapa 4: Resultado da simulação -->
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
                                                <input type="text" class="form-control" id="name" name="name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="email" class="form-label">E-mail</label>
                                                <input type="email" class="form-control" id="email" name="email">
                                            </div>
                                            <div class="mb-3">
                                                <label for="phone" class="form-label">Telefone (WhatsApp) *</label>
                                                <input type="text" class="form-control" id="phone" name="phone" required>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-8 mb-3">
                                                    <label for="city" class="form-label">Cidade *</label>
                                                    <input type="text" class="form-control" id="city" name="city" required>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="state" class="form-label">Estado *</label>
                                                    <select class="form-select" id="state" name="state" required>
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
                                                <input type="text" class="form-control" id="plan_model" name="plan_model">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-secondary btn-back">Voltar</button>
                                <button type="submit" class="btn btn-primary">Enviar Simulação</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dados para o JavaScript -->
<script>
    const carPlans = <?php echo json_encode($car_plans); ?>;
    const motorcyclePlans = <?php echo json_encode($motorcycle_plans); ?>;
    const carTerms = <?php echo json_encode($car_terms); ?>;
    const motorcycleTerms = <?php echo json_encode($motorcycle_terms); ?>;
</script>

<!-- Incluir o JavaScript do simulador -->
<script src="<?php echo url('assets/js/simulador.js'); ?>"></script>
