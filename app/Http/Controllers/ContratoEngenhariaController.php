<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ContratoEngenharia;
use App\Models\ContratoEngItem;
use App\Models\ContratoEngFuncionario;
use App\Models\FaturaEngenharia;
use App\Models\Filial;
use App\Models\Funcionario;
use App\Models\Cidade;
use App\Models\Produto;
use App\Models\Servico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContratoEngenhariaController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->model = ContratoEngenharia::class;
        $this->redirectPage = '/contratos';
        $this->formTitle = 'Contrato de Locação / Serviços';
        $this->listView = 'contratos.list';
        $this->registerView = 'contratos.register';
    }

    protected function rules(): array
    {
        return [
            'cliente_id' => 'required',
            'valor_contrato' => 'required',
            'data_inicio' => 'required|date',
        ];
    }

    protected function messages(): array
    {
        return [
            'cliente_id.required' => 'O preenchimento do Cliente é obrigatório.',
            'valor_contrato.required' => 'O Valor do Contrato é obrigatório.',
            'data_inicio.required' => 'A Data de Início é obrigatória.',
        ];
    }

    protected function headers(): array
    {
        return ['Nº Contrato', 'Cliente', 'Filial / Matriz', 'Data Início', 'Valor Total (R$)', 'Status'];
    }

    protected function fields(): array
    {
        return ['numero_contrato', 'cliente_id', 'filial_id', 'data_inicio', 'valor_contrato', 'status'];
    }

    public function list(Request $request)
    {
        $empresaId = (int) $this->empresa_id;

        $query = ContratoEngenharia::query()
            ->where('empresa_id', $empresaId)
            ->with(['cliente', 'filial']);

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('filial_id')) {
            $request->filial_id === 'matriz'
                ? $query->whereNull('filial_id')
                : $query->where('filial_id', $request->filial_id);
        }

        $data = $query->orderByDesc('id')->get();
        $clientes = Cliente::where('empresa_id', $empresaId)->orderBy('razao_social')->get();
        $filiaisLista = Filial::where('empresa_id', $empresaId)->orderBy('descricao')->get();
        // Mantém o título histórico da tela; o controller continua no padrão BaseController.
        $title = 'Contratos de Locação / Serviços';

        return view('contratos.list', [
            'data' => $data,
            'lista' => $data,
            'clientes' => $clientes,
            'filiaisLista' => $filiaisLista,
            'search' => $request->all(),
            'title' => $title,
            'headers' => $this->headers(),
            'fields' => $this->fields(),
            'newItemUrl' => "{$this->redirectPage}/new",
            'newItemText' => 'Novo Contrato',
            'actionNew' => "{$this->redirectPage}/new",
            'actionEdit' => "{$this->redirectPage}/edit",
            'actionDelete' => "{$this->redirectPage}/delete",
        ]);
    }

    public function filtro(Request $request)
    {
        return $this->list($request);
    }

    public function register($id = null)
    {
        $empresaId = (int) $this->empresa_id;

        $data = null;
        if ($id) {
            $data = ContratoEngenharia::where('empresa_id', $empresaId)
                ->with('itens')
                ->findOrFail($id);

            $data->funcionario_id = $data->vendedor_id;
        }

        $vendedores = Funcionario::query()
            ->leftJoin('funcoes', 'funcoes.id', '=', 'funcionarios.funcao_id')
            ->where('funcionarios.empresa_id', $empresaId)
            ->where(function ($query) {
                $query->where('funcoes.nome', 'like', '%Vendedor%')
                    ->orWhere('funcoes.nome', 'like', '%Vendedora%');
            })
            ->select('funcionarios.id', 'funcionarios.nome', 'funcoes.nome as funcao_nome')
            ->orderBy('funcionarios.nome')
            ->get();

        $servicos = Servico::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        // 1. Resíduos e Sucatas: Traz todos os produtos ativos para a lista geral
        $produtosGerais = Produto::where('empresa_id', $empresaId)
            ->where(function ($q) {
                $q->where('inativo', 0)->orWhereNull('inativo');
            })
            ->orderBy('nome')
            ->get(['id', 'nome', 'unidade_venda']);

        // 2. Caçambas e Equipamentos (Locação): Busca inteligente
        $produtosLocacao = Produto::where('empresa_id', $empresaId)
            ->where(function ($q) {
                $q->where('inativo', 0)->orWhereNull('inativo');
            })
            ->where(function ($q) {
                // Procura se tem um valor de locação definido
                if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'valor_locacao')) {
                    $q->orWhere('valor_locacao', '>', 0);
                }
                // Procura se tem as flags de locação antigas
                if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'locacao')) {
                    $q->orWhere('locacao', 1)->orWhere('locacao', '1');
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'tipo_locacao')) {
                    $q->orWhere('tipo_locacao', 1)->orWhere('tipo_locacao', '1');
                }
                // Procura por palavras-chave no nome do produto
                $q->orWhere('nome', 'LIKE', '%CAÇAMBA%')
                    ->orWhere('nome', 'LIKE', '%CACAMBA%')
                    ->orWhere('nome', 'LIKE', '%CAIXA ESTACION%')
                    ->orWhere('nome', 'LIKE', '%CONTAINER%')
                    ->orWhere('nome', 'LIKE', '%ROLO%')
                    ->orWhere('nome', 'LIKE', '%EQUIPAMENTO%');
            })
            ->orderBy('nome')
            ->get(['id', 'nome', 'unidade_venda']);

        return view('contratos.register', [
            'data'            => $data,
            'clientes'        => Cliente::where('empresa_id', $empresaId)->orderBy('razao_social')->get(),
            'filiaisLista'    => Filial::where('empresa_id', $empresaId)->orderBy('descricao')->get(),
            'vendedores'      => $vendedores,
            'cidades'         => Cidade::orderBy('nome')->get(),
            'servicos'        => $servicos,
            'produtos'        => $produtosGerais,
            'produtosLocacao' => $produtosLocacao,
            'title'           => $data ? 'Editar Contrato' : 'Novo Contrato',
            'actionSave'      => "{$this->redirectPage}/save",
            'actionUpdate'    => "{$this->redirectPage}/update",
            'actionCancel'    => $this->redirectPage,
        ]);
    }

    public function save(Request $request)
    {
        if (!$this->validateRequest($request)) {
            return redirect()->back()->withInput();
        }

        DB::beginTransaction();

        try {
            $empresaId = (int) $this->empresa_id;
            $data = $request->except(['_token', 'itens', 'arquivo_contrato']);

            // Compatibilidade com o nome histórico do campo da view.
            $data['vendedor_id'] = $request->filled('funcionario_id')
                ? $request->input('funcionario_id')
                : null;
            unset($data['funcionario_id']);

            $data['valor_contrato'] = $this->money($request->valor_contrato);
            $data['valor_faturado'] = $data['valor_faturado'] ?? 0;
            $data['percentual_retencao'] = $this->money($request->input('percentual_retencao', 0));
            $data['filial_id'] = $request->filled('filial_id') ? $request->filial_id : null;

            if ($request->hasFile('arquivo_contrato') && $request->file('arquivo_contrato')->isValid()) {
                $dir = public_path('uploads/contratos');
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                $file = $request->file('arquivo_contrato');
                $name = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $file->getClientOriginalName());
                $file->move($dir, $name);
                $data['arquivo_contrato'] = $name;
            }

            if ($request->filled('id')) {
                $contrato = ContratoEngenharia::where('empresa_id', $empresaId)
                    ->findOrFail($request->id);
                $contrato->fill($data);
                $contrato->save();
            } else {
                $data['empresa_id'] = $empresaId;
                $data['usuario_id'] = $this->usuario_id;
                $contrato = ContratoEngenharia::create($data);
            }

            ContratoEngItem::where('contrato_eng_id', $contrato->id)->delete();

            foreach ((array) $request->input('itens', []) as $item) {
                $tipo = $item['tipo_item'] ?? 'Servico';
                $qtd = $this->decimal($item['quantidade_prevista'] ?? 1);
                $valor = $this->money($item['valor_unitario'] ?? 0);

                // Captura produto de resíduo ou de locação
                $produtoId = !empty($item['produto_id'])
                    ? $item['produto_id']
                    : (!empty($item['produto_id_locacao']) ? $item['produto_id_locacao'] : null);

                // Captura a unidade enviada pelo select do form (se não informada, infere pelo tipo)
                $unidade = !empty($item['unidade'])
                    ? strtoupper(trim($item['unidade']))
                    : ($tipo === 'Locacao' ? 'MES' : ($tipo === 'Frete' ? 'VIAGEM' : 'TON'));

                $itemCriado = ContratoEngItem::create([
                    'empresa_id'          => $empresaId,
                    'filial_id'           => $contrato->filial_id,
                    'usuario_id'          => $this->usuario_id,
                    'contrato_eng_id'     => $contrato->id,
                    'tipo_item'           => $tipo,
                    'servico_id'          => in_array($tipo, ['Servico', 'Frete', 'Residuo']) && !empty($item['servico_id']) ? $item['servico_id'] : null,
                    'produto_id'          => in_array($tipo, ['Locacao', 'Residuo']) ? $produtoId : null,
                    'quantidade_prevista' => $qtd > 0 ? $qtd : 1,
                    'unidade'             => $unidade,
                    'valor_unitario'      => $valor,
                    'valor_total'         => ($qtd > 0 ? $qtd : 1) * $valor,
                ]);

                // Se for locação com produto informado, sincroniza com o módulo de locações
                if ($tipo === 'Locacao' && !empty($itemCriado->produto_id)) {
                    $this->sincronizarComModuloLocacao($contrato, $itemCriado);
                }
            }

            DB::commit();

            return redirect('/contratos')->with('mensagem_sucesso', 'Contrato e itens salvos com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao salvar contrato de engenharia.', [
                'empresa_id' => $this->empresa_id,
                'usuario_id' => $this->usuario_id,
                'error'      => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with('mensagem_erro', 'Erro ao salvar: ' . $e->getMessage());
        }
    }


    public function update(Request $request, $id)
    {
        $request->merge(['id' => $id]);
        return $this->save($request);
    }

    public function edit($id)
    {
        return $this->register($id);
    }

    public function delete($id)
    {
        DB::beginTransaction();

        try {
            $contrato = ContratoEngenharia::where('empresa_id', $this->empresa_id)
                ->findOrFail($id);

            if (FaturaEngenharia::where('contrato_eng_id', $id)->exists()) {
                throw new \RuntimeException(
                    'O contrato possui medições/faturamentos vinculados e não pode ser excluído.'
                );
            }

            ContratoEngItem::where('contrato_eng_id', $id)->delete();
            ContratoEngFuncionario::where('contrato_eng_id', $id)->delete();
            $contrato->delete();

            DB::commit();
            return redirect('/contratos')->with('mensagem_sucesso', 'Contrato excluído com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect('/contratos')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function detalhes($id)
    {
        $empresaId = (int) $this->empresa_id;

        $contrato = ContratoEngenharia::where('empresa_id', $empresaId)
            ->with(['cliente', 'filial', 'itens.servico', 'itens.produto'])
            ->findOrFail($id);

        $medicoes = FaturaEngenharia::where('empresa_id', $empresaId)
            ->where('contrato_eng_id', $contrato->id)
            ->orderByDesc('id')
            ->get();

        $despesas = DB::table('conta_pagars')
            ->leftJoin('fornecedors', 'fornecedors.id', '=', 'conta_pagars.fornecedor_id')
            ->leftJoin('categoria_contas', 'categoria_contas.id', '=', 'conta_pagars.categoria_id')
            ->where('conta_pagars.empresa_id', $empresaId)
            ->where('conta_pagars.contrato_eng_id', $contrato->id)
            ->select(
                'conta_pagars.*',
                'fornecedors.razao_social as fornecedor_nome',
                'categoria_contas.nome as categoria_nome'
            )
            ->get();

        $equipeAlocada = DB::table('contrato_eng_funcionarios as cf')
            ->join('funcionarios as f', 'f.id', '=', 'cf.funcionario_id')
            ->leftJoin('funcoes as fn', 'fn.id', '=', 'f.funcao_id')
            ->where('cf.contrato_eng_id', $contrato->id)
            ->where('cf.status', 'Ativo')
            ->where('f.empresa_id', $empresaId)
            ->select('cf.id as alocacao_id', 'cf.data_alocacao', 'f.nome', DB::raw("COALESCE(fn.nome, 'Operacional') as cargo_nome"))
            ->get();

        $todosFuncionarios = DB::table('funcionarios as f')
            ->leftJoin('funcoes as fn', 'fn.id', '=', 'f.funcao_id')
            ->where('f.empresa_id', $empresaId)
            ->select('f.id', 'f.nome', DB::raw("COALESCE(fn.nome, 'Operacional') as cargo_nome"))
            ->orderBy('f.nome')
            ->get();

        $totalReceitas = (float) $medicoes->sum('valor_total');
        $totalDespesas = (float) $despesas->sum('valor_integral');
        $lucro = $totalReceitas - $totalDespesas;
        $valorTotal = (float) $contrato->valor_contrato;
        $margem = $totalReceitas > 0 ? ($lucro / $totalReceitas) * 100 : 0;

        return view('contratos.detalhes', [
            'contratoEng' => $contrato,
            'cliente' => $contrato->cliente,
            'medicoes' => $medicoes,
            'despesas' => $despesas,
            'equipeAlocada' => $equipeAlocada,
            'todosFuncionarios' => $todosFuncionarios,
            'valorTotal' => $valorTotal,
            'totalReceitas' => $totalReceitas,
            'totalDespesas' => $totalDespesas,
            'lucroPrejuizo' => $lucro,
            'margemLucro' => $margem,
            'saldoAFaturar' => $valorTotal - $totalReceitas,
            'title' => 'DRE e Histórico do Contrato #' . ($contrato->numero_contrato ?: $contrato->id),
        ]);
    }

    public function alocarFuncionario(Request $request, $id)
    {
        $contrato = ContratoEngenharia::where('empresa_id', $this->empresa_id)->findOrFail($id);

        $funcionario = Funcionario::where('empresa_id', $this->empresa_id)
            ->findOrFail($request->funcionario_id);

        ContratoEngFuncionario::create([
            'contrato_eng_id' => $contrato->id,
            'funcionario_id' => $funcionario->id,
            'data_alocacao' => $request->input('data_alocacao', date('Y-m-d')),
            'status' => 'Ativo',
        ]);

        return redirect()->back()->with('mensagem_sucesso', 'Funcionário alocado com sucesso!');
    }

    public function desalocarFuncionario($alocacaoId)
    {
        $alocacao = DB::table('contrato_eng_funcionarios as cf')
            ->join('contratos_engenharia as c', 'c.id', '=', 'cf.contrato_eng_id')
            ->where('cf.id', $alocacaoId)
            ->where('c.empresa_id', $this->empresa_id)
            ->select('cf.id')
            ->first();

        abort_if(!$alocacao, 404);

        DB::table('contrato_eng_funcionarios')
            ->where('id', $alocacaoId)
            ->update([
                'status' => 'Finalizado',
                'data_desalocacao' => date('Y-m-d'),
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('mensagem_sucesso', 'Funcionário removido da obra!');
    }

    public function dashboardDre(Request $request)
    {
        $status = $request->input('status', 'Ativo');

        $query = ContratoEngenharia::where('empresa_id', $this->empresa_id)
            ->with('cliente');

        if ($status !== 'todos') {
            $query->where('status', $status);
        }

        $contratos = $query->get();

        $relatorio = $contratos->map(function ($contrato) {
            $receitas = (float) FaturaEngenharia::where('contrato_eng_id', $contrato->id)->sum('valor_total');
            $despesas = (float) DB::table('conta_pagars')->where('contrato_eng_id', $contrato->id)->sum('valor_integral');
            $lucro = $receitas - $despesas;
            $margem = $receitas > 0 ? ($lucro / $receitas) * 100 : 0;

            $diasRestantes = null;
            $proximoFim = false;
            if ($contrato->data_fim) {
                $diasRestantes = now()->startOfDay()->diffInDays($contrato->data_fim, false);
                $proximoFim = $diasRestantes >= 0 && $diasRestantes <= 30 && $contrato->status === 'Ativo';
            }

            $contrato->cliente_nome = $contrato->cliente->razao_social
                ?? $contrato->cliente->nome
                ?? null;
            $contrato->total_receitas = $receitas;
            $contrato->total_despesas = $despesas;
            $contrato->lucro = $lucro;
            $contrato->margem = $margem;
            $contrato->proximo_fim = $proximoFim;
            $contrato->dias_restantes = $diasRestantes;
            $contrato->margem_critica = ($receitas > 0 && $margem < 15) || $lucro < 0;

            return $contrato;
        });

        $totalReceitas = $relatorio->sum('total_receitas');
        $totalDespesas = $relatorio->sum('total_despesas');
        $lucro = $totalReceitas - $totalDespesas;

        return view('contratos.dashboard_dre', [
            'relatorio' => $relatorio,
            'totalContratosAtivos' => ContratoEngenharia::where('empresa_id', $this->empresa_id)->where('status', 'Ativo')->count(),
            'totalProximosFim' => $relatorio->where('proximo_fim', true)->count(),
            'totalMargemCritica' => $relatorio->where('margem_critica', true)->count(),
            'totalReceitasGeral' => $totalReceitas,
            'totalDespesasGeral' => $totalDespesas,
            'lucroGeral' => $lucro,
            'margemGeral' => $totalReceitas > 0 ? ($lucro / $totalReceitas) * 100 : 0,
            'statusFiltro' => $status,
            'chartLabels' => $relatorio->map(fn ($c) => 'Contrato #' . ($c->numero_contrato ?: $c->id))->values()->toJson(),
            'chartReceitas' => $relatorio->pluck('total_receitas')->values()->toJson(),
            'chartDespesas' => $relatorio->pluck('total_despesas')->values()->toJson(),
            'title' => 'Dashboard & DRE Gerencial de Obras',
        ]);
    }

    public function exportarExcelDashboard(Request $request)
    {
        $response = $this->dashboardDreData($request);
        $rows = '';

        foreach ($response as $c) {
            $rows .= '<tr>'
                . '<td>' . e($c->numero_contrato ?: $c->id) . '</td>'
                . '<td>' . e(optional($c->cliente)->razao_social ?: 'N/A') . '</td>'
                . '<td>' . e(optional($c->data_inicio)->format('d/m/Y') ?: '-') . '</td>'
                . '<td>' . e(optional($c->data_fim)->format('d/m/Y') ?: '-') . '</td>'
                . '<td>' . e($c->status) . '</td>'
                . '<td>' . number_format((float) $c->valor_contrato, 2, ',', '.') . '</td>'
                . '<td>' . number_format((float) $c->total_receitas, 2, ',', '.') . '</td>'
                . '<td>' . number_format((float) $c->total_despesas, 2, ',', '.') . '</td>'
                . '<td>' . number_format((float) $c->lucro, 2, ',', '.') . '</td>'
                . '<td>' . number_format((float) $c->margem, 1, ',', '.') . '%</td>'
                . '</tr>';
        }

        $html = '<table border="1"><thead><tr>'
            . '<th>Nº Contrato</th><th>Cliente</th><th>Início</th><th>Fim</th><th>Status</th>'
            . '<th>Valor Contrato</th><th>Receitas</th><th>Despesas</th><th>Lucro</th><th>Margem</th>'
            . '</tr></thead><tbody>' . $rows . '</tbody></table>';

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="DRE_Obras_' . date('Y_m_d_H_i') . '.xls"');
    }

    private function dashboardDreData(Request $request)
    {
        $status = $request->input('status', 'Ativo');
        $query = ContratoEngenharia::where('empresa_id', $this->empresa_id)->with('cliente');

        if ($status !== 'todos') {
            $query->where('status', $status);
        }

        return $query->get()->map(function ($contrato) {
            $contrato->total_receitas = (float) FaturaEngenharia::where('contrato_eng_id', $contrato->id)->sum('valor_total');
            $contrato->total_despesas = (float) DB::table('conta_pagars')->where('contrato_eng_id', $contrato->id)->sum('valor_integral');
            $contrato->lucro = $contrato->total_receitas - $contrato->total_despesas;
            $contrato->margem = $contrato->total_receitas > 0
                ? ($contrato->lucro / $contrato->total_receitas) * 100
                : 0;

            return $contrato;
        });
    }

    private function sincronizarComModuloLocacao($contrato, $item)
    {
        try {
            // Texto usado para vincular e encontrar a locação
            $observacaoContrato = 'Gerado a partir do Contrato #' . ($contrato->numero_contrato ?: $contrato->id);

            // 1. Tenta encontrar se já existe o CABEÇALHO da locação para este contrato
            $locacao = DB::table('locacaos')
                ->where('empresa_id', $contrato->empresa_id)
                ->where('cliente_id', $contrato->cliente_id)
                ->where('observacao', $observacaoContrato)
                ->first();

            if ($locacao) {
                $locacaoId = $locacao->id;
                // Atualiza o total do cabeçalho
                DB::table('locacaos')->where('id', $locacaoId)->update([
                    'total' => DB::raw('total + ' . (float) $item->valor_total),
                    'updated_at' => now()
                ]);
            } else {
                // Se não existir, cria o CABEÇALHO da locação
                $locacaoId = DB::table('locacaos')->insertGetId([
                    'empresa_id'             => $contrato->empresa_id,
                    'cliente_id'             => $contrato->cliente_id,
                    'filial_id'              => $contrato->filial_id,
                    'usuario_id'             => $this->usuario_id,
                    'inicio'                 => $contrato->data_inicio ?: now()->format('Y-m-d'),
                    'fim'                    => $contrato->data_fim ?: '1969-12-31', // Data padrão do seu sistema quando não tem fim
                    'total'                  => $item->valor_total,
                    'observacao'             => $observacaoContrato,
                    'status'                 => 1,
                    'tipo'                   => 'cacamba',
                    'finalidade'             => 'locacao_cliente',
                    'faturado'               => 0,
                    'tipo_calculo'           => 'mes',
                    'valor_unitario_calculo' => $item->valor_unitario,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);
            }

            // 2. Insere ou Atualiza o PRODUTO na tabela secundária de itens
            DB::table('item_locacaos')->updateOrInsert(
                [
                    'locacao_id' => $locacaoId,
                    'produto_id' => $item->produto_id,
                ],
                [
                    'valor'      => $item->valor_unitario,
                    'observacao' => '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Aviso: Falha ao tentar sincronizar locação automaticamente: " . $e->getMessage());
        }
    }

    public function imprimir($id)
    {
        $empresaId = (int) $this->empresa_id;

        // 🚀 Mudamos o nome para $contratoObj para blindar o código
        $contratoObj = \App\Models\ContratoEngenharia::where('empresa_id', $empresaId)
            ->with(['cliente.cidade', 'filial', 'itens.servico', 'itens.produto'])
            ->findOrFail($id);

        $equipeAlocada = \Illuminate\Support\Facades\DB::table('contrato_eng_funcionarios as cf')
            ->join('funcionarios as f', 'f.id', '=', 'cf.funcionario_id')
            ->leftJoin('funcoes as fn', 'fn.id', '=', 'f.funcao_id')
            ->where('cf.contrato_eng_id', $contratoObj->id)
            ->where('cf.status', 'Ativo')
            ->select('f.nome', \Illuminate\Support\Facades\DB::raw("COALESCE(fn.nome, 'Operacional') as cargo_nome"))
            ->get();

        $empresa = \Illuminate\Support\Facades\DB::table('empresas')->where('id', $empresaId)->first();
        $configNota = \Illuminate\Support\Facades\DB::table('config_notas')->where('empresa_id', $empresaId)->first();

        $numeroExibicao = !empty($contratoObj->numero_contrato) ? $contratoObj->numero_contrato : str_pad($contratoObj->id, 4, '0', STR_PAD_LEFT);

        return view('contratos.print', [
            'dadosContrato'  => $contratoObj, // 🚀 AQUI: Garante que envia o objeto e não o $id
            'equipeAlocada'  => $equipeAlocada,
            'empresa'        => $empresa,
            'configNota'     => $configNota,
            'numeroExibicao' => $numeroExibicao,
            'title'          => 'Contrato #' . $numeroExibicao
        ]);
    }

    private function money($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return (float) str_replace(',', '.', str_replace('.', '', (string) $value));
    }

    private function decimal($value): float
    {
        return $this->money($value);
    }
}
