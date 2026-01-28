@echo off
REM Script para limpar o cache do Laravel

REM Limpa todos os caches do Laravel
echo Limpando cache do Laravel...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan key:generate
REM php artisan config:cache

REM Mensagem de conclusão
echo Cache do Laravel limpo com sucesso!
pause
