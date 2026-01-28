<?php

namespace App\Http\Middleware;

use Closure;
use Response;
use App\Models\UsuarioAcesso;

class AcessoUsuario
{
    public function handle($request, Closure $next)
    {
        // Permitir acesso à rota de login
        if ($request->is('login') || $request->is('login/*')) {
            return $next($request);
        }

        // Verifica se a sessão do usuário está ativa
        $value = session('user_logged');

        if (!$value || !isset($value['id'])) {
            session()->flash('mensagem_login', 'Sessão expirada. Por favor, faça login novamente.');
            return redirect("/login");
        }

        // Busca o acesso ativo do usuário pelo ID e status
        $acesso = UsuarioAcesso::where('usuario_id', $value['id'])
            ->where('status', 0)
            ->first();

        if (!$acesso) {
            session()->flash('mensagem_login', 'Sessão expirada ou não encontrada. Por favor, faça login novamente.');
            return redirect("/login");
        }

        // Validação de hash e IP
        if ($value['hash'] != $acesso->hash || $value['ip_address'] != $this->getClientIp()) {
            session()->flash('mensagem_login', 'Já existe uma sessão ativa em outro equipamento.');

            // Remove todas as sessões duplicadas
            UsuarioAcesso::where('usuario_id', $value['id'])
                ->where('status', 0)
                ->delete();

            return redirect("/login/logoff?notmessage=1");
        }

        return $next($request);
    }

    private function getClientIp()
    {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return 'UNKNOWN';
    }
}

/*

<?php

namespace App\Http\Middleware;

use Closure;
use Response;
use App\Models\UsuarioAcesso;

class AcessoUsuario
{
	public function handle($request, Closure $next){

		$response = $next($request);

		$value = session('user_logged');

		if(isset($value['id'])){
			$acesso = UsuarioAcesso::
			where('usuario_id', $value['id'])
			->where('status', 0)
			->first();

			if(!$acesso){
				return $response;
			}
		}else{
			return $response;
		}


		// echo (strtotime(date('Y-m-d')) - strtotime($acesso->created_at))/60/24;
		// die;

		if($value['hash'] != $acesso->hash && $value['ip_address'] != $this->get_client_ip()){
			session()->flash('mensagem_login', 'Já existe uma sessão ativa em outro equipamento.');

			$acesso2 = UsuarioAcesso::
			where('usuario_id', $value['id'])
			->where('status', 0)
			->orderBy('id', 'desc')
			->first();
			$acesso2->delete();

			// $acesso->delete();
			return redirect("/login/logoff?notmessage=1");
		}

		return $response;

	}

	private function get_client_ip() {
		$ipaddress = '';
		if (isset($_SERVER['HTTP_CLIENT_IP']))
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_X_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if(isset($_SERVER['REMOTE_ADDR']))
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else
			$ipaddress = 'UNKNOWN';
		return $ipaddress;
	}

}
*/