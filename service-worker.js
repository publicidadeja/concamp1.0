// Service Worker para ConCamp - Sistema de Contratos Premiados
const CACHE_NAME = 'concamp-v2'; // Incrementamos a versão
const APP_PREFIX = 'concamp'; // Prefixo para identificar os caches da aplicação

// URLs para serem cacheadas durante a instalação
const urlsToCache = [
  // Arquivos CSS
  '/concamp/assets/css/style.css',
  '/concamp/assets/css/dashboard.css',
  '/concamp/assets/css/hardcoded-theme.css',
  '/concamp/assets/css/theme.php',
  
  // Arquivos JavaScript
  '/concamp/assets/js/app.js',
  '/concamp/assets/js/dashboard.js',
  '/concamp/assets/js/notifications.js',
  '/concamp/assets/js/simulador.js',
  '/concamp/assets/js/pwa.js',
  
  // Bibliotecas externas
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css',
  'https://unpkg.com/imask',
  
  // Ícones e imagens importantes
  '/concamp/assets/img/icons/favicon.png',
  '/concamp/assets/img/icons/icon-72x72.png',
  '/concamp/assets/img/icons/icon-96x96.png',
  '/concamp/assets/img/icons/icon-128x128.png',
  '/concamp/assets/img/icons/icon-144x144.png',
  '/concamp/assets/img/icons/icon-152x152.png',
  '/concamp/assets/img/icons/icon-192x192.png',
  '/concamp/assets/img/icons/icon-384x384.png',
  '/concamp/assets/img/icons/icon-512x512.png',
  
  // Páginas principais
  '/concamp/index.php',
  '/concamp/index.php?route=dashboard',
  '/concamp/index.php?route=leads',
  '/concamp/index.php?route=login',
  '/concamp/manifest.json',
  '/concamp/offline.php'
];

// Instalação do Service Worker
self.addEventListener('install', event => {
  // Realizar etapa de instalação
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Cache aberto');
        return cache.addAll(urlsToCache);
      })
  );
});

// Ativação do Service Worker
self.addEventListener('activate', event => {
  // Manter apenas o cache atual e remover versões antigas
  const cacheWhitelist = [CACHE_NAME];
  
  event.waitUntil(
    // Obter todas as chaves de cache e limpar as versões antigas
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          // Se o cache pertence a esta aplicação (tem o prefixo) e não está na lista whitelist
          if (cacheName.startsWith(APP_PREFIX) && cacheWhitelist.indexOf(cacheName) === -1) {
            console.log('Service Worker: Removendo cache antigo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      // Forçar que o service worker assuma o controle deste cliente imediatamente
      console.log('Service Worker: Ativado e controlando a página');
      return self.clients.claim();
    })
  );
});

// Interceptação de solicitações para cache/rede
self.addEventListener('fetch', event => {
  // Verificar se é uma URL relacionada a notificações
  const isNotifications = event.request.url.includes('notifications') || 
                        event.request.url.includes('?route=notifications');
  
  // Para notificações, NUNCA interceptar a requisição
  // Permitir que sempre passe diretamente para o servidor
  if (isNotifications) {
    console.log('[Service Worker] Bypass para URL de notificações:', event.request.url);
    return; // Bypass completo para notificações
  }
  
  // Endereços que não devem ser cacheados
  const apiUrls = ['/api/', '?route=api-', 'upload-', '/admin-'];
  // Verificar se não deve ser cacheado
  const shouldNotCache = event.request.method !== 'GET' || 
                        apiUrls.some(url => event.request.url.includes(url));
  
  // Não interceptar requisições para APIs, POSTs ou endpoints sensíveis
  if (shouldNotCache) {
    return;
  }
  
  // Estratégia: Cache-First com fallback para rede e offline
  event.respondWith(
    caches.match(event.request)
      .then(cachedResponse => {
        // 1. Primeiro verificamos se temos a resposta no cache
        if (cachedResponse) {
          console.log('Service Worker: Usando cache para:', event.request.url);
          return cachedResponse;
        }
        
        // 2. Se não estiver no cache, tentar buscar da rede
        console.log('Service Worker: Buscando da rede:', event.request.url);
        return fetch(event.request.clone())
          .then(networkResponse => {
            // Verificar se recebemos uma resposta válida
            if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
              console.log('Service Worker: Resposta não cacheável:', event.request.url);
              return networkResponse;
            }
            
            // Armazenar em cache a resposta da rede para uso futuro
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME)
              .then(cache => {
                console.log('Service Worker: Armazenando no cache:', event.request.url);
                cache.put(event.request, responseToCache);
              })
              .catch(error => {
                console.error('Service Worker: Erro ao armazenar em cache:', error);
              });
              
            return networkResponse;
          })
          .catch(error => {
            console.log('Service Worker: Falha na rede, usando fallback para:', event.request.url);
            
            // 3. Se a rede falhar, mostrar página offline para solicitações HTML
            if (event.request.headers.get('Accept')?.includes('text/html')) {
              return caches.match('/concamp/offline.php');
            }
            
            // Para outros recursos (CSS, JS), apenas retornar o erro
            return Promise.reject(error);
          });
      })
  );
});