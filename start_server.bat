@echo off
REM Script para limpar o cache e iniciar o servidor Laravel

REM Verifica se foi passada uma porta como parâmetro
set PORTA=%1

REM Limpa todos os caches do Laravel
echo Limpando cache do Laravel...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan key:generate
::php artisan config:cache

REM Inicia o servidor Laravel
if "%PORTA%"=="" (
    echo Iniciando o servidor Laravel na porta padrão 8000...
    php artisan serve
) else (
    echo Iniciando o servidor Laravel na porta %PORTA%...
    php artisan serve --port=%PORTA%
)

::pause
