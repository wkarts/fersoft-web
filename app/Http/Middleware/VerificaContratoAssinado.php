<?php

namespace App\Http\Middleware;

use Closure;
use Response;
use App\Models\Empresa;
use App\Models\Contrato;

class VerificaContratoAssinado
{

	public function handle($request, Closure $next){

		$value = session('user_logged');
		// return $next($request);
		
		if($value['super'] || $value['tipo_representante']){
			return $next($request);
		}

		$empresa = Empresa::find($value['empresa']);
		$dataHoje = strtotime(date('Y-m-d'));
		
		$exp = $empresa->planoEmpresa ? $empresa->planoEmpresa->expiracao : null;
		$dif = strtotime($exp) - $dataHoje;
		$dias = $dif/60/60/24;

		if($dias <= 0 && $empresa->planoEmpresa->expiracao != '0000-00-00'){
			session()->flash("mensagem_erro", "Realize o pagamento para continuar!");
			return redirect('/payment');
		}
		
		return $next($request);

		$uri = $_SERVER['REQUEST_URI'];
		if($uri == '/configNF' || $uri == '/configNF/save'){
			return $next($request);
		}

		$contrato = Contrato::first();

		if(!$empresa->planoEmpresa){
			session()->forget('user_logged');
			session()->flash('mensagem_login', 'Contate o administrador, plano expirado.');
			return redirect("/login");
		}

		if($contrato != null){

			$dias = $contrato->accessos_forcar_assinar;

			if($empresa->contrato == null){
				return $next($request);
			}
			
			// $dataContrato = \Carbon\Carbon::parse($empresa->contrato->created_at)->format('Y-m-d');
			$dataContrato = \Carbon\Carbon::parse($empresa->created_at)->format('Y-m-d');

			$dataContrato = strtotime($dataContrato);
			$dif = $dataHoje - $dataContrato;
			$dif = $dif/24/60/60;

			if($dif >= $dias && $empresa->contrato->status == 0){
				session()->flash("mensagem_erro", "Assine o contrato para continuar!");
				return redirect('/assinarContrato');
			}
		}
		return $next($request);

	}

}