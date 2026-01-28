<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContaReceber;
use App\Models\CategoriaConta;
use App\Models\Cliente;
use App\Models\ConfigNota;
use App\Models\Cidade;
use Dompdf\Dompdf;
use App\Imports\ProdutoImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Utils\ContaEmpresaUtil;
use App\Models\ContaEmpresa;
use App\Models\ItemContaEmpresa;
use App\Exports\ContasReceberExport;

class ContaReceberController extends Controller
{
	protected $empresa_id = null;
	protected $util;

	public function __construct(ContaEmpresaUtil $util){
		$this->util = $util;

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
		__saveRedirect($this->empresa_id, '', 'contas_receber');
		$permissaoAcesso = __getLocaisUsarioLogado();
		$local_padrao = __get_local_padrao();
		if($local_padrao == -1){
			$local_padrao = null;
		}
		$contas = ContaReceber::
		where('empresa_id', $this->empresa_id)
		->whereBetween('data_vencimento', [date("Y-m-d"),
			date('Y-m-d', strtotime('+1 month'))])
		->orderBy('data_vencimento', 'desc')
		->where(function($query) use ($permissaoAcesso){
			if($permissaoAcesso != null){
				foreach ($permissaoAcesso as $value) {
					if($value == -1){
						$value = null;
					}
					$query->orWhere('filial_id', $value);
				}
			}
		})
		->when($local_padrao != NULL, function ($query) use ($local_padrao) {
			$query->where('filial_id', $local_padrao);
		})
		->get();

		$categorias = CategoriaConta::
		where('empresa_id', $this->empresa_id)
		->where('tipo', 'receber')
		->get();

		$somaContas = $this->somaCategoriaDeContas($contas);

		$clientes = Cliente::
		where('empresa_id', $this->empresa_id)
		->where('inativo', false)
		->get();

		return view('contaReceber/list')
		->with('contas', $contas)
		->with('graficoJs', true)
		->with('categorias', $categorias)
		->with('clientes', $clientes)
		->with('somaContas', $somaContas)
		->with('infoDados', "Dos próximos 30 dias")
		->with('title', 'Contas a Receber')
        ->with('dataInicial', isset($dataInicial) ? $dataInicial : date("d/m/Y"))
        ->with('dataFinal', isset($dataFinal) ? $dataFinal : date('d/m/Y', strtotime('+1 month')));
	}

	private function somaCategoriaDeContas($contas){
		$arrayCategorias = $this->criaArrayDecategoriaDeContas();
		$temp = [];
		foreach($contas as $c){
			foreach($arrayCategorias as $a){
				if($c->categoria->nome == $a){
					if(isset($temp[$a])){
						$temp[$a] = $temp[$a] + $c->valor_integral;
					}else{
						$temp[$a] = $c->valor_integral;
					}
				}
			}
		}
		return $temp;
	}

	private function criaArrayDecategoriaDeContas(){
		$categorias = CategoriaConta::
		where('empresa_id', $this->empresa_id)
		->where('tipo', 'receber')
		->get();
		$temp = [];
		foreach($categorias as $c){
			array_push($temp, $c->nome);
		}

		return $temp;
	}

