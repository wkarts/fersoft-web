<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContratoEngenharia;
use App\Models\Cliente;
use App\Models\Funcionario;
use App\Models\Filial;
use App\Models\Cidade;
use App\Models\Servico;
use App\Models\Produto;

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

    /**
     * Atende às rotas GET '/' e GET '/list' definidas no web.php
     */
    public function list(Request $request)
    {
        $empresa_id = session('user_logged')['empresa'] ?? $this->empresa_id;

        $title = $this->formatString($this->listTitle, [
            'form_title' => $this->formTitle,
        ]);

        $query = $this->model::where('empresa_id', $empresa_id)->with(['cliente', 'filial']);

        // Filtro por Cliente
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        // Filtro por Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por Filial / Matriz (Matriz = null)
        if ($request->has('filial_id') && $request->filial_id !== '') {
            if ($request->filial_id == 'matriz') {
                $query->whereNull('filial_id');
            } else {
                $query->where('filial_id', $request->filial_id);
            }
        }

        $data = $query->orderBy('id', 'desc')->get();
        $clientes = Cliente::where('empresa_id', $empresa_id)->get();
        $filiaisLista = Filial::where('empresa_id', $empresa_id)->get();

        return view($this->listView, [
            'data' => $data,
            'lista' => $data,
            'title' => $title,
            'search' => $request->all(),
            'clientes' => $clientes,
            'filiaisLista' => $filiaisLista,
            'headers' => $this->headers(),
            'fields' => $this->fields(),
            'newItemUrl' => "{$this->redirectPage}/new",
            'newItemText' => 'Novo Contrato',
            'actionNew' => "{$this->redirectPage}/new",
            'actionEdit' => "{$this->redirectPage}/edit",
            'actionDelete' => "{$this->redirectPage}/delete",
        ]);
    }

    /**
     * Atende à rota POST '/list' para os filtros
     */
    public function filtro(Request $request)
    {
        return $this->list($request);
    }

    public function register($id = null)
{
    $empresa_id = session('user_logged')['empresa'] ?? $this->empresa_id;

    // Garante que $data seja uma instância do Model ou null (e não um número inteiro)
    $data = null;
    if ($id && is_numeric($id)) {
        $data = $this->model::where('empresa_id', $empresa_id)
            ->with('itens')
            ->find($id);
    }

    $title = $this->formatString($this->registerTitle, [
        'form_title' => $this->formTitle,
    ]);

    $clientes     = Cliente::where('empresa_id', $empresa_id)->orderBy('razao_social')->get();
    $filiaisLista = Filial::where('empresa_id', $empresa_id)->get();

    // 🎯 FILTRO ESTRITO: Puxa SOMENTE quem tem a função de Vendedor / Vendedora
        $vendedores = Funcionario::join('funcoes', 'funcoes.id', '=', 'funcionarios.funcao_id')
            ->where('funcionarios.empresa_id', $empresa_id)
            ->where(function($q) {
                $q->where('funcoes.nome', 'LIKE', '%Vendedor%')
                  ->orWhere('funcoes.nome', 'LIKE', '%Vendedora%');
            })
            ->select('funcionarios.id', 'funcionarios.nome', 'funcoes.nome as funcao_nome')
            ->orderBy('funcionarios.nome')
            ->get();

    $cidades  = Cidade::all();
    $servicos = Servico::where('empresa_id', $empresa_id)->get();
    $produtos = Produto::where('empresa_id', $empresa_id)->get();

    return view($this->registerView, [
        'data'         => $data,
        'title'        => $title,
        'actionSave'   => "{$this->redirectPage}/save",
        'actionUpdate' => "{$this->redirectPage}/update",
        'actionCancel' => $this->redirectPage,
        'filiaisLista' => $filiaisLista,
        'clientes'     => $clientes,
        'vendedores'   => $vendedores,
        'cidades'      => $cidades,
        'servicos'     => $servicos,
        'produtos'     => $produtos
    ]);
}
    public function save(Request $request)
    {
        try {
            $empresa_id = session('user_logged')['empresa'] ?? $this->empresa_id;
            $dados = $request->except(['itens']);
            
            if (isset($dados['valor_contrato'])) {
                $dados['valor_contrato'] = str_replace(['.', ','], ['', '.'], $dados['valor_contrato']);
            }

            if ($request->hasFile('arquivo_contrato')) {
                $file = $request->file('arquivo_contrato');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/contratos'), $fileName);
                $dados['arquivo_contrato'] = $fileName;
            }

            if ($request->filled('id')) {
                $contrato = ContratoEngenharia::findOrFail($request->id);
                $contrato->update($dados);
                $contrato_id = $contrato->id;
            } else {
                $dados['empresa_id'] = $empresa_id;
                $dados['usuario_id'] = session('user_logged')['id'] ?? null;
                $contrato = ContratoEngenharia::create($dados);
                $contrato_id = $contrato->id;
            }

            \Illuminate\Support\Facades\DB::table('contrato_eng_itens')->where('contrato_eng_id', $contrato_id)->delete();
            
            if ($request->has('itens')) {
                foreach ($request->itens as $item) {
                    $vl_unit = str_replace(['.', ','], ['', '.'], ($item['valor_unitario'] ?? '0'));
                    $qtd = str_replace(['.', ','], ['', '.'], ($item['quantidade_prevista'] ?? '0'));
                    
                    \Illuminate\Support\Facades\DB::table('contrato_eng_itens')->insert([
                        'contrato_eng_id' => $contrato_id,
                        'tipo_item' => $item['tipo_item'] ?? 'Servico',
                        'servico_id' => ($item['tipo_item'] == 'Servico') ? ($item['servico_id'] ?? null) : null,
                        'produto_id' => ($item['tipo_item'] == 'Locacao') ? ($item['produto_id'] ?? null) : null,
                        'quantidade_prevista' => $qtd,
                        'valor_unitario' => $vl_unit,
                        'valor_total' => $qtd * $vl_unit,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            session()->flash('mensagem_sucesso', 'Contrato e Itens salvos com sucesso!');
            return redirect($this->redirectPage);

        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao salvar: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }
  
  /**
     * Sobrescreve o edit para tratar corretamente a empresa logada
     */
    public function edit($id)
    {
        return $this->register($id);
    }

    /**
     * Sobrescreve o delete para tratar com segurança e excluir os itens vinculados
     */
    public function delete($id)
    {
        try {
            $empresa_id = session('user_logged')['empresa'] ?? $this->empresa_id;
            $contrato = ContratoEngenharia::where('empresa_id', $empresa_id)->findOrFail($id);

            // Deleta os itens do contrato primeiro
            \Illuminate\Support\Facades\DB::table('contrato_eng_itens')->where('contrato_eng_id', $id)->delete();
            
            // Deleta o contrato
            $contrato->delete();

            session()->flash('mensagem_sucesso', 'Contrato excluído com sucesso!');
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao excluir: ' . $e->getMessage());
        }

        return redirect($this->redirectPage);
    }

    /**
     * Tela de Histórico, Movimentações e Saldos do Contrato
     */
   public function detalhes($id)
    {
        $empresa_id = session('user_logged')['empresa'] ?? $this->empresa_id ?? 1;

        // 1. Busca o contrato
        $contratoEng = \DB::table('contratos_engenharia')
            ->where('id', (int) $id)
            ->first();

        if (!$contratoEng) {
            return redirect('/contratos')->with('mensagem_erro', 'Contrato não encontrado.');
        }

        // 2. Busca o cliente
        $cliente = null;
        if (!empty($contratoEng->cliente_id)) {
            $cliente = \DB::table('clientes')
                ->where('id', $contratoEng->cliente_id)
                ->first();
        }

        // 3. Busca as medições (Receitas)
        $medicoes = \DB::table('faturas_engenharia')
            ->where('contrato_eng_id', $contratoEng->id)
            ->get();

        // 4. Busca as Contas a Pagar lançadas para este contrato com as tabelas e nomes corretos
        $despesas = \DB::table('conta_pagars')
            ->leftJoin('fornecedors', 'fornecedors.id', '=', 'conta_pagars.fornecedor_id')
            ->leftJoin('categoria_contas', 'categoria_contas.id', '=', 'conta_pagars.categoria_id')
            ->where('conta_pagars.contrato_eng_id', $contratoEng->id)
            ->select(
                'conta_pagars.*',
                'fornecedors.razao_social as fornecedor_nome',
                'categoria_contas.nome as categoria_nome'
            )
            ->get();

        // 5. Busca a equipe alocada na obra
        $equipeAlocada = \DB::table('contrato_eng_funcionarios')
            ->join('funcionarios', 'funcionarios.id', '=', 'contrato_eng_funcionarios.funcionario_id')
            ->leftJoin('funcoes', 'funcoes.id', '=', 'funcionarios.funcao_id')
            ->where('contrato_eng_funcionarios.contrato_eng_id', $contratoEng->id)
            ->where('contrato_eng_funcionarios.status', 'Ativo')
            ->select(
                'contrato_eng_funcionarios.id as alocacao_id',
                'contrato_eng_funcionarios.data_alocacao',
                'funcionarios.nome',
                \DB::raw("COALESCE(funcoes.nome, 'Operacional') as cargo_nome")
            )
            ->get();

        // 6. Busca os funcionários para o modal
        $todosFuncionarios = \DB::table('funcionarios')
            ->leftJoin('funcoes', 'funcoes.id', '=', 'funcionarios.funcao_id')
            ->select('funcionarios.id', 'funcionarios.nome', \DB::raw("COALESCE(funcoes.nome, 'Operacional') as cargo_nome"))
            ->get();

        // 7. Consolidação da DRE do Contrato (Receitas vs Despesas)
        $totalReceitas = $medicoes->sum('valor_total');
        $totalDespesas = $despesas->sum('valor_integral') ?? $despesas->sum('valor') ?? 0;
        $lucroPrejuizo = $totalReceitas - $totalDespesas;
        $margemLucro   = $totalReceitas > 0 ? ($lucroPrejuizo / $totalReceitas) * 100 : 0;
        
        $valorTotal    = (float) ($contratoEng->valor_contrato ?? $contratoEng->valor_total ?? 0);
        $saldoAFaturar = $valorTotal - $totalReceitas;

        // 8. Retorno
        return view('contratos.detalhes', [
            'contratoEng'       => $contratoEng,
            'cliente'           => $cliente,
            'medicoes'          => $medicoes,
            'despesas'          => $despesas,
            'equipeAlocada'     => $equipeAlocada,
            'todosFuncionarios' => $todosFuncionarios,
            'valorTotal'        => $valorTotal,
            'totalReceitas'     => $totalReceitas,
            'totalDespesas'     => $totalDespesas,
            'lucroPrejuizo'     => $lucroPrejuizo,
            'margemLucro'       => $margemLucro,
            'saldoAFaturar'     => $saldoAFaturar,
            'title'             => 'DRE e Histórico do Contrato #' . ($contratoEng->numero_contrato ?? $contratoEng->id)
        ]);
    }
  
  // Método para Alocar Funcionário
public function alocarFuncionario(Request $request, $id)
{
    \DB::table('contrato_eng_funcionarios')->insert([
        'contrato_eng_id' => $id,
        'funcionario_id'  => $request->funcionario_id,
        'data_alocacao'   => $request->data_alocacao ?? date('Y-m-d'),
        'status'          => 'Ativo',
        'created_at'      => now(),
        'updated_at'      => now()
    ]);

    return redirect()->back()->with('mensagem_sucesso', 'Funcionário alocado com sucesso!');
}

// Método para Remover / Desalocar Funcionário
public function desalocarFuncionario($alocacao_id)
{
    \DB::table('contrato_eng_funcionarios')
        ->where('id', $alocacao_id)
        ->update([
            'status'           => 'Finalizado',
            'data_desalocacao' => date('Y-m-d'),
            'updated_at'       => now()
        ]);

    return redirect()->back()->with('mensagem_sucesso', 'Funcionário removido da obra!');
}
  
  
  public function dashboardDre(Request $request)
{
    $empresa_id = session('user_logged')['empresa'] ?? $this->empresa_id ?? 1;
    $statusFiltro = $request->input('status', 'Ativo');

    // 1. Consulta Base de Contratos com Cliente
    $query = \DB::table('contratos_engenharia as c')
        ->leftJoin('clientes as cl', 'cl.id', '=', 'c.cliente_id')
        ->where('c.empresa_id', $empresa_id);

    if ($statusFiltro != 'todos') {
        $query->where('c.status', $statusFiltro);
    }

    $contratos = $query->select(
        'c.id',
        'c.numero_contrato',
        'c.valor_contrato',
        'c.data_inicio',
        'c.data_fim',
        'c.status',
        'cl.razao_social as cliente_nome'
    )->get();

    // 2. Cálculo dos Resultados Financeiros
    $relatorio = $contratos->map(function ($contrato) {
        $receitas = \DB::table('faturas_engenharia')
            ->where('contrato_eng_id', $contrato->id)
            ->sum('valor_total') ?? 0;

        $despesas = \DB::table('conta_pagars')
            ->where('contrato_eng_id', $contrato->id)
            ->sum('valor_integral') ?? 0;

        $lucro  = $receitas - $despesas;
        $margem = $receitas > 0 ? ($lucro / $receitas) * 100 : 0;

        $diasRestantes = null;
        $proximoFim    = false;
        if (!empty($contrato->data_fim)) {
            $dataFim = \Carbon\Carbon::parse($contrato->data_fim);
            $hoje    = \Carbon\Carbon::now();
            $diasRestantes = (int) $hoje->diffInDays($dataFim, false);

            if ($diasRestantes >= 0 && $diasRestantes <= 30 && $contrato->status == 'Ativo') {
                $proximoFim = true;
            }
        }

        $contrato->total_receitas  = $receitas;
        $contrato->total_despesas  = $despesas;
        $contrato->lucro           = $lucro;
        $contrato->margem          = $margem;
        $contrato->proximo_fim     = $proximoFim;
        $contrato->dias_restantes  = $diasRestantes;
        $contrato->margem_critica  = ($receitas > 0 && $margem < 15) || $lucro < 0;

        return $contrato;
    });

    // 3. Totais Consolidados
    $totalContratosAtivos = \DB::table('contratos_engenharia')->where('empresa_id', $empresa_id)->where('status', 'Ativo')->count();
    $totalProximosFim     = $relatorio->where('proximo_fim', true)->count();
    $totalMargemCritica   = $relatorio->where('margem_critica', true)->count();
    $totalReceitasGeral   = $relatorio->sum('total_receitas');
    $totalDespesasGeral   = $relatorio->sum('total_despesas');
    $lucroGeral           = $totalReceitasGeral - $totalDespesasGeral;
    $margemGeral          = $totalReceitasGeral > 0 ? ($lucroGeral / $totalReceitasGeral) * 100 : 0;

    // 4. Monta os Dados Formatados para o Gráfico (Chart.js)
    $chartLabels   = [];
    $chartReceitas = [];
    $chartDespesas = [];

    foreach ($relatorio as $item) {
        $chartLabels[]   = "Contrato #" . ($item->numero_contrato ?? $item->id);
        $chartReceitas[] = round($item->total_receitas, 2);
        $chartDespesas[] = round($item->total_despesas, 2);
    }

    return view('contratos.dashboard_dre', [
        'relatorio'            => $relatorio,
        'totalContratosAtivos' => $totalContratosAtivos,
        'totalProximosFim'     => $totalProximosFim,
        'totalMargemCritica'   => $totalMargemCritica,
        'totalReceitasGeral'   => $totalReceitasGeral,
        'totalDespesasGeral'   => $totalDespesasGeral,
        'lucroGeral'           => $lucroGeral,
        'margemGeral'          => $margemGeral,
        'statusFiltro'         => $statusFiltro,
        'chartLabels'          => json_encode($chartLabels),
        'chartReceitas'        => json_encode($chartReceitas),
        'chartDespesas'        => json_encode($chartDespesas),
        'title'                => 'Dashboard & DRE Gerencial de Obras'
    ]);
}

// Método de Exportação em Excel nativo do Laravel
public function exportarExcelDashboard(Request $request)
{
    $empresa_id = session('user_logged')['empresa'] ?? $this->empresa_id ?? 1;
    $statusFiltro = $request->input('status', 'Ativo');

    $query = \DB::table('contratos_engenharia as c')
        ->leftJoin('clientes as cl', 'cl.id', '=', 'c.cliente_id')
        ->where('c.empresa_id', $empresa_id);

    if ($statusFiltro != 'todos') {
        $query->where('c.status', $statusFiltro);
    }

    $contratos = $query->select(
        'c.id', 'c.numero_contrato', 'c.valor_contrato',
        'c.data_inicio', 'c.data_fim', 'c.status', 'cl.razao_social as cliente_nome'
    )->get();

    $html = '<table border="1">
        <thead>
            <tr style="background-color: #212529; color: #ffffff;">
                <th>Nº Contrato</th>
                <th>Cliente</th>
                <th>Data Inicio</th>
                <th>Data Fim</th>
                <th>Status</th>
                <th>Valor Contrato (R$)</th>
                <th>Receitas Faturadas (R$)</th>
                <th>Custos/Despesas (R$)</th>
                <th>Lucro Liquido (R$)</th>
                <th>Margem (%)</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($contratos as $c) {
        $rec = \DB::table('faturas_engenharia')->where('contrato_eng_id', $c->id)->sum('valor_total') ?? 0;
        $des = \DB::table('conta_pagars')->where('contrato_eng_id', $c->id)->sum('valor_integral') ?? 0;
        $luc = $rec - $des;
        $mar = $rec > 0 ? ($luc / $rec) * 100 : 0;

        $html .= '<tr>
            <td>' . ($c->numero_contrato ?? $c->id) . '</td>
            <td>' . ($c->cliente_nome ?? 'N/A') . '</td>
            <td>' . (!empty($c->data_inicio) ? date('d/m/Y', strtotime($c->data_inicio)) : '-') . '</td>
            <td>' . (!empty($c->data_fim) ? date('d/m/Y', strtotime($c->data_fim)) : '-') . '</td>
            <td>' . $c->status . '</td>
            <td>' . number_format($c->valor_contrato, 2, ',', '.') . '</td>
            <td>' . number_format($rec, 2, ',', '.') . '</td>
            <td>' . number_format($des, 2, ',', '.') . '</td>
            <td>' . number_format($luc, 2, ',', '.') . '</td>
            <td>' . number_format($mar, 1, ',', '.') . '%</td>
        </tr>';
    }

    $html .= '</tbody></table>';

    $filename = "DRE_Obras_" . date('Y_m_d_H_i') . ".xls";
    return response($html)
        ->header('Content-Type', 'application/vnd.ms-excel')
        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
}
}