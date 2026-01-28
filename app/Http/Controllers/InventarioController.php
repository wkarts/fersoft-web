<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inventario;
use App\Models\ItemInventario;
use App\Models\Produto;
use App\Models\ConfigNota;
use Dompdf\Dompdf;

class InventarioController extends Controller
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
		$inventarios = Inventario::
		orderBy('id', 'desc')
		->where('empresa_id', $this->empresa_id)
		->get();

		return view('inventarios/list')
		->with('title', 'Inventários')
		->with('inventarios', $inventarios);
	}

	public function filtro(Request $request){

		$dataInicial = $request->data_inicial;
		$dataFinal = $request->data_final;
		$tipo = $request->tipo;
		$referencia = $request->referencia;

		$inventarios = Inventario::
		orderBy('id', 'desc')
		->where('empresa_id', $this->empresa_id);

		if($tipo != 'todos'){
			$inventarios->where('tipo', $tipo);
		}
		if($referencia){
			$inventarios->where('referencia', 'LIKE', "%$referencia%");
		}
		if($dataInicial){
			$inventarios->where('inicio', $this->parseDate($dataInicial));
		}
		if($dataFinal){
			$inventarios->where('fim', $this->parseDate($dataFinal));
		}

		$inventarios = $inventarios->get();

		return view('inventarios/list')
		->with('title', 'Inventários')
		->with('referencia', $referencia)
		->with('dataInicial', $dataInicial)
		->with('dataFinal', $dataFinal)
		->with('tipo', $tipo)
		->with('inventarios', $inventarios);
	}

	private function parseDate($date, $plusDay = false){
		if($plusDay == false)
			return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
		else
			return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
	}

	public function new(){
		return view('inventarios/register')
		->with('title', 'Novo inventário');
	}

	public function save(Request $request){
		$this->_validate($request);
		try{
			$request->merge(
				[
					'inicio' => \Carbon\Carbon::parse(str_replace("/", "-", $request->inicio))->format('Y-m-d'),
					'fim' => \Carbon\Carbon::parse(str_replace("/", "-", $request->fim))->format('Y-m-d'),
					'observacao' => $request->observacao ?? '',
					'status' => 1
				]
			);
			Inventario::create($request->all());
			session()->flash("mensagem_sucesso", "Inventário salvo!");

		}catch(\Exception $e){
			session()->flash("mensagem_erro", "Erro: " . $e->getMessage());
		}

		return redirect('/inventario');
	}

	private function _validate(Request $request){
		$rules = [
			'inicio' => 'required',
			'fim' => 'required',
			'tipo' => 'required',
			'referencia' => 'required|max:30'
		];

		$messages = [
			'inicio.required' => 'O campo data de ínicio é obrigatório.',
			'fim.required' => 'O campo data de término é obrigatório.',
			'tipo.required' => 'O campo tipo é obrigatório.',
			'referencia.required' => 'O campo referência é obrigatório.',
			'referencia.max' => 'Máximo de 30 caracteres.'
		];
		$this->validate($request, $rules, $messages);
	}

	public function edit($id){
		$inventario = Inventario::find($id);
		if(valida_objeto($inventario)){
			return view('inventarios/register')
			->with('inventario', $inventario)
			->with('title', 'Editar inventário');
		}else{
			return redirect('/403');
		}
	}

	public function update(Request $request){
		$this->_validate($request);
		try{
			$inventario = Inventario::find($request->id);
			$inventario->observacao = $request->observacao ?? '';
			$inventario->inicio = \Carbon\Carbon::parse(str_replace("/", "-", $request->inicio))->format('Y-m-d');
			$inventario->fim = \Carbon\Carbon::parse(str_replace("/", "-", $request->fim))->format('Y-m-d');
			$inventario->tipo = $request->tipo;
			$inventario->referencia = $request->referencia;

			$inventario->save();
			session()->flash("mensagem_sucesso", "Inventário salvo!");

		}catch(\Exception $e){
			session()->flash("mensagem_erro", "Erro: " . $e->getMessage());
		}

		return redirect('/inventario');
	}

	public function delete($id){
		try{
			$inventario = Inventario::find($id);
			if(valida_objeto($inventario)){
				ItemInventario::
				where('inventario_id', $id)
				->delete();
				$inventario->delete();
				session()->flash("mensagem_sucesso", "Registro removido!");
			}
		}catch(\Exception $e){
			session()->flash("mensagem_erro", "Erro: " . $e->getMessage());
		}
		return redirect()->back();
	}

	public function apontar($id){
		try{
			$inventario = Inventario::find($id);
			if(valida_objeto($inventario)){
				$produtos = Produto::
				where('empresa_id', $this->empresa_id)
				->orderBy('nome')
				->get();
				return view('inventarios/apontar')
				->with('inventario', $inventario)
				->with('produtos', $produtos)
				->with('title', 'Apontamento do inventário');
			}
		}catch(\Exception $e){
			echo "Erro: " . $e->getMessage();
		}
	}

	public function itens($id){
		try{
			$inventario = Inventario::find($id);
			if(valida_objeto($inventario)){

				$itens = ItemInventario::
				where('inventario_id', $id)
				->paginate(50);

				$totaliza = $this->totaliza($itens);

				return view('inventarios/itens')
				->with('inventario', $inventario)
				->with('totaliza', $totaliza)
				->with('itens', $itens)
				->with('links', true)
				->with('title', 'Apontamento do inventário');
			}
		}catch(\Exception $e){
			echo "Erro: " . $e->getMessage();
		}
	}

	public function pesquisaItem(Request $request){
		try{
			$inventario = Inventario::find($request->inventario_id);
			if(valida_objeto($inventario)){

				$itens = ItemInventario::
				select('item_inventarios.*')
				->where('inventario_id', $request->inventario_id)
				->join('produtos', 'produtos.id', '=', 'item_inventarios.produto_id')
				->where('produtos.nome', 'LIKE', "%$request->pesquisa%")
				->get();

				$totaliza = $this->totaliza($itens);

				return view('inventarios/itens')
				->with('inventario', $inventario)
				->with('totaliza', $totaliza)
				->with('itens', $itens)
				->with('pesquisa', $request->pesquisa)
				->with('title', 'Apontamento do inventário');
			}
		}catch(\Exception $e){
			echo "Erro: " . $e->getMessage();
		}
	}

	private function totaliza($itens){
		$soma['compra'] = 0;
		$soma['venda'] = 0;
		$soma['qtd'] = 0;
		foreach($itens as $e){
			$soma['compra'] += $e->produto->valor_compra * $e->quantidade;
			$soma['venda'] += $e->produto->valor_venda * $e->quantidade;
			$soma['qtd'] += $e->quantidade;
		}
		return $soma;
	}

	public function apontarSave(Request $request){
		try{
			$this->_validateItem($request);

			$request->merge(['quantidade' => str_replace(",", ".", $request->quantidade)]);
			$request->merge(['observacao' => $request->observacao ?? '']);
			$request->merge(['usuario_id' => get_id_user()]);
			ItemInventario::create($request->all());
			session()->flash("mensagem_sucesso", "Item apontado!");

		}catch(\Exception $e){
			session()->flash("mensagem_erro", "Erro: " . $e->getMessage());
		}
		return redirect()->back();
	}

	private function _validateItem(Request $request){
		$rules = [
			'produto_id' => 'required',
			'quantidade' => 'required',
			'estado' => 'required'
		];

		$messages = [
			'produto_id.required' => 'O campo produto é obrigatório.',
			'quantidade.required' => 'O campo quantidade é obrigatório.',
			'estado.required' => 'O campo estado é obrigatório.'
		];

		$this->validate($request, $rules, $messages);
	}

	public function itensDelete($id){
		try{
			$item = ItemInventario::find($id);
			if(valida_objeto($item->inventario)){
				$item->delete();
			}
		}catch(\Exception $e){
			session()->flash("mensagem_erro", "Erro: " . $e->getMessage());
		}
		return redirect()->back();
	}

	public function imprimir($id){
		try{
			$inventario = Inventario::find($id);
			if(valida_objeto($inventario)){
				$itens = ItemInventario::
				where('inventario_id', $id)
				->get();

				$totaliza = $this->totaliza($itens);
				$config = ConfigNota::
				where('empresa_id', $this->empresa_id)
				->first();

				$p = view('inventarios/print')
				->with('inventario', $inventario)
				->with('totaliza', $totaliza)
				->with('config', $config)
				->with('itens', $itens);

				// return $p;

				$domPdf = new Dompdf(["enable_remote" => true]);
				$domPdf->loadHtml($p);

				$pdf = ob_get_clean();

				$domPdf->setPaper("A4");
				$domPdf->render();
				$domPdf->stream("Inventario $id.pdf");
			}
		}catch(\Exception $e){
			echo "Erro: " . $e->getMessage();
		}
	}

	public function alterarStatus($id){
		try{
			$inventario = Inventario::find($id);
			if(valida_objeto($inventario)){
				$inventario->status = !$inventario->status;
				$inventario->save();
				session()->flash("mensagem_sucesso", "Status alterado!");
				return redirect()->back();
			}
		}catch(\Exception $e){
			session()->flash("mensagem_erro", "Erro: " . $e->getMessage());
		}
		return redirect()->back();
	}

	public function imprimirFiltro(Request $request){
		try{
			$inventario = Inventario::find($request->inventario_id);
			if(valida_objeto($inventario)){
				$itens = ItemInventario::
				select('item_inventarios.*')
				->where('inventario_id', $request->inventario_id)
				->join('produtos', 'produtos.id', '=', 'item_inventarios.produto_id')
				->where('produtos.nome', 'LIKE', "%$request->pesquisa%")
				->get();

				$totaliza = $this->totaliza($itens);
				$config = ConfigNota::
				where('empresa_id', $this->empresa_id)
				->first();

				$p = view('inventarios/print')
				->with('inventario', $inventario)
				->with('totaliza', $totaliza)
				->with('config', $config)
				->with('itens', $itens);

				// return $p;

				$domPdf = new Dompdf(["enable_remote" => true]);
				$domPdf->loadHtml($p);

				$pdf = ob_get_clean();

				$domPdf->setPaper("A4");
				$domPdf->render();
				$domPdf->stream("Inventario $request->inventario_id.pdf");
			}
		}catch(\Exception $e){
			echo "Erro: " . $e->getMessage();
		}
	}

	public function comparaEstoque($id){
		try{
			$inventario = Inventario::find($id);
			if(valida_objeto($inventario)){
				$produtos = Produto::
				where('empresa_id', $this->empresa_id)
				->orderBy('nome')
				->get();

				$produtosSemContar = $this->produtosSemContar($produtos, $inventario);

				$itens = ItemInventario::
				where('inventario_id', $id)
				->paginate(50);

				return view('inventarios/compara')
				->with('inventario', $inventario)
				->with('itens', $itens)
				->with('links', true)
				->with('produtosSemContar', $produtosSemContar)
				->with('title', 'Apontamento do inventário');
			}
		}catch(\Exception $e){
			echo "Erro: " . $e->getMessage();
		}
	}

	private function produtosSemContar($produtos, $inventario){
		$produtosSemContar = [];
		foreach($produtos as $p){
			$achou = false;
			foreach($inventario->itens as $i){
				if($p->id == $i->produto_id){
					$achou = true;
				}
			}
			if(!$achou){
				array_push($produtosSemContar, $p);
			}
		}
		return $produtosSemContar;
	}

	public function pendentes($id){
		try{
			$inventario = Inventario::find($id);
			if(valida_objeto($inventario)){
				$produtos = Produto::
				where('empresa_id', $this->empresa_id)
				->orderBy('nome')
				->get();

				$produtosSemContar = $this->produtosSemContar($produtos, $inventario);

				return view('inventarios/sem_contar')
				->with('inventario', $inventario)
				->with('produtosSemContar', $produtosSemContar)
				->with('title', 'Apontamento do inventário');
			}
		}catch(\Exception $e){
			echo "Erro: " . $e->getMessage();
		}
	}

	public function imprimirPendentes($id){
		try{
			$inventario = Inventario::find($id);
			if(valida_objeto($inventario)){
				
				$produtos = Produto::
				where('empresa_id', $this->empresa_id)
				->orderBy('nome')
				->get();

				$produtosSemContar = $this->produtosSemContar($produtos, $inventario);

				$config = ConfigNota::
				where('empresa_id', $this->empresa_id)
				->first();

				$p = view('inventarios/print_pendentes')
				->with('inventario', $inventario)
				->with('config', $config)
				->with('produtosSemContar', $produtosSemContar);

				// return $p;

				$domPdf = new Dompdf(["enable_remote" => true]);
				$domPdf->loadHtml($p);

				$pdf = ob_get_clean();

				$domPdf->setPaper("A4");
				$domPdf->render();
				$domPdf->stream("Itens pendentes $id.pdf");
			}
		}catch(\Exception $e){
			echo "Erro: " . $e->getMessage();
		}
	}

	public function imprimirCompara($id){
		try{
			$inventario = Inventario::find($id);
			if(valida_objeto($inventario)){
				$produtos = Produto::
				where('empresa_id', $this->empresa_id)
				->orderBy('nome')
				->get();

				$itens = ItemInventario::
				where('inventario_id', $id)
				->get();
				
				$config = ConfigNota::
				where('empresa_id', $this->empresa_id)
				->first();

				$p = view('inventarios/print_compara')
				->with('inventario', $inventario)
				->with('config', $config)
				->with('itens', $itens);

				// return $p;

				$domPdf = new Dompdf(["enable_remote" => true]);
				$domPdf->loadHtml($p);

				$pdf = ob_get_clean();

				$domPdf->setPaper("A4");
				$domPdf->render();
				$domPdf->stream("Inventário $id.pdf");
			}
		}catch(\Exception $e){
			echo "Erro: " . $e->getMessage();
		}
	}

	public function produtoJaAdicionadoInventario(Request $request){
		$produtoId = $request->produto;
		$inventarioId = $request->inventario;

		$item = ItemInventario::
		where('produto_id', $produtoId)
		->where('inventario_id', $inventarioId)
		->first();

		if($item == null) return response()->json('ok', 200);
		return response()->json('ja incluso', 403);
	}

}
