<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MovimentacaoVeiculo;
use App\Models\TraccarConfig;
use App\Models\PontoMarcacao;
use App\Models\Funcionario;
use App\Models\Veiculo;
use App\Models\TraccarWebhookEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MonitorarFrota extends Command
{
    /**
     * Eventos persistidos só são marcados como consumidos depois do save()
     * da movimentação. Em caso de falha antes do save, ficam disponíveis
     * para uma nova execução.
     */
    private array $eventosWebhookParaConsumir = [];

    protected $signature = 'frota:monitorar';
    protected $description = 'Consulta a API do Traccar e calcula distância para dar saída/chegada na frota e notificar ponto.';

    public function handle()
    {
        $empresasIds = MovimentacaoVeiculo::whereIn('status', ['agendado', 'iniciado'])
            ->pluck('empresa_id')
            ->unique();

        if ($empresasIds->isEmpty()) {
            return;
        }

        foreach ($empresasIds as $empresaId) {
            $config = TraccarConfig::where('empresa_id', $empresaId)->first();

            /*
             * Prioridade da configuração Traccar:
             *
             * 1. Configuração específica da empresa no MySQL;
             * 2. Configuração global em config/traccar.php, alimentada pelo .env.
             *
             * O fallback é feito campo a campo para permitir que o tenant sobrescreva
             * apenas o que for necessário.
             */
            $baseUrl = $config?->base_url ?: config('traccar.base_url');
            $username = $config?->mail_user_name ?: config('traccar.auth.username');
            $password = $config?->password ?: config('traccar.auth.password');
            $token = $config?->token_traccar ?: config('traccar.auth.token');

            if (empty($baseUrl) || (empty($token) && (empty($username) || empty($password)))) {
                Log::warning('Traccar não configurado para monitoramento da frota.', [
                    'empresa_id' => $empresaId,
                ]);
                continue;
            }

            $movimentacoes = MovimentacaoVeiculo::with(['veiculo', 'motorista', 'ajudantes'])
                ->where('empresa_id', $empresaId)
                ->whereIn('status', ['agendado', 'iniciado'])
                ->get();

            try {
                $urlDevices = rtrim($baseUrl, '/') . '/api/devices';
                $requestDevices = Http::withHeaders([
                    'Accept' => 'application/json'
                ])
                    ->connectTimeout(5)
                    ->timeout(15);

                if (!empty($token)) {
                    $requestDevices = $requestDevices->withToken($token);
                } else {
                    $requestDevices = $requestDevices->withBasicAuth(
                        $username,
                        $password
                    );
                }

                $respDevices = $requestDevices->get($urlDevices);

                if ($respDevices->failed()) {
                    Log::warning('ROBÔ: Falha ao consultar dispositivos no Traccar.', [
                        'empresa_id' => $empresaId,
                        'http_status' => $respDevices->status(),
                    ]);
                    continue;
                }

                $listaDispositivos = collect($respDevices->json());

                $urlPositions = rtrim($baseUrl, '/') . '/api/positions';
                $requestPositions = Http::withHeaders([
                    'Accept' => 'application/json'
                ])
                    ->connectTimeout(5)
                    ->timeout(15);

                if (!empty($token)) {
                    $requestPositions = $requestPositions->withToken($token);
                } else {
                    $requestPositions = $requestPositions->withBasicAuth(
                        $username,
                        $password
                    );
                }

                $respPositions = $requestPositions->get($urlPositions);

                if ($respPositions->failed()) {
                    Log::warning('ROBÔ: Falha ao consultar posições no Traccar.', [
                        'empresa_id' => $empresaId,
                        'http_status' => $respPositions->status(),
                    ]);
                    continue;
                }

                $posicoes = collect($respPositions->json());
            } catch (\Throwable $e) {
                /*
                 * Uma indisponibilidade do Traccar de um tenant não derruba
                 * o comando inteiro nem impede o processamento das demais
                 * empresas. Na próxima execução o robô tenta novamente.
                 */
                Log::warning('ROBÔ: Traccar temporariamente indisponível.', [
                    'empresa_id' => $empresaId,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            $movsPorVeiculo = $movimentacoes->groupBy('veiculo_id');

            foreach ($movsPorVeiculo as $veiculoId => $movsDoVeiculo) {

                $movAtiva = $movsDoVeiculo->where('status', 'iniciado')->first()
                    ?? $movsDoVeiculo->where('status', 'agendado')->sortBy('data_hora_saida')->first();

                if (!$movAtiva) continue;

                $imeiDispositivo = null;
                if ($movAtiva->motorista_id) {
                    $imeiDispositivo = Funcionario::find($movAtiva->motorista_id)->traccar_id ?? null;
                }
                if (!$imeiDispositivo && $movAtiva->veiculo) {
                    $imeiDispositivo = $movAtiva->veiculo->traccar_id ?? null;
                }

                if (!$imeiDispositivo) continue;

                /*
                 * Compatibilidade sem migração abrupta:
                 *
                 * - padrão atual: traccar_id = device.uniqueId (IMEI);
                 * - legado: alguns registros podem conter device.id.
                 */
                $traccarDevice = $listaDispositivos->first(function ($device) use ($imeiDispositivo) {
                    $uniqueId = isset($device['uniqueId'])
                        ? (string) $device['uniqueId']
                        : '';

                    $deviceId = isset($device['id'])
                        ? (string) $device['id']
                        : '';

                    return $uniqueId === (string) $imeiDispositivo
                        || $deviceId === (string) $imeiDispositivo;
                });

                if (!$traccarDevice) continue;

                $idInternoTraccar = $traccarDevice['id'];
                $pos = $posicoes->firstWhere('deviceId', $idInternoTraccar);
                if (!$pos) continue;

                $latAtual = $pos['latitude'];
                $lonAtual = $pos['longitude'];
                $velocidadeNoh = $pos['speed'] ?? 0;
                $velocidadeKmH = $velocidadeNoh * 1.852;

                $base = $movAtiva->filial_id
                    ? DB::table('filials')->where('id', $movAtiva->filial_id)->first()
                    : DB::table('config_notas')->where('empresa_id', $movAtiva->empresa_id)->first();

                $latBase = $base->latitude ?? null;
                $lonBase = $base->longitude ?? null;

                $latOrigem = $latBase;
                $lonOrigem = $lonBase;

                if (isset($movAtiva->tipo_partida) && $movAtiva->tipo_partida == 'residencia' && $movAtiva->motorista) {
                    $latOrigem = $movAtiva->motorista->latitude_residencia ?? $latBase;
                    $lonOrigem = $movAtiva->motorista->longitude_residencia ?? $lonBase;
                }

                $distanciaOrigem = ($latOrigem && $lonOrigem) ? $this->calcularDistanciaMetros($latAtual, $lonAtual, $latOrigem, $lonOrigem) : null;
                $distanciaDestino = ($movAtiva->latitude_destino && $movAtiva->longitude_destino) ? $this->calcularDistanciaMetros($latAtual, $lonAtual, $movAtiva->latitude_destino, $movAtiva->longitude_destino) : null;
                $distanciaBase = ($latBase && $lonBase) ? $this->calcularDistanciaMetros($latAtual, $lonAtual, $latBase, $lonBase) : null;

                $atualizou = false;
                $horario = now();

                /*
                 * Eventos capturados pelo webhook do Traccar.
                 *
                 * O webhook não altera a movimentação diretamente. Ele apenas
                 * persiste o evento no inbox Traccar e este comando continua sendo o
                 * responsável por efetivar todas as regras existentes.
                 */
                $eventoSaidaOrigem = $this->getEventoWebhook(
                    $movAtiva->id,
                    'saida_origem'
                );

                $eventoChegadaCliente = $this->getEventoWebhook(
                    $movAtiva->id,
                    'chegada_cliente'
                );

                $eventoSaidaCliente = $this->getEventoWebhook(
                    $movAtiva->id,
                    'saida_cliente'
                );

                $eventoChegadaBase = $this->getEventoWebhook(
                    $movAtiva->id,
                    'chegada_base'
                );

                $horarioSaidaOrigemWebhook = $this->getHorarioEventoWebhook(
                    $eventoSaidaOrigem
                );

                $horarioChegadaClienteWebhook = $this->getHorarioEventoWebhook(
                    $eventoChegadaCliente
                );

                $horarioSaidaClienteWebhook = $this->getHorarioEventoWebhook(
                    $eventoSaidaCliente
                );

                $horarioChegadaBaseWebhook = $this->getHorarioEventoWebhook(
                    $eventoChegadaBase
                );

                // Lógica de Saída da Agenda
                if ($movAtiva->status == 'agendado') {
                    $dataAgendada = Carbon::parse(
                        $movAtiva->data_hora_saida
                    )->startOfDay();

                    $horarioPrevistoSaida = Carbon::parse(
                        $movAtiva->data_hora_saida
                    );

                    $horarioPermitidoIniciar = (clone $horarioPrevistoSaida)
                        ->subMinutes(30);

                    /*
                     * Se o webhook registrou a saída da origem, usamos o
                     * horário real do evento como referência.
                     *
                     * Caso contrário, permanece exatamente a heurística
                     * existente: velocidade >= 5 km/h e distância > 200 m.
                     */
                    $momentoReferenciaSaida = $horarioSaidaOrigemWebhook
                        ?: Carbon::now();

                    $dataReferenciaSaida = $momentoReferenciaSaida
                        ->copy()
                        ->startOfDay();

                    $saiuDoLugarPorWebhook = $horarioSaidaOrigemWebhook !== null;

                    // Trava de movimento real: velocidade mínima de 5 km/h e mais de 200 metros de afastamento da origem
                    $saiuDoLugar = $saiuDoLugarPorWebhook
                        || (
                            ($velocidadeKmH >= 5)
                            && (
                                $distanciaOrigem !== null
                                && $distanciaOrigem > 200
                            )
                        );

                    if (
                        $dataAgendada->equalTo($dataReferenciaSaida)
                        && $momentoReferenciaSaida->greaterThanOrEqualTo(
                            $horarioPermitidoIniciar
                        )
                        && $saiuDoLugar
                    ) {

                        // Trava anti-duplicação de mensagens: dispara somente se a saída real ainda não estiver preenchida
                        $jaDisparado = !empty($movAtiva->data_hora_saida_real);

                        // 1. PONTO AUTOMÁTICO DO MOTORISTA
                        if (!$jaDisparado && $movAtiva->motorista && $movAtiva->bater_ponto_whatsapp == 1) {
                            $func = $movAtiva->motorista;
                            $pontoExiste = PontoMarcacao::where('funcionario_id', $func->id)
                                ->whereDate('data_hora_marcacao', Carbon::now()->format('Y-m-d'))
                                ->where('tipo_marcacao', 'entrada')
                                ->exists();

                            if (!$pontoExiste) {
                                /*
                                 * O horário do ponto continua sendo o momento
                                 * efetivo do processamento, sem retroagir marcação
                                 * trabalhista por causa de pacote GPS offline.
                                 *
                                 * Apenas a saída da viagem usa o horário do GPS.
                                 */
                                $agora = Carbon::now();
                                $hashComprovante = strtoupper(substr(hash('sha256', $func->id . $agora->timestamp . 'entrada'), 0, 16));

                                PontoMarcacao::create([
                                    'empresa_id'               => $movAtiva->empresa_id,
                                    'funcionario_id'           => $func->id,
                                    'data_hora_marcacao'       => $agora,
                                    'origem'                   => 'automatico_traccar',
                                    'tipo_marcacao'            => 'entrada',
                                    'status'                   => 'processada',
                                    'latitude'                 => $latAtual,
                                    'longitude'                => $lonAtual,
                                    'movimentacao_veiculo_id'  => $movAtiva->id,
                                    'observacoes'              => "WhatsApp: {$func->celular} | 🚀 Batida automática ({$movAtiva->tipo_partida})"
                                ]);

                                $this->enviarComprovantePontoAutomaticoWhatsApp($movAtiva, $func, $latAtual, $lonAtual, $hashComprovante);
                            }
                        }

                        // 2. SOLICITAÇÃO DO PIN PARA OS AJUDANTES DA VIAGEM (Disparo único)
                        if (!$jaDisparado && $movAtiva->ajudantes && $movAtiva->ajudantes->count() > 0 && $movAtiva->motorista) {
                            $telefoneMotorista = $movAtiva->motorista->celular ?? $movAtiva->motorista->telefone;
                            $listaAjudantes = $movAtiva->ajudantes->pluck('nome')->implode(', ');
                            $msgAjudantes = "👥 *REGISTRO DE AJUDANTES DA SUA VIAGEM*\n\nA rota foi iniciada!\nAjudantes escalados: *{$listaAjudantes}*.\n\nPara registrar a ENTRADA da equipe, peça para cada um enviar o seu *PIN de 4 dígitos* respondendo a esta mensagem.";

                            $this->enviarRespostaWhatsApp($movAtiva->empresa_id, $telefoneMotorista, $msgAjudantes);
                        }

                        // 3. CHECKLIST VEICULAR OBRIGATÓRIO (Disparo único)
                        if (!$jaDisparado && $movAtiva->motorista && ($movAtiva->checklist_obrigatorio ?? 1) == 1) {
                            $telefoneMotorista = $movAtiva->motorista->celular ?? $movAtiva->motorista->telefone;

                            // Se a URL do .env ainda estiver como localhost, faz fallback automático para o domínio de produção
                            $baseDominio = config('app.url');
                            if (empty($baseDominio) || str_contains($baseDominio, 'localhost')) {
                                $baseDominio = 'https://grupometal.fersofterp.com.br';
                            }
                            $linkChecklist = rtrim($baseDominio, '/') . '/checklist/veiculo/' . $movAtiva->id;

                            $msgChecklist = "📋 *CHECKLIST DE PRÉ-VIAGEM OBRIGATÓRIO*\n\nOlá, *{$movAtiva->motorista->nome}*! O veículo iniciou o percurso.\nPor favor, preencha o checklist veicular e envie as fotos pelo link abaixo:\n\n👉 {$linkChecklist}";

                            $this->enviarRespostaWhatsApp($movAtiva->empresa_id, $telefoneMotorista, $msgChecklist);
                        }

                        $movAtiva->data_hora_saida_real = $horarioSaidaOrigemWebhook
                            ?: $horario;

                        $movAtiva->status = 'iniciado';
                        $atualizou = true;

                        if ($horarioSaidaOrigemWebhook !== null) {
                            $this->forgetEventoWebhook(
                                $movAtiva->id,
                                'saida_origem'
                            );
                        }
                    }
                }
                elseif ($movAtiva->status == 'iniciado') {
                    /*
                     * Limpa eventos que eventualmente ficaram pendentes depois
                     * de uma etapa já ter sido gravada por outra execução.
                     */
                    if (!empty($movAtiva->data_hora_chegada_cliente)) {
                        $this->forgetEventoWebhook(
                            $movAtiva->id,
                            'chegada_cliente'
                        );

                        $horarioChegadaClienteWebhook = null;
                        $eventoChegadaCliente = null;
                    }

                    if (!empty($movAtiva->data_hora_saida_cliente)) {
                        $this->forgetEventoWebhook(
                            $movAtiva->id,
                            'saida_cliente'
                        );

                        $horarioSaidaClienteWebhook = null;
                        $eventoSaidaCliente = null;
                    }

                    if (!empty($movAtiva->data_hora_chegada)) {
                        $this->forgetEventoWebhook(
                            $movAtiva->id,
                            'chegada_base'
                        );

                        $horarioChegadaBaseWebhook = null;
                        $eventoChegadaBase = null;
                    }

                    $chegouDestino = ($horarioChegadaClienteWebhook !== null)
                        || (
                            $distanciaDestino !== null
                            && $distanciaDestino <= 250
                        );

                    $saiuDestino = ($horarioSaidaClienteWebhook !== null)
                        || (
                            $distanciaDestino !== null
                            && $distanciaDestino > 300
                        );

                    $chegouBase = ($horarioChegadaBaseWebhook !== null)
                        || (
                            $distanciaBase !== null
                            && $distanciaBase <= 250
                        );

                    // Registro de Chegada no Cliente
                    if (
                        $chegouDestino
                        && empty($movAtiva->data_hora_chegada_cliente)
                    ) {
                        $movAtiva->data_hora_chegada_cliente =
                            $horarioChegadaClienteWebhook
                                ?: $horario;

                        $atualizou = true;

                        if ($horarioChegadaClienteWebhook !== null) {
                            $this->forgetEventoWebhook(
                                $movAtiva->id,
                                'chegada_cliente'
                            );
                        }
                    }
                    // Registro de Saída do Cliente (exige ter chegado antes e ficado pelo menos 2 minutos no local)
                    elseif (
                        $saiuDestino
                        && !empty($movAtiva->data_hora_chegada_cliente)
                        && empty($movAtiva->data_hora_saida_cliente)
                    ) {
                        $horarioSaidaClienteEfetivo =
                            $horarioSaidaClienteWebhook
                                ?: $horario;

                        $velocidadeSaidaKmH =
                            $this->getVelocidadeEventoWebhookKmH(
                                $eventoSaidaCliente
                            )
                            ?? $velocidadeKmH;

                        $minutosNoCliente = Carbon::parse(
                            $movAtiva->data_hora_chegada_cliente
                        )->diffInMinutes(
                            $horarioSaidaClienteEfetivo
                        );

                        if (
                            $minutosNoCliente >= 2
                            || $velocidadeSaidaKmH > 10
                        ) {
                            $movAtiva->data_hora_saida_cliente =
                                $horarioSaidaClienteEfetivo;

                            $atualizou = true;

                            if ($horarioSaidaClienteWebhook !== null) {
                                $this->forgetEventoWebhook(
                                    $movAtiva->id,
                                    'saida_cliente'
                                );
                            }
                        } elseif ($horarioSaidaClienteWebhook !== null) {
                            /*
                             * Evento muito rápido após a entrada e sem velocidade
                             * suficiente: tratamos como possível oscilação de cerca
                             * e deixamos a heurística normal assumir depois.
                             */
                            $this->forgetEventoWebhook(
                                $movAtiva->id,
                                'saida_cliente'
                            );
                        }
                    }
                    // Retorno à Garagem e Conclusão (apenas se já registrou a saída do cliente)
                    elseif (
                        $chegouBase
                        && !empty($movAtiva->data_hora_saida_cliente)
                        && empty($movAtiva->data_hora_chegada)
                    ) {
                        $movAtiva->data_hora_chegada =
                            $horarioChegadaBaseWebhook
                                ?: $horario;

                        $movAtiva->status = 'finalizado';

                        $kmPercorrido = 0;
                        try {
                            $from = Carbon::parse($movAtiva->data_hora_saida_real)->timezone('UTC')->format('Y-m-d\TH:i:s\Z');

                            /*
                             * Se o retorno veio pelo webhook, o relatório do
                             * Traccar termina no horário real do evento.
                             */
                            $to = Carbon::parse(
                                $movAtiva->data_hora_chegada
                            )
                                ->timezone('UTC')
                                ->format('Y-m-d\TH:i:s\Z');

                            $urlSummary = rtrim($baseUrl, '/') . "/api/reports/summary?deviceId={$idInternoTraccar}&from={$from}&to={$to}";

                            $requestSummary = Http::withHeaders(['Accept' => 'application/json']);
                            if (!empty($token)) {
                                $requestSummary = $requestSummary->withToken($token);
                            } else {
                                $requestSummary = $requestSummary->withBasicAuth($username, $password);
                            }
                            $respSummary = $requestSummary->get($urlSummary);

                            if ($respSummary->successful() && count($respSummary->json()) > 0) {
                                $kmPercorrido = ($respSummary->json()[0]['distance'] ?? 0) / 1000;
                            }
                        } catch (\Exception $e) {
                            Log::error("ROBÔ: Erro ao calcular resumo de KM: " . $e->getMessage());
                        }

                        if ($kmPercorrido <= 0) {
                            $distIda = ($latOrigem && $lonOrigem && $movAtiva->latitude_destino && $movAtiva->longitude_destino)
                                ? $this->calcularDistanciaMetros($latOrigem, $lonOrigem, $movAtiva->latitude_destino, $movAtiva->longitude_destino) : 0;
                            $distVolta = ($movAtiva->latitude_destino && $movAtiva->longitude_destino && $latBase && $lonBase)
                                ? $this->calcularDistanciaMetros($movAtiva->latitude_destino, $movAtiva->longitude_destino, $latBase, $lonBase) : 0;
                            $kmPercorrido = (($distIda + $distVolta) / 1000) * 1.25;
                        }

                        $movAtiva->km_final = $movAtiva->km_inicial + round(max(0, $kmPercorrido), 2);

                        if ($movAtiva->veiculo_id && $movAtiva->km_final > 0) {
                            $veiculoObj = Veiculo::find($movAtiva->veiculo_id);
                            if ($veiculoObj) {
                                $veiculoObj->quilometragem = $movAtiva->km_final;
                                $veiculoObj->save();
                            }
                        }

                        $atualizou = true;

                        if ($horarioChegadaBaseWebhook !== null) {
                            $this->forgetEventoWebhook(
                                $movAtiva->id,
                                'chegada_base'
                            );
                        }
                    }
                }

                if ($atualizou) {
                    $movAtiva->save();
                }

                /*
                 * Confirma o consumo somente após a persistência da etapa.
                 * Chamadas de limpeza de etapas já gravadas também passam
                 * por este ponto, mantendo o processo idempotente.
                 */
                $this->confirmarEventosWebhookProcessados(
                    $movAtiva->id
                );
            }
        }
    }

    private function getEventoWebhook(
        int $movimentacaoId,
        string $etapa
    ): ?array {
        /*
         * Fonte principal: inbox persistente no MySQL.
         *
         * O try/catch é deliberado: caso a migration ainda não tenha sido
         * executada durante um deploy, o robô NÃO quebra. Ele continua com
         * a heurística normal e ainda consegue ler eventos do Cache da versão
         * anterior.
         */
        try {
            $persistido = TraccarWebhookEvent::query()
                ->where('movimentacao_id', $movimentacaoId)
                ->where('etapa', $etapa)
                ->where(
                    'status',
                    TraccarWebhookEvent::STATUS_CLASSIFICADO
                )
                ->orderBy('event_time')
                ->orderBy('id')
                ->first();

            if ($persistido) {
                return $persistido->toMonitorPayload();
            }
        } catch (\Throwable $e) {
            Log::warning(
                'ROBÔ: inbox persistente Traccar indisponível; usando fallback.',
                [
                    'movimentacao_id' => $movimentacaoId,
                    'etapa' => $etapa,
                    'error' => $e->getMessage(),
                ]
            );
        }

        // Retrocompatibilidade durante a transição Cache -> MySQL.
        $evento = Cache::get(
            $this->getEventoWebhookCacheKey(
                $movimentacaoId,
                $etapa
            )
        );

        return is_array($evento)
            ? $evento
            : null;
    }

    private function getHorarioEventoWebhook(
        ?array $evento
    ): ?Carbon {
        if (
            !$evento
            || empty($evento['event_time'])
        ) {
            return null;
        }

        try {
            return Carbon::parse(
                $evento['event_time']
            )->setTimezone(
                config(
                    'app.timezone',
                    'America/Bahia'
                )
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getVelocidadeEventoWebhookKmH(
        ?array $evento
    ): ?float {
        if (
            !$evento
            || !isset($evento['speed'])
            || $evento['speed'] === null
        ) {
            return null;
        }

        /*
         * A API do Traccar informa speed em nós.
         */
        return ((float) $evento['speed']) * 1.852;
    }

    private function forgetEventoWebhook(
        int $movimentacaoId,
        string $etapa
    ): void {
        /*
         * Não marca o registro persistente como consumido aqui.
         * Primeiro deixamos a movimentação salvar com sucesso.
         */
        $this->eventosWebhookParaConsumir[$movimentacaoId][$etapa] = true;

        // Eventos legados em Cache podem ser removidos imediatamente.
        Cache::forget(
            $this->getEventoWebhookCacheKey(
                $movimentacaoId,
                $etapa
            )
        );
    }

    private function confirmarEventosWebhookProcessados(
        int $movimentacaoId
    ): void {
        $etapas = array_keys(
            $this->eventosWebhookParaConsumir[$movimentacaoId]
            ?? []
        );

        if (empty($etapas)) {
            return;
        }

        try {
            TraccarWebhookEvent::query()
                ->where('movimentacao_id', $movimentacaoId)
                ->whereIn('etapa', $etapas)
                ->where(
                    'status',
                    TraccarWebhookEvent::STATUS_CLASSIFICADO
                )
                ->update([
                    'status' => TraccarWebhookEvent::STATUS_CONSUMIDO,
                    'processed_at' => now(),
                    'locked_at' => null,
                    'next_retry_at' => null,
                    'last_error' => null,
                    'updated_at' => now(),
                ]);

            unset(
                $this->eventosWebhookParaConsumir[$movimentacaoId]
            );
        } catch (\Throwable $e) {
            /*
             * Não interrompe o robô depois de a regra de negócio já ter sido
             * gravada. Na próxima execução, os próprios campos da movimentação
             * impedem a repetição da etapa e o evento será limpo novamente.
             */
            Log::warning(
                'ROBÔ: não foi possível confirmar consumo do webhook Traccar.',
                [
                    'movimentacao_id' => $movimentacaoId,
                    'etapas' => $etapas,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    private function getEventoWebhookCacheKey(
        int $movimentacaoId,
        string $etapa
    ): string {
        return "traccar:frota:movimentacao:{$movimentacaoId}:{$etapa}";
    }

    private function enviarComprovantePontoAutomaticoWhatsApp($movimentacao, $funcionario, $lat, $lon, $hash)
    {
        try {
            $telefone = $funcionario->celular ?? $funcionario->telefone;
            if (empty($telefone)) return;

            $telefoneLimpo = preg_replace('/[^0-9]/', '', $telefone);
            if (!str_starts_with($telefoneLimpo, '55')) {
                $telefoneLimpo = '55' . $telefoneLimpo;
            }

            $evoInstance = DB::table('evo_api_instances')->where('empresa_id', $movimentacao->empresa_id)->first();
            if (!$evoInstance) return;

            $dadosEmpresa = $this->getEmpresaConfigDoRobo($movimentacao->empresa_id, $movimentacao->filial_id);
            $placa = $movimentacao->veiculo->placa ?? 'Veículo';

            $comprovante = "🧾 *COMPROVANTE DE REGISTRO DE PONTO (AUTOMÁTICO)*\n";
            $comprovante .= "----------------------------------------\n";
            $comprovante .= "🏢 *Empresa:* {$dadosEmpresa['razao_social']}\n";
            $comprovante .= "📋 *CNPJ:* {$dadosEmpresa['cnpj']}\n";
            $comprovante .= "📍 *Endereço:* {$dadosEmpresa['endereco']}\n";
            $comprovante .= "----------------------------------------\n";
            $comprovante .= "👤 *Funcionário:* {$funcionario->nome}\n";
            $comprovante .= "🔢 *PIS:* " . ($funcionario->pis ?? 'Não informado') . "\n";
            $comprovante .= "----------------------------------------\n";
            $comprovante .= "📌 *Tipo:* 🟢 ENTRADA (Início de Jornada)\n";
            $comprovante .= "🕒 *Data/Hora:* " . Carbon::now()->format('d/m/Y \à\s H:i:s') . "\n";
            $comprovante .= "🚛 *Veículo:* {$placa}\n";
            $comprovante .= "📍 *GPS Traccar:* Lat: {$lat}, Lon: {$lon}\n";
            $comprovante .= "----------------------------------------\n";
            $comprovante .= "🔑 *Autenticação:* {$hash}\n";
            $comprovante .= "----------------------------------------\n";
            $comprovante .= "_Comprovante emitido nos termos da Portaria 671/2021 MTE_";

            Http::withHeaders(['apikey' => $evoInstance->api_key])
                ->post(rtrim($evoInstance->base_url, '/') . "/message/sendText/{$evoInstance->name}", [
                    'number' => $telefoneLimpo,
                    'textMessage' => ['text' => $comprovante]
                ]);
        } catch (\Exception $e) {
            Log::error("ROBÔ Ponto: " . $e->getMessage());
        }
    }

    private function enviarRespostaWhatsApp($empresaId, $telefone, $mensagem)
    {
        try {
            if (empty($telefone)) return;
            $telefoneLimpo = preg_replace('/[^0-9]/', '', $telefone);
            if (!str_starts_with($telefoneLimpo, '55')) $telefoneLimpo = '55' . $telefoneLimpo;

            $evoInstance = DB::table('evo_api_instances')->where('empresa_id', $empresaId)->first();
            if (!$evoInstance) return;

            Http::withHeaders(['apikey' => $evoInstance->api_key])
                ->post(rtrim($evoInstance->base_url, '/') . "/message/sendText/{$evoInstance->name}", [
                    'number' => $telefoneLimpo,
                    'textMessage' => ['text' => $mensagem]
                ]);
        } catch (\Exception $e) {
            Log::error("ROBÔ WhatsApp: " . $e->getMessage());
        }
    }

    private function getEmpresaConfigDoRobo($empresaId, $filialId = null)
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

        return [
            'razao_social' => 'FERSOFT ERP',
            'cnpj'          => '00.000.000/0001-00',
            'endereco'      => 'Endereço não cadastrado'
        ];
    }

    private function calcularDistanciaMetros($lat1, $lon1, $lat2, $lon2)
    {
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return null;
        $raioTerra = 6371000;
        $latDe = deg2rad($lat1);
        $lonDe = deg2rad($lon1);
        $latPara = deg2rad($lat2);
        $lonPara = deg2rad($lon2);

        $deltaLat = $latPara - $latDe;
        $deltaLon = $lonPara - $lonDe;

        $angulo = 2 * asin(sqrt(pow(sin($deltaLat / 2), 2) + cos($latDe) * cos($latPara) * pow(sin($deltaLon / 2), 2)));
        return $angulo * $raioTerra;
    }
}
