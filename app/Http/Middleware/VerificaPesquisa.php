<?php

namespace App\Http\Middleware;

use Closure;
use Response;
use App\Models\Empresa;
use App\Models\Pesquisa;
use App\Models\PesquisaResposta;
use App\Models\UsuarioAcesso;

class VerificaPesquisa
{

	public function handle($request, Closure $next){

		$response = $next($request);
		$value = session('user_logged');

		if($value){
			try{
				$pesquisas = Pesquisa::where('status', 1)
				->limit(5)
				->get();

				foreach($pesquisas as $p){
					$ex = PesquisaResposta::
					where('empresa_id', $value['empresa'])
					->where('pesquisa_id', $p->id)
					->exists();

					if(!$ex){
						if($p->maximo_acessos > 0){
							$acessos = UsuarioAcesso::
							whereBetween('created_at', [
								$p->created_at,
								date('Y-m-d H:i')
							])->count();

							if($acessos > $p->maximo_acessos){
								session()->flash('forcar_pesquisa', 1);
							}
						}
						session()->flash('pesquisa_satisfacao', $p->id);
					}
				}
			}catch(\Exception $e){}

			return $response;
		}else{
			return redirect('/login');
		}


	}

}