<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoriaConta;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Utils\ContaEmpresaUtil;

class ApuracaoController extends Controller
{
    protected $empresa_id = null;
    protected $util;

    public function __construct(ContaEmpresaUtil $util){
        $this->util = $util;
        $this->middleware(function ($request, $next) {
            $sessionData = session('user_logged');
            if(!$sessionData) return redirect("/login");
            $this->empresa_id = $sessionData['empresa_id'] ?? $request->empresa_id ?? 2;
            return $next($request);
        });
    }

    public function index(Request $request)
{
    $mes = (int) ($request->mes ?? date('m'));
    $ano = (int) ($request->ano ?? date('Y'));
    $regime = $request->regime ?? 'competencia';
    $filial_id = $request->filled('filial_id') ? $request->filial_id : 'todos';

    $colDataRec = $regime == 'caixa' ? 'data_recebimento' : 'data_vencimento';
    $colDataPag = $regime == 'caixa' ? 'data_pagamento' : 'data_emissao';
    $colValRec = $regime == 'caixa' ? 'valor_recebido' : 'valor_integral';
    $colValPag = $regime == 'caixa' ? 'valor_pago' : 'valor_integral';

    $mesAnterior = $mes == 1 ? 12 : $mes - 1;
    $anoAnterior = $mes == 1 ? $ano - 1 : $ano;

    // --- CMV ---
    $estoqueInicial = DB::table('estoque_mensal_fechamentos')->where('empresa_id', $this->empresa_id)
        ->where('mes', $mesAnterior)->where('ano', $anoAnterior)->sum(DB::raw('quantidade * valor_unitario_custo'));

    $comprasDoMes = DB::table('item_compras as ic')->join('compras as c', 'c.id', '=', 'ic.compra_id')
        ->join('produtos as p', 'p.id', '=', 'ic.produto_id')
        ->where('c.empresa_id', $this->empresa_id)->whereIn('p.tipo_item', ['00', '01', '04'])
        ->whereMonth('c.created_at', $mes)->whereYear('c.created_at', $ano)->sum(DB::raw('ic.quantidade * ic.valor_unitario'));

    $estoqueFinal = DB::table('estoque_mensal_fechamentos')->where('empresa_id', $this->empresa_id)
        ->where('mes', $mes)->where('ano', $ano)->sum(DB::raw('quantidade * valor_unitario_custo'));

    // Se o mês atual não tiver fechamento, usa o estoque "ao vivo" como final
    if($estoqueFinal <= 0 && $mes == date('m') && $ano == date('Y')){
        $estoqueFinal = DB::table('estoques')->where('empresa_id', $this->empresa_id)->sum(DB::raw('quantidade * valor_compra'));
    }

    $cmvReal = ($estoqueInicial + $comprasDoMes) - $estoqueFinal;

    // --- RECEITAS E DESPESAS ---
    $receitas_res = DB::table('conta_recebers as r')->join('categoria_contas as c', 'r.categoria_id', '=', 'c.id')
        ->select('c.dre_grupo', 'c.nome as categoria_nome', DB::raw("SUM(r.$colValRec) as total"))
        ->where('r.empresa_id', $this->empresa_id)->where('c.incluir_resultado', 1)
        ->whereMonth("r.$colDataRec", $mes)->whereYear("r.$colDataRec", $ano)->groupBy('c.dre_grupo', 'c.nome')->get();

    $despesas_res = DB::table('conta_pagars as p')->join('categoria_contas as c', 'p.categoria_id', '=', 'c.id')
        ->select('c.dre_grupo', 'c.nome as categoria_nome', DB::raw("SUM(p.$colValPag) as total"))
        ->where('p.empresa_id', $this->empresa_id)->where('c.incluir_resultado', 1)
        ->whereMonth("p.$colDataPag", $mes)->whereYear("p.$colDataPag", $ano)->groupBy('c.dre_grupo', 'c.nome')->get();

    $dados = []; $detalhes = [];
    foreach (CategoriaConta::gruposDRE() as $key => $v) { $dados[$key] = 0; $detalhes[$key] = []; }

    foreach ($receitas_res as $r) {
        $grupo = trim($r->dre_grupo);
        if(isset($dados[$grupo])) { $dados[$grupo] += (float)$r->total; $detalhes[$grupo][] = $r; }
    }
    foreach ($despesas_res as $d) {
        $grupo = trim($d->dre_grupo);
        if(isset($dados[$grupo])) { $dados[$grupo] += (float)$d->total; $detalhes[$grupo][] = $d; }
    }

    $nomeMatriz = DB::table('empresas')->where('id', $this->empresa_id)->value('nome_fantasia') ?? 'MATRIZ';
    $filiais = DB::table('filials')->where('empresa_id', $this->empresa_id)->get();
    $saldoAnterior = DB::table('fechamentos_mensais')->where('empresa_id', $this->empresa_id)
        ->where('mes', $mesAnterior)->where('ano', $anoAnterior)->value('lucro_prejuizo_liquido') ?? 0;

    return view('apuracao.index', compact('dados', 'detalhes', 'mes', 'ano', 'regime', 'filial_id', 'filiais', 'saldoAnterior', 'nomeMatriz', 'cmvReal', 'estoqueInicial', 'comprasDoMes', 'estoqueFinal'))
        ->with('title', 'Apuração de Resultado');
}

    public function finalizar(Request $request)
    {
        $mes = $request->mes;
        $ano = $request->ano;
        $empresa_id = $this->empresa_id;

        DB::beginTransaction();
        try {
            // Snapshot do estoque
            $itensEstoque = DB::table('estoques as e')
                ->join('produtos as p', 'p.id', '=', 'e.produto_id')
                ->where('e.empresa_id', $empresa_id)
                ->whereIn('p.tipo_item', ['00', '01', '04'])
                ->select('e.produto_id', 'e.filial_id', 'e.quantidade', 'e.valor_compra')
                ->get();

            foreach ($itensEstoque as $item) {
                DB::table('estoque_mensal_fechamentos')->insert([
                    'empresa_id' => $empresa_id,
                    'produto_id' => $item->produto_id,
                    'filial_id'  => $item->filial_id,
                    'quantidade' => $item->quantidade,
                    'valor_unitario_custo' => $item->valor_compra,
                    'mes' => $mes,
                    'ano' => $ano,
                    'created_at' => now(), 'updated_at' => now()
                ]);
            }

            DB::table('fechamentos_mensais')->insert([
                'empresa_id' => $empresa_id,
                'mes' => $mes, 'ano' => $ano,
                'regime' => $request->regime ?? 'competencia',
                'lucro_prejuizo_liquido' => $request->lucro_prejuizo_liquido ?? 0,
                'status' => 'encerrado',
                'created_at' => now(), 'updated_at' => now()
            ]);

            DB::commit();
            return redirect()->back()->with('mensagem_sucesso', 'Período encerrado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('mensagem_erro', 'Erro: ' . $e->getMessage());
        }
    }
}