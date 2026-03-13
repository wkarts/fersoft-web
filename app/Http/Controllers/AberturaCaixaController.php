<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AberturaCaixa;
use App\Models\VendaCaixa;
use App\Models\Venda;
use App\Models\Usuario;
use App\Models\Nfse;
use App\Models\ConfigNota;
use App\Models\SuprimentoCaixa;
use App\Models\SangriaCaixa;
use App\Models\ContaReceber;
use App\Models\ContaPagar;
use App\Models\ContaEmpresa;
use Dompdf\Dompdf;
use Dompdf\Options;
//use NFePHP\DA\NFe\ComprovanteFechamentoCaixa;
use App\Prints\ComprovanteFechamentoCaixa;
use App\Prints\CaixaPrint80;


class AberturaCaixaController extends Controller
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

	public function abrir(Request $request){

		$ultimaVendaNfce = VendaCaixa::
		where('empresa_id', $this->empresa_id)
		->orderBy('id', 'desc')->first();

		$ultimaVendaNfe = Venda::
		where('empresa_id', $this->empresa_id)
		->orderBy('id', 'desc')->first();
		$verify = $this->verificaAberturaCaixa();

		$conta_id = $request->conta_id;
		if($verify == -1){
			$result = AberturaCaixa::create([
				'usuario_id' => get_id_user(),
				'valor' => str_replace(",", ".", $request->valor),
				'empresa_id' => $this->empresa_id,
				'primeira_venda_nfe' => $ultimaVendaNfe != null ?
				$ultimaVendaNfe->id : 0,
				'primeira_venda_nfce' => $ultimaVendaNfce != null ?
				$ultimaVendaNfce->id : 0,
				'status' => 0,
				'filial_id' => $request->filial_id,
				'conta_id' => $conta_id
			]);
			echo json_encode($result);
		}else{
			echo json_encode(true);
		}
	}

	public function verificaHoje(){
		echo json_encode($this->verificaAberturaCaixa());
	}

	public function diaria(){
		date_default_timezone_set('America/Sao_Paulo');
		$hoje = date("Y-m-d") . " 00:00:00";
		$amanha = date('Y-m-d', strtotime('+1 days')). " 00:00:00";
		$abertura = AberturaCaixa::
		whereBetween('data_registro', [$hoje,
			$amanha])
		->where('empresa_id', $this->empresa_id)
		->first();

		echo json_encode($abertura);
	}

	private function setUsuario($sangrias){
		for($aux = 0; $aux < count($sangrias); $aux++){
			$sangrias[$aux]['nome_usuario'] = $sangrias[$aux]->usuario->nome;
		}
		return $sangrias;
	}

	private function verificaAberturaCaixa(){
		$config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

		$ab = AberturaCaixa::where('ultima_venda_nfce', 0)
		->where('empresa_id', $this->empresa_id)
		->where('status', 0)
		->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
			return $q->where('usuario_id', get_id_user());
		})
		->orderBy('id', 'desc')->first();

		$ab2 = AberturaCaixa::where('ultima_venda_nfe', 0)
		->where('empresa_id', $this->empresa_id)
		->where('status', 0)
		->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
			return $q->where('usuario_id', get_id_user());
		})
		->orderBy('id', 'desc')->first();

		if($ab != null && $ab2 == null){
			return $ab->valor;
		}else if($ab == null && $ab2 != null){
			$ab2->valor;
		}else if($ab != null && $ab2 != null){
			if(strtotime($ab->created_at) > strtotime($ab2->created_at)){
				$ab->valor;
			}else{
				$ab2->valor;
			}
		}else{
			return -1;
		}
		if($ab != null) return $ab->valor;
		else return -1;
	}

	//view do caixa

	public function index(){

		$config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
		if($config == null){
			session()->flash('mensagem_erro', 'Configure o emitente');
			return redirect('/configNF');
		}

		$abertura = $this->verificaAberturaCaixa();
		$ultimaFechadaNfce = AberturaCaixa::where('ultima_venda_nfce', '>', 0)
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

		$ultimaVendaNfce = VendaCaixa::
		where('empresa_id', $this->empresa_id)
		->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
			return $q->where('usuario_id', get_id_user());
		})
		->orderBy('id', 'desc')->first();

		$ultimaVendaNfe = Venda::
		where('empresa_id', $this->empresa_id)
		->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
			return $q->where('usuario_id', get_id_user());
		})
		->orderBy('id', 'desc')->first();

		$vendas = [];
		$somaTiposPagamento = [];

		$caixa = [];
		$nfse = [];

		$contasRecebidas = 0;
		$contasPagas = 0;
		if($abertura != -1){
			$contasRecebidas = $this->getContasRecebidasCaixaAberto($config);
			$contasPagas = $this->getContasPagasCaixaAberto($config);
			$caixa = $this->getCaixaAberto();
			$nfse = $this->getNotasServico($config);

		}


		$ab = AberturaCaixa::where('ultima_venda_nfce', 0)
		->where('ultima_venda_nfe', 0)
		->where('empresa_id', $this->empresa_id)
		->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
			return $q->where('usuario_id', get_id_user());
		})
		->where('status', 0)
		->orderBy('id', 'desc')->first();

		$usuarios = [];

		$user = Usuario::find(get_id_user());

		if($user->adm){
			$usuarios = Usuario::
			where('empresa_id', $this->empresa_id)
			->get();
		}

		$config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
		$contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)
		->where('status', 1)->get();

		return view('caixa/index')
		->with('vendas', $vendas)
		->with('nfse', $nfse)
		->with('usuario_id', get_id_user())
		->with('usuarios', $usuarios)
		->with('config', $config)
		->with('abertura', $ab)
		->with('contasRecebidas', $contasRecebidas)
		->with('contasPagas', $contasPagas)
		->with('contasEmpresa', $contasEmpresa)
		->with('caixaJs', true)
		->with('caixa', $caixa)
		->with('title', 'Caixa');
	}

	private function getContasPagasCaixaAberto($config){
		$abertura = AberturaCaixa::where('ultima_venda_nfce', 0)
		->where('empresa_id', $this->empresa_id)
		->where('status', 0)
		->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
			return $q->where('usuario_id', get_id_user());
		})
		->orderBy('id', 'desc')->first();

		$sumContas = ContaPagar::where('empresa_id', $this->empresa_id)
		// ->whereDate('data_recebimento', $abertura->created_at)
		->whereBetween('data_pagamento',
			[
				$abertura->created_at,
				date('Y-m-d') . " 23:59:59"
			]
		)
		->where('status', 1)
		->sum('valor_pago');

		$contas = ContaPagar::where('empresa_id', $this->empresa_id)
		// ->whereDate('data_recebimento', '>=', $abertura->created_at)
		->whereBetween('data_pagamento',
			[
				$abertura->created_at,
				date('Y-m-d') . " 23:59:59"
			]
		)
		->where('status', 1)->get();

		return [
			'soma' => $sumContas,
			'contas' => $contas
		];
	}

	private function getNotasServico($config){
		$abertura = AberturaCaixa::where('ultima_venda_nfce', 0)
		->where('empresa_id', $this->empresa_id)
		->where('status', 0)
		->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
			return $q->where('usuario_id', get_id_user());
		})
		->orderBy('id', 'desc')->first();

		$sumNotas = Nfse::where('empresa_id', $this->empresa_id)
		->where('estado', '!=', 'rejeitado')
		->where('estado', '!=', 'cancelado')
		->whereBetween('created_at',
			[
				$abertura->created_at,
				date('Y-m-d') . " 23:59:59"
			]
		)
		->sum('valor_total');

		$contas = Nfse::where('empresa_id', $this->empresa_id)
		->where('estado', '!=', 'rejeitado')
		->where('estado', '!=', 'cancelado')
		->whereBetween('created_at',
			[
				$abertura->created_at,
				date('Y-m-d') . " 23:59:59"
			]
		)->get();

		return [
			'soma' => $sumNotas,
			'notas' => $contas
		];
	}

	private function getContasRecebidasCaixaAberto($config){
		$abertura = AberturaCaixa::where('ultima_venda_nfce', 0)
		->where('empresa_id', $this->empresa_id)
		->where('status', 0)
		->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
			return $q->where('usuario_id', get_id_user());
		})
		->orderBy('id', 'desc')->first();

		$sumContas = ContaReceber::where('empresa_id', $this->empresa_id)
		// ->whereDate('data_recebimento', $abertura->created_at)
		->whereBetween('data_recebimento',
			[
				$abertura->created_at,
				date('Y-m-d') . " 23:59:59"
			]
		)
		->where('status', 1)
		->sum('valor_recebido');

		$contas = ContaReceber::where('empresa_id', $this->empresa_id)
		// ->whereDate('data_recebimento', '>=', $abertura->created_at)
		->whereBetween('data_recebimento',
			[
				$abertura->created_at,
				date('Y-m-d') . " 23:59:59"
			]
		)
		->where('status', 1)->get();

		return [
			'soma' => $sumContas,
			'contas' => $contas
		];
	}

	private function getCaixaAberto($usuario = 0){
		$config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

		if($usuario == 0){
			$usuario = get_id_user();
		}

		$aberturaNfe = AberturaCaixa::where('ultima_venda_nfe', 0)
		->where('empresa_id', $this->empresa_id)
		->when($config->caixa_por_usuario == 1, function ($q) use ($config, $usuario) {
			return $q->where('usuario_id', $usuario);
		})
		->orderBy('id', 'desc')->first();

		$aberturaNfce = AberturaCaixa::where('ultima_venda_nfce', 0)
		->where('empresa_id', $this->empresa_id)
		->when($config->caixa_por_usuario == 1, function ($q) use ($config, $usuario) {
			return $q->where('usuario_id', $usuario);
		})
		->orderBy('id', 'desc')->first();

		$ultimaVendaCaixa = VendaCaixa::
		where('empresa_id', $this->empresa_id)
		->when($config->caixa_por_usuario == 1, function ($q) use ($config, $usuario) {
			return $q->where('usuario_id', $usuario);
		})
		->orderBy('id', 'desc')->first();

		$ultimaVenda = Venda::
		where('empresa_id', $this->empresa_id)
		->when($config->caixa_por_usuario == 1, function ($q) use ($config, $usuario) {
			return $q->where('usuario_id', $usuario);
		})
		->orderBy('id', 'desc')->first();

		$vendas = [];
		$somaTiposPagamento = [];

		if($ultimaVendaCaixa != null || $ultimaVenda != null){
			$ultimaVendaCaixa = $ultimaVendaCaixa != null ? $ultimaVendaCaixa->id : 0;
			$ultimaVenda = $ultimaVenda != null ? $ultimaVenda->id : 0;

			$vendasPdv = VendaCaixa
			::whereBetween('id', [($aberturaNfce != null ? $aberturaNfce->primeira_venda_nfce+1 : 0),
				$ultimaVendaCaixa])
			->where('empresa_id', $this->empresa_id)
			->when($config->caixa_por_usuario == 1, function ($q) use ($config, $usuario) {
				return $q->where('usuario_id', $usuario);
			})
			->get();

			$vendas = Venda
			::whereBetween('id', [($aberturaNfe != null ? $aberturaNfe->primeira_venda_nfe+1 : 0),
				$ultimaVenda])
			->where('empresa_id', $this->empresa_id)
			->when($config->caixa_por_usuario == 1, function ($q) use ($config, $usuario) {
				return $q->where('usuario_id', $usuario);
			})
			->get();

			$vendas = $this->agrupaVendas($vendas, $vendasPdv);
			$somaTiposPagamento = $this->somaTiposPagamento($vendas);

		}

		usort($vendas, function($a, $b){
			return strtotime($a['created_at']) < strtotime($b['created_at']) ? 1 : -1;
		});

		$suprimentos = [];
		$sangrias = [];
		if($aberturaNfe != null){
			$suprimentos = SuprimentoCaixa::
			whereBetween('created_at', [
				$aberturaNfe->created_at,
				date('Y-m-d H:i:s')
			])
			->where('empresa_id', $this->empresa_id)
			->when($config->caixa_por_usuario == 1, function ($q) use ($config, $usuario) {
				return $q->where('usuario_id', $usuario);
			})
			->get();

			$sangrias = SangriaCaixa::
			whereBetween('created_at', [$aberturaNfe->created_at,
				date('Y-m-d H:i:s')])
			->where('empresa_id', $this->empresa_id)
			->when($config->caixa_por_usuario == 1, function ($q) use ($config, $usuario) {
				return $q->where('usuario_id', $usuario);
			})
			->get();
		}

		return [
			'vendas' => $vendas,
			'sangrias' => $sangrias,
			'suprimentos' => $suprimentos,
			'somaTiposPagamento' => $somaTiposPagamento
		];
	}

	public function filtroUsuario(Request $request){

		$usuario = $request->usuario;
		$user = Usuario::find(get_id_user());

		if(!$user->adm){
			session()->flash('mensagem_erro', 'Não permitido o acesso!');
			return redirect('/caixa');
		}

		$abertura = $this->verificaAberturaCaixa();
		$ultimaFechadaNfce = AberturaCaixa::where('ultima_venda_nfce', '>', 0)
		->where('empresa_id', $this->empresa_id)
		->where('usuario_id', $usuario)
		->orderBy('id', 'desc')->first();

		$ultimaFechadaNfe = AberturaCaixa::where('ultima_venda_nfe', '>', 0)
		->where('empresa_id', $this->empresa_id)
		->where('usuario_id', $usuario)
		->orderBy('id', 'desc')->first();

		$ultimaVendaNfce = VendaCaixa::
		where('empresa_id', $this->empresa_id)
		->where('usuario_id', $usuario)
		->orderBy('id', 'desc')->first();

		$ultimaVendaNfe = Venda::
		where('empresa_id', $this->empresa_id)
		->where('usuario_id', $usuario)
		->orderBy('id', 'desc')->first();

		$vendas = [];
		$somaTiposPagamento = [];

		$caixa = [];

		if($abertura != -1){
			$caixa = $this->getCaixaAberto($usuario);
		}else{
			if($usuario){
				$caixa = $this->getCaixaAberto($usuario);
			}
		}

		$ab = AberturaCaixa::where('ultima_venda_nfce', 0)
		->where('ultima_venda_nfe', 0)
		->where('empresa_id', $this->empresa_id)
		->where('usuario_id', $usuario)
		->where('status', 0)
		->orderBy('id', 'desc')->first();

		$usuarios = Usuario::
		where('empresa_id', $this->empresa_id)
		->get();

		return view('caixa/filtro')
		->with('vendas', $vendas)
		->with('usuario_id', $usuario)
		->with('usuarios', $usuarios)
		->with('abertura', $ab)
		->with('caixaJs', true)
		->with('caixa', $caixa)
		->with('title', 'Caixa');
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

	private function somaTiposPagamento($vendas){
		$tipos = $this->preparaTipos();

		foreach($vendas as $v){

			if($v->estado != 'CANCELADO'){

				if($v->tipo_pagamento){
				// $tipos[$v->tipo_pagamento] += $v->valor_total;
					// echo $v->valor_total;
					if($v->tipo_pagamento != 99){

						// $tipos[$v->tipo_pagamento] += $v->valor_total - $v->desconto + $v->acrescimo;
						if(isset($v->NFcNumero)){
							if(!$v->rascunho && !$v->consignado){
								$tipos[$v->tipo_pagamento] += $v->valor_total;
							}
						}else{
							if(sizeof($v->duplicatas) > 0){
								foreach($v->duplicatas as $d){
									// $tipos[Venda::getTipoPagamentoNFe($d->tipo_pagamento)] += $d->valor_integral;
									$tipos[Venda::getTipoPagamentoNFe($d->tipo_pagamento)] += $d->valor_integral;
								}
							}else{
								$tipos[$v->tipo_pagamento] += $v->valor_total - $v->desconto;
							}
						}
					}else{

						// if($v->valor_pagamento_1 > 0){
						// 	$tipos[$v->tipo_pagamento_1] += $v->valor_pagamento_1;
						// }
						// if($v->valor_pagamento_2 > 0){
						// 	$tipos[$v->tipo_pagamento_2] += $v->valor_pagamento_2;
						// }
						// if($v->valor_pagamento_3 > 0){
						// 	$tipos[$v->tipo_pagamento_3] += $v->valor_pagamento_3;
						// }
						if($v->fatura){
							foreach($v->fatura as $f){
								$tipos[trim($f->forma_pagamento)] += $f->valor;
							}
						}
					}
				}
			}
		}

		return $tipos;

	}

	private function preparaTipos(){
		$temp = [];
		foreach(Venda::tiposPagamento() as $key => $tp){
			$temp[$key] = 0;
		}
		return $temp;
	}

	public function list(){

		$value = session('user_logged');
		if(!$value['adm']){
			session()->flash("mensagem_erro", "Somente adm podem acessar a lista de caixas!");
			return redirect()->back();
		}
		$aberturas = AberturaCaixa::
		where('empresa_id', $this->empresa_id)
		->where('ultima_venda_nfe', '>', 0)
		->orWhere('ultima_venda_nfce', '>', 0)
		->where('empresa_id', $this->empresa_id)
		->orderBy('id', 'desc')
		->get();

		return view('caixa/list')
		->with('aberturas', $aberturas)
		->with('title', 'Lista de Caixas');
	}

	public function filtro(Request $request){

		$aberturas = AberturaCaixa::
		where('empresa_id', $this->empresa_id)
		->whereBetween('created_at', [
			$this->parseDate($request->data_inicial),
			$this->parseDate($request->data_final, true)
		])
		->where('ultima_venda_nfe', '>', 0)

		->orWhere('ultima_venda_nfce', '>', 0)
		->whereBetween('created_at', [
			$this->parseDate($request->data_inicial),
			$this->parseDate($request->data_final, true)
		])
		->where('empresa_id', $this->empresa_id)

		->orderBy('id', 'desc')
		->get();

		return view('caixa/list')
		->with('aberturas', $aberturas)
		->with('dataInicial', $request->data_inicial)
		->with('dataFinal', $request->data_final)
		->with('title', 'Lista de Caixas');
	}

	private function parseDate($date, $plusDay = false){
		if($plusDay == false)
			return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
		else
			return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
	}

	private function getContasRecebidas($config, $inicio, $fim){

		$abertura = AberturaCaixa::where('ultima_venda_nfce', 0)
		->where('empresa_id', $this->empresa_id)
		->where('status', 0)
		->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
			return $q->where('usuario_id', get_id_user());
		})
		->orderBy('id', 'desc')->first();

		$sumContas = ContaReceber::where('empresa_id', $this->empresa_id)
		->whereBetween('data_recebimento',
			[
				$inicio,
				$fim
			]
		)
		->where('status', 1)
		->sum('valor_recebido');

		return $sumContas;
	}

	public function detalhes($id){
		$abertura = AberturaCaixa::find($id);
		$aberturas = AberturaCaixa::
		where('empresa_id', $this->empresa_id)
		->get();
		$config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

		if(valida_objeto($abertura)){

			$aberturaAnterior = AberturaCaixa::find($id-1);

			$fim = $abertura->updated_at;
			$inicio = $abertura->created_at;

			$vendasPdv = VendaCaixa
			::whereBetween('id', [
				$abertura->primeira_venda_nfce+1,
				$abertura->ultima_venda_nfce
			])
			->where('empresa_id', $this->empresa_id)
			->get();

			$vendas = Venda
			::whereBetween('id', [
				$abertura->primeira_venda_nfe+1,
				$abertura->ultima_venda_nfe
			])
			->where('empresa_id', $this->empresa_id)
			->get();

			$vendas = $this->agrupaVendas($vendas, $vendasPdv);
			$somaTiposPagamento = $this->somaTiposPagamento($vendas);


			$suprimentos = SuprimentoCaixa::
			whereBetween('created_at', [$inicio,
				$fim])
			->where('empresa_id', $this->empresa_id)
			->get();

			$sangrias = SangriaCaixa::
			whereBetween('created_at', [$inicio,
				$fim])
			->where('empresa_id', $this->empresa_id)
			->get();

			$contasRecebidas = $this->getContasRecebidas($config, $inicio, $fim);
			$nfse = $suprimentos = Nfse::
			whereBetween('created_at', [$inicio,
				$fim])
			->where('empresa_id', $this->empresa_id)
			->get();

			return view('caixa/detalhes')
			->with('abertura', $abertura)
			->with('vendas', $vendas)
			->with('nfse', $nfse)
			->with('suprimentos', $suprimentos)
			->with('contasRecebidas', $contasRecebidas)
			->with('sangrias', $sangrias)
			->with('somaTiposPagamento', $somaTiposPagamento)
			->with('title', 'Detalhes Caixa');
		}else{
			return redirect('/403');
		}
	}

	public function imprimir($id){
		$abertura = AberturaCaixa::find($id);
		$aberturas = AberturaCaixa::
		where('empresa_id', $this->empresa_id)
		->get();

		if(valida_objeto($abertura)){

			$aberturaAnterior = AberturaCaixa::find($id-1);

			// $fim = $abertura->updated_at;
			// $inicio = $aberturaAnterior == null ? '2016-01-01' : $aberturaAnterior->created_at;
			$fim = $abertura->updated_at;
			$inicio = $abertura->created_at;

			$vendasPdv = VendaCaixa
			::whereBetween('id', [
				$abertura->primeira_venda_nfce+1,
				$abertura->ultima_venda_nfce
			])
			->where('empresa_id', $this->empresa_id)
			->get();

			$vendas = Venda
			::whereBetween('id', [
				$abertura->primeira_venda_nfe+1,
				$abertura->ultima_venda_nfe
			])
			->where('empresa_id', $this->empresa_id)
			->get();

			$vendas = $this->agrupaVendas($vendas, $vendasPdv);
			$somaTiposPagamento = $this->somaTiposPagamento($vendas);

			$suprimentos = SuprimentoCaixa::
			whereBetween('created_at', [$inicio,
				$fim])
			->where('empresa_id', $this->empresa_id)
			->get();

			$sangrias = SangriaCaixa::
			whereBetween('created_at', [$inicio,
				$fim])
			->where('empresa_id', $this->empresa_id)
			->get();

			$usuario = Usuario::find(get_id_user());
			$config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

			$nfse = $suprimentos = Nfse::
			whereBetween('created_at', [$inicio,
				$fim])
			->where('empresa_id', $this->empresa_id)
			->get();

			$p = view('caixa/relatorio')
			->with('abertura', $abertura)
			->with('vendas', $vendas)
			->with('nfse', $nfse)
			->with('suprimentos', $suprimentos)
			->with('sangrias', $sangrias)
			->with('usuario', $usuario)
			->with('config', $config)
			->with('somaTiposPagamento', $somaTiposPagamento)
			->with('title', 'Detalhes Caixa');

			// return $p;

			$domPdf = new Dompdf(["enable_remote" => true]);
			$domPdf->loadHtml($p);

			$pdf = ob_get_clean();

			$domPdf->setPaper("A4");
			$domPdf->render();
			$domPdf->stream("Relatório caixa.pdf", array("Attachment" => false));
		}else{
			return redirect('/403');
		}
	}

    public function imprimir80($id)
    {
        $ab = AberturaCaixa::findOrFail($id);

        $pdv = VendaCaixa::whereBetween('id', [
            $ab->primeira_venda_nfce + 1,
            $ab->ultima_venda_nfce
        ])->where('empresa_id', $this->empresa_id)->get();

        $nfe = Venda::whereBetween('id', [
            $ab->primeira_venda_nfe + 1,
            $ab->ultima_venda_nfe
        ])->where('empresa_id', $this->empresa_id)->get();

        $vendas = $this->agrupaVendas($nfe, $pdv);
        $somaTiposPagamento = $this->somaTiposPagamento($vendas);

        $somaVendas = collect($vendas)
            ->filter(fn($v)=> $v->estado!='CANCELADO' && !$v->rascunho && !$v->consignado)
            ->sum(fn($v)=> isset($v->cpf)
                ? $v->valor_total
                : ($v->valor_total - $v->desconto + $v->acrescimo)
            );

        $printer = new \App\Prints\CaixaPrint80(
            $vendas, $somaVendas, $somaTiposPagamento
        );
        $pdf     = $printer->render();

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf');
    }

    public function imprimir80_ultimoUsado($id)
    {
        $ab = AberturaCaixa::findOrFail($id);

        // coleta vendas PDV e NFe
        $pdv = VendaCaixa::whereBetween('id', [
            $ab->primeira_venda_nfce + 1,
            $ab->ultima_venda_nfce
        ])->where('empresa_id', $this->empresa_id)->get();

        $nfe = Venda::whereBetween('id', [
            $ab->primeira_venda_nfe + 1,
            $ab->ultima_venda_nfe
        ])->where('empresa_id', $this->empresa_id)->get();

        // agrupa e soma
        $vendas = $this->agrupaVendas($nfe, $pdv);
        $somaTiposPagamento = $this->somaTiposPagamento($vendas);

        // soma total
        $somaVendas = collect($vendas)
            ->filter(fn($v)=> $v->estado!='CANCELADO' && !$v->rascunho && !$v->consignado)
            ->sum(fn($v)=> isset($v->cpf)
                ? $v->valor_total
                : ($v->valor_total - $v->desconto + $v->acrescimo)
            );

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        // logo base64
        $logoData = $logoMime = null;
        if (!empty($config->logo) && file_exists($path = public_path("logos/{$config->logo}"))) {
            $logoData = base64_encode(safe_file_get_contents($path));
            $logoMime = mime_content_type($path);
        }

        // renderiza HTML
        $html = view('caixa.relatorio80', compact(
            'vendas','somaVendas','somaTiposPagamento','config','logoData','logoMime'
        ))->render();

        // Dinâmica de altura:
        $nVenda  = count($vendas);
        $nTot    = collect($somaTiposPagamento)->filter(fn($v)=> $v>0)->count() + 1;

        // em mm
        $headerMm   = 12; // header+title+sep
        $theadMm    = 6;  // cabeçalho de colunas
        $rowMm      = 8;  // cada registro: 2 linhas ×4mm
        $totRowMm   = 4;  // cada total
        $footerMm   = 6;  // rodapé

        $mmAltura = $headerMm
            + $theadMm
            + ($nVenda * $rowMm)
            + ($nTot   * $totRowMm)
            + $footerMm;

        // converte mm → pontos
        $ptPerMm = 72 / 25.4;
        $wPts    = 80   * $ptPerMm;
        $hPts    = $mmAltura * $ptPerMm;

        // Dompdf
        $options = (new Options())
            ->set('isRemoteEnabled', true)
            ->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->setPaper([0, 0, $wPts, $hPts]);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->stream(
            "FechamentoCaixa_{$ab->id}.pdf",
            ['Attachment' => false]
        );
    }

    public function imprimir80_010203($id)
    {
        $ab = AberturaCaixa::findOrFail($id);

        $pdv = VendaCaixa::whereBetween('id', [
            $ab->primeira_venda_nfce + 1,
            $ab->ultima_venda_nfce
        ])->where('empresa_id', $this->empresa_id)->get();

        $nfe = Venda::whereBetween('id', [
            $ab->primeira_venda_nfe + 1,
            $ab->ultima_venda_nfe
        ])->where('empresa_id', $this->empresa_id)->get();

        $vendas = $this->agrupaVendas($nfe, $pdv);
        $somaTiposPagamento = $this->somaTiposPagamento($vendas);

        $somaVendas = 0;
        foreach ($vendas as $v) {
            if ($v->estado !== 'CANCELADO' && !$v->rascunho && !$v->consignado) {
                $somaVendas += isset($v->cpf)
                    ? $v->valor_total
                    : ($v->valor_total - $v->desconto + $v->acrescimo);
            }
        }

        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        // Logo em base64, se houver
        $logoData = $logoMime = null;
        if (!empty($config->logo) && file_exists($path = public_path("logos/{$config->logo}"))) {
            $logoData = base64_encode(safe_file_get_contents($path));
            $logoMime = mime_content_type($path);
        }

        $html = view('caixa.relatorio80', compact(
            'vendas',
            'somaVendas',
            'somaTiposPagamento',
            'config',
            'logoData',
            'logoMime'
        ))->render();

        // Calcula altura em mm
        $nVenda    = count($vendas);
        $nTotais   = count(array_filter($somaTiposPagamento, fn($v)=> $v>0)) + 1;

        $headerMm    = 12;  // cabeçalho
        $titleMm     = 6;   // título
        $sepMm       = 1;   // separador
        $colHdrMm    = 4;   // cabeçalho de colunas
        $rowMm       = 6;   // cada venda (2 linhas)
        $totRowMm    = 4;   // cada total
        $footerMm    = 6;   // rodapé

        $mmAltura = $headerMm
            + $titleMm
            + $sepMm
            + $colHdrMm
            + ($nVenda * $rowMm)
            + ($nTotais * $totRowMm)
            + $footerMm;

        $ptPerMm = 72 / 25.4;
        $wPts    = 80 * $ptPerMm;
        $hPts    = $mmAltura * $ptPerMm;

        $options = (new Options())
            ->set('isRemoteEnabled', true)
            ->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper([0, 0, $wPts, $hPts]);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->stream(
            "FechamentoCaixa_{$ab->id}.pdf",
            ['Attachment' => false]
        );
    }

    public function imprimir80_esse_ok($id)
    {
        // 1) Busca a abertura
        $abertura = AberturaCaixa::findOrFail($id);

        // 2) Determina intervalo de PDV e NFe
        $ant = AberturaCaixa::find($id - 1);
        $inicio = $ant ? $ant->updated_at : '2016-01-01';
        $fim    = $abertura->updated_at;

        $pdv = VendaCaixa::whereBetween('id', [
            $abertura->primeira_venda_nfce + 1,
            $abertura->ultima_venda_nfce
        ])->where('empresa_id', $this->empresa_id)->get();

        $nfe = Venda::whereBetween('id', [
            $abertura->primeira_venda_nfe + 1,
            $abertura->ultima_venda_nfe
        ])->where('empresa_id', $this->empresa_id)->get();

        // 3) Agrupa e soma tipos de pagamento
        $vendas             = $this->agrupaVendas($nfe, $pdv);
        $somaTiposPagamento = $this->somaTiposPagamento($vendas);

        // 4) Calcula total geral de vendas
        $somaVendas = 0;
        foreach ($vendas as $v) {
            if ($v->estado !== 'CANCELADO' && !$v->rascunho && !$v->consignado) {
                if (isset($v->cpf)) {
                    $somaVendas += $v->valor_total;
                } else {
                    $somaVendas += $v->valor_total - $v->desconto + $v->acrescimo;
                }
            }
        }

        // 5) Busca configuração do emitente
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        // 6) Tenta carregar logo em base64
        $logoData = $logoMime = null;
        if (!empty($config->logo)
            && file_exists($path = public_path("logos/{$config->logo}"))
        ) {
            $logoData = base64_encode(safe_file_get_contents($path));
            $logoMime = mime_content_type($path);
        }

        // 7) Renderiza o Blade (relatorio80.blade.php)
        $html = view('caixa.relatorio80', compact(
            'vendas',
            'somaVendas',
            'somaTiposPagamento',
            'config',
            'logoData',
            'logoMime'
        ))->render();

        // 8) Calcula altura dinâmica (em mm → pt)
        $linhas   = count($vendas);
        $qtTotais = count(array_filter($somaTiposPagamento, fn($v)=> $v>0)) + 1;
        $mmAltura = 100           // cabeçalho + título
            + ($linhas * 5)  // cada venda ~5mm
            + ($qtTotais * 5) // totais ~5mm cada
            + 10;            // folga
        $ptsPerMm = 72 / 25.4;
        $wPts     = 70 * $ptsPerMm;
        $hPts     = $mmAltura * $ptsPerMm;

        // 9) Configura Dompdf
        $options = (new Options())
            ->set('isRemoteEnabled', true)
            ->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->setPaper([0, 0, $wPts, $hPts]);
        $dompdf->loadHtml($html);
        $dompdf->render();

        // 10) Envia ao browser sem page-breaks
        return $dompdf->stream(
            "FechamentoCaixa_{$abertura->id}.pdf",
            ['Attachment' => false]
        );
    }


    public function imprimir80___($id)
    {
        // 1) Carrega a abertura
        $abertura = AberturaCaixa::findOrFail($id);

        // 2) Busca vendas PDV e NFe no intervalo correto
        $vPdv = VendaCaixa::whereBetween('id', [
            $abertura->primeira_venda_nfce + 1,
            $abertura->ultima_venda_nfce
        ])->where('empresa_id', $this->empresa_id)->get()->all();

        $vNfe = Venda::whereBetween('id', [
            $abertura->primeira_venda_nfe + 1,
            $abertura->ultima_venda_nfe
        ])->where('empresa_id', $this->empresa_id)->get()->all();

        // 3) Agrupa e soma tipos de pagamento
        $vendas = $this->agrupaVendas($vNfe, $vPdv);
        $somaTiposPagamento = $this->somaTiposPagamento($vendas);

        // 4) Soma total de vendas
        $somaVendas = array_reduce($vendas, function($carry, $v) {
            if ($v->estado !== 'CANCELADO' && !$v->rascunho && !$v->consignado) {
                $val = isset($v->cpf)
                    ? $v->valor_total
                    : ($v->valor_total - $v->desconto + $v->acrescimo);
                return $carry + $val;
            }
            return $carry;
        }, 0);

        // 5) Carrega config do emitente
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        // 6) Renderiza HTML com Blade
        $html = view('caixa.relatorio80', [
            'vendas'             => $vendas,
            'somaVendas'         => $somaVendas,
            'somaTiposPagamento' => $somaTiposPagamento,
            'config'             => $config,
            'perPage'            => 30,
        ])->render();

        // 7) Configura Dompdf para 80 mm contínuo + paginação
        $options = (new Options())
            ->set('isRemoteEnabled', true)
            ->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        // 80 mm em pontos ≈ 80 × 2.835 = 226.8px, altura grande para rolar
        $dompdf->setPaper([0, 0, 226.8, 10000]);
        $dompdf->loadHtml($html);
        $dompdf->render();

        // 8) Envia para o navegador/impresora térmica sem attachment
        return $dompdf->stream(
            "FechamentoCaixa_{$abertura->id}.pdf",
            ['Attachment' => false]
        );
    }

    public function imprimir80_($id)
    {
        // 1) Carrega a abertura
        $abertura = AberturaCaixa::findOrFail($id);

        // 2) Monta o intervalo de vendas (PDV + NFe)
        $abAnterior = AberturaCaixa::find($id - 1);
        $inicio    = $abAnterior ? $abAnterior->updated_at : '2016-01-01';
        $fim       = $abertura->updated_at;

        $vendasPdv = VendaCaixa::whereBetween('id', [
            $abertura->primeira_venda_nfce + 1,
            $abertura->ultima_venda_nfce
        ])->where('empresa_id', $this->empresa_id)->get();

        $vendasNfe = Venda::whereBetween('id', [
            $abertura->primeira_venda_nfe + 1,
            $abertura->ultima_venda_nfe
        ])->where('empresa_id', $this->empresa_id)->get();

        // 3) Agrupa e soma
        $vendas = $this->agrupaVendas($vendasNfe, $vendasPdv);
        $somaTiposPagamento = $this->somaTiposPagamento($vendas);

        // 4) Total geral de vendas (já subtrai desconto e adiciona acréscimo)
        $somaVendas = 0;
        foreach ($vendas as $v) {
            if ($v->estado !== 'CANCELADO' && !$v->rascunho && !$v->consignado) {
                if (isset($v->cpf)) {
                    $somaVendas += $v->valor_total;
                } else {
                    $somaVendas += $v->valor_total - $v->desconto + $v->acrescimo;
                }
            }
        }

        // 5) Carrega as configurações do emitente
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        // 6) Renderiza o HTML via Blade (relatorio80.blade.php)
        $html = view('caixa.relatorio80', [
            'vendas'             => $vendas,
            'somaVendas'         => $somaVendas,
            'somaTiposPagamento' => $somaTiposPagamento,
            'config'             => $config
        ])->render();

        // 7) Configura o Dompdf para cupom 80mm
        $options = (new Options())
            ->set('isRemoteEnabled', true)
            ->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        // 80mm em pontos ≈ 80 × 2,835 = 226.8px (ajuste automático de altura)
        $dompdf->setPaper([0, 0, 226.8, 1000]);
        $dompdf->loadHtml($html);
        $dompdf->render();

        // 8) Retorna sem attachment (impressão direta)
        return $dompdf->stream(
            "FechamentoCaixa_{$abertura->id}.pdf",
            ['Attachment' => false]
        );
    }

    public function imprimir80_04072025_bkp($id){
		$abertura = AberturaCaixa::find($id);
		$aberturas = AberturaCaixa::
		where('empresa_id', $this->empresa_id)
		->get();

		if(valida_objeto($abertura)){

			$aberturaAnterior = AberturaCaixa::find($id-1);

			$fim = $abertura->updated_at;
			$inicio = $aberturaAnterior == null ? '2016-01-01' : $aberturaAnterior->updated_at;

			$vendasPdv = VendaCaixa
			::whereBetween('id', [
				$abertura->primeira_venda_nfce+1,
				$abertura->ultima_venda_nfce
			])
			->where('empresa_id', $this->empresa_id)
			->get();

			$vendas = Venda
			::whereBetween('id', [
				$abertura->primeira_venda_nfe+1,
				$abertura->ultima_venda_nfe
			])
			->where('empresa_id', $this->empresa_id)
			->get();

			$vendas = $this->agrupaVendas($vendas, $vendasPdv);
			$somaTiposPagamento = $this->somaTiposPagamento($vendas);

			$somaVendas = 0;

			foreach($vendas as $v){
				if($v->estado != 'CANCELADO' && !$v->rascunho && !$v->consignado){
					$total = $v->valor_total;
					if(!isset($v->cpf)){
						$total = $total-$v->desconto+$v->acrescimo;
					}

					$somaVendas += $total;
				}
			}
			$suprimentos = SuprimentoCaixa::
			whereBetween('created_at', [$inicio,
				$fim])
			->where('empresa_id', $this->empresa_id)
			->get();

			$sangrias = SangriaCaixa::
			whereBetween('created_at', [$inicio,
				$fim])
			->where('empresa_id', $this->empresa_id)
			->get();

			$config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
			$usuario = Usuario::find(get_id_user());

			$cupom = new ComprovanteFechamentoCaixa($vendas, '', $config, 80, $suprimentos, $sangrias, $somaTiposPagamento, $abertura, $usuario, $somaVendas);
			$cupom->monta();
			$pdf = $cupom->render();

			return response($pdf)
			->header('Content-Type', 'application/pdf');
		}else{
			return redirect('/403');
		}
	}

}
