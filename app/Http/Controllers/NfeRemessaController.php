<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RemessaNfe;
use App\Models\ItemRemessaNfe;
use App\Models\RemessaNfeFatura;
use App\Models\RemessaReferenciaNfe;
use App\Models\Certificado;
use App\Models\ConfigNota;
use App\Models\NaturezaOperacao;
use App\Models\Categoria;
use App\Models\Tributacao;
use App\Models\Cliente;
use App\Models\Venda;
use App\Models\Produto;
use App\Models\Transportadora;
use App\Models\AberturaCaixa;
use App\Models\ContaBancaria;
use App\Models\FormaPagamento;
use App\Models\Usuario;
use App\Services\NFeRemessaService;
use App\Models\ListaPreco;
use Illuminate\Support\Facades\DB;
use App\Helpers\StockMove;
use App\Models\ContaReceber;
use App\Models\CategoriaConta;

class NfeRemessaController extends Controller
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

        $permissaoAcesso = __getLocaisUsarioLogado();
        $local_padrao = __get_local_padrao();

        if($local_padrao == -1){
            $local_padrao = null;
        }

        $data = RemessaNfe::
        where('estado', 'novo')
        ->where('empresa_id', $this->empresa_id)
        ->where(function($query) use ($permissaoAcesso){
            if($permissaoAcesso != null){
                foreach ($permissaoAcesso as $value) {
                    if($value == -1){
                        $value = null;  
                    } 
                    $query->orWhere('filial_id', $value);
                }
            }
        })
        ->when($local_padrao != NULL, function ($query) use ($local_padrao) {
            $query->where('filial_id', $local_padrao);
        })
        ->orderBy('id', 'desc')
        ->paginate(30);

        $certificado = Certificado::
        where('empresa_id', $this->empresa_id)
        ->first();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $menos30 = $this->menos30Dias();
        $date = date('d/m/Y');

        return view("remessa_nfe/index")
        ->with('data', $data)
        ->with('config', $config)
        ->with('links', true)
        ->with('dataInicial', $menos30)
        ->with('dataFinal', $date)
        ->with('certificado', $certificado)
        ->with('title', "Lista de NFe");
    }


    public function filtro(Request $request){
        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $dataEmissao = $request->data_emissao;
        $cliente = $request->cliente;
        $estado = $request->estado;
        $numero_nfe = $request->numero_nfe;
        $numero_doc = $request->numero_doc;
        $filial_id = $request->filial_id;

        $vendas = null;

        $permissaoAcesso = __getLocaisUsarioLogado();

        $vendas = RemessaNfe::
        where('remessa_nves.empresa_id', $this->empresa_id)
        ->where(function($query) use ($permissaoAcesso){
            if($permissaoAcesso != null){
                foreach ($permissaoAcesso as $value) {
                    if($value == -1){
                        $value = null;  
                    } 
                    $query->orWhere('remessa_nves.filial_id', $value);
                }
            }
        })
        ->orderBy('remessa_nves.id', 'desc')
        ->select('remessa_nves.*');

        if(isset($dataInicial) && isset($dataFinal)){
            $vendas->whereBetween('remessa_nves.'.$request->tipo_pesquisa_data, [
                $this->parseDate($dataInicial), 
                $this->parseDate($dataFinal, true)
            ]);
        }

        if(($dataEmissao)){
            $vendas->whereDate('remessa_nves.data_emissao', $this->parseDate($dataEmissao));
        }

        if(isset($cliente)){
            $vendas->join('clientes', 'clientes.id' , '=', 'remessa_nves.cliente_id')
            ->where('clientes.'.$request->tipo_pesquisa, 'LIKE', "%$cliente%");
        }

        if($numero_nfe != ""){
            $vendas->where('numero_nfe', $numero_nfe);
        }
        if($numero_doc != ""){
            $vendas->where('numero_sequencial', $numero_doc);
        }

        if($filial_id){
            if($filial_id == -1){
                $vendas->where('filial_id', null);
            }else{
                $vendas->where('filial_id', $filial_id);
            }
        }

        if($estado != "todos"){
            $vendas->where('estado', $estado);
        }

        $vendas = $vendas->get();

        $certificado = Certificado::
        where('empresa_id', $this->empresa_id)
        ->first();

        return view("remessa_nfe/index")
        ->with('data', $vendas)
        ->with('cliente', $cliente)
        ->with('tipoPesquisa', $request->tipo_pesquisa)
        ->with('tipoPesquisaData', $request->tipo_pesquisa_data)
        ->with('certificado', $certificado)
        ->with('dataInicial', $dataInicial)
        ->with('dataFinal', $dataFinal)
        ->with('dataEmissao', $dataEmissao)
        ->with('numero_doc', $numero_doc)
        ->with('numero_nfe', $numero_nfe)
        ->with('filial_id', $filial_id)
        ->with('estado', $estado)

        ->with('title', "Filtro de NFe");
    }

    private function menos30Dias(){
        return date('d/m/Y', strtotime("-30 days",strtotime(str_replace("/", "-", 
            date('Y-m-d')))));
    }

    private function parseDate($date, $plusDay = false){
        if($plusDay == false)
            return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
        else
            return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
    }

    public function create(){
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();
        if($config == null){
            return redirect('configNF');
        }
        $lastNF = RemessaNfe::lastNFe();

        $naturezas = NaturezaOperacao::
        where('empresa_id', $this->empresa_id)
        ->get();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $categorias = Categoria::
        where('empresa_id', $this->empresa_id)
        ->get();

        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
        ->first();

        $clientes = Cliente::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->get();
        $tiposPagamento = Venda::tiposPagamento();

        $countProdutos = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->count();

        if(count($naturezas) == 0 || $config == null || count($categorias) == 0 || $tributacao == null || count($clientes) == 0 || $countProdutos == 0){

            $p = view("vendas/alerta")
            ->with('categorias', count($categorias))
            ->with('clientes', count($clientes))
            ->with('naturezas', $naturezas)
            ->with('produtos', $countProdutos)
            ->with('config', $config)
            ->with('tributacao', $tributacao)
            ->with('title', "Validação para Emitir");
            return $p;

        }else{

            $transportadoras = Transportadora::
            where('empresa_id', $this->empresa_id)
            ->get();

            foreach($clientes as $c){
                $c->cidade;
            }

            $abertura = $this->verificaAberturaCaixa();
            if($abertura == -1 && env("CAIXA_PARA_NFE") == 1){
                session()->flash("mensagem_erro", "Abra o caixa para vender!");
                return redirect('/caixa');
            }

            $contaPadrao = ContaBancaria::
            where('empresa_id', $this->empresa_id)
            ->where('padrao', true)
            ->first();

            $unidadesDeMedida = Produto::unidadesMedida();

            $tributacao = Tributacao::
            where('empresa_id', $this->empresa_id)
            ->first();
            $anps = Produto::lista_ANP();

            if($tributacao->regime == 1){
                $listaCSTCSOSN = Produto::listaCST();
            }else{
                $listaCSTCSOSN = Produto::listaCSOSN();
            }
            $listaCST_PIS_COFINS = Produto::listaCST_PIS_COFINS();
            $listaCST_IPI = Produto::listaCST_IPI();

            $natureza = Produto::
            firstNatureza($this->empresa_id);

            $formasPagamento = FormaPagamento::
            where('empresa_id', $this->empresa_id)
            ->where('status', true)
            ->get();

            $usuario = Usuario::find(get_id_user());

            $usuarios = Usuario::where('empresa_id', $this->empresa_id)
            ->where('ativo', 1)
            ->orderBy('nome', 'asc')
            ->get();

            $vendedores = [];
            foreach($usuarios as $u){
                if($u->funcionario){
                    array_push($vendedores, $u);
                }
            }

            $p = view("remessa_nfe/register")
            ->with('naturezas', $naturezas)
            ->with('formasPagamento', $formasPagamento)
            ->with('config', $config)
            ->with('usuario', $usuario)
            ->with('vendedores', $vendedores)
            ->with('listaCSTCSOSN', $listaCSTCSOSN)
            ->with('listaCST_PIS_COFINS', $listaCST_PIS_COFINS)
            ->with('listaCST_IPI', $listaCST_IPI)
            ->with('natureza', $natureza)
            ->with('contaPadrao', $contaPadrao)
            ->with('clientes', $clientes)
            ->with('categorias', $categorias)
            ->with('anps', $anps)
            ->with('unidadesDeMedida', $unidadesDeMedida)
            ->with('tributacao', $tributacao)
            ->with('transportadoras', $transportadoras)
            ->with('tiposPagamento', $tiposPagamento)
            ->with('lastNF', $lastNF)
            ->with('listaPreco', ListaPreco::where('empresa_id', $this->empresa_id)->get())
            ->with('title', "Nova NFe");

            return $p;
        }
    }

    public function clone($id){

        $item = RemessaNfe::findOrFail($id);

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();
        if($config == null){
            return redirect('configNF');
        }
        $lastNF = RemessaNfe::lastNFe();

        $naturezas = NaturezaOperacao::
        where('empresa_id', $this->empresa_id)
        ->get();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $categorias = Categoria::
        where('empresa_id', $this->empresa_id)
        ->get();

        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
        ->first();

        $clientes = Cliente::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->get();
        
        $tiposPagamento = Venda::tiposPagamento();

        $countProdutos = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->count();

        
        $transportadoras = Transportadora::
        where('empresa_id', $this->empresa_id)
        ->get();

        foreach($clientes as $c){
            $c->cidade;
        }

        $abertura = $this->verificaAberturaCaixa();
        if($abertura == -1 && env("CAIXA_PARA_NFE") == 1){
            session()->flash("mensagem_erro", "Abra o caixa para vender!");
            return redirect('/caixa');
        }

        $contaPadrao = ContaBancaria::
        where('empresa_id', $this->empresa_id)
        ->where('padrao', true)
        ->first();

        $unidadesDeMedida = Produto::unidadesMedida();

        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
        ->first();
        $anps = Produto::lista_ANP();

        if($tributacao->regime == 1){
            $listaCSTCSOSN = Produto::listaCST();
        }else{
            $listaCSTCSOSN = Produto::listaCSOSN();
        }
        $listaCST_PIS_COFINS = Produto::listaCST_PIS_COFINS();
        $listaCST_IPI = Produto::listaCST_IPI();

        $natureza = Produto::
        firstNatureza($this->empresa_id);

        $formasPagamento = FormaPagamento::
        where('empresa_id', $this->empresa_id)
        ->where('status', true)
        ->get();

        $usuario = Usuario::find(get_id_user());

        $usuarios = Usuario::where('empresa_id', $this->empresa_id)
        ->where('ativo', 1)
        ->orderBy('nome', 'asc')
        ->get();

        $vendedores = [];
        foreach($usuarios as $u){
            if($u->funcionario){
                array_push($vendedores, $u);
            }
        }

        return view("remessa_nfe/register")
        ->with('naturezas', $naturezas)
        ->with('formasPagamento', $formasPagamento)
        ->with('config', $config)
        ->with('usuario', $usuario)
        ->with('vendedores', $vendedores)
        ->with('listaCSTCSOSN', $listaCSTCSOSN)
        ->with('listaCST_PIS_COFINS', $listaCST_PIS_COFINS)
        ->with('listaCST_IPI', $listaCST_IPI)
        ->with('natureza', $natureza)
        ->with('contaPadrao', $contaPadrao)
        ->with('clientes', $clientes)
        ->with('categorias', $categorias)
        ->with('anps', $anps)
        ->with('item', $item)
        ->with('unidadesDeMedida', $unidadesDeMedida)
        ->with('tributacao', $tributacao)
        ->with('transportadoras', $transportadoras)
        ->with('tiposPagamento', $tiposPagamento)
        ->with('lastNF', $lastNF)
        ->with('clone', 1)
        ->with('listaPreco', ListaPreco::where('empresa_id', $this->empresa_id)->get())
        ->with('title', "Clonar NFe");
        
    }

    public function edit($id){

        $item = RemessaNfe::findOrFail($id);

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();
        if($config == null){
            return redirect('configNF');
        }
        $lastNF = RemessaNfe::lastNFe();

        $naturezas = NaturezaOperacao::
        where('empresa_id', $this->empresa_id)
        ->get();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $categorias = Categoria::
        where('empresa_id', $this->empresa_id)
        ->get();

        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
        ->first();

        $clientes = Cliente::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->get();
        
        $tiposPagamento = Venda::tiposPagamento();

        $countProdutos = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->count();

        
        $transportadoras = Transportadora::
        where('empresa_id', $this->empresa_id)
        ->get();

        foreach($clientes as $c){
            $c->cidade;
        }

        $abertura = $this->verificaAberturaCaixa();
        if($abertura == -1 && env("CAIXA_PARA_NFE") == 1){
            session()->flash("mensagem_erro", "Abra o caixa para vender!");
            return redirect('/caixa');
        }

        $contaPadrao = ContaBancaria::
        where('empresa_id', $this->empresa_id)
        ->where('padrao', true)
        ->first();

        $unidadesDeMedida = Produto::unidadesMedida();

        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
        ->first();
        $anps = Produto::lista_ANP();

        if($tributacao->regime == 1){
            $listaCSTCSOSN = Produto::listaCST();
        }else{
            $listaCSTCSOSN = Produto::listaCSOSN();
        }
        $listaCST_PIS_COFINS = Produto::listaCST_PIS_COFINS();
        $listaCST_IPI = Produto::listaCST_IPI();

        $natureza = Produto::
        firstNatureza($this->empresa_id);

        $formasPagamento = FormaPagamento::
        where('empresa_id', $this->empresa_id)
        ->where('status', true)
        ->get();

        $usuario = Usuario::find(get_id_user());

        $usuarios = Usuario::where('empresa_id', $this->empresa_id)
        ->where('ativo', 1)
        ->orderBy('nome', 'asc')
        ->get();

        $vendedores = [];
        foreach($usuarios as $u){
            if($u->funcionario){
                array_push($vendedores, $u);
            }
        }

        return view("remessa_nfe/register")
        ->with('naturezas', $naturezas)
        ->with('formasPagamento', $formasPagamento)
        ->with('config', $config)
        ->with('usuario', $usuario)
        ->with('vendedores', $vendedores)
        ->with('listaCSTCSOSN', $listaCSTCSOSN)
        ->with('listaCST_PIS_COFINS', $listaCST_PIS_COFINS)
        ->with('listaCST_IPI', $listaCST_IPI)
        ->with('natureza', $natureza)
        ->with('contaPadrao', $contaPadrao)
        ->with('clientes', $clientes)
        ->with('categorias', $categorias)
        ->with('anps', $anps)
        ->with('item', $item)
        ->with('unidadesDeMedida', $unidadesDeMedida)
        ->with('tributacao', $tributacao)
        ->with('transportadoras', $transportadoras)
        ->with('tiposPagamento', $tiposPagamento)
        ->with('lastNF', $lastNF)
        ->with('listaPreco', ListaPreco::where('empresa_id', $this->empresa_id)->get())
        ->with('title', "Editar NFe");
        
    }

    private function verificaAberturaCaixa(){

        $ab = AberturaCaixa::where('ultima_venda_nfce', 0)
        ->where('empresa_id', $this->empresa_id)
        ->where('status', 0)
        ->orderBy('id', 'desc')->first();

        $ab2 = AberturaCaixa::where('ultima_venda_nfe', 0)
        ->where('empresa_id', $this->empresa_id)
        ->where('status', 0)
        ->orderBy('id', 'desc')->first();

        if($ab != null && $ab2 == null){
            return $ab->valor;
        }else if($ab == null && $ab2 != null){
            $ab2->valor;
        }else if($ab != null && $ab2 != null){
            if(strtotime($ab->created_at) > strtotime($ab2->created_at)){
                $ab->valor;
            }else{
                $ab2->valor;
            }
        }else{
            return -1;
        }

        if($ab != null) return $ab->valor;
        else return -1;
    }



    public function store(Request $request){

        $numero_sequencial = 0;
        $last = RemessaNfe::where('empresa_id', $this->empresa_id)
        ->orderBy('id', 'desc')
        ->first();

        $numero_sequencial = $last != null ? ($last->numero_sequencial + 1) : 1;

        try{
            $result = DB::transaction(function () use ($request, $numero_sequencial) {
                $dataNFe = [
                    'cliente_id' => $request->cliente_id,
                    'empresa_id' => $request->empresa_id,
                    'usuario_id' => get_id_user(),
                    'valor_total' => __replace($request->valor_total),
                    'forma_pagamento_parcela' => $request->valor_total[0],
                    'numero_nfe' => 0,
                    'natureza_id' => $request->natureza,
                    'chave' => '',
                    'estado' => 'novo',
                    'observacao' => $request->obs ?? '',
                    'desconto' => $request->desconto ? __replace($request->desconto) : 0,
                    'transportadora_id' => $request->transportadora != 'null' ? $request->transportadora : null,
                    'sequencia_cce' => 0,
                    'acrescimo' => $request->acrescimo ? __replace($request->acrescimo) : 0,
                    'data_entrega' => $request->data_entrega,
                    'nSerie' => 0,
                    'numero_sequencial' => $numero_sequencial,
                    'filial_id' => $request->filial_id != -1 ? $request->filial_id : null,
                    'baixa_estoque' => $request->baixa_estoque,
                    'gerar_conta_receber' => $request->gerar_conta_receber,
                    'tipo' => $request->tipo,
                    'placa' => $request->placa,
                    'uf' => $request->uf,
                    'valor_frete' => $request->valor_frete ? __replace($request->valor_frete) : 0,
                    'tipo_frete' => $request->tipo_frete,
                    'qtd_volumes' => $request->qtd_volumes ? __replace($request->qtd_volumes) : 0,
                    'numeracao_volumes' => $request->numeracao_volumes ? __replace($request->numeracao_volumes) : 0,
                    'especie' => $request->especie,
                    'peso_liquido' => $request->peso_liquido ? __replace($request->peso_liquido) : 0,
                    'peso_bruto' => $request->peso_bruto ? __replace($request->peso_bruto) : 0,
                    'data_retroativa' => $request->data_retroativa,
                    'data_saida' => $request->data_saida,
                    'venda_caixa_id' => isset($request->venda_caixa_id) ? $request->venda_caixa_id : null
                ];

                $remessa = RemessaNfe::create($dataNFe);
                $natureza = NaturezaOperacao::findOrFail($request->natureza);
                $stockMove = new StockMove();

                for($i=0; $i<sizeof($request->quantidade); $i++){

                    $dataItem = [
                        'remessa_id' => $remessa->id,
                        'produto_id' => $request->produto_id[$i],
                        'quantidade' => __replace($request->quantidade[$i]),
                        'valor_unitario' => __replace($request->valor_unit[$i]),
                        'sub_total' => __replace($request->sub_total[$i]),
                        'cst_csosn' => $request->cst_csosn[$i],
                        'cst_pis' => $request->cst_pis[$i],
                        'cfop' => $request->cfop[$i],
                        'cst_cofins' => $request->cst_cofins[$i],
                        'cst_ipi' => $request->cst_ipi[$i],
                        'perc_icms' => $request->perc_icms[$i] ? __replace($request->perc_icms[$i]) : 0,
                        'perc_pis' => $request->perc_pis[$i] ? __replace($request->perc_pis[$i]) : 0,
                        'perc_cofins' => $request->perc_cofins[$i] ? __replace($request->perc_cofins[$i]) : 0,
                        'perc_ipi' => $request->perc_ipi[$i] ? __replace($request->perc_ipi[$i]) : 0,
                        'pRedBC' => $request->pRedBC[$i] ? __replace($request->pRedBC[$i]) : 0,
                        'vbc_icms' => $request->vbc_icms[$i] ? __replace($request->vbc_icms[$i]) : 0,
                        'vbc_pis' => $request->vbc_pis[$i] ? __replace($request->vbc_pis[$i]) : 0,
                        'vbc_cofins' => $request->vbc_cofins[$i] ? __replace($request->vbc_cofins[$i]) : 0,
                        'vbc_ipi' => $request->vbc_ipi[$i] ? __replace($request->vbc_ipi[$i]) : 0,
                        'vBCSTRet' => $request->vBCSTRet[$i] ? __replace($request->vBCSTRet[$i]) : 0,
                        'vFrete' => $request->vFrete[$i] ? __replace($request->vFrete[$i]) : 0,
                        'modBCST' => $request->modBCST[$i] ? __replace($request->modBCST[$i]) : 0,
                        'vBCST' => $request->vBCST[$i] ? __replace($request->vBCST[$i]) : 0,
                        'pICMSST' => $request->pICMSST[$i] ? __replace($request->pICMSST[$i]) : 0,
                        'vICMSST' => $request->vICMSST[$i] ? __replace($request->vICMSST[$i]) : 0,
                        'pMVAST' => $request->pMVAST[$i] ? __replace($request->pMVAST[$i]) : 0,
                        'x_pedido' => $request->x_pedido[$i] ?? '',
                        'num_item_pedido' => $request->num_item_pedido[$i] ?? '',
                        'cest' => $request->cest[$i] ?? '',
                        'valor_icms' => $request->valor_icms[$i] ? __replace($request->valor_icms[$i]) : 0,
                        'valor_pis' => $request->valor_pis[$i] ? __replace($request->valor_pis[$i]) : 0,
                        'valor_cofins' => $request->valor_cofins[$i] ? __replace($request->valor_cofins[$i]) : 0,
                        'valor_ipi' => $request->valor_ipi[$i] ? __replace($request->valor_ipi[$i]) : 0,
                        'produto_nome' => isset($request->descricao_item[$i]) ? $request->descricao_item[$i] : null,
                    ];
                    $produto = Produto::findOrFail($request->produto_id[$i]);

                    if($request->cfop[$i] != '5929' && $request->cfop[$i] != '6929'){
                        if($produto->gerenciar_estoque && $request->baixa_estoque && $natureza->nao_movimenta_estoque == 0){
                            $stockMove->downStock($produto->id, __replace($request->quantidade[$i]), $request->filial_id != -1 ? $request->filial_id : null);
                        }
                    }
                    ItemRemessaNfe::create($dataItem);
                }

                if($request->chave_referencia){
                    for($i=0; $i<sizeof($request->chave_referencia); $i++){
                        if($request->chave_referencia[$i]){
                            RemessaReferenciaNfe::create([
                                'remessa_id' => $remessa->id,
                                'chave' => $request->chave_referencia[$i]
                            ]);
                        }
                    }
                }

                for($i=0; $i<sizeof($request->valor_parcela); $i++){
                    if(__replace($request->valor_parcela[$i]) > 0){
                        $vencimento = $request->vencimento_parcela[$i];
                        if(!$vencimento){
                            $vencimento = date('Y-m-d');
                        }
                        $fat = RemessaNfeFatura::create([
                            'remessa_id' => $remessa->id,
                            'tipo_pagamento' => $request->forma_pagamento_parcela[$i],
                            'valor' => __replace($request->valor_parcela[$i]),
                            'data_vencimento' => $vencimento
                        ]);


                        if($request->gerar_conta_receber){
                            $catVenda = $this->categoriaVenda();
                            ContaReceber::create([
                                'venda_id' => null,
                                'data_vencimento' => $vencimento,
                                'data_recebimento' => $vencimento,
                                'valor_integral' => __replace($request->valor_parcela[$i]),
                                'cliente_id' => $request->cliente_id,
                                'valor_recebido' => 0,
                                'status' => false,
                                'tipo_pagamento' => $fat->getTipo(),
                                'referencia' => "Parcela $i+1, da NFe $remessa->id",
                                'categoria_id' => $catVenda,
                                'empresa_id' => $this->empresa_id
                            ]);
                        }
                    }
                }

            });

session()->flash("mensagem_sucesso", "NFe criada!");
}catch(\Exception $e){
    dd($e->getMessage());
    __saveError($e, $this->empresa_id);
    session()->flash("mensagem_erro", "Algo deu errado");
}

return redirect('/nferemessa');

}

private function categoriaVenda(){
    $cat = CategoriaConta::
    where('empresa_id', $this->empresa_id)
    ->where('nome', 'Vendas')
    ->first();
    if($cat != null) return $cat->id;
    $cat = CategoriaConta::create([
        'nome' => 'Vendas',
        'empresa_id' => $this->empresa_id,
        'tipo'=> 'receber'
    ]);
    return $cat->id;
}

public function update(Request $request, $id){

    $numero_sequencial = 0;
    $last = RemessaNfe::where('empresa_id', $this->empresa_id)
    ->orderBy('id', 'desc')
    ->first();

    $numero_sequencial = $last != null ? ($last->numero_sequencial + 1) : 1;

    try{
        $result = DB::transaction(function () use ($request, $id) {
            $item = RemessaNfe::findOrFail($id);
            $dataNFe = [
                'cliente_id' => $request->cliente_id,
                'empresa_id' => $request->empresa_id,
                'usuario_id' => get_id_user(),
                'valor_total' => __replace($request->valor_total),
                'forma_pagamento_parcela' => $request->valor_total[0],
                'natureza_id' => $request->natureza,
                'observacao' => $request->obs ?? '',
                'desconto' => $request->desconto ? __replace($request->desconto) : 0,
                'transportadora_id' => $request->transportadora != 'null' ? $request->transportadora : null,
                'acrescimo' => $request->acrescimo ? __replace($request->acrescimo) : 0,
                'data_entrega' => $request->data_entrega,
                'filial_id' => $request->filial_id != -1 ? $request->filial_id : null,
                'baixa_estoque' => $request->baixa_estoque,
                'gerar_conta_receber' => $request->gerar_conta_receber,
                'tipo' => $request->tipo,
                'placa' => $request->placa,
                'uf' => $request->uf,
                'valor_frete' => $request->valor_frete ? __replace($request->valor_frete) : 0,
                'tipo_frete' => $request->tipo_frete,
                'qtd_volumes' => $request->qtd_volumes ? __replace($request->qtd_volumes) : 0,
                'numeracao_volumes' => $request->numeracao_volumes ? __replace($request->numeracao_volumes) : 0,
                'especie' => $request->especie,
                'peso_liquido' => $request->peso_liquido ? __replace($request->peso_liquido) : 0,
                'peso_bruto' => $request->peso_bruto ? __replace($request->peso_bruto) : 0,
                'data_retroativa' => $request->data_retroativa,
                'data_saida' => $request->data_saida,
            ];
            $stockMove = new StockMove();

            $item->update($dataNFe);

            $item->itens()->delete();
            $item->fatura()->delete();
            $item->referencias()->delete();
            if($request->baixa_estoque == 1){
                $this->reverteEstoque($item->itens);
            }

            for($i=0; $i<sizeof($request->quantidade); $i++){

                $dataItem = [
                    'remessa_id' => $item->id,
                    'produto_id' => $request->produto_id[$i],
                    'quantidade' => __replace($request->quantidade[$i]),
                    'valor_unitario' => __replace($request->valor_unit[$i]),
                    'sub_total' => __replace($request->sub_total[$i]),
                    'cst_csosn' => $request->cst_csosn[$i],
                    'cst_pis' => $request->cst_pis[$i],
                    'cst_cofins' => $request->cst_cofins[$i],
                    'cst_ipi' => $request->cst_ipi[$i],
                    'cfop' => $request->cfop[$i],
                    'perc_icms' => $request->perc_icms[$i] ? __replace($request->perc_icms[$i]) : 0,
                    'perc_pis' => $request->perc_pis[$i] ? __replace($request->perc_pis[$i]) : 0,
                    'perc_cofins' => $request->perc_cofins[$i] ? __replace($request->perc_cofins[$i]) : 0,
                    'perc_ipi' => $request->perc_ipi[$i] ? __replace($request->perc_ipi[$i]) : 0,
                    'pRedBC' => $request->pRedBC[$i] ? __replace($request->pRedBC[$i]) : 0,
                    'vbc_icms' => $request->vbc_icms[$i] ? __replace($request->vbc_icms[$i]) : 0,
                    'vbc_pis' => $request->vbc_pis[$i] ? __replace($request->vbc_pis[$i]) : 0,
                    'vbc_cofins' => $request->vbc_cofins[$i] ? __replace($request->vbc_cofins[$i]) : 0,
                    'vbc_ipi' => $request->vbc_ipi[$i] ? __replace($request->vbc_ipi[$i]) : 0,
                    'vBCSTRet' => $request->vBCSTRet[$i] ? __replace($request->vBCSTRet[$i]) : 0,
                    'vFrete' => $request->vFrete[$i] ? __replace($request->vFrete[$i]) : 0,
                    'modBCST' => $request->modBCST[$i] ? __replace($request->modBCST[$i]) : 0,
                    'vBCST' => $request->vBCST[$i] ? __replace($request->vBCST[$i]) : 0,
                    'pICMSST' => $request->pICMSST[$i] ? __replace($request->pICMSST[$i]) : 0,
                    'vICMSST' => $request->vICMSST[$i] ? __replace($request->vICMSST[$i]) : 0,
                    'pMVAST' => $request->pMVAST[$i] ? __replace($request->pMVAST[$i]) : 0,
                    'x_pedido' => $request->x_pedido[$i] ?? '',
                    'num_item_pedido' => $request->num_item_pedido[$i] ?? '',
                    'cest' => $request->cest[$i] ?? '',
                    'valor_icms' => $request->valor_icms[$i] ? __replace($request->valor_icms[$i]) : 0,
                    'valor_pis' => $request->valor_pis[$i] ? __replace($request->valor_pis[$i]) : 0,
                    'valor_cofins' => $request->valor_cofins[$i] ? __replace($request->valor_cofins[$i]) : 0,
                    'valor_ipi' => $request->valor_ipi[$i] ? __replace($request->valor_ipi[$i]) : 0,
                    'produto_nome' => isset($request->descricao_item[$i]) ? $request->descricao_item[$i] : null,
                ];
                $produto = Produto::findOrFail($request->produto_id[$i]);
                $natureza = NaturezaOperacao::findOrFail($request->natureza);

                if($produto->gerenciar_estoque && $request->baixa_estoque && $natureza->nao_movimenta_estoque == 0){
                    $stockMove->downStock($produto->id, __replace($request->quantidade[$i]), $request->filial_id != -1 ? $request->filial_id : null);
                }
                ItemRemessaNfe::create($dataItem);
            }

            if($request->chave_referencia){
                for($i=0; $i<sizeof($request->chave_referencia); $i++){
                    if($request->chave_referencia[$i]){
                        RemessaReferenciaNfe::create([
                            'remessa_id' => $item->id,
                            'chave' => $request->chave_referencia[$i]
                        ]);
                    }
                }
            }

            for($i=0; $i<sizeof($request->valor_parcela); $i++){
                if(__replace($request->valor_parcela[$i]) > 0){
                    RemessaNfeFatura::create([
                        'remessa_id' => $item->id,
                        'tipo_pagamento' => $request->forma_pagamento_parcela[$i],
                        'valor' => __replace($request->valor_parcela[$i]),
                        'data_vencimento' => $request->vencimento_parcela[$i]
                    ]);
                }
            }

        });

session()->flash("mensagem_sucesso", "NFe atualizada!");
}catch(\Exception $e){
    __saveError($e, $this->empresa_id);
    dd($e->getMessage());
    session()->flash("mensagem_erro", "Algo deu errado");
}

return redirect('/nferemessa');

}

