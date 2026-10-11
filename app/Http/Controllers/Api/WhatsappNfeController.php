<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Funcionario;
use App\Models\ConnectApiWebhookEvent;
use App\Services\ConnectApi\ConnectApiMessageService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Http\Controllers\NotaFiscalController;
use App\Models\Cliente;
use App\Models\Produto;
use App\Models\ConfigNota;
use App\Models\Frete;
use App\Models\Venda;
use App\Models\ItemVenda;
use App\Models\Usuario;
use App\Models\NaturezaOperacao;
use App\Models\Transportadora;
use App\Models\Fornecedor;
use App\Models\Cidade;
use App\Models\Devolucao;
use App\Models\ItemDevolucao;
use App\Services\DevolucaoService;

class WhatsappNfeController extends Controller
{

    /**
     * Controller fiscal complementar. O webhook público deve permanecer somente
     * no ConnectApiWebhookController, que autentica tenant/instância e deduplica.
     * Esta classe NÃO registra webhook HTTP independente.
     */
    private int $empresaFiscal = 0;
    private int $instanciaFiscal = 0;
    private array $payloadEvento = [];

    /**
     * Roteamento compatível com a implementação do parceiro:
     * - comandos fiscais e XMLs explícitos iniciam o módulo;
     * - áudios podem iniciar o módulo SEM sessão, desde que o funcionário
     *   tenha autorização fiscal na empresa do evento;
     * - conversas fiscais em andamento mantêm suas opções numéricas;
     * - demais mensagens seguem o fluxo existente do Ponto e automações.
     *
     * Este método não altera o evento nem o estado de outras automações.
     */
    public function deveProcessarEvento(ConnectApiWebhookEvent $event): bool
    {
        $data = (array) ($event->normalized_payload ?? []);
        if (($data['event'] ?? '') !== 'messages-upsert' || (int) $event->empresa_id < 1) {
            return false;
        }

        $raw = (array) ($event->payload ?? []);
        if (in_array(data_get($raw, 'data.key.fromMe'), [true, 1, 'true', '1'], true)) {
            return false;
        }

        $jid = (string) ($data['remote_jid'] ?? '');
        if ($jid === '' || str_ends_with($jid, '@g.us') || str_ends_with($jid, '@broadcast')) {
            return false;
        }

        $numero = (string) ($data['from'] ?? '');
        $texto = trim((string) ($data['text'] ?? ''));
        $tipo = strtolower((string) (
            data_get($raw, 'data.messageType')
            ?? data_get($raw, 'data.message.messageType')
            ?? ''
        ));
        $msg = (array) data_get($raw, 'data.message', []);
        $ehAudio = isset($msg['audioMessage']) || in_array($tipo, ['audiomessage', 'audio', 'ptt'], true);

        // A verificação de estado é por empresa, instância e número real,
        // inclusive quando o JID recebido é um LID.
        $funcionario = null;
        $numeroFiscal = $numero;
        if (str_ends_with($jid, '@lid') || $ehAudio) {
            $funcionario = $this->localizarFuncionarioAutorizado(
                (int) $event->empresa_id, $jid, $numero
            );
            if ($funcionario) {
                $numeroFiscal = app(ConnectApiMessageService::class)->normalizeNumber(
                    (string) ($funcionario->celular ?: $funcionario->telefone)
                );
            }
        }

        if ($numeroFiscal !== '' && !in_array(
                Cache::get($this->chaveEstado(
                    (int) $event->empresa_id,
                    (int) $event->connect_api_instance_id,
                    $numeroFiscal
                )), [null, 'INICIO'], true
            )) {
            return true;
        }

        // Inclui DEVOLUÇÃO, REMESSA, FATURAR e COMANDOS reconhecidos pelo
        // código original do parceiro, sem capturar respostas numéricas
        // que pertençam ao ponto eletrônico.
        if ($texto !== '' && preg_match(
                '/^(?:FISCAL|ASSISTENTE\s+FISCAL|NOTA|NFE|NF-E|NF\b|DANFE|PDF\s*\d|XML\s*\d|DEVOLU|FAZER\s+DEVOLU|GERAR\s+DEVOLU|CONSULTAR\s+(?:NOTA|NF|NFE|DANFE|XML|PDF)|IMPRIMIR\s+(?:NOTA|NF|NFE|DANFE|XML|PDF)|CANCELAR\s+(?:NOTA|NF|NFE|NF-E|\d)|EMITIR\s+(?:NOTA|NF|NFE)|FATURAR\b)/iu',
                $texto
            )) {
            return true;
        }

        $documento = (array) (
            data_get($raw, 'data.message.documentMessage')
            ?? data_get($raw, 'data.message.documentWithCaptionMessage.message.documentMessage')
            ?? []
        );
        if (strtolower(pathinfo((string) ($documento['fileName'] ?? ''), PATHINFO_EXTENSION)) === 'xml'
            || str_contains(strtolower((string) ($documento['mimetype'] ?? '')), 'xml')) {
            return true;
        }

        if (!$ehAudio) {
            return false;
        }

        // Áudio sem sessão: mantém o fluxo original, mas só intercepta quando
        // realmente pertence a funcionário autorizado; áudio de outros
        // processos continua livre para o processamento existente.
        if (!$funcionario || !$funcionario->usuario_id || $numeroFiscal === '') {
            return false;
        }
        $usuario = Usuario::where('id', $funcionario->usuario_id)
            ->where('empresa_id', $event->empresa_id)
            ->first();

        return $usuario !== null && $this->usuarioAutorizado(
                $usuario, $funcionario, (int) $event->empresa_id
            );
    }

    public function processarEventoConnectApi(ConnectApiWebhookEvent $event)
    {
        $normalized = (array) ($event->normalized_payload ?? []);
        if ((int) $event->empresa_id < 1 || !$this->deveProcessarEvento($event)) {
            return response()->json(['status' => 'ignorado']);
        }
        $jid = (string) ($normalized['remote_jid'] ?? '');
        $numero = (string) ($normalized['from'] ?? '');
        $funcionario = $this->localizarFuncionarioAutorizado((int) $event->empresa_id, $jid, $numero);
        if (!$funcionario) {
            Log::warning('NF-e WhatsApp: funcionário não identificado ou número ambíguo.', [
                'event_id' => $event->id, 'empresa_id' => $event->empresa_id,
            ]);
            return response()->json(['status' => 'funcionario_nao_identificado']);
        }

        $numero = app(ConnectApiMessageService::class)->normalizeNumber(
            (string) ($funcionario->celular ?: $funcionario->telefone)
        );
        if ($numero === '') return response()->json(['status' => 'telefone_nao_cadastrado']);

        $this->empresaFiscal = (int) $event->empresa_id;
        $this->instanciaFiscal = (int) $event->connect_api_instance_id;
        $this->payloadEvento = (array) ($event->payload ?? []);

        $text = trim((string) ($normalized['text'] ?? ''));
        $text = trim((string) preg_replace('/^(?:FISCAL|ASSISTENTE FISCAL)\s*[:\-]?\s*/iu', '', $text));
        $message = (array) data_get($this->payloadEvento, 'data.message', []);
        $documentoAninhado = data_get($this->payloadEvento,
            'data.message.documentWithCaptionMessage.message.documentMessage');
        $type = (isset($message['documentMessage']) || is_array($documentoAninhado))
            ? 'documentMessage'
            : ((isset($message['audioMessage']) || data_get($this->payloadEvento, 'data.messageType') === 'audioMessage')
                ? 'audioMessage' : 'text');
        $mediaUrl = (string) (data_get($this->payloadEvento, 'data.media_url')
            ?? data_get($this->payloadEvento, 'data.message.documentMessage.mediaUrl')
            ?? data_get($this->payloadEvento, 'data.message.documentWithCaptionMessage.message.documentMessage.mediaUrl')
            ?? data_get($this->payloadEvento, 'data.message.documentMessage.url')
            ?? data_get($this->payloadEvento, 'data.message.audioMessage.mediaUrl')
            ?? data_get($this->payloadEvento, 'data.message.audioMessage.url') ?? '');
        $request = Request::create('/_internal/fiscal/whatsapp', 'POST', [
            'numero_tratado' => $numero,
            'texto_tratado' => $text,
            'message_type' => $type,
            'media_url' => $mediaUrl,
            'file_name' => (string) (data_get($this->payloadEvento, 'data.message.documentMessage.fileName')
                ?? data_get($this->payloadEvento, 'data.message.documentWithCaptionMessage.message.documentMessage.fileName') ?? ''),
        ]);
        $request->attributes->set('fiscal_connect_api_event_id', (int) $event->id);
        $request->attributes->set('funcionario_obj', $funcionario);

        $sessaoAnterior = session()->get('user_logged');
        $authAnterior = Auth::user();
        try {
            return $this->webhook($request);
        } finally {
            if ($sessaoAnterior === null) session()->forget('user_logged');
            else session()->put('user_logged', $sessaoAnterior);
            if ($authAnterior) Auth::setUser($authAnterior);
            else Auth::forgetUser();
            $this->empresaFiscal = 0;
            $this->instanciaFiscal = 0;
            $this->payloadEvento = [];
        }
    }

    /**
     * Se uma rota HTTP antiga ainda apontar aqui, ela falha fechada.
     */
    public function webhook(Request $request)
    {
        if (!$request->attributes->get('fiscal_connect_api_event_id') || $this->empresaFiscal < 1) {
            return response()->json(['status' => 'acesso_negado'], 403);
        }
        $funcionario = $request->attributes->get('funcionario_obj');
        $numero = (string) $request->input('numero_tratado', '');
        $mensagem = trim((string) $request->input('texto_tratado', ''));
        $whatsappSender = $this;
        if (!$funcionario || (int) $funcionario->empresa_id !== $this->empresaFiscal) {
            return response()->json(['status' => 'empresa_invalida'], 403);
        }
        Log::info('NF-e WhatsApp: evento encaminhado pela Connect|API.', [
            'empresa_id' => $this->empresaFiscal,
            'evento_id' => $request->attributes->get('fiscal_connect_api_event_id'),
        ]);

        // =========================================================================
        // REGRA 1: VALIDAÇÃO DE PERMISSÃO DO FUNCIONÁRIO (TRAVA DE SEGURANÇA)
        // =========================================================================
        $usuarioLogado = Usuario::find($funcionario->usuario_id);

        if (!$usuarioLogado || (int) $usuarioLogado->empresa_id !== $this->empresaFiscal
            || !$this->usuarioAutorizado($usuarioLogado, $funcionario)) {
            Log::warning("Acesso negado para emissão NFe WhatsApp. Usuario ID: " . $funcionario->usuario_id);
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⛔ *Acesso Negado*\nO seu utilizador não possui permissão administrativa para emitir ou gerir Notas Fiscais remotamente. Contacte a gerência.");
            return response()->json(['status' => 'sem_permissao']);
        }

        $estadoId = $this->chaveCache('estado', $numero);
        $msgUpper = strtoupper($mensagem);

        // =========================================================================
        // COMANDOS GLOBAIS DE CANCELAMENTO DA OPERAÇÃO DO ROBÔ
        // =========================================================================
        if ($msgUpper === 'CANCELAR') {
            Cache::forget($estadoId);
            Cache::forget($this->chaveCache('nfe_dados', $numero));
            Cache::forget($this->chaveCache('dev_dados', $numero));
            Cache::forget($this->chaveCache('cancelamento_id', $numero));
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Operação cancelada com sucesso. O robô está livre para uma nova instrução.");
            return response()->json(['status' => 'cancelado']);
        }

        $estadoAtual = Cache::get($estadoId, 'INICIO');

        // =========================================================================
        // CAPTURA DE ARQUIVOS E ÁUDIOS (PRIORIDADE ALTA)
        // =========================================================================
        $tipoMensagem = $request->message_type ?? $request->tipo_mensagem ?? '';
        $urlArquivo = (string) ($request->input('media_url') ?? $request->input('document_url') ?? $request->input('audio_url') ?? '');
        $extensaoArq = pathinfo((string) $request->input('file_name', ''), PATHINFO_EXTENSION);

        // Recebe XML somente quando MIME/nome do documento indicar XML
        if ($tipoMensagem === 'documentMessage' && (strtolower($extensaoArq) === 'xml' || str_contains(strtolower((string) (data_get($this->payloadEvento, 'data.message.documentMessage.mimetype') ?? data_get($this->payloadEvento, 'data.message.documentWithCaptionMessage.message.documentMessage.mimetype') ?? '')), 'xml'))) {
            Log::info("--- XML DETETADO PARA DEVOLUÇÃO DE {$numero} ---");
            return $this->processarXmlDevolucao($numero, $urlArquivo, $funcionario, $estadoId, $whatsappSender);
        }

        // Se enviar um Áudio
        if ($tipoMensagem === 'audioMessage' || $tipoMensagem === 'audio' || $tipoMensagem === 'ptt') {
            Log::info("--- NFE: ÁUDIO DETETADO DE {$numero}, INICIANDO PROCESSAMENTO ---");
            return $this->processarMensagemAudio($numero, $urlArquivo, $funcionario, $estadoId, $whatsappSender);
        }


        // =========================================================================
        // GATILHOS INTELIGENTES POR TEXTO (SÓ ATUAM QUANDO O ROBÔ ESTÁ LIVRE)
        // =========================================================================
        if (in_array($estadoAtual, ['INICIO', 'FISCAL_ATIVO'], true) && $mensagem !== '') {

            // A. INICIAR DEVOLUÇÃO (Impede que "Nota de Exportação" entre aqui)
            if (preg_match('/^(NOTA DE DEVOLU|DEVOLU|FAZER DEVOLU|GERAR DEVOLU)/i', $msgUpper) && !str_contains($msgUpper, 'EXPORTA')) {
                Cache::put($estadoId, 'AGUARDANDO_XML_DEVOLUCAO', 600);
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "📦 *Módulo de Devolução*\n\nPara iniciar, por favor, **envie o ficheiro XML** da nota original aqui no chat.");
                return response()->json(['status' => 'aguardando_xml']);
            }

            // B. CONSULTAR / IMPRIMIR NOTA
            if (preg_match('/^(?:CONSULTAR|IMPRIMIR\s+)?(?:DANFE|PDF|XML|NOTA|NFE|NF-E|NF)\s*(?:DA\s*NOTA)?\s*#?(\d+)$/i', $mensagem, $matches) || preg_match('/^(\d+)$/', $mensagem, $matches)) {
                $numeroNota = $matches[1];
                return $this->enviarSegundaViaNfe($numero, $numeroNota, $funcionario, $whatsappSender);
            }

            // C. CANCELAR NOTA NA SEFAZ
            if (preg_match('/^(?:CANCELAR\s+(?:N[F-]*E|NOTA|NF)|(?:N[F-]*E|NOTA|NF)\s+\d+\s+CANCELAR)\s*#?(\d+)?/i', $mensagem, $matches) || preg_match('/^CANCELAR\s+(\d+)$/i', $mensagem, $matches)) {
                preg_match_all('/\d+/', $mensagem, $nums);
                $numeroNotaParaCancelar = $nums[0][0] ?? null;

                if ($numeroNotaParaCancelar) {
                    $vendaAlvo = Venda::where('empresa_id', $funcionario->empresa_id)->where('NfNumero', $numeroNotaParaCancelar)->first()
                        ?? Venda::where('empresa_id', $funcionario->empresa_id)->where('id', $numeroNotaParaCancelar)->first();

                    if ($vendaAlvo && strtoupper($vendaAlvo->estado) === 'APROVADO') {
                        Cache::put($this->chaveCache('cancelamento_id', $numero), $vendaAlvo->id, 600);
                        Cache::put($estadoId, 'AGUARDANDO_JUSTIFICATIVA_CANCELAMENTO', 600);
                        $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "📝 Informe a **justificativa** para cancelar a Nota Fiscal #{$numeroNotaParaCancelar}:");
                    } else {
                        $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠ Nota Fiscal #{$numeroNotaParaCancelar} não encontrada ou não aprovada.");
                    }
                    return response()->json(['status' => 'aguardando_justificativa']);
                }
            }

            // D. EMISSÃO DE NOTA DE VENDA / EXPORTAÇÃO / REMESSA (Manda para o Gemini)
            if (preg_match('/^(NOTA|NFE|NF|EMITIR|FATURAR)\b/i', $msgUpper)) {
                return $this->processarMensagemInicial($numero, $mensagem, $funcionario, $estadoId, $whatsappSender);
            }
        }

        // =========================================================================
        // MÁQUINA DE ESTADOS DOS PROCESSOS EM ANDAMENTO
        // =========================================================================
        try {
            switch ($estadoAtual) {
                case 'AGUARDANDO_XML_DEVOLUCAO':
                    $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⏳ Ainda aguardo que envie o **ficheiro XML** da nota para continuarmos a devolução.\n\n_(Se quiser desistir, digite CANCELAR)_");
                    return response()->json(['status' => 'aguardando_ficheiro']);

                case 'AGUARDANDO_JUSTIFICATIVA_CANCELAMENTO':
                    return $this->processarCancelamentoSefaz($numero, $mensagem, $funcionario, $estadoId, $whatsappSender);

                case 'DEVOLUCAO_AGUARDANDO_TIPO':
                    return $this->processarTipoDevolucao($numero, $mensagem, $funcionario, $estadoId, $whatsappSender);

                case 'DEVOLUCAO_AGUARDANDO_DESTINATARIO_ORIGEM':
                    return $this->processarOrigemVendaDevolucao($numero, $mensagem, $funcionario, $estadoId, $whatsappSender);

                case 'DEVOLUCAO_AGUARDANDO_TOTAL_PARCIAL':
                    return $this->processarTotalParcialDevolucao($numero, $mensagem, $funcionario, $estadoId, $whatsappSender);

                case 'DEVOLUCAO_AGUARDANDO_ITENS_PARCIAIS':
                    return $this->processarItensParciaisDevolucao($numero, $mensagem, $funcionario, $estadoId, $whatsappSender);

                case 'DEVOLUCAO_AGUARDANDO_MOTIVO':
                    return $this->processarFinalizacaoDevolucao($numero, $mensagem, $funcionario, $estadoId, $whatsappSender);

                case 'AGUARDANDO_CONFIRMACAO':
                    return $this->processarConfirmacaoFinal($numero, $mensagem, $funcionario, $estadoId, $whatsappSender);

                default:
                    Cache::put($estadoId, 'FISCAL_ATIVO', 600);
                    $menu = "🤖 *Assistente Fiscal {$this->nomeAplicacao()}*\n\nComo posso ajudar?\n\n";
                    $menu .= "🔹 *Emitir Nota:* _\"Nota para cliente X, 100kg de cobre a R$30\"_\n";
                    $menu .= "🔹 *Devolução:* _\"Nota de devolução\"_ (ou anexe direto o XML)\n";
                    $menu .= "🔹 *Consultar/Imprimir:* _\"Nota 123\"_\n";
                    $menu .= "🔹 *Cancelar:* _\"Cancelar nota 123\"_";

                    $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, $menu);
                    return response()->json(['status' => 'menu_ajuda']);
            }
        } catch (\Exception $e) {
            Log::error("Erro no Webhook: " . $e->getMessage());
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠ Ocorreu um erro ao processar. Digite CANCELAR e tente novamente.");
            return response()->json(['status' => 'erro']);
        }
    }


    /**
     * Função isolada para o Cancelamento na SEFAZ
     */
    public function processarCancelamentoSefaz($numero, $mensagem, $funcionario, $estadoId, $whatsappSender)
    {
        $vendaIdParaCancelar = Cache::get($this->chaveCache('cancelamento_id', $numero));
        $mensagem = trim((string) $mensagem);
        if (mb_strlen($mensagem) < 15 || mb_strlen($mensagem) > 255) {
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero,
                '⚠️ A justificativa fiscal precisa ter entre 15 e 255 caracteres.');
            return response()->json(['status' => 'justificativa_invalida']);
        }

        if (!$vendaIdParaCancelar) {
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Sessão de cancelamento expirada. Tente novamente.");
            return response()->json(['status' => 'expirado']);
        }

        $vendaAlvo = Venda::where('empresa_id', $funcionario->empresa_id)->where('id', $vendaIdParaCancelar)->first();

        if ($vendaAlvo && strtoupper($vendaAlvo->estado) === 'APROVADO') {
            try {
                $requestCancela = new \Illuminate\Http\Request();
                $requestCancela->merge(['id' => $vendaAlvo->id, 'justificativa' => $mensagem, '_token' => csrf_token()]);

                $this->forcarLoginMestre($vendaAlvo->empresa_id, $funcionario->usuario_id);

                $nfController = app(\App\Http\Controllers\NotaFiscalController::class);
                $definirEmpresa = function (int $empresa) { $this->empresa_id = $empresa; };
                $definirEmpresa->call($nfController, (int) $vendaAlvo->empresa_id);

                $respostaCancelamento = $nfController->cancelar($requestCancela);
                $dadosRetorno = json_decode($respostaCancelamento->getContent(), true);

                $vendaAlvo->refresh();

                if (strtoupper($vendaAlvo->estado) === 'CANCELADO') {
                    $numExibicao = $vendaAlvo->NfNumero ?? $vendaAlvo->id;
                    Cache::forget($estadoId);
                    Cache::forget($this->chaveCache('cancelamento_id', $numero));
                    $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "✅ Nota Fiscal #{$numExibicao} cancelada com sucesso junto à SEFAZ e estoque revertido!\n\n*Motivo:* {$mensagem}");
                } else {
                    $motivoErro = $dadosRetorno['retEvento']['infEvento']['xMotivo'] ?? $dadosRetorno['motivo'] ?? "Rejeição SEFAZ";
                    $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠️ A SEFAZ não autorizou o cancelamento. Consulte o motivo no ERP.");
                }
            } catch (\Exception $cancelEx) {
                Log::error("Erro ao cancelar NF via WhatsApp: " . $cancelEx->getMessage());
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠️ Não foi possível concluir o cancelamento. Verifique a rejeição no ERP.");
            }
        }
        return response()->json(['status' => 'processado_cancelamento']);
    }


    // =========================================================================
    // MÓDULO DE DEVOLUÇÃO DE NOTAS FISCAIS VIA WHATSAPP (XML)
    // =========================================================================

    public function processarXmlDevolucao($numero, $urlArquivo, $funcionario, $estadoId, $whatsappSender)
    {
        $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "📂 A ler o ficheiro XML da nota original... Aguarde.");

        try {
            // A mídia é entregue pelo evento autenticado Connect|API; URLs arbitrárias
            // não são baixadas (proteção contra SSRF e vazamento entre tenants).
            $xmlConteudo = $this->obterMidiaRecebida('xml', (string) $urlArquivo);
            if ($xmlConteudo === '') {
                throw new \RuntimeException('XML não disponível. Verifique o Base64 ou a URL de mídia autorizada da Connect|API.');
            }

            $xmlConteudo = trim($xmlConteudo);
            if (stripos($xmlConteudo, '<!DOCTYPE') !== false || stripos($xmlConteudo, '<!ENTITY') !== false) {
                throw new \RuntimeException('XML com entidades externas não é aceito.');
            }
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlConteudo, \SimpleXMLElement::class, LIBXML_NONET);

            if ($xml === false) {
                $errosLibxml = libxml_get_errors();
                libxml_clear_errors();
                $msgErroParse = $errosLibxml[0]->message ?? 'Formato XML inválido';
                throw new \Exception("Erro de parse no XML: " . trim($msgErroParse));
            }


            $infNFe = null;
            if (isset($xml->NFe->infNFe)) {
                $infNFe = $xml->NFe->infNFe;
            } elseif (isset($xml->infNFe)) {
                $infNFe = $xml->infNFe;
            } else {
                throw new \Exception("A estrutura do XML não corresponde a uma NFe válida.");
            }

            $chave = preg_replace('/\D+/', '', (string) $infNFe->attributes()->Id);
            if (strlen($chave) !== 44) {
                throw new \RuntimeException('Chave de acesso do XML inválida.');
            }
            $emit = $infNFe->emit;
            $dest = $infNFe->dest;

            $cnpjEmpresa = preg_replace('/\D+/', '', (string) (ConfigNota::where('empresa_id', $funcionario->empresa_id)->value('cnpj') ?? ''));
            $cnpjEmitente = preg_replace('/\D+/', '', (string) ($emit->CNPJ ?? ''));
            $cnpjDestinatario = preg_replace('/\D+/', '', (string) ($dest->CNPJ ?? ''));
            $participantes = array_filter([$cnpjEmitente, $cnpjDestinatario]);
            $cnpjsEmpresa = [$cnpjEmpresa];
            foreach (DB::table('filials')->where('empresa_id', $funcionario->empresa_id)->pluck('cnpj') as $cnpjFilial) {
                $cnpjsEmpresa[] = preg_replace('/\D+/', '', (string) $cnpjFilial);
            }
            if (!$participantes || !array_intersect($cnpjsEmpresa, $participantes)) {
                throw new \RuntimeException('Emitente ou destinatário não pertence à matriz ou filial da empresa.');
            }

            $dadosOriginal = [
                'chave' => $chave,
                'nNf' => (string)$infNFe->ide->nNF,
                'emitente' => [
                    'razao' => (string)$emit->xNome,
                    'cnpj_cpf' => (string)($emit->CNPJ ?? $emit->CPF),
                    'ie' => (string)$emit->IE,
                    'logradouro' => (string)$emit->enderEmit->xLgr,
                    'numero' => (string)$emit->enderEmit->nro,
                    'bairro' => (string)$emit->enderEmit->xBairro,
                    'cidade_id' => (string)$emit->enderEmit->cMun,
                    'uf' => (string)$emit->enderEmit->UF,
                    'cep' => (string)$emit->enderEmit->CEP,
                ],
                'destinatario' => [
                    'razao' => (string)$dest->xNome,
                    'cnpj_cpf' => (string)($dest->CNPJ ?? $dest->CPF),
                    'ie' => (string)($dest->IE ?? 'ISENTO'),
                    'logradouro' => (string)($dest->enderDest->xLgr ?? ''),
                    'numero' => (string)($dest->enderDest->nro ?? ''),
                    'bairro' => (string)($dest->enderDest->xBairro ?? ''),
                    'cidade_id' => (string)($dest->enderDest->cMun ?? ''),
                    'uf' => (string)($dest->enderDest->UF ?? ''),
                    'cep' => (string)($dest->enderDest->CEP ?? ''),
                ],
                'itens' => [],
                'valor_original' => 0.0,
            ];

            foreach ($infNFe->det as $item) {
                $trib = Devolucao::getTrib($item->imposto);
                $dadosOriginal['valor_original'] += ((float) $item->prod->vUnCom) * ((float) $item->prod->qCom);
                $dadosOriginal['itens'][] = [
                    'codigo' => (string)$item->prod->cProd,
                    'xProd' => (string)$item->prod->xProd,
                    'NCM' => (string)$item->prod->NCM,
                    'CFOP' => (string)$item->prod->CFOP,
                    'uCom' => (string)$item->prod->uCom,
                    'vUnCom' => (float)$item->prod->vUnCom,
                    'qCom' => (float)$item->prod->qCom,
                    'codBarras' => (string)($item->prod->cEAN ?? ''),
                    'cst_csosn' => $trib['cst_csosn'],
                    'cst_pis' => $trib['cst_pis'],
                    'cst_cofins' => $trib['cst_cofins'],
                    'cst_ipi' => $trib['cst_ipi'],
                    'perc_icms' => $trib['pICMS'],
                    'perc_pis' => $trib['pPIS'],
                    'perc_cofins' => $trib['pCOFINS'],
                    'perc_ipi' => $trib['pIPI'],
                    'pRedBC' => $trib['pRedBC'],
                    'orig' => $trib['orig'],
                    // ESPELHAMENTO DA SUBSTITUIÇÃO TRIBUTÁRIA E MVA
                    'modBCST' => $trib['modBCST'] ?? 0,
                    'vBCST' => $trib['vBCST'] ?? 0,
                    'pICMSST' => $trib['pICMSST'] ?? 0,
                    'vICMSST' => $trib['vICMSST'] ?? 0,
                    'pMVAST' => $trib['pMVAST'] ?? 0,
                    'vBCSTRet' => $trib['vBCSTRet'] ?? 0,
                    'pST' => $trib['pST'] ?? 0,
                    'vICMSSubstituto' => $trib['vICMSSubstituto'] ?? 0,
                    'vICMSSTRet' => $trib['vICMSSTRet'] ?? 0,
                ];
            }

            $folder = storage_path('app/whatsapp_nfe/entradas/' . $this->empresaFiscal);
            if (!is_dir($folder)) mkdir($folder, 0755, true);
            $arquivoEntrada = $folder . DIRECTORY_SEPARATOR . $chave . '.xml';
            if (file_put_contents($arquivoEntrada, $xmlConteudo, LOCK_EX) === false) throw new \RuntimeException('Falha ao persistir XML recebido.');
            @chmod($arquivoEntrada, 0600);

            Cache::put($this->chaveCache('dev_dados', $numero), $dadosOriginal, 600);
            Cache::put($estadoId, 'DEVOLUCAO_AGUARDANDO_TIPO', 600);

            $msg = "📄 *XML da Nota #{$dadosOriginal['nNf']} Carregado com Sucesso!*\n\n";
            $msg .= "Emitente: {$dadosOriginal['emitente']['razao']}\n";
            $msg .= "Destinatário: {$dadosOriginal['destinatario']['razao']}\n\n";
            $msg .= "Indique o tipo de operação:\n*1* - Devolução de **Compra** (Saída)\n*2* - Devolução de **Venda** (Entrada)";

            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, $msg);
            return response()->json(['status' => 'aguardando_tipo']);

        } catch (\Exception $e) {
            Log::error("Erro detalhado leitura XML Devolução: " . $e->getMessage());
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Falha ao validar o XML recebido. Confira o arquivo e tente novamente.");
            return response()->json(['status' => 'erro']);
        }
    }

    public function processarTipoDevolucao($numero, $mensagem, $funcionario, $estadoId, $whatsappSender)
    {
        $dadosDev = Cache::get($this->chaveCache('dev_dados', $numero));
        if (!$dadosDev || !is_array($dadosDev)) return response()->json(['status' => 'expirado']);

        $opcao = trim($mensagem);
        if ($opcao !== '1' && $opcao !== '2') {
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠️ Digite *1* para Compra ou *2* para Venda.");
            return response()->json(['status' => 'invalido']);
        }

        $tipoOperacao = ($opcao === '1') ? 'compra' : 'venda';
        $dadosDev['tipo_operacao'] = $tipoOperacao;

        if ($tipoOperacao === 'compra') {
            $dadosDev['enquadramento_cabecalho'] = 'emitente';
            Cache::put($this->chaveCache('dev_dados', $numero), $dadosDev, 600); // Salva o array atualizado com todos os dados
            Cache::put($estadoId, 'DEVOLUCAO_AGUARDANDO_TOTAL_PARCIAL', 600);

            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "📦 Devolução de **Compra**.\n\nA devolução é:\n*1* - **Total**\n*2* - **Parcial**");
            return response()->json(['status' => 'aguardando_parcial']);
        } else {
            Cache::put($this->chaveCache('dev_dados', $numero), $dadosDev, 600);
            Cache::put($estadoId, 'DEVOLUCAO_AGUARDANDO_DESTINATARIO_ORIGEM', 600);

            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "🔄 Devolução de **Venda**.\n\nEmitir contra:\n*1* - **Emitente** original\n*2* - **Destinatário** original");
            return response()->json(['status' => 'aguardando_origem']);
        }
    }

    public function processarOrigemVendaDevolucao($numero, $mensagem, $funcionario, $estadoId, $whatsappSender)
    {
        $dadosDev = Cache::get($this->chaveCache('dev_dados', $numero));
        if (!$dadosDev || !is_array($dadosDev)) return response()->json(['status' => 'expirado']);

        $opcao = trim($mensagem);
        if ($opcao !== '1' && $opcao !== '2') {
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠️ Digite *1* para Emitente ou *2* para Destinatário.");
            return response()->json(['status' => 'invalido']);
        }

        $dadosDev['enquadramento_cabecalho'] = ($opcao === '1') ? 'emitente' : 'destinatario';
        Cache::put($this->chaveCache('dev_dados', $numero), $dadosDev, 600); // Salva preservando o array completo
        Cache::put($estadoId, 'DEVOLUCAO_AGUARDANDO_TOTAL_PARCIAL', 600);

        $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "A devolução é:\n*1* - **Total**\n*2* - **Parcial**");
        return response()->json(['status' => 'aguardando_parcial']);
    }

    public function processarTotalParcialDevolucao($numero, $mensagem, $funcionario, $estadoId, $whatsappSender)
    {
        $dadosDev = Cache::get($this->chaveCache('dev_dados', $numero));
        if (!$dadosDev || !is_array($dadosDev)) {
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Sessão expirada. Envie o XML novamente.");
            Cache::forget($estadoId);
            return response()->json(['status' => 'expirado']);
        }

        $opcao = trim($mensagem);
        if ($opcao !== '1' && $opcao !== '2') {
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠️ Digite *1* para Total ou *2* para Parcial.");
            return response()->json(['status' => 'invalido']);
        }

        $dadosDev['devolucao_parcial'] = ($opcao === '2') ? '2' : '1';
        Cache::put($this->chaveCache('dev_dados', $numero), $dadosDev, 600);
        if ($opcao === '2') {
            Cache::put($estadoId, 'DEVOLUCAO_AGUARDANDO_ITENS_PARCIAIS', 600);
            $linhas = [];
            foreach (($dadosDev['itens'] ?? []) as $i => $item) {
                $linhas[] = ($i + 1) . '. ' . ($item['xProd'] ?? 'Produto')
                    . ' (máx. ' . ($item['qCom'] ?? '0') . ')';
            }
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero,
                "📦 *Devolução parcial*\n" . implode("\n", $linhas)
                . "\n\nInforme quantidades por item no formato *1=2,5;2=1*. Itens não informados não serão devolvidos.");
            return response()->json(['status' => 'aguardando_quantidades']);
        }
        Cache::put($estadoId, 'DEVOLUCAO_AGUARDANDO_MOTIVO', 600);

        $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "✍️ Qual o *Motivo da Devolução*?\n\n_(Ex: Mercadoria avariada, Desistência, etc. Este motivo e os dados da nota sairão automaticamente nas Informações Complementares)_");

        return response()->json(['status' => 'aguardando_motivo']);
    }

    public function processarFinalizacaoDevolucao($numero, $mensagem, $funcionario, $estadoId, $whatsappSender)
    {
        $dadosDev = Cache::get($this->chaveCache('dev_dados', $numero));

        if (!$dadosDev || !is_array($dadosDev) || !isset($dadosDev['emitente'])) {
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Sessão de devolução expirada. Por favor, envie o XML novamente.");
            Cache::forget($estadoId);
            return response()->json(['status' => 'expirado']);
        }

        $motivo = trim($mensagem);
        if (empty($motivo) || strlen($motivo) < 3) {
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠️ Por favor, escreva um motivo válido para a devolução.");
            return response()->json(['status' => 'invalido']);
        }

        $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "🚀 A gerar e transmitir a Devolução para a SEFAZ... Aguarde.");

        try {
            DB::beginTransaction();

            $tipoCabecalho = $dadosDev['enquadramento_cabecalho'] ?? 'emitente';
            $entidadeInfo = $dadosDev[$tipoCabecalho] ?? null;

            if (!$entidadeInfo || !isset($entidadeInfo['cnpj_cpf'])) {
                throw new \Exception("Dados do cabeçalho original ({$tipoCabecalho}) não foram encontrados.");
            }

            // =======================================================
            // LIMPEZA CIRÚRGICA DA CHAVE PARA 44 DÍGITOS ESTILIZADOS
            // =======================================================
            $chaveOrigem = preg_replace('/\D/', '', $dadosDev['chave'] ?? '');
            if (strlen($chaveOrigem) !== 44) {
                throw new \Exception("A chave da nota referenciada não tem 44 dígitos ({$chaveOrigem}). O XML original pode estar corrompido.");
            }

            $serieOrigem = (int)substr($chaveOrigem, 22, 3);
            $nNfOrigem = $dadosDev['nNf'] ?? (int)substr($chaveOrigem, 25, 9);

            $isParcial = (isset($dadosDev['devolucao_parcial']) && $dadosDev['devolucao_parcial'] === '2');
            $tipoExtenso = $isParcial ? 'PARCIAL' : 'TOTAL';

            // Mensagem complementar montada com o motivo, série, nota e chave referenciada
            $obsComplementar = mb_strtoupper("{$motivo} - REFERENTE DEVOLUCAO {$tipoExtenso} DA NF-E {$nNfOrigem} SERIE {$serieOrigem} - CHAVE: {$chaveOrigem}");

            $docEntidade = preg_replace('/\D/', '', $entidadeInfo['cnpj_cpf']);

            $fornecedor = Fornecedor::where('empresa_id', $funcionario->empresa_id)
                ->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '/', ''), '-', '') = ?", [$docEntidade])
                ->first();

            if (!$fornecedor) {
                $clienteCadastrado = Cliente::where('empresa_id', $funcionario->empresa_id)
                    ->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '/', ''), '-', '') = ?", [$docEntidade])
                    ->first();

                if ($clienteCadastrado) {
                    $fornecedor = Fornecedor::firstOrCreate([
                        'empresa_id' => $funcionario->empresa_id,
                        'cpf_cnpj' => $docEntidade
                    ], [
                        'razao_social' => $clienteCadastrado->razao_social,
                        'nome_fantasia' => $clienteCadastrado->nome_fantasia ?? $clienteCadastrado->razao_social,
                        'rua' => $clienteCadastrado->rua ?? 'Não informada',
                        'numero' => $clienteCadastrado->numero ?? 'S/N',
                        'bairro' => $clienteCadastrado->bairro ?? 'Centro',
                        'cep' => preg_replace('/\D/', '', (string) ($clienteCadastrado->cep ?? '')),
                        'ie_rg' => $clienteCadastrado->ie_rg ?: 'ISENTO',
                        'cidade_id' => $clienteCadastrado->cidade_id,
                    ]);
                } else {
                    $cidade = Cidade::where('codigo', $entidadeInfo['cidade_id'] ?? '')->first();
                    if (!$cidade) throw new \RuntimeException('Município do participante não localizado no cadastro.');

                    $fornecedor = Fornecedor::create([
                        'razao_social' => $entidadeInfo['razao'] ?? 'CLIENTE/FORNECEDOR XML',
                        'nome_fantasia' => $entidadeInfo['razao'] ?? 'CLIENTE/FORNECEDOR XML',
                        'rua' => $entidadeInfo['logradouro'] ?? 'Não informada',
                        'numero' => $entidadeInfo['numero'] ?? 'S/N',
                        'bairro' => $entidadeInfo['bairro'] ?? 'Centro',
                        'cep' => preg_replace('/\D/', '', (string) ($entidadeInfo['cep'] ?? '')),
                        'cpf_cnpj' => $docEntidade,
                        'ie_rg' => !empty($entidadeInfo['ie']) ? $entidadeInfo['ie'] : 'ISENTO',
                        'cidade_id' => $cidade->id,
                        'empresa_id' => $funcionario->empresa_id,
                    ]);
                }
            }

            $natureza = NaturezaOperacao::where('empresa_id', $funcionario->empresa_id)->where('finNFe', 4)->first()
                ?: NaturezaOperacao::where('empresa_id', $funcionario->empresa_id)->first();

            if (!$natureza || (int) ($natureza->finNFe ?? 0) !== 4) {
                throw new \RuntimeException('Natureza de devolução (finNFe=4) não configurada para a empresa.');
            }

            $tipoNf = (isset($dadosDev['tipo_operacao']) && $dadosDev['tipo_operacao'] === 'compra') ? 1 : 0;

            $valorTotalDevolucao = 0;
            if (isset($dadosDev['itens']) && is_array($dadosDev['itens'])) {
                foreach ($dadosDev['itens'] as $i) {
                    if (isset($i['vUnCom']) && isset($i['qCom'])) {
                        $valorTotalDevolucao += ($i['vUnCom'] * $i['qCom']);
                    }
                }
            }

            $devolucao = Devolucao::create([
                'fornecedor_id' => $fornecedor->id,
                'usuario_id' => $funcionario->usuario_id,
                'natureza_id' => $natureza->id,
                'valor_integral' => (float) ($dadosDev['valor_original'] ?? $valorTotalDevolucao),
                'valor_devolvido' => $valorTotalDevolucao,
                'motivo' => mb_strtoupper($motivo),
                'observacao' => $obsComplementar,
                'estado' => 0,
                'devolucao_parcial' => $isParcial ? 1 : 0,
                'chave_nf_entrada' => $chaveOrigem,
                'nNf' => $dadosDev['nNf'] ?? '',
                'vFrete' => 0.00,
                'vDesc' => 0.00,
                'vOutro' => 0.00,
                'tipo' => $tipoNf,
                'empresa_id' => $funcionario->empresa_id,
                'filial_id' => $funcionario->filial_id ?? null,
            ]);

            // Espelhamento completo de impostos e devolução ST
            if (isset($dadosDev['itens']) && is_array($dadosDev['itens'])) {
                foreach ($dadosDev['itens'] as $i) {
                    ItemDevolucao::create([
                        'cod' => $i['codigo'] ?? '00',
                        'nome' => $i['xProd'] ?? 'PRODUTO DEVOLUCAO',
                        'ncm' => $i['NCM'] ?? '',
                        'cfop' => $i['CFOP'] ?? '',
                        'valor_unit' => $i['vUnCom'] ?? 0,
                        'quantidade' => $i['qCom'] ?? 0,
                        'item_parcial' => $isParcial ? 1 : 0,
                        'unidade_medida' => $i['uCom'] ?? 'UN',
                        'codBarras' => $i['codBarras'] ?? '',
                        'devolucao_id' => $devolucao->id,
                        'cst_csosn' => $i['cst_csosn'] ?? '102',
                        'cst_pis' => $i['cst_pis'] ?? '99',
                        'cst_cofins' => $i['cst_cofins'] ?? '99',
                        'cst_ipi' => $i['cst_ipi'] ?? '99',
                        'perc_icms' => $i['perc_icms'] ?? 0,
                        'perc_pis' => $i['perc_pis'] ?? 0,
                        'perc_cofins' => $i['perc_cofins'] ?? 0,
                        'perc_ipi' => $i['perc_ipi'] ?? 0,
                        'pRedBC' => $i['pRedBC'] ?? 0,
                        'modBCST' => $i['modBCST'] ?? 0,
                        'vBCST' => $i['vBCST'] ?? 0,
                        'pICMSST' => $i['pICMSST'] ?? 0,
                        'vICMSST' => $i['vICMSST'] ?? 0,
                        'pMVAST' => $i['pMVAST'] ?? 0,
                        'vBCSTRet' => $i['vBCSTRet'] ?? 0,
                        'pST' => $i['pST'] ?? 0,
                        'vICMSSubstituto' => $i['vICMSSubstituto'] ?? 0,
                        'vICMSSTRet' => $i['vICMSSTRet'] ?? 0,
                        'orig' => $i['orig'] ?? 0,
                    ]);
                }
            }

            DB::commit();

            // =====================================================================
            // BLINDAGEM MÁXIMA DA CHAVE PARA O NFePHP
            // =====================================================================
            // Recuperamos o objeto da base de dados e INJETAMOS EXPLICITAMENTE
            // a string pura de 44 dígitos no atributo. Isto impede qualquer falha
            // no DevolucaoService por casting de variáveis do Eloquent ou espaços em branco.
            $devolucaoParaEnvio = Devolucao::find($devolucao->id);
            $devolucaoParaEnvio->chave_nf_entrada = (string)$chaveOrigem;

            $this->forcarLoginMestre((int) $funcionario->empresa_id, (int) $funcionario->usuario_id);

            $configNota = ConfigNota::where('empresa_id', $funcionario->empresa_id)->first();
            if (!$configNota) throw new \RuntimeException('Emitente fiscal não configurado.');
            $cnpjEmissor = preg_replace('/\D/', '', $configNota->cnpj ?? '');

            $servicoDevolucao = new DevolucaoService([
                'atualizacao' => date('Y-m-d H:i:s'),
                'tpAmb' => (int)($configNota->ambiente ?? 2),
                'razaosocial' => $configNota->razao_social ?? 'Empresa',
                'siglaUF' => $configNota->UF,
                'cnpj' => $cnpjEmissor,
                'schemes' => config('fiscal.default_schemes', 'PL_009_V4'),
                'versao' => '4.00',
                'tokenIBPT' => $configNota->token_ibpt ?? '',
                'CSC' => $configNota->csc ?? '',
                'CSCid' => $configNota->csc_id ?? '',
                'is_filial' => $devolucaoParaEnvio->filial_id
            ], 55);

            $xmlGerado = $servicoDevolucao->gerarDevolucao($devolucaoParaEnvio);

            if (isset($xmlGerado['erros_xml'])) {
                throw new \Exception("Erro ao gerar XML: " . (is_array($xmlGerado['erros_xml']) ? implode(" | ", $xmlGerado['erros_xml']) : $xmlGerado['erros_xml']));
            }

            $xmlAssinado = $servicoDevolucao->sign($xmlGerado['xml']);
            $resultadoTransmissao = $servicoDevolucao->transmitir($xmlAssinado, $xmlGerado['chave']);

            if (preg_match('/^\d+$/', trim($resultadoTransmissao))) {
                $devolucaoParaEnvio->chave_gerada = $xmlGerado['chave'];
                $devolucaoParaEnvio->numero_gerado = $xmlGerado['nNf'];
                $devolucaoParaEnvio->estado = 1;
                $devolucaoParaEnvio->save();

                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "✅ *Devolução Autorizada com Sucesso!*\nProtocolo: {$resultadoTransmissao}");
            } else {
                $devolucaoParaEnvio->estado = 2;
                $devolucaoParaEnvio->save();
                throw new \Exception("SEFAZ Rejeitou: " . $resultadoTransmissao);
            }

            Cache::forget($estadoId);
            Cache::forget($this->chaveCache('dev_dados', $numero));
            return response()->json(['status' => 'sucesso']);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) DB::rollBack();
            Log::error("Erro Devolução WhatsApp: " . $e->getMessage());
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ A devolução não foi concluída. Verifique os dados e os registros do ERP.");
            return response()->json(['status' => 'erro']);
        }
    }


    // =========================================================================
    // MÉTODOS DE EMISSÃO NORMAL DE NFE (TEXTO, ÁUDIO, SEGUNDA VIA, CANCELAMENTO)
    // =========================================================================

    public function enviarSegundaViaNfe($numero, $numeroNota, $funcionario, $whatsappSender)
    {
        $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⏳ A procurar a Nota Fiscal #{$numeroNota}... Aguarde um instante.");

        $venda = Venda::where('empresa_id', $funcionario->empresa_id)
            ->where(function ($q) use ($numeroNota) {
                $q->where('NfNumero', $numeroNota)->orWhere('id', $numeroNota);
            })->first();

        if (!$venda) {
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Não encontrei a Nota Fiscal #{$numeroNota} no sistema.");
            return response()->json(['status' => 'nao_encontrado']);
        }

        if (strtoupper($venda->estado) !== 'APROVADO') {
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠ A Nota Fiscal #{$numeroNota} consta como *{$venda->estado}* (ainda não foi aprovada na SEFAZ).");
            return response()->json(['status' => 'nao_aprovado']);
        }

        try {
            $this->enviarDocumentosFiscais($venda, $numero, $funcionario, true);
        } catch (\Exception $docEx) {
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Não foi possível preparar o DANFE/XML. Consulte o ERP ou tente novamente.");
        }

        return response()->json(['status' => 'processado_danfe']);
    }

    public function processarMensagemInicial($numero, $mensagem, $funcionario, $estadoId, $whatsappSender)
    {
        Log::info('--- NFE: PROCESSANDO MENSAGEM TEXTO ---');

        $prompt = "
        Você é um assistente de faturamento de um ERP corporativo configurável por empresa.
        Analise a seguinte mensagem do WhatsApp e extraia os dados rigorosamente no formato JSON.

        Regras de Extração:
        - empresa_emissora: Nome da filial mencionada. Vazio se não houver.
        - is_exportacao: Retorne true se a mensagem mencionar 'exportação', 'porto' ou dados de navio.
        - cliente_nome_pesquisa: Nome do cliente e sua cidade, se informada. Ignore números de controle.
        - transportadora_nome_pesquisa: Nome da transportadora, se houver.
        - operacao: 'venda' ou 'compra'. Padrão é 'venda'.
        - peso_bruto e peso_liquido: Extraia os números totais em quilos (kg). REMOVA os pontos de milhar e use ponto (.) apenas para as casas decimais (Ex: 31.882,5 -> 31882.50).
        - volume: Quantidade de volumes.
        - especie: Tipo de embalagem (ex: bag/fardo/solto).
        - placa: Placa principal, se houver.
        - observacao_extra: AQUI devem entrar obrigatoriamente as informações de Motorista, Cavalo, Carreta e afins. MUITO IMPORTANTE: Em vez de quebras de linha (\\n), use PONTO E VÍRGULA (;) para que o ERP possa pular a linha depois. Exemplo exigido: ;Motorista Nome Informado;Cavalo PlacaInformada;Carreta PlacaInformada;
        - itens: Se os pesos estiverem listados numa parte da mensagem e os valores por KG estiverem listados mais abaixo, faça o CRUZAMENTO EXATO pelo nome do produto!
          A quantidade e o valor_unitario devem vir sem ponto de milhar e usar ponto para decimais (Ex: 7.623,5 -> 7623.50).

        Mensagem do Operador: '{$mensagem}'

        Retorne APENAS um JSON válido nesta estrutura exata:
        {
            \"notas\": [
                {
                    \"empresa_emissora\": \"\",
                    \"is_exportacao\": false,
                    \"cliente_nome_pesquisa\": \"\",
                    \"transportadora_nome_pesquisa\": \"\",
                    \"operacao\": \"venda\",
                    \"peso_bruto\": 0.00,
                    \"peso_liquido\": 0.00,
                    \"volume\": 1,
                    \"especie\": \"\",
                    \"placa\": \"\",
                    \"observacao_extra\": \"\",
                    \"itens\": [
                        {
                            \"descricao_venda\": \"\",
                            \"quantidade\": 0.00,
                            \"valor_unitario\": 0.00
                        }
                    ]
                }
            ]
        }
        ";

        try {
            $responseStr = $this->consultarIaFiscal([
                ['text' => $prompt],
            ]);

            $respostaDecodificada = json_decode($responseStr, true);
            $jsonTexto = $respostaDecodificada['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $dadosRetorno = json_decode($jsonTexto, true);
            if (!is_array($dadosRetorno)) throw new \RuntimeException('IA retornou estrutura inválida.');

            if (!empty($dadosRetorno['notas']) && is_array($dadosRetorno['notas'])) {

                foreach($dadosRetorno['notas'] as $key => $nota) {

                    $termoCliente = trim($nota['cliente_nome_pesquisa']);
                    $clienteDb = Cliente::where('empresa_id', $funcionario->empresa_id)
                        ->where(function($q) use ($termoCliente) {
                            $q->where('razao_social', 'like', '%' . $termoCliente . '%')
                                ->orWhere('nome_fantasia', 'like', '%' . $termoCliente . '%');
                        })->first();

                    if (!$clienteDb) {
                        $palavras = explode(' ', $termoCliente);
                        if (count($palavras) > 1) {
                            $primeira = $palavras[0];
                            $ultima = end($palavras);
                            $clienteDb = Cliente::where('empresa_id', $funcionario->empresa_id)
                                ->where(function($q) use ($primeira, $ultima) {
                                    $q->where(function($q1) use ($primeira, $ultima) {
                                        $q1->where('razao_social', 'like', '%' . $primeira . '%')
                                            ->where('razao_social', 'like', '%' . $ultima . '%');
                                    })->orWhere(function($q2) use ($primeira, $ultima) {
                                        $q2->where('nome_fantasia', 'like', '%' . $primeira . '%')
                                            ->where('nome_fantasia', 'like', '%' . $ultima . '%');
                                    });
                                })->first();
                        }

                        if (!$clienteDb) {
                            $primeiraPalavraCli = $palavras[0];
                            $clienteDb = Cliente::where('empresa_id', $funcionario->empresa_id)
                                ->where(function($q) use ($primeiraPalavraCli) {
                                    $q->where('razao_social', 'like', '%' . $primeiraPalavraCli . '%')
                                        ->orWhere('nome_fantasia', 'like', '%' . $primeiraPalavraCli . '%');
                                })->first();
                        }
                    }

                    $dadosRetorno['notas'][$key]['cliente_id'] = $clienteDb ? $clienteDb->id : null;

                    foreach($nota['itens'] as $kItem => $itemIA) {
                        $descricaoOriginal = strtoupper(trim($itemIA['descricao_venda']));

                        if (str_contains($descricaoOriginal, 'ALUMINIO') || str_contains($descricaoOriginal, 'ALUMÍNIO') || str_contains($descricaoOriginal, 'PERFIL')) {
                            $termoBuscaERP = 'ALUMINIO';
                        } elseif (str_contains($descricaoOriginal, 'COBRE') || str_contains($descricaoOriginal, 'MEL') || (str_contains($descricaoOriginal, 'MISTO') && !str_contains($descricaoOriginal, 'PERFIL'))) {
                            $termoBuscaERP = 'COBRE';
                        } elseif (str_contains($descricaoOriginal, 'INOX')) {
                            $termoBuscaERP = 'INOX';
                        } else {
                            $termoBuscaERP = explode(' ', $descricaoOriginal)[0];
                        }

                        $produto = Produto::where('empresa_id', $funcionario->empresa_id)
                            ->where(function($q) use ($termoBuscaERP) {
                                $q->where('nome', 'like', '%' . $termoBuscaERP . '%')
                                    ->orWhere('nome', 'like', '%RESIDUOS%COBRE%')
                                    ->orWhere('nome', 'like', '%DESPERDICIOS%');
                            })
                            ->first();

                        if (!$produto) {
                            $produto = Produto::where('empresa_id', $funcionario->empresa_id)
                                ->where('nome', 'like', '%' . explode(' ', $descricaoOriginal)[0] . '%')
                                ->first();
                        }
                        if (!$produto) {
                            // Não inventar produto quando não existir correspondência no cadastro.
                        }

                        $nomeAjustado = $descricaoOriginal;
                        $produtoId = null;

                        if ($produto) {
                            $produtoId = $produto->id;
                            $nomeAjustado = strtoupper(trim($produto->nome));

                            if (str_contains($descricaoOriginal, 'MEL') && !str_contains($nomeAjustado, 'MEL')) {
                                $nomeAjustado .= " MEL";
                            } elseif (str_contains($descricaoOriginal, 'MISTO') && !str_contains($nomeAjustado, 'MISTO')) {
                                $nomeAjustado .= " MISTO";
                            } elseif (str_contains($descricaoOriginal, 'PERFIL') && !str_contains($nomeAjustado, 'PERFIL')) {
                                $nomeAjustado .= " PERFIL";
                            }
                        }

                        $dadosRetorno['notas'][$key]['itens'][$kItem]['produto_id'] = $produtoId;
                        $dadosRetorno['notas'][$key]['itens'][$kItem]['descricao_ajustada'] = $nomeAjustado;
                    }
                }

                Cache::put($this->chaveCache('nfe_dados', $numero), $dadosRetorno, 600);
                Cache::put($estadoId, 'AGUARDANDO_CONFIRMACAO', 600);

                // =========================================================================
                // EXIBIÇÃO DETALHADA NO RESUMO (COM PRIORIDADE PARA EXPORTAÇÃO)
                // =========================================================================
                $configNotaMatriz = ConfigNota::where('empresa_id', $funcionario->empresa_id)->first();
                $ufEmitente = strtoupper((string) ($configNotaMatriz->UF ?? ''));

                $msgResumo = "📋 *RESUMO DA(S) NOTA(S)*\nA IA preparou " . count($dadosRetorno['notas']) . " nota(s) fiscal(is):\n";

                foreach($dadosRetorno['notas'] as $index => $notaAtualizada) {
                    $clienteObj = $notaAtualizada['cliente_id'] ? Cliente::find($notaAtualizada['cliente_id']) : null;
                    $clienteNome = $clienteObj ? $clienteObj->razao_social : '⚠ CLIENTE NÃO ENCONTRADO (' . $notaAtualizada['cliente_nome_pesquisa'] . ')';

                    $cidadeCliente = 'Não informada';
                    $ufCliente = '';

                    if ($clienteObj) {
                        if (isset($clienteObj->cidade) && $clienteObj->cidade) {
                            $cidadeCliente = $clienteObj->cidade->nome ?? 'Não informada';
                            $ufCliente = strtoupper((string) ($clienteObj->cidade->uf ?? ''));
                        } elseif (!empty($clienteObj->cidade_id)) {
                            $cidReg = Cidade::find($clienteObj->cidade_id);
                            if ($cidReg) {
                                $cidadeCliente = $cidReg->nome;
                                $ufCliente = strtoupper($cidReg->uf);
                            }
                        }
                        if ($ufCliente === '' && !empty($clienteObj->estado)) {
                            $ufCliente = strtoupper($clienteObj->estado);
                        }
                    }

                    $isExport = (isset($notaAtualizada['is_exportacao']) && $notaAtualizada['is_exportacao'] === true) || $ufCliente === 'EX';
                    $foraDoEstado = ($ufCliente !== $ufEmitente);

                    // Descrição da natureza baseada na regra fiscal exata (Interno, Interestadual ou Exportação)
                    $nomeNatureza = "VENDA DE MERCADORIAS";
                    if ($isExport) {
                        $nomeNatureza = str_contains(strtoupper($mensagem), 'REMESSA') ? "REMESSA P EXPORTACAO" : "VENDA EXPORTACAO MERCADORIAS";
                    } elseif ($foraDoEstado) {
                        $nomeNatureza = "VENDA MERCADORIAS INTERESTADUAL";
                    }

                    // A busca agora é estrita e dinâmica para exibir o nome correto no resumo
                    $naturezaPrevista = NaturezaOperacao::where('empresa_id', $funcionario->empresa_id)
                        ->where(function($q) use ($nomeNatureza, $isExport, $foraDoEstado) {
                            $q->where('natureza', 'like', '%' . $nomeNatureza . '%');
                            if ($isExport) {
                                $q->orWhere('natureza', 'like', '%EXPORTACAO%');
                            } elseif ($foraDoEstado) {
                                $q->orWhere('natureza', 'like', '%INTERESTADUAL%');
                            } else {
                                $q->orWhere('natureza', 'like', '%MERCADORIA%');
                            }
                        })
                        ->first();

                    $naturezaExibir = $naturezaPrevista ? $naturezaPrevista->natureza : $nomeNatureza;

                    $numNota = $index + 1;
                    $tipoOperacao = $isExport ? 'EXPORTAÇÃO' : strtoupper($notaAtualizada['operacao']);
                    $nomeEmpresa = !empty($notaAtualizada['empresa_emissora']) ? strtoupper($notaAtualizada['empresa_emissora']) : 'MATRIZ';

                    $msgResumo .= "\n📄 *NOTA {$numNota}* ({$tipoOperacao}) - Emissor: {$nomeEmpresa}\n";
                    $msgResumo .= "👤 Cliente: {$clienteNome}\n";
                    $msgResumo .= "📍 Local: {$cidadeCliente} - {$ufCliente} " . ($isExport ? "(Exportação / Exterior)" : ($foraDoEstado ? "(Interestadual)" : "(Interno)")) . "\n";
                    $msgResumo .= "⚙️ Natureza: {$naturezaExibir}\n";

                    if (!empty($notaAtualizada['transportadora_nome_pesquisa'])) {
                        $msgResumo .= "🚚 Transp: " . strtoupper($notaAtualizada['transportadora_nome_pesquisa']) . "\n";
                    }
                    if (!empty($notaAtualizada['peso_liquido'])) {
                        $msgResumo .= "⚖ Peso Líquido: " . number_format($notaAtualizada['peso_liquido'], 2, ',', '.') . " kg\n";
                    }

                    if (!empty($notaAtualizada['observacao_extra'])) {
                        $msgResumo .= "\n📝 *Obs. Transporte:* " . str_replace(';', "\n", trim($notaAtualizada['observacao_extra'], ';')) . "\n";
                    }

                    $valorTotal = 0;
                    $msgResumo .= "\n*Itens:*\n";
                    foreach($notaAtualizada['itens'] as $item) {
                        $sub = $item['quantidade'] * $item['valor_unitario'];
                        $valorTotal += $sub;
                        $nomeExibir = $item['descricao_ajustada'] ?? $item['descricao_venda'] ?? 'Produto';
                        $msgResumo .= "📦 " . $nomeExibir . "\n   " . number_format($item['quantidade'], 2, ',', '.') . " kg | R$ " . number_format($item['valor_unitario'], 2, ',', '.') . "\n";
                    }
                    $msgResumo .= "\n💰 *Total:* R$ " . number_format($valorTotal, 2, ',', '.') . "\n";
                }

                $msgResumo .= "\nConfirma a gravação e transmissão para a SEFAZ?\n*1* - SIM\n*2* - NÃO (Cancelar)";

                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, $msgResumo);
                return response()->json(['status' => 'sucesso']);
            }

            return response()->json(['status' => 'erro_dados']);

        } catch (\Exception $e) {
            Log::error("Exceção IA: " . $e->getMessage());
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠ Falha ao interpretar solicitação fiscal. Confira os dados e tente novamente.");
            return response()->json(['status' => 'erro']);
        }
    }

    public function processarConfirmacaoFinal($numero, $mensagem, $funcionario, $estadoId, $whatsappSender)
    {
        if (trim($mensagem) == '1') {
            $confirmacaoLockName = 'wa:nfe:confirmacao:' . md5($this->empresaFiscal . ':' . $this->instanciaFiscal . ':' . $numero);
            $confirmacaoLock = Cache::lock($confirmacaoLockName, 180);

            try {
                if (!$confirmacaoLock->get()) {
                    return response()->json(['status' => 'processamento_em_andamento']);
                }
            } catch (\Throwable $lockEx) {
                Log::warning('NF-e WhatsApp: lock de confirmação indisponível.', ['erro' => $lockEx->getMessage()]);
                return response()->json(['status' => 'lock_indisponivel']);
            }

            try {
                $dadosIA = Cache::get($this->chaveCache('nfe_dados', $numero));

                if (!$dadosIA || !isset($dadosIA['notas'])) {
                    $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Os dados da nota expiraram. Por favor, envie os dados novamente.");
                    Cache::forget($estadoId);
                    return response()->json(['status' => 'expirado']);
                }

                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "🚀 A processar e a transmitir as notas para a SEFAZ... Aguarde.");

                $usuarioIdParaVenda = $funcionario->usuario_id;
                $configNotaMatriz = ConfigNota::where('empresa_id', $funcionario->empresa_id)->first();
                $erros = [];
                $sucessos = 0;

                foreach ($dadosIA['notas'] as $index => $nota) {
                    try {
                        DB::beginTransaction();

                        $clienteId = $nota['cliente_id'] ?? null;
                        if (!$clienteId && !empty($nota['cliente_nome_pesquisa'])) {
                            $termoCliente = trim($nota['cliente_nome_pesquisa']);
                            $clienteDb = Cliente::where('empresa_id', $funcionario->empresa_id)
                                ->where(function($q) use ($termoCliente) {
                                    $q->where('razao_social', 'like', '%' . $termoCliente . '%')
                                        ->orWhere('nome_fantasia', 'like', '%' . $termoCliente . '%');
                                })->first();

                            if (!$clienteDb) {
                                $palavras = explode(' ', $termoCliente);
                                if (count($palavras) > 1) {
                                    $primeira = $palavras[0];
                                    $ultima = end($palavras);
                                    $clienteDb = Cliente::where('empresa_id', $funcionario->empresa_id)
                                        ->where(function($q) use ($primeira, $ultima) {
                                            $q->where(function($q1) use ($primeira, $ultima) {
                                                $q1->where('razao_social', 'like', '%' . $primeira . '%')
                                                    ->where('razao_social', 'like', '%' . $ultima . '%');
                                            })->orWhere(function($q2) use ($primeira, $ultima) {
                                                $q2->where('nome_fantasia', 'like', '%' . $primeira . '%')
                                                    ->where('nome_fantasia', 'like', '%' . $ultima . '%');
                                            });
                                        })->first();
                                }

                                if (!$clienteDb) {
                                    $primeiraPalavraCli = $palavras[0];
                                    $clienteDb = Cliente::where('empresa_id', $funcionario->empresa_id)
                                        ->where(function($q) use ($primeiraPalavraCli) {
                                            $q->where('razao_social', 'like', '%' . $primeiraPalavraCli . '%')
                                                ->orWhere('nome_fantasia', 'like', '%' . $primeiraPalavraCli . '%');
                                        })->first();
                                }
                            }
                            $clienteId = $clienteDb ? $clienteDb->id : null;
                        }

                        if (!$clienteId) {
                            throw new \Exception("Cliente '{$nota['cliente_nome_pesquisa']}' não foi encontrado no ERP.");
                        }

                        $nota['cliente_id'] = $clienteId;
                        if (empty($nota['itens']) || !is_array($nota['itens'])) throw new \RuntimeException('Nota sem itens válidos.');
                        $clienteObj = Cliente::where('empresa_id', $funcionario->empresa_id)->findOrFail($nota['cliente_id']);

                        // CORREÇÃO FISCAL SEFAZ: Consumidor final para não contribuintes
                        $isConsumidorFinal = $clienteObj->consumidor_final;
                        if ($clienteObj->contribuinte == 0 || strtoupper(trim($clienteObj->ie_rg)) == 'ISENTO' || empty(trim($clienteObj->ie_rg))) {
                            $isConsumidorFinal = 1;
                        }

                        $filialId = null;
                        $numeroDiferimento = $configNotaMatriz ? $configNotaMatriz->numero_diferimento : '';

                        $temFiliais = DB::table('filials')->where('empresa_id', $funcionario->empresa_id)->exists();

                        if ($temFiliais && !empty($nota['empresa_emissora'])) {
                            $filialEncontrada = DB::table('filials')
                                ->where('empresa_id', $funcionario->empresa_id)
                                ->where(function($query) use ($nota) {
                                    $query->where('descricao', 'like', '%' . $nota['empresa_emissora'] . '%')
                                        ->orWhere('razao_social', 'like', '%' . $nota['empresa_emissora'] . '%');
                                })->first();

                            if ($filialEncontrada) {
                                $filialId = $filialEncontrada->id;
                                if(isset($filialEncontrada->numero_diferimento) && !empty($filialEncontrada->numero_diferimento)) {
                                    $numeroDiferimento = $filialEncontrada->numero_diferimento;
                                }
                            }
                        }

                        $transportadoraId = null;
                        if (!empty($nota['transportadora_nome_pesquisa'])) {
                            $termoTransp = trim($nota['transportadora_nome_pesquisa']);
                            $transpDb = Transportadora::where('empresa_id', $funcionario->empresa_id)
                                ->where('razao_social', 'like', '%' . $termoTransp . '%')
                                ->first();

                            if (!$transpDb) {
                                $primeiraPalavra = explode(' ', $termoTransp)[0];
                                if (strlen($primeiraPalavra) >= 3) {
                                    $transpDb = Transportadora::where('empresa_id', $funcionario->empresa_id)
                                        ->where('razao_social', 'like', '%' . $primeiraPalavra . '%')
                                        ->first();
                                }
                            }
                            $transportadoraId = $transpDb ? $transpDb->id : null;
                        }

                        // ==========================================
                        // SELEÇÃO INTELIGENTE DE NATUREZA (PRIORIDADE EXPORTAÇÃO)
                        // ==========================================
                        $ufEmitente = strtoupper((string) ($configNotaMatriz->UF ?? ''));
                        if ($filialId) {
                            $filialObj = DB::table('filials')->where('empresa_id', $funcionario->empresa_id)->where('id', $filialId)->first();
                            if ($filialObj && isset($filialObj->UF)) {
                                $ufEmitente = $filialObj->UF;
                            }
                        } elseif ($configNotaMatriz && isset($configNotaMatriz->UF)) {
                            $ufEmitente = $configNotaMatriz->UF;
                        }

                        $ufCliente = '';
                        if ($clienteObj) {
                            if (isset($clienteObj->cidade) && $clienteObj->cidade) {
                                $ufCliente = strtoupper((string) ($clienteObj->cidade->uf ?? ''));
                            } elseif (!empty($clienteObj->cidade_id)) {
                                $cidReg = Cidade::find($clienteObj->cidade_id);
                                if ($cidReg) $ufCliente = strtoupper($cidReg->uf);
                            }
                            if ($ufCliente === '' && !empty($clienteObj->estado)) {
                                $ufCliente = strtoupper($clienteObj->estado);
                            }
                        }

                        if ($ufEmitente === '' || $ufCliente === '') { throw new \RuntimeException('UF do emitente/destinatário não cadastrada.'); }
                        $isExport = (isset($nota['is_exportacao']) && $nota['is_exportacao'] === true) || $ufCliente === 'EX';

                        $nomeNatureza = "VENDA DE MERCADORIAS";
                        if ($isExport) {
                            $nomeNatureza = str_contains(strtoupper(json_encode($nota)), 'REMESSA') ? "REMESSA P EXPORTACAO" : "VENDA EXPORTACAO MERCADORIAS";
                        } elseif ($ufCliente !== $ufEmitente) {
                            $nomeNatureza = "VENDA MERCADORIAS INTERESTADUAL";
                        }

                        $naturezaOp = NaturezaOperacao::where('empresa_id', $funcionario->empresa_id)
                            ->where(function($q) use ($nomeNatureza, $isExport, $ufCliente, $ufEmitente) {
                                $q->where('natureza', 'like', '%' . $nomeNatureza . '%')
                                    ->orWhere('natureza', 'like', $isExport ? '%EXPORTACAO%' : ($ufCliente !== $ufEmitente ? '%INTERESTADUAL%' : '%MERCADORIA%'));
                            })
                            ->first();

                        if (!$naturezaOp) {
                            $naturezaOp = NaturezaOperacao::where('empresa_id', $funcionario->empresa_id)->first();
                        }

                        $naturezaId = $naturezaOp ? $naturezaOp->id : ($configNotaMatriz ? $configNotaMatriz->nat_op_padrao : null);
                        if (!$naturezaId || !NaturezaOperacao::where('empresa_id', $funcionario->empresa_id)->whereKey($naturezaId)->exists()) {
                            throw new \RuntimeException('Natureza de operação fiscal não validada.');
                        }

                        $pesoLiquido = !empty($nota['peso_liquido']) ? $nota['peso_liquido'] : 0;
                        $pesoBruto = !empty($nota['peso_bruto']) ? $nota['peso_bruto'] : 0;
                        $qtdVolumes = !empty($nota['volume']) ? $nota['volume'] : 1;

                        if ($pesoLiquido <= 0) {
                            foreach ($nota['itens'] as $itemIA) {
                                $pesoLiquido += $itemIA['quantidade'];
                            }
                        }
                        if ($pesoBruto <= 0) $pesoBruto = $pesoLiquido;

                        $frete = Frete::create([
                            'valor' => 0,
                            'tipo' => 9,
                            'peso_liquido' => $pesoLiquido,
                            'peso_bruto' => $pesoBruto,
                            'qtdVolumes' => $qtdVolumes,
                            'numeracaoVolumes' => '1',
                            'especie' => $nota['especie'] ?? '',
                            'placa' => $nota['placa'] ?? '',
                        ]);

                        // ==========================================
                        // MOTOR DE OBSERVAÇÕES E QUEBRAS POR PONTO E VÍRGULA
                        // ==========================================
                        $dadosAdicionaisFiscal = "";

                        if ($naturezaOp && isset($naturezaOp->usar_info_cliente) && (int)$naturezaOp->usar_info_cliente === 1) {
                            if (!empty($clienteObj->informacao_nfe)) {
                                $dadosAdicionaisFiscal .= trim($clienteObj->informacao_nfe) . "\n";
                            }
                        }

                        if (!empty($nota['observacao_extra'])) {
                            $obsTratada = str_replace([';', '.'], "\n", trim($nota['observacao_extra']));
                            $linhasObs = array_filter(explode("\n", $obsTratada));
                            foreach($linhasObs as $linha) {
                                $dadosAdicionaisFiscal .= trim($linha) . "\n";
                            }
                        }

                        if (!empty($nota['placa'])) {
                            $dadosAdicionaisFiscal .= "Placa do Veiculo: " . strtoupper(trim($nota['placa'])) . "\n";
                        }

                        if ($naturezaOp && isset($naturezaOp->usar_diferimento) && (int)$naturezaOp->usar_diferimento === 1) {
                            $diferimentoFinal = !empty($numeroDiferimento) ? $numeroDiferimento : "NÃO CADASTRADO";
                            $dadosAdicionaisFiscal .= "DIFERIMENTO " . $diferimentoFinal . " e Venda com suspensao das contribuicoes para PISCofins nos termos do artigo 48 da Lei 111962002\n";
                        } elseif ($isExport) {
                            $dadosAdicionaisFiscal .= "Operação de exportação; verificar enquadramento tributário no ERP.\n";
                        } elseif ($ufCliente !== $ufEmitente) {
                            $dadosAdicionaisFiscal .= "Operação interestadual - tributação conforme cadastro fiscal.\n";
                        }

                        $dadosAdicionaisFiscal = trim(preg_replace("/[\r\n]+/", "\n", $dadosAdicionaisFiscal));

                        $venda = Venda::create([
                            'empresa_id'            => $funcionario->empresa_id,
                            'cliente_id'            => $nota['cliente_id'],
                            'usuario_id'            => $usuarioIdParaVenda,
                            'filial_id'             => $filialId,
                            'natureza_id'           => $naturezaId,
                            'transportadora_id'     => $transportadoraId,
                            'frete_id'              => $frete->id,
                            'observacao'            => $dadosAdicionaisFiscal,
                            'valor_total'           => 0,
                            'desconto'              => 0.00,
                            'acrescimo'             => 0.00,
                            'estado'                => 'DISPONIVEL',
                            'tipo_pagamento'        => '17',
                            'forma_pagamento'       => 'a_vista',
                        ]);

                        $clienteObj->consumidor_final = $isConsumidorFinal;
                        $valorTotal = 0;

                        foreach ($nota['itens'] as $itemIA) {
                            $produtoId = $itemIA['produto_id'] ?? null;
                            if ($produtoId && !Produto::where('empresa_id', $funcionario->empresa_id)->whereKey($produtoId)->exists()) {
                                throw new \RuntimeException('Produto não pertence à empresa.');
                            }
                            if ((float) ($itemIA['quantidade'] ?? 0) <= 0 || (float) ($itemIA['valor_unitario'] ?? 0) <= 0) {
                                throw new \RuntimeException('Quantidade ou valor unitário inválidos.');
                            }
                            $nomeParaNota = $itemIA['descricao_ajustada'] ?? strtoupper(trim($itemIA['descricao_venda']));

                            if (!$produtoId) {
                                throw new \Exception("Nenhum produto cadastrado no ERP.");
                            }

                            $subtotal = $itemIA['quantidade'] * $itemIA['valor_unitario'];
                            $valorTotal += $subtotal;

                            ItemVenda::create([
                                'venda_id'     => $venda->id,
                                'produto_id'   => $produtoId,
                                'quantidade'   => $itemIA['quantidade'],
                                'valor'        => $itemIA['valor_unitario'],
                                'subtotal'     => $subtotal,
                                'empresa_id'   => $funcionario->empresa_id,
                                'produto_nome' => $nomeParaNota,
                            ]);
                        }

                        $venda->update(['valor_total' => $valorTotal]);

                        DB::commit();

                        $requestNf = new \Illuminate\Http\Request();
                        $requestNf->merge([
                            'vendaId' => $venda->id,
                            'empresa_id' => $funcionario->empresa_id,
                            'consumidor_final' => $isConsumidorFinal
                        ]);

                        $this->forcarLoginMestre($funcionario->empresa_id, $usuarioIdParaVenda);

                        try {
                            $nfController = app(\App\Http\Controllers\NotaFiscalController::class);

                            $definirEmpresa = function (int $empresa) { $this->empresa_id = $empresa; };
                            $definirEmpresa->call($nfController, (int) $funcionario->empresa_id);

                            $respostaTransmissao = $nfController->gerarNf($requestNf);
                            $venda->refresh();

                            if (strtoupper($venda->estado) !== 'APROVADO') {
                                $erroMsg = "Rejeição SEFAZ ou erro estrutural.";
                                $conteudo = $respostaTransmissao->getContent();
                                $dadosRetorno = json_decode($conteudo, true);

                                if (is_array($dadosRetorno)) {
                                    if (isset($dadosRetorno['erros_xml'])) {
                                        $errosStr = is_array($dadosRetorno['erros_xml']) ? implode(" | ", $dadosRetorno['erros_xml']) : $dadosRetorno['erros_xml'];
                                        $erroMsg = "Erro no XML: " . $errosStr;
                                    } elseif (isset($dadosRetorno['motivo'])) {
                                        $erroMsg = "SEFAZ: " . $dadosRetorno['motivo'];
                                    } else {
                                        $erroMsg = "Erro: " . strip_tags($conteudo);
                                    }
                                } elseif (is_string($conteudo) && !empty($conteudo)) {
                                    $erroMsg = strip_tags($conteudo);
                                }

                                throw new \Exception($erroMsg);
                            }

                            $sucessos++;
                            $numeroNotaFiscal = $venda->NfNumero ?? $venda->id;

                            try {
                                $this->enviarDocumentosFiscais($venda, $numero, $funcionario);
                            } catch (\Exception $docEx) {
                                $erros[] = "Nota {$numeroNotaFiscal} autorizada, mas falhou ao enviar anexos: " . $docEx->getMessage();
                            }

                        } catch (\Exception $transEx) {
                            $erros[] = "Nota " . ($index + 1) . " Rejeitada SEFAZ: " . $transEx->getMessage();
                        }
                    } catch (\Throwable $itemErro) {
                        if (DB::transactionLevel() > 0) DB::rollBack();
                        $erros[] = 'Nota ' . ($index + 1) . ': erro de validação ou persistência.';
                        Log::error('NF-e WhatsApp: falha por nota.', [
                            'empresa_id' => $funcionario->empresa_id,
                            'indice' => $index,
                            'erro' => $itemErro->getMessage(),
                        ]);
                    }
                }

                Cache::forget($this->chaveCache('nfe_dados', $numero));
                Cache::forget($estadoId);

                $msgFinal = "✅ *RESULTADO DA EMISSÃO:*\n\n✅ {$sucessos} Nota(s) processada(s) com sucesso!\n";
                if (count($erros) > 0) $msgFinal .= "\n❌ *Erros Encontrados:*\n- " . implode("\n- ", $erros) . "\n";

                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, $msgFinal);
                return response()->json(['status' => 'processado']);
            } finally {
                if ($confirmacaoLock) {
                    try { $confirmacaoLock->release(); } catch (\Throwable $releaseEx) {}
                }
            }
        } else {
            Cache::forget($this->chaveCache('nfe_dados', $numero));
            Cache::forget($estadoId);
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Operação cancelada.");
            return response()->json(['status' => 'cancelado']);
        }
    }

    public function processarMensagemAudio($numero, $urlAudio, $funcionario, $estadoId, $whatsappSender)
    {
        // Mantém o mecanismo do parceiro: Gemini interpreta diretamente
        // o áudio multimodal e retorna o JSON fiscal, sem transcritor.
        $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "🎧 Processando áudio fiscal...");
        try {
            $audioBytes = $this->obterMidiaRecebida('audio', (string) $urlAudio);
            if ($audioBytes === '') {
                throw new \RuntimeException('Áudio indisponível no evento da Connect|API.');
            }

            $mime = (string) (
            data_get($this->payloadEvento, 'data.message.audioMessage.mimetype')
                ?: 'audio/ogg'
            );
            $mime = trim(explode(';', $mime)[0]);

            // O prompt e as regras de extração são as da versão do parceiro.
            $prompt = "Extraia dados de faturamento para JSON. Regras: Converta toneladas para quilos. itens (Cobre mel) e valor. cliente_nome_pesquisa: extrair do áudio. observacao_extra: Use PONTO E VÍRGULA (;) para pular linha. Ex: ;Motorista André;Placa OVD9253; operacao: 'venda'. Retorne JSON exato.";

            $res = $this->consultarIaFiscal([
                ['text' => $prompt],
                ['inline_data' => [
                    'mime_type' => $mime,
                    'data' => base64_encode($audioBytes),
                ]],
            ]);
            $respostaIa = json_decode($res, true);
            $json = json_decode((string) ($respostaIa['candidates'][0]['content']['parts'][0]['text'] ?? '{}'), true);

            if (is_array($json) && !empty($json['notas']) && is_array($json['notas'])) {
                $configNotaMatriz = ConfigNota::where('empresa_id', $funcionario->empresa_id)->first();
                $ufEmitente = $configNotaMatriz ? $configNotaMatriz->UF : 'BA';

                foreach($json['notas'] as $k => $nota) {
                    $c = Cliente::where('empresa_id', $funcionario->empresa_id)->where('razao_social', 'like', "%{$nota['cliente_nome_pesquisa']}%")->first();
                    $json['notas'][$k]['cliente_id'] = $c ? $c->id : null;
                    foreach($nota['itens'] as $ki => $item) {
                        $desc = strtoupper($item['descricao_venda']);
                        $term = str_contains($desc, 'MEL') || str_contains($desc, 'MISTO') || str_contains($desc, 'COBRE') ? 'COBRE' : explode(' ', $desc)[0];
                        $prod = Produto::where('empresa_id', $funcionario->empresa_id)->where('nome', 'like', "%$term%")->first() ?: Produto::where('empresa_id', $funcionario->empresa_id)->first();

                        $nm = $desc;
                        if ($prod) {
                            $nm = strtoupper(trim($prod->nome));
                            if (str_contains($desc, 'MEL') && !str_contains($nm, 'MEL')) $nm .= " MEL";
                            elseif (str_contains($desc, 'MISTO') && !str_contains($nm, 'MISTO')) $nm .= " MISTO";
                        }
                        $json['notas'][$k]['itens'][$ki]['produto_id'] = $prod ? $prod->id : null;
                        $json['notas'][$k]['itens'][$ki]['descricao_ajustada'] = $nm;
                    }
                }
                Cache::put($this->chaveCache('nfe_dados', $numero), $json, 600);
                Cache::put($estadoId, 'AGUARDANDO_CONFIRMACAO', 600);

                $msg = "📋 *RESUMO DA(S) NOTA(S) (via Áudio)*\n";
                foreach($json['notas'] as $i => $n) {
                    $cObj = $n['cliente_id'] ? Cliente::find($n['cliente_id']) : null;
                    $cn = $cObj ? $cObj->razao_social : '⚠️ CLIENTE NÃO ENCONTRADO';

                    $ufCli = 'BA';
                    $cidCli = 'Não informada';
                    if ($cObj) {
                        if (isset($cObj->cidade) && $cObj->cidade) {
                            $cidCli = $cObj->cidade->nome ?? 'Não informada';
                            $ufCli = strtoupper($cObj->cidade->uf ?? 'BA');
                        } elseif (!empty($cObj->cidade_id)) {
                            $cidReg = Cidade::find($cObj->cidade_id);
                            if ($cidReg) {
                                $cidCli = $cidReg->nome;
                                $ufCli = strtoupper($cidReg->uf);
                            }
                        }
                        if ($ufCli === 'BA' && !empty($cObj->estado)) {
                            $ufCli = strtoupper($cObj->estado);
                        }
                    }

                    $isExport = (isset($n['is_exportacao']) && $n['is_exportacao'] === true) || $ufCli === 'EX';
                    $fora = ($ufCli !== $ufEmitente);

                    $nomNat = "VENDA DE MERCADORIAS";
                    if ($isExport) {
                        $nomNat = "VENDA EXPORTACAO MERCADORIAS";
                    } elseif ($fora) {
                        $nomNat = "VENDA MERCADORIAS INTERESTADUAL";
                    }

                    $natObj = NaturezaOperacao::where('empresa_id', $funcionario->empresa_id)->where('natureza', 'like', "%$nomNat%")->first();
                    $natExibir = $natObj ? $natObj->natureza : $nomNat;

                    $msg .= "\n📄 *NOTA ".($i+1)."* - Cliente: $cn\n";
                    $msg .= "📍 Local: $cidCli - $ufCli " . ($isExport ? "(Exportação)" : ($fora ? "(Interestadual)" : "(Interno)")) . "\n";
                    $msg .= "⚙️ Natureza: $natExibir\n";

                    if (!empty($n['observacao_extra'])) $msg .= "\n📝 *Obs:* " . str_replace(';', "\n", trim($n['observacao_extra'], ';')) . "\n";
                    $msg .= "*Itens:*\n";
                    $tot = 0;
                    foreach($n['itens'] as $it) {
                        $tot += $it['quantidade'] * $it['valor_unitario'];
                        $msg .= "📦 ".($it['descricao_ajustada'] ?? 'Prod')."\n   {$it['quantidade']} kg | R$ {$it['valor_unitario']}\n";
                    }
                    $msg .= "💰 *Total:* R$ $tot\n";
                }
                $msg .= "\nConfirma a transmissão?\n*1* - SIM\n*2* - NÃO";
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, $msg);
                return response()->json(['status' => 'sucesso']);
            }
            throw new \Exception("Nenhum item extraído.");
        } catch (\Exception $e) {
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠ Erro: " . $e->getMessage());
            return response()->json(['status' => 'erro']);
        }
    }

    private function forcarLoginMestre(int $empresaId, $usuarioId = null)
    {
        $usuarioId = (int) $usuarioId;
        $usuario = Usuario::where('id', $usuarioId)->where('empresa_id', $empresaId)->first();
        if (!$usuario || !$this->usuarioAutorizado($usuario, null)) {
            throw new \RuntimeException('Usuário fiscal não autorizado para a empresa.');
        }
        session()->put('user_logged', [
            'empresa' => $empresaId,
            'id' => $usuarioId,
            'nome' => (string) $usuario->nome,
            'adm' => (int) ($usuario->adm ?? 0),
            'log_id' => 0,
        ]);
        Auth::setUser($usuario);
        return $usuarioId;
    }

    private function gerarDocumentoDireto(Venda $venda, string $tipo, $funcionario): string
    {
        $chave = preg_replace('/\D+/', '', (string) ($venda->chave ?? ''));
        if (strlen($chave) !== 44 || (int) $venda->empresa_id !== $this->empresaFiscal) {
            throw new \RuntimeException('Chave inválida ou empresa divergente.');
        }

        $caminhoXml = public_path("xml_nfe/{$chave}.xml");
        if (!file_exists($caminhoXml)) {
            $caminhoXml = storage_path("app/public/xml_nfe/{$chave}.xml");
            if (!file_exists($caminhoXml)) throw new \Exception("O XML original não foi encontrado.");
        }

        $xmlContent = file_get_contents($caminhoXml);
        if (empty($xmlContent)) throw new \Exception("O XML está vazio.");
        if ($tipo === 'xml') return $xmlContent;

        if ($tipo === 'pdf') {
            $config = ConfigNota::where('empresa_id', $funcionario->empresa_id)->first();
            $logo = null;
            try {
                if ($config && !empty($config->logo)) $logo = \App\Support\FiscalLogoHelper::buildDataUri($config->logo);
                if (!empty($venda->filial_id)) {
                    $filial = DB::table('filials')->where('empresa_id', $funcionario->empresa_id)->where('id', $venda->filial_id)->first();
                    if ($filial && !empty($filial->logo)) $logo = \App\Support\FiscalLogoHelper::buildDataUri($filial->logo);
                }
            } catch (\Exception $e) {}

            try {
                $danfe = new \App\Services\CustomDanfe($xmlContent);
                if ($config && isset($config->casas_decimais)) $danfe->setVUnComCasasDec($config->casas_decimais);
                if ($logo) $danfe->logoParameters($logo, 'L');
                $pdf = $danfe->render();
                if (empty($pdf)) throw new \Exception("A biblioteca devolveu PDF em branco.");
                return $pdf;
            } catch (\Exception $e) {
                throw new \Exception("Falha no PDF: " . $e->getMessage());
            }
        }
        throw new \Exception("Tipo ($tipo) desconhecido.");
    }

    /** Convivência com ponto: funcionário precisa pertencer ao tenant do evento. */
    private function localizarFuncionarioAutorizado(int $empresaId, string $jid, string $numero): ?Funcionario
    {
        if (str_ends_with($jid, '@lid')) {
            $lid = explode('@', $jid)[0];
            return Funcionario::where('empresa_id', $empresaId)
                ->where(function ($q) use ($lid, $jid) {
                    $q->where('whatsapp_id', $lid)->orWhere('whatsapp_id', $jid);
                })->first();
        }
        $digits = preg_replace('/\D+/', '', $numero);
        if ($digits === '' || strlen($digits) < 10) return null;
        $matches = Funcionario::where('empresa_id', $empresaId)
            ->whereNotNull('usuario_id')
            ->get()
            ->filter(function ($f) use ($digits) {
                foreach ([$f->celular, $f->telefone] as $field) {
                    if (!$field) continue;
                    $normalized = app(ConnectApiMessageService::class)->normalizeNumber((string) $field);
                    if ($normalized === $digits) return true;
                }
                return false;
            });
        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function usuarioAutorizado(Usuario $usuario, ?Funcionario $funcionario, ?int $empresaContexto = null): bool
    {
        if ((int) $usuario->empresa_id !== ($empresaContexto ?? $this->empresaFiscal) || !(bool) ($usuario->ativo ?? true)) {
            return false;
        }
        if (Schema::hasColumn('usuarios', 'permissao_nfe_whatsapp')) {
            return (bool) $usuario->permissao_nfe_whatsapp;
        }
        if ($funcionario && Schema::hasColumn('funcionarios', 'permissao_nfe_whatsapp')) {
            return (bool) $funcionario->permissao_nfe_whatsapp;
        }
        // Preserva permissão fiscal explícita; não autoriza automaticamente administradores.
        // Em instalações antigas, aplicar a migration que adiciona a coluna.
        return false;
    }

    private function chaveEstado(int $empresa, int $instancia, string $numero): string
    {
        return 'wa:fiscal:' . $empresa . ':' . $instancia . ':' . hash('sha256', $numero) . ':estado';
    }

    private function chaveCache(string $nome, string $numero): string
    {
        return 'wa:fiscal:' . $this->empresaFiscal . ':' . $this->instanciaFiscal
            . ':' . hash('sha256', $numero) . ':' . $nome;
    }

    private function nomeAplicacao(): string
    {
        return trim((string) config('app.name')) ?: 'Aplicação';
    }

    private function identificacaoAplicacao(): string
    {
        $nome = $this->nomeAplicacao();
        try {
            $versao = trim((string) app(\App\Services\AppVersionService::class)->resolveInstalledVersion());
            return $versao !== '' ? $nome . ' v' . $versao : $nome;
        } catch (\Throwable $e) {
            return $nome;
        }
    }

    /** Preserva a assinatura do envio original, usando a Connect|API. */
    private function enviarRespostaWhatsApp($empresaId, $numero, $mensagem): bool
    {
        try {
            $ret = app(ConnectApiMessageService::class)->sendText((int) $empresaId, (string) $numero, (string) $mensagem);
            if (!($ret['success'] ?? false)) {
                Log::warning('NF-e WhatsApp: envio de texto não confirmado pela Connect|API.', [
                    'empresa_id' => $empresaId,
                    'erro' => $ret['error'] ?? $ret['message'] ?? 'Resposta negativa do serviço',
                ]);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            Log::error('NF-e WhatsApp: erro de envio na Connect|API.', ['empresa_id' => $empresaId, 'erro' => $e->getMessage()]);
            return false;
        }
    }

    /** Arquivo local privado -> Connect|API, sem URL pública e sem provider legado. */
    private function enviarDocumentosFiscais(Venda $venda, string $numero, $funcionario, bool $reenvioSolicitado = false): void
    {
        $empresaId = (int) $funcionario->empresa_id;
        $vendaId = (int) $venda->id;
        if ($empresaId !== $this->empresaFiscal || (int) $venda->empresa_id !== $empresaId) {
            throw new \RuntimeException('NF-e não pertence à empresa atual.');
        }
        $numeroNfe = (string) ($venda->NfNumero ?: $vendaId);
        foreach (['pdf' => 'DANFE', 'xml' => 'XML'] as $tipo => $descricao) {
            $chave = $this->chaveCache('documento:' . $vendaId . ':' . $tipo, $numero);
            $lock = Cache::lock('lock:' . $chave, 90);
            if (!$lock->get()) {
                Log::info('NF-e WhatsApp: arquivo em processamento por outra execução.', ['venda_id' => $vendaId, 'tipo' => $tipo]);
                continue;
            }
            try {
                if (!$reenvioSolicitado && $this->documentoJaEnviado($empresaId, $numero, $vendaId, $tipo, $chave)) continue;
                $content = $this->gerarDocumentoDireto($venda, $tipo, $funcionario);
                if ($content === '' || ($tipo === 'pdf' && !str_starts_with(ltrim($content), '%PDF')) ||
                    ($tipo === 'xml' && !str_contains(ltrim(substr($content, 0, 400)), '<'))) {
                    throw new \RuntimeException('Documento fiscal gerado inválido: ' . $tipo);
                }
                $dir = storage_path('app/whatsapp_nfe/' . $empresaId . '/' . $vendaId);
                if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
                    throw new \RuntimeException('Diretório privado de documentos indisponível.');
                }
                $arquivo = $dir . DIRECTORY_SEPARATOR . $descricao . '_' . preg_replace('/[^A-Za-z0-9_-]/', '', $numeroNfe) . '.' . $tipo;
                if (file_put_contents($arquivo, $content, LOCK_EX) === false) throw new \RuntimeException('Falha ao gravar arquivo privado.');
                @chmod($arquivo, 0600);
                $res = app(ConnectApiMessageService::class)->sendMedia(
                    $empresaId, $numero, $arquivo, "{$descricao} NF-e #{$numeroNfe}"
                );
                if (!($res['success'] ?? false)) {
                    throw new \RuntimeException('Connect|API recusou anexo: ' . ($res['error'] ?? 'sem confirmação'));
                }
                $this->registrarDocumentoEnviado($empresaId, $numero, $vendaId, $tipo, $chave, $res);
            } finally {
                try { $lock->release(); } catch (\Throwable $e) {}
            }
        }
    }

    private function documentoJaEnviado(int $empresa, string $numero, int $venda, string $tipo, string $key): bool
    {
        if (Schema::hasTable('whatsapp_nfe_documentos_envios')) {
            return DB::table('whatsapp_nfe_documentos_envios')->where('empresa_id', $empresa)
                ->where('venda_id', $venda)->where('numero_destino', $numero)->where('tipo', $tipo)
                ->whereNotNull('enviado_em')->exists();
        }
        return (bool) Cache::get($key, false);
    }

    private function registrarDocumentoEnviado(int $empresa, string $numero, int $venda, string $tipo, string $key, array $res): void
    {
        if (Schema::hasTable('whatsapp_nfe_documentos_envios')) {
            DB::table('whatsapp_nfe_documentos_envios')->updateOrInsert(
                ['empresa_id' => $empresa, 'venda_id' => $venda, 'numero_destino' => $numero, 'tipo' => $tipo],
                ['provider_message_id' => (string) ($res['message_id'] ?? data_get($res, 'data.key.id', '')),
                    'enviado_em' => now(), 'updated_at' => now()]
            );
        }
        Cache::put($key, true, now()->addDays(30));
    }

    /**
     * Obtém mídia usando a mesma instância que recebeu o webhook autenticado.
     * Complementa base64=false sem mudar a configuração global do webhook.
     * Não envolve transcrição, workers de áudio nem endpoints públicos extras.
     */
    private function recuperarMidiaDaInstancia(int $limite): string
    {
        if ($this->empresaFiscal < 1 || $this->instanciaFiscal < 1) {
            return '';
        }

        $message = (array) data_get($this->payloadEvento, 'data.message', []);
        $key = (array) data_get($this->payloadEvento, 'data.key', []);
        if (empty($key['id']) || empty($key['remoteJid'])) {
            return '';
        }

        $instancia = \App\Models\ConnectApiInstance::query()
            ->where('id', $this->instanciaFiscal)
            ->where('empresa_id', $this->empresaFiscal)
            ->whereNull('deleted_at')
            ->first();
        if (!$instancia || !$instancia->instance_token) {
            return '';
        }

        $resposta = app(\App\Services\ConnectApi\ConnectApiClient::class)
            ->getBase64FromMediaMessage($instancia, [
                'key' => [
                    'id' => (string) $key['id'],
                    'remoteJid' => (string) $key['remoteJid'],
                    'fromMe' => false,
                ],
                'message' => $message,
                'messageType' => data_get($this->payloadEvento, 'data.messageType'),
            ]);

        if (!($resposta['success'] ?? false)) {
            Log::warning('NF-e WhatsApp: recuperação da mídia não atendida pelo provedor.', [
                'empresa_id' => $this->empresaFiscal,
                'instancia_id' => $this->instanciaFiscal,
                'status' => $resposta['status'] ?? null,
            ]);
            return '';
        }

        $dados = (array) ($resposta['data'] ?? []);
        $valor = $dados['base64']
            ?? data_get($dados, 'data.base64')
            ?? data_get($dados, 'message.base64')
            ?? data_get($dados, 'data.message.base64')
            ?? null;
        if (!is_string($valor) || trim($valor) === '') {
            return '';
        }
        $valor = preg_replace('/^data:[^,]*,/', '', $valor);
        if (strlen($valor) > ($limite * 2)) {
            throw new \RuntimeException('Mídia recuperada excede o limite configurado.');
        }
        $decoded = base64_decode($valor, true);
        if ($decoded === false || strlen($decoded) > $limite) {
            throw new \RuntimeException('Mídia do provedor inválida ou excede o limite.');
        }
        return $decoded;
    }

    /**
     * Recupera mídia do evento autenticado sem pré-processamento por outro serviço.
     * Base64 tem prioridade. A URL de fallback é aceita somente quando
     * o host estiver explicitamente autorizado na configuração local.
     */
    private function obterMidiaRecebida(string $tipo, string $url): string
    {
        $payload = $this->payloadEvento;
        $base64 = $tipo === 'xml'
            ? (data_get($payload, 'data.message.documentMessage.base64')
                ?? data_get($payload, 'data.message.documentWithCaptionMessage.message.documentMessage.base64')
                ?? data_get($payload, 'data.message.base64')
                ?? data_get($payload, 'data.base64'))
            : (data_get($payload, 'data.message.audioMessage.base64')
                ?? data_get($payload, 'data.message.base64')
                ?? data_get($payload, 'data.base64'));

        $limite = $tipo === 'xml'
            ? (int) config('whatsapp_nfe.midia.max_xml_bytes', 4194304)
            : (int) config('whatsapp_nfe.midia.max_audio_bytes', 8388608);

        if (is_string($base64) && $base64 !== '') {
            $base64 = preg_replace('/^data:[^,]*,/', '', $base64);
            if (strlen($base64) > ($limite * 2)) {
                throw new \RuntimeException('Mídia excede o limite configurado.');
            }
            $decoded = base64_decode($base64, true);
            if ($decoded === false || strlen($decoded) > $limite) {
                throw new \RuntimeException('Base64 inválido ou excede o limite.');
            }
            return $decoded;
        }

        // Base64 não veio no webhook. Recuperação autenticada da mídia
        // pela própria instância precede URLs externas potencialmente
        // criptografadas ou indisponíveis.
        try {
            $binario = $this->recuperarMidiaDaInstancia($limite);
            if ($binario !== '') {
                return $binario;
            }
        } catch (\Throwable $e) {
            Log::warning('NF-e WhatsApp: mídia da instância indisponível.', [
                'empresa_id' => $this->empresaFiscal,
                'instancia_id' => $this->instanciaFiscal,
                'erro' => $e->getMessage(),
            ]);
        }

        if (trim($url) === '') return '';

        $parsed = parse_url($url);
        if (!is_array($parsed)
            || strtolower((string) ($parsed['scheme'] ?? '')) !== 'https'
            || empty($parsed['host'])
            || isset($parsed['user']) || isset($parsed['pass'])) {
            throw new \RuntimeException('URL de mídia recebida não é permitida.');
        }

        $host = strtolower(rtrim((string) $parsed['host'], '.'));
        $hostConnect = strtolower((string) parse_url(
            (string) config('connect_api.base_url', ''), PHP_URL_HOST
        ));
        $hosts = array_filter(array_map(
            static fn ($item) => strtolower(trim((string) $item)),
            (array) config('whatsapp_nfe.midia.allowed_hosts', [])
        ));
        if ($hostConnect !== '') $hosts[] = $hostConnect;

        if (!in_array($host, $hosts, true)) {
            throw new \RuntimeException(
                'Host de mídia não autorizado. Configure o domínio legítimo do provedor.'
            );
        }

        $response = Http::withOptions(['allow_redirects' => false])
            ->connectTimeout(5)->timeout(20)
            ->withHeaders(['Accept' => '*/*'])
            ->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException('Não foi possível recuperar a mídia recebida.');
        }

        $conteudo = $response->body();
        if (strlen($conteudo) > $limite) {
            throw new \RuntimeException('Mídia excede o limite configurado.');
        }
        return $conteudo;
    }

    private function consultarIaFiscal(array $parts, bool $json = true): string
    {
        $key = trim((string) config('whatsapp_nfe.ai.api_key', ''));
        $model = trim((string) config('whatsapp_nfe.ai.model', ''));
        if ($key === '' || $model === '' || !preg_match('/^[A-Za-z0-9_.-]+$/', $model)) {
            throw new \RuntimeException('Provedor de IA fiscal não configurado.');
        }
        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent';
        $payload = ['contents' => [['parts' => $parts]]];
        if ($json) $payload['generationConfig'] = ['response_mime_type' => 'application/json'];
        $response = Http::withHeaders(['x-goog-api-key' => $key, 'Accept' => 'application/json'])
            ->connectTimeout(5)->timeout(35)->post($endpoint, $payload);
        if (!$response->successful()) {
            Log::warning('NF-e WhatsApp: IA recusou a solicitação.', [
                'empresa_id' => $this->empresaFiscal, 'http_status' => $response->status(),
            ]);
            throw new \RuntimeException('IA fiscal temporariamente indisponível.');
        }
        return $response->body();
    }

    public function processarItensParciaisDevolucao($numero, $mensagem, $funcionario, $estadoId, $sender)
    {
        $dados = Cache::get($this->chaveCache('dev_dados', $numero));
        if (!is_array($dados) || empty($dados['itens'])) return response()->json(['status' => 'expirado']);
        $selecionados = [];
        foreach (explode(';', $mensagem) as $parte) {
            if (!preg_match('/^\s*(\d+)\s*=\s*([\d.,]+)\s*$/', $parte, $match)) {
                $sender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, 'Formato inválido. Exemplo: 1=2,5;2=1');
                return response()->json(['status' => 'formato_invalido']);
            }
            $indice = (int) $match[1] - 1;
            $quantidade = str_contains($match[2], ',')
                ? (float) str_replace(',', '.', str_replace('.', '', $match[2])) : (float) $match[2];
            if (!isset($dados['itens'][$indice]) || $quantidade <= 0 || $quantidade > (float) $dados['itens'][$indice]['qCom']) {
                $sender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, 'Quantidade fora do intervalo do XML.');
                return response()->json(['status' => 'quantidade_invalida']);
            }
            $selecionados[$indice] = $quantidade;
        }
        if (!$selecionados) return response()->json(['status' => 'nenhum_item']);
        $itens = [];
        foreach ($dados['itens'] as $indice => $item) {
            if (!isset($selecionados[$indice])) continue;
            $item['qCom'] = $selecionados[$indice];
            $itens[] = $item;
        }
        $dados['itens'] = $itens;
        Cache::put($this->chaveCache('dev_dados', $numero), $dados, 600);
        Cache::put($estadoId, 'DEVOLUCAO_AGUARDANDO_MOTIVO', 600);
        $sender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero,
            '✅ Quantidades registradas. Informe agora o motivo da devolução.');
        return response()->json(['status' => 'aguardando_motivo']);
    }

}
