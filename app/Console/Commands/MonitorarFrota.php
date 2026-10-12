<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MovimentacaoVeiculo;
use App\Models\TraccarConfig;
use App\Models\PontoMarcacao;
use App\Models\Funcionario;
use App\Models\Veiculo;
use App\Models\TraccarWebhookEvent;
use App\Services\AppVersionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;
use Carbon\Carbon;

class MonitorarFrota extends Command
{
    /**
     * Eventos persistidos só são marcados como consumidos depois do save()
     * da movimentação. Em caso de falha antes do save, ficam disponíveis
     * para uma nova execução.
     */
    private array $eventosWebhookParaConsumir = [];
    private array $eventosWebhookLidos = [];

    protected $signature = 'frota:monitorar';
    protected $description = 'Monitora frota Traccar, webhooks, ponto, destinos, paradas e preventivas.';

    public function handle()
    {
        $empresasIds = MovimentacaoVeiculo::whereIn('status', ['agendado', 'iniciado'])
            ->pluck('empresa_id')
            ->unique();

        if ($empresasIds->isEmpty()) {
            return self::SUCCESS;
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

            // Destinos é opcional: a release 5.0.202 ainda não possui esse relacionamento.
            $relacoes = ['veiculo', 'motorista', 'ajudantes'];
            if (method_exists(MovimentacaoVeiculo::class, 'destinos')) {
                $relacoes[] = 'destinos';
            }

            $movimentacoes = MovimentacaoVeiculo::with($relacoes)
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

            $movsPorVeiculo = $movimentacoes->groupBy('veiculo_id')
                ->sortBy(function ($movs) {
                    // Acompanhamento de rota já iniciada tem precedência global.
                    $ativa = $movs->firstWhere('status', 'iniciado');
                    return ($ativa ? '0' : '1') . '_' .
                        (string) ($ativa?->data_hora_saida ?? $movs->min('data_hora_saida'));
                });
            $rastreadoresProcessados = [];

            foreach ($movsPorVeiculo as $veiculoId => $movsDoVeiculo) {

                $movAtiva = $movsDoVeiculo->where('status', 'iniciado')->first()
                    ?? $movsDoVeiculo->where('status', 'agendado')->sortBy('data_hora_saida')->first();

                if (!$movAtiva) continue;

                try {
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

                    // Evita processar o mesmo dispositivo em viagens diferentes.
                    if (isset($rastreadoresProcessados[(string) $idInternoTraccar])) {
                        Log::warning('ROBÔ: rastreador associado a mais de uma viagem ativa.', [
                            'empresa_id' => $empresaId,
                            'movimentacao_id' => $movAtiva->id,
                            'device_id' => $idInternoTraccar,
                        ]);
                        continue;
                    }
                    $rastreadoresProcessados[(string) $idInternoTraccar] = true;

                    if (!isset($pos['latitude'], $pos['longitude'])
                        || !is_numeric($pos['latitude']) || !is_numeric($pos['longitude'])) {
                        continue;
                    }

                    $latAtual = (float) $pos['latitude'];
                    $lonAtual = (float) $pos['longitude'];
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

                    $distanciaOrigem = ($latOrigem !== null && $lonOrigem !== null) ? $this->calcularDistanciaMetros($latAtual, $lonAtual, $latOrigem, $lonOrigem) : null;
                    $distanciaDestino = ($movAtiva->latitude_destino !== null && $movAtiva->longitude_destino !== null) ? $this->calcularDistanciaMetros($latAtual, $lonAtual, $movAtiva->latitude_destino, $movAtiva->longitude_destino) : null;
                    $distanciaBase = ($latBase !== null && $lonBase !== null) ? $this->calcularDistanciaMetros($latAtual, $lonAtual, $latBase, $lonBase) : null;

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

                                $linkChecklist = $this->urlPublica('checklist/veiculo/' . $movAtiva->id);
                                if ($linkChecklist !== null) {
                                    $msgChecklist = "📋 *CHECKLIST DE PRÉ-VIAGEM OBRIGATÓRIO*\n\nOlá, *{$movAtiva->motorista->nome}*! O veículo iniciou o percurso.\nPor favor, preencha o checklist veicular e envie as fotos pelo link abaixo:\n\n👉 {$linkChecklist}";
                                    $this->enviarRespostaWhatsApp($movAtiva->empresa_id, $telefoneMotorista, $msgChecklist);
                                }
                            }

                            // Recurso novo: abastecimento. Somente com rota pública existente.
                            if (!$jaDisparado && $movAtiva->motorista
                                && (int) ($movAtiva->solicitar_abastecimento ?? 0) === 1) {
                                $link = $this->urlPublica('abastecimento-motorista/' . $movAtiva->id);
                                if ($link !== null && $this->rotaPublicaGetExiste('abastecimento-motorista/{id}')) {
                                    $telefone = $movAtiva->motorista->celular ?? $movAtiva->motorista->telefone;
                                    $placa = $movAtiva->veiculo->placa ?? 'do veículo';
                                    $mensagem = "⛽ *REGISTRO DE ABASTECIMENTO*\n\nSua viagem foi iniciada!\nSe houver necessidade de abastecimento para o veículo *{$placa}*, registre os litros e a foto do cupom fiscal:\n\n👉 {$link}";
                                    $this->enviarRespostaWhatsApp($movAtiva->empresa_id, $telefone, $mensagem);
                                } else {
                                    Log::warning('ROBÔ: link de abastecimento indisponível; mensagem não enviada.', [
                                        'movimentacao_id' => $movAtiva->id,
                                    ]);
                                }
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
                        // Rotas multi-stop complementam a rota legada, sem substituí-la.
                        $temDestinos = method_exists(MovimentacaoVeiculo::class, 'destinos')
                            && $movAtiva->destinos && $movAtiva->destinos->isNotEmpty();

                        if ($temDestinos) {
                            $this->processarDestinos($movAtiva, $latAtual, $lonAtual, $velocidadeKmH, $horario);
                        }

                        // Etapa concluída por outra execução: limpa eventual evento atrasado.
                        if (!empty($movAtiva->data_hora_saida_real)) {
                            $this->forgetEventoWebhook($movAtiva->id, 'saida_origem');
                        }

                        /*
                         * Limpa eventos que eventualmente ficaram pendentes depois
                         * de uma etapa já ter sido gravada por outra execução.
                         */
                        if (!$temDestinos && !empty($movAtiva->data_hora_chegada_cliente)) {
                            $this->forgetEventoWebhook(
                                $movAtiva->id,
                                'chegada_cliente'
                            );

                            $horarioChegadaClienteWebhook = null;
                            $eventoChegadaCliente = null;
                        }

                        if (!$temDestinos && !empty($movAtiva->data_hora_saida_cliente)) {
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
                            !$temDestinos
                            && $chegouDestino
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
                            !$temDestinos
                            && $saiuDestino
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
                                $horarioSaidaClienteEfetivo, false
                            );

                            if (
                                $minutosNoCliente >= 2
                                || ($minutosNoCliente >= 0 && $velocidadeSaidaKmH > 10)
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
                            && ($temDestinos
                                ? $this->todosDestinosVisitados($movAtiva)
                                : !empty($movAtiva->data_hora_saida_cliente))
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

                                $requestSummary = Http::withHeaders(['Accept' => 'application/json'])
                                    ->connectTimeout(5)->timeout(15);
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

                            if ($kmPercorrido <= 0 && $temDestinos) {
                                $kmPercorrido = $this->estimativaKmMultiplosDestinos(
                                    $movAtiva, $latOrigem, $lonOrigem, $latBase, $lonBase
                                );
                            }

                            if ($kmPercorrido <= 0 && !$temDestinos) {
                                $distIda = ($latOrigem && $lonOrigem && $movAtiva->latitude_destino && $movAtiva->longitude_destino)
                                    ? $this->calcularDistanciaMetros($latOrigem, $lonOrigem, $movAtiva->latitude_destino, $movAtiva->longitude_destino) : 0;
                                $distVolta = ($movAtiva->latitude_destino && $movAtiva->longitude_destino && $latBase && $lonBase)
                                    ? $this->calcularDistanciaMetros($movAtiva->latitude_destino, $movAtiva->longitude_destino, $latBase, $lonBase) : 0;
                                $kmPercorrido = (($distIda + $distVolta) / 1000) * 1.25;
                            }

                            $movAtiva->km_final = (float) $movAtiva->km_inicial + round(max(0, $kmPercorrido), 2);

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

                        if ($movAtiva->status === 'finalizado') {
                            if ($movAtiva->veiculo_id && $movAtiva->km_final > 0) {
                                $veiculoObj = Veiculo::find($movAtiva->veiculo_id);
                                if ($veiculoObj) {
                                    $veiculoObj->quilometragem = $movAtiva->km_final;
                                    $veiculoObj->save();
                                }
                            }

                            $this->verificarManutencoesPreventivas($movAtiva);
                            $this->registrarParadasTraccar(
                                $movAtiva, $baseUrl, $username, $password, $token, $idInternoTraccar
                            );
                        }
                    }

                    /*
                     * Confirma o consumo somente após a persistência da etapa.
                     * Chamadas de limpeza de etapas já gravadas também passam
                     * por este ponto, mantendo o processo idempotente.
                     */
                    $this->confirmarEventosWebhookProcessados(
                        $movAtiva->id
                    );
                } catch (Throwable $e) {
                    unset($this->eventosWebhookParaConsumir[$movAtiva->id]);
                    unset($this->eventosWebhookLidos[$movAtiva->id]);
                    Log::error('ROBÔ: falha isolada ao processar movimentação.', [
                        'empresa_id' => $empresaId,
                        'movimentacao_id' => $movAtiva->id,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }
        }
        return self::SUCCESS;
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
                $payload = $persistido->toMonitorPayload();
                $this->eventosWebhookLidos[$movimentacaoId][$etapa] = $payload;
                return $payload;
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

        $payload = is_array($evento) ? $evento : null;
        if ($payload !== null) {
            $this->eventosWebhookLidos[$movimentacaoId][$etapa] = $payload;
        }
        return $payload;
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

    private function forgetEventoWebhook(int $movimentacaoId, string $etapa): void
    {
        // Cache e inbox só são confirmados após persistência da movimentação.
        $payload = $this->eventosWebhookLidos[$movimentacaoId][$etapa] ?? null;
        $this->eventosWebhookParaConsumir[$movimentacaoId][$etapa] =
            isset($payload['persisted_event_id'])
                ? (int) $payload['persisted_event_id']
                : null;
    }

    private function confirmarEventosWebhookProcessados(int $movimentacaoId): void
    {
        $eventos = $this->eventosWebhookParaConsumir[$movimentacaoId] ?? [];
        if (!$eventos) return;

        $ids = array_values(array_filter(array_values($eventos), fn ($id) => $id !== null));
        try {
            if ($ids) {
                TraccarWebhookEvent::query()
                    ->where('movimentacao_id', $movimentacaoId)
                    ->whereIn('id', $ids)
                    ->where('status', TraccarWebhookEvent::STATUS_CLASSIFICADO)
                    ->update([
                        'status' => TraccarWebhookEvent::STATUS_CONSUMIDO,
                        'processed_at' => now(),
                        'locked_at' => null,
                        'next_retry_at' => null,
                        'last_error' => null,
                        'updated_at' => now(),
                    ]);
            }

            foreach (array_keys($eventos) as $etapa) {
                Cache::forget($this->getEventoWebhookCacheKey($movimentacaoId, $etapa));
                unset($this->eventosWebhookLidos[$movimentacaoId][$etapa]);
            }
            unset($this->eventosWebhookParaConsumir[$movimentacaoId]);
        } catch (Throwable $e) {
            Log::warning('ROBÔ: falha ao confirmar consumo de webhook.', [
                'movimentacao_id' => $movimentacaoId,
                'eventos' => $eventos,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Endereço público resolvido pela própria configuração da instalação.
     * No CLI/Artisan não há request HTTP para detectar o host público.
     * Nunca inventar domínio a partir de localhost ou hostname de container.
     */
    private function urlPublica(string $caminho): ?string
    {
        $base = trim((string) config('app.url', ''));
        $partes = parse_url($base);
        $scheme = strtolower((string) ($partes['scheme'] ?? ''));
        $host = strtolower((string) ($partes['host'] ?? ''));

        $ipPublico = filter_var($host, FILTER_VALIDATE_IP)
            ? (bool) filter_var($host, FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            : null;

        if (!in_array($scheme, ['http', 'https'], true)
            || $host === '' || $host === 'localhost'
            || (!str_contains($host, '.') && $ipPublico === null)
            || $ipPublico === false
            || str_ends_with($host, '.local') || str_ends_with($host, '.internal')
            || in_array($host, ['127.0.0.1', '0.0.0.0', '::1'], true)) {
            Log::warning('ROBÔ: APP_URL pública ausente ou inválida; link não enviado.', [
                'app_url' => $base,
            ]);
            return null;
        }

        return rtrim($base, '/') . '/' . ltrim($caminho, '/');
    }

    /** Não envia link de funcionalidade que ainda não possui rota web GET. */
    private function rotaPublicaGetExiste(string $uri): bool
    {
        foreach (Route::getRoutes() as $rota) {
            if (trim($rota->uri(), '/') === trim($uri, '/')
                && in_array('GET', $rota->methods(), true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Complemento opcional: viagens com vários destinos.
     * Requer relacionamento destinos() e colunas previstas no novo módulo.
     * Uma única parada é atualizada por rodada para evitar geofence oscilante.
     */
    private function processarDestinos(
        MovimentacaoVeiculo $mov,
        float $latitude,
        float $longitude,
        float $velocidadeKmH,
        Carbon $horario
    ): void {
        $pendentes = $mov->destinos->filter(
            fn ($d) => $d->status !== 'visitado'
        );
        if ($pendentes->isEmpty()) return;

        // Prioriza o destino no qual a viagem já registrou chegada.
        $destino = $pendentes->first(
            fn ($d) => !empty($d->data_hora_chegada) && empty($d->data_hora_saida)
        );

        if (!$destino) {
            // Se a ordem física mudou, permite chegada ao destino mais próximo.
            $candidatos = $pendentes->filter(function ($d) use ($latitude, $longitude) {
                if (!is_numeric($d->latitude) || !is_numeric($d->longitude)) return false;
                $dist = $this->calcularDistanciaMetros(
                    $latitude, $longitude, $d->latitude, $d->longitude
                );
                return $dist !== null && $dist <= 250;
            });
            $destino = $candidatos->sortBy(function ($d) use ($latitude, $longitude) {
                return $this->calcularDistanciaMetros(
                    $latitude, $longitude, $d->latitude, $d->longitude
                );
            })->first();
        }

        if (!$destino || !is_numeric($destino->latitude) || !is_numeric($destino->longitude)) {
            return;
        }

        $distancia = $this->calcularDistanciaMetros(
            $latitude, $longitude, $destino->latitude, $destino->longitude
        );

        if ($distancia !== null && $distancia <= 250 && empty($destino->data_hora_chegada)) {
            $destino->data_hora_chegada = $horario;
            $destino->save();
            return;
        }

        if ($distancia !== null && $distancia > 300
            && !empty($destino->data_hora_chegada) && empty($destino->data_hora_saida)) {
            $minutos = Carbon::parse($destino->data_hora_chegada)->diffInMinutes($horario, false);
            if ($minutos >= 2 || ($minutos >= 0 && $velocidadeKmH > 10)) {
                $destino->data_hora_saida = $horario;
                $destino->status = 'visitado';
                $destino->save();
            }
        }
    }

    private function todosDestinosVisitados(MovimentacaoVeiculo $mov): bool
    {
        return $mov->destinos->isNotEmpty()
            && $mov->destinos->every(
                fn ($destino) => $destino->status === 'visitado'
                    && !empty($destino->data_hora_saida)
            );
    }

    private function estimativaKmMultiplosDestinos(
        MovimentacaoVeiculo $mov,
                            $latOrigem, $lonOrigem, $latBase, $lonBase
    ): float {
        $anteriorLat = $latOrigem;
        $anteriorLon = $lonOrigem;
        $metros = 0;
        $trechos = 0;
        foreach ($mov->destinos->sortBy(fn ($d) => $d->ordem ?? $d->id) as $destino) {
            if (!is_numeric($destino->latitude) || !is_numeric($destino->longitude)) {
                continue;
            }
            if ($anteriorLat !== null && $anteriorLon !== null) {
                $metros += $this->calcularDistanciaMetros(
                    $anteriorLat, $anteriorLon, $destino->latitude, $destino->longitude
                ) ?? 0;
                $trechos++;
            }
            $anteriorLat = $destino->latitude;
            $anteriorLon = $destino->longitude;
        }
        if ($anteriorLat !== null && $anteriorLon !== null
            && $latBase !== null && $lonBase !== null) {
            $metros += $this->calcularDistanciaMetros(
                $anteriorLat, $anteriorLon, $latBase, $lonBase
            ) ?? 0;
            $trechos++;
        }
        // O fator 1,25 é a estimativa já utilizada pela regra legada.
        return $trechos > 0 ? ($metros / 1000) * 1.25 : 0;
    }

    /** Persistência idempotente de paradas, quando módulo complementar existe. */
    private function registrarParadasTraccar(
        MovimentacaoVeiculo $mov,
        string $baseUrl,
        ?string $username,
        ?string $password,
        ?string $token,
                            $deviceId
    ): void {
        $modelo = 'App\\Models\\MovimentacaoHistoricoParada';
        if (!class_exists($modelo)) {
            Log::info('ROBÔ: módulo de histórico de paradas não instalado; sem alteração no fluxo legado.', [
                'movimentacao_id' => $mov->id,
            ]);
            return;
        }
        if (empty($mov->data_hora_saida_real) || empty($mov->data_hora_chegada)) return;

        try {
            $from = Carbon::parse($mov->data_hora_saida_real)
                ->utc()->format('Y-m-d\TH:i:s\Z');
            $to = Carbon::parse($mov->data_hora_chegada)
                ->utc()->format('Y-m-d\TH:i:s\Z');
            $requisicao = Http::acceptJson()->connectTimeout(5)->timeout(15);
            $requisicao = $token
                ? $requisicao->withToken($token)
                : $requisicao->withBasicAuth($username, $password);
            $resposta = $requisicao->get(rtrim($baseUrl, '/') . '/api/reports/stops', [
                'deviceId' => $deviceId, 'from' => $from, 'to' => $to,
            ]);
            if ($resposta->failed()) {
                Log::warning('ROBÔ: Traccar não retornou histórico de paradas.', [
                    'movimentacao_id' => $mov->id,
                    'http_status' => $resposta->status(),
                ]);
                return;
            }
            foreach ((array) $resposta->json() as $parada) {
                if (empty($parada['startTime']) || empty($parada['endTime'])) continue;
                $duracao = (int) floor(((float) ($parada['duration'] ?? 0)) / 60000);
                if ($duracao < 5) continue;
                $fuso = config('app.timezone', 'America/Bahia');
                $inicio = Carbon::parse($parada['startTime'])->setTimezone($fuso);
                $fim = Carbon::parse($parada['endTime'])->setTimezone($fuso);
                $modelo::firstOrCreate(
                    [
                        'movimentacao_veiculo_id' => $mov->id,
                        'data_hora_inicio' => $inicio,
                        'data_hora_fim' => $fim,
                    ],
                    [
                        'duracao_minutos' => $duracao,
                        'endereco' => $parada['address'] ?? 'Endereço desconhecido',
                        'latitude' => $parada['latitude'] ?? null,
                        'longitude' => $parada['longitude'] ?? null,
                    ]
                );
            }
        } catch (Throwable $e) {
            Log::warning('ROBÔ: não foi possível persistir paradas Traccar.', [
                'movimentacao_id' => $mov->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Usa as tabelas de preventivas já previstas nas migrations da 5.0.202.
     * Não exige models extras nem duplica alertas abertos.
     */
    private function verificarManutencoesPreventivas(MovimentacaoVeiculo $mov): void
    {
        if (!$mov->veiculo_id || !$mov->km_final) return;
        try {
            if (!Schema::hasTable('veiculo_planos_preventivos')
                || !Schema::hasTable('veiculo_alertas_manutencao')) {
                Log::info('ROBÔ: tabelas de manutenção preventiva ausentes; recurso ignorado.', [
                    'movimentacao_id' => $mov->id,
                ]);
                return;
            }

            $planos = DB::table('veiculo_planos_preventivos')
                ->where('empresa_id', $mov->empresa_id)
                ->where('veiculo_id', $mov->veiculo_id)
                ->where('ativo', 1)->get();

            foreach ($planos as $plano) {
                $kmRestante = (float) $plano->intervalo_km
                    - ((float) $mov->km_final - (float) $plano->ultimo_km_executado);
                if ($kmRestante > 500) continue;
                $jaExiste = DB::table('veiculo_alertas_manutencao')
                    ->where('empresa_id', $mov->empresa_id)
                    ->where('veiculo_id', $mov->veiculo_id)
                    ->where('tipo', 'preventiva')
                    ->where('titulo', $plano->servico_nome)
                    ->where('fase', '!=', 'resolvido')->exists();
                if ($jaExiste) continue;

                DB::table('veiculo_alertas_manutencao')->insert([
                    'empresa_id' => $mov->empresa_id,
                    'filial_id' => $mov->filial_id,
                    'veiculo_id' => $mov->veiculo_id,
                    'tipo' => 'preventiva',
                    'titulo' => $plano->servico_nome,
                    'descricao' => "Veículo atingiu {$mov->km_final} KM. Próxima execução: " .
                        ((float) $plano->ultimo_km_executado + (float) $plano->intervalo_km) . ' KM.',
                    'prioridade' => $kmRestante <= 0 ? 'Urgente' : 'Alta',
                    'fase' => 'identificado',
                    'impedir_circulacao' => $kmRestante <= -500 ? 1 : 0,
                    'intervalo_km' => $plano->intervalo_km,
                    'ultimo_km_executado' => $plano->ultimo_km_executado,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('ROBÔ: falha ao verificar manutenções preventivas.', [
                'empresa_id' => $mov->empresa_id,
                'veiculo_id' => $mov->veiculo_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Geocodificação opcional com cache; sempre retorna coordenadas se falhar. */
    private function obterEnderecoPorCoordenadas($lat, $lon): string
    {
        $fallback = "Lat: {$lat}, Lon: {$lon}";
        $email = (string) config('mail.from.address');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) return $fallback;

        /*
         * Identificação dinâmica da instalação para o Nominatim.
         * Nome: APP_NAME, via config('app.name') (compatível com config:cache).
         * Versão: mesmo AppVersionService utilizado pelo frontend.
         * Não fixar marca nem versão no código-fonte.
         */
        $nomeAplicacao = trim((string) config('app.name', ''));
        $identificador = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', Str::ascii($nomeAplicacao)), '-._');
        if ($identificador === '') {
            Log::warning('ROBÔ: APP_NAME não configurado para identificar requisição ao Nominatim.');
            return $fallback;
        }

        try {
            $versaoAplicacao = trim((string) app(AppVersionService::class)->resolveInstalledVersion());
        } catch (Throwable $e) {
            $versaoAplicacao = '';
            Log::warning('ROBÔ: não foi possível obter a versão instalada da aplicação.', [
                'error' => $e->getMessage(),
            ]);
        }

        $agenteAplicacao = $identificador;
        if ($versaoAplicacao !== '' && $versaoAplicacao !== '0.0.0') {
            $agenteAplicacao .= '/' . $versaoAplicacao;
        }
        $agenteAplicacao .= ' (' . $email . ')';
        $chave = 'frota:reverse:' . md5(round((float) $lat, 5) . ',' . round((float) $lon, 5));
        if (Cache::has($chave)) return (string) Cache::get($chave);

        // Um acesso global no máximo a cada 2 segundos ao serviço público.
        if (!Cache::add('frota:nominatim:throttle', true, 2)) return $fallback;

        try {
            $resposta = Http::withHeaders([
                'User-Agent' => $agenteAplicacao,
            ])->connectTimeout(2)->timeout(3)->get(
                'https://nominatim.openstreetmap.org/reverse',
                ['format' => 'jsonv2', 'lat' => $lat, 'lon' => $lon, 'addressdetails' => 1]
            );
            if ($resposta->successful()) {
                $dados = $resposta->json('address', []);
                $partes = array_filter([
                    $dados['road'] ?? $dados['pedestrian'] ?? $dados['street'] ?? '',
                    $dados['suburb'] ?? $dados['neighbourhood'] ?? '',
                    $dados['city'] ?? $dados['town'] ?? $dados['municipality'] ?? '',
                ]);
                if ($partes) {
                    $endereco = implode(', ', $partes);
                    Cache::put($chave, $endereco, now()->addDays(30));
                    return $endereco;
                }
            }
        } catch (Throwable $e) {
            Log::warning('ROBÔ: geocodificação reversa indisponível.', [
                'error' => $e->getMessage(),
            ]);
        }
        return $fallback;
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

            $dadosEmpresa = $this->getEmpresaConfigDoRobo($movimentacao->empresa_id, $movimentacao->filial_id);
            $placa = $movimentacao->veiculo->placa ?? 'Veículo';
            $enderecoLocal = $this->obterEnderecoPorCoordenadas($lat, $lon);

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
            $comprovante .= "🏠 *Localização:* {$enderecoLocal}\n";
            $comprovante .= "🔗 *Mapa:* https://maps.google.com/?q={$lat},{$lon}\n";
            $comprovante .= "----------------------------------------\n";
            $comprovante .= "🔑 *Autenticação:* {$hash}\n";
            $comprovante .= "----------------------------------------\n";
            $comprovante .= "_Comprovante emitido nos termos da Portaria 671/2021 MTE_";

            $resultado = app(\App\Utils\WhatsAppUtil::class)->sendMessage(
                $telefoneLimpo,
                $comprovante,
                (int) $movimentacao->empresa_id
            );
            $this->registrarRetornoWhatsapp($resultado, (int) $movimentacao->empresa_id, 'ponto');
        } catch (\Exception $e) {
            Log::error("ROBÔ Ponto: " . $e->getMessage());
        }
    }

    private function enviarRespostaWhatsApp($empresaId, $telefone, $mensagem)
    {
        try {
            if (empty($telefone)) {
                return;
            }

            $resultado = app(\App\Utils\WhatsAppUtil::class)->sendMessage(
                (string) $telefone,
                (string) $mensagem,
                (int) $empresaId
            );
            $this->registrarRetornoWhatsapp($resultado, (int) $empresaId, 'frota');
        } catch (\Throwable $e) {
            Log::error("ROBÔ WhatsApp via Connect|API: " . $e->getMessage());
        }
    }

    private function registrarRetornoWhatsapp($resultado, int $empresaId, string $tipo): void
    {
        // WhatsAppUtil desta release devolve string JSON mesmo quando falha.
        if (!is_string($resultado)) return;
        $dados = json_decode($resultado, true);
        if (is_array($dados) && ($dados['success'] ?? null) === false) {
            Log::warning('ROBÔ: WhatsAppUtil retornou falha.', [
                'empresa_id' => $empresaId,
                'tipo' => $tipo,
                'message' => $dados['message'] ?? 'Falha não especificada',
            ]);
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
            'razao_social' => trim((string) config('app.name', '')) ?: 'Nome da aplicação não configurado',
            'cnpj'          => 'Não informado',
            'endereco'      => 'Endereço não cadastrado'
        ];
    }

    private function calcularDistanciaMetros($lat1, $lon1, $lat2, $lon2)
    {
        foreach ([$lat1, $lon1, $lat2, $lon2] as $valor) {
            if ($valor === null || !is_numeric($valor)) return null;
        }
        if (abs((float) $lat1) > 90 || abs((float) $lat2) > 90
            || abs((float) $lon1) > 180 || abs((float) $lon2) > 180) return null;
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
