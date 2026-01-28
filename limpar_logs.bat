@echo off
REM Script para limpar logs do Laravel

echo Limpando logs do Laravel...

REM Define o caminho para os arquivos de log
set LOG_PATH=storage\logs

REM Remove todos os arquivos de log
del /Q "%LOG_PATH%\*.log"

REM Mensagem de conclusão
echo Logs do Laravel limpos com sucesso!
pause