public function delete($id){
    $item = RemessaNfe::
    where('id', $id)
    ->first();

    if(valida_objeto($item)){
        if($item->baixa_estoque){
            $this->reverteEstoque($item->itens);
        }
        // $this->removerDuplicadas($venda);
        $item->fatura()->delete();
        $item->itens()->delete();
        $item->referencias()->delete();
        $item->delete();
        session()->flash("mensagem_sucesso", "NFe removida!");

        return redirect('/nferemessa');
    }else{
        return redirect('/403');
    }
}

private function reverteEstoque($itens){
    $stockMove = new StockMove();
    foreach($itens as $i){
        if(!empty($i->produto->receita)){
            $receita = $i->produto->receita; 
            foreach($receita->itens as $rec){

                if(!empty($rec->produto->receita)){
                    $receita2 = $rec->produto->receita; 
                    foreach($receita2->itens as $rec2){
                        $stockMove->pluStock(
                            $rec2->produto_id, 
                            (float) str_replace(",", ".", $i->quantidade) * 
                            ($rec2->quantidade/$receita2->rendimento),
                            -1,
                            $itens[0]->venda->filial_id ?? null
                        );
                    }
                }else{

                    $stockMove->pluStock(
                        $rec->produto_id, 
                        (float) str_replace(",", ".", $i->quantidade) * 
                        ($rec->quantidade/$receita->rendimento),
                        -1,
                        $itens[0]->venda->filial_id ?? null
                    );
                }
            }
        }else{
            $stockMove->pluStock(
                $i->produto_id, (float) str_replace(",", ".", $i->quantidade),
                -1,$itens[0]->venda->filial_id ?? null);
        }
    }
}

