<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Produto;
use App\Models\Locacao;
use App\Models\ConfigNota;
use App\Models\ItemLocacao;
use App\Models\ItemLocacaoDisponibilidade;
use Dompdf\Dompdf;
use Dompdf\Options;

class LocacaoController extends Controller
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
		$locacoes = Locacao::
		where('empresa_id', $this->empresa_id)
		->orderBy('id', 'desc')
		->paginate(30);

		return view('locacao/list')
		->with('locacoes', $locacoes)
		->with('links', true)
		->with('title', 'Locações');
	}

	public function pesquisa(Request $request){
		$produto = null;
		$locacoes = Locacao::
		select('locacaos.*')
		->where('locacaos.empresa_id', $this->empresa_id);

		if($request->cliente){
			$locacoes->join('clientes', 'clientes.id', '=', 'locacaos.cliente_id')
			->where('clientes.razao_social', 'LIKE', "%$request->cliente%");
		}

		if($request->data_inicial && $request->data_final){
			// $locacoes->whereBetween('inicio', [
			// 	$this->parseDate($request->data_inicial) . " 00:00:00", 
			// 	$this->parseDate($request->data_final) . " 23:59:59"
			// ]);
			$locacoes->whereDate('inicio', '>=', $this->parseDate($request->data_inicial))
			->whereDate('fim', '<=', $this->parseDate($request->data_final));
		}

		if($request->estado){
			$locacoes->where('status', $request->estado);
		}

		if($request->produto){
			$produto = Produto::findOrFail($request->produto);
			$locacoes->join('item_locacaos', 'item_locacaos.locacao_id', '=', 'locacaos.id')
			->where('item_locacaos.produto_id', $request->produto);
		}

		$locacoes = $locacoes->orderBy('locacaos.id', 'desc')
		->get();

		return view('locacao/list')
		->with('locacoes', $locacoes)
		->with('produto', $produto)
		->with('cliente', $request->cliente)
		->with('dataInicial', $request->data_inicial)
		->with('dataFinal', $request->data_final)
		->with('estado', $request->estado)
		->with('pesquisa', true)
		->with('title', 'Locações');
	}

	public function relatorio(Request $request){

		$locacoes = Locacao::
		select('locacaos.*')
		->where('locacaos.empresa_id', $this->empresa_id);

		if($request->cliente){
			$locacoes->join('clientes', 'clientes.id', '=', 'locacaos.cliente_id')
			->where('clientes.razao_social', 'LIKE', "%$request->cliente%");
		}

		if($request->data_inicial && $request->data_final){
			// $locacoes->whereBetween('inicio', [
			// 	$this->parseDate($request->data_inicial), 
			// 	$this->parseDate($request->data_final, true)
			// ]);
			$locacoes->whereDate('inicio', '>=', $this->parseDate($request->data_inicial))
			->whereDate('fim', '<=', $this->parseDate($request->data_final));
		}

		if($request->estado){
			$locacoes->where('status', $request->estado);
		}

		if($request->produto){
			$produto = Produto::findOrFail($request->produto);
			$locacoes->join('item_locacaos', 'item_locacaos.locacao_id', '=', 'locacaos.id')
			->where('item_locacaos.produto_id', $request->produto);
		}

		$locacoes = $locacoes->orderBy('locacaos.inicio')
		->get();

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$p = view('locacao/print')
		->with('locacoes', $locacoes)
		->with('cliente', $request->cliente)
		->with('dataInicial', $request->data_inicial)
		->with('dataFinal', $request->data_final)
		->with('estado', $request->estado)
		->with('config', $config);

		// return $p;

		$domPdf = new Dompdf(["enable_remote" => true]);
		$domPdf->loadHtml($p);

		$pdf = ob_get_clean();

		$domPdf->setPaper("A4");
		$domPdf->render();
		$domPdf->stream("Locacao.pdf", array("Attachment" => false));

		
	}

	public function novo(){

		$clientes = Cliente::
		where('empresa_id', $this->empresa_id)
		->where('inativo', false)
		->get();

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		if($config == null){
			session()->flash('mensagem_erro', 'Configure o emitente');
			return redirect('configNF');
		}

		return view('locacao/register')
		->with('title', 'Nova locação')
		->with('config', $config)
		->with('pessoaFisicaOuJuridica', true)
		->with('clientes', $clientes);

	}

	public function edit($id){

		$clientes = Cliente::
		where('empresa_id', $this->empresa_id)
		->where('inativo', false)
		->get();

		$locacao = Locacao::find($id);

		if(valida_objeto($locacao)){

			$config = ConfigNota::
			where('empresa_id', $this->empresa_id)
			->first();
			
			return view('locacao/register')
			->with('title', 'Editar locação')
			->with('locacao', $locacao)
			->with('config', $config)
			->with('clientes', $clientes);
		}else{
			return redirect('/403');
		}

	}

	public function salvar(Request $request){
		$this->_validate($request);

		if($request->id > 0){
			//update

			$locacao = Locacao::find($request->id);

			$locacao->observacao = $request->observacao ?? '';
			$locacao->inicio = $this->parseDate($request->inicio);
			$locacao->fim = $this->parseDate($request->fim);
			$locacao->cliente_id = $request->cliente_id;

			ItemLocacaoDisponibilidade::where('locacao_id', $locacao->id)
			->update([
				'data' => $this->parseDate($request->inicio)
			]);

			try{

				$locacao->save();
				return redirect('/locacao/itens/'. $locacao->id);
			}catch(\Exception $e){
				session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
				return redirect()->back();
			}
		}else{

			$request->merge(['observacao' => $request->observacao ?? '']);
			$request->merge(['inicio' => $this->parseDate($request->inicio) ]);
			$request->merge(['fim' => $this->parseDate($request->fim) ]);
			try{

				$l = Locacao::create($request->all());
				return redirect('/locacao/itens/'. $l->id);
			}catch(\Exception $e){
				session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
				return redirect()->back();
			}
		}

	}

	private function parseDate($date, $plusDay = false){
		if($plusDay == false)
			return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
		else
			return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
	}

	private function _validate(Request $request){
		$rules = [
			'cliente_id' => 'required|numeric|min:1',
			'inicio' => 'required',
			'fim' => 'required'
		];

		$messages = [
			'cliente_id.required' => 'O campo cliente é obrigatório.',
			'cliente_id.min' => 'O campo cliente é obrigatório.',
			'inicio.required' => 'O campo inicio é obrigatório.',
			'fim.required' => 'O campo fim é obrigatório.',
		];
		$this->validate($request, $rules, $messages);
	}

	public function itens($id){
		$locacao = Locacao::find($id);
		if(valida_objeto($locacao)){

			$produtos = Produto::
			where('empresa_id', $this->empresa_id)
			->where('valor_locacao', '>', 0)
			->get();

			return view('locacao/itens')
			->with('title', 'Locação itens')
			->with('produtos', $produtos)
			->with('locacao', $locacao);
		}else{
			return redirect('/403');
		}
	}

	public function validaEstoque($produto_id, $locacao_id){
		try{
			$produto = Produto::find($produto_id);
			$locacao = Locacao::find($locacao_id);

			$estoqueTotal = $produto->estoqueAtual();

			$diferenca = strtotime($locacao->fim) - strtotime($locacao->inicio);
			$dias = floor($diferenca / (60 * 60 * 24)); 

			$semEstoqueData = "";
			$date = $locacao->inicio;
			$estoqueDisponivel = $produto->estoqueAtual();

			$arrDatas = [];

			for($i=0; $i<=$dias; $i++){
				$countTemp = ItemLocacaoDisponibilidade::where('produto_id', $produto_id)
				->whereDate('data', $date)
				->count();
				if($countTemp >= $estoqueDisponivel && $semEstoqueData == ""){
					$semEstoqueData = $date;
				}
				$date = date('Y-m-d', strtotime("+1 days",strtotime($date)));
			}


			$valor_locacao = $produto->valor_locacao;

			$arr = [
				'valor_locacao' => $valor_locacao,
				'semEstoqueData' => $semEstoqueData != "" ? \Carbon\Carbon::parse($semEstoqueData)->format('d/m/Y') : ""
			];

			return response()->json($arr, 200);

		}catch(\Exception $e){
			return response()->json("erro: ". $e->getMessage(), 401);
		}

	}

	public function salvarItem(Request $request){
		$this->_validateItem($request);

		$request->merge(['observacao' => $request->observacao ?? '']);
		try{
			$locacao = Locacao::find($request->locacao_id);

			$diferenca = strtotime($locacao->fim) - strtotime($locacao->inicio);
			$dias = floor($diferenca / (60 * 60 * 24));

			$l = ItemLocacao::create($request->all());
			$date = $locacao->inicio;
			for($i=0; $i<=$dias; $i++){
				ItemLocacaoDisponibilidade::create([
					'produto_id' => $request->produto_id,
					'data' => $date,
					'locacao_id' => $locacao->id
				]);
				$date = date('Y-m-d', strtotime("+1 days",strtotime($date)));
			}

			$locacao->total = $this->somaItens($locacao);
			$locacao->save();
			session()->flash('mensagem_sucesso', 'Item adicionado');

			return redirect()->back();
		}catch(\Exception $e){
			session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
			return redirect()->back();
		}
	}

	private function somaItens($locacao){
		$total = 0;
		foreach($locacao->itens as $i){
			$total += $i->valor;
		}
		return $total;
	}

	public function delete($id){
		$locacao = Locacao::find($id);

		if(valida_objeto($locacao)){
			ItemLocacaoDisponibilidade::where('locacao_id', $locacao->id)->delete();
			$locacao->delete();

			session()->flash('mensagem_sucesso', 'Registro removido');
			return redirect()->back();
		}else{
			return redirect('/403');
		}

	}

	public function saveObs(Request $request){
		try{
			$locacao = Locacao::find($request->id);

			$locacao->observacao = $request->observacao;
			$locacao->save();
			session()->flash('mensagem_sucesso', 'Registro removido');
		}catch(\Exception $e){
			session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
		}

		return redirect()->back();

	}

	public function deleteItem($id){
		$item = ItemLocacao::find($id);
		$locacao = Locacao::find($item->locacao_id);
		if(valida_objeto($locacao)){

			ItemLocacaoDisponibilidade::where('locacao_id', $locacao->id)
			->where('produto_id', $item->produto_id)->delete();
			$item->delete();
			$locacao->total = $this->somaItens($locacao);
			$locacao->save();
			session()->flash('mensagem_sucesso', 'Item removido');

			return redirect()->back();
		}else{
			return redirect('/403');
		}

	}

	private function _validateItem(Request $request){
		$rules = [
			'produto_id' => 'required|numeric|min:1',
			'valor' => 'required'
		];

		$messages = [
			'produto_id.required' => 'O campo produto é obrigatório.',
			'produto_id.min' => 'O campo produto é obrigatório.',
			'valor.required' => 'O campo valor é obrigatório.',
		];
		$this->validate($request, $rules, $messages);
	}

	public function alterarStatus($id){
		try{
			$locacao = Locacao::find($id);
			if(valida_objeto($locacao)){

				$locacao->status = true;
				$locacao->save();
				session()->flash('mensagem_sucesso', 'Status alterado');

				ItemLocacaoDisponibilidade::where('locacao_id', $locacao->id)->delete();

				return redirect()->back();
			}else{
				return redirect('/403');
			}
		}catch(\Exception $e){
			session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
			return redirect()->back();
		}
	}

	public function comprovante($id){
		try{
			$locacao = Locacao::find($id);
			if(valida_objeto($locacao)){

				$config = ConfigNota::
				where('empresa_id', $this->empresa_id)
				->first();

				$p = view('locacao/comprovante2')
				->with('config', $config)
				->with('locacao', $locacao);

				// return $p;

				// $options = new Options();
				// $options->set('isRemoteEnabled', TRUE);
				$domPdf = new Dompdf(["enable_remote" => true]);
				$domPdf->loadHtml($p);

				$pdf = ob_get_clean();

				$domPdf->setPaper("A4");
				$domPdf->render();
				$domPdf->stream("relatorio_locacao_$id.pdf", array("Attachment" => false));

			// 	$domPdf->loadHtml($p);


			// 	$domPdf->setPaper("A4");
			// 	$domPdf->render();

			// // $domPdf->stream("orcamento.pdf", ["Attachment" => false]);
			// 	$domPdf->stream("relatorio_locacao_$locacao->id.pdf", ["Attachment" => false]);

			}else{
				return redirect('/403');
			}
		}catch(\Exception $e){
			session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
			return redirect()->back();
		}
	}

}
