#!/bin/bash
# Script para limpar logs do Laravel

echo "Limpando logs do Laravel..."

# Define o caminho para os arquivos de log
LOG_PATH="storage/logs"

# Remove todos os arquivos de log
find "$LOG_PATH" -type f -name "*.log" -exec rm -f {} \;

# Mensagem de conclusão
echo "Logs do Laravel limpos com sucesso!"
