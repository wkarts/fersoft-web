<?php

namespace App\Http\Controllers;

use App\Models\MovimentacaoVeiculo;
use App\Models\Veiculo;
use App\Models\Funcionario;
use App\Models\TipoMovimentacao;
use App\Models\Produto;
use App\Models\Cliente;
use App\Models\Fornecedor;
use App\Models\AbastecimentoMovimentacao;
use App\Models\DespesaMovimentacao;
use App\Helpers\StockMove;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MovimentacaoVeiculoController extends BaseController
{

    use \App\Traits\GeocodeTrait;

    protected $model = MovimentacaoVeiculo::class;
    protected $resource = 'movimentacoes_veiculos';
    protected $table = 'movimentacoes_veiculos';
    protected $formTitle = 'Movimentações de Veículos';
    protected $listView = 'movimentacoes_veiculos.list';
    protected $registerView = 'movimentacoes_veiculos.register';
    protected $redirectPage = '/movimentacaoVeiculo';

    /* --- Métodos Obrigatórios do BaseController --- */
    public function rules(): array { return []; }
    public function messages(): array { return []; }

    protected function getTenantRecords()
    {
        return $this->applyFilialFilter(parent::getTenantRecords())
            ->with(['veiculo', 'motorista', 'tipoMovimentacao', 'cliente', 'fornecedor', 'abastecimentos'])
            ->orderByDesc('data_hora_saida');
    }

    protected function headers(): array
    {
        return [
            'Veículo', 'Motorista', 'Saída', 'Chegada',
            'Status', 'KM Ini.', 'KM Fim',
            'Total Abastec.',
            'Destino', 'Tipo',
            'Cliente / Fornecedor'
        ];
    }

    protected function fields(): array
    {
        return [
            'veiculo.placa', 'motorista.nome', 'data_saida_formatada', 'data_chegada_formatada',
            'status_formatado', 'km_inicial_formatado', 'km_final_formatado',
            'valor_total_abastecimentos_formatado',
            'destino', 'tipoMovimentacao.nome', 'entidade_nome'
        ];
    }

    public function index(Request $request)
    {
        $query = $this->model::where('empresa_id', $this->empresa_id);

        // Aplicação dos Filtros na Query
        if ($request->filled('veiculo_id')) {
            $query->where('veiculo_id', $request->veiculo_id);
        }
        if ($request->filled('motorista_id')) {
            $query->where('motorista_id', $request->motorista_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('data_inicio')) {
            $query->whereDate('data_hora_saida', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('data_hora_saida', '<=', $request->data_fim);
        }

        $records = $query->with(['veiculo', 'motorista', 'tipoMovimentacao'])
            ->orderByDesc('data_hora_saida')
            ->get(); // Mantido como get() ou paginate() conforme sua preferência

        // Montagem correta dos filtros para a View
        $filters = [
            [
                'name' => 'veiculo_id',
                'label' => 'Veículo',
                'type' => 'select',
                'placeholder' => 'Todos os Veículos',
                'options' => Veiculo::where('empresa_id', $this->empresa_id)->get()->map(function($v) {
                    return ['value' => $v->id, 'label' => $v->placa];
                })->toArray()
            ],
            [
                'name' => 'motorista_id',
                'label' => 'Motorista',
                'type' => 'select',
                'placeholder' => 'Todos os Motoristas',
                'options' => \App\Models\Funcionario::where('empresa_id', $this->empresa_id)->get()->map(function($f) {
                    return ['value' => $f->id, 'label' => $f->nome];
                })->toArray()
            ],
            [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'placeholder' => 'Todos os Status',
                'options' => [
                    ['value' => 'agendado', 'label' => 'Agendado'],
                    ['value' => 'iniciado', 'label' => 'Iniciado'],
                    ['value' => 'finalizado', 'label' => 'Finalizado'],
                    ['value' => 'cancelado', 'label' => 'Cancelado']
                ]
            ],
            ['name' => 'data_inicio', 'label' => 'Data Início', 'type' => 'date'],
            ['name' => 'data_fim', 'label' => 'Data Fim', 'type' => 'date'],
        ];

        return view($this->listView, [
            'records' => $records,
            'filters' => $filters,
            'title' => $this->formTitle,
            'headers' => $this->headers(),
            'fields' => $this->fields(),
            'filterUrl' => url($this->redirectPage),
            'newItemUrl' => url("{$this->redirectPage}/new"),
            'newItemText' => 'Nova Movimentação',
            'editUrl' => url("{$this->redirectPage}/edit"),
            'deleteUrl' => url("{$this->redirectPage}/delete"),
        ]);
    }

    public function edit($id)
    {
        $movimentacao = $this->model::findOrFail($id);
        if ($movimentacao->status == 'finalizado') {
            session()->flash('mensagem_erro', 'Esta movimentação já foi concluída e não pode ser alterada.');
            return redirect($this->redirectPage);
        }
        return $this->register($id);
    }

    public function register($id = null)
    {
        $data = $id ? $this->model::with(['abastecimentos.produto', 'despesas'])->findOrFail($id) : null;

        $combustiveis = Produto::where('empresa_id', $this->empresa_id)
            ->where(function($q) {
                $q->where('nome', 'LIKE', '%DIESEL%')
                    ->orWhere('nome', 'LIKE', '%ARLA%')
                    ->orWhere('nome', 'LIKE', '%GASOLINA%');
            })->get();

        return view($this->registerView, [
            'data' => $data,
            'title' => $this->formTitle,
            'actionSave' => "{$this->redirectPage}/save",
            'actionUpdate' => "{$this->redirectPage}/save",
            'actionCancel' => $this->redirectPage,
            'veiculos' => Veiculo::where('empresa_id', $this->empresa_id)->get(),
            'funcionarios' => Funcionario::where('empresa_id', $this->empresa_id)->get(),
            'tipos' => TipoMovimentacao::where('empresa_id', $this->empresa_id)->get(),
            'clientes' => Cliente::where('empresa_id', $this->empresa_id)->orderBy('razao_social')->get(),
            'fornecedores' => Fornecedor::where('empresa_id', $this->empresa_id)->orderBy('razao_social')->get(),
            'combustiveis' => $combustiveis,
        ]);
    }

    public function save(Request $request)
    {
        $user_logged = session('user_logged');
        $usuario_id = $user_logged['id'] ?? $user_logged['usuario_id'];
        $empresa_id = $this->empresa_id;

        // SE NÃO TIVER FILIAL OU FOR MATRIZ, DEFINE COMO NULL
        $filial_id = $request->filial_id ?? ($user_logged['filial_id'] ?? null);
        if ($filial_id <= 0) $filial_id = null;

        try {
            return DB::transaction(function () use ($request, $usuario_id, $empresa_id, $filial_id) {

                $dados = $request->all();
                $dados['empresa_id'] = $empresa_id;
                $dados['filial_id'] = $filial_id;

                // 1. Definição do Status e Herança Automática de KM (Efeito Cascata)
                if ($request->filled('status')) {
                    $dados['status'] = $request->status;
                } else {
                    if ($request->filled('km_final')) {
                        $dados['status'] = 'finalizado';
                    } elseif ($request->filled('km_inicial')) {
                        $dados['status'] = 'iniciado';
                    } else {
                        $dados['status'] = 'agendado';
                    }
                }

                // 🚀 EFEITO CASCATA DE KM: Se for um novo cadastro e o usuário não digitou o KM Inicial
                if (empty($request->id) && empty($request->filled('km_inicial')) && $request->filled('veiculo_id')) {
                    $ultimaMovimentacao = MovimentacaoVeiculo::where('empresa_id', $empresa_id)
                        ->where('veiculo_id', $request->veiculo_id)
                        ->whereIn('status', ['finalizado', 'iniciado'])
                        ->orderBy('data_hora_saida', 'desc')
                        ->first();

                    if ($ultimaMovimentacao && $ultimaMovimentacao->km_final > 0) {
                        $dados['km_inicial'] = $ultimaMovimentacao->km_final;
                    } else {
                        $veiculoObj = Veiculo::find($request->veiculo_id);
                        $dados['km_inicial'] = $veiculoObj->quilometragem ?? 0;
                    }
                }

                // 2. Mapeamento dos novos campos de horário no cliente
                $dados['data_hora_chegada_cliente'] = $request->data_hora_chegada_cliente ?? null;
                $dados['data_hora_saida_cliente']   = $request->data_hora_saida_cliente ?? null;

                if (empty($dados['latitude_destino']) || empty($dados['longitude_destino'])) {
                    if (!empty($dados['cliente_id'])) {
                        $cli = \App\Models\Cliente::find($dados['cliente_id']);
                        $dados['latitude_destino'] = $cli->latitude ?? null;
                        $dados['longitude_destino'] = $cli->longitude ?? null;
                    } elseif (!empty($dados['fornecedor_id'])) {
                        $forn = \App\Models\Fornecedor::find($dados['fornecedor_id']);
                        $dados['latitude_destino'] = $forn->latitude ?? null;
                        $dados['longitude_destino'] = $forn->longitude ?? null;
                    } elseif (!empty($dados['destino'])) {
                        $coords = $this->buscarCoordenadas($dados['destino']);
                        $dados['latitude_destino'] = $coords['latitude'] ?? null;
                        $dados['longitude_destino'] = $coords['longitude'] ?? null;
                    }
                }

                if ($request->id > 0) {
                    $registro = $this->model::findOrFail($request->id);
                    $registro->update($dados);
                    $registro->abastecimentos()->delete();
                    $registro->despesas()->delete();
                } else {
                    $registro = $this->model::create($dados);
                }

                // =========================================================================
                // 🚀 NOVO AJUSTE AQUI: SALVA OS MÚLTIPLOS AJUDANTES NA TABELA PIVÔ
                // =========================================================================
                if ($request->has('ajudantes_ids')) {
                    // Sincroniza o array de IDs recebidos do formulário
                    $registro->ajudantes()->sync($request->ajudantes_ids);
                } else {
                    // Se nenhum ajudante for selecionado, limpa as vinculações anteriores
                    $registro->ajudantes()->detach();
                }
                // =========================================================================

                // GRAVAÇÃO DOS ABASTECIMENTOS
                if ($request->has('abastecimentos')) {
                    foreach ($request->abastecimentos as $item) {
                        if (!empty($item['produto_id']) && $item['quantidade'] > 0) {

                            $qtd = str_replace(',', '.', $item['quantidade']);
                            $unit = str_replace(',', '.', $item['unitario']);
                            $total = str_replace(',', '.', $item['total']);
                            $kmAbast = str_replace(',', '.', $item['km_abastecimento'] ?? 0);

                            AbastecimentoMovimentacao::create([
                                'empresa_id'         => $empresa_id,
                                'usuario_id'         => $usuario_id,
                                'filial_id'          => $filial_id,
                                'movimentacao_id'    => $registro->id,
                                'produto_id'         => $item['produto_id'],
                                'tipo'               => $item['tipo'] ?? 'externo',
                                'data_abastecimento' => $item['data'],
                                'quantidade'         => (float)$qtd,
                                'valor_unitario'     => (float)$unit,
                                'valor_total'        => (float)$total,
                                'km_abastecimento'   => (float)$kmAbast,
                            ]);

                            if (isset($item['tipo']) && $item['tipo'] == 'interno') {
                                $this->baixar($item['produto_id'], (float)$qtd, $filial_id);
                            }
                        }
                    }
                }

                // GRAVAÇÃO DAS DESPESAS
                if ($request->has('despesas')) {
                    foreach ($request->despesas as $desp) {
                        if (!empty($desp['valor']) && $desp['valor'] > 0) {
                            $valorDespesa = str_replace(',', '.', $desp['valor']);

                            $novaDespesa = new DespesaMovimentacao();

                            $novaDespesa->empresa_id      = $empresa_id;
                            $novaDespesa->usuario_id      = $usuario_id;
                            $novaDespesa->filial_id       = $filial_id;
                            $novaDespesa->movimentacao_id = $registro->id;
                            $novaDespesa->tipo            = $desp['tipo'];
                            $novaDespesa->valor           = (float)$valorDespesa;
                            $novaDespesa->descricao       = $desp['descricao'] ?? '';

                            $novaDespesa->save();
                        }
                    }
                }

                // ATUALIZA KM NO VEÍCULO
                if ($dados['status'] == 'finalizado' && $request->filled('km_final')) {
                    $v = Veiculo::find($request->veiculo_id);
                    if ($v) {
                        $v->quilometragem = str_replace(',', '.', $request->km_final);
                        $v->save();
                    }
                }

                session()->flash('mensagem_sucesso', 'Movimentação salva com sucesso!');
                return redirect($this->redirectPage);
            });
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao salvar: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    // --- MÉTODOS ADICIONADOS PARA A AGENDA E CANCELAMENTO ---

    function agenda(Request $request)
    {
        // Permite navegar pelos meses passados e futuros
        $mesDesejado = $request->mes ?? date('Y-m');
        $dt = \Carbon\Carbon::parse($mesDesejado . '-01');

        $dataInicio = $dt->copy()->startOfMonth()->format('Y-m-d');
        $dataFim    = $dt->copy()->endOfMonth()->format('Y-m-d');
        $veiculoId  = $request->veiculo_id;

        $query = MovimentacaoVeiculo::where('empresa_id', $this->empresa_id)
            ->whereBetween('data_hora_saida', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);

        if ($veiculoId) {
            $query->where('veiculo_id', $veiculoId);
        }

        $totalRealizadas = (clone $query)->whereIn('status', ['finalizado', 'concluida'])->count();
        $totalCanceladas = (clone $query)->where('status', 'cancelado')->count();
        $totalAgendadas  = (clone $query)->where('status', 'agendado')->count();
        $totalEmPercurso = (clone $query)->where('status', 'iniciado')->count();

        $coletasRegistradas = (clone $query)->with(['veiculo', 'motorista', 'cliente'])->get();
        $coletasPorData = [];

        foreach ($coletasRegistradas as $c) {
            $dataKey = \Carbon\Carbon::parse($c->data_hora_saida)->format('Y-m-d');
            $coletasPorData[$dataKey][] = $c;
        }

        $primeiroDiaSemana = $dt->copy()->startOfMonth()->dayOfWeek;
        $diasNoMes = $dt->daysInMonth;
        $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->get();
        $title = "Agenda de Coletas Planejadas";

        // Navegação entre meses
        $mesAnterior = $dt->copy()->subMonth()->format('Y-m');
        $mesProximo  = $dt->copy()->addMonth()->format('Y-m');

        return view('movimentacoes_veiculos.agenda', compact(
            'title', 'totalRealizadas', 'totalCanceladas',
            'totalAgendadas', 'totalEmPercurso', 'coletasPorData',
            'primeiroDiaSemana', 'diasNoMes', 'dt', 'veiculos',
            'dataInicio', 'dataFim', 'veiculoId', 'mesAnterior', 'mesProximo'
        ));
    }

    public function registrarHorarioCliente(Request $request, $id)
    {
        try {
            $user_logged = session('user_logged');
            $usuarioIdLogado = $user_logged['id'] ?? $user_logged['usuario_id'];
            $isAdmin = $user_logged['adm'] ?? $user_logged['is_admin'] ?? false;

            $coleta = MovimentacaoVeiculo::with(['veiculo', 'motorista'])->where('empresa_id', $this->empresa_id)->findOrFail($id);

            $funcionarioMotorista = \App\Models\Funcionario::where('id', $coleta->motorista_id)->first();
            $isMotoristaDaColeta = ($funcionarioMotorista && $funcionarioMotorista->usuario_id == $usuarioIdLogado);

            if (!$isAdmin && !$isMotoristaDaColeta) {
                return response()->json([
                    'success' => false,
                    'mensagem' => 'Acesso negado: Somente o Motorista escalado ou Administradores podem registrar horários nesta coleta.'
                ], 403);
            }

            $tipo = $request->tipo;

            if ($tipo == 'saida_garagem') {
                $coleta->data_hora_saida_real = now();
                if ($coleta->status == 'agendado') {
                    $coleta->status = 'iniciado';
                }
            } elseif ($tipo == 'chegada') {
                $coleta->data_hora_chegada_cliente = now();
                if ($coleta->status == 'agendado') {
                    $coleta->status = 'iniciado';
                }
            } elseif ($tipo == 'saida') {
                $coleta->data_hora_saida_cliente = now();
            } elseif ($tipo == 'chegada_garagem') {
                $coleta->data_hora_chegada = now();
                $coleta->status = 'finalizado';

                // 🚀 CALCULA O KM FINAL COM RESUMO DE ROTA DO TRACCAR OU MATEMÁTICA
                $base = $coleta->filial_id
                    ? \Illuminate\Support\Facades\DB::table('filials')->where('id', $coleta->filial_id)->first()
                    : \Illuminate\Support\Facades\DB::table('config_notas')->where('empresa_id', $this->empresa_id)->first();
                $latBase = $base->latitude ?? null;
                $lonBase = $base->longitude ?? null;

                $latOrigem = $latBase;
                $lonOrigem = $lonBase;
                if ($coleta->tipo_partida == 'residencia' && $coleta->motorista) {
                    $latOrigem = $coleta->motorista->latitude_residencia ?? $latBase;
                    $lonOrigem = $coleta->motorista->longitude_residencia ?? $lonBase;
                }

                $calcDist = function($lat1, $lon1, $lat2, $lon2) {
                    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 0;
                    $raioTerra = 6371000;
                    $latDe = deg2rad($lat1); $lonDe = deg2rad($lon1);
                    $latPara = deg2rad($lat2); $lonPara = deg2rad($lon2);
                    $deltaLat = $latPara - $latDe; $deltaLon = $lonPara - $lonDe;
                    $angulo = 2 * asin(sqrt(pow(sin($deltaLat / 2), 2) + cos($latDe) * cos($latPara) * pow(sin($deltaLon / 2), 2)));
                    return $angulo * $raioTerra;
                };

                $kmPercorrido = 0;

                if ($coleta->veiculo && $coleta->veiculo->traccar_id) {
                    try {
                        $config = \App\Models\TraccarConfig::where('empresa_id', $this->empresa_id)->first();
                        if ($config && !empty($config->base_url)) {
                            $baseUrl = rtrim($config->base_url, '/');
                            $respDevices = \Illuminate\Support\Facades\Http::withBasicAuth($config->mail_user_name, $config->password)
                                ->get($baseUrl . '/api/devices');

                            if ($respDevices->successful()) {
                                $device = collect($respDevices->json())->firstWhere('uniqueId', $coleta->veiculo->traccar_id);
                                if ($device) {
                                    $idInterno = $device['id'];
                                    $from = \Carbon\Carbon::parse($coleta->data_hora_saida_real)->timezone('UTC')->format('Y-m-d\TH:i:s\Z');
                                    $to = \Carbon\Carbon::now()->timezone('UTC')->format('Y-m-d\TH:i:s\Z');

                                    $urlSummary = $baseUrl . "/api/reports/summary?deviceId={$idInterno}&from={$from}&to={$to}";
                                    $respSummary = \Illuminate\Support\Facades\Http::withBasicAuth($config->mail_user_name, $config->password)
                                        ->withHeaders(['Accept' => 'application/json'])->get($urlSummary);

                                    if ($respSummary->successful() && count($respSummary->json()) > 0) {
                                        $kmPercorrido = ($respSummary->json()[0]['distance'] ?? 0) / 1000;
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Erro Traccar Manual KM: " . $e->getMessage());
                    }
                }

                if ($kmPercorrido <= 0) {
                    $distIda = $calcDist($latOrigem, $lonOrigem, $coleta->latitude_destino, $coleta->longitude_destino);
                    $distVolta = $calcDist($coleta->latitude_destino, $coleta->longitude_destino, $latBase, $lonBase);
                    $kmPercorrido = (($distIda + $distVolta) / 1000) * 1.25;
                }

                $coleta->km_final = $coleta->km_inicial + round(max(0, $kmPercorrido), 2);

                if ($coleta->veiculo_id && $coleta->km_final > 0) {
                    $veiculoObj = \App\Models\Veiculo::find($coleta->veiculo_id);
                    if ($veiculoObj) {
                        $veiculoObj->quilometragem = $coleta->km_final;
                        $veiculoObj->save();
                    }
                }
            }

            $coleta->save();

            return response()->json(['success' => true, 'mensagem' => 'Horário e status atualizados com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'mensagem' => $e->getMessage()], 400);
        }
    }

    public function cancelar(Request $request, $id)
    {
        $movimentacao = MovimentacaoVeiculo::where('empresa_id', $this->empresa_id)->findOrFail($id);

        if ($movimentacao->status == 'finalizado') {
            session()->flash('mensagem_erro', 'Não é possível cancelar uma movimentação já finalizada.');
            return redirect()->back();
        }

        $movimentacao->status = 'cancelado';
        $movimentacao->motivo_cancelamento = $request->motivo_cancelamento ?? 'Cancelado pelo usuário';
        $movimentacao->data_cancelamento = now();
        $movimentacao->save();

        session()->flash('mensagem_sucesso', 'Coleta/Movimentação cancelada com sucesso!');
        return redirect()->back();
    }

    public function relatorioColetas(Request $request)
    {
        $dataInicio = $request->data_inicio ?? date('Y-m-01');
        $dataFim    = $request->data_fim ?? date('Y-m-t');
        $veiculoId  = $request->veiculo_id;
        $status     = $request->status;

        $query = MovimentacaoVeiculo::with(['veiculo', 'motorista', 'cliente'])
            ->where('empresa_id', $this->empresa_id)
            ->whereBetween('data_hora_saida', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);

        if ($veiculoId) {
            $query->where('veiculo_id', $veiculoId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $coletas = $query->orderBy('data_hora_saida', 'asc')->get();

        // Resumo/Métricas
        $totalRealizadas = $coletas->whereIn('status', ['finalizado', 'concluida'])->count();
        $totalCanceladas = $coletas->where('status', 'cancelado')->count();
        $totalAgendadas  = $coletas->where('status', 'agendado')->count();
        $totalEmCurso    = $coletas->where('status', 'iniciado')->count();

        $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->get();
        $title    = "Relatório Gerencial de Coletas";

        return view('movimentacoes_veiculos.relatorio_coletas', compact(
            'coletas', 'totalRealizadas', 'totalCanceladas',
            'totalAgendadas', 'totalEmCurso', 'veiculos',
            'dataInicio', 'dataFim', 'veiculoId', 'status', 'title'
        ));
    }

    public function eventosCalendario(Request $request)
    {
        $start = $request->start;
        $end   = $request->end;

        $coletas = MovimentacaoVeiculo::with(['veiculo', 'motorista', 'cliente'])
            ->where('empresa_id', $this->empresa_id)
            ->whereBetween('data_hora_saida', [$start, $end])
            ->get();

        $eventos = [];

        foreach ($coletas as $c) {
            $cor = match($c->status) {
                'agendado'               => '#ffc107', // Amarelo
                'iniciado'               => '#007bff', // Azul
                'finalizado', 'concluida' => '#28a745', // Verde
                'cancelado'              => '#dc3545', // Vermelho
                default                  => '#6c757d'
            };

            // Título formatado: "PLACA - CLIENTE" (ex: "ABC-1234 - Cliente X")
            $placaVeiculo = $c->veiculo->placa ?? 'Sem Placa';
            $nomeCliente   = $c->cliente->razao_social ?? $c->destino ?? '';
            $tituloEvento = $placaVeiculo . ($nomeCliente ? ' - ' . $nomeCliente : '');

            $eventos[] = [
                'id'       => $c->id,
                'title'    => $tituloEvento,
                'start'    => \Carbon\Carbon::parse($c->data_hora_saida)->format('Y-m-d\TH:i:s'),
                'color'    => $cor,
                'extendedProps' => [
                    'motorista' => $c->motorista->nome ?? 'Não informado',
                    'status'    => ucfirst($c->status),
                    'chegada'   => $c->data_chegada_cliente_formatada,
                    'saida'     => $c->data_saida_cliente_formatada,
                ]
            ];
        }

        return response()->json($eventos);
    }

    public function dashboard(Request $request)
    {
        $dataInicio = $request->data_inicio ?? date('Y-m-01');
        $dataFim = $request->data_fim ?? date('Y-m-t');
        $veiculo_id = $request->veiculo_id;

        // --- 1. Alertas operacionais ---
        $motoristasAlerta = \App\Models\Funcionario::where('empresa_id', $this->empresa_id)
            ->where('status_funcionario', 'Ativo')
            ->whereNotNull('vencimento_cnh')
            ->whereDate('vencimento_cnh', '<=', \Carbon\Carbon::now()->addDays(30))
            ->get();

        $veiculosManutencao = DB::table('manutencoes')
            ->join('veiculos', 'manutencoes.veiculo_id', '=', 'veiculos.id')
            ->where('manutencoes.empresa_id', $this->empresa_id)
            ->whereIn('manutencoes.status', ['em andamento', 'aguardando'])
            ->select('veiculos.placa', 'veiculos.modelo', 'manutencoes.status as manutencao_status')
            ->get();

        // --- 2. Cálculos do Período Atual ---
        $queryAbast = DB::table('abastecimentos_movimentacoes')
            ->join('movimentacoes_veiculos', 'abastecimentos_movimentacoes.movimentacao_id', '=', 'movimentacoes_veiculos.id')
            ->join('produtos', 'abastecimentos_movimentacoes.produto_id', '=', 'produtos.id')
            ->where('movimentacoes_veiculos.empresa_id', $this->empresa_id)
            ->whereBetween('movimentacoes_veiculos.data_hora_saida', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59'])
            ->when($veiculo_id, function($q) use ($veiculo_id) {
                return $q->where('movimentacoes_veiculos.veiculo_id', $veiculo_id);
            });

        $custoDiesel = (clone $queryAbast)->where('produtos.nome', 'NOT LIKE', '%ARLA%')->sum('valor_total');
        $totalLitrosDiesel = (clone $queryAbast)->where('produtos.nome', 'NOT LIKE', '%ARLA%')->sum('quantidade');
        $totalCustoArla = (clone $queryAbast)->where('produtos.nome', 'LIKE', '%ARLA%')->sum('valor_total');
        $totalLitrosArla = (clone $queryAbast)->where('produtos.nome', 'LIKE', '%ARLA%')->sum('quantidade');

        $custoManutencao = DB::table('conta_pagars')
            ->where('empresa_id', $this->empresa_id)
            ->whereBetween('data_emissao', [$dataInicio, $dataFim])
            ->where('categoria_id', '!=', 6)
            ->when($veiculo_id, function($q) use ($veiculo_id) {
                return $q->where('veiculo_id', $veiculo_id);
            })->sum('valor_integral');

        // Despesas de Viagem (Refeição, Pedágio)
        $custoViagem = DB::table('despesas_movimentacoes')
            ->join('movimentacoes_veiculos', 'despesas_movimentacoes.movimentacao_id', '=', 'movimentacoes_veiculos.id')
            ->where('movimentacoes_veiculos.empresa_id', $this->empresa_id)
            ->whereBetween('movimentacoes_veiculos.data_hora_saida', [$dataInicio, $dataFim])
            ->when($veiculo_id, function($q) use ($veiculo_id) {
                return $q->where('movimentacoes_veiculos.veiculo_id', $veiculo_id);
            })->sum('valor');

        $queryMov = \App\Models\MovimentacaoVeiculo::where('empresa_id', $this->empresa_id)
            ->whereBetween('data_hora_saida', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59'])
            ->when($veiculo_id, function($q) use ($veiculo_id) {
                return $q->where('veiculo_id', $veiculo_id);
            });

        $totalKm = (clone $queryMov)->where('status', 'finalizado')->get()->sum(function($m){
            return ($m->km_final > 0) ? ($m->km_final - $m->km_inicial) : 0;
        });

        $mediaKmL = $totalLitrosDiesel > 0 ? ($totalKm / $totalLitrosDiesel) : 0;

        // --- 3. Lógica do Gráfico de Histórico (Últimos 4 Meses) ---
        $graficoMeses = []; $graficoKm = []; $graficoFinanceiro = []; $graficoViagem = [];
        for ($i = 3; $i >= 0; $i--) {
            $mesReferencia = date('Y-m', strtotime("-$i months"));
            $graficoMeses[] = date('M/y', strtotime($mesReferencia));

            $graficoKm[] = \App\Models\MovimentacaoVeiculo::where('empresa_id', $this->empresa_id)
                ->where('data_hora_saida', 'like', "$mesReferencia%")
                ->when($veiculo_id, function($q) use ($veiculo_id) { return $q->where('veiculo_id', $veiculo_id); })
                ->get()->sum(function($m){ return ($m->km_final - $m->km_inicial); });

            $graficoFinanceiro[] = DB::table('conta_pagars')
                ->where('empresa_id', $this->empresa_id)
                ->where('data_emissao', 'like', "$mesReferencia%")
                ->when($veiculo_id, function($q) use ($veiculo_id) { return $q->where('veiculo_id', $veiculo_id); })
                ->sum('valor_integral');

            $graficoViagem[] = DB::table('despesas_movimentacoes')
                ->join('movimentacoes_veiculos', 'despesas_movimentacoes.movimentacao_id', '=', 'movimentacoes_veiculos.id')
                ->where('movimentacoes_veiculos.empresa_id', $this->empresa_id)
                ->where('movimentacoes_veiculos.data_hora_saida', 'like', "$mesReferencia%")
                ->when($veiculo_id, function($q) use ($veiculo_id) { return $q->where('movimentacoes_veiculos.veiculo_id', $veiculo_id); })
                ->sum('valor');
        }

        return view('movimentacoes_veiculos.dashboard', [
            'title' => 'Painel Gerencial de Frota',
            'dataInicio' => $dataInicio, 'dataFim' => $dataFim,
            'veiculoSelecionado' => $veiculo_id,
            'veiculos' => \App\Models\Veiculo::where('empresa_id', $this->empresa_id)->orderBy('placa')->get(),
            'totalKm' => $totalKm, 'mediaKmPorLitro' => number_format($mediaKmL, 2, ',', '.'),
            'totalLitros' => $totalLitrosDiesel, 'totalLitrosArla' => $totalLitrosArla,
            'custoDiesel' => $custoDiesel, 'totalCustoArla' => $totalCustoArla,
            'custoManutencao' => $custoManutencao, 'custoViagem' => $custoViagem,
            'totalDespesas' => $custoDiesel + $totalCustoArla + $custoManutencao + $custoViagem,
            'emPercurso' => (clone $queryMov)->where('status', 'iniciado')->count(),
            'disponiveis' => \App\Models\Veiculo::where('empresa_id', $this->empresa_id)->count() - (clone $queryMov)->where('status', 'iniciado')->count(),
            'motoristasAlerta' => $motoristasAlerta, 'veiculosManutencao' => $veiculosManutencao,
            // Gráficos agora povoados
            'graficoMeses' => json_encode($graficoMeses), 'graficoKm' => json_encode($graficoKm),
            'graficoContas' => json_encode($graficoFinanceiro), 'graficoDespesas' => json_encode($graficoViagem),
            'pizzaLabels' => json_encode(['Diesel', 'Arla 32', 'Manutenção', 'Despesas Viagem']),
            'pizzaValores' => json_encode([$custoDiesel, $totalCustoArla, $custoManutencao, $custoViagem]),
        ]);
    }


    public function relatorio(Request $request)
    {
        $dataInicio = $request->data_inicio ?: date('Y-m-01');
        $dataFim = $request->data_fim ?: date('Y-m-t');
        $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->orderBy('placa')->get();

        $dadosRelatorio = [];

        foreach ($veiculos as $v) {
            $movs = MovimentacaoVeiculo::with(['abastecimentos.produto', 'despesas'])
                ->where('veiculo_id', $v->id)
                ->whereBetween('data_hora_saida', [$dataInicio.' 00:00:00', $dataFim.' 23:59:59'])
                ->get();

            $statusTexto = 'Disponível';
            $kmRodado = 0;
            $custoCombustivel = 0;
            $custoArla = 0;
            $litrosCombustivel = 0;
            $litrosArla = 0;

            $kmRodado = $movs->where('status', 'finalizado')->sum(function($m) {
                return $m->km_final - $m->km_inicial;
            });

            if ($movs->where('status', 'iniciado')->count() > 0) {
                $statusTexto = 'Em Viagem';
            }

            foreach($movs as $m) {
                foreach($m->abastecimentos as $abast) {
                    if (str_contains(strtoupper($abast->produto->nome), 'ARLA')) {
                        $custoArla += $abast->valor_total;
                        $litrosArla += $abast->quantidade;
                    } else {
                        $custoCombustivel += $abast->valor_total;
                        $litrosCombustivel += $abast->quantidade;
                    }
                }
            }

            $custoManutencao = DB::table('conta_pagars')
                ->where('veiculo_id', $v->id)
                ->where('empresa_id', $this->empresa_id)
                ->whereBetween('data_emissao', [$dataInicio, $dataFim])
                ->sum('valor_integral');

            $custoTotalGeral = $custoCombustivel + $custoArla + $custoManutencao;
            $custoPorKm = ($kmRodado > 0) ? ($custoTotalGeral / $kmRodado) : 0;

            if ($kmRodado > 0 || $custoCombustivel > 0 || $custoArla > 0) {
                $dadosRelatorio[] = (object)[
                    'placa' => $v->placa,
                    'modelo' => $v->modelo,
                    'status' => $statusTexto,
                    'km_rodado' => $kmRodado,
                    'custo_manutencao' => $custoManutencao,
                    'custo_combustivel' => $custoCombustivel,
                    'litros_combustivel' => $litrosCombustivel,
                    'custo_arla' => $custoArla,
                    'litros_arla' => $litrosArla,
                    'custo_total' => $custoTotalGeral,
                    'custo_por_km' => $custoPorKm,
                    'media_kml' => $litrosCombustivel > 0 ? ($kmRodado / $litrosCombustivel) : 0,
                ];
            }
        }
        $title = "Relatório Consolidado de Frota";
        return view('movimentacoes_veiculos.relatorio', compact('dadosRelatorio', 'dataInicio', 'dataFim', 'title'));
    }

    private function baixar($id, $qtd, $filial) {
        (new StockMove())->pluStock($id, (float)$qtd * -1, -1, $filial);
    }

    public function imprimir($id) {
        $data = $this->model::with(['veiculo', 'motorista', 'abastecimentos.produto'])->findOrFail($id);
        return view('movimentacoes_veiculos.print', compact('data'));
    }

    // Envia o link do checklist manualmente para o motorista
    public function enviarChecklistWhatsApp($id)
    {
        $mov = MovimentacaoVeiculo::with(['motorista', 'veiculo'])->where('empresa_id', $this->empresa_id)->findOrFail($id);

        if (!$mov->motorista || empty($mov->motorista->celular && $mov->motorista->telefone)) {
            session()->flash('mensagem_erro', 'Motorista sem telefone cadastrado.');
            return redirect()->back();
        }

        $telefone = $mov->motorista->celular ?? $mov->motorista->telefone;
        $linkChecklist = url('/checklist/veiculo/' . $mov->id);

        $msg = "📋 *CHECKLIST DE PRÉ-VIAGEM*\n\nOlá, *{$mov->motorista->nome}*! Por favor, preencha o checklist do veículo *{$mov->veiculo->placa}* clicando no link abaixo antes ou logo no início da sua rota:\n\n👉 {$linkChecklist}";

        // Disparo centralizado pela Connect|API da empresa.
        app(\App\Utils\WhatsAppUtil::class)->sendMessage(
            $telefone,
            $msg,
            (int) $this->empresa_id
        );

        session()->flash('mensagem_sucesso', 'Link do checklist enviado com sucesso para o WhatsApp do motorista!');
        return redirect()->back();
    }

    // Exibe as respostas e fotos do checklist no ERP
    public function verChecklist($id)
    {
        $movimentacao = MovimentacaoVeiculo::with(['veiculo', 'motorista'])->where('empresa_id', $this->empresa_id)->findOrFail($id);

        $checklist = DB::table('checklists_movimentacoes')
            ->where('movimentacao_veiculo_id', $id)
            ->first();

        $fotos = [];
        if ($checklist) {
            $fotos = DB::table('checklist_fotos')
                ->where('checklist_movimentacao_id', $checklist->id)
                ->get();
        }

        $title = "Detalhes do Checklist - Veículo: " . ($movimentacao->veiculo->placa ?? '');
        return view('movimentacoes_veiculos.ver_checklist', compact('movimentacao', 'checklist', 'fotos', 'title'));
    }
}
