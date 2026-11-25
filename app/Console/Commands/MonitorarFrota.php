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
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;
use Carbon\Carbon;
use Exception;

class MonitorarFrota extends Command
{
    private array $eventosWebhookParaConsumir = [];
    private array $eventosWebhookLidos = [];

    protected $signature = 'frota:monitorar';
    protected $description = 'Monitora frota Traccar multiempresa, envia previsões ao cliente, alertas de trânsito e desvios.';

    public function handle()
    {
        $empresasIds = MovimentacaoVeiculo::whereIn('status', ['agendado', 'iniciado'])
            ->pluck('empresa_id')
            ->unique();

        if ($empresasIds->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($empresasIds as $empresaId) {

            // =========================================================================
            // 🚀 GATILHO PARA TERCEIROS (SEM RASTREAMENTO TRACCAR)
            // =========================================================================
            $terceirosPendentes = MovimentacaoVeiculo::with('destinos')
                ->where('empresa_id', $empresaId)
                ->where('is_terceirizado', 1)
                ->whereIn('status', ['agendado', 'iniciado'])
                ->whereNotNull('data_hora_saida')
                ->whereNull('alerta_atraso_enviado_at')
                ->get();

            foreach ($terceirosPendentes as $movTerceiro) {
                $horarioSaida = Carbon::parse($movTerceiro->data_hora_saida);

                if (Carbon::now()->greaterThanOrEqualTo((clone $horarioSaida)->addMinutes(30))) {
                    if ($movTerceiro->gerar_mtr == 1 && !empty($movTerceiro->terceiro_telefone)) {
                        $destino = $movTerceiro->destinos->first();

                        if ($destino) {
                            $linkColeta = $this->urlPublica("coleta-motorista/{$movTerceiro->id}/destino/{$destino->id}");

                            $msg = "♻️ *REGISTRO DE COLETA E MTR (TERCEIRIZADO)*\n\n";
                            $msg .= "Olá, *{$movTerceiro->terceiro_nome}*! Sua rota está em andamento.\n";
                            $msg .= "Por favor, informe o material coletado para emissão do MTR clicando no link abaixo:\n\n";
                            $msg .= "👉 {$linkColeta}";

                            $this->enviarRespostaWhatsApp($movTerceiro->empresa_id, $movTerceiro->terceiro_telefone, $msg);
                        }
                    }

                    $movTerceiro->alerta_atraso_enviado_at = now();
                    $movTerceiro->status = 'iniciado';
                    $movTerceiro->save();
                }
            }

            // Busca exclusivamente a configuração Traccar daquela empresa
            $config = DB::table('traccar_configs')->where('empresa_id', $empresaId)->orderBy('id', 'desc')->first();

            if (!$config || empty($config->base_url)) {
                Log::warning('ROBÔ: Traccar não configurado para esta empresa.', ['empresa_id' => $empresaId]);
                continue;
            }

            $baseUrl = rtrim($config->base_url, '/');
            $username = $config->mail_user_name;
            $token = $config->token_traccar;
            $password = $config->password;

            if (!empty($password)) {
                try {
                    $password = Crypt::decryptString($password);
                } catch (Throwable $e) {
                    $password = $config->password;
                }
            }

            if (empty($baseUrl) || (empty($token) && (empty($username) || empty($password)))) {
                continue;
            }

            $relacoes = ['veiculo', 'motorista', 'ajudantes'];
            if (method_exists(MovimentacaoVeiculo::class, 'destinos')) {
                $relacoes[] = 'destinos.cliente';
                $relacoes[] = 'destinos.fornecedor';
            }

            $movimentacoes = MovimentacaoVeiculo::with($relacoes)
                ->where('empresa_id', $empresaId)
                ->whereIn('status', ['agendado', 'iniciado'])
                ->where(function ($q) {
                    $q->where('status', 'iniciado')
                        ->orWhereBetween('data_hora_saida', [
                            Carbon::now()->subHours(48)->format('Y-m-d H:i:s'),
                            Carbon::now()->addHours(12)->format('Y-m-d H:i:s')
                        ]);
                })
                ->orderBy('data_hora_saida', 'asc')
                ->get();

            if ($movimentacoes->isEmpty()) continue;

            try {
                $urlDevices = "{$baseUrl}/api/devices";
                $requestDevices = Http::withHeaders(['Accept' => 'application/json'])->connectTimeout(5)->timeout(15);
                $requestDevices = !empty($token) ? $requestDevices->withToken($token) : $requestDevices->withBasicAuth($username, $password);
                $respDevices = $requestDevices->get($urlDevices);
                if ($respDevices->failed()) continue;
                $listaDispositivos = collect($respDevices->json());

                $urlPositions = "{$baseUrl}/api/positions";
                $requestPositions = Http::withHeaders(['Accept' => 'application/json'])->connectTimeout(5)->timeout(15);
                $requestPositions = !empty($token) ? $requestPositions->withToken($token) : $requestPositions->withBasicAuth($username, $password);
                $respPositions = $requestPositions->get($urlPositions);
                if ($respPositions->failed()) continue;
                $posicoes = collect($respPositions->json());
            } catch (Throwable $e) { continue; }

            $movsAgrupadas = $movimentacoes->groupBy(function ($item) {
                return ($item->veiculo_id ?: 'sem_veic') . '_' . ($item->motorista_id ?: 'sem_mot');
            });

            foreach ($movsAgrupadas as $chaveGrupo => $movsDoGrupo) {
                $hojeStr = Carbon::now()->format('Y-m-d');
                $movAtiva = $movsDoGrupo->first(fn($m) => $m->status === 'iniciado' && str_starts_with((string)$m->data_hora_saida, $hojeStr))
                    ?? $movsDoGrupo->first(fn($m) => $m->status === 'agendado' && str_starts_with((string)$m->data_hora_saida, $hojeStr))
                    ?? $movsDoGrupo->where('status', 'iniciado')->sortByDesc('data_hora_saida')->first()
                    ?? $movsDoGrupo->where('status', 'agendado')->sortBy('data_hora_saida')->first();

                if (!$movAtiva) continue;

                try {
                    // AVISO PRÉVIO: Envia a Ordem de Coleta com GPS 30 min antes da saída
                    if ($movAtiva->status === 'agendado' && !empty($movAtiva->data_hora_saida)) {
                        $horarioPrevistoCheck = Carbon::parse($movAtiva->data_hora_saida);
                        if (Carbon::now()->lessThanOrEqualTo($horarioPrevistoCheck) && Carbon::now()->diffInMinutes($horarioPrevistoCheck) <= 30) {
                            $this->dispararAvisoPrevioMotorista($movAtiva, $horarioPrevistoCheck);
                        }
                    }

                    $imeiDispositivo = null;
                    if ($movAtiva->motorista_id) {
                        $imeiDispositivo = $movAtiva->motorista->traccar_id ?? Funcionario::find($movAtiva->motorista_id)?->traccar_id;
                    }
                    if (!$imeiDispositivo && $movAtiva->veiculo) {
                        $imeiDispositivo = $movAtiva->veiculo->traccar_id ?? null;
                    }

                    if ($imeiDispositivo && !empty($movAtiva->data_hora_saida)) {
                        $inicioJanela = Carbon::parse($movAtiva->data_hora_saida)->subHours(2);
                        if (Carbon::now()->greaterThanOrEqualTo($inicioJanela)) {
                            try {
                                app(\App\Http\Controllers\MovimentacaoVeiculoController::class)
                                    ->sincronizarRetroativoTraccar($movAtiva->id);
                                $movAtiva->refresh();
                            } catch (Throwable $eSync) {}
                        }
                    }

                    if ($movAtiva->status === 'finalizado') continue;

                    if ($imeiDispositivo) {
                        $traccarDevice = $listaDispositivos->first(function ($device) use ($imeiDispositivo) {
                            return (isset($device['uniqueId']) && (string) $device['uniqueId'] === (string) $imeiDispositivo)
                                || (isset($device['id']) && (string) $device['id'] === (string) $imeiDispositivo);
                        });

                        if ($traccarDevice) {
                            $idInternoTraccar = $traccarDevice['id'];
                            $pos = $posicoes->firstWhere('deviceId', $idInternoTraccar);

                            if ($pos && isset($pos['latitude'], $pos['longitude']) && is_numeric($pos['latitude']) && is_numeric($pos['longitude'])) {

                                $latAtual = (float) $pos['latitude'];
                                $lonAtual = (float) $pos['longitude'];
                                $velocidadeKmH = ($pos['speed'] ?? 0) * 1.852;

                                $filialObj = $movAtiva->filial_id ? DB::table('filials')->where('id', $movAtiva->filial_id)->first() : null;
                                $configNotasObj = DB::table('config_notas')->where('empresa_id', $movAtiva->empresa_id)->first();
                                $latBase = $filialObj->latitude ?? $configNotasObj->latitude ?? null;
                                $lonBase = $filialObj->longitude ?? $configNotasObj->longitude ?? null;

                                $latOrigem = $latBase;
                                $lonOrigem = $lonBase;

                                if (isset($movAtiva->tipo_partida) && $movAtiva->tipo_partida == 'residencia' && $movAtiva->motorista) {
                                    $latOrigem = $movAtiva->motorista->latitude_residencia ?? $latBase;
                                    $lonOrigem = $movAtiva->motorista->longitude_residencia ?? $lonBase;
                                    if ($latBase === null || $lonBase === null) { $latBase = $latOrigem; $lonBase = $lonOrigem; }
                                }

                                $distanciaOrigem = ($latOrigem !== null && $lonOrigem !== null) ? $this->calcularDistanciaMetros($latAtual, $lonAtual, $latOrigem, $lonOrigem) : null;
                                $distanciaDestino = ($movAtiva->latitude_destino !== null && $movAtiva->longitude_destino !== null) ? $this->calcularDistanciaMetros($latAtual, $lonAtual, $movAtiva->latitude_destino, $movAtiva->longitude_destino) : null;
                                $distanciaBase = ($latBase !== null && $lonBase !== null) ? $this->calcularDistanciaMetros($latAtual, $lonAtual, $latBase, $lonBase) : null;

                                $atualizou = false;
                                $horario = now();

                                $eventoSaidaOrigem = $this->getEventoWebhook($movAtiva->id, 'saida_origem');
                                $eventoChegadaCliente = $this->getEventoWebhook($movAtiva->id, 'chegada_cliente');
                                $eventoSaidaCliente = $this->getEventoWebhook($movAtiva->id, 'saida_cliente');
                                $eventoChegadaBase = $this->getEventoWebhook($movAtiva->id, 'chegada_base');

                                $horarioSaidaOrigemWebhook = $this->getHorarioEventoWebhook($eventoSaidaOrigem);
                                $horarioChegadaClienteWebhook = $this->getHorarioEventoWebhook($eventoChegadaCliente);
                                $horarioSaidaClienteWebhook = $this->getHorarioEventoWebhook($eventoSaidaCliente);
                                $horarioChegadaBaseWebhook = $this->getHorarioEventoWebhook($eventoChegadaBase);

                                // 1. LÓGICA DE SAÍDA DA AGENDA (AGENDADO) E AVISO AO CLIENTE
                                if ($movAtiva->status == 'agendado') {
                                    $momentoReferenciaSaida = $horarioSaidaOrigemWebhook ?: Carbon::now();
                                    $pertoDeAlgumDestino = ($distanciaDestino !== null && $distanciaDestino <= 250);
                                    if (!$pertoDeAlgumDestino && $movAtiva->destinos && $movAtiva->destinos->isNotEmpty()) {
                                        foreach ($movAtiva->destinos as $dCheck) {
                                            $ld = $dCheck->latitude ?? $dCheck->cliente->latitude ?? $dCheck->fornecedor->latitude ?? null;
                                            $lnd = $dCheck->longitude ?? $dCheck->cliente->longitude ?? $dCheck->fornecedor->longitude ?? null;
                                            $distCheck = $this->calcularDistanciaMetros($latAtual, $lonAtual, $ld, $lnd);
                                            if ($distCheck !== null && $distCheck <= 250) { $pertoDeAlgumDestino = true; break; }
                                        }
                                    }

                                    $saiuDoLugar = ($horarioSaidaOrigemWebhook !== null)
                                        || ($distanciaOrigem !== null && $distanciaOrigem > 250)
                                        || ($velocidadeKmH >= 5 && $distanciaOrigem !== null && $distanciaOrigem > 150)
                                        || $pertoDeAlgumDestino;

                                    if ($momentoReferenciaSaida->betweenIncluded(Carbon::parse($movAtiva->data_hora_saida)->subHours(2), Carbon::parse($movAtiva->data_hora_saida)->addHours(36)) && $saiuDoLugar) {

                                        $this->dispararGatilhosInicioViagem($movAtiva, $latAtual, $lonAtual, !empty($movAtiva->data_hora_saida_real));

                                        $movAtiva->data_hora_saida_real = $horarioSaidaOrigemWebhook ?: $horario;
                                        $movAtiva->status = 'iniciado';
                                        $atualizou = true;
                                        $movAtiva->save();

                                        if ($horarioSaidaOrigemWebhook !== null) $this->forgetEventoWebhook($movAtiva->id, 'saida_origem');

                                        // 🚀 GATILHO ETA: VEÍCULO SAIU, AVISA O CLIENTE/FORNECEDOR MATEMATICAMENTE
                                        $this->enviarAvisoPartidaCliente($movAtiva, $latAtual, $lonAtual);
                                    }
                                }

                                // 2. LÓGICA DE VIAGEM EM PERCURSO (MONITORAMENTO DE PARADAS)
                                if ($movAtiva->status == 'iniciado') {

                                    // 🚀 GATILHO TRÂNSITO: Verifica se parou indevidamente
                                    $this->monitorarParadasIndevidasEDesvios($movAtiva, $latAtual, $lonAtual, $velocidadeKmH, $distanciaDestino, $distanciaBase);

                                    $cacheKeyParadas = "frota:sync_stops:{$movAtiva->id}";
                                    if (!Cache::has($cacheKeyParadas)) {
                                        $this->registrarParadasTraccar($movAtiva, $baseUrl, $username, $password, $token, $idInternoTraccar);
                                        Cache::put($cacheKeyParadas, true, now()->addMinutes(10));
                                    }

                                    $temDestinos = method_exists(MovimentacaoVeiculo::class, 'destinos') && $movAtiva->destinos && $movAtiva->destinos->isNotEmpty();
                                    if ($temDestinos) $this->processarDestinos($movAtiva, $latAtual, $lonAtual, $velocidadeKmH, $horario);

                                    $chegouDestino = ($horarioChegadaClienteWebhook !== null) || ($distanciaDestino !== null && $distanciaDestino <= 250);
                                    $saiuDestino = ($horarioSaidaClienteWebhook !== null) || ($distanciaDestino !== null && $distanciaDestino > 300);
                                    $chegouBase = ($horarioChegadaBaseWebhook !== null) || ($distanciaBase !== null && $distanciaBase <= 300);

                                    if (!$temDestinos && $chegouDestino && empty($movAtiva->data_hora_chegada_cliente)) {
                                        $movAtiva->data_hora_chegada_cliente = $horarioChegadaClienteWebhook ?: $horario;
                                        $atualizou = true;
                                        if ($horarioChegadaClienteWebhook !== null) $this->forgetEventoWebhook($movAtiva->id, 'chegada_cliente');
                                    } elseif (!$temDestinos && $saiuDestino && !empty($movAtiva->data_hora_chegada_cliente) && empty($movAtiva->data_hora_saida_cliente)) {
                                        $horarioSaidaClienteEfetivo = $horarioSaidaClienteWebhook ?: $horario;
                                        $velocidadeSaidaKmH = $this->getVelocidadeEventoWebhookKmH($eventoSaidaCliente) ?? $velocidadeKmH;
                                        $minutosNoCliente = Carbon::parse($movAtiva->data_hora_chegada_cliente)->diffInMinutes($horarioSaidaClienteEfetivo, false);
                                        if ($minutosNoCliente >= 2 || ($minutosNoCliente >= 0 && $velocidadeSaidaKmH > 10)) {
                                            $movAtiva->data_hora_saida_cliente = $horarioSaidaClienteEfetivo;
                                            $atualizou = true;
                                            if ($horarioSaidaClienteWebhook !== null) $this->forgetEventoWebhook($movAtiva->id, 'saida_cliente');
                                        }
                                    } elseif ($chegouBase && empty($movAtiva->data_hora_chegada)) {
                                        $todosVisitados = $temDestinos ? $this->todosDestinosVisitados($movAtiva) : !empty($movAtiva->data_hora_saida_cliente);
                                        $horaUltimaSaida = $temDestinos && $movAtiva->destinos->count() > 0 ? Carbon::parse($movAtiva->destinos->max('data_hora_saida')) : ($movAtiva->data_hora_saida_cliente ? Carbon::parse($movAtiva->data_hora_saida_cliente) : null);
                                        $horarioAtual = $horarioChegadaBaseWebhook ?: $horario;

                                        if ($todosVisitados && $horaUltimaSaida && $horarioAtual->greaterThan($horaUltimaSaida)) {
                                            $movAtiva->data_hora_chegada = $horarioAtual;
                                            $movAtiva->status = 'finalizado';
                                            $movAtiva->km_final = (float) $movAtiva->km_inicial + 1;
                                            $atualizou = true;
                                            if ($horarioChegadaBaseWebhook !== null) $this->forgetEventoWebhook($movAtiva->id, 'chegada_base');
                                        }
                                    }
                                }

                                if ($atualizou) {
                                    $movAtiva->save();
                                    if ($movAtiva->status === 'finalizado') {
                                        if ($movAtiva->veiculo_id && $movAtiva->km_final > 0) {
                                            Veiculo::where('id', $movAtiva->veiculo_id)->update(['quilometragem' => $movAtiva->km_final]);
                                        }
                                        $movAtiva->gerarPrePesagemAutomatica();
                                        $this->verificarManutencoesPreventivas($movAtiva);
                                    }
                                }
                                $this->confirmarEventosWebhookProcessados($movAtiva->id);
                            }
                        }
                    }

                    // Atraso de saída
                    $movAtiva->refresh();
                    if ($movAtiva->status === 'agendado' && !empty($movAtiva->data_hora_saida)) {
                        $horarioPrevistoCheck = Carbon::parse($movAtiva->data_hora_saida);
                        $param = DB::table('parametros_agendamento_logistica')->where('empresa_id', $empresaId)->first();
                        $tolerancia = $param ? (int) ($param->minutos_tolerancia_atraso ?? 20) : 15;
                        if (Carbon::now()->greaterThanOrEqualTo((clone $horarioPrevistoCheck)->addMinutes($tolerancia))) {
                            $this->dispararAlertaAtrasoSaida($movAtiva, $horarioPrevistoCheck);
                        }
                    }

                } catch (Throwable $e) { continue; }
            }
        }
        return self::SUCCESS;
    }

    // =========================================================================
    // 🚀 MÉTODOS DE INTELIGÊNCIA DE ROTAS (AVISOS AO CLIENTE E GESTOR)
    // =========================================================================

    private function enviarAvisoPartidaCliente(MovimentacaoVeiculo $mov, float $latAtual, float $lonAtual): void
    {
        try {
            $destinoObj = method_exists($mov, 'destinos') && $mov->destinos->isNotEmpty() ? $mov->destinos->sortBy('ordem')->first() : $mov;

            $clienteEntidade = $destinoObj->cliente ?? $mov->cliente;
            $fornecedorEntidade = $destinoObj->fornecedor ?? $mov->fornecedor;

            $telefoneCliente = $clienteEntidade->celular ?? $clienteEntidade->telefone ?? $fornecedorEntidade->celular ?? $fornecedorEntidade->telefone ?? null;
            $nomeParceiro = $clienteEntidade->razao_social ?? $clienteEntidade->nome ?? $fornecedorEntidade->razao_social ?? $fornecedorEntidade->nome ?? null;

            if (empty($telefoneCliente)) return;

            $latDest = $destinoObj->latitude ?? $clienteEntidade->latitude ?? $fornecedorEntidade->latitude ?? null;
            $lonDest = $destinoObj->longitude ?? $clienteEntidade->longitude ?? $fornecedorEntidade->longitude ?? null;

            $mensagemEta = "";

            if ($latDest && $lonDest) {
                // CHAMA O GOOGLE MAPS AQUI!
                $google = $this->calcularDistanciaTempoGoogle($latAtual, $lonAtual, $latDest, $lonDest);

                if ($google) {
                    $horaPrevisao = now()->addMinutes($google['tempo_minutos'])->format('H:i');
                    $mensagemEta = "A distância é de " . number_format($google['distancia_km'], 1, ',', '.') . " km. \n⏱️ *Previsão de Chegada:* {$google['texto_tempo']} (aprox. às {$horaPrevisao}).";
                }
            }

            $placa = $mov->veiculo->placa ?? 'Veículo da frota';

            $msg = "🚚 *Aviso de Rota - Transporte a Caminho*\n\n";
            $msg .= "Olá, *{$nomeParceiro}*!\n";
            $msg .= "Informamos que o veículo *{$placa}* acabou de iniciar o deslocamento e está a caminho do seu endereço.\n\n";
            if ($mensagemEta) {
                $msg .= "{$mensagemEta}\n\n";
            }
            $msg .= "Prepare o local para a operação. Até já!";

            $this->enviarRespostaWhatsApp($mov->empresa_id, $telefoneCliente, $msg);

        } catch (\Throwable $e) {}
    }

    private function monitorarParadasIndevidasEDesvios(MovimentacaoVeiculo $mov, float $latAtual, float $lonAtual, float $velocidadeKmH, ?float $distDestino, ?float $distBase): void
    {
        try {
            $cacheKey = "frota:parada_tempo:{$mov->id}";
            $alertaEnviadoKey = "frota:alerta_parada_enviado:{$mov->id}";

            if ($velocidadeKmH < 3 && ($distDestino === null || $distDestino > 300) && ($distBase === null || $distBase > 300)) {

                $tempoParadoDesde = Cache::get($cacheKey);

                if (!$tempoParadoDesde) {
                    Cache::put($cacheKey, now(), now()->addHours(8));
                } else {
                    $minutosParado = now()->diffInMinutes(Carbon::parse($tempoParadoDesde));

                    if ($minutosParado >= 20 && !Cache::has($alertaEnviadoKey)) {
                        $this->alertarGestorDesvioOuTransito($mov, $latAtual, $lonAtual, $minutosParado);
                        $this->alertarClienteAtrasoTransito($mov);
                        Cache::put($alertaEnviadoKey, true, now()->addHours(2));
                    }
                }
            } else {
                Cache::forget($cacheKey);
                Cache::forget($alertaEnviadoKey);
            }

        } catch (\Throwable $e) {}
    }

    private function alertarGestorDesvioOuTransito(MovimentacaoVeiculo $mov, float $lat, float $lon, int $minutosParado): void
    {
        $responsaveis = Funcionario::where('empresa_id', $mov->empresa_id)->where('recebe_alerta_frota', 1)->get();
        if ($responsaveis->isEmpty()) return;

        $placa = $mov->veiculo->placa ?? 'N/A';
        $motorista = $mov->motorista->nome ?? 'N/A';
        $linkMaps = "https://www.google.com/maps/search/?api=1&query={$lat},{$lon}";

        $msg = "⚠️ *ALERTA DE PARADA/TRÂNSITO INTENSO*\n\n";
        $msg .= "O veículo *{$placa}* conduzido por *{$motorista}* encontra-se parado fora do cliente e da base há mais de *{$minutosParado} minutos*.\n\n";
        $msg .= "📍 *Localização Atual:* {$linkMaps}\n\n";
        $msg .= "_Verifique a telemetria no sistema para apurar se trata de trânsito, avaria ou desvio._";

        foreach ($responsaveis as $resp) {
            $tel = $resp->celular ?: $resp->telefone;
            $this->enviarRespostaWhatsApp($mov->empresa_id, $tel, $msg);
        }
    }

    private function alertarClienteAtrasoTransito(MovimentacaoVeiculo $mov): void
    {
        $destinoObj = method_exists($mov, 'destinos') && $mov->destinos->isNotEmpty() ? $mov->destinos->sortBy('ordem')->first() : $mov;
        $entidade = $destinoObj->cliente ?? $mov->cliente ?? $destinoObj->fornecedor ?? $mov->fornecedor;

        if (!$entidade) return;
        $telefone = $entidade->celular ?: $entidade->telefone;
        if (empty($telefone)) return;

        $msg = "🚦 *Aviso de Rota*\n\n";
        $msg .= "Olá! O veículo que está a caminho do seu endereço encontrou trânsito intenso ou uma retenção inesperada no trajeto.\n\n";
        $msg .= "Poderá ocorrer um ligeiro atraso na previsão de chegada informada. Agradecemos a compreensão!";

        $this->enviarRespostaWhatsApp($mov->empresa_id, $telefone, $msg);
    }

    // =========================================================================
    // RESTANTE DOS GATILHOS (MANTIDOS INTACTOS)
    // =========================================================================

    private function dispararGatilhosInicioViagem(MovimentacaoVeiculo $movAtiva, float $latAtual, float $lonAtual, bool $jaDisparado): void
    {
        if ($jaDisparado) return;

        if ($movAtiva->motorista && $movAtiva->bater_ponto_whatsapp == 1) {
            $func = $movAtiva->motorista;
            $pontoExiste = PontoMarcacao::where('funcionario_id', $func->id)->whereDate('data_hora_marcacao', Carbon::now()->format('Y-m-d'))->where('tipo_marcacao', 'entrada')->exists();

            if (!$pontoExiste) {
                $agora = Carbon::now();
                $hashComprovante = strtoupper(substr(hash('sha256', $func->id . $agora->timestamp . 'entrada'), 0, 16));

                PontoMarcacao::create([
                    'empresa_id'              => $movAtiva->empresa_id,
                    'funcionario_id'          => $func->id,
                    'data_hora_marcacao'      => $agora,
                    'origem'                  => 'automatico_traccar',
                    'tipo_marcacao'           => 'entrada',
                    'status'                  => 'processada',
                    'latitude'                => $latAtual,
                    'longitude'               => $lonAtual,
                    'movimentacao_veiculo_id' => $movAtiva->id,
                    'observacoes'             => "WhatsApp: {$func->celular} | 🚀 Batida automática ({$movAtiva->tipo_partida})"
                ]);

                $this->enviarComprovantePontoAutomaticoWhatsApp($movAtiva, $func, $latAtual, $lonAtual, $hashComprovante);
            }
        }

        if ($movAtiva->ajudantes && $movAtiva->ajudantes->count() > 0 && $movAtiva->motorista) {
            $telefoneMotorista = $movAtiva->motorista->celular ?? $movAtiva->motorista->telefone;
            $listaAjudantes = $movAtiva->ajudantes->pluck('nome')->implode(', ');
            $msgAjudantes = "👥 *REGISTRO DE AJUDANTES DA SUA VIAGEM*\n\nA rota foi iniciada!\nAjudantes escalados: *{$listaAjudantes}*.\n\nPara registrar a ENTRADA da equipe, peça para cada um enviar o seu *PIN de 4 dígitos* respondendo a esta mensagem.";

            $this->enviarRespostaWhatsApp($movAtiva->empresa_id, $telefoneMotorista, $msgAjudantes);
        }

        if ($movAtiva->motorista && ($movAtiva->checklist_obrigatorio ?? 1) == 1) {
            $telefoneMotorista = $movAtiva->motorista->celular ?? $movAtiva->motorista->telefone;
            $linkChecklist = $this->urlPublica('checklist/veiculo/' . $movAtiva->id);
            if ($linkChecklist !== null) {
                $msgChecklist = "📋 *CHECKLIST DE PRÉ-VIAGEM OBRIGATÓRIO*\n\nOlá, *{$movAtiva->motorista->nome}*! O veículo iniciou o percurso.\nPor favor, preencha o checklist veicular e envie as fotos pelo link abaixo:\n\n👉 {$linkChecklist}";
                $this->enviarRespostaWhatsApp($movAtiva->empresa_id, $telefoneMotorista, $msgChecklist);
            }
        }

        if ($movAtiva->motorista && (int) ($movAtiva->solicitar_abastecimento ?? 0) === 1) {
            $link = $this->urlPublica('abastecimento-motorista/' . $movAtiva->id);
            if ($link !== null && $this->rotaPublicaGetExiste('abastecimento-motorista/{id}')) {
                $telefone = $movAtiva->motorista->celular ?? $movAtiva->motorista->telefone;
                $placa = $movAtiva->veiculo->placa ?? 'do veículo';
                $mensagem = "⛽ *REGISTRO DE ABASTECIMENTO*\n\nSua viagem foi iniciada!\nSe houver necessidade de abastecimento para o veículo *{$placa}*, registre os litros e a foto do cupom fiscal:\n\n👉 {$link}";
                $this->enviarRespostaWhatsApp($movAtiva->empresa_id, $telefone, $mensagem);
            }
        }
    }

    private function getEventoWebhook(int $movimentacaoId, string $etapa): ?array
    {
        try {
            $persistido = TraccarWebhookEvent::query()
                ->where('movimentacao_id', $movimentacaoId)
                ->where('etapa', $etapa)
                ->where('status', TraccarWebhookEvent::STATUS_CLASSIFICADO)
                ->orderBy('event_time')
                ->orderBy('id')
                ->first();

            if ($persistido) {
                $payload = $persistido->toMonitorPayload();
                $this->eventosWebhookLidos[$movimentacaoId][$etapa] = $payload;
                return $payload;
            }
        } catch (Throwable $e) {}

        $evento = Cache::get($this->getEventoWebhookCacheKey($movimentacaoId, $etapa));
        $payload = is_array($evento) ? $evento : null;
        if ($payload !== null) $this->eventosWebhookLidos[$movimentacaoId][$etapa] = $payload;
        return $payload;
    }

    private function getHorarioEventoWebhook(?array $evento): ?Carbon
    {
        if (!$evento || empty($evento['event_time'])) return null;
        try { return Carbon::parse($evento['event_time'])->setTimezone(config('app.timezone', 'America/Bahia')); }
        catch (Throwable $e) { return null; }
    }

    private function getVelocidadeEventoWebhookKmH(?array $evento): ?float
    {
        if (!$evento || !isset($evento['speed']) || $evento['speed'] === null) return null;
        return ((float) $evento['speed']) * 1.852;
    }

    private function forgetEventoWebhook(int $movimentacaoId, string $etapa): void
    {
        $payload = $this->eventosWebhookLidos[$movimentacaoId][$etapa] ?? null;
        $this->eventosWebhookParaConsumir[$movimentacaoId][$etapa] = isset($payload['persisted_event_id']) ? (int) $payload['persisted_event_id'] : null;
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
                        'status'        => TraccarWebhookEvent::STATUS_CONSUMIDO,
                        'processed_at'  => now(),
                        'locked_at'     => null,
                        'next_retry_at' => null,
                        'last_error'    => null,
                        'updated_at'    => now(),
                    ]);
            }

            foreach (array_keys($eventos) as $etapa) {
                Cache::forget($this->getEventoWebhookCacheKey($movimentacaoId, $etapa));
                unset($this->eventosWebhookLidos[$movimentacaoId][$etapa]);
            }
            unset($this->eventosWebhookParaConsumir[$movimentacaoId]);
        } catch (Throwable $e) {}
    }

    private function urlPublica(string $caminho): ?string
    {
        $base = trim((string) config('app.url', ''));
        $partes = parse_url($base);
        $scheme = strtolower((string) ($partes['scheme'] ?? ''));
        $host = strtolower((string) ($partes['host'] ?? ''));

        $ipPublico = filter_var($host, FILTER_VALIDATE_IP)
            ? (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            : null;

        if (!in_array($scheme, ['http', 'https'], true)
            || $host === '' || $host === 'localhost'
            || (!str_contains($host, '.') && $ipPublico === null)
            || $ipPublico === false
            || str_ends_with($host, '.local') || str_ends_with($host, '.internal')
            || in_array($host, ['127.0.0.1', '0.0.0.0', '::1'], true)) {
            return null;
        }

        return rtrim($base, '/') . '/' . ltrim($caminho, '/');
    }

    private function rotaPublicaGetExiste(string $uri): bool
    {
        foreach (Route::getRoutes() as $rota) {
            if (trim($rota->uri(), '/') === trim($uri, '/') && in_array('GET', $rota->methods(), true)) {
                return true;
            }
        }
        return false;
    }

    private function processarDestinos(MovimentacaoVeiculo $mov, float $latitude, float $longitude, float $velocidadeKmH, Carbon $horario): void
    {
        $pendentes = $mov->destinos->filter(fn ($d) => $d->status !== 'visitado');
        if ($pendentes->isEmpty()) return;

        foreach ($pendentes as $item) {
            if (!is_numeric($item->latitude) || !is_numeric($item->longitude)) {
                $latCad = $item->cliente->latitude ?? $item->fornecedor->latitude ?? null;
                $lonCad = $item->cliente->longitude ?? $item->fornecedor->longitude ?? null;
                if (is_numeric($latCad) && is_numeric($lonCad)) {
                    $item->latitude = $latCad;
                    $item->longitude = $lonCad;
                    $item->save();
                }
            }
        }

        $destino = $pendentes->first(fn ($d) => !empty($d->data_hora_chegada) && empty($d->data_hora_saida));

        if (!$destino) {
            $candidatos = $pendentes->filter(function ($d) use ($latitude, $longitude) {
                if (!is_numeric($d->latitude) || !is_numeric($d->longitude)) return false;
                $dist = $this->calcularDistanciaMetros($latitude, $longitude, $d->latitude, $d->longitude);
                return $dist !== null && $dist <= 250;
            });
            $destino = $candidatos->sortBy(function ($d) use ($latitude, $longitude) {
                return $this->calcularDistanciaMetros($latitude, $longitude, $d->latitude, $d->longitude);
            })->first();
        }

        if (!$destino || !is_numeric($destino->latitude) || !is_numeric($destino->longitude)) return;
        $distancia = $this->calcularDistanciaMetros($latitude, $longitude, $destino->latitude, $destino->longitude);

        if ($distancia !== null && $distancia <= 250 && empty($destino->data_hora_chegada)) {
            $destino->data_hora_chegada = $horario;
            $destino->save();

            if (empty($mov->data_hora_chegada_cliente)) {
                $mov->data_hora_chegada_cliente = $horario;
                $mov->save();
            }

            $this->enviarLinkColetaResiduoMotorista($mov, $destino);

            if ((int) ($destino->is_aterro ?? 0) === 1) {
                $this->notificarResponsavelMtr($mov, $destino, 'chegada_aterro');
            }
            return;
        }

        if ($distancia !== null && $distancia > 300 && !empty($destino->data_hora_chegada) && empty($destino->data_hora_saida)) {
            $minutos = Carbon::parse($destino->data_hora_chegada)->diffInMinutes($horario, false);
            if ($minutos >= 2 || ($minutos >= 0 && $velocidadeKmH > 10)) {
                $destino->data_hora_saida = $horario;
                $destino->status = 'visitado';
                $destino->save();

                $mov->data_hora_saida_cliente = $horario;
                $mov->save();

                $this->atualizarStatusCaixasDestino($destino);

                $temAterroNaRota = $mov->destinos->contains(fn ($d) => (int) ($d->is_aterro ?? 0) === 1);
                if (((int) ($mov->gerar_mtr ?? 0) === 1 || $temAterroNaRota) && (int) ($destino->is_aterro ?? 0) === 0) {
                    $this->notificarResponsavelMtr($mov, $destino, 'saida_cliente');
                }
            }
        }
    }

    private function todosDestinosVisitados(MovimentacaoVeiculo $mov): bool
    {
        if ($mov->destinos->isEmpty()) return false;
        $destinosComCoordenada = $mov->destinos->filter(fn ($d) => is_numeric($d->latitude) && is_numeric($d->longitude));
        if ($destinosComCoordenada->isEmpty()) return !empty($mov->data_hora_saida_cliente);
        return $destinosComCoordenada->every(fn ($destino) => $destino->status === 'visitado' && !empty($destino->data_hora_saida));
    }

    private function estimativaKmMultiplosDestinos(MovimentacaoVeiculo $mov, $latOrigem, $lonOrigem, $latBase, $lonBase): float
    {
        $anteriorLat = $latOrigem;
        $anteriorLon = $lonOrigem;
        $metros = 0;
        $trechos = 0;
        foreach ($mov->destinos->sortBy(fn ($d) => $d->ordem ?? $d->id) as $destino) {
            if (!is_numeric($destino->latitude) || !is_numeric($destino->longitude)) continue;
            if ($anteriorLat !== null && $anteriorLon !== null) {
                $metros += $this->calcularDistanciaMetros($anteriorLat, $anteriorLon, $destino->latitude, $destino->longitude) ?? 0;
                $trechos++;
            }
            $anteriorLat = $destino->latitude;
            $anteriorLon = $destino->longitude;
        }
        if ($anteriorLat !== null && $anteriorLon !== null && $latBase !== null && $lonBase !== null) {
            $metros += $this->calcularDistanciaMetros($anteriorLat, $anteriorLon, $latBase, $lonBase) ?? 0;
            $trechos++;
        }
        return $trechos > 0 ? ($metros / 1000) * 1.25 : 0;
    }

    private function registrarParadasTraccar(MovimentacaoVeiculo $mov, string $baseUrl, ?string $username, ?string $password, ?string $token, $deviceId): void
    {
        $inicioRef = $mov->data_hora_saida_real ?: $mov->data_hora_saida;
        $fimRef    = $mov->data_hora_chegada ?: Carbon::now();
        if (empty($inicioRef)) return;

        try {
            $from = Carbon::parse($inicioRef)->subHours(2)->utc()->format('Y-m-d\TH:i:s\Z');
            $to   = Carbon::parse($fimRef)->addHours(2)->utc()->format('Y-m-d\TH:i:s\Z');

            $requisicao = Http::acceptJson()->connectTimeout(5)->timeout(15);
            $requisicao = $token ? $requisicao->withToken($token) : $requisicao->withBasicAuth($username, $password);
            $resposta = $requisicao->get(rtrim($baseUrl, '/') . '/api/reports/stops', ['deviceId' => $deviceId, 'from' => $from, 'to' => $to]);

            if ($resposta->failed()) return;
            $paradasTraccar = (array) $resposta->json();

            foreach ($paradasTraccar as $parada) {
                if (empty($parada['startTime']) || empty($parada['endTime'])) continue;
                $duracao = (int) floor(((float) ($parada['duration'] ?? 0)) / 60000);
                if ($duracao < 5) continue;

                $fuso = config('app.timezone', 'America/Bahia');
                $inicio = Carbon::parse($parada['startTime'])->setTimezone($fuso);
                $fim = Carbon::parse($parada['endTime'])->setTimezone($fuso);

                $enderecoParada = $parada['address'] ?? null;
                if (empty($enderecoParada) && isset($parada['latitude'], $parada['longitude'])) {
                    $enderecoParada = $this->obterEnderecoPorCoordenadas($parada['latitude'], $parada['longitude']);
                }

                $existe = DB::table('movimentacao_historico_paradas')->where('movimentacao_veiculo_id', $mov->id)->where('data_hora_inicio', $inicio)->first();

                if (!$existe) {
                    DB::table('movimentacao_historico_paradas')->insert([
                        'movimentacao_veiculo_id' => $mov->id, 'data_hora_inicio' => $inicio, 'data_hora_fim' => $fim,
                        'duracao_minutos' => $duracao, 'endereco' => $enderecoParada ?: 'Endereço desconhecido',
                        'latitude' => $parada['latitude'] ?? null, 'longitude' => $parada['longitude'] ?? null,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                } else {
                    DB::table('movimentacao_historico_paradas')->where('id', $existe->id)->update(['data_hora_fim' => $fim, 'duracao_minutos' => $duracao, 'updated_at' => now()]);
                }
            }
        } catch (Throwable $e) {}
    }

    private function dispararAlertaAtrasoSaida(MovimentacaoVeiculo $mov, Carbon $horarioPrevisto): void
    {
        try {
            $cacheKey = "frota:alerta_atraso_enviado:{$mov->id}";
            if (!empty($mov->alerta_atraso_enviado) || !empty($mov->alerta_atraso_enviado_at) || Cache::has($cacheKey)) return;
            if (!Schema::hasColumn('funcionarios', 'recebe_alerta_frota')) return;

            $responsaveis = Funcionario::where('empresa_id', $mov->empresa_id)->where('recebe_alerta_frota', 1)->get();
            if ($responsaveis->isEmpty()) return;

            $minutosAtraso = $horarioPrevisto->diffInMinutes(Carbon::now());
            $placa = $mov->veiculo->placa ?? 'S/ Placa';
            $motorista = $mov->motorista->nome ?? 'Não informado';
            $destino = $mov->destino ?? 'Rota agendada';

            $msg = "🚨 *ALERTA DE ATRASO NA FROTA*\n\nA viagem *#{$mov->id}* está atrasada há *{$minutosAtraso} minutos* e ainda não iniciou deslocamento!\n\n🚛 *Veículo:* {$placa}\n👤 *Motorista:* {$motorista}\n🕒 *Saída Prevista:* " . $horarioPrevisto->format('d/m/Y H:i') . "\n📍 *Destino:* {$destino}";

            foreach ($responsaveis as $resp) {
                $tel = $resp->celular ?: $resp->telefone;
                if (!empty($tel)) $this->enviarRespostaWhatsApp($mov->empresa_id, $tel, $msg);
            }
            Cache::put($cacheKey, true, now()->addHours(24));
            if (Schema::hasColumn('movimentacoes_veiculos', 'alerta_atraso_enviado')) {
                DB::table('movimentacoes_veiculos')->where('id', $mov->id)->update(['alerta_atraso_enviado' => 1]);
            }
        } catch (Throwable $e) {}
    }

    private function enviarLinkColetaResiduoMotorista(MovimentacaoVeiculo $mov, $destino): void
    {
        try {
            if (!$mov->motorista || (int) ($destino->is_aterro ?? 0) === 1) return;
            $link = $this->urlPublica("coleta-motorista/{$mov->id}/destino/{$destino->id}");
            if (!$link || !$this->rotaPublicaGetExiste('coleta-motorista/{id}/destino/{destino_id}')) return;

            $telefone = $mov->motorista->celular ?: $mov->motorista->telefone;
            $nomeLocal = $destino->cliente->razao_social ?? $destino->fornecedor->razao_social ?? $destino->endereco_completo;
            $msg = "♻️ *REGISTRO DE COLETA / CAÇAMBA*\n\nOlá, *{$mov->motorista->nome}*! Chegada registrada em:\n*{$nomeLocal}*\n\nClique no link abaixo para informar o *Resíduo coletado, Peso e Caçamba*:\n👉 {$link}";
            $this->enviarRespostaWhatsApp($mov->empresa_id, $telefone, $msg);
        } catch (Throwable $e) {}
    }

    private function notificarResponsavelMtr(MovimentacaoVeiculo $mov, $destino, string $momento): void
    {
        try {
            if (!empty($destino->alerta_mtr_enviado)) return;
            $temColMtr = Schema::hasColumn('funcionarios', 'responsavel_mtr');
            $temColFrota = Schema::hasColumn('funcionarios', 'recebe_alerta_frota');
            if (!$temColMtr && !$temColFrota) return;

            $responsaveis = Funcionario::where('empresa_id', $mov->empresa_id)->where(function ($q) use ($temColMtr, $temColFrota) {
                if ($temColMtr) $q->orWhere('responsavel_mtr', 1);
                if ($temColFrota) $q->orWhere('recebe_alerta_frota', 1);
            })->get();

            if ($responsaveis->isEmpty()) return;

            $placa = $mov->veiculo->placa ?? 'S/ Placa';
            $motorista = $mov->motorista->nome ?? 'Não informado';
            $nomeLocal = $destino->cliente->razao_social ?? $destino->fornecedor->razao_social ?? $destino->endereco_completo;
            $linkMtr = $this->urlPublica("movimentacaoVeiculo/emitirMtr/{$mov->id}");
            $tituloEvento = $momento === 'saida_cliente' ? "🚛 *VEÍCULO SAIU DO CLIENTE RUMO AO ATERRO*" : "🏭 *VEÍCULO CHEGOU NO LOCAL DE DESCARTE (ATERRO)*";

            $msg = "{$tituloEvento}\n\nA viagem *#{$mov->id}* requer a emissão do *MTR*:\n📍 *Local:* {$nomeLocal}\n🚛 *Veículo:* {$placa} ({$motorista})\n\n✅ *Para emitir pelo WhatsApp:* Responda apenas *EMITIR MTR {$mov->id}*\n";
            if ($linkMtr) $msg .= "🔗 *Ou emita pelo painel:* {$linkMtr}";

            foreach ($responsaveis as $resp) {
                $tel = $resp->celular ?: $resp->telefone;
                if (!empty($tel)) $this->enviarRespostaWhatsApp($mov->empresa_id, $tel, $msg);
            }
            if (Schema::hasColumn('movimentacao_destinos', 'alerta_mtr_enviado')) {
                DB::table('movimentacao_destinos')->where('id', $destino->id)->update(['alerta_mtr_enviado' => 1]);
            }
        } catch (Throwable $e) {}
    }

    private function atualizarStatusCaixasDestino($destino): void
    {
        try {
            if (!Schema::hasTable('equipamentos_caixas')) return;

            if (!empty($destino->caixa_deixada_id)) {
                DB::table('equipamentos_caixas')->where('id', $destino->caixa_deixada_id)->update([
                    'status' => 'no_cliente', 'cliente_atual_id' => $destino->cliente_id,
                    'ultimo_endereco' => $destino->endereco_completo, 'data_alocacao' => now(), 'updated_at' => now(),
                ]);
            }
            if (!empty($destino->caixa_retirada_id)) {
                $novoStatus = ((int) ($destino->is_aterro ?? 0) === 1) ? 'disponivel' : 'em_transito';
                DB::table('equipamentos_caixas')->where('id', $destino->caixa_retirada_id)->update([
                    'status' => $novoStatus, 'cliente_atual_id' => null, 'updated_at' => now(),
                ]);
            }
        } catch (Throwable $e) {}
    }

    private function verificarManutencoesPreventivas(MovimentacaoVeiculo $mov): void
    {
        if (!$mov->veiculo_id || !$mov->km_final) return;
        try {
            if (!Schema::hasTable('veiculo_planos_preventivos') || !Schema::hasTable('veiculo_alertas_manutencao')) return;

            $planos = DB::table('veiculo_planos_preventivos')->where('empresa_id', $mov->empresa_id)->where('veiculo_id', $mov->veiculo_id)->where('ativo', 1)->get();
            foreach ($planos as $plano) {
                $kmRestante = (float) $plano->intervalo_km - ((float) $mov->km_final - (float) $plano->ultimo_km_executado);
                if ($kmRestante > 500) continue;
                $jaExiste = DB::table('veiculo_alertas_manutencao')->where('empresa_id', $mov->empresa_id)->where('veiculo_id', $mov->veiculo_id)->where('tipo', 'preventiva')->where('titulo', $plano->servico_nome)->where('fase', '!=', 'resolvido')->exists();
                if ($jaExiste) continue;

                DB::table('veiculo_alertas_manutencao')->insert([
                    'empresa_id' => $mov->empresa_id, 'filial_id' => $mov->filial_id, 'veiculo_id' => $mov->veiculo_id,
                    'tipo' => 'preventiva', 'titulo' => $plano->servico_nome,
                    'descricao' => "Veículo atingiu {$mov->km_final} KM. Próxima execução: " . ((float) $plano->ultimo_km_executado + (float) $plano->intervalo_km) . ' KM.',
                    'prioridade' => $kmRestante <= 0 ? 'Urgente' : 'Alta', 'fase' => 'identificado',
                    'impedir_circulacao' => $kmRestante <= -500 ? 1 : 0, 'intervalo_km' => $plano->intervalo_km,
                    'ultimo_km_executado' => $plano->ultimo_km_executado, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        } catch (Throwable $e) {}
    }

    private function obterEnderecoPorCoordenadas($lat, $lon): string
    {
        $fallback = "Lat: {$lat}, Lon: {$lon}";
        $email = (string) config('mail.from.address');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) return $fallback;
        $nomeAplicacao = trim((string) config('app.name', ''));
        $identificador = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', Str::ascii($nomeAplicacao)), '-._');
        if ($identificador === '') return $fallback;

        try { $versaoAplicacao = trim((string) app(AppVersionService::class)->resolveInstalledVersion()); } catch (Throwable $e) { $versaoAplicacao = ''; }
        $agenteAplicacao = $identificador . ($versaoAplicacao !== '' && $versaoAplicacao !== '0.0.0' ? '/' . $versaoAplicacao : '') . ' (' . $email . ')';
        $chave = 'frota:reverse:' . md5(round((float) $lat, 5) . ',' . round((float) $lon, 5));
        if (Cache::has($chave)) return (string) Cache::get($chave);
        if (!Cache::add('frota:nominatim:throttle', true, 2)) return $fallback;

        try {
            $resposta = Http::withHeaders(['User-Agent' => $agenteAplicacao])->connectTimeout(2)->timeout(3)->get('https://nominatim.openstreetmap.org/reverse', ['format' => 'jsonv2', 'lat' => $lat, 'lon' => $lon, 'addressdetails' => 1]);
            if ($resposta->successful()) {
                $dados = $resposta->json('address', []);
                $partes = array_filter([$dados['road'] ?? $dados['pedestrian'] ?? $dados['street'] ?? '', $dados['suburb'] ?? $dados['neighbourhood'] ?? '', $dados['city'] ?? $dados['town'] ?? $dados['municipality'] ?? '']);
                if ($partes) {
                    $endereco = implode(', ', $partes);
                    Cache::put($chave, $endereco, now()->addDays(30));
                    return $endereco;
                }
            }
        } catch (Throwable $e) {}
        return $fallback;
    }

    private function getEventoWebhookCacheKey(int $movimentacaoId, string $etapa): string
    {
        return "traccar:frota:movimentacao:{$movimentacaoId}:{$etapa}";
    }

    private function enviarComprovantePontoAutomaticoWhatsApp($movimentacao, $funcionario, $lat, $lon, $hash)
    {
        try {
            $telefone = $funcionario->celular ?? $funcionario->telefone;
            if (empty($telefone)) return;
            $telefoneLimpo = preg_replace('/[^0-9]/', '', $telefone);
            if (!str_starts_with($telefoneLimpo, '55')) $telefoneLimpo = '55' . $telefoneLimpo;

            $dadosEmpresa = $this->getEmpresaConfigDoRobo($movimentacao->empresa_id, $movimentacao->filial_id);
            $placa = $movimentacao->veiculo->placa ?? 'Veículo';
            $enderecoLocal = $this->obterEnderecoPorCoordenadas($lat, $lon);

            $comprovante = "🧾 *COMPROVANTE DE REGISTRO DE PONTO (AUTOMÁTICO)*\n----------------------------------------\n";
            $comprovante .= "🏢 *Empresa:* {$dadosEmpresa['razao_social']}\n📋 *CNPJ:* {$dadosEmpresa['cnpj']}\n📍 *Endereço:* {$dadosEmpresa['endereco']}\n----------------------------------------\n";
            $comprovante .= "👤 *Funcionário:* {$funcionario->nome}\n🔢 *PIS:* " . ($funcionario->pis ?? 'Não informado') . "\n----------------------------------------\n";
            $comprovante .= "📌 *Tipo:* 🟢 ENTRADA (Início de Jornada)\n🕒 *Data/Hora:* " . Carbon::now()->format('d/m/Y \à\s H:i:s') . "\n";
            $comprovante .= "🚛 *Veículo:* {$placa}\n📍 *GPS Traccar:* Lat: {$lat}, Lon: {$lon}\n🏠 *Localização:* {$enderecoLocal}\n";
            $comprovante .= "🔗 *Mapa:* https://maps.google.com/?q={$lat},{$lon}\n----------------------------------------\n";
            $comprovante .= "🔑 *Autenticação:* {$hash}\n----------------------------------------\n_Comprovante emitido nos termos da Portaria 671/2021 MTE_";

            $resultado = app(\App\Utils\WhatsAppUtil::class)->sendMessage($telefoneLimpo, $comprovante, (int) $movimentacao->empresa_id);
            $this->registrarRetornoWhatsapp($resultado, (int) $movimentacao->empresa_id, 'ponto');
        } catch (Exception $e) {}
    }

    private function enviarRespostaWhatsApp($empresaId, $telefone, $mensagem)
    {
        try {
            if (empty($telefone)) return;
            $resultado = app(\App\Utils\WhatsAppUtil::class)->sendMessage((string) $telefone, (string) $mensagem, (int) $empresaId);
            $this->registrarRetornoWhatsapp($resultado, (int) $empresaId, 'frota');
        } catch (Throwable $e) {}
    }

    private function registrarRetornoWhatsapp($resultado, int $empresaId, string $tipo): void
    {
        if (!is_string($resultado)) return;
        $dados = json_decode($resultado, true);
        if (is_array($dados) && ($dados['success'] ?? null) === false) {
            Log::warning('ROBÔ: WhatsAppUtil retornou falha.', ['empresa_id' => $empresaId, 'tipo' => $tipo, 'message' => $dados['message'] ?? 'Falha']);
        }
    }

    private function getEmpresaConfigDoRobo($empresaId, $filialId = null)
    {
        if (!empty($filialId)) {
            $filial = DB::table('filials')->where('id', $filialId)->first();
            if ($filial) return ['razao_social' => $filial->razao_social, 'cnpj' => $filial->cnpj, 'endereco' => "{$filial->logradouro}, {$filial->numero} - {$filial->bairro}, {$filial->municipio} - {$filial->UF}"];
        }
        $configNotas = DB::table('config_notas')->where('empresa_id', $empresaId)->first();
        if ($configNotas) return ['razao_social' => $configNotas->razao_social, 'cnpj' => $configNotas->cnpj, 'endereco' => "{$configNotas->logradouro}, {$configNotas->numero} - {$configNotas->bairro}, {$configNotas->municipio} - {$configNotas->UF}"];
        return ['razao_social' => trim((string) config('app.name', '')) ?: 'Nome da aplicação não configurado', 'cnpj' => 'Não informado', 'endereco' => 'Endereço não cadastrado'];
    }

    private function calcularDistanciaMetros($lat1, $lon1, $lat2, $lon2)
    {
        foreach ([$lat1, $lon1, $lat2, $lon2] as $valor) { if ($valor === null || !is_numeric($valor)) return null; }
        if (abs((float) $lat1) > 90 || abs((float) $lat2) > 90 || abs((float) $lon1) > 180 || abs((float) $lon2) > 180) return null;
        $raioTerra = 6371000;
        $latDe = deg2rad($lat1); $lonDe = deg2rad($lon1); $latPara = deg2rad($lat2); $lonPara = deg2rad($lon2);
        $deltaLat = $latPara - $latDe; $deltaLon = $lonPara - $lonDe;
        $angulo = 2 * asin(sqrt(pow(sin($deltaLat / 2), 2) + cos($latDe) * cos($latPara) * pow(sin($deltaLon / 2), 2)));
        return $angulo * $raioTerra;
    }

    private function dispararAvisoPrevioMotorista(MovimentacaoVeiculo $mov, Carbon $horarioPrevisto): void
    {
        try {
            $cacheKey = "frota:aviso_previo_30min:{$mov->id}";
            if (Cache::has($cacheKey)) return;
            if (!$mov->motorista || (empty($mov->motorista->celular) && empty($mov->motorista->telefone))) return;

            $telefone = $mov->motorista->celular ?: $mov->motorista->telefone;
            $placa = $mov->veiculo->placa ?? 'Veículo';
            $modelo = $mov->veiculo->modelo ?? '';
            $horaSaida = $horarioPrevisto->format('H:i');

            $msg = "🚚 *ORDEM DE COLETA DIGITAL* | Viagem #{$mov->id}\nOlá, *{$mov->motorista->nome}*! Sua rota está programada para sair em breve.\n\n📋 *DETALHES DA ESCALA*\n• *Veículo:* {$placa} ({$modelo})\n• *Saída Prevista:* {$horaSaida} (em aprox. 30 minutos)\n\n🗺️ *ITINERÁRIO DE PARADAS*\n";

            if ($mov->destinos && $mov->destinos->count() > 0) {
                foreach ($mov->destinos as $idx => $dest) {
                    $n = $idx + 1;
                    $nomeCliente = $dest->cliente->razao_social ?? ($dest->cliente->nome ?? ($dest->fornecedor->razao_social ?? 'Parada Sem Nome'));
                    $endereco = $dest->endereco_completo ?: 'Endereço não informado';
                    $opcoes = ['nenhuma' => 'Apenas Coleta de Resíduo', 'colocar' => 'Deixar Caçamba Vazia', 'retirar' => 'Recolher Caçamba Cheia', 'trocar'  => 'Trocar Caçamba'];
                    $missao = $opcoes[$dest->tipo_operacao_caixa] ?? 'Conforme Planejamento';
                    $linkMaps = (!empty($dest->latitude) && !empty($dest->longitude)) ? "https://www.google.com/maps/search/?api=1&query={$dest->latitude},{$dest->longitude}" : "https://www.google.com/maps/search/?api=1&query=" . urlencode($endereco);

                    $msg .= "*Parada {$n}:* {$nomeCliente}\n• *Missão:* {$missao}\n• *Endereço:* {$endereco}\n📍 *Abrir GPS:* {$linkMaps}\n\n";
                }
            } else {
                $msg .= "• Destino: " . ($mov->destino ?: 'Conforme instrução interna') . "\n\n";
            }

            $linkColeta = $this->urlPublica("coleta-motorista/{$mov->id}/destino/" . ($mov->destinos->first()->id ?? 0));
            $linkChecklist = $this->urlPublica("checklist/veiculo/{$mov->id}");

            $msg .= "📲 *LINKS RÁPIDOS*\n";
            if ($linkChecklist) $msg .= "👉 *Checklist do Veículo:* {$linkChecklist}\n";
            if ($linkColeta) $msg .= "👉 *Apontamento de Balança/Caçamba:* {$linkColeta}\n\n_Tenha uma excelente e segura viagem!_";

            $this->enviarRespostaWhatsApp($mov->empresa_id, $telefone, $msg);
            Cache::put($cacheKey, true, now()->addDays(2));
        } catch (Throwable $e) {}
    }

    private function calcularDistanciaTempoGoogle($latOrigem, $lonOrigem, $latDestino, $lonDestino)
    {
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        if (empty($apiKey)) return null;

        try {
            $resposta = Http::withHeaders([
                'X-Goog-Api-Key' => $apiKey,
                'X-Goog-FieldMask' => 'routes.duration,routes.distanceMeters'
            ])->connectTimeout(3)->timeout(5)->post("https://routes.googleapis.com/directions/v2:computeRoutes", [
                'origin' => [
                    'location' => [
                        'latLng' => ['latitude' => (float)$latOrigem, 'longitude' => (float)$lonOrigem]
                    ]
                ],
                'destination' => [
                    'location' => [
                        'latLng' => ['latitude' => (float)$latDestino, 'longitude' => (float)$lonDestino]
                    ]
                ],
                'travelMode' => 'DRIVE',
                'routingPreference' => 'TRAFFIC_AWARE',
            ]);

            if ($resposta->successful()) {
                $dados = $resposta->json();

                if (!empty($dados['routes'][0])) {
                    $rota = $dados['routes'][0];
                    $distanciaKm = ($rota['distanceMeters'] ?? 0) / 1000;

                    // Remove o "s" dos segundos que o Google retorna
                    $segundos = (int) str_replace('s', '', $rota['duration'] ?? '0');
                    $minutos = (int) round($segundos / 60);

                    $textoTempo = $minutos > 60
                        ? floor($minutos / 60) . 'h ' . ($minutos % 60) . ' min'
                        : $minutos . ' min';

                    return [
                        'distancia_km'  => $distanciaKm,
                        'tempo_minutos' => $minutos,
                        'texto_tempo'   => $textoTempo
                    ];
                }
            } else {
                \Illuminate\Support\Facades\Log::warning("Google Maps retornou erro: " . $resposta->body());
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Erro API Google Maps (Routes): " . $e->getMessage());
        }
        return null;
    }
}
