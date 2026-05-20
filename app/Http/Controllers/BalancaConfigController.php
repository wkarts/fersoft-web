<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BalancaConfig;
use App\Models\AdpIntegradorConfig;
use App\Models\BalancaConfigCamera;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use PhpSerial\PhpSerial;

class BalancaConfigController extends BaseController
{
    protected $model = BalancaConfig::class;
    protected $redirectPage = '/balancas';
    protected $formTitle = 'Configurações de Balança';
    protected $listView = 'balanca_config.list';
    protected $balancaView = 'balanca_config.balanca';
    protected $balancaTitle = 'Balança - Conferência de Pesagens';


    /**
     * Validações.
     */
    protected function rules($id = null): array
    {
        return [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'usuario_id' => ['nullable', 'exists:usuarios,id'],
            'descricao' => ['required', 'string', 'max:100'],
            'backend_server_address' => ['nullable', 'string', 'max:1000'],
            'modelo' => ['nullable', 'string', 'max:100'],
            //'marca' => ['required', 'string', 'max:100'],
            'port' => ['nullable', 'string', 'max:50'],
            //'port_custom' => ['nullable', 'string', 'max:50'],
            //'bits' => ['required', 'integer'],
            //'paridade' => ['required', 'string', 'max:10'],
            //'bits_stop' => ['required', 'integer'],
            //'cabecalho' => ['nullable', 'string', 'max:50'],
            //'rodape' => ['nullable', 'string', 'max:50'],
            //'timeout' => ['required', 'integer'],
            //'serie_number' => ['required', 'string', 'max:100', 'unique:balanca_configs,serie_number'],
            'serie_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('balanca_configs', 'serie_number')
                    ->ignore($id)
                    ->whereNull('deleted_at')
            ],
            'ativo' => ['required', 'boolean'],
            'tipo' => ['required', 'string'],
            'observacoes' => ['nullable', 'string'],
        ];
    }

    /**
     * Mensagens de erro personalizadas.
     */
    protected function messages(): array
    {
        return [
            'descricao.required' => 'A descrição é obrigatória.',
            'modelo.required' => 'O modelo é obrigatório.',
            'marca.required' => 'A marca é obrigatória.',
            'port.required' => 'A porta é obrigatória.',
            'bits.required' => 'O campo de bits é obrigatório.',
            'paridade.required' => 'A paridade é obrigatória.',
            'bits_stop.required' => 'O campo de bits de parada é obrigatório.',
            'serie_number.required' => 'O número de série é obrigatório.',
            'serie_number.unique' => 'O número de série deve ser único.',
            'timeout.required' => 'O tempo limite é obrigatório.',
            'ativo.required' => 'O status é obrigatório.',
            'tipo.required' => 'O tipo é obrigatório.',
        ];
    }

    /**
     * Exibe a lista de balanças do tenant.
     */
    public function list(Request $request)
    {
        $balancas = BalancaConfig::where('empresa_id', $this->empresa_id)
            ->paginate();

        return view($this->listView, [
            'balancas' => $balancas,
            'title' => $this->formTitle,
        ]);
    }

    /**
     * Formulário para cadastro ou edição.
     */
    public function register($id = null)
    {
        if ($id) {
            return $this->edit($id); // Redireciona para editar se existir ID.
        }

        $title = 'Nova Balança';

        return view($this->listView, [
            'title' => $title,
            'actionSave' => route('balancas.save'),
            'actionCancel' => $this->redirectPage,
        ]);
    }

    /**
     * Formulário para editar uma balança existente.
     */
    public function edit($id)
    {
        $balanca = BalancaConfig::findOrFail($id);
        $title = "Editar Balança - {$balanca->descricao}";

        return view($this->listView, [
            'balanca' => $balanca,
            'title' => $title,
            'actionSave' => route('balancas.save', $id),
            'actionCancel' => $this->redirectPage,
        ]);
    }

    /**
     * Salva ou atualiza uma balança.
     */
    public function save(Request $request, $id = null)
    {
        $integrador = (string) $request->input('integrador', 'legacy');
        if ($integrador === '') {
            $integrador = 'legacy';
        }

        // No modo ADP alguns campos do bloco legado ficam desabilitados na view.
        // Mesmo assim a tabela/validação ainda precisa de valores mínimos para cadastro.
        $mergeData = [
            'integrador' => $integrador,
        ];

        if ($integrador === 'adp') {
            if (!$request->has('ativo') || $request->input('ativo') === null || $request->input('ativo') === '') {
                $mergeData['ativo'] = 1;
            }

            if (!$request->filled('tipo')) {
                $mergeData['tipo'] = 'plataforma';
            }
        }

        $request->merge($mergeData);

        $validatedData = $request->validate($this->rules($id), $this->messages());
        $validatedData['empresa_id'] = $this->empresa_id;
        $validatedData['usuario_id'] = $this->usuario_id;
        $validatedData['integrador'] = $request->input('integrador', $validatedData['integrador'] ?? null);
        $validatedData['integrador_config_id'] = $request->input('integrador_config_id');
        $validatedData['adp_scale_uuid'] = $request->input('adp_scale_uuid');
        $validatedData['porta_serial'] = $request->input('porta_serial');
        $validatedData['baud_rate'] = $request->input('baud_rate');
        $cameraUuids = $this->normalizeAdpCameraUuids($request->input('adp_camera_uuids'));
        $validatedData['adp_camera_uuids'] = json_encode($cameraUuids, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $validatedData['quantidade_cameras'] = (int) ($request->input('quantidade_cameras', count($cameraUuids)));
        $validatedData['usa_cameras'] = $request->boolean('usa_cameras') || count($cameraUuids) > 0;

        if ($integrador === 'adp') {
            if (empty($validatedData['integrador_config_id'])) {
                return redirect()->back()->withInput()->with('mensagem_erro', 'Selecione a configuração global ADP.');
            }
            if (empty($validatedData['adp_scale_uuid'])) {
                return redirect()->back()->withInput()->with('mensagem_erro', 'Selecione uma balança ADP descoberta.');
            }
            $validatedData['backend_server_address'] = $validatedData['backend_server_address'] ?? '';
            $validatedData['port'] = $validatedData['port'] ?? ($validatedData['porta_serial'] ?? '');
            $validatedData['modelo'] = $validatedData['modelo'] ?? 'ADP';

            // No modo ADP o campo serie_number pode não existir no payload, pois o bloco legado
            // fica oculto/desabilitado. Nunca acessar a chave diretamente.
            $serieNumber = trim((string) ($validatedData['serie_number'] ?? ''));
            if ($serieNumber === '') {
                $serieNumber = 'ADP-' . strtoupper(substr(md5((string) $validatedData['adp_scale_uuid']), 0, 12));
            }
            $validatedData['serie_number'] = $serieNumber;
        } else {
            if (empty($validatedData['backend_server_address']) || empty($validatedData['modelo']) || empty($validatedData['port']) || empty($validatedData['serie_number'])) {
                return redirect()->back()->withInput()->with('mensagem_erro', 'No modo legado, backend, equipamento, porta e serial number são obrigatórios.');
            }
        }

        if (!empty($validatedData['integrador_config_id'])) {
            $configValida = AdpIntegradorConfig::where('empresa_id', $this->empresa_id)
                ->where('ativo', true)
                ->where('id', (int) $validatedData['integrador_config_id'])
                ->exists();
            if (!$configValida) {
                return redirect()->back()->withInput()->with('mensagem_erro', 'Configuração ADP inválida para esta empresa.');
            }
        }

        try {
            if ($id) {
                // Atualizar registro existente
                $balanca = BalancaConfig::findOrFail($id);
                $balanca->update($validatedData);
            } else {
                // Criar nova balança
                $balanca = BalancaConfig::create($validatedData);
            }

            $this->syncCamerasAdp($balanca, $validatedData['adp_camera_uuids'] ?? '[]');

            session()->flash('mensagem_sucesso', 'Configurações salvas com sucesso!');
            return redirect()->route('balancas.list');
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao salvar: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    private function normalizeAdpCameraUuids($value): array
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $raw = trim((string) ($value ?? ''));

            if ($raw === '') {
                return [];
            }

            $decoded = json_decode($raw, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $items = is_array($decoded) ? $decoded : [$decoded];
            } else {
                // Compatibilidade com a versão anterior da tela, que enviava CSV.
                $items = explode(',', $raw);
            }
        }

        return collect($items)
            ->map(function ($item) {
                if (is_array($item)) {
                    return $item['uuid'] ?? $item['id'] ?? $item['camera_uuid'] ?? '';
                }

                return $item;
            })
            ->map(function ($item) {
                return trim((string) $item);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function syncCamerasAdp(BalancaConfig $balanca, $cameraUuids): void
    {
        if (!Schema::hasTable('balanca_config_cameras')) {
            return;
        }

        BalancaConfigCamera::where('empresa_id', $this->empresa_id)
            ->where('balanca_config_id', $balanca->id)
            ->delete();

        $uuids = collect($this->normalizeAdpCameraUuids($cameraUuids));

        foreach ($uuids as $idx => $uuid) {
            BalancaConfigCamera::create([
                'empresa_id' => $this->empresa_id,
                'balanca_config_id' => $balanca->id,
                'camera_uuid' => $uuid,
                'camera_nome' => $uuid,
                'ordem' => $idx + 1,
                'ativo' => true,
                'metadata_json' => [],
            ]);
        }
    }

    /**
     * Exclui uma balança.
     */
    public function delete($id)
    {
        try {
            BalancaConfig::findOrFail($id)->delete();
            session()->flash('mensagem_sucesso', 'Balança excluída com sucesso!');
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao excluir: ' . $e->getMessage());
        }

        return redirect()->route('balancas.list');
    }

    public function lerPesoBalança(Request $request)
    {
        try {
            // Porta Serial
            $porta = $request->input('port') ?? 'COM1'; // Porta recebida do formulário
            $baudRate = 9600; // Taxa de transmissão padrão

            // Inicializa a conexão com a porta serial
            $serial = new Serial();
            $serial->deviceSet($porta);       // Define a porta
            $serial->confBaudRate($baudRate); // Configura a velocidade (baud rate)
            $serial->confParity("none");     // Paridade (padrão: none)
            $serial->confCharacterLength(8); // Comprimento do caractere
            $serial->confStopBits(1);        // Bits de parada
            $serial->deviceOpen();           // Abre a conexão

            // Aguarda dados da balança
            usleep(100000); // 100ms para estabilizar
            $dados = $serial->readPort(); // Lê os dados recebidos

            $serial->deviceClose(); // Fecha a conexão

            // Retorna os dados lidos
            return response()->json(['peso' => trim($dados)]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Lista portas seriais disponíveis.
     */
    /*
    public function listarPortas()
    {
        try {
            // Lista portas simuladas (para ambientes de teste)
            $portas = ['COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6'];
            return response()->json(['success' => true, 'portas' => $portas]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    */

    public function listarPortas()
    {
        try {
            $ports = [];

            // Verifica o sistema operacional
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows
                exec('mode', $output);
                foreach ($output as $line) {
                    if (preg_match('/COM\d+/i', $line, $matches)) {
                        $ports[] = $matches[0]; // Coleta apenas o nome da porta COM
                    }
                }
            } else {
                // Linux/MacOS
                exec('ls /dev/tty*', $output);
                foreach ($output as $line) {
                    if (preg_match('/\/dev\/tty(S|USB|ACM)\d+/', $line, $matches)) {
                        $ports[] = $matches[0]; // Coleta nomes como /dev/ttyUSB0
                    }
                }
            }

            return response()->json(['ports' => $ports], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Testa a comunicação com a balança.
     */
    public function testarBalanca(Request $request)
    {
        $porta = $request->input('porta');

        try {
            // Inicializa a conexão com a porta serial
            $serial = new PhpSerial(); // Corrigido
            $serial->deviceSet($porta); // Define a porta
            $serial->confBaudRate(9600); // Configurações padrão
            $serial->confParity("none");
            $serial->confCharacterLength(8);
            $serial->confStopBits(1);
            $serial->deviceOpen(); // Abre a conexão

            // Envia comando para teste
            $serial->sendMessage("TESTE_PESO");
            sleep(1); // Aguarda a resposta
            $peso = $serial->readPort(); // Lê os dados retornados
            $serial->deviceClose(); // Fecha a conexão

            return response()->json(['success' => true, 'peso' => $peso]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Exibe a interface de controle para balança selecionada.
     */
    public function showBalanca(Request $request)
    {
        // Carrega todas as balanças para seleção
        $balancas = BalancaConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->get();

        // Passa as balanças para a view
        return view($this->balancaView, [
            'balancas' => $balancas,
            'title' => $this->balancaTitle,
        ]);
    }


}
