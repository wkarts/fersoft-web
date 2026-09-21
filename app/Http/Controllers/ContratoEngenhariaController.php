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
        $title = $this->formatString($this->listTitle, [
            'form_title' => $this->formTitle,
        ]);

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

            // A view histórica usa funcionario_id para o vendedor/responsável.
            // O schema consolidado usa vendedor_id: adaptamos no controller,
            // sem alterar a view nem sua mecânica.
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

        // Mantém o título histórico usado pela view, inclusive na edição.
        $title = $this->formatString($this->registerTitle, [
            'form_title' => $this->formTitle,
        ]);

        return view('contratos.register', [
            'data' => $data,
            'clientes' => Cliente::where('empresa_id', $empresaId)->orderBy('razao_social')->get(),
            'filiaisLista' => Filial::where('empresa_id', $empresaId)->orderBy('descricao')->get(),
            'vendedores' => $vendedores,
            'cidades' => Cidade::orderBy('nome')->get(),
            'servicos' => Servico::where('empresa_id', $empresaId)->orderBy('nome')->get(),
            'produtos' => Produto::where('empresa_id', $empresaId)->orderBy('nome')->get(),
            'title' => $title,
            'actionSave' => "{$this->redirectPage}/save",
            'actionUpdate' => "{$this->redirectPage}/update",
            'actionCancel' => $this->redirectPage,
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
                $tipo = ($item['tipo_item'] ?? 'Servico') === 'Locacao' ? 'Locacao' : 'Servico';
                $qtd = $this->decimal($item['quantidade_prevista'] ?? 0);
                $valor = $this->money($item['valor_unitario'] ?? 0);

                if ($qtd <= 0 || $valor < 0) {
                    continue;
                }

                ContratoEngItem::create([
                    'contrato_eng_id' => $contrato->id,
                    'tipo_item' => $tipo,
                    'servico_id' => $tipo === 'Servico' ? ($item['servico_id'] ?? null) : null,
                    'produto_id' => $tipo === 'Locacao' ? ($item['produto_id'] ?? null) : null,
                    'quantidade_prevista' => $qtd,
                    'valor_unitario' => $valor,
                    'valor_total' => $qtd * $valor,
                ]);
            }

            DB::commit();

            return redirect('/contratos')->with('mensagem_sucesso', 'Contrato e itens salvos com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erro ao salvar contrato de engenharia.', [
                'empresa_id' => $this->empresa_id,
                'usuario_id' => $this->usuario_id,
                'error' => $e->getMessage(),
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
