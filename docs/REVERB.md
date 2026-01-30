# Reverb (Laravel 10) - Instalação e Configuração

## 1) Instalação
```bash
composer require laravel/reverb
```

## 2) Publicar configs (se necessário)
```bash
php artisan vendor:publish --tag=reverb-config
```

## 3) Configuração do .env (exatamente conforme padrão do projeto)
```
REVERB_SERVER_PORT=8082
REVERB_APP=default
REVERB_APP_ID=564819
REVERB_APP_KEY=olqxgocr9wuhsp2kulzu
REVERB_APP_SECRET=dxhge07ntsfp9lsdyoqc
REVERB_HOST="localhost"
REVERB_PORT=8082
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## 4) Rodar o servidor Reverb
```bash
php artisan reverb:start --host=0.0.0.0 --port=8082 --hostname="localhost"
```

## 5) Rodar o frontend
```bash
npm install
npm run dev
```

---

## 🔧 Correção do erro "Target class [hash] does not exist"
Esse erro normalmente ocorre por:
- cache corrompido em `bootstrap/cache/*.php`
- providers essenciais não registrados
- config cache antigo

### Passo a passo (OBRIGATÓRIO):
```bash
php artisan optimize:clear
rm -f bootstrap/cache/*.php
php artisan config:clear
php artisan cache:clear
composer dump-autoload
```

### Confirme no `config/app.php`:
- `Illuminate\Hashing\HashServiceProvider::class`
- `Laravel\Reverb\ReverbServiceProvider::class`
- `App\Providers\BroadcastServiceProvider::class`

---

## ✅ Checklist Rápido
- [ ] `php artisan reverb:start` roda sem erro
- [ ] Porta = 8082 (ou a definida no .env)
- [ ] WebSocket conecta sem erro no console
- [ ] `BROADCAST_DRIVER=reverb`
- [ ] `config:clear` após alterar .env
