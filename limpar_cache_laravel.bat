@echo off
REM Script seguro para limpar o cache do Laravel
REM
REM IMPORTANTE:
REM Limpar cache NÃO deve regenerar APP_KEY.
REM A APP_KEY é chave estrutural da aplicação e não faz parte do cache.
REM Se realmente precisar trocar a APP_KEY, execute:
REM     limpar_cache_laravel.bat --regen-key
REM
REM A integração ADP foi ajustada para não depender da APP_KEY no token global,
REM mas trocar APP_KEY ainda pode invalidar cookies, sessões e dados criptografados
REM nativos do Laravel em outros pontos do sistema.

echo Limpando cache do Laravel...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

if /I "%1"=="--regen-key" (
    echo ATENCAO: regenerando APP_KEY por solicitacao explicita...
    php artisan key:generate --force
) else (
    echo APP_KEY preservada. Use --regen-key somente quando realmente desejar trocar a chave da aplicacao.
)

REM php artisan config:cache

echo Cache do Laravel limpo com sucesso!
pause
