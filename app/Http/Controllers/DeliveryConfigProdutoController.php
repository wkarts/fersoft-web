<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProdutoDelivery;
use App\Models\CategoriaProdutoDelivery;
use App\Models\ImagensProdutoDelivery;
use App\Models\TamanhoPizza;
use App\Models\ProdutoPizza;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\Tributacao;
use App\Models\DeliveryConfig;
use App\Models\ComplementoDelivery;
use App\Models\ConfigNota;
use App\Models\ClienteDelivery;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DeliveryConfigProdutoController extends Controller
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

    public function byCategoria($categoria_id){
        $data = ProdutoDelivery::
        where('empresa_id', $this->empresa_id)
        ->when($categoria_id != 'todos', function ($q) use ($categoria_id) {
            return $q->where('categoria_id', $categoria_id);
        })

        ->when($categoria_id == 'todos', function ($q) use ($categoria_id) {
            return $q->limit(50);
        })
        ->with('produto')
        ->with('categoria')
        ->with('pizza')
        ->get();

        return response()->json($data, 200);
    }

    public function search(Request $request){
        $pesquisa = $request->pesquisa;
        $data = ProdutoDelivery::
        where('produtos.empresa_id', $this->empresa_id)
        ->select('produto_deliveries.*')
        ->join('produtos', 'produtos.id', '=', 'produto_deliveries.produto_id')
        ->with('produto')
        ->with('categoria')
        ->with('pizza')
        ->limit(20)

        ->when($pesquisa != '', function ($q) use ($pesquisa) {
            return $q->where('produtos.nome', 'LIKE', "%$pesquisa%");
        })
        ->when($pesquisa == '', function ($q) use ($pesquisa) {
            return $q->inRandomOrder();
        })
        ->get();

        return response()->json($data, 200);
    }

    public function searchPizzas(Request $request){
        $pesquisa = $request->pesquisa;
        $data = ProdutoDelivery::
        where('produtos.empresa_id', $this->empresa_id)
        ->select('produto_deliveries.*')
        ->join('produtos', 'produtos.id', '=', 'produto_deliveries.produto_id')
        ->join('categoria_produto_deliveries', 'categoria_produto_deliveries.id', '=', 'produto_deliveries.categoria_id')
        ->with('produto')
        ->with('categoria')
        ->with('pizza')
        ->where('categoria_produto_deliveries.tipo_pizza', 1)
        ->limit(20)

        ->when($pesquisa != '', function ($q) use ($pesquisa) {
            return $q->where('produtos.nome', 'LIKE', "%$pesquisa%");
        })
        ->when($pesquisa == '', function ($q) use ($pesquisa) {
            return $q->inRandomOrder();
        })
        ->get();

        return response()->json($data, 200);
    }

    public function find($id){
        $item = ProdutoDelivery::
        where('id', $id)
        ->with('produto')
        ->with('categoria')
        ->first();

        return response()->json($item, 200);
    }

    public function adicionais(Request $request){
        $data = ComplementoDelivery::
        where('empresa_id', $this->empresa_id)
        ->when(isset($request->pesquisa), function ($q) use ($request) {
            return $q->where('.nome', 'LIKE', "%$request->pesquisa%");
        })
        ->get();

        return response()->json($data, 200);
    }

    public function index(){
        $produtos = ProdutoDelivery::
        where('empresa_id', $this->empresa_id)
        ->paginate(18);

        return view('produtoDelivery/list')
        ->with('produtos', $produtos)
        ->with('produtoJs', true)
        ->with('links', true)
        ->with('title', 'Produtos de Delivery');
    }

    public function editMany(){

        return view('produtoDelivery/editMany')
        ->with('title', 'Produtos de Delivery');
    }

    public function editManySearch(Request $request){

        $pesquisa = $request->pesquisa;
        $tipo = $request->tipo;

        $deliveryProducts = ProdutoDelivery::where('empresa_id', $this->empresa_id)
        ->pluck('produto_id')->all();

        $data = Produto::
        select('produtos.*')
        ->when(!empty($pesquisa), function ($query) use ($pesquisa) {
            return $query->where('produtos.nome', 'LIKE', "%$pesquisa%");
        })
        ->when(!empty($tipo), function ($query) use ($tipo, $deliveryProducts) {
            if($tipo == 'delivery'){
                return $query->join('produto_deliveries', 'produto_deliveries.produto_id', '=', 
                    'produtos.id');
            }else{
                return $query->whereNotIn('produtos.id', $deliveryProducts);
            }
        })
        ->limit(60)
        ->get();

        return view('produtoDelivery/editMany')
        ->with('data', $data)
        ->with('pesquisa', $pesquisa)
        ->with('tipo', $tipo)
        ->with('title', 'Produtos de Delivery');
    }

    public function pesquisa(Request $request){
        $pesquisa = $request->pesquisa;
        $produtos = ProdutoDelivery::
        join('produtos', 'produto_deliveries.produto_id', '=', 'produtos.id')
        ->select('produto_deliveries.*')
        ->where('produtos.empresa_id', $this->empresa_id)
        ->where('produtos.nome', 'LIKE', "%$pesquisa%")
        ->get();

        return view('produtoDelivery/list')
        ->with('produtos', $produtos)
        ->with('produtoJs', true)
        ->with('pesquisa', $pesquisa)
        ->with('title', 'Produtos de Delivery');
    }

    public function new(){
        $categoria = Categoria::
        where('empresa_id', $this->empresa_id)
        ->first();
        if($categoria == null){
            //nao tem categoria
            session()->flash('mensagem_alerta', 'Cadastre ao menos uma categoria!');
            return redirect('/categorias');
        }

        $natureza = Produto::
        firstNatureza($this->empresa_id);

        if($natureza == null){
            session()->flash('mensagem_alerta', 'Cadastre uma natureza de operação!');
            return redirect('/naturezaOperacao');
        }

        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($tributacao == null){
            session()->flash('mensagem_alerta', 'Informe a tributação padrão!');
            return redirect('/tributos');
        }

        $produtos = Produto::
        where('empresa_id', $this->empresa_id)
        ->orderBy('nome')
        ->get();

        $tamanhos = TamanhoPizza::
        where('empresa_id', $this->empresa_id)
        ->get();
        $categorias = CategoriaProdutoDelivery::
        where('empresa_id', $this->empresa_id)
        ->get();

        if(sizeof($categorias) == 0){
            session()->flash("mensagem_erro", "Cadastre uma categoria para o delivery!");
            return redirect('/deliveryCategoria/new');
        }


        return view('produtoDelivery/register')
        ->with('title', 'Cadastrar Produto para Delivery')
        ->with('categorias', $categorias)
        ->with('produtos', $produtos)
        ->with('tamanhos', $tamanhos)
        ->with('produtoJs', true);
    }

    private function categoriaRegister($nome){
        $cat = Categoria::where('nome', $nome)->first();
        if($cat == null){
            return Categoria::create([
                'nome' => $nome,
                'empresa_id' => $this->empresa_id
            ]);
        }
        return $cat;
    }

    public function save(Request $request){

        $this->_validate($request);

        $produto = $request->input('produto');

        $catDelivery = CategoriaProdutoDelivery::find($request->categoria_id);

        $categoria = $this->categoriaRegister($catDelivery->nome);
        $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();

        $produtoNovo = false;
        if($request->produto != ""){

            //novo produto
            $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
            $natureza = Produto::firstNatureza($this->empresa_id);

            $arr = [
                'nome' => $produto,
                'categoria_id' => $categoria->id,
                'cor' => '',
                'valor_venda' => str_replace(",", ".", $request->valor),
                'NCM' => $tributacao->ncm_padrao,
                'CST_CSOSN' => $config->CST_CSOSN_padrao,
                'CST_PIS' => $config->CST_PIS_padrao,
                'CST_COFINS' => $config->CST_COFINS_padrao,
                'CST_IPI' => $config->CST_IPI_padrao,
                'unidade_compra' => 'UN',
                'unidade_venda' => 'UN',
                'composto' => 0,
                'codBarras' => 'SEM GTIN',
                'conversao_unitaria' => 1,
                'valor_livre' => 0,
                'perc_icms' => $tributacao->icms,
                'perc_pis' => $tributacao->pis,
                'perc_cofins' => $tributacao->cofins,
                'perc_ipi' => $tributacao->ipi,
                'CFOP_saida_estadual' => $natureza->CFOP_saida_estadual,
                'CFOP_saida_inter_estadual' => $natureza->CFOP_saida_inter_estadual,
                'codigo_anp' => '',
                'descricao_anp' => '',
                'perc_iss' => 0,
                'cListServ' => '',
                'imagem' => '',
                'alerta_vencimento' => 0,
                'valor_compra' => 0,
                'gerenciar_estoque' => 0,
                'estoque_minimo' => 0,
                'referencia' => $request->referencia ?? '',
                'tela_id' => NULL,
                'largura' => 0,
                'comprimento' => 0,
                'altura' => 0,
                'peso_liquido' => 0,
                'peso_bruto' => 0,
                'empresa_id' => $this->empresa_id,
                'referencia_grade' => Str::random(20),
                'grade' => false,
                'str_grade' => '',
                'locais' => '[-1]'
            ];
            $produto = Produto::create($arr);
            $produtoNovo = $produto->id;
            
        }

        $catPizza = false;

        $request->merge([ 'status' => $request->input('status') ? true : false ]);
        $request->merge([ 'tem_adicionais' => $request->input('tem_adicionais') ? true : false ]);
        $request->merge([ 'destaque' => $request->input('destaque') ? true : false ]);
        $request->merge([ 'ingredientes' => $request->input('ingredientes') ?? '']);
        $request->merge([ 'descricao' => $request->input('descricao') ?? '']);
        $request->merge([ 'descricao_curta' => $request->input('descricao_curta') ?? '']);
        $request->merge([ 'referencia' => $request->input('referencia') ?? '']);
        $request->merge([ 'produto_id' => $request->produto_id ? $request->produto_id : $produto->id]);

        if($catDelivery->tipo_pizza){
            $request->merge([ 'valor' => 0]);
            $request->merge([ 'valor_anterior' => 0]);

        }else{
            $request->merge([ 'valor' => str_replace(",", ".", $request->valor)]);
            $request->merge([ 'valor_anterior' => str_replace(",", ".", $request->valor_anterior ?? 0)]);
        }

        $result = ProdutoDelivery::create($request->all());
        if($request->hasFile('file')){
            $file = $request->file('file');
            $produtoDeliveryId = $request->id;

            $extensao = $file->getClientOriginalExtension();
            $nomeImagem = md5($file->getClientOriginalName()).".".$extensao;
            $request->merge([ 'path' => $nomeImagem ]);
            $request->merge([ 'produto_id' => $result->id ]);

            $upload = $file->move(public_path('imagens_produtos'), $nomeImagem);

            if($produtoNovo){
                copy(public_path('imagens_produtos/').$nomeImagem, public_path('imgs_produtos/').$nomeImagem);
                $p = Produto::find($produtoNovo);
                $p->imagem = $nomeImagem;
                $p->save();

            }

            ImagensProdutoDelivery::create($request->all());
        }

        if($catDelivery->tipo_pizza){
            $tamanhosPizza = TamanhoPizza::where('empresa_id', $this->empresa_id)->get();

            foreach($tamanhosPizza as $t){
                $res = ProdutoPizza::create([
                    'produto_id' => $result->id,
                    'tamanho_id' => $t->id,
                    'valor' => str_replace(",", ".", $request->input('valor_'.$t->nome))
                ]);
            }

        }

        if($result){
            session()->flash("mensagem_sucesso", "Produto cadastrado com sucesso!");
        }else{

            session()->flash('mensagem_erro', 'Erro ao cadastrar produto!');
        }

        return redirect('/deliveryProduto');
    }

    public function saveImagem(Request $request){

        $file = $request->file('file');
        $produtoDeliveryId = $request->id;

        $extensao = $file->getClientOriginalExtension();
        $nomeImagem = md5($file->getClientOriginalName()).".".$extensao;
        $request->merge([ 'path' => $nomeImagem ]);
        $request->merge([ 'produto_id' => $produtoDeliveryId ]);

        $produtoDelivery = ProdutoDelivery::find($produtoDeliveryId);

        $upload = $file->move(public_path('imagens_produtos'), $nomeImagem);

        $result = ImagensProdutoDelivery::create($request->all());

        if($result){

            session()->flash("mensagem_sucesso", "Imagem cadastrada com sucesso!");
        }else{

            session()->flash('mensagem_erro', 'Erro ao cadastrar produto!');
        }

        return redirect('/deliveryProduto/galeria/'.$produtoDeliveryId );

    }

    public function edit($id){
        $tamanhos = TamanhoPizza::
        where('empresa_id', $this->empresa_id)
        ->get();
        $produto = new ProdutoDelivery();
        $categorias = CategoriaProdutoDelivery::
        where('empresa_id', $this->empresa_id)
        ->get();
        $produtos = Produto::orderBy('nome')->
        where('empresa_id', $this->empresa_id)
        ->get();

        $resp = $produto
        ->where('id', $id)->first();  

        if(valida_objeto($resp)){

            return view('produtoDelivery/register')
            ->with('produto', $resp)
            ->with('categorias', $categorias)
            ->with('tamanhos', $tamanhos)
            ->with('produtos', $produtos)
            ->with('produtoJs', true)
            ->with('title', 'Editar Produto de Delivery');
        }else{
            return redirect('/403');
        }

    }

    public function alterarDestaque($id){
        $produto = new ProdutoDelivery(); //Model
        $categorias = CategoriaProdutoDelivery::
        where('empresa_id', $this->empresa_id)
        ->get();
        $resp = $produto
        ->where('id', $id)->first(); 

        $resp->destaque = !$resp->destaque;
        $resp->save(); 

        echo json_encode($resp);
    }

    public function alterarStatus($id){
        $produto = new ProdutoDelivery(); //Model
        $categorias = CategoriaProdutoDelivery::
        where('empresa_id', $this->empresa_id)
        ->get();
        $resp = $produto
        ->where('id', $id)->first();  

        $resp->status = !$resp->status;
        $resp->save();
        echo json_encode($resp);

    }


    public function galeria($id){
        $produto = new ProdutoDelivery(); //Model

        $resp = $produto
        ->where('id', $id)->first();  
        if(valida_objeto($resp)){
            return view('produtoDelivery/galery')
            ->with('produto', $resp)
            ->with('title', 'Galeria de Produto');
        }else{
            return redirect('/403');
        }
    }

    public function update(Request $request){
    	$produto = new ProdutoDelivery();

    	$id = $request->input('id');
    	$resp = $produto
    	->where('id', $id)->first(); 

        $this->_validate($request);

        $resp->categoria_id = $request->categoria_id;
        $resp->ingredientes = $request->ingredientes ?? '';
        $resp->descricao = $request->descricao ?? '';
        $resp->descricao_curta = $request->descricao_curta ?? '';
        $resp->valor = str_replace(",", ".", $request->valor);
        $resp->valor_anterior = str_replace(",", ".", $request->valor_anterior);
        $resp->limite_diario = $request->limite_diario;
        $resp->destaque = $request->input('destaque') ? true : false;
        $resp->status = $request->input('status') ? true : false;
        $resp->tem_adicionais = $request->input('tem_adicionais') ? true : false;

        $controlUpdatePizza = [];
        foreach($resp->pizza as $p){
            $p->valor = str_replace(",", ".", $request->input('valor_'.$p->tamanho->nome));
            $p->save();
            array_push($controlUpdatePizza, $p->tamanho->id);
        }
        if($resp->categoria->tipo_pizza){

            $tamanhosPizza = TamanhoPizza::
            where('empresa_id', $this->empresa_id)
            ->get();
            if(count($tamanhosPizza) > count($resp->pizza)){
            //precisa inserir tambem

                foreach($tamanhosPizza as $t){
                    if(!in_array($t->id, $controlUpdatePizza)){
                    //entao insere
                        $res = ProdutoPizza::create([
                            'produto_id' => $resp->id,
                            'tamanho_id' => $t->id,
                            'valor' => str_replace(",", ".", $request->input('valor_'.$t->nome))
                        ]);
                    }
                }
            }
        }
        

        $result = $resp->save();
        if($result){
            session()->flash('mensagem_sucesso', 'Produto editado com sucesso!');
        }else{
            session()->flash('mensagem_erro', 'Erro ao editar produto!');
        }

        return redirect('/deliveryProduto'); 
    }

    public function delete($id){
        $produto = ProdutoDelivery
        ::where('id', $id)
        ->first();
        if(valida_objeto($produto)){
            foreach ($produto->galeria as $g) {
                $public = env('SERVIDOR_WEB') ? 'public/' : '';
                if($g->path != '' && file_exists($public . 'imagens_produtos/'.$g->path))
                    unlink($public . 'imagens_produtos/'.$g->path);
            }

            if($produto->delete()){
                session()->flash('mensagem_sucesso', 'Registro removido!');
            }else{
                session()->flash('mensagem_erro', 'Erro ao remover!');
            }
            return redirect('/deliveryProduto');
        }else{
            return redirect('/403');
        }
    }

    public function deleteImagem($id){
        $imagem = ImagensProdutoDelivery
        ::where('id', $id)
        ->first();

        if(valida_objeto($imagem->produto)){

            $public = env('SERVIDOR_WEB') ? 'public/' : '';
            if(file_exists($public . 'imagens_produtos/'.$imagem->path))
                unlink($public . 'imagens_produtos/'.$imagem->path);

            if($imagem->delete()){
                session()->flash('mensagem_sucesso', 'Imagem removida!');
            }else{
                session()->flash('mensagem_erro', 'Erro!');
            }
            return redirect('/deliveryProduto/galeria/'.$imagem->produto_id);
        }
    }

    private function _validate(Request $request, $fileExist = true){

        $categoria = CategoriaProdutoDelivery::find($request->categoria_id);

        $catPizza = $categoria->tipo_pizza;

        $rules = [
            'produto' => $request->id > 0 ? '' : ($request->produto_id ? '' : 'required'),
            'produto_id' => $request->id > 0 ? '' : ($request->produto != "" ? '' : 'required'),
            'ingredientes' => 'max:255',
            'descricao' => 'max:255',
            'descricao_curta' => 'max:50',
            'valor' => !$catPizza ? 'required' : '',
            'limite_diario' => 'required'
        ];

        $messages = [
            'produto.required' => 'O campo produto é obrigatório.',
            'produto.min' => 'Selecione um produto.',
            'ingredientes.required' => 'O campo ingredientes é obrigatório.',
            'ingredientes.max' => '255 caracteres maximos permitidos.',
            'descricao.required' => 'O campo descricao é obrigatório.',
            'descricao.max' => '255 caracteres maximos permitidos.',
            'descricao_curta.max' => '50 caracteres maximos permitidos.',
            'valor.required' => 'O campo valor é obrigatório.',
            'limite_diario.required' => 'O campo limite diário é obrigatório',
        ];

        if($catPizza){
            $tamanhosPizza = TamanhoPizza::
            where('empresa_id', $this->empresa_id)
            ->get();

            foreach($tamanhosPizza as $t){
                $rules['valor_'.$t->nome] = 'required';
                $messages['valor_'.$t->nome.'.required'] = 'Campo obrigatório ' . $t->nome;
            }
        }

        $this->validate($request, $rules, $messages);
    }

    public function push($id){
        $produto = ProdutoDelivery::
        where('id', $id)
        ->first();
        if(valida_objeto($produto)){
            $clientes = ClienteDelivery::orderBy('nome')
            ->where('empresa_id', $this->empresa_id)
            ->get();

            return view('push/new')
            ->with('pushJs', true)
            ->with('titulo', $this->randomTitles())
            ->with('clientes', $clientes)
            ->with('mensagem', $this->randomMensagem($produto))
            ->with('imagem', isset($produto->galeria[0]) ? $produto->galeria[0]->path : '')
            ->with('referencia', $produto->id)
            ->with('title', 'Nova Push');
        }else{
            return redirect('/403');
        }
    }

    private function randomTitles(){
        $titles = [
            'Mega oferta de Hoje',
            'Promoção imperdivel',
            'Não perca isso',
            'Não deixe de comprar'
        ];
        return $titles[rand(0,3)];
    }

    private function randomMensagem($produto){
        $messages = [
            $produto->produto->nome.' por apenas, R$ '.$produto->valor,
            $produto->produto->nome. ' de R$'. $produto->valor_anterior.' por apenas R$'. 
            $produto->valor,
            'Peca já o seu '.$produto->produto->nome. ' o melhor :)',
            'Promoção de hoje '. $produto->produto->nome. ' venha conferir'
        ];
        return $messages[rand(0,3)];
    }

    public function autocomplete(Request $request){
        try{
            $loja = DeliveryConfig::where('empresa_id', $request->loja_id)->first();
            $data = ProdutoDelivery::
            where('produto_deliveries.empresa_id', $loja->empresa_id)
            ->select('produto_deliveries.*')
            ->join('produtos', 'produtos.id', '=', 'produto_deliveries.produto_id')
            ->where('produtos.nome', 'LIKE', "%$request->pesquisa%")
            ->orderBy('produtos.nome')
            ->with('produto')
            ->get();

            return response()->json($data, 200);

        }catch(\Exception $e){
            return response($e->getMessage(), 401);
        }
    }

    public function confirmMany(Request $request){
        $produtos = [];
        for($i=0; $i<sizeof($request->check); $i++){
            $p = Produto::findOrFail($request->check[$i]);
            array_push($produtos, $p);
        }

        $tamanhos = TamanhoPizza::
        where('empresa_id', $this->empresa_id)
        ->get();
        $categorias = CategoriaProdutoDelivery::
        where('empresa_id', $this->empresa_id)
        ->get();

        return view('produtoDelivery/formEditMany', compact('produtos', 'tamanhos', 'categorias'))
        ->with('title', 'Editar Produtos');
    }

    public function confirmManyPost(Request $request){
        try{

            DB::transaction(function () use ($request) {
                for($i=0; $i<sizeof($request->produto_id); $i++){
                    $produto = Produto::findOrFail($request->produto_id[$i]);

                    $file = isset($request->file[$i]) ? $request->file[$i] : null;
                    $fileName = '';
                    if($file != null){
                        $extensao = $file->getClientOriginalExtension();
                        $fileName = Str::random(20).".".$extensao;
                        $file->move(public_path('imagens_produtos'), $fileName);
                    }
                    $produtoDelivery = $produto->delivery;
                    if(!$produtoDelivery){
                        //cadastrar em delivery
                        $produtoDelivery = ProdutoDelivery::create([
                            'produto_id' => $produto->id,
                            'categoria_id' => $request->categoria_id[$i],
                            'valor' => $request->valor[$i] ? __replace($request->valor[$i]) : 0,
                            'valor_anterior' => 0,
                            'descricao' => '',
                            'ingredientes' => '',
                            'referencia' => '',
                            'descricao_curta' => '',
                            'limite_diario' => -1,
                            'status' => 1,
                            'destaque' => 0,
                            'empresa_id' => $this->empresa_id,
                            'tem_adicionais' => 0,
                            'tipo' => 'simples',
                        ]);
                        if($fileName != ''){
                            ImagensProdutoDelivery::create([
                                'produto_id' => $produtoDelivery->id,
                                'path' => $fileName
                            ]);
                        }
                    }else{
                        $categoriaDelivery = CategoriaProdutoDelivery::findOrFail($request->categoria_id[$i]);
                        if($fileName != ''){
                            foreach ($produtoDelivery->galeria as $g) {
                                $public = env('SERVIDOR_WEB') ? 'public/' : '';
                                if($g->path != '' && file_exists($public . 'imagens_produtos/'.$g->path))
                                    unlink($public . 'imagens_produtos/'.$g->path);
                            }
                            $produtoDelivery->galeria()->delete();
                            ImagensProdutoDelivery::create([
                                'produto_id' => $produtoDelivery->id,
                                'path' => $fileName
                            ]);
                        }
                        if($categoriaDelivery->tipo_pizza){
                            for($aux=0; $aux<sizeof($request->valor_pizza); $aux++){
                                $valor = __replace($request->valor_pizza[$aux]);
                                $pz = $produtoDelivery->pizza[$aux];
                                $pz->valor = $valor;
                                $pz->save();
                            }
                            
                        }else{
                            $produtoDelivery->valor = __replace($request->valor[$i]);
                            $produtoDelivery->categoria_id = $request->categoria_id[$i];
                            $produtoDelivery->save();
                        }
                    }
                }
            });
            session()->flash("mensagem_sucesso", "Produtos alterados/adicionais para delivery");
            return redirect('/deliveryProduto');

        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getLine());
            return redirect()->back();
        }

    }

}
