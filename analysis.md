# Análise do Sistema ConCamp

## Visão Geral

O sistema ConCamp é uma aplicação web desenvolvida em PHP para gerenciar contratos premiados de veículos (carros e motos). Ele possui funcionalidades de CRM, como gestão de leads, follow-ups, tarefas e envio de mensagens via WhatsApp. Além disso, oferece uma ferramenta de simulação de planos e recursos administrativos para gerenciar usuários, planos e configurações.

## Estrutura do Projeto

O sistema é organizado da seguinte forma:

- `/`: Raiz do projeto, contendo o arquivo principal `index.php`.
- `index.php`: Arquivo principal que lida com o roteamento e inclui as páginas e componentes necessários.
- `templates/`: Contém os templates de cabeçalho (`header.php`) e rodapé (`footer.php`).
- `pages/`: Contém as páginas do sistema, divididas em:
    - Páginas públicas: `home.php`, `login.php`, `simulador.php`, etc.
    - Páginas do painel (CRM): `dashboard.php`, `leads.php`, `lead-detail.php`, etc.
    - Páginas de administração: `admin/index.php`, `admin/users.php`, `admin/plans.php`, etc.
    - `404.php`: Página de erro para rotas não encontradas.
    - `access-denied.php`: Página exibida quando o usuário não tem permissão para acessar um recurso.
- `includes/`: Contém arquivos PHP com funções e componentes reutilizáveis:
    - `auth.php`: Funções de autenticação e controle de acesso.
    - `functions.php`: Funções utilitárias diversas, como conexão com o banco de dados, formatação de dados, envio de mensagens, etc.
- `assets/`: Contém arquivos estáticos:
    - `css/`: Estilos CSS (`style.css`, `dashboard.css`).
    - `js/`: Scripts JavaScript (`app.js`, `dashboard.js`).
- `api/`: Contém os endpoints da API para comunicação com o frontend:
    - `clear-cache.php`: Limpa o cache (provavelmente um cache de configurações ou dados).
    - `plans.php`: Lida com requisições relacionadas a planos.
    - `test-whatsapp.php`, `test-whatsapp-media.php`: Testam o envio de mensagens via WhatsApp.
    - `task/`: Endpoints relacionados a tarefas.
    - `lead/`: Endpoints relacionados a leads.
- `actions/`: Contém scripts PHP que executam ações específicas:
    - `process-simulation.php`: Processa os dados do formulário de simulação.
- `install/`: Contém o script de instalação do sistema (`index.php`).
- `config/`: (Não presente inicialmente, mas criado pelo instalador) Contém o arquivo de configuração `config.php` com as credenciais do banco de dados e outras configurações.

## Componentes Principais

### Roteamento (`index.php`)

O sistema utiliza um roteamento básico baseado no parâmetro `route` da URL (`$_GET['route']`). Ele verifica se a rota é pública ou protegida e redireciona o usuário para a página de login se necessário. Em seguida, inclui o arquivo PHP correspondente à rota solicitada.

### Autenticação (`includes/auth.php`)

- Gerencia o login e logout dos usuários.
- Verifica se o usuário está logado (`isLoggedIn()`).
- Obtém o ID e os dados do usuário logado (`getCurrentUserId()`, `getCurrentUser()`).
- Verifica permissões de acesso (`isAdmin()`, `isManager()`, `hasPermission()`).
- Cria e atualiza usuários.
- Gerencia a recuperação de senha.

### Funções Utilitárias (`includes/functions.php`)

- Conexão com o banco de dados (`getConnection()`).
- Formatação de dados (moeda, telefone, data/hora).
- Geração de URLs (`url()`).
- Redirecionamento (`redirect()`).
- Sanitização de inputs (`sanitize()`).
- Obtenção e atualização de configurações (`getSetting()`, `updateSetting()`).
- Envio de mensagens via WhatsApp (`sendWhatsAppMessage()`).
- Gerenciamento de planos (obtenção, cálculo de valores).
- Gerenciamento de leads (salvar, obter, atualizar status, adicionar follow-ups).
- Gerenciamento de mensagens (registrar, processar templates).
- Gerenciamento de tarefas (obter, marcar como concluída).
- Obtenção de estatísticas para o dashboard.
- Geração de relatórios.
- Criação e verificação de tokens CSRF.
- Validação de arquivos (imagem, PDF).
- Upload de arquivos.

### Páginas

- **Páginas Públicas:**
    - `home.php`: Página inicial com informações sobre o Contrato Premiado, simulação, depoimentos e FAQ.
    - `login.php`: Formulário de login.
    - `simulador.php`: Formulário de simulação de planos.
    - Outras páginas (registro, recuperação de senha, etc.).
- **Páginas do Painel (CRM):**
    - `dashboard.php`: Painel com estatísticas e informações relevantes para o usuário.
    - `leads.php`: Listagem de leads com filtros e paginação.
    - `lead-detail.php`: Detalhes de um lead específico, com histórico de follow-ups e mensagens.
    - `tasks.php`: (Não implementado no código fornecido) Gerenciamento de tarefas.
    - `messages.php`: (Não implementado no código fornecido) Gerenciamento de mensagens.
- **Páginas de Administração:**
    - `admin/index.php`: Painel de administração.
    - `admin/users.php`: Gerenciamento de usuários.
    - `admin/plans.php`: Gerenciamento de planos.
    - `admin/settings.php`: Gerenciamento de configurações.
    - `admin/integrations.php`: (Não implementado no código fornecido) Gerenciamento de integrações.
    - `admin/messages.php`: (Não implementado no código fornecido) Gerenciamento de modelos de mensagens.
    - `admin/reports.php`: Geração de relatórios.

### API

- `clear-cache.php`: Limpa o cache (provavelmente um cache de configurações ou dados).
- `plans.php`: Lida com requisições relacionadas a planos (obter planos, termos disponíveis, etc.).
- `test-whatsapp.php`, `test-whatsapp-media.php`: Testam o envio de mensagens via WhatsApp (texto e mídia).
- `task/complete.php`: Marca uma tarefa como concluída.
- `lead/add-followup.php`: Adiciona um follow-up a um lead.
- `lead/assign-seller.php`: Atribui um vendedor a um lead.
- `lead/update-status.php`: Atualiza o status de um lead.

### Instalação (`install/index.php`)

- Verifica se o sistema já está instalado.
- Coleta informações do usuário (credenciais do banco de dados, dados do administrador, token do WhatsApp).
- Valida os dados do formulário.
- Conecta ao banco de dados.
- Cria o banco de dados (se não existir).
- Cria as tabelas necessárias:
    - `users`: Armazena os dados dos usuários.
    - `plans`: Armazena os planos disponíveis.
    - `leads`: Armazena os leads gerados.
    - `follow_ups`: Armazena os follow-ups e tarefas dos leads.
    - `message_templates`: Armazena os modelos de mensagens.
    - `sent_messages`: Armazena o histórico de mensagens enviadas.
    - `settings`: Armazena as configurações do sistema.
- Insere dados iniciais (configurações padrão, planos pré-configurados, modelo de mensagem padrão).
- Cria o arquivo de configuração `config/config.php`.

## Dependências

- **PHP:** Linguagem de programação principal.
- **MySQL:** Banco de dados.
- **Extensões PHP:**
    - `pdo_mysql`: Para conexão com o banco de dados MySQL.
    - `curl`: Para comunicação com a API do WhatsApp.
    - `fileinfo`: Para validação de tipos de arquivos.
- **Bibliotecas JavaScript:**
    - `Bootstrap`: Framework CSS para estilização.
    - Outras bibliotecas podem ser usadas nos arquivos JavaScript (`app.js`, `dashboard.js`), mas não foram detalhadas no código fornecido.

## Fluxo de Funcionamento (Exemplo: Simulação)

1. O usuário acessa a página `index.php?route=simulador`.
2. O arquivo `index.php` inclui o cabeçalho (`templates/header.php`).
3. O arquivo `pages/simulador.php` é incluído, exibindo o formulário de simulação.
4. O usuário preenche o formulário e clica em "Simular".
5. Os dados são enviados para `index.php?route=process-simulation`.
6. O arquivo `actions/process-simulation.php` é incluído:
    - Os dados são sanitizados.
    - O plano selecionado é obtido do banco de dados.
    - Os cálculos de valores são realizados.
    - Os dados do lead são salvos no banco de dados.
    - Uma mensagem de WhatsApp é enviada para o cliente (opcional).
    - Os dados da simulação são armazenados na sessão.
    - O usuário é redirecionado para a página inicial (`index.php?route=home&simulation_success=true`).
7. A página inicial exibe uma mensagem de sucesso e os detalhes da simulação.

## Próximos Passos

Para executar o sistema completamente, é necessário:

1. **Configurar um ambiente PHP:** Instalar o PHP e as extensões necessárias (`pdo_mysql`, `curl`, `fileinfo`).
2. **Configurar um servidor MySQL:** Criar um banco de dados e um usuário com as credenciais apropriadas.
3. **Executar o script de instalação:** Acessar `install/index.php` em um navegador e preencher o formulário de instalação.
4. **Configurar a API do WhatsApp:** Obter um token válido e configurar as credenciais no sistema.
5. **Testar todas as funcionalidades:** Verificar se o roteamento, autenticação, simulação, CRM e recursos administrativos estão funcionando corretamente.

Análise concluída.
