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

class MovimentacaoVeiculoController extends BaseController
{
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

        if ($request->veiculo_id) $query->where('veiculo_id', $request->veiculo_id);
        if ($request->data_inicio) $query->whereDate('data_hora_saida', '>=', $request->data_inicio);
        if ($request->data_fim) $query->whereDate('data_hora_saida', '<=', $request->data_fim);

        $records = $query->with(['veiculo', 'motorista', 'tipoMovimentacao'])
                         ->orderByDesc('data_hora_saida')
                         ->paginate(20);

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
            ['name' => 'data_inicio', 'label' => 'Data Início', 'type' => 'date'],
            ['name' => 'data_fim', 'label' => 'Data Fim', 'type' => 'date'],
        ];

        return view($this->listView, [
            'records' => $records,
            'filters' => $filters,
            'title' => $this->formTitle,
            'links' => $records->appends($request->all())->links(), 
            'headers' => $this->headers(),
            'fields' => $this->fields(),
            'filterUrl' => url($this->redirectPage),
            'newItemUrl' => url("{$this->redirectPage}/new"),
            'newItemText' => 'Nova Movimentação',
            'editUrl' => url("{$this->redirectPage}/edit"),
            'deleteUrl' => url("{$this->redirectPage}/delete"),
            'printUrl' => url("{$this->redirectPage}/imprimir")
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
    // 1. Definição obrigatória das variáveis de contexto
    $user_logged = session('user_logged');
    $usuario_id = $user_logged['id'] ?? $user_logged['usuario_id'];
    $empresa_id = $this->empresa_id;

    // 2. DEFINIÇÃO DA FILIAL (A lógica que você sugeriu)
    // Se a matriz é null no banco, forçamos o ID 1 para evitar erros de integridade
    $filial_id = $request->filial_id ?? ($user_logged['filial_id'] ?? 1);

    try {
        // 3. É CRÍTICO que o $filial_id esteja aqui no "use"
        return DB::transaction(function () use ($request, $usuario_id, $empresa_id, $filial_id) {
            
            $dados = $request->all();
            $dados['empresa_id'] = $empresa_id;
            $dados['filial_id'] = ($filial_id > 0) ? $filial_id : null;
            $dados['status'] = $request->filled('km_final') ? 'finalizado' : 'iniciado';

            if ($request->id > 0) {
                $registro = $this->model::findOrFail($request->id);
                $registro->update($dados);
                $registro->abastecimentos()->delete();
                $registro->despesas()->delete();
            } else {
                $registro = $this->model::create($dados);
            }

            // GRAVAÇÃO DOS ABASTECIMENTOS
            if ($request->has('abastecimentos')) {
                foreach ($request->abastecimentos as $item) {
                    if (!empty($item['produto_id']) && $item['quantidade'] > 0) {
                        
                        // Conversão de valores
                        $qtd = str_replace(',', '.', $item['quantidade']);
                        $unit = str_replace(',', '.', $item['unitario']);
                        $total = str_replace(',', '.', $item['total']);
                        $kmAbast = str_replace(',', '.', $item['km_abastecimento'] ?? 0);

                        // O erro morre aqui porque agora $filial_id existe no escopo
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
                            DespesaMovimentacao::create([
                                'movimentacao_id' => $registro->id,
                                'tipo'            => $desp['tipo'],
                                'valor'           => (float)$valorDespesa,
                                'descricao'       => $desp['descricao'] ?? '',
                            ]);
                        }
                    }
                }

                // ATUALIZA KM NO VEÍCULO
                if ($dados['status'] == 'finalizado' && $request->filled('km_final')) {
                    $v = Veiculo::find($request->veiculo_id);
                    if ($v) {
                        $v->quilometragem = $request->km_final; 
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

public function dashboard(Request $request)
{
    // 1. Definição do Período e Filtros
    $dataInicio = $request->data_inicio ?? date('Y-m-01');
    $dataFim = $request->data_fim ?? date('Y-m-t');
    $veiculo_id = $request->veiculo_id;

    // 2. Veículos em Manutenção (Lendo da tabela de manutenção)
    $veiculosManutencao = DB::table('manutencoes')
        ->join('veiculos', 'manutencoes.veiculo_id', '=', 'veiculos.id')
        ->where('manutencoes.empresa_id', $this->empresa_id)
        ->whereIn('manutencoes.status', ['em andamento', 'aguardando'])
        ->select('veiculos.placa', 'veiculos.modelo', 'manutencoes.status as manutencao_status')
        ->get();

    // 3. Base de Movimentação (KM e Litros)
    $queryMov = MovimentacaoVeiculo::where('empresa_id', $this->empresa_id)
        ->whereBetween('data_hora_saida', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59'])
        ->when($veiculo_id, function($q) use ($veiculo_id) { 
            return $q->where('veiculo_id', $veiculo_id); 
        });

    $totalKm = (clone $queryMov)->where('status', 'finalizado')->get()->sum(function($m){
        return ($m->km_final > 0) ? ($m->km_final - $m->km_inicial) : 0;
    });

    // 4. Abastecimentos (Litros e Valores)
    $queryAbastecimento = DB::table('abastecimentos_movimentacoes')
        ->join('movimentacoes_veiculos', 'abastecimentos_movimentacoes.movimentacao_id', '=', 'movimentacoes_veiculos.id')
        ->where('movimentacoes_veiculos.empresa_id', $this->empresa_id)
        ->whereBetween('movimentacoes_veiculos.data_hora_saida', [$dataInicio, $dataFim])
        ->when($veiculo_id, function($q) use ($veiculo_id) { 
            return $q->where('movimentacoes_veiculos.veiculo_id', $veiculo_id); 
        });

    $totalLitros = (clone $queryAbastecimento)->sum('quantidade');
    $totalCustoCombustivel = (clone $queryAbastecimento)->sum('valor_total');

    // 5. Financeiro (Trava de Duplicidade: Ignora categoria 6 - Combustível)
    $totalOutrasDespesas = DB::table('conta_pagars')
        ->where('empresa_id', $this->empresa_id)
        ->whereBetween('data_emissao', [$dataInicio, $dataFim])
        ->where('categoria_id', '!=', 6) 
        ->when($veiculo_id, function($q) use ($veiculo_id) {
            return $q->where('veiculo_id', $veiculo_id);
        })
        ->sum('valor_integral');

    $totalGeralDespesas = $totalCustoCombustivel + $totalOutrasDespesas;
    $mediaKmPorLitro = $totalLitros > 0 ? ($totalKm / $totalLitros) : 0;

    // 6. Gráficos (Histórico de 4 meses)
    $graficoMeses = []; $graficoKmData = []; $graficoFinanceiroData = [];
    for ($i = 3; $i >= 0; $i--) {
        $dataRef = date('Y-m', strtotime("-$i months", strtotime($dataInicio)));
        $graficoMeses[] = date('M/y', strtotime($dataRef));
        
        $graficoKmData[] = MovimentacaoVeiculo::where('empresa_id', $this->empresa_id)
            ->where('data_hora_saida', 'like', "$dataRef%")
            ->when($veiculo_id, function($q) use ($veiculo_id) { 
                return $q->where('veiculo_id', $veiculo_id); 
            })
            ->get()->sum(function($m){ return ($m->km_final - $m->km_inicial); });

        $graficoFinanceiroData[] = DB::table('conta_pagars')
            ->where('empresa_id', $this->empresa_id)
            ->where('data_emissao', 'like', "$dataRef%")
            ->when($veiculo_id, function($q) use ($veiculo_id) { 
                return $q->where('veiculo_id', $veiculo_id); 
            })
            ->sum('valor_integral');
    }

    // 7. Retorno da View com todas as variáveis
    return view('movimentacoes_veiculos.dashboard', [
        'title' => 'Painel Gerencial - Frota',
        'dataInicio' => $dataInicio,
        'dataFim' => $dataFim,
        'veiculoSelecionado' => $veiculo_id,
        'veiculos' => Veiculo::where('empresa_id', $this->empresa_id)->orderBy('placa')->get(),
        'veiculosManutencao' => $veiculosManutencao,
        'totalKm' => $totalKm,
        'totalLitros' => $totalLitros,
        'totalDespesas' => $totalGeralDespesas,
        'mediaKmPorLitro' => number_format($mediaKmPorLitro, 2, ',', '.'),
        'totalVeiculos' => Veiculo::where('empresa_id', $this->empresa_id)->count(),
        'emPercurso' => MovimentacaoVeiculo::where('empresa_id', $this->empresa_id)->where('status', 'iniciado')->count(),
        'disponiveis' => Veiculo::where('empresa_id', $this->empresa_id)->count() - MovimentacaoVeiculo::where('empresa_id', $this->empresa_id)->where('status', 'iniciado')->count(),
        'graficoMeses' => json_encode($graficoMeses),
        'graficoKm' => json_encode($graficoKmData),
        'graficoContas' => json_encode($graficoFinanceiroData),
        'graficoDespesas' => json_encode([$totalCustoCombustivel]),
        'pizzaLabels' => json_encode(['Combustível', 'Outras Despesas']),
        'pizzaValores' => json_encode([$totalCustoCombustivel, $totalOutrasDespesas]),
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

    // 1. Inicialize SEMPRE as variáveis no topo do loop
    $statusTexto = 'Disponível'; 
    $kmRodado = 0;
    $custoCombustivel = 0; 
    $custoArla = 0; 
    $litrosCombustivel = 0;

    // 2. Cálculo de KM
    $kmRodado = $movs->where('status', 'finalizado')->sum(function($m) { 
        return $m->km_final - $m->km_inicial; 
    });

    // 3. Verificação de Status atual
    if ($movs->where('status', 'iniciado')->count() > 0) {
        $statusTexto = 'Em Viagem';
    }

    // 4. Separação de custos (Abastecimentos)
    foreach($movs as $m) {
        foreach($m->abastecimentos as $abast) {
            if (str_contains(strtoupper($abast->produto->nome), 'ARLA')) {
                $custoArla += $abast->valor_total;
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
    // 5. Montagem do objeto (Certifique-se que o nome aqui bate com a View)
    if ($kmRodado > 0 || $custoCombustivel > 0 || $custoArla > 0) {
        $dadosRelatorio[] = (object)[
            'placa' => $v->placa, 
            'modelo' => $v->modelo,
            'status' => $statusTexto, // Agora a variável sempre existirá
            'km_rodado' => $kmRodado, 
            'custo_manutencao' => $custoManutencao,
            'custo_combustivel' => $custoCombustivel, 
            'custo_arla' => $custoArla, 
            'custo_total' => $custoCombustivel + $custoArla + $custoManutencao,
			'custo_por_km' => $custoPorKm,
            'media_kml' => $litrosCombustivel > 0 ? ($kmRodado / $litrosCombustivel) : 0,
        ];
    }
}
    $title = "Relatório Consolidado de Frota";
    return view('movimentacoes_veiculos.relatorio', compact('dadosRelatorio', 'dataInicio', 'dataFim', 'title'));
}

    private function baixar($id, $qtd, $filial) { (new StockMove())->pluStock($id, (float)$qtd * -1, -1, $filial); }

    public function imprimir($id) {
        $data = $this->model::with(['veiculo', 'motorista', 'abastecimentos.produto'])->findOrFail($id);
        return view('movimentacoes_veiculos.print', compact('data'));
    }
}