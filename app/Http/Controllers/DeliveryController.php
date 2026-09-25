<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoriaProdutoDelivery;
use App\Models\ProdutoDelivery;
use App\Models\ClienteDelivery;
use Mail;
use App\Models\DeliveryConfig;
use App\Models\ProdutoPizza;
use App\Models\TamanhoPizza;
use App\Models\TokenWeb;
use Comtele\Services\TextMessageService;
use App\Rules\CelularDup;
use App\Rules\EmailDup;
use App\Models\ItemPedidoDelivery;
use App\Models\EnderecoDelivery;
use App\Models\PedidoDelivery;
use App\Models\BairroDelivery;
use App\Models\CategoriaMasterDelivery;
use App\Models\FuncionamentoDelivery;

class DeliveryController extends Controller
{
    protected $config = null;
    protected $empresa_id = null;

    public function __construct(){
        $this->middleware(function ($request, $next) {
            // Preserva os outros usos históricos deste controller (mesa/rota de entrega).
            if (!$request->is('cardapio', 'cardapio/*', 'pizza/*', 'autenticar', 'autenticar/*', 'info', 'info/*')) {
                $this->config = DeliveryConfig::first();
                return $next($request);
            }

            $this->empresa_id = session('empresa_id');
            $this->config = $this->empresa_id
                ? DeliveryConfig::where('empresa_id', $this->empresa_id)->where('status', 1)
                    ->whereHas('empresa', function ($query) { $query->where('status', 1); })->first()
                : null;

            if (!$this->config) {
                return response('Cardápio indisponível. Acesse pelo link da empresa.', 404);
            }

            // Uma sessão de cliente de outra empresa não pode ser reutilizada.
            if (session('cliente_log') && !ClienteDelivery::where('id', session('cliente_log.id'))
                ->where('empresa_id', $this->empresa_id)->where('ativo', 1)->exists()) {
                session()->forget(['cliente_log', 'telefone_cliente', 'ultimo_pedido_id']);
            }
            return $next($request);
        });
    }

    public function index(){
        $deliveryAtivo = env('DELIVERY');

        if($deliveryAtivo == 0){
            return redirect('/login');
        }

        $clienteLog = session('cliente_log');
        session()->forget('tamanho_pizza');
        session()->forget('sabores');
        $categorias = CategoriaMasterDelivery::all();
        $destaques = ProdutoDelivery::where('destaque', true)->where('status', true)->get();

        $dataHoje = date('Y-m-d');
        $categoriasMaster = CategoriaMasterDelivery::all();

        return view('multi_delivery.index')
        ->with('categorias', $categorias)
        ->with('categoriasMaster', $categoriasMaster)
        ->with('destaques', $destaques)
        ->with('config', $this->config)
        ->with('tokenJs', true)
        ->with('cliente_logado', $clienteLog['nome'] ?? '')
        ->with('title', 'INICIO');
    }

    public function cardapio(){
        $funcionamento = $this->funcionamento();
        
        if(!$funcionamento['status']){
            if($funcionamento['funcionamento'] != null){
                session()->flash("message_erro", "Delivery das " .$funcionamento['funcionamento']->inicio_expediente. " às ".$funcionamento['funcionamento']->fim_expediente);
            }else{
                session()->flash("message_erro", "Não haverá delivery no dia de hoje!");
            }
            return response(session('message_erro'), 409, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        session()->forget('tamanho_pizza');
        session()->forget('sabores');
        
        $categorias = \App\Models\CategoriaProdutoDelivery::where('empresa_id', $this->empresa_id)->get();
        $destaques = \App\Models\ProdutoDelivery::where('empresa_id', $this->empresa_id)->where('destaque', true)->where('status', true)->get();

        return view('delivery.index')
        ->with('categorias', $categorias)
        ->with('destaques', $destaques)
        ->with('config', $this->config)
        ->with('tokenJs', true)
        ->with('title', 'CARDÁPIO');
    }

    public function produtos($id){
        $funcionamento = $this->funcionamento();
        if(!$funcionamento['status']){
            if($funcionamento['funcionamento'] != null){
                session()->flash("message_erro", "Delivery das " .$funcionamento['funcionamento']->inicio_expediente. " às ".$funcionamento['funcionamento']->fim_expediente);
            }else{
                session()->flash("message_erro", "Não haverá delivery no dia de hoje!");
            }
            return redirect('/cardapio');
        }

        // Acesso direto à categoria
        $categoria = CategoriaProdutoDelivery::where('empresa_id', $this->empresa_id)->where('id', $id)->firstOrFail();

        if(strpos(strtolower($categoria->nome), 'izza') !== false){
            $tamanhos = TamanhoPizza::where('empresa_id', session('empresa_id'))->get();
            return view('delivery.tipoPizza')
            ->with('tamanhos', $tamanhos)
            ->with('config', $this->config)
            ->with('categoria', $categoria)
            ->with('title', 'TIPO DA PIZZA');
        }else{
            return view('delivery.produtos')
            ->with('produtos', $categoria->produtos)
            ->with('categoria', $categoria)
            ->with('config', $this->config)
            ->with('title', 'PRODUTOS');
        }
    }

    public function verProduto($id){
        $produto = ProdutoDelivery::where('empresa_id', $this->empresa_id)->where('status', 1)->where('id', $id)->firstOrFail();

        return view('delivery/ver_produto')
        ->with('produto', $produto)
        ->with('config', $this->config)
        ->with('title', 'ADICIONAR');
    }

    public function escolherSabores(Request $request){
        if($request->tipo){
            $tipo = $request->tipo;
            $tamanho = explode("-", $tipo)[0];
            $auxSabor = $sabores = explode("-", $tipo)[1];
            $categoria = $request->categoria;

            $session = [
                'tamanho' => $tamanho,
                'sabores' => $sabores
            ];

            session(['tamanho_pizza' => $session]);

            $t = TamanhoPizza::where('empresa_id', $this->empresa_id)->where('nome', $tamanho)->firstOrFail();
            $tamanho = session('tamanho_pizza');
            $sabores = session('sabores');

            if(empty($sabores) && $request->produto > 0){
                $session = [
                    $request->produto,
                ];
                session(['sabores' => $session]);
                $sabores = session('sabores');
                if($auxSabor == 1){
                    // ATENÇÃO: Verifique se essa rota está liberada no web.php
                    return redirect('/pizza/adicionais'); 
                }
            }

            $saboresIncluidos = [];
            $somaValores = 0;
            $valorPizza = 0;
            $maiorValor = 0;

            if($sabores){
                foreach($sabores as $s){
                    $p = ProdutoDelivery::where('empresa_id', $this->empresa_id)->where('status', 1)->where('id', $s)->firstOrFail();
                    $p->produto;
                    $p->galeria;

                    foreach($p->pizza as $pz){
                        if($tamanho['tamanho'] == $pz->tamanho->nome){
                            $valor = $pz->valor;
                        }
                    }
                    $somaValores += $p->valorPizza = $valor;
                    if($valor > $maiorValor) $maiorValor = $valor;

                    array_push($saboresIncluidos, $p);
                }
            }

            if(env("DIVISAO_VALOR_PIZZA") == 1 && is_array($sabores) && sizeof($sabores) > 0){
                $valorPizza = $somaValores/sizeof($sabores);
            }else{
                $valorPizza = $maiorValor;
            }

            return view('delivery/pizzas')
            ->with('pizzas', $t->produtoPizza)
            ->with('config', $this->config)
            ->with('pizzaJs', true)
            ->with('categoria', $categoria)
            ->with('valorPizza', $valorPizza)
            ->with('saboresIncluidos', $saboresIncluidos)
            ->with('title', 'PIZZAS');
        }else{
            session()->flash("message_erro", "Escolha um sabor");
            return back()->withInput();
        }
    }

    public function pesquisa(Request $request){
        $pesquisa = $request->input('pesquisa');
        $tamanho = session('tamanho_pizza');
        $produtos = ProdutoPizza::
        select('produto_pizzas.*')
        ->join('produto_deliveries', 'produto_pizzas.produto_id', '=', 'produto_deliveries.id')
        ->join('tamanho_pizzas', 'produto_pizzas.tamanho_id', '=', 'tamanho_pizzas.id')
        ->join('produtos', 'produtos.id', '=', 'produto_deliveries.produto_id')
        ->where('produto_deliveries.empresa_id', $this->empresa_id)
        ->where('produtos.nome', 'LIKE', "%$pesquisa%")
        ->where('tamanho_pizzas.nome', $tamanho['tamanho'])
        ->get();

        $sabores = session('sabores');
        $saboresIncluidos = [];
        $somaValores = 0;
        $valorPizza = 0;
        $maiorValor = 0;
        if($sabores){
            foreach($sabores as $s){
                $p = ProdutoDelivery::where('empresa_id', $this->empresa_id)->where('status', 1)->where('id', $s)->firstOrFail();
                $p->produto;
                $p->galeria;
                $valor = 0;

                foreach($p->pizza as $pz){
                    if($tamanho['tamanho'] == $pz->tamanho->nome){
                        $valor = $pz->valor;
                    }
                }
                $somaValores += $p->valorPizza = $valor;
                $p->valorPizza = $valor;

                array_push($saboresIncluidos, $p);
            }
        }
        if(env("DIVISAO_VALOR_PIZZA") == 1 && sizeof($sabores) > 0){
            $valorPizza = $somaValores/sizeof($sabores);
        }else{
            $valorPizza = $maiorValor;
        }

        $link = $request->link;
        return view('delivery/pizzas')
        ->with('pizzas', $produtos)
        ->with('config', $this->config)
        ->with('pizzaJs', true)
        ->with('pesquisa', true)
        ->with('link', $link)
        ->with('valorPizza', $valorPizza)
        ->with('saboresIncluidos', $saboresIncluidos)
        ->with('title', 'PIZZAS');
    }

    public function adicionais(){
        $sabores = session('sabores');
        $tamanho = session('tamanho_pizza');
        $saboresIncluidos = [];
        $tamanhoId = 0;

        $maiorValor = 0;
        $somaValores = 0;
        if($sabores){
            foreach($sabores as $s){
                $p = ProdutoDelivery::
                select('produto_deliveries.*')
                ->join('produto_pizzas', 'produto_pizzas.produto_id', '=', 'produto_deliveries.id')
                ->join('tamanho_pizzas', 'produto_pizzas.tamanho_id', '=', 'tamanho_pizzas.id')
                ->where('produto_deliveries.empresa_id', $this->empresa_id)
                ->where('produto_deliveries.id', $s)
                ->where('tamanho_pizzas.nome', $tamanho['tamanho'])
                ->first();

                if($p){
                    $p->produto;
                    $p->galeria;
                    array_push($saboresIncluidos, $p);

                    foreach($p->pizza as $t){
                        if($t->tamanho->nome == $tamanho['tamanho']){
                            $tamanhoId = $t->tamanho->id;
                            $somaValores += $t->valor;
                            if($t->valor > $maiorValor){
                                $maiorValor = $t->valor;
                            }
                        }
                    }
                }
            }
            if(env("DIVISAO_VALOR_PIZZA") == 1 && sizeof($sabores) > 0){
                $maiorValor = number_format(($somaValores/sizeof($sabores)),2);
            }
        }

        $produto = $saboresIncluidos[0] ?? null;
        if(!$produto) return redirect('/cardapio');

        $add = $produto->categoria->adicionais;
        $tamanhoStr = substr($tamanho['tamanho'], 0, 1);

        $adicionais = [];

        foreach($add as $a){
            $nome = $a->complemento->nome;
            $ex = explode('>', $nome);

            if(sizeof($ex) > 1){
                if(strtolower($ex[0]) == strtolower($tamanhoStr)){
                    array_push($adicionais, $a);
                }
            }else{
                array_push($adicionais, $a);
            }
        }

        return view('delivery/adicionalPizza')
        ->with('maiorValor', $maiorValor)
        ->with('saboresIncluidos', $saboresIncluidos)
        ->with('acompanhamentoPizza', true)
        ->with('sabores', $sabores)
        ->with('tamanho', $tamanhoId)
        ->with('adicionais', $adicionais)
        ->with('config', $this->config)
        ->with('title', 'Adicionais para Pizza');
    }
  
    public function pizzas(Request $request){
        $categorias = CategoriaProdutoDelivery::where('nome', 'like', '%izza%')
            ->when($this->empresa_id, function ($query) { $query->where('empresa_id', $this->empresa_id); })->get();
        $produtos = [];
        foreach($categorias as $categoria){
            foreach($categoria->produtos as $p){
                if($p->produto->delivery){
                    $p->produto->delivery->galeria;
                    foreach($p->produto->delivery->pizza as $pp){
                        if($request->tamanho == $pp->tamanho_id){
                           $p->tamanhoValor = $pp->valor;
                       }
                   }
               } else{
                 $p->produto;
                 $p->tamanhoValor = 0;
             }
             array_push($produtos, $p);
         }
     }
     echo json_encode($produtos);
 }

 public function adicionarSabor(Request $request){
    ProdutoDelivery::where('empresa_id', $this->empresa_id)->where('status', 1)->findOrFail($request->pizza_id);
    $sabores = session('sabores');
    if($sabores){
        array_push($sabores, $request->pizza_id);
        session(['sabores' => $sabores]);
    }else{
        $session = [
            $request->pizza_id,
        ];
        session(['sabores' => $session]);
    }
    $link = (string)$request->link;
    // O retorno continua aceitando URLs da própria instalação, sem sair do delivery.
    $partesLink = parse_url($link);
    if ($partesLink !== false && (!isset($partesLink['host']) ||
        ($partesLink['host'] === request()->getHost() && ($partesLink['scheme'] ?? '') === request()->getScheme()))) {
        $link = ($partesLink['path'] ?? '') . (isset($partesLink['query']) ? '?' . $partesLink['query'] : '');
    } else {
        $link = '';
    }
    session()->flash("message_sucesso", "Sabor adicionado!!");
    if(preg_match('#^/(pizza|cardapio)(/|\\?|$)#', $link))
        return redirect($link);
    else
        return redirect('/cardapio');
}

public function removeSabor($id){
    $sabores = session('sabores');
    $temp = [];
    if($sabores){
        foreach($sabores as $s){
            if($s != $id){
                array_push($temp, $s);
            }
        }
        session(['sabores' => $temp]);
    }
    return redirect()->back();
}

public function verificaPizzaAdicionada(Request $request){
    $sabores = session('sabores');
    if($sabores){
        foreach($sabores as $s){
            if(in_array($request->pizza_id, $sabores)){
                return json_encode(true);
            }
        }
        return json_encode(false);
    }else{
        return json_encode(false);
    }
}

public function acompanhamento($id){
        $produto = ProdutoDelivery::where('empresa_id', $this->empresa_id)->where('status', 1)->where('id', $id)->firstOrFail();

        $funcionamento = $this->funcionamento();
        if(!$funcionamento['status']){
            if($funcionamento['funcionamento'] != null){
                session()->flash("message_erro", "Delivery das " .$funcionamento['funcionamento']->inicio_expediente. " às ".$funcionamento['funcionamento']->fim_expediente);
            }else{
                session()->flash("message_erro", "Não haverá delivery no dia de hoje!");
            }
            return redirect('/cardapio');
        }

        if(strpos(strtolower($produto->categoria->nome), 'izza') !== false){
            $tamanhos = TamanhoPizza::where('empresa_id', session('empresa_id'))->get();

            return view('delivery/tipoPizza')
            ->with('tamanhos', $tamanhos)
            ->with('config', $this->config)
            ->with('produto', $produto)
            ->with('categoria', $produto->categoria)
            ->with('title', 'TIPO DA PIZZA');
        }else{
            return view('delivery/acompanhamentos')
            ->with('produto', $produto)
            ->with('acompanhamento', true)
            ->with('adicionais', $produto->categoria->adicionais)
            ->with('config', $this->config)
            ->with('title', 'ACOMPANHAMENTO');
        }
    }

public function login(){
    return view('delivery/login')->with('nome_empresa', $this->empresa_id)
    ->with('config', $this->config)
    ->with('tokenJs', true)
    ->with('title', 'AUTENTICAR');
}

private function setaMascaraPhone($phone){
    $n = substr($phone, 0, 2) . " ";
    $n .= substr($phone, 2, 5)."-";
    $n .= substr($phone, 7, 4);
    return $n;
}

public function autenticar(Request $request){
    $mailPhone = $request->mail_phone;
    $mailPhone = str_replace(" ", "", $mailPhone);
    $senha = md5($request->senha);
    $cliente = null;
    if(is_numeric($mailPhone)){
        if(strlen($mailPhone) != 11){
            session()->flash('message_erro_telefone', 'Digite o telefone seguindo este padrao de exemplo 43999998888 - 11 Digitos.');
            return redirect("/autenticar");
        }
        $cliente = ClienteDelivery::where('celular', $this->setaMascaraPhone($mailPhone))
        ->where('empresa_id', $this->empresa_id)
        ->where('senha', $senha)
        ->first();
    }else{
        $cliente = ClienteDelivery::where('email', $mailPhone)
        ->where('empresa_id', $this->empresa_id)
        ->where('senha', $senha)
        ->first();
    }
    if($cliente == null){
        session()->flash('message_erro', 'Credenciais inválidas.');
        return redirect('/autenticar');
    }else{
        if(env("AUTENTICACAO_SMS") == 0 && env("AUTENTICACAO_EMAIL") == 0){
            $cliente->ativo = 1;
            $cliente->save();
            $session = [
                'id' => $cliente->id,
                'nome' => $cliente->nome,
            ];
            session(['cliente_log' => $session]);
            session()->flash("message_sucesso", "Bem vindo ". $cliente->nome);
            return redirect('/cardapio');
        }
        if($cliente->ativo == 0){
            $celular = $cliente->celular;
            $celular = str_replace("-", "", $celular);
            $celular = str_replace(" ", "", $celular);
            if(env("AUTENTICACAO_SMS") == 1) $this->sendSms($celular, $cliente->token);
            if(env("AUTENTICACAO_EMAIL") == 1) $this->sendEmailLink($cliente->email, $cliente->token);
            return view('delivery/ativar')
            ->with('config', $this->config)
            ->with('cliente', $cliente)
            ->with('login_ative', true)
            ->with('title', 'ATIVAR CADASTRO');
        }else{
            $session = [
                'id' => $cliente->id,
                'nome' => $cliente->nome,
            ];
            session(['cliente_log' => $session]);
            session()->flash("message_sucesso", "Bem vindo ". $cliente->nome);
            return redirect('/cardapio');
        }
    }
}

public function refreshToken(Request $request){
    $cliente = ClienteDelivery::where('empresa_id', $this->empresa_id)->where('id', $request->id)->firstOrFail();
    $cod = rand(100000, 888888);
    $celular = $cliente->celular;
    $celular = str_replace(" ", "", $celular);
    $celular = str_replace("-", "", $celular);
    if(env("AUTENTICACAO_SMS") == 1) $this->sendSms($celular, $cod);
    if(env("AUTENTICACAO_EMAIL") == 1 && env("SERVIDOR_WEB_TYPE") == 1) $this->sendEmailLink($cliente->email, $cod);
    $cliente->token = $cod;
    if($cliente->save())
        return response()->json($cliente, 200);
    else
        return response()->json(false, 204);
}

public function logoff(){
    session()->forget(['cliente_log', 'telefone_cliente', 'ultimo_pedido_id']);
    session()->flash('message_erro', 'Logoff realizado.');
    return redirect("/autenticar");
}

public function registro(){
    $clienteLog = session('cliente_log');
    if(!$clienteLog){
        return view('delivery/registro')
        ->with('config', $this->config)
        ->with('title', 'REGISTRAR-SE');
    }else{
        session()->flash("message_sucesso", "Voce já esta logado ".$clienteLog['nome']);
        return redirect('/cardapio');
    }
}

public function salvarRegistro(Request $request){
    $this->_validate($request);
    $cod = rand(100000, 888888);
    $request->merge([ 'senha' => md5($request->senha)]);
    $request->merge([ 'ativo' => false]);
    $request->merge([ 'token' => $cod]);
    $request->merge(['empresa_id' => $this->empresa_id, 'cpf' => $request->cpf ?? '', 'foto' => '', 'uid' => uniqid('cli_', false)]);

    $result = ClienteDelivery::create($request->all());
    if($result){
        $celular = $request->celular;
        $celular = str_replace(" ", "", $celular);
        $celular = str_replace("-", "", $celular);

        if(env("AUTENTICACAO_SMS") == 1){
            $this->sendSms($celular, $cod);
        }
        else if(env("AUTENTICACAO_EMAIL") == 1) {
            $this->sendEmailLink($request->email, $cod);
        }else{
            $cliente = ClienteDelivery::find($result->id);
            $session = [
                'id' => $cliente->id,
                'nome' => $cliente->nome,
            ];
            $cliente->ativo = 1;
            $cliente->save();
            session(['cliente_log' => $session]);
            session()->flash("message_sucesso", "Bem vindo ". $cliente->nome);
            return redirect('/cardapio');
        }
        return view('delivery/autenticarCliente')
        ->with('config', $this->config)
        ->with('celular', $celular)
        ->with('cadastro_ative', true)
        ->with('title', 'AUTENTICAR');
    }else{
        session()->flash('message_erro', 'Erro ao se registrar!');
        return redirect('/cardapio');
    }
}

private function sendSms($phone, $cod){
    $nomeEmpresa = env('SMS_NOME_EMPRESA');
    $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
    $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
    $content = $nomeEmpresa. " codigo de Autorizacao ". $cod;
    $textMessageService = new TextMessageService(env('SMS_KEY'));
    $res = $textMessageService->send("Sender", $content, [$phone]);
    return $res;
}

private function sendEmailCod($email, $cod){
    Mail::send('mail.codigo_verifica', ['cod' => $cod], function($m) use ($email){
        $nomeEmail = env('MAIL_NAME');
        $nomeEmail = str_replace("_", " ", $nomeEmail);
        $m->from(env('MAIL_USERNAME'), $nomeEmail);
        $m->subject('Autenticação');
        $m->to($email);
    });
}

public function validaToken(Request $request){
    $token = $request->codToken;
    $celular = $request->celular;

    if(substr($celular, 8,1) != "-")
        $validCelular = $this->setMask($celular);
    else
        $validCelular = $celular;

    $cliente = ClienteDelivery::where('empresa_id', $this->empresa_id)->where('celular', $validCelular)->firstOrFail();

    if($cliente->token == $token){
        $cliente->ativo = true;
        $cliente->save();
        $session = [
            'id' => $cliente->id,
            'nome' => $cliente->nome,
        ];
        session(['cliente_log' => $session]);
        session()->flash("message_sucesso", "Bem vindo ". $cliente->nome."!");
        return response()->json(true, 200);
    }else{
        session()->flash("message_erro", "Código de verificação inválido");
        return response()->json(false, 204);
    }
}

private function setMask($celular){
    $c = substr($celular, 0, 2) . " " .
    substr($celular, 2,5) . "-" . substr($celular, 7,4);
    return $c;
}

private function _validate(Request $request){
    $rules = [
        'nome' => 'required|max:30',
        'sobre_nome' => 'required|max:30',
        'senha' => 'required|min:4|max:10|same:senha_confirma',
        'celular' => ['required','min:13', 'max:15', \Illuminate\Validation\Rule::unique('cliente_deliveries', 'celular')->where('empresa_id', $this->empresa_id)],
        'email' => ['required', 'max:50','email', \Illuminate\Validation\Rule::unique('cliente_deliveries', 'email')->where('empresa_id', $this->empresa_id)],
        'senha_confirma' => 'required'
    ];

    $messages = [
        'nome.required' => 'O campo nome é obrigatório.',
        'nome.max' => 'Maximo de 30 caracteres',
        'sobre_nome.required' => 'O campo sobre nome é obrigatório.',
        'sobre_nome.max' => 'Maximo de 30 caracteres',
        'senha.required' => 'O campo senha é obrigatório.',
        'senha.max' => 'Maximo de 10 caracteres',
        'senha.min' => 'Maximo de 4 caracteres',
        'senha.same' => 'Senhas não coincidem',
        'senha_confirma.required' => 'O campo confirma senha é obrigatório',
        'celular.required' => 'O campo celular é obrigatório.',
        'celular.min' => 'Minimo de 15 caracteres',
        'celular.max' => 'Maximo de 15 caracteres',
        'email.required' => 'O campo email é obrigatório.',
        'email.max' => 'Maximo de 50 caracteres',
        'email.email' => 'Email inválido'
    ];
    $this->validate($request, $rules, $messages);
}

public function recuperarSenha(){
    return view('delivery/recuperarSenha')
    ->with('config', $this->config)
    ->with('title', 'Recuperar Senha');
}

public function enviarSenha(Request $request){
    $mailPhone = $request->mail_phone;
    $mailPhone = str_replace(" ", "", $mailPhone);
    $cliente = null;
    if(is_numeric($mailPhone)){
        if(strlen($mailPhone) != 11){
            session()->flash('message_erro_telefone', 'Digite o telefone seguindo este padrao de exemplo 43999998888 - 11 Digitos.');
            return redirect("/autenticar/esqueceu_a_senha");
        }
        $cliente = ClienteDelivery::where('empresa_id', $this->empresa_id)->where('celular', $this->setaMascaraPhone($mailPhone))->first();
    }else{
        $cliente = ClienteDelivery::where('empresa_id', $this->empresa_id)->where('email', $mailPhone)->first();
    }

    if($cliente == null){
        session()->flash('message_erro', 'Email ou telefone não encontrado.');
        return redirect('/autenticar/esqueceu_a_senha');
    }else{
        $newPass = $this->randomPassword();
        if(env("AUTENTICACAO_SMS") == 1) {
            $this->sendSmsSenha($mailPhone, $newPass);
            $cliente->senha = md5($newPass);
            $cliente->save();
            session()->flash('message_sucesso', 'SMS enviado com sua nova senha, aguarde o recebimento...');
            return redirect('/autenticar');
        }
        if(env("AUTENTICACAO_EMAIL") == 1 && env("SERVIDOR_WEB_TYPE") == 1) {
            Mail::send('mail.nova_senha', ['senha' => $newPass], function($m) use ($cliente){
                $nomeEmail = env('MAIL_NAME');
                $nomeEmail = str_replace("_", " ", $nomeEmail);
                $m->from(env('MAIL_USERNAME'), $nomeEmail);
                $m->subject('recuperacao de senha');
                $m->to($cliente->email);
            });
            $cliente->senha = md5($newPass);
            $cliente->save();
            session()->flash('message_sucesso', 'Email enviado com sua nova senha, aguarde o recebimento...');
            return redirect('/autenticar/esqueceu_a_senha');
        }else{
            session()->flash('message_sucesso', 'Nada configurado.');
            return redirect('/autenticar');
        }
    }
}

private function sendSmsSenha($phone, $cod){
    $nomeEmpresa = env('SMS_NOME_EMPRESA');
    $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
    $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
    $content = $nomeEmpresa. ", sua nova senha é ". $cod;
    $textMessageService = new TextMessageService(env('SMS_KEY'));
    $res = $textMessageService->send("Sender", $content, [$phone]);
    return $res;
}

private function randomPassword() {
    $alphabet = 'abcdefghijklmnopqrstuvwxyz1234567890';
    $pass = array();
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < 4; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass);
}

public function saveTokenWeb(Request $request){
    $request->merge(['user' => session('cliente_log.id', 0)]);
    $tk = TokenWeb::where('token', $request->token)->first();

    if($tk == null){
        $res = TokenWeb::create([
            'token' => $request->token,
            'cliente_id' => $request->user > 0 ? $request->user : null
        ]);
        echo json_encode('insert');
    }else{
        if($request->user > 0){
            $tk->cliente_id = $request->user;
            $tk->save();
        }
        echo json_encode('update');
    }
}

private function sendEmailLink($email, $cod){
    Mail::send('mail.link_verifica', ['link' => md5("$cod-$email")], function($m) use ($email){
        $nomeEmail = env('MAIL_NAME');
        $nomeEmail = str_replace("_", " ", $nomeEmail);
        $m->from(env('MAIL_USERNAME'), $nomeEmail);
        $m->subject('Autenticação');
        $m->to($email);
    });
}

public function autenticarClienteEmail($cod){
    $clientes = ClienteDelivery::where('empresa_id', $this->empresa_id)->get();
    $cliente = null;
    foreach($clientes as $c){
        if(md5("$c->token-$c->email") == $cod){
            $c->ativo = true;
            $c->save();
            $cliente = $c;
        }
    }

    if($cliente != null){
        $session = [
            'id' => $cliente->id,
            'nome' => $cliente->nome,
        ];
        session(['cliente_log' => $session]);
        session()->flash("message_sucesso", "Bem vindo ". $cliente->nome . ", habilitado para App e Webdelivery");
        return redirect('/cardapio');
    }else{
        echo "Erro";
    }
}

private function funcionamento(){
    $atual = strtotime(date('H:i'));
    $dias = FuncionamentoDelivery::dias();
    $hoje = $dias[date('w')];
    $func = FuncionamentoDelivery::where('empresa_id', $this->empresa_id)->where('dia', $hoje)->first();

    if($func){
        if($atual >= strtotime($func->inicio_expediente) && $atual < strtotime($func->fim_expediente) && $func->ativo){
            return ['status' => true, 'funcionamento' => $func];
        }else{
            return ['status' => false, 'funcionamento' => $func];
        }
    }else{
        return ['status' => false, 'funcionamento' => null];
    }
}

public function rotaEntrega($id){
    $pedido = PedidoDelivery::find($id);
    $endereco = $pedido->endereco;

    $config = DeliveryConfig::first();

    if($endereco != null){
        return view('clienteDelivery/enderecoMap2')
        ->with('title', 'ver Mapa')
        ->with('mapJs', true)
        ->with('config', $config)
        ->with('endereco', $endereco);
    }else{
        echo "<h1>Não possui endereço!!</h1>";
    }
}

public function infos(){
    $clienteLog = session('cliente_log');
    if($clienteLog){
        $cliente = ClienteDelivery::where('empresa_id', $this->empresa_id)->findOrFail($clienteLog['id']);

        return view('delivery/infos')
        ->with('cliente', $cliente)
        ->with('config', $this->config)
        ->with('pass', true)
        ->with('title', 'Minhas informações');
    }else{
        session()->flash("message_erro", "Você não esta logado!!");
        return redirect('/cardapio');
    }
}

public function atualizarSenha(Request $request){
    try{
        abort_unless(session('cliente_log.id') && (int) session('cliente_log.id') === (int) $request->id, 403);
        $cliente = ClienteDelivery::where('empresa_id', $this->empresa_id)->findOrFail($request->id);
        $novaSenha = md5($request->senha);
        $cliente->senha = $novaSenha;
        $cliente->save();
        return response()->json($novaSenha, 200);
    }catch(\Exception $e){
        return response()->json("Erro", 404);
    }
}

public function alterarEndereco($id){
    $clienteLog = session('cliente_log');
    if($clienteLog){
        $endereco = EnderecoDelivery::where('id', $id)
        ->where('cliente_id', $clienteLog['id'])
        ->first();
        if($endereco == null){
            session()->flash("message_erro", "Nada encontrado!!");
            return redirect('/info');
        }else{
            $bairros = BairroDelivery::orderBy('nome')->get();

            return view('delivery/alterar_endereco')
            ->with('config', $this->config)
            ->with('bairros', $bairros)
            ->with('endereco', $endereco)
            ->with('title', 'Alterar endereço');
        }
    }else{
        session()->flash("message_erro", "Você não esta logado!!");
        return redirect('/cardapio');
    }
}

public function updateEndereco(Request $request){
    try{
        abort_unless(session('cliente_log.id'), 403);
        $endereco = EnderecoDelivery::where('cliente_id', session('cliente_log.id'))->findOrFail($request->endereco_id);
        $endereco->rua = $request->rua;
        $endereco->numero = $request->numero;
        $endereco->referencia = $request->referencia;
        if($request->bairro_id){
            $endereco->bairro_id = $request->bairro_id;
        }else{
            $endereco->bairro = $request->bairro;
        }
        $endereco->save();
        session()->flash("message_sucesso", "Endereço atualziado!!");
        return redirect('/info');

    }catch(\Exception $e){

    }
}

}