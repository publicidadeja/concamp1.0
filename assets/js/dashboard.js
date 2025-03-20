/**
 * ConCamp - Sistema de Contratos Premiados
 * Script para o painel administrativo
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips do Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Toggle Sidebar (Mobile)
    initSidebar();
    
    // Inicializar gráficos
    initCharts();
    
    // Inicializar datatables
    initDataTables();
    
    // Operações de leads
    initLeadOperations();
    
    // Inicializar operações relacionadas a tarefas
    initTaskOperations();
    
    // Inicializar operações de envio de mensagens
    initMessageOperations();
    
    // Inicializar AJAX para formulários
    initAjaxForms();
    
    // Inicializar filtros de tabelas
    initTableFilters();
    
    // Inicializar toast notifications
    initToasts();
});

/**
 * Inicializar sidebar responsiva
 */
function initSidebar() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarToggleTop = document.getElementById('sidebarToggleTop');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('opened');
        });
    }
    
    if (sidebarToggleTop && sidebar) {
        sidebarToggleTop.addEventListener('click', function() {
            sidebar.classList.toggle('opened');
        });
    }
    
    // Fechar sidebar quando clicar fora em dispositivos móveis
    if (window.innerWidth < 768) {
        document.addEventListener('click', function(event) {
            if (sidebar && sidebar.classList.contains('opened') && 
                !sidebar.contains(event.target) && 
                event.target !== sidebarToggle && 
                event.target !== sidebarToggleTop) {
                sidebar.classList.remove('opened');
            }
        });
    }
}

/**
 * Inicializar gráficos com Chart.js
 */
function initCharts() {
    // Gráfico de Leads por Status
    const statusChartEl = document.getElementById('leadsStatusChart');
    if (statusChartEl) {
        const statusData = JSON.parse(statusChartEl.dataset.stats || '{}');
        
        if (Object.keys(statusData).length > 0) {
            new Chart(statusChartEl, {
                type: 'doughnut',
                data: {
                    labels: [
                        'Novos', 
                        'Contatados', 
                        'Negociando', 
                        'Convertidos', 
                        'Perdidos'
                    ],
                    datasets: [{
                        data: [
                            statusData.new || 0,
                            statusData.contacted || 0,
                            statusData.negotiating || 0,
                            statusData.converted || 0,
                            statusData.lost || 0
                        ],
                        backgroundColor: [
                            '#0d6efd',
                            '#6c757d',
                            '#ffc107',
                            '#198754',
                            '#dc3545'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }
    }
    
    // Gráfico de Leads por Tipo
    const typeChartEl = document.getElementById('leadsTypeChart');
    if (typeChartEl) {
        const typeData = JSON.parse(typeChartEl.dataset.stats || '{}');
        
        if (Object.keys(typeData).length > 0) {
            new Chart(typeChartEl, {
                type: 'pie',
                data: {
                    labels: ['Carros', 'Motos'],
                    datasets: [{
                        data: [
                            typeData.car || 0,
                            typeData.motorcycle || 0
                        ],
                        backgroundColor: [
                            '#0dcaf0',
                            '#6610f2'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }
    }
    
    // Gráfico de Leads Recentes
    const recentChartEl = document.getElementById('recentLeadsChart');
    if (recentChartEl) {
        const recentData = JSON.parse(recentChartEl.dataset.stats || '{}');
        
        if (Object.keys(recentData).length > 0) {
            const dates = Object.keys(recentData).sort().reverse();
            const counts = dates.map(date => recentData[date] || 0);
            
            new Chart(recentChartEl, {
                type: 'bar',
                data: {
                    labels: dates.map(date => {
                        const parts = date.split('-');
                        return `${parts[2]}/${parts[1]}`;
                    }),
                    datasets: [{
                        label: 'Leads',
                        data: counts,
                        backgroundColor: '#0d6efd',
                        borderColor: '#0a58ca',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    }
    
    // Gráfico de Desempenho de Vendedores
    const sellerChartEl = document.getElementById('sellerPerformanceChart');
    if (sellerChartEl) {
        const sellerData = JSON.parse(sellerChartEl.dataset.performance || '[]');
        
        if (sellerData.length > 0) {
            const sellers = sellerData.map(item => item.name);
            const converted = sellerData.map(item => parseInt(item.converted || 0));
            const negotiating = sellerData.map(item => parseInt(item.negotiating || 0));
            const contacted = sellerData.map(item => parseInt(item.contacted || 0));
            const newLeads = sellerData.map(item => parseInt(item.new_leads || 0));
            const lost = sellerData.map(item => parseInt(item.lost || 0));
            
            new Chart(sellerChartEl, {
                type: 'bar',
                data: {
                    labels: sellers,
                    datasets: [
                        {
                            label: 'Convertidos',
                            data: converted,
                            backgroundColor: '#198754',
                            borderColor: '#146c43',
                            borderWidth: 1
                        },
                        {
                            label: 'Negociando',
                            data: negotiating,
                            backgroundColor: '#ffc107',
                            borderColor: '#cc9a06',
                            borderWidth: 1
                        },
                        {
                            label: 'Contatados',
                            data: contacted,
                            backgroundColor: '#6c757d',
                            borderColor: '#565e64',
                            borderWidth: 1
                        },
                        {
                            label: 'Novos',
                            data: newLeads,
                            backgroundColor: '#0d6efd',
                            borderColor: '#0a58ca',
                            borderWidth: 1
                        },
                        {
                            label: 'Perdidos',
                            data: lost,
                            backgroundColor: '#dc3545',
                            borderColor: '#b02a37',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            stacked: true,
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    }
    
    // Gráfico de Planos Populares
    const plansChartEl = document.getElementById('popularPlansChart');
    if (plansChartEl) {
        const plansData = JSON.parse(plansChartEl.dataset.plans || '[]');
        
        if (plansData.length > 0) {
            const plans = plansData.map(item => {
                if (item.plan_type === 'car') {
                    return `Carro - R$ ${formatCurrency(item.plan_credit)}`;
                } else {
                    return `${item.plan_model} - R$ ${formatCurrency(item.plan_credit)}`;
                }
            });
            const counts = plansData.map(item => parseInt(item.count || 0));
            
            new Chart(plansChartEl, {
                type: 'horizontalBar',
                data: {
                    labels: plans,
                    datasets: [{
                        label: 'Quantidade',
                        data: counts,
                        backgroundColor: '#0dcaf0',
                        borderColor: '#0a94b3',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    }
}

/**
 * Inicializar DataTables
 */
function initDataTables() {
    // Tabela de Leads
    const leadsTable = document.getElementById('leadsTable');
    if (leadsTable) {
        new DataTable('#leadsTable', {
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/pt-BR.json',
            },
            responsive: true,
            pageLength: 10,
            order: [[0, 'desc']]
        });
    }
    
    // Tabela de Usuários
    const usersTable = document.getElementById('usersTable');
    if (usersTable) {
        new DataTable('#usersTable', {
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/pt-BR.json',
            },
            responsive: true,
            pageLength: 10
        });
    }
    
    // Tabela de Planos
    const plansTable = document.getElementById('plansTable');
    if (plansTable) {
        new DataTable('#plansTable', {
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/pt-BR.json',
            },
            responsive: true,
            pageLength: 10
        });
    }
}

/**
 * Inicializar operações de leads
 */
function initLeadOperations() {
    // Botões para atualizar status do lead
    const statusBtns = document.querySelectorAll('.update-status-btn');
    
    statusBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const leadId = this.dataset.leadId;
            const status = this.dataset.status;
            
            // Atualizar status via AJAX
            if (leadId && status) {
                updateLeadStatus(leadId, status);
            }
        });
    });
    
    // Asignar vendedor ao lead
    const assignSellerForm = document.getElementById('assignSellerForm');
    if (assignSellerForm) {
        assignSellerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const leadId = this.dataset.leadId;
            const sellerId = document.getElementById('seller_id').value;
            
            if (leadId && sellerId) {
                assignLeadToSeller(leadId, sellerId);
            }
        });
    }
    
    // Formulário de adição de nota/tarefa
    const followUpForm = document.getElementById('followUpForm');
    if (followUpForm) {
        followUpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const leadId = this.dataset.leadId;
            const type = document.getElementById('followup_type').value;
            const content = document.getElementById('followup_content').value;
            const dueDate = document.getElementById('due_date').value;
            
            if (leadId && type && content) {
                addFollowUp(leadId, type, content, dueDate);
            }
        });
    }
}

/**
 * Atualizar status de um lead
 */
function updateLeadStatus(leadId, status) {
    showLoader();
    
    const formData = new FormData();
    formData.append('lead_id', leadId);
    formData.append('status', status);
    formData.append('csrf_token', getCsrfToken());

    fetch('index.php?route=api/lead/update-status', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Tentar ler os dados independentemente do status
        return response.text().then(text => {
            // Tentar converter para JSON
            try {
                return JSON.parse(text);
            } catch (e) {
                // Se não for JSON válido, retornar erro formatado
                console.error('Resposta não é JSON válido:', text);
                return {
                    success: false,
                    error: 'Resposta do servidor inválida',
                    rawResponse: text.substring(0, 500) // Limitar tamanho
                };
            }
        });
    })
    .then(data => {
        hideLoader();
        
        if (data.success) {
            // Atualizar interface
            const statusBadge = document.querySelector(`.lead-status-badge[data-lead-id="${leadId}"]`);
            if (statusBadge) {
                // Remover classes antigas
                statusBadge.classList.remove(
                    'badge-new', 
                    'badge-contacted', 
                    'badge-negotiating', 
                    'badge-converted', 
                    'badge-lost'
                );
                
                // Adicionar nova classe
                statusBadge.classList.add(`badge-${status}`);
                
                // Atualizar texto
                const statusText = {
                    'new': 'Novo',
                    'contacted': 'Contatado',
                    'negotiating': 'Negociando',
                    'converted': 'Convertido',
                    'lost': 'Perdido'
                };
                statusBadge.textContent = statusText[status] || status;
            }
            
            showToast('Sucesso!', data.message || 'Status atualizado com sucesso.', 'success');
            
            // Recarregar a página após 1.5 segundos
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast('Erro!', data.error || 'Ocorreu um erro ao atualizar o status.', 'danger');
        }
    })
    .catch(error => {
        hideLoader();
        console.error('=== ERRO DE COMUNICAÇÃO COM API ===');
        console.error('URL:', 'index.php?route=api/lead/update-status');
        console.error('Erro detalhado:', error);
        
        // A atualização pode ter funcionado mesmo com erro no retorno
        // Tentar recarregar a página para ver o status atualizado
        showToast('Aviso', 'A operação pode ter sido concluída, mas houve um erro na comunicação. A página será recarregada para verificar.', 'warning');
        
        // Recarregar a página após 2 segundos
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    });
}

/**
 * Asignar vendedor a um lead
 */
function assignLeadToSeller(leadId, sellerId) {
    showLoader();
    
    // Enviar requisição AJAX
    fetch('index.php?route=api/lead/assign-seller', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `lead_id=${leadId}&seller_id=${sellerId}&csrf_token=${getCsrfToken()}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na requisição: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        hideLoader();
        
        if (data.success) {
            const sellerInfo = document.getElementById('lead-seller-info');
            if (sellerInfo && data.seller_name) {
                sellerInfo.textContent = data.seller_name;
            }
            
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('assignSellerModal'));
            if (modal) {
                modal.hide();
            }
            
            showToast('Sucesso!', 'Vendedor atribuído com sucesso.', 'success');
        } else {
            showToast('Erro!', data.error || 'Ocorreu um erro ao atribuir o vendedor.', 'danger');
        }
    })
    .catch(error => {
        hideLoader();
        showToast('Erro!', 'Ocorreu um erro na comunicação com o servidor.', 'danger');
        console.error('Erro:', error);
    });
}

/**
 * Adicionar follow-up (nota/tarefa) para um lead
 */
function addFollowUp(leadId, type, content, dueDate = null) {
    showLoader();
    
    // Montar dados para envio
    let formData = new FormData();
    formData.append('lead_id', leadId);
    formData.append('type', type);
    formData.append('content', content);
    formData.append('csrf_token', getCsrfToken());
    
    if (dueDate) {
        formData.append('due_date', dueDate);
    }
    
    // Enviar requisição AJAX
    fetch('index.php?route=api/lead/add-followup', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Tentar ler os dados independentemente do status
        return response.text().then(text => {
            // Tentar converter para JSON
            try {
                return JSON.parse(text);
            } catch (e) {
                // Se não for JSON válido, retornar erro formatado
                console.error('Resposta não é JSON válido:', text);
                return {
                    success: false,
                    error: 'Resposta do servidor inválida',
                    rawResponse: text.substring(0, 500) // Limitar tamanho
                };
            }
        });
    })
    .then(data => {
        hideLoader();
        
        if (data.success) {
            // Limpar formulário
            document.getElementById('followup_content').value = '';
            document.getElementById('due_date').value = '';
            
            // Atualizar a timeline com o novo follow-up
            if (data.followup) {
                addFollowUpToTimeline(data.followup);
            }
            
            showToast('Sucesso!', `${type === 'note' ? 'Nota' : 'Tarefa'} adicionada com sucesso.`, 'success');
        } else {
            showToast('Erro!', data.error || 'Ocorreu um erro ao adicionar o follow-up.', 'danger');
        }
    })
    .catch(error => {
        hideLoader();
        console.error('=== ERRO DE COMUNICAÇÃO COM API (Follow-up) ===');
        console.error('URL:', 'index.php?route=api/lead/add-followup');
        console.error('Erro detalhado:', error);
        
        // A operação pode ter funcionado mesmo com erro no retorno
        showToast('Aviso', 'A nota/tarefa pode ter sido adicionada, mas houve um erro na comunicação. A página será recarregada para verificar.', 'warning');
        
        // Limpar o formulário de qualquer forma
        document.getElementById('followup_content').value = '';
        document.getElementById('due_date').value = '';
        
        // Recarregar a página após 2 segundos
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    });
}

/**
 * Adicionar um novo follow-up à timeline
 */
function addFollowUpToTimeline(followup) {
    const timeline = document.querySelector('.timeline');
    if (!timeline) return;
    
    // Remover alerta de "nenhum registro" se existir
    const noRecordsAlert = timeline.querySelector('.alert');
    if (noRecordsAlert) {
        noRecordsAlert.remove();
    }
    
    // Criar elementos do item de timeline
    const item = document.createElement('div');
    item.className = 'timeline-item';
    
    const dot = document.createElement('div');
    dot.className = 'timeline-dot';
    
    const content = document.createElement('div');
    content.className = 'timeline-content';
    
    const header = document.createElement('div');
    header.className = 'timeline-header';
    
    const title = document.createElement('h6');
    title.className = 'timeline-title';
    title.textContent = followup.type === 'note' ? 'Nota' : 'Tarefa';
    
    const date = document.createElement('span');
    date.className = 'timeline-date';
    date.textContent = followup.created_at || formatDateTime(new Date());
    
    const body = document.createElement('div');
    body.className = 'timeline-body';
    body.textContent = followup.content;
    
    const footer = document.createElement('div');
    footer.className = 'timeline-footer';
    footer.textContent = `Por: ${followup.user_name}`;
    
    // Montar estrutura
    header.appendChild(title);
    header.appendChild(date);
    
    content.appendChild(header);
    content.appendChild(body);
    content.appendChild(footer);
    
    item.appendChild(dot);
    item.appendChild(content);
    
    // Adicionar ao início da timeline
    timeline.insertBefore(item, timeline.firstChild);
}

/**
 * Inicializar operações relacionadas a tarefas
 */
function initTaskOperations() {
    // Botões para marcar tarefa como concluída
    const completeTaskBtns = document.querySelectorAll('.complete-task-btn');
    
    completeTaskBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const taskId = this.dataset.taskId;
            
            if (taskId) {
                completeTask(taskId);
            }
        });
    });
}

/**
 * Marcar tarefa como concluída
 */
function completeTask(taskId) {
    showLoader();
    
    // Enviar requisição AJAX
    fetch('index.php?route=api/task/complete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `task_id=${taskId}&csrf_token=${getCsrfToken()}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na requisição: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        hideLoader();
        
        if (data.success) {
            // Remover ou atualizar elemento na interface
            const taskItem = document.querySelector(`.task-item[data-task-id="${taskId}"]`);
            if (taskItem) {
                taskItem.remove();
            }
            
            // Atualizar contador de tarefas
            const taskCounter = document.getElementById('tasks-count');
            if (taskCounter) {
                const currentCount = parseInt(taskCounter.textContent) || 0;
                taskCounter.textContent = Math.max(0, currentCount - 1);
            }
            
            showToast('Sucesso!', 'Tarefa marcada como concluída.', 'success');
        } else {
            showToast('Erro!', data.error || 'Ocorreu um erro ao concluir a tarefa.', 'danger');
        }
    })
    .catch(error => {
        hideLoader();
        showToast('Erro!', 'Ocorreu um erro na comunicação com o servidor.', 'danger');
        console.error('Erro:', error);
    });
}

/**
 * Inicializar operações de envio de mensagens
 */
function initMessageOperations() {
    // Formulário de envio de mensagem
    const sendMessageForm = document.getElementById('sendMessageForm');
    if (sendMessageForm) {
        sendMessageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const leadId = this.dataset.leadId;
            const templateId = document.getElementById('message_template').value;
            let message = document.getElementById('message_content').value;
            const mediaInput = document.getElementById('message_media');
            
            // Nota: Não substituímos mais o conteúdo pelo template original
            // porque o código em lead-detail.php já processou as variáveis no cliente
            
            if (leadId && message) {
                // Verificar arquivo de mídia
                const mediaFile = mediaInput && mediaInput.files && mediaInput.files.length > 0 ? mediaInput.files[0] : null;
                
                if (mediaFile) {
                    console.log('Arquivo de mídia detectado:', mediaFile.name, 'tipo:', mediaFile.type, 'tamanho:', mediaFile.size);
                }
                
                sendWhatsAppMessage(leadId, message, mediaFile);
            }
        });
        
        // O processamento de variáveis para templates de mensagem agora está implementado
        // diretamente na página lead-detail.php para garantir a substituição adequada
        // com valores dinâmicos do lead e consultor atual.
    }
}

/**
 * Enviar mensagem de WhatsApp para um lead
 */
function sendWhatsAppMessage(leadId, message, media = null) {
    showLoader();
    
    console.log('Preparando envio de mensagem para o lead #' + leadId);
    console.log('Mídia: ', media);
    
    // Montar dados para envio
    let formData = new FormData();
    formData.append('lead_id', leadId);
    formData.append('message', message);
    formData.append('csrf_token', getCsrfToken());
    
    // Anexar mídia com o nome correto esperado pelo backend
    if (media) {
        console.log('Anexando mídia: ', media.name, media.type, media.size);
        formData.append('media', media);
    }
    
    // Obter ID do template se selecionado
    const templateSelect = document.getElementById('message_template');
    if (templateSelect && templateSelect.value) {
        formData.append('template_id', templateSelect.value);
    }
    
    console.log('Enviando mensagem para lead #' + leadId);
    
    // Enviar requisição AJAX
    fetch('index.php?route=api/message/send', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Resposta recebida:', response.status);
        
        // Tentar ler os dados independentemente do status
        return response.text().then(text => {
            // Tentar converter para JSON
            try {
                return JSON.parse(text);
            } catch (e) {
                // Se não for JSON válido, retornar erro formatado
                console.error('Resposta não é JSON válido:', text);
                return {
                    success: false,
                    error: 'Resposta do servidor inválida',
                    rawResponse: text.substring(0, 500) // Limitar tamanho
                };
            }
        });
    })
    .then(data => {
        hideLoader();
        console.log('Dados da resposta:', data);
        
        if (data.success) {
            // Limpar formulário
            document.getElementById('message_content').value = '';
            document.getElementById('message_template').value = '';
            
            if (document.getElementById('message_media')) {
                document.getElementById('message_media').value = '';
            }
            
            // Atualizar histórico de mensagens
            if (data.sent_message) {
                addMessageToHistory(data.sent_message);
            }
            
            // Fechar modal se estiver aberto
            const modal = bootstrap.Modal.getInstance(document.getElementById('sendMessageModal'));
            if (modal) {
                modal.hide();
            }
            
            // Mensagem personalizada dependendo se a mensagem foi realmente enviada via WhatsApp ou apenas registrada
            if (data.whatsapp_sent) {
                showToast('Sucesso!', 'Mensagem enviada com sucesso via WhatsApp.', 'success');
            } else {
                // Exibir mensagem informando que é necessário configurar o token
                showToast('Atenção', 'Mensagem registrada no sistema, mas não foi enviada por WhatsApp. Configure um token de WhatsApp na página "Minha Landing Page".', 'warning');
            }
        } else {
            showToast('Erro!', data.error || 'Ocorreu um erro ao enviar a mensagem.', 'danger');
        }
    })
    .catch(error => {
        hideLoader();
        console.error('=== ERRO DE COMUNICAÇÃO COM API (Mensagem) ===');
        console.error('URL:', 'index.php?route=api/message/send');
        console.error('Erro detalhado:', error);
        
        // Recarregar a página após erro para verificar se a mensagem foi enviada
        showToast('Aviso', 'Houve um erro na comunicação ao enviar a mensagem. A página será recarregada para verificar.', 'warning');
        
        // Fechar modal se estiver aberto
        const modal = bootstrap.Modal.getInstance(document.getElementById('sendMessageModal'));
        if (modal) {
            modal.hide();
        }
        
        // Recarregar a página após 2 segundos
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    });
}

/**
 * Adicionar mensagem ao histórico
 */
function addMessageToHistory(message) {
    const messageList = document.getElementById('message-history');
    if (!messageList) return;
    
    // Remover alerta de "nenhuma mensagem" se existir
    const noMessagesAlert = messageList.querySelector('.alert');
    if (noMessagesAlert) {
        noMessagesAlert.remove();
    }
    
    // Criar elemento de mensagem
    const item = document.createElement('div');
    item.className = 'message-item mb-3 p-3 bg-light rounded';
    
    const header = document.createElement('div');
    header.className = 'd-flex justify-content-between mb-2';
    
    const date = document.createElement('span');
    date.className = 'text-muted small';
    date.textContent = message.sent_date || formatDateTime(new Date());
    
    const sender = document.createElement('span');
    sender.className = 'fw-bold';
    sender.textContent = `Enviado por: ${message.user_name}`;
    
    const content = document.createElement('div');
    content.className = 'message-content';
    content.textContent = message.content;
    
    // Montar estrutura
    header.appendChild(sender);
    header.appendChild(date);
    
    item.appendChild(header);
    item.appendChild(content);
    
    // Se tiver mídia
    if (message.media_url) {
        const media = document.createElement('div');
        media.className = 'message-media mt-2';
        
        const link = document.createElement('a');
        link.href = message.media_url;
        link.target = '_blank';
        link.className = 'text-primary';
        link.innerHTML = '<i class="fas fa-paperclip me-1"></i> Ver anexo';
        
        media.appendChild(link);
        item.appendChild(media);
    }
    
    // Adicionar ao início da lista
    messageList.insertBefore(item, messageList.firstChild);
}

// Função auxiliar para formatar data e hora
function formatDateTime(date) {
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

/**
 * Inicializar formulários AJAX
 */
function initAjaxForms() {
    const ajaxForms = document.querySelectorAll('.ajax-form');
    
    ajaxForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar formulário
            if (!this.checkValidity()) {
                this.classList.add('was-validated');
                return;
            }
            
            const formData = new FormData(this);
            const url = this.action;
            const method = this.method || 'POST';
            const redirectUrl = this.dataset.redirect || '';
            
            submitAjaxForm(url, method, formData, redirectUrl);
        });
    });
}

/**
 * Submeter formulário via AJAX
 */
function submitAjaxForm(url, method, formData, redirectUrl = '') {
    showLoader();
    
    // Adicionar token CSRF aos dados
    formData.append('csrf_token', getCsrfToken());
    
    // Enviar requisição AJAX
    fetch(url, {
        method: method,
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na requisição: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        hideLoader();
        
        if (data.success) {
            showToast('Sucesso!', data.message || 'Operação realizada com sucesso.', 'success');
            
            // Redirecionar se necessário
            if (redirectUrl) {
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 1500);
            }
        } else {
            showToast('Erro!', data.error || 'Ocorreu um erro ao processar a solicitação.', 'danger');
        }
    })
    .catch(error => {
        hideLoader();
        showToast('Erro!', 'Ocorreu um erro na comunicação com o servidor.', 'danger');
        console.error('Erro:', error);
    });
}

/**
 * Inicializar filtros de tabelas
 */
function initTableFilters() {
    const filterForms = document.querySelectorAll('.filter-form');
    
    filterForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Construir URL com parâmetros de filtro
            const formData = new FormData(this);
            const params = new URLSearchParams();
            
            for (const [key, value] of formData.entries()) {
                if (value) {
                    params.append(key, value);
                }
            }
            
            // Adicionar parâmetro de rota
            const currentRoute = window.location.href.split('?')[0];
            const routeName = this.dataset.route || '';
            
            if (routeName) {
                params.append('route', routeName);
            }
            
            // Redirecionar com filtros
            window.location.href = `${currentRoute}?${params.toString()}`;
        });
    });
}

/**
 * Inicializar toast notifications
 */
function initToasts() {
    const toastContainer = document.getElementById('liveToast');
    
    // Verificar se há mensagem flash
    const flashMessage = document.getElementById('flash-message');
    if (flashMessage) {
        const message = flashMessage.dataset.message || '';
        const type = flashMessage.dataset.type || 'info';
        
        if (message) {
            showToast('Notificação', message, type);
        }
    }
}

/**
 * Exibir toast notification
 */
function showToast(title, message, type = 'info') {
    const toastContainer = document.getElementById('liveToast');
    if (!toastContainer) return;
    
    // Definir título e mensagem
    const toastTitle = document.getElementById('toastTitle');
    const toastMessage = document.getElementById('toastMessage');
    
    if (toastTitle) toastTitle.textContent = title;
    if (toastMessage) toastMessage.textContent = message;
    
    // Definir cor com base no tipo
    toastContainer.className = 'toast';
    toastContainer.classList.add(`bg-${type === 'danger' ? 'danger' : (type === 'success' ? 'success' : 'light')}`);
    
    if (type === 'danger' || type === 'success') {
        toastContainer.classList.add('text-white');
    } else {
        toastContainer.classList.remove('text-white');
    }
    
    // Exibir toast
    const toast = new bootstrap.Toast(toastContainer, { delay: 5000 });
    toast.show();
}

/**
 * Exibir loader
 */
function showLoader() {
    let loader = document.getElementById('page-loader');
    
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'page-loader';
        loader.className = 'loader-container';
        loader.innerHTML = '<div class="loader"></div>';
        document.body.appendChild(loader);
    }
    
    loader.style.display = 'flex';
}

/**
 * Ocultar loader
 */
function hideLoader() {
    const loader = document.getElementById('page-loader');
    if (loader) {
        loader.style.display = 'none';
    }
}

/**
 * Obter token CSRF da sessão
 */
function getCsrfToken() {
    const tokenInput = document.querySelector('input[name="csrf_token"]');
    return tokenInput ? tokenInput.value : '';
}

/**
 * Formatar número para exibição em moeda
 */
function formatCurrency(value) {
    return parseFloat(value).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
