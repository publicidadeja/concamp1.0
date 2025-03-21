# Documentação do Progressive Web App (PWA) - ConCamp

## Introdução

Esta documentação descreve a implementação do Progressive Web App (PWA) no sistema ConCamp. O PWA permite que os usuários instalem o sistema como um aplicativo em seus dispositivos (smartphones, tablets e desktops), oferecendo uma experiência similar a um aplicativo nativo, com acesso offline e melhor desempenho.

## Arquivos Principais

1. **manifest.json** - Arquivo de manifesto que define as propriedades do PWA (nome, cores, ícones, etc.)
2. **service-worker.js** - Script que gerencia o cache e o comportamento offline do aplicativo
3. **offline.php** - Página exibida quando o usuário está sem conexão
4. **assets/js/pwa.js** - Script que gerencia a instalação do PWA e a interface de usuário

## Configurações Personalizáveis

O administrador pode personalizar as seguintes propriedades do PWA através do painel administrativo:

- **Nome do Aplicativo**: Nome completo exibido na tela de instalação
- **Nome Curto**: Nome exibido abaixo do ícone na tela inicial
- **Descrição**: Descrição do aplicativo exibida durante a instalação
- **Cor do Tema**: Cor da barra de navegação/status quando o aplicativo está em execução
- **Cor de Fundo**: Cor de fundo exibida durante o carregamento
- **Ícone do PWA**: Ícone do aplicativo exibido na tela inicial (recomendado: 512x512px)

## Funcionamento

### Registro do Service Worker

O Service Worker é registrado automaticamente quando a página é carregada. Ele gerencia o cache dos arquivos estáticos e intercepta as solicitações de rede, permitindo o funcionamento offline.

### Instalação do PWA

Quando um usuário acessa o sistema pela primeira vez em um dispositivo compatível com PWA, é apresentada a opção de instalar o aplicativo. Esta opção também pode ser acessada através do menu do navegador.

### Cache e Funcionamento Offline

O Service Worker armazena em cache os seguintes recursos:

- Arquivos CSS e JavaScript
- Bibliotecas externas (Bootstrap, Font Awesome)
- Páginas principais do sistema

Quando o usuário está offline, o Service Worker intercepta as solicitações e retorna os recursos em cache. Se um recurso solicitado não estiver em cache, é exibida a página offline.

## Tamanhos de Ícones

O sistema gera automaticamente ícones em vários tamanhos a partir do ícone principal:

- 72x72px
- 96x96px
- 128x128px
- 144x144px
- 152x152px
- 192x192px
- 384x384px
- 512x512px

## Solução de Problemas

### Ícones não aparecem

Verifique se:
1. O diretório `assets/img/icons/` existe e tem permissões de escrita
2. O arquivo de ícone original foi carregado corretamente
3. O processamento de imagem no PHP está funcionando (extensão GD ou Imagick)

### Instalação não é oferecida

Verifique se:
1. O dispositivo/navegador é compatível com PWA
2. O manifesto está sendo carregado corretamente (sem erros HTTP)
3. O site está sendo acessado via HTTPS (obrigatório para PWA)
4. O Service Worker está registrado corretamente

### Offline não funciona

Verifique se:
1. O Service Worker está ativo (devtools > Application > Service Workers)
2. Os recursos estão sendo armazenados em cache (devtools > Application > Cache Storage)
3. O Service Worker está interceptando as solicitações corretamente

## Compatibilidade

O PWA é suportado nos seguintes navegadores:

- Chrome (Android, Windows, macOS, Linux)
- Edge (Windows)
- Firefox (Android)
- Safari (iOS 11.3+, macOS)
- Samsung Internet
- Opera

## Atualização do PWA

Quando novas versões do sistema são lançadas, o Service Worker atualiza automaticamente o aplicativo na próxima vez que o usuário o abrir com conexão à internet.