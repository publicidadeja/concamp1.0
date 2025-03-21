<?php
$page_title = "Você está offline - ConCamp";
$body_class = "offline-page";

// Obter nome personalizado do PWA se configurado
$pwa_name = getSetting('pwa_name') ?: 'ConCamp';
$pwa_theme_color = getSetting('pwa_theme_color') ?: '#0d6efd';
$pwa_icon_url = getSetting('pwa_icon_url') ?: 'assets/img/icons/icon-192x192.png';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Meta tags para PWA -->
    <meta name="theme-color" content="<?php echo $pwa_theme_color; ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: <?php echo $pwa_theme_color; ?>;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .offline-container {
            text-align: center;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .app-icon {
            width: 80px;
            height: 80px;
            margin-bottom: 1.5rem;
            border-radius: 1rem;
            padding: 0.5rem;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .offline-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
            background-color: #f8f9fa;
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
        }
        
        .offline-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #343a40;
        }
        
        .offline-text {
            font-size: 1.1rem;
            margin: 0 auto 1.5rem;
            color: #6c757d;
            line-height: 1.5;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .btn-primary:hover {
            filter: brightness(0.9);
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .recently-visited {
            margin-top: 2rem;
            width: 100%;
        }
        
        .recently-visited h3 {
            font-size: 1.1rem;
            color: #343a40;
            margin-bottom: 1rem;
            font-weight: 600;
            text-align: left;
        }
        
        .cached-pages {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: left;
        }
        
        .cached-pages li {
            margin-bottom: 0.5rem;
        }
        
        .cached-pages li a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            display: block;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        
        .cached-pages li a:hover {
            background-color: #f8f9fa;
        }
        
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.05;
            background-image: linear-gradient(30deg, #ccc 12%, transparent 12.5%, transparent 87%, #ccc 87.5%, #ccc),
            linear-gradient(150deg, #ccc 12%, transparent 12.5%, transparent 87%, #ccc 87.5%, #ccc),
            linear-gradient(30deg, #ccc 12%, transparent 12.5%, transparent 87%, #ccc 87.5%, #ccc),
            linear-gradient(150deg, #ccc 12%, transparent 12.5%, transparent 87%, #ccc 87.5%, #ccc),
            linear-gradient(60deg, #cccccc77 25%, transparent 25.5%, transparent 75%, #cccccc77 75%, #cccccc77),
            linear-gradient(60deg, #cccccc77 25%, transparent 25.5%, transparent 75%, #cccccc77 75%, #cccccc77);
            background-position:0 0, 0 0, 25px 25px, 25px 25px, 0 0, 25px 25px;
            background-size: 50px 50px;
        }
    </style>
</head>
<body>
    <div class="bg-pattern"></div>
    
    <div class="offline-container">
        <img src="<?php echo $pwa_icon_url; ?>" alt="App Icon" class="app-icon" id="appIcon">
        
        <div class="offline-icon">
            <i class="fas fa-wifi-slash"></i>
        </div>
        
        <h1 class="offline-title">Você está offline</h1>
        
        <p class="offline-text">
            Parece que você perdeu a conexão com a internet. Algumas funcionalidades podem não estar disponíveis 
            enquanto estiver offline.
        </p>
        
        <button class="btn btn-primary" onclick="window.location.reload()">
            <i class="fas fa-sync-alt me-2"></i> Tentar novamente
        </button>
        
        <div class="recently-visited" id="recentlyVisited" style="display: none;">
            <h3>Páginas visitadas recentemente:</h3>
            <ul class="cached-pages" id="cachedPages">
                <!-- Páginas em cache serão listadas aqui -->
            </ul>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Corrigir ícone se não puder carregar
        const appIcon = document.getElementById('appIcon');
        appIcon.onerror = function() {
            this.src = 'assets/img/icons/icon-192x192.png';
        };
        
        // Verificar se o navegador suporta o Cache Storage API
        if ('caches' in window) {
            caches.open('concamp-v2').then(cache => {
                // Listar as páginas HTML em cache
                cache.keys().then(requests => {
                    const htmlRequests = requests.filter(request => {
                        const url = new URL(request.url);
                        return (url.pathname.endsWith('.php') || url.pathname === '/' || url.search.includes('route=')) && 
                              !url.pathname.includes('/api/') && 
                              !url.search.includes('route=api-');
                    });
                    
                    if (htmlRequests.length > 0) {
                        const recentlyVisited = document.getElementById('recentlyVisited');
                        const cachedPages = document.getElementById('cachedPages');
                        
                        // Mostrar seção de páginas visitadas recentemente
                        recentlyVisited.style.display = 'block';
                        
                        // Adicionar links para páginas em cache
                        htmlRequests.forEach(request => {
                            const url = new URL(request.url);
                            let pageName = url.pathname.split('/').pop() || 'Página Inicial';
                            
                            // Tentar obter nome mais amigável baseado na rota
                            if (url.searchParams.has('route')) {
                                const route = url.searchParams.get('route');
                                switch(route) {
                                    case 'dashboard':
                                        pageName = 'Dashboard';
                                        break;
                                    case 'leads':
                                        pageName = 'Leads';
                                        break;
                                    case 'login':
                                        pageName = 'Login';
                                        break;
                                    default:
                                        pageName = route.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase());
                                }
                            } else if (url.pathname === '/' || url.pathname.endsWith('/index.php')) {
                                pageName = 'Página Inicial';
                            }
                            
                            const li = document.createElement('li');
                            li.innerHTML = `<a href="${request.url}"><i class="fas fa-file me-2"></i>${pageName}</a>`;
                            cachedPages.appendChild(li);
                        });
                    }
                });
            }).catch(error => {
                console.error('Erro ao acessar o cache:', error);
            });
        }
    });
    </script>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>