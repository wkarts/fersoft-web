<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Veiculo;
use App\Models\TraccarConfig;
use MrWolfGb\Traccar\Services\TraccarService;
use Illuminate\Support\Facades\Crypt;

class TraccarIntegrationController extends Controller
{
    private TraccarService $traccarService;

    protected $empresa_id;

    public function __construct(Request $request)
    {
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;

            if (!$this->empresa_id) {
                throw new \Exception('Empresa não identificada no contexto.');
            }

            $tenantConfig = TraccarConfig::where('empresa_id', $this->empresa_id)->first();

            /*
             * Prioridade das credenciais:
             *
             * 1. Configuração específica do tenant no MySQL;
             * 2. Configuração global do config/traccar.php, alimentada pelo .env.
             *
             * Dessa forma, tenants que possuem Traccar próprio continuam usando
             * suas credenciais do banco, enquanto os demais utilizam o padrão global.
             */
            $baseUrl = $tenantConfig?->base_url ?: config('traccar.base_url');
            $email = $tenantConfig?->mail_user_name ?: config('traccar.auth.username');
            $password = $tenantConfig?->password ?: config('traccar.auth.password');
            $token = $tenantConfig?->token_traccar ?: config('traccar.auth.token');

            if (empty($baseUrl)) {
                throw new \Exception(
                    'URL base do Traccar não configurada. Verifique o tenant no MySQL ou TRACCAR_BASE_URL no .env.'
                );
            }

            if (empty($token) && (empty($email) || empty($password))) {
                throw new \Exception(
                    'Autenticação do Traccar incompleta. Configure token ou usuário/senha no tenant ou no .env.'
                );
            }

            $this->traccarService = new TraccarService(
                baseUrl: $baseUrl,
                email: (string) $email,
                password: (string) $password,
                token: $token ?: null,
                headers: ['Content-Type' => 'application/x-www-form-urlencoded']
            );

            return $next($request);
        });
    }

    public function __construct2(Request $request)
    {
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;

            if (!$this->empresa_id) {
                throw new \Exception('Empresa não identificada no contexto.');
            }

            $tenantConfig = TraccarConfig::where('empresa_id', $this->empresa_id)->first();

            $baseUrl = $tenantConfig?->base_url ?: config('traccar.base_url');
            $email = $tenantConfig?->mail_user_name ?: config('traccar.auth.username');
            $password = $tenantConfig?->password ?: config('traccar.auth.password');
            $token = $tenantConfig?->token_traccar ?: config('traccar.auth.token');

            if (empty($baseUrl)) {
                throw new \Exception(
                    'URL base do Traccar não configurada. Verifique o tenant no MySQL ou TRACCAR_BASE_URL no .env.'
                );
            }

            if (empty($token) && (empty($email) || empty($password))) {
                throw new \Exception(
                    'Autenticação do Traccar incompleta. Configure token ou usuário/senha no tenant ou no .env.'
                );
            }

            $this->traccarService = new TraccarService(
                baseUrl: $baseUrl,
                email: (string) $email,
                password: (string) $password,
                token: $token ?: null,
                headers: ['Content-Type' => 'application/json']
            );

            return $next($request);
        });
    }

    public function listarDispositivos3()
    {
        try {
            $deviceRepo = $this->traccarService->deviceRepository();
            $dispositivos = $deviceRepo->fetchListDevices();

            \Log::info('Dispositivos retornados do Traccar:', $dispositivos);

            return response()->json([
                'status' => 'success',
                'raw_devices' => $dispositivos, // Verifique o conteúdo diretamente
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao buscar dispositivos no Traccar: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function listarDispositivos2()
    {
        try {
            // Teste para confirmar dispositivos no Traccar
            $dispositivos = $this->traccarService->deviceRepository()->fetchListDevices();

            return response()->json([
                'status' => 'success',
                'raw_devices' => $dispositivos, // Retorna os dispositivos crus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function listarDispositivos_original()
    {
        try {
            // Obter veículos do tenant
            $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->get();

            // IDs dos dispositivos para busca
            $traccarIds = $veiculos->pluck('traccar_id')->filter()->toArray();

            // Buscar dispositivos do Traccar
            $dispositivos = $this->traccarService->deviceRepository()->fetchListDevices();

            // Mapear dispositivos ao veículo
            $devicesWithVehicles = collect($dispositivos)->map(function ($dispositivo) use ($veiculos) {
                $veiculo = $veiculos->firstWhere('traccar_id', $dispositivo['id']);

                return [
                    'id' => $dispositivo['id'],
                    'name' => $dispositivo['name'],
                    'unique_id' => $dispositivo['uniqueId'],
                    'status' => $dispositivo['status'] ?? 'unknown',
                    'veiculo' => $veiculo ? [
                        'id' => $veiculo->id,
                        'placa' => $veiculo->placa,
                        'modelo' => $veiculo->modelo,
                        'motorista' => $veiculo->motoristaNome,
                    ] : null,
                ];
            });

            // Adicionar veículos sem dispositivos
            $veiculosSemDispositivo = $veiculos->filter(fn($v) => is_null($v->traccar_id))->map(function ($veiculo) {
                return [
                    'id' => null,
                    'name' => null,
                    'unique_id' => null,
                    'status' => 'sem dispositivo',
                    'veiculo' => [
                        'id' => $veiculo->id,
                        'placa' => $veiculo->placa,
                        'modelo' => $veiculo->modelo,
                        'motorista' => $veiculo->motoristaNome,
                    ],
                ];
            });

            // Combinar listas
            $resultado = $devicesWithVehicles->merge($veiculosSemDispositivo);

            return response()->json([
                'status' => 'success',
                'devices' => $resultado->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function listarDispositivos4()
    {
        try {
            $deviceRepo = $this->traccarService->deviceRepository();

            // Buscar todos os dispositivos no Traccar
            $allDevices = $deviceRepo->fetchListDevices();

            // Filtrar dispositivos associados aos veículos
            $veiculos = Veiculo::where('empresa_id', $this->empresa_id)
                ->whereNotNull('traccar_id')
                ->pluck('traccar_id')
                ->toArray();

            $filteredDevices = array_filter($allDevices, function ($device) use ($veiculos) {
                return in_array($device['id'], $veiculos);
            });

            return response()->json([
                'status' => 'success',
                'devices' => $filteredDevices,
                'raw_devices' => $allDevices, // Retorna todos para depuração, se necessário
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function listarDispositivos5()
    {
        try {
            $deviceRepo = $this->traccarService->deviceRepository();

            // Buscar todos os dispositivos no Traccar
            $allDevices = $deviceRepo->fetchListDevices()->toArray(); // Converte para array

            // Filtrar dispositivos associados aos veículos
            $veiculos = Veiculo::where('empresa_id', $this->empresa_id)
                ->whereNotNull('traccar_id')
                ->pluck('traccar_id')
                ->toArray();

            $filteredDevices = array_filter($allDevices, function ($device) use ($veiculos) {
                return in_array($device['id'], $veiculos);
            });

            return response()->json([
                'status' => 'success',
                'devices' => $filteredDevices,
                'raw_devices' => $allDevices, // Retorna todos para depuração, se necessário
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function listarDispositivos()
    {
        try {
            $tenantConfig = \App\Models\TraccarConfig::where('empresa_id', $this->empresa_id)->first();

            $baseUrl = $tenantConfig?->base_url ?: config('traccar.base_url');
            $username = $tenantConfig?->mail_user_name ?: config('traccar.auth.username');
            $password = $tenantConfig?->password ?: config('traccar.auth.password');
            $token = $tenantConfig?->token_traccar ?: config('traccar.auth.token');

            if (empty($baseUrl) || (empty($token) && (empty($username) || empty($password)))) {
                throw new \Exception(
                    'Configuração do Traccar incompleta. Configure a URL e token ou usuário/senha no tenant ou no .env.'
                );
            }

            // 1. Faz a busca DIRETO na API nativa do Traccar, ignorando o pacote com bug
            $url = rtrim($baseUrl, '/') . '/api/devices';

            $requestTraccar = \Illuminate\Support\Facades\Http::withHeaders([
                'Accept' => 'application/json',
            ]);

            if (!empty($token)) {
                $requestTraccar = $requestTraccar->withToken($token);
            } else {
                $requestTraccar = $requestTraccar->withBasicAuth($username, $password);
            }

            $response = $requestTraccar->get($url);

            if ($response->failed()) {
                throw new \Exception('Erro ao buscar na API Traccar: ' . $response->body());
            }

            // Converte a resposta em array
            $allDevices = $response->json();

            // 2. Busca os veículos do seu ERP que possuem rastreador
            $veiculos = \App\Models\Veiculo::where('empresa_id', $this->empresa_id)
                ->whereNotNull('traccar_id')
                ->pluck('traccar_id')
                ->map(fn($id) => (string) $id)
                ->toArray();

            /*
             * 3. Compatibilidade sem migração abrupta:
             *
             * - cadastros antigos podem ter salvo device.id;
             * - o padrão atual/canônico passa a ser device.uniqueId (IMEI).
             *
             * Enquanto existirem os dois formatos no banco, aceitamos ambos.
             */
            $filteredDevices = array_filter($allDevices, function ($device) use ($veiculos) {
                $deviceId = isset($device['id'])
                    ? (string) $device['id']
                    : '';

                $uniqueId = isset($device['uniqueId'])
                    ? (string) $device['uniqueId']
                    : '';

                return in_array($uniqueId, $veiculos, true)
                    || in_array($deviceId, $veiculos, true);
            });

            return response()->json([
                'status' => 'success',
                // array_values reorganiza a lista para o JavaScript do painel não se perder
                'devices' => array_values($filteredDevices),
                'raw_devices' => $allDevices,
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao listar dispositivos:', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function criarDispositivo(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unique_id' => 'required|string|max:255',
            'veiculo_id' => 'required|exists:veiculos,id',
        ]);

        try {
            $deviceRepo = $this->traccarService->deviceRepository();
            $device = $deviceRepo->createDevice(
                name: $request->input('name'),
                uniqueId: $request->input('unique_id')
            );

            $veiculo = Veiculo::findOrFail($request->input('veiculo_id'));

            /*
             * O restante da aplicação (frota:monitorar e mapa em tempo real)
             * trabalha preferencialmente com uniqueId/IMEI.
             *
             * Mantemos o valor informado no cadastro como fallback porque
             * algumas versões do pacote podem não devolver uniqueId no array.
             */
            $veiculo->traccar_id = $device['uniqueId']
                ?? $request->input('unique_id');

            $veiculo->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Dispositivo criado e vinculado ao veículo com sucesso.',
                'device' => $device,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function deletarDispositivo($id)
    {
        try {
            $deviceRepo = $this->traccarService->deviceRepository();

            /*
             * Descobre o uniqueId antes da exclusão para conseguir
             * desvincular tanto registros antigos (device.id) quanto
             * registros atuais (device.uniqueId/IMEI).
             */
            $uniqueId = null;

            try {
                $device = collect($deviceRepo->fetchListDevices())
                    ->first(function ($item) use ($id) {
                        return isset($item['id'])
                            && (string) $item['id'] === (string) $id;
                    });

                $uniqueId = $device['uniqueId'] ?? null;
            } catch (\Throwable $e) {
                // A exclusão continua mesmo se não conseguirmos consultar o device.
            }

            $deviceRepo->deleteDevice(device: $id);

            Veiculo::where('empresa_id', $this->empresa_id)
                ->where(function ($query) use ($id, $uniqueId) {
                    $query->where('traccar_id', (string) $id);

                    if (!empty($uniqueId)) {
                        $query->orWhere('traccar_id', (string) $uniqueId);
                    }
                })
                ->update(['traccar_id' => null]);

            return response()->json([
                'status' => 'success',
                'message' => 'Dispositivo removido e desvinculado do veículo com sucesso.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function monitorar_original()
    {
        try {
            // Recuperar as configurações do tenant com fallback para o .env
            $tenantConfig = TraccarConfig::where('empresa_id', $this->empresa_id)->first();
            $socketUrl = $tenantConfig?->socket_url ?: config('traccar.websocket_url');

            if (!$socketUrl) {
                throw new \Exception(
                    'URL do WebSocket não configurada no tenant nem em TRACCAR_SOCKET_URL.'
                );
            }

            // Teste de conexão ao WebSocket
            $sessionRepo = $this->traccarService->sessionRepository();
            $sessionInfo = $sessionRepo->fetchSessionInformation();

            return response()->json([
                'status' => 'success',
                'socket_url' => $socketUrl,
                'session_id' => $sessionInfo->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function monitorar()
    {
        try {
            $tenantConfig = TraccarConfig::where('empresa_id', $this->empresa_id)->first();

            $socketUrl = $tenantConfig?->socket_url ?: config('traccar.websocket_url');
            $token = $tenantConfig?->token_traccar ?: config('traccar.auth.token');

            if (empty($socketUrl) || empty($token)) {
                throw new \Exception(
                    'WebSocket/token do Traccar não configurados no tenant nem no .env.'
                );
            }

            // Ignoramos o pacote MrWolfGb que está quebrando o token!
            // E mandamos o WebSocket conectar com o token nativo direto na URL
            $urlComToken = "{$socketUrl}?token={$token}";

            return response()->json([
                'status' => 'success',
                'socket_url' => $urlComToken,
                'session_id' => 'conexao-direta', // Mockado para satisfazer o front-end
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function testeSessao()
    {
        try {
            $sessionInfo = $this->traccarService->sessionRepository()->fetchSessionInformation();

            return response()->json([
                'status' => 'success',
                'session' => $sessionInfo,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function posicoesTempoReal()
    {
        $empresaId = $this->empresa_id;
        $tenantConfig = \App\Models\TraccarConfig::where('empresa_id', $empresaId)->first();

        $baseUrl = $tenantConfig?->base_url ?: config('traccar.base_url');
        $username = $tenantConfig?->mail_user_name ?: config('traccar.auth.username');
        $password = $tenantConfig?->password ?: config('traccar.auth.password');
        $token = $tenantConfig?->token_traccar ?: config('traccar.auth.token');

        if (empty($baseUrl) || (empty($token) && (empty($username) || empty($password)))) {
            return response()->json([
                'erro' => 'Configuração do Traccar incompleta no tenant e no .env.'
            ], 400);
        }

        $baseUrl = rtrim($baseUrl, '/');

        // 1. Busca Dispositivos e Posições do Traccar (Veículos)
        $requestDevices = \Illuminate\Support\Facades\Http::withHeaders([
            'Accept' => 'application/json',
        ]);

        $requestPositions = \Illuminate\Support\Facades\Http::withHeaders([
            'Accept' => 'application/json',
        ]);

        if (!empty($token)) {
            $requestDevices = $requestDevices->withToken($token);
            $requestPositions = $requestPositions->withToken($token);
        } else {
            $requestDevices = $requestDevices->withBasicAuth($username, $password);
            $requestPositions = $requestPositions->withBasicAuth($username, $password);
        }

        $respDevices = $requestDevices->get($baseUrl . '/api/devices');
        $respPositions = $requestPositions->get($baseUrl . '/api/positions');

        $devices = $respDevices->successful() ? collect($respDevices->json()) : collect([]);
        $positions = $respPositions->successful() ? collect($respPositions->json()) : collect([]);

        // 2. Busca Viagens Ativas no ERP
        $viagensAtivas = \App\Models\MovimentacaoVeiculo::with(['veiculo', 'cliente', 'motorista'])
            ->where('empresa_id', $empresaId)
            ->whereIn('status', ['agendado', 'iniciado'])
            ->get();

        $frota = [];

        // Processa os Veículos do Traccar
        foreach ($positions as $pos) {
            $device = $devices->firstWhere('id', $pos['deviceId']);
            if ($device) {
                $velocidadeKm = ($pos['speed'] ?? 0) * 1.852;
                $uniqueIdTraccar = $device['uniqueId'];

                $attributes = $pos['attributes'] ?? [];
                $temIgnicao = array_key_exists('ignition', $attributes);
                $ignicao = $attributes['ignition'] ?? false;

                $placa = $device['name'] ?? 'Dispositivo ' . $device['id'];
                $motorista = 'Sem viagem vinculada';

                if ($velocidadeKm > 3) {
                    $statusCor = '#28a745'; // Verde (Em movimento)
                    $statusTexto = 'Em Movimento (' . round($velocidadeKm) . ' km/h)';
                } else {
                    if ($temIgnicao) {
                        $statusCor = $ignicao ? '#fd7e14' : '#dc3545';
                        $statusTexto = $ignicao ? 'Parado (Motor Ligado)' : 'Motor Desligado';
                    } else {
                        $statusCor = '#8e44ad'; // Roxo (App Celular)
                        $statusTexto = 'Parado (App Celular)';
                    }
                }

                $viagem = $viagensAtivas->first(function($mov) use ($uniqueIdTraccar) {
                    return ($mov->veiculo->traccar_id ?? null) == $uniqueIdTraccar
                        || ($mov->motorista->traccar_id ?? null) == $uniqueIdTraccar;
                });

                if ($viagem) {
                    if (!empty($viagem->veiculo->placa)) $placa = $viagem->veiculo->placa;
                    if (!empty($viagem->motorista->nome)) $motorista = $viagem->motorista->nome;
                    $clienteNome = $viagem->cliente->razao_social ?? $viagem->destino ?? 'Cliente';

                    if ($viagem->latitude_destino && $viagem->longitude_destino) {
                        $distancia = $this->calcularDistanciaMetros($pos['latitude'], $pos['longitude'], $viagem->latitude_destino, $viagem->longitude_destino);
                        if ($distancia !== null && $distancia <= 100) {
                            $statusCor = '#3699FF';
                            $statusTexto = "📍 No Cliente: <b>{$clienteNome}</b>";
                        }
                    }
                }

                $frota[] = [
                    'tipo_pino' => 'veiculo',
                    'id' => 'v_' . $device['id'],
                    'placa' => $placa,
                    'motorista' => $motorista,
                    'lat' => $pos['latitude'],
                    'lon' => $pos['longitude'],
                    'cor' => $statusCor,
                    'status' => $statusTexto,
                    'atualizacao' => \Carbon\Carbon::parse($pos['fixTime'])->setTimezone('America/Bahia')->format('d/m/Y H:i:s')
                ];
            }
        }

        // 3. Busca Locações Ativas (Status 0: Agendado/Novo, 1: Em uso/No cliente, 2: Aguardando retirada)
        $locacoes = \App\Models\Locacao::with([
            'itens.produto',
            'cliente',
            'fornecedor'
        ])
            ->where('empresa_id', $empresaId)
            ->whereIn('status', [0, 1, 2])
            ->get();

        $locacoesMapa = [];

        foreach ($locacoes as $loc) {
            // Define se pega a coordenada do Cliente ou do Fornecedor[cite: 2]
            $entidade = $loc->finalidade == 'coleta_fornecedor' ? $loc->fornecedor : $loc->cliente; //[cite: 2]

            $lat = $entidade->latitude ?? null;
            $lon = $entidade->longitude ?? null;

            // Se o registro possuir coordenadas válidas no cadastro
            if ($lat && $lon) {
                // Mapeamento de Cores por Status da Locação[cite: 2]
                switch ($loc->status) { //[cite: 2]
                    case 0:
                        $corStatus = '#3699FF'; // Azul: AGENDADO / NOVO
                        $statusDesc = 'AGENDADO / NOVO (Na Empresa/Pátio)';
                        break;
                    case 1:
                        $corStatus = '#1BC5BD'; // Verde Água: EM USO / NO CLIENTE
                        $statusDesc = 'EM USO / NO CLIENTE';
                        break;
                    case 2:
                        $corStatus = '#FFA800'; // Laranja: AGUARDANDO RETIRADA
                        $statusDesc = 'AGUARDANDO RETIRADA (No Cliente)';
                        break;
                    default:
                        $corStatus = '#7E8299';
                        $statusDesc = 'OUTRO';
                }

                // Lista patrimônios/equipamentos alocados[cite: 2]
                $itensDesc = [];
                if ($loc->itens) {
                    foreach ($loc->itens as $item) { //[cite: 2]
                        $p = $item->produto->nome ?? 'Equipamento'; //[cite: 2]
                        if (!empty($item->codigo_patrimonio)) { //[cite: 2]
                            $p .= " [Patr: {$item->codigo_patrimonio}]"; //[cite: 2]
                        }
                        $itensDesc[] = $p;
                    }
                }
                $strItens = count($itensDesc) > 0 ? implode(', ', $itensDesc) : 'Caçamba / Equipamento';

                $nomeLocal = $entidade->razao_social ?? 'Local Indefinido';

                $locacoesMapa[] = [
                    'id' => 'l_' . $loc->id,
                    'tipo_pino' => 'locacao',
                    'placa' => "Locação #{$loc->id}",
                    'produto' => $strItens, // <-- CHAVE ESSENCIAL para a busca e exibição lateral
                    'motorista' => "Local: {$nomeLocal}",
                    'lat' => (float)$lat,
                    'lon' => (float)$lon,
                    'cor' => $corStatus,
                    'status' => "<b>{$statusDesc}</b>",
                    'atualizacao' => \Carbon\Carbon::parse($loc->updated_at)->format('d/m/Y H:i')
                ];
            }
        }

        // Retorna a junção dos veículos em movimento com os pontos fixos de locação
        return response()->json(array_merge($frota, $locacoesMapa));
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

        $angulo = 2 * asin(sqrt(pow(sin($deltaLat / 2), 2) +
                cos($latDe) * cos($latPara) * pow(sin($deltaLon / 2), 2)));

        return $angulo * $raioTerra;
    }
}
