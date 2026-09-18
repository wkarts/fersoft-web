<?php

namespace App\Console\Commands;

use App\Models\MovimentacaoVeiculo;
use App\Models\TraccarConfig;
use App\Services\TraccarWebhookService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReconciliarTraccarEventos extends Command
{
    protected $signature = 'traccar:eventos:reconciliar
                            {--hours= : Janela máxima de busca em horas}';

    protected $description = 'Recupera do Traccar eventos de geofence que possam ter falhado no Event Forwarding';

    public function handle(TraccarWebhookService $webhookService): int
    {
        $horas = $this->option('hours') !== null
            ? (int) $this->option('hours')
            : (int) config('traccar.webhook.reconcile_hours', 48);

        $horas = max(1, min(168, $horas));

        $empresasIds = MovimentacaoVeiculo::query()
            ->whereIn('status', ['agendado', 'iniciado'])
            ->pluck('empresa_id')
            ->unique();

        if ($empresasIds->isEmpty()) {
            return self::SUCCESS;
        }

        $totalEventos = 0;
        $totalAceitos = 0;

        foreach ($empresasIds as $empresaId) {
            try {
                $credenciais = $this->resolverCredenciais((int) $empresaId);

                if (!$credenciais) {
                    continue;
                }

                $movimentacoes = MovimentacaoVeiculo::with([
                    'veiculo',
                    'motorista',
                ])
                    ->where('empresa_id', $empresaId)
                    ->whereIn('status', ['agendado', 'iniciado'])
                    ->get();

                if ($movimentacoes->isEmpty()) {
                    continue;
                }

                $devices = $this->getJson(
                    $credenciais,
                    '/api/devices'
                );

                if (!is_array($devices)) {
                    continue;
                }

                $devicesCollection = collect($devices);
                $deviceIds = [];

                foreach ($movimentacoes as $movimentacao) {
                    $identificadores = [];

                    if ($movimentacao->motorista_id) {
                        $motorista = $movimentacao->motorista;
                        if (!empty($motorista?->traccar_id)) {
                            $identificadores[] = (string) $motorista->traccar_id;
                        }
                    }

                    if (!empty($movimentacao->veiculo?->traccar_id)) {
                        $identificadores[] = (string) $movimentacao->veiculo->traccar_id;
                    }

                    foreach (array_unique($identificadores) as $identificador) {
                        $device = $devicesCollection->first(function ($item) use ($identificador) {
                            return (string) ($item['uniqueId'] ?? '') === $identificador
                                || (string) ($item['id'] ?? '') === $identificador;
                        });

                        if ($device && isset($device['id'])) {
                            $deviceIds[(int) $device['id']] = $device;
                        }
                    }
                }

                if (empty($deviceIds)) {
                    continue;
                }

                $inicioMovimentacoes = $movimentacoes
                    ->pluck('data_hora_saida')
                    ->filter()
                    ->map(function ($valor) {
                        try {
                            return Carbon::parse($valor)->subMinutes(30);
                        } catch (Throwable $e) {
                            return null;
                        }
                    })
                    ->filter()
                    ->sort()
                    ->first();

                $limiteJanela = now()->subHours($horas);

                if (!$inicioMovimentacoes || $inicioMovimentacoes->lt($limiteJanela)) {
                    $inicio = $limiteJanela;
                } else {
                    $inicio = $inicioMovimentacoes;
                }

                $fim = now()->addMinute();

                $eventos = $this->getJson(
                    $credenciais,
                    '/api/reports/events',
                    [
                        'deviceId' => array_keys($deviceIds),
                        'type' => ['geofenceEnter', 'geofenceExit'],
                        'from' => $inicio
                            ->copy()
                            ->utc()
                            ->format('Y-m-d\TH:i:s\Z'),
                        'to' => $fim
                            ->copy()
                            ->utc()
                            ->format('Y-m-d\TH:i:s\Z'),
                    ]
                );

                if (!is_array($eventos)) {
                    continue;
                }

                foreach ($eventos as $evento) {
                    $deviceId = isset($evento['deviceId'])
                        ? (int) $evento['deviceId']
                        : null;

                    if (!$deviceId || !isset($deviceIds[$deviceId])) {
                        continue;
                    }

                    $totalEventos++;

                    $resultado = $webhookService->receber([
                        'event' => $evento,
                        'device' => $deviceIds[$deviceId],
                    ]);

                    if (in_array(
                        $resultado['status'] ?? null,
                        ['accepted', 'duplicate'],
                        true
                    )) {
                        $totalAceitos++;
                    }
                }
            } catch (Throwable $e) {
                Log::warning('Traccar reconciliação: falha em uma empresa.', [
                    'empresa_id' => $empresaId,
                    'error' => $e->getMessage(),
                ]);

                // Uma empresa com falha não interrompe as demais.
                continue;
            }
        }

        $this->info(
            "Reconciliação Traccar: {$totalEventos} evento(s) lido(s), "
            . "{$totalAceitos} aceito(s)/já conhecido(s)."
        );

        return self::SUCCESS;
    }

    private function resolverCredenciais(int $empresaId): ?array
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

        return compact('baseUrl', 'username', 'password', 'token');
    }

    private function getJson(
        array $credenciais,
        string $path,
        array $query = []
    ): ?array {
        $request = Http::withHeaders([
            'Accept' => 'application/json',
        ])
            ->connectTimeout(5)
            ->timeout(20);

        if (!empty($credenciais['token'])) {
            $request = $request->withToken($credenciais['token']);
        } else {
            $request = $request->withBasicAuth(
                $credenciais['username'],
                $credenciais['password']
            );
        }

        $url = rtrim($credenciais['baseUrl'], '/') . $path;

        if (!empty($query)) {
            $partes = [];

            foreach ($query as $chave => $valor) {
                $valores = is_array($valor)
                    ? $valor
                    : [$valor];

                foreach ($valores as $item) {
                    $partes[] = rawurlencode((string) $chave)
                        . '='
                        . rawurlencode((string) $item);
                }
            }

            $url .= '?' . implode('&', $partes);
        }

        $response = $request->get($url);

        if ($response->failed()) {
            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : null;
    }
}
