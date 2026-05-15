# Cron / Scheduler com detecção automática

## Objetivo

Manter duas formas de executar o Laravel Scheduler sem duplicar processamento:

1. **Cron real do servidor**: forma recomendada.
2. **Cron interno HTTP protegido**: fallback para hospedagens sem cron nativo.

A rota HTTP trabalha em modo `auto`: se detectar que o cron real do servidor executou recentemente, ela não dispara `schedule:run` novamente.

## Forma recomendada em produção

Configure no crontab do servidor:

```bash
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

## Fallback HTTP protegido

URL de execução:

```text
GET /admin/cron-master/{CRON_MASTER_TOKEN}
```

URL de status:

```text
GET /admin/cron-master/status/{CRON_MASTER_TOKEN}
```

Também é possível enviar o token pelo header:

```http
X-Cron-Token: seu-token
```

## Variáveis de ambiente

```env
CRON_HTTP_MODE=auto
CRON_MASTER_TOKEN=uma_chave_forte
CRON_ALLOWED_IPS=
CRON_SERVER_HEARTBEAT_TTL_SECONDS=180
```

### CRON_HTTP_MODE

- `auto`: executa o cron HTTP apenas se o cron do servidor não estiver ativo recentemente.
- `always`: sempre permite execução HTTP protegida por token/IP.
- `disabled`: desativa totalmente a execução HTTP, mantendo apenas o cron real do servidor.

## Como a detecção funciona

Foi criado o comando:

```bash
php artisan cron:heartbeat
```

Ele roda a cada minuto dentro do `app/Console/Kernel.php`.

- Se o Scheduler foi iniciado pelo cron real do servidor, grava origem `server`.
- Se o Scheduler foi iniciado pela rota HTTP protegida, grava origem `internal_http`.

A detecção usa cache Laravel (`CACHE_DRIVER=file` no projeto atual) e considera ativo qualquer heartbeat dentro do TTL configurado em:

```env
CRON_SERVER_HEARTBEAT_TTL_SECONDS=180
```

## Rotas antigas

As rotas públicas antigas foram mantidas comentadas e desabilitadas:

```php
// Desabilitado por Wallace em 15052026
```

- `/cron-master`
- `/teste-dfe`

## Arquivos alterados/adicionados

```text
app/Console/Kernel.php
app/Console/Commands/CronHeartbeatCommand.php
app/Services/Cron/CronMonitor.php
app/Http/Kernel.php
app/Http/Middleware/ProtectCronRoute.php
routes/web.php
.env.example
docs/CRON_AUTO_DETECT_15052026.md
```
