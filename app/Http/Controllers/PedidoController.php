<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\ItemPedido;
use App\Models\ItemPizzaPedidoLocal;
use App\Models\Produto;
use App\Models\TelaPedido;
use App\Models\ProdutoPizza;
use App\Models\ItemPedidoComplementoLocal;
use App\Models\ComplementoDelivery;
use App\Models\CatracaLog;
use App\Models\VendaCaixa;
use App\Models\ConfigNota;
use App\Models\Funcionario;
use App\Models\ContaEmpresa;
use App\Models\BairroDelivery;
use App\Models\PedidoDelete;
use App\Models\TamanhoPizza;
use App\Models\Mesa;
use App\Models\Usuario;
use Comtele\Services\TextMessageService;
use NFePHP\DA\NFe\CupomPedido;
use NFePHP\DA\NFe\Itens;
use NFePHP\DA\NFe\ItensMulti;
use App\Models\Certificado;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\AberturaCaixa;
use App\Models\ConfigCaixa;
use App\Models\ApkComanda;
use App\Models\Acessor;
use App\Models\ItemVendaCaixa;
use App\Models\Cidade;
use App\Models\Pais;
use App\Models\GrupoCliente;

class PedidoController extends Controller{

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
    $pedidos = Pedido::
    where('desativado', false)
    ->where('empresa_id', $this->empresa_id)
    ->get();

    $mesas = Mesa::
    where('empresa_id', $this->empresa_id)
    ->get();
    $mesasParaAtivar = $this->mesasParaAtivar();

    $mesasFechadas = $this->mesasFechadas();

    $clientes = Cliente::
    where('empresa_id', $this->empresa_id)
    ->where('inativo', false)
    ->get();

    $cidades = Cidade::all();

