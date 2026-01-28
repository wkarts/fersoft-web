<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produto;
use App\Models\ConfigNota;
use App\Models\Tributacao;
use App\Models\Categoria;
use Illuminate\Support\Str;
use App\Helpers\StockMove;

class NuvemShopProdutoController extends Controller
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

    public function index(Request $request){

        $page = $request->page ? $request->page : 1;
        $search = $request->search;
        $store_info = session('store_info');
        if(!$store_info){
            return redirect('/nuvemshop');
        }
        $api = new \TiendaNube\API($store_info['store_id'], $store_info['access_token'], 'Awesome App ('.$store_info['email'].')');

        if($search != ""){
            $produtos = (array)$api->get("products?q='".$search."'&per_page=21");
        }else{
            $produtos = (array)$api->get("products?page=".$page."&per_page=12");
        }
        $produtos = $produtos['body'];

        $this->validaProdutos($produtos);
        
        return view('nuvemshop/produtos')
        ->with('produtos', $produtos)
        ->with('page', $page)
        ->with('search', $search)
        ->with('title', 'Produtos');
    }

    private function validaProdutos($produtos){
        foreach($produtos as $p){
            // echo "<pre>";
            // print_r($p);
            // echo "</pre>";
            $rand = Str::random(20);

            if(sizeof($p->variants) > 1){
                foreach($p->variants as $v){
                    $ean = $v->barcode;
                    $result = Produto::
                    where('codBarras', $ean)
                    ->where('codBarras', '!=', 'SEM GTIN')
                    ->where('empresa_id', $this->empresa_id)
                    ->first();

                    // echo "<pre>";
                    // print_r($v);
                    // echo "</pre>";
                    // die;

                    if($result == null){
                        $str = "";
                        foreach($v->values as $s){
                            $str .= $s->pt . " ";
                        }
                        $result = Produto::
                        where('nome', $p->name->pt)
                        ->where('str_grade', $str)
                        ->where('empresa_id', $this->empresa_id)
                        ->first();

                    }

                    if($result == null){
                        $this->salvarProdutoBanco2($p, $v, $rand, $str);
                    }
                }

            }else{

                $result = Produto::
                where('nome', $p->name->pt)
                ->where('empresa_id', $this->empresa_id)
                ->first();

                if($result == null){
                    $ean = $p->variants[0]->barcode;
                    $result = Produto::
                    where('codBarras', $ean)
                    ->where('codBarras', '!=', 'SEM GTIN')
                    ->where('empresa_id', $this->empresa_id)
                    ->first();
                }

                if($result == null){
                //cadastrar
                    $this->salvarProdutoBanco2($p, null, $rand);
                }
            }
        }
    }

    public function produto_edit($id){
        $store_info = session('store_info');
        $api = new \TiendaNube\API($store_info['store_id'], $store_info['access_token'], 'Awesome App ('.$store_info['email'].')');
        $produto = (array)$api->get("products/".$id);
        $produto = $produto['body'];

        // echo "<pre>";
        // print_r($produto);
        // echo "</pre>";

        // die;

        $categorias = (array)$api->get("categories");
        $categorias = $categorias['body'];

        $prodBd = Produto::where('nuvemshop_id', $produto->id)
        ->first();

        return view('nuvemshop/produtos_form')
        ->with('produto', $produto)
        ->with('prodBd', $prodBd)
        ->with('categorias', $categorias)
        ->with('contratoJs', true)
        ->with('title', 'Editar Produto');

    }

    public function produto_new(){

        $store_info = session('store_info');
        $api = new \TiendaNube\API($store_info['store_id'], $store_info['access_token'], 'Awesome App ('.$store_info['email'].')');

        // echo "<pre>";
        // print_r($produto);
        // echo "</pre>";

        // die;

        $categorias = (array)$api->get("categories");
        $categorias = $categorias['body'];

        return view('nuvemshop/produtos_form')
        ->with('categorias', $categorias)
        ->with('contratoJs', true)
        ->with('title', 'Novo Produto');

    }

    public function saveProduto(Request $request){

        $nome = $request->nome;
        $descricao = $request->descricao;
        $valor = $request->valor;
        $id = $request->id;
        $categoria_id = $request->categoria_id;
        $estoque = $request->estoque;
        $valor_promocional = $request->valor_promocional ?? 0;
        $codigo_barras = $request->codigo_barras ?? '';

        $peso = $request->peso ? __replace($request->peso) : 0;
        $largura = $request->largura ? __replace($request->largura) : 0;
        $altura = $request->altura ? __replace($request->altura) : 0;
        $comprimento = $request->comprimento ? __replace($request->comprimento) : 0;

        $this->_validate($request);
        try{
            $store_info = session('store_info');
            $api = new \TiendaNube\API($store_info['store_id'], $store_info['access_token'], 'Awesome App ('.$store_info['email'].')');
            if($id > 0){
                $response = $api->put("products/$id", [
                    'name' => $nome,
                    'description' => $descricao,
                    'categories' => $categoria_id ? [$categoria_id] : []
                ]);

                $produto = (array)$api->get("products/".$id);
                $produto = $produto['body'];

                if(sizeof($produto->variants) == 1){

                    $resp = $response = $api->put("products/$id/variants/".$produto->variants[0]->id, [
                        'price' => __replace($valor),
                        'stock' => __replace($estoque),
                        'promotional_price' => __replace($valor_promocional),
                        'barcode' => __replace($codigo_barras),

                        "weight" => $peso,
                        "width" => $largura,
                        "height" => $altura,
                        "depth" => $comprimento,
                    ]);
                }

                $prodBd = Produto::where('nuvemshop_id', $request->id)
                ->first();

                if($prodBd == null){
                    $this->salvarProdutoBanco($request, $request->id);
                }

                if($response){
                    session()->flash("mensagem_sucesso", "Produto atualizado!");
                }else{
                    session()->flash("mensagem_erro", "Erro inesperado: " . $e->getMessage());
                }

            }else{

                $response = $api->post("products", [
                    'name' => $nome,
                    'parent' => $categoria_id,
                    'description' => $descricao
                ]);

                $produto = $response->body;

                $resp = $response = $api->put("products/$produto->id/variants/".$produto->variants[0]->id, [
                    'price' => __replace($valor),
                    'stock' => __replace($estoque),
                    'promotional_price' => __replace($valor_promocional),
                    'barcode' => __replace($codigo_barras),
                    "weight" => $peso,
                    "width" => $largura,
                    "height" => $altura,
                    "depth" => $comprimento,
                ]);

                $this->salvarProdutoBanco($request, $produto->id);
                // print_r($response);
                // die;
                if($response){
                    session()->flash("mensagem_sucesso", "Produto criado!");
                }else{
                    session()->flash("mensagem_erro", "Erro inesperado: " . $e->getMessage());
                }
            }
        }catch(\Exception $e){
            echo $e->getMessage();
            die;
            session()->flash("mensagem_erro", "Erro inesperado: " . $e->getMessage());

        }
        return redirect('/nuvemshop/produtos');
    }

    private function salvarProdutoBanco($request, $nuvemshop_id){

        if($request->produto_id == 0){
            $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
            $natureza = Produto::firstNatureza($this->empresa_id);
            $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();
            $categoria = Categoria::where('empresa_id', $this->empresa_id)->first();
            $valorVenda = __replace($request->valor);

            $valorCompra = $valorVenda - (($valorVenda*$config->percentual_lucro_padrao)/100);

            $arr = [
                'nome' => $request->nome,
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
                'valor_compra' => $valorCompra,
                'gerenciar_estoque' => 0,
                'estoque_minimo' => 0,
                'referencia' => '',
                'tela_id' => NULL,
                'largura' => $largura,
                'comprimento' => $comprimento,
                'altura' => $altura,
                'peso_liquido' => $peso,
                'peso_bruto' => $peso,
                'empresa_id' => $this->empresa_id,
                'percentual_lucro' => $config->percentual_lucro_padrao,
                'referencia_grade' => Str::random(20),
                "nuvemshop_id" => $nuvemshop_id
            ];

            $produto = Produto::create($arr);

            if($request->estoque){
                $stockMove = new StockMove();
                $stockMove->pluStock($produto->id, __replace($request->estoque), $valorCompra);
            }
        }else{
            $produto = Produto::find($request->produto_id);
            $produto->nuvemshop_id = $nuvemshop_id;
            $produto->save();
        }
    }

    private function _validate(Request $request){
        $rules = [
            'referencia' => 'required',
            'nome' => 'required',
            'descricao' => 'required',
            'valor' => 'required',
        ];

        $messages = [
            'referencia.required' => 'O campo referência é obrigatório.',
            'descricao.required' => 'O campo descricao é obrigatório.',
            'nome.required' => 'O campo nome é obrigatório.',
            'valor.required' => 'O campo valor é obrigatório.',
            'estoque.required' => 'O campo estoque é obrigatório.'
        ];
        $this->validate($request, $rules, $messages);
    }

    public function produto_galeria($id){
        $store_info = session('store_info');
        $api = new \TiendaNube\API($store_info['store_id'], $store_info['access_token'], 'Awesome App ('.$store_info['email'].')');
        $produto = (array)$api->get("products/".$id);
        $produto = $produto['body'];

        $prodBd = Produto::where('nuvemshop_id', $produto->id)
        ->first();

        return view('nuvemshop/produtos_galery')
        ->with('produto', $produto)
        ->with('prodBd', $prodBd)
        ->with('title', 'Galeria do Produto');

    }

    public function delete_imagem($produto_id, $image_id){

        $store_info = session('store_info');
        $api = new \TiendaNube\API($store_info['store_id'], $store_info['access_token'], 'Awesome App ('.$store_info['email'].')');
        try{
            $response = $api->delete("products/$produto_id/images/$image_id");
            session()->flash("mensagem_sucesso", "Imagem removida!");

        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Erro inesperado: " . $e->getMessage());

        }
        return redirect()->back();

    }

    public function save_imagem(Request $request){
        if($request->hasFile('file')){
            $store_info = session('store_info');
            $api = new \TiendaNube\API($store_info['store_id'], $store_info['access_token'], 'Awesome App ('.$store_info['email'].')');

            $image = base64_encode(file_get_contents($request->file('file')->path()));

            $ext = $request->file('file')->getClientOriginalExtension();
            $response = $api->post("products/$request->id/images",[
                "filename" => Str::random(20).".".$ext,
                "attachment" => $image
            ]);

            session()->flash("mensagem_sucesso", "Imagem salva!");
        }else{
            session()->flash("mensagem_erro", "Selecione uma imagem!");
        }

        return redirect()->back();
    }

    private function salvarProdutoBanco2($prod, $variacao = null, $rand, $str_grade = ""){

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $natureza = Produto::firstNatureza($this->empresa_id);
        $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();
        $categoria = Categoria::where('empresa_id', $this->empresa_id)->first();
        $valorVenda = __replace($prod->variants[0]->price);

        $valorCompra = $valorVenda - (($valorVenda*$config->percentual_lucro_padrao)/100);
        $data = [
            'nome' => $prod->name->pt,
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
            "nuvemshop_id" => $prod->id
        ];

        if($variacao == null){
            $data['codBarras'] = $prod->variants[0]->barcode ?? '';
            $data['largura'] = $prod->variants[0]->width ?? '';
            $data['comprimento'] = $prod->variants[0]->depth ?? '';
            $data['altura'] = $prod->variants[0]->height ?? '';
            $data['peso_liquido'] = $prod->variants[0]->weight ?? '';
            $data['peso_bruto'] = $prod->variants[0]->weight ?? '';
        }else{
            $data['codBarras'] = $variacao->barcode ?? '';
            $data['largura'] = $variacao->width ?? '';
            $data['comprimento'] = $variacao->depth ?? '';
            $data['altura'] = $variacao->height ?? '';
            $data['peso_liquido'] = $variacao->weight ?? '';
            $data['peso_bruto'] = $variacao->weight ?? '';
            $data['str_grade'] = $str_grade;
            $data['grade'] = 1;

        }
        $produto = Produto::create($data);

        if($prod->variants[0]->stock){
            $stockMove = new StockMove();
            $stockMove->pluStock($produto->id, __replace($prod->variants[0]->stock), $valorCompra);
        }
    }


}
