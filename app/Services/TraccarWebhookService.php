<?php

namespace App\Services;

use App\Models\Funcionario;
use App\Models\MovimentacaoVeiculo;
use App\Models\TraccarConfig;
use App\Models\TraccarWebhookEvent;
use App\Models\Veiculo;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TraccarWebhookService
{
    private const EVENTO_GEOFENCE_ENTER = 'geofenceEnter';
    private const EVENTO_GEOFENCE_EXIT = 'geofenceExit';

    private const ETAPA_SAIDA_ORIGEM = 'saida_origem';
    private const ETAPA_CHEGADA_CLIENTE = 'chegada_cliente';
    private const ETAPA_SAIDA_CLIENTE = 'saida_cliente';
    private const ETAPA_CHEGADA_BASE = 'chegada_base';

    /**
     * Registra e classifica um evento do Traccar de forma idempotente.
     *
     * O registro no banco acontece antes do processamento. Assim, se o PHP,
     * servidor ou API externa cair durante o processamento, o evento continua
     * disponível para reprocessamento posterior.
     */
    public function receber(array $dados): array
    {
        if (
            !isset($dados['event'])
            || !is_array($dados['event'])
            || !isset($dados['device'])
            || !is_array($dados['device'])
        ) {
            return [
                'http_status' => 200,
                'status' => 'ignored',
                'message' => 'Payload inválido.',
            ];
        }

        $evento = $dados['event'];
        $dispositivo = $dados['device'];
        $tipoEvento = $evento['type'] ?? null;

        if (!in_array($tipoEvento, [
            self::EVENTO_GEOFENCE_ENTER,
            self::EVENTO_GEOFENCE_EXIT,
        ], true)) {
            return [
                'http_status' => 200,
                'status' => 'ignored',
                'message' => 'Evento não utilizado pela automação da frota.',
                'event_type' => $tipoEvento,
            ];
        }

        $uniqueId = trim((string) ($dispositivo['uniqueId'] ?? ''));
        $deviceId = isset($dispositivo['id'])
            ? trim((string) $dispositivo['id'])
            : '';

        if ($uniqueId === '' && $deviceId === '') {
            return [
                'http_status' => 200,
                'status' => 'ignored',
                'message' => 'Dispositivo sem identificador.',
            ];
        }

        $dedupeKey = $this->gerarDedupeKey($dados);
        $registro = $this->reservarEvento(
            $dedupeKey,
            $dados,
            $tipoEvento,
            $deviceId,
            $uniqueId
        );

        if (!$registro) {
            return [
                'http_status' => 503,
                'status' => 'error',
                'message' => 'Não foi possível persistir o evento Traccar.',
            ];
        }

        if (in_array($registro->status, [
            TraccarWebhookEvent::STATUS_CLASSIFICADO,
            TraccarWebhookEvent::STATUS_CONSUMIDO,
            TraccarWebhookEvent::STATUS_IGNORADO,
        ], true)) {
            return [
                'http_status' => 200,
                'status' => 'duplicate',
                'message' => 'Evento já recebido anteriormente.',
                'event_record_id' => $registro->id,
                'event_status' => $registro->status,
                'etapa' => $registro->etapa,
                'movimentacao_id' => $registro->movimentacao_id,
            ];
        }

        return $this->processarRegistro($registro);
    }

    /**
     * Reprocessa eventos que ficaram pendentes por falha transitória.
     */
    public function reprocessarPendentes(int $limit = 100): array
    {
        $this->liberarProcessamentosTravados();

        $eventos = TraccarWebhookEvent::query()
            ->whereIn('status', [
                TraccarWebhookEvent::STATUS_RECEBIDO,
                TraccarWebhookEvent::STATUS_RETRY,
            ])
            ->where(function ($query) {
                $query->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $resultado = [
            'encontrados' => $eventos->count(),
            'processados' => 0,
            'classificados' => 0,
            'ignorados' => 0,
            'retry' => 0,
            'falharam' => 0,
        ];

        foreach ($eventos as $evento) {
            try {
                $retorno = $this->processarRegistro($evento);
                $resultado['processados']++;

                if (($retorno['status'] ?? null) === 'accepted') {
                    $resultado['classificados']++;
                } elseif (($retorno['status'] ?? null) === 'ignored') {
                    $resultado['ignorados']++;
                } elseif (($retorno['status'] ?? null) === 'retry') {
                    $resultado['retry']++;
                } elseif (($retorno['status'] ?? null) === 'error') {
                    $resultado['falharam']++;
                }
            } catch (Throwable $e) {
                $resultado['falharam']++;

                Log::error('Traccar webhook: falha inesperada no reprocessamento.', [
                    'event_record_id' => $evento->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $resultado;
    }

    public function podarEventosAntigos(?int $dias = null): int
    {
        $dias = $dias ?? (int) config(
            'traccar.webhook.retention_days',
            30
        );

        return TraccarWebhookEvent::query()
            ->whereIn('status', [
                TraccarWebhookEvent::STATUS_CONSUMIDO,
                TraccarWebhookEvent::STATUS_IGNORADO,
                TraccarWebhookEvent::STATUS_FALHOU,
            ])
            ->where('updated_at', '<', now()->subDays(max(1, $dias)))
            ->delete();
    }

    private function reservarEvento(
        string $dedupeKey,
        array $dados,
        ?string $tipoEvento,
        string $deviceId,
        string $uniqueId
    ): ?TraccarWebhookEvent {
        $evento = $dados['event'] ?? [];

        try {
            return TraccarWebhookEvent::firstOrCreate(
                ['dedupe_key' => $dedupeKey],
                [
                    'traccar_event_id' => isset($evento['id'])
                        ? (int) $evento['id']
                        : null,
                    'event_type' => $tipoEvento,
                    'device_id' => $deviceId ?: null,
                    'unique_id' => $uniqueId ?: null,
                    'position_id' => isset($evento['positionId'])
                        ? (string) $evento['positionId']
                        : null,
                    'geofence_id' => isset($evento['geofenceId'])
                        ? (string) $evento['geofenceId']
                        : null,
                    'status' => TraccarWebhookEvent::STATUS_RECEBIDO,
                    'payload' => $dados,
                ]
            );
        } catch (QueryException $e) {
            // Corrida entre duas entregas simultâneas do mesmo evento.
            // O índice UNIQUE é a última barreira de idempotência.
            $existente = TraccarWebhookEvent::where(
                'dedupe_key',
                $dedupeKey
            )->first();

            if ($existente) {
                return $existente;
            }

            Log::error('Traccar webhook: erro ao persistir evento.', [
                'dedupe_key' => $dedupeKey,
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (Throwable $e) {
            Log::error('Traccar webhook: erro ao persistir evento.', [
                'dedupe_key' => $dedupeKey,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function processarRegistro(
        TraccarWebhookEvent $registro
    ): array {
        $registroId = (int) $registro->id;
        $registroAdquirido = $this->adquirirRegistroParaProcessamento(
            $registroId
        );

        if (!$registroAdquirido) {
            $atual = TraccarWebhookEvent::find($registroId);

            return [
                'http_status' => 200,
                'status' => 'duplicate',
                'message' => 'Evento já está sendo processado ou já foi finalizado.',
                'event_record_id' => $registroId,
                'event_status' => $atual?->status,
                'etapa' => $atual?->etapa,
                'movimentacao_id' => $atual?->movimentacao_id,
            ];
        }

        $registro = $registroAdquirido;

        try {
            $dados = is_array($registro->payload)
                ? $registro->payload
                : [];

            $evento = $dados['event'] ?? [];
            $dispositivo = $dados['device'] ?? [];
            $tipoEvento = $evento['type'] ?? $registro->event_type;

            $uniqueId = trim((string) (
                $dispositivo['uniqueId']
                ?? $registro->unique_id
                ?? ''
            ));

            $deviceId = trim((string) (
                $dispositivo['id']
                ?? $registro->device_id
                ?? ''
            ));

            $movimentacao = null;
            $identificadorUsado = null;

            if ($uniqueId !== '') {
                $movimentacao = $this->buscarMovimentacaoAtivaPorTraccarId(
                    $uniqueId
                );

                if ($movimentacao) {
                    $identificadorUsado = $uniqueId;
                }
            }

            if (!$movimentacao && $deviceId !== '') {
                $movimentacao = $this->buscarMovimentacaoAtivaPorTraccarId(
                    $deviceId
                );

                if ($movimentacao) {
                    $identificadorUsado = $deviceId;
                }
            }

            if (!$movimentacao) {
                return $this->agendarRetryOuFalhar(
                    $registro,
                    'Nenhuma movimentação ativa encontrada para o dispositivo.',
                    (int) config(
                        'traccar.webhook.no_movement_max_attempts',
                        5
                    )
                );
            }

            $posicao = isset($dados['position'])
            && is_array($dados['position'])
                ? $dados['position']
                : null;

            if (!$posicao && !empty($evento['positionId'])) {
                $posicao = $this->buscarPosicaoEvento(
                    $movimentacao->empresa_id,
                    $evento['positionId']
                );
            }

            if (
                !$posicao
                || !isset($posicao['latitude'])
                || !isset($posicao['longitude'])
            ) {
                return $this->agendarRetryOuFalhar(
                    $registro,
                    'Evento sem posição utilizável.',
                    (int) config(
                        'traccar.webhook.max_attempts',
                        20
                    )
                );
            }

            $horarioExato = $this->resolverHorarioEvento(
                $evento,
                $posicao
            );

            $latitude = (float) $posicao['latitude'];
            $longitude = (float) $posicao['longitude'];
            $velocidadeNos = isset($posicao['speed'])
                ? (float) $posicao['speed']
                : null;

            $base = $movimentacao->filial_id
                ? DB::table('filials')
                    ->where('id', $movimentacao->filial_id)
                    ->first()
                : DB::table('config_notas')
                    ->where('empresa_id', $movimentacao->empresa_id)
                    ->first();

            $latBase = $base->latitude ?? null;
            $lonBase = $base->longitude ?? null;
            $latOrigem = $latBase;
            $lonOrigem = $lonBase;

            if (
                isset($movimentacao->tipo_partida)
                && $movimentacao->tipo_partida === 'residencia'
                && $movimentacao->motorista
            ) {
                $latOrigem = $movimentacao->motorista->latitude_residencia
                    ?? $latBase;

                $lonOrigem = $movimentacao->motorista->longitude_residencia
                    ?? $lonBase;
            }

            $distanciaOrigem = (
                $latOrigem !== null
                && $lonOrigem !== null
            )
                ? $this->calcularDistanciaMetros(
                    $latitude,
                    $longitude,
                    $latOrigem,
                    $lonOrigem
                )
                : null;

            $distanciaDestino = (
                $movimentacao->latitude_destino !== null
                && $movimentacao->longitude_destino !== null
            )
                ? $this->calcularDistanciaMetros(
                    $latitude,
                    $longitude,
                    $movimentacao->latitude_destino,
                    $movimentacao->longitude_destino
                )
                : null;

            $distanciaBase = (
                $latBase !== null
                && $lonBase !== null
            )
                ? $this->calcularDistanciaMetros(
                    $latitude,
                    $longitude,
                    $latBase,
                    $lonBase
                )
                : null;

            $temSaidaOrigem = !empty($movimentacao->data_hora_saida_real)
                || $movimentacao->status === 'iniciado'
                || $this->existeEtapaPendente(
                    $movimentacao->id,
                    self::ETAPA_SAIDA_ORIGEM
                );

            $temChegadaCliente = !empty($movimentacao->data_hora_chegada_cliente)
                || $this->existeEtapaPendente(
                    $movimentacao->id,
                    self::ETAPA_CHEGADA_CLIENTE
                );

            $temSaidaCliente = !empty($movimentacao->data_hora_saida_cliente)
                || $this->existeEtapaPendente(
                    $movimentacao->id,
                    self::ETAPA_SAIDA_CLIENTE
                );

            $temChegadaBase = !empty($movimentacao->data_hora_chegada)
                || $movimentacao->status === 'finalizado'
                || $this->existeEtapaPendente(
                    $movimentacao->id,
                    self::ETAPA_CHEGADA_BASE
                );

            $etapa = null;
            $deferir = false;

            if (
                $tipoEvento === self::EVENTO_GEOFENCE_EXIT
                && !$temSaidaOrigem
                && $movimentacao->status === 'agendado'
                && $distanciaOrigem !== null
                && $distanciaOrigem > 200
                && $this->eventoCompativelComHorarioAgendado(
                    $movimentacao,
                    $horarioExato
                )
            ) {
                $etapa = self::ETAPA_SAIDA_ORIGEM;
            } elseif (
                $tipoEvento === self::EVENTO_GEOFENCE_ENTER
                && !$temChegadaCliente
                && $distanciaDestino !== null
                && $distanciaDestino <= 250
            ) {
                if ($temSaidaOrigem) {
                    $etapa = self::ETAPA_CHEGADA_CLIENTE;
                } else {
                    // Pode ter chegado fora de ordem antes da saída ser classificada.
                    $deferir = true;
                }
            } elseif (
                $tipoEvento === self::EVENTO_GEOFENCE_EXIT
                && $temChegadaCliente
                && !$temSaidaCliente
                && $distanciaDestino !== null
                && $distanciaDestino > 300
            ) {
                $etapa = self::ETAPA_SAIDA_CLIENTE;
            } elseif (
                $tipoEvento === self::EVENTO_GEOFENCE_ENTER
                && $temSaidaCliente
                && !$temChegadaBase
                && $distanciaBase !== null
                && $distanciaBase <= 250
            ) {
                $etapa = self::ETAPA_CHEGADA_BASE;
            }

            if ($deferir) {
                return $this->agendarRetryOuFalhar(
                    $registro,
                    'Evento válido recebido fora de ordem; aguardando etapa anterior.',
                    (int) config(
                        'traccar.webhook.max_attempts',
                        20
                    )
                );
            }

            if ($etapa === null) {
                $this->finalizarComoIgnorado(
                    $registro,
                    $movimentacao->id,
                    $movimentacao->empresa_id,
                    'Evento não corresponde a uma transição válida da movimentação atual.'
                );

                return [
                    'http_status' => 200,
                    'status' => 'ignored',
                    'message' => 'Evento recebido, mas não corresponde a uma transição válida da movimentação atual.',
                    'event_record_id' => $registro->id,
                    'movimentacao_id' => $movimentacao->id,
                ];
            }

            $registro->fill([
                'empresa_id' => $movimentacao->empresa_id,
                'movimentacao_id' => $movimentacao->id,
                'etapa' => $etapa,
                'device_id' => $deviceId ?: null,
                'unique_id' => $uniqueId ?: null,
                'position_id' => $evento['positionId']
                    ?? ($posicao['id'] ?? null),
                'geofence_id' => $evento['geofenceId'] ?? null,
                'event_time' => $horarioExato,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'speed' => $velocidadeNos,
                'status' => TraccarWebhookEvent::STATUS_CLASSIFICADO,
                'next_retry_at' => null,
                'locked_at' => null,
                'last_error' => null,
            ]);
            $registro->save();

            Log::info('Traccar webhook: evento classificado e persistido.', [
                'event_record_id' => $registro->id,
                'traccar_event_id' => $registro->traccar_event_id,
                'movimentacao_id' => $movimentacao->id,
                'empresa_id' => $movimentacao->empresa_id,
                'etapa' => $etapa,
                'event_time' => $horarioExato->toDateTimeString(),
                'traccar_id_resolvido' => $identificadorUsado,
            ]);

            return [
                'http_status' => 200,
                'status' => 'accepted',
                'message' => 'Evento persistido e classificado para processamento pelo robô da frota.',
                'event_record_id' => $registro->id,
                'movimentacao_id' => $movimentacao->id,
                'etapa' => $etapa,
                'event_time' => $horarioExato->toIso8601String(),
            ];
        } catch (Throwable $e) {
            Log::error('Traccar webhook: falha ao classificar evento.', [
                'event_record_id' => $registro->id,
                'error' => $e->getMessage(),
            ]);

            return $this->agendarRetryOuFalhar(
                $registro,
                $e->getMessage(),
                (int) config(
                    'traccar.webhook.max_attempts',
                    20
                )
            );
        }
    }

    private function adquirirRegistroParaProcessamento(
        int $registroId
    ): ?TraccarWebhookEvent {
        $lockSeconds = max(
            30,
            (int) config(
                'traccar.webhook.processing_lock_seconds',
                120
            )
        );

        return DB::transaction(function () use (
            $registroId,
            $lockSeconds
        ) {
            $registro = TraccarWebhookEvent::query()
                ->whereKey($registroId)
                ->lockForUpdate()
                ->first();

            if (!$registro) {
                return null;
            }

            if (in_array($registro->status, [
                TraccarWebhookEvent::STATUS_CLASSIFICADO,
                TraccarWebhookEvent::STATUS_CONSUMIDO,
                TraccarWebhookEvent::STATUS_IGNORADO,
                TraccarWebhookEvent::STATUS_FALHOU,
            ], true)) {
                return null;
            }

            if (
                $registro->status === TraccarWebhookEvent::STATUS_PROCESSANDO
                && $registro->locked_at
                && Carbon::parse($registro->locked_at)
                    ->greaterThan(now()->subSeconds($lockSeconds))
            ) {
                return null;
            }

            $registro->status = TraccarWebhookEvent::STATUS_PROCESSANDO;
            $registro->locked_at = now();
            $registro->attempts = ((int) $registro->attempts) + 1;
            $registro->save();

            return $registro->fresh();
        }, 3);
    }

    private function liberarProcessamentosTravados(): void
    {
        $lockSeconds = max(
            30,
            (int) config(
                'traccar.webhook.processing_lock_seconds',
                120
            )
        );

        TraccarWebhookEvent::query()
            ->where('status', TraccarWebhookEvent::STATUS_PROCESSANDO)
            ->where('locked_at', '<', now()->subSeconds($lockSeconds))
            ->update([
                'status' => TraccarWebhookEvent::STATUS_RETRY,
                'next_retry_at' => now(),
                'locked_at' => null,
                'last_error' => 'Processamento anterior interrompido; liberado automaticamente para retry.',
                'updated_at' => now(),
            ]);
    }

    private function agendarRetryOuFalhar(
        TraccarWebhookEvent $registro,
        string $erro,
        int $maxAttempts
    ): array {
        $maxAttempts = max(1, $maxAttempts);

        if (((int) $registro->attempts) >= $maxAttempts) {
            $registro->status = TraccarWebhookEvent::STATUS_FALHOU;
            $registro->last_error = $erro;
            $registro->next_retry_at = null;
            $registro->locked_at = null;
            $registro->processed_at = now();
            $registro->save();

            return [
                'http_status' => 200,
                'status' => 'error',
                'message' => 'Evento não pôde ser classificado após o limite de tentativas. O robô de frota permanece como fallback.',
                'event_record_id' => $registro->id,
            ];
        }

        $baseSeconds = max(
            10,
            (int) config(
                'traccar.webhook.retry_seconds',
                60
            )
        );

        // Backoff progressivo limitado a 15 minutos.
        $delay = min(
            900,
            $baseSeconds * max(1, min(10, (int) $registro->attempts))
        );

        $registro->status = TraccarWebhookEvent::STATUS_RETRY;
        $registro->last_error = $erro;
        $registro->next_retry_at = now()->addSeconds($delay);
        $registro->locked_at = null;
        $registro->save();

        return [
            'http_status' => 202,
            'status' => 'retry',
            'message' => 'Evento persistido e agendado para nova tentativa.',
            'event_record_id' => $registro->id,
            'retry_at' => $registro->next_retry_at?->toIso8601String(),
        ];
    }

    private function finalizarComoIgnorado(
        TraccarWebhookEvent $registro,
        ?int $movimentacaoId,
        ?int $empresaId,
        string $motivo
    ): void {
        $registro->movimentacao_id = $movimentacaoId;
        $registro->empresa_id = $empresaId;
        $registro->status = TraccarWebhookEvent::STATUS_IGNORADO;
        $registro->last_error = $motivo;
        $registro->next_retry_at = null;
        $registro->locked_at = null;
        $registro->processed_at = now();
        $registro->save();
    }

    private function existeEtapaPendente(
        int $movimentacaoId,
        string $etapa
    ): bool {
        $persistido = TraccarWebhookEvent::query()
            ->where('movimentacao_id', $movimentacaoId)
            ->where('etapa', $etapa)
            ->whereIn('status', [
                TraccarWebhookEvent::STATUS_CLASSIFICADO,
                TraccarWebhookEvent::STATUS_CONSUMIDO,
            ])
            ->exists();

        if ($persistido) {
            return true;
        }

        // Compatibilidade com eventos que tenham entrado na versão anterior
        // imediatamente antes do deploy desta versão.
        return Cache::has(
            "traccar:frota:movimentacao:{$movimentacaoId}:{$etapa}"
        );
    }

    private function gerarDedupeKey(array $dados): string
    {
        $evento = $dados['event'] ?? [];
        $device = $dados['device'] ?? [];
        $position = $dados['position'] ?? [];

        $partes = [
            'v1',
            (string) ($evento['id'] ?? ''),
            (string) ($evento['type'] ?? ''),
            (string) ($device['id'] ?? ''),
            (string) ($device['uniqueId'] ?? ''),
            (string) ($evento['positionId'] ?? ($position['id'] ?? '')),
            (string) ($evento['geofenceId'] ?? ''),
            (string) ($evento['eventTime'] ?? ''),
            (string) ($position['deviceTime'] ?? ''),
            (string) ($position['fixTime'] ?? ''),
            (string) ($position['latitude'] ?? ''),
            (string) ($position['longitude'] ?? ''),
        ];

        return hash('sha256', implode('|', $partes));
    }

    private function buscarMovimentacaoAtivaPorTraccarId(
        string $traccarId
    ): ?MovimentacaoVeiculo {
        $veiculosIds = Veiculo::query()
            ->where('traccar_id', $traccarId)
            ->pluck('id');

        $funcionariosIds = Funcionario::query()
            ->where('traccar_id', $traccarId)
            ->pluck('id');

        if (
            $veiculosIds->isEmpty()
            && $funcionariosIds->isEmpty()
        ) {
            return null;
        }

        return MovimentacaoVeiculo::with([
            'veiculo',
            'motorista',
            'ajudantes',
        ])
            ->whereIn('status', [
                'agendado',
                'iniciado',
            ])
            ->where(function ($query) use (
                $veiculosIds,
                $funcionariosIds
            ) {
                if ($veiculosIds->isNotEmpty()) {
                    $query->whereIn('veiculo_id', $veiculosIds);
                }

                if ($funcionariosIds->isNotEmpty()) {
                    if ($veiculosIds->isNotEmpty()) {
                        $query->orWhereIn('motorista_id', $funcionariosIds);
                    } else {
                        $query->whereIn('motorista_id', $funcionariosIds);
                    }
                }
            })
            ->orderByRaw(
                "CASE WHEN status = 'iniciado' THEN 0 ELSE 1 END"
            )
            ->orderBy('data_hora_saida', 'asc')
            ->first();
    }

    private function buscarPosicaoEvento(
        int $empresaId,
            $positionId
    ): ?array {
        try {
            $credenciais = $this->resolverCredenciaisTraccar($empresaId);

            if (!$credenciais) {
                return null;
            }

            $request = Http::withHeaders([
                'Accept' => 'application/json',
            ])
                ->connectTimeout(5)
                ->timeout(10);

            if (!empty($credenciais['token'])) {
                $request = $request->withToken($credenciais['token']);
            } else {
                $request = $request->withBasicAuth(
                    $credenciais['username'],
                    $credenciais['password']
                );
            }

            $response = $request->get(
                rtrim($credenciais['base_url'], '/') . '/api/positions',
                ['id' => $positionId]
            );

            if ($response->failed()) {
                return null;
            }

            $posicoes = $response->json();

            return is_array($posicoes)
            && isset($posicoes[0])
            && is_array($posicoes[0])
                ? $posicoes[0]
                : null;
        } catch (Throwable $e) {
            Log::warning('Traccar webhook: erro ao buscar posição do evento.', [
                'empresa_id' => $empresaId,
                'position_id' => $positionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function resolverCredenciaisTraccar(int $empresaId): ?array
    {
        $config = TraccarConfig::where('empresa_id', $empresaId)->first();

        $baseUrl = $config?->base_url ?: config('traccar.base_url');
        $username = $config?->mail_user_name ?: config('traccar.auth.username');
        $password = $config?->password ?: config('traccar.auth.password');
        $token = $config?->token_traccar ?: config('traccar.auth.token');

        if (
            empty($baseUrl)
            || (
                empty($token)
                && (empty($username) || empty($password))
            )
        ) {
            return null;
        }

        return [
            'base_url' => $baseUrl,
            'username' => $username,
            'password' => $password,
            'token' => $token,
        ];
    }

    private function resolverHorarioEvento(
        array $evento,
        array $posicao
    ): Carbon {
        $candidatos = [
            $posicao['deviceTime'] ?? null,
            $posicao['fixTime'] ?? null,
            $evento['eventTime'] ?? null,
        ];

        foreach ($candidatos as $valor) {
            if (empty($valor)) {
                continue;
            }

            try {
                return Carbon::parse($valor)
                    ->setTimezone(
                        config('app.timezone', 'America/Bahia')
                    );
            } catch (Throwable $e) {
                // Tenta o próximo campo disponível.
            }
        }

        return now();
    }

    private function eventoCompativelComHorarioAgendado(
        MovimentacaoVeiculo $movimentacao,
        Carbon $horarioEvento
    ): bool {
        if (empty($movimentacao->data_hora_saida)) {
            return true;
        }

        try {
            $previsto = Carbon::parse($movimentacao->data_hora_saida);
            $inicioPermitido = $previsto->copy()->subMinutes(30);

            if (
                !$horarioEvento
                    ->copy()
                    ->startOfDay()
                    ->equalTo($previsto->copy()->startOfDay())
            ) {
                return false;
            }

            return $horarioEvento->greaterThanOrEqualTo($inicioPermitido);
        } catch (Throwable $e) {
            return false;
        }
    }

    private function calcularDistanciaMetros(
        $lat1,
        $lon1,
        $lat2,
        $lon2
    ): ?float {
        if (
            $lat1 === null
            || $lon1 === null
            || $lat2 === null
            || $lon2 === null
        ) {
            return null;
        }

        $raioTerra = 6371000;
        $latDe = deg2rad((float) $lat1);
        $lonDe = deg2rad((float) $lon1);
        $latPara = deg2rad((float) $lat2);
        $lonPara = deg2rad((float) $lon2);
        $deltaLat = $latPara - $latDe;
        $deltaLon = $lonPara - $lonDe;

        $angulo = 2 * asin(
                sqrt(
                    pow(sin($deltaLat / 2), 2)
                    + cos($latDe)
                    * cos($latPara)
                    * pow(sin($deltaLon / 2), 2)
                )
            );

        return $angulo * $raioTerra;
    }
}
