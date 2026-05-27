<?php

namespace App\Http\Controllers;

use App\Services\LogService;
use Illuminate\Http\Request;
use App\Models\VendaCaixa;
use App\Models\Venda;
use App\Helpers\StockMove;
use App\Models\ConfigNota;
use App\Models\NaturezaOperacao;
use App\Models\Categoria;
use App\Models\Produto;
use App\Models\Cliente;
use App\Models\ComissaoVenda;
use App\Models\Tributacao;
use App\Models\Funcionario;
use App\Models\TrocaVendaCaixa;
use App\Models\Usuario;
use App\Models\Certificado;
use App\Models\ListaPreco;
use App\Models\AberturaCaixa;
use App\Models\ProdutoPizza;
use App\Models\CreditoVenda;
use App\Models\ConfigCaixa;
use App\Models\ContaReceber;
use App\Models\ItemVendaCaixa;
use App\Models\Cidade;
use App\Models\Pais;
use App\Models\ContaEmpresa;
use App\Models\GrupoCliente;
use App\Models\Acessor;
use App\Models\VendaCaixaPreVenda;
use Piggly\Pix\StaticPayload;
use Piggly\Pix\Parser;
use App\Models\Contigencia;

class FrontBoxController extends Controller
{
    protected $empresa_id = null;
    protected $filial_id = null;
    protected $usuario_id = null;

