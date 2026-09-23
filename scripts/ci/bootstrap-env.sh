#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="${GITHUB_WORKSPACE:-$(pwd)}"
cd "$ROOT_DIR"

# -----------------------------
# Helpers (sem refatoração abrupta)
# -----------------------------
gen_base64_32() {
  if command -v php >/dev/null 2>&1; then
    php -r "echo 'base64:'.base64_encode(random_bytes(32));"
    return 0
  fi

  if command -v openssl >/dev/null 2>&1; then
    echo "base64:$(openssl rand -base64 32 | tr -d '\n')"
    return 0
  fi

  # fallback (último recurso)
  echo "base64:$(date +%s | sha256sum | awk '{print $1}' | head -c 43)"
}

upsert_env() {
  local key="$1"
  local val="$2"

  # remove CR
  val="${val//$'\r'/}"

  # remove linhas antigas do mesmo key e escreve de novo no final
  if [ -f .env ]; then
    # shellcheck disable=SC2002
    cat .env | grep -vE "^${key}=" > .env.__tmp || true
    mv .env.__tmp .env
  fi

  echo "${key}=${val}" >> .env
}

# -----------------------------
# 1) Garante chaves (secrets -> se vazio, gera)
# -----------------------------
if [[ -z "${APP_KEY:-}" ]]; then
  APP_KEY="$(gen_base64_32)"
  export APP_KEY
fi

if [[ -z "${ENCRYPTION_KEY:-}" ]]; then
  ENCRYPTION_KEY="$(gen_base64_32)"
  export ENCRYPTION_KEY
fi

# -----------------------------
# 2) Escreve o .env INTEGRAL (como você pediu)
#    OBS IMPORTANTE:
#    - NÃO deixamos APP_KEY/ENCRYPTION_KEY vazios aqui, para evitar duplicidade.
#    - Duplicidade no .env pode "prender" o primeiro valor (vazio) durante o bootstrap do Laravel.
# -----------------------------
cat > .env <<ENVEOF
APP_NAME="DEVELOPMENT"
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
       --box-width: 90%;                            Proporção do tamanho da box (Ex: 50%, 80%, 90%)
       --box-max-width: 1100px;                      Largura máxima da box (Ex: 800px, 1100px)
       --box-padding: 25px;                         Espaçamento interno da box (Ex: 25px)
       --box-border: 1px solid #ddd;                Borda (Ex: '1px solid #ddd', 'none' para ocultar)
       --box-border-radius: 10px;                   Arredondamento das bordas (Ex: 10px)
       --box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);  Sombra (Ex: 'none' para remover)
       --box-bg-color: #f9f9f9;                     Cor de fundo (Ex: '#f9f9f9' ou 'transparent')
       --font-size: 14px;                           Tamanho da fonte (Ex: 14px)
       --title-color: #001f3f;                      Cor dos títulos (Ex: Azul Marinho '#001f3f')
       --subtitle-color: #ff0000;                   Cor dos subtítulos (Ex: Vermelho '#ff0000')

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

    /* Estilo das Boxes Individuais */
    .inner-box {
        flex: 1 1 calc(48% - 20px); /* Ocupa 48% da largura com espaçamento */
        padding: 15px;
        border: var(--box-border);
        border-radius: var(--box-border-radius);
        box-shadow: var(--box-shadow);
        background-color: var(--box-bg-color);
        font-size: var(--font-size);
        box-sizing: border-box;
        margin: 10px; /* Espaçamento uniforme */
    }

    .inner-box h4 {
        text-align: center;
        color: var(--subtitle-color);
    }

    .inner-box p {
        text-align: justify;
    }

    /* Container Principal */
    .box-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between; /* Garante que fiquem lado a lado */
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .inner-box {
            flex: 1 1 100%; /* Ocupa 100% da largura em telas menores */
        }
    }
