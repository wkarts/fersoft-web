<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Funcionario;
use App\Models\PontoMarcacao;
use App\Models\TraccarConfig;
use App\Models\MovimentacaoVeiculo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class PontoWhatsAppController extends Controller
{
    public function receberMensagem(Request $request)
    {
        Log::info('--- WEBHOOK WHATSAPP RECEBIDO ---', $request->all());

        $payload = $request->all();

        // 🛑 TRAVA 1: Ignora mensagens de Status/Stories
        $remoteJid = $payload['data']['key']['remoteJid'] ?? '';
        if (str_contains($remoteJid, 'status@broadcast')) {
            return response()->json(['status' => 'status_ignorado']);
        }

        // 🛑 TRAVA 2: Ignora mensagens de Grupos
        if (str_ends_with($remoteJid, '@g.us')) {
            return response()->json(['status' => 'grupo_ignorado']);
        }

        // 🛑 TRAVA 3: Ignora mensagens enviadas pelo próprio bot
        $fromMe = $payload['data']['key']['fromMe'] ?? false;
        if ($fromMe) {
            return response()->json(['status' => 'from_me_ignorado']);
        }

        // 1. Extração antecipada e segura de texto, tipo de mensagem e áudio
        $texto = strtoupper(trim(
            $payload['data']['message']['conversation']
            ?? $payload['data']['message']['extendedTextMessage']['text']
            ?? $payload['message']
            ?? $payload['body']
            ?? ''
        ));

        $messageType = $payload['data']['messageType'] ?? $payload['data']['message']['messageType'] ?? '';
        $audioUrl = $payload['data']['message']['audioMessage']['mediaUrl'] ?? null;

        $remoteJid = $payload['data']['key']['remoteJid'] ?? '';
        $numLimpoOrigemFiscal = preg_replace('/\D/', '', explode('@', $remoteJid)[0]);
        $numeroDestinoEnvioFiscal = strlen($numLimpoOrigemFiscal) >= 10 && !str_starts_with($numLimpoOrigemFiscal, '55')
            ? '55' . $numLimpoOrigemFiscal
            : $numLimpoOrigemFiscal;

        // =========================================================================
        // 🚀 PRIORIDADE MÁXIMA: DESVIO IMEDIATO PARA O MÓDULO FISCAL (NFE / XML / DEVOLUÇÕES)
        // =========================================================================
        $estadoNfeId = 'wa_nfe_estado_' . $numeroDestinoEnvioFiscal;
        $temEstadoFiscalAtivo = \Illuminate\Support\Facades\Cache::has($estadoNfeId);

        // Deteta se é um ficheiro XML
        $fileName = $payload['data']['message']['documentMessage']['fileName'] ?? $payload['data']['message']['documentWithCaptionMessage']['message']['documentMessage']['fileName'] ?? '';
        $documentUrl = $payload['data']['message']['documentMessage']['mediaUrl'] ?? $payload['data']['message']['documentMessage']['url'] ?? '';
        $extensaoArq = pathinfo($fileName, PATHINFO_EXTENSION);

        $ehXmlDevolucao = ($messageType === 'documentMessage' || strtolower($extensaoArq) === 'xml' || str_contains(strtolower($documentUrl), '.xml'));

        $iniciouNfe = false;

        if ($temEstadoFiscalAtivo || $ehXmlDevolucao || strtoupper($texto) === 'CANCELAR' || preg_match('/^(?:IMPRIMIR\s+)?(?:DANFE|PDF|XML|NOTA|NFE|NF-E|EMITIR NOTA|FATURAR)/i', $texto) || $messageType === 'audioMessage' || !empty($audioUrl)) {
            $iniciouNfe = true;
        }

        if ($iniciouNfe) {
            $ultimos8Fiscal = strlen($numLimpoOrigemFiscal) >= 8 ? substr($numLimpoOrigemFiscal, -8) : $numLimpoOrigemFiscal;
            $funcionarioFiscal = Funcionario::where(function($q) use ($remoteJid, $ultimos8Fiscal) {
                $q->where('whatsapp_id', $remoteJid)
                    ->orWhereRaw("RIGHT(REGEXP_REPLACE(celular, '[^0-9]', ''), 8) = ?", [$ultimos8Fiscal])
                    ->orWhereRaw("RIGHT(REGEXP_REPLACE(telefone, '[^0-9]', ''), 8) = ?", [$ultimos8Fiscal]);
            })->first();

            $request->merge([
                'funcionario_obj' => $funcionarioFiscal,
                'numero_tratado' => $numeroDestinoEnvioFiscal,
                'texto_tratado' => $texto,
                'message_type' => $messageType,
                'audio_url' => $audioUrl,
                'file_name' => $fileName,
                'media_url' => $documentUrl
            ]);

            $nfeController = app(\App\Http\Controllers\Api\WhatsappNfeController::class);
            return $nfeController->webhook($request);
        }
        // =========================================================================

        // =========================================================================
        // 🛑 TRAVA ANTI-DUPLICAÇÃO DE MENSAGEM RÁPIDA (10 segundos)
        // =========================================================================
        $numLimpoOrigemCheck = preg_replace('/\D/', '', explode('@', $remoteJid)[0]);
        if (!empty($numLimpoOrigemCheck) && !empty($texto)) {
            $chaveCacheDuplicada = "web_ponto_lock_{$numLimpoOrigemCheck}_{$texto}";
            if (Cache::has($chaveCacheDuplicada)) {
                return response()->json(['status' => 'mensagem_duplicada_ignorada']);
            }
            Cache::put($chaveCacheDuplicada, true, 10);
        }

        $locationMsg = $payload['data']['message']['locationMessage'] ?? null;
        $latZap = $locationMsg['degreesLatitude'] ?? null;
        $lonZap = $locationMsg['degreesLongitude'] ?? null;

        $pushName = $payload['data']['pushName'] ?? null;

        // =========================================================================
        // 🚀 1.1 EXTRAÇÃO DOS IDENTIFICADORES DO WHATSAPP (LID E SENDER)
        // =========================================================================
        $lidCompleto = (!empty($remoteJid) && str_contains($remoteJid, '@lid')) ? $remoteJid : null;
        $lidPuro = $lidCompleto ? explode('@', $lidCompleto)[0] : null;

        $numeroOrigem = !str_contains($remoteJid, '@lid') ? preg_replace('/\D/', '', explode('@', $remoteJid)[0]) : null;

        $telefoneSemDdi = $numeroOrigem;
        if ($numeroOrigem && str_starts_with($numeroOrigem, '55') && strlen($numeroOrigem) >= 12) {
            $telefoneSemDdi = substr($numeroOrigem, 2);
        }

        $numLimpoOrigem = preg_replace('/\D/', '', explode('@', $remoteJid)[0]);
        $ultimos8 = strlen($numLimpoOrigem) >= 8 ? substr($numLimpoOrigem, -8) : $numLimpoOrigem;

        // 🛑 TRAVA 4: Se o número não tiver ao menos 8 dígitos válidos, encerra na hora
        if (empty($ultimos8) || strlen($ultimos8) < 8) {
            return response()->json(['status' => 'numero_invalido_ignorado']);
        }

        // =========================================================================
        // 🚀 1.2 PAREAMENTO EXPRESSO: "ATIVAR [ID]" (Ex: ATIVAR 1)
        // =========================================================================
        if (preg_match('/^(?:ATIVAR|CONECTAR|VINCULAR)\s+(\d+)$/i', $texto, $matches)) {
            $idFuncionario = (int) $matches[1];
            $funcAlvo = Funcionario::find($idFuncionario);

            if ($funcAlvo) {
                $idParaGravar = $lidPuro ?: $lidCompleto ?: $remoteJid;
                $funcAlvo->update(['whatsapp_id' => $idParaGravar]);

                $celularReal = preg_replace('/\D/', '', $funcAlvo->celular ?: $funcAlvo->telefone);
                if (!empty($celularReal) && !str_starts_with($celularReal, '55')) {
                    $celularReal = '55' . $celularReal;
                }

                $msgAtivacao = "✅ *DISPOSITIVO VINCULADO COM SUCESSO!*\n\n"
                    . "Olá, *{$funcAlvo->nome}*! Seu aparelho foi homologado no sistema de ponto da empresa.\n\n"
                    . "Agora envie *Oi* ou o número da opção para registrar seu ponto.";

                $this->enviarRespostaWhatsApp($funcAlvo->empresa_id, $celularReal, $msgAtivacao);
                return response()->json(['status' => 'dispositivo_vinculado']);
            } else {
                return response()->json(['status' => 'funcionario_nao_encontrado']);
            }
        }

        // =========================================================================
        // 2. IDENTIFICAÇÃO DIRETA (ÚLTIMOS 8 DÍGITOS LIMPOS)
        // =========================================================================
        $numLimpoOrigem = preg_replace('/\D/', '', explode('@', $remoteJid)[0]);
        $ultimos8 = strlen($numLimpoOrigem) >= 8 ? substr($numLimpoOrigem, -8) : $numLimpoOrigem;

        // Busca direto pelo final de 8 dígitos ignorando qualquer máscara do banco
        $funcionario = Funcionario::where(function($q) use ($lidCompleto, $lidPuro, $ultimos8) {
            if ($lidCompleto) $q->where('whatsapp_id', $lidCompleto);
            if ($lidPuro) $q->orWhere('whatsapp_id', $lidPuro);
            if ($ultimos8) {
                $q->orWhereRaw("RIGHT(REGEXP_REPLACE(celular, '[^0-9]', ''), 8) = ?", [$ultimos8])
                    ->orWhereRaw("RIGHT(REGEXP_REPLACE(telefone, '[^0-9]', ''), 8) = ?", [$ultimos8]);
            }
        })->first();

        if (!$funcionario && $ultimos8) {
            $funcionario = Funcionario::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(celular, '(', ''), ')', ''), '-', ''), ' ', '') LIKE ?", ["%{$ultimos8}%"])
                ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') LIKE ?", ["%{$ultimos8}%"])
                ->first();
        }

        if ($funcionario && empty($funcionario->whatsapp_id)) {
            $funcionario->update(['whatsapp_id' => $lidPuro ?: $numLimpoOrigem]);
        }

        if (!$funcionario) {
            $portariaAberta = DB::table('portaria_respostas_temp')->where('status', 'pendente')->orderBy('id', 'desc')->first();
            if ($portariaAberta) $funcionario = Funcionario::find($portariaAberta->funcionario_id);
        }

        if (!$funcionario) {
            Log::warning("Número não reconhecido no ERP: {$numLimpoOrigem} ({$pushName})");

            $numFallbackLog = $numeroOrigem ?? $numLimpoOrigem;
            $evoFallback = DB::table('evo_api_instances')->first();
            if ($evoFallback && !empty($numFallbackLog)) {
                $this->enviarRespostaWhatsApp(
                    $evoFallback->empresa_id,
                    $numFallbackLog,
                    "⚠️ *ACESSO NÃO AUTORIZADO*\n\nOlá, *{$pushName}*!\nSeu número (*{$numFallbackLog}*) não foi localizado no cadastro de colaboradores do ERP.\n\nFavor solicitar a atualização do seu telefone junto ao RH."
                );
            }

            return response()->json(['status' => 'error', 'message' => 'Funcionario nao reconhecido']);
        }

        // =========================================================================
        // 🎯 NÚMERO DE RESPOSTA: DIRETO NA CONVERSA ATIVA
        // =========================================================================
        $numeroOrigem = !empty($remoteJid) ? preg_replace('/\D/', '', explode('@', $remoteJid)[0]) : null;

        $telefoneNumeros = (!empty($numeroOrigem) && strlen($numeroOrigem) >= 10)
            ? $numeroOrigem
            : preg_replace('/\D/', '', $funcionario->celular ?: $funcionario->telefone);

        if (!str_starts_with($telefoneNumeros, '55')) {
            $telefoneNumeros = '55' . $telefoneNumeros;
        }

        $numeroDestinoEnvio = $telefoneNumeros;

        // =========================================================================
        // 🚀 DESVIO PARA EMISSÃO DE NF-E (TEXTO OU ÁUDIO DA EVOLUTION API)
        // =========================================================================
        $estadoNfeId = 'wa_nfe_estado_' . $numeroDestinoEnvio;
        $emFluxoNfe = \Illuminate\Support\Facades\Cache::has($estadoNfeId);

        $iniciouNfe = false;

        if (preg_match('/^(NOTA|NFE|NF-E|EMITIR NOTA|FATURAR|CANCELAR)/i', $texto) || $messageType === 'audioMessage' || !empty($audioUrl)) {
            $iniciouNfe = true;
        }

        if ($emFluxoNfe || $iniciouNfe) {
            $request->merge([
                'funcionario_obj' => $funcionario,
                'numero_tratado' => $numeroDestinoEnvio,
                'texto_tratado' => $texto,
                'message_type' => $messageType,
                'audio_url' => $audioUrl
            ]);

            $nfeController = app(\App\Http\Controllers\Api\WhatsappNfeController::class);
            return $nfeController->webhook($request);
        }
        // =========================================================================

        // =========================================================================
        // 3. Portaria (Preservado)
        // =========================================================================
        $portariaPendente = DB::table('portaria_respostas_temp')
            ->where('funcionario_id', $funcionario->id)
            ->where('status', 'pendente')
            ->first();

        if ($portariaPendente) {
            if (str_contains($texto, '1') || str_contains($texto, 'LIBERAR') || str_contains($texto, 'AUTORIZAR')) {
                DB::table('portaria_respostas_temp')->where('id', $portariaPendente->id)->update(['status' => 'liberado']);
                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, "✅ Entrada do visitante LIBERADA com sucesso!");
                return response()->json(['status' => 'portaria_liberada']);
            }
            if (str_contains($texto, '2') || str_contains($texto, 'NEGAR') || str_contains($texto, 'BLOQUEAR')) {
                DB::table('portaria_respostas_temp')->where('id', $portariaPendente->id)->update(['status' => 'negado']);
                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, "❌ Entrada do visitante NEGADA.");
                return response()->json(['status' => 'portaria_negada']);
            }
        }

        // =========================================================================
        // 📄 3.1 SOLICITAÇÃO E ASSINATURA DE CONTRACHEQUE
        // =========================================================================
        if ($texto == 'HOLERITE' || str_contains($texto, 'CONTRACHEQUE') || $texto == 'ASSINAR' || $texto == 'ACEITO' || $texto == 'CONFIRMAR') {
            $cpfLimpo = preg_replace('/\D/', '', $funcionario->cpf ?? '');

            $holerite = DB::table('folha_holerites')
                ->where('empresa_id', $funcionario->empresa_id)
                ->where(function($q) use ($funcionario, $cpfLimpo) {
                    $q->where('funcionario_id', $funcionario->id);
                    if (!empty($funcionario->cpf)) $q->orWhere('cpf', $funcionario->cpf);
                    if (!empty($cpfLimpo)) $q->orWhereRaw("REPLACE(REPLACE(cpf, '.', ''), '-', '') = ?", [$cpfLimpo]);
                })
                ->orderBy('competencia', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if (!$holerite) {
                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, "ℹ Olá, *{$funcionario->nome}*!\nNenhum contracheque foi localizado no sistema até o momento.");
                return response()->json(['status' => 'sem_holerite']);
            }

            if (empty($holerite->token_assinatura)) {
                $tokenNovo = bin2hex(random_bytes(20));
                DB::table('folha_holerites')->where('id', $holerite->id)->update(['token_assinatura' => $tokenNovo]);
                $holerite->token_assinatura = $tokenNovo;
            }

            if (in_array($texto, ['ASSINAR', 'ACEITO', 'CONFIRMAR', 'CIENTE'])) {
                DB::table('folha_holerites')->where('id', $holerite->id)->update([
                    'assinado'        => 1,
                    'data_assinatura' => now(),
                    'tipo_assinatura' => 'whatsapp_texto',
                    'ip_assinatura'   => 'ZAP_' . $remoteJid
                ]);
                $holerite->assinado = 1;
                $holerite->data_assinatura = now();
                $holerite->tipo_assinatura = 'whatsapp_texto';
                $holerite->ip_assinatura = 'ZAP_' . $remoteJid;
            }

            if (!$holerite->assinado) {
                $baseDominio = config('app.url') ?: 'https://grupometal.fersofterp.com.br';
                $linkTouch = rtrim($baseDominio, '/') . "/holerite/assinar/{$holerite->token_assinatura}";

                $msgPendente = "📄 *SEU CONTRACHEQUE ESTÁ PRONTO! ({$holerite->competencia})*\n\n"
                    . "Olá, *{$funcionario->nome}*! O seu comprovante de pagamento foi gerado:\n"
                    . "💰 *Salário Base:* R$ " . number_format($holerite->salario_base, 2, ',', '.') . "\n"
                    . "🟢 *Vencimentos:* R$ " . number_format($holerite->total_vencimentos, 2, ',', '.') . "\n"
                    . "🔴 *Descontos:* R$ " . number_format($holerite->total_descontos, 2, ',', '.') . "\n"
                    . "💵 *Líquido:* *R$ " . number_format($holerite->valor_liquido, 2, ',', '.') . "*\n\n"
                    . "⚠️ *Para receber o PDF oficial, confirme o recebimento:*\n\n"
                    . "✍️ *Opção 1 (Assinar com o dedo):*\n{$linkTouch}\n\n"
                    . "📱 *Opção 2 (Assinar por aqui):*\nResponda apenas *ASSINAR* nesta conversa.";

                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, $msgPendente);
                return response()->json(['status' => 'solicitada_assinatura']);
            }

            try {
                $itens = DB::table('folha_holerite_itens')
                    ->where('folha_holerite_id', $holerite->id)
                    ->orderBy('tipo', 'desc')
                    ->orderBy('codigo_evento', 'asc')
                    ->get();

                $dadosEmpresa = $this->getEmpresaConfig($funcionario->empresa_id, $funcionario->filial_id);

                $pdf = Pdf::loadView('folha_pagamento.holerite_pdf', compact('holerite', 'itens', 'funcionario', 'dadosEmpresa'))
                    ->setPaper('a4', 'portrait');

                $compLimpa = str_replace('/', '-', $holerite->competencia);
                $nomeArquivo = "Contracheque_{$compLimpa}_{$funcionario->id}.pdf";
                $caminhoRelativo = "holerites/{$nomeArquivo}";

                \Illuminate\Support\Facades\Storage::disk('public')->put($caminhoRelativo, $pdf->output());

                $evoInstance = DB::table('evo_api_instances')->where('empresa_id', $funcionario->empresa_id)->first()
                    ?: DB::table('evo_api_instances')->first();

                if ($evoInstance) {
                    $urlPdf = "https://grupometal.fersofterp.com.br/storage/{$caminhoRelativo}";
                    $legenda = "✅ *Contracheque Assinado!*\n"
                        . "Competência: {$holerite->competencia}\n\n"
                        . "Caso o anexo não abra no seu celular, acesse diretamente por este link:\n{$urlPdf}";

                    $response = Http::withHeaders([
                        'apikey' => $evoInstance->api_key,
                        'Content-Type' => 'application/json'
                    ])
                        ->timeout(30)
                        ->post(rtrim($evoInstance->base_url, '/') . "/message/sendMedia/{$evoInstance->name}", [
                            'number' => $numeroDestinoEnvio,
                            'mediaMessage' => [
                                'mediatype' => 'document',
                                'caption'   => $legenda,
                                'media'     => $urlPdf,
                                'fileName'  => $nomeArquivo,
                                'mimetype'  => 'application/pdf'
                            ]
                        ]);

                    Log::info("Resposta EvoAPI Envio PDF: " . $response->body());
                }

                return response()->json(['status' => 'holerite_assinado_enviado']);
            } catch (\Exception $e) {
                Log::error("Erro PDF Holerite: " . $e->getMessage());
                return response()->json(['status' => 'erro_envio']);
            }
        }

        $agora = Carbon::now();
        $hoje = $agora->format('Y-m-d');

        $movimentacaoAtiva = MovimentacaoVeiculo::with('ajudantes')
            ->where('empresa_id', $funcionario->empresa_id)
            ->where('motorista_id', $funcionario->id)
            ->whereDate('data_hora_saida', $hoje)
            ->whereIn('status', ['agendado', 'iniciado'])
            ->orderBy('data_hora_saida', 'asc')
            ->first();

        if (preg_match('/^\d{4}$/', $texto)) {
            if (!$movimentacaoAtiva) {
                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, "⚠️ Não há nenhuma viagem agendada ou em andamento para o motorista *{$funcionario->nome}* no momento.");
                return response()->json(['status' => 'sem_agenda_ativa']);
            }

            $ajudante = $movimentacaoAtiva->ajudantes->firstWhere('pin_ponto', $texto);

            if (!$ajudante) {
                $ajudantesEscaladosNomes = $movimentacaoAtiva->ajudantes->pluck('nome')->implode(', ');
                $this->enviarRespostaWhatsApp(
                    $funcionario->empresa_id,
                    $numeroDestinoEnvio,
                    "❌ *PIN NÃO RECONHECIDO NESSA VIAGEM!*\n\nO PIN digitado não pertence a nenhum dos ajudantes escalados com você hoje: " . ($ajudantesEscaladosNomes ?: 'Nenhum ajudante escalado.')
                );
                return response()->json(['status' => 'pin_invalido_viagem']);
            }

            $marcacoesAjudanteHoje = PontoMarcacao::where('funcionario_id', $ajudante->id)
                ->whereDate('data_hora_marcacao', $hoje)
                ->where('status', 'processada')
                ->pluck('tipo_marcacao')->toArray();

            $tipoBatidaAjudante = 'entrada';
            if (in_array('entrada', $marcacoesAjudanteHoje)) $tipoBatidaAjudante = 'inicio_almoco';
            if (in_array('inicio_almoco', $marcacoesAjudanteHoje)) $tipoBatidaAjudante = 'fim_almoco';
            if (in_array('fim_almoco', $marcacoesAjudanteHoje)) $tipoBatidaAjudante = 'saida';
            if (in_array('saida', $marcacoesAjudanteHoje)) $tipoBatidaAjudante = 'inicio_hora_extra';
            if (in_array('inicio_hora_extra', $marcacoesAjudanteHoje)) $tipoBatidaAjudante = 'fim_hora_extra';

            if (in_array('fim_hora_extra', $marcacoesAjudanteHoje)) {
                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, "⚠️ O ajudante *{$ajudante->nome}* já concluiu todas as marcações de hoje.");
                return response()->json(['status' => 'ajudante_jornada_concluida']);
            }

            $latAjudante = $latZap;
            $lonAjudante = $lonZap;
            $origemAjudante = 'whatsapp_pin';

            if (!$latAjudante || !$lonAjudante) {
                $config = TraccarConfig::where('empresa_id', $funcionario->empresa_id)->first();
                if ($config && !empty($config->base_url) && !empty($funcionario->traccar_id)) {
                    try {
                        $baseUrl = rtrim($config->base_url, '/');
                        $respDevices = Http::withBasicAuth($config->mail_user_name, $config->password)->get($baseUrl . '/api/devices');
                        $respPositions = Http::withBasicAuth($config->mail_user_name, $config->password)->get($baseUrl . '/api/positions');

                        if ($respDevices->successful() && $respPositions->successful()) {
                            $device = collect($respDevices->json())->firstWhere('uniqueId', $funcionario->traccar_id);
                            if ($device) {
                                $pos = collect($respPositions->json())->firstWhere('deviceId', $device['id']);
                                if ($pos && isset($pos['latitude'])) {
                                    $latAjudante = $pos['latitude'];
                                    $lonAjudante = $pos['longitude'];
                                    $origemAjudante = 'whatsapp_pin_traccar';
                                }
                            }
                        }
                    } catch (\Exception $e) {}
                }
            }

            $hashComprovanteAj = strtoupper(substr(hash('sha256', $ajudante->id . $agora->timestamp . $tipoBatidaAjudante), 0, 16));

            PontoMarcacao::create([
                'empresa_id'               => $ajudante->empresa_id,
                'funcionario_id'           => $ajudante->id,
                'data_hora_marcacao'       => $agora,
                'origem'                   => $origemAjudante,
                'tipo_marcacao'            => $tipoBatidaAjudante,
                'status'                   => 'processada',
                'latitude'                 => $latAjudante,
                'longitude'                => $lonAjudante,
                'movimentacao_veiculo_id'  => $movimentacaoAtiva->id,
                'observacoes'              => "WhatsApp Motorista: {$funcionario->nome} ({$numeroDestinoEnvio}) | Viagem #{$movimentacaoAtiva->id}"
            ]);

            $labelsAj = [
                'entrada'           => '🟢 ENTRADA (Início de Jornada)',
                'inicio_almoco'     => '🟡 SAÍDA PARA O ALMOÇO',
                'fim_almoco'        => '🟢 RETORNO DO ALMOÇO',
                'saida'             => '🔴 SAÍDA (Fim de Expediente)',
                'inicio_hora_extra' => '⚡ INÍCIO DE HORA EXTRA',
                'fim_hora_extra'    => '🛑 SAÍDA FINAL (Fim da Hora Extra)'
            ];

            $dadosEmpresaAj = $this->getEmpresaConfig($ajudante->empresa_id, $ajudante->filial_id);
            $enderecoGpsAjudante = $this->obterEnderecoPorCoordenadas(
                $latAjudante,
                $lonAjudante,
                $ajudante->empresa_id,
                $funcionario->traccar_id
            );

            $comprovanteAj = "🧾 *COMPROVANTE DE PONTO (AJUDANTE)*\n";
            $comprovanteAj .= "----------------------------------------\n";
            $comprovanteAj .= "🏢 *Empresa:* {$dadosEmpresaAj['razao_social']}\n";
            $comprovanteAj .= "----------------------------------------\n";
            $comprovanteAj .= "👤 *Ajudante:* *{$ajudante->nome}*\n";
            $comprovanteAj .= "🚛 *Motorista Responsável:* {$funcionario->nome}\n";
            $comprovanteAj .= "🔢 *PIS:* " . ($ajudante->pis ?? 'Não informado') . "\n";
            $comprovanteAj .= "----------------------------------------\n";
            $comprovanteAj .= "📌 *Tipo:* {$labelsAj[$tipoBatidaAjudante]}\n";
            $comprovanteAj .= "🕒 *Data/Hora:* " . $agora->format('d/m/Y \à\s H:i:s') . "\n";

            $latFmtAj = $latAjudante ? number_format((float)$latAjudante, 6, '.', '') : null;
            $lonFmtAj = $lonAjudante ? number_format((float)$lonAjudante, 6, '.', '') : null;

            if ($enderecoGpsAjudante) {
                $comprovanteAj .= "📍 *Local:* {$enderecoGpsAjudante}\n";
            }
            if ($latFmtAj && $lonFmtAj) {
                $comprovanteAj .= "🌐 *GPS:* {$latFmtAj}, {$lonFmtAj}\n";
                $comprovanteAj .= "🗺️ *Mapa:* https://maps.google.com/?q={$latFmtAj},{$lonFmtAj}\n";
            } else {
                $comprovanteAj .= "📍 *Localização:* Não capturada\n";
            }

            $comprovanteAj .= "----------------------------------------\n";
            $comprovanteAj .= "🔑 *Autenticação:* {$hashComprovanteAj}\n";
            $comprovanteAj .= "----------------------------------------\n";
            $comprovanteAj .= "_Comprovante emitido nos termos da Portaria 671/2021 MTE_";

            try {
                $calculoService = app(\App\Services\Ponto\PontoCalculoService::class);
                $calculoService->calcularDia($ajudante->id, $hoje);

                if (in_array($tipoBatidaAjudante, ['saida', 'fim_hora_extra'])) {
                    $comprovanteAj .= "\n\n" . $calculoService->obterMensagemResumoDiario($ajudante->id, $hoje);
                }
            } catch (\Exception $e) {
                Log::warning("Erro ao calcular resumo do ajudante: " . $e->getMessage());
            }

            $this->enviarRespostaWhatsApp(
                $funcionario->empresa_id,
                $numeroDestinoEnvio,
                "✅ Ponto do ajudante *{$ajudante->nome}* registrado com sucesso!"
            );

            $telAjudante = preg_replace('/\D/', '', $ajudante->celular ?: $ajudante->telefone);
            if (!empty($telAjudante)) {
                if (!str_starts_with($telAjudante, '55')) {
                    $telAjudante = '55' . $telAjudante;
                }

                $msgDiretaAjudante = "Olá, *{$ajudante->nome}*!\nSeu ponto foi registrado na viagem com o motorista *{$funcionario->nome}*.\n\n" . $comprovanteAj;
                $this->enviarRespostaWhatsApp($ajudante->empresa_id, $telAjudante, $msgDiretaAjudante);
            }

            return response()->json(['status' => 'ponto_ajudante_registrado']);
        }

        if (isset($funcionario->tipo_ponto) && $funcionario->tipo_ponto == 'local') {
            $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, "⚠️ *ACESSO NEGADO!*\n\nOlá, *{$funcionario->nome}*. Seu cadastro exige registro de ponto presencial.");
            return response()->json(['status' => 'error', 'message' => 'Ponto apenas local']);
        }

        $pontoPendenteLoc = PontoMarcacao::where('funcionario_id', $funcionario->id)
            ->whereDate('data_hora_marcacao', $hoje)
            ->where('status', 'pendente_localizacao')
            ->first();

        if ($pontoPendenteLoc) {
            if ($latZap && $lonZap) {
                $texto = $pontoPendenteLoc->tipo_marcacao;
                $pontoPendenteLoc->delete();
            } else {
                if (in_array($texto, ['CANCELAR', 'SAIR', '0'])) {
                    $pontoPendenteLoc->delete();
                    $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, "❌ Marcação cancelada.");
                    return response()->json(['status' => 'cancelado']);
                }
                $msgReq = "📍 *Aguardando Localização*\n\nPara registrar o ponto, clique no ícone de clipe (📎), escolha *Localização* e envie sua *Localização Atual*.\n\n_(Digite CANCELAR para abortar)_";
                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, $msgReq);
                return response()->json(['status' => 'aguardando_localizacao']);
            }
        }

        $ordemEtapas = [
            'entrada'            => 1,
            'inicio_almoco'      => 2,
            'fim_almoco'         => 3,
            'saida'              => 4,
            'inicio_hora_extra'  => 5,
            'fim_hora_extra'     => 6,
        ];

        $labels = [
            'entrada'            => '🟢 ENTRADA (Início de Jornada)',
            'inicio_almoco'      => '🟡 SAÍDA PARA O ALMOÇO',
            'fim_almoco'         => '🟢 RETORNO DO ALMOÇO',
            'saida'              => '🔴 SAÍDA (Fim de Expediente)',
            'inicio_hora_extra'  => '⚡ INÍCIO DE HORA EXTRA',
            'fim_hora_extra'     => '🛑 SAÍDA FINAL (Fim da Hora Extra)',
        ];

        $tipoBatida = null;
        if (str_contains($texto, '1') || str_contains($texto, 'ENTRADA') || $texto == 'entrada') {
            $tipoBatida = 'entrada';
        } elseif (str_contains($texto, '2') || str_contains($texto, 'ALMOCO') || $texto == 'inicio_almoco') {
            $tipoBatida = 'inicio_almoco';
        } elseif (str_contains($texto, '3') || str_contains($texto, 'VOLTA') || $texto == 'fim_almoco') {
            $tipoBatida = 'fim_almoco';
        } elseif (str_contains($texto, '4') || str_contains($texto, 'SAIDA') || $texto == 'saida') {
            $tipoBatida = 'saida';
        } elseif (str_contains($texto, '5') || str_contains($texto, 'EXTRA')) {
            $tipoBatida = 'inicio_hora_extra';
        } elseif (str_contains($texto, '6') || str_contains($texto, 'FINAL') || str_contains($texto, 'ENCERRAR')) {
            $tipoBatida = 'fim_hora_extra';
        }

        $marcacoesHoje = PontoMarcacao::where('funcionario_id', $funcionario->id)
            ->whereDate('data_hora_marcacao', $hoje)
            ->where('status', '!=', 'pendente_localizacao')
            ->orderBy('data_hora_marcacao', 'asc')
            ->get();

        $tiposJaBatidosToday = $marcacoesHoje->pluck('tipo_marcacao')->toArray();

        $maiorNivelHoje = 0;
        foreach ($tiposJaBatidosToday as $tipoJa) {
            if (isset($ordemEtapas[$tipoJa]) && $ordemEtapas[$tipoJa] > $maiorNivelHoje) {
                $maiorNivelHoje = $ordemEtapas[$tipoJa];
            }
        }

        $proximaEtapaSugerida = 'entrada';
        if ($maiorNivelHoje == 1) $proximaEtapaSugerida = 'inicio_almoco';
        elseif ($maiorNivelHoje == 2) $proximaEtapaSugerida = 'fim_almoco';
        elseif ($maiorNivelHoje == 3) $proximaEtapaSugerida = 'saida';
        elseif ($maiorNivelHoje == 4) $proximaEtapaSugerida = 'inicio_hora_extra';
        elseif ($maiorNivelHoje == 5) $proximaEtapaSugerida = 'fim_hora_extra';
        elseif ($maiorNivelHoje >= 6) $proximaEtapaSugerida = 'concluido';

        if (!$tipoBatida) {
            if ($maiorNivelHoje >= 6) {
                $this->enviarRespostaWhatsApp(
                    $funcionario->empresa_id,
                    $numeroDestinoEnvio,
                    "✅ *JORNADA E HORAS EXTRAS CONCLUÍDAS!*\n\nOlá, *{$funcionario->nome}*. Todas as suas etapas de hoje foram registradas.\n\n📄 _Digite *HOLERITE* para consultar seu contracheque em PDF._"
                );
                return response()->json(['status' => 'jornada_ja_finalizada']);
            }

            $menu = "📱 *REGISTRO DE PONTO*\n\nOlá, *{$funcionario->nome}*! Sua próxima etapa sugerida é:\n👉 *" . ($labels[$proximaEtapaSugerida] ?? '') . "*\n\nOpções disponíveis:";

            if ($maiorNivelHoje < 1) $menu .= "\n1️⃣ ENTRADA";
            if ($maiorNivelHoje < 2) $menu .= "\n2️⃣ SAÍDA ALMOÇO";
            if ($maiorNivelHoje < 3) $menu .= "\n3️⃣ RETORNO ALMOÇO";
            if ($maiorNivelHoje < 4) $menu .= "\n4️⃣ SAÍDA";

            if ($maiorNivelHoje >= 4 && $maiorNivelHoje < 5) {
                $menu .= "\n5️⃣ INÍCIO HORA EXTRA";
            }
            if ($maiorNivelHoje >= 5 && $maiorNivelHoje < 6) {
                $menu .= "\n6️⃣ SAÍDA FINAL (FIM DA HORA EXTRA)";
            }

            $menu .= "\n\n📄 _Digite *HOLERITE* a qualquer momento para receber seu contracheque em PDF._";

            $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, $menu);
            return response()->json(['status' => 'menu_enviado']);
        }

        $nivelEscolhido = $ordemEtapas[$tipoBatida] ?? 0;

        if ($nivelEscolhido <= $maiorNivelHoje) {
            $this->enviarRespostaWhatsApp(
                $funcionario->empresa_id,
                $numeroDestinoEnvio,
                "⚠️ *OPÇÃO INDISPONÍVEL!*\n\nVocê já registrou uma etapa igual ou superior hoje.\nO ponto só permite avançar para as próximas etapas disponíveis."
            );
            return response()->json(['status' => 'etapa_retrograda_bloqueada']);
        }

        if ($tipoBatida == 'fim_hora_extra' && !in_array('inicio_hora_extra', $tiposJaBatidosToday)) {
            $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, "⚠️ Você precisa registrar a opção *5️⃣ INÍCIO HORA EXTRA* antes da saída final.");
            return response()->json(['status' => 'sequencia_invalida']);
        }

        if ($tipoBatida == 'inicio_almoco') {
            $ultimaEntrada = $marcacoesHoje->firstWhere('tipo_marcacao', 'entrada');
            if ($ultimaEntrada) {
                $segundosDesdeEntrada = Carbon::parse($ultimaEntrada->data_hora_marcacao)->diffInSeconds($agora);
                $tempoMinimoEntrada = 15 * 60;

                if ($segundosDesdeEntrada < $tempoMinimoEntrada) {
                    $segundosRestantesEntrada = $tempoMinimoEntrada - $segundosDesdeEntrada;
                    $faltamEntrada = ceil($segundosRestantesEntrada / 60);

                    $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, "⏳ Intervalo insuficiente. Aguarde {$faltamEntrada} min após a entrada para sair para o almoço.");
                    return response()->json(['status' => 'bloqueado_inicio_almoco']);
                }
            }
        }

        if ($tipoBatida == 'fim_almoco') {
            $ultimaSaidaAlmoco = $marcacoesHoje->firstWhere('tipo_marcacao', 'inicio_almoco');
            if ($ultimaSaidaAlmoco) {
                $segundosDesdeSaida = Carbon::parse($ultimaSaidaAlmoco->data_hora_marcacao)->diffInSeconds($agora);
                $tempoMinimoAlmoco = 60 * 60;

                if ($segundosDesdeSaida < $tempoMinimoAlmoco) {
                    $segundosRestantes = $tempoMinimoAlmoco - $segundosDesdeSaida;
                    $faltam = ceil($segundosRestantes / 60);

                    $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, "⏳ Intervalo de almoço em andamento. Aguarde {$faltam} min para registrar o retorno.");
                    return response()->json(['status' => 'bloqueado_almoco']);
                }
            }
        }

        $lat = $latZap;
        $lon = $lonZap;
        $origemNome = $latZap ? 'whatsapp_localizacao' : 'whatsapp';

        if (!$lat || !$lon) {
            $config = TraccarConfig::where('empresa_id', $funcionario->empresa_id)->first();
            if ($config && !empty($config->base_url) && !empty($funcionario->traccar_id)) {
                try {
                    $baseUrl = rtrim($config->base_url, '/');
                    $respDevices = Http::withBasicAuth($config->mail_user_name, $config->password)->get($baseUrl . '/api/devices');
                    $respPositions = Http::withBasicAuth($config->mail_user_name, $config->password)->get($baseUrl . '/api/positions');

                    if ($respDevices->successful() && $respPositions->successful()) {
                        $device = collect($respDevices->json())->firstWhere('uniqueId', $funcionario->traccar_id);
                        if ($device) {
                            $pos = collect($respPositions->json())->firstWhere('deviceId', $device['id']);
                            if ($pos && isset($pos['latitude'])) {
                                $lat = $pos['latitude'];
                                $lon = $pos['longitude'];
                                $origemNome = 'whatsapp_traccar';
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Erro Traccar ponto: " . $e->getMessage());
                }
            }
        }

        if (!$lat || !$lon) {
            PontoMarcacao::create([
                'empresa_id'         => $funcionario->empresa_id,
                'funcionario_id'     => $funcionario->id,
                'data_hora_marcacao' => $agora,
                'origem'             => 'sistema',
                'tipo_marcacao'      => $tipoBatida,
                'status'             => 'pendente_localizacao',
                'observacoes'        => "Aguardando envio de localização via WhatsApp"
            ]);

            $msgReq = "📍 *Localização Obrigatória*\n\nNão foi possível sincronizar o seu GPS automático.\n\nPara registrar a etapa de *{$labels[$tipoBatida]}*, clique no clipe (📎), escolha *Localização* e envie sua *Localização Atual*.";
            $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, $msgReq);
            return response()->json(['status' => 'solicitado_localizacao']);
        }

        $movIdVinculado = $movimentacaoAtiva ? $movimentacaoAtiva->id : null;
        $hashComprovante = strtoupper(substr(hash('sha256', $funcionario->id . $agora->timestamp . $tipoBatida), 0, 16));
        $obsJornada = "WhatsApp: {$numeroDestinoEnvio} | Fonte: {$origemNome}";

        $horaAtual = $agora->format('H:i');
        if ($tipoBatida == 'entrada') {
            if ($movimentacaoAtiva) {
                $horaSaidaAgendada = Carbon::parse($movimentacaoAtiva->data_hora_saida)->format('H:i');
                $obsJornada .= " | 🚚 VIAGEM PROGRAMADA (Saída às {$horaSaidaAgendada})";
            } else {
                if ($horaAtual < '07:45') {
                    $obsJornada .= " | ⏰ ENTRADA ANTECIPADA (Sem viagem vinculada)";
                } elseif ($horaAtual > '08:10') {
                    $obsJornada .= " | ⚠️️ ATRASO";
                }
            }
        } elseif ($tipoBatida == 'saida' && $horaAtual > '18:10') {
            $obsJornada .= " | ⚡ HORA EXTRA";
        }

        PontoMarcacao::create([
            'empresa_id'               => $funcionario->empresa_id,
            'funcionario_id'           => $funcionario->id,
            'data_hora_marcacao'       => $agora,
            'origem'                   => $origemNome,
            'tipo_marcacao'            => $tipoBatida,
            'status'                   => 'processada',
            'latitude'                 => $lat,
            'longitude'                => $lon,
            'movimentacao_veiculo_id'  => $movIdVinculado,
            'observacoes'              => $obsJornada
        ]);

        $dadosEmpresa = $this->getEmpresaConfig($funcionario->empresa_id, $funcionario->filial_id);

        $enderecoGps = $this->obterEnderecoPorCoordenadas(
            $lat,
            $lon,
            $funcionario->empresa_id,
            $funcionario->traccar_id
        );

        $latFormatada = number_format((float)$lat, 6, '.', '');
        $lonFormatada = number_format((float)$lon, 6, '.', '');
        $linkMaps = "https://maps.google.com/?q={$latFormatada},{$lonFormatada}";

        $comprovante = "🧾 *COMPROVANTE DE REGISTRO DE PONTO*\n";
        $comprovante .= "----------------------------------------\n";
        $comprovante .= "🏢 *Empresa:* {$dadosEmpresa['razao_social']}\n";
        $comprovante .= "📋 *CNPJ:* {$dadosEmpresa['cnpj']}\n";
        $comprovante .= "📍 *Sede:* {$dadosEmpresa['endereco']}\n";
        $comprovante .= "----------------------------------------\n";
        $comprovante .= "👤 *Funcionário:* {$funcionario->nome}\n";
        $comprovante .= "🔢 *PIS:* " . ($funcionario->pis ?? 'Não informado') . "\n";
        $comprovante .= "----------------------------------------\n";
        $comprovante .= "📌 *Tipo:* {$labels[$tipoBatida]}\n";
        $comprovante .= "🕒 *Data/Hora:* " . $agora->format('d/m/Y \à\s H:i:s') . "\n";

        if ($enderecoGps) {
            $comprovante .= "📍 *Local:* {$enderecoGps}\n";
        }
        $comprovante .= "🌐 *GPS:* {$latFormatada}, {$lonFormatada}\n";
        $comprovante .= "🗺️ *Mapa:* {$linkMaps}\n";

        $comprovante .= "----------------------------------------\n";
        $comprovante .= "🔑 *Autenticação:* {$hashComprovante}\n";
        $comprovante .= "----------------------------------------\n";
        $comprovante .= "_Comprovante emitido nos termos da Portaria 671/2021 MTE_";

        $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, $comprovante);

        try {
            $calculoService = app(\App\Services\Ponto\PontoCalculoService::class);
            $calculoService->calcularDia($funcionario->id, $hoje);

            if (in_array($tipoBatida, ['saida', 'fim_hora_extra'])) {
                $extratoDiario = $calculoService->obterMensagemResumoDiario($funcionario->id, $hoje);
                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, $extratoDiario);
            }
        } catch (\Exception $e) {
            Log::warning("Erro ao calcular dia ou gerar extrato do colaborador: " . $e->getMessage());
        }

        if ($tipoBatida == 'entrada' && $movimentacaoAtiva && $movimentacaoAtiva->ajudantes && $movimentacaoAtiva->ajudantes->count() > 0) {
            $chaveCacheAjudantes = "msg_ajudantes_enviada_viagem_{$movimentacaoAtiva->id}_{$hoje}";

            if (!Cache::has($chaveCacheAjudantes)) {
                Cache::put($chaveCacheAjudantes, true, 64800);

                $listaNomes = $movimentacaoAtiva->ajudantes->pluck('nome')->implode(', ');
                $msgAjudantes = "👥 *REGISTRO DE AJUDANTES DA SUA VIAGEM*\n\n"
                    . "A rota foi iniciada!\n"
                    . "Ajudantes escalados: *{$listaNomes}*.\n\n"
                    . "Para registrar a ENTRADA da equipe, peça para cada um enviar o seu *PIN de 4 dígitos* respondendo a esta mensagem.";

                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $numeroDestinoEnvio, $msgAjudantes);
            }
        }

        return response()->json(['status' => 'ponto_registrado']);
    }

    private function getEmpresaConfig($empresaId, $filialId = null)
    {
        if (!empty($filialId)) {
            $filial = DB::table('filials')->where('id', $filialId)->first();
            if ($filial) {
                return [
                    'razao_social' => $filial->razao_social,
                    'cnpj'          => $filial->cnpj,
                    'endereco'      => "{$filial->logradouro}, {$filial->numero} - {$filial->bairro}, {$filial->municipio} - {$filial->UF}"
                ];
            }
        }
        $configNotas = DB::table('config_notas')->where('empresa_id', $empresaId)->first();
        if ($configNotas) {
            return [
                'razao_social' => $configNotas->razao_social,
                'cnpj'          => $configNotas->cnpj,
                'endereco'      => "{$configNotas->logradouro}, {$configNotas->numero} - {$configNotas->bairro}, {$configNotas->municipio} - {$configNotas->UF}"
            ];
        }
        return [ 'razao_social' => 'FERSOFT ERP', 'cnpj' => '00.000.000/0001-00', 'endereco' => 'Endereço não cadastrado' ];
    }

    public function enviarRespostaWhatsApp($empresaId, $telefone, $mensagem)
    {
        try {
            $destino = explode('@', (string)$telefone)[0];
            $numLimpo = preg_replace('/[^0-9]/', '', $destino);

            if (strlen($numLimpo) >= 10 && strlen($numLimpo) <= 11 && !str_starts_with($numLimpo, '55')) {
                $numLimpo = '55' . $numLimpo;
            }

            $evoInstance = $empresaId
                ? DB::table('evo_api_instances')->where('empresa_id', $empresaId)->first()
                : DB::table('evo_api_instances')->first();

            if (!$evoInstance) {
                Log::error("Evo API: Nenhuma instancia encontrada para empresa {$empresaId}");
                return;
            }

            $url = rtrim($evoInstance->base_url, '/') . "/message/sendText/{$evoInstance->name}";

            $response = Http::withHeaders([
                'apikey' => $evoInstance->api_key,
                'Content-Type' => 'application/json'
            ])
                ->timeout(15)
                ->post($url, [
                    'number' => $numLimpo,
                    'textMessage' => ['text' => $mensagem]
                ]);

            Log::info("Evo API Envio para [{$numLimpo}]: Status " . $response->status(), [
                'resposta' => $response->json()
            ]);

        } catch (\Exception $e) {
            Log::error("Erro envio WhatsApp: " . $e->getMessage());
        }
    }

    private function obterEnderecoPorCoordenadas($lat, $lon, $empresaId = null, $traccarId = null)
    {
        if (!$lat || !$lon) return null;

        $latFloat = (float) str_replace(',', '.', (string)$lat);
        $lonFloat = (float) str_replace(',', '.', (string)$lon);

        $cacheKey = 'geo_' . round($latFloat, 4) . '_' . round($lonFloat, 4);

        return Cache::remember($cacheKey, 86400 * 7, function () use ($latFloat, $lonFloat, $empresaId, $traccarId) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'FersoftERP-Ponto/1.0 (suporte@fersoft.com.br)'
                ])
                    ->timeout(5)
                    ->get("https://nominatim.openstreetmap.org/reverse", [
                        'format' => 'jsonv2',
                        'lat' => $latFloat,
                        'lon' => $lonFloat,
                        'zoom' => 18,
                        'addressdetails' => 1
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $address = $data['address'] ?? [];

                    $rua = $address['road']
                        ?? $address['street']
                        ?? $address['pedestrian']
                        ?? $address['residential']
                        ?? '';

                    $bairro = $address['neighbourhood']
                        ?? $address['city_district']
                        ?? $address['quarter']
                        ?? '';

                    if (empty($bairro) && !empty($address['suburb'])) {
                        $suburbLower = strtolower($address['suburb']);
                        if (!in_array($suburbLower, ['alagados', 'península itapagipana', 'subúrbio'])) {
                            $bairro = $address['suburb'];
                        } else {
                            $bairro = 'Boa Vista de São Caetano';
                        }
                    }

                    $cidade = $address['city'] ?? $address['town'] ?? $address['municipality'] ?? 'Salvador';
                    $estado = $address['state'] ?? 'BA';

                    if (!empty($rua) && !empty($bairro)) {
                        return "{$rua} - {$bairro}, {$cidade} - {$estado}";
                    } elseif (!empty($rua)) {
                        return "{$rua}, {$cidade} - {$estado}";
                    } elseif (!empty($data['display_name'])) {
                        $partes = explode(',', $data['display_name']);
                        return trim(implode(',', array_slice($partes, 0, 3)));
                    }
                }

            } catch (\Exception $e) {
                Log::warning("Nominatim falhou: " . $e->getMessage());
            }

            if ($empresaId && $traccarId) {
                try {
                    $config = \App\Models\TraccarConfig::where('empresa_id', $empresaId)->first();
                    if ($config && !empty($config->base_url)) {
                        $baseUrl = rtrim($config->base_url, '/');
                        $respDevices = Http::withBasicAuth($config->mail_user_name, $config->password)->timeout(4)->get($baseUrl . '/api/devices');
                        $respPositions = Http::withBasicAuth($config->mail_user_name, $config->password)->timeout(4)->get($baseUrl . '/api/positions');

                        if ($respDevices->successful() && $respPositions->successful()) {
                            $device = collect($respDevices->json())->firstWhere('uniqueId', $traccarId);
                            if ($device) {
                                $pos = collect($respPositions->json())->firstWhere('deviceId', $device['id']);
                                if (!empty($pos['address'])) {
                                    return str_replace([', BR', ', Brasil'], '', $pos['address']);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {}
            }

            return null;
        });
    }
}
