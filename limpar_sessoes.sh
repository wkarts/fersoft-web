#!/bin/bash
# Script para limpar sessões do Laravel

echo "Limpando sessões do Laravel..."

# Define o caminho para os arquivos de sessão
SESSION_PATH="storage/framework/sessions"

# Remove todos os arquivos de sessão
find "$SESSION_PATH" -type f -exec rm -f {} \;

# Mensagem de conclusão
echo "Sessões do Laravel limpas com sucesso!"
