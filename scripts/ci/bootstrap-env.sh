#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="${GITHUB_WORKSPACE:-$(pwd)}"
cd "$ROOT_DIR"

# -----------------------------
# Helpers
# -----------------------------
append_if_set() {
  local key="$1"
  local val="${!key:-}"

  if [[ -n "${val}" ]]; then
    # remove CR (caso venha de Windows)
    val="${val//$'\r'/}"
    echo "${key}=${val}" >> .env
  fi
}

append_force() {
  local key="$1"
  local val="$2"
  val="${val//$'\r'/}"
  echo "${key}=${val}" >> .env
}

# -----------------------------
# 1) Garante ENCRYPTION_KEY e APP_KEY no ambiente do CI
# -----------------------------
if [[ -z "${ENCRYPTION_KEY:-}" ]]; then
  if command -v php >/dev/null 2>&1; then
    ENCRYPTION_KEY="$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")"
    export ENCRYPTION_KEY
  else
    # fallback (não ideal, mas evita quebrar)
    ENCRYPTION_KEY="base64:$(openssl rand -base64 32 2>/dev/null | tr -d '\n' | sed 's/=*$//')"
    export ENCRYPTION_KEY
  fi
fi

# APP_KEY: se você já usa no CI, ótimo. Se não existir, geramos.
if [[ -z "${APP_KEY:-}" ]]; then
  if command -v php >/dev/null 2>&1; then
    APP_KEY="$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")"
    export APP_KEY
  else
    APP_KEY="base64:$(openssl rand -base64 32 2>/dev/null | tr -d '\n' | sed 's/=*$//')"
    export APP_KEY
  fi
fi

# -----------------------------
# 2) Cria .env "INTEGRALMENTE" (template)
#    - aqui vai o seu .env completo
#    - valores sensíveis ficam como placeholders e serão sobrescritos abaixo
# -----------------------------
cat > .env <<'ENVEOF'
APP_NAME="DEVELOPMENT ERP"
APP_SUFIX_NAME="WEB"
APP_DESC=""
APP_TIMEZONE=America/Bahia
VERSION="3.7"
#TITULO_PLANO="<strong style='color: #69f0ae'>Planos e Preços</strong>"
#MENSAGEM_PLANO="Confira as vantagens de ser <strong style='color: #69f0ae'>Fersoft</strong>"
TITULO_PLANO="<strong style='color: #001f3f'>Soluções e Preços que se Adaptam ao Seu Negócio</strong>"
MENSAGEM_PLANO="
<!-- Configurações Personalizáveis -->
<style>
       /*
  	   =======================================
       CONFIGURAÇÕES DE ESTILO PERSONALIZÁVEIS
       =======================================

       Instruções:
       1. Altere os valores das variáveis abaixo conforme necessário.
       2. Os valores padrão estão especificados em cada descrição.
       3. Para restaurar as configurações padrão, copie e cole os exemplos abaixo:

       Exemplos de valores padrão:
       --box-width: 90%;
       --box-max-width: 1100px;
       --box-padding: 25px;
       --box-border: 1px solid #ddd;
       --box-border-radius: 10px;
       --box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
       --box-bg-color: #f9f9f9;
       --font-size: 14px;
       --title-color: #001f3f;
       --subtitle-color: #ff0000;

       ======================================
       */
    :root {
        --box-width: 90%;
        --box-max-width: 2000px;
        --box-padding: 0px;
        --box-border: 0px solid #ddd;
        --box-border-radius: 10px;
        --box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        --box-bg-color: #f9f9f9;
        --font-size: 10px;
        --title-color: #001f3f;
        --subtitle-color: #ff0000;
    }

    .inner-box {
        flex: 1 1 calc(48% - 20px);
        padding: 15px;
        border: var(--box-border);
        border-radius: var(--box-border-radius);
        box-shadow: var(--box-shadow);
        background-color: var(--box-bg-color);
        font-size: var(--font-size);
        box-sizing: border-box;
        margin: 10px;
    }

    .inner-box h4 {
        text-align: center;
        color: var(--subtitle-color);
    }

    .inner-box p {
        text-align: justify;
    }

    .box-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    @media (max-width: 768px) {
        .inner-box {
            flex: 1 1 100%;
        }
    }
</style>
<br><br>
<div style='display: flex; justify-content: center; align-items: center; height: auto;'>
    <div style='
        width: var(--box-width);
        max-width: var(--box-max-width);
        padding: var(--box-padding);
        border: var(--box-border);
        border-radius: var(--box-border-radius);
        box-shadow: var(--box-shadow);
        background-color: var(--box-bg-color);
        font-size: var(--font-size);
        text-align: justify;'
    >
        <br>
        <h3 style='color: var(--title-color); text-align: center;'>Descubra todas as vantagens de automatizar o seu negócio com nossa solução completa!</h3><br>

        <div class='box-container'>
            <div class='inner-box'>
                <h4>IMPORTANTE:</h4>
                <h4>⚠️💳💰</h4>
                <p>O sistema possui um período de avaliação gratuito. Após esse período, o plano será ativado automaticamente e o sistema será <strong>bloqueado até a confirmação do pagamento</strong>.</p>
            </div>

            <div class='inner-box'>
                <h4>Taxas de Treinamento e Implantação:</h4>
                <h4>⚠️💵💶</h4>
                <p>Cada plano possui <strong>taxas específicas</strong> de treinamento e implantação, que são <strong>cobradas à parte</strong> e <strong>não estão incluídas</strong> no valor do plano selecionado.</p>
            </div>

            <div class='inner-box'>
                <h4>ATENÇÃO:</h4>
                <h4>⚠️📊📈</h4>
                <p>- O sistema inclui <strong>rastreamento com Traccar ou outro sistema similar</strong> como cortesia, sem custos adicionais. <br>- <strong>Não oferecemos garantias, indenizações ou nos responsabilizamos</strong> por informações de rastreamento incorretas.</p>
            </div>

            <div class='inner-box'>
                <h4>Chat de Suporte Integrado via WhatsApp:</h4>
                <h4>📞💬📱</h4>
                <p>- Integração para <strong>atendimento ao cliente pelo WhatsApp</strong>. <br>- Envio de documentos e notas fiscais (NFe e NFCe) diretamente pelos módulos do sistema. <br>- Disponível como <strong>módulo adicional gratuito</strong> (cortesia). <br>- <strong>Solicite a ativação</strong> após cadastro e escolha do plano.</p>
            </div>
        </div>
    </div>
</div>
<br><br>
"

CONTATO_SUPORTE="(00) 00000-0000"
SITE_SUPORTE="portal.development.com.br"
EMAIL_SUPORTE="suporte@development.com.br"

APP_ENV=production
APP_DEBUG=true
APP_URL=http://localhost
PATH_URL=http://127.0.0.1:8000
SERVIDOR_WEB=http://127.0.0.1:8000
PORTAL_URL=https://portal.development.com.br
URL_PESAGEM_TOKEN=https://erp.development.com.br
IDLOG=0000000009

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE="development-db"
DB_USERNAME="root"
DB_PASSWORD=${DB_PASSWORD}

BROADCAST_DRIVER=reverb
CACHE_DRIVER=file
FILESYSTEM_DRIVER=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=240

MAIL_MAILER=smtp
MAIL_HOST=${SMTP_HOST}
MAIL_PORT=${SMTP_PORT}
MAIL_USERNAME=${SMTP_USER}
MAIL_PASSWORD=${SMTP_PASS}
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=${SMTP_FROM}
MAIL_FROM_NAME="Suporte Sistema - ${APP_NAME}"

PUSHER_APP_ID=${PUSHER_APP_ID}
PUSHER_APP_KEY=${PUSHER_APP_KEY}
PUSHER_APP_SECRET=${PUSHER_APP_SECRET}
PUSHER_APP_CLUSTER=mt1

#REVERB
REVERB_SERVER=reverb
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=9000
REVERB_APP=default
REVERB_APP_ID=${REVERB_APP_ID}
REVERB_APP_KEY=${REVERB_APP_KEY}
REVERB_APP_SECRET=${REVERB_APP_SECRET}
REVERB_HOST=127.0.0.1
REVERB_PORT=9000
REVERB_SCHEME=http

