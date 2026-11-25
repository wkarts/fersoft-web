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
use App\Models\Compra;
use App\Models\ItemCompra;
use App\Models\ContaPagar;
use App\Models\CategoriaConta;
use App\Services\DevolucaoService;
use App\Helpers\StockMove;

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
     * - comandos fiscais, MTR, Agenda e XMLs explícitos iniciam o módulo;
     * - áudios podem iniciar o módulo SEM sessão, desde que o funcionário
     *   tenha autorização fiscal na empresa do evento;
     * - conversas fiscais em andamento mantêm suas opções numéricas;
     * - demais mensagens seguem o fluxo existente do Ponto e automações.
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

        if ($numeroFiscal !== '' && (
                !in_array(
                    Cache::get($this->chaveEstado(
                        (int) $event->empresa_id,
                        (int) $event->connect_api_instance_id,
                        $numeroFiscal
                    )), [null, 'INICIO'], true
                ) || Cache::has('wa_nfe_estado_' . $numeroFiscal)
            )) {
            return true;
        }

        // Inclui AGENDA, MTR, COMPRA, DEVOLUÇÃO, REMESSA, FATURAR e COMANDOS
        if ($texto !== '' && (
                preg_match(
                    '/^(?:AGENDA(?:R)?\b|EMITIR\s+MTR\s+AGENDA|MTR\s+AGENDA|CONFIRMAR\s+MTR|FISCAL|ASSISTENTE\s+FISCAL|NOTA|NFE|NF-E|NF\b|DANFE|PDF\s*\d|XML\s*\d|DEVOLU|FAZER\s+DEVOLU|GERAR\s+DEVOLU|COMPRA|NOTA\s+DE\s+COMPRA|NOTAS\s+DE\s+COMPRA|CONSULTAR\s+(?:NOTA|NF|NFE|DANFE|XML|PDF)|IMPRIMIR\s+(?:NOTA|NF|NFE|DANFE|XML|PDF)|CANCELAR\s+(?:NOTA|NF|NFE|NF-E|\d)|EMITIR\s+(?:NOTA|NF|NFE)|FATURAR\b)/iu',
                    $texto
                ) || (stripos($texto, 'NOME:') !== false && stripos($texto, 'CPF:') !== false)
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

    private function usuarioAutorizadoMtr(Usuario $usuario, ?Funcionario $funcionario): bool
    {
        if (Schema::hasColumn('usuarios', 'permissao_mtr_whatsapp') && (bool) $usuario->permissao_mtr_whatsapp) {
            return true;
        }
        if ($funcionario && Schema::hasColumn('funcionarios', 'permissao_mtr_whatsapp') && (bool) $funcionario->permissao_mtr_whatsapp) {
            return true;
        }
        return false;
    }

    /**
     * Entrada principal compatível tanto com ConnectApiWebhookController
     * quanto com o desvio do PontoWhatsAppController.
     */
    public function webhook(Request $request)
    {
        $funcionarioInput = $request->input('funcionario_obj') ?? $request->attributes->get('funcionario_obj');
        $numero = (string) $request->input('numero_tratado', '');
        $mensagem = trim((string) $request->input('texto_tratado', ''));
        $whatsappSender = $this;

        // Tenta capturar a empresa da requisição/instância atual do webhook
        $empresaIdInstancia = $request->input('empresa_id') ?? $request->attributes->get('empresa_id');

        // Se a requisição traz uma empresa específica, validamos se o funcionário pertence a ela
        $funcionario = null;
        if ($funcionarioInput) {
            if (!$empresaIdInstancia || (int) $funcionarioInput->empresa_id === (int) $empresaIdInstancia) {
                $funcionario = $funcionarioInput;
            }
        }

        // Se o funcionário veio global ou de outra empresa cruzada, fazemos a busca restrita pelo número e empresa da instância
        if (!$funcionario && $empresaIdInstancia && !empty($numero)) {
            $numeroLimpo = preg_replace('/[^0-9]/', '', explode('@', $numero)[0]);
            $funcionario = \App\Models\Funcionario::where('empresa_id', $empresaIdInstancia)
                ->where(function($q) use ($numeroLimpo) {
                    $q->where('telefone', 'like', "%{$numeroLimpo}%")
                        ->orWhere('celular', 'like', "%{$numeroLimpo}%");
                })->first();
        }

        // Se ainda assim não encontrar um funcionário válido para ESTA empresa, bloqueia o acesso
        if (!$funcionario) {
            Log::warning('NF-e WhatsApp: Acesso bloqueado, funcionário não identificado ou de outra empresa.');
            return response()->json(['status' => 'empresa_invalida'], 403);
        }

        if ($this->empresaFiscal < 1) {
            $this->empresaFiscal = (int) $funcionario->empresa_id;
            $this->instanciaFiscal = (int) ($request->input('connect_api_instance_id') ?? 1);
        }

        // Trava de segurança extra: garante que a empresa fiscal bate com a empresa do funcionário validado
        if ($empresaIdInstancia && (int) $this->empresaFiscal !== (int) $empresaIdInstancia) {
            Log::warning('NF-e WhatsApp: Conflito de empresas entre a instância e o funcionário.');
            return response()->json(['status' => 'conflito_empresa'], 403);
        }

        Log::info('--- WHATSAPP NFE CONTROLLER: WEBHOOK INICIADO ---');
        Log::info('NF-e WhatsApp: Processamento Autorizado.', [
            'empresa_id' => $this->empresaFiscal,
            'evento_id' => $request->attributes->get('fiscal_connect_api_event_id'),
        ]);

        $msgUpper = mb_strtoupper($mensagem, 'UTF-8');

        // =========================================================================
        // 🚪 INTERCEPÇÃO DE AGENDAMENTO DE PORTARIA (Permite qualquer funcionário da empresa)
        // =========================================================================
        $ehMtrTemp = str_contains($msgUpper, 'MTR AGENDA') || str_contains($msgUpper, 'CONFIRMAR MTR');
        if (!$ehMtrTemp && (preg_match('/^AGENDA(R)?\b/iu', $msgUpper) || (str_contains($msgUpper, 'NOME:') && str_contains($msgUpper, 'CPF:')))) {
            return $this->processarAgendamentoPortaria($numero, $mensagem, $funcionario, $whatsappSender);
        }

        // =========================================================================
        // ♻️ INTERCEPÇÃO DE COMANDO MTR DA AGENDA DE COLETA
        // =========================================================================
        if (preg_match('/^EMITIR\s+MTR\s+AGENDA\s*[\-\s]?\s*(\d+)$/i', $mensagem, $matchesMtr)) {
            $mtrId = (int) $matchesMtr[1];
            $usuarioLogadoMtr = Usuario::find($funcionario->usuario_id);
            if (!$usuarioLogadoMtr || !$this->usuarioAutorizadoMtr($usuarioLogadoMtr, $funcionario)) {
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⛔ *Acesso Negado*\nO seu utilizador não possui permissão para emitir MTR via WhatsApp.");
                return response()->json(['status' => 'sem_permissao_mtr']);
            }

            try {
                $ctrl = app(\App\Http\Controllers\MtrController::class);
                $definirEmpresa = function (int $empresa) { $this->empresa_id = $empresa; };
                $definirEmpresa->call($ctrl, (int) $funcionario->empresa_id);

                $fakeRequest = new \Illuminate\Http\Request();
                $ctrl->transmitir($fakeRequest, $mtrId);

                $manifestoAtualizado = \App\Models\MtrManifesto::find($mtrId);

                if ($manifestoAtualizado && $manifestoAtualizado->status === 'transmitido') {
                    $whatsappSender->enviarRespostaWhatsApp(
                        $funcionario->empresa_id,
                        $numero,
                        "✅ *MTR Oficial Emitido com Sucesso!*\nNº: {$manifestoAtualizado->numero_mtr}\n\nA preparar o PDF para envio..."
                    );

                    $pdfContent = $ctrl->downloadPdf($manifestoAtualizado->numero_mtr);

                    if ($pdfContent) {
                        $dir = storage_path('app/public/mtr_envio');
                        if (!is_dir($dir)) mkdir($dir, 0755, true);
                        $pathPdf = $dir . DIRECTORY_SEPARATOR . 'MTR_' . $manifestoAtualizado->numero_mtr . '.pdf';
                        file_put_contents($pathPdf, $pdfContent);

                        app(\App\Services\ConnectApi\ConnectApiMessageService::class)->sendMedia(
                            (int) $funcionario->empresa_id,
                            (string) $numero,
                            $pathPdf,
                            "📄 MTR Oficial #{$manifestoAtualizado->numero_mtr}"
                        );
                    }
                } else {
                    $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Falha na transmissão do MTR. Verifique os registros no ERP.");
                }
            } catch (\Throwable $e) {
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Erro ao emitir MTR: " . $e->getMessage());
            }
            return response()->json(['status' => 'mtr_agenda_processado']);
        }

        // =========================================================================
        // REGRA 1: VALIDAÇÃO DE PERMISSÃO DO FUNCIONÁRIO (TRAVA DE SEGURANÇA FISCAL)
        // =========================================================================
        $usuarioLogado = Usuario::find($funcionario->usuario_id);

        if (!$usuarioLogado || (int) $usuarioLogado->empresa_id !== $this->empresaFiscal
            || !$this->usuarioAutorizado($usuarioLogado, $funcionario)) {
            Log::warning("Acesso negado para emissão NFe WhatsApp. Usuario ID: " . $funcionario->usuario_id);
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⛔ *Acesso Negado*\nO seu utilizador não possui permissão administrativa para emitir ou gerir Notas Fiscais remotamente. Contacte a gerência.");
            return response()->json(['status' => 'sem_permissao']);
        }

        $estadoId = $this->chaveCache('estado', $numero);

        // =========================================================================
        // COMANDOS GLOBAIS DE CANCELAMENTO DA OPERAÇÃO DO ROBÔ
        // =========================================================================
        if ($msgUpper === 'CANCELAR') {
            Cache::forget($estadoId);
            Cache::forget($this->chaveCache('nfe_dados', $numero));
            Cache::forget($this->chaveCache('compra_dados', $numero));
            Cache::forget($this->chaveCache('dev_dados', $numero));
            Cache::forget($this->chaveCache('cancelamento_id', $numero));
            Cache::forget('wa_nfe_estado_' . $numero);
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Operação cancelada com sucesso. O Assistente Virtual Fiscal está livre para uma nova instrução.");
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
            if (preg_match('/^(NOTA DE DEVOLU|DEVOLU|FAZER DEVOLU|GERAR DEVOLU)/iu', $msgUpper) && !str_contains($msgUpper, 'EXPORTA')) {
                Cache::put($estadoId, 'AGUARDANDO_XML_DEVOLUCAO', 600);
                Cache::put('wa_nfe_estado_' . $numero, true, 600);
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "📦 *Módulo de Devolução*\n\nPara iniciar, por favor, **envie o ficheiro XML** da nota original aqui no chat.");
                return response()->json(['status' => 'aguardando_xml']);
            }

            // B. CONSULTAR / IMPRIMIR NOTA
            if (preg_match('/^(?:CONSULTAR|IMPRIMIR\s+)?(?:DANFE|PDF|XML|NOTA|NFE|NF-E|NF)\s*(?:DA\s*NOTA)?\s*#?(\d+)$/iu', $mensagem, $matches) || preg_match('/^(\d+)$/', $mensagem, $matches)) {
                $numeroNota = $matches[1];
                return $this->enviarSegundaViaNfe($numero, $numeroNota, $funcionario, $whatsappSender);
            }

            // C. CANCELAR NOTA NA SEFAZ
            if (preg_match('/^(?:CANCELAR\s+(?:N[F-]*E|NOTA|NF)|(?:N[F-]*E|NOTA|NF)\s+\d+\s+CANCELAR)\s*#?(\d+)?/iu', $mensagem, $matches) || preg_match('/^CANCELAR\s+(\d+)$/iu', $mensagem, $matches)) {
                preg_match_all('/\d+/', $mensagem, $nums);
                $numeroNotaParaCancelar = $nums[0][0] ?? null;

                if ($numeroNotaParaCancelar) {
                    $vendaAlvo = Venda::where('empresa_id', $funcionario->empresa_id)->where('NfNumero', $numeroNotaParaCancelar)->first()
                        ?? Venda::where('empresa_id', $funcionario->empresa_id)->where('id', $numeroNotaParaCancelar)->first();

                    if ($vendaAlvo && strtoupper($vendaAlvo->estado) === 'APROVADO') {
                        Cache::put($this->chaveCache('cancelamento_id', $numero), $vendaAlvo->id, 600);
                        Cache::put($estadoId, 'AGUARDANDO_JUSTIFICATIVA_CANCELAMENTO', 600);
                        Cache::put('wa_nfe_estado_' . $numero, true, 600);
                        $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "📝 Informe a **justificativa** para cancelar a Nota Fiscal #{$numeroNotaParaCancelar}:");
                    } else {
                        $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠ Nota Fiscal #{$numeroNotaParaCancelar} não encontrada ou não aprovada.");
                    }
                    return response()->json(['status' => 'aguardando_justificativa']);
                }
            }

            // D. EMISSÃO DE NOTA DE COMPRA
            if (preg_match('/^(COMPRA|NOTA DE COMPRA|NOTAS DE COMPRA)\b/iu', $msgUpper)) {
                return $this->processarMensagemCompra($numero, $mensagem, $funcionario, $estadoId, $whatsappSender);
            }

            // H. EMISSÃO EXPRESSA DE NOTA VIA BALANÇA (PESAGEM)
            if (preg_match('/^(?:NOTA\s+DA\s+)?PESAGEM\s+(\d+)(?:\s+VALOR\s*[R\$]*\s*([\d\,\.]+))?/iu', $msgUpper, $matchesPesagem)) {
                return $this->processarResumoPesagemWhatsApp($numero, $matchesPesagem, $funcionario, $estadoId, $whatsappSender);
            }

            // E. EMISSÃO DE NOTA DE VENDA / EXPORTAÇÃO / REMESSA / TRANSFERÊNCIA
            if (preg_match('/^(NOTA|NFE|NF-E|NF|EMITIR|FATURAR)\b/iu', $msgUpper)) {
                return $this->processarMensagemInicial($numero, $mensagem, $funcionario, $estadoId, $whatsappSender);
            }

            // F. AGENDAMENTO DE PORTARIA
            if (preg_match('/^AGENDA(R)?\b/iu', $msgUpper)) {
                return $this->processarAgendamentoPortaria($numero, $mensagem, $funcionario, $whatsappSender);
            }

            // G. TRANSFERÊNCIA DE LOCAIS
            if (preg_match('/^(TRANSF|TRANSFERENCIA|NOTA DE TRANSFERENCIA)\b/iu', $msgUpper)) {
                return $this->processarMensagemTransferencia($numero, $mensagem, $funcionario, $estadoId, $whatsappSender);
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

                case 'AGUARDANDO_SELECAO_FORNECEDOR_COMPRA':
                    return $this->processarSelecaoFornecedorCompra($numero, $mensagem, $funcionario, $estadoId, $whatsappSender);

                case 'AGUARDANDO_CONFIRMACAO_COMPRA':
                    return $this->processarConfirmacaoFinalCompra($numero, $mensagem, $funcionario, $estadoId, $whatsappSender);


                case 'AGUARDANDO_CONFIRMACAO_TRANSFERENCIA':
                    return $this->processarConfirmacaoFinalTransferencia($numero, $mensagem, $funcionario, $estadoId, $whatsappSender);


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

                case 'AGUARDANDO_CONFIRMACAO_PESAGEM_EXPRESSA':
                    return $this->processarConfirmacaoPesagemExpressa($numero, $mensagem, $funcionario, $estadoId, $whatsappSender);


                default:
                    Cache::put($estadoId, 'FISCAL_ATIVO', 600);
                    $menu = "🤖 *Assistente Virtual Fiscal {$this->nomeAplicacao()}*\n\nComo posso ajudar?\n\n";
                    $menu .= "🔹 *Emitir Venda:* _\"Nota para cliente X, 100kg de cobre a R$30\"_\n";
                    $menu .= "🔹 *Emitir Compra:* _\"Nota de compra para Jorge Rosa...\"_\n";
                    $menu .= "🔹 *Devolução:* _\"Nota de devolução\"_ (ou anexe direto o XML)\n";
                    $menu .= "🔹 *Consultar/Imprimir:* _\"Nota 123\"_\n";
                    $menu .= "🔹 *Cancelar:* _\"Cancelar nota 123\"_\n";
                    $menu .= "🔹 *Agendar Portaria:* _\"Agenda\"_";

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
            Cache::forget('wa_nfe_estado_' . $numero);
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
                    Cache::forget('wa_nfe_estado_' . $numero);
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
            Cache::put('wa_nfe_estado_' . $numero, true, 600);

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
            Cache::put($this->chaveCache('dev_dados', $numero), $dadosDev, 600);
            Cache::put($estadoId, 'DEVOLUCAO_AGUARDANDO_TOTAL_PARCIAL', 600);
            Cache::put('wa_nfe_estado_' . $numero, true, 600);

            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "📦 Devolução de **Compra**.\n\nA devolução é:\n*1* - **Total**\n*2* - **Parcial**");
            return response()->json(['status' => 'aguardando_parcial']);
        } else {
            Cache::put($this->chaveCache('dev_dados', $numero), $dadosDev, 600);
            Cache::put($estadoId, 'DEVOLUCAO_AGUARDANDO_DESTINATARIO_ORIGEM', 600);
            Cache::put('wa_nfe_estado_' . $numero, true, 600);

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
        Cache::put($this->chaveCache('dev_dados', $numero), $dadosDev, 600);
        Cache::put($estadoId, 'DEVOLUCAO_AGUARDANDO_TOTAL_PARCIAL', 600);
        Cache::put('wa_nfe_estado_' . $numero, true, 600);

        $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "A devolução é:\n*1* - **Total**\n*2* - **Parcial**");
        return response()->json(['status' => 'aguardando_parcial']);
    }

    public function processarTotalParcialDevolucao($numero, $mensagem, $funcionario, $estadoId, $whatsappSender)
    {
        $dadosDev = Cache::get($this->chaveCache('dev_dados', $numero));
        if (!$dadosDev || !is_array($dadosDev)) {
            Cache::forget('wa_nfe_estado_' . $numero);
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
            Cache::put('wa_nfe_estado_' . $numero, true, 600);
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
        Cache::put('wa_nfe_estado_' . $numero, true, 600);

        $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "✍️ Qual o *Motivo da Devolução*?\n\n_(Ex: Mercadoria avariada, Desistência, etc. Este motivo e os dados da nota sairão automaticamente nas Informações Complementares)_");

        return response()->json(['status' => 'aguardando_motivo']);
    }

    public function processarFinalizacaoDevolucao($numero, $mensagem, $funcionario, $estadoId, $whatsappSender)
    {
        $dadosDev = Cache::get($this->chaveCache('dev_dados', $numero));

        if (!$dadosDev || !is_array($dadosDev) || !isset($dadosDev['emitente'])) {
            Cache::forget('wa_nfe_estado_' . $numero);
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

            $chaveOrigem = preg_replace('/\D/', '', $dadosDev['chave'] ?? '');
            if (strlen($chaveOrigem) !== 44) {
                throw new \Exception("A chave da nota referenciada não tem 44 dígitos ({$chaveOrigem}). O XML original pode estar corrompido.");
            }

            $serieOrigem = (int)substr($chaveOrigem, 22, 3);
            $nNfOrigem = $dadosDev['nNf'] ?? (int)substr($chaveOrigem, 25, 9);

            $isParcial = (isset($dadosDev['devolucao_parcial']) && $dadosDev['devolucao_parcial'] === '2');
            $tipoExtenso = $isParcial ? 'PARCIAL' : 'TOTAL';

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
            Cache::forget('wa_nfe_estado_' . $numero);
            return response()->json(['status' => 'sucesso']);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) DB::rollBack();
            Log::error("Erro Devolução WhatsApp: " . $e->getMessage());
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ A devolução não foi concluída. Verifique os dados e os registros do ERP.");
            return response()->json(['status' => 'erro']);
        }
    }

    // =========================================================================
    // MÉTODOS DE EMISSÃO NORMAL DE NFE (SEGUNDA VIA, TEXTO, ÁUDIO E AUXILIARES)
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
        Você é um assistente de faturamento de um ERP corporativo do ramo de sucatas metálicas (alumínio, cobre, inox, ferro, etc.).
        Analise a seguinte mensagem do WhatsApp e extraia os dados rigorosamente no formato JSON.

        Regras de Extração:
        - empresa_emissora: Nome da filial mencionada. Vazio se não houver.
        - is_exportacao: Retorne true se a mensagem mencionar 'exportação', 'porto' ou dados de navio.
        - cliente_nome_pesquisa: Nome principal do cliente (ex: 'BRASIL', 'LATASA', 'CIBRAFER').
        - cliente_cidade_pesquisa: Nome da cidade do cliente, se informada na mensagem (ex: 'SALVADOR', 'ITABUNA', 'ARUJA').
        - cliente_uf_pesquisa: Sigla do estado (UF) do cliente, se informada (ex: 'SP', 'BA', 'MG').
        - transportadora_nome_pesquisa: Nome da transportadora, se houver.
        - operacao: 'venda' ou 'compra'. Padrão é 'venda'.
        - REGRA CRÍTICA DE PESOS E BALANÇA (quantidade, peso_bruto, peso_liquido):
          1. Muitas vezes o operador da balança digita VÍRGULA (,) no lugar de PONTO (.) para separar milhares!
          2. Se um peso tiver DUAS vírgulas (ex: '4,794,5' ou '10,985,5'), a primeira vírgula é milhar e a última é decimal -> converta para 4794.5 e 10985.5.
          3. Se um peso tiver 3 dígitos após a vírgula (ex: '1,920' ou '4,271') e NÃO tiver a palavra 'ton' ou 't' ao lado, valide pela SOMA do Peso Líquido total (ex: 4794.5 + 1920 + 4271 = 10985.5). Nesse caso, trata-se de quilos (1920.00 e 4271.00) e NÃO toneladas!
          4. Só multiplique por 1000 se estiver escrito explicitamente 'ton' ou 't' (ex: '2,5 ton' -> 2500.00).
          5. Mantenha todas as casas decimais exatas quando houver fração real em quilos (ex: '16.267,97740' -> 16267.9774).
        - REGRA DE PREÇO UNITÁRIO (valor_unitario):
          Extraia o valor após 'R$' ou após o traço. Mantenha todas as casas decimais informadas (ex: 'R$ 16,835' -> 16.835 | 'R$ 18,50' -> 18.50).
        - volume: Quantidade de volumes (padrão 1).
        - especie: Tipo de embalagem (ex: bag/fardo/solto).
        - placa: Placa principal, se houver.
        - observacao_extra: Extraia OBRIGATORIAMENTE todas as informações adicionais, dados de transporte (Motorista, Cavalo, Carreta) E TAMBÉM qualquer texto livre de observação fiscal/exportação no final da mensagem (ex: NÃO INCIDÊNCIA DE ICMS, leis, Porto de embarque/destino, Operador portuário, Container, Lacre, Tara, Booking, Navio, Viagem, Invoice/INV, Taxa Dólar PTAX, etc.). Separe cada bloco ou frase por PONTO E VÍRGULA (;). NUNCA deixe de extrair o texto final da mensagem!
        - itens: Extraia a descrição exata do produto (ex: 'Perfil Natural', 'Perfil Pintado', 'Perfil Estrutural', 'Cobre Mel', 'Cobre Misto', 'Inox 304').

        Mensagem do Operador: '{$mensagem}'

        Retorne APENAS um JSON válido nesta estrutura exata:
        {
            \"notas\": [
                {
                    \"empresa_emissora\": \"\",
                    \"is_exportacao\": false,
                    \"cliente_nome_pesquisa\": \"\",
                    \"cliente_cidade_pesquisa\": \"\",
                    \"cliente_uf_pesquisa\": \"\",
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
            $jsonTexto = trim(str_replace(['```json', '```'], '', $jsonTexto));

            $dadosRetorno = json_decode($jsonTexto, true);
            if (!is_array($dadosRetorno)) throw new \RuntimeException('IA retornou estrutura inválida.');

            if (!empty($dadosRetorno['notas']) && is_array($dadosRetorno['notas'])) {

                foreach($dadosRetorno['notas'] as $key => $nota) {

                    // =====================================================================
                    // 1. BUSCA INTELIGENTE DE CLIENTE (Considera Nome, Cidade e UF exatos)
                    // =====================================================================
                    $clienteDb = $this->buscarClienteInteligente(
                        (int) $funcionario->empresa_id,
                        (string) ($nota['cliente_nome_pesquisa'] ?? ''),
                        (string) ($nota['cliente_cidade_pesquisa'] ?? ''),
                        (string) ($nota['cliente_uf_pesquisa'] ?? '')
                    );
                    $dadosRetorno['notas'][$key]['cliente_id'] = $clienteDb ? $clienteDb->id : null;

                    // =====================================================================
                    // 2. MAPEAMENTO DE PRODUTOS DE SUCATA + REGRA ESTRITA DE COBRE (MEL/MISTO)
                    // =====================================================================
                    foreach($nota['itens'] as $kItem => $itemIA) {
                        $descricaoOriginal = mb_strtoupper(trim($itemIA['descricao_venda']), 'UTF-8');
                        $descSemAcento = str_replace(['Á','À','Ã','Â','É','Ê','Í','Ó','Ô','Õ','Ú','Ç'], ['A','A','A','A','E','E','I','O','O','O','U','C'], $descricaoOriginal);

                        $temMel = str_contains($descSemAcento, 'MEL');
                        $temMisto = str_contains($descSemAcento, 'MISTO');
                        $ehCobre = str_contains($descSemAcento, 'COBRE') || $temMel || ($temMisto && !str_contains($descSemAcento, 'PERFIL'));

                        $produto = null;
                        $nomeAjustado = $descricaoOriginal;
                        $produtoId = null;

                        if ($ehCobre) {
                            // 1ª OPÇÃO: DESPERDICIOS E RESIDUOS E SUCATA COBRE
                            $produto = Produto::where('empresa_id', $funcionario->empresa_id)
                                ->where('nome', 'like', '%DESPERDICIOS E RESIDUOS E SUCATA COBRE%')
                                ->first();

                            // 2ª OPÇÃO: SUCATA DE COBRE
                            if (!$produto) {
                                $produto = Produto::where('empresa_id', $funcionario->empresa_id)
                                    ->where(function($q) {
                                        $q->where('nome', 'SUCATA DE COBRE')
                                            ->orWhere('nome', 'like', '%SUCATA DE COBRE%');
                                    })
                                    ->orderByRaw("CASE WHEN UPPER(TRIM(nome)) = 'SUCATA DE COBRE' THEN 1 ELSE 2 END")
                                    ->first();
                            }

                            // 3ª OPÇÃO: Sucata de cobre mel e sucata de cobre misto
                            if (!$produto) {
                                if ($temMel && $temMisto) {
                                    $produto = Produto::where('empresa_id', $funcionario->empresa_id)
                                        ->where(function($q) {
                                            $q->where('nome', 'like', '%SUCATA DE COBRE MEL%')
                                                ->orWhere('nome', 'like', '%SUCATA DE COBRE MISTO%');
                                        })->first();
                                } elseif ($temMel) {
                                    $produto = Produto::where('empresa_id', $funcionario->empresa_id)
                                        ->where('nome', 'like', '%SUCATA DE COBRE MEL%')
                                        ->first();
                                } elseif ($temMisto) {
                                    $produto = Produto::where('empresa_id', $funcionario->empresa_id)
                                        ->where('nome', 'like', '%SUCATA DE COBRE MISTO%')
                                        ->first();
                                }
                            }

                            if ($produto) {
                                $produtoId = $produto->id;
                                $nomeAjustado = mb_strtoupper(trim($produto->nome), 'UTF-8');
                                if ($temMel && !str_contains($nomeAjustado, 'MEL')) {
                                    $nomeAjustado .= " MEL";
                                }
                                if ($temMisto && !str_contains($nomeAjustado, 'MISTO')) {
                                    $nomeAjustado .= " MISTO";
                                }
                            }
                        } else {
                            // DEMAIS FAMÍLIAS DE SUCATA (Alumínio, Inox, Latão, Bronze, Chumbo, Ferro)
                            $familiaMaterial = null;
                            if (str_contains($descSemAcento, 'ALUMINIO') || str_contains($descSemAcento, 'PERFIL') || str_contains($descSemAcento, 'LATINHA') || str_contains($descSemAcento, 'CHAPARIA') || str_contains($descSemAcento, 'BLOCO') || str_contains($descSemAcento, 'PANELA')) {
                                $familiaMaterial = 'ALUMIN';
                            } elseif (str_contains($descSemAcento, 'INOX') || preg_match('/\b(304|316|410|430)\b/', $descSemAcento)) {
                                $familiaMaterial = 'INOX';
                            } elseif (str_contains($descSemAcento, 'LATAO')) {
                                $familiaMaterial = 'LAT';
                            } elseif (str_contains($descSemAcento, 'BRONZE')) {
                                $familiaMaterial = 'BRONZE';
                            } elseif (str_contains($descSemAcento, 'CHUMBO')) {
                                $familiaMaterial = 'CHUMBO';
                            } elseif (str_contains($descSemAcento, 'FERRO') || str_contains($descSemAcento, 'MIUDA') || str_contains($descSemAcento, 'PESADA')) {
                                $familiaMaterial = 'FERRO';
                            }

                            $produto = Produto::where('empresa_id', $funcionario->empresa_id)
                                ->where('nome', 'like', '%' . $descricaoOriginal . '%')
                                ->first();

                            if (!$produto && $familiaMaterial) {
                                $produto = Produto::where('empresa_id', $funcionario->empresa_id)
                                    ->where(function($q) use ($familiaMaterial) {
                                        $q->where('nome', 'like', '%SUCATA%' . $familiaMaterial . '%')
                                            ->orWhere('nome', 'like', '%' . $familiaMaterial . '%');
                                    })
                                    ->orderByRaw("CASE WHEN nome LIKE 'SUCATA%' THEN 1 ELSE 2 END")
                                    ->first();
                            }

                            if (!$produto) {
                                $palavrasProd = explode(' ', $descricaoOriginal);
                                $primeiraPalavraProd = $palavrasProd[0] ?? $descricaoOriginal;
                                $produto = Produto::where('empresa_id', $funcionario->empresa_id)
                                    ->where('nome', 'like', '%' . $primeiraPalavraProd . '%')
                                    ->first();
                            }

                            if ($produto) {
                                $produtoId = $produto->id;
                                $nomeBase = mb_strtoupper(trim($produto->nome), 'UTF-8');

                                $complemento = preg_replace('/\b(SUCATA|SUCATAS|DE|DO|DA|ALUMINIO|ALUMÍNIO|COBRE|INOX|FERRO|LATAO|LATÃO|BRONZE|CHUMBO|KG)\b/u', ' ', $descricaoOriginal);
                                $complemento = trim(preg_replace('/\s+/', ' ', $complemento));

                                if ($complemento !== '' && !str_contains($nomeBase, $complemento)) {
                                    $nomeAjustado = $nomeBase . ' ' . $complemento;
                                } else {
                                    $nomeAjustado = $nomeBase;
                                }
                            }
                        }

                        $dadosRetorno['notas'][$key]['itens'][$kItem]['produto_id'] = $produtoId;
                        $dadosRetorno['notas'][$key]['itens'][$kItem]['descricao_ajustada'] = $nomeAjustado;
                    }
                }

                // =========================================================================
                // 3. SELEÇÃO DE NATUREZA E MONTAGEM DO RESUMO
                // =========================================================================
                $configNotaMatriz = ConfigNota::where('empresa_id', $funcionario->empresa_id)->first();
                $ufEmitente = strtoupper((string) ($configNotaMatriz->UF ?? 'BA'));

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

                    $msgTextoUpper = mb_strtoupper($mensagem, 'UTF-8');
                    $isRemessaExport = str_contains($msgTextoUpper, 'REMESSA EXPORTA')
                        || str_contains($msgTextoUpper, 'REMESSA P EXPORTA')
                        || str_contains($msgTextoUpper, 'REMESSA PARA EXPORTA')
                        || str_contains($msgTextoUpper, 'FIM DE EXPORTA');
                    $isVendaExport = !$isRemessaExport && (str_contains($msgTextoUpper, 'EXPORTA') || (isset($notaAtualizada['is_exportacao']) && $notaAtualizada['is_exportacao'] === true) || $ufCliente === 'EX');
                    $isExport = $isRemessaExport || $isVendaExport;
                    $foraDoEstado = ($ufCliente !== '' && $ufCliente !== $ufEmitente);

                    $queryNat = NaturezaOperacao::where('empresa_id', $funcionario->empresa_id);

                    if ($isRemessaExport) {
                        $nomeNatureza = "REMESSA P EXPORTACAO";
                        $naturezaPrevista = $queryNat->where('natureza', 'like', '%REMESSA%')
                            ->where('natureza', 'like', '%EXPORTA%')
                            ->orderByRaw("CASE WHEN natureza LIKE 'REMESSA P%EXPORTA%' THEN 1 ELSE 2 END")
                            ->first();
                    } elseif ($isVendaExport) {
                        $nomeNatureza = "VENDA EXPORTACAO DE MERCADORIAS";
                        $naturezaPrevista = $queryNat->where('natureza', 'like', '%EXPORTA%')
                            ->where('natureza', 'not like', '%REMESSA%')
                            ->where('natureza', 'not like', '%DEVOLU%')
                            ->first();
                    } elseif (str_contains($msgTextoUpper, 'SIMPLES REMESSA') || str_contains($msgTextoUpper, 'REMESSA')) {
                        $nomeNatureza = "SIMPLES REMESSA";
                        $naturezaPrevista = $queryNat->where(function($q) {
                            $q->where('natureza', 'like', '%SIMPLES REMESSA%')
                                ->orWhere('natureza', 'like', '%REMES%');
                        })
                            ->where('natureza', 'not like', '%EXPORTA%')
                            ->orderByRaw("CASE WHEN natureza LIKE '%SIMPLES REMESSA%' THEN 1 ELSE 2 END")
                            ->first();
                    } elseif (str_contains($msgTextoUpper, 'TRANSFERENCIA') || str_contains($msgTextoUpper, 'TRANSFERÊNCIA')) {
                        $nomeNatureza = "TRANSFERENCIA DE MERCADORIAS";
                        $naturezaPrevista = $queryNat->where('natureza', 'like', '%TRANSF%')->first();
                    } elseif (str_contains($msgTextoUpper, 'DEVOLUCAO') || str_contains($msgTextoUpper, 'DEVOLUÇÃO')) {
                        $nomeNatureza = "DEVOLUCAO MERC ADQRECEB DE TERC";
                        $naturezaPrevista = $queryNat->where('natureza', 'like', '%DEVOLU%')->first();
                    } elseif (str_contains($msgTextoUpper, 'COMPRA')) {
                        $nomeNatureza = "COMPRA DE MERCADORIAS";
                        $naturezaPrevista = $queryNat->where('natureza', 'like', '%COMPRA%')
                            ->where('natureza', 'not like', '%DEVOLU%')
                            ->first();
                    } elseif ($foraDoEstado) {
                        $nomeNatureza = "VENDA DE MERCADORIAS INTERESTADUAL";
                        $naturezaPrevista = $queryNat->where('natureza', 'like', '%VENDA%')
                            ->where('natureza', 'like', '%INTERESTADUAL%')
                            ->first();
                    } else {
                        $nomeNatureza = "VENDAS DE MERCADORIAS";
                        $naturezaPrevista = $queryNat->where('natureza', 'like', '%VENDA%')
                            ->where('natureza', 'not like', '%INTERESTADUAL%')
                            ->where('natureza', 'not like', '%EXPORTA%')
                            ->where('natureza', 'not like', '%TRANSF%')
                            ->where('natureza', 'not like', '%DEVOLU%')
                            ->where('natureza', 'not like', '%ATIVO%')
                            ->orderByRaw("CASE
                                WHEN natureza IN ('VENDAS DE MERCADORIAS', 'VENDA DE MERCADORIAS', 'VENDAS') THEN 1
                                WHEN usar_diferimento = 1 THEN 2
                                ELSE 3 END")
                            ->first();
                    }

                    $naturezaExibir = $naturezaPrevista ? $naturezaPrevista->natureza : $nomeNatureza;
                    $dadosRetorno['notas'][$index]['natureza_id_escolhida'] = $naturezaPrevista ? $naturezaPrevista->id : null;
                    $dadosRetorno['notas'][$index]['is_exportacao'] = $isExport;

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

                    $pesoLiquidoExibir = !empty($notaAtualizada['peso_liquido']) ? (float)$notaAtualizada['peso_liquido'] : 0;
                    if ($pesoLiquidoExibir <= 0) {
                        foreach($notaAtualizada['itens'] as $itPeso) {
                            $pesoLiquidoExibir += (float)$itPeso['quantidade'];
                        }
                    }
                    if ($pesoLiquidoExibir > 0) {
                        $pesoExatoFmt = rtrim(rtrim(number_format($pesoLiquidoExibir, 5, ',', '.'), '0'), ',');
                        $msgResumo .= "⚖ Peso Líquido: " . $pesoExatoFmt . " kg\n";
                    }

                    if (!empty($notaAtualizada['observacao_extra'])) {
                        $msgResumo .= "\n📝 *Informações Complementares:* \n" . str_replace(';', "\n", trim($notaAtualizada['observacao_extra'], ';')) . "\n";
                    }

                    $valorTotal = 0;
                    $msgResumo .= "\n*Itens:*\n";
                    foreach($notaAtualizada['itens'] as $item) {
                        $sub = (float)$item['quantidade'] * (float)$item['valor_unitario'];
                        $valorTotal += $sub;
                        $nomeExibir = $item['descricao_ajustada'] ?? $item['descricao_venda'] ?? 'Produto';
                        $qtdExataFmt = rtrim(rtrim(number_format((float)$item['quantidade'], 5, ',', '.'), '0'), ',');

                        $vUn = (float)$item['valor_unitario'];
                        $casasPreco = (round($vUn, 2) != $vUn) ? strlen(substr(rtrim(number_format($vUn, 4, '.', ''), '0'), strpos(rtrim(number_format($vUn, 4, '.', ''), '0'), '.') + 1)) : 2;
                        $precoExatoFmt = number_format($vUn, max(2, $casasPreco), ',', '.');

                        $msgResumo .= "📦 " . $nomeExibir . "\n   " . $qtdExataFmt . " kg | R$ " . $precoExatoFmt . "\n";
                    }
                    $msgResumo .= "\n💰 *Total:* R$ " . number_format($valorTotal, 2, ',', '.') . "\n";
                }

                Cache::put($this->chaveCache('nfe_dados', $numero), $dadosRetorno, 600);
                Cache::put($estadoId, 'AGUARDANDO_CONFIRMACAO', 600);
                Cache::put('wa_nfe_estado_' . $numero, true, 600);

                $msgResumo .= "\nConfirma a gravação e transmissão para a SEFAZ?\n*1* - SIM\n*2* - NÃO (Cancelar)";

                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, $msgResumo);
                return response()->json(['status' => 'sucesso']);
            }

            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠️ *Não consegui extrair os dados da nota.*\n\nA Inteligência Artificial não conseguiu interpretar o formato enviado. Certifique-se de que o nome do cliente, produto, quantidade e valor estão claros.");
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
                    Cache::forget('wa_nfe_estado_' . $numero);
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
                            $clienteDb = $this->buscarClienteInteligente(
                                (int) $funcionario->empresa_id,
                                (string) ($nota['cliente_nome_pesquisa'] ?? ''),
                                (string) ($nota['cliente_cidade_pesquisa'] ?? ''),
                                (string) ($nota['cliente_uf_pesquisa'] ?? '')
                            );
                            $clienteId = $clienteDb ? $clienteDb->id : null;
                        }

                        if (!$clienteId) {
                            throw new \Exception("Cliente '{$nota['cliente_nome_pesquisa']}' não foi encontrado no ERP.");
                        }

                        $nota['cliente_id'] = $clienteId;
                        if (empty($nota['itens']) || !is_array($nota['itens'])) throw new \RuntimeException('Nota sem itens válidos.');
                        $clienteObj = Cliente::where('empresa_id', $funcionario->empresa_id)->findOrFail($nota['cliente_id']);

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

                        if (isset($clienteObj->numero_diferimento) && !empty($clienteObj->numero_diferimento)) {
                            $numeroDiferimento = $clienteObj->numero_diferimento;
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

                        $ufEmitente = strtoupper((string) ($configNotaMatriz->UF ?? 'BA'));
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

                        $naturezaOp = null;
                        if (!empty($nota['natureza_id_escolhida'])) {
                            $naturezaOp = NaturezaOperacao::where('empresa_id', $funcionario->empresa_id)
                                ->where('id', $nota['natureza_id_escolhida'])
                                ->first();
                        }

                        if (!$naturezaOp) {
                            $queryNat = NaturezaOperacao::where('empresa_id', $funcionario->empresa_id);
                            if ($isExport) {
                                $naturezaOp = $queryNat->where('natureza', 'like', '%EXPORTA%')->where('natureza', 'not like', '%DEVOLU%')->first();
                            } elseif ($ufCliente !== $ufEmitente) {
                                $naturezaOp = $queryNat->where('natureza', 'like', '%VENDA%')->where('natureza', 'like', '%INTERESTADUAL%')->first();
                            } else {
                                $naturezaOp = $queryNat->where('natureza', 'like', '%VENDA%')
                                    ->where('natureza', 'not like', '%INTERESTADUAL%')
                                    ->where('natureza', 'not like', '%EXPORTA%')
                                    ->where('natureza', 'not like', '%TRANSF%')
                                    ->where('natureza', 'not like', '%DEVOLU%')
                                    ->orderByRaw("CASE WHEN natureza IN ('VENDAS DE MERCADORIAS', 'VENDA DE MERCADORIAS', 'VENDAS') THEN 1 WHEN usar_diferimento = 1 THEN 2 ELSE 3 END")
                                    ->first();
                            }
                        }

                        $naturezaId = $naturezaOp ? $naturezaOp->id : ($configNotaMatriz ? $configNotaMatriz->nat_op_padrao : null);
                        if (!$naturezaId || !NaturezaOperacao::where('empresa_id', $funcionario->empresa_id)->whereKey($naturezaId)->exists()) {
                            throw new \RuntimeException('Natureza de operação fiscal não validada.');
                        }

                        $pesoLiquido = !empty($nota['peso_liquido']) ? (float)$nota['peso_liquido'] : 0;
                        $pesoBruto = !empty($nota['peso_bruto']) ? (float)$nota['peso_bruto'] : 0;
                        $qtdVolumes = !empty($nota['volume']) ? (int)$nota['volume'] : 1;

                        if ($pesoLiquido <= 0) {
                            foreach ($nota['itens'] as $itemIA) {
                                $pesoLiquido += (float)$itemIA['quantidade'];
                            }
                        }
                        if ($pesoBruto <= 0) $pesoBruto = $pesoLiquido;

                        $frete = Frete::create([
                            'valor' => 0,
                            'tipo' => 9,
                            'peso_liquido' => $pesoLiquido,
                            'peso_bruto' => $pesoBruto,
                            'qtdVolumes' => $qtdVolumes,
                            'numeracaoVolumes' => '',
                            'especie' => $nota['especie'] ?? '',
                            'placa' => $nota['placa'] ?? '',
                        ]);

                        $dadosAdicionaisFiscal = "";

                        if ($naturezaOp && isset($naturezaOp->usar_info_cliente) && (int)$naturezaOp->usar_info_cliente === 1) {
                            if (!empty($clienteObj->informacao_nfe)) {
                                $dadosAdicionaisFiscal .= trim($clienteObj->informacao_nfe) . "\n";
                            }
                        }

                        if (!empty($nota['observacao_extra'])) {
                            // Quebra linha apenas em ponto e vírgula (;), preservando pontos de abreviações (ART., PTAX., etc.)
                            $obsTratada = str_replace(';', "\n", trim($nota['observacao_extra'], ';'));
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
                        } elseif ($isExport && empty($nota['observacao_extra'])) {
                            // Só coloca texto genérico se o operador não tiver enviado as informações de exportação
                            $dadosAdicionaisFiscal .= "Operação de exportação; verificar enquadramento tributário no ERP.\n";
                        } elseif (!$isExport && $ufCliente !== $ufEmitente && empty($nota['observacao_extra'])) {
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
                            $nomeParaNota = $itemIA['descricao_ajustada'] ?? mb_strtoupper(trim($itemIA['descricao_venda']), 'UTF-8');

                            if (!$produtoId) {
                                throw new \Exception("Nenhum produto cadastrado no ERP.");
                            }

                            $subtotal = (float)$itemIA['quantidade'] * (float)$itemIA['valor_unitario'];
                            $valorTotal += $subtotal;

                            ItemVenda::create([
                                'venda_id'     => $venda->id,
                                'produto_id'   => $produtoId,
                                'quantidade'   => (float)$itemIA['quantidade'],
                                'valor'        => (float)$itemIA['valor_unitario'],
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
                Cache::forget('wa_nfe_estado_' . $numero);

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
            Cache::forget('wa_nfe_estado_' . $numero);
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Operação cancelada.");
            return response()->json(['status' => 'cancelado']);
        }
    }

    public function processarMensagemAudio($numero, $urlAudio, $funcionario, $estadoId, $whatsappSender)
    {
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

            $prompt = "Extraia dados de faturamento para JSON do ramo de sucatas (alumínio, cobre, inox). Regras: Mantenha as casas decimais exatas. Itens com descrições detalhadas. cliente_nome_pesquisa, cliente_cidade_pesquisa, cliente_uf_pesquisa: extrair do áudio. observacao_extra: Use PONTO E VÍRGULA (;) para pular linha. Ex: ;Motorista André;Placa OVD9253; operacao: 'venda'. Retorne JSON exato.";

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
                    $c = $this->buscarClienteInteligente(
                        (int) $funcionario->empresa_id,
                        (string) ($nota['cliente_nome_pesquisa'] ?? ''),
                        (string) ($nota['cliente_cidade_pesquisa'] ?? ''),
                        (string) ($nota['cliente_uf_pesquisa'] ?? '')
                    );
                    $json['notas'][$k]['cliente_id'] = $c ? $c->id : null;

                    foreach($nota['itens'] as $ki => $item) {
                        $desc = mb_strtoupper(trim($item['descricao_venda']), 'UTF-8');
                        $temMel = str_contains($desc, 'MEL');
                        $temMisto = str_contains($desc, 'MISTO');
                        $ehCobre = str_contains($desc, 'COBRE') || $temMel || ($temMisto && !str_contains($desc, 'PERFIL'));

                        $prod = null;
                        if ($ehCobre) {
                            $prod = Produto::where('empresa_id', $funcionario->empresa_id)
                                ->where('nome', 'like', '%DESPERDICIOS E RESIDUOS E SUCATA COBRE%')
                                ->first();

                            if (!$prod) {
                                $prod = Produto::where('empresa_id', $funcionario->empresa_id)
                                    ->where('nome', 'like', '%SUCATA DE COBRE%')
                                    ->first();
                            }

                            if (!$prod) {
                                if ($temMel && $temMisto) {
                                    $prod = Produto::where('empresa_id', $funcionario->empresa_id)
                                        ->where(function($q) {
                                            $q->where('nome', 'like', '%SUCATA DE COBRE MEL%')
                                                ->orWhere('nome', 'like', '%SUCATA DE COBRE MISTO%');
                                        })->first();
                                } elseif ($temMel) {
                                    $prod = Produto::where('empresa_id', $funcionario->empresa_id)
                                        ->where('nome', 'like', '%SUCATA DE COBRE MEL%')
                                        ->first();
                                } elseif ($temMisto) {
                                    $prod = Produto::where('empresa_id', $funcionario->empresa_id)
                                        ->where('nome', 'like', '%SUCATA DE COBRE MISTO%')
                                        ->first();
                                }
                            }
                        }

                        if (!$prod) {
                            $prod = Produto::where('empresa_id', $funcionario->empresa_id)->where('nome', 'like', "%$desc%")->first()
                                ?: Produto::where('empresa_id', $funcionario->empresa_id)->where('nome', 'like', '%' . explode(' ', $desc)[0] . '%')->first();
                        }

                        $nm = $desc;
                        if ($prod) {
                            $nm = mb_strtoupper(trim($prod->nome), 'UTF-8');
                            if ($temMel && !str_contains($nm, 'MEL')) $nm .= " MEL";
                            if ($temMisto && !str_contains($nm, 'MISTO')) $nm .= " MISTO";
                        }
                        $json['notas'][$k]['itens'][$ki]['produto_id'] = $prod ? $prod->id : null;
                        $json['notas'][$k]['itens'][$ki]['descricao_ajustada'] = $nm;
                    }
                }
                Cache::put($this->chaveCache('nfe_dados', $numero), $json, 600);
                Cache::put($estadoId, 'AGUARDANDO_CONFIRMACAO', 600);
                Cache::put('wa_nfe_estado_' . $numero, true, 600);

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
                        $tot += (float)$it['quantidade'] * (float)$it['valor_unitario'];
                        $qtdAduFmt = rtrim(rtrim(number_format((float)$it['quantidade'], 5, ',', '.'), '0'), ',');
                        $msg .= "📦 ".($it['descricao_ajustada'] ?? 'Prod')."\n   {$qtdAduFmt} kg | R$ {$it['valor_unitario']}\n";
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
            'log_id' => $usuarioId,
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
        if (!is_array($dados) || empty($dados['itens'])) {
            Cache::forget('wa_nfe_estado_' . $numero);
            return response()->json(['status' => 'expirado']);
        }
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
        Cache::put('wa_nfe_estado_' . $numero, true, 600);
        $sender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero,
            '✅ Quantidades registradas. Informe agora o motivo da devolução.');
        return response()->json(['status' => 'aguardando_motivo']);
    }

    /**
     * Busca inteligente de cliente considerando Nome, Cidade, UF e Nome Fantasia
     */
    private function buscarClienteInteligente(int $empresaId, string $termoPesquisa, string $cidadeIa = '', string $ufIa = '', string $ufEmitente = 'BA'): ?Cliente
    {
        $termoBruto = trim(preg_replace('/\s+/', ' ', $termoPesquisa));
        if ($termoBruto === '') return null;

        $buscaDireta = Cliente::where('empresa_id', $empresaId)
            ->where(function ($q) use ($termoBruto) {
                $q->where('nome_fantasia', 'like', '%' . $termoBruto . '%')
                    ->orWhere('razao_social', 'like', '%' . $termoBruto . '%');
            })->get();

        if ($buscaDireta->count() === 1) {
            return $buscaDireta->first();
        }

        $ufAlvo = strtoupper(trim($ufIa));
        if ($ufAlvo === '' && preg_match('/\b(AC|AL|AP|AM|BA|CE|DF|ES|GO|MA|MT|MS|MG|PA|PB|PR|PE|PI|RJ|RN|RS|RO|RR|SC|SP|SE|TO|EX)\b$/i', $termoBruto, $matchUf)) {
            $ufAlvo = strtoupper($matchUf[1]);
        }

        $termoSemUf = trim(preg_replace('/\b(AC|AL|AP|AM|BA|CE|DF|ES|GO|MA|MT|MS|MG|PA|PB|PR|PE|PI|RJ|RN|RS|RO|RR|SC|SP|SE|TO|EX)\b$/i', '', $termoBruto));
        if ($termoSemUf === '') $termoSemUf = $termoBruto;

        $cidadeAlvo = trim($cidadeIa);
        $nomeEmpresaBusca = $termoSemUf;

        $palavras = array_values(array_filter(explode(' ', $termoSemUf)));
        if ($cidadeAlvo !== '') {
            $nomeEmpresaBusca = trim(str_ireplace($cidadeAlvo, '', $termoSemUf));
        } elseif (count($palavras) >= 2) {
            $possivelCidade = end($palavras);
            if (mb_strlen($possivelCidade) >= 4 && Cidade::where('nome', 'like', '%' . $possivelCidade . '%')->exists()) {
                $cidadeAlvo = $possivelCidade;
                array_pop($palavras);
                $nomeEmpresaBusca = implode(' ', $palavras);
            }
        }

        if ($nomeEmpresaBusca === '') {
            $nomeEmpresaBusca = $palavras[0] ?? $termoSemUf;
        }
        $primeiraPalavra = explode(' ', $nomeEmpresaBusca)[0];

        if ($cidadeAlvo !== '') {
            $cidadesIds = Cidade::where('nome', 'like', '%' . $cidadeAlvo . '%')
                ->when($ufAlvo !== '', fn($q) => $q->where('uf', $ufAlvo))
                ->pluck('id');

            if ($cidadesIds->isNotEmpty()) {
                $clientePorCidade = Cliente::where('empresa_id', $empresaId)
                    ->whereIn('cidade_id', $cidadesIds)
                    ->where(function ($q) use ($nomeEmpresaBusca, $primeiraPalavra) {
                        $q->where('razao_social', 'like', '%' . $nomeEmpresaBusca . '%')
                            ->orWhere('nome_fantasia', 'like', '%' . $nomeEmpresaBusca . '%')
                            ->orWhere('razao_social', 'like', '%' . $primeiraPalavra . '%')
                            ->orWhere('nome_fantasia', 'like', '%' . $primeiraPalavra . '%');
                    })->first();

                if ($clientePorCidade) return $clientePorCidade;
            }
        }

        if ($ufAlvo !== '') {
            $cidadesUfIds = Cidade::where('uf', $ufAlvo)->pluck('id');

            $clientePorUf = Cliente::where('empresa_id', $empresaId)
                ->whereIn('cidade_id', $cidadesUfIds)
                ->where(function ($q) use ($nomeEmpresaBusca, $primeiraPalavra) {
                    $q->where('razao_social', 'like', '%' . $nomeEmpresaBusca . '%')
                        ->orWhere('nome_fantasia', 'like', '%' . $nomeEmpresaBusca . '%')
                        ->orWhere('razao_social', 'like', '%' . $primeiraPalavra . '%')
                        ->orWhere('nome_fantasia', 'like', '%' . $primeiraPalavra . '%');
                })->first();

            if ($clientePorUf) return $clientePorUf;
        }

        $cidadesEmitenteIds = Cidade::where('uf', $ufEmitente)->pluck('id');
        $clientePadraoEstado = Cliente::where('empresa_id', $empresaId)
            ->whereIn('cidade_id', $cidadesEmitenteIds)
            ->where(function ($q) use ($nomeEmpresaBusca, $primeiraPalavra) {
                $q->where('razao_social', 'like', '%' . $nomeEmpresaBusca . '%')
                    ->orWhere('nome_fantasia', 'like', '%' . $nomeEmpresaBusca . '%')
                    ->orWhere('razao_social', 'like', '%' . $primeiraPalavra . '%')
                    ->orWhere('nome_fantasia', 'like', '%' . $primeiraPalavra . '%');
            })->first();

        if ($clientePadraoEstado) return $clientePadraoEstado;

        return Cliente::where('empresa_id', $empresaId)
            ->where(function ($q) use ($nomeEmpresaBusca, $primeiraPalavra) {
                $q->where('razao_social', 'like', '%' . $nomeEmpresaBusca . '%')
                    ->orWhere('nome_fantasia', 'like', '%' . $nomeEmpresaBusca . '%')
                    ->orWhere('razao_social', 'like', '%' . $primeiraPalavra . '%')
                    ->orWhere('nome_fantasia', 'like', '%' . $primeiraPalavra . '%');
            })->first();
    }

    // =========================================================================
    // MÓDULO DE EMISSÃO DE NOTA DE COMPRA VIA WHATSAPP
    // =========================================================================

    public function processarMensagemCompra($numero, $mensagem, $funcionario, $estadoId, $whatsappSender)
    {
        Log::info('--- NFE: PROCESSANDO NOTA DE COMPRA ---');

        $prompt = "
        Você é um assistente de faturamento de um ERP corporativo do ramo de sucatas metálicas.
        Analise a seguinte mensagem de NOTA DE COMPRA e extraia os dados estritamente em formato JSON.

        Regras de Extração:
        - fornecedor_nome_pesquisa: Nome ou apelido da pessoa física fornecedora (ex: 'JORGE ROSA DE ALMEIDA' ou 'JORGE ROSA').
        - itens: Extraia a descrição do produto (ex: 'FERRO', 'ALUMINIO', 'COBRE'), quantidade em quilos e valor unitário. Se o montante total vier em dinheiro (ex: 'R$ 2500,00 a R$ 0,80'), calcule a quantidade dividindo o total pelo valor unitário, ou extraia a quantidade informada.

        Mensagem do Operador: '{$mensagem}'

        Retorne APENAS um JSON válido nesta estrutura exata:
        {
            \"fornecedor_nome_pesquisa\": \"\",
            \"itens\": [
                {
                    \"descricao_venda\": \"\",
                    \"quantidade\": 0.00,
                    \"valor_unitario\": 0.00
                }
            ]
        }
        ";

        try {
            $responseStr = $this->consultarIaFiscal([['text' => $prompt]]);
            $respostaDecodificada = json_decode($responseStr, true);
            $jsonTexto = trim(str_replace(['```json', '```'], '', $respostaDecodificada['candidates'][0]['content']['parts'][0]['text'] ?? '{}'));
            $dadosIA = json_decode($jsonTexto, true);

            if (!is_array($dadosIA) || empty($dadosIA['fornecedor_nome_pesquisa'])) {
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠️ Não consegui identificar o fornecedor na nota de compra. Verifique o texto.");
                return response()->json(['status' => 'erro_fornecedor']);
            }

            $termoForn = trim($dadosIA['fornecedor_nome_pesquisa']);
            $fornecedores = Fornecedor::where('empresa_id', $funcionario->empresa_id)
                ->where(function($q) use ($termoForn) {
                    $q->where('razao_social', 'like', '%' . $termoForn . '%')
                        ->orWhere('nome_fantasia', 'like', '%' . $termoForn . '%');
                })
                ->get();

            if ($fornecedores->count() === 0) {
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Nenhum fornecedor encontrado com o nome '{$termoForn}'. Cadastre-o no ERP primeiro.");
                return response()->json(['status' => 'fornecedor_nao_encontrado']);
            }

            // HOMÓNIMOS / NOMES PARECIDOS
            if ($fornecedores->count() > 1) {
                $msgHom = "⚠️ Encontrei **vários fornecedores** com nomes parecidos. Responda com o número correspondente ao CPF correto:\n\n";
                foreach ($fornecedores as $idx => $f) {
                    $docFmt = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $f->cpf_cnpj);
                    $msgHom .= "*" . ($idx + 1) . "* - {$f->razao_social} (Apelido: {$f->nome_fantasia} | CPF: {$docFmt})\n";
                }
                Cache::put($this->chaveCache('compra_dados', $numero), [
                    'dados_ia' => $dadosIA,
                    'fornecedores_ids' => $fornecedores->pluck('id')->toArray()
                ], 600);
                Cache::put($estadoId, 'AGUARDANDO_SELECAO_FORNECEDOR_COMPRA', 600);
                Cache::put('wa_nfe_estado_' . $numero, true, 600);

                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, $msgHom);
                return response()->json(['status' => 'multiplos_fornecedores']);
            }

            $fornecedorUnico = $fornecedores->first();
            return $this->prepararResumoCompra($numero, $dadosIA, $fornecedorUnico, $funcionario, $estadoId, $whatsappSender);

        } catch (\Exception $e) {
            Log::error("Erro Nota de Compra WhatsApp: " . $e->getMessage());
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠️ Falha ao processar a nota de compra.");
            return response()->json(['status' => 'erro']);
        }
    }

    public function processarSelecaoFornecedorCompra($numero, $mensagem, $funcionario, $estadoId, $whatsappSender)
    {
        $sessaoCompra = Cache::get($this->chaveCache('compra_dados', $numero));
        if (!is_array($sessaoCompra)) {
            Cache::forget('wa_nfe_estado_' . $numero);
            Cache::forget($estadoId);
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Sessão expirada. Envie a compra novamente.");
            return response()->json(['status' => 'expirado']);
        }

        $opcao = (int) trim($mensagem);
        $idsForn = $sessaoCompra['fornecedores_ids'] ?? [];
        if (!isset($idsForn[$opcao - 1])) {
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠️ Opção inválida. Digite o número correspondente da lista.");
            return response()->json(['status' => 'opcao_invalida']);
        }

        $fornecedorEscolhido = Fornecedor::find($idsForn[$opcao - 1]);
        return $this->prepararResumoCompra($numero, $sessaoCompra['dados_ia'], $fornecedorEscolhido, $funcionario, $estadoId, $whatsappSender);
    }

    private function prepararResumoCompra($numero, $dadosIA, $fornecedor, $funcionario, $estadoId, $whatsappSender)
    {
        foreach ($dadosIA['itens'] as $kItem => $itemIA) {
            $descOriginal = mb_strtoupper(trim($itemIA['descricao_venda']), 'UTF-8');
            $temMel = str_contains($descOriginal, 'MEL');
            $temMisto = str_contains($descOriginal, 'MISTO');
            $ehCobre = str_contains($descOriginal, 'COBRE') || $temMel || ($temMisto && !str_contains($descOriginal, 'PERFIL'));

            $produto = null;
            if ($ehCobre) {
                $produto = Produto::where('empresa_id', $funcionario->empresa_id)
                    ->where('nome', 'like', '%DESPERDICIOS E RESIDUOS E SUCATA COBRE%')
                    ->first()
                    ?? Produto::where('empresa_id', $funcionario->empresa_id)
                        ->where('nome', 'like', '%SUCATA DE COBRE%')
                        ->first();
            }

            if (!$produto) {
                $produto = Produto::where('empresa_id', $funcionario->empresa_id)
                    ->where('nome', 'like', '%' . $descOriginal . '%')
                    ->first()
                    ?? Produto::where('empresa_id', $funcionario->empresa_id)
                        ->where('nome', 'like', '%' . explode(' ', $descOriginal)[0] . '%')
                        ->first();
            }

            $nomeAjustado = $produto ? mb_strtoupper(trim($produto->nome), 'UTF-8') : $descOriginal;
            if ($temMel && !str_contains($nomeAjustado, 'MEL')) $nomeAjustado .= " MEL";
            if ($temMisto && !str_contains($nomeAjustado, 'MISTO')) $nomeAjustado .= " MISTO";

            $dadosIA['itens'][$kItem]['produto_id'] = $produto ? $produto->id : null;
            $dadosIA['itens'][$kItem]['descricao_ajustada'] = $nomeAjustado;
        }

        $dadosIA['fornecedor_id'] = $fornecedor->id;
        Cache::put($this->chaveCache('compra_dados', $numero), $dadosIA, 600);
        Cache::put($estadoId, 'AGUARDANDO_CONFIRMACAO_COMPRA', 600);
        Cache::put('wa_nfe_estado_' . $numero, true, 600);

        $msgResumo = "📥 *RESUMO DA NOTA DE COMPRA*\n\n";
        $msgResumo .= "👤 Fornecedor: {$fornecedor->razao_social} (CPF: {$fornecedor->cpf_cnpj})\n";
        $msgResumo .= "⚙️ Natureza: COMPRA DE MERCADORIAS\n";
        $msgResumo .= "💳 Pagamento: À Vista (Integrado ao Contas a Pagar)\n";
        $msgResumo .= "📂 Categoria: compra de sucata pf\n\n*Itens:*\n";

        $valorTotal = 0;
        foreach ($dadosIA['itens'] as $item) {
            $sub = (float)$item['quantidade'] * (float)$item['valor_unitario'];
            if ($sub <= 0 && isset($item['valor_total'])) $sub = (float)$item['valor_total'];
            $valorTotal += $sub;
            $qtdFmt = rtrim(rtrim(number_format((float)$item['quantidade'], 5, ',', '.'), '0'), ',');
            $msgResumo .= "📦 " . $item['descricao_ajustada'] . "\n   {$qtdFmt} kg | R$ " . number_format((float)$item['valor_unitario'], 2, ',', '.') . "\n";
        }

        $msgResumo .= "\n💰 *Total da Compra:* R$ " . number_format($valorTotal, 2, ',', '.') . "\n\n";
        $msgResumo .= "Confirma a gravação desta compra no ERP?\n*1* - SIM\n*2* - NÃO (Cancelar)";

        $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, $msgResumo);
        return response()->json(['status' => 'resumo_compra']);
    }

    public function processarConfirmacaoFinalCompra($numero, $mensagem, $funcionario, $estadoId, $whatsappSender)
    {
        if (trim($mensagem) == '1') {
            $dadosCompra = Cache::get($this->chaveCache('compra_dados', $numero));

            Cache::forget($this->chaveCache('compra_dados', $numero));
            Cache::forget($estadoId);
            Cache::forget('wa_nfe_estado_' . $numero);

            if (!is_array($dadosCompra)) {
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Dados da compra expirados ou já processados.");
                return response()->json(['status' => 'expirado_ou_duplicado']);
            }

            try {
                DB::beginTransaction();

                $fornecedor = Fornecedor::where('empresa_id', $funcionario->empresa_id)->findOrFail($dadosCompra['fornecedor_id']);

                $natureza = NaturezaOperacao::where('empresa_id', $funcionario->empresa_id)
                    ->where('natureza', 'like', '%COMPRA%')
                    ->where('natureza', 'not like', '%DEVOLU%')
                    ->first();

                $categoriaConta = CategoriaConta::where('empresa_id', $funcionario->empresa_id)
                    ->where('nome', 'like', '%compra de sucata pf%')
                    ->first()
                    ?? CategoriaConta::where('empresa_id', $funcionario->empresa_id)->first();

                $valorTotal = 0;
                foreach ($dadosCompra['itens'] as $item) {
                    $sub = (float)$item['quantidade'] * (float)$item['valor_unitario'];
                    $valorTotal += $sub;
                }

                // Código SEFAZ '01' (Dinheiro) para preenchimento da tag <tPag>
                $tipoPagamentoSefaz = '01';

                $compra = Compra::create([
                    'empresa_id' => $funcionario->empresa_id,
                    'filial_id' => $funcionario->filial_id ?? null,
                    'fornecedor_id' => $fornecedor->id,
                    'usuario_id' => $funcionario->usuario_id,
                    'natureza_id' => $natureza ? $natureza->id : null,
                    'categoria_conta_id' => $categoriaConta ? $categoriaConta->id : null,
                    'nf' => '0',
                    'numero_emissao' => '0',
                    'data_emissao' => date('Y-m-d H:i:s'),
                    'valor' => $valorTotal,
                    'desconto' => 0.00,
                    'acrescimo' => 0.00,
                    'estado' => 'NOVO',
                    'tipo' => 9,
                    'tipo_pagamento' => $tipoPagamentoSefaz,
                ]);

                $stockMove = new StockMove();
                foreach ($dadosCompra['itens'] as $itemIA) {
                    $produtoId = $itemIA['produto_id'] ?? null;
                    if (!$produtoId) throw new \Exception("Produto não localizado no ERP para a compra.");

                    $prod = Produto::where('empresa_id', $funcionario->empresa_id)->findOrFail($produtoId);
                    $qtd = (float)$itemIA['quantidade'];
                    $vUnit = (float)$itemIA['valor_unitario'];

                    ItemCompra::create([
                        'compra_id' => $compra->id,
                        'produto_id' => $prod->id,
                        'quantidade' => $qtd,
                        'valor_unitario' => $vUnit,
                        'unidade_compra' => $prod->unidade_compra ?? 'KG',
                    ]);

                    if ($prod->gerenciar_estoque) {
                        $stockMove->pluStock(
                            $prod->id,
                            $qtd * $prod->conversao_unitaria,
                            $vUnit,
                            $compra->filial_id,
                            'compra',
                            $compra->id,
                            date('Y-m-d')
                        );
                    }
                }

                ContaPagar::create([
                    'compra_id' => $compra->id,
                    'fornecedor_id' => $fornecedor->id,
                    'usuario_id' => $funcionario->usuario_id,
                    'numero_nota_fiscal' => '0',
                    'data_emissao' => date('Y-m-d'),
                    'data_vencimento' => date('Y-m-d'),
                    'data_pagamento' => date('Y-m-d'),
                    'valor_integral' => $valorTotal,
                    'valor_pago' => $valorTotal,
                    'status' => true,
                    'tipo_pagamento' => 'dinheiro',
                    'referencia' => "Compra Manual via WhatsApp ID: {$compra->id}",
                    'categoria_id' => $categoriaConta ? $categoriaConta->id : null,
                    'empresa_id' => $funcionario->empresa_id,
                    'filial_id' => $funcionario->filial_id ?? null,
                    'usuario_baixa_id' => $funcionario->usuario_id
                ]);

                DB::commit();

                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "🚀 A transmitir a Nota de Compra #{$compra->id} para a SEFAZ... Aguarde.");

                $this->forcarLoginMestre($funcionario->empresa_id, $funcionario->usuario_id);

                $naturezaIdParaNfe = $natureza ? $natureza->id : null;

                try {
                    $purchaseController = app(\App\Http\Controllers\PurchaseController::class);

                    $definirContexto = function (int $empresa, int $usuario, $filial) {
                        $this->empresa_id = $empresa;
                        $this->usuario_id = $usuario;
                        $this->filial_id  = $filial;
                    };
                    $definirContexto->call(
                        $purchaseController,
                        (int) $funcionario->empresa_id,
                        (int) $funcionario->usuario_id,
                        $funcionario->filial_id ?? null
                    );

                    $requestEntrada = new Request([
                        'compra_id' => $compra->id,
                        'natureza' => $naturezaIdParaNfe,
                        'tipo_pagamento' => $tipoPagamentoSefaz,
                    ]);

                    $respostaTransmissao = $purchaseController->gerarEntrada($requestEntrada);

                    $resultado = [];
                    $conteudoBruto = '';
                    if (method_exists($respostaTransmissao, 'getData')) {
                        $dadosResp = $respostaTransmissao->getData(true);
                        if (is_array($dadosResp)) {
                            $resultado = $dadosResp;
                        } else {
                            $conteudoBruto = (string) $dadosResp;
                        }
                    } elseif (method_exists($respostaTransmissao, 'getContent')) {
                        $conteudoBruto = (string) $respostaTransmissao->getContent();
                        $resultado = json_decode($conteudoBruto, true) ?? [];
                    }

                    $compra->refresh();

                    if (($resultado['sucesso'] ?? false) || strtoupper((string) $compra->estado) === 'APROVADO') {
                        $nfeNum = $compra->numero_emissao ?: $compra->nf;

                        ContaPagar::where('compra_id', $compra->id)->update([
                            'referencia' => "COMPRA DE MERCADORIA REF. NF-E $nfeNum",
                            'numero_nota_fiscal' => $nfeNum
                        ]);

                        $whatsappSender->enviarRespostaWhatsApp(
                            $funcionario->empresa_id,
                            $numero,
                            "✅ *Nota de Compra Aprovada na SEFAZ!* \n\nID: #{$compra->id} | NF-e: #{$nfeNum}\nChave: {$compra->chave}\nValor: R$ " . number_format($valorTotal, 2, ',', '.') . "\nBaixada no Contas a Pagar (*compra de sucata pf*)."
                        );
                    } else {
                        $motivoRejeicao = $resultado['mensagem']
                            ?? $resultado['xMotivo']
                            ?? ($conteudoBruto !== '' ? strip_tags($conteudoBruto) : 'Rejeição SEFAZ');

                        $whatsappSender->enviarRespostaWhatsApp(
                            $funcionario->empresa_id,
                            $numero,
                            "⚠ A compra #{$compra->id} foi salva no ERP, mas a SEFAZ rejeitou:\n\n_{$motivoRejeicao}_"
                        );
                    }

                } catch (\Throwable $exTrans) {
                    Log::error("Erro na execução direta de PurchaseController::gerarEntrada via WhatsApp: " . $exTrans->getMessage());
                    $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠ Compra #{$compra->id} salva, mas falhou ao transmitir: " . $exTrans->getMessage());
                }

                return response()->json(['status' => 'compra_processada']);

            } catch (\Exception $e) {
                if (DB::transactionLevel() > 0) DB::rollBack();
                Log::error("Erro Transmissão Compra WhatsApp: " . $e->getMessage());
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Erro ao processar a compra: " . $e->getMessage());
                return response()->json(['status' => 'erro']);
            }
        } else {
            Cache::forget($this->chaveCache('compra_dados', $numero));
            Cache::forget($estadoId);
            Cache::forget('wa_nfe_estado_' . $numero);
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Operação de compra cancelada.");
            return response()->json(['status' => 'cancelado']);
        }
    }

    public function processarMensagemTransferencia($numero, $mensagem, $funcionario, $estadoId, $whatsappSender)
    {
        Log::info('--- NFE: PROCESSANDO TRANSFERÊNCIA DE ESTOQUE ---');

        // Usa a coluna DESCRICAO para ajudar a IA e montar a lista corretamente
        $filiais = \App\Models\Filial::where('empresa_id', $funcionario->empresa_id)->pluck('descricao', 'id')->toArray();
        $listaFiliaisStr = "Matriz, " . collect($filiais)->map(fn($nome, $id) => "$nome (ID: $id)")->implode(', ');

        $prompt = "
        Você é um assistente de faturamento de um ERP corporativo do ramo de sucatas.
        Analise a seguinte mensagem de TRANSFERÊNCIA DE LOCAIS e extraia os dados estritamente em formato JSON.

        Locais disponíveis na empresa: {$listaFiliaisStr}

        Regras de Extração:
        - origem_pesquisa: Nome da filial ou 'MATRIZ' se for a matriz.
        - destino_pesquisa: Nome da filial ou 'MATRIZ' se for a matriz.
        - itens: Extraia a descrição do produto e a quantidade exata em quilos ou unidades.

        Mensagem do Operador: '{$mensagem}'

        Retorne APENAS um JSON válido nesta estrutura exata:
        {
            \"origem_pesquisa\": \"\",
            \"destino_pesquisa\": \"\",
            \"itens\": [
                {
                    \"descricao_venda\": \"\",
                    \"quantidade\": 0.00
                }
            ]
        }
        ";

        try {
            $responseStr = $this->consultarIaFiscal([['text' => $prompt]]);
            $respostaDecodificada = json_decode($responseStr, true);
            $jsonTexto = trim(str_replace(['```json', '```'], '', $respostaDecodificada['candidates'][0]['content']['parts'][0]['text'] ?? '{}'));
            $dadosIA = json_decode($jsonTexto, true);

            if (!is_array($dadosIA) || empty($dadosIA['origem_pesquisa']) || empty($dadosIA['destino_pesquisa'])) {
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠️ Não consegui identificar a origem ou o destino da transferência. Verifique o texto.");
                return response()->json(['status' => 'erro_locais']);
            }

            // Busca inteligente: Inclui a coluna DESCRICAO
            $buscarFilialInteligente = function ($nomePesquisa, $empresaId) {
                $termo = mb_strtoupper(trim($nomePesquisa), 'UTF-8');
                if (str_contains($termo, 'MATRIZ')) return null;

                $termoLimpo = trim(str_replace(['FILIAL', 'LOJA', 'UNIDADE'], '', $termo));

                $filial = \App\Models\Filial::where('empresa_id', $empresaId)
                    ->where(function($q) use ($termo, $termoLimpo) {
                        $q->where('razao_social', 'like', "%{$termo}%")
                            ->orWhere('nome_fantasia', 'like', "%{$termo}%")
                            ->orWhere('descricao', 'like', "%{$termo}%") // <-- Busca na nova coluna
                            ->orWhere('razao_social', 'like', "%{$termoLimpo}%")
                            ->orWhere('nome_fantasia', 'like', "%{$termoLimpo}%")
                            ->orWhere('descricao', 'like', "%{$termoLimpo}%"); // <-- Busca na nova coluna sem "Filial"
                    })->first();

                return $filial ? $filial->id : false;
            };

            $saidaId = $buscarFilialInteligente($dadosIA['origem_pesquisa'], $funcionario->empresa_id);
            if ($saidaId === false) {
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Não encontrei a filial de *Origem* ({$dadosIA['origem_pesquisa']}) no sistema. Verifique o nome.");
                return response()->json(['status' => 'origem_nao_encontrada']);
            }

            $entradaId = $buscarFilialInteligente($dadosIA['destino_pesquisa'], $funcionario->empresa_id);
            if ($entradaId === false) {
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Não encontrei a filial de *Destino* ({$dadosIA['destino_pesquisa']}) no sistema. Verifique o nome.");
                return response()->json(['status' => 'destino_nao_encontrado']);
            }

            if ($saidaId === $entradaId) {
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ O local de origem não pode ser igual ao local de destino.");
                return response()->json(['status' => 'locais_iguais']);
            }

            // Busca Natureza de Operação de Transferência padrão
            $naturezaTransf = NaturezaOperacao::where('empresa_id', $funcionario->empresa_id)
                ->where(fn($q) => $q->where('natureza', 'like', '%TRANSF%')->orWhere('natureza', 'like', '%TRANSFERENCIA%'))
                ->first() ?? NaturezaOperacao::where('empresa_id', $funcionario->empresa_id)->first();

            foreach ($dadosIA['itens'] as $kItem => $itemIA) {
                $descOriginal = mb_strtoupper(trim($itemIA['descricao_venda']), 'UTF-8');
                [$produtoId, $nomeAjustado] = $this->resolverProdutoSucata($funcionario->empresa_id, $descOriginal);

                $dadosIA['itens'][$kItem]['produto_id'] = $produtoId;
                $dadosIA['itens'][$kItem]['descricao_ajustada'] = $nomeAjustado;
                $dadosIA['itens'][$kItem]['quantidade'] = (float)($itemIA['quantidade'] ?? 0);
            }

            $dadosIA['filial_saida_id'] = $saidaId;
            $dadosIA['filial_entrada_id'] = $entradaId;
            $dadosIA['natureza_id'] = $naturezaTransf ? $naturezaTransf->id : null;

            Cache::put($this->chaveCache('transf_dados', $numero), $dadosIA, 600);
            Cache::put($estadoId, 'AGUARDANDO_CONFIRMACAO_TRANSFERENCIA', 600);
            Cache::put('wa_nfe_estado_' . $numero, true, 600);

            // Exibe a 'descricao' da filial se for diferente de null
            $nomeOrigemExibir = ($saidaId !== null) ? (\App\Models\Filial::find($saidaId)->descricao ?? 'Filial') : 'Matriz';
            $nomeDestinoExibir = ($entradaId !== null) ? (\App\Models\Filial::find($entradaId)->descricao ?? 'Filial') : 'Matriz';
            $natExibir = $naturezaTransf ? $naturezaTransf->natureza : 'TRANSFERENCIA DE MERCADORIAS';

            $msgResumo = "🔄 *RESUMO DA TRANSFERÊNCIA DE ESTOQUE*\n\n";
            $msgResumo .= "📤 *Origem (Saída):* {$nomeOrigemExibir}\n";
            $msgResumo .= "📥 *Destino (Entrada):* {$nomeDestinoExibir}\n";
            $msgResumo .= "⚙️ *Natureza:* {$natExibir}\n";
            $msgResumo .= "📝 *Informações Complementares:* \nNão incidência de ICMS nas transferências de mercadorias entre estabelecimentos do mesmo titular, nos termos da ADC 49 do STF e Lei Complementar nº 204/2023.\n\n*Itens:*\n";

            foreach ($dadosIA['itens'] as $item) {
                $qtdFmt = rtrim(rtrim(number_format((float)$item['quantidade'], 4, ',', '.'), '0'), ',');
                $msgResumo .= "📦 " . $item['descricao_ajustada'] . "\n   {$qtdFmt} kg\n";
            }

            $msgResumo .= "\nConfirma a realização da transferência e emissão da NF-e?\n*1* - SIM\n*2* - NÃO (Cancelar)";

            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, $msgResumo);
            return response()->json(['status' => 'resumo_transferencia']);

        } catch (\Exception $e) {
            Log::error("Erro Transferência WhatsApp: " . $e->getMessage());
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠️ Falha ao processar a transferência.");
            return response()->json(['status' => 'erro']);
        }
    }

    public function processarConfirmacaoFinalTransferencia($numero, $mensagem, $funcionario, $estadoId, $whatsappSender)
    {
        if (trim($mensagem) == '1') {
            $dadosTransf = Cache::get($this->chaveCache('transf_dados', $numero));

            Cache::forget($this->chaveCache('transf_dados', $numero));
            Cache::forget($estadoId);
            Cache::forget('wa_nfe_estado_' . $numero);

            if (!is_array($dadosTransf)) {
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Dados da transferência expirados.");
                return response()->json(['status' => 'expirado']);
            }

            try {
                DB::beginTransaction();

                $saidaId = $dadosTransf['filial_saida_id'];
                $entradaId = $dadosTransf['filial_entrada_id'];
                $agora = now();

                // Calcula o peso total da transferência
                $pesoTotalTransferencia = 0;
                foreach ($dadosTransf['itens'] as $itemIA) {
                    $pesoTotalTransferencia += (float) ($itemIA['quantidade'] ?? 0);
                }

                // 1. Cria o registro na tabela transferencias garantindo o peso com 3 casas decimais
                $transferencia = \App\Models\Transferencia::create([
                    'empresa_id' => $funcionario->empresa_id,
                    'filial_saida_id' => $saidaId,
                    'filial_entrada_id' => $entradaId,
                    'natureza_id' => $dadosTransf['natureza_id'] ?? null,
                    'peso_liquido' => number_format($pesoTotalTransferencia, 3, '.', ''),
                    'peso_bruto' => number_format($pesoTotalTransferencia, 3, '.', ''),
                    'observacao' => 'Não incidência de ICMS nas transferências de mercadorias entre estabelecimentos do mesmo titular, nos termos da ADC 49 do STF e Lei Complementar nº 204/2023.',
                    'usuario_id' => $funcionario->usuario_id,
                    'tpNF' => 1, // Saída
                    'finNFe' => 1,
                    'estado' => 'novo'
                ]);

                $stockMove = new StockMove();

                foreach ($dadosTransf['itens'] as $itemIA) {
                    $produtoId = $itemIA['produto_id'] ?? null;
                    if (!$produtoId) throw new \Exception("Produto não localizado para a transferência.");

                    $p = Produto::findOrFail($produtoId);
                    $quantidade = (float) $itemIA['quantidade'];

                    // Busca a configuração de estoque negativo (Matriz ou Filial)
                    $permiteEstoqueNegativo = false;
                    if ($saidaId !== null) {
                        $filial = \App\Models\Filial::find($saidaId);
                        if ($filial && isset($filial->permitir_estoque_negativo) && $filial->permitir_estoque_negativo == 1) {
                            $permiteEstoqueNegativo = true;
                        }
                    } else {
                        $configNota = \App\Models\ConfigNota::where('empresa_id', $funcionario->empresa_id)->first();
                        if ($configNota && isset($configNota->permitir_estoque_negativo) && $configNota->permitir_estoque_negativo == 1) {
                            $permiteEstoqueNegativo = true;
                        }
                    }

                    // Valida estoque na origem
                    $estoqueAtual = \App\Models\Estoque::where('produto_id', $produtoId)
                        ->when($saidaId !== null, fn($q) => $q->where('filial_id', $saidaId))
                        ->first();

                    if (!$permiteEstoqueNegativo && (!$estoqueAtual || $estoqueAtual->quantidade < $quantidade)) {
                        throw new \Exception("Estoque insuficiente do produto {$p->nome} na origem.");
                    }

                    // Atualiza locais do produto
                    $locais = json_decode($p->locais);
                    if (!is_array($locais)) $locais = [];
                    if (!in_array($entradaId, $locais)) {
                        array_push($locais, $entradaId);
                        $p->locais = $locais;
                        $p->save();
                    }

                    // Movimenta o estoque físico (Saída e Entrada)
                    $stockMove->downStock($produtoId, $quantidade, $saidaId);
                    $stockMove->pluStock($produtoId, $quantidade, -1, $entradaId);

                    $custoUnitario = $p->valor_compra ?? 0;
                    $valorTotal = $custoUnitario * $quantidade;

                    \App\Models\ItemTransferencia::create([
                        'transferencia_id' => $transferencia->id,
                        'produto_id' => $produtoId,
                        'quantidade' => $quantidade,
                        'valor_unitario' => $custoUnitario,
                        'sub_total' => $valorTotal,
                    ]);

                    // Histórico stock_movements (Saída e Entrada)
                    $saldoSaida = \App\Models\Estoque::where('produto_id', $produtoId)->where('filial_id', $saidaId)->value('quantidade') ?? 0;
                    $saldoEntrada = \App\Models\Estoque::where('produto_id', $produtoId)->where('filial_id', $entradaId)->value('quantidade') ?? 0;

                    DB::table('stock_movements')->insert([
                        'empresa_id' => $funcionario->empresa_id,
                        'filial_id' => $saidaId,
                        'usuario_id' => $funcionario->usuario_id,
                        'produto_id' => $produtoId,
                        'contexto' => 'transferencia',
                        'tipo' => 'saida',
                        'quantidade' => $quantidade,
                        'custo_unitario' => $custoUnitario,
                        'valor_total' => $valorTotal,
                        'origem_tipo' => \App\Models\Transferencia::class,
                        'origem_id' => $transferencia->id,
                        'idempotency_key' => 'transf_' . $transferencia->id . '_saida_' . $produtoId . '_' . time(),
                        'movimentado_em' => $agora,
                        'saldo_momento' => $saldoSaida,
                        'created_at' => $agora,
                        'updated_at' => $agora
                    ]);

                    DB::table('stock_movements')->insert([
                        'empresa_id' => $funcionario->empresa_id,
                        'filial_id' => $entradaId,
                        'usuario_id' => $funcionario->usuario_id,
                        'produto_id' => $produtoId,
                        'contexto' => 'transferencia',
                        'tipo' => 'entrada',
                        'quantidade' => $quantidade,
                        'custo_unitario' => $custoUnitario,
                        'valor_total' => $valorTotal,
                        'origem_tipo' => \App\Models\Transferencia::class,
                        'origem_id' => $transferencia->id,
                        'idempotency_key' => 'transf_' . $transferencia->id . '_entrada_' . $produtoId . '_' . time(),
                        'movimentado_em' => $agora,
                        'saldo_momento' => $saldoEntrada,
                        'created_at' => $agora,
                        'updated_at' => $agora
                    ]);
                }

                DB::commit();

                // 2. Dispara a Transmissão da NF-e de Transferência via TransferenciaController
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "🚀 A transmitir a Nota de Transferência #{$transferencia->id} para a SEFAZ... Aguarde.");

                $this->forcarLoginMestre($funcionario->empresa_id, $funcionario->usuario_id);

                $transfController = app(\App\Http\Controllers\TransferenciaController::class);
                $definirContexto = function (int $empresa) { $this->empresa_id = $empresa; };
                $definirContexto->call($transfController, (int) $funcionario->empresa_id);

                $requestTransf = new Request(['transferencia_id' => $transferencia->id]);

                ob_start();
                $transfController->transmitirNfe($requestTransf);
                $respTransmissao = ob_get_clean();

                $transferencia->refresh();

                if ($transferencia->estado === 'aprovado') {
                    $whatsappSender->enviarRespostaWhatsApp(
                        $funcionario->empresa_id,
                        $numero,
                        "✅ *Transferência e NF-e Aprovadas na SEFAZ!* \n\nTransferência ID: #{$transferencia->id}\nNF-e: #{$transferencia->numero_nfe}\nChave: {$transferencia->chave}\nEstoque atualizado na origem e destino com entrada automática gerada!"
                    );

                    // ==========================================
                    // GERAÇÃO E ENVIO DO DANFE (PDF) DA TRANSFERÊNCIA
                    // ==========================================
                    try {
                        $xmlPath = public_path('xml_nfe/' . $transferencia->chave . '.xml');
                        if (file_exists($xmlPath)) {
                            $xml = file_get_contents($xmlPath);
                            $danfe = new \App\Services\CustomDanfe($xml);

                            // Busca a logo correta (Filial ou Matriz)
                            $logo = null;
                            if ($saidaId) { // $saidaId já foi definido no início da função
                                $filial = \App\Models\Filial::find($saidaId);
                                if ($filial && $filial->logo) {
                                    $logoPath = public_path('logos/' . $filial->logo);
                                    if(file_exists($logoPath)) $logo = 'data://text/plain;base64,' . base64_encode(file_get_contents($logoPath));
                                }
                            } else {
                                $config = \App\Models\ConfigNota::where('empresa_id', $funcionario->empresa_id)->first();
                                if ($config && $config->logo) {
                                    $logoPath = public_path('logos/' . $config->logo);
                                    if(file_exists($logoPath)) $logo = 'data://text/plain;base64,' . base64_encode(file_get_contents($logoPath));
                                }
                            }

                            // Renderiza o PDF (com ou sem logo)
                            $pdf = $logo ? $danfe->render($logo) : $danfe->render();

                            // Prepara o diretório para salvar o PDF temporariamente
                            $dir = storage_path('app/public/whatsapp_nfe/' . $funcionario->empresa_id . '/' . $transferencia->id);
                            if (!is_dir($dir)) {
                                mkdir($dir, 0755, true);
                            }

                            $pdfFileName = 'DANFE_Transf_' . $transferencia->numero_nfe . '.pdf';
                            $pdfPath = $dir . DIRECTORY_SEPARATOR . $pdfFileName;

                            // Salva e envia a mídia via Connect|API
                            if (file_put_contents($pdfPath, $pdf)) {
                                app(\App\Services\ConnectApi\ConnectApiMessageService::class)->sendMedia(
                                    (int) $funcionario->empresa_id,
                                    (string) $numero,
                                    $pdfPath,
                                    "📄 DANFE Transferência #{$transferencia->numero_nfe}"
                                );
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Erro ao enviar DANFE de Transferência via WhatsApp: " . $e->getMessage());
                    }
                    // ==========================================

                } else {
                    $whatsappSender->enviarRespostaWhatsApp(
                        $funcionario->empresa_id,
                        $numero,
                        "⚠ A transferência #{$transferencia->id} foi gerada e o estoque movimentado, mas a SEFAZ retornou:\n\n_{$respTransmissao}_"
                    );
                }

                return response()->json(['status' => 'transferencia_processada']);

            } catch (\Exception $e) {
                if (DB::transactionLevel() > 0) DB::rollBack();
                Log::error("Erro Transmissão Transferencia WhatsApp: " . $e->getMessage());
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Erro ao processar a transferência: " . $e->getMessage());
                return response()->json(['status' => 'erro']);
            }
        } else {
            Cache::forget($this->chaveCache('transf_dados', $numero));
            Cache::forget($estadoId);
            Cache::forget('wa_nfe_estado_' . $numero);
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Operação de transferência cancelada.");
            return response()->json(['status' => 'cancelado']);
        }
    }

    private function gerarEntradaDestinatario(Transferencia $transferencia, $chave, $numeroNfe)
    {
        try {
            // 1. Verifica se já não gerou a compra para evitar duplicidade
            $existe = \App\Models\Compra::where('chave', $chave)->first();
            if ($existe) return;

            // 2. Carrega os dados da ORIGEM (Quem emitiu a nota fiscal de saída e é o fornecedor da entrada)
            $emitente = $transferencia->filial_saida_id == null
                ? \App\Models\ConfigNota::where('empresa_id', $transferencia->empresa_id)->first()
                : \App\Models\Filial::find($transferencia->filial_saida_id);

            if (!$emitente) {
                throw new \Exception("Emitente (origem) da transferência não encontrado.");
            }

            // Extrai e limpa estritamente apenas os números do CNPJ da origem
            $docEmitente = $emitente->cnpj ?? $emitente->cpf_cnpj ?? $emitente->documento ?? '';
            $cnpjEmitenteLimpo = preg_replace('/[^0-9]/', '', $docEmitente);

            if (empty($cnpjEmitenteLimpo)) {
                throw new \Exception("CNPJ do emitente (origem) está vazio.");
            }

            // Formata o CNPJ com máscara padrão para salvar caso precise cadastrar novo
            $cnpjEmitenteMask = preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpjEmitenteLimpo);

            // 3. BUSCA O FORNECEDOR: Compara o CNPJ limpo E TRAVA ESTRITAMENTE na empresa atual (`empresa_id`)
            $fornecedor = \App\Models\Fornecedor::where('empresa_id', $transferencia->empresa_id)
                ->get()
                ->first(function ($f) use ($cnpjEmitenteLimpo) {
                    return preg_replace('/[^0-9]/', '', $f->cpf_cnpj) === $cnpjEmitenteLimpo;
                });

            // Se não existir o fornecedor PARA ESSA EMPRESA com esse CNPJ exato, cadastra-o agora
            if (!$fornecedor) {
                $cidade = \App\Models\Cidade::where('nome', $emitente->municipio)->first();
                $fornecedor = \App\Models\Fornecedor::create([
                    'razao_social' => $emitente->razao_social,
                    'nome_fantasia' => $emitente->nome_fantasia ?? $emitente->razao_social,
                    'cpf_cnpj' => $cnpjEmitenteMask,
                    'rua' => $emitente->logradouro ?? 'N/D',
                    'numero' => $emitente->numero ?? 'S/N',
                    'bairro' => $emitente->bairro ?? 'N/D',
                    'cidade_id' => $cidade ? $cidade->id : 1,
                    'empresa_id' => $transferencia->empresa_id, // Vincula estritamente à empresa correta
                ]);
            }

            // 4. Calcula o total da nota e os pesos
            $total = 0;
            $pesoLiquido = $transferencia->peso_liquido ?? 0;
            $pesoBruto = $transferencia->peso_bruto ?? 0;

            foreach($transferencia->itens as $i) {
                $total += $i->sub_total;
            }

            // 5. Gera o cabeçalho da Compra (Entrada) amarrando no DESTINO correto e com o Fornecedor correto da empresa
            $compra = \App\Models\Compra::create([
                'fornecedor_id' => $fornecedor->id, // ID correto do fornecedor da mesma empresa
                'usuario_id' => $transferencia->usuario_id,
                'empresa_id' => $transferencia->empresa_id,
                'filial_id' => $transferencia->filial_entrada_id != null ? $transferencia->filial_entrada_id : null, // Destino (Matriz=null, Filial=ID)
                'nf' => (string) $numeroNfe,
                'numero_emissao' => $numeroNfe,
                'numero_sequencial' => 0,
                'chave' => $chave,
                'valor' => $total,
                'desconto' => 0.00,
                'acrescimo' => 0.00,
                'valor_frete' => 0.00,
                'peso_liquido' => $pesoLiquido,
                'peso_bruto' => $pesoBruto,
                'qtdVolumes' => 1,
                'numeracaoVolumes' => '',
                'especie' => '',
                'placa' => '',
                'uf' => '',
                'sequencia_cce' => 0,
                'estado' => 'aprovado',
                'xml_path' => '',
                'xml_importado' => 1,
                'observacao' => 'Entrada automática gerada por Transf. Interna #' . $transferencia->id,
                'data_emissao' => date('Y-m-d H:i:s'),
                'tipo' => 9,
                'tipo_pagamento' => '90',
            ]);

            // 6. Gera os itens da compra
            foreach($transferencia->itens as $i) {
                $produto = \App\Models\Produto::find($i->produto_id);
                $unidadeCompra = $produto ? $produto->unidade_compra : 'UN';

                \App\Models\ItemCompra::create([
                    'compra_id' => $compra->id,
                    'produto_id' => $i->produto_id,
                    'quantidade' => $i->quantidade,
                    'valor_unitario' => $i->valor_unitario,
                    'unidade_compra' => $unidadeCompra,
                    'codigo_siad' => '',
                ]);
            }

        } catch (\Exception $e) {
            \Log::error("Erro ao gerar Entrada Destinatário Transf: " . $e->getMessage() . ' na linha ' . $e->getLine());
        }
    }

    // =========================================================================
    // MÓDULO DE AGENDAMENTO DE PORTARIA VIA WHATSAPP (MULTIEMPRESA E COMPLETO)
    // =========================================================================

    public function processarAgendamentoPortaria($numero, $mensagem, $funcionarioRemetente, $whatsappSender = null)
    {
        try {
            $empresaId = $funcionarioRemetente->empresa_id;

            $enviarZap = function($destinatario, $texto) use ($empresaId) {
                try {
                    $numLimpo = preg_replace('/[^0-9]/', '', $destinatario);
                    if (strlen($numLimpo) >= 10) {
                        if (!str_starts_with($numLimpo, '55')) {
                            $numLimpo = '55' . $numLimpo;
                        }
                        app(\App\Services\ConnectApi\ConnectApiMessageService::class)->sendText((int)$empresaId, (string)$numLimpo, (string)$texto);
                    }
                } catch (\Throwable $ex) {
                    \Log::error("Erro ao enviar zap portaria: " . $ex->getMessage());
                }
            };

            $textoLimpo = str_replace("\xc2\xa0", ' ', $mensagem);

            if ( !preg_match('/nome\s*:/i', $textoLimpo) && !preg_match('/cpf\s*:/i', $textoLimpo) ) {
                $modelo  = "📋 *MODELO DE AGENDAMENTO - PORTARIA*\n";
                $modelo .= "Copie a mensagem abaixo, preencha os dados e envie:\n\n";
                $modelo .= "Agenda\n";
                $modelo .= "Nome: \n";
                $modelo .= "CPF: \n";
                $modelo .= "Tipo: Particular\n";
                $modelo .= "Quem procurar: \n";
                $modelo .= "Data: " . date('d/m/Y') . "\n";
                $modelo .= "Hora: " . date('H:i') . "\n";
                $modelo .= "Placa: ";

                $enviarZap($numero, $modelo);
                return response()->json(['status' => 'modelo_enviado']);
            }

            preg_match('/nome\s*:\s*(.+)/i', $textoLimpo, $mNome);
            preg_match('/cpf\s*:\s*([0-9.\-\/\s]+)/i', $textoLimpo, $mCpf);
            preg_match('/tipo\s*:\s*(.+)/i', $textoLimpo, $mTipo);
            preg_match('/quem\s*procurar\s*:\s*(.+)/i', $textoLimpo, $mProcurar);
            preg_match('/data\s*:\s*([0-9]{1,2}[\/\-][0-9]{1,2}[\/\-][0-9]{2,4})/i', $textoLimpo, $mData);
            preg_match('/hora(?:rio|s)?\s*:\s*([0-9]{1,2}\s*:\s*[0-9]{2})/i', $textoLimpo, $mHora);
            preg_match('/placa\s*:\s*([^\r\n]+)/i', $textoLimpo, $mPlaca);

            $nome         = trim($mNome[1] ?? '');
            $cpfLimpo     = preg_replace('/[^0-9]/', '', $mCpf[1] ?? '');
            $tipoTexto    = strtolower(trim($mTipo[1] ?? 'particular'));
            $quemProcurar = trim($mProcurar[1] ?? '');
            $dataStr      = str_replace('-', '/', trim($mData[1] ?? ''));
            $horaStr      = preg_replace('/\s+/', '', trim($mHora[1] ?? ''));
            $placaRaw     = trim($mPlaca[1] ?? '');

            $placa = null;
            if ($placaRaw !== '' && !preg_match('/^(opcional|nenhuma|nao|não|-)$/i', $placaRaw)) {
                $placaAlfanum = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $placaRaw));
                if (strlen($placaAlfanum) >= 7) {
                    $placa = substr($placaAlfanum, 0, 3) . '-' . substr($placaAlfanum, 3, 4);
                } else {
                    $placa = strtoupper($placaRaw);
                }
            }

            $erros = [];
            if ($nome === '') $erros[] = "• *Nome* não informado.";
            if (strlen($cpfLimpo) < 11) $erros[] = "• *CPF* inválido ou não informado.";
            if ($quemProcurar === '') $erros[] = "• *Quem procurar* (funcionário) não informado.";
            if ($dataStr === '') $erros[] = "• *Data* não informada (Ex: 19/10/2026).";
            if ($horaStr === '') $erros[] = "• *Hora* não informada (Ex: 11:30).";

            if (!empty($erros)) {
                $msgErro = "❌ *Dados incompletos no agendamento:*\n\n" . implode("\n", $erros);
                $enviarZap($numero, $msgErro);
                return response()->json(['status' => 'erro_validacao']);
            }

            $partesData = explode('/', $dataStr);
            if (count($partesData) === 3 && strlen($partesData[2]) === 2) {
                $partesData[2] = '20' . $partesData[2];
            }
            $dataFormatada = implode('/', $partesData);

            try {
                $dataHoraPrevista = \Carbon\Carbon::createFromFormat('d/m/Y H:i', "{$dataFormatada} {$horaStr}")->format('Y-m-d H:i:00');
            } catch (\Exception $e) {
                $enviarZap($numero, "❌ Data ou Hora em formato inválido. Use *DD/MM/AAAA* e *HH:MM*.");
                return response()->json(['status' => 'erro_data']);
            }

            $funcionarioVisitado = \App\Models\Funcionario::where('empresa_id', $empresaId)
                ->where('nome', 'like', "%{$quemProcurar}%")
                ->first();

            if (!$funcionarioVisitado) {
                $listaFunc = \App\Models\Funcionario::where('empresa_id', $empresaId)
                    ->limit(15)
                    ->pluck('nome')
                    ->implode(', ');

                $enviarZap($numero, "❌ Funcionário *{$quemProcurar}* não encontrado no cadastro.\n\n👥 *Funcionários disponíveis:* {$listaFunc}");
                return response()->json(['status' => 'funcionario_nao_encontrado']);
            }

            $cpfFormatado = strlen($cpfLimpo) === 11
                ? preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cpfLimpo)
                : $cpfLimpo;

            $visitante = \App\Models\Visitante::where('empresa_id', $empresaId)
                ->where(function ($q) use ($cpfLimpo, $cpfFormatado) {
                    $q->where('cpf', $cpfLimpo)->orWhere('cpf', $cpfFormatado);
                })
                ->first();

            if ($visitante) {
                if ($visitante->bloqueado == 1) {
                    $motivo = $visitante->motivo_bloqueio ?? 'Motivo não informado.';
                    $enviarZap($numero, "🚫 *ACESSO BLOQUEADO (LISTA NEGRA)*\n\nO visitante *{$visitante->nome}* (CPF: {$cpfFormatado}) possui restrição de acesso nesta empresa.\n*Motivo:* {$motivo}");
                    return response()->json(['status' => 'visitante_bloqueado']);
                }

                $visitante->update(['nome' => mb_strtoupper($nome, 'UTF-8')]);
            } else {
                try {
                    $visitante = \App\Models\Visitante::create([
                        'nome'       => mb_strtoupper($nome, 'UTF-8'),
                        'cpf'        => $cpfLimpo,
                        'empresa_id' => $empresaId
                    ]);
                } catch (\Throwable $exDup) {
                    $visitante = \App\Models\Visitante::where('empresa_id', $empresaId)
                        ->where(function ($q) use ($cpfLimpo, $cpfFormatado) {
                            $q->where('cpf', $cpfLimpo)->orWhere('cpf', $cpfFormatado);
                        })
                        ->first();

                    if (!$visitante) {
                        throw new \Exception("Erro ao processar cadastro do visitante: " . $exDup->getMessage());
                    }
                    $visitante->update(['nome' => mb_strtoupper($nome, 'UTF-8')]);
                }
            }

            $tipoLabel = 'Particular';
            if (strpos($tipoTexto, 'cliente') !== false) {
                $tipoLabel = 'Cliente';
                $cliente = \App\Models\Cliente::where('empresa_id', $empresaId)
                    ->where(function ($q) use ($cpfLimpo, $cpfFormatado, $nome) {
                        $q->where('cpf_cnpj', $cpfLimpo)
                            ->orWhere('cpf_cnpj', $cpfFormatado)
                            ->orWhere('razao_social', 'like', "%{$nome}%");
                    })->first();

                $visitante->update(['cliente_id' => $cliente ? $cliente->id : null, 'fornecedor_id' => null]);
            } elseif (strpos($tipoTexto, 'fornecedor') !== false) {
                $tipoLabel = 'Fornecedor';
                $fornecedor = \App\Models\Fornecedor::where('empresa_id', $empresaId)
                    ->where(function ($q) use ($cpfLimpo, $cpfFormatado, $nome) {
                        $q->where('cpf_cnpj', $cpfLimpo)
                            ->orWhere('cpf_cnpj', $cpfFormatado)
                            ->orWhere('razao_social', 'like', "%{$nome}%");
                    })->first();

                $visitante->update(['fornecedor_id' => $fornecedor ? $fornecedor->id : null, 'cliente_id' => null]);
            } else {
                $visitante->update(['cliente_id' => null, 'fornecedor_id' => null]);
            }

            $agendamento = \App\Models\PortariaAgendamento::create([
                'visitante_id'           => $visitante->id,
                'funcionario_id'         => $funcionarioVisitado->id,
                'usuario_criador_id'     => $funcionarioRemetente->usuario_id ?? 1,
                'empresa_id'             => $empresaId,
                'data_hora_prevista'     => $dataHoraPrevista,
                'placa_veiculo_prevista' => $placa,
                'status'                 => 'pendente'
            ]);

            if (!empty($visitante->telefone)) {
                $msgVisitante  = "🏢 *Agendamento Confirmado!*\n\n";
                $msgVisitante .= "Olá, *{$visitante->nome}*.\n";
                $msgVisitante .= "Sua visita está agendada para: *{$dataFormatada} às {$horaStr}*.\n";
                $msgVisitante .= "Procurar por: *{$funcionarioVisitado->nome}*.\n";
                $msgVisitante .= "Por favor, apresente-se na portaria.";
                $enviarZap($visitante->telefone, $msgVisitante);
            }

            $telVisitado = $funcionarioVisitado->celular ?? $funcionarioVisitado->telefone;
            if (!empty($telVisitado)) {
                $numVisitadoLimpo = preg_replace('/[^0-9]/', '', $telVisitado);
                if (substr($numVisitadoLimpo, -8) !== substr(preg_replace('/[^0-9]/', '', $numero), -8)) {
                    $msgFunc  = "📅 *Novo Agendamento de Portaria*\n\n";
                    $msgFunc .= "Olá, *{$funcionarioVisitado->nome}*.\n";
                    $msgFunc .= "Uma visita foi agendada para você:\n";
                    $msgFunc .= "👤 *Visitante:* {$visitante->nome}\n";
                    $msgFunc .= "🪪 *CPF:* {$cpfFormatado}\n";
                    $msgFunc .= "🏷️ *Tipo:* {$tipoLabel}\n";
                    $msgFunc .= "🕒 *Data/Hora:* {$dataFormatada} às {$horaStr}\n";
                    if ($placa) {
                        $msgFunc .= "🚗 *Placa:* {$placa}\n";
                    }
                    $enviarZap($numVisitadoLimpo, $msgFunc);
                }
            }

            $resposta  = "✅ *Agendamento de Portaria Criado!* (#{$agendamento->id})\n\n";
            $resposta .= "👤 *Visitante:* {$visitante->nome}\n";
            $resposta .= "🪪 *CPF:* {$cpfFormatado}\n";
            $resposta .= "🏷️ *Tipo:* {$tipoLabel}\n";
            $resposta .= "🧑‍💼 *Procurar por:* {$funcionarioVisitado->nome}\n";
            $resposta .= "📅 *Data/Hora:* {$dataFormatada} às {$horaStr}\n";
            $resposta .= "🚗 *Placa:* " . ($placa ?: 'Não informada (Opcional)');

            $enviarZap($numero, $resposta);

            return response()->json(['status' => 'agendamento_criado', 'id' => $agendamento->id]);

        } catch (\Throwable $e) {
            \Log::error("Erro ao processar agendamento de portaria via WhatsApp: " . $e->getMessage());
            try {
                app(\App\Services\ConnectApi\ConnectApiMessageService::class)->sendText((int)$funcionarioRemetente->empresa_id, (string)$numero, "❌ Erro ao registrar agendamento: " . $e->getMessage());
            } catch (\Throwable $ex) {}
            return response()->json(['status' => 'erro']);
        }
    }

    private function resolverProdutoSucata(int $empresaId, string $descricaoOriginal)
    {
        $descUpper = mb_strtoupper(trim($descricaoOriginal), 'UTF-8');
        $temMel = str_contains($descUpper, 'MEL');
        $temMisto = str_contains($descUpper, 'MISTO');
        $ehCobre = str_contains($descUpper, 'COBRE') || $temMel || ($temMisto && !str_contains($descUpper, 'PERFIL'));

        $produto = null;
        $nomeAjustado = $descUpper;
        $produtoId = null;

        if ($ehCobre) {
            // 1ª OPÇÃO: DESPERDICIOS E RESIDUOS E SUCATA COBRE
            $produto = \App\Models\Produto::where('empresa_id', $empresaId)
                ->where('nome', 'like', '%DESPERDICIOS E RESIDUOS E SUCATA COBRE%')
                ->first();

            // 2ª OPÇÃO: SUCATA DE COBRE
            if (!$produto) {
                $produto = \App\Models\Produto::where('empresa_id', $empresaId)
                    ->where(function($q) {
                        $q->where('nome', 'SUCATA DE COBRE')
                            ->orWhere('nome', 'like', '%SUCATA DE COBRE%');
                    })
                    ->orderByRaw("CASE WHEN UPPER(TRIM(nome)) = 'SUCATA DE COBRE' THEN 1 ELSE 2 END")
                    ->first();
            }

            // 3ª OPÇÃO: Sucata de cobre mel / Sucata de cobre misto
            if (!$produto) {
                if ($temMel && $temMisto) {
                    $produto = \App\Models\Produto::where('empresa_id', $empresaId)
                        ->where(function($q) {
                            $q->where('nome', 'like', '%SUCATA DE COBRE MEL%')
                                ->orWhere('nome', 'like', '%SUCATA DE COBRE MISTO%');
                        })->first();
                } elseif ($temMel) {
                    $produto = \App\Models\Produto::where('empresa_id', $empresaId)
                        ->where('nome', 'like', '%SUCATA DE COBRE MEL%')
                        ->first();
                } elseif ($temMisto) {
                    $produto = \App\Models\Produto::where('empresa_id', $empresaId)
                        ->where('nome', 'like', '%SUCATA DE COBRE MISTO%')
                        ->first();
                }
            }
        } else {
            // DEMAIS FAMÍLIAS DE SUCATA (Alumínio, Inox, Ferro, etc)
            $produto = \App\Models\Produto::where('empresa_id', $empresaId)
                ->where('nome', 'like', '%' . $descUpper . '%')
                ->first()
                ?? \App\Models\Produto::where('empresa_id', $empresaId)
                    ->where('nome', 'like', '%' . explode(' ', $descUpper)[0] . '%')
                    ->first();
        }

        if ($produto) {
            $produtoId = $produto->id;
            $nomeAjustado = mb_strtoupper(trim($produto->nome), 'UTF-8');
        }

        // Adiciona Mel e/ou Misto no final do complemento da descrição
        if ($temMel && !str_contains($nomeAjustado, 'MEL')) {
            $nomeAjustado .= " MEL";
        }
        if ($temMisto && !str_contains($nomeAjustado, 'MISTO')) {
            $nomeAjustado .= " MISTO";
        }

        return [$produtoId, $nomeAjustado];
    }

    // =========================================================================
    // MÓDULO: EMISSÃO EXPRESSA DE NF-E VIA PESAGEM (BALANÇA)
    // =========================================================================

    public function processarResumoPesagemWhatsApp($numero, $matches, $funcionario, $estadoId, $whatsappSender)
    {
        $pesagemId = (int) $matches[1];
        $valorStr = $matches[2] ?? null;

        // Trata o valor (se informado) convertendo vírgula para ponto
        $valorInformado = 0;
        if (!empty($valorStr)) {
            $valorStr = str_replace('.', '', $valorStr);
            $valorStr = str_replace(',', '.', $valorStr);
            $valorInformado = (float) $valorStr;
        }

        // Busca a pesagem 100% fiel ao banco de dados
        $pesagem = \App\Models\Pesagem::with(['cliente', 'veiculo', 'motorista', 'tickets.produto'])
            ->where('empresa_id', $funcionario->empresa_id)
            ->find($pesagemId);

        if (!$pesagem) {
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ A pesagem #{$pesagemId} não foi encontrada no sistema.");
            return response()->json(['status' => 'nao_encontrada']);
        }
        if ($pesagem->venda_id) {
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠️ A pesagem #{$pesagemId} já possui a Nota de Venda #{$pesagem->venda_id} vinculada.");
            return response()->json(['status' => 'ja_faturada']);
        }
        if ($pesagem->status !== 'concluído') {
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠️ A pesagem #{$pesagemId} ainda está '{$pesagem->status}'. Conclua a pesagem na balança antes de faturar.");
            return response()->json(['status' => 'nao_concluida']);
        }

        // Lógica para puxar produto, agrupar e calcular o peso líquido exato
        $itensResumo = [];
        $valorTotalNota = 0;
        $grupos = [];

        foreach ($pesagem->tickets as $t) {
            $pOrig = $t->produto;
            if(!$pOrig) continue;
            // Se o produto for sucata de ferro, verifica se tem referência atrelada
            $pId = $pOrig->produto_referenciado_id ?: $pOrig->id;
            $grupos[$pId][] = $t;
        }

        foreach ($grupos as $prodId => $ticketsGrupo) {
            $pGrupo = \App\Models\Produto::find($prodId);
            $entrada = $saida = $avulsa = 0;

            foreach ($ticketsGrupo as $t) {
                $pesoLiq = max(0, $t->peso - $t->peso_bag);
                if ($t->tipo === 'entrada') { $entrada += $pesoLiq; }
                elseif ($t->tipo === 'saida') { $saida += $pesoLiq; }
                else { $avulsa += $pesoLiq; }
            }
            $quantidadeLiquida = max(0, ($entrada + $avulsa) - $saida);

            // Preço unitário: se o cliente digitou no zap, usa ele. Se não, pega do cadastro do produto.
            $valorUnitario = $valorInformado > 0 ? $valorInformado : (float) $pGrupo->valor_venda;
            $subtotal = $quantidadeLiquida * $valorUnitario;
            $valorTotalNota += $subtotal;

            $itensResumo[] = [
                'nome' => mb_strtoupper($pGrupo->nome, 'UTF-8'),
                'quantidade' => $quantidadeLiquida,
                'valor_unitario' => $valorUnitario,
                'subtotal' => $subtotal
            ];
        }

        // Salva os dados na sessão para a próxima resposta
        Cache::put($this->chaveCache('pesagem_expressa_dados', $numero), [
            'pesagem_id' => $pesagemId,
            'valor_informado' => $valorInformado
        ], 600);
        Cache::put($estadoId, 'AGUARDANDO_CONFIRMACAO_PESAGEM_EXPRESSA', 600);
        Cache::put('wa_nfe_estado_' . $numero, true, 600);

        // Monta o Resumo Visual Bonito
        $clienteNome = $pesagem->cliente->razao_social ?? 'Não informado';
        $placa = $pesagem->veiculo->placa ?? $pesagem->placa_veiculo ?? 'N/A';
        $motorista = optional($pesagem->motorista)->nome ?? $pesagem->motorista_nome ?? 'N/A';

        $msgResumo = "⚖️ *RESUMO DA PESAGEM #{$pesagemId}*\n\n";
        $msgResumo .= "👤 *Cliente:* {$clienteNome}\n";
        $msgResumo .= "🚛 *Veículo:* {$placa} (Mot: {$motorista})\n\n";
        $msgResumo .= "*Itens Aferidos (Líquido):*\n";

        foreach ($itensResumo as $ir) {
            $qtdFmt = rtrim(rtrim(number_format($ir['quantidade'], 3, ',', '.'), '0'), ',');
            $vUnFmt = number_format($ir['valor_unitario'], 2, ',', '.');
            $msgResumo .= "📦 {$ir['nome']}\n   {$qtdFmt} kg | R$ {$vUnFmt}\n";
        }

        $msgResumo .= "\n💰 *Total da Venda:* R$ " . number_format($valorTotalNota, 2, ',', '.') . "\n\n";
        $msgResumo .= "Confirma a geração da venda e transmissão para a SEFAZ?\n*1* - SIM\n*2* - NÃO (Cancelar)";

        $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, $msgResumo);
        return response()->json(['status' => 'resumo_pesagem_enviado']);
    }

    public function processarConfirmacaoPesagemExpressa($numero, $mensagem, $funcionario, $estadoId, $whatsappSender)
    {
        if (trim($mensagem) == '1') {
            $dados = Cache::get($this->chaveCache('pesagem_expressa_dados', $numero));
            Cache::forget($this->chaveCache('pesagem_expressa_dados', $numero));
            Cache::forget($estadoId);
            Cache::forget('wa_nfe_estado_' . $numero);

            if (!$dados || !isset($dados['pesagem_id'])) {
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Sessão expirada. Por favor, envie o comando da pesagem novamente.");
                return response()->json(['status' => 'expirado']);
            }

            $pesagemId = $dados['pesagem_id'];
            $valorInformado = $dados['valor_informado'];

            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "🚀 Gerando a venda e transmitindo para a SEFAZ... Aguarde.");

            try {
                DB::beginTransaction();

                $pesagem = \App\Models\Pesagem::with(['veiculo', 'motorista', 'tickets'])->where('empresa_id', $funcionario->empresa_id)->find($pesagemId);

                // 1. Atualiza os preços dos tickets se o usuário informou valor no comando
                if ($valorInformado > 0) {
                    foreach($pesagem->tickets as $ticket) {
                        $ticket->valor_unitario = $valorInformado;
                        $ticket->save();
                    }
                }

                // 2. Monta informações complementares
                $placa = $pesagem->veiculo->placa ?? $pesagem->placa_veiculo ?? 'Não informada';
                $motorista = optional($pesagem->motorista)->nome ?? $pesagem->motorista_nome ?? 'Não informado';
                $obsCustomizada = "Venda gerada automaticamente a partir da pesagem #{$pesagem->id}.\nMotorista: {$motorista}\nPlaca: {$placa}";

                // 3. Aciona o PesagemController para faturar
                $pesagemController = app(\App\Http\Controllers\PesagemController::class);
                $definirContexto = function (int $empresa, int $usuario) {
                    $this->empresa_id = $empresa;
                    $this->usuario_id = $usuario;
                };
                $definirContexto->call($pesagemController, (int) $funcionario->empresa_id, (int) $funcionario->usuario_id);

                $resposta = $pesagemController->criarVendaDePesagemWhatsApp($pesagemId, $obsCustomizada);
                $dadosResp = $resposta->getData(true);

                if (!isset($dadosResp['venda_id'])) {
                    throw new \Exception($dadosResp['message'] ?? "Falha ao estruturar a venda.");
                }

                $vendaId = $dadosResp['venda_id'];
                DB::commit();

                // 4. Inicia emissão na SEFAZ
                $requestNf = new \Illuminate\Http\Request();
                $requestNf->merge([
                    'vendaId' => $vendaId,
                    'empresa_id' => $funcionario->empresa_id,
                    'consumidor_final' => 0
                ]);

                $this->forcarLoginMestre($funcionario->empresa_id, $funcionario->usuario_id);
                $nfController = app(\App\Http\Controllers\NotaFiscalController::class);
                $definirEmpresa = function (int $empresa) { $this->empresa_id = $empresa; };
                $definirEmpresa->call($nfController, (int) $funcionario->empresa_id);

                $nfController->gerarNf($requestNf);

                $vendaAlvo = \App\Models\Venda::find($vendaId);

                if (strtoupper($vendaAlvo->estado) === 'APROVADO') {
                    $nfeNum = $vendaAlvo->NfNumero ?? $vendaAlvo->id;
                    $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "✅ *Nota Fiscal Aprovada na SEFAZ!* \n\nPesagem: #{$pesagemId}\nNF-e: #{$nfeNum}\nChave: {$vendaAlvo->chave}");

                    try {
                        $this->enviarDocumentosFiscais($vendaAlvo, $numero, $funcionario);
                    } catch (\Exception $e) {}
                } else {
                    $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "⚠️ A Venda #{$vendaId} foi salva, mas a SEFAZ rejeitou a nota. Consulte os logs no ERP.");
                }

                return response()->json(['status' => 'sucesso']);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Erro NFe Pesagem WhatsApp: " . $e->getMessage());
                $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Erro ao faturar a pesagem: " . $e->getMessage());
                return response()->json(['status' => 'erro']);
            }
        } else {
            Cache::forget($this->chaveCache('pesagem_expressa_dados', $numero));
            Cache::forget($estadoId);
            Cache::forget('wa_nfe_estado_' . $numero);
            $whatsappSender->enviarRespostaWhatsApp($funcionario->empresa_id, $numero, "❌ Faturamento da pesagem cancelado.");
            return response()->json(['status' => 'cancelado']);
        }
    }
}
