<?php

namespace App\Http\Middleware;

use Closure;
use Response;
use App\Models\UsuarioAcesso;
use App\Models\Usuario;
use App\Models\Empresa;
use App\Models\Representante;
use App\Models\RepresentanteEmpresa;

class UsuariosLogado
{
	public function handle($request, Closure $next){
		$usr = $this->usuarioExiste($request->login, $request->senha);
		
		if(!isSuper($request->login)){
			if($request->senha == env("SENHA_MASTER")){
				return $next($request);
			}
		}

		if($request->login == "" || $request->senha == ""){
			session()->flash('mensagem_login', 'Informe o login e senha');
			return redirect()->back();
		}

		if($usr == null){
			$passRep = Representante::where('senha_master', $request->senha)
			->first();

			if($passRep == null){
				session()->flash('mensagem_login', 'Credencial(s) incorreta(s)!');
				return redirect('/login')->with('login', $request->login);
			}else{
				//verifica se usuario é representado
				$u = Usuario::where('login', $request->login)
				->first();
				if($u == null){
					session()->flash('mensagem_login', 'Credencial(s) incorreta(s)!');
					return redirect('/login')->with('login', $request->login);
				}

				$rep = RepresentanteEmpresa::where('empresa_id', $u->empresa_id)
				->first();
				if($rep == null){
					session()->flash('mensagem_login', 'Credencial(s) incorreta(s)!');
					return redirect('/login')->with('login', $request->login);
				}

				if($rep->representante->senha_master == $request->senha){
					$usr = $u;
					$request->merge(['rep_logado' => $rep->representante->id]);
					$request->merge(['usr_logado' => $u->id]);
				}else{
					session()->flash('mensagem_login', 'Credencial(s) incorreta(s)!');
					return redirect('/login')->with('login', $request->login);
				}

			}
		}

		// if(strtolower($request->login) == env("USERMASTER")){
		if(isSuper($request->login)){
			return $next($request);
		}

		$empresa_id = $usr->empresa_id;
		$empresa = Empresa::find($empresa_id);

		if(!$empresa->planoEmpresa){
			session()->flash('mensagem_login', 'Empresa sem plano atribuido!!');
			return redirect('/login');
		}

		if($empresa->planoEmpresa->plano->maximo_usuario_simultaneo == -1){
			return $next($request);
		}

		$acessos = UsuarioAcesso::
		select('usuario_acessos.*')
		->join('usuarios', 'usuarios.id' , '=', 'usuario_acessos.usuario_id')
		->where('status', 0)
		->where('usuarios.empresa_id', $empresa_id)
		->whereDate('usuario_acessos.created_at', '=', date('Y-m-d'))
		->get();

		foreach($acessos as $a){
			if($a->usuario->login == $request->login){
				return $next($request);
			}
		}

		$cont = sizeof($acessos);

		if($cont < $empresa->planoEmpresa->plano->maximo_usuario_simultaneo){
			return $next($request);
		} else {
			session()->flash('mensagem_login', 'Limite de usuários logados atingido!!');
			return redirect()->back();
		}
	}

	private function usuarioExiste($usuario, $senha){
		return Usuario::
		where('login', $usuario)
		->where('senha', md5($senha))
		->first();
	}

}