    public function __construct(){

        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;

            $this->usuario_id = session('user_logged')['id'] ?? null;
            if (!$this->usuario_id) {
                return redirect('/login');
            }

            $this->filial_id = $request->get('filial_id')
                ?? session('user_logged.local_padrao')
                ?? null;

            $this->logService = new LogService($this->empresa_id, $this->usuario_id, $this->filial_id);

            return $next($request);
        });
    }

    private function getRascunhos(){
        return VendaCaixa::
        where('rascunho', 1)
        ->where('empresa_id', $this->empresa_id)
        ->limit(20)
        ->orderBy('id', 'desc')
        ->get();
    }

    private function getConsignadas(){
        return VendaCaixa::
        where('consignado', 1)
        ->where('empresa_id', $this->empresa_id)
        ->limit(50)
        ->orderBy('id', 'desc')
        ->get();
    }

    public function edit(Request $request, $pv = false){
        $id = $request->id;
        $pv = $request->pv;
        if($pv){
            $venda = VendaCaixaPreVenda::find($id);
            $venda->isPrevenda = true;
        }else{
            $venda = VendaCaixa::find($id);
        }

        foreach($venda->itens as $i){
            $i->produto;
        }


        if(!valida_objeto($venda)){
            return redirect('/403');
        }
        // if($venda->rascunho || $venda->prevenda_nivel === 2){
        // }else{
        //     session()->flash('mensagem_erro', 'Esta venda não é um rascunho ou uma pré-venda');
        //     return redirect('/frenteCaixa/list');
        // }
        if($venda->estado === 'APROVADO'){
            session()->flash('mensagem_erro', 'Impossível editar uma venda APROVADA');
            return redirect('/frenteCaixa/list');
        }

        $countProdutos = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->count();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($config->nat_op_padrao == 0){

            session()->flash('mensagem_erro', 'Informe a natureza de operação para o PDV!');
            return redirect('/configNF');
        }

        $view = $this->pdvAssincrono($venda);
        return $view;

        if($countProdutos > env("ASSINCRONO_PRODUTOS")){
        // if($countProdutos > 10){

            $view = $this->pdvAssincrono($venda);
            return $view;
        }else{

            $naturezas = NaturezaOperacao::
            where('empresa_id', $this->empresa_id)
            ->get();

            $categorias = Categoria::
            where('empresa_id', $this->empresa_id)
            ->get();

            $produtos = Produto::
            where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->get();

            $produtosGroup = Produto::
            where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->where('valor_venda', '>', 0)
            ->groupBy('referencia_grade')
            ->get();

            $tributacao = Tributacao::
            where('empresa_id', $this->empresa_id)
            ->get();

            $tiposPagamento = VendaCaixa::tiposPagamento();
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();

            $certificado = Certificado::
            where('empresa_id', $this->empresa_id)
            ->first();

            $usuario = Usuario::find(get_id_user());

            if(count($naturezas) == 0 || count($produtos) == 0 || $config == null || count($categorias) == 0 || $tributacao == null){

                return view("frontBox/alerta")
                ->with('produtos', count($produtos))
                ->with('categorias', count($categorias))
                ->with('naturezas', $naturezas)
                ->with('config', $config)
                ->with('tributacao', $tributacao)
                ->with('title', "Validação para Emitir");
            }else{

                if($config->nat_op_padrao == 0){

                    session()->flash('mensagem_erro', 'Informe a natureza de operação para o PDV!');
                    return redirect('/configNF');
                }else{

                    $tiposPagamentoMulti = VendaCaixa::tiposPagamentoMulti();

                    $produtos = Produto::
                    where('empresa_id', $this->empresa_id)
                    ->where('inativo', false)
                    ->where('valor_venda', '>', 0)
                    ->orderBy('nome')
                    ->get();

                    foreach($produtos as $p){
                        $p->listaPreco;
                        $estoque_atual = 0;
                        if($p->estoque){
                            if($p->unidade_venda == 'UN' || $p->unidade_venda == 'UNID'){
                                $estoque_atual = number_format($p->estoque->quantidade);
                            }else{
                                $estoque_atual = $p->estoque->quantidade;
                            }
                        }
                        $p->estoque_atual = $estoque_atual;
                        if($p->grade){
                            $p->nome .= " $p->str_grade";
                        }
                    }

                    foreach($produtosGroup as $p){
                        $p->listaPreco;
                        $estoque_atual = 0;
                        if($p->estoque){
                            if($p->unidade_venda == 'UN' || $p->unidade_venda == 'UNID'){
                                $estoque_atual = number_format($p->estoque->quantidade);
                            }else{
                                $estoque_atual = $p->estoque->quantidade;
                            }
                        }
                        $p->estoque_atual = $estoque_atual;

                    }

                    $categorias = Categoria::
                    where('empresa_id', $this->empresa_id)
                    ->orderBy('nome')->get();

                    $clientes = Cliente::orderBy('razao_social')
                    ->where('empresa_id', $this->empresa_id)
                    ->where('inativo', false)
                    ->get();

                    foreach($clientes as $c){
                        $c->totalEmAberto = 0;
                        $soma = $this->getTotalContaCredito($c);
                        if($soma != null){
                            $c->totalEmAberto = $soma->total;
                        }
                    }

                    $atalhos = ConfigCaixa::
                    where('usuario_id', get_id_user())
                    ->first();

                    // $view = 'main';
                    // if($atalhos != null && $atalhos->modelo_pdv == 1){
                    //     $view = 'main2';
                    // }
                    // if($atalhos != null && $atalhos->modelo_pdv == 2){
                    //     $view = 'main3';
                    // }
                    $view = 'main3';
                    $listas = ListaPreco::where('empresa_id', $this->empresa_id)->get();

                    $venda->cliente;
                    foreach($venda->itens as $it){
                        $it->produto;
                    }

                    $rascunhos = $this->getRascunhos();

                    // Dados para o modal -> adicionar novo cliente
                    $estados = Cliente::estados();
                    $cidades = Cidade::all();
                    $pais = Pais::all();
                    $grupos = GrupoCliente::get();
                    $acessores = Acessor::where('empresa_id', $this->empresa_id)->get();
                    $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->get();
                    //

                    $consignadas = $this->getConsignadas();

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

                    $estados = Cliente::estados();
                    $cidades = Cidade::all();
                    $pais = Pais::all();
                    $grupos = GrupoCliente::get();

                    return view('frontBox/'.$view)
                    ->with('frenteCaixa', true)
                    ->with('tiposPagamento', $tiposPagamento)
                    ->with('config', $config)
                    ->with('rascunhos', $rascunhos)
                    ->with('certificado', $certificado)
                    ->with('listaPreco', $listas)
                    ->with('atalhos', $atalhos)
                    ->with('venda', $venda)
                    ->with('venda', $venda)
                    ->with('vendedores', $vendedores)
                    ->with('disableFooter', true)
                    ->with('usuario', $usuario)
                    ->with('produtos', $produtos)
                    ->with('produtosGroup', $produtosGroup)
                    ->with('clientes', $clientes)
                    ->with('categorias', $categorias)
                    ->with('tiposPagamentoMulti', $tiposPagamentoMulti)
                    ->with('consignadas', $consignadas)
                    ->with('pessoaFisicaOuJuridica', true)
                    ->with('cidadeJs', true)
                    ->with('cidades', $cidades)
                    ->with('estados', $estados)
                    ->with('acessores', $acessores)
                    ->with('funcionarios', $funcionarios)
                    ->with('grupos', $grupos)
                    ->with('pais', $pais)
                    ->with('title', 'Frente de Caixa');
                }
            }
        }
    }

    public function numeroSequencial(){
        $verify = VendaCaixa::where('empresa_id', $this->empresa_id)
        ->where('numero_sequencial', 0)
        ->first();

        if($verify){
            $vendas = VendaCaixa::where('empresa_id', $this->empresa_id)
            ->get();

            $n = 1;
            foreach($vendas as $v){
                $v->numero_sequencial = $n;
                $n++;
                $v->save();
            }
        }
    }

    public function index(){
        $this->numeroSequencial();
        $countProdutos = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->count();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($config == null){
            session()->flash('mensagem_erro', 'Informe a configuração!');
            return redirect('/configNF');
        }

        if($config->nat_op_padrao == 0){

            session()->flash('mensagem_erro', 'Informe a natureza de operação para o PDV!');
            return redirect('/configNF');
        }

        $view = $this->pdvAssincrono();
        return $view;
        if($countProdutos > env("ASSINCRONO_PRODUTOS")){
            $view = $this->pdvAssincrono();
            return $view;
        }else{

            $naturezas = NaturezaOperacao::
            where('empresa_id', $this->empresa_id)
            ->get();

            $categorias = Categoria::
            where('empresa_id', $this->empresa_id)
            ->get();

            $produtos = Produto::
            where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->get();

            $produtosGroup = Produto::
            where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->where('valor_venda', '>', 0)
            ->groupBy('referencia_grade')
            ->get();

            $tributacao = Tributacao::
            where('empresa_id', $this->empresa_id)
            ->get();

            $tiposPagamento = VendaCaixa::tiposPagamento();
            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
            ->first();

            $certificado = Certificado::
            where('empresa_id', $this->empresa_id)
            ->first();

            $usuario = Usuario::find(get_id_user());

            if(count($naturezas) == 0 || count($produtos) == 0 || $config == null || count($categorias) == 0 || $tributacao == null){

                return view("frontBox/alerta")
                ->with('produtos', count($produtos))
                ->with('categorias', count($categorias))
                ->with('naturezas', $naturezas)
                ->with('config', $config)
                ->with('tributacao', $tributacao)
                ->with('title', "Validação para Emitir");
            }else{

                if($config->nat_op_padrao == 0){

                    session()->flash('mensagem_erro', 'Informe a natureza de operação para o PDV!');
                    return redirect('/configNF');
                }else{

                    $tiposPagamentoMulti = VendaCaixa::tiposPagamentoMulti();

                    $produtos = Produto::
                    where('empresa_id', $this->empresa_id)
                    ->where('inativo', false)
                    ->where('valor_venda', '>', 0)
                    ->orderBy('nome')
                    ->get();

                    foreach($produtos as $p){
                        $p->listaPreco;
                        $estoque_atual = 0;
                        if($p->estoque){
                            if($p->unidade_venda == 'UN' || $p->unidade_venda == 'UNID'){
                                $estoque_atual = number_format($p->estoque->quantidade);
                            }else{
                                $estoque_atual = $p->estoque->quantidade;
                            }
                        }
                        $p->estoque_atual = $estoque_atual;
                        if($p->grade){
                            $p->nome .= " $p->str_grade";
                        }
                    }

                    foreach($produtosGroup as $p){
                        $p->listaPreco;
                        $estoque_atual = 0;
                        if($p->estoque){
                            if($p->unidade_venda == 'UN' || $p->unidade_venda == 'UNID'){
                                $estoque_atual = number_format($p->estoque->quantidade);
                            }else{
                                $estoque_atual = $p->estoque->quantidade;
                            }
                        }
                        $p->estoque_atual = $estoque_atual;

                    }

                    $categorias = Categoria::
                    where('empresa_id', $this->empresa_id)
                    ->orderBy('nome')->get();

                    $clientes = Cliente::orderBy('razao_social')
                    ->where('empresa_id', $this->empresa_id)
                    ->where('inativo', false)
                    ->get();

                    foreach($clientes as $c){
                        $c->totalEmAberto = 0;
                        $soma = $this->getTotalContaCredito($c);

                        if($soma->total != null){
                            $c->totalEmAberto = $soma->total;
                        }
                    }

                    $atalhos = ConfigCaixa::
                    where('usuario_id', get_id_user())
                    ->first();

                    $view = 'main';
                    if($atalhos != null && $atalhos->modelo_pdv == 1){
                        $view = 'main2';
                    }
                    if($atalhos != null && $atalhos->modelo_pdv == 2){
                        $view = 'main3';
                    }
                    $listas = ListaPreco::where('empresa_id', $this->empresa_id)->get();

                    $funcionarios = Funcionario::
                    where('funcionarios.empresa_id', $this->empresa_id)
                    ->select('funcionarios.*')
                    ->join('usuarios', 'usuarios.id', '=', 'funcionarios.usuario_id')
                    ->get();

                    $funcionarios = $this->validaCaixaAberto($funcionarios);

                    if(sizeof($funcionarios) == 0 && $usuario->caixa_livre){
                        session()->flash("mensagem_erro", "Usuário definido para caixa livre, cadastre ao menos um funcionário!");
                        return redirect('/funcionarios');
                    }

                    $rascunhos = $this->getRascunhos();
                    $consignadas = $this->getConsignadas();

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

                    $estados = Cliente::estados();
                    $cidades = Cidade::all();
                    $pais = Pais::all();
                    $grupos = GrupoCliente::get();
                    $acessores = Acessor::where('empresa_id', $this->empresa_id)->get();

                    return view('frontBox/'.$view)
                    ->with('frenteCaixa', true)
                    ->with('tiposPagamento', $tiposPagamento)
                    ->with('config', $config)
                    ->with('certificado', $certificado)
                    ->with('listaPreco', $listas)
                    ->with('funcionarios', $funcionarios)
                    ->with('rascunhos', $rascunhos)
                    ->with('consignadas', $consignadas)
                    ->with('atalhos', $atalhos)
                    ->with('vendedores', $vendedores)
                    ->with('estados', $estados)
                    ->with('disableFooter', true)
                    ->with('usuario', $usuario)
                    ->with('produtos', $produtos)
                    ->with('produtosGroup', $produtosGroup)
                    ->with('clientes', $clientes)
                    ->with('categorias', $categorias)
                    ->with('tiposPagamentoMulti', $tiposPagamentoMulti)

                    ->with('pessoaFisicaOuJuridica', true)
                    ->with('cidadeJs', true)
                    ->with('cidades', $cidades)
                    ->with('estados', $estados)
                    ->with('acessores', $acessores)
                    ->with('funcionarios', $funcionarios)
                    ->with('grupos', $grupos)
                    ->with('pais', $pais)

                    ->with('title', 'Frente de Caixa');
                }
            }
        }
    }

    private function validaCaixaAberto($funcionarios){
        $temp = [];
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        foreach($funcionarios as $f){
            $aberturaNfe = AberturaCaixa::
            where('empresa_id', $this->empresa_id)
            ->when($config->caixa_por_usuario == 1, function ($q) use ($f) {
                return $q->where('usuario_id', $f->usuario_id);
            })
            ->orderBy('id', 'desc')->first();
            if($aberturaNfe != null){

                if($aberturaNfe->status == 0){
                    array_push($temp, $f);
                }else{
                    session()->flash('mensagem_erro', 'Caixa do usuário ' . $f->usuario->nome . ' esta fechado, abra para continuar o PDV com caixa livre!');
                }
            }
        }
        return $temp;
    }

    private function produtosMaisVendidos(){

        $abertura = AberturaCaixa::where('empresa_id', $this->empresa_id)
        ->where('usuario_id', get_id_user())
        ->where('status', 0)
        ->orderBy('id', 'desc')
        ->first();
        $filial = -1;

        if($abertura){
            $filial = $abertura->filial_id;
            if($filial == null){
                $filial = -1;
            }
        }

        $itens = ItemVendaCaixa::
        selectRaw('item_venda_caixas.*, count(quantidade) as qtd')
        ->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
        ->join('produtos', 'produtos.id', '=', 'item_venda_caixas.produto_id')
        ->where('venda_caixas.empresa_id', $this->empresa_id)
        ->groupBy('item_venda_caixas.produto_id')
        ->orderBy('qtd')
        ->when(empresaComFilial(), function ($q) use ($filial) {
            return $q->where(function($query) use ($filial){
                $query->where('produtos.locais', 'like', "%{$filial}%");
            });
        })
        ->limit(21)
        ->get();

        $produtos = [];
        $add = [];

        foreach($itens as $i){
            $p = Produto::find($i->produto_id);
            $locais = json_decode($p->locais);
            foreach($locais as $l){
                if($l == $filial && !in_array($p->id, $add)){

                    if(!$p->inativo){
                        array_push($produtos, $p);
                        array_push($add, $p->id);
                    }
                }
            }
        }

        return $produtos;
    }

    protected function pdvAssincrono($venda = null, $edit = 0){
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $naturezas = NaturezaOperacao::
        where('empresa_id', $this->empresa_id)
        ->get();

        $categorias = Categoria::
        where('empresa_id', $this->empresa_id)
        ->get();

        $produtos = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->count();

        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
        ->get();

        $tiposPagamento = VendaCaixa::tiposPagamento();
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $certificado = Certificado::
        where('empresa_id', $this->empresa_id)
        ->first();

        $usuario = Usuario::find(get_id_user());
        if(count($naturezas) == 0 || $config == null || count($categorias) == 0 || $tributacao == null || $produtos == 0){

            $p = view("frontBox/alerta")
            ->with('produtos', $produtos)
            ->with('categorias', count($categorias))
            ->with('naturezas', $naturezas)
            ->with('config', $config)
            ->with('tributacao', $tributacao)
            ->with('title', "Validação para Emitir");

            return $p;
        }else {
            $tiposPagamentoMulti = VendaCaixa::tiposPagamentoMulti();
            $categorias = Categoria::
            where('empresa_id', $this->empresa_id)
                ->orderBy('nome')->get();

            $clientes = Cliente::orderBy('razao_social')
                ->where('empresa_id', $this->empresa_id)
                ->where('inativo', false)
                ->get();

            foreach ($clientes as $c) {
                $c->totalEmAberto = 0;
                $soma = $this->getTotalContaCredito($c);
                if ($soma->total != null) {
                    $c->totalEmAberto = $soma->total;
                }
            }

            $atalhos = ConfigCaixa::
            where('usuario_id', get_id_user())
                ->first();

            $listas = ListaPreco::where('empresa_id', $this->empresa_id)->get();

            $produtosMaisVendidos = $this->produtosMaisVendidos();

            $rascunhos = $this->getRascunhos();
            $funcionarios = Funcionario::
            where('funcionarios.empresa_id', $this->empresa_id)
                ->select('funcionarios.*')
                ->join('usuarios', 'usuarios.id', '=', 'funcionarios.usuario_id')
                ->get();

            if ($config->caixa_por_usuario == 1) {
                $funcionarios = $this->validaCaixaAberto($funcionarios);
                if (sizeof($funcionarios) == 0 && $usuario->caixa_livre == 1) {
                    return redirect('/caixa');

                    if (sizeof($funcionarios) == 0) {
                        return redirect('/funcionarios');
                    }
                    return redirect('/caixa');
                }
            }

            $view = 'pdv_assincrono';

            if ($atalhos != null && $atalhos->modelo_pdv == 2) {
                $view = 'main3';
            }

            $usuarios = Usuario::where('empresa_id', $this->empresa_id)
                ->where('ativo', 1)
                ->orderBy('nome', 'asc')
                ->get();

            $vendedores = [];
            foreach ($usuarios as $u) {
                if ($u->funcionario) {
                    array_push($vendedores, $u);
                }
            }

            $estados = Cliente::estados();
            $cidades = Cidade::all();
            $pais = Pais::all();
            $grupos = GrupoCliente::get();
            $acessores = Acessor::where('empresa_id', $this->empresa_id)->get();
            $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->get();

            if ($edit) {
                $view = 'main3';
            }

            /*
            $abertura = AberturaCaixa::where('empresa_id', $this->empresa_id)
                ->where('usuario_id', get_id_user())
                ->where('status', 0)
                ->orderBy('id', 'desc')
                ->first();
            */
            $abertura = AberturaCaixa::with('filial') // carrega a relação corretamente
            ->where('empresa_id', $this->empresa_id)
                ->where('usuario_id', get_id_user())
                ->where('status', 0)
                ->orderBy('id', 'desc')
                ->first();

            //$filial = $abertura != null ? $abertura->filial : null;
            $filial = $abertura != null ? $abertura->filial()->first() : null;

            $consignadas = $this->getConsignadas();

            $contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)
            ->where('status', 1)->get();

            $p = view('frontBox/'.$view)
            ->with('tiposPagamento', $tiposPagamento)
            ->with('config', $config)
            ->with('abertura', $abertura)
            ->with('filial', $filial) // <- ESSA LINHA É ESSENCIAL
            ->with('contasEmpresa', $contasEmpresa)
            ->with('certificado', $certificado)
            ->with('rascunhos', $rascunhos)
            ->with('consignadas', $consignadas)
            ->with('estados', $estados)
            //->with('filial', $filial)
            ->with('filial', $abertura && $abertura->filial ? $abertura->filial : null)
            ->with('cidades', $cidades)
            ->with('pais', $pais)
            ->with('grupos', $grupos)
            ->with('acessores', $acessores)
            ->with('vendedores', $vendedores)
            ->with('usuarios', $usuarios)
            ->with('funcionarios', $funcionarios)
            ->with('listaPreco', $listas)
            ->with('produtosMaisVendidos', $produtosMaisVendidos)
            ->with('atalhos', $atalhos)
            ->with('disableFooter', true)
            ->with('usuario', $usuario)
            ->with('clientes', $clientes)
            ->with('categorias', $categorias)
            ->with('tiposPagamentoMulti', $tiposPagamentoMulti)
            ->with('title', 'Frente de Caixa');

            if($venda != null){
                $venda->cliente;
                foreach($venda->itens as $it){
                    $it->produto;
                }
                $p->with('venda', $venda);
            }

            return $p;
        }
    }

    private function getTotalContaCredito($cliente){
        return CreditoVenda::
        selectRaw('sum(vendas.valor_total) as total')
        ->join('vendas', 'vendas.id', '=', 'credito_vendas.venda_id')
        ->where('credito_vendas.cliente_id', $cliente->id)
        ->where('status', 0)
        ->first();
    }

    private function cancelarNFCe($venda){
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $cnpj = str_replace(".", "", $config->cnpj);
        $cnpj = str_replace("/", "", $cnpj);
        $cnpj = str_replace("-", "", $cnpj);
        $cnpj = str_replace(" ", "", $cnpj);
        $nfe_service = new NFeService([
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb" => 2,
            "razaosocial" => $config->razao_social,
            "siglaUF" => $config->UF,
            "cnpj" => $cnpj,
            "schemes" => config('fiscal.default_schemes'),
            "versao" => "4.00",
            "tokenIBPT" => "AAAAAAA",
            "CSC" => "XTZOH6COASX5DYLKBUZXG5TABFG7ZFTQVSA2",
            "CSCid" => "000001"
        ], 65);

        $nfce = $nfe_service->cancelarNFCe($venda->id, "Troca de produtos requisitada pelo cliente");
        return is_array($nfce);
    }

    private function criarLog($objeto, $tipo = 'criar'){
        if(isset(session('user_logged')['log_id'])){
            $record = [
                'tipo' => $tipo,
                'usuario_log_id' => session('user_logged')['log_id'],
                'tabela' => 'venda_caixas',
                'registro_id' => $objeto->id,
                'empresa_id' => $this->empresa_id
            ];
            __saveLog($record);
        }
    }

    public function deleteVenda($id){
        $venda = VendaCaixa::findOrFail($id);

        if(valida_objeto($venda)){
            $stockMove = new StockMove();
            $this->criarLog($venda, 'deletar');
            $comissao = ComissaoVenda::
            where('empresa_id', $this->empresa_id)
            ->where('tabela', 'venda_caixas')
            ->where('venda_id', $id)
            ->first();

            if($comissao != null)
                $comissao->delete();

            if($venda->troca()){
                $venda->troca()->delete();
            }

            foreach($venda->itens as $i){
                if($i->produto->receita){

                    $receita = $i->produto->receita;
                    foreach($receita->itens as $rec){

                        if($i->itemPedido != NULL && $i->itemPedido->tamanho != NULL){
                            $totalSabores = count($i->itemPedido->sabores);
                            $produtoPizza = ProdutoPizza::
                            where('produto_id', $i->produto->delivery->id)
                            ->where('tamanho_id', $i->itemPedido->tamanho->id)
                            ->first();

                            $stockMove->pluStock(
                                $rec->produto_id, $i->quantidade *
                                ((($rec->quantidade/$totalSabores)/$receita->pedacos)*$produtoPizza->tamanho->pedacos)/$receita->rendimento
                            );

                        }else{
                            $stockMove->pluStock($rec->produto_id,
                                $i->quantidade);
                        }
                    }
                }else{
                    $stockMove->pluStock($i->produto_id,
                        $i->quantidade); // -50 na altera valor compra
                }
            }

            ContaReceber::where('venda_caixa_id', $venda->id)
            ->delete();
            if($venda->delete()){
                session()->flash("mensagem_sucesso", "Venda removida com sucesso!");
            }else{
                session()->flash('mensagem_erro', 'Erro ao remover venda!');
            }
            return redirect('/frenteCaixa/devolucao');
        }else{
            return redirect('/403');
        }
    }

    public function retornaEstoque($id){
        $venda = VendaCaixa
        ::where('id', $id)
        ->first();

        if(valida_objeto($venda)){
            $stockMove = new StockMove();

            $comissao = ComissaoVenda::
            where('empresa_id', $this->empresa_id)
            ->where('tabela', 'venda_caixas')
            ->where('venda_id', $id)
            ->first();

            if($comissao != null)
                $comissao->delete();

            foreach($venda->itens as $i){
                if($i->produto->receita){
                    $receita = $i->produto->receita;
                    foreach($receita->itens as $rec){

                        if($i->itemPedido != NULL && $i->itemPedido->tamanho != NULL){
                            $totalSabores = count($i->itemPedido->sabores);
                            $produtoPizza = ProdutoPizza::
                            where('produto_id', $i->produto->delivery->id)
                            ->where('tamanho_id', $i->itemPedido->tamanho->id)
                            ->first();

                            $stockMove->pluStock(
                                $rec->produto_id, $i->quantidade
                      *
                                ((($rec->quantidade/$totalSabores)/$receita->pedacos)*$produtoPizza->tamanho->pedacos)/$receita->rendimento
                            );

                        }else{
                            $stockMove->pluStock($rec->produto_id,
                                $i->quantidade);
                        }
                    }
                }else{
                    $stockMove->pluStock($i->produto_id,
                        $i->quantidade); // -50 na altera valor compra
                }
            }

            // ContaReceber::where('venda_caixa_id', $venda->id)
            // ->delete();
            $venda->retorno_estoque = 1;
            $venda->save();

            session()->flash("mensagem_sucesso", "Venda com estoque retornado!");

            return redirect('/frenteCaixa/devolucao');
        }else{
            return redirect('/403');
        }
    }

    public function deleteRascunho($id){
        $venda = VendaCaixa
        ::where('id', $id)
        ->first();

        if(valida_objeto($venda)){
            if($venda->rascunho){
                if($venda->delete()){
                    session()->flash("mensagem_sucesso", "Venda removida com sucesso!");
                }else{
                    session()->flash('mensagem_erro', 'Erro ao remover venda!');
                }
            }else{
                session()->flash('mensagem_erro', 'Esta venda não é um rascunho!');
            }
            return redirect('/frenteCaixa');

        }else{
            return redirect('/403');
        }
    }

    public function deleteRascunhoPreVenda($id){
        $venda = VendaCaixaPreVenda
        ::where('id', $id)
        ->first();

        $stockMove = new StockMove();
        foreach($venda->itens as $i){
            if($i->produto->receita){
                $receita = $i->produto->receita;
                foreach($receita->itens as $rec){

                    if($i->itemPedido != NULL && $i->itemPedido->tamanho != NULL){
                        $totalSabores = count($i->itemPedido->sabores);
                        $produtoPizza = ProdutoPizza::
                        where('produto_id', $i->produto->delivery->id)
                        ->where('tamanho_id', $i->itemPedido->tamanho->id)
                        ->first();

                        $stockMove->pluStock(
                            $rec->produto_id, $i->quantidade *
                            ((($rec->quantidade/$totalSabores)/$receita->pedacos)*$produtoPizza->tamanho->pedacos)/$receita->rendimento
                        );

                    }else{
                        $stockMove->pluStock($rec->produto_id,
                            $i->quantidade);
                    }
                }
            }else{
                $stockMove->pluStock($i->produto_id,
                        $i->quantidade); // -50 na altera valor compra
            }
        }

        if(valida_objeto($venda)){

            if($venda->delete()){
                session()->flash("mensagem_sucesso", "Venda removida com sucesso!");
            }else{
                session()->flash('mensagem_erro', 'Erro ao remover venda!');
            }

            return redirect('/frenteCaixa/prevenda');

        }else{
            return redirect('/403');
        }
    }

    private function getContigencia(){
        $active = Contigencia::
        where('empresa_id', $this->empresa_id)
        ->where('status', 1)
        ->where('documento', 'NFCe')
        ->first();
        return $active;
    }

    public function contigencia(Request $request){
        $contigencia = $this->getContigencia();
        if($contigencia != null){
            session()->flash('mensagem_erro', 'Desative a contigência do sistema para acessar essa tela!');
            return redirect('/contigencia');
        }
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $vendas = VendaCaixa::where('contigencia', 1)
        ->where('reenvio_contigencia', 0)
        ->when(!empty($start_date), function ($query) use ($start_date) {
            return $query->whereDate('created_at', '>=', $start_date);
        })
        ->when(!empty($end_date), function ($query) use ($end_date) {
            return $query->whereDate('created_at', '<=', $end_date);
        })
        ->where('empresa_id', $this->empresa_id)->get();

        return view('frontBox/contigencia')
        ->with('vendas', $vendas)
        ->with('start_date', $start_date)
        ->with('end_date', $end_date)
        ->with('title', 'NFCe em contigência');
    }

    public function list(){

        $this->numeroSequencial();
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        $vendas = VendaCaixa::filtroData(
            $this->parseDate(date("Y-m-d")),
            $this->parseDate(date("Y-m-d"), true),
            $config
        );

        $somaTiposPagamento = $this->somaTiposPagamento($vendas);
        $user = Usuario::find(get_id_user());
        $usuarios = [];
        if($user->adm){
            $usuarios = Usuario::
            where('empresa_id', $this->empresa_id)
            ->get();
        }
        $usuario_id = get_id_user();

        $certificado = Certificado::
        where('empresa_id', $this->empresa_id)
        ->first();

        $clientes = Cliente::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', 0)
        ->get();

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
        $total = 0;
        foreach($vendas as $v){
            if(!$v->rascunho && !$v->consignado){
                $total += $v->valor_total;
            }
        }
        return view('frontBox/list')
        ->with('vendas', $vendas)
        ->with('config', $config)
        ->with('vendedores', $vendedores)
        ->with('clientes', $clientes)
        ->with('usuario_id', $usuario_id)
        ->with('total', $total)
        ->with('frenteCaixa', true)
        ->with('certificado', $certificado)
        ->with('contigencia', $this->getContigencia())
        ->with('somaTiposPagamento', $somaTiposPagamento)
        ->with('usuarios', $usuarios)
        ->with('info', "Lista de vendas de Hoje: " . date("d/m/Y") )
        ->with('title', 'Lista de Vendas na Frente de Caixa');
    }

    private function somaTiposPagamento($vendas){
        $tipos = $this->preparaTipos();

        foreach($vendas as $v){
            if(isset($tipos[$v->tipo_pagamento])){
                if(!$v->rascunho && !$v->consignado){
                    if($v->tipo_pagamento != 99){
                        $tipos[$v->tipo_pagamento] += $v->valor_total;
                    }else{
                        if($v->fatura){
                            foreach($v->fatura as $fat){
                                $tipos[trim($fat->forma_pagamento)] += $fat->valor;
                            }
                        }
                    }
                }
            }else{
                if(!$v->rascunho && !$v->consignado){
                    foreach($v->fatura as $fat){
                        $tipos[trim($fat->forma_pagamento)] += $fat->valor;
                    }
                }
            }
        }
        return $tipos;

    }

    private function preparaTipos(){
        $temp = [];
        foreach(VendaCaixa::tiposPagamento() as $key => $tp){
            $temp[$key] = 0;
        }
        return $temp;
    }

    public function devolucao(){
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        $vendas = VendaCaixa::
        orderBy('id', 'desc')
        ->where('empresa_id', $this->empresa_id)
        ->limit(20)
        ->when(optional($config)->caixa_por_usuario == 1, function ($q) {
            return $q->where('usuario_id', get_id_user());
        })
        ->get();

        $caixa = AberturaCaixa::where('status', 1)
        ->where('empresa_id', $this->empresa_id)
        ->orderBy('id', 'desc')->first();

        foreach($vendas as $v){
            $v->impedeDelete = false;
            if($caixa != null && strtotime($v->created_at) < strtotime($caixa->updated_at)){
                $v->impedeDelete = true;
            }
        }

        return view('frontBox/devolucao')
        ->with('config', $config)
        ->with('vendas', $vendas)
        ->with('frenteCaixa', true)
        ->with('nome', '')
        ->with('nfce', '')
        ->with('valor', '')
        ->with('data', '')
        ->with('info', "Lista das ultimas 20 vendas")
        ->with('title', 'Devolução NFCe');
    }

    public function filtro(Request $request){
        $dataInicial = $request->data_inicial;
        $dataFinal = $request->data_final;
        $vendedor_id = $request->vendedor_id;
        $filial_id = $request->filial_id;
        $cliente_id = $request->cliente_id;
        $codigo_venda = $request->codigo_venda;
        $tipo_pagamento = $request->tipo_pagamento;

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        $permissaoAcesso = __getLocaisUsarioLogado();

        $vendas = VendaCaixa::
        orderBy('id', 'desc')
        ->where(function($query) use ($permissaoAcesso){
            if($permissaoAcesso != null){
                foreach ($permissaoAcesso as $value) {
                    if($value == -1){
                        $value = null;
                    }
                    $query->orWhere('venda_caixas.filial_id', $value);
                }
            }
        })
        ->when($filial_id, function ($query) use ($filial_id) {
            $filial_id = $filial_id == -1 ? null : $filial_id;
            return $query->where('venda_caixas.filial_id', $filial_id);
        })
        ->when($codigo_venda, function ($query) use ($codigo_venda) {
            return $query->where('venda_caixas.id', $codigo_venda);
        })
        // ->when($tipo_pagamento, function ($query) use ($tipo_pagamento) {
        //     return $query->where('venda_caixas.tipo_pagamento', $tipo_pagamento);
        // })
        ->when($vendedor_id, function ($query) use ($vendedor_id) {
            return $query->where('venda_caixas.vendedor_id', $vendedor_id);
        })
        ->when($cliente_id, function ($query) use ($cliente_id) {
            return $query->where('venda_caixas.cliente_id', $cliente_id);
        });

        if($dataInicial && $dataFinal){
            $vendas->whereBetween('created_at', [
                $dataInicial,
                $dataFinal
            ]);
        }
        $vendas->where('empresa_id', $this->empresa_id);

        if($request->status != ""){
            if($request->status == "fiscal"){
                $vendas->where('estado', 'APROVADO')
                ->where('NFcNumero', '>', 0);
            }else if($request->status == "nao_fiscal"){
                $vendas->where('estado', 'DISPONIVEL')
                ->where('NFcNumero', 0);
            }else if($request->status == "rascunho"){
                $vendas->where('rascunho', 1);
            }
        }

        if($request->valor != ""){
            $vendas->where('valor_total', __replace($request->valor));
        }

        if($request->numero_nfce != ""){
            $vendas->where('NFcNumero', $request->numero_nfce);
        }

        $usuario_id = get_id_user();
        if(isset($request->usuario)){
            if($request->usuario != "--"){
                $vendas->where('usuario_id', $request->usuario);
                $usuario_id = $request->usuario;
            }else{
                $usuario_id = null;
            }
        }else{
            if($config->caixa_por_usuario == 1){
                $vendas->where('usuario_id', get_id_user());
            }
        }

        $vendas = $vendas->get();

        if($tipo_pagamento){
            $data = [];
            foreach($vendas as $v){
                if(sizeof($v->fatura) > 0){
                    foreach($v->fatura as $f){
                        if($f->forma_pagamento == $tipo_pagamento){
                            array_push($data, $v->id);
                        }
                    }
                }else{
                    if($v->tipo_pagamento == $tipo_pagamento){
                        array_push($data, $v->id);
                    }
                }
            }
            $vendas = VendaCaixa::whereIn('id', $data)->get();
        }


        $somaTiposPagamento = $this->somaTiposPagamento($vendas);

        $user = Usuario::find(get_id_user());
        $usuarios = [];
        if($user->adm){
            $usuarios = Usuario::
            where('empresa_id', $this->empresa_id)
            ->get();
        }

        $certificado = Certificado::
        where('empresa_id', $this->empresa_id)
        ->first();

        $clientes = Cliente::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', 0)
        ->get();

        $vendedores = [];
        foreach($usuarios as $u){
            if($u->funcionario){
                array_push($vendedores, $u);
            }
        }
        $total = 0;
        foreach($vendas as $v){
            if(!$v->rascunho && !$v->consignado){
                $total += $v->valor_total;
            }
        }

        return view('frontBox/list')
        ->with('vendas', $vendas)
        ->with('clientes', $clientes)
        ->with('total', $total)
        ->with('vendedores', $vendedores)
        ->with('dataInicial', $dataInicial)
        ->with('certificado', $certificado)
        ->with('codigo_venda', $codigo_venda)
        ->with('status', $request->status)
        ->with('vendedor_id', $request->vendedor_id)
        ->with('numero_nfce', $request->numero_nfce)
        ->with('valor', $request->valor)
        ->with('contigencia', $this->getContigencia())
        ->with('somaTiposPagamento', $somaTiposPagamento)
        ->with('info', "Lista de vendas período: $dataInicial até $dataFinal")
        ->with('dataFinal', $dataFinal)
        ->with('usuarios', $usuarios)
        ->with('tipo_pagamento', $tipo_pagamento)
        ->with('filial_id', $filial_id)
        ->with('usuario_id', $usuario_id)
        ->with('config', $config)
        ->with('cliente_id', $cliente_id)
        ->with('frenteCaixa', true)
        ->with('info', "")
        ->with('title', 'Filtro de Vendas na Frente de Caixa');
    }


    private function parseDate($date, $plusDay = false){
        if($plusDay == false)
            return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
        else
            return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
    }



    public function filtroCliente(Request $request){

        $vendas = VendaCaixa::filtroCliente($request->nome);
        return view('frontBox/devolucao')
        ->with('vendas', $vendas)
        ->with('frenteCaixa', true)
        ->with('valor', '')
        ->with('nome', $request->nome)
        ->with('nfce', '')
        ->with('data', '')
        ->with('info', "Filtro cliente: $request->nome")

        ->with('title', 'Filtro por cliente');
    }


    public function filtroNFCe(Request $request){

        $vendas = VendaCaixa::filtroNFCe($request->nfce);
        return view('frontBox/devolucao')
        ->with('vendas', $vendas)
        ->with('frenteCaixa', true)
        ->with('valor', '')
        ->with('nfce', $request->nfce)
        ->with('nome', '')
        ->with('data', '')
        ->with('info', "Filtro NFCE: $request->nfce")
        ->with('title', 'Filtro por NFCe');
    }

    public function filtroData(Request $request){

        $vendas = VendaCaixa::filtroData2($request->data);

        return view('frontBox/devolucao')
        ->with('vendas', $vendas)
        ->with('frenteCaixa', true)
        ->with('valor', '')
        ->with('data', $request->data)
        ->with('nome', '')
        ->with('nfce', '')
        ->with('info', "Filtro Data: $request->data")
        ->with('title', 'Filtro por Data');
    }

    public function filtroValor(Request $request){

        $valor = __replace($request->valor);

        $vendas = VendaCaixa::filtroValor($valor);
        return view('frontBox/devolucao')
        ->with('vendas', $vendas)
        ->with('frenteCaixa', true)
        ->with('nfce', '')
        ->with('valor', $valor)
        ->with('nome', '')
        ->with('data', '')
        ->with('info', "Filtro valor: $request->valor")

        ->with('title', 'Filtro por Valor');
    }

    public function fechar(){
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        $aberturaNfe = AberturaCaixa::where('ultima_venda_nfe', 0)
        ->where('empresa_id', $this->empresa_id)
        ->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
            return $q->where('usuario_id', get_id_user());
        })
        ->orderBy('id', 'desc')->first();

        $aberturaNfce = AberturaCaixa::where('ultima_venda_nfce', 0)
        ->where('empresa_id', $this->empresa_id)
        ->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
            return $q->where('usuario_id', get_id_user());
        })
        ->orderBy('id', 'desc')->first();

        $ultimaFechadaNfe = AberturaCaixa::where('ultima_venda_nfe', '>', 0)
        ->where('empresa_id', $this->empresa_id)
        ->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
            return $q->where('usuario_id', get_id_user());
        })
        ->orderBy('id', 'desc')->first();

        $ultimaFechadaNfce = AberturaCaixa::where('ultima_venda_nfce', '>', 0)
        ->where('empresa_id', $this->empresa_id)
        ->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
            return $q->where('usuario_id', get_id_user());
        })
        ->orderBy('id', 'desc')->first();

        $ultimaVendaCaixa = VendaCaixa::
        where('empresa_id', $this->empresa_id)
        ->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
            return $q->where('usuario_id', get_id_user());
        })
        ->orderBy('id', 'desc')->first();

        $ultimaVenda = Venda::
        where('empresa_id', $this->empresa_id)
        ->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
            return $q->where('usuario_id', get_id_user());
        })
        ->orderBy('id', 'desc')->first();

        $vendas = [];

        $somaTiposPagamento = [];
        if($ultimaVendaCaixa != null || $ultimaVenda != null){
            $ultimaVendaCaixa = $ultimaVendaCaixa != null ? $ultimaVendaCaixa->id : 0;
            $ultimaVenda = $ultimaVenda != null ? $ultimaVenda->id : 0;

            $vendasPdv = VendaCaixa
            ::whereBetween('id', [($ultimaFechadaNfce != null ? $ultimaFechadaNfce->ultima_venda_nfce+1 : 0),
                $ultimaVendaCaixa])
            ->where('empresa_id', $this->empresa_id)
            ->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
                return $q->where('usuario_id', get_id_user());
            })
            ->get();

            $vendas = Venda
            ::whereBetween('id', [($ultimaFechadaNfe != null ? $ultimaFechadaNfe->ultima_venda_nfe+1 : 0),
                $ultimaVenda])
            ->where('empresa_id', $this->empresa_id)
            ->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
                return $q->where('usuario_id', get_id_user());
            })
            ->get();

            $vendas = $this->agrupaVendas($vendas, $vendasPdv);
            $somaTiposPagamento = $this->somaTiposPagamento($vendas);

        }

        return view('frontBox/fechar_caixa')
        ->with('vendas', $vendas)
        ->with('abertura', $aberturaNfe != null ? $aberturaNfe : $aberturaNfce)
        ->with('somaTiposPagamento', $somaTiposPagamento)
        ->with('title', 'Fechar caixa');

    }

    private function agrupaVendas($vendas, $vendasPdv){
        $temp = [];
        foreach($vendas as $v){
            $v->tipo = 'VENDA';
            array_push($temp, $v);
        }

        foreach($vendasPdv as $v){
            $v->tipo = 'PDV';
            array_push($temp, $v);
        }

        return $temp;
    }

    public function fecharPost(Request $request){
        $id = $request->abertura_id;
        $abertura = AberturaCaixa::find($id);
        $ultimaVendaCaixa = VendaCaixa::
        where('empresa_id', $this->empresa_id)
        ->orderBy('id', 'desc')->first();

        $ultimaVenda = Venda::
        where('empresa_id', $this->empresa_id)
        ->orderBy('id', 'desc')->first();

        $abertura->ultima_venda_nfce = $ultimaVendaCaixa != null ?
        $ultimaVendaCaixa->id : 0;
        $abertura->ultima_venda_nfe = $ultimaVenda != null ? $ultimaVenda->id : 0;
        $abertura->status = true;
        $abertura->valor_dinheiro_caixa = __replace($request->valor_dinheiro_caixa);
        $abertura->save();
        session()->flash("mensagem_sucesso", "Caixa fechado com sucesso!");

        if(isset($request->redirect)){
            return redirect($request->redirect);
        }
        return redirect('frenteCaixa/list');
    }

    public function fechamentos(){
        $aberturas = AberturaCaixa::where('ultima_venda', '>', 0)
        ->where('empresa_id', $this->empresa_id)->get();
        $arr = [];

        for($i = 0; $i < sizeof($aberturas); $i++){
            $atual = $aberturas[$i]->ultima_venda;
            if($i == 0){
                $anterior = 0;
            }else{
                $anterior = $aberturas[$i-1]->ultima_venda;
            }
            $vendas = VendaCaixa
            ::whereBetween('id', [$anterior+1,
                $atual])
            ->get();

            $total = 0;
            foreach($vendas as $v){
                $total += $v->valor_total;
            }

            $temp = [
                'inicio' => \Carbon\Carbon::parse($aberturas[$i]->created_at)->format('d/m/Y H:i:s'),
                'fim' => \Carbon\Carbon::parse($aberturas[$i]->updated_at)->format('d/m/Y H:i:s'),
                'total' => $total,
                'id' => $aberturas[$i]->id
            ];

            array_push($arr, $temp);
        }

        usort($arr, function ($a, $b) {
            return ($a['id'] < $b['id']) ? 1 : -1;
        });

        return view('frontBox/fechamentos')
        ->with('fechamentos', $arr)
        ->with('title', 'Lista de Caixas');
    }

    public function listaFechamento($id){
        $aberturas = AberturaCaixa::
        where('empresa_id', $this->empresa_id)
        ->get();
        $abertura = null;
        $inicio = 0;
        $fim = 0;

        for($i = 0; $i < sizeof($aberturas); $i++){
            if($aberturas[$i]->id == $id){
                $abertura = $aberturas[$i];
                if($i > 0){
                    $inicio = $aberturas[$i-1]->ultima_venda +1;
                }

                $fim = $aberturas[$i]->ultima_venda;
            }
        }

        $vendas = [];
        $somaTiposPagamento = [];


        $vendas = VendaCaixa
        ::whereBetween('id', [$inicio,
            $fim])
        ->get();

        $somaTiposPagamento = $this->somaTiposPagamento($vendas);

        return view('frontBox/lista_fecha_caixa')
        ->with('vendas', $vendas)
        ->with('abertura', $abertura)
        ->with('filial', $filial) // <- ESSA LINHA É ESSENCIAL
        ->with('somaTiposPagamento', $somaTiposPagamento)
        ->with('title', 'Detalhe fecha caixa');
    }

    public function config(){

        $config = ConfigCaixa::
        where('usuario_id', get_id_user())
        ->first();

        if($config != null)
            $config->tipos_pagamento = json_decode($config->tipos_pagamento);

        return view('frontBox/config')
        ->with('config', $config)
        ->with('title', 'Configuração Caixa');
    }

    public function configSave(Request $request){
        // $usuario = Usuario::find(get_id_user());
        $config = ConfigCaixa::
        where('usuario_id', get_id_user())
        ->first();

        if(!isset($request->tipos_pagamento)){
            $request->tipos_pagamento = [];
        }

        if($config == null){
            $data = [
                'finalizar' => $request->finalizar ?? '',
                'reiniciar' => $request->reiniciar ?? '',
                'editar_desconto' => $request->editar_desconto ?? '',
                'editar_acrescimo' => $request->editar_acrescimo ?? '',
                'editar_observacao' => $request->editar_observacao ?? '',
                'setar_valor_recebido' => $request->setar_valor_recebido ?? '',
                'forma_pagamento_dinheiro' => $request->forma_pagamento_dinheiro ?? '',
                'forma_pagamento_debito' => $request->forma_pagamento_debito ?? '',
                'forma_pagamento_credito' => $request->forma_pagamento_credito ?? '',
                'setar_quantidade' => $request->setar_quantidade ?? '',
                'forma_pagamento_pix' => $request->forma_pagamento_pix ?? '',
                'setar_leitor' => $request->setar_leitor ?? '',
                'finalizar_fiscal' => $request->finalizar_fiscal ?? '',
                'finalizar_nao_fiscal' => $request->finalizar_nao_fiscal ?? '',
                'valor_recebido_automatico' => 0,
                'modelo_pdv' => $request->modelo_pdv,
                'balanca_valor_peso' => $request->balanca_valor_peso,
                'balanca_digito_verificador' => $request->balanca_digito_verificador ?? 5,
                'impressora_modelo' => $request->impressora_modelo ?? 80,
                'impressao_pre_venda' => $request->impressao_pre_venda ?? 80,
                'cupom_modelo' => $request->cupom_modelo ?? 1,
                'usuario_id' => get_id_user(),
                'mercadopago_public_key' => $request->mercadopago_public_key ?? '',
                'mercadopago_access_token' => $request->mercadopago_access_token ?? '',
                'tipos_pagamento' => json_encode($request->tipos_pagamento),
                'tipo_pagamento_padrao' => $request->tipo_pagamento_padrao ?? '',
                'mensagem_padrao_cupom' => $request->mensagem_padrao_cupom ?? '',
                'exibe_produtos' => $request->exibe_produtos ? true : false,
                'exibe_modal_cartoes' => $request->exibe_modal_cartoes ? true : false,
                'botao_nao_fiscal' => $request->botao_nao_fiscal ? true : false,
                'imprimir_ticket_troca' => $request->imprimir_ticket_troca ? true : false,
            ];

            ConfigCaixa::create($data);
            session()->flash("mensagem_sucesso", "Configuração salva!");

        }else{
            $config->finalizar = $request->finalizar ?? '';
            $config->reiniciar = $request->reiniciar ?? '';
            $config->editar_desconto = $request->editar_desconto ?? '';
            $config->editar_acrescimo = $request->editar_acrescimo ?? '';
            $config->setar_quantidade = $request->setar_quantidade ?? '';
            $config->editar_observacao = $request->editar_observacao ?? '';
            $config->setar_valor_recebido = $request->setar_valor_recebido ?? '';
            $config->forma_pagamento_dinheiro = $request->forma_pagamento_dinheiro ?? '';
            $config->forma_pagamento_debito = $request->forma_pagamento_debito ?? '';
            $config->forma_pagamento_credito = $request->forma_pagamento_credito ?? '';
            $config->forma_pagamento_pix = $request->forma_pagamento_pix ?? '';
            $config->setar_leitor = $request->setar_leitor ?? '';
            $config->finalizar_fiscal = $request->finalizar_fiscal ?? '';
            $config->mensagem_padrao_cupom = $request->mensagem_padrao_cupom ?? '';
            $config->finalizar_nao_fiscal = $request->finalizar_nao_fiscal ?? '';
            $config->balanca_digito_verificador = $request->balanca_digito_verificador ?? '';
            $config->valor_recebido_automatico = $request->valor_recebido_automatico ? true : false;
            $config->exibe_produtos = $request->exibe_produtos ? true : false;
            $config->exibe_modal_cartoes = $request->exibe_modal_cartoes ? true : false;
            $config->botao_nao_fiscal = $request->botao_nao_fiscal ? true : false;
            $config->imprimir_ticket_troca = $request->imprimir_ticket_troca ? true : false;

            $config->balanca_valor_peso = $request->balanca_valor_peso;
            $config->modelo_pdv = $request->modelo_pdv;
            $config->balanca_digito_verificador = $request->balanca_digito_verificador ?? 5;
            $config->mercadopago_public_key = $request->mercadopago_public_key ?? '';
            $config->mercadopago_access_token = $request->mercadopago_access_token ?? '';
            $config->impressora_modelo = $request->impressora_modelo ?? 80;
            $config->impressao_pre_venda = $request->impressao_pre_venda ?? 80;
            $config->cupom_modelo = $request->cupom_modelo ?? 1;
            $config->tipos_pagamento = json_encode($request->tipos_pagamento);
            $config->tipo_pagamento_padrao = $request->tipo_pagamento_padrao;

            $config->save();
            session()->flash("mensagem_sucesso", "Configuração editada!");

        }

        return redirect()->back();
    }

    public function troca(Request $request){
        $data = $request->data;

        $trocas = TrocaVendaCaixa::
        orderBy('id', 'desc');

        if($data !== null){
            $trocas->whereBetween('created_at', [
                $this->parseDate($data) . " 00:00:00",
                $this->parseDate($data) . " 23:59:59",
            ]);
        }

        $trocas = $trocas->where('empresa_id', $this->empresa_id)
        ->limit(20)
        ->get();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        return view('frontBox/troca')
        ->with('config', $config)
        ->with('trocas', $trocas)
        ->with('frenteCaixa', true)
        ->with('data', $data)
        ->with('title', 'Troca de PDV');
    }

    public function editTroca(Request $request){
        $venda = false;
        if($request->nfce != null){
            $venda = VendaCaixa::where('NFcNumero', $request->nfce)->where('empresa_id', $this->empresa_id)->first();
        }else{
            $venda = VendaCaixa::where('id', $request->id)->where('empresa_id', $this->empresa_id)->first();
        }

        if(!$venda){
            session()->flash('mensagem_erro', 'ID da Venda ou número de NFCe Incorretos!');
            return redirect('/frenteCaixa/troca');
        }

        if(!valida_objeto($venda)){
            return redirect('/403');
        }

        $troca = TrocaVendaCaixa::where('antiga_venda_caixas_id', $venda->id)->first();

        // if($troca){
        //     session()->flash('mensagem_erro', 'Já houve uma troca para essa venda');
        //     return redirect('/frenteCaixa/list');
        // }

        $countProdutos = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->count();


        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $naturezas = NaturezaOperacao::
        where('empresa_id', $this->empresa_id)
        ->get();

        $categorias = Categoria::
        where('empresa_id', $this->empresa_id)
        ->get();

        $produtos = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->get();

        $produtosGroup = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->where('valor_venda', '>', 0)
        ->groupBy('referencia_grade')
        ->get();

        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
        ->get();

        $tiposPagamento = VendaCaixa::tiposPagamento();
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $certificado = Certificado::
        where('empresa_id', $this->empresa_id)
        ->first();

        $usuario = Usuario::find(get_id_user());

        if(count($naturezas) == 0 || count($produtos) == 0 || $config == null || count($categorias) == 0 || $tributacao == null){

            return view("frontBox/alerta")
            ->with('produtos', count($produtos))
            ->with('categorias', count($categorias))
            ->with('naturezas', $naturezas)
            ->with('config', $config)
            ->with('tributacao', $tributacao)
            ->with('title', "Validação para Emitir");
        }else{

            if($config->nat_op_padrao == 0){

                session()->flash('mensagem_erro', 'Informe a natureza de operação para o PDV!');
                return redirect('/configNF');
            }else{

                $tiposPagamentoMulti = VendaCaixa::tiposPagamentoMulti();

                $produtos = Produto::
                where('empresa_id', $this->empresa_id)
                ->where('inativo', false)
                ->where('valor_venda', '>', 0)
                ->orderBy('nome')
                ->get();

                foreach($produtos as $p){
                    $p->listaPreco;
                    $estoque_atual = 0;
                    if($p->estoque){
                        if($p->unidade_venda == 'UN' || $p->unidade_venda == 'UNID'){
                            $estoque_atual = number_format($p->estoque->quantidade);
                        }else{
                            $estoque_atual = $p->estoque->quantidade;
                        }
                    }
                    $p->estoque_atual = $estoque_atual;
                    if($p->grade){
                        $p->nome .= " $p->str_grade";
                    }
                }

                foreach($produtosGroup as $p){
                    $p->listaPreco;
                    $estoque_atual = 0;
                    if($p->estoque){
                        if($p->unidade_venda == 'UN' || $p->unidade_venda == 'UNID'){
                            $estoque_atual = number_format($p->estoque->quantidade);
                        }else{
                            $estoque_atual = $p->estoque->quantidade;
                        }
                    }
                    $p->estoque_atual = $estoque_atual;

                }

                $categorias = Categoria::
                where('empresa_id', $this->empresa_id)
                ->orderBy('nome')->get();

                $clientes = Cliente::orderBy('razao_social')
                ->where('empresa_id', $this->empresa_id)
                ->where('inativo', false)
                ->get();

                foreach($clientes as $c){
                    $c->totalEmAberto = 0;
                    $soma = $this->getTotalContaCredito($c);
                    if($soma != null){
                        $c->totalEmAberto = $soma->total;
                    }
                }

                $atalhos = ConfigCaixa::
                where('usuario_id', get_id_user())
                ->first();

                $view = 'main';
                if($atalhos != null && $atalhos->modelo_pdv == 1){
                    $view = 'main2';
                }
                $listas = ListaPreco::where('empresa_id', $this->empresa_id)->get();

                $venda->cliente;
                foreach($venda->itens as $it){
                    $it->produto;
                }

                $rascunhos = $this->getRascunhos();

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

                    // Dados para o modal -> adicionar novo cliente
                $estados = Cliente::estados();
                $cidades = Cidade::all();
                $pais = Pais::all();
                $grupos = GrupoCliente::get();
                $acessores = Acessor::where('empresa_id', $this->empresa_id)->get();
                $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->get();
                    //
                $produtosMaisVendidos = $this->produtosMaisVendidos();
                $abertura = AberturaCaixa::where('empresa_id', $this->empresa_id)
                ->where('usuario_id', get_id_user())
                ->where('status', 0)
                ->orderBy('id', 'desc')
                ->first();

                //$filial = $abertura != null ? $abertura->filial : null;
                $filial = $abertura != null ? $abertura->filial()->first() : null;

                $contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)
                ->where('status', 1)->get();
                return view('frontBox/main3')
                ->with('frenteCaixa', true)
                ->with('is_troca', true)
                ->with('vendedores', $vendedores)
                ->with('contasEmpresa', $contasEmpresa)
                ->with('filial', $filial)
                ->with('produtosMaisVendidos', $produtosMaisVendidos)
                ->with('tiposPagamento', $tiposPagamento)
                ->with('config', $config)
                ->with('rascunhos', $rascunhos)
                ->with('certificado', $certificado)
                ->with('listaPreco', $listas)
                ->with('atalhos', $atalhos)
                ->with('venda', $venda)
                ->with('disableFooter', true)
                ->with('usuario', $usuario)
                ->with('produtos', $produtos)
                ->with('produtosGroup', $produtosGroup)
                ->with('clientes', $clientes)
                ->with('categorias', $categorias)
                ->with('tiposPagamentoMulti', $tiposPagamentoMulti)
                    // para o modal -> adicionar novo cliente
                ->with('pessoaFisicaOuJuridica', true)
                ->with('cidadeJs', true)
                ->with('cidades', $cidades)
                ->with('estados', $estados)
                ->with('acessores', $acessores)
                ->with('funcionarios', $funcionarios)
                ->with('grupos', $grupos)
                ->with('pais', $pais)
                    //
                ->with('title', 'Frente de Caixa - Troca');
            }
        }
    }

    public function preVenda(Request $request){
        $countProdutos = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->count();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $naturezas = NaturezaOperacao::
        where('empresa_id', $this->empresa_id)
        ->get();

        $categorias = Categoria::
        where('empresa_id', $this->empresa_id)
        ->get();

        $produtos = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->get();

        $produtosGroup = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->where('valor_venda', '>', 0)
        ->groupBy('referencia_grade')
        ->get();

        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
        ->get();

        $tiposPagamento = VendaCaixa::tiposPagamento();
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $certificado = Certificado::
        where('empresa_id', $this->empresa_id)
        ->first();

        $usuario = Usuario::find(get_id_user());

        if(count($naturezas) == 0 || count($produtos) == 0 || $config == null || count($categorias) == 0 || $tributacao == null){

            return view("frontBox/alerta")
            ->with('produtos', count($produtos))
            ->with('categorias', count($categorias))
            ->with('naturezas', $naturezas)
            ->with('config', $config)
            ->with('tributacao', $tributacao)
            ->with('title', "Validação para Emitir");
        }else{

            if($config->nat_op_padrao == 0){

                session()->flash('mensagem_erro', 'Informe a natureza de operação para o PDV!');
                return redirect('/configNF');
            }else{

                $tiposPagamentoMulti = VendaCaixa::tiposPagamentoMulti();

                $categorias = Categoria::
                where('empresa_id', $this->empresa_id)
                ->orderBy('nome')->get();

                $clientes = Cliente::orderBy('razao_social')
                ->where('empresa_id', $this->empresa_id)
                ->where('inativo', false)
                ->get();

                foreach($clientes as $c){
                    $c->totalEmAberto = 0;
                    $soma = $this->getTotalContaCredito($c);
                    if($soma != null){
                        $c->totalEmAberto = $soma->total;
                    }
                }

                $atalhos = ConfigCaixa::
                where('usuario_id', get_id_user())
                ->first();

                if($atalhos == null){
                    session()->flash('mensagem_erro', 'Informe a configuração primeiro!');
                    return redirect('/frenteCaixa/config');
                }

                $view = 'main';
                if($atalhos != null && $atalhos->modelo_pdv == 1){
                    $view = 'main2';
                }
                $listas = ListaPreco::where('empresa_id', $this->empresa_id)->get();

                $rascunhos = $this->getRascunhos();

                    // rascunhos de pré-venda do usuário
                $preVendaRascunhos = VendaCaixaPreVenda::where('empresa_id', $this->empresa_id)
                ->where('prevenda_nivel', 1)
                ->limit(100)
                ->orderBy('updated_at', 'desc')
                ->get();

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

                    // Dados para o modal -> adicionar novo cliente
                $estados = Cliente::estados();
                $cidades = Cidade::all();
                $pais = Pais::all();
                $grupos = GrupoCliente::get();
                $acessores = Acessor::where('empresa_id', $this->empresa_id)->get();
                $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->get();
                    //

                $produtosMaisVendidos = $this->produtosMaisVendidos();
                $abertura = AberturaCaixa::where('empresa_id', $this->empresa_id)
                ->where('usuario_id', get_id_user())
                ->where('status', 0)
                ->orderBy('id', 'desc')
                ->first();

                //$filial = $abertura != null ? $abertura->filial : null;
                $filial = $abertura != null ? $abertura->filial()->first() : null;

                $contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)
                ->where('status', 1)->get();
                $viewBlade = view('frontBox/main3')
                ->with('frenteCaixa', true)
                ->with('preVenda', true)
                ->with('preVendaRascunhos', $preVendaRascunhos)
                ->with('produtosMaisVendidos', $produtosMaisVendidos)
                ->with('vendedores', $vendedores)
                ->with('contasEmpresa', $contasEmpresa)
                ->with('tiposPagamento', $tiposPagamento)
                ->with('config', $config)
                ->with('certificado', $certificado)
                ->with('listaPreco', $listas)
                ->with('rascunhos', $rascunhos)
                ->with('consignadas', $this->getConsignadas())
                ->with('atalhos', $atalhos)
                ->with('filial', $filial)
                ->with('disableFooter', true)
                ->with('usuario', $usuario)

                ->with('produtosGroup', $produtosGroup)
                ->with('clientes', $clientes)
                ->with('categorias', $categorias)
                ->with('tiposPagamentoMulti', $tiposPagamentoMulti)
                     // para o modal -> adicionar novo cliente
                ->with('pessoaFisicaOuJuridica', true)
                ->with('cidadeJs', true)
                ->with('cidades', $cidades)
                ->with('estados', $estados)
                ->with('acessores', $acessores)
                ->with('funcionarios', $funcionarios)
                ->with('grupos', $grupos)
                ->with('pais', $pais)
                    //
                ->with('title', 'Pré-venda');

                if(isset($request->id)){
                    $venda = VendaCaixaPreVenda::where('empresa_id', $this->empresa_id)
                    ->where('prevenda_nivel', 1)->find($request->id);
                    $venda->cliente;
                    foreach($venda->itens as $it){
                        $it->produto;
                    }
                    return $viewBlade->with('venda', json_encode($venda));
                }else{
                    return $viewBlade;
                }
            }

        }
    }

    public function preVendaEdit(Request $request){
        $countProdutos = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->count();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $naturezas = NaturezaOperacao::
        where('empresa_id', $this->empresa_id)
        ->get();

        $categorias = Categoria::
        where('empresa_id', $this->empresa_id)
        ->get();

        $produtos = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->get();

        $produtosGroup = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->where('valor_venda', '>', 0)
        ->groupBy('referencia_grade')
        ->get();

        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
        ->get();

        $tiposPagamento = VendaCaixa::tiposPagamento();
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $certificado = Certificado::
        where('empresa_id', $this->empresa_id)
        ->first();

        $usuario = Usuario::find(get_id_user());

        if(count($naturezas) == 0 || count($produtos) == 0 || $config == null || count($categorias) == 0 || $tributacao == null){

            return view("frontBox/alerta")
            ->with('produtos', count($produtos))
            ->with('categorias', count($categorias))
            ->with('naturezas', $naturezas)
            ->with('config', $config)
            ->with('tributacao', $tributacao)
            ->with('title', "Validação para Emitir");
        }else{

            if($config->nat_op_padrao == 0){

                session()->flash('mensagem_erro', 'Informe a natureza de operação para o PDV!');
                return redirect('/configNF');
            }else{

                $tiposPagamentoMulti = VendaCaixa::tiposPagamentoMulti();

                $categorias = Categoria::
                where('empresa_id', $this->empresa_id)
                ->orderBy('nome')->get();

                $clientes = Cliente::orderBy('razao_social')
                ->where('empresa_id', $this->empresa_id)
                ->get();

                foreach($clientes as $c){
                    $c->totalEmAberto = 0;
                    $soma = $this->getTotalContaCredito($c);
                    if($soma != null){
                        $c->totalEmAberto = $soma->total;
                    }
                }

                $atalhos = ConfigCaixa::
                where('usuario_id', get_id_user())
                ->first();

                $view = 'main';
                if($atalhos != null && $atalhos->modelo_pdv == 1){
                    $view = 'main2';
                }
                $listas = ListaPreco::where('empresa_id', $this->empresa_id)->get();

                $rascunhos = $this->getRascunhos();

                    // rascunhos de pré-venda do usuário
                $preVendaRascunhos = VendaCaixaPreVenda::where('empresa_id', $this->empresa_id)
                ->where('prevenda_nivel', 1)
                ->limit(100)
                ->orderBy('updated_at', 'desc')
                ->get();

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

                    // Dados para o modal -> adicionar novo cliente
                $estados = Cliente::estados();
                $cidades = Cidade::all();
                $pais = Pais::all();
                $grupos = GrupoCliente::get();
                $acessores = Acessor::where('empresa_id', $this->empresa_id)->get();
                $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->get();

                $produtosMaisVendidos = $this->produtosMaisVendidos();
                $consignadas = $this->getConsignadas();
                $abertura = AberturaCaixa::where('empresa_id', $this->empresa_id)
                ->where('usuario_id', get_id_user())
                ->where('status', 0)
                ->orderBy('id', 'desc')
                ->first();
                //$filial = $abertura != null ? $abertura->filial : null;
                $filial = $abertura != null ? $abertura->filial()->first() : null;

                $contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)
                ->where('status', 1)->get();

                $viewBlade = view('frontBox/main3')
                ->with('frenteCaixa', true)
                ->with('preVenda', true)
                ->with('preVendaRascunhos', $preVendaRascunhos)
                ->with('vendedores', $vendedores)
                ->with('tiposPagamento', $tiposPagamento)
                ->with('config', $config)
                ->with('filial', $filial)
                ->with('contasEmpresa', $contasEmpresa)
                ->with('certificado', $certificado)
                ->with('listaPreco', $listas)
                ->with('produtosMaisVendidos', $produtosMaisVendidos)
                ->with('rascunhos', $rascunhos)
                ->with('atalhos', $atalhos)
                ->with('disableFooter', true)
                ->with('usuario', $usuario)
                ->with('consignadas', $consignadas)

                ->with('produtosGroup', $produtosGroup)
                ->with('clientes', $clientes)
                ->with('categorias', $categorias)
                ->with('tiposPagamentoMulti', $tiposPagamentoMulti)
                     // para o modal -> adicionar novo cliente
                ->with('pessoaFisicaOuJuridica', true)
                ->with('cidadeJs', true)
                ->with('cidades', $cidades)
                ->with('estados', $estados)
                ->with('acessores', $acessores)
                ->with('funcionarios', $funcionarios)
                ->with('grupos', $grupos)
                ->with('pais', $pais)
                ->with('editPrevenda', 1)
                    //
                ->with('title', 'Pré-venda');

                if(isset($request->id)){
                    $venda = VendaCaixaPreVenda::where('empresa_id', $this->empresa_id)
                    ->find($request->id);
                    $venda->cliente;
                    foreach($venda->itens as $it){
                        $it->produto;
                    }
                    return $viewBlade->with('venda', json_encode($venda));
                }else{
                    return $viewBlade;
                }
            }

        }
    }

    public function print(){
        return view('frontBox/print');
    }

}
