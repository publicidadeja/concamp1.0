#!/bin/bash
# Script para configurar o cron job para verificação de tarefas

# Caminho absoluto para o diretório do projeto
PROJECT_DIR="$(readlink -f "$(dirname "$0")")"

# Caminho para o script PHP
SCRIPT_PATH="$PROJECT_DIR/task_notifications.php"

# Caminho para o arquivo de log
LOG_PATH="$PROJECT_DIR/logs/task_notifications.log"

# Criar diretório de logs se não existir
mkdir -p "$PROJECT_DIR/logs"

# Verificar se o PHP está instalado
if ! command -v php &> /dev/null; then
    echo "Erro: PHP CLI não está instalado."
    exit 1
fi

# Criar o comando cron
CRON_CMD="0 8 * * * php $SCRIPT_PATH >> $LOG_PATH 2>&1"

# Adicionar ao crontab se não existir
(crontab -l 2>/dev/null | grep -v "task_notifications.php" ; echo "$CRON_CMD") | crontab -

echo "Cron job configurado para executar diariamente às 8:00 AM."
echo "Você pode verificar o funcionamento executando o script manualmente:"
echo "php $SCRIPT_PATH"
echo ""
echo "Logs serão gravados em:"
echo "$LOG_PATH"