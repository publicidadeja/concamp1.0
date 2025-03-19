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
      
      const title = document.createElement('h5');
      title.textContent = term + ' meses';
      
      cardBody.appendChild(title);
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
      
      const title = document.createElement('h5');
      title.textContent = 'R$ ' + formatCurrency(plan.credit_value);
      
      const subtitle = document.createElement('p');
      subtitle.className = 'mb-0';
      subtitle.innerHTML = '<small>1ª de R$ ' + formatCurrency(plan.first_installment) + '<br>' +
                          'Demais de R$ ' + formatCurrency(plan.other_installments) + '</small>';
      
      cardBody.appendChild(title);
      cardBody.appendChild(subtitle);
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
    
    requiredFields.forEach(field => {
      if (!field.value.trim()) {
        valid = false;
        field.classList.add('is-invalid');
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
    }
    
    if (!valid) {
      e.preventDefault();
      alert('Por favor, preencha todos os campos obrigatórios corretamente.');
    }
  });
});
