/**
 * ConCamp - Sistema de Contratos Premiados
 * Script principal para funções gerais do site
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips do Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Inicializar popovers do Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Simulador multi-etapas
    initSimulator();
    
    // Máscaras para inputs
    initInputMasks();
    
    // Inicializar validação de formulários
    initFormValidation();
    
    // Animações de entrada
    initScrollAnimations();
});

/**
 * Inicializar simulador multi-etapas
 */
function initSimulator() {
    const simulator = document.getElementById('simulator');
    if (!simulator) return;
    
    const steps = simulator.querySelectorAll('.simulator-step');
    const progress = simulator.querySelector('.progress-bar');
    const btnPrev = simulator.querySelector('.btn-prev');
    const btnNext = simulator.querySelector('.btn-next');
    
    let currentStep = 0;
    
    // Exibir primeira etapa
    if (steps.length > 0) {
        steps[0].classList.add('active');
        updateProgress();
    }
    
    // Botão próximo
    if (btnNext) {
        btnNext.addEventListener('click', function() {
            if (validateStep(currentStep)) {
                steps[currentStep].classList.remove('active');
                currentStep++;
                
                if (currentStep >= steps.length) {
                    submitForm();
                    return;
                }
                
                steps[currentStep].classList.add('active');
                updateProgress();
                window.scrollTo(0, simulator.offsetTop - 50);
            }
        });
    }
    
    // Botão anterior
    if (btnPrev) {
        btnPrev.addEventListener('click', function() {
            if (currentStep > 0) {
                steps[currentStep].classList.remove('active');
                currentStep--;
                steps[currentStep].classList.add('active');
                updateProgress();
                window.scrollTo(0, simulator.offsetTop - 50);
            }
        });
    }
    
    // Verificar visibilidade dos botões a cada troca de etapa
    function updateProgress() {
        if (progress) {
            const percentage = ((currentStep + 1) / steps.length) * 100;
            progress.style.width = percentage + '%';
            progress.setAttribute('aria-valuenow', percentage);
        }
        
        if (btnPrev) {
            btnPrev.style.display = currentStep === 0 ? 'none' : 'block';
        }
        
        if (btnNext) {
            btnNext.textContent = currentStep === steps.length - 1 ? 'Finalizar' : 'Próximo';
        }
    }
    
    // Validar campos da etapa atual
    function validateStep(stepIndex) {
        const step = steps[stepIndex];
        const requiredInputs = step.querySelectorAll('[required]');
        let isValid = true;
        
        requiredInputs.forEach(input => {
            if (!input.value) {
                isValid = false;
                input.classList.add('is-invalid');
                
                // Adicionar feedback de erro se não existir
                const parent = input.parentElement;
                if (!parent.querySelector('.invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = 'Este campo é obrigatório.';
                    parent.appendChild(feedback);
                }
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        // Para o caso de radio buttons e checkboxes
        const radioGroups = step.querySelectorAll('.form-check-input[required]');
        const groupNames = [];
        
        radioGroups.forEach(radio => {
            if (!groupNames.includes(radio.name)) {
                groupNames.push(radio.name);
            }
        });
        
        groupNames.forEach(name => {
            const checkedInput = step.querySelector(`input[name="${name}"]:checked`);
            if (!checkedInput) {
                isValid = false;
                
                // Destacar erro no grupo de radio buttons
                const radioContainer = step.querySelector(`.radio-group-${name}`);
                if (radioContainer) {
                    radioContainer.classList.add('is-invalid');
                    
                    // Adicionar feedback de erro se não existir
                    if (!radioContainer.querySelector('.invalid-feedback')) {
                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback d-block';
                        feedback.textContent = 'Selecione uma opção.';
                        radioContainer.appendChild(feedback);
                    }
                }
            } else {
                const radioContainer = step.querySelector(`.radio-group-${name}`);
                if (radioContainer) {
                    radioContainer.classList.remove('is-invalid');
                }
            }
        });
        
        return isValid;
    }
    
    // Submeter o formulário na última etapa
    function submitForm() {
        const form = simulator.querySelector('form');
        if (form) {
            form.submit();
        }
    }
    
    // Eventos de mudança de plano e prazo para atualização de valores
    setupPlanChangeEvents();
}

/**
 * Configurar eventos para atualização de valores no simulador
 */
function setupPlanChangeEvents() {
    // Tipo de plano (carro/moto)
    const planType = document.getElementById('plan_type');
    if (planType) {
        planType.addEventListener('change', function() {
            updatePlanOptions();
        });
    }
    
    // Plano específico
    const planSelect = document.getElementById('plan_id');
    if (planSelect) {
        planSelect.addEventListener('change', function() {
            updatePlanValues();
        });
    }
    
    // Prazo
    const termSelect = document.getElementById('plan_term');
    if (termSelect) {
        termSelect.addEventListener('change', function() {
            updatePlanOptions();
        });
    }
}

/**
 * Atualizar opções de planos com base no tipo e prazo
 */
function updatePlanOptions() {
    const planType = document.getElementById('plan_type');
    const termSelect = document.getElementById('plan_term');
    const planSelect = document.getElementById('plan_id');
    
    if (planType && termSelect && planSelect) {
        const type = planType.value;
        const term = termSelect.value;
        
        // Requisição AJAX para buscar planos disponíveis
        fetch(`index.php?route=api/plans&type=${type}&term=${term}`)
            .then(response => response.json())
            .then(data => {
                // Limpar opções atuais
                planSelect.innerHTML = '<option value="">Selecione</option>';
                
                if (data.success && data.plans && data.plans.length > 0) {
                    // Adicionar novas opções
                    data.plans.forEach(plan => {
                        const option = document.createElement('option');
                        option.value = plan.id;
                        
                        // Texto diferente para carros e motos
                        if (type === 'car') {
                            option.textContent = `Crédito: R$ ${formatCurrency(plan.credit_value)}`;
                        } else {
                            const model = plan.model || 'Modelo não especificado';
                            option.textContent = `${model} - R$ ${formatCurrency(plan.credit_value)}`;
                        }
                        
                        // Adicionar dados extras como atributos
                        option.setAttribute('data-first', plan.first_installment);
                        option.setAttribute('data-other', plan.other_installments);
                        option.setAttribute('data-credit', plan.credit_value);
                        option.setAttribute('data-model', plan.model || '');
                        
                        planSelect.appendChild(option);
                    });
                }
                
                // Atualizar valores
                updatePlanValues();
            })
            .catch(error => {
                console.error('Erro ao buscar planos:', error);
            });
    }
}

/**
 * Atualizar valores com base no plano selecionado
 */
function updatePlanValues() {
    const planSelect = document.getElementById('plan_id');
    const resultContainer = document.getElementById('simulator-result');
    
    if (planSelect && planSelect.value && resultContainer) {
        const selectedOption = planSelect.options[planSelect.selectedIndex];
        
        const first = parseFloat(selectedOption.getAttribute('data-first'));
        const other = parseFloat(selectedOption.getAttribute('data-other'));
        const credit = parseFloat(selectedOption.getAttribute('data-credit'));
        const term = parseInt(document.getElementById('plan_term').value);
        
        // Calcular valor total
        const total = first + (other * (term - 1));
        
        // Atualizar elementos de resultado
        const firstEl = document.getElementById('result-first');
        const otherEl = document.getElementById('result-other');
        const totalEl = document.getElementById('result-total');
        const creditEl = document.getElementById('result-credit');
        
        if (firstEl) firstEl.textContent = formatCurrency(first);
        if (otherEl) otherEl.textContent = formatCurrency(other);
        if (totalEl) totalEl.textContent = formatCurrency(total);
        if (creditEl) creditEl.textContent = formatCurrency(credit);
        
        // Mostrar resultado
        resultContainer.style.display = 'block';
        
        // Preencher campos ocultos para envio
        document.getElementById('first_installment').value = first;
        document.getElementById('other_installments').value = other;
        document.getElementById('plan_credit').value = credit;
        
        const modelInput = document.getElementById('plan_model');
        if (modelInput) {
            modelInput.value = selectedOption.getAttribute('data-model');
        }
    }
}

/**
 * Inicializar máscaras para inputs
 */
function initInputMasks() {
    // Máscara para telefone
    const phoneInputs = document.querySelectorAll('.phone-mask');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 11) {
                value = value.slice(0, 11);
            }
            
            if (value.length > 2) {
                value = '(' + value.slice(0, 2) + ') ' + value.slice(2);
            }
            
            if (value.length > 10) {
                value = value.slice(0, 10) + '-' + value.slice(10);
            }
            
            e.target.value = value;
        });
    });
    
    // Máscara para moeda
    const currencyInputs = document.querySelectorAll('.currency-mask');
    currencyInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 0) {
                value = (parseInt(value) / 100).toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL',
                    minimumFractionDigits: 2
                });
            }
            
            e.target.value = value;
        });
    });
}

/**
 * Inicializar validação de formulários
 */
function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        });
    });
}

/**
 * Inicializar animações de entrada ao rolar
 */
function initScrollAnimations() {
    const elements = document.querySelectorAll('.fade-in, .slide-up');
    
    if (elements.length === 0) return;
    
    // Observador de interseção para animações
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });
    
    elements.forEach(element => {
        observer.observe(element);
    });
}

/**
 * Funções auxiliares
 */

// Formatar valor para exibição em moeda
function formatCurrency(value) {
    return value.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
