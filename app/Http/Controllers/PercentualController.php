<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TributacaoUf;
use App\Models\Produto;
use App\Models\Cliente;

class PercentualController extends Controller
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

		$estados = Cliente::estados();
		$tribucoesCadastradas = TributacaoUf::
		select('tributacao_ufs.uf')
		->join('produtos', 'produtos.id', '=', 'tributacao_ufs.produto_id')
		->where('empresa_id', $this->empresa_id)
		->distinct()
		->get();

		$estados = $this->preparaEstados($estados, $tribucoesCadastradas);

		$this->validaProdutosSemPercentual($tribucoesCadastradas);

		return view('tributos/por_uf')
		->with('estados', $estados)
		->with('title', 'Tributação');
	}

	private function validaProdutosSemPercentual($tribucoesCadastradas){
		$produtos = Produto::
		where('empresa_id', $this->empresa_id)
		->get();

		foreach($produtos as $p){
			foreach($tribucoesCadastradas as $t){
				$ex = TributacaoUf::
				where('produto_id', $p->id)
				->where('uf', $t->uf)
				->exists();

				if(!$ex){
					$temp = TributacaoUf::
					where('uf', $t->uf)
					->join('produtos', 'produtos.id', '=', 'tributacao_ufs.produto_id')
					->where('empresa_id', $this->empresa_id)
					->first();

					$res = TributacaoUf::create([
						'produto_id' => $p->id,
						'uf' => $t->uf,
						'percentual_icms' => $temp->percentual_icms,
						'percentual_red_bc' => $temp->percentual_red_bc,
						'percentual_fcp' => $temp->percentual_fcp,
						'percentual_icms_interno' => $temp->percentual_icms_interno,
					]);

				}
			}
		}
	}

	private function preparaEstados($estados, $tribucoesCadastradas){
		$ufs = [];
		foreach($estados as $e){
			$jaTem = false;
			foreach($tribucoesCadastradas as $t){
				if($e == $t->uf){
					$jaTem = true;
				}
			}
			$ob = [];
			if($jaTem){
				$ob = [
					'uf' => $e,
					'ja_cadastrado' => 1
				];
			}else{
				$ob = [
					'uf' => $e,
					'ja_cadastrado' => 0
				];
			}

			array_push($ufs, $ob);
		}

		return $ufs;
	}

	public function novo($uf){
		return view('tributos/register_por_uf')
		->with('uf', $uf)
		->with('title', 'Tributação');
	}

	public function edit($uf){
		$tributacao = TributacaoUf::
		select('tributacao_ufs.*')
		->join('produtos', 'produtos.id', '=', 'tributacao_ufs.produto_id')
		->where('empresa_id', $this->empresa_id)
		->where('tributacao_ufs.uf', $uf)
		->first();

		return view('tributos/register_por_uf')
		->with('uf', $uf)
		->with('tributacao', $tributacao)
		->with('title', 'Tributação');
	}

	public function save(Request $request){
		$this->_validate($request);
		try{

			$produtos = Produto::
			where('empresa_id', $this->empresa_id)
			->get();

			foreach($produtos as $p){
				TributacaoUf::where('produto_id', $p->id)
				->where('uf', $request->uf)
				->delete();

				TributacaoUf::create([
					'produto_id' => $p->id,
					'uf' => $request->uf,
					'percentual_icms' => __replace($request->percentual_icms),
					'percentual_red_bc' => __replace($request->percentual_red_bc),
					'percentual_fcp' => __replace($request->percentual_fcp),
					'percentual_icms_interno' => __replace($request->percentual_icms_interno)
				]);
			}
			session()->flash('mensagem_sucesso', 'Percentual atribuído nos produtos UF ' . $request->uf);

			return redirect('/percentualuf');
		}catch(\Exception $e){
			session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
			return redirect('/percentualuf');
		}
	}

	public function update(Request $request){
		$this->_validate($request);
		try{
			$produtos = Produto::
			where('empresa_id', $this->empresa_id)
			->get();

			TributacaoUf::
			join('produtos', 'produtos.id', '=', 'tributacao_ufs.produto_id')
			->where('empresa_id', $this->empresa_id)
			->where('tributacao_ufs.uf', $request->uf)
			->delete();

			foreach($produtos as $p){
				TributacaoUf::create([
					'produto_id' => $p->id,
					'uf' => $request->uf,
					'percentual_icms' => __replace($request->percentual_icms),
					'percentual_red_bc' => __replace($request->percentual_red_bc),
					'percentual_fcp' => __replace($request->percentual_fcp),
					'percentual_icms_interno' => __replace($request->percentual_icms_interno)
				]);
			}
			session()->flash('mensagem_sucesso', 'Percentual atribuído nos produtos UF ' . $request->uf);

			return redirect('/percentualuf');
		}catch(\Exception $e){
			session()->flash('mensagem_erro', 'Erro: ' . $e->getMessage());
			return redirect('/percentualuf');

		}
	}

	private function _validate(Request $request){
		$rules = [
			'percentual_icms' => 'required',
			'percentual_red_bc' => 'required',
			'percentual_fcp' => 'required',
			'percentual_icms_interno' => 'required',
		];

		$messages = [
			'percentual_icms.required' => 'Campo obrigatório.',
			'percentual_red_bc.required' => 'Campo obrigatório.',
			'percentual_fcp.required' => 'Campo obrigatório.',
			'percentual_icms_interno.required' => 'Campo obrigatório.',
		];
		$this->validate($request, $rules, $messages);
	}

	public function verProdutos($uf){
		$data = TributacaoUf::
		select('tributacao_ufs.*')
		->join('produtos', 'produtos.id', '=', 'tributacao_ufs.produto_id')
		->where('empresa_id', $this->empresa_id)
		->where('tributacao_ufs.uf', $uf)
		->paginate(40);

		return view('tributos/ver_produtos')
		->with('uf', $uf)
		->with('links', true)
		->with('data', $data)
		->with('title', 'Tributação');
	}

	public function editPercentual($id){
		$tributacao = TributacaoUf::find($id);
		if(valida_objeto($tributacao->produto)){
			return view('tributos/edit_percentual')
			->with('tributacao', $tributacao)
			->with('title', 'Editar percentual');
		}else{
			return redirect('/403');
		}
	}

	public function updatePercentualSingle(Request $request){
		$this->_validate($request);
		$tributacao = TributacaoUf::find($request->id);
		if(valida_objeto($tributacao->produto)){

			$tributacao->percentual_icms = __replace($request->percentual_icms);
			$tributacao->percentual_red_bc = __replace($request->percentual_red_bc);
			$tributacao->percentual_fcp = __replace($request->percentual_fcp);
			$tributacao->percentual_icms_interno = __replace($request->percentual_icms_interno);
			$tributacao->save();
			session()->flash('mensagem_sucesso', 'Percentual atualizado');
			return redirect('/percentualuf/verProdutos/'. $tributacao->uf);
		}else{
			return redirect('/403');
		}

	}
}
