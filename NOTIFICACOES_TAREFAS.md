# Sistema de Notificações de Tarefas - ConCamp

Este documento explica como configurar e usar o sistema de notificações automáticas para tarefas no ConCamp.

## Visão Geral

O sistema de notificações de tarefas é responsável por alertar os usuários sobre:
- Tarefas vencidas (atrasadas)
- Tarefas que vencem hoje
- Tarefas que vencem amanhã

Quando uma dessas condições é atendida, o sistema gera notificações automáticas que aparecem no sininho de notificações no topo da página.

## Configuração

Para configurar o sistema de notificações de tarefas, siga os passos abaixo:

### 1. Executar a Página de Configuração

Acesse a página de configuração através do menu Admin > Configurar Notificações de Tarefas, ou pelo link direto:

```
index.php?route=create-task-notification-field
```

Esta página irá adicionar um campo `notified` à tabela `follow_ups` que é necessário para o funcionamento do sistema.

### 2. Configurar o Cron Job

Para que as notificações sejam geradas automaticamente, é necessário configurar um cron job no servidor que execute o script `task_notifications.php` diariamente.

**Via SSH (Linux/Unix):**

Execute o script de configuração fornecido:

```
bash setup_task_cron.sh
```

**Configuração Manual:**

Adicione a seguinte linha ao crontab (executando `crontab -e`):

```
0 8 * * * php /caminho/para/concamp/task_notifications.php >> /caminho/para/concamp/logs/task_notifications.log 2>&1
```

Isso configura o sistema para verificar tarefas pendentes todos os dias às 8:00 da manhã.

## Como Funciona

- O script verifica diariamente todas as tarefas pendentes (follow_ups do tipo "task")
- Para cada tarefa que está vencida, vence hoje ou vence amanhã, uma notificação é gerada
- Cada tarefa só gera uma notificação (usando o campo "notified" para controle)
- Quando uma tarefa é concluída, o campo "notified" é resetado, permitindo novas notificações se a tarefa for reaberta

## Verificação Manual

Para verificar manualmente se há tarefas que deveriam gerar notificações:

1. Acesse a página de configuração de notificações
2. Clique no botão "Executar Verificação de Tarefas"

Ou execute diretamente o script:

```
php /caminho/para/concamp/task_notifications.php
```

## Problemas Comuns

**Notificações não aparecem:**
- Verifique se o cron job está configurado corretamente
- Verifique os logs em `/caminho/para/concamp/logs/task_notifications.log`
- Execute manualmente o script para ver erros

**Tarefas não estão gerando notificações:**
- Certifique-se de que as tarefas têm uma data de vencimento definida
- Verifique se o campo "notified" existe na tabela follow_ups

## Log de Atividades

O sistema mantém logs das notificações geradas no diretório:

```
/caminho/para/concamp/logs/task_notifications.log
```

Monitore este arquivo para entender o funcionamento do sistema e detectar eventuais problemas.