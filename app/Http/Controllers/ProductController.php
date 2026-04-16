<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produto;
use App\Models\Etiqueta;
use App\Models\Estoque;
use App\Models\Categoria;
use App\Models\ConfigNota;
use App\Models\Tributacao;
use App\Rules\EAN13;
use App\Rules\ValidaValor;
use App\Helpers\StockMove;
use App\Helpers\ProdutoGrade;
use App\Models\CategoriaProdutoDelivery;
use App\Models\ProdutoDelivery;
use App\Models\NaturezaOperacao;
use App\Models\ProdutoListaPreco;
use App\Models\ImagensProdutoDelivery;
use App\Models\ItemDfe;
use App\Models\Marca;
use App\Models\SubCategoria;
use App\Models\TributacaoUf;
use App\Models\SubCategoriaEcommerce;
use App\Models\AlteracaoEstoque;
use App\Models\Cliente;
use Dompdf\Dompdf;
use App\Models\DivisaoGrade;
use Illuminate\Support\Str;
use App\Models\CategoriaProdutoEcommerce;
use App\Models\ProdutoEcommerce;
use App\Models\ImagemProdutoEcommerce;
use App\Imports\ProdutoImport;
use Maatwebsite\Excel\Facades\Excel;
use setasign\Fpdi\TcpdfFpdi;
use App\Exports\ProdutoExport;
use App\Models\ProdutoIbpt;
use App\Services\IbptService;
use Illuminate\Support\Facades\DB;
use App\Models\TelaPedido;
use App\Models\ReformaTributaria\CstIbsCbs;
use App\Models\ReformaTributaria\ClassTribIbsCbs;

class ProductController extends Controller
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

    // private function validaLocais(){
    //     Produto::
    //     where('empresa_id', $this->empresa_id)
    //     ->where('locais', null)
    //     ->update(['locais' => '["-1"]']);
    // }

    public function index(){

        $permissaoAcesso = __getLocaisUsarioLogado();
        $local_padrao = __get_local_padrao();
        // dd($permissaoAcesso);
        // $this->validaLocais();
        $produtos = Produto::
        where('empresa_id', $this->empresa_id)
        ->groupBy('referencia_grade')
        ->orderBy('inativo')
        ->orderBy('nome', 'asc')
        ->where(function($query) use ($permissaoAcesso){
            if($permissaoAcesso != null){
                foreach ($permissaoAcesso as $value) {
                    // $query->where(function($q) use($value){

                    // });
                    $query->orWhere('locais', 'like', "%{$value}%");
                }
            }
        })
        ->when($local_padrao != NULL, function ($query) use ($local_padrao) {
            $query->where('locais', 'like', "%{$local_padrao}%");
        });

        if(sizeof(__locaisAtivosAll()) > 1){
            $produtos = $produtos->get();
            $ids = [];
            foreach($produtos as $p){
                $locais = json_decode($p->locais);
                foreach($locais as $l){
                    if(in_array($l, $permissaoAcesso)){
                        array_push($ids, $p->id);
                    }
                }
            }
            $produtos = Produto::whereIn('id', $ids)->paginate(40);
        }else{
            $produtos = $produtos->paginate(30);
        }

        $categorias = Categoria::
        where('empresa_id', $this->empresa_id)
        ->get();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        // $produtos = $this->setaEstoque($produtos);

        $marcas = Marca::
        where('empresa_id', $this->empresa_id)
        ->get();

        // --- detectar se há algum 'locais' fora do padrão ou com duplicatas ---
        $hasLocaisErrors = false;
        $rawList = Produto::where('empresa_id', $this->empresa_id)
            ->pluck('locais');

        foreach ($rawList as $raw) {
            $arr = json_decode($raw, true);

            // 1) JSON inválido ou não-array?
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($arr)) {
                $hasLocaisErrors = true;
                break;
            }

            // 2) checa tipo, valor e duplicatas
            //    - cada $loc deve ser STRING contendo inteiro
            //    - o inteiro deve ser -1 ou >= 1
            //    - não pode haver duplicatas
            $seen = [];
            foreach ($arr as $loc) {
                if (! is_string($loc) || ! preg_match('/^-?\d+$/', $loc)) {
                    $hasLocaisErrors = true;
                    break 2;
                }
                $num = intval($loc);
                if (! ($num === -1 || $num >= 1)) {
                    $hasLocaisErrors = true;
                    break 2;
                }
                // duplicata?
                if (in_array($loc, $seen, true)) {
                    $hasLocaisErrors = true;
                    break 2;
                }
                $seen[] = $loc;
            }
        }

        $ibpt = $config == null ? "": $config->token_ibpt != "";
        return view('produtos/list')
        ->with('produtos', $produtos)
        ->with('links', true)
        ->with('ibpt', $ibpt)
        ->with('marcas', $marcas)
        ->with('categorias', $categorias)
        ->with('hasLocaisErrors', $hasLocaisErrors)
        ->with('title', 'Produtos');
    }

    private function setaEstoque($produtos){
        return $produtos;
        foreach($produtos as $p){
            if($p->grade){
                $produtosGrade = Produto::
                where('referencia_grade', $p->referencia_grade)
                ->get();
                $valores = "";
                foreach($produtosGrade as $p){
                    $valores .= " ". number_format($p->valor_venda, 2) . " | ";
                }
                $p->valores_grade = substr($valores, 0, strlen($valores)-2);
            }else{
                $p->valores_grade = '--';
            }
        }
        return $produtos;
    }

    private function incluiDigito($code){
        $weightflag = true;
        $sum = 0;
        for ($i = strlen($code) - 1; $i >= 0; $i--) {
            $sum += (int)$code[$i] * ($weightflag?3:1);
            $weightflag = !$weightflag;
        }
        return $code . (10 - ($sum % 10)) % 10;
    }

    public function gerarCodigoEan(){
        try{
            $rand = rand(11111, 99999);
            $code = $this->incluiDigito('7891000'.$rand);
            return response()->json($code, 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function new(Request $request){
        $categoria = Categoria::
        where('empresa_id', $request->empresa_id)
        ->first();
        if($categoria == null){
            //nao tem categoria
            session()->flash('mensagem_alerta', 'Cadastre ao menos uma categoria!');
            return redirect('/categorias');
        }

        $anps = Produto::lista_ANP();
        $natureza = Produto::
        firstNatureza($request->empresa_id);

        if($natureza == null){

            session()->flash('mensagem_alerta', 'Cadastre uma natureza de operação!');
            return redirect('/naturezaOperacao');
        }

        $categorias = Categoria::
        where('empresa_id', $request->empresa_id)
        ->get();

        $categoriasDelivery = [];

        $listaCST_PIS_COFINS = Produto::listaCST_PIS_COFINS();
        $listaCST_IPI = Produto::listaCST_IPI();
        $tributacao = Tributacao::
        where('empresa_id', $request->empresa_id)
        ->first();

        if($tributacao == null){
            session()->flash('mensagem_alerta', 'Informe a tributação padrão!');
            return redirect('/tributos');
        }

        if($tributacao->regime == 1){
            $listaCSTCSOSN = Produto::listaCST();
        }else{
            $listaCSTCSOSN = Produto::listaCSOSN();
        }

        $unidadesDeMedida = Produto::unidadesMedida();
        $config = ConfigNota::
        where('empresa_id', $request->empresa_id)
        ->first();

        if($config == null){
            session()->flash('mensagem_alerta', 'Informe a configuração do emitente!');
            return redirect('/configNF');
        }

        $divisoes = DivisaoGrade::
        where('empresa_id', $request->empresa_id)
        ->where('sub_divisao', false)
        ->get();

        $subDivisoes = DivisaoGrade::
        where('empresa_id', $request->empresa_id)
        ->where('sub_divisao', true)
        ->get();

        $categoriasEcommerce = CategoriaProdutoEcommerce::
        where('empresa_id', $request->empresa_id)
        ->get();

        $marcas = Marca::
        where('empresa_id', $request->empresa_id)
        ->get();

        $subs = SubCategoria::
        select('sub_categorias.*')
        ->join('categorias', 'categorias.id', '=', 'sub_categorias.categoria_id')
        ->where('empresa_id', $request->empresa_id)
        ->get();

        $subsEcommerce = SubCategoriaEcommerce::
        select('sub_categoria_ecommerces.*')
        ->join('categoria_produto_ecommerces', 'categoria_produto_ecommerces.id', '=', 'sub_categoria_ecommerces.categoria_id')
        ->where('empresa_id', $this->empresa_id)
        ->get();

        $telas = TelaPedido::
        where('empresa_id', $request->empresa_id)
        ->get();

        return view('produtos/register')
        ->with('categorias', $categorias)
        ->with('categoriasEcommerce', $categoriasEcommerce)
        ->with('unidadesDeMedida', $unidadesDeMedida)
        ->with('listaCSTCSOSN', $listaCSTCSOSN)
        ->with('listaCST_PIS_COFINS', $listaCST_PIS_COFINS)
        ->with('listaCST_IPI', $listaCST_IPI)
        ->with('marcas', $marcas)
        ->with('anps', $anps)
        ->with('subs', $subs)
        ->with('telas', $telas)
        ->with('subsEcommerce', $subsEcommerce)
        ->with('config', $config)
        ->with('divisoes', $divisoes)
        ->with('subDivisoes', $subDivisoes)
        ->with('tributacao', $tributacao)
        ->with('natureza', $natureza)
        ->with('categoriasDelivery', $categoriasDelivery)
        ->with('produtoJs', true)
        ->with('gradeJs', true)
        // ->with('contratoJs', true)
        ->with('title', 'Cadastrar Produto');
    }

    public function save(Request $request)
    {
        $this->_validate($request);
        if ($request->ecommerce) {
            $this->_validateEcommerce($request);
        }
        try {
            $result = DB::transaction(function () use ($request) {
                $produto = new Produto();

                $anps = Produto::lista_ANP();
                $descAnp = '';

                foreach ($anps as $key => $a) {
                    if ($key == $request->anp) {
                        $descAnp = $a;
                    }
                }

                $request->merge([ 'composto' => $request->input('composto') ? true : false ]);
                $request->merge([ 'info_tecnica_composto' => $request->info_tecnica_composto ?? '' ]);
                $request->merge([ 'observacao' => $request->observacao ?? '' ]);
                $request->merge([ 'info_adicional_item' => $request->info_adicional_item ?? '' ]);
                $request->merge([ 'inativo' => $request->input('inativo') ? true : false ]);
                $request->merge([ 'valor_livre' => $request->input('valor_livre') ? true : false ]);
                $request->merge([ 'gerenciar_estoque' => $request->input('gerenciar_estoque') ? true : false ]);
                $request->merge([ 'reajuste_automatico' => $request->input('reajuste_automatico') ? true : false ]);
                $request->merge([ 'valor_venda' => str_replace(",", ".", $request->input('valor_venda')) ]);
                $request->merge([ 'percentual_lucro' => str_replace(",", ".", $request->input('percentual_lucro')) ]);
                $request->merge([ 'valor_compra' => str_replace(",", ".", $request->input('valor_compra')) ]);
                $request->merge([ 'conversao_unitaria' => $request->input('conversao_unitaria') ? $request->input('conversao_unitaria') : 1 ]);
                $request->merge([ 'codBarras' => $request->input('codBarras') ?? 'SEM GTIN' ]);
                $request->merge([ 'CST_CSOSN' => $request->input('CST_CSOSN') ?? '0' ]);
                $request->merge([ 'CST_CSOSN_EXP' => $request->input('CST_CSOSN_EXP') ?? '' ]);
                $request->merge([ 'CST_PIS' => $request->input('CST_PIS') ?? '0' ]);
                $request->merge([ 'CST_COFINS' => $request->input('CST_COFINS') ?? '0' ]);

                $request->merge([ 'CST_CSOSN_entrada' => $request->input('CST_CSOSN_entrada') ?? '0' ]);
                $request->merge([ 'CST_PIS_entrada' => $request->input('CST_PIS_entrada') ?? '0' ]);
                $request->merge([ 'CST_COFINS_entrada' => $request->input('CST_COFINS_entrada') ?? '0' ]);
                $request->merge([ 'CST_IPI_entrada' => $request->input('CST_IPI_entrada') ?? '0' ]);
                $request->merge([ 'CST_IPI' => $request->input('CST_IPI') ?? '0' ]);
                $request->merge([ 'codigo_anp' => $request->anp != '' ? $request->anp : '' ]);
                $request->merge([ 'descricao_anp' => $request->anp != '' ? $request->anp : '' ]);

                $request->merge([ 'perc_glp' => $request->perc_glp != '' ? __replace($request->perc_glp) : '' ]);
                $request->merge([ 'perc_gnn' => $request->perc_gnn != '' ? __replace($request->perc_gnn) : '' ]);
                $request->merge([ 'perc_gni' => $request->perc_gni != '' ? __replace($request->perc_gni) : '' ]);
                $request->merge([ 'valor_partida' => $request->valor_partida != '' ? __replace($request->valor_partida) : '' ]);
                $request->merge([ 'unidade_tributavel' => $request->unidade_tributavel != '' ? $request->unidade_tributavel : '' ]);
                $request->merge([ 'quantidade_tributavel' => $request->quantidade_tributavel != '' ? __replace($request->quantidade_tributavel) : '' ]);

                $request->merge([ 'cListServ' => $request->cListServ ?? '' ]);
                $request->merge([ 'alerta_vencimento' => $request->alerta_vencimento ?? 0 ]);
                $request->merge([ 'imagem' => '' ]);
                $request->merge([ 'estoque_minimo' => $request->estoque_minimo ?? 0 ]);
                $request->merge([ 'referencia_balanca' => $request->referencia_balanca ?? 0 ]);
                $request->merge([ 'referencia' => $request->referencia ?? '' ]);

                $request->merge([ 'largura' => $request->largura ?? 0 ]);
                $request->merge([ 'comprimento' => $request->comprimento ?? 0 ]);
                $request->merge([ 'altura' => $request->altura ?? 0 ]);
                $request->merge([ 'peso_liquido' => $request->peso_liquido ?? 0 ]);
                $request->merge([ 'peso_bruto' => $request->peso_bruto ?? 0 ]);
                $request->merge([ 'tela_pedido_id' => $request->tela_pedido_id ?? 0 ]);
                $request->merge([ 'limite_maximo_desconto' => $request->limite_maximo_desconto ?? 0 ]);
                $request->merge([ 'perc_icms' => $request->perc_icms ? __replace($request->perc_icms) : 0 ]);
                $request->merge([ 'perc_pis' => $request->perc_pis ? __replace($request->perc_pis) : 0 ]);
                $request->merge([ 'perc_cofins' => $request->perc_cofins ? __replace($request->perc_cofins) : 0 ]);
                $request->merge([ 'perc_ipi' => $request->perc_ipi ? __replace($request->perc_ipi) : 0 ]);
                $request->merge([ 'pRedBC' => $request->pRedBC ? __replace($request->pRedBC) : 0 ]);
                $request->merge([ 'adRemICMSRet' => $request->adRemICMSRet ? __replace($request->adRemICMSRet) : 0 ]);
                $request->merge([ 'pBio' => $request->pBio ? __replace($request->pBio) : 0 ]);
                $request->merge([ 'peso' => $request->peso ? __replace($request->peso) : 0 ]);

                $request->merge([ 'perc_frete' => $request->perc_frete ? __replace($request->perc_frete) : 0 ]);
                $request->merge([ 'perc_outros' => $request->perc_outros ? __replace($request->perc_outros) : 0 ]);
                $request->merge([ 'perc_mlv' => $request->perc_mlv ? __replace($request->perc_mlv) : 0 ]);
                $request->merge([ 'perc_mva' => $request->perc_mva ? __replace($request->perc_mva) : 0 ]);

                $request->merge([ 'cBenef' => $request->cBenef ? $request->cBenef : '' ]);
                $request->merge([ 'CEST' => $request->CEST ?? '' ]);

                $request->merge([ 'perc_icms_interestadual' => $request->perc_icms_interestadual ? __replace($request->perc_icms_interestadual) : 0 ]);
                $request->merge([ 'perc_icms_interno' => $request->perc_icms_interno ? __replace($request->perc_icms_interno) : 0 ]);
                $request->merge([ 'perc_fcp_interestadual' => $request->perc_fcp_interestadual ? __replace($request->perc_fcp_interestadual) : 0 ]);

                $request->merge([ 'renavam' => $request->renavam ?? '' ]);
                $request->merge([ 'placa' => $request->placa ?? '' ]);
                $request->merge([ 'chassi' => $request->chassi ?? '' ]);
                $request->merge([ 'combustivel' => $request->combustivel ?? '' ]);
                $request->merge([ 'ano_modelo' => $request->ano_modelo ?? '' ]);
                $request->merge([ 'cor_veiculo' => $request->cor_veiculo ?? '' ]);

                $request->merge([ 'lote' => $request->lote ?? '' ]);
                $request->merge([ 'CFOP_entrada_estadual' => $request->CFOP_entrada_estadual ?? '' ]);
                $request->merge([ 'CFOP_entrada_inter_estadual' => $request->CFOP_entrada_inter_estadual ?? '' ]);
                $request->merge([ 'vencimento' => $request->vencimento ?? '' ]);

                $locais = json_encode($request->local);
                if ($request->local == null) {
                    $locais = '["-1"]';
                }
                $request->merge([ 'locais' => $locais ]);

                $request->merge([ 'valor_locacao' => $request->valor_locacao ? __replace($request->valor_locacao) : 0 ]);
                $request->merge([ 'tipo_dimensao' => $request->tipo_dimensao ?? '' ]);
                $request->merge([ 'perc_comissao' => $request->perc_comissao ? __replace($request->perc_comissao) : 0 ]);
                $request->merge([ 'valor_comissao' => $request->valor_comissao ? __replace($request->valor_comissao) : 0 ]);
                $request->merge([ 'acrescimo_perca' => $request->acrescimo_perca ? __replace($request->acrescimo_perca) : 0 ]);
                $request->merge([ 'custo_assessor' => $request->custo_assessor ? __replace($request->custo_assessor) : 0 ]);

                $request->merge([ 'pICMSST' => $request->pICMSST ? __replace($request->pICMSST) : 0 ]);
                $request->merge([ 'modBCST' => $request->modBCST ?? 0 ]);
                $request->merge([ 'modBC' => $request->modBC ?? 0 ]);

                $request->merge([ 'pOrig' => $request->pOrig ? __replace($request->pOrig) : 0 ]);

                // === INCLUIR OS NOVOS RECURSOS AQUI ===
                $request->merge([ 'controla_pesagem' => $request->input('controla_pesagem') ? true : false ]);
                $request->merge([ 'produto_referenciado_id' => $request->input('produto_referenciado_id') ?? null ]);
                $request->merge([ 'produto_prateleira_id' => $request->input('produto_prateleira_id') ?? null ]);

                $request->merge([ 'cst_ibs_cbs' => $request->input('cst_ibs_cbs') ?? '' ]);
                $request->merge([ 'class_trib_ibs_cbs' => $request->input('class_trib_ibs_cbs') ?? '' ]);
                $request->merge([ 'reducao_ibs' => $request->input('reducao_ibs') ? __replace($request->input('reducao_ibs')) : 0 ]);
                $request->merge([ 'reducao_cbs' => $request->input('reducao_cbs') ? __replace($request->input('reducao_cbs')) : 0 ]);

                // regra Delphi (CST 200 => buscar percentuais pela class_trib)
                if (($request->input('cst_ibs_cbs') ?? '') === '200' && ($request->input('class_trib_ibs_cbs') ?? '') !== '') {
                    $vals = $this->resolveReducaoIbsCbs($request->input('class_trib_ibs_cbs'));
                    $request->merge([
                        'reducao_ibs' => $vals['predibs'],
                        'reducao_cbs' => $vals['predcbs'],
                    ]);
                }


                if (!$request->grade) {
                    $request->merge([ 'referencia_grade' => Str::random(20) ]);
                    $request->merge([ 'grade' => false ]);
                    $request->merge([ 'str_grade' => '' ]);

                    $result = $produto->create($request->all());
                    $this->inserePercentualPorEstado($result);

                    $this->criarLog($result);
                    $produto = Produto::find($result->id);

                    $nomeImagem = $this->salveImagemProduto($request, $produto);

                    if ($request->delivery) {
                        $this->salvarProdutoNoDelivery($request, $produto, $nomeImagem);
                    }

                    $this->saveIbpt($produto);
                    if ($request->ecommerce) {
                        $this->salvarProdutoEcommerce($request, $produto, $nomeImagem);
                    }

                    $mensagem_sucesso = "Produto cadastrado com sucesso!";
                    $estoque = $request->estoque;
                    if ($estoque) {
                        $estoque = __replace($request->estoque);
                        $data = [
                            'produto_id' => $produto->id,
                            'usuario_id' => get_id_user(),
                            'quantidade' => $estoque,
                            'tipo' => 'incremento',
                            'observacao' => '',
                            'empresa_id' => $this->empresa_id
                        ];

                        $estoque = $request->conversao_unitaria * $estoque;
                        AlteracaoEstoque::create($data);
                        $stockMove = new StockMove();
                        $stockMove->pluStock($produto->id,
                            $estoque, str_replace(",", ".", $produto->valor_compra));
                        $mensagem_sucesso = "Produto cadastrado com sucesso, e atribuido estoque!";
                    }

                    $locais = isset($request->local) ? $request->local : [];

                    if (sizeof($locais) > 0) {
                        session()->flash("mensagem_sucesso", "Produto cadastrado com sucesso, informe o estoque");
                        return redirect('/produtos/set-estoque/' . $result->id);
                    } elseif ($request->composto == true) {
                        session()->flash("mensagem_sucesso", "Produto cadastrado com sucesso, informe a composição");
                        return redirect('/produtos/receita/' . $result->id);
                    } else {
                        if ($result) {
                            session()->flash("mensagem_sucesso", $mensagem_sucesso);
                        } else {
                            session()->flash('mensagem_erro', 'Erro ao cadastrar produto!');
                        }
                        return redirect('/produtos');
                    }
                } else {
                    $produtoGrade = new ProdutoGrade();

                    // Certificar-se de que os mesmos campos estão disponíveis para cada variação de grade:
                    $request->merge([ 'controla_pesagem' => $request->input('controla_pesagem') ? true : false ]);
                    $request->merge([ 'produto_referenciado_id' => $request->input('produto_referenciado_id') ?? null ]);
                    $request->merge([ 'produto_prateleira_id' => $request->input('produto_prateleira_id') ?? null ]);

                    $nomeImagem = "";
                    if ($request->hasFile('file')) {
                        $nomeImagem = $this->salveImagemProdutoTemp($request);
                    }
                    $res = $produtoGrade->salvar($request, $nomeImagem);

                    if ($res == "ok") {
                        session()->flash("mensagem_sucesso", "Produto cadastrado como grade!");
                    } else {
                        session()->flash('mensagem_erro', 'Erro ao cadastrar produto, confira a grade!');
                    }
                    $locais = isset($request->local) ? $request->local : [];

                    if (sizeof($locais) > 0) {
                        $lastProduto = Produto::where('empresa_id', $this->empresa_id)
                            ->orderBy('id', 'desc')
                            ->first();
                        return redirect('/produtos/set-estoque/' . $lastProduto->id);
                    }
                    return redirect('/produtos');
                }
            });

            return $result;
        } catch (\Exception $e) {
            __saveError($e, $this->empresa_id);
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
            return redirect('/produtos');
        }
    }

    private function inserePercentualPorEstado($produto){
        $tribucoesCadastradas = TributacaoUf::
        select('tributacao_ufs.uf')
        ->join('produtos', 'produtos.id', '=', 'tributacao_ufs.produto_id')
        ->where('empresa_id', $this->empresa_id)
        ->distinct()
        ->get();

        foreach($tribucoesCadastradas as $t){
            $ex = TributacaoUf::
            where('produto_id', $produto->id)
            ->where('uf', $t->uf)
            ->exists();

            if(!$ex){
                $temp = TributacaoUf::
                where('uf', $t->uf)
                ->join('produtos', 'produtos.id', '=', 'tributacao_ufs.produto_id')
                ->where('empresa_id', $this->empresa_id)
                ->first();

                $res = TributacaoUf::create([
                    'produto_id' => $produto->id,
                    'uf' => $t->uf,
                    'percentual_icms' => $temp->percentual_icms
                ]);

            }
        }

    }

    private function criarLog($objeto, $tipo = 'criar'){
        if(isset(session('user_logged')['log_id'])){
            $record = [
                'tipo' => $tipo,
                'usuario_log_id' => session('user_logged')['log_id'],
                'tabela' => 'produtos',
                'registro_id' => $objeto->id,
                'empresa_id' => $this->empresa_id
            ];
            __saveLog($record);
        }
    }
    private function saveIbpt($produto){
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($config->token_ibpt != ""){
            $ibptService = new IbptService($config->token_ibpt, preg_replace('/[^0-9]/', '', $config->cnpj));
            $data = [
                'ncm' => preg_replace('/[^0-9]/', '', $produto->NCM),
                'uf' => $config->UF,
                'extarif' => 0,
                'descricao' => $produto->nome,
                'unidadeMedida' => $produto->unidade_venda,
                'valor' => number_format(0, $config->casas_decimais),
                'gtin' => $produto->codBarras,
                'codigoInterno' => 0
            ];
            $resp = $ibptService->consulta($data);
            if(!isset($resp->httpcode)){
                if($resp->Codigo){
                    $dataIbpt = [
                        'produto_id' => $produto->id,
                        'codigo' => $resp->Codigo,
                        'uf' => $resp->UF,
                        'descricao' => $resp->Descricao,
                        'nacional' => $resp->Nacional,
                        'estadual' => $resp->Estadual,
                        'importado' => $resp->Importado,
                        'municipal' => $resp->Municipal,
                        'vigencia_inicio' => $resp->VigenciaInicio,
                        'vigencia_fim' => $resp->VigenciaFim,
                        'chave' => $resp->Chave,
                        'versao' => $resp->Versao,
                        'fonte' => $resp->Fonte
                    ];

                    ProdutoIbpt::create($dataIbpt);
                }

            }
        }
    }

    public function edit($id){
        $natureza = Produto::firstNatureza($this->empresa_id);
        $anps = Produto::lista_ANP();

        if($natureza == null){
            session()->flash('mensagem_erro', 'Cadastre uma natureza de operação!');
            return redirect('/naturezaOperacao');
        }

        $produto = new Produto();

            // $listaCSTCSOSN = Produto::listaCSTCSOSN();
        $listaCST_PIS_COFINS = Produto::listaCST_PIS_COFINS();
        $listaCST_IPI = Produto::listaCST_IPI();

        $categorias = Categoria::
        where('empresa_id', $this->empresa_id)
        ->get();

        $unidadesDeMedida = Produto::unidadesMedida();
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($tributacao == null){
            session()->flash('mensagem_erro', 'Informe a tributação padrão!');
            return redirect('/tributos');
        }

        $resp = $produto
        ->where('id', $id)->first();

        $categoriasDelivery = [];

        if($tributacao->regime == 1){
            $listaCSTCSOSN = Produto::listaCST();
        }else{
            $listaCSTCSOSN = Produto::listaCSOSN();
        }

        if($tributacao == null){

            session()->flash('mensagem_erro', 'Informe a tributação padrão!');
            return redirect('tributos');
        }

        $divisoes = DivisaoGrade::
        where('empresa_id', $this->empresa_id)
        ->where('sub_divisao', false)
        ->get();

        $subDivisoes = DivisaoGrade::
        where('empresa_id', $this->empresa_id)
        ->where('sub_divisao', true)
        ->get();

        $marcas = Marca::
        where('empresa_id', $this->empresa_id)
        ->get();

        $subs = SubCategoria::
        select('sub_categorias.*')
        ->join('categorias', 'categorias.id', '=', 'sub_categorias.categoria_id')
        ->where('empresa_id', $this->empresa_id)
        ->get();

        $subsEcommerce = SubCategoriaEcommerce::
        select('sub_categoria_ecommerces.*')
        ->join('categoria_produto_ecommerces', 'categoria_produto_ecommerces.id', '=', 'sub_categoria_ecommerces.categoria_id')
        ->where('empresa_id', $this->empresa_id)
        ->get();

        $categoriasEcommerce = CategoriaProdutoEcommerce::
        where('empresa_id', $this->empresa_id)
        ->get();

        $telas = TelaPedido::
        where('empresa_id', $this->empresa_id)
        ->get();

        if(valida_objeto($resp)){
            if(!$resp->grade){
                return view('produtos/register')
                ->with('produto', $resp)
                ->with('config', $config)
                ->with('tributacao', $tributacao)
                ->with('marcas', $marcas)
                ->with('subs', $subs)
                ->with('natureza', $natureza)
                ->with('categoriasEcommerce', $categoriasEcommerce)
                ->with('divisoes', $divisoes)
                ->with('subDivisoes', $subDivisoes)
                ->with('listaCSTCSOSN', $listaCSTCSOSN)
                ->with('listaCST_PIS_COFINS', $listaCST_PIS_COFINS)
                ->with('listaCST_IPI', $listaCST_IPI)
                ->with('categoriasDelivery', $categoriasDelivery)
                ->with('anps', $anps)
                ->with('subsEcommerce', $subsEcommerce)
                ->with('unidadesDeMedida', $unidadesDeMedida)
                ->with('categorias', $categorias)
                ->with('telas', $telas)
                ->with('produtoJs', true)
                ->with('gradeJs', true)
                ->with('title', 'Editar Produto');
            }else{
                return view('produtos/register_grade')
                ->with('produto', $resp)
                ->with('config', $config)
                ->with('tributacao', $tributacao)
                ->with('subsEcommerce', $subsEcommerce)
                ->with('marcas', $marcas)
                ->with('categoriasEcommerce', $categoriasEcommerce)
                ->with('subs', $subs)
                ->with('natureza', $natureza)
                ->with('telas', $telas)
                ->with('divisoes', $divisoes)
                ->with('subDivisoes', $subDivisoes)
                ->with('listaCSTCSOSN', $listaCSTCSOSN)
                ->with('listaCST_PIS_COFINS', $listaCST_PIS_COFINS)
                ->with('listaCST_IPI', $listaCST_IPI)
                ->with('categoriasDelivery', $categoriasDelivery)
                ->with('anps', $anps)
                ->with('unidadesDeMedida', $unidadesDeMedida)
                ->with('categorias', $categorias)
                ->with('produtoJs', true)
                ->with('gradeJs', true)
                ->with('title', 'Editar Produto Grade');
            }
        }else{
            return redirect('/403');
        }

    }

    public function editGrade($id){
        $natureza = Produto::firstNatureza($this->empresa_id);
        $anps = Produto::lista_ANP();

        if($natureza == null){
            session()->flash('mensagem_erro', 'Cadastre uma natureza de operação!');
            return redirect('/naturezaOperacao');
        }

        $produto = new Produto();

            // $listaCSTCSOSN = Produto::listaCSTCSOSN();
        $listaCST_PIS_COFINS = Produto::listaCST_PIS_COFINS();
        $listaCST_IPI = Produto::listaCST_IPI();

        $categorias = Categoria::
        where('empresa_id', $this->empresa_id)
        ->get();

        $unidadesDeMedida = Produto::unidadesMedida();
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
        ->first();

        $resp = $produto
        ->where('id', $id)->first();

        $categoriasDelivery = [];

        if($tributacao->regime == 1){
            $listaCSTCSOSN = Produto::listaCST();
        }else{
            $listaCSTCSOSN = Produto::listaCSOSN();
        }

        if($tributacao == null){

            session()->flash('mensagem_erro', 'Informe a tributação padrão!');
            return redirect('tributos');
        }

        $divisoes = DivisaoGrade::
        where('empresa_id', $this->empresa_id)
        ->where('sub_divisao', false)
        ->get();

        $subDivisoes = DivisaoGrade::
        where('empresa_id', $this->empresa_id)
        ->where('sub_divisao', true)
        ->get();

        $marcas = Marca::
        where('empresa_id', $this->empresa_id)
        ->get();

        $subs = SubCategoria::
        select('sub_categorias.*')
        ->join('categorias', 'categorias.id', '=', 'sub_categorias.categoria_id')
        ->where('empresa_id', $this->empresa_id)
        ->get();

        $subsEcommerce = SubCategoriaEcommerce::
        select('sub_categoria_ecommerces.*')
        ->join('categoria_produto_ecommerces', 'categoria_produto_ecommerces.id', '=', 'sub_categoria_ecommerces.categoria_id')
        ->where('empresa_id', $this->empresa_id)
        ->get();

        $categoriasEcommerce = CategoriaProdutoEcommerce::
        where('empresa_id', $this->empresa_id)
        ->get();

        $telas = TelaPedido::
        where('empresa_id', $this->empresa_id)
        ->get();

        if(valida_objeto($resp)){

            return view('produtos/register')
            ->with('produto', $resp)
            ->with('config', $config)
            ->with('subsEcommerce', $subsEcommerce)
            ->with('marcas', $marcas)
            ->with('categoriasEcommerce', $categoriasEcommerce)
            ->with('subs', $subs)
            ->with('tributacao', $tributacao)
            ->with('natureza', $natureza)
            ->with('divisoes', $divisoes)
            ->with('subDivisoes', $subDivisoes)
            ->with('listaCSTCSOSN', $listaCSTCSOSN)
            ->with('listaCST_PIS_COFINS', $listaCST_PIS_COFINS)
            ->with('listaCST_IPI', $listaCST_IPI)
            ->with('categoriasDelivery', $categoriasDelivery)
            ->with('telas', $telas)
            ->with('tipo_grade', 1)
            ->with('anps', $anps)
            ->with('unidadesDeMedida', $unidadesDeMedida)
            ->with('categorias', $categorias)
            ->with('produtoJs', true)
            ->with('title', 'Editar Produto');
        }else{
            return redirect('/403');
        }

    }
    private function salveImagemProduto($request, $produto){
        if($request->hasFile('file')){

            $public = env('SERVIDOR_WEB') ? 'public/' : '';
                //unlink anterior
            if(file_exists(public_path('imgs_produtos/').$produto->imagem) && $produto->imagem != '')
                unlink(public_path('imgs_produtos/').$produto->imagem);

            $file = $request->file('file');

            $extensao = $file->getClientOriginalExtension();
            $nomeImagem = Str::random(25) . ".".$extensao;

            $upload = $file->move(public_path('imgs_produtos'), $nomeImagem);
            $produto->imagem = $nomeImagem;
            $produto->save();

            return $nomeImagem;
        }else{
            return "";
        }
    }
    private function salveImagemProdutoTemp($request){
        if($request->hasFile('file')){

            $public = env('SERVIDOR_WEB') ? 'public/' : '';
                //unlink anterior

            $file = $request->file('file');

            $extensao = $file->getClientOriginalExtension();
            $nomeImagem = md5($file->getClientOriginalName()).".".$extensao;

            $upload = $file->move(public_path('imgs_produtos'), $nomeImagem);

            return $nomeImagem;
        }else{
            return "";
        }
    }

    public function pesquisa(Request $request){
        $pesquisa = $request->input('pesquisa');

            // $produtos = Produto::where('nome', 'LIKE', "%$pesquisa%")
            // ->where('empresa_id', $request->empresa_id)->get();

        $produtos = Produto::
        where('nome', 'LIKE', "%$pesquisa%")
        ->where('empresa_id', $this->empresa_id)
        ->groupBy('referencia_grade')
        ->orderBy('inativo')
        ->orderBy('id', 'desc')
        ->paginate(15);

        $categorias = Categoria::all();
        $produtos = $this->setaEstoque($produtos);


        return view('produtos/list')
        ->with('categorias', $categorias)
        ->with('produtos', $produtos)
        ->with('title', 'Filtro Produto');
    }

    public function filtroCategoria(Request $request){
        $categoria = $request->input('categoria');
        $estoque = $request->input('estoque');
        $pesquisa = $request->input('pesquisa');
        $tipo = $request->input('tipo');
        $marca = $request->input('marca');
        $filial_id = $request->input('filial_id');

        $porCodigoBarras = is_numeric($pesquisa);

        if($tipo == 'cod_barras'){
            $query = Produto::where('codBarras', 'LIKE', "%$pesquisa%");
        }else if($tipo == 'referencia'){
            $query = Produto::where('referencia', 'LIKE', "%$pesquisa%");
        }else{
            $query = Produto::where('nome', 'LIKE', "%$pesquisa%");
        }
        if($categoria != '-'){
            $query->where('categoria_id', $categoria);
        }
        if($marca != '-'){
            $query->where('marca_id', $marca);
        }

        $permissaoAcesso = __getLocaisUsarioLogado();
        $query->where('empresa_id', $request->empresa_id)
        ->groupBy('referencia_grade')
        ->orderBy('inativo')
        ->where(function($query) use ($permissaoAcesso){
            if($permissaoAcesso != null){
                foreach ($permissaoAcesso as $value) {
                    $query->orWhere('locais', 'like', "%{$value}%");
                }
            }
        })

        ->orderBy('id', 'desc')
        ->limit(100);
        $prods = $query->get();


        if($estoque != '--'){
            $temp = [];
            foreach($prods as $p){
                if($estoque == 1){
                    if($p->estoque && $p->estoque->quantidade > 0){
                        array_push($temp, $p);
                    }
                }else{
                    if(!$p->estoque || $p->estoque->quantidade < 0){
                        array_push($temp, $p);
                    }
                }
            }
            $produtos = $temp;
        }

        $produtos = [];
        if($filial_id){
            foreach($prods as $p){
                $l = json_decode($p->locais);
                if(is_array($l)){
                    if(in_array($filial_id, $l)){
                        array_push($produtos, $p);
                    }
                }
            }
        }else{
            $produtos = $prods;
        }

        $categorias = Categoria::
        where('empresa_id', $this->empresa_id)
        ->get();

        $categoria = Categoria::find($categoria);
        $produtos = $this->setaEstoque($produtos);

            // if(sizeof($produtos) == 1 && $porCodigoBarras){
            //     return redirect('/produtos/edit/'.$produtos[0]->id);
            // }

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $ibpt = $config->token_ibpt != "";

        $marcas = Marca::
        where('empresa_id', $this->empresa_id)
        ->get();

        return view('produtos/list')
        ->with('produtos', $produtos)
        ->with('categorias', $categorias)
        ->with('marcas', $marcas)
        ->with('paraImprimir', true)
        ->with('categoria', $request->categoria)
        ->with('estoque', $estoque)
        ->with('tipo', $tipo)
        ->with('marca', $marca)
        ->with('filial_id', $filial_id)
        ->with('ibpt', $ibpt)
        ->with('pesquisa', $pesquisa)
        ->with('title', 'Filtro Produto');
    }

    public function relatorio(Request $request){
        $categoria = $request->input('categoria');
        $estoque = $request->input('estoque');
        $pesquisa = $request->input('pesquisa');
        $tipo = $request->input('tipo');
        $marca = $request->input('marca');
        $filial_id = $request->input('filial_id');

        $porCodigoBarras = is_numeric($pesquisa);

        $permissaoAcesso = __getLocaisUsarioLogado();

        if($tipo == 'cod_barras'){
            $query = Produto::where('codBarras', 'LIKE', "%$pesquisa%");
        }else if($tipo == 'referencia'){
            $query = Produto::where('referencia', 'LIKE', "%$pesquisa%");
        }else{
            $query = Produto::where('nome', 'LIKE', "%$pesquisa%");
        }
        if($categoria != '-'){
            $query = Produto::where('categoria_id', $categoria);
        }

        if($marca != '-'){
            $query = Produto::where('marca_id', $marca);
        }

        $query->where('empresa_id', $request->empresa_id)
        // ->groupBy('referencia_grade')
        ->orderBy('inativo')
        ->where(function($query) use ($permissaoAcesso){
            if($permissaoAcesso != null){
                foreach ($permissaoAcesso as $value) {
                    $query->orWhere('locais', 'like', "%{$value}%");
                }
            }
        })
        ->orderBy('id', 'desc');

        $produtos = $query->get();

        if($estoque != '--'){
            $temp = [];
            foreach($produtos as $p){
                if($estoque == 1){
                    if($p->estoque && $p->estoque->quantidade > 0){
                        array_push($temp, $p);
                    }
                }else{
                    if(!$p->estoque || $p->estoque->quantidade < 0){
                        array_push($temp, $p);
                    }
                }
            }
            $produtos = $temp;
        }

        $prods = [];
        if($filial_id){

            foreach($produtos as $p){
                $l = json_decode($p->locais);
                if(is_array($l)){
                    if(in_array($filial_id, $l)){
                        array_push($prods, $p);
                    }
                }
            }

            $produtos = $prods;
        }

        $p = view('produtos/relatorio_produtos')
        ->with('title', 'Relatório de produtos')
        ->with('produtos', $produtos);

            // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Relatório de produtos.pdf", array("Attachment" => false));


    }

    public function receita($id){
        $resp = Produto::
        where('id', $id)
        ->first();

        $produtos = Produto::where('empresa_id', $this->empresa_id)->get();

        return view('produtos/receita')
        ->with('produto', $resp)
        ->with('produtos', $produtos)
        ->with('produtoJs', true)
        ->with('title', 'Receita do Produto');

    }

    public function update(Request $request)
    {
        if ($request->ecommerce) {
            $this->_validateEcommerce($request);
        }
        $this->_validate($request);
        try {
            $result = DB::transaction(function () use ($request) {
                $product = new Produto();

                $id = $request->input('id');
                $resp = $product
                    ->where('id', $id)->first();

                $anps = Produto::lista_ANP();
                $descAnp = '';
                foreach ($anps as $key => $a) {
                    if ($key == $request->anp) {
                        $descAnp = $a;
                    }
                }

                $locais = json_encode($request->local);
                if ($request->local == null) {
                    $locais = '["-1"]';
                }
                $request->merge([ 'locais' => $locais ]);

                $resp->nome = $request->input('nome');
                $resp->categoria_id = $request->input('categoria_id');
                $resp->sub_categoria_id = $request->input('sub_categoria_id');
                $resp->marca_id = $request->input('marca_id');
                $resp->cor = $request->input('cor');
                $resp->valor_venda = str_replace(",", ".", $request->input('valor_venda'));
                $resp->valor_compra = str_replace(",", ".", $request->input('valor_compra'));

                $resp->percentual_lucro = str_replace(",", ".", $request->input('percentual_lucro'));
                $resp->NCM = $request->input('NCM');
                $resp->CEST = $request->input('CEST') ?? '';
                $resp->tipo_item = $request->input('tipo_item');
                $resp->ca_numero = $request->input('ca_numero');
                $resp->fabricante = $request->input('fabricante');

                $resp->CST_CSOSN = $request->input('CST_CSOSN');
                $resp->CST_CSOSN_EXP = $request->input('CST_CSOSN_EXP');

                $resp->CST_PIS = $request->input('CST_PIS');
                $resp->CST_COFINS = $request->input('CST_COFINS');
                $resp->CST_IPI = $request->input('CST_IPI');
                $resp->cenq_ipi = $request->input('cenq_ipi');

                $resp->cst_ibs_cbs = $request->input('cst_ibs_cbs') ?? '';
                $resp->class_trib_ibs_cbs = $request->input('class_trib_ibs_cbs') ?? '';
                $resp->reducao_ibs = $request->input('reducao_ibs') ? __replace($request->input('reducao_ibs')) : 0;
                $resp->reducao_cbs = $request->input('reducao_cbs') ? __replace($request->input('reducao_cbs')) : 0;

                if (($resp->cst_ibs_cbs ?? '') === '200' && ($resp->class_trib_ibs_cbs ?? '') !== '') {
                    $vals = $this->resolveReducaoIbsCbs($resp->class_trib_ibs_cbs);
                    $resp->reducao_ibs = $vals['predibs'];
                    $resp->reducao_cbs = $vals['predcbs'];
                }

                $resp->CST_CSOSN_entrada = $request->input('CST_CSOSN_entrada');
                $resp->CST_PIS_entrada = $request->input('CST_PIS_entrada');
                $resp->CST_COFINS_entrada = $request->input('CST_COFINS_entrada');
                $resp->CST_IPI_entrada = $request->input('CST_IPI_entrada');
                // $resp->CFOP = $request->input('CFOP');
                $resp->unidade_venda = $request->input('unidade_venda');
                $resp->unidade_compra = $request->input('unidade_compra');
                $resp->conversao_unitaria = $request->input('conversao_unitaria') ? $request->input('conversao_unitaria') : $resp->conversao_unitaria;
                $resp->codBarras = $request->input('codBarras') ?? 'SEM GTIN';

                $resp->perc_icms = $request->perc_icms ? __replace($request->perc_icms) : 0;
                $resp->perc_pis = $request->perc_pis ? __replace($request->perc_pis) : 0;
                $resp->perc_cofins = $request->perc_cofins ? __replace($request->perc_cofins) : 0;
                $resp->perc_ipi = $request->perc_ipi ? __replace($request->perc_ipi) : 0;
                $resp->perc_iss = $request->perc_iss ? __replace($request->perc_iss) : 0;
                $resp->adRemICMSRet = $request->adRemICMSRet ? __replace($request->adRemICMSRet) : 0;
                $resp->pBio = $request->pBio ? __replace($request->pBio) : 0;
                $resp->peso = $request->peso ? __replace($request->peso) : 0;
                $resp->cListServ = $request->input('cListServ');

                $resp->CFOP_saida_estadual = $request->input('CFOP_saida_estadual');
                $resp->CFOP_entrada_estadual = $request->input('CFOP_entrada_estadual') ?? '';
                $resp->CFOP_saida_inter_estadual = $request->input('CFOP_saida_inter_estadual');
                $resp->CFOP_entrada_inter_estadual = $request->input('CFOP_entrada_inter_estadual') ?? '';
                $resp->codigo_anp = $request->input('anp') ?? '';
                $resp->perc_glp = $request->perc_glp ? __replace($request->perc_glp) : 0;
                $resp->perc_gnn = $request->perc_gnn ? __replace($request->perc_gnn) : 0;
                $resp->perc_gni = $request->perc_gni ? __replace($request->perc_gni) : 0;
                $resp->custo_assessor = $request->custo_assessor ? __replace($request->custo_assessor) : 0;
                $resp->valor_partida = $request->valor_partida ? __replace($request->valor_partida) : 0;

                $resp->quantidade_tributavel = $request->quantidade_tributavel ? __replace($request->quantidade_tributavel) : 0;

                $resp->unidade_tributavel = $request->unidade_tributavel ?? '';
                $resp->descricao_anp = $request->anp ?? '';
                $resp->alerta_vencimento = $request->alerta_vencimento;
                $resp->origem = $request->origem;

                $resp->referencia = $request->referencia;
                $resp->referencia_balanca = $request->referencia_balanca;

                $resp->composto = $request->composto ? true : false;
                $resp->valor_livre = $request->valor_livre ? true : false;
                $resp->gerenciar_estoque = $request->gerenciar_estoque ? true : false;
                $resp->reajuste_automatico = $request->reajuste_automatico ? true : false;
                $resp->inativo = $request->inativo ? true : false;
                $resp->envia_controle_pedidos = $request->envia_controle_pedidos;
                $resp->tipo_servico = $request->tipo_servico;
                $resp->estoque_minimo = $request->estoque_minimo;
                $resp->locais = $request->locais;

                $resp->pRedBC = __replace($request->pRedBC);

                $resp->perc_frete = __replace($request->perc_frete);
                $resp->perc_outros = __replace($request->perc_outros);
                $resp->perc_mlv = __replace($request->perc_mlv);
                $resp->perc_mva = __replace($request->perc_mva);

                $resp->cBenef = $request->cBenef;

                $resp->largura = $request->largura;
                $resp->comprimento = $request->comprimento;
                $resp->altura = $request->altura;
                $resp->peso_liquido = __replace($request->peso_liquido);
                $resp->peso_bruto = __replace($request->peso_bruto);
                $resp->limite_maximo_desconto = $request->limite_maximo_desconto;

                $resp->perc_icms_interestadual = $request->perc_icms_interestadual ? __replace($request->perc_icms_interestadual) : 0;
                $resp->perc_icms_interno = $request->perc_icms_interno ? __replace($request->perc_icms_interno) : 0;
                $resp->perc_fcp_interestadual = $request->perc_fcp_interestadual ? __replace($request->perc_fcp_interestadual) : 0;

                $resp->renavam = $request->renavam ?? '';
                $resp->placa = $request->placa ?? '';
                $resp->chassi = $request->chassi ?? '';
                $resp->combustivel = $request->combustivel ?? '';
                $resp->ano_modelo = $request->ano_modelo ?? '';
                $resp->cor_veiculo = $request->cor_veiculo ?? '';
                $resp->valor_locacao = $request->valor_locacao ? __replace($request->valor_locacao) : 0;

                $resp->perc_comissao = $request->perc_comissao ? __replace($request->perc_comissao) : 0;
                $resp->valor_comissao = $request->valor_comissao ? __replace($request->valor_comissao) : 0;
                $resp->pOrig = $request->pOrig ? __replace($request->pOrig) : 0;
                $resp->cUFOrig = $request->cUFOrig;
                $resp->indImport = $request->indImport;

                $resp->acrescimo_perca = $request->acrescimo_perca ? __replace($request->acrescimo_perca) : 0;
                $resp->tipo_dimensao = $request->tipo_dimensao ?? '';

                $resp->lote = $request->lote ?? '';
                $resp->vencimento = $request->vencimento ?? '';
                $resp->info_tecnica_composto = $request->info_tecnica_composto ?? '';
                $resp->observacao = $request->observacao ?? '';
                $resp->info_adicional_item = $request->info_adicional_item ?? '';
                $resp->tela_pedido_id = $request->tela_pedido_id ?? 0;

                $resp->pICMSST = $request->pICMSST ? __replace($request->pICMSST) : 0;
                $resp->modBCST = $request->modBCST ?? 0;
                $resp->modBC = $request->modBC ?? 0;

                // === ATRIBUIÇÃO DOS NOVOS RECURSOS NO PRODUTO EXISTENTE ===
                $resp->controla_pesagem = $request->input('controla_pesagem') ? true : false;
                $resp->produto_referenciado_id = $request->input('produto_referenciado_id') ?? null;
                $resp->produto_prateleira_id = $request->input('produto_prateleira_id') ?? null;

                $result = $resp->save();

                $this->criarLog($resp, 'atualizar');

                if ($request->grade) {
                    $combinacoes = json_decode($request->combinacoes);

                    $resp->grade = 1;
                    $resp->str_grade = $combinacoes[0]->titulo;
                    $result = $resp->save();

                    $produtoGrade = new ProdutoGrade();

                    $nomeImagem = "";
                    if ($request->hasFile('file')) {
                        $nomeImagem = $this->salveImagemProdutoTemp($request);
                    }

                    if ($request->ecommerce) {
                        $this->salvarProdutoEcommerce($request, $resp, $nomeImagem);
                    }

                    // Garantir que cada variação de grade receba estes campos antes de chamar update():
                    $request->merge([ 'controla_pesagem' => $request->input('controla_pesagem') ? true : false ]);
                    $request->merge([ 'produto_referenciado_id' => $request->input('produto_referenciado_id') ?? null ]);
                    $request->merge([ 'produto_prateleira_id' => $request->input('produto_prateleira_id') ?? null ]);

                    $res = $produtoGrade->update($request, $nomeImagem, $resp->referencia_grade);

                    if ($res == "ok") {
                        $mensagem_sucesso = "Produto editado com sucesso, alterado para grade!";
                    } else {
                        session()->flash('mensagem_erro', 'Erro ao editar produto, confira a grade!');
                        return redirect('/produtos');
                    }
                } else {
                    $nomeImagem = $this->salveImagemProduto($request, $resp);

                    if ($request->ecommerce) {
                        $this->salvarProdutoEcommerce($request, $resp, $nomeImagem);
                    }

                    $produto = $resp;
                    $mensagem_sucesso = 'Produto editado com sucesso!';

                    $estoque = $request->estoque;
                    $stockMove = new StockMove();

                    if (isset($request->estoque) && $estoque >= 0) {
                        $estoque = __replace($request->estoque);

                        if (!$produto->estoque) {
                            $data = [
                                'produto_id' => $produto->id,
                                'usuario_id' => get_id_user(),
                                'quantidade' => $estoque,
                                'tipo' => 'incremento',
                                'observacao' => '',
                                'empresa_id' => $this->empresa_id
                            ];

                            AlteracaoEstoque::create($data);
                            $result = $stockMove->pluStock($produto->id,
                                $estoque, str_replace(",", ".", $produto->valor_venda));
                            $mensagem_sucesso = "Produto editado com sucesso, e estoque atribuido!";
                        } else {
                            if ($produto->estoque->quantidade > $estoque || $produto->estoque->quantidade < $estoque) {
                                $tipo = '';
                                $valorAlterar = 0;
                                $estoqueAtual = $produto->estoque->quantidade;
                                if ($estoqueAtual > $estoque) {
                                    $tipo = 'reducao';
                                    $valorAlterar = $estoqueAtual - $estoque;
                                } else {
                                    $tipo = 'incremento';
                                    $valorAlterar = $estoque - $estoqueAtual;
                                }

                                $data = [
                                    'produto_id' => $produto->id,
                                    'usuario_id' => get_id_user(),
                                    'quantidade' => $valorAlterar,
                                    'tipo' => $tipo,
                                    'observacao' => '',
                                    'empresa_id' => $this->empresa_id
                                ];

                                AlteracaoEstoque::create($data);
                                if (!empresaComFilial()) {
                                    if ($produto->estoque->quantidade > $estoque) {
                                        $stockMove->downStock($produto->id, $valorAlterar, -1);
                                    } else {
                                        $stockMove->pluStock($produto->id,
                                            $valorAlterar, str_replace(",", ".", $produto->valor_venda));
                                    }
                                }

                                $mensagem_sucesso = "Produto editado com sucesso, e atualizado estoque!";
                            }
                        }
                    }
                }
                if ($result) {
                    if ($request->atribuir_delivery) {
                        $this->updateProdutoNoDelivery($request, $resp);
                    }
                    session()->flash('mensagem_sucesso', $mensagem_sucesso);
                } else {
                    session()->flash('mensagem_erro', 'Erro ao editar produto!');
                }

                $locais = isset($request->local) ? $request->local : [];

                if ($resp->grade) {
                    return redirect('/produtos/grade/' . $resp->id);
                }

                if ($request->composto == true) {
                    session()->flash("mensagem_sucesso", "Produto atualizado com sucesso, informe a composição");
                    return redirect('/produtos/receita/' . $resp->id);
                } else {
                    return redirect('/produtos');
                }
            });

            return $result;
        } catch (\Exception $e) {
            __saveError($e, $this->empresa_id);
            session()->flash("mensagem_erro", "algo deu errado: " . $e->getMessage());
            return redirect('/produtos');
        }
    }

    public function update_ok_bkp(Request $request){

        if($request->ecommerce){
            $this->_validateEcommerce($request);
        }
        $this->_validate($request);
        try{
            $result = DB::transaction(function () use ($request) {
                $product = new Produto();

                $id = $request->input('id');
                $resp = $product
                ->where('id', $id)->first();

                $anps = Produto::lista_ANP();
                $descAnp = '';
                foreach($anps as $key => $a){
                    if($key == $request->anp){
                        $descAnp = $a;
                    }
                }

                $locais = json_encode($request->local);
                if($request->local == null){
                    $locais = '["-1"]';
                }
                $request->merge([ 'locais' => $locais ]);

                $resp->nome = $request->input('nome');
                $resp->categoria_id = $request->input('categoria_id');
                $resp->sub_categoria_id = $request->input('sub_categoria_id');
                $resp->marca_id = $request->input('marca_id');
                $resp->cor = $request->input('cor');
                $resp->valor_venda = str_replace(",", ".", $request->input('valor_venda'));
                $resp->valor_compra = str_replace(",", ".", $request->input('valor_compra'));

                $resp->percentual_lucro = str_replace(",", ".", $request->input('percentual_lucro'));
                $resp->NCM = $request->input('NCM');
                $resp->CEST = $request->input('CEST') ?? '';
                $resp->tipo_item = $request->input('tipo_item');

                $resp->CST_CSOSN = $request->input('CST_CSOSN');
                $resp->CST_CSOSN_EXP = $request->input('CST_CSOSN_EXP');

                $resp->CST_PIS = $request->input('CST_PIS');
                $resp->CST_COFINS = $request->input('CST_COFINS');
                $resp->CST_IPI = $request->input('CST_IPI');
                $resp->cenq_ipi = $request->input('cenq_ipi');

                $resp->CST_CSOSN_entrada = $request->input('CST_CSOSN_entrada');
                $resp->CST_PIS_entrada = $request->input('CST_PIS_entrada');
                $resp->CST_COFINS_entrada = $request->input('CST_COFINS_entrada');
                $resp->CST_IPI_entrada = $request->input('CST_IPI_entrada');
            // $resp->CFOP = $request->input('CFOP');
                $resp->unidade_venda = $request->input('unidade_venda');
                $resp->unidade_compra = $request->input('unidade_compra');
                $resp->conversao_unitaria = $request->input('conversao_unitaria') ? $request->input('conversao_unitaria') : $resp->conversao_unitaria;
                $resp->codBarras = $request->input('codBarras') ?? 'SEM GTIN';

                $resp->perc_icms = $request->perc_icms ? __replace($request->perc_icms) : 0;
                $resp->perc_pis = $request->perc_pis ? __replace($request->perc_pis) : 0;
                $resp->perc_cofins = $request->perc_cofins ? __replace($request->perc_cofins) : 0;
                $resp->perc_ipi = $request->perc_ipi ? __replace($request->perc_ipi) : 0;
                $resp->perc_iss = $request->perc_iss ? __replace($request->perc_iss) : 0;
                $resp->adRemICMSRet = $request->adRemICMSRet ? __replace($request->adRemICMSRet) : 0;
                $resp->pBio = $request->pBio ? __replace($request->pBio) : 0;
                $resp->peso = $request->peso ? __replace($request->peso) : 0;
                $resp->cListServ = $request->input('cListServ');

                $resp->CFOP_saida_estadual = $request->input('CFOP_saida_estadual');
                $resp->CFOP_entrada_estadual = $request->input('CFOP_entrada_estadual') ?? '';
                $resp->CFOP_saida_inter_estadual = $request->input('CFOP_saida_inter_estadual');
                $resp->CFOP_entrada_inter_estadual = $request->input('CFOP_entrada_inter_estadual') ?? '';
                $resp->codigo_anp = $request->input('anp') ?? '';
                $resp->perc_glp = $request->perc_glp ? __replace($request->perc_glp) : 0;
                $resp->perc_gnn = $request->perc_gnn ? __replace($request->perc_gnn) : 0;
                $resp->perc_gni = $request->perc_gni ? __replace($request->perc_gni) : 0;
                $resp->custo_assessor = $request->custo_assessor ? __replace($request->custo_assessor) : 0;
                $resp->valor_partida = $request->valor_partida ?
                __replace($request->valor_partida) : 0;

                $resp->quantidade_tributavel = $request->quantidade_tributavel ?
                __replace($request->quantidade_tributavel) : 0;

                $resp->unidade_tributavel = $request->unidade_tributavel ?? '';
                $resp->descricao_anp = $request->anp ?? '';
                $resp->alerta_vencimento = $request->alerta_vencimento;
                $resp->origem = $request->origem;

                $resp->referencia = $request->referencia;
                $resp->referencia_balanca = $request->referencia_balanca;

                $resp->composto = $request->composto ? true : false;
                $resp->valor_livre = $request->valor_livre ? true : false;
                $resp->gerenciar_estoque = $request->gerenciar_estoque ? true : false;
                $resp->reajuste_automatico = $request->reajuste_automatico ? true : false;
                $resp->inativo = $request->inativo ? true : false;
                $resp->envia_controle_pedidos = $request->envia_controle_pedidos;
                $resp->tipo_servico = $request->tipo_servico;
                $resp->estoque_minimo = $request->estoque_minimo;
                $resp->locais = $request->locais;

                $resp->pRedBC = __replace($request->pRedBC);

                $resp->perc_frete = __replace($request->perc_frete);
                $resp->perc_outros = __replace($request->perc_outros);
                $resp->perc_mlv = __replace($request->perc_mlv);
                $resp->perc_mva = __replace($request->perc_mva);

                $resp->cBenef = $request->cBenef;

                $resp->largura = $request->largura;
                $resp->comprimento = $request->comprimento;
                $resp->altura = $request->altura;
                $resp->peso_liquido = __replace($request->peso_liquido);
                $resp->peso_bruto = __replace($request->peso_bruto);
                $resp->limite_maximo_desconto = $request->limite_maximo_desconto;

                $resp->perc_icms_interestadual = $request->perc_icms_interestadual ? __replace($request->perc_icms_interestadual) : 0;
                $resp->perc_icms_interno = $request->perc_icms_interno ? __replace($request->perc_icms_interno) : 0;
                $resp->perc_fcp_interestadual = $request->perc_fcp_interestadual ? __replace($request->perc_fcp_interestadual) : 0;

                $resp->renavam = $request->renavam ?? '';
                $resp->placa = $request->placa ?? '';
                $resp->chassi = $request->chassi ?? '';
                $resp->combustivel = $request->combustivel ?? '';
                $resp->ano_modelo = $request->ano_modelo ?? '';
                $resp->cor_veiculo = $request->cor_veiculo ?? '';
                $resp->valor_locacao = $request->valor_locacao ?
                __replace($request->valor_locacao) : 0;

                $resp->perc_comissao = $request->perc_comissao ? __replace($request->perc_comissao) : 0;
                $resp->valor_comissao = $request->valor_comissao ? __replace($request->valor_comissao) : 0;
                $resp->pOrig = $request->pOrig ? __replace($request->pOrig) : 0;
                $resp->cUFOrig = $request->cUFOrig;
                $resp->indImport = $request->indImport;

                $resp->acrescimo_perca = $request->acrescimo_perca ?
                __replace($request->acrescimo_perca) : 0;
                $resp->tipo_dimensao = $request->tipo_dimensao ?? '';

                $resp->lote = $request->lote ?? '';
                $resp->vencimento = $request->vencimento ?? '';
                $resp->info_tecnica_composto = $request->info_tecnica_composto ?? '';
                $resp->observacao = $request->observacao ?? '';
                $resp->info_adicional_item = $request->info_adicional_item ?? '';
                $resp->tela_pedido_id = $request->tela_pedido_id ?? 0;

                $resp->pICMSST = $request->pICMSST ? __replace($request->pICMSST) : 0;
                $resp->modBCST = $request->modBCST ?? 0;
                $resp->modBC = $request->modBC ?? 0;

            // $resp->percentual_lucro = __replace($request->percentual_lucro);

                $result = $resp->save();

                $this->criarLog($resp, 'atualizar');

                if($request->grade){

                    $combinacoes = json_decode($request->combinacoes);

                    $resp->grade = 1;
                    $resp->str_grade = $combinacoes[0]->titulo;
                    $result = $resp->save();

                    $produtoGrade = new ProdutoGrade();

                    $nomeImagem = "";
                    if($request->hasFile('file')){
                        $nomeImagem = $this->salveImagemProdutoTemp($request);
                    }

                    if($request->ecommerce){
                        $this->salvarProdutoEcommerce($request, $resp, $nomeImagem);
                    }
                    $res = $produtoGrade->update($request, $nomeImagem, $resp->referencia_grade);

                    if($res == "ok"){
                        $mensagem_sucesso = "Produto editado com sucesso, alterado para grade!";
                    }else{
                        session()->flash('mensagem_erro', 'Erro ao editar produto, confira a grade!');
                        return redirect('/produtos');
                    }
                }else{
                    $nomeImagem = $this->salveImagemProduto($request, $resp);

                    if($request->ecommerce){
                        $this->salvarProdutoEcommerce($request, $resp, $nomeImagem);
                    }

                    $produto = $resp;
                    $mensagem_sucesso = 'Produto editado com sucesso!';

                    $estoque = $request->estoque;
                    $stockMove = new StockMove();

                    if(isset($request->estoque) && $estoque >= 0){

                        $estoque = __replace($request->estoque);

                        if(!$produto->estoque){
                            $data = [
                                'produto_id' => $produto->id,
                                'usuario_id' => get_id_user(),
                                'quantidade' => $estoque,
                                'tipo' => 'incremento',
                                'observacao' => '',
                                'empresa_id' => $this->empresa_id
                            ];

                            AlteracaoEstoque::create($data);
                            $result = $stockMove->pluStock($produto->id,
                                $estoque, str_replace(",", ".", $produto->valor_venda));
                            $mensagem_sucesso = "Produto editado com sucesso, e estoque atribuido!";
                        }else{

                            if($produto->estoque->quantidade > $estoque || $produto->estoque->quantidade < $estoque){
                        //alterar
                                $tipo = '';
                                $valorAlterar = 0;
                                $estoqueAtual = $produto->estoque->quantidade;
                                if($estoqueAtual > $estoque){
                                    $tipo = 'reducao';
                                    $valorAlterar = $estoqueAtual - $estoque;
                                }else{
                                    $tipo = 'incremento';
                                    $valorAlterar = $estoque - $estoqueAtual;
                                }

                                $data = [
                                    'produto_id' => $produto->id,
                                    'usuario_id' => get_id_user(),
                                    'quantidade' => $valorAlterar,
                                    'tipo' => $tipo,
                                    'observacao' => '',
                                    'empresa_id' => $this->empresa_id
                                ];

                                AlteracaoEstoque::create($data);
                                if(!empresaComFilial()){
                                    if($produto->estoque->quantidade > $estoque){
                                        $stockMove->downStock($produto->id, $valorAlterar, -1);
                                    }else{
                                        $stockMove->pluStock($produto->id,
                                            $valorAlterar, str_replace(",", ".", $produto->valor_venda));
                                    }
                                }

                                $mensagem_sucesso = "Produto editado com sucesso, e atualizado estoque!";

                            }

                        }
                    }
                }
                if($result){
                    if($request->atribuir_delivery){
                        $this->updateProdutoNoDelivery($request, $resp);
                    }
                    session()->flash('mensagem_sucesso', $mensagem_sucesso);
                }else{
                    session()->flash('mensagem_erro', 'Erro ao editar produto!');
                }

                $locais = isset($request->local) ? $request->local : [];

                // if(sizeof($locais) > 1){
                //     session()->flash("mensagem_sucesso", "Produto cadastrado com sucesso, informe o estoque");
                //     return redirect('/produtos/set-estoque/' . $resp->id);
                // }
                if($resp->grade){
                    return redirect('/produtos/grade/'.$resp->id);
                }

                if($request->composto == true){
                    session()->flash("mensagem_sucesso", "Produto atualizado com sucesso, informe a composição");
                    return redirect('/produtos/receita/' . $resp->id);
                }else{
                    return redirect('/produtos');
                }
            });
    return $result;
    }catch(\Exception $e){
        __saveError($e, $this->empresa_id);
        session()->flash("mensagem_erro", "algo deu errado: " . $e->getMessage());
        return redirect('/produtos');
    }
    }

    public function delete($id){
        try{
            $produto = Produto
            ::where('id', $id)
            ->first();

            if(valida_objeto($produto)){

                $this->criarLog($produto, 'deletar');

                $public = env('SERVIDOR_WEB') ? 'public/' : '';

                if(file_exists(public_path('imgs_produtos/').$produto->imagem) && $produto->imagem != '')
                    unlink(public_path('imgs_produtos/').$produto->imagem);

                try{
                    $produto->estoque()->delete();
                    if($produto->grade){
                        $produtos = Produto::
                        where('referencia_grade', $produto->referencia_grade)
                        ->delete();
                    }else{
                        $produto->delete();
                    }


                    session()->flash('mensagem_sucesso', 'Registro removido!');
                }catch(\Exception $e){
                    // session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
                    session()->flash('mensagem_erro', 'Erro: o produto pode estar em vendas ou compras, e não poderá ser removido!');
                }
                return redirect()->back();


            }else{
                return redirect('/403');
            }
        }catch(\Exception $e){
            return view('errors.sql')
            ->with('title', 'Erro ao deletar produto')
            ->with('motivo', 'Não é possivel remover produtos, presentes vendas, compras ou pedidos!');
        }
    }

    private function _validate(Request $request){
        $rules = [
            'nome' => 'required|max:100',
            'valor_venda' => ['required'],
            // 'valor_venda' => ['required', new ValidaValor],
            'valor_compra' => ['required'],
            // 'valor_compra' => ['required', new ValidaValor],
            'categoria_id' => 'required',
            'percentual_lucro' => 'required',
            'NCM' => 'required|min:10',
            'perc_icms' => 'required',
            'perc_pis' => 'required',
            'perc_cofins' => 'required',
            'perc_ipi' => 'required',
            'codBarras' => [],
            'CFOP_saida_estadual' => 'required',
            'CFOP_saida_inter_estadual' => 'required',
                // 'CFOP_entrada_estadual' => 'required',
                // 'CFOP_entrada_inter_estadual' => 'required',
            'file' => 'max:700',
            'lote' => 'max:10',
            'vencimento' => 'max:10',
            'cenq_ipi' => 'required',
                // 'CEST' => 'required'
        ];

        $messages = [
            'nome.required' => 'O campo nome é obrigatório.',
            'categoria_id.required' => 'O campo categoria é obrigatório.',
            'NCM.required' => 'O campo NCM é obrigatório.',
            'NCM.min' => 'NCM precisa de 8 digitos.',
                // 'CFOP.required' => 'O campo CFOP é obrigatório.',
            'CEST.required' => 'O campo CEST é obrigatório.',
            'valor_venda.required' => 'O campo valor de venda é obrigatório.',
            'valor_compra.required' => 'O campo valor de compra é obrigatório.',
            'percentual_lucro.required' => 'O campo % lucro é obrigatório.',
            'nome.max' => '100 caracteres maximos permitidos.',
            'perc_icms.required' => 'O campo %ICMS é obrigatório.',
            'perc_pis.required' => 'O campo %PIS é obrigatório.',
            'perc_cofins.required' => 'O campo %COFINS é obrigatório.',
            'perc_ipi.required' => 'O campo %IPI é obrigatório.',
            'CFOP_saida_estadual.required' => 'Campo obrigatório.',
            'CFOP_saida_inter_estadual.required' => 'Campo obrigatório.',
            'CFOP_entrada_estadual.required' => 'Campo obrigatório.',
            'CFOP_entrada_inter_estadual.required' => 'Campo obrigatório.',
            'file.max' => 'Arquivo muito grande maximo 300 Kb',
            'lote.max' => '10 caracteres maximos permitidos.',
            'vencimento.max' => '10 caracteres maximos permitidos.',
            'cenq_ipi.required' => 'Campo obrigatório.',
        ];

        $this->validate($request, $rules, $messages);
    }

    public function all(){
        $products = Produto::all();
        $arr = array();
        foreach($products as $p){
            $arr[$p->id. ' - ' .$p->nome . ($p->cor != '--' ? ' | COR: ' . $p->cor : '') . ($p->referencia != '' ? ' | REF: ' . $p->referencia : '')] = null;
                    //array_push($arr, $temp);
        }
        echo json_encode($arr);
    }

    public function getUnidadesMedida(){
        $unidades = Produto::unidadesMedida();
        echo json_encode($unidades);
    }

    public function composto(){
        $products = Produto::
        where('composto', true)
        ->get();
        $arr = array();
        foreach($products as $p){
            $arr[$p->id. ' - ' .$p->nome . ($p->cor != '--' ? ' | Cor: ' . $p->cor : '') . ($p->referencia != '' ? ' | REF: ' . $p->referencia : '')] = null;
                    //array_push($arr, $temp);
        }
        echo json_encode($arr);
    }

    public function naoComposto(){
        $products = Produto::
        where('composto', false)
        ->get();
        $arr = array();
        foreach($products as $p){
            $arr[$p->id. ' - ' .$p->nome . ($p->cor != '--' ? ' | Cor: ' . $p->cor : '') . ($p->referencia != '' ? ' | REF: ' . $p->referencia : '')] = null;
                    //array_push($arr, $temp);
        }
        echo json_encode($arr);
    }

    public function getValue(Request $request){
        $id = $request->input('id');
        $product = Product::
        where('id', $id)
        ->first();
        echo json_encode($product->value_sale);
    }

    public function getProduto($id){
        $produto = Produto::
        where('id', $id)
        ->first();
        if($produto->delivery){
            foreach($produto->delivery->pizza as $tp){
                $tp->tamanho;
            }
        }
        if($produto->ecommerce){
            $produto->ecommerce;
        }
        echo json_encode($produto);
    }

    public function getProdutoCodigoReferencia($codigo){
        $produto = Produto::
        where('referencia_balanca', $codigo)
        ->where('empresa_id', $this->empresa_id)
        ->first();

        if($produto != null){
            return response()->json($produto, 200);
        }else{
            return response()->json("Nada encontrado!", 401);
        }

    }

    public function getProdutoVenda($id, $listaId){
        $produto = Produto::
        where('id', $id)
        ->first();
        if($produto->delivery){
            foreach($produto->delivery->pizza as $tp){
                $tp->tamanho;
            }
        }

        if($listaId > 0){
            $lista = ProdutoListaPreco::
            where('lista_id', $listaId)
            ->where('produto_id', $produto->id)
            ->first();

            if($lista->valor > 0){
                $produto->valor_venda = (string) $lista->valor;
            }
        }

        $estoque = Estoque::where('produto_id', $id)->first();
        $produto->estoque_atual = $estoque != null ? $estoque->quantidade : 0;
        echo json_encode($produto);
    }

    public function getProdutoCodBarras($cod){
        $produto = Produto::
        where('codBarras', $cod)
        ->where('empresa_id', $this->empresa_id)
        ->with('estoque')
        ->with('listaPreco')
        ->first();

        echo json_encode($produto);
    }

    public function salvarProdutoDaNota(Request $request){
            //echo json_encode($request->produto);
        $produto = $request->produto;
        $natureza = Produto::firstNatureza($this->empresa_id);

        $valorVenda = str_replace(".", "", $produto['valorVenda']);
        $valorVenda = str_replace(",", ".", $valorVenda);

        $valorCompra = $produto['valorCompra'];

        $cfop = $produto['cfop'];
        $digito = substr($cfop, 0, 1);

        $cfopEstadual = '';
        $cfopInterEstadual = '';
        if($digito == '5'){
            $cfopEstadual = $cfop;
            $cfopInterEstadual = '6'. substr($cfop, 1, 4);

        }else{
            $cfopInterEstadual = $cfop;
            $cfopEstadual = '5'. substr($cfop, 1, 4);
        }

        $conversaoUnitaria = (int)$produto['conversao_unitaria'];
        $valorCompra = (float)__replace($produto['valorCompra']);
        $result = Produto::create([
            'nome' => $produto['nome'],
            'NCM' => $produto['ncm'],
                // 'CFOP' => $produto['cfop'],
            'valor_venda' => $valorVenda,
            'valor_compra' => $valorCompra,
            'valor_livre' => false,
            'percentual_lucro' => $produto['percentual_lucro'] ?? 0,
            'custo_assessor' => $produto['custo_assessor'] ?? 0,
            'referencia' => $produto['referencia'],
            'conversao_unitaria' => $conversaoUnitaria,
            'categoria_id' => $produto['categoria_id'],
            'marca_id' => $produto['marca_id'],
            'sub_categoria_id' => $produto['sub_categoria_id'],
            'unidade_compra' => $produto['unidadeCompra'],
            'unidade_venda' => $produto['unidadeVenda'],
            'codBarras' => $produto['codBarras'] ?? 'SEM GTIN',
            'composto' => false,
            'CST_CSOSN' => $produto['CST_CSOSN'],
            'CST_PIS' => $produto['CST_PIS'],
            'CST_COFINS' => $produto['CST_COFINS'],
            'CST_IPI' => $produto['CST_IPI'],
            'perc_icms' => __replace($produto['perc_icms']),
            'perc_pis' => __replace($produto['perc_pis']),
            'perc_cofins' => __replace($produto['perc_cofins']),
            'perc_ipi' => __replace($produto['perc_ipi']),
            'CFOP_saida_estadual' => $cfopEstadual,
            'CFOP_saida_inter_estadual' => $cfopInterEstadual,
            'codigo_anp' => '',
            'descricao_anp' => '',
            'cListServ' => '',
            'imagem' => '',
            'alerta_vencimento' => 0,
            'referencia' => $produto['referencia'] ?? '',
            'empresa_id' => $this->empresa_id,
            'gerenciar_estoque' => $produto['gerenciar_estoque'],
            'reajuste_automatico' => 0,
            'limite_maximo_desconto' => 0,
            'grade' => 0,
            'referencia_grade' => Str::random(20),
            'estoque_minimo' => $produto['estoque_minimo'] ?? 0,
            'gerenciar_estoque' => $produto['gerenciar_estoque'],
            'inativo' => $produto['inativo'],
            'CEST' => $produto['CEST'] ?? '',
            'codigo_anp' => $produto['anp'] ?? '',
            'perc_glp' => $produto['perc_glp'] ?? 0,
            'perc_gnn' => $produto['perc_gnn'] ?? 0,
            'perc_gni' => $produto['perc_gni'] ?? 0,
            'valor_partida' => __replace($produto['valor_partida']),
            'unidade_tributavel' => $produto['unidade_tributavel'] ?? '',
            'quantidade_tributavel' => $produto['quantidade_tributavel'] ?? 1,
            'largura' => __replace($produto['largura']),
            'altura' => __replace($produto['altura']),
            'comprimento' => __replace($produto['comprimento']),
            'peso_liquido' => __replace($produto['peso_liquido']),
            'peso_bruto' => __replace($produto['peso_bruto']),
            'locais' => $produto['filial_id'] ? '["'.$produto['filial_id'].'"]' : '["-1"]'

        ]);

        echo json_encode($result);
    }

    public function salvarProdutoDaNotaComEstoque(Request $request){
            //echo json_encode($request->produto);
        $produto = $request->produto;
        $natureza = Produto::firstNatureza($this->empresa_id);
        $valorVenda = str_replace(",", ".", $produto['valorVenda']);

        $valorCompra = $produto['valorCompra'];

        $cfop = $produto['cfop'];
        $digito = substr($cfop, 0, 1);

        $cfopEstadual = '';
        $cfopInterEstadual = '';
        if($digito == '5'){
            $cfopEstadual = $cfop;
            $cfopInterEstadual = '6'. substr($cfop, 1, 4);

        }else{
            $cfopInterEstadual = $cfop;
            $cfopEstadual = '6'. substr($cfop, 1, 4);
        }

        $result = Produto::create([
            'nome' => $produto['nome'],
            'NCM' => $produto['ncm'],
            'valor_venda' => $valorVenda,
            'valor_compra' => $valorCompra,
            'percentual_lucro' => $produto['percentual_lucro'] ?? 0,
            'valor_livre' => false,
            'conversao_unitaria' => (float)$produto['conversao_unitaria'],
            'categoria_id' => $produto['categoria_id'],
            'unidade_compra' => $produto['unidadeCompra'],
            'unidade_venda' => $produto['unidadeVenda'],
            'codBarras' => $produto['codBarras'] ?? 'SEM GTIN',
            'composto' => false,
            'CST_CSOSN' => $produto['CST_CSOSN'],
            'CST_PIS' => $produto['CST_PIS'],
            'CST_COFINS' => $produto['CST_COFINS'],
            'CST_IPI' => $produto['CST_IPI'],
            'perc_icms' => __replace($produto['perc_icms']),
            'perc_pis' => __replace($produto['perc_pis']),
            'perc_cofins' => __replace($produto['perc_cofins']),
            'perc_ipi' => __replace($produto['perc_ipi']),
            'CFOP_saida_estadual' => $cfopEstadual,
            'CFOP_saida_inter_estadual' => $cfopInterEstadual,
            'codigo_anp' => '',
            'descricao_anp' => '',
            'cListServ' => '',
            'imagem' => '',
            'alerta_vencimento' => 0,
            'referencia' => $produto['referencia'],
            'empresa_id' => $this->empresa_id,
            'gerenciar_estoque' => $produto['gerenciar_estoque'],
            'reajuste_automatico' => 0,
            'limite_maximo_desconto' => 0,
            'grade' => 0,
            'referencia_grade' => Str::random(20),

            'estoque_minimo' => $produto['estoque_minimo'] ?? 0,
            'gerenciar_estoque' => $produto['gerenciar_estoque'],
            'inativo' => $produto['inativo'],
            'CEST' => $produto['CEST'] ?? '',
            'codigo_anp' => $produto['anp'] ?? '',
            'perc_glp' => $produto['perc_glp'] ?? 0,
            'perc_gnn' => $produto['perc_gnn'] ?? 0,
            'perc_gni' => $produto['perc_gni'] ?? 0,
            'valor_partida' => __replace($produto['valor_partida']),
            'unidade_tributavel' => $produto['unidade_tributavel'] ?? '',
            'quantidade_tributavel' => $produto['quantidade_tributavel'] ?? 1,
            'largura' => __replace($produto['largura']),
            'altura' => __replace($produto['altura']),
            'comprimento' => __replace($produto['comprimento']),
            'peso_liquido' => __replace($produto['peso_liquido']),
            'peso_bruto' => __replace($produto['peso_bruto']),

            'cenq_ipi' => $produto['cenq_ipi'],
            'perc_iss' => $produto['perc_iss'] ? __replace($produto['perc_iss']) : 0,
            'pRedBC' => $produto['pRedBC'] ? __replace($produto['pRedBC']) : 0,
            'pICMSST' => $produto['pICMSST'] ? __replace($produto['pICMSST']) : 0,
            'cBenef' => $produto['cBenef'] ?? '',
            'origem' => $produto['origem'],

            'perc_icms_interestadual' => $produto['perc_icms_interestadual'] ? __replace($produto['perc_icms_interestadual']) : 0,
            'perc_icms_interno' => $produto['perc_icms_interno'] ? __replace($produto['perc_icms_interno']) : 0,
            'perc_fcp_interestadual' => $produto['perc_fcp_interestadual'] ? __replace($produto['perc_fcp_interestadual']) : 0,

            'CFOP_entrada_estadual' => $produto['CFOP_entrada_estadual'] ?? '',
            'CFOP_entrada_inter_estadual' => $produto['CFOP_entrada_inter_estadual'] ?? '',
            'modBC' => $produto['modBC'],
            'modBCST' => $produto['modBCST'],
            'CST_CSOSN_entrada' => $produto['CST_CSOSN_entrada'],
            'CST_PIS_entrada' => $produto['CST_PIS_entrada'],
            'CST_COFINS_entrada' => $produto['CST_COFINS_entrada'],
            'CST_IPI_entrada' => $produto['CST_IPI_entrada'],

            'perc_frete' => $produto['perc_frete'] ? __replace($produto['perc_frete']) : 0,
            'perc_outros' => $produto['perc_outros'] ? __replace($produto['perc_outros']) : 0,
            'perc_mlv' => $produto['perc_mlv'] ? __replace($produto['perc_mlv']) : 0,
            'locais' => $produto['filial_id'] ? '["'.$produto['filial_id'].'"]' : '["-1"]'

        ]);

    ItemDfe::create(
        [
            'numero_nfe' => $produto['numero_nfe'],
            'produto_id' => $result->id,
            'empresa_id' => $this->empresa_id
        ]
    );
    if($result->gerenciar_estoque){
        $stockMove = new StockMove();
        $stockMove->pluStock($result->id, ($produto['quantidade']*(float)$produto['conversao_unitaria']), $valorCompra);
    }
    echo json_encode($result);
    }

    public function updateProdutoDaNotaComEstoque(Request $request){
            //echo json_encode($request->produto);
        try{
            $arr = $request->produto;
            $produto = Produto::find($arr['produto_id']);

            $produto->valor_venda = __replace($arr['valor_venda']);
            $produto->valor_compra = __replace($arr['valor_compra']);
            $produto->referencia = $arr['referencia'];

            $produto->save();
            $qtd = $arr['estoque'];
            $stockMove = new StockMove();
            $stockMove->pluStock($produto->id, ($qtd*(float)$produto->conversao_unitaria), $produto->valor_compra);

            ItemDfe::create(
                [
                    'numero_nfe' => $arr['numero_nfe'],
                    'produto_id' => $produto->id,
                    'empresa_id' => $this->empresa_id
                ]
            );

            return response()->json($produto, 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function setEstoque(Request $request){
        $stockMove = new StockMove();
        $stockMove->pluStock($request->produto, $request->quantidade, $request->valor);

        $produto = Produto::find($request->produto);
        $perc = $produto->percentual_lucro;

        $produto->valor_compra = $request->valor;
        if($produto->reajuste_automatico){
            $produto->valor_venda = $request->valor +
            (($request->valor*$produto->percentual_lucro)/100);
        }

        $produto->save();
        ItemDfe::create(
            [
                'numero_nfe' => $request->numero_nfe,
                'produto_id' => $request->produto,
                'empresa_id' => $this->empresa_id
            ]
        );
        echo json_encode("ok");
    }

    private function salvarProdutoNoDelivery($request, $produto, $nomeImagem){

        $categoria = CategoriaProdutoDelivery::
        where('empresa_id', $this->empresa_id)
        ->first();

        $valor = str_replace(",", ".", $request->valor_venda);

        $produtoDelivery = [
            'status' => 1 ,
            'produto_id' => $produto->id,
            'descricao' => $request->descricao ?? '',
            'ingredientes' => '',
            'limite_diario' => -1,
            'categoria_id' => $categoria->id,
            'valor' => $valor,
            'valor_anterior' => 0,
            'referencia' => '',
            'empresa_id' => $this->empresa_id
        ];

        $result = ProdutoDelivery::create($produtoDelivery);
        $produtoDelivery = ProdutoDelivery::find($result->id);
        if($result){
            $this->salveImagemProdutoDelivery($nomeImagem, $produtoDelivery);
        }

    }

    private function salvarProdutoEcommerce($request, $produto, $nomeImagem){
            // $this->_validateEcommerce($request);
        $categoriaFirst =  CategoriaProdutoEcommerce::
        where('empresa_id', $this->empresa_id)
        ->first();

        $produtoEcommerce = [
            'produto_id' => $produto->id,
            'categoria_id' => $request->categoria_ecommerce_id ? $request->categoria_ecommerce_id : $categoriaFirst->id,
            'empresa_id' => $this->empresa_id,
            'descricao' => $request->descricao ?? '',
            'controlar_estoque' => $request->input('controlar_estoque') ? true : false,
            'status' => $request->input('status') ? true : false ,
            'valor' => $request->valor_ecommerce ? __replace($request->valor_ecommerce) : str_replace(",", ".", $request->valor_venda),
            'destaque' => $request->input('destaque') ? true : false
        ];
        if($produto->ecommerce){
            $result = $produto->ecommerce;
            $result->fill($produtoEcommerce)->save();
        }else{
            $result = ProdutoEcommerce::create($produtoEcommerce);
        }
        $produtoEcommerce = ProdutoEcommerce::find($result->id);
        if($result){
            $this->salveImagemProdutoEcommerce($nomeImagem, $produtoEcommerce);
        }

    }

    private function updateProdutoNoDelivery($request, $produto){
            // $this->_validateDelivery($request);
        $produtoDelivery = $produto->delivery;
        if($produtoDelivery){
            $catPizza = false;
            $categoria = CategoriaProdutoDelivery::
            where('id', $request->categoria_delivery_id)
            ->first();

            $valor = 0;
            if($categoria && strpos($categoria->nome, 'izza') !== false){

            }else{
                $valor = str_replace(",", ".", $request->valor_venda);
            }

            $produtoDelivery->destaque = $request->input('destaque') ? true : false;
            $produtoDelivery->descricao = $request->input('descricao') ?? $produtoDelivery->descricao;
            $produtoDelivery->ingredientes = $request->input('ingredientes') ?? $produtoDelivery->ingredientes;
            $produtoDelivery->limite_diario = $request->input('limite_diario') ?? $produtoDelivery->limite_diario;
            $produtoDelivery->categoria_id = $request->input('categoria_delivery_id') ?? $produtoDelivery->categoria_delivery_id;
            $produtoDelivery->valor = $request->input('valor') ?? $valor;

            $result = $produtoDelivery->save();

            if($result){
                // $this->salveImagemProdutoDelivery($request, $produtoDelivery);
            }
        }else{
            $this->salvarProdutoNoDelivery($request, $produto);
        }

    }

    private function _validateEcommerce(Request $request){

        if($request->ecommerce){
            $rules = [
                'valor_ecommerce' => 'required',
                'categoria_ecommerce_id' => 'required',
                'descricao' => 'required',
                'valor_ecommerce' => 'required',
                'largura' => 'required',
                'altura' => 'required',
                'comprimento' => 'required',
                'peso_liquido' => 'required',
                'peso_bruto' => 'required'
            ];
        }else{
            $rules = [];
        }

        $messages = [

            'categoria_ecommerce_id.required' => 'O campo categoria é obrigatório.',
            'descricao.required' => 'O campo descricao é obrigatório.',
            'descricao.min' => 'Minimo de 20 caracteres',
            'valor_ecommerce.required' => 'O campo valor para ecommerce é obrigatório.',
            'largura.required' => 'O campo largura é obrigatório.',
            'altura.required' => 'O campo altura é obrigatório.',
            'comprimento.required' => 'O campo comprimento é obrigatório.',
            'peso_liquido.required' => 'O campo peso liquido é obrigatório.',
            'peso_bruto.required' => 'O campo peso bruto é obrigatório.',

        ];

        $this->validate($request, $rules, $messages);
    }

    private function _validateDelivery(Request $request){
        $rules = [
            'ingredientes' => 'max:255',
            'descricao' => 'max:255',
            'limite_diario' => 'required'
        ];

        $messages = [
            'ingredientes.required' => 'O campo ingredientes é obrigatório.',
            'ingredientes.max' => '255 caracteres maximos permitidos.',
            'descricao.required' => 'O campo descricao é obrigatório.',
            'descricao.max' => '255 caracteres maximos permitidos.',
            'limite_diario.required' => 'O campo limite diário é obrigatório'
        ];

        $this->validate($request, $rules, $messages);
    }

    private function salveImagemProdutoDelivery($nomeImagem, $produtoDelivery){


        if($nomeImagem != ""){
            copy(public_path('imgs_produtos/').$nomeImagem, public_path('imagens_produtos/').$nomeImagem);
                // $upload = $file->move(public_path('ecommerce/produtos'), $nomeImagem);

            ImagensProdutoDelivery::create(
                [
                    'produto_id' => $produtoDelivery->id,
                    'path' => $nomeImagem
                ]
            );

        }else{

        }
    }

    private function salveImagemProdutoEcommerce($nomeImagem, $produtoEcommerce){

        if($nomeImagem != ""){
            copy(public_path('imgs_produtos/').$nomeImagem, public_path('ecommerce/produtos/').$nomeImagem);
                // $upload = $file->move(public_path('ecommerce/produtos'), $nomeImagem);

            ImagemProdutoEcommerce::create(
                [
                    'produto_id' => $produtoEcommerce->id,
                    'img' => $nomeImagem
                ]
            );

        }else{

        }
    }

    public function movimentacao($id){
        $produto = Produto::find($id);

        $movimentacoes = $produto->movimentacoes();

        // dd($movimentacoes);

        return view('produtos/movimentacoes')
        ->with('movimentacoes', $movimentacoes)
        ->with('produto', $produto)
        ->with('title', 'Movimentações');

    }

    public function movimentacaoImprimir($id){
        $produto = Produto::find($id);
        if(valida_objeto($produto)){

            $movimentacoes = $produto->movimentacoes();

            $p = view('produtos/relatorio_movimentacoes')
            ->with('produto', $produto)
            ->with('title', 'Relatório de movimentações')
            ->with('movimentacoes', $movimentacoes);

            // return $p;

            $domPdf = new Dompdf(["enable_remote" => true]);
            $domPdf->loadHtml($p);

            $pdf = ob_get_clean();

            $domPdf->setPaper("A4", "landscape");
            $domPdf->render();
            $domPdf->stream("Relatório de movimentações.pdf", array("Attachment" => false));
        }else{
            return redirect('/403');
        }

    }

    public function importacao(){
        $zip_loaded = extension_loaded('zip') ? true : false;
        if ($zip_loaded === false) {
            session()->flash('mensagem_erro', "Por favor instale/habilite o PHP zip para importar");
            return redirect()->back();
        }
        $categoria = Categoria::where('empresa_id', $this->empresa_id)->first();
        if($categoria == null){
            session()->flash('mensagem_erro', 'Cadastre uma categoria!!');
            return redirect('/categorias');
        }

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        if($config == null){
            session()->flash('mensagem_erro', 'Cadastre o emitente!!');
            return redirect('/configNF');
        }

        $trib = Tributacao::where('empresa_id', $this->empresa_id)->first();
        if($trib == null){
            session()->flash('mensagem_erro', 'Cadastre uma tributação padrão!!');
            return redirect('/tributos');
        }
        return view('produtos/importacao')
        ->with('title', 'Importação de produto');
    }

    public function downloadModelo(){
        try{
            $public = env('SERVIDOR_WEB') ? 'public/' : '';
            return response()->download(public_path('files/') . 'import_products_csv_template.xlsx');
        }catch(\Exception $e){
            echo $e->getMessage();
        }
    }

    public function deleteAll(){
        Estoque::where('empresa_id', $this->empresa_id)->delete();
        Produto::where('empresa_id', $this->empresa_id)->delete();
        session()->flash('mensagem_sucesso', 'Produto e estoque zerado');

        return redirect()->back();
    }

    public function importacaoStore(Request $request){
        if ($request->hasFile('file')) {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            $locais = json_encode($request->local);
            if($request->local == null){
                $locais = '["-1"]';
            }

            $rows = Excel::toArray(new ProdutoImport, $request->file);

            $retornoErro = $this->validaArquivo($rows);
            // $retornoErro = "";

            if($retornoErro == ""){
                    //armazenar no bd

                $teste = [];
                $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();
                $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
                $categoria = Categoria::where('empresa_id', $this->empresa_id)->first();

                $contNovo = 0;
                $contAtualizado = 0;

                foreach($rows as $row){
                    foreach($row as $key => $r){
                        if($r[0] != 'NOME' && $r[0] != 'NOME*'){

                            try{
                                $objeto = $this->preparaObjeto($r, $tributacao, $config, $categoria->id, $locais);
                                if($objeto != null){
                                    if($objeto['categoria'] != ''){
                                        $cat = Categoria::where('nome', $objeto['categoria'])
                                        ->where('empresa_id', $this->empresa_id)
                                        ->first();

                                        if($cat == null){

                                            $cat = Categoria::create(
                                                [
                                                    'nome' => $objeto['categoria'],
                                                    'empresa_id' => $this->empresa_id
                                                ]
                                            );
                                            $objeto['categoria_id'] = $cat->id;
                                        }else{
                                            $objeto['categoria_id'] = $cat->id;
                                        }
                                    }else{
                                        $objeto['categoria_id'] = $categoria->id;
                                    }

                                    $prod = $this->produtoDuplicadoImport($objeto['nome'], $objeto['referencia']);
                                    if($prod == null){
                                        $contNovo++;
                                        $prod = Produto::create($objeto);

                                        if($objeto['estoque'] > 0){
                                            $stockMove = new StockMove();
                                            $result = $stockMove->pluStock($prod->id,
                                                $objeto['estoque'], str_replace(",", ".", $prod->valor_venda));
                                        }
                                    }else{
                                        // echo $objeto['nome'] . " ---- ";
                                        // echo $objeto['referencia']  . "<br>";
                                        $prod->valor_venda = $objeto['valor_venda'];
                                        $prod->valor_compra = $objeto['valor_compra'];
                                        $prod->CEST = $objeto['CEST'];
                                        $prod->CST_CSOSN = $objeto['CST_CSOSN'];
                                        $prod->CST_PIS = $objeto['CST_PIS'];
                                        $prod->CST_COFINS = $objeto['CST_COFINS'];
                                        $prod->CST_IPI = $objeto['CST_IPI'];
                                        $prod->unidade_compra = $objeto['unidade_compra'];
                                        $prod->unidade_venda = $objeto['unidade_venda'];
                                        $prod->codBarras = $objeto['codBarras'];
                                        $prod->perc_icms = $objeto['perc_icms'];
                                        $prod->perc_pis = $objeto['perc_pis'];
                                        $prod->perc_cofins = $objeto['perc_cofins'];
                                        $prod->perc_ipi = $objeto['perc_ipi'];
                                        $prod->largura = $objeto['largura'];
                                        $prod->comprimento = $objeto['comprimento'];
                                        $prod->altura = $objeto['altura'];
                                        $prod->peso_liquido = $objeto['peso_liquido'];
                                        $prod->peso_bruto = $objeto['peso_bruto'];
                                        $prod->NCM = $objeto['NCM'];
                                        $prod->CFOP_entrada_estadual = $objeto['CFOP_entrada_estadual'];
                                        $prod->CFOP_entrada_inter_estadual = $objeto['CFOP_entrada_inter_estadual'];

                                        $prod->CFOP_saida_estadual = $objeto['CFOP_saida_estadual'];
                                        $prod->CFOP_saida_inter_estadual = $objeto['CFOP_saida_inter_estadual'];
                                        $contAtualizado++;
                                        $prod->save();

                                        if($objeto['estoque'] > 0){
                                            Estoque::where('produto_id', $prod->id)->delete();
                                            $stockMove = new StockMove();
                                            $result = $stockMove->pluStock(
                                                $prod->id, $objeto['estoque'], str_replace(",", ".", $prod->valor_venda));
                                        }
                                    }

                                }

                            }catch(\Exception $e){
                                session()->flash('mensagem_erro', $e->getMessage());
                                return redirect()->back();
                            }
                        }
                    }
                }

                session()->flash('mensagem_sucesso', "Produtos inseridos: $contNovo, Produtos atualizados: $contAtualizado");
                return redirect('/produtos');

            }else{
                session()->flash('mensagem_erro', $retornoErro);
                return redirect()->back();
            }

        }else{
            session()->flash('mensagem_erro', 'Nenhum Arquivo!!');
            return redirect()->back();
        }

    }

    private function produtoDuplicadoImport($nome, $referencia){

        $result = null;
        $result = Produto::
        where('nome', $nome)
        ->where('empresa_id', $this->empresa_id)
        ->first();

        if($result == null && $referencia != ''){
            $result = Produto::
            where('referencia', $referencia)
            ->where('empresa_id', $this->empresa_id)
            ->first();
        }

        return $result;
    }

    private function validaNumero($numero){
        if(strlen($numero) == 1){
            return "0".$numero;
        }
        return $numero;
    }

    private function preparaObjeto($r, $tributacao, $config, $categoria, $locais){
        if(trim($r[0]) == ""){
            return null;
        }

        $natureza = NaturezaOperacao::where('empresa_id', $this->empresa_id)->first();
        $valorVenda = __replace($r[3]);
        $valorCompra = __replace($r[4]);
        $percentual_lucro = 0;
        if($valorCompra > 0 && $valorVenda > 0){
            $percentual_lucro = (($valorVenda - $valorCompra)/$valorCompra)*100;
        }

        $arr = [
            'nome' => $r[0],
            'categoria' => $r[2],
            'cor' => $r[1] ?? '',
            'valor_venda' => __replace($r[3]),
            'valor_compra' => __replace($r[4]),
            'NCM' => $r[5] != "" ? $r[5] : $tributacao->ncm_padrao,
            'CEST' => $r[7] ?? '',
            'CST_CSOSN' => $r[8] != "" ? $this->validaNumero($r[8]) : $config->CST_CSOSN_padrao,
            'CST_PIS' => $r[9] != "" ? $this->validaNumero($r[9]) : $config->CST_PIS_padrao,
            'CST_COFINS' => $r[10] != "" ? $this->validaNumero($r[10]) : $config->CST_COFINS_padrao,
            'CST_IPI' => $r[11] != "" ? $r[11] : $config->CST_IPI_padrao,
            'unidade_compra' => $r[12] != "" ? $r[12] : 'UN',
            'unidade_venda' => $r[13] != "" ? $r[13] : 'UN',
            'composto' => $r[15] != "" ? $r[15] : 0,
            'codBarras' => $r[6] != "" ? $r[6] : 'SEM GTIN',
            'conversao_unitaria' => $r[14] != "" ? $r[14] : 1,
            'valor_livre' => $r[16] != "" ? $r[16] : 0,
            'perc_icms' => $r[17] != "" ? $r[17] : $tributacao->icms,
            'perc_pis' => $r[18] != "" ? $r[18] : $tributacao->pis,
            'perc_cofins' => $r[19] != "" ? $r[19] : $tributacao->cofins,
            'perc_ipi' => $r[20] != "" ? $r[20] : $tributacao->ipi,
            'CFOP_saida_estadual' => $r[22] != "" ? $r[22] : $natureza->CFOP_saida_estadual,
            'CFOP_saida_inter_estadual' => $r[23] != "" ? $r[23] : $natureza->CFOP_saida_inter_estadual,
            'codigo_anp' => $r[24] ??'',
            'descricao_anp' => $r[25]?? '',
            'perc_iss' => $r[21] ?? 0,
            'cListServ' => '',
            'imagem' => '',
            'alerta_vencimento' => $r[26] != "" ? $r[26] : 0,
            'gerenciar_estoque' => $r[27] != "" ? $r[27] : 0,
            'estoque_minimo' => $r[28] != "" ? $r[28] : 0,
            'referencia' => $r[29] ?? '',
            'empresa_id' => $this->empresa_id,
            'largura' => $r[30] != "" ? $r[30] : 0,
            'comprimento' => $r[31] != "" ? $r[31] : 0,
            'altura' => $r[32] != "" ? $r[32] : 0,
            'peso_liquido' => $r[33] != "" ? $r[33] : 0,
            'peso_bruto' => $r[34] != "" ? $r[34] : 0,
            'limite_maximo_desconto' => $r[35] != "" ? $r[35] : 0,
            'pRedBC' => $r[36] ?? '',
            'cBenef' => $r[37] ?? '',
            'percentual_lucro' => $percentual_lucro,
            'CST_CSOSN_EXP' => '',
            'referencia_grade' => Str::random(20),
            'grade' => 0,
            'str_grade' => 0,
            'perc_glp' => 0,
            'perc_gnn' => 0,
            'perc_gni' => 0,
            'valor_partida' => 0,
            'unidade_tributavel' => '',
            'quantidade_tributavel' => 0,
            'perc_icms_interestadual' => 0,
            'perc_icms_interno' => 0,
            'perc_fcp_interestadual' => 0,
            'inativo' => 0,
            'estoque' => $r[38] != "" ? $r[38] : 0,
            'CFOP_entrada_estadual' => $r[39] != "" ? $r[39] : '',
            'CFOP_entrada_inter_estadual' => $r[40] != "" ? $r[40] : '',
            'locais' => $locais
        ];
        return $arr;
    }

    private function validaArquivo($rows){
        $cont = 0;
        $msgErro = "";
        foreach($rows as $row){
            foreach($row as $key => $r){

                $nome = $r[0];
                $valorVenda = $r[3];
                $valorCompra = $r[4];

                if(strlen($nome) == 0){
                    $msgErro .= "Coluna nome em branco na linha: $cont | ";
                }

                if(strlen($valorVenda) == 0){
                    $msgErro .= "Coluna valor venda em branco na linha: $cont | ";
                }

                if(strlen($valorCompra) == 0){
                    $msgErro .= "Coluna valor compra em branco na linha: $cont";
                }

                if($msgErro != ""){
                    return $msgErro;
                }
                $cont++;
            }
        }

        return $msgErro;
    }

    public function duplicar($id){
        $natureza = Produto::firstNatureza($this->empresa_id);
        $anps = Produto::lista_ANP();

        if($natureza == null){
            session()->flash('mensagem_erro', 'Cadastre uma natureza de operação!');
            return redirect('/naturezaOperacao');
        }

        $produto = new Produto();
            // $listaCSTCSOSN = Produto::listaCSTCSOSN();
        $listaCST_PIS_COFINS = Produto::listaCST_PIS_COFINS();
        $listaCST_IPI = Produto::listaCST_IPI();

        $categorias = Categoria::
        where('empresa_id', $this->empresa_id)
        ->get();

        $unidadesDeMedida = Produto::unidadesMedida();
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
        ->first();

        $resp = $produto
        ->where('id', $id)->first();

        $categoriasDelivery = [];

        if($tributacao->regime == 1){
            $listaCSTCSOSN = Produto::listaCST();
        }else{
            $listaCSTCSOSN = Produto::listaCSOSN();
        }

        if($tributacao == null){
            session()->flash('mensagem_erro', 'Informe a tributação padrão!');
            return redirect('tributos');
        }

        $marcas = Marca::
        where('empresa_id', $this->empresa_id)
        ->get();

        $subs = SubCategoria::
        select('sub_categorias.*')
        ->join('categorias', 'categorias.id', '=', 'sub_categorias.categoria_id')
        ->where('empresa_id', $this->empresa_id)
        ->get();

        $subsEcommerce = SubCategoriaEcommerce::
        select('sub_categoria_ecommerces.*')
        ->join('categoria_produto_ecommerces', 'categoria_produto_ecommerces.id', '=', 'sub_categoria_ecommerces.categoria_id')
        ->where('empresa_id', $this->empresa_id)
        ->get();

        $divisoes = DivisaoGrade::
        where('empresa_id', $this->empresa_id)
        ->where('sub_divisao', false)
        ->get();

        $subDivisoes = DivisaoGrade::
        where('empresa_id', $this->empresa_id)
        ->where('sub_divisao', true)
        ->get();


        if(valida_objeto($resp)){
            return view('produtos/duplicar')
            ->with('produto', $resp)
            ->with('config', $config)
            ->with('marcas', $marcas)
            ->with('divisoes', $divisoes)
            ->with('subDivisoes', $subDivisoes)
            ->with('subs', $subs)
            ->with('subsEcommerce', $subsEcommerce)
            ->with('tributacao', $tributacao)
            ->with('natureza', $natureza)
            ->with('listaCSTCSOSN', $listaCSTCSOSN)
            ->with('listaCST_PIS_COFINS', $listaCST_PIS_COFINS)
            ->with('listaCST_IPI', $listaCST_IPI)
            ->with('categoriasDelivery', $categoriasDelivery)
            ->with('anps', $anps)
            ->with('unidadesDeMedida', $unidadesDeMedida)
            ->with('categorias', $categorias)
            ->with('produtoJs', true)
            ->with('title', 'Duplicar Produto');
        }else{
            return redirect('/403');
        }

    }

    public function grade($id){
        $produto = Produto::find($id);
        if(valida_objeto($produto)){
            $produtos = Produto::produtosDaGrade($produto->referencia_grade);

            $produtos = $this->setaEstoqueGrade($produtos);
            return view('produtos/grade')
            ->with('produtos', $produtos)
            ->with('title', 'Grade');
        }else{
            return redirect('/403');
        }
    }

    private function setaEstoqueGrade($produtos){
        foreach($produtos as $p){
            $estoque = Estoque::where('produto_id', $p->id)->first();
            $p->estoque_atual = $estoque == null ? 0 : $estoque->quantidade;
        }
        return $produtos;
    }

    public function quickSave(Request $request){
            //echo json_encode($request->produto);
        $produto = $request->data;
        $natureza = Produto::firstNatureza($this->empresa_id);

        $valorVenda = __replace($produto['valor_venda']);
        $valorCompra = __replace($produto['valor_compra']);

        try{
            $result = Produto::create([
                'nome' => $produto['nome'],
                'NCM' => $produto['NCM'],
                'valor_venda' => $valorVenda,
                'valor_compra' => $valorCompra,
                'valor_livre' => false,
                'cor' => '',
                'locais' => '["-1"]',
                'conversao_unitaria' => 1,
                'categoria_id' => $produto['categoria_id'],
                'unidade_compra' => $produto['unidade_compra'],
                'unidade_venda' => $produto['unidade_venda'],
                'codBarras' => $produto['codBarras'] ?? 'SEM GTIN',
                'composto' => false,
                'CST_CSOSN' => $produto['CST_CSOSN'],
                'CST_PIS' => $produto['CST_PIS'],
                'CST_COFINS' => $produto['CST_COFINS'],
                'CST_IPI' => $produto['CST_IPI'],
                'CST_CSOSN_EXP' => $produto['CST_CSOSN_EXP'] ?? '',
                'perc_icms' => $produto['perc_icms'],
                'perc_pis' => $produto['perc_pis'],
                'perc_cofins' => $produto['perc_cofins'],
                'perc_ipi' => $produto['perc_ipi'],
                'perc_iss' => $produto['perc_iss'],
                'pRedBC' => $produto['pRedBC'],
                'cBenef' => $produto['cBenef'] ?? '',
                'CFOP_saida_estadual' => $produto['CFOP_saida_estadual'],
                'CFOP_saida_inter_estadual' => $produto['CFOP_saida_inter_estadual'],
                'codigo_anp' => '',
                'descricao_anp' => '',
                'cListServ' => '',
                'imagem' => '',
                'alerta_vencimento' => $produto['alerta_vencimento'] ?? 0,
                'referencia' => $produto['referencia'] ?? '',
                'empresa_id' => $this->empresa_id,
                'gerenciar_estoque' => $produto['gerenciar_estoque'],
                'limite_maximo_desconto' => $produto['limite_maximo_desconto'] ?? 0,
                'perc_icms_interestadual' => $produto['perc_icms_interestadual'] ?? 0,
                'perc_icms_interno' => $produto['perc_icms_interno'] ?? 0,
                'perc_fcp_interestadual' => $produto['perc_fcp_interestadual'] ?? 0,
                'CEST' => $produto['CEST'] ?? '',
                'grade' => 0,
                'referencia_grade' => Str::random(20),
                'percentual_lucro' => $produto['percentual_lucro'],

                'CST_CSOSN_entrada' => isset($produto['CST_CSOSN_entrada']) ? $produto['CST_CSOSN_entrada'] : '102',
                'CST_PIS_entrada' => isset($produto['CST_PIS_entrada']) ? $produto['CST_PIS_entrada'] : '99',
                'CST_COFINS_entrada' => isset($produto['CST_COFINS_entrada']) ? $produto['CST_COFINS_entrada'] : '99',
                'CST_IPI_entrada' => isset($produto['CST_IPI_entrada']) ? $produto['CST_IPI_entrada'] : '49',
            ]);
            return response()->json($result, 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }

    }

    public function teste(){
        $filial_id = -1;
        $data = Produto::where('empresa_id', $this->empresa_id)
        ->whereIn('produtos.locais', function($query){
            $query->select('locais');
        })
        ->get();

        echo $data->pluck('nome');
    }

    public function atualizarGradeCompleta(Request $request){
        $product = new Produto();

        $id = $request->input('id');
        $resp = $product
        ->where('id', $id)->first();

        $gradeCompleta = $product->
        where('referencia_grade', $resp->referencia_grade)
        ->get();

        $this->_validate($request);

        $anps = Produto::lista_ANP();
        $descAnp = '';
        foreach($anps as $key => $a){
            if($key == $request->anp){
                $descAnp = $a;
            }
        }

        try{
            foreach($gradeCompleta as $g){
                $resp = $g;

                $resp->nome = $request->input('nome');
                $resp->categoria_id = $request->input('categoria_id');
                $resp->sub_categoria_id = $request->input('sub_categoria_id');
                $resp->marca_id = $request->input('marca_id');
                $resp->cor = $request->input('cor');
                if($request->check_valor_venda){
                    $resp->valor_venda = str_replace(",", ".", $request->input('valor_venda'));
                }

                if($request->check_valor_compra){
                    $resp->valor_compra = str_replace(",", ".", $request->input('valor_compra'));
                }

                $resp->percentual_lucro = str_replace(",", ".", $request->input('percentual_lucro'));
                $resp->NCM = $request->input('NCM');
                $resp->CEST = $request->input('CEST') ?? '';

                $resp->CST_CSOSN = $request->input('CST_CSOSN');
                $resp->CST_CSOSN_EXP = $request->input('CST_CSOSN_EXP');

                $resp->CST_PIS = $request->input('CST_PIS');
                $resp->CST_COFINS = $request->input('CST_COFINS');
                $resp->CST_IPI = $request->input('CST_IPI');
            // $resp->CFOP = $request->input('CFOP');
                $resp->unidade_venda = $request->input('unidade_venda');
                $resp->unidade_compra = $request->input('unidade_compra');
                $resp->conversao_unitaria = $request->input('conversao_unitaria') ? $request->input('conversao_unitaria') : $resp->conversao_unitaria;
                $resp->codBarras = $request->input('codBarras') ?? 'SEM GTIN';

                $resp->perc_icms = $request->perc_icms ? __replace($request->perc_icms) : 0;
                $resp->perc_pis = $request->perc_pis ? __replace($request->perc_pis) : 0;
                $resp->perc_cofins = $request->perc_cofins ? __replace($request->perc_cofins) : 0;
                $resp->perc_ipi = $request->perc_ipi ? __replace($request->perc_ipi) : 0;
                $resp->perc_iss = $request->perc_iss ? __replace($request->perc_iss) : 0;
                $resp->cListServ = $request->input('cListServ');

                $resp->CFOP_saida_estadual = $request->input('CFOP_saida_estadual');
                $resp->CFOP_saida_inter_estadual = $request->input('CFOP_saida_inter_estadual');
                $resp->codigo_anp = $request->input('anp') ?? '';
                $resp->perc_glp = $request->perc_glp ? __replace($request->perc_glp) : 0;
                $resp->perc_gnn = $request->perc_gnn ? __replace($request->perc_gnn) : 0;
                $resp->perc_gni = $request->perc_gni ? __replace($request->perc_gni) : 0;
                $resp->valor_partida = $request->valor_partida ?
                __replace($request->valor_partida) : 0;

                $resp->quantidade_tributavel = $request->quantidade_tributavel ?
                __replace($request->quantidade_tributavel) : 0;

                $resp->unidade_tributavel = $request->unidade_tributavel ?? '';

                $resp->descricao_anp = $request->anp ?? '';
                $resp->alerta_vencimento = $request->alerta_vencimento;

                $resp->referencia = $request->referencia;
                $resp->referencia_balanca = $request->referencia_balanca;

                $resp->composto = $request->composto ? true : false;
                $resp->valor_livre = $request->valor_livre ? true : false;
                $resp->gerenciar_estoque = $request->gerenciar_estoque ? true : false;
                $resp->reajuste_automatico = $request->reajuste_automatico ? true : false;
                $resp->inativo = $request->inativo ? true : false;
                $resp->estoque_minimo = $request->estoque_minimo;

                $resp->pRedBC = __replace($request->pRedBC);
                $resp->cBenef = $request->cBenef;

                $resp->largura = $request->largura;
                $resp->comprimento = $request->comprimento;
                $resp->altura = $request->altura;
                $resp->peso_liquido = __replace($request->peso_liquido);
                $resp->peso_bruto = __replace($request->peso_bruto);
                $resp->limite_maximo_desconto = $request->limite_maximo_desconto;

                $resp->perc_icms_interestadual = $request->perc_icms_interestadual ? __replace($request->perc_icms_interestadual) : 0;
                $resp->perc_icms_interno = $request->perc_icms_interno ? __replace($request->perc_icms_interno) : 0;
                $resp->perc_fcp_interestadual = $request->perc_fcp_interestadual ? __replace($request->perc_fcp_interestadual) : 0;


                $resp->renavam = $request->renavam ?? '';
                $resp->placa = $request->placa ?? '';
                $resp->chassi = $request->chassi ?? '';
                $resp->combustivel = $request->combustivel ?? '';
                $resp->ano_modelo = $request->ano_modelo ?? '';
                $resp->cor_veiculo = $request->cor_veiculo ?? '';

                $resp->lote = $request->lote ?? '';
                $resp->vencimento = $request->vencimento ?? '';

                    // $this->salveImagemProduto($request, $resp);
                if($request->hasFile('file')){
                    $nomeImagem = $this->salveImagemProdutoTemp($request);
                    $resp->imagem = $nomeImagem;
                }

                $result = $resp->save();

                $estoque = $request->estoque;
                $stockMove = new StockMove();

                if($estoque && $request->check_estoque){
                    $estoque = __replace($request->estoque);
                    if(!$produto->estoque){
                        $data = [
                            'produto_id' => $produto->id,
                            'usuario_id' => get_id_user(),
                            'quantidade' => $estoque,
                            'tipo' => 'incremento',
                            'observacao' => '',
                            'empresa_id' => $this->empresa_id
                        ];

                        AlteracaoEstoque::create($data);
                        $result = $stockMove->pluStock($produto->id,
                            $estoque, str_replace(",", ".", $produto->valor_venda));
                        $mensagem_sucesso = "Produto editado com sucesso, e estoque atribuido!";
                    }else{

                        if($produto->estoque->quantidade > $estoque || $produto->estoque->quantidade < $estoque){
                        //alterar

                            $tipo = '';
                            $valorAlterar = 0;
                            $estoqueAtual = $produto->estoque->quantidade;
                            if($estoqueAtual > $estoque){
                                $tipo = 'reducao';
                                $valorAlterar = $estoqueAtual - $estoque;
                            }else{
                                $tipo = 'incremento';
                                $valorAlterar = $estoque - $estoqueAtual;

                            }
                            $data = [
                                'produto_id' => $produto->id,
                                'usuario_id' => get_id_user(),
                                'quantidade' => $valorAlterar,
                                'tipo' => $tipo,
                                'observacao' => '',
                                'empresa_id' => $this->empresa_id
                            ];

                            AlteracaoEstoque::create($data);
                            if($produto->estoque->quantidade > $estoque){
                                $stockMove->pluStock($produto->id,
                                    $valorAlterar, str_replace(",", ".", $produto->valor_venda));
                            }else{
                                $stockMove->pluStock($produto->id,
                                    $valorAlterar, str_replace(",", ".", $produto->valor_venda));
                            }

                        }
                    }
                }
            }
            session()->flash('mensagem_sucesso', 'Grade alterada!');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro ao editar ' . $e->getMessage());

        }
        return redirect('/produtos');
    }

    public function pesquisaSelect2(Request $request){
        $pesquisa = $request->pesquisa;
        $categoria_id = $request->categoria_id;

        $data = Produto::
        where('produtos.empresa_id', $this->empresa_id)
        ->select('produtos.*')
        ->when($categoria_id, function ($q) use ($categoria_id) {
            return $q->where('categoria_id', $categoria_id);
        })
        ->when(is_numeric($pesquisa), function ($q) use ($pesquisa) {
            return $q->where('codBarras', 'like', "%$pesquisa%");
        })
        ->when(!is_numeric($pesquisa), function ($q) use ($pesquisa) {
            return $q->where('nome', 'like', "%$pesquisa%");
        })
        ->get();

        return response()->json($data, 200);

    }

    public function autocomplete(Request $request){
        try{
            $filial_id = $request->filial_id;
            $lista_id = $request->lista_id;
            $categoria = $request->categoria;

            $filial_id = ($filial_id == 'null' || !$filial_id) ? -1 : $filial_id;

            $produtos = Produto::
            where('produtos.empresa_id', $this->empresa_id)
            ->select('produtos.*')
            ->where('produtos.inativo', false)
            //->where('produtos.valor_venda', '>', 0) // Busca de Produtos (sem a trava do valor_venda > 0)
            ->where('produtos.nome', 'LIKE', "%$request->pesquisa%")
            ->where('produtos.locais', 'like', "%$filial_id%")
            ->orderBy('produtos.nome')
            ->with('listaPreco')
            ->when($categoria != null && $categoria != 'todos', function ($query) use ($categoria) {
                return $query->where('produtos.categoria_id', $categoria);
            })
            ->get();

            $refs = Produto::
            where('empresa_id', $this->empresa_id)
            ->select('produtos.*')
            ->where('produtos.referencia', 'LIKE', "%$request->pesquisa%")
            ->orderBy('produtos.nome')
            ->with('listaPreco')
            ->where('produtos.locais', 'like', "%$filial_id%")
            ->where('produtos.inativo', false)
            ->where('produtos.valor_venda', '>', 0)
            ->when($categoria != null && $categoria != 'todos', function ($query) use ($categoria) {
                return $query->where('produtos.categoria_id', $categoria);
            })
            ->get();

            $permissaoAcesso = __getLocaisUsarioLogado();
            $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

            $temp = [];
            $add = [];

            foreach($produtos as $p){

                $locais = json_decode($p->locais);
                $p->estoqueAtual = $p->estoquePorLocalPavaVenda($filial_id, $config);

                if($lista_id){
                    $p->valor_venda = $p->listaPreco->where('lista_id', $lista_id)->first()->valor;
                }

                if(sizeof($locais) > 1){
                    foreach($locais as $l){

                        if(in_array($l, $permissaoAcesso) && !in_array($p->id, $add)){
                            array_push($temp, $p);
                            array_push($add, $p->id);
                        }
                    }
                }else{
                    array_push($temp, $p);
                }
            }

            foreach($refs as $p){
                $locais = json_decode($p->locais);
                $p->estoqueAtual = $p->estoquePorLocal($filial_id);
                if(sizeof($locais) > 1){
                    foreach($locais as $l){
                        if(in_array($l, $permissaoAcesso) && !in_array($p->id, $add)){
                            array_push($temp, $p);
                            array_push($add, $p->id);
                        }
                    }
                }else{
                    array_push($temp, $p);
                }
            }

            $prods = [];
            $add = [];

            if($filial_id){
                foreach($temp as $p){
                    $locais = json_decode($p->locais);
                    $ts = "";
                    foreach($locais as $l){
                        if($l == $filial_id && !in_array($p->id, $add)){
                            array_push($prods, $p);
                            array_push($add, $p->id);
                        }
                    }
                }
                return response()->json($prods, 200);

            }else{

                return response()->json($temp, 200);
            }


        }catch(\Exception $e){
            return response($e->getMessage(), 401);
        }
    }

    public function autocompleteProduto(Request $request){
        $filial_id = $request->filial_id;
        $natureza_id = $request->natureza_id;

        $natureza = null;
        if($natureza_id){
            $natureza = NaturezaOperacao::findOrFail($natureza_id);
        }

        if($request->lista_id == 0){

            $produto = Produto::
            where('empresa_id', $this->empresa_id)
            ->where('id', $request->id)
            ->first();
            $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
            $produto->estoqueAtual = $produto->estoqueAtualPdv($filial_id);

            if($filial_id){
                $produto->_estoque = Estoque::where('produto_id', $produto->id)
                ->where('filial_id', $filial_id)
                ->first();
            }
        }else{
            // $produtoListaPreco = ProdutoListaPreco::
            // where('produto_id', $request->id)
            // ->where('lista_id', $request->lista_id)
            // ->with('listaPreco')
            // ->first();

            $produto = Produto::
            select('produtos.*')
            ->join('produto_lista_precos', 'produto_lista_precos.produto_id', '=', 'produtos.id')
            ->where('produtos.empresa_id', $this->empresa_id)
            ->where('produtos.id', $request->id)
            ->with('listaPreco')
            ->first();

            $produto->estoqueAtual = $produto->estoqueAtual($filial_id);

            // $produto = $produtoListaPreco->produto;
            // $produto->estoqueAtual = $produto->estoqueAtual();
            $produto->valor_venda = $produto->listaPreco->where('lista_id', $request->lista_id)
            ->first()->valor;



        }
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        if(isset($request->cliente_id)){

            $cliente = Cliente::find($request->cliente_id);
            if($cliente != null){
                if($config->UF != $cliente->cidade->uf){
                    $produto->CFOP_saida_estadual = $produto->CFOP_saida_inter_estadual;
                }
            }

        }

        if($natureza != null){
            if($natureza->sobrescreve_cfop){
                $cliente = Cliente::find($request->cliente_id);
                if($cliente != null){
                    if($config->UF != $cliente->cidade->uf){
                        $produto->CFOP_saida_estadual = $natureza->CFOP_saida_inter_estadual;
                    }else{
                        $produto->CFOP_saida_estadual = $natureza->CFOP_saida_estadual;
                    }
                }else{
                    $produto->CFOP_saida_estadual = $natureza->CFOP_saida_estadual;
                }
            }

            if($natureza->CST_CSOSN){
                $produto->CST_CSOSN = $natureza->CST_CSOSN;
            }
        }
        return response($produto, 200);

    }

    public function produtosDaCategoria(Request $request){
        $filial_id = $request->filial_id;
        $filial_id = $filial_id == 'null' ? -1 : $filial_id;
        if($request->lista_id == 0){

            $data = Produto::
            where('empresa_id', $this->empresa_id)
            ->where('categoria_id', $request->categoria_id)
            ->where('produtos.locais', 'like', "%$filial_id%")
            ->limit(50)
            ->get();
            $prods = [];
            foreach($data as $p){
                $p->estoqueAtual = $p->estoquePorLocalPavaVenda($filial_id);
                $locais = json_decode($p->locais);
                if(sizeof($locais) > 1){
                    foreach($locais as $l){
                        if($l == $filial_id){
                            array_push($prods, $p);
                        }
                    }
                }else{
                    array_push($prods, $p);
                }

            }

            return response($prods, 200);
        }else{

            $data = Produto::
            select('produtos.*')
            ->where('produtos.empresa_id', $this->empresa_id)
            ->join('produto_lista_precos', 'produto_lista_precos.produto_id', '=', 'produtos.id')
            ->where('categoria_id', $request->categoria_id)
            ->where('produtos.locais', 'like', "%$filial_id%")
            ->where('produto_lista_precos.id', $request->lista_id)
            ->with('listaPreco')
            ->limit(50)
            ->get();

            foreach($data as $p){
                $p->estoqueAtual = $p->estoqueAtual();
            }
            return response($data, 200);

        }
    }

    public function produtosRandom(Request $request){
        $filial_id = $request->filial_id;

        $filial_id = $filial_id == 'null' ? -1 : $filial_id;
        if($request->lista_id == 0){
            $data = Produto::
            where('empresa_id', $this->empresa_id)
            ->inRandomOrder()
            ->limit(50)
            ->get();

            $prods = [];
            foreach($data as $p){
                $p->estoqueAtual = $p->estoquePorLocalPavaVenda($filial_id);
                $locais = json_decode($p->locais);
                foreach($locais as $l){
                    if($l == $filial_id){
                        array_push($prods, $p);
                    }
                }
            }

            return response($prods, 200);
        }else{

            $data = Produto::
            select('produtos.*')
            ->join('produto_lista_precos', 'produto_lista_precos.produto_id', '=', 'produtos.id')
            ->where('produto_lista_precos.lista_id', $request->lista_id)
            ->with('listaPreco')
            ->get();

            foreach($data as $p){
                $p->estoqueAtual = $p->estoqueAtual();
            }
            return response($data, 200);

        }
    }

    public function findById(Request $request){

        if($request->lista_id == 0){
            $produto = Produto::
            where('empresa_id', $this->empresa_id)
            ->where('id', $request->id)
            ->first();

            $produto->estoqueAtual = $produto->estoqueAtual();
            return response($produto, 200);
        }else{
            $produtoListaPreco = ProdutoListaPreco::
            where('produto_id', $request->id)
            ->where('lista_id', $request->lista_id)
            ->first();

            $produto = $produtoListaPreco->produto;
            $produto->estoqueAtual = $produto->estoqueAtual();
            $produto->valor_venda = $produtoListaPreco->valor;
            return response($produto, 200);

        }

    }

        // public function zebra($id){

        //     $nome = "Cerv Original 350ml";
        //     $codigo = "7891000777794";
        //     $valor = "4,50";
        //     $unidade = "Un";

        //     $generatorPNG = new \Picqer\Barcode\BarcodeGeneratorPNG();

        //     $bar_code = $generatorPNG->getBarcode($codigo, $generatorPNG::TYPE_EAN_13);
        //     safe_file_put_contents("etiqueta.png", $bar_code);
        //     $pdf = new TcpdfFpdi('P', 'mm', 'A4');
        //     $pdf->AddPage('L', [30, 50]);
        //     $pdf->SetMargins(0,0,0, false);


        //     $pdf->SetFont('helvetica', '', 10);

        //     // $pdf->Image("etiqueta.png", 10, 1, 30);
        //     $pdf->Text(1, 5, $nome);
        //     $pdf->Text(1, 10, $codigo);


        //     $pdf->Output();
        // }

    public function etiqueta($id){
        try{
            $padrosEtiqueta = Etiqueta::
            where('empresa_id', null)
            ->orWhere('empresa_id', $this->empresa_id)
            ->get();
                // $pTemp = Etiqueta::
                // where('empresa_id', $this->empresa_id)
                // ->get();

                // foreach($padrosEtiqueta as $p){

                // }
            $produto = Produto::find($id);
            if(valida_objeto($produto)){

                return view('produtos/etiqueta')
                ->with('title', 'Gerar etiqueta')
                ->with('padrosEtiqueta', $padrosEtiqueta)
                ->with('produto', $produto);
            }else{
                return redirect('/403');
            }
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function etiquetaStore(Request $request){

        $this->_validateEtiqueta($request);
        try{

            $files = glob(public_path("barcode/*"));

            foreach($files as $file){
                if(is_file($file)) {
                    unlink($file);
                }
            }

            $produto = Produto::find($request->produto_id);
            $nome = $produto->nome . " " . $produto->str_grade;
            $codigo = $produto->codBarras;
            $valor = $produto->valor_venda;
            $unidade = $produto->unidade_venda;

            if($codigo == "" || $codigo == "SEM GTIN" || $codigo == "sem gtin"){
                session()->flash('mensagem_erro', 'Produto sem código de barras definido');
                return redirect()->back();
            }

            $data = [
                'nome_empresa' => $request->nome_empresa ? true : false,
                'nome_produto' => $request->nome_produto ? true : false,
                'valor_produto' => $request->valor_produto ? true : false,
                'cod_produto' => $request->cod_produto ? true : false,
                'tipo' => $request->tipo,
                'codigo_barras_numerico' => $request->codigo_barras_numerico ? true : false,
                'nome' => $nome,
                'codigo' => $produto->id . ($produto->referencia != '' ? ' | REF'.$produto->referencia : ''),
                'valor' => $valor,
                'unidade' => $unidade,
                'empresa' => $produto->empresa->nome
            ];
            $generatorPNG = new \Picqer\Barcode\BarcodeGeneratorPNG();

            $bar_code = $generatorPNG->getBarcode($codigo, $generatorPNG::TYPE_EAN_13);

            $rand = rand(1000, 9999);
            safe_file_put_contents(public_path("barcode")."/$rand.png", $bar_code);
            $qtdLinhas = $request->qtd_linhas;
            $qtdTotal = $request->qtd_etiquetas;

            return view('produtos/print')
            ->with('altura', $request->altura)
            ->with('largura', $request->largura)
            ->with('rand', $rand)
            ->with('codigo', $codigo)
            ->with('quantidade', $qtdTotal)
            ->with('distancia_topo', $request->dist_topo)
            ->with('distancia_lateral', $request->dist_lateral)
            ->with('quantidade_por_linhas', $qtdLinhas)
            ->with('tamanho_fonte', $request->tamanho_fonte)
            ->with('tamanho_codigo', $request->tamanho_codigo)
            ->with('data', $data);
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    private function _validateEtiqueta(Request $request){
        $rules = [
            'largura' => 'required',
            'altura' => 'required',
            'qtd_linhas' => 'required',
            'dist_lateral' => 'required',
            'dist_topo' => 'required',
            'qtd_etiquetas' => 'required',
            'tamanho_fonte' => 'required',
            'tamanho_codigo' => 'required',
        ];

        $messages = [
            'largura.required' => 'Campo obrigatório.',
            'altura.required' => 'Campo obrigatório.',
            'qtd_linhas.required' => 'Campo obrigatório.',
            'dist_lateral.required' => 'Campo obrigatório.',
            'dist_topo.required' => 'Campo obrigatório.',
            'qtd_etiquetas.required' => 'Campo obrigatório.',
            'tamanho_fonte.required' => 'Campo obrigatório.',
            'tamanho_codigo.required' => 'Campo obrigatório.',

        ];
        $this->validate($request, $rules, $messages);
    }

    public function verEtiquetasPadroes(){
        $etiquetas = Etiqueta::
        where('empresa_id', $this->empresa_id)
        ->get();
        $title = 'Etiquetas por empresa';
        return view('produtos/etiquetas_list', compact('title', 'etiquetas'));
    }

    public function newEtiquetaPadrao(){
        return view('produtos/etiquetas_register')
        ->with('title', 'Nova etiqueta');
    }

    public function editEtiqueta($id){
        $etiqueta = Etiqueta::find($id);
        if(valida_objeto($etiqueta)){
            return view('produtos/etiquetas_register')
            ->with('etiqueta', $etiqueta)
            ->with('title', 'Nova etiqueta');
        }else{
            return redirect('/403');
        }
    }

    public function saveEtiqueta(Request $request){
        $this->_validateEtiqueta2($request);

        try{
            $request->merge([
                'nome_empresa' => $request->nome_empresa ? true : false,
                'nome_produto' => $request->nome_produto ? true : false,
                'valor_produto' => $request->valor_produto ? true : false,
                'codigo_produto' => $request->codigo_produto ? true : false,
                'observacao' => $request->observacao ?? '',
                'empresa_id' => $this->empresa_id
            ]);
            Etiqueta::create($request->all());
            session()->flash('mensagem_sucesso', "Etiqueta cadastrada");

        }catch(\Exception $e){
            session()->flash('mensagem_erro', $e->getMessage());
        }
        return redirect('/produtos/verEtiquetasPadroes');
    }

    public function updateEtiqueta(Request $request){
        $this->_validateEtiqueta2($request);
        try{
            $etiqueta = Etiqueta::find($request->id);
            $etiqueta->nome_empresa = $request->nome_empresa ? true : false;
            $etiqueta->nome_produto = $request->nome_produto ? true : false;
            $etiqueta->valor_produto = $request->valor_produto ? true : false;
            $etiqueta->codigo_produto = $request->codigo_produto ? true : false;
            $etiqueta->codigo_barras_numerico = $request->codigo_barras_numerico ? true : false;
            $etiqueta->observacao = $request->observacao ?? '';

            $etiqueta->nome = $request->nome;
            $etiqueta->distancia_etiquetas_lateral = $request->distancia_etiquetas_lateral;
            $etiqueta->altura = $request->altura;
            $etiqueta->largura = $request->largura;
            $etiqueta->etiquestas_por_linha = $request->etiquestas_por_linha;
            $etiqueta->distancia_etiquetas_topo = $request->distancia_etiquetas_topo;
            $etiqueta->quantidade_etiquetas = $request->quantidade_etiquetas;
            $etiqueta->tamanho_fonte = $request->tamanho_fonte;
            $etiqueta->tamanho_codigo_barras = $request->tamanho_codigo_barras;
            $etiqueta->save();
            session()->flash('mensagem_sucesso', "Etiqueta atualiada!");
        }catch(\Exception $e){
            session()->flash('mensagem_erro', $e->getMessage());
        }
        return redirect('/produtos/verEtiquetasPadroes');
    }

    private function _validateEtiqueta2(Request $request){
        $rules = [
            'nome' => 'required',
            'altura' => 'required',
            'largura' => 'required',
            'etiquestas_por_linha' => 'required',
            'distancia_etiquetas_lateral' => 'required',
            'distancia_etiquetas_topo' => 'required',
            'quantidade_etiquetas' => 'required',
            'tamanho_fonte' => 'required',
            'tamanho_codigo_barras' => 'required',
        ];

        $messages = [
            'nome.required' => 'Campo obrigatório.',
            'altura.required' => 'Campo obrigatório.',
            'largura.required' => 'Campo obrigatório.',
            'etiquestas_por_linha.required' => 'Campo obrigatório.',
            'distancia_etiquetas_lateral.required' => 'Campo obrigatório.',
            'distancia_etiquetas_topo.required' => 'Campo obrigatório.',
            'quantidade_etiquetas.required' => 'Campo obrigatório.',
            'tamanho_fonte.required' => 'Campo obrigatório.',
            'tamanho_codigo_barras.required' => 'Campo obrigatório.',
        ];
        $this->validate($request, $rules, $messages);
    }

    public function deleteEtiqueta($id){
        try{
            $etiqueta = Etiqueta::find($id);
            if(valida_objeto($etiqueta)){
                $etiqueta->delete();
                session()->flash('mensagem_sucesso', "Etiqueta removida");
            }else{
                return redirect('/403');
            }

        }catch(\Exception $e){
            session()->flash('mensagem_erro', $e->getMessage());
        }
        return redirect('/produtos/verEtiquetasPadroes');
    }

    public function exportacao(){
        $produtos = Produto::
        where('empresa_id', $this->empresa_id)
        ->get();
        $relatorioEx = new ProdutoExport($produtos);
        return Excel::download($relatorioEx, 'produtos.xlsx');
    }

    public function randGrade(){
        $produtos = Produto::all();
        foreach($produtos as $p){
            $p->referencia_grade = Str::random(20);
            $p->grade = false;
            $p->str_grade = '';
            $p->save();
        }
    }

    public function dup($qtd){
        // $produtos = Produto::
        // where('empresa_id', $this->empresa_id)->get();
            // for($i=0; $i< 2400; $i++){
            //     foreach($produtos as $p){

            //         $t = $p->toArray();
            //         unset($t['id']);
            //         $t['nome'] = Str::random(10);
            //         Produto::create($t);
            //     }
            // }
    }

    public function exportacaoBalanca(){
        $data = Produto::where('referencia_balanca', '!=', '')
        ->get();

        return view('produtos.balanca', compact('data'))
        ->with('title', 'Produtos Balança');
    }

    public function exportacaoBalancaFile(Request $request){
        if(sizeof($request->produto_id) == 0){
            session()->flash('mensagem_erro', "Selecione ao menos um produto!");
            return redirect()->back();
        }
        $fileStr = "";

        for($i=0; $i<sizeof($request->produto_id); $i++){
            $produto = Produto::findOrFail($request->produto_id[$i]);
            $fileStr .= "0101";
            $referencia_balanca = $produto->referencia_balanca;
            for($j=0; $j<(7-strlen($referencia_balanca)); $j++){
                $fileStr .= "0";
            }
            $fileStr .= $produto->referencia_balanca;

            $vl = str_replace(".", "", number_format($produto->valor_venda,2));
            for($j=0; $j<(6-strlen($vl)); $j++){
                $fileStr .= "0";
            }
            $fileStr .= $vl;

            $vencimento = \Carbon\Carbon::parse(str_replace("/", "-", $produto->vencimento))->format('Y-m-d');
            $dataHoje = date('Y-m-d');

            $dif = strtotime($vencimento) - strtotime($dataHoje);
            $dias = floor($dif / (60 * 60 * 24));
            $dias = $produto->alerta_vencimento;

            for($j=0; $j<(3-strlen($dias)); $j++){
                $fileStr .= "0";
            }
            $fileStr .= $dias;
            $fileStr .= $this->retiraAcentos($produto->nome);

            $fileStr .= "\n";
        }
        $modelo = $request->modelo;
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-disposition: attachment; filename=TXITENS.txt');
        echo $fileStr;
    }

    private function retiraAcentos($texto){
        return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/", "/(ç)/"),explode(" ","a A e E i I o O u U n N c"),$texto);
    }

     /**
     * Busca produtos para o Select2 no formulário de ticket de pesagem.
     * Retorna JSON no formato que o Select2 espera em `data.results`.
     */
     public function searchProduto(Request $request)
    {
        $term = $request->get('term', '');

        $produtos = Produto::where('empresa_id', $this->empresa_id)
            ->where(function ($query) use ($term) {
                $query->where('nome', 'like', "%{$term}%")
                    ->orWhere('referencia', 'like', "%{$term}%")
                    ->orWhere('codBarras', 'like', "%{$term}%");
            })
            ->limit(10)
            ->get([
                'id', 'nome', 'categoria_id', 'cor', 'valor_venda', 'NCM', 'CST_CSOSN',
                'CST_PIS', 'CST_COFINS', 'CST_IPI', 'unidade_compra', 'unidade_venda',
                'composto', 'codBarras', 'conversao_unitaria', 'valor_livre', 'perc_icms',
                'perc_pis', 'perc_cofins', 'perc_ipi', 'CFOP_saida_estadual',
                'CFOP_saida_inter_estadual', 'codigo_anp', 'descricao_anp', 'perc_iss',
                'cListServ', 'imagem', 'alerta_vencimento', 'valor_compra', 'gerenciar_estoque',
                'estoque_minimo', 'referencia', 'empresa_id', 'largura', 'comprimento',
                'altura', 'peso_liquido', 'peso_bruto', 'limite_maximo_desconto', 'pRedBC',
                'cBenef', 'percentual_lucro', 'CST_CSOSN_EXP', 'referencia_grade', 'grade',
                'str_grade', 'perc_glp', 'perc_gnn', 'perc_gni', 'valor_partida',
                'unidade_tributavel', 'quantidade_tributavel', 'perc_icms_interestadual',
                'perc_icms_interno', 'perc_fcp_interestadual', 'inativo', 'CEST',
                'sub_categoria_id', 'marca_id', 'referencia_balanca', 'renavam', 'placa',
                'chassi', 'combustivel', 'ano_modelo', 'cor_veiculo', 'reajuste_automatico',
                'valor_locacao', 'lote', 'vencimento', 'origem', 'tipo_dimensao', 'perc_comissao',
                'acrescimo_perca', 'nuvemshop_id', 'info_tecnica_composto', 'CST_CSOSN_entrada',
                'CST_PIS_entrada', 'CST_COFINS_entrada', 'CST_IPI_entrada', 'CFOP_entrada_estadual',
                'CFOP_entrada_inter_estadual', 'custo_assessor', 'envia_controle_pedidos',
                'cenq_ipi', 'tela_pedido_id', 'ifood_id', 'modBCST', 'modBC', 'pICMSST',
                'locais', 'perc_frete', 'perc_outros', 'perc_mlv', 'perc_mva', 'valor_comissao',
                'adRemICMSRet', 'tipo_servico', 'pBio', 'indImport', 'cUFOrig', 'pOrig',
                'peso', 'info_adicional_item', 'observacao'
            ]);

        $results = $produtos->map(function ($produto) {
            return [
                'id' => $produto->id,
                'nome' => $produto->nome,
                'text' => "{$produto->referencia} | {$produto->nome} | {$produto->codBarras}",
                'valor_venda' => number_format($produto->valor_venda, 2, ',', '.'),
                'referencia' => $produto->referencia,
                'codBarras' => $produto->codBarras,
                'categoria' => $produto->categoria->descricao ?? 'Sem categoria',
                'imagem' => $produto->imagem
                    ? asset("imgs_produtos/{$produto->imagem}")
                    : asset("imgs/no_image.png"),
                'dados_completos' => $produto->only([
                    'cor', 'NCM', 'CST_CSOSN', 'CST_PIS', 'CST_COFINS', 'CST_IPI',
                    'unidade_compra', 'unidade_venda', 'composto', 'conversao_unitaria',
                    'valor_livre', 'perc_icms', 'perc_pis', 'perc_cofins', 'perc_ipi',
                    'CFOP_saida_estadual', 'CFOP_saida_inter_estadual', 'codigo_anp',
                    'descricao_anp', 'perc_iss', 'cListServ', 'alerta_vencimento',
                    'valor_compra', 'gerenciar_estoque', 'estoque_minimo', 'referencia',
                    'empresa_id', 'largura', 'comprimento', 'altura', 'peso_liquido',
                    'peso_bruto', 'limite_maximo_desconto', 'pRedBC', 'cBenef',
                    'percentual_lucro', 'CST_CSOSN_EXP', 'referencia_grade', 'grade',
                    'str_grade', 'perc_glp', 'perc_gnn', 'perc_gni', 'valor_partida',
                    'unidade_tributavel', 'quantidade_tributavel', 'perc_icms_interestadual',
                    'perc_icms_interno', 'perc_fcp_interestadual', 'inativo', 'CEST',
                    'sub_categoria_id', 'marca_id', 'referencia_balanca', 'renavam', 'placa',
                    'chassi', 'combustivel', 'ano_modelo', 'cor_veiculo', 'reajuste_automatico',
                    'valor_locacao', 'lote', 'vencimento', 'origem', 'tipo_dimensao',
                    'perc_comissao', 'acrescimo_perca', 'nuvemshop_id', 'info_tecnica_composto',
                    'CST_CSOSN_entrada', 'CST_PIS_entrada', 'CST_COFINS_entrada', 'CST_IPI_entrada',
                    'CFOP_entrada_estadual', 'CFOP_entrada_inter_estadual', 'custo_assessor',
                    'envia_controle_pedidos', 'cenq_ipi', 'tela_pedido_id', 'ifood_id',
                    'modBCST', 'modBC', 'pICMSST', 'locais', 'perc_frete', 'perc_outros',
                    'perc_mlv', 'perc_mva', 'valor_comissao', 'adRemICMSRet', 'tipo_servico',
                    'pBio', 'indImport', 'cUFOrig', 'pOrig', 'peso', 'info_adicional_item',
                    'observacao'
                ]),
            ];
        });

        return response()->json(['results' => $results]);
    }

    public function corrigeLocais(Request $request)
    {
        $produtos = Produto::where('empresa_id', $this->empresa_id)->get();
        $count = 0;

        foreach ($produtos as $p) {
            $raw     = $p->locais;
            $decoded = json_decode($raw, true);

            // Corrige aspas simples ou JSON malformado
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                $decoded = json_decode(str_replace("'", '"', $raw), true) ?: [];
            }

            $hasSentinel = false;
            $cleanNums   = [];

            foreach ($decoded as $loc) {
                // null ou qualquer valor < 1 vira sentinel
                if ($loc === null || intval($loc) < 1) {
                    $hasSentinel = true;
                } else {
                    $cleanNums[] = intval($loc);
                }
            }

            // único e ordena
            $cleanNums = array_unique($cleanNums);
            sort($cleanNums, SORT_NUMERIC);

            // monta o array final
            $resultNums = [];
            if ($hasSentinel || empty($cleanNums)) {
                $resultNums[] = -1;
            }
            foreach ($cleanNums as $n) {
                $resultNums[] = $n;
            }
            $resultNums = array_unique($resultNums);

            // transforma em strings e codifica
            $asStrings = array_map('strval', $resultNums);
            $novoJson  = json_encode($asStrings);

            if ($novoJson !== $raw) {
                $p->locais = $novoJson;
                $p->save();
                $count++;
            }
        }

        return redirect()->back()
            ->with('mensagem_sucesso', "Corrigidos {$count} produto(s).");
    }

    public function corrigeLocais_(Request $request)
    {
        $produtos = Produto::where('empresa_id', $this->empresa_id)->get();
        $count = 0;

        foreach ($produtos as $p) {
            $raw = $p->locais;
            $decoded = json_decode($raw, true);

            // tenta consertar aspas simples
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                $decoded = @json_decode(str_replace("'", '"', $raw), true);
            }

            // filtra só valores numéricos maiores que 1
            $clean = [];
            if (is_array($decoded)) {
                foreach ($decoded as $loc) {
                    $num = intval($loc);
                    if ($num > 1) {
                        $clean[] = $num;
                    }
                }
            }

            // se não sobrou nada válido, coloca só o sentinela -1
            if (empty($clean)) {
                $clean = [-1];
            }

            // elimina duplicatas e ordena
            $clean = array_unique($clean);
            sort($clean, SORT_NUMERIC);

            // transforma em strings e json-encode
            $asStrings = array_map('strval', $clean);
            $novoJson = json_encode($asStrings);

            if ($novoJson !== $raw) {
                $p->locais = $novoJson;
                $p->save();
                $count++;
            }
        }

        return redirect()->back()
            ->with('mensagem_sucesso', "Corrigidos {$count} produto(s).");
    }

    private function resolveReducaoIbsCbs(string $classTrib): array
    {
        $classTrib = trim($classTrib);
        if ($classTrib === '') {
            return ['predibs' => 0, 'predcbs' => 0];
        }

        $row = ClassTribIbsCbs::where('CCLASSTRIB', $classTrib)->first();

        if (!$row) {
            return ['predibs' => 0, 'predcbs' => 0];
        }

        return [
            'predibs' => (float) ($row->PREDIBS ?? 0),
            'predcbs' => (float) ($row->PREDCBS ?? 0),
        ];
    }

    public function ajaxCstIbsCbs(Request $request)
    {
        $term = trim((string) $request->get('term', ''));
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 20;

        $q = CstIbsCbs::query()
            ->select(['cst_ibs_cbs', 'descricao_cst_ibs_cbs'])
            ->when($term !== '', function ($qq) use ($term) {
                $qq->where('cst_ibs_cbs', 'like', "%{$term}%")
                    ->orWhere('descricao_cst_ibs_cbs', 'like', "%{$term}%");
            })
            ->orderBy('cst_ibs_cbs', 'asc');

        $total = (clone $q)->count();

        $items = $q->skip(($page - 1) * $perPage)->take($perPage)->get();

        $results = $items->map(function ($i) {
            $text = trim($i->cst_ibs_cbs . ' - ' . ($i->descricao_cst_ibs_cbs ?? ''));
            return ['id' => (string) $i->cst_ibs_cbs, 'text' => $text];
        });

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    public function ajaxClassTribIbsCbs(Request $request)
    {
        $term = trim((string) $request->get('term', ''));
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 20;

        $q = ClassTribIbsCbs::query()
            ->select(['CCLASSTRIB', 'PREDIBS', 'PREDCBS'])
            ->when($term !== '', function ($qq) use ($term) {
                $qq->where('CCLASSTRIB', 'like', "%{$term}%");
            })
            ->orderBy('CCLASSTRIB', 'asc');

        $total = (clone $q)->count();
        $items = $q->skip(($page - 1) * $perPage)->take($perPage)->get();

        $results = $items->map(function ($i) {
            $text = (string) $i->CCLASSTRIB;
            return ['id' => $text, 'text' => $text];
        });

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    public function ajaxReducaoIbsCbs(Request $request)
    {
        $cst = trim((string) $request->get('cst', ''));
        $classTrib = trim((string) $request->get('class', ''));

        // só aplica automaticamente quando CST = 200
        if ($cst !== '200' || $classTrib === '') {
            return response()->json(['predibs' => 0, 'predcbs' => 0], 200);
        }

        $vals = $this->resolveReducaoIbsCbs($classTrib);
        return response()->json($vals, 200);
    }

}