</style>
<br><br>
<!-- Box Configurável -->
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
        <!-- Título Principal -->
        <br>
        <h3 style='color: var(--title-color); text-align: center;'>Descubra todas as vantagens de automatizar o seu negócio com nossa solução completa!</h3><br>

        <!-- Boxes Individuais -->
        <div class='box-container'>
            <!-- IMPORTANTE -->
            <div class='inner-box'>
                <h4>IMPORTANTE:</h4>
                <h4>⚠️💳💰</h4>
                <p>O sistema possui um período de avaliação gratuito. Após esse período, o plano será ativado automaticamente e o sistema será <strong>bloqueado até a confirmação do pagamento</strong>.</p>
            </div>

            <!-- Taxas de Treinamento e Implantação -->
            <div class='inner-box'>
                <h4>Taxas de Treinamento e Implantação:</h4>
                <h4>⚠️💵💶</h4>
                <p>Cada plano possui <strong>taxas específicas</strong> de treinamento e implantação, que são <strong>cobradas à parte</strong> e <strong>não estão incluídas</strong> no valor do plano selecionado.</p>
            </div>

            <!-- ATENÇÃO -->
            <div class='inner-box'>
                <h4>ATENÇÃO:</h4>
                <h4>⚠️📊📈</h4>
                <p>- O sistema inclui <strong>rastreamento com Traccar ou outro sistema similar</strong> como cortesia, sem custos adicionais. <br>- <strong>Não oferecemos garantias, indenizações ou nos responsabilizamos</strong> por informações de rastreamento incorretas.</p>
            </div>

            <!-- Chat de Suporte -->
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

CONTATO_SUPORTE="(75) 98844-9231"
SITE_SUPORTE="portal.development.com.br"
EMAIL_SUPORTE="suporte@development.com.br"
# Ambiente de demonstração/produção
APP_ENV=production # local para rodar app ou demo para demonstracao de login
# Logins de demonstração
DEMO_SUPER_USER=super
DEMO_SUPER_PASS=12345
DEMO_ADMIN_USER=admin
DEMO_ADMIN_PASS=12345

APP_KEY=${APP_KEY}
ENCRYPTION_KEY=${ENCRYPTION_KEY}

APP_DEBUG=true
APP_URL=http://localhost
PATH_URL=http://127.0.0.1:8000 # URL Path do sistema
SERVIDOR_WEB=http://127.0.0.1:8000
PORTAL_URL=https://portal.development.com.br
URL_PESAGEM_TOKEN=https://erp.development.com.br
IDLOG=0000000009

LOG_CHANNEL=stack
LOG_LEVEL=debug

USERMASTER="wallace,werika" # master do app
SENHA_MASTER="123456"
OTP_LOGIN_ENABLED=true
CONSULTAS_MANIFESTO_DIA=100 # maximo de consulta documentos manifesto dia
PLANO_AUTOMATICO_NOME=""    # Para atribui plano automatico auto cadastro
PLANO_AUTOMATICO_DIAS=1
PLANO_PAGAMENTO_DIAS=3
ALERTA_PAGAMENTO_DIAS=4
PRODUTO_GERENCIAR_ESTOQUE=0
DIAS_ASSINAR_CONTRATO=30
CONTRATO_CERTIFICADO=1

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

BROADCAST_DRIVER=reverb
CACHE_DRIVER=file
FILESYSTEM_DRIVER=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=240

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mail.development.com.br
MAIL_PORT=465
MAIL_NAME=SYS_FAST
MAIL_USERNAME="suporte@development.com.br"
MAIL_PASSWORD="YrKzzqo.VPVN"
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="suporte@development.com.br"
MAIL_FROM_NAME="Suporte Sistema - \${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

MIX_PUSHER_APP_KEY="\${PUSHER_APP_KEY}"
MIX_PUSHER_APP_CLUSTER="\${PUSHER_APP_CLUSTER}"

##############

#REPSONSAVEL TECNICO NFE/NFCE

RESP_CNPJ=36518327000105
RESP_NOME=WWSOFTWARE'S
RESP_EMAIL=suporte@development.com.br
RESP_FONE=75988449231

##############

SMS_NOME_EMPRESA=WWSOFTWARES_DELIVERY # NOME COM

#################

#MODULOS DO SISTEMA: 1 - ativa, 0 - desativa
SERVIDOR_WEB_TYPE=1			# Se o sistema estiver em localhost matenha 0
DEVOLUCAO_ALTERA_ESTOQUE=1  # Se ativado reduz o estoque em nota de devolução
ROTA_INICIAL="graficos" 	# Rota de redirecionamento após o login no sistema
ASSINCRONO_PRODUTOS=0 		# variavel para condicional pdv, pedido e compra assincrono

QRCODE_MAPS=1 #Habilita o QrCode de leitura do entregador para ver a rota, tela de visaizacao do pedido
CIDADE_MAPS="Santo Antônio de Jesus"
PEDIDO_LOCAL=1 # Ativa o módulo de controle de mesas

MESA_ATIVA_QRCODE=0 	# se 1 ativa automatico a mesa para pedir, se 0 caixa deve confirmar mesa para pedir
CLIENTES_MESA_QRCODE=1  # Maximo de clientes que podem usar o pedido por qrcode simultaneo
TAMANHO_QRCODE=8

MDFE=1 		# Ativa módulo MDF-e
CTE=1 		# Ativa módulo CT-e
OS=1 		# Ativa módulo Ordem de serviço
COTACAO=1 	# Ativa módulo cotação
CATRACA=1 	# Ativa módulo catraca

DIVISAO_VALOR_PIZZA=1 # se ativado divide o valor da pizza ao meio, se desativado mantém o valor da maior

AUTENTICACAO_SMS=0 	 # Autenticação de cliente de delivery por SMS
AUTENTICACAO_EMAIL=0 # Autenticação de cliente de delivery por Email
EVENTO=1
ALERTA_CONTAS_DIAS=5 # Alerta para contas a pagar e receber

##################

PDV_VALOR_RECEBIDO=1
KEY_APP=laravel
DELIVERY=1
IFOOD=1
ATENDIMENTO=1
ANIMACAO=1
ECOMMERCE=1
LOCACAO=1
EVENTO=1
LOGOCLIENTE=0

CAIXA_PARA_NFE=1
MIGRADOR=1
CEP_PRODUTO_ECOMMERCE=1

MERCADOPAGO_PUBLIC_KEY=
MERCADOPAGO_ACCESS_TOKEN=
MERCADOPAGO_PUBLIC_KEY_PRODUCAO=
MERCADOPAGO_ACCESS_TOKEN_PRODUCAO=
MERCADOPAGO_AMBIENTE=production

NFCE_SINCRONO=1
CERTIFICADO_ARQUIVO=1
VIDEO_AJUDA=1
MINUTOS_ONLINE=5
AVISO_EMAIL_NOVO_CADASTRO="wallace.almeida@development.com.br"
AVISO_EMAIL_NOVO_CADASTRO_PARCEIRO="wallace.almeida@development.com.br"
ALERTA_VENCIMENTO_CERTIFICADO=20

LINK_DAS="http://www8.receita.fazenda.gov.br/SimplesNacional/Aplicacoes/ATSPO/pgmei.app/Identificacao"
LINK_FAT="http://www8.receita.fazenda.gov.br/SimplesNacional/Aplicacoes/ATSPO/dasnsimei.app/Default.aspx"

PAG_LOGIN="access_2"
#access, access_2, access_3
HERDAR_DADOS_SUPER=0

CLIENT_ID_NUVEMSHOP=
CLIENT_SECRET_NUVEMSHOP=
EMAIL_NUVEMSHOP=

SERIALNUMBER=""
APPVERSION="metronic"

#WHATSAPP API ENVIOS
TOKEN_PREFIX=WWSOFTWARES
API_WHATSAPP_ATENDIMENTO=https://api.support.development.com.br/api/messages/send
WHATSAPP_ATENDIMENTO=https://support.development.com.br
FORCE_PUBLIC_PATH=true #PARA ENVIO DE ANEXO CONSIDERANDO PUBLIC PATH true|false

# Configuração do rodapé DANFE
DANFE_RODAPE_ESQUERDA="Impresso em {DATA_HORA}"
DANFE_RODAPE_DIREITA="Powered by WWSoftware's®"
DANFE_RODAPE_SITE="https://development.com.br/"
MENSAGEM_EVENTO_DINAMICO_110110="Este documento é uma representação gráfica da CC-e e foi impresso apenas para sua informação e não possui validade fiscal.\nA CC-e deve ser recebida e mantida em arquivo eletrônico XML e pode ser consultada através dos Portais das SEFAZ."
MENSAGEM_EVENTO_DINAMICO_110111="Este documento é uma representação gráfica do evento de NFe e foi impresso apenas para sua informação e não possui validade fiscal.\nO Evento deve ser recebido e mantido em arquivo eletrônico XML e pode ser consultado através dos Portais das SEFAZ."