public function editXml($id){
    $item = RemessaNfe::findOrFail($id);

    $config = ConfigNota::
    where('empresa_id', $this->empresa_id)
    ->first();

    $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);
    $isFilial = $item->filial_id;
    if($item->filial_id == null){
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();
    }else{
        $config = Filial::findOrFail($item->filial_id);
        if($config->arquivo_certificado == null){
            echo "Necessário o certificado para realizar esta ação!";
            die;
        }
    }
    $nfe_service = new NFeRemessaService([
        "atualizacao" => date('Y-m-d h:i:s'),
        "tpAmb" => (int)$config->ambiente,
        "razaosocial" => $config->razao_social,
        "siglaUF" => $config->UF,
        "cnpj" => $cnpj,
        "schemes" => "PL_009_V4",
        "versao" => "4.00",
        "tokenIBPT" => "",
        "CSC" => $config->csc,
        "CSCid" => $config->csc_id,
        "is_filial" => $isFilial
    ]);
    
    $nfe = $nfe_service->gerarNFe($item);

    if(!isset($nfe['erros_xml'])){
        $xml = $nfe['xml'];

        return view('remessa_nfe.edit_xml', compact('item', 'xml'))
        ->with('title', 'Editando XML');
    }else{
        print_r($nfe['erros_xml']);
    }

}


}
