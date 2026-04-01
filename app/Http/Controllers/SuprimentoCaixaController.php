<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SuprimentoCaixa;
use App\Models\AberturaCaixa;
use App\Models\ConfigNota;
use App\Models\ItemContaEmpresa;
use App\Models\Usuario;
use NFePHP\DA\NFe\ComprovanteCaixa;
use App\Utils\ContaEmpresaUtil;

class SuprimentoCaixaController extends Controller
{
	protected $util;
	protected $empresa_id = null;
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

	public function save(Request $request){
		$result = SuprimentoCaixa::create([
			'usuario_id' => get_id_user(),
			'valor' => str_replace(",", ".", $request->valor),
			'observacao' => $request->obs ?? '',
			'tipo' => $request->tipo,
			'empresa_id' => $this->empresa_id
		]);

		$abertura = AberturaCaixa::
		where('empresa_id', $this->empresa_id)
		->where('status', 0)
		->orderBy('id', 'desc')
		->first();

		if($abertura->conta_id){
			$data = [
				'conta_id' => $abertura->conta_id,
				'descricao' => "Suprimento de caixa",
				'tipo_pagamento' => $request->tipo,
				'valor' => __replace($request->valor),
				'caixa_id' => $abertura->id,
				'tipo' => 'entrada'
			];
			$itemContaEmpresa = ItemContaEmpresa::create($data);
			$this->util->atualizaSaldo($itemContaEmpresa);

			$data = [
				'conta_id' => $request->conta_id,
				'descricao' => "Suprimento de caixa",
				'tipo_pagamento' => $request->tipo,
				'valor' => __replace($request->valor),
				'caixa_id' => $abertura->id,
				'tipo' => 'saida'
			];
			$itemContaEmpresa = ItemContaEmpresa::create($data);
			$this->util->atualizaSaldo($itemContaEmpresa);
		}
		echo json_encode($result);

	}

	public function diaria(){
		$ab = AberturaCaixa::where('ultima_venda_nfe', 0)
		->where('ultima_venda_nfce', 0)
		->orderBy('id', 'desc')->first();

		\App\Support\FiscalDateHelper::applyDefaultTimezone();
		$hoje = date("Y-m-d") . " 00:00:00";
		$amanha = date('Y-m-d', strtotime('+1 days')). " 00:00:00";
		$sangrias = SuprimentoCaixa::
		whereBetween('created_at', [$ab->created_at,
			$amanha])
		->where('empresa_id', $this->empresa_id)
		->get();
		echo json_encode($this->setUsuario($sangrias));
	}

	private function setUsuario($sangrias){
		for($aux = 0; $aux < count($sangrias); $aux++){
			$sangrias[$aux]['nome_usuario'] = $sangrias[$aux]->usuario->nome;
		}
		return $sangrias;
	}

	public function imprimir($id){
		$suprimento = SuprimentoCaixa::find($id);
		if(valida_objeto($suprimento)){

			$config = ConfigNota::
			where('empresa_id', $this->empresa_id)
			->first();

			//$public = env('SERVIDOR_WEB') ? 'public/' : '';
            $public = rtrim(public_path(), '/\\') . DIRECTORY_SEPARATOR;
            $pathLogo = $public.'logos/' . $config->logo;
			$usuario = Usuario::find(get_id_user());

			$cupom = new ComprovanteCaixa("Comprovante de suprimento de caixa", $suprimento, $pathLogo, $config, $usuario->config ? $usuario->config->impressora_modelo : 80, $usuario->nome);
			$cupom->monta();
			$pdf = $cupom->render();
			header("Content-Disposition: ; filename=Comprovante operação de caixa.pdf");
			return response($pdf)
			->header('Content-Type', 'application/pdf');
		}else{
			return redirect('/403');
		}
	}

}
