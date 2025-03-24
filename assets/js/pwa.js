/**
 * ConCamp PWA - Script para gerenciar funcionalidades de Progressive Web App
 */

// Variáveis para controle da instalação do PWA
let deferredPrompt;
let userChecked = false;
let sessionCheckIntervalId = null;

// Definir classe do corpo baseada no tipo de usuário e autenticação
function setUserBodyClass() {
    // Verificar via URL se estamos na página de dashboard ou admin
    const urlParams = new URLSearchParams(window.location.search);
    const route = urlParams.get('route') || '';
    const isAdminRoute = route.startsWith('admin-');
    const isDashboardRoute = route === 'dashboard' || route === 'leads' || route.includes('seller-');
    
    // Verificar se há elementos no DOM que indicam que o usuário está logado
    const hasAdminMenu = document.querySelector('.dropdown-item[href*="admin-"]');
    const hasUserMenu = document.querySelector('#userDropdown');
    const hasDashboard = document.querySelector('a[href*="dashboard"]');
    
    // Se o usuário está logado, adicionar classes específicas ao corpo da página
    if (hasUserMenu) {
        if (hasAdminMenu || isAdminRoute) {
            document.body.classList.add('admin-user');
        } else if (hasDashboard || isDashboardRoute) {
            document.body.classList.add('seller-user');
        }
        document.body.classList.add('authenticated-user');
    }
    
    userChecked = true;
}

// Verificar se o site está sendo executado como PWA
function isPwa() {
    return window.matchMedia('(display-mode: standalone)').matches || 
           window.navigator.standalone || 
           document.referrer.includes('android-app://');
}

// Registrar o Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        // Definir classes do corpo
        setUserBodyClass();
        
        // Registrar Service Worker com escopo explícito
        navigator.serviceWorker.register('/concamp/service-worker.js', {
            scope: '/concamp/'
        })
            .then(registration => {
                console.log('Service Worker registrado com sucesso:', registration);
            })
            .catch(error => {
                console.error('Erro ao registrar Service Worker:', error);
            });
    });
}

// Verificar se o PWA já foi instalado ou se o usuário já recusou a instalação
const pwaStateKey = 'concamp_pwa_state';
const pwaState = JSON.parse(localStorage.getItem(pwaStateKey) || '{"installed": false, "dismissed": false, "lastPrompt": 0}');

// Determinar se devemos mostrar o prompt
function shouldShowPrompt() {
    // Não mostrar se já estiver instalado
    if (isPwa() || pwaState.installed) {
        return false;
    }
    
    // Não mostrar se o usuário recusou recentemente (menos de 7 dias)
    if (pwaState.dismissed) {
        const daysSinceLastPrompt = (Date.now() - pwaState.lastPrompt) / (1000 * 60 * 60 * 24);
        if (daysSinceLastPrompt < 7) {
            return false;
        }
    }
    
    // Verificar se o usuário está logado e é admin ou vendedor
    const isAuthenticated = document.body.classList.contains('authenticated-user');
    const isAdmin = document.body.classList.contains('admin-user');
    const isSeller = document.body.classList.contains('seller-user');
    
    return isAuthenticated && (isAdmin || isSeller);
}

// Escutar evento beforeinstallprompt para capturar prompt de instalação
window.addEventListener('beforeinstallprompt', (e) => {
    // Prevenir Chrome 67+ de mostrar automaticamente o prompt
    e.preventDefault();
    
    // Guardar o evento para mostrar mais tarde
    deferredPrompt = e;
    
    // Verificar se não configuramos as classes do usuário ainda
    if (!userChecked) {
        setUserBodyClass();
    }
    
    // Verificar se devemos mostrar o prompt
    if (shouldShowPrompt()) {
        // Mostrar modal depois de 3 segundos
        setTimeout(() => {
            showInstallPrompt();
        }, 3000);
    }
});

// Função para mostrar o modal de instalação PWA
function showInstallPrompt() {
    // Se já tivermos um modal aberto, não criar outro
    if (document.getElementById('pwaInstallModal')) {
        return;
    }
    
    // Obter o nome personalizado do PWA se disponível
    const pwaName = document.querySelector('meta[name="apple-mobile-web-app-title"]')?.content || 'ConCamp';
    
    // Criar modal dinamicamente
    const modalHtml = `
    <div class="modal fade" id="pwaInstallModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Instalar ${pwaName} como aplicativo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-mobile-alt fa-4x text-primary mb-3"></i>
                        <p>Instale o ${pwaName} em seu dispositivo para ter acesso mais rápido e uma experiência melhorada, mesmo offline!</p>
                    </div>
                    <div class="d-flex align-items-center border rounded p-3 mb-3">
                        <i class="fas fa-check-circle text-success me-3 fa-2x"></i>
                        <div>
                            <h6 class="mb-1">Acesso rápido</h6>
                            <p class="mb-0 small text-muted">Abra diretamente da tela inicial do seu dispositivo</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center border rounded p-3 mb-3">
                        <i class="fas fa-check-circle text-success me-3 fa-2x"></i>
                        <div>
                            <h6 class="mb-1">Funciona offline</h6>
                            <p class="mb-0 small text-muted">Acesse recursos básicos mesmo sem internet</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center border rounded p-3">
                        <i class="fas fa-check-circle text-success me-3 fa-2x"></i>
                        <div>
                            <h6 class="mb-1">Experiência de aplicativo nativo</h6>
                            <p class="mb-0 small text-muted">Interface otimizada para seu dispositivo</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="pwaLater">Mais tarde</button>
                    <button type="button" class="btn btn-primary" id="pwaInstall">
                        <i class="fas fa-download me-2"></i>Instalar agora
                    </button>
                </div>
            </div>
        </div>
    </div>`;
    
    // Adicionar modal ao corpo da página
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Inicializar o modal
    const modalElement = document.getElementById('pwaInstallModal');
    const modal = new bootstrap.Modal(modalElement);
    
    // Mostrar o modal
    modal.show();
    
    // Adicionar tratadores de eventos aos botões
    document.getElementById('pwaInstall').addEventListener('click', () => {
        // Esconder o modal
        modal.hide();
        
        // Mostrar o prompt de instalação do navegador
        if (deferredPrompt) {
            deferredPrompt.prompt();
            
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('Usuário aceitou a instalação do PWA');
                    pwaState.installed = true;
                    localStorage.setItem(pwaStateKey, JSON.stringify(pwaState));
                } else {
                    console.log('Usuário recusou a instalação do PWA');
                }
                
                deferredPrompt = null;
            });
        }
    });
    
    // Ação para o botão "Mais tarde"
    document.getElementById('pwaLater').addEventListener('click', () => {
        pwaState.dismissed = true;
        pwaState.lastPrompt = Date.now();
        localStorage.setItem(pwaStateKey, JSON.stringify(pwaState));
    });
    
    // Quando o modal for escondido
    modalElement.addEventListener('hidden.bs.modal', () => {
        if (!pwaState.dismissed) {
            pwaState.dismissed = true;
            pwaState.lastPrompt = Date.now();
            localStorage.setItem(pwaStateKey, JSON.stringify(pwaState));
        }
    });
}

// Detectar quando o PWA foi instalado
window.addEventListener('appinstalled', (evt) => {
    console.log('ConCamp PWA foi instalado');
    pwaState.installed = true;
    localStorage.setItem(pwaStateKey, JSON.stringify(pwaState));
    
    // Esconder o modal se estiver aberto
    const modalElement = document.getElementById('pwaInstallModal');
    if (modalElement) {
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
    }
});

// Adicionar botão para instalar PWA no menu do usuário (para usuários autenticados)
document.addEventListener('DOMContentLoaded', () => {
    // Verificar se estamos em PWA e configurar verificação de sessão
    if (isPwa()) {
        // Iniciar um ping a cada 2 minutos para manter a sessão ativa
        if (!sessionCheckIntervalId) {
            sessionCheckIntervalId = setInterval(() => {
                const userRole = document.body.classList.contains('seller-user') ? 'seller' : 
                                (document.body.classList.contains('admin-user') ? 'admin' : '');
                                
                if (userRole) {
                    // Fazer uma requisição simples para o servidor para manter a sessão
                    fetch(`index.php?route=dashboard&pwa=1&keep_session=1&role=${userRole}`, { 
                        method: 'HEAD',
                        credentials: 'include',
                        cache: 'no-store'
                    }).catch(err => console.error('Erro ao manter sessão:', err));
                }
            }, 120000); // A cada 2 minutos
        }
    }
    
    // Adicionar tratador de eventos para botão de instalação PWA mesmo sem deferredPrompt
    // para suportar situações em que o evento beforeinstallprompt ainda não ocorreu
    const mobileMenuInstallPwa = document.getElementById('mobileMenuInstallPwa');
    if (mobileMenuInstallPwa) {
        mobileMenuInstallPwa.addEventListener('click', (e) => {
            e.preventDefault();
            // Se temos um prompt adiado, mostrar
            if (deferredPrompt) {
                showInstallPrompt();
            } else {
                // Caso contrário, mostrar instruções alternativas
                alert('Para instalar o app:\n\n' + 
                      '1. No Chrome: toque em ⋮ (menu) e selecione "Instalar aplicativo"\n' + 
                      '2. No Safari: toque em ↑ (compartilhar) e selecione "Adicionar à Tela de Início"');
            }
        });
    }
    
    // Verificar se o usuário pode instalar o PWA
    if (deferredPrompt && !isPwa()) {
        // Verificar se estamos em uma página autenticada
        if (document.body.classList.contains('authenticated-user')) {
            // Adicionar handler para o botão no dropdown do desktop
            const menuInstallPwa = document.getElementById('menuInstallPwa');
            if (menuInstallPwa) {
                menuInstallPwa.addEventListener('click', (e) => {
                    e.preventDefault();
                    showInstallPrompt();
                });
            }
            
            // Adicionar handler para o botão no menu mobile
            const mobileMenuInstallPwa = document.getElementById('mobileMenuInstallPwa');
            if (mobileMenuInstallPwa) {
                mobileMenuInstallPwa.addEventListener('click', (e) => {
                    e.preventDefault();
                    showInstallPrompt();
                });
            }
        }
    } else {
        // Ocultar links de instalação se o PWA já estiver instalado ou não disponível
        const installLinks = document.querySelectorAll('#menuInstallPwa, #mobileMenuInstallPwa');
        installLinks.forEach(link => {
            const parentLi = link.closest('li');
            if (parentLi) {
                parentLi.style.display = 'none';
                
                // Ocultar também o separador após o link de instalação no desktop
                const nextElement = parentLi.nextElementSibling;
                if (nextElement && nextElement.tagName === 'LI' && nextElement.querySelector('hr.dropdown-divider')) {
                    nextElement.style.display = 'none';
                }
            }
        });
    }
});