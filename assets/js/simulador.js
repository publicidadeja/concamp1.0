/**
 * Simulador de Contrato Premiado
 * Script para gerenciar a navegação e cálculos do simulador
 */

document.addEventListener('DOMContentLoaded', function() {
  // Elementos do DOM
  const form = document.getElementById('simulator-form');
  const steps = document.querySelectorAll('.step');
  const vehicleCards = document.querySelectorAll('.vehicle-card');
  const backButtons = document.querySelectorAll('.btn-back');
  
  // Variáveis para armazenar a seleção atual
  let selectedVehicle = null;
  let selectedTerm = null;
  let selectedPlan = null;
  
  // Inicializar máscaras para campos de formulário
  if (typeof IMask !== 'undefined') {
    // Máscara para telefone
    const phoneMask = IMask(document.getElementById('phone'), {
      mask: '(00) 00000-0000'
    });
  }
  
  // Evento para seleção de tipo de veículo
  vehicleCards.forEach(card => {
    card.addEventListener('click', function() {
      // Remover seleção anterior
      vehicleCards.forEach(c => c.classList.remove('selected'));
      
      // Adicionar seleção atual
      this.classList.add('selected');
      
      // Armazenar tipo selecionado
      selectedVehicle = this.dataset.vehicle;
      document.getElementById('plan_type').value = selectedVehicle;
      
      // Carregar opções de prazo
      loadTermOptions(selectedVehicle);
      
      // Avançar para próxima etapa
      goToStep(2);
    });
  });
  
  // Evento para botões de voltar
  backButtons.forEach(button => {
    button.addEventListener('click', function() {
      const currentStep = parseInt(this.closest('.step').id.split('-')[1]);
      goToStep(currentStep - 1);
    });
  });
  
  // Função para carregar opções de prazo
  function loadTermOptions(vehicleType) {
    const termOptions = document.querySelector('.term-options');
    termOptions.innerHTML = '';
    
    const terms = vehicleType === 'car' ? carTerms : motorcycleTerms;
    
    terms.forEach(term => {
      const col = document.createElement('div');
      col.className = 'col-md-3 mb-3';
      
      const card = document.createElement('div');
      card.className = 'card term-card';
      card.dataset.term = term;
      
      const cardBody = document.createElement('div');
      cardBody.className = 'card-body text-center';
      
      const valueDiv = document.createElement('div');
      valueDiv.className = 'term-value';
      valueDiv.textContent = term;
      
      const unit = document.createElement('div');
      unit.className = 'term-unit';
      unit.textContent = 'meses';
      
      cardBody.appendChild(valueDiv);
      cardBody.appendChild(unit);
      card.appendChild(cardBody);
      col.appendChild(card);
      termOptions.appendChild(col);
      
      // Adicionar evento de clique
      card.addEventListener('click', function() {
        // Remover seleção anterior
        document.querySelectorAll('.term-card').forEach(c => c.classList.remove('selected'));
        
        // Adicionar seleção atual
        this.classList.add('selected');
        
        // Armazenar prazo selecionado
        selectedTerm = parseInt(this.dataset.term);
        document.getElementById('plan_term').value = selectedTerm;
        
        // Carregar opções de crédito
        loadCreditOptions(selectedVehicle, selectedTerm);
        
        // Avançar para próxima etapa
        goToStep(3);
      });
    });
  }
  
  // Função para carregar opções de crédito
  function loadCreditOptions(vehicleType, term) {
    const creditOptions = document.querySelector('.credit-options');
    creditOptions.innerHTML = '';
    
    const plans = vehicleType === 'car' ? carPlans : motorcyclePlans;
    const filteredPlans = plans.filter(plan => parseInt(plan.term) === term);
    
    filteredPlans.forEach(plan => {
      const col = document.createElement('div');
      col.className = 'col-md-4 mb-3';
      
      const card = document.createElement('div');
      card.className = 'card credit-card';
      card.dataset.planId = plan.id;
      card.dataset.credit = plan.credit_value;
      card.dataset.first = plan.first_installment;
      card.dataset.others = plan.other_installments;
      
      const cardBody = document.createElement('div');
      cardBody.className = 'card-body text-center';
      
      const valueDiv = document.createElement('div');
      valueDiv.className = 'credit-value';
      valueDiv.textContent = 'R$ ' + formatCurrency(plan.credit_value);
      
      const installmentDiv = document.createElement('div');
      installmentDiv.className = 'installment';
      installmentDiv.textContent = '1ª de R$ ' + formatCurrency(plan.first_installment);
      
      const otherInstallments = document.createElement('div');
      otherInstallments.className = 'other-installments';
      otherInstallments.textContent = 'Demais de R$ ' + formatCurrency(plan.other_installments);
      
      cardBody.appendChild(valueDiv);
      cardBody.appendChild(installmentDiv);
      cardBody.appendChild(otherInstallments);
      card.appendChild(cardBody);
      col.appendChild(card);
      creditOptions.appendChild(col);
      
      // Adicionar evento de clique
      card.addEventListener('click', function() {
        // Remover seleção anterior
        document.querySelectorAll('.credit-card').forEach(c => c.classList.remove('selected'));
        
        // Adicionar seleção atual
        this.classList.add('selected');
        
        // Armazenar plano selecionado
        selectedPlan = {
          id: this.dataset.planId,
          credit: this.dataset.credit,
          first: this.dataset.first,
          others: this.dataset.others
        };
        
        document.getElementById('plan_id').value = selectedPlan.id;
        document.getElementById('plan_credit').value = selectedPlan.credit;
        document.getElementById('first_installment').value = selectedPlan.first;
        document.getElementById('other_installments').value = selectedPlan.others;
        
        // Preencher resultado da simulação
        document.getElementById('result-type').textContent = selectedVehicle === 'car' ? 'Carro' : 'Moto';
        document.getElementById('result-credit').textContent = formatCurrency(selectedPlan.credit);
        document.getElementById('result-term').textContent = selectedTerm;
        document.getElementById('result-first').textContent = formatCurrency(selectedPlan.first);
        document.getElementById('result-others').textContent = formatCurrency(selectedPlan.others);
        
        // Avançar para próxima etapa
        goToStep(4);
      });
    });
  }
  
  // Função para navegar entre etapas
  function goToStep(step) {
    steps.forEach(s => s.style.display = 'none');
    document.getElementById('step-' + step).style.display = 'block';
    
    // Rolar para o topo do formulário
    form.scrollIntoView({ behavior: 'smooth' });
  }
  
  // Função para formatar valores monetários
  function formatCurrency(value) {
    return parseFloat(value).toLocaleString('pt-BR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }
  
  // Validação do formulário antes do envio
  form.addEventListener('submit', function(e) {
    const requiredFields = form.querySelectorAll('[required]');
    let valid = true;
    let firstInvalidField = null;
    
    // Remover alertas anteriores
    document.querySelectorAll('.validation-message').forEach(el => el.remove());
    
    requiredFields.forEach(field => {
      if (!field.value.trim()) {
        valid = false;
        field.classList.add('is-invalid');
        
        // Adicionar mensagem de erro
        addValidationMessage(field, 'Este campo é obrigatório');
        
        if (!firstInvalidField) firstInvalidField = field;
      } else {
        field.classList.remove('is-invalid');
      }
    });
    
    // Validar telefone (deve ter pelo menos 10 dígitos)
    const phone = document.getElementById('phone');
    const phoneDigits = phone.value.replace(/\D/g, '');
    if (phoneDigits.length < 10) {
      valid = false;
      phone.classList.add('is-invalid');
      
      // Adicionar mensagem de erro
      addValidationMessage(phone, 'Telefone inválido. Ex: (11) 98765-4321');
      
      if (!firstInvalidField) firstInvalidField = phone;
    }
    
    // Validar email se preenchido
    const email = document.getElementById('email');
    if (email.value.trim() && !validateEmail(email.value)) {
      valid = false;
      email.classList.add('is-invalid');
      
      // Adicionar mensagem de erro
      addValidationMessage(email, 'Email inválido');
      
      if (!firstInvalidField) firstInvalidField = email;
    }
    
    if (!valid) {
      e.preventDefault();
      
      // Focar no primeiro campo inválido
      if (firstInvalidField) {
        firstInvalidField.focus();
        firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      
      // Adicionar animação de shake para destacar campos inválidos
      document.querySelectorAll('.is-invalid').forEach(field => {
        field.classList.add('animate-shake');
        setTimeout(() => {
          field.classList.remove('animate-shake');
        }, 500);
      });
    }
  });
  
  // Função para validar email
  function validateEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
  }
  
  // Função para adicionar mensagem de validação
  function addValidationMessage(field, message) {
    const msgElement = document.createElement('div');
    msgElement.className = 'validation-message text-danger mt-1 small';
    msgElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
    field.parentNode.appendChild(msgElement);
  }
});