#EVOAPI
EVO_DDI=${EVO_DDI}
EVO_DDD=${EVO_DDD}
EVO_BASE_URL=${EVO_BASE_URL}
EVO_GLOBAL_API=${EVO_GLOBAL_API}
EVO_API_VERSION=${EVO_API_VERSION}
EVO_QR_LOGO_BASE64=${EVO_QR_LOGO_BASE64}
EVO_QR_LOGO_SIZE=${EVO_QR_LOGO_SIZE}
EVO_TOKEN_PREFIX=${EVO_TOKEN_PREFIX}
EVO_TOKEN_ALPHABET=${EVO_TOKEN_ALPHABET}
EVO_WHATSAPP_MODAL_HINTBUTTON=${EVO_WHATSAPP_MODAL_HINTBUTTON}
EVO_WHATSAPP_MODAL_TITLE=${EVO_WHATSAPP_MODAL_TITLE}
EVO_WHATSAPP_MODAL_HINTFOOTER=${EVO_WHATSAPP_MODAL_HINTFOOTER}

# UPDATER (mantido)
UPDATER_ENABLED=true
UPDATER_UI_ENABLED=true
UPDATER_UI_PREFIX=_updater
ENVEOF

# -----------------------------
# 3) Overwrites IMPORTANTES (CI)
#    - garante que o Laravel não quebre no package:discover
#    - garante que secrets realmente entrem no .env
# -----------------------------
append_force "APP_KEY" "${APP_KEY}"
append_force "ENCRYPTION_KEY" "${ENCRYPTION_KEY}"

# Se você tiver esses secrets no repo, eles entram no .env:
append_if_set "DB_PASSWORD"

append_if_set "SMTP_HOST"
append_if_set "SMTP_PORT"
append_if_set "SMTP_USER"
append_if_set "SMTP_PASS"
append_if_set "SMTP_FROM"

append_if_set "PUSHER_APP_ID"
append_if_set "PUSHER_APP_KEY"
append_if_set "PUSHER_APP_SECRET"

append_if_set "REVERB_APP_ID"
append_if_set "REVERB_APP_KEY"
append_if_set "REVERB_APP_SECRET"

append_if_set "EVO_DDI"
append_if_set "EVO_DDD"
append_if_set "EVO_BASE_URL"
append_if_set "EVO_GLOBAL_API"
append_if_set "EVO_API_VERSION"
append_if_set "EVO_QR_LOGO_BASE64"
append_if_set "EVO_QR_LOGO_SIZE"
append_if_set "EVO_TOKEN_PREFIX"
append_if_set "EVO_TOKEN_ALPHABET"
append_if_set "EVO_WHATSAPP_MODAL_HINTBUTTON"
append_if_set "EVO_WHATSAPP_MODAL_TITLE"
append_if_set "EVO_WHATSAPP_MODAL_HINTFOOTER"

# >>> Ponto crítico do seu erro:
# No CI, force BROADCAST_DRIVER=log para NÃO tentar construir Pusher/Reverb no package:discover
append_force "BROADCAST_DRIVER" "log"

# E para blindar 100%: se alguém mudar config e cair no pusher mesmo assim, garante dummy:
if [[ -z "${PUSHER_APP_KEY:-}" ]]; then
  append_force "PUSHER_APP_KEY" "ci_dummy"
fi
if [[ -z "${PUSHER_APP_SECRET:-}" ]]; then
  append_force "PUSHER_APP_SECRET" "ci_dummy"
fi
if [[ -z "${PUSHER_APP_ID:-}" ]]; then
  append_force "PUSHER_APP_ID" "1"
fi

# -----------------------------
# 4) Mostra diagnóstico curto
# -----------------------------
echo "bootstrap-env.sh: .env gerado em $(pwd)/.env"
echo "bootstrap-env.sh: ENCRYPTION_KEY OK / APP_KEY OK / BROADCAST_DRIVER=log (CI safe)"
