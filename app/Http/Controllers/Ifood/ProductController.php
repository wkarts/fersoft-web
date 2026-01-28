<?php

namespace App\Http\Controllers\Ifood;

use Illuminate\Http\Request;
use App\Models\IfoodConfig;
use App\Models\ProdutoIfood;
use App\Models\CategoriaIfood;
use App\Models\PrecoProdutoIfood;
use App\Models\ConfigNota;
use App\Models\Produto;
use App\Models\Tributacao;
use App\Models\Categoria;
use App\Restaurant\IfoodService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

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

    public function index(){

        $item = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($item->catalogId == ""){
            session()->flash("mensagem_erro", "Defina o catalogo!");
            return redirect('/ifood/catalogos');
        }

        $data = ProdutoIfood::orderBy('nome', 'asc')
        ->paginate(12);

        if(sizeof($data) == 0){
            $this->refreshProduct();
        }

        return view('produto_ifood/index', compact('data'))
        ->with('links', true)
        ->with('title', 'Produtos iFood');

    }

    public function productsFilter(Request $request){

        $search = $request->search;
        $data = ProdutoIfood::orderBy('nome', 'asc')
        ->where('nome', 'like', "%$search%")
        ->paginate(12);

        return view('produto_ifood/index', compact('data'))
        ->with('search', $search)
        ->with('title', 'Produtos iFood');

    }

    // public function refreshProduct(){
    //     $item = IfoodConfig::
    //     where('empresa_id', $this->empresa_id)
    //     ->first();

    //     if($item == null){
    //         session()->flash("mensagem_erro", "Configure o App");
    //         return redirect('/ifood/config');
    //     }

    //     $iFoodService = new IfoodService($item);
    //     $result = $iFoodService->getProducts();

    //     if(isset($result->message) && $result->message == 'token expired'){
    //         session()->flash("mensagem_erro", "Token Expirado!");
    //         return redirect('/ifood/config');
    //     }else{
    //         //buscou as categorias

    //         // echo "<pre>";
    //         // print_r($result);
    //         // echo "</pre>";
    //         // die;
    //         $page = 1;
    //         do{

    //             if(isset($result->elements)){
    //                 foreach($result->elements as $item){

    //                     // $find = $iFoodService->findProduct($item->id);

    //                     // echo "<pre>";
    //                     // print_r($item);
    //                     // echo "</pre>";

    //                     $dataProduto = [
    //                         'empresa_id' => $this->empresa_id,
    //                         'nome' => $item->name,
    //                         'imagem' => $item->image,
    //                         'id_ifood' => $item->id,
    //                         'serving' => $item->serving,
    //                         'descricao' => $item->description ?? '',
    //                     ];

    //                     ProdutoIfood::updateOrCreate($dataProduto);
    //                 }
    //             }

    //             $page++;

    //             $result = $iFoodService->getProducts($page);
    //         }while(sizeof($result->elements) != 0);

    //         session()->flash("mensagem_sucesso", "Busca realizada!");
    //         return redirect('/ifood/products');
    //     }
    // }

    public function refreshProduct($message = ''){
        $item = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($item->catalogId == ""){
            session()->flash("mensagem_erro", "Defina o catalogo!");
            return redirect('/ifood/catalogos');
        }

        if($item == null){
            session()->flash("mensagem_erro", "Configure o App");
            return redirect('/ifood/config');
        }

        $iFoodService = new IfoodService($item);
        $result = $iFoodService->getCategories();

        if(isset($result->message) && $result->message == 'token expired'){
            session()->flash("mensagem_erro", "Token Expirado!");
            return redirect('/ifood/config');
        }else{

            // echo "<pre>";
            // print_r($result);
            // echo "</pre>";
            // die;

            foreach($result as $cat){

                $tempCat = CategoriaIfood::updateOrCreate([
                    'empresa_id' => $this->empresa_id,
                    'nome' => $cat->name,
                    'status' => $cat->status,
                    'id_ifood' => $cat->id
                ]);
                if(isset($cat->items)){
                    foreach($cat->items as $prod){
                        // if($prod->name == "Nome do Refrigerante 2 L"){
                        //     print_r($prod);
                        //     die;
                        // }

                        $estoque = $iFoodService->getStock($prod->productId);

                        $dataProduct = [
                            'empresa_id' => $this->empresa_id,
                            'id_ifood' => $prod->productId,
                            'id_ifood_aux' => $prod->id,
                            'nome' => $prod->name,
                            'imagem' => $prod->imagePath,
                            'serving' => $prod->serving,
                            'ean' => $prod->ean ?? '',
                            'status' => $prod->status,
                            'estoque' => isset($estoque->amount) ? $estoque->amount : 0,
                        // 'sellingOption_minimum' => ,
                        // 'sellingOption_incremental' => ,
                        // 'sellingOption_averageUnit' => ,
                        // 'sellingOption_availableUnits' => ,
                            'descricao' => $prod->description ?? '',
                            'valor' => $prod->price->value,
                            'categoria_id' => $tempCat->id
                        ];

                        if(!$this->validaProdutoInseridoIfood($prod->productId)){
                            $p = ProdutoIfood::create($dataProduct);
                            $this->salvarProdutoBanco($p, $prod->productId);
                        }else{
                            $prod = ProdutoIfood::where('id_ifood', $prod->productId)
                            ->first();
                            if($prod){
                                $prod->fill($dataProduct)->save();
                            }
                        }
                    }
                }elseif(isset($cat->pizza)){
                    foreach($cat->pizza->toppings as $pizza){
                        // if($pizza->name == 'Mussarela'){
                        //     echo "<pre>";
                        //     print_r($pizza);
                        //     echo "</pre>";
                        //     die;
                        // }
                        $dataProduct = [
                            'empresa_id' => $this->empresa_id,
                            'id_ifood' => $pizza->id,
                            'id_ifood_aux' => $pizza->id,
                            'nome' => $pizza->name,
                            'imagem' => $pizza->image,
                            'serving' => '',
                            'ean' => '',
                        // 'sellingOption_minimum' => ,
                        // 'sellingOption_incremental' => ,
                        // 'sellingOption_averageUnit' => ,
                        // 'sellingOption_availableUnits' => ,
                            'descricao' => $pizza->description ?? '',
                            'valor' => 0,
                            'categoria_id' => $tempCat->id
                        ];

                        if(!$this->validaProdutoInseridoIfood($pizza->id)){

                            $newPizza = ProdutoIfood::create($dataProduct);

                            $rand = Str::random(20);
                            foreach($pizza->prices as $key => $price){
                                $dataPrice = [
                                    'id_ifood' => $key,
                                    'produto_ifood_id' => $newPizza->id,
                                    'valor' => $price->value
                                ];

                                PrecoProdutoIfood::create($dataPrice);
                                $this->salvarProdutoBancoVariation($newPizza, $rand, $price->value, $newPizza->id);
                            }

                        }else{
                            $prod = ProdutoIfood::where('id_ifood', $pizza->id)
                            ->first();
                            if($prod){
                                $prod->fill($dataProduct)->save();
                            }
                        }
                    }
                }
            }

            if($message == ""){
                session()->flash("mensagem_sucesso", "Busca realizada!");
            }else{
                session()->flash("mensagem_sucesso", $message);
            }
            return redirect('/ifood/products');
        }
    }

    private function validaProdutoInseridoIfood($id){
        return ProdutoIfood::
        where('empresa_id', $this->empresa_id)
        ->where('id_ifood', $id)
        ->exists();
    }

    public function productsCreate(){

        $item = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $iFoodService = new IfoodService($item);
        $categories = $iFoodService->getCategories();

        return view('produto_ifood/create', compact('categories'))
        ->with('title', 'Produtos iFood');
    }

    public function productsEdit($id){

        $item = produtoIfood::findOrFail($id);

        $config = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $iFoodService = new IfoodService($config);
        $categories = $iFoodService->getCategories();

        return view('produto_ifood/create', compact('categories', 'item'))
        ->with('title', 'Produtos iFood');
    }

    private function saveCategoria($nome){

        try{
            $categoria = Categoria::where('empresa_id', $this->empresa_id)
            ->where('nome', $nome)
            ->first();
            if($categoria == null){
                $categoria = Categoria::create([
                    'empresa_id'=> $this->empresa_id,
                    'nome' => $nome
                ]);
            }
            return $categoria;
        }catch(\Exception $e){
            echo $e->getMessage();
            die;
        }
    }

    private function salvarProdutoBanco($produtoIfood, $ifood_id){

        $p = Produto::where('ifood_id', $ifood_id)->first();
        if($p == null){
            $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
            $natureza = Produto::firstNatureza($this->empresa_id);
            $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();
            $categoria = $this->saveCategoria($produtoIfood->categoria->nome);
            $valorVenda = __replace($produtoIfood->valor);

            $valorCompra = $valorVenda - (($valorVenda*$config->percentual_lucro_padrao)/100);
            $data = [
                'nome' => $produtoIfood->nome,
                'categoria_id' => $categoria->id,
                'cor' => '',
                'valor_venda' => $valorVenda,
                'NCM' => $tributacao->ncm_padrao,
                'CST_CSOSN' => $config->CST_CSOSN_padrao,
                'CST_PIS' => $config->CST_PIS_padrao,
                'CST_COFINS' => $config->CST_COFINS_padrao,
                'CST_IPI' => $config->CST_IPI_padrao,
                'unidade_compra' => 'UN',
                'unidade_venda' => 'UN',
                'composto' => 0,
                'codBarras' => $produtoIfood->ean,
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
                'valor_compra' => $valorCompra,
                'gerenciar_estoque' => 0,
                'estoque_minimo' => 0,
                'referencia' => '',
                'tela_id' => NULL,
                'empresa_id' => $this->empresa_id,
                'percentual_lucro' => $config->percentual_lucro_padrao,
                'referencia_grade' => Str::random(20),
                "ifood_id" => $produtoIfood->id_ifood
            ];

            $produto = Produto::create($data);
        }
    }

    private function salvarProdutoBancoVariation($produtoIfood, $rand, $valorVenda, $ifood_id){
        $p = Produto::where('ifood_id', $ifood_id)->first();
        if($p == null){
            $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
            $natureza = Produto::firstNatureza($this->empresa_id);
            $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();
            $categoria = Categoria::where('empresa_id', $this->empresa_id)->first();
            $categoria = $this->saveCategoria($produtoIfood->categoria->nome);

            $valorCompra = $valorVenda - (($valorVenda*$config->percentual_lucro_padrao)/100);
            $data = [
                'nome' => $produtoIfood->nome,
                'categoria_id' => $categoria->id,
                'cor' => '',
                'valor_venda' => $valorVenda,
                'NCM' => $tributacao->ncm_padrao,
                'CST_CSOSN' => $config->CST_CSOSN_padrao,
                'CST_PIS' => $config->CST_PIS_padrao,
                'CST_COFINS' => $config->CST_COFINS_padrao,
                'CST_IPI' => $config->CST_IPI_padrao,
                'unidade_compra' => 'UN',
                'unidade_venda' => 'UN',
                'composto' => 0,
                'conversao_unitaria' => 1,
                'valor_livre' => 0,
                'codBarras' => $produtoIfood->ean,
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
                'valor_compra' => $valorCompra,
                'gerenciar_estoque' => 0,
                'estoque_minimo' => 0,
                'referencia' => '',
                'tela_id' => NULL,
                'empresa_id' => $this->empresa_id,
                'percentual_lucro' => $config->percentual_lucro_padrao,
                'referencia_grade' => $rand,
                'grade' => 1,
                "ifood_id" => $produtoIfood->id_ifood
            ];

            $produto = Produto::create($data);

        }
    }

    public function store(Request $request){
        $this->_validate($request);

        $item = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $data = [
            'name' => $request->nome,
            'serving' => 'SERVES_1',
        ];

        if($request->descricao){
            $data['description'] = $request->descricao;
        }
        if($request->codigo_barras){
            $data['ean'] = $request->codigo_barras;
        }

        if($request->hasFile('file')){

            $file = $request->file('file');
            $mimeType = $file->getClientOriginalExtension();
            $image = "data:image/$mimeType;base64,".base64_encode(file_get_contents($request->file('file')));

            $data['image'] = $image;
        }

        $codeCategory = $request->categoria;
        $category = CategoriaIfood::where('id_ifood', $codeCategory)->first();

        $iFoodService = new IfoodService($item);
        $product = $iFoodService->storeProduct($data);

        $novo_produto = $request->novo_produto ? true : false;

        $dataAssociation = [
            'status' => 'AVAILABLE',
            'price' => [
                'value' => (float)__replace($request->valor)
            ]

        ];

        if(isset($product->error)){
            session()->flash("mensagem_erro", $product->error->details[0]->message);
            return redirect()->back();
        }

        //add estoque

        if($request->estoque){
            $dataEstoque = [
                "productId" => $product->id,
                'amount' => (float)__replace($request->estoque)
            ];
            $stock = $iFoodService->addStockProduct($dataEstoque);

        }

        // echo "<pre>";
        // print_r($product);
        // echo "</pre>";

        // die;

        $association = $iFoodService->associationProductCategory($codeCategory, $product->id, $dataAssociation);

        // echo $product->id;
        // echo "<pre>";
        // print_r($association);
        // echo "</pre>";

        if(isset($association->id)){

            $dataProduct = [
                'empresa_id' => $this->empresa_id,
                'id_ifood' => $product->id,
                'id_ifood_aux' => $product->id,
                'nome' => $request->nome,
                'imagem' => $product->image,
                'serving' => 'SERVES_1',
                'ean' => $prod->codigo_barras ?? '',
                'status' => 'AVAILABLE',
                'estoque' => $request->estoque ?? 0,

                'descricao' => $prod->descricao ?? '',
                'valor' => __replace($request->valor),
                'categoria_id' => $category->id
            ];
            $p = ProdutoIfood::create($dataProduct);

            if(!$novo_produto){
                $produto = Produto::find($request->produto_id);
                $produto->ifood_id = $product->id;
                $produto->save();
            }else{
                $this->salvarProdutoBanco($p, $product->id);
            }

            session()->flash("mensagem_sucesso", "Produto cadastrado com sucesso!");
            return redirect('/ifood/products');

        }else{
            session()->flash("mensagem_erro", "Algo deu errado na associação do produto com categoria!");
            return redirect()->back();
        }
    }

    private function _validate(Request $request){
        $rules = [
            'referencia' => 'required',
            'nome' => 'required',
            'categoria' => 'required',
            'valor' => 'required',
            // 'estoque' => 'required',
        ];

        $messages = [
            'referencia.required' => 'O campo referência é obrigatório.',
            'estoque.required' => 'O campo estoque é obrigatório.',
            'nome.required' => 'O campo nome é obrigatório.',
            'valor.required' => 'O campo valor é obrigatório.',
            'categoria.required' => 'O campo categoria é obrigatório.'
        ];
        $this->validate($request, $rules, $messages);
    }

    public function destroy($id){
        $item = produtoIfood::findOrFail($id);
        $config = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $iFoodService = new IfoodService($config);
        $result = $iFoodService->deleteProduct($item->categoria->id_ifood, $item->id_ifood);


        if(isset($result->error)){
            session()->flash("mensagem_erro", $result->error->message);
        }else{
            $item->delete();
            session()->flash("mensagem_sucesso", "Item removido com sucesso!");
        }
        return redirect()->back();
    }

    public function update(Request $request, $id){
        $this->_validate($request);
        $item = produtoIfood::findOrFail($id);

        try{
            $config = IfoodConfig::
            where('empresa_id', $this->empresa_id)
            ->first();

            $data = [
                'name' => $request->nome,
                'serving' => 'SERVES_1',
            ];

            if($request->descricao){
                $data['description'] = $request->descricao;
            }
            if($request->codigo_barras){
                $data['ean'] = $request->codigo_barras;
            }

            if($request->hasFile('file')){

                $file = $request->file('file');
                $mimeType = $file->getClientOriginalExtension();
                $image = "data:image/$mimeType;base64,".base64_encode(file_get_contents($request->file('file')));

                $data['image'] = $image;
            }

            $iFoodService = new IfoodService($config);
            $product = $iFoodService->updateProduct($data, $item->id_ifood);

            // echo "<pre>";
            // print_r($product);
            // echo "</pre>";

            // die;

            $codeCategory = $request->categoria;
            $category = CategoriaIfood::where('id_ifood', $codeCategory)->first();

            $item->imagem = isset($product->image) ? ("https://static-images.ifood.com.br/pratos/".$product->image) : '';
            $item->nome = $request->nome;
            $item->descricao = $request->descricao;
            $item->categoria_id = $category->id;
            $item->estoque = $request->estoque ?? 0;
            $item->ean = $request->codigo_barras ?? '';
            $item->valor = __replace($request->valor);
            $item->save();
            session()->flash("mensagem_sucesso", "Produto atualizado com sucesso!");
            return redirect('/ifood/products');

        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
            return redirect()->back();
        }
    }

}
