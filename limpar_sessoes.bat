@echo off
REM Script para limpar sessões do Laravel

echo Limpando sessões do Laravel...

REM Define o caminho para os arquivos de sessão
set SESSION_PATH=storage\framework\sessions

REM Remove todos os arquivos de sessão
del /Q "%SESSION_PATH%\*"

REM Mensagem de conclusão
echo Sessões do Laravel limpas com sucesso!
pause