RECAPTCHA_SITE_KEY=6LdCwT8iAAAAAHyQr-PCciOhzh7GKlyXvxzDOwi0
RECAPTCHA_SECRET_KEY=6LdCwT8iAAAAACjLK9xHb5EcmghuGhVmpyHCdklE

GA_MEASUREMENT_ID=G-7NKDENVQJ6
HOTJAR_ID=5050930

POSTHOG_KEY=phc_lqgGHrGOJRIxTPnRCob0BNqYGrUHImRQjjNKXDpRbFk
POSTHOG_HOST=https://us.i.posthog.com
POSTHOG_AUTOCAPTURE=true
POSTHOG_CAPTURE_PAGEVIEW=true
POSTHOG_DEBUG=false

POSTHOG_SESSION_RECORDING_ENABLED=true
POSTHOG_SR_MASK_ALL_INPUTS=true
POSTHOG_SR_CAPTURE_CANVAS=false

POSTHOG_IDENTIFY_ENABLED=true

HUB_BASE_URL=https://hubsaas.development.com.br
HUB_TOKEN=EsJkZ4nje1nxk9qzhu3wa6B8

FOOTER_COMPANY_NAME=WWSoftware's
FOOTER_COMPANY_URL=https://portal.development.com.br

APP_TEST_URL=https://teste.development.com.br/
APP_TEST_PATH=https://teste.development.com.br
IBPT_URL=https://ibpt.development.com.br/tabela/ibpt/

LOJA_MODELO_ECOMMERCE="Minha Loja"
LOJA_MODELO_ECOMMERCE_LINK="MinhaLoja"

# EMPRESA PADRÃO
EMPRESA_NOME="DEVELOPMENT"
EMPRESA_RUA="DEVELOPMENT ADDRESS"
EMPRESA_NUMERO="SN"
EMPRESA_BAIRRO="CENTER"
EMPRESA_CIDADE="CITY"
EMPRESA_EMAIL="contato@development.com.br"
EMPRESA_TELEFONE="00000000000"
EMPRESA_CNPJ="00.000.000/0000-00"
EMPRESA_PERMISSAO=""

# USUÁRIO PADRÃO
USUARIO_NOME="DEVELOPMENT USER"
USUARIO_LOGIN="development"
USUARIO_SENHA_HASH="202cb962ac59075b964b07152d234b70"
USUARIO_EMAIL=""

# Connect|API
CONNECT_API_BASE_URL=""
CONNECT_API_BOOTSTRAP_KEY=""
CONNECT_API_TIMEOUT=30
CONNECT_API_CONNECT_TIMEOUT=10
CONNECT_API_DDI=55
CONNECT_API_DDD=75
CONNECT_API_INSTANCE_PREFIX=""
CONNECT_API_WHATSAPP_MODAL_HINTBUTTON="Enviar WhatsApp"
CONNECT_API_WHATSAPP_MODAL_TITLE="Enviar WhatsApp"
CONNECT_API_WHATSAPP_SEND_BUTTON_TEXT="Enviar"
CONNECT_API_WHATSAPP_MODAL_HINTFOOTER="Envio realizado pela Connect|API configurada para esta empresa."

#SOCKET NATIVO
REVERB_SERVER=reverb
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=9000
REVERB_APP=default
REVERB_APP_ID=564819
REVERB_APP_KEY=olqxgocr9wuhsp2kulzu
REVERB_APP_SECRET=dxhge07ntsfp9lsdyoqc
REVERB_HOST=127.0.0.1
REVERB_PORT=9000
REVERB_SCHEME=http

VITE_APP_NAME="\${APP_NAME}"
VITE_PUSHER_APP_KEY="\${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="\${PUSHER_HOST}"
VITE_PUSHER_PORT="\${PUSHER_PORT}"
VITE_PUSHER_SCHEME="\${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="\${PUSHER_APP_CLUSTER}"
VITE_REVERB_APP_KEY="\${REVERB_APP_KEY}"
VITE_REVERB_HOST="\${REVERB_HOST}"
VITE_REVERB_PORT="\${REVERB_PORT}"
VITE_REVERB_SCHEME="\${REVERB_SCHEME}"

#ATUALIZADOR DO SISTEMA
UPDATER_ENABLED=true
UPDATER_UI_ENABLED=true
UPDATER_UI_PREFIX=_updater

UPDATER_UI_AUTH_ENABLED=true
UPDATER_UI_AUTO_PROVISION_ADMIN=true
UPDATER_UI_DEFAULT_EMAIL=admin@admin.com
UPDATER_UI_DEFAULT_PASSWORD=123456
UPDATER_UI_SESSION_TTL=120
UPDATER_UI_LOGIN_MAX_ATTEMPTS=10
UPDATER_UI_LOGIN_DECAY_MINUTES=10

UPDATER_UI_2FA_ENABLED=true
UPDATER_UI_2FA_REQUIRED=false
UPDATER_UI_2FA_ISSUER="Fersoft Updater"

UPDATER_UI_FORCE_SYNC=true

UPDATER_APP_NAME="Updater Fersoft"
UPDATER_APP_SUFIX_NAME="Enterprise"
UPDATER_APP_DESC="Gerenciador de Atualizações"

UPDATER_NOTIFY_ENABLED=true
UPDATER_NOTIFY_TO=nome.sobrenome@development.com.br
UPDATER_GIT_PATH=/home/development-web/htdocs/web.development.com.br
UPDATER_GIT_REMOTE=origin
UPDATER_GIT_BRANCH=main
UPDATER_GIT_FF_ONLY=flase
UPDATER_GIT_AUTO_INIT=true
UPDATER_GIT_REMOTE_URL=https://github.com/development/development-web.git
UPDATER_GIT_DEFAULT_UPDATE_MODE=merge
UPDATER_SOURCES_ALLOW_MULTIPLE=false

UPDATER_MAINTENANCE_RENDER_VIEW=laravel-updater::maintenance
UPDATER_MAINTENANCE_USE_RENDER=true
UPDATER_MAINTENANCE_FALLBACK_NO_RENDER=true
ENVEOF

# -----------------------------
# 3) Overrides seguros pro CI (evita dependências externas no package:discover)
# -----------------------------
upsert_env "APP_ENV" "testing"
upsert_env "APP_DEBUG" "true"
upsert_env "APP_URL" "http://localhost"

# Laravel 11/12 pode usar BROADCAST_CONNECTION; teu .env usa BROADCAST_DRIVER.
# Defino os dois, sem quebrar teu padrão.
upsert_env "BROADCAST_CONNECTION" "log"
upsert_env "BROADCAST_DRIVER" "log"

# Evita dependências externas no CI
upsert_env "CACHE_DRIVER" "array"
upsert_env "SESSION_DRIVER" "array"
upsert_env "QUEUE_CONNECTION" "sync"

# Banco do CI.
# Quando o workflow fornece MySQL 8, preserva essa configuração.
# Sem configuração externa, mantém o fallback SQLite usado no desenvolvimento/CI legado.
if [[ "${DB_CONNECTION:-sqlite}" == "mysql" ]]; then
  upsert_env "DB_CONNECTION" "mysql"
  upsert_env "DB_HOST" "${DB_HOST:-127.0.0.1}"
  upsert_env "DB_PORT" "${DB_PORT:-3306}"
  upsert_env "DB_DATABASE" "${DB_DATABASE:-fersoft_test}"
  upsert_env "DB_USERNAME" "${DB_USERNAME:-root}"
  upsert_env "DB_PASSWORD" "${DB_PASSWORD:-root}"
else
  mkdir -p database
  if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
  fi
  upsert_env "DB_CONNECTION" "sqlite"
  upsert_env "DB_DATABASE" "database/database.sqlite"
fi

# -----------------------------
# 4) Se existirem secrets importantes, sobrescreve (sem exigir template)
# -----------------------------
# SMTP
if [[ -n "${SMTP_HOST:-}" ]]; then upsert_env "MAIL_HOST" "${SMTP_HOST}"; fi
if [[ -n "${SMTP_PORT:-}" ]]; then upsert_env "MAIL_PORT" "${SMTP_PORT}"; fi
if [[ -n "${SMTP_USER:-}" ]]; then upsert_env "MAIL_USERNAME" "${SMTP_USER}"; fi
if [[ -n "${SMTP_PASS:-}" ]]; then upsert_env "MAIL_PASSWORD" "${SMTP_PASS}"; fi
if [[ -n "${SMTP_FROM:-}" ]]; then upsert_env "MAIL_FROM_ADDRESS" "${SMTP_FROM}"; fi

# Connect|API (secrets/configuração externa)
if [[ -n "${CONNECT_API_BASE_URL:-}" ]]; then upsert_env "CONNECT_API_BASE_URL" "${CONNECT_API_BASE_URL}"; fi
if [[ -n "${CONNECT_API_BOOTSTRAP_KEY:-}" ]]; then upsert_env "CONNECT_API_BOOTSTRAP_KEY" "${CONNECT_API_BOOTSTRAP_KEY}"; fi
if [[ -n "${CONNECT_API_DDI:-}" ]]; then upsert_env "CONNECT_API_DDI" "${CONNECT_API_DDI}"; fi
if [[ -n "${CONNECT_API_DDD:-}" ]]; then upsert_env "CONNECT_API_DDD" "${CONNECT_API_DDD}"; fi
if [[ -n "${CONNECT_API_INSTANCE_PREFIX:-}" ]]; then upsert_env "CONNECT_API_INSTANCE_PREFIX" "${CONNECT_API_INSTANCE_PREFIX}"; fi
if [[ -n "${CONNECT_API_WHATSAPP_MODAL_HINTBUTTON:-}" ]]; then upsert_env "CONNECT_API_WHATSAPP_MODAL_HINTBUTTON" "\"\${CONNECT_API_WHATSAPP_MODAL_HINTBUTTON}\""; fi
if [[ -n "${CONNECT_API_WHATSAPP_MODAL_TITLE:-}" ]]; then upsert_env "CONNECT_API_WHATSAPP_MODAL_TITLE" "\"\${CONNECT_API_WHATSAPP_MODAL_TITLE}\""; fi
if [[ -n "${CONNECT_API_WHATSAPP_SEND_BUTTON_TEXT:-}" ]]; then upsert_env "CONNECT_API_WHATSAPP_SEND_BUTTON_TEXT" "\"\${CONNECT_API_WHATSAPP_SEND_BUTTON_TEXT}\""; fi
if [[ -n "${CONNECT_API_WHATSAPP_MODAL_HINTFOOTER:-}" ]]; then upsert_env "CONNECT_API_WHATSAPP_MODAL_HINTFOOTER" "\"\${CONNECT_API_WHATSAPP_MODAL_HINTFOOTER}\""; fi

# Reverb (secrets)
if [[ -n "${REVERB_APP:-}" ]]; then upsert_env "REVERB_APP" "${REVERB_APP}"; fi
if [[ -n "${REVERB_APP_ID:-}" ]]; then upsert_env "REVERB_APP_ID" "${REVERB_APP_ID}"; fi
if [[ -n "${REVERB_APP_KEY:-}" ]]; then upsert_env "REVERB_APP_KEY" "${REVERB_APP_KEY}"; fi
if [[ -n "${REVERB_APP_SECRET:-}" ]]; then upsert_env "REVERB_APP_SECRET" "${REVERB_APP_SECRET}"; fi
if [[ -n "${REVERB_HOST:-}" ]]; then upsert_env "REVERB_HOST" "${REVERB_HOST}"; fi
if [[ -n "${REVERB_PORT:-}" ]]; then upsert_env "REVERB_PORT" "${REVERB_PORT}"; fi
if [[ -n "${REVERB_SCHEME:-}" ]]; then upsert_env "REVERB_SCHEME" "${REVERB_SCHEME}"; fi
if [[ -n "${REVERB_SERVER:-}" ]]; then upsert_env "REVERB_SERVER" "${REVERB_SERVER}"; fi
if [[ -n "${REVERB_SERVER_HOST:-}" ]]; then upsert_env "REVERB_SERVER_HOST" "${REVERB_SERVER_HOST}"; fi
if [[ -n "${REVERB_SERVER_PORT:-}" ]]; then upsert_env "REVERB_SERVER_PORT" "${REVERB_SERVER_PORT}"; fi

echo "Bootstrap concluído. .env gerado e chaves garantidas."
