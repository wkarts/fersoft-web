<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ProdutoEcommerce;
use App\Models\CarrosselEcommerce;
use App\Models\CategoriaProdutoEcommerce;
use App\Models\SubCategoriaEcommerce;
use App\Models\CurtidaProdutoEcommerce;
use App\Models\ClienteEcommerce;
use App\Models\ConfigEcommerce;

class ProdutoController extends Controller
{
	public function categoria($id){
		$categoria = CategoriaProdutoEcommerce::
		where('empresa_id', request()->empresa_id)
		->where('id', $id)
		->first();

		return response()->json($categoria, 200);
	}

	public function subcategorias($id){
		$subs = SubCategoriaEcommerce::
		where('categoria_id', $id)
		->get();

		return response()->json($subs, 200);
	}

	public function destaques(Request $request){
		$produtos = ProdutoEcommerce::
		select('produto_ecommerces.*')
		->join('produtos', 'produtos.id', '=', 'produto_ecommerces.produto_id')
		->where('produto_ecommerces.empresa_id', $request->empresa_id)
		->where('produto_ecommerces.destaque', true)
		->groupBy('produtos.referencia_grade')
		->get();

		foreach($produtos as $p){
			$p->produto;
		}

		return response()->json($produtos, 200);
	}

	public function novosProdutos(Request $request){
		$produtos = ProdutoEcommerce::
		select('produto_ecommerces.*')
		->where('produto_ecommerces.empresa_id', $request->empresa_id)
		->join('produtos', 'produtos.id', '=', 'produto_ecommerces.produto_id')
		// ->where('novo', true)
		// ->where('ecommerce', true)
		->groupBy('produtos.referencia_grade')
		->orderBy('created_at', 'desc')
		->limit(20)
		->get();

		foreach($produtos as $p){
			$p->produto;
		}

		return response()->json($produtos, 200);
	}

	public function maisVendidos(Request $request){
		
		$produtos = ProdutoEcommerce::
		selectRaw('produto_ecommerces.*, sum(item_pedido_ecommerces.quantidade) as soma')
		->join('item_pedido_ecommerces', 'item_pedido_ecommerces.produto_id',
			'=', 'produtos.id')
		->join('pedido_ecommerces', 'item_pedido_ecommerces.pedido_id',
			'=', 'pedido_ecommerces.id')
		->where('produto_ecommerces.empresa_id', $request->empresa_id)
		->where('pedido_ecommerces.status', '!=', 0)
		->groupBy('produto_ecommerces.id')
		->orderBy('soma')
		->limit(20)
		->get();

		return response()->json($produtos, 200);
	}

	public function pesquisa(Request $request){
		$produtos = ProdutoEcommerce::
		where('empresa_id', $request->empresa_id)
		->where('name', 'LIKE', "%$request->pesquisa%")
		->get();

		return response()->json($produtos, 200);
	}

	public function categoriasEmDestaque(Request $request){
		$categorias = CategoriaProdutoEcommerce::
		where('empresa_id', $request->empresa_id)
		->where('destaque', 1)
		->get();

		return response()->json($categorias, 200);
	}

	public function categorias(Request $request){
		$categorias = CategoriaProdutoEcommerce::
		where('empresa_id', $request->empresa_id)
		->get();

		return response()->json($categorias, 200);
	}

	public function carrossel(Request $request){
		$carrossel = CarrosselEcommerce::
		where('empresa_id', $request->empresa_id)
		->get();

		return response()->json($carrossel, 200);
	}

	public function porCategoria(Request $request, $id){
		$produtos = ProdutoEcommerce::
		select('produto_ecommerces.*')
		->join('produtos', 'produtos.id', '=', 'produto_ecommerces.produto_id')
		->where('produto_ecommerces.empresa_id', $request->empresa_id)
		->where('produto_ecommerces.categoria_id', $id)
		->groupBy('produtos.referencia_grade')
		->get();

		foreach($produtos as $p){
			$p->produto;
			$p->categoria;
			$p->subCategoria;
		}

		return response()->json($produtos, 200);
	}

	public function porId(Request $request){
		$produto = ProdutoEcommerce::
		where('empresa_id', $request->empresa_id)
		->where('id', $request->id)
		->first();

		$produto->categoria;
		$produto->produto;

		if($produto->produto->grade){
			$produto->variacao = true;
			//monta variacoes
			$variacoes = ProdutoEcommerce::
			select('produto_ecommerces.*')
			->join('produtos', 'produtos.id', '=', 'produto_ecommerces.produto_id')
			->where('produtos.referencia_grade', $produto->produto->referencia_grade)
			->get();
			$temp = [];
			foreach($variacoes as $v){
				$arr = [
					'id' => $v->id,
					'variacao' => $v->produto->str_grade,
					'valor' => $v->valor,
					'image' => $v->image_url
				];

				array_push($temp, $arr);
			}

			$produto->variacoes = $temp;
		}


		// foreach($produto->variations as $v){
		// 	$v->media;
		// }
		$temp = [];
		// array_push($temp, $produto->image_url);
		// if(sizeof($produto->galeria) > 1){
		foreach($produto->galeria as $i){
			array_push($temp, $i->image_url);
		}
		// }

		$cliente = ClienteEcommerce::
		where('token', $request->token)
		->first();

		$curtida = null;
		if($cliente){
			$curtida = CurtidaProdutoEcommerce::
			where('produto_id', $request->id)
			->where('cliente_id', $cliente->id)
			->first();
		}

		$config = ConfigEcommerce::
		where('empresa_id', $request->empresa_id)
		->first();

		$produto = $this->preparaValorDescontoSingle($produto, $config);

		$produto->favorito = $curtida != null ? true : false;
		$produto->imagensAll = $temp;

		return response()->json($produto, 200);
	}

	private function preparaValorDescontoSingle($p, $config){

		if($config->desconto_padrao_pix > 0){
			$p->valor_pix = $p->valor - (($p->valor*$config->desconto_padrao_pix)/100);
		}else{
			$p->valor_pix = 0;
		}

		if($config->desconto_padrao_cartao > 0){
			$p->valor_cartao = $p->valor - 
			(($p->valor*$config->desconto_padrao_cartao)/100);
		}else{
			$p->valor_cartao = 0;
		}

		if($config->desconto_padrao_boleto > 0){
			$p->valor_boleto = $p->valor - 
			(($p->valor*$config->desconto_padrao_boleto)/100);
		}else{
			$p->valor_boleto = 0;
		}

		return $p;
	}

	public function favorito(Request $request){

		try{
			$cliente = ClienteEcommerce::
			where('token', $request->token)
			->first();

			$curtida = CurtidaProdutoEcommerce::
			where('produto_id', $request->produtoId)
			->where('cliente_id', $cliente->id)
			->first();

			if($curtida != null){
				$curtida->delete();
				return response()->json("delete", 200);
			}else{
				CurtidaProdutoEcommerce::create(
					[
						'produto_id' => $request->produtoId,
						'cliente_id' => $cliente->id 
					]
				);
				return response()->json("inserido", 201);

			}
		}catch(\Exception $e){
			return response()->json($e->getMessage(), 401);
		}

	}
}