	public function filtro(Request $request){

		$dataInicial = $request->data_inicial;
		$dataFinal = $request->data_final;
		$clienteId = $request->clienteId;
		$status = $request->status;
		$filial_id = $request->filial_id;
		$numero_pedido = $request->numero_pedido;
		$contas = [];

		if($request->tipo_pagamento && $request->tipo_pagamento == 'Pix'){
			$request->tipo_pagamento = 'Pagamento Instantâneo (PIX)';
		}

		$url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		__saveRedirect($this->empresa_id, $url, 'contas_receber');
		$permissaoAcesso = __getLocaisUsarioLogado();

		$c = ContaReceber::
		select('conta_recebers.*')
		->where(function($query) use ($permissaoAcesso){
			if($permissaoAcesso != null){
				foreach ($permissaoAcesso as $value) {
					if($value == -1){
						$value = null;
					}
					$query->orWhere('conta_recebers.filial_id', $value);
				}
			}
		})
		->when($filial_id, function ($query) use ($filial_id) {
			$filial_id = $filial_id == -1 ? null : $filial_id;
			return $query->where('conta_recebers.filial_id', $filial_id);
		});

		if($clienteId != 'null'){
			// $contas->join('clientes', 'clientes.id' , '=', 'conta_recebers.cliente_id');
			$c->where('conta_recebers.cliente_id', $clienteId);
		}

		if($dataInicial && $dataFinal){

			if($request->tipo_filtro_data == 1){
				$c->whereBetween('conta_recebers.data_vencimento',
					[
						$this->parseDate($dataInicial),
						$this->parseDate($dataFinal)
					]
				);
			}elseif($request->tipo_filtro_data == 2){
				$c->whereBetween('conta_recebers.created_at',
					[
						$this->parseDate($dataInicial),
						$this->parseDate($dataFinal, true)
					]
				);
			}else{

				$d1 = str_replace("/", "-", $dataInicial);
				$d2 = str_replace("/", "-", $dataFinal);

				$c->whereBetween('conta_recebers.data_recebimento',
					[
						\Carbon\Carbon::parse($d1)->format('Y-m-d') . " 00:00:00",
						\Carbon\Carbon::parse($d2)->format('Y-m-d') . " 23:59:59"
					]
				);
			}
		}

		if($status != 'todos'){
			if($status == 'pago'){
				$c->where('status', true);
			} else if($status == 'pendente'){
				$c->where('status', false);
			}else if($status == 'vencido'){
				$c->where('status', false)
				->whereDate('data_vencimento', '<=', date('Y-m-d'));
			}
		}

		if($request->tipo_filtro_data == 3){
			$c->where('status', true);
		}

		if($request->numero_nota_fiscal){
			$c->where('conta_recebers.numero_nota_fiscal', $request->numero_nota_fiscal);
		}

		if($request->categoria != 'todos'){
			$c->where('conta_recebers.categoria_id', $request->categoria);
		}

		if($request->tipo_pagamento){
			$c->where('conta_recebers.tipo_pagamento', $request->tipo_pagamento);
		}

		if($numero_pedido){
			$c->join('vendas', 'vendas.id', '=', 'conta_recebers.venda_id')
			->where('vendas.id', $numero_pedido);
		}
		$c->where('conta_recebers.empresa_id', $this->empresa_id);

		$temp = $c->get();
		foreach($temp as $t){
			array_push($contas, $t);
		}

		$c = ContaReceber::
		select('conta_recebers.*')
		->where(function($query) use ($permissaoAcesso){
			if($permissaoAcesso != null){
				foreach ($permissaoAcesso as $value) {
					if($value == -1){
						$value = null;
					}
					$query->orWhere('conta_recebers.filial_id', $value);
				}
			}
		})
		->when($filial_id, function ($query) use ($filial_id) {
			$filial_id = $filial_id == -1 ? null : $filial_id;
			return $query->where('conta_recebers.filial_id', $filial_id);
		});

		if($clienteId != 'null'){

			$c->join('vendas', 'vendas.id' , '=', 'conta_recebers.venda_id')
			->where('vendas.cliente_id', $clienteId);
		}

		if($dataInicial && $dataFinal){
			if($request->tipo_filtro_data == 1){
				$c->whereBetween('conta_recebers.data_vencimento',
					[
						$this->parseDate($dataInicial),
						$this->parseDate($dataFinal)
					]
				);
			}elseif($request->tipo_filtro_data == 2){
				$c->whereBetween('conta_recebers.created_at',
					[
						$this->parseDate($dataInicial),
						$this->parseDate($dataFinal, true)
					]
				);
			}else{
				$d1 = str_replace("/", "-", $dataInicial);
				$d2 = str_replace("/", "-", $dataFinal);

				$c->whereBetween('conta_recebers.data_recebimento',
					[
						\Carbon\Carbon::parse($d1)->format('Y-m-d') . " 00:00:00",
						\Carbon\Carbon::parse($d2)->format('Y-m-d') . " 23:59:59"
					]
				);
			}
		}

		if($status != 'todos'){
			if($status == 'pago'){
				$c->where('status', true);
			} else if($status == 'pendente'){
				$c->where('status', false);
			}else if($status == 'vencido'){
				$c->where('status', false)
				->whereDate('data_vencimento', '<=', date('Y-m-d'));
			}
		}

		if($request->tipo_filtro_data == 3){
			$c->where('status', true);
		}

		if($request->numero_nota_fiscal){
			$c->where('conta_recebers.numero_nota_fiscal', $request->numero_nota_fiscal);
		}
		if($request->categoria != 'todos'){
			$c->where('conta_recebers.categoria_id', $request->categoria);
		}

		if($request->tipo_pagamento){
			$c->where('conta_recebers.tipo_pagamento', $request->tipo_pagamento);
		}

		if($numero_pedido){
			$c->join('vendas', 'vendas.id', '=', 'conta_recebers.venda_id')
			->where('vendas.id', $numero_pedido);
		}
		$c->where('conta_recebers.empresa_id', $this->empresa_id);

		$temp = $c->get();
		foreach($temp as $t){
			if(!$this->validaInArray($t, $contas)){
				array_push($contas, $t);
			}
		}

		$somaContas = $this->somaCategoriaDeContas($contas);

		$categorias = CategoriaConta::
		where('empresa_id', $this->empresa_id)
		->where('tipo', 'receber')
		->get();

		$clientes = Cliente::
		where('empresa_id', $this->empresa_id)
		->where('inativo', false)
		->get();

		return view('contaReceber/list')
		->with('contas', $contas)
		->with('clienteId', $clienteId)
		->with('clientes', $clientes)
		->with('categorias', $categorias)
		->with('tipoPesquisa', $request->tipo_pesquisa)
		->with('tipo_filtro_data', $request->tipo_filtro_data)
		->with('categoria', $request->categoria)
		->with('tipo_pagamento', $request->tipo_pagamento)
        ->with('dataInicial', isset($dataInicial) ? $dataInicial : date("d/m/Y"))
        ->with('dataFinal', isset($dataFinal) ? $dataFinal : date('d/m/Y', strtotime('+1 month')))
		->with('status', $status)
		->with('filial_id', $filial_id)
		->with('numero_pedido', $numero_pedido)
		->with('somaContas', $somaContas)
		->with('graficoJs', true)
		->with('numero_nota_fiscal', $request->numero_nota_fiscal)
		->with('paraImprimir', true)
		->with('infoDados', "Contas filtradas")
		->with('title', 'Filtro Contas a Receber');
	}

	private function validaInArray($ct, $contas){
		foreach($contas as $c){
			if($c->id == $ct->id) return true;
		}
		return false;
	}

	public function salvarParcela(Request $request){
		$parcela = $request->parcela;

		$valorParcela = str_replace(".", "", $parcela['valor_parcela']);
		$valorParcela = str_replace(",", ".", $valorParcela);

		$categoria = CategoriaConta::
		where('empresa_id', $this->empresa_id)
		->where('tipo', 'receber')
		->first();

		$result = ContaReceber::create([
			'venda_id' => $parcela['compra_id'],
			'data_vencimento' => $this->parseDate($parcela['vencimento']),
			'data_recebimento' => $this->parseDate($parcela['vencimento']),
			'valor_integral' => $valorParcela,
			'valor_recebido' => 0,
			'status' => false,
			'referencia' => $parcela['referencia'],
			'categoria_id' => $categoria->id,
			'empresa_id' => $this->empresa_id
		]);
		echo json_encode($parcela);
	}

	public function save(Request $request){

		if(strlen($request->recorrencia) == 5){
			echo $request->recorrencia;
			$valid = $this->validaRecorrencia($request->recorrencia);
			if(!$valid){
				session()->flash('mensagem_erro', 'Valor recorrente inválido!');
				return redirect('/contasReceber/new');
			}
		}
		$clienteId = NULL;
		if($request->cliente_id != ""){
			$clienteId = $request->cliente_id;
		}

		$request->merge([
			'filial_id' => $request->filial_id == -1 ? null : $request->filial_id
		]);

		$this->_validate($request);
		$parcelas = json_decode($request->parcelas);

		$result = ContaReceber::create([
			'venda_id' => null,
			'data_vencimento' => $this->parseDate($request->vencimento),
			'data_recebimento' => $this->parseDate($request->vencimento),
			'valor_integral' => str_replace(",", ".", $request->valor),
			'valor_recebido' => $request->status ? str_replace(",", ".", $request->valor_recebido) : 0,
			'status' => $request->status ? true : false,
			'referencia' => $request->referencia . (sizeof($parcelas) > 0 ? " - parcela 1" . "/".(sizeof($parcelas)+1) : ""),
			'tipo_pagamento' => $request->tipo_pagamento ?? '',
			'observacao' => $request->observacao ?? '',
			'categoria_id' => $request->categoria_id,
			'empresa_id' => $this->empresa_id,
			'cliente_id' => $clienteId,
			'filial_id' => $request->filial_id,
			'numero_nota_fiscal' => $request->numero_nota_fiscal ?? 0,
		]);

		// $loopRecorrencia = $this->calculaRecorrencia($request->recorrencia);
		// if($loopRecorrencia > 0){
		// 	$diaVencimento = substr($request->vencimento, 0, 2);
		// 	$proximoMes = substr($request->vencimento, 3, 2);
		// 	$ano = substr($request->vencimento, 6, 4);

		// 	while($loopRecorrencia > 0){
		// 		$proximoMes = $proximoMes == 12 ? 1 : $proximoMes+1;
		// 		$proximoMes = $proximoMes < 10 ? "0".$proximoMes : $proximoMes;
		// 		if($proximoMes == 1)  $ano++;
		// 		$d = $diaVencimento . "/".$proximoMes . "/" . $ano;

		// 		$result = ContaReceber::create([
		// 			'venda_id' => null,
		// 			'data_vencimento' => $this->parseDate($d),
		// 			'data_recebimento' => $this->parseDate($d),
		// 			'valor_integral' => str_replace(",", ".", $request->valor),
		// 			'valor_recebido' => 0,
		// 			'status' => false,
		// 			'referencia' => $request->referencia,
		// 			'categoria_id' => $request->categoria_id,
		// 			'empresa_id' => $this->empresa_id,
		// 			'cliente_id' => $clienteId
		// 		]);
		// 		$loopRecorrencia--;
		// 	}
		// }

		if(sizeof($parcelas) > 0){
			foreach($parcelas as $key => $p){
				$result = ContaReceber::create([
					'venda_id' => null,
					'data_vencimento' => $p->vencimento,
					'data_recebimento' => $p->vencimento,
					'valor_integral' => str_replace(",", ".", $p->valor),
					'valor_recebido' => $request->status ? str_replace(",", ".", $request->valor_recebido) : 0,
					'status' => $request->status ? true : false,
					'tipo_pagamento' => $request->tipo_pagamento ?? '',
					'cliente_id' => $clienteId,
					'observacao' => $request->observacao ?? '',
					'numero_nota_fiscal' => $request->numero_nota_fiscal ?? 0,
					'referencia' => $request->referencia . " - parcela " .($key+2) . "/".(sizeof($parcelas)+1),
					'categoria_id' => $request->categoria_id,
					'empresa_id' => $this->empresa_id
				]);
			}
		}


		session()->flash('mensagem_sucesso', 'Registro inserido!');

		return redirect('/contasReceber');
	}

	public function update(Request $request){
		$this->_validate($request);
		$conta = ContaReceber::
		where('id', $request->id)
		->first();

		$request->merge([
			'filial_id' => $request->filial_id == -1 ? null : $request->filial_id
		]);

		$conta->data_vencimento = $this->parseDate($request->vencimento);
		$conta->referencia = $request->referencia;
		$conta->tipo_pagamento = $request->tipo_pagamento ?? '';
		$conta->observacao = $request->observacao ?? '';
		$conta->valor_integral = str_replace(",", ".", $request->valor);
		$conta->categoria_id = $request->categoria_id;
		$conta->filial_id = $request->filial_id;
		$conta->numero_nota_fiscal = $request->numero_nota_fiscal ?? 0;
		if(isset($request->cliente_id)){
			$conta->cliente_id = $request->cliente_id;
		}

		$result = $conta->save();

		if($result){

			session()->flash('mensagem_sucesso', 'Registro atualizado!');
		}else{

			session()->flash('mensagem_erro', 'Ocorreu um erro!');
		}

		$rota = __getRedirect($this->empresa_id, 'contas_receber');
		if($rota != ""){
			return redirect($rota);
		}

		return redirect('/contasReceber');

	}

	private function calculaRecorrencia($recorrencia){
		if(strlen($recorrencia) == 5){
			$dataAtual = date("Y-m");
			$dif = strtotime($this->parseRecorrencia($recorrencia)) - strtotime($dataAtual);

			$meses = floor($dif / (60 * 60 * 24 * 30));

			return $meses;
		}
		return 0;
	}

	public function validaRecorrencia($rec){
		$mesAutal = date('m');
		$anoAtual = date('y');
		$temp = explode("/", $rec);

		if($anoAtual > $temp[1]) return false;
		if((int)$temp[0] <= $mesAutal && $anoAtual == $temp[1]) return false;

		return true;
	}

	private function _validate(Request $request){

		$rules = [
			'cliente_id' => $request->id == 0 ? 'required' : '',
			'referencia' => 'required',
			'valor' => 'required',
			//'observacao' => 'max:100',
            'observacao' => 'nullable|string',
			'categoria_id' => 'required',
			'vencimento' => 'required',
		];

		$messages = [
			'cliente_id.required' => 'O campo cliente é obrigatório.',
			'referencia.required' => 'O campo referencia é obrigatório.',
			'valor.required' => 'O campo valor é obrigatório.',
			'observacao.max' => 'Máximo de 100 caracteres.',
			'categoria_id.required' => 'O campo categoria é obrigatório.',
			'vencimento.required' => 'O campo vencimento é obrigatório.'
		];
		$this->validate($request, $rules, $messages);
	}

	public function new(){
		$categorias = CategoriaConta::
		where('empresa_id', $this->empresa_id)
		->where('tipo', 'receber')
		->orderBy('nome')
		->get();

		if(sizeof($categorias) == 0){
			session()->flash('mensagem_alerta', 'Cadastre uma categoria com o tipo receber!');
			return redirect('/categoriasConta');
		}

		$clientes = Cliente::
		where('empresa_id', $this->empresa_id)
		->where('inativo', false)
		->get();

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		if($config == null){
			session()->flash('mensagem_alerta', 'Informe a configuração do emitente!');
			return redirect('/configNF');
		}

		return view('contaReceber/register')
		->with('categorias', $categorias)
		->with('clientes', $clientes)
		->with('config', $config)
		->with('title', 'Cadastrar Contas a Receber');
	}

	public function edit($id){
		$categorias = CategoriaConta::
		where('empresa_id', $this->empresa_id)
		->where('tipo', 'receber')
		->orderBy('nome')
		->get();

		$conta = ContaReceber::
		where('id', $id)
		->first();

		if($conta->venda_caixa_id != null){
			$conta->cliente_id = $conta->vendaCaixa->cliente_id;
			$conta->save();
		}
		if($conta->venda_id != null){
			$conta->cliente_id = $conta->venda->cliente_id;
			$conta->save();
		}

		$conta = ContaReceber::
		where('id', $id)
		->first();

		if($conta->boleto){
			session()->flash('mensagem_erro', 'Conta já possui boleto emitido!');
			return redirect('/contasReceber');
		}

		$clientes = Cliente::
		where('empresa_id', $this->empresa_id)
		->where('inativo', false)
		->get();

		if(valida_objeto($conta)){
			return view('contaReceber/register')
			->with('conta', $conta)
			->with('categorias', $categorias)
			->with('clientes', $clientes)
			->with('title', 'Editar Contas a Receber');
		}else{
			return redirect('/403');
		}
	}

	public function estorno($id){

		$conta = ContaReceber::findOrFail($id);

		if(valida_objeto($conta)){
			return view('contaReceber/estorno')
			->with('conta', $conta)
			->with('title', 'Estornar Conta');
		}else{
			return redirect('/403');
		}
	}

	public function estornoConta(Request $request){
		$conta = ContaReceber::findOrFail($request->id);

		try{
			$conta->status = false;
			$conta->estorno = true;
			$conta->motivo_estorno = $request->motivo;
			$conta->save();
			session()->flash('mensagem_sucesso', 'Conta estornada!');

			$rota = __getRedirect($this->empresa_id, 'contas_receber');
			if($rota != ""){
				return redirect($rota);
			}
			return redirect('/contasReceber');
		}catch(\Exception $e){
			session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());

		}
		return redirect('/contasReceber');
	}

	public function receber($id){
		$categorias = CategoriaConta::
		where('empresa_id', $this->empresa_id)
		->where('tipo', 'receber')
		->get();
		$conta = ContaReceber::findOrFail($id);

		if(valida_objeto($conta)){

			$contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)
			->where('status', 1)->get();

			return view('contaReceber/receber')
			->with('conta', $conta)
			->with('contasEmpresa', $contasEmpresa)
			->with('categorias', $categorias)
			->with('title', 'Receber Conta');
		}else{
			return redirect('/403');
		}
	}

    public function receberConta(Request $request)
    {
        $conta = ContaReceber::
        where('id', $request->id)
            ->first();

        // dd($request->all());
        if (valida_objeto($conta)) {

            $vIntegral = number_format($conta->valor_integral, 2);
            $vReq = number_format(str_replace(",", ".", $request->valor), 2);

            if ($vIntegral != $vReq) {
                $valor = __replace($request->valor);

                if (isset($request->conta_id)) {

                    $tipoPagamento = \App\Models\Venda::getTipoPagamentoNFe($request->tipo_pagamento);

                    $data = [
                        'conta_id'       => $request->conta_id,
                        'descricao'      => "Recebimento de conta " . $conta->referencia,
                        'tipo_pagamento' => $tipoPagamento,
                        'valor'          => $valor,
                        'tipo'           => 'entrada'
                    ];
                    $itemContaEmpresa = ItemContaEmpresa::create($data);
                    $this->util->atualizaSaldo($itemContaEmpresa);
                }

                if ($conta->venda_id != null) {
                    $contasParaReceber = ContaReceber::
                    select('conta_recebers.*')
                        ->join('vendas', 'vendas.id', '=', 'conta_recebers.venda_id')
                        ->where('conta_recebers.status', false)
                        ->where('conta_recebers.id', '!=', $conta->id)
                        ->where('vendas.cliente_id', $conta->venda->cliente_id)
                        ->get();

                    if ($conta->valor_integral > $request->valor) {
                        $contasParaReceber = [];
                    }

                    return view('contaReceber/valorDivergente')
                        ->with('conta', $conta)
                        ->with('valor', $valor)
                        ->with('tipo_pagamento', $request->tipo_pagamento)
                        ->with('receberConta', true)
                        ->with('contasParaReceber', $contasParaReceber)
                        ->with('title', 'Receber Conta');
                } else {
                    $contasParaReceber = [];
                    return view('contaReceber/valorDivergente')
                        ->with('conta', $conta)
                        ->with('valor', $valor)
                        ->with('tipo_pagamento', $request->tipo_pagamento)
                        ->with('receberConta', true)
                        ->with('contasParaReceber', $contasParaReceber)
                        ->with('title', 'Receber Conta');
                }
            } else {
                // Ajuste para tratar a data de recebimento conforme o tamanho do input
                if (strlen($request->data_pagamento) == 10) {
                    $dtReceb = \Carbon\Carbon::createFromFormat('d/m/Y', $request->data_pagamento)
                            ->format('Y-m-d') . " " . date("H:i:s");
                } else {
                    $dtReceb = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $request->data_pagamento)
                        ->format('Y-m-d H:i:s');
                }

                $conta->status = true;
                $conta->valor_recebido = __replace($request->valor);
                $conta->data_recebimento = $dtReceb;
                $conta->tipo_pagamento = $request->tipo_pagamento;

                $result = $conta->save();

                if (isset($request->conta_id)) {

                    $tipoPagamento = \App\Models\Venda::getTipoPagamentoNFe($request->tipo_pagamento);

                    $data = [
                        'conta_id'       => $request->conta_id,
                        'descricao'      => "Recebimento de conta " . $conta->referencia,
                        'tipo_pagamento' => $tipoPagamento,
                        'valor'          => $conta->valor_recebido,
                        'tipo'           => 'entrada'
                    ];
                    $itemContaEmpresa = ItemContaEmpresa::create($data);
                    $this->util->atualizaSaldo($itemContaEmpresa);
                }

                if ($result) {
                    session()->flash('mensagem_sucesso', 'Conta recebida!');
                } else {
                    session()->flash('mensagem_erro', 'Erro!');
                }

                $rota = __getRedirect($this->empresa_id, 'contas_receber');
                if ($rota != "") {
                    return redirect($rota);
                }
                return redirect('/contasReceber');
            }
            // fim codigo valor divergente
        } else {
            return redirect('/403');
        }
    }

	public function delete($id){
		$conta = ContaReceber
		::where('id', $id)
		->first();
		if($conta->venda_id != null){
			session()->flash('mensagem_erro', 'Esta conta esta vinculada a uma venda!');
			return redirect('/contasReceber');
		}

		if($conta->boleto){
			session()->flash('mensagem_erro', 'Conta já possui boleto emitido!');
			return redirect('/contasReceber');
		}

		if(valida_objeto($conta)){
			if($conta->delete()){

				session()->flash('mensagem_sucesso', 'Registro removido!');
			}else{

				session()->flash('mensagem_erro', 'Erro!');
			}
			return redirect()->back();
		}else{
			return redirect('/403');
		}
	}

	private function parseDate($date, $plusDay = false){
		if($plusDay == false)
			return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
		else
			return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
	}

	private function parseRecorrencia($rec){
		$temp = explode("/", $rec);
		$rec = "01/".$temp[0]."/20".$temp[1];
		//echo $rec;
		return date('Y-m', strtotime(str_replace("/", "-", $rec)));
	}

	public function relatorio(Request $request){
		$dataInicial = $request->data_inicial;
		$dataFinal = $request->data_final;
		$cliente = $request->cliente;
		$status = $request->status;
		$filial_id = $request->filial_id;

		$contas = null;

		$permissaoAcesso = __getLocaisUsarioLogado();

		$contas = ContaReceber::
		select('conta_recebers.*')
		->where(function($query) use ($permissaoAcesso){
			if($permissaoAcesso != null){
				foreach ($permissaoAcesso as $value) {
					if($value == -1){
						$value = null;
					}
					$query->orWhere('conta_recebers.filial_id', $value);
				}
			}
		})
		->when($filial_id, function ($query) use ($filial_id) {
			$filial_id = $filial_id == -1 ? null : $filial_id;
			return $query->where('conta_recebers.filial_id', $filial_id);
		});

		if($cliente != 'null'){
			$contas->join('clientes', 'clientes.id' , '=', 'conta_recebers.cliente_id');
			$contas->where('conta_recebers.cliente_id', $cliente);
		}

		if($dataInicial && $dataFinal){

			if($request->tipo_filtro_data == 1){
				$contas->whereBetween('conta_recebers.data_vencimento',
					[
						$this->parseDate($dataInicial),
						$this->parseDate($dataFinal)
					]
				);
			}elseif($request->tipo_filtro_data == 2){
				$contas->whereBetween('conta_recebers.created_at',
					[
						$this->parseDate($dataInicial),
						$this->parseDate($dataFinal, true)
					]
				);
			}else{

				$d1 = str_replace("/", "-", $dataInicial);
				$d2 = str_replace("/", "-", $dataFinal);

				$contas->whereBetween('conta_recebers.data_recebimento',
					[
						\Carbon\Carbon::parse($d1)->format('Y-m-d') . "",
						\Carbon\Carbon::parse($d2)->format('Y-m-d') . ""
					]
				);
			}
		}

		if($status != 'todos'){
			if($status == 'pago'){
				$contas->where('status', true);
			} else if($status == 'pendente'){
				$contas->where('status', false);
			}else if($status == 'vencido'){
				$contas->where('status', false)
				->whereDate('data_vencimento', '<=', date('Y-m-d'));
			}
		}

		if($request->tipo_filtro_data == 3){
			$contas->where('status', true);
		}
		$contas->where('conta_recebers.empresa_id', $this->empresa_id);
		// $contas->groupBy('conta_recebers.data_vencimento');
		$contas->orderBy('conta_recebers.data_vencimento', 'asc');

		if($request->categoria != 'todos'){
			$contas->where('categoria_id', $request->categoria);
		}

		if($request->numero_nota_fiscal){
			$contas->where('conta_recebers.numero_nota_fiscal', $request->numero_nota_fiscal);
		}

		$contas = $contas->get();

		// echo $contas;


		$p = view('relatorios/relatorio_contas_receber')
		->with('data_inicial', $request->data_inicial)
		->with('data_final', $request->data_final)
		->with('contas', $contas);

		// return $p;

		$domPdf = new Dompdf(["enable_remote" => true]);
		$domPdf->loadHtml($p);

		$pdf = ob_get_clean();

		$domPdf->setPaper("A4");
		$domPdf->render();
		$domPdf->stream("Relatorio de Contas a Receber.pdf", array("Attachment" => false));

	}

	public function receberSomente(Request $request){
		$conta = ContaReceber::find($request->id);
		$valor = __replace($request->valor);

		$conta->status = true;
		$conta->valor_recebido = $request->valor;
		$conta->data_recebimento = date("Y-m-d") . " " . date('H:i:s');
		$conta->tipo_pagamento = $request->tipo_pagamento;

		$result = $conta->save();
		if($result){

			session()->flash('mensagem_sucesso', 'Conta recebida!');
		}else{

			session()->flash('mensagem_erro', 'Erro!');
		}
		return redirect('/contasReceber');
	}

	public function receberComDivergencia(Request $request){
		$conta = ContaReceber::find($request->id);
		$valor = __replace($request->valor);

		$res = ContaReceber::create([
			'venda_id' => $conta->venda_id,
			'venda_caixa_id' => $conta->venda_caixa_id,
			'cliente_id' => $conta->cliente_id,
			'data_vencimento' => $conta->data_vencimento,
			'data_recebimento' => $conta->data_recebimento,
			'valor_integral' => $conta->valor_integral - $valor,
			'valor_recebido' => 0,
			'status' => false,
			'referencia' => $conta->referencia,
			'categoria_id' => $conta->categoria_id,
			'empresa_id' => $this->empresa_id,
		]);

		$conta->status = true;
		$conta->valor_recebido = $request->valor;
		$conta->valor_integral = $request->valor;
		$conta->tipo_pagamento = $request->tipo_pagamento;
		$conta->data_recebimento = date("Y-m-d") . " " . date('H:i:s');

		$result = $conta->save();
		if($result){
			$id = $res->id;
			session()->flash('mensagem_sucesso', 'Conta recebida parcialmente, uma nova foi criada com ID: ' . $id);
		}else{

			session()->flash('mensagem_erro', 'Erro!');
		}
		return redirect('/contasReceber');
	}

	public function receberComOutros(Request $request){
		$conta = ContaReceber::find($request->id);
		$valor = $request->valor;
		$temp = "";
		$somaParaTroco = $conta->valor_integral;
		try{
			if(isset($request->contas)){
				$contasMais = explode(",", $request->contas);
				// print_r($contasMais);
				foreach($contasMais as $key => $c){
					$ctemp = ContaReceber::find($c);
					$ctemp->status = true;
					$ctemp->valor_recebido = $ctemp->valor_integral;
					$ctemp->data_recebimento = date("Y-m-d") . " " . date('H:i:s');
					$ctemp->save();

					$temp .= " $c" . (sizeof($contasMais)-1 > $key ? "," : "");

					$somaParaTroco += $ctemp->valor_integral;
				}
			}

			$conta->status = true;
			$conta->valor_recebido = $conta->valor_integral;
			$conta->data_recebimento = date("Y-m-d") . " " . date('H:i:s');
			$conta->save();

			$troco = $valor - $somaParaTroco;

			$msg = "Sucesso conta(s) com ID: $conta->id, " . $temp . " recebida(s)";

			if($troco > 0){
				$msg .= " , valor de troco: R$ " . number_format($troco, 2);
			}
			session()->flash('mensagem_sucesso', $msg);

			return redirect('/contasReceber');

		}catch(\Exception $e){
			session()->flash('mensagem_erro', 'Ocorreu um erro ao receber: ' . $e->getMessage());

		}
	}

	public function detalhesVenda($contaId){
		$conta = ContaReceber::find($contaId);

		if(valida_objeto($conta)){

			if($conta->venda_id != null){
				// venda nfe
				return redirect('/vendas/detalhar/'.$conta->venda_id);
			}else{
				// venda pdv
				return redirect('/nfce/detalhes/'.$conta->venda_caixa_id);
			}
		}else{
			return redirect('/403');
		}
	}

	public function pendentes(){
		$clientes = Cliente::
		where('empresa_id', $this->empresa_id)
		->where('inativo', false)
		->get();

		$title = 'Contas pendentes';
		return view('contaReceber/pendentes', compact('clientes', 'title'));
	}

	public function filtroPendente(Request $request){

		if(!$request->clienteId){
			session()->flash('mensagem_erro', 'Informe o cliente');
			return redirect()->back();
		}

		$clientes = Cliente::
		where('empresa_id', $this->empresa_id)
		->where('inativo', false)
		->get();

		$title = 'Contas pendentes';
		$dataInicial = $request->data_inicial;
		$DataFinal = $request->data_final;
		$clienteId = $request->clienteId;
		$tipo_pagamento = $request->tipo_pagamento;

		$contas = ContaReceber::
		where('empresa_id', $this->empresa_id)
		->where('cliente_id', $clienteId)
		->orderBy('data_vencimento', 'desc')
		->where('status', 0);

		if($tipo_pagamento){
			$contas->where('tipo_pagamento', $request->tipo_pagamento);
		}
		$contas = $contas->get();

		return view('contaReceber/pendentes',
			compact('clientes', 'title', 'contas', 'dataInicial', 'DataFinal', 'tipo_pagamento', 'clienteId')
		);
	}

	public function receberMultiplos($ids){
		$temp = explode(",", $ids);
		$contas = [];

		$somaTotal = 0;

		foreach($temp as $i){
			$conta = ContaReceber::find($i);
			if($conta->empresa_id != $this->empresa_id){
				session()->flash('mensagem_erro', "Erro inesperado!");
				return redirect()->back();
			}
			$somaTotal += $conta->valor_integral;

			array_push($contas, $conta);
		}

		if(sizeof($contas) <= 1){
			session()->flash('mensagem_erro', "É necessário selecionar mais de uma conta!");
			return redirect()->back();
		}
		$title = 'Receber contas';

		$contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)
		->where('status', 1)->get();

		return view('contaReceber/receber_multi',
			compact('somaTotal', 'title', 'contas', 'ids', 'contasEmpresa')
		);
	}

	public function receberMulti(Request $request){
		// print_r($request->all());

		$dtReceb = \Carbon\Carbon::parse(str_replace("/", "-", $request->data_pagamento))->format('Y-m-d');
		$dtReceb .= " " . date("H:i:s");
		$temp = explode(",", $request->ids);
		$valorRecebido = __replace($request->valor);
		$somaTotal = $request->somaTotal;
		$tipo_pagamento = $request->tipo_pagamento;

		$somaPagamento = 0;
		$diferenca = 0;
		foreach($temp as $i){
			$conta = ContaReceber::find($i);
			if($conta->empresa_id == $this->empresa_id){
				$somaPagamento += $conta->valor_integral;
				$conta->status = 1;
				$conta->valor_recebido = $conta->valor_integral;
				$conta->tipo_pagamento = $tipo_pagamento;
				$conta->data_recebimento = $dtReceb;

				if(isset($request->conta_id)){

					$tipoPagamento = \App\Models\Venda::getTipoPagamentoNFe($request->tipo_pagamento);

					$data = [
						'conta_id' => $request->conta_id,
						'descricao' => "Recebimento de conta " . $conta->referencia,
						'tipo_pagamento' => $tipoPagamento,
						'valor' => $conta->valor_integral,
						'tipo' => 'entrada'
					];
					$itemContaEmpresa = ItemContaEmpresa::create($data);
					$this->util->atualizaSaldo($itemContaEmpresa);
				}

				if($somaPagamento <= $valorRecebido){
					$conta->save();
				}else{
				// criar uma nova
					if($diferenca == 0){

						$diferenca = $somaPagamento - $valorRecebido;

						$novoValor = $conta->valor_integral - $diferenca;
						$conta->valor_integral = $novoValor;
						$novaConta = $conta->makeHidden(['created_at', 'updated_at']);

						$conta->save();

						$novaConta->status = 0;
						$novaConta->valor_integral = $diferenca;
						$novaConta = $novaConta->toArray();
						ContaReceber::create($novaConta);

					}

				}
			}
		}


		if($somaPagamento == $valorRecebido){
			session()->flash('mensagem_sucesso', "Contas recebidas!");
		}else{
			session()->flash('mensagem_sucesso', "As contas foram recebidas, porém com saldo insuficiente, uma nova conta com a diferença R$".number_format($diferenca, 2, ',', '.' )." foi criada!");
		}

		return redirect('/contasReceber');
		die;
	}

	public function importacao(){
		$zip_loaded = extension_loaded('zip') ? true : false;
		if ($zip_loaded === false) {
			session()->flash('mensagem_erro', "Por favor instale/habilite o PHP zip para importar");
			return redirect()->back();
		}


		return view('contaReceber/importacao')
		->with('title', 'Importação de conta receber');
	}

	public function downloadModelo(){
		try{
			$public = env('SERVIDOR_WEB') ? 'public/' : '';
			return response()->download(public_path('files/') . 'import_conta_receber_csv_template.xlsx');
		}catch(\Exception $e){
			echo $e->getMessage();
		}
	}

	public function importacaoStore(Request $request){
		if ($request->hasFile('file')) {
			ini_set('max_execution_time', 0);
			ini_set('memory_limit', -1);

			$filial_id = $request->filial_id;

			$rows = Excel::toArray(new ProdutoImport, $request->file);
			$retornoErro = $this->validaArquivo($rows);

			if($retornoErro == ""){
				$cont = 0;
				foreach($rows as $row){
					foreach($row as $key => $r){
						if($key > 0){

							try{
								$objeto = $this->preparaObjeto($r, $filial_id);
								if($objeto != null){
									ContaReceber::create($objeto);
									$cont++;
								}
							}catch(\Exception $e){
								echo $e->getMessage() . ", linha: " . $e->getLine();
								die;
								session()->flash('mensagem_erro', $e->getMessage());
								return redirect()->back();
							}

							session()->flash('mensagem_sucesso', "Contas inseridas: $cont");
							return redirect('/contasReceber');
						}
					}
				}
			}else{
				session()->flash('mensagem_erro', $retornoErro);
				return redirect()->back();
			}

		}
	}

	private function preparaObjeto($r, $filial_id){
		if(trim($r[1]) == ""){
			return null;
		}

		$documento = $r[1];

		$documento = trim(preg_replace('/[^0-9]/', '', $documento));
		$cliente = Cliente::where('cpf_cnpj', $documento)->first();
		if($cliente == null){
			$mask = "###.###.###-##";
			if(strlen($documento) == 14){
				$mask = "##.###.###/####-##";
			}

			$documento = $this->__mask($documento, $mask);
			$cliente = Cliente::where('cpf_cnpj', $documento)->first();

		}

		if($cliente == null){
			$cliente = $this->cadastrarCliente($r);
		}

		$valor = $r[11];
		$vencimento = $r[12];
		$referencia = $r[13];
		$status = $r[14] != '' ? $r[14] : 0;

		$v = str_replace("/", "-", $vencimento);

		$v = \Carbon\Carbon::parse($v)->format('Y-m-d') . " " . date('H:i:s');

		$data = [
			'venda_id' => null,
			'data_vencimento' => $v,
			'data_recebimento' => $v,
			'valor_integral' => __replace($valor),

			'valor_recebido' => $status ? __replace($valor) : 0,
			'referencia' => $referencia != '' ? $referencia : '',
			'categoria_id' => CategoriaConta::where('empresa_id', $this->empresa_id)->where('tipo', 'receber')->first()->id,
			'status' => $status,
			'empresa_id' => $this->empresa_id,

			'cliente_id' => $cliente->id,
			'juros' => 0,
			'multa' => 0,
			'venda_caixa_id' => null,
			'observacao' => '',
			'tipo_pagamento' => '',

			'filial_id' => $filial_id == -1 ? null : $filial_id,
			'entrada' => 0
		];
		return $data;
	}

	private function cadastrarCliente($r){
		$nome = $r[0];
		$documento = $r[1];
		$documento = trim(preg_replace('/[^0-9]/', '', $documento));

		$ie = $r[2];
		$rua = $r[4];
		$numero = $r[5];
		$bairro = $r[6];
		$cidade = $r[7];
		$uf = $r[8];
		$cep = $r[9];
		$email = $r[10];
		$cidade = Cidade::where('nome', $cidade)->where('uf', $uf)->first();
		return Cliente::create([
			'razao_social' => $nome,
			'cpf_cnpj' => $documento,
			'ie_rg' => $ie != '' ? $ie : '',
			'rua' => $rua,
			'numero' => $numero,
			'bairro' => $bairro,
			'cep' => $cep,
			'email' => $email != '' ? $email : '',
			'cidade_id' => $cidade ? $cidade->id : 1,
			'consumidor_final' => 1,
			'limite_venda' => 0,
			'contribuinte' => $ie != '' ? 1 : 0,
			'empresa_id' => $this->empresa_id
		]);
	}

	private function validaArquivo($rows){
		$cont = 0;
		$msgErro = "";
		foreach($rows as $row){
			foreach($row as $key => $r){
				if($key > 0){
					$nome = $r[0];
					$documento = $r[1];
					$ie = $r[2];

					$documento = trim(preg_replace('/[^0-9]/', '', $documento));
					$cliente = Cliente::where('cpf_cnpj', $documento)
					->where('empresa_id', $this->empresa_id)
					->first();
					if($cliente == null){
						$mask = "###.###.###-##";
						if(strlen($documento) == 14){
							$mask = "##.###.###/####-##";
						}

						$documento = $this->__mask($documento, $mask);
						$cliente = Cliente::where('cpf_cnpj', $documento)
						->where('empresa_id', $this->empresa_id)
						->first();

					}
					$rua = $r[4];
					$numero = $r[5];
					$bairro = $r[6];
					$cidade = $r[7];
					$uf = $r[8];
					$cep = $r[9];
					$email = $r[10];
					$valor = $r[11];
					$vencimento = $r[12];

					if($cliente == null){
						if(strlen($nome) == 0){
							$msgErro .= "Coluna nome em branco na linha: $cont | ";
						}

						if(strlen($rua) == 0){
							$msgErro .= "Coluna rua em branco na linha: $cont | ";
						}

						if(strlen($numero) == 0){
							$msgErro .= "Coluna numero em branco na linha: $cont";
						}

						if(strlen($numero) == 0){
							$msgErro .= "Coluna numero em branco na linha: $cont";
						}
						if(strlen($bairro) == 0){
							$msgErro .= "Coluna bairro em branco na linha: $cont";
						}
						if(strlen($cidade) == 0){
							$msgErro .= "Coluna cidade em branco na linha: $cont";
						}
						if(strlen($uf) == 0){
							$msgErro .= "Coluna uf em branco na linha: $cont";
						}
					}

					if(strlen($valor) == 0){
						$msgErro .= "Coluna valor em branco na linha: $cont";
					}
					if(strlen($vencimento) == 0){
						$msgErro .= "Coluna vencimento em branco na linha: $cont";
					}

					if($msgErro != ""){
						return $msgErro;
					}
					$cont++;
				}
			}
		}

		return $msgErro;
	}

	private function __mask($val, $mask){
		$maskared = '';
		$k = 0;
		for ($i = 0; $i <= strlen($mask) - 1; ++$i) {
			if ($mask[$i] == '#') {
				if (isset($val[$k])) {
					$maskared .= $val[$k++];
				}
			} else {
				if (isset($mask[$i])) {
					$maskared .= $mask[$i];
				}
			}
		}

		return $maskared;
	}

    public function exportExcel(Request $request)
    {
        // Obtém os parâmetros de filtro
        $dataInicial         = $request->input('data_inicial');
        $dataFinal           = $request->input('data_final');
        $clienteId           = $request->input('cliente'); // ou 'clienteId'
        $status              = $request->input('status', 'todos');
        $categoria           = $request->input('categoria', 'todos');
        $tipo_pagamento      = $request->input('tipo_pagamento');
        $numero_nota_fiscal  = $request->input('numero_nota_fiscal');
        $filial_id           = $request->input('filial_id'); // -1 = Matriz
        $tipo_filtro_data    = $request->input('tipo_filtro_data', 1);

        // Converte datas
        $dataInicialFormatada = $dataInicial ? $this->parseDate($dataInicial) : date("Y-m-d");
        $dataFinalFormatada   = $dataFinal ? $this->parseDate($dataFinal, true) : date('Y-m-d', strtotime('+1 month'));

        // Monta a query com joins e seleção de campos (inclui nome da filial)
        $query = \DB::table('conta_recebers as cr')
            ->join('categoria_contas as cc', 'cr.categoria_id', '=', 'cc.id')
            ->leftJoin('clientes as cli', 'cr.cliente_id', '=', 'cli.id')
            ->leftJoin('filials as fil', 'cr.filial_id', '=', 'fil.id')
            ->select(
                'cr.id',
                'cr.referencia',
                'cr.valor_integral',
                'cr.valor_recebido',
                'cr.data_vencimento',
                'cr.data_recebimento',
                'cr.status',
                'cr.tipo_pagamento',
                'cr.numero_nota_fiscal',
                'cr.categoria_id',
                'cr.cliente_id',
                'cr.empresa_id',
                'cr.filial_id',
                'cc.nome as categoria_nome',
                'cli.razao_social as cliente_razao',
                'cli.cpf_cnpj as cliente_cpf_cnpj',
                \DB::raw("COALESCE(fil.descricao, 'Matriz') as filial_nome")
            )
            ->where('cr.empresa_id', $this->empresa_id);

        // Filtro por período
        if ($dataInicial && $dataFinal) {
            if ((int)$tipo_filtro_data === 1) {
                $query->whereBetween('cr.data_vencimento', [$dataInicialFormatada, $dataFinalFormatada]);
            } elseif ((int)$tipo_filtro_data === 2) {
                $query->whereBetween('cr.created_at', [$this->parseDate($dataInicial), $this->parseDate($dataFinal, true)]);
            } else {
                $query->whereBetween('cr.data_recebimento', [$this->parseDate($dataInicial), $this->parseDate($dataFinal, true)]);
            }
        }

        // Demais filtros
        if ($clienteId && $clienteId != 'null') {
            $query->where('cr.cliente_id', $clienteId);
        }

        if ($status && $status !== 'todos') {
            if ($status === 'pago') {
                $query->where('cr.status', true);
            } elseif ($status === 'pendente') {
                $query->where('cr.status', false);
            } elseif ($status === 'vencido') {
                $query->where('cr.status', false)
                    ->whereDate('cr.data_vencimento', '<=', date('Y-m-d'));
            }
        }

        if ($categoria && $categoria !== 'todos') {
            $query->where('cr.categoria_id', $categoria);
        }

        if ($tipo_pagamento) {
            $query->where('cr.tipo_pagamento', $tipo_pagamento);
        }

        if ($numero_nota_fiscal) {
            $query->where('cr.numero_nota_fiscal', $numero_nota_fiscal);
        }

        // >>> CORREÇÃO DO FILTRO DE FILIAL <<<
        // -1 => Matriz (no banco costuma estar como NULL; manter fallback para -1 se existir)
        if ($filial_id !== null && $filial_id !== '' && $filial_id !== 'null') {
            if ((int)$filial_id === -1) {
                $query->where(function ($q) {
                    $q->whereNull('cr.filial_id')
                        ->orWhere('cr.filial_id', -1);
                });
            } else {
                $query->where('cr.filial_id', $filial_id);
            }
        }
        // Caso contrário (sem filial_id), retorna "todas" (Matriz + Filiais)

        $contas = $query->orderBy('cr.data_vencimento', 'asc')->get();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ContasReceberExport($contas),
            'Relatorio_Contas_Receber.xlsx'
        );
    }

    public function syncNotaFiscal()
    {
        $updatedCount = 0;

        // Sincronização para contas a receber vinculadas a venda (venda_id)
        $contasVenda = ContaReceber::whereNotNull('venda_id')
            ->where(function ($query) {
                $query->whereNull('numero_nota_fiscal')
                    ->orWhere('numero_nota_fiscal', '=', 0)
                    ->orWhere('numero_nota_fiscal', '=', '');
            })
            ->where('empresa_id', $this->empresa_id)
            ->get();

        foreach ($contasVenda as $conta) {
            // Busca a venda vinculada
            $venda = \App\Models\Venda::find($conta->venda_id);
            // Se a venda existir e tiver um número de nota válido, atualiza a conta
            if ($venda && !empty($venda->nf) && $venda->nf != 0) {
                $conta->numero_nota_fiscal = $venda->nf;
                if ($conta->save()) {
                    $updatedCount++;
                }
            }
        }

        // Sincronização para contas a receber vinculadas a venda de caixa (venda_caixa_id)
        $contasVendaCaixa = ContaReceber::whereNotNull('venda_caixa_id')
            ->where(function ($query) {
                $query->whereNull('numero_nota_fiscal')
                    ->orWhere('numero_nota_fiscal', '=', 0)
                    ->orWhere('numero_nota_fiscal', '=', '');
            })
            ->where('empresa_id', $this->empresa_id)
            ->get();

        foreach ($contasVendaCaixa as $conta) {
            // Busca a venda de caixa vinculada
            $vendaCaixa = \App\Models\VendaCaixa::find($conta->venda_caixa_id);
            // Se a venda de caixa existir e tiver um número de nota válido, atualiza a conta
            if ($vendaCaixa && !empty($vendaCaixa->nf) && $vendaCaixa->nf != 0) {
                $conta->numero_nota_fiscal = $vendaCaixa->nf;
                if ($conta->save()) {
                    $updatedCount++;
                }
            }
        }

        // Define o retorno padrão conforme o padrão utilizado
        if ($updatedCount >= 0) {
            session()->flash('mensagem_sucesso', "Sincronização concluída. {$updatedCount} conta(s) atualizada(s)!");
            $result = true;
        } else {
            session()->flash('mensagem_erro', 'Ocorreu um erro!');
            $result = false;
        }

        // Redireciona conforme o padrão: se existir uma rota salva, redireciona para ela; caso contrário, para /contasReceber
        $rota = __getRedirect($this->empresa_id, 'contas_receber');
        if ($rota != "") {
            return redirect($rota);
        }
        return redirect('/contasReceber');
    }

}
