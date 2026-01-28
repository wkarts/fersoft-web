<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tributacao;
use App\Models\Dre;
use App\Models\DreCategoria;
use App\Models\LancamentoCategoria;

use App\Models\Venda;
use App\Models\VendaCaixa;
use App\Models\Devolucao;
use App\Models\Compra;
use App\Models\ComissaoVenda;
use App\Models\Frete;
use App\Models\Produto;
use App\Models\Funcionario;
use App\Models\ContaPagar;
use App\Models\ItemVenda;
use App\Models\ItemVendaCaixa;
use App\Models\ItemCompra;
use Dompdf\Dompdf;

class DreController extends Controller
{
    protected $empresa_id = null;
    public function __construct(){
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }
            return $next($request);
        });
    }
    public function index(){

    	$tributacao = Tributacao::
    	where('empresa_id', $this->empresa_id)
    	->first();

        if($tributacao == null){
            session()->flash("mensagem_alerta", "Informe a tributação!");
            return redirect('/tributos');
        }

        return view('dre/index')
        ->with('tributacao', $tributacao)
        ->with('title', 'DRE');
    }

    public function save(Request $request){
        $inicio = $request->data_inicio;
        $fim = $request->data_fim;
        $filial_id = $request->filial_id;

        if (!$inicio || !$fim) {
            session()->flash("mensagem_erro", "Informe a data inícial e final!");
            return redirect()->back();
        }
        $percImposto = $request->perc_imposto;

        try {
            $filial = intval($request->filial_id);
            $data = [
                'empresa_id'         => $this->empresa_id,
                'inicio'             => $this->parseDate($inicio),
                'fim'                => $this->parseDate($fim),
                'observacao'         => $request->observacao ?? '',
                'filial_id'          => $filial > 0 ? $filial : null,
                'percentual_imposto' => __replace($percImposto),
                'lucro_prejuizo'     => 0
            ];

            $dre = Dre::create($data);
            $dre->criaCategoriasPreDefinidas();

            $this->iniciaDre($dre, $inicio, $fim);

            session()->flash("mensagem_sucesso", "Relatório DRE criado!");
            return redirect('/dre/list');
        } catch (\Exception $e) {
            echo $e->getLine();
            echo $e->getMessage();
        }
    }

    public function save_(Request $request){
        $inicio = $request->data_inicio;
        $fim = $request->data_fim;
        $filial_id = $request->filial_id;

        if(!$inicio || !$fim){
            session()->flash("mensagem_erro", "Informe a data inícial e final!");
            return redirect()->back();
        }
        $percImposto = $request->perc_imposto;

        try{
            $data = [
                'empresa_id' => $this->empresa_id,
                'inicio' => $this->parseDate($inicio),
                'fim' => $this->parseDate($fim),
                'observacao' => $request->observacao ?? '',
                'filial_id' => $request->filial_id ?? null,
                'percentual_imposto' => __replace($percImposto),
                'lucro_prejuizo' => 0
            ];

            $dre = Dre::create($data);
            $dre->criaCategoriasPreDefinidas();

            $this->iniciaDre($dre, $inicio, $fim);

            session()->flash("mensagem_sucesso", "Relatório DRE criado!");
            return redirect('/dre/list');

        }catch(\Exception $e){
            echo $e->getLine();
            echo $e->getMessage();
        }

    }

    public function list(){
        $docs = Dre::
        where('empresa_id', $this->empresa_id)
        ->orderBy('id', 'desc')
        ->get();

        return view('dre/list')
        ->with('docs', $docs)
        ->with('title', 'DRE');
    }

    public function ver($id){
        $dre = Dre::find($id);
        if(valida_objeto($dre)){

            $tributacao = Tributacao::
            where('empresa_id', $this->empresa_id)
            ->first();

            return view('dre/ver')
            ->with('dre', $dre)
            ->with('dreJs', true)
            ->with('tributacao', $tributacao)
            ->with('title', 'DRE');
        }else{
            return redirect('/403');
        }
    }

    public function updatelancamento(Request $request){
        $lancamentoId = $request->lancamento_id;
        $lancamento = LancamentoCategoria::find($lancamentoId);

        $lancamento->valor = __replace($request->valor);
        $lancamento->nome = $request->nome;
        $lancamento->save();

        $this->recalcularPercentual($lancamento->categoria->dre_id);
        session()->flash("mensagem_sucesso", "Lançamento alterado com sucesso!");
        return redirect()->back();

    }

    public function novolancamento(Request $request){
        $categoriaId = $request->categoria_id;
        $valor = __replace($request->valor);
        $nome = $request->nome;

        $dataLancamento = [
            'categoria_id' => $categoriaId,
            'nome' => $nome,
            'valor' => $valor,
            'percentual' => 0
        ];

        LancamentoCategoria::create($dataLancamento);

        $categoria = DreCategoria::find($categoriaId);
        $this->recalcularPercentual($categoria->dre_id);
        session()->flash("mensagem_sucesso", "Lançamento cadastrado com sucesso!");
        return redirect()->back();

    }


    public function deleteLancamento($id){
        try{
            LancamentoCategoria::find($id)->delete();

            session()->flash("mensagem_sucesso", "Lançamento removido com sucesso!");
        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Erro ao remover lançamento!");

        }

        return redirect()->back();

    }

    /**
     * Recalcula todo o DRE:
     * - Faturamento Líquido = soma(cat0) - soma(cat1)
     * - Lucro/Prejuízo = Faturamento Líquido - soma(categorias ≥3)
     * - Atualiza o lançamento “Faturamento Líquido”
     * - Atualiza percentual interno de cada lançamento (dentro da própria categoria)
     */
    private function recalcularPercentual(int $dreId)
    {
        $dre = Dre::with('categorias.lancamentos')->findOrFail($dreId);

        // 1) soma de receitas (cat 0) e deduções (cat 1)
        $sumReceitas = $dre->categorias[0]->lancamentos->sum('valor');
        $sumDeducoes = $dre->categorias[1]->lancamentos->sum('valor');

        // 2) novo faturamento líquido
        $novoLiquido = $sumReceitas - $sumDeducoes;

        // 3) atualiza o lançamento “Faturamento Líquido” (cat 2)
        foreach ($dre->categorias[2]->lancamentos as $lanc) {
            if ($lanc->nome === 'Faturamento Líquido') {
                $lanc->valor      = $novoLiquido;
                $lanc->percentual = $sumReceitas > 0
                    ? round($novoLiquido / $sumReceitas * 100, 2)
                    : 0;
                $lanc->save();
            }
        }

        // 4) soma de custos/despesas (cat ≥3)
        $sumCustos = collect($dre->categorias)
            ->slice(3)
            ->sum(fn($cat) => $cat->lancamentos->sum('valor'));

        // 5) atualiza lucro/prejuízo
        $dre->lucro_prejuizo = $novoLiquido - $sumCustos;
        $dre->save();

        // 6) recalcula percentuais internos (opcional)
        foreach ($dre->categorias as $cat) {
            $totalCat = $cat->lancamentos->sum('valor');
            foreach ($cat->lancamentos as $lan) {
                $lan->percentual = $totalCat > 0
                    ? round($lan->valor / $totalCat * 100, 2)
                    : 0;
                $lan->save();
            }
        }
    }


    private function recalcularPercentual___(int $dreId)
    {
        $dre = Dre::with('categorias.lancamentos')->findOrFail($dreId);

        // 1) soma de receitas (cat 0) e deduções (cat 1)
        $cat0 = $dre->categorias[0];
        $cat1 = $dre->categorias[1];
        $sumReceitas   = $cat0->lancamentos->sum('valor');
        $sumDeducoes   = $cat1->lancamentos->sum('valor');

        // 2) novo faturamento líquido
        $novoLiquido = $sumReceitas - $sumDeducoes;

        // 3) atualiza o lançamento “Faturamento Líquido” (cat 2)
        $cat2 = $dre->categorias[2];
        foreach ($cat2->lancamentos as $lanc) {
            if ($lanc->nome === 'Faturamento Líquido') {
                $lanc->valor      = $novoLiquido;
                // percentual sobre o bruto
                $lanc->percentual = $sumReceitas > 0
                    ? round($novoLiquido / $sumReceitas * 100, 2)
                    : 0;
                $lanc->save();
            }
        }

        // 4) soma de custos/despesas (categorias a partir do índice 3)
        $sumCustos = collect($dre->categorias)
            ->slice(3)
            ->sum(fn($cat) => $cat->lancamentos->sum('valor'));

        // 5) atualiza lucro/prejuízo no DRE
        $dre->lucro_prejuizo = $novoLiquido - $sumCustos;
        $dre->save();

        // 6) recalcula percentual dentro de cada categoria (opcional, mantém a lógica antiga)
        foreach ($dre->categorias as $cat) {
            $totalCat = $cat->lancamentos->sum('valor');
            foreach ($cat->lancamentos as $lan) {
                $lan->percentual = $totalCat > 0
                    ? round($lan->valor / $totalCat * 100, 2)
                    : 0;
                $lan->save();
            }
        }
    }

    private function recalcularPercentual__($dreId)
    {
        // Carrega DRE e suas categorias com lançamentos
        $dre = Dre::with('categorias.lancamentos')->findOrFail($dreId);

        // 1) Recalcula Faturamento Líquido (categoria índice 2)
        $categoriaLiquida = $dre->categorias[2];
        $valorLiquido    = $categoriaLiquida->lancamentos->sum('valor');

        // Atualiza o próprio lançamento "Faturamento Líquido"
        foreach ($categoriaLiquida->lancamentos as $lanc) {
            if ($lanc->nome === 'Faturamento Líquido') {
                $lanc->valor      = $valorLiquido;
                $lanc->percentual = 100;
                $lanc->save();
            }
        }

        // 2) Soma todas as deduções (categorias a partir do índice 3)
        $somaDeducoes = collect($dre->categorias)
            ->slice(3)
            ->sum(fn($cat) => $cat->lancamentos->sum('valor'));

        // 3) Recalcula percentual de cada lançamento em todas as categorias
        foreach ($dre->categorias as $cat) {
            $totalCat = $cat->lancamentos->sum('valor');
            foreach ($cat->lancamentos as $lan) {
                $lan->percentual = $totalCat > 0
                    ? round(($lan->valor / $totalCat) * 100, 2)
                    : 0;
                $lan->save();
            }
        }

        // 4) Atualiza o lucro/prejuízo do DRE e salva
        $dre->lucro_prejuizo = $valorLiquido - $somaDeducoes;
        $dre->save();
    }

    private function recalcularPercentual_($dreId){

        $dre = Dre::find($dreId);
        $liquido = 0;
        $somaDeducoes = 0;
        foreach($dre->categorias as $key => $c){
            $faturamento = $c->soma();
            if($key > 0){
                foreach($c->lancamentos as $l){
                    if($faturamento == 0){
                        $l->percentual = 0;
                    }else{
                        $percentual = number_format((($l->valor/$faturamento) * 100), 2);
                        $l->percentual = $percentual;
                    }
                    $l->save();
                }
            }

            if($key == 2){
                $liquido = $l->valor;
                echo $liquido;
            }

            if($key > 2){
                foreach($c->lancamentos as $l){
                    $somaDeducoes += $l->valor;
                }
            }
        }


        $lucro_prejuizo = $liquido - $somaDeducoes;
        $dre->lucro_prejuizo = $lucro_prejuizo;
        $dre->save();

    }

    public function iniciaDre_($dre, $inicio, $fim){
        $somaCustos   = 0;
        $valorLiquido = 0;

        // ajusta para capturar o dia inteiro
        $inicioDt = $this->parseDate($inicio) . ' 00:00:00';
        $fimDt    = $this->parseDate($fim)    . ' 23:59:59';

        foreach($dre->categorias as $key => $c){
            if($key == 0){
                // Venda NFE
                $vendas = Venda::
                selectRaw('sum(valor_total) as soma')
                    ->whereBetween('created_at', [$inicioDt, $fimDt])
                    ->when($dre->filial_id, function($query) use ($dre) {
                        return $query->where('filial_id', $dre->filial_id);
                    })
                    ->where('empresa_id', $dre->empresa_id)
                    ->first();
                LancamentoCategoria::create([
                    'categoria_id' => $c->id,
                    'nome'         => 'Faturamento bruto vendas',
                    'valor'        => $vendas->soma ?? 0,
                    'percentual'   => 0
                ]);

                // PDV
                $vendasPdv = VendaCaixa::
                selectRaw('sum(valor_total) as soma')
                    ->whereBetween('created_at', [$inicioDt, $fimDt])
                    ->when($dre->filial_id, function($query) use ($dre) {
                        return $query->where('filial_id', $dre->filial_id);
                    })
                    ->where('empresa_id', $dre->empresa_id)
                    ->first();
                LancamentoCategoria::create([
                    'categoria_id' => $c->id,
                    'nome'         => 'Faturamento bruto vendas PDV',
                    'valor'        => $vendasPdv->soma ?? 0,
                    'percentual'   => 0
                ]);

                // Outros
                LancamentoCategoria::create([
                    'categoria_id' => $c->id,
                    'nome'         => 'Faturamento outros',
                    'valor'        => 0,
                    'percentual'   => 0
                ]);
            }

            if($key == 1){
                // Devoluções
                $devolucoes = Devolucao::
                selectRaw('sum(valor_devolvido) as soma')
                    ->whereBetween('created_at', [$inicioDt, $fimDt])
                    ->when($dre->filial_id, function($query) use ($dre) {
                        return $query->where('filial_id', $dre->filial_id);
                    })
                    ->where('empresa_id', $dre->empresa_id)
                    ->where('tipo', 0)
                    ->first();
                LancamentoCategoria::create([
                    'categoria_id' => $c->id,
                    'nome'         => 'Devoluções',
                    'valor'        => $devolucoes->soma ?? 0,
                    'percentual'   => 0
                ]);

                // Abatimentos
                LancamentoCategoria::create([
                    'categoria_id' => $c->id,
                    'nome'         => 'Abatimentos/Descontos',
                    'valor'        => 0,
                    'percentual'   => 0
                ]);

                // Impostos
                $calculaImposto = $this->calculaImposto(
                    $inicioDt, $fimDt,
                    $dre->percentual_imposto,
                    $dre->filial_id
                );
                LancamentoCategoria::create([
                    'categoria_id' => $c->id,
                    'nome'         => 'Impostos',
                    'valor'        => $calculaImposto,
                    'percentual'   => 0
                ]);
            }

            if($key == 2){
                // Faturamento Líquido
                $faturamentoLiquido = $this->calculoFaturamentoLiquido($dre);
                LancamentoCategoria::create([
                    'categoria_id' => $c->id,
                    'nome'         => 'Faturamento Líquido',
                    'valor'        => $faturamentoLiquido,
                    'percentual'   => 100
                ]);
                $valorLiquido = $faturamentoLiquido;
            }

            if($key == 3){
                // CMV/CMP
                $cmv = $this->getCMVCMP($inicioDt, $fimDt, $dre->filial_id);
                $somaCustos += $cmv;
                LancamentoCategoria::create([
                    'categoria_id' => $c->id,
                    'nome'         => 'CMV/CMP',
                    'valor'        => $cmv,
                    'percentual'   => 0
                ]);

                // Fretes
                $fretes = $this->getFretes($inicioDt, $fimDt, $dre->filial_id);
                $somaCustos += $fretes->soma ?? 0;
                LancamentoCategoria::create([
                    'categoria_id' => $c->id,
                    'nome'         => 'Despesas com Transportes',
                    'valor'        => $fretes->soma ?? 0,
                    'percentual'   => 0
                ]);

                // Comissões
                $comissoes = $this->getComissaoVendas($inicioDt, $fimDt);
                $somaCustos += $comissoes->soma ?? 0;
                LancamentoCategoria::create([
                    'categoria_id' => $c->id,
                    'nome'         => 'Comissões Vendas',
                    'valor'        => $comissoes->soma ?? 0,
                    'percentual'   => 0
                ]);
            }

            if($key == 4){
                // Salários
                $salarios = $this->getSalarios($inicioDt, $fimDt);
                $somaCustos += $salarios->soma ?? 0;
                LancamentoCategoria::create([
                    'categoria_id' => $c->id,
                    'nome'         => 'Sálario Funcionários',
                    'valor'        => $salarios->soma ?? 0,
                    'percentual'   => 0
                ]);

                // Contas a Pagar
                $contas = $this->getContasPagar($inicioDt, $fimDt, $dre->filial_id);
                foreach($contas as $conta){
                    $somaCustos += $conta->soma ?? 0;
                    LancamentoCategoria::create([
                        'categoria_id' => $c->id,
                        'nome'         => $conta->nome,
                        'valor'        => $conta->soma ?? 0,
                        'percentual'   => 0
                    ]);
                }
            }
        }

        $dre->lucro_prejuizo = $valorLiquido - $somaCustos;
        $dre->save();
        $this->recalcularPercentual($dre->id);
    }

    public function iniciaDre($dre, $inicio, $fim){
        $somaCustos = 0;

        foreach($dre->categorias as $key => $c){
            if($key == 0){

                //Venda NFE
                $vendas = $this->getVendasPeriodo($this->parseDate($inicio), $this->parseDate($fim, true), $dre->filial_id);

                $dataLancamento = [
                    'categoria_id' => $c->id,
                    'nome' => 'Faturamento bruto vendas',
                    'valor' => $vendas->soma ?? 0,
                    'percentual' => 0
                ];

                LancamentoCategoria::create($dataLancamento);

                // PDV
                $vendas = $this->getVendasPdvPeriodo($this->parseDate($inicio), $this->parseDate($fim, true), $dre->filial_id);

                $dataLancamento = [
                    'categoria_id' => $c->id,
                    'nome' => 'Faturamento bruto vendas PDV',
                    'valor' => $vendas->soma ?? 0,
                    'percentual' => 0
                ];
                LancamentoCategoria::create($dataLancamento);

                // Outros
                $dataLancamento = [
                    'categoria_id' => $c->id,
                    'nome' => 'Faturamento outros',
                    'valor' => 0,
                    'percentual' => 0
                ];
                LancamentoCategoria::create($dataLancamento);
            }

            if($key == 1){

                //Devoluções
                $devolucoes = $this->getDevolucoes($this->parseDate($inicio), $this->parseDate($fim, true), $dre->filial_id);

                $dataLancamento = [
                    'categoria_id' => $c->id,
                    'nome' => 'Devoluções',
                    'valor' => $devolucoes->soma ?? 0,
                    'percentual' => 0
                ];
                LancamentoCategoria::create($dataLancamento);

                //Abatimentos
                $dataLancamento = [
                    'categoria_id' => $c->id,
                    'nome' => 'Abatimentos/Descontos',
                    'valor' => 0,
                    'percentual' => 0
                ];
                LancamentoCategoria::create($dataLancamento);

                //Imposto
                $calculaImposto = $this->calculaImposto($this->parseDate($inicio), $this->parseDate($fim, true), $dre->percentual_imposto, $dre->filial_id);

                $dataLancamento = [
                    'categoria_id' => $c->id,
                    'nome' => 'Impostos',
                    'valor' => $calculaImposto,
                    'percentual' => 0
                ];
                LancamentoCategoria::create($dataLancamento);

            }

            if($key == 2){
                //Faturamento liquido
                $faturamentoLiquido = $this->calculoFaturamentoLiquido($dre);
                $dataLancamento = [
                    'categoria_id' => $c->id,
                    'nome' => 'Faturamento Líquido',
                    'valor' => $faturamentoLiquido,
                    'percentual' => 100
                ];
                LancamentoCategoria::create($dataLancamento);

                $valorLiquido = $faturamentoLiquido;
            }



            if($key == 3){
                //Custos de Produção Variáveis
                // $compras = $this->getCompras($this->parseDate($inicio), $this->parseDate($fim, true));

                $cmv = $this->getCMVCMP($this->parseDate($inicio),
                    $this->parseDate($fim, true), $dre->percentual_imposto);

                // echo "<pre>";
                // print_r($cmv);
                // echo "</pre>";

                // die;

                $somaCustos += $cmv;

                $dataLancamento = [
                    'categoria_id' => $c->id,
                    'nome' => 'CMV/CMP',
                    'valor' => $cmv,
                    'percentual' => 0
                ];

                LancamentoCategoria::create($dataLancamento);

                $fretes = $this->getFretes($this->parseDate($inicio), $this->parseDate($fim, true), $dre->filial_id);
                $dataLancamento = [
                    'categoria_id' => $c->id,
                    'nome' => 'Despesas com Transportes',
                    'valor' => $fretes->soma ?? 0,
                    'percentual' => 0
                ];
                LancamentoCategoria::create($dataLancamento);

                $somaCustos += $fretes->soma ?? 0;

                $comissoes = $this->getComissaoVendas($this->parseDate($inicio), $this->parseDate($fim, true));
                $dataLancamento = [
                    'categoria_id' => $c->id,
                    'nome' => 'Comissões Vendas',
                    'valor' => $comissoes->soma ?? 0,
                    'percentual' => 0
                ];
                LancamentoCategoria::create($dataLancamento);

                $somaCustos += $comissoes->soma ?? 0;

            }

            if($key == 4){
                //Custos Fixos e Despesas

                $salarios = $this->getSalarios($this->parseDate($inicio), $this->parseDate($fim, true));
                $dataLancamento = [
                    'categoria_id' => $c->id,
                    'nome' => 'Sálario Funcionários',
                    'valor' => $salarios->soma ?? 0,
                    'percentual' => 0
                ];
                LancamentoCategoria::create($dataLancamento);

                $somaCustos += $salarios->soma ?? 0;

                $contas = $this->getContasPagar($this->parseDate($inicio), $this->parseDate($fim, true), $dre->filial_id);

                foreach($contas as $conta){

                    $dataLancamento = [
                        'categoria_id' => $c->id,
                        'nome' => $conta->nome,
                        'valor' => $conta->soma ?? 0,
                        'percentual' => 0
                    ];
                    LancamentoCategoria::create($dataLancamento);

                    $somaCustos += $conta->soma ?? 0;

                }

            }
        }

        $dre->lucro_prejuizo = $valorLiquido - $somaCustos;

        // echo $dre->lucro_prejuizo;
        // die;
        $dre->save();
        $this->recalcularPercentual($dre->id);
    }


    private function parseDate($date, $plusDay = false){
        if($plusDay == false)
            return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
        else
            return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
    }


    private function getVendasPeriodo($inicio, $fim, $filial_id){
        if($filial_id == 1){
            $filial_id = null;
        }
        $vendas = Venda::
        selectRaw('sum(valor_total) as soma')
        ->whereBetween('created_at', [
            $inicio,
            $fim
        ])
        ->when($filial_id, function ($query) use ($filial_id) {
            return $query->where('filial_id', $filial_id);
        })
        ->where('empresa_id', $this->empresa_id)
        ->first();

        return $vendas;
    }

    private function getVendasPdvPeriodo($inicio, $fim, $filial_id){
        if($filial_id == 1){
            $filial_id = null;
        }
        $vendas = VendaCaixa::
        selectRaw('sum(valor_total) as soma')
        ->whereBetween('created_at', [
            $inicio,
            $fim
        ])
        ->when($filial_id, function ($query) use ($filial_id) {
            return $query->where('filial_id', $filial_id);
        })
        ->where('empresa_id', $this->empresa_id)
        ->first();

        return $vendas;
    }

    private function getDevolucoes($inicio, $fim, $filial_id){
        if($filial_id == 1){
            $filial_id = null;
        }
        $devolucoes = Devolucao::
        selectRaw('sum(valor_devolvido) as soma')
        ->whereBetween('created_at', [
            $inicio,
            $fim
        ])
        ->where('empresa_id', $this->empresa_id)
        ->where('tipo', 0)
        ->when($filial_id, function ($query) use ($filial_id) {
            return $query->where('filial_id', $filial_id);
        })
        ->first();

        return $devolucoes;
    }

    private function getCMVCMP($inicio, $fim, $filial_id){
        if($filial_id == 1){
            $filial_id = null;
        }
        $vendas = Venda::
        whereBetween('created_at', [
            $inicio,
            $fim
        ])
        ->when($filial_id, function ($query) use ($filial_id) {
            return $query->where('filial_id', $filial_id);
        })
        ->where('empresa_id', $this->empresa_id)
        ->get();

        $vendasCaixa = VendaCaixa::
        whereBetween('created_at', [
            $inicio,
            $fim
        ])
        ->when($filial_id, function ($query) use ($filial_id) {
            return $query->where('filial_id', $filial_id);
        })
        ->where('empresa_id', $this->empresa_id)
        ->get();

        $custo = 0;

        foreach($vendas as $v){
            foreach($v->itens as $i){
                $produto = Produto::find($i->produto_id);
                $custo += $produto->valor_compra * $i->quantidade;
            }
        }

        foreach($vendasCaixa as $v){
            foreach($v->itens as $i){
                $produto = Produto::find($i->produto_id);
                $custo += $produto->valor_compra * $i->quantidade;
            }
        }

        return $custo;
    }

    private function calculaImposto($inicio, $fim, $percImposto, $filial_id){
        if($filial_id == 1){
            $filial_id = null;
        }
        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($tributacao->regime != 1){
            $vendas = Venda::
            selectRaw('sum(valor_total) as soma')
            ->whereBetween('created_at', [
                $inicio,
                $fim
            ])
            ->where('empresa_id', $this->empresa_id)
            ->where('NfNumero', '>', 0)
            ->where('estado', 'APROVADO')
            ->when($filial_id, function ($query) use ($filial_id) {
                return $query->where('filial_id', $filial_id);
            })
            ->first();

            $vendasCaixa = VendaCaixa::
            selectRaw('sum(valor_total) as soma')
            ->whereBetween('created_at', [
                $inicio,
                $fim
            ])
            ->when($filial_id, function ($query) use ($filial_id) {
                return $query->where('filial_id', $filial_id);
            })
            ->where('NFcNumero', '>', 0)
            ->where('empresa_id', $this->empresa_id)
            ->where('estado', 'APROVADO')
            ->first();


            $soma = $vendasCaixa->soma + $vendas->soma;
            $p = $soma*($percImposto/100);

            return $p;
        }else{
            $vendas = Venda::
            whereBetween('created_at', [
                $inicio,
                $fim
            ])
            ->when($filial_id, function ($query) use ($filial_id) {
                return $query->where('filial_id', $filial_id);
            })
            ->where('empresa_id', $this->empresa_id)
            ->where('NfNumero', '>', 0)
            ->where('estado', 'APROVADO')
            ->get();

            $impostoNFe = $this->extrairImposto($vendas, 'xml_nfe');

            $vendasCaixa = VendaCaixa::
            whereBetween('created_at', [
                $inicio,
                $fim
            ])
            ->when($filial_id, function ($query) use ($filial_id) {
                return $query->where('filial_id', $filial_id);
            })
            ->where('NFcNumero', '>', 0)
            ->where('empresa_id', $this->empresa_id)
            ->where('estado', 'APROVADO')
            ->get();

            $impostoNFCe = $this->extrairImposto($vendasCaixa, 'xml_nfce');


            return $impostoNFe + $impostoNFCe;
        }

    }

    private function extrairImposto($vendas, $path){

        $somaIcms = 0;
        $somaPis = 0;
        $somaCofins = 0;
        foreach($vendas as $v){
            $file = public_path($path) . "/" . $v->chave . ".xml";
            $xml = simplexml_load_file($file);

            $vIcms = $xml->NFe->infNFe->total->ICMSTot->vICMS;
            $vPis = $xml->NFe->infNFe->total->ICMSTot->vPIS;
            $vCofins = $xml->NFe->infNFe->total->ICMSTot->vCOFINS;

            $somaIcms += $vIcms;
            $somaPis += $vPis;
            $somaCofins += $vCofins;

        }

        return $somaIcms + $somaPis + $somaCofins;
    }

    private function calculoFaturamentoLiquido($dre){
        $somaBruto = 0;
        $somaDeducoes = 0;
        foreach($dre->categorias as $key => $c){
            if($key == 0){
                foreach($c->lancamentos as $l){
                    $somaBruto += $l->valor;
                }
            }

            if($key == 1){
                foreach($c->lancamentos as $l){
                    $somaDeducoes += $l->valor;
                }
            }
        }
        return $somaBruto - $somaDeducoes;
    }

    private function getCompras($inicio, $fim, $filial_id){
        if($filial_id == 1){
            $filial_id = null;
        }
        $produtosVendidos = $this->getProdutosVendidos($inicio, $fim);
        $soma = 0;
        foreach($produtosVendidos as $p){
            $item = ItemCompra::
            select('item_compras.valor_unitario')
            ->join('compras', 'compras.id', '=', 'item_compras.compra_id')

            ->whereBetween('item_compras.created_at', [
                $inicio . " 00:00:00",
                $fim . " 23:59:00"
            ])
            ->where('item_compras.produto_id', $p['id'])
            ->where('compras.empresa_id', $this->empresa_id)
            ->when($filial_id, function ($query) use ($filial_id) {
                return $query->where('compras.filial_id', $filial_id);
            })
            ->first();

            $soma += $p['qtd'] * ($item != null ? $item->valor_unitario : 0);
        }

        return $soma;

    }

    private function getProdutosVendidos($inicio, $fim, $filial_id){
        if($filial_id == 1){
            $filial_id = null;
        }
        $itens = [];
        $pVenda = ItemVenda::
        selectRaw('sum(item_vendas.quantidade) as qtd, item_vendas.id, item_vendas.valor')
        ->join('vendas', 'vendas.id', '=', 'item_vendas.venda_id')
        ->whereBetween('item_vendas.created_at', [
            $inicio . " 00:00:00",
            $fim . " 23:59:00"
        ])
        ->where('vendas.empresa_id', $this->empresa_id)
        ->groupBy('item_vendas.id')
        ->when($filial_id, function ($query) use ($filial_id) {
            return $query->where('vendas.filial_id', $filial_id);
        })
        ->get();

        $pVendaCaixa = ItemVendaCaixa::
        selectRaw('sum(item_venda_caixas.quantidade) as qtd, item_venda_caixas.id, item_venda_caixas.valor')
        ->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
        ->whereBetween('item_venda_caixas.created_at', [
            $inicio . " 00:00:00",
            $fim . " 23:59:00"
        ])
        ->where('venda_caixas.empresa_id', $this->empresa_id)
        ->when($filial_id, function ($query) use ($filial_id) {
            return $query->where('venda_caixas.filial_id', $filial_id);
        })
        ->get();


        foreach($pVenda as $p){
            if($p->qtd != null){
                $temp = [
                    'qtd' => $p->qtd,
                    'id' => $p->id,
                    'valor' => $p->valor
                ];

                array_push($itens, $temp);
            }
        }

        foreach($pVendaCaixa as $p){
            if($p->qtd != null){

                $temp = [
                    'qtd' => $p->qtd,
                    'id' => $p->id,
                    'valor' => $p->valor
                ];

                array_push($itens, $temp);
            }
        }

        return $itens;
    }

    private function getComissaoVendas($inicio, $fim){
        $comissoes = ComissaoVenda::
        selectRaw('sum(valor) as soma')
        ->whereBetween('created_at', [
            $inicio,
            $fim
        ])
        ->where('empresa_id', $this->empresa_id)
        ->first();

        return $comissoes;
    }

    private function getFretes($inicio, $fim, $filial_id){
        $fretes = Frete::
        selectRaw('sum(fretes.valor) as soma')
        ->join('vendas', 'vendas.frete_id' , '=', 'fretes.id')
        ->whereBetween('fretes.created_at', [
            $inicio,
            $fim
        ])
        ->when($filial_id, function ($query) use ($filial_id) {
            return $query->where('vendas.filial_id', $filial_id);
        })
        ->where('vendas.empresa_id', $this->empresa_id)
        ->first();

        return $fretes;
    }

    private function getSalarios($inicio, $fim){
        $funcionarios = Funcionario::
        selectRaw('sum(salario) as soma')
        ->whereBetween('created_at', [
            $inicio,
            $fim
        ])
        ->where('empresa_id', $this->empresa_id)
        ->first();

        return $funcionarios;
    }

    private function getContasPagar($inicio, $fim, $filial_id){
        if($filial_id == 1){
            $filial_id = null;
        }
        $contas = ContaPagar::
        selectRaw('categoria_contas.nome as nome, sum(conta_pagars.valor_integral) as soma')
        ->join('categoria_contas', 'categoria_contas.id' , '=', 'conta_pagars.categoria_id')
        ->whereBetween('conta_pagars.data_vencimento', [
            $inicio,
            $fim
        ])
        ->when($filial_id, function ($query) use ($filial_id) {
            return $query->where('conta_pagars.filial_id', $filial_id);
        })
        ->where('conta_pagars.empresa_id', $this->empresa_id)
        ->where('categoria_contas.nome', '!=', 'Compras')
        ->where('categoria_contas.nome', '!=', 'Vendas')
        ->groupBy('categoria_contas.id')
        ->get();

        return $contas;
    }

    public function imprimir($id){
        $dre = Dre::find($id);
        if(valida_objeto($dre)){

            $tributacao = Tributacao::
            where('empresa_id', $this->empresa_id)
            ->first();

            $p = view('dre/imprimir')
            ->with('dre', $dre)
            ->with('tributacao', $tributacao)
            ->with('title', 'DRE');

            // return $p;
            $domPdf = new Dompdf(["enable_remote" => true]);
            $domPdf->loadHtml($p);

            // $pdf = ob_get_clean();

            $domPdf->setPaper("A4");
            $domPdf->render();
            $domPdf->stream("DRE.pdf");
        }else{
            return redirect('/403');
        }
    }

    public function delete($id){
        $dre = Dre::find($id);
        if(valida_objeto($dre)){
            $dre->delete();
            session()->flash("mensagem_sucesso", "Registro removido");
            return redirect()->back();
        }else{
            return redirect('/403');
        }
    }
}
