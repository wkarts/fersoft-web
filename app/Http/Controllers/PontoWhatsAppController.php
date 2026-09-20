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

        // 1. Extração do texto e da localização
        $texto = strtoupper(trim(
            $payload['data']['message']['conversation']
            ?? $payload['data']['message']['extendedTextMessage']['text']
            ?? $payload['message']
            ?? $payload['body']
            ?? ''
        ));

        $locationMsg = $payload['data']['message']['locationMessage'] ?? null;
        $latZap = $locationMsg['degreesLatitude'] ?? null;
        $lonZap = $locationMsg['degreesLongitude'] ?? null;

        $pushName = $payload['data']['pushName'] ?? null;

        // =========================================================================
        // 🚀 1.1 EXTRAÇÃO DOS IDENTIFICADORES DO WHATSAPP (LID E SENDER)
        // =========================================================================
        $remoteJid = $payload['data']['key']['remoteJid'] ?? '';

        // Pega o LID que identifica o aparelho que mandou a mensagem
        $lidCompleto = (!empty($remoteJid) && str_contains($remoteJid, '@lid')) ? $remoteJid : null;
        $lidPuro = $lidCompleto ? explode('@', $lidCompleto)[0] : null;

        // Se NÃO for @lid, o remoteJid é o próprio número telefônico
        $numeroOrigem = !str_contains($remoteJid, '@lid') ? preg_replace('/\D/', '', explode('@', $remoteJid)[0]) : null;

        $telefoneSemDdi = $numeroOrigem;
        if ($numeroOrigem && str_starts_with($numeroOrigem, '55') && strlen($numeroOrigem) >= 12) {
            $telefoneSemDdi = substr($numeroOrigem, 2);
        }
        $ultimos8 = ($telefoneSemDdi && strlen($telefoneSemDdi) >= 8) ? substr($telefoneSemDdi, -8) : $telefoneSemDdi;

        // =========================================================================
        // 🚀 1.2 PAREAMENTO EXPRESSO: "ATIVAR [ID]" (Ex: ATIVAR 1)
        // =========================================================================
        if (preg_match('/^(?:ATIVAR|CONECTAR|VINCULAR)\s+(\d+)$/i', $texto, $matches)) {
            $idFuncionario = (int) $matches[1];
            $funcAlvo = Funcionario::find($idFuncionario);

            if ($funcAlvo) {
                $idParaGravar = $lidPuro ?: $lidCompleto ?: $remoteJid;
                $funcAlvo->update(['whatsapp_id' => $idParaGravar]);

                // Envia a resposta direto para o celular cadastrado no ERP
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
        // 2. IDENTIFICAÇÃO DO MOTORISTA / DONO DO WHATSAPP
        // =========================================================================
        $funcionario = Funcionario::where(function($q) use ($lidCompleto, $lidPuro, $ultimos8, $telefoneSemDdi) {
            if ($lidCompleto) $q->where('whatsapp_id', $lidCompleto);
            if ($lidPuro) $q->orWhere('whatsapp_id', $lidPuro);

            if (!empty($ultimos8)) {
                $q->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(celular, '(', ''), ')', ''), '-', ''), ' ', '') LIKE ?", ["%{$ultimos8}%"])
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') LIKE ?", ["%{$ultimos8}%"]);
            }
            if (!empty($telefoneSemDdi)) {
                $q->orWhere('telefone', 'LIKE', "%{$telefoneSemDdi}%")
                    ->orWhere('celular', 'LIKE', "%{$telefoneSemDdi}%");
            }
        })->first();

        // Se achou pelo celular mas o whatsapp_id estava vazio, vincula na hora
        if ($funcionario && empty($funcionario->whatsapp_id) && $lidPuro) {
            $funcionario->update(['whatsapp_id' => $lidPuro]);
        }

        // Fallback Portaria
        if (!$funcionario) {
            $portariaAberta = DB::table('portaria_respostas_temp')->where('status', 'pendente')->orderBy('id', 'desc')->first();
            if ($portariaAberta) $funcionario = Funcionario::find($portariaAberta->funcionario_id);
        }

        if (!$funcionario) {
            return response()->json(['status' => 'error', 'message' => 'Funcionario nao reconhecido']);
        }

        // =========================================================================
        // 🎯 NÚMERO DE RESPOSTA: SEMPRE O CELULAR REAL DO ERP!
        // =========================================================================
        $telefoneNumeros = preg_replace('/\D/', '', $funcionario->celular ?: $funcionario->telefone);
        if (!str_starts_with($telefoneNumeros, '55')) {
            $telefoneNumeros = '55' . $telefoneNumeros;
        }

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
                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, "✅ Entrada do visitante LIBERADA com sucesso!");
                return response()->json(['status' => 'portaria_liberada']);
            }
            if (str_contains($texto, '2') || str_contains($texto, 'NEGAR') || str_contains($texto, 'BLOQUEAR')) {
                DB::table('portaria_respostas_temp')->where('id', $portariaPendente->id)->update(['status' => 'negado']);
                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, "❌ Entrada do visitante NEGADA.");
                return response()->json(['status' => 'portaria_negada']);
            }
        }

        // =========================================================================
        // 📄 3.1 SOLICITAÇÃO DE CONTRACHEQUE EM PDF
        // =========================================================================
        if ($texto == 'HOLERITE' || str_contains($texto, 'CONTRACHEQUE') || str_contains($texto, 'CONTRA-CHEQUE')) {
            $cpfLimpo = preg_replace('/\D/', '', $funcionario->cpf ?? '');

            // Captura competência caso informada (Ex: "HOLERITE 06/2026", "HOLERITE 07/26" ou "HOLERITE JULHO/2026")
            $compDesejada = null;
            if (preg_match('/(\d{2}\/\d{2,4})/', $texto, $mComp)) {
                $compDesejada = $mComp[1];
            } elseif (preg_match('/(JANEIRO|FEVEREIRO|MARCO|ABRIL|MAIO|JUNHO|JULHO|AGOSTO|SETEMBRO|OUTUBRO|NOVEMBRO|DEZEMBRO)\/?(\d{2,4})?/i', $texto, $mCompNome)) {
                $compDesejada = $mCompNome[0];
            }

            $queryHolerite = DB::table('folha_holerites')
                ->where('empresa_id', $funcionario->empresa_id)
                ->where(function($q) use ($funcionario, $cpfLimpo) {
                    $q->where('funcionario_id', $funcionario->id);
                    if (!empty($funcionario->cpf)) {
                        $q->orWhere('cpf', $funcionario->cpf);
                    }
                    if (!empty($cpfLimpo)) {
                        $q->orWhereRaw("REPLACE(REPLACE(cpf, '.', ''), '-', '') = ?", [$cpfLimpo]);
                    }
                });

            if ($compDesejada) {
                $queryHolerite->where('competencia', 'LIKE', "%{$compDesejada}%");
            }

            $holerite = $queryHolerite->orderBy('id', 'desc')->first();

            if (!$holerite) {
                $msgNaoEncontrado = $compDesejada
                    ? "ℹ️ Olá, *{$funcionario->nome}*!\nNão localizamos contracheque referente à competência *{$compDesejada}*."
                    : "ℹ️ Olá, *{$funcionario->nome}*!\nNenhum contracheque foi localizado no sistema para o seu cadastro até o momento.";

                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, $msgNaoEncontrado);
                return response()->json(['status' => 'sem_holerite']);
            }

            try {
                $itens = DB::table('folha_holerite_itens')
                    ->where('folha_holerite_id', $holerite->id)
                    ->orderBy('tipo', 'desc')
                    ->orderBy('codigo_evento', 'asc')
                    ->get();

                $dadosEmpresa = $this->getEmpresaConfig($funcionario->empresa_id, $funcionario->filial_id);
                $dadosJson = json_decode($holerite->dados_json, true) ?? [];

                $holerite->base_inss = $dadosJson['base_inss'] ?? 0;
                $holerite->base_irrf = $dadosJson['base_irrf'] ?? 0;
                $holerite->base_fgts = $dadosJson['base_fgts'] ?? 0;
                $holerite->valor_fgts = $dadosJson['vlr_fgts'] ?? 0;

                $pdf = Pdf::loadView('folha_pagamento.holerite_pdf', compact('holerite', 'itens', 'funcionario', 'dadosEmpresa'))
                    ->setPaper('a4', 'portrait');

                $compLimpa = str_replace('/', '-', $holerite->competencia);
                $nomeArquivo = "Contracheque_{$compLimpa}_{$funcionario->id}.pdf";
                $caminhoRelativo = "holerites/{$nomeArquivo}";

                Storage::disk('public')->put($caminhoRelativo, $pdf->output());

                $baseDominio = config('app.url');
                if (empty($baseDominio) || str_contains($baseDominio, 'localhost')) {
                    $baseDominio = 'https://grupometal.fersofterp.com.br';
                }
                $urlPdf = rtrim($baseDominio, '/') . "/storage/{$caminhoRelativo}";

                $numDestino = preg_replace('/[^0-9]/', '', $telefoneNumeros);
                if (!str_starts_with($numDestino, '55')) {
                    $numDestino = '55' . $numDestino;
                }

                $msgLegenda = "📄 *RECIBO DE PAGAMENTO DE SALÁRIO*\n\n"
                    . "Olá, *{$funcionario->nome}*! Segue em anexo o seu contracheque.\n"
                    . "📅 *Competência:* {$holerite->competencia}\n"
                    . "💰 *Salário Base:* R$ " . number_format($holerite->salario_base, 2, ',', '.') . "\n"
                    . "🟢 *Total Vencimentos:* R$ " . number_format($holerite->total_vencimentos, 2, ',', '.') . "\n"
                    . "🔴 *Total Descontos:* R$ " . number_format($holerite->total_descontos, 2, ',', '.') . "\n"
                    . "💵 *Valor Líquido:* R$ " . number_format($holerite->valor_liquido, 2, ',', '.') . "\n\n"
                    . "💡 _Para consultar outro mês, envie por exemplo: *HOLERITE 06/2026*._";

                app(\App\Utils\WhatsAppUtil::class)->sendMessage(
                    $numDestino,
                    $msgLegenda,
                    (int) $funcionario->empresa_id,
                    $urlPdf
                );

                return response()->json(['status' => 'holerite_enviado']);
            } catch (\Exception $e) {
                Log::error("Erro envio holerite WhatsApp: " . $e->getMessage());
                $this->enviarRespostaWhatsApp(
                    $funcionario->empresa_id,
                    $telefoneNumeros,
                    "⚠️ Ocorreu um erro ao gerar seu contracheque. Por favor, solicite ao departamento pessoal."
                );
                return response()->json(['status' => 'erro_geracao_holerite']);
            }
        }

        $agora = Carbon::now();
        $hoje = $agora->format('Y-m-d');

        // Agenda de viagem do motorista
        $movimentacaoAtiva = MovimentacaoVeiculo::with('ajudantes')
            ->where('empresa_id', $funcionario->empresa_id)
            ->where('motorista_id', $funcionario->id)
            ->whereDate('data_hora_saida', $hoje)
            ->whereIn('status', ['agendado', 'iniciado'])
            ->orderBy('data_hora_saida', 'asc')
            ->first();

        // =========================================================================
        // 🚀 VALIDAÇÃO DO PIN DOS AJUDANTES (4 DÍGITOS)
        // =========================================================================
        if (preg_match('/^\d{4}$/', $texto)) {
            if (!$movimentacaoAtiva) {
                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, "⚠️ Não há nenhuma viagem agendada ou em andamento para o motorista *{$funcionario->nome}* no momento.");
                return response()->json(['status' => 'sem_agenda_ativa']);
            }

            $ajudante = $movimentacaoAtiva->ajudantes->firstWhere('pin_ponto', $texto);

            if (!$ajudante) {
                $ajudantesEscaladosNomes = $movimentacaoAtiva->ajudantes->pluck('nome')->implode(', ');
                $this->enviarRespostaWhatsApp(
                    $funcionario->empresa_id,
                    $telefoneNumeros,
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
                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, "⚠️ O ajudante *{$ajudante->nome}* já concluiu todas as marcações de hoje.");
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
                'observacoes'              => "WhatsApp Motorista: {$funcionario->nome} ({$telefoneNumeros}) | Viagem #{$movimentacaoAtiva->id}"
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

            // ⚡ Dispara recálculo do dia para o ajudante
            try {
                $calculoService = app(\App\Services\Ponto\PontoCalculoService::class);
                $calculoService->calcularDia($ajudante->id, $hoje);

                if (in_array($tipoBatidaAjudante, ['saida', 'fim_hora_extra'])) {
                    $comprovanteAj .= "\n\n" . $calculoService->obterMensagemResumoDiario($ajudante->id, $hoje);
                }
            } catch (\Exception $e) {
                Log::warning("Erro ao calcular resumo do ajudante: " . $e->getMessage());
            }

            $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, "✅ Ponto do ajudante registrado com sucesso!\n\n" . $comprovanteAj);

            return response()->json(['status' => 'ponto_ajudante_registrado']);
        }

        // 4. Lógica de Ponto Externa/Local
        if (isset($funcionario->tipo_ponto) && $funcionario->tipo_ponto == 'local') {
            $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, "⚠️ *ACESSO NEGADO!*\n\nOlá, *{$funcionario->nome}*. Seu cadastro exige registro de ponto presencial.");
            return response()->json(['status' => 'error', 'message' => 'Ponto apenas local']);
        }

        // Aguardando envio manual de localização
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
                    $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, "❌ Marcação cancelada.");
                    return response()->json(['status' => 'cancelado']);
                }
                $msgReq = "📍 *Aguardando Localização*\n\nPara registrar o ponto, clique no ícone de clipe (📎), escolha *Localização* e envie sua *Localização Atual*.\n\n_(Digite CANCELAR para abortar)_";
                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, $msgReq);
                return response()->json(['status' => 'aguardando_localizacao']);
            }
        }

        // =========================================================================
        // 🚀 MAPEAMENTO HIERÁRQUICO DAS 6 ETAPAS
        // =========================================================================
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

        // Busca todas as marcações válidas feitas hoje
        $marcacoesHoje = PontoMarcacao::where('funcionario_id', $funcionario->id)
            ->whereDate('data_hora_marcacao', $hoje)
            ->where('status', '!=', 'pendente_localizacao')
            ->orderBy('data_hora_marcacao', 'asc')
            ->get();

        $tiposJaBatidosToday = $marcacoesHoje->pluck('tipo_marcacao')->toArray();

        // Identifica qual a maior etapa já registrada hoje (0 a 6)
        $maiorNivelHoje = 0;
        foreach ($tiposJaBatidosToday as $tipoJa) {
            if (isset($ordemEtapas[$tipoJa]) && $ordemEtapas[$tipoJa] > $maiorNivelHoje) {
                $maiorNivelHoje = $ordemEtapas[$tipoJa];
            }
        }

        // Sugere a próxima etapa natural
        $proximaEtapaSugerida = 'entrada';
        if ($maiorNivelHoje == 1) $proximaEtapaSugerida = 'inicio_almoco';
        elseif ($maiorNivelHoje == 2) $proximaEtapaSugerida = 'fim_almoco';
        elseif ($maiorNivelHoje == 3) $proximaEtapaSugerida = 'saida';
        elseif ($maiorNivelHoje == 4) $proximaEtapaSugerida = 'inicio_hora_extra';
        elseif ($maiorNivelHoje == 5) $proximaEtapaSugerida = 'fim_hora_extra';
        elseif ($maiorNivelHoje >= 6) $proximaEtapaSugerida = 'concluido';

        // =========================================================================
        // 🚀 MENU DINÂMICO
        // =========================================================================
        if (!$tipoBatida) {
            if ($maiorNivelHoje >= 6) {
                $this->enviarRespostaWhatsApp(
                    $funcionario->empresa_id,
                    $telefoneNumeros,
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

            $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, $menu);
            return response()->json(['status' => 'menu_enviado']);
        }

        // =========================================================================
        // 🚀 REGRA DE PROGRESSÃO: SÓ ANDA PARA FRENTE
        // =========================================================================
        $nivelEscolhido = $ordemEtapas[$tipoBatida] ?? 0;

        if ($nivelEscolhido <= $maiorNivelHoje) {
            $this->enviarRespostaWhatsApp(
                $funcionario->empresa_id,
                $telefoneNumeros,
                "⚠️ *OPÇÃO INDISPONÍVEL!*\n\nVocê já registrou uma etapa igual ou superior hoje.\nO ponto só permite avançar para as próximas etapas disponíveis."
            );
            return response()->json(['status' => 'etapa_retrograda_bloqueada']);
        }

        // Para bater 6 (fim da extra), precisa ter batido 5 (início da extra)
        if ($tipoBatida == 'fim_hora_extra' && !in_array('inicio_hora_extra', $tiposJaBatidosToday)) {
            $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, "⚠️ Você precisa registrar a opção *5️⃣ INÍCIO HORA EXTRA* antes da saída final.");
            return response()->json(['status' => 'sequencia_invalida']);
        }

        // Trava de 15 minutos entre entrada e almoço (se a entrada foi registrada no WhatsApp)
        if ($tipoBatida == 'inicio_almoco') {
            $ultimaEntrada = $marcacoesHoje->firstWhere('tipo_marcacao', 'entrada');
            if ($ultimaEntrada && Carbon::parse($ultimaEntrada->data_hora_marcacao)->diffInMinutes($agora) < 15) {
                $faltamEntrada = 15 - Carbon::parse($ultimaEntrada->data_hora_marcacao)->diffInMinutes($agora);
                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, "⏳ Intervalo insuficiente. Aguarde {$faltamEntrada} min após a entrada para sair para o almoço.");
                return response()->json(['status' => 'bloqueado_inicio_almoco']);
            }
        }

        // Trava de 60 minutos de intervalo de almoço
        if ($tipoBatida == 'fim_almoco') {
            $ultimaSaidaAlmoco = $marcacoesHoje->firstWhere('tipo_marcacao', 'inicio_almoco');
            if ($ultimaSaidaAlmoco && Carbon::parse($ultimaSaidaAlmoco->data_hora_marcacao)->diffInMinutes($agora) < 60) {
                $faltam = 60 - Carbon::parse($ultimaSaidaAlmoco->data_hora_marcacao)->diffInMinutes($agora);
                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, "⏳ Intervalo de almoço em andamento. Aguarde {$faltam} min para registrar o retorno.");
                return response()->json(['status' => 'bloqueado_almoco']);
            }
        }

        // Obtenção de Coordenadas
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
            $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, $msgReq);
            return response()->json(['status' => 'solicitado_localizacao']);
        }

        $movIdVinculado = $movimentacaoAtiva ? $movimentacaoAtiva->id : null;
        $hashComprovante = strtoupper(substr(hash('sha256', $funcionario->id . $agora->timestamp . $tipoBatida), 0, 16));
        $obsJornada = "WhatsApp: {$telefoneNumeros} | Fonte: {$origemNome}";

        // Tratamento de Antecedência e Viagem Programada
        $horaAtual = $agora->format('H:i');
        if ($tipoBatida == 'entrada') {
            if ($movimentacaoAtiva) {
                $horaSaidaAgendada = Carbon::parse($movimentacaoAtiva->data_hora_saida)->format('H:i');
                $obsJornada .= " | 🚚 VIAGEM PROGRAMADA (Saída às {$horaSaidaAgendada})";
            } else {
                if ($horaAtual < '07:45') {
                    $obsJornada .= " | ⏰ ENTRADA ANTECIPADA (Sem viagem vinculada)";
                } elseif ($horaAtual > '08:10') {
                    $obsJornada .= " | ⚠️ ATRASO";
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

        $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, $comprovante);

        // =========================================================================
        // ⚡ 5. RECÁLCULO AUTOMÁTICO E EXTRATO DE FECHAMENTO DE EXPEDIENTE
        // =========================================================================
        try {
            $calculoService = app(\App\Services\Ponto\PontoCalculoService::class);
            $calculoService->calcularDia($funcionario->id, $hoje);

            // Se registrou SAÍDA (4) ou FIM DE HORA EXTRA (6), envia o extrato de horas
            if (in_array($tipoBatida, ['saida', 'fim_hora_extra'])) {
                $extratoDiario = $calculoService->obterMensagemResumoDiario($funcionario->id, $hoje);
                $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, $extratoDiario);
            }
        } catch (\Exception $e) {
            Log::warning("Erro ao calcular dia ou gerar extrato do colaborador: " . $e->getMessage());
        }

        // Se for a ENTRADA do motorista, solicita o PIN dos ajudantes da viagem
        if ($tipoBatida == 'entrada' && $movimentacaoAtiva && $movimentacaoAtiva->ajudantes && $movimentacaoAtiva->ajudantes->count() > 0) {
            $listaNomes = $movimentacaoAtiva->ajudantes->pluck('nome')->implode(', ');
            $msgAjudantes = "👥 *REGISTRO DE AJUDANTES DA SUA VIAGEM*\n\nSua entrada foi registrada!\nAjudantes escalados com você: *{$listaNomes}*.\n\nPeça para cada um enviar o seu *PIN de 4 dígitos* respondendo a esta conversa para registrar a entrada deles.";
            $this->enviarRespostaWhatsApp($funcionario->empresa_id, $telefoneNumeros, $msgAjudantes);
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

    private function enviarRespostaWhatsApp($empresaId, $telefone, $mensagem)
    {
        try {
            $destino = explode('@', (string) $telefone)[0];
            $numLimpo = preg_replace('/[^0-9]/', '', $destino);

            if (strlen($numLimpo) >= 10 && strlen($numLimpo) <= 11 && !str_starts_with($numLimpo, '55')) {
                $numLimpo = '55' . $numLimpo;
            }

            app(\App\Utils\WhatsAppUtil::class)->sendMessage(
                $numLimpo,
                $mensagem,
                (int) $empresaId
            );
        } catch (\Throwable $e) {
            Log::error("Erro envio WhatsApp via Connect|API: " . $e->getMessage());
        }
    }

    private function obterEnderecoPorCoordenadas($lat, $lon, $empresaId = null, $traccarId = null)
    {
        if (!$lat || !$lon) return null;

        $latFloat = (float) str_replace(',', '.', (string)$lat);
        $lonFloat = (float) str_replace(',', '.', (string)$lon);

        $cacheKey = 'geo_' . round($latFloat, 4) . '_' . round($lonFloat, 4);

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 86400 * 7, function () use ($latFloat, $lonFloat, $empresaId, $traccarId) {
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

                    $rua = $address['road'] ?? $address['street'] ?? $address['pedestrian'] ?? $address['suburb'] ?? '';
                    $bairro = $address['neighbourhood'] ?? $address['quarter'] ?? $address['suburb'] ?? '';
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