    return view('pedido/list')
    ->with('pedidos', $pedidos)
    ->with('mesas', $mesas)
    ->with('cidades', $cidades)
    ->with('clientes', $clientes)
    ->with('atribuirComandaJs', true)
    ->with('mesasParaAtivar', $mesasParaAtivar)
    ->with('mesasFechadas', $mesasFechadas)
    ->with('title', 'Lista de Comandas');
  }

  public function filtrar(Request $request){
    $pedidos = Pedido::
    where('desativado', false)
    ->select('pedidos.*')
    ->where('pedidos.empresa_id', $this->empresa_id);

    if($request->comanda){
      $pedidos->where('comanda', $request->comanda);
    }

    if($request->nome || $request->cpf_cnpj){
      $pedidos->join('clientes', 'clientes.id', '=', 'pedidos.cliente_id');
    }

    if($request->nome){
      $pedidos->where('razao_social', 'like', "%$request->nome%");
    }

    if($request->cpf_cnpj){
      $pedidos->where('cpf_cnpj', 'like', "%$request->cpf_cnpj%");
    }

    $pedidos = $pedidos->get();

    $mesas = Mesa::
    where('empresa_id', $this->empresa_id)
    ->get();
    $mesasParaAtivar = $this->mesasParaAtivar();

    $mesasFechadas = $this->mesasFechadas();

    $clientes = Cliente::
    where('empresa_id', $this->empresa_id)
    ->where('inativo', false)
    ->get();

    $cidades = Cidade::all();

    return view('pedido/list')
    ->with('pedidos', $pedidos)
    ->with('mesas', $mesas)
    ->with('clientes', $clientes)
    ->with('comanda', $request->comanda)
    ->with('nome', $request->nome)
    ->with('cpf_cnpj', $request->cpf_cnpj)
    ->with('cidades', $cidades)
    ->with('atribuirComandaJs', true)
    ->with('mesasParaAtivar', $mesasParaAtivar)
    ->with('mesasFechadas', $mesasFechadas)
    ->with('title', 'Lista de Pedidos');
  }

  private function mesasParaAtivar(){
    $mesas = Pedido::where('mesa_ativa', false)
    ->where('mesa_id', '!=', null)
    ->where('empresa_id', $this->empresa_id)
    ->get();
    return $mesas;
  }

  private function mesasFechadas(){
    $mesas = Pedido::where('fechar_mesa', true)
    ->where('mesa_id', '!=', null)
    ->where('desativado', false)
    ->where('empresa_id', $this->empresa_id)
    ->get();
    return $mesas;
  }


  public function abrir(Request $request){

    $codComanda = $request->comanda;
    if(!$codComanda){
      $codComanda = rand(50, 1000);
    }
    $comanda = Pedido::
    where('comanda', $codComanda)
    ->where('desativado', false)
    ->where('empresa_id', $this->empresa_id)
    ->first();
    if(empty($comanda)){
      $res = Pedido::create([
        'comanda' => $codComanda,
        'observacao' => $request->observacao ?? '',
        'status' => false,
        'nome' => '',
        'rua' => '',
        'numero' => '',
        'bairro_id' => null,
        'referencia' => '',
        'telefone' => '',
        'desativado' => false,
        'mesa_id' => $request->mesa_id != 'null' ? $request->mesa_id : null,
        'cliente_id' => $request->cliente_id != 'null' ? $request->cliente_id : null,
        'empresa_id' => $this->empresa_id
      ]);
      if($res) {

        session()->flash('mensagem_sucesso', 'Comanda aberta com sucesso!');
        return redirect('/pedidos/ver/'.$res->id);

      }
    }else{

      session()->flash('mensagem_erro', 'Esta comanda encontra-se ativa!');
      return redirect('/pedidos');
    }
  }

  public function setCliente(Request $request){
    $pedido = Pedido::findOrFail($request->pedido_id);
    $pedido->cliente_id = $request->cliente_id;
    $pedido->save();

    session()->flash('mensagem_sucesso', 'Cliente definido!');
    return redirect()->back();
  }

  public function ver($id){
    $pedido = Pedido::findOrFail($id);
    if(valida_objeto($pedido)){
      $bairros = BairroDelivery::
      orderBy('nome')->get();
      
      $produtos = Produto::
      where('empresa_id', $this->empresa_id)
      ->orderBy('nome')->get();
      $tamanhos = TamanhoPizza::where('empresa_id', $this->empresa_id)->get();

      $pizzas = [];

      foreach($produtos as $p){
        if($p->delivery){
          $p->delivery->pizza;

          foreach($p->delivery->pizza as $pz){
            $pz->tamanho;
          }
          if(sizeof($p->delivery->pizza) > 0){
            array_push($pizzas, $p);
          }

        } 
      }

      $adicionais = ComplementoDelivery::
      where('empresa_id', $this->empresa_id)
      ->get();

      $clientes = Cliente::
      where('empresa_id', $this->empresa_id)
      ->where('inativo', false)
      ->get();

      return view('pedido/ver')
      ->with('pedido', $pedido)
      ->with('bairros', $bairros)
      ->with('produtos', $produtos)
      ->with('pizzas', $pizzas)
      ->with('clientes', $clientes)
      ->with('tamanhos', $tamanhos)
      ->with('adicionais', $adicionais)
      ->with('pedidoJs', true)
      ->with('title', 'Comanda '.$id);
    }else{
      return redirect('/403');
    }
  }

  public function alterarStatus($id){
    $item = ItemPedido::
    where('id', $id)
    ->first();

    $item->status = 1;
    $item->save();

    session()->flash('mensagem_sucesso', 'Produto '. $item->produto->nome . ' marcado como concluido!');
    return redirect("/pedidos/ver/".$item->pedido->id);
  }

  public function deleteItem($id){
    $item = ItemPedido::
    where('id', $id)
    ->first();

    PedidoDelete::create(
      [
        'pedido_id' => $item->pedido_id,
        'produto' => $item->nomeDoProduto(),
        'quantidade' => $item->quantidade,
        'valor' => $item->valor,
        'data_insercao' => \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i:s'),
        'empresa_id' => $this->empresa_id
      ]
    );

    if($item->delete()){
      session()->flash('mensagem_sucesso', 'Item removido!');
    }else{
      session()->flash('mensagem_erro', 'Erro');
    }
    return redirect('/pedidos/ver/'.$item->pedido_id);
  }

  public function desativar($id){
    $item = Pedido::
    where('id', $id)
    ->first();

    if(valida_objeto($item)){
      $item->desativado = true;
      $res = $item->save();

      if($res){

        session()->flash('mensagem_sucesso', 'Comanda desativada!');
      }else{

        session()->flash('mensagem_erro', 'Erro');
      }
      return redirect('/pedidos');
    }else{
      return redirect('/403');
    }
  }

  public function emAberto(){
    $pedidos = ItemPedido::where('status', false)
    ->get();

    return response()->json(count($pedidos), 200);
  }


  public function saveItem(Request $request){

    $this->_validateItem($request);
    $pedido = Pedido::
    where('id', $request->id)
    ->first();

    $produto = $request->input('produto');
    $produto = explode("-", $produto);
    $produto = $produto[0];

    if($pedido->cliente){
      $limite_venda = $pedido->cliente->limite_venda;
      if($limite_venda > 0){
        $soma = $pedido->somaItems() + (float)(__replace($request->valor) * __replace($request->quantidade));
        if($soma > $limite_venda){
          session()->flash('mensagem_erro', 'Limite de venda para este cliente é R$ ' . 
            number_format($limite_venda, 2, ',', '.'));
          return redirect()->back();
        }
      }
    }

    $result = ItemPedido::create([
      'pedido_id' => $pedido->id,
      'produto_id' => $produto,
      'quantidade' => str_replace(",", ".", $request->quantidade),
      'status' => $request->status,
      'tamanho_pizza_id' => $request->tamanho_pizza_id ?? NULL,
      'observacao' => $request->observacao ?? '',
      'valor' => str_replace(",", ".", $request->valor),
      'impresso' => false
    ]);

    if($request->tamanho_pizza_id && $request->sabores_escolhidos){
      $saborDup = false;

      $sabores = explode(",", $request->sabores_escolhidos);
      if(count($sabores) > 0){
        foreach($sabores as $sab){
          $prod = Produto
          ::where('id', $sab)
          ->first();

          $item = ItemPizzaPedidoLocal::create([
            'item_pedido' => $result->id,
            'sabor_id' => $prod->delivery->id,
          ]);

          if($prod->id == $produto) $saborDup = true;
        }
      }

      if(!$saborDup){
        $prod = Produto
        ::where('id', $produto)
        ->first();
        $item = ItemPizzaPedidoLocal::create([
          'item_pedido' => $result->id,
          'sabor_id' => $prod->delivery->id,
        ]);
      }
    }else if($request->tamanho_pizza_id){
      $prod = Produto
      ::where('id', $produto)
      ->first();
      $item = ItemPizzaPedidoLocal::create([
        'item_pedido' => $result->id,
        'sabor_id' => $prod->delivery->id,
      ]);
    }

    if($request->adicioanis_escolhidos){
      $adicionais = explode(",", $request->adicioanis_escolhidos);
      foreach($adicionais as $id){
        $id = (int)$id;

        $adicional = ComplementoDelivery
        ::where('id', $id)
        ->first();


        $item = ItemPedidoComplementoLocal::create([
          'item_pedido' => $result->id,
          'complemento_id' => $adicional->id,
          'quantidade' => str_replace(",", ".", $request->quantidade),
        ]);
      }
    }


    if($result){
      session()->flash('mensagem_sucesso', 'Item adicionado!');
    }else{
      session()->flash('mensagem_erro', 'Erro');
    }
    return redirect('/pedidos/ver/'.$pedido->id);
  }

  private function _validateItem(Request $request){
    $validaTamanho = false;
    if($request->input('produto')){
      $produto = $request->input('produto');
      $produto = explode("-", $produto);
      $produto = $produto[0];


      $p = Produto::
      where('id', $produto)
      ->first();

      if($p && strpos(strtolower($p->categoria->nome), 'izza') !== false){
        $validaTamanho = true;
      }

      if($produto == 'null'){
        $request->merge(['produto' => '']);
      }
    }
    $rules = [
      'produto' => 'required',
      'quantidade' => 'required',
      'tamanho_pizza_id' => $validaTamanho ? 'required' : '',
    ];

    $messages = [
      'produto.required' => 'O campo produto é obrigatório.',

      'quantidade.required' => 'O campo quantidade é obrigatório.',
      'tamanho_pizza_id.required' => 'Selecione um tamanho.',
    ];

    $this->validate($request, $rules, $messages);
  }

  public function finalizar($id){
    $pedido = Pedido::
    where('id', $id)
    ->first();

    $atributes = $this->addAtributes($pedido->itens);

    $usuario = Usuario::find(get_id_user());
    $tiposPagamento = VendaCaixa::tiposPagamento();
    $config = ConfigNota::
    where('empresa_id', $this->empresa_id)
    ->first();

    $produtosGroup = Produto::
    where('empresa_id', $this->empresa_id)
    ->where('inativo', false)
    ->where('valor_venda', '>', 0)
    ->groupBy('referencia_grade')
    ->get();

    $certificado = Certificado::
    where('empresa_id', $this->empresa_id)
    ->first();

    $tiposPagamentoMulti = VendaCaixa::tiposPagamentoMulti();
    $produtos = Produto::
    where('empresa_id', $this->empresa_id)
    ->where('inativo', false)
    ->orderBy('nome')->get();
    
    $categorias = Categoria::
    where('empresa_id', $this->empresa_id)
    ->get();

    $clientes = Cliente::where('empresa_id', $this->empresa_id)
    ->where('inativo', false)
    ->orderBy('razao_social')->get();

    $atalhos = ConfigCaixa::
    where('usuario_id', get_id_user())
    ->first();

    $funcionarios = Funcionario::
    where('funcionarios.empresa_id', $this->empresa_id)
    ->select('funcionarios.*')
    ->join('usuarios', 'usuarios.id', '=', 'funcionarios.usuario_id')
    ->get();

    $view = 'main3';
    // if($atalhos != null && $atalhos->modelo_pdv == 1){
    //   $view = 'main2';
    // }

    $rascunhos = $this->getRascunhos();
    $consignadas = $this->getConsignadas();
    $acessores = Acessor::where('empresa_id', $this->empresa_id)->get();
    $produtosMaisVendidos = $this->produtosMaisVendidos();
    $vendedores = [];

    $usuarios = Usuario::where('empresa_id', $this->empresa_id)
    ->where('ativo', 1)
    ->orderBy('nome', 'asc')
    ->get();

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
    $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->get();
    // return view('frontBox/main')
    $abertura = AberturaCaixa::where('empresa_id', $this->empresa_id)
    ->where('usuario_id', get_id_user())
    ->where('status', 0)
    ->orderBy('id', 'desc')
    ->first();
    $filial = $abertura != null ? $abertura->filial : null;

    $contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)
    ->where('status', 1)->get();
    
    return view('frontBox/'.$view)
    ->with('itens', $atributes)
    ->with('atalhos', $atalhos)
    ->with('contasEmpresa', $contasEmpresa)
    ->with('filial', $filial)
    ->with('estados', $estados)
    ->with('cidades', $cidades)
    ->with('pais', $pais)
    ->with('grupos', $grupos)
    ->with('vendedores', $vendedores)
    ->with('usuarios', $usuarios)
    ->with('acessores', $acessores)
    ->with('produtosMaisVendidos', $produtosMaisVendidos)
    ->with('rascunhos', $rascunhos)
    ->with('consignadas', $consignadas)
    ->with('funcionarios', $funcionarios)
    ->with('produtosGroup', $produtosGroup)
    ->with('cod_comanda', $pedido->comanda)
    ->with('frenteCaixa', true)
    ->with('tiposPagamento', $tiposPagamento)
    ->with('tiposPagamentoMulti', $tiposPagamentoMulti)
    ->with('config', $config)
    ->with('usuario', $usuario)
    ->with('clientes', $clientes)
    ->with('produtos', $produtos)
    ->with('categorias', $categorias)
    ->with('certificado', $certificado)
    ->with('bairro', $pedido->bairro)
    ->with('title', 'Finalizar Comanda '.$id);
    
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
    foreach($itens as $i){
      $p = Produto::find($i->produto_id);
      if(!$p->inativo){
        array_push($produtos, $p);
      }
    }
    return $produtos;
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
    ->limit(20)
    ->orderBy('id', 'desc')
    ->get();
  }


  private function addAtributes($itens){
    $temp = [];
    foreach($itens as $i){
      $i->produto;

      if(!empty($i->sabores)){
        $i->sabores;

        $valorAdicional = 0;

        // foreach($i->itensAdicionais as $ad){
        //   $valorAdicional += $ad->adicional->valor;
        // }

        $i->valorAdicional = $valorAdicional;



        $maiorValor = 0;
        $somaValores = 0; 
        foreach($i->sabores as $sb){
          $sb->produto->produto;

          $v = $sb->maiorValor($sb->sabor_id, $i->tamanho_pizza_id);
          $somaValores += $v;
          if($v > $maiorValor) $maiorValor = $v;


        }

        if(env("DIVISAO_VALOR_PIZZA") == 1){
          $divide = sizeof($i->sabores);
          $divide = $divide == 0 ? 1 : $divide; 
          $i->maiorValor = $somaValores/$divide;
        }

      }
      $i->produto->valor_venda = $i->valor/$i->quantidade;

      if($i->maiorValor < $i->valor) $i->maiorValor = $i->valor;
      $i->produto_id = $i->produto->id;
      $i->produto->nome = $i->produto->nome;
      $i->item_pedido = $i->id;
      $i->imagem = $i->produto->imagem;
      array_push($temp, $i);
    }
        // echo json_encode($temp);
    return $temp;
  }

  public function itensPendentes(){
    $itens = ItemPedido::
    where('status', false)
    ->get();

    echo json_encode(count($itens));
  }

  public function sms(Request $request){
    $data = $request->data;
    $phone = $data['numero'];
    $msg = $data['msg'];
    $res = $this->sendSms($phone, $msg);
    echo json_encode($res);
  }

  private function sendSms($phone, $msg){
    $nomeEmpresa = env('SMS_NOME_EMPRESA');
    $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
    $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
    $content = $msg;
    $textMessageService = new TextMessageService(env('SMS_KEY'));
    $res = $textMessageService->send("Sender", $content, [$phone]);
    return $res;
  }

  public function imprimirPedido($id){
    $pedido = Pedido::
    where('id', $id)
    ->where('empresa_id', $this->empresa_id)
    ->first();
    if(valida_objeto($pedido)){
      $public = env('SERVIDOR_WEB') ? 'public/' : '';
      $pathLogo = $public.'imgs/logo.jpg';

      $config = ConfigNota::
      where('empresa_id', $this->empresa_id)
      ->first();

      if($config->logo){
        $public = env('SERVIDOR_WEB') ? 'public/' : '';
        $pathLogo = $public.'logos/' . $config->logo;
      }

      $cupom = new CupomPedido($pedido, $pathLogo);
      $cupom->monta();
      $pdf = $cupom->render();
  // file_put_contents($public.'pdf/CUPOM_PEDIDO.pdf',$pdf);
  // return redirect($public.'pdf/CUPOM_PEDIDO.pdf');

      return response($pdf)
      ->header('Content-Type', 'application/pdf');
    }else{
      return redirect('/403');
    }
  }

  public function itensParaFrenteCaixa(Request $request){
    $cod = $request->cod;

    $pedido = Pedido::
    where('comanda', $cod)
    ->where('status', 0)
    ->where('empresa_id', $this->empresa_id)
    ->where('desativado', 0)
    ->first();

    if($pedido == null) return response()->json("Nao existe", 401);

    $atributes = $this->addAtributes($pedido->itens);
    return response()->json($atributes, 200);
  }

  public function setarBairro(Request $request){
    $pedido = Pedido::find($request->pedido_id);
    if(valida_objeto($resp)){
      $pedido->bairro_id = $request->bairro_id;
      $res = $pedido->save();
      return response()->json($res, 200);
    }else{
      return redirect('/403');
    }
  }

  public function setarEndereco(Request $request){
    $pedido = Pedido::find($request->pedido_id);
    if(valida_objeto($resp)){
      $pedido->nome = $request->nome;
      $pedido->rua = $request->rua;
      $pedido->numero = $request->numero;
      $pedido->telefone = $request->telefone;
      $pedido->referencia = $request->referencia;
      $res = $pedido->save();


      session()->flash('mensagem_sucesso', 'Endereço setado!');
      return redirect('/pedidos/ver/'.$request->pedido_id);
    }else{
      return redirect('/403');
    }
  }

  public function imprimirItens(Request $request){
    $ids = $request->ids;
    $ids = explode(",", $ids);
    $itens = [];

    foreach($ids as $i){
      if($i != null){
        $item = ItemPedido::find($i);
        $item->impresso = true;
        $item->save();
        array_push($itens, $item);
      }
    }
    if(sizeof($itens) > 0){

      $telas = TelaPedido::where('empresa_id', $this->empresa_id)
      ->get();

      $printers = [];

      $printers[] = [
        'tela' => 'todos',
        'produtos' => []
      ];

      foreach($telas as $tela){

        $printers[] = [
          'tela' => $tela->nome,
          'produtos' => [],
        ];
      }

      $pedido = null;
      $sizePaper = 1;
      foreach($itens as $item){
        $pedido = $item->pedido;
        if($item->produto->tela){
          for($i=0;$i<=sizeof($telas);$i++){
            if($printers[$i]['tela'] == $item->produto->tela->nome){
              $printers[$i]['produtos'][] = $item;
            }
          }

        }else{
          $printers[0]['produtos'][] = $item;
        }
      }

      foreach($printers as $p){
        if(sizeof($p['produtos']) > $sizePaper){
          $sizePaper = sizeof($p['produtos']);
        }
      }

      $pathLogo = '';
      if(sizeof($telas) > 0){
        $cupom = new ItensMulti($printers, $pedido, $sizePaper);
      }else{
        $cupom = new Itens($itens, $pathLogo);
      }

      $pdf = $cupom->render();
      return response($pdf)
      ->header('Content-Type', 'application/pdf');
    }else{
      echo "Selecione ao menos um item!";
    }

  // header('Content-Type: application/pdf');
  // echo $pdf;



  }

  public function controleComandas(){
    $comandas = Pedido::
    limit(30)
    ->where('empresa_id', $this->empresa_id)
    ->orderBy('id', 'desc')
    ->get();
    return view('pedido/controle_comandas')
    ->with('comandas', $comandas)
    ->with('mensagem', '*Listando os 30 ultimos registros')
    ->with('title', 'Controle de Comandas');
  }

  public function verDetalhes($id){
    $pedido = Pedido::find($id);
    $removidos = PedidoDelete::where('pedido_id', $id)->where('empresa_id', $this->empresa_id)->get();

    return view('pedido/detalhes')
    ->with('pedido', $pedido)
    ->with('removidos', $removidos)
    ->with('title', 'Detalhes comanda ' . $pedido->comanda);
  }

  public function filtroComanda(Request $request){
    if($request->data_inicial == null || $request->data_final == null){
      return redirect()->back();
    }

    $data_inicial = $this->parseDate($request->data_inicial);
    $data_final = $this->parseDate($request->data_final, true);
    $numero_comanda = $request->numero_comanda;

    if($numero_comanda != null){
      $comandas = Pedido::
      whereBetween('created_at', [$data_inicial, 
        $data_final])
      ->where('empresa_id', $this->empresa_id)
      ->where('comanda', $numero_comanda)
      ->get();
    }else{
      $comandas = Pedido::
      whereBetween('created_at', [$data_inicial, 
        $data_final])
      ->where('empresa_id', $this->empresa_id)
      ->get();
    }

    return view('pedido/controle_comandas')
    ->with('comandas', $comandas)
    ->with('mensagem', '*Listando os resultados do filtro')
    ->with('title', 'Controle de Comandas');
  }

  private function parseDate($date, $plusDay = false){
    if($plusDay == false)
      return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
    else
      return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
  }

  public function mesas(){
    $pedidos = Pedido::
    where('desativado', false)
    ->where('mesa_id', '!=', null)
    ->where('empresa_id', $this->empresa_id)
    ->groupBy('mesa_id')
    ->get();
    return view('pedido/mesas')
    ->with('pedidos', $pedidos)
    ->with('title', 'Mesas em aberto');
  }

  public function verMesa($mesa_id){
    $mesa = Mesa::find($mesa_id);
    $pedidos = Pedido::
    where('mesa_id', $mesa_id)
    ->where('desativado', false)
    ->where('empresa_id', $this->empresa_id)
    ->where('status', false)
    ->get();
    return view('pedido/verMesa')
    ->with('mesa', $mesa)
    ->with('pedidos', $pedidos)
    ->with('title', 'Comandas da Mesa');
  }

  public function ativarMesa($id){
    $pedido = Pedido::find($id);
    if(valida_objeto($resp)){
      $pedido->mesa_ativa = true;
      $pedido->Save();

      session()->flash('mensagem_sucesso', 'Mesa ativada com sucesso!');

      return redirect('/pedidos');
    }else{
      return redirecT('/403');
    }
  }

  public function atribuirComanda(Request $request){

    $pedido = Pedido::find($request->pedido_id);
    if(valida_objeto($resp)){
      $pedido->observacao = $request->observacao ?? '';
      if(!$request->comanda){
        session()->flash('mensagem_erro', 'Informe a comanda!');
        return redirect()->back();
      }
      $pedido->comanda = $request->comanda;

      $pedido->save();

      session()->flash('mensagem_sucesso', 'Comanda atribuida a ' . $pedido->mesa->nome . '!');

      return redirect('/pedidos');
    }else{
      return redirect('/403');
    }

  }

  public function atribuirMesa(Request $request){
    $pedido = Pedido::find($request->pedido_id);
    $pedido->mesa_id = $request->mesa;

    $pedido->save();
    session()->flash('mensagem_sucesso', 'Mesa atribuida a comanda ' . $pedido->comanda . '!');
    return redirect('/pedidos');
  }

  public function saveCliente(Request $request){
    $data = [
      'razao_social' => $request->nome,
      'cpf_cnpj' => $request->cpf_cnpj ?? '',
      'telefone' => $request->telefone ?? '',
      'celular' => $request->celular ?? '',
      'limite_venda' => __replace($request->limite_venda),
      'cidade_id' => 1,
      'empresa_id' => $this->empresa_id,
      'email' => '',
      'rua' => $request->rua ?? '',
      'numero' => $request->numero ?? '',
      'bairro' => $request->bairro ?? '',
      'cep' => $request->cep ?? '',
      'cidade_id' => $request->cidade_id ? $request->cidade_id : 1,
      'complemento' => $request->complemento ?? '',
    ];

    $open = $request->open;
    $cliente = null;
    try{
      $cliente = Cliente::create($data);
      session()->flash('cliente_session', $cliente->id);
      session()->flash('mensagem_sucesso', 'Cliente cadastrado!');

    }catch(\Exception $e){
      // echo $e->getMessage();
      session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
      // die;
    }

    if($open == 0){
      $cliente = null;
    }
    return redirect()->back();
  }

  public function upload(){
    $config = ApkComanda
    ::where('empresa_id', $this->empresa_id)
    ->first();
    $title = 'Upload de APK comanda';

    $rotaDownload = "";
    if($config != null){
      if(file_exists(public_path('apks/').$config->nome_arquivo)){
        $rotaDownload = env('PATH_URL') . '/pedidos/download';
      }
    }

    $rotaDownloadGenerico = "";
    if(file_exists(public_path('apks/app.apk'))){
      $rotaDownloadGenerico = env('PATH_URL') . '/pedidos/download_generic';
    }

    return view('pedido/upload', compact('config', 'title', 'rotaDownload', 'rotaDownloadGenerico'));
  }

  public function apkUpload(Request $request){
    $config = ApkComanda
    ::where('empresa_id', $this->empresa_id)
    ->first();
    try{

      if(!is_dir(public_path('apks'))){
        mkdir(public_path('apks'), 0777, true);
      }

      $file = $request->file('file');

      $extensao = $file->getClientOriginalExtension();
      $fileName = "controle_comandas_" . date('Y-m-d H:i') . "." .$extensao;
      $upload = $file->move(public_path('apks'), $fileName);

      if($config == null){
        ApkComanda::create([
          'nome_arquivo' => $fileName,
          'empresa_id' => $this->empresa_id
        ]);
      }else{

        if(file_exists(public_path('apks/').$config->nome_arquivo)){
          unlink(public_path('apks/').$config->nome_arquivo);
        }
        $config->nome_arquivo = $fileName;
        $config->save();
      }

      session()->flash('mensagem_sucesso', 'Upload realizado com sucesso!!');


    }catch(\Exception $e){
      session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());

    }
    return redirect()->back();
  }

  public function download(){
    $config = ApkComanda
    ::where('empresa_id', $this->empresa_id)
    ->first();
    $title = 'Upload de APK comanda';


    if($config != null){
      if(file_exists(public_path('apks/').$config->nome_arquivo)){
        // return response()->download(public_path('apks/').$config->nome_arquivo);

        return response()->file(public_path('apks/').$config->nome_arquivo ,[
          'Content-Type'=>'application/vnd.android.package-archive',
          'Content-Disposition'=> 'attachment; filename="app.apk"',
        ]) ;
      }else{
        echo "Nenhum arquivo encontrado!";
      }
    }else{
      echo "Nenhum arquivo encontrado!";
    }
  }

  public function download_generic(){
    try{
      // return response()->download(public_path('apks/app'));
      return response()->file(public_path('apks/app.apk') ,[
        'Content-Type'=>'application/vnd.android.package-archive',
        'Content-Disposition'=> 'attachment; filename="app.apk"',
      ]) ;
    }catch(\Exception $e){
      echo $e->getMessage();
    }

  }

  public function getComandasNovas(Request $request){
    try{
      $item = Pedido::where('pedidos.empresa_id', $this->empresa_id)
      ->select('pedidos.*')
      ->where('pedidos.status', 0)
      ->where('pedidos.desativado', 0)
      ->where('pedidos.catraca_aberta', 0)
      ->join('item_pedidos', 'item_pedidos.pedido_id', '=', 'pedidos.id')
      ->first();

      $xml = "";
      if($item != null){
        $item->catraca_aberta = 1;
        $item->save();
        CatracaLog::create([
          'empresa_id' => $this->empresa_id,
          'comanda' => $item->comanda,
          'tipo' => 'C'
        ]);
        return view('catraca_config.xml_close', compact('item'));
        
      }

      return response()->json($xml, 200);
    }catch(\Exception $e){
      return response()->json($e->getMessage(), 403);
    }
  }

  public function getComandasFechadas(Request $request){
    try{
      $item = Pedido::where('pedidos.empresa_id', $this->empresa_id)
      ->select('pedidos.*')
      // ->where('pedidos.status', 1)
      ->where('pedidos.desativado', 1)
      ->where('pedidos.catraca_fechada', 0)
      ->join('item_pedidos', 'item_pedidos.pedido_id', '=', 'pedidos.id')
      ->first();
      // return "2";
      $xml = "";
      if($item != null){
        $item->catraca_fechada = 1;
        $item->save();
        CatracaLog::create([
          'empresa_id' => $this->empresa_id,
          'comanda' => $item->comanda,
          'tipo' => 'L'
        ]);
        return view('catraca_config.xml_open', compact('item'));

      }

      return response()->json($xml, 200);
    }catch(\Exception $e){
      return response()->json($e->getMessage(), 403);
    }
  }

  public function getMesas(){
    $data = Mesa::where('empresa_id', $this->empresa_id)
    ->get();

    foreach($data as $m){
      $m->pedido = Pedido::where('empresa_id', $this->empresa_id)
      ->where('status', 0)
      ->where('desativado', 0)
      ->where('mesa_id', $m->id)
      ->first();
    }

    return view('frontBox.partials.comandas', compact('data'));

    return response()->json($data, 200);

  }

}
