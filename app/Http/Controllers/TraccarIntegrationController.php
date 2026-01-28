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

            if (!$tenantConfig) {
                throw new \Exception('Configuração do Traccar não encontrada para o tenant.');
            }

            $this->traccarService = new TraccarService(
                baseUrl: $tenantConfig->base_url,
                email: $tenantConfig->mail_user_name,
                password: $tenantConfig->password, // Model já descriptografa
                token: $tenantConfig->token_traccar,
                headers: ['Content-Type' => 'application/json']
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

            if (!$tenantConfig) {
                throw new \Exception('Configuração do Traccar não encontrada para o tenant.');
            }

            $this->traccarService = new TraccarService(
                baseUrl: $tenantConfig->base_url,
                email: $tenantConfig->mail_user_name,
                password: $tenantConfig->password,
                token: $tenantConfig->token_traccar,
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
            $deviceRepo = $this->traccarService->deviceRepository();

            // Buscar todos os dispositivos
            $allDevices = $deviceRepo->fetchListDevices()->toArray();

            // Registrar no log os dispositivos retornados do Traccar
            \Log::info('Dispositivos do Traccar:', $allDevices);

            // Buscar veículos com traccar_id associados ao tenant
            $veiculos = Veiculo::where('empresa_id', $this->empresa_id)
                ->whereNotNull('traccar_id')
                ->pluck('traccar_id')
                ->toArray();

            // Registrar os IDs dos veículos para depuração
            \Log::info('Veículos com traccar_id:', $veiculos);

            // Filtrar dispositivos vinculados aos veículos
            $filteredDevices = array_filter($allDevices, function ($device) use ($veiculos) {
                return in_array($device['id'], $veiculos);
            });

            return response()->json([
                'status' => 'success',
                'devices' => $filteredDevices,
                'raw_devices' => $allDevices,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao listar dispositivos:', ['error' => $e->getMessage()]);

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
            $veiculo->traccar_id = $device['id'];
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
            $deviceRepo->deleteDevice(device: $id);

            Veiculo::where('traccar_id', $id)->update(['traccar_id' => null]);

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
            // Recuperar as configurações do tenant
            $tenantConfig = TraccarConfig::where('empresa_id', $this->empresa_id)->first();
            $socketUrl = $tenantConfig->socket_url;

            if (!$socketUrl) {
                throw new \Exception('URL do WebSocket não configurada para este tenant.');
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

            if (!$tenantConfig->socket_url) {
                throw new \Exception('URL do WebSocket não configurada para este tenant.');
            }

            $sessionRepo = $this->traccarService->sessionRepository();
            $sessionInfo = $sessionRepo->fetchSessionInformation();

            if (!$sessionInfo || empty($sessionInfo->id)) {
                throw new \Exception('Token de sessão inválido ou não encontrado.');
            }

            return response()->json([
                'status' => 'success',
                'socket_url' => "{$tenantConfig->socket_url}?session={$sessionInfo->id}",
                'session_id' => $sessionInfo->id,
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

}
