#!/bin/bash
# Script para limpar o cache do Laravel

# Limpa todos os caches do Laravel
echo "Limpando cache do Laravel..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan key:generate --force
# php artisan config:cache

# Mensagem de conclusão
echo "Cache do Laravel limpo com sucesso!"
