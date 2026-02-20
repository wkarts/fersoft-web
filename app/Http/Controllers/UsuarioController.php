<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\UsuarioAcesso;
use App\Models\ConfigCaixa;
use App\Models\BalancaConfig;
use App\Helpers\Menu;
use App\Services\LogService;
use App\Services\OtpService;

class UsuarioController extends Controller
{

    protected $model = Usuario::class;
    protected $logService;
	protected $empresa_id = null;
    protected $usuario_id;
    protected $filial_id;

	public function __construct(){


		$this->middleware(function ($request, $next) {

            $this->empresa_id = $request->empresa_id;

            $this->usuario_id = session('user_logged')['id'] ?? null;
  			$value = session('user_logged');
			if(!$value){
				return redirect("/login");
			}

            // 🔹 Define a filial padrão com prioridade:
            // 1. Requisição (request param)
            // 2. Sessão do usuário (local_padrao)
            // 3. Fallback: null
            $this->filial_id = $request->get('filial_id')
                ?? session('user_logged.local_padrao')
                ?? null;

            // 🔹 Inicializa o LogService com empresa e usuário automaticamente
            $this->logService = new LogService($this->empresa_id, $this->usuario_id, $this->filial_id);

			return $next($request);
		});
	}

    public function setLocation_(Request $request)
    {
        try {
            // 🔹 Obtém a instância correta da model Usuario
            $modelInstance = is_string(Usuario::class) ? app(Usuario::class) : Usuario::class;

            // 🔹 Busca o usuário e captura os dados antes da alteração
            $usuario = Usuario::findOrFail(get_id_user());
            $dadosAnteriores = $usuario->toArray();

            // 🔹 Converte "-1" para null (representa Matriz no banco)
            $filialId = $request->filial_id;
            if ($filialId === '-1' || $filialId === -1) {
                $filialId = null;
            }

            // 🔹 Atualiza o local padrão do usuário
            $usuario->local_padrao = $filialId;
            $usuario->save();

            // 🔹 Captura os dados após a alteração
            $dadosDepois = $usuario->toArray();

            // 🔹 Atualiza a sessão sem apagar dados obrigatórios
            $user_logged = session('user_logged');
            $user_logged['local_padrao'] = $usuario->local_padrao;
            $user_logged['id'] = $usuario->id; // reforça id
            $user_logged['empresa'] = $usuario->empresa_id; // reforça empresa
            session(['user_logged' => $user_logged]);
            session(['empresa_id' => $usuario->empresa_id]); // reforça empresa global

            //session()->put('user_logged.local_padrao', $request->filial_id);

            // 🔹 Registra log da alteração de localização do usuário
            $this->logService->registrar('update', get_class($modelInstance), [
                'registro_id' => $usuario->id,
                'dados_antes' => $dadosAnteriores,
                'dados_depois' => $dadosDepois,
            ]);

            return response()->json($usuario, 200);
        } catch (\Exception $e) {
            \Log::error('Erro ao atualizar localização do usuário', [
                'usuario_id' => get_id_user(),
                'filial_id' => $request->filial_id,
                'exception' => $e->getMessage(),
            ]);

            return response()->json($e->getMessage(), 401);
        }
    }

    public function setLocation(Request $request)
    {
        try {
            // 🔹 Obtém a instância correta da model Usuario
            $modelInstance = is_string(Usuario::class) ? app(Usuario::class) : Usuario::class;

            // 🔹 Busca o usuário e captura os dados antes da alteração
            $usuario = Usuario::findOrFail(get_id_user());
            $dadosAnteriores = $usuario->toArray();

            // 🔹 Atualiza o local padrão do usuário
            $usuario->local_padrao = $request->filial_id;
            $usuario->save();

            // 🔹 Captura os dados após a alteração
            $dadosDepois = $usuario->toArray();

            // 🔹 Atualiza a sessão
            $user_logged = session('user_logged');
            $user_logged['local_padrao'] = $usuario->local_padrao;
            session(['user_logged' => $user_logged]);
            //session()->put('user_logged.local_padrao', $request->filial_id);

            // 🔹 Registra log da alteração de localização do usuário
            $this->logService->registrar('update', get_class($modelInstance), [
                'registro_id' => $usuario->id,
                'dados_antes' => $dadosAnteriores,
                'dados_depois' => $dadosDepois,
            ]);

            return response()->json($usuario, 200);
        } catch (\Exception $e) {
            \Log::error('Erro ao atualizar localização do usuário', [
                'usuario_id' => get_id_user(),
                'filial_id' => $request->filial_id,
                'exception' => $e->getMessage(),
            ]);

            return response()->json($e->getMessage(), 401);
        }
    }

	public function setLocation_nolog(Request $request){
		try{
			$usuario = Usuario::find(get_id_user());
			$usuario->local_padrao = $request->filial_id;
			$usuario->save();
			return response()->json($usuario, 200);
		}catch(\Exception $e){
			return response()->json($e->getMessage(), 401);
		}
	}

	public function lista(){
		$usuarios = Usuario::
		where('empresa_id', $this->empresa_id)
		->get();
		return view('usuarios/list')
		->with('usuarios', $usuarios)
		->with('title', 'Lista de Usuários');
	}

    public function new()
    {
        try {
            // 🔹 Obtém o usuário logado
            $value = session('user_logged');
            $usuario = Usuario::findOrFail($value['id']);

            // 🔹 Captura os dados antes da alteração
            $dadosAnteriores = $usuario->toArray();

            $permissoesAtivas = $usuario->empresa->permissao;
            $permissoesAtivas = json_decode($permissoesAtivas);
            $permissoesUsuario = [];
            $permissoesDoUsuario = [];

            if ($value['super']) {
                $permissoesAtivas = $this->detalhesMaster();
            }

            $menu = new Menu();
            $menu = $menu->getMenu();

            for ($i = 0; $i < sizeof($menu); $i++) {
                $temp = false;
                foreach ($menu[$i]['subs'] as $s) {
                    if (in_array($s['rota'], $permissoesAtivas)) {
                        $temp = true;
                    }
                }
                $menu[$i]['ativo'] = $temp;
            }

            // 🔹 Captura os dados depois da alteração
            $dadosDepois = [
                'permissoesAtivas' => $permissoesAtivas,
                'menu' => $menu
            ];

            // 🔹 Obtém a instância correta da model Usuario
            $modelInstance = is_string(Usuario::class) ? app(Usuario::class) : Usuario::class;

            // 🔹 Registra log da criação do usuário
            $this->logService->registrar('create', get_class($modelInstance), [
                'registro_id' => $usuario->id,
                'dados_antes' => $dadosAnteriores,
                'dados_depois' => $dadosDepois,
            ]);

            $balancasAtivas = BalancaConfig::where('empresa_id', $this->empresa_id)->where('ativo', true)->get();

            return view('usuarios/register')
                ->with('usuarioJs', true)
                ->with('permissoesAtivas', $permissoesAtivas)
                ->with('permissoesUsuario', $permissoesUsuario)
                ->with('menuAux', $menu)
                ->with('permissoesDoUsuario', $permissoesDoUsuario)
                ->with('balancasAtivas', $balancasAtivas)
                ->with('title', 'Cadastrar Usuário');

        } catch (\Exception $e) {
            \Log::error('Erro ao carregar formulário de novo usuário', [
                'usuario_id' => session('user_logged')['id'] ?? null,
                'exception' => $e->getMessage(),
            ]);

            session()->flash('mensagem_erro', 'Erro ao carregar o formulário de novo usuário.');

            return redirect()->back();
        }
    }

    public function new_nolog(){
		$value = session('user_logged');
		$usuario = Usuario::find($value['id']);
		$permissoesAtivas = $usuario->empresa->permissao;
		$permissoesDoUsuario = [];
		$permissoesAtivas = json_decode($permissoesAtivas);
		$permissoesUsuario = [];

		if($value['super']){
			$permissoesAtivas = $this->detalhesMaster();
		}

		$menu = new Menu();
		$menu = $menu->getMenu();

		for($i=0; $i < sizeof($menu); $i++){
			$temp = false;
			foreach($menu[$i]['subs'] as $s){
				if(in_array($s['rota'], $permissoesAtivas)){
					$temp = true;
				}
			}
			$menu[$i]['ativo'] = $temp;
		}

		$balancasAtivas = BalancaConfig::where('empresa_id', $this->empresa_id)->where('ativo', true)->get();

		return view('usuarios/register')
		->with('usuarioJs', true)
		->with('permissoesAtivas', $permissoesAtivas)
		->with('permissoesUsuario', $permissoesUsuario)
		->with('menuAux', $menu)
		->with('permissoesDoUsuario', $permissoesDoUsuario)
		->with('balancasAtivas', $balancasAtivas)
		->with('title', 'Cadastrar Usuário');
	}

	private function detalhesMaster(){
		$menu = new Menu();
		$menu = $menu->getMenu();
		$temp = [];
		foreach($menu as $m){
			foreach($m['subs'] as $s){
				array_push($temp, $s['rota']);
			}
		}
		return $temp;
	}

	public function edit($id){
		$value = session('user_logged');

		$usuario = Usuario::
		where('id', $id)
		->first();
		if(valida_objeto($usuario)){

			$permissoesAtivas = $usuario->empresa->permissao;
			$permissoesUsuario = $usuario->permissao;
			$permissoesDoUsuario = [];
			$permissoesAtivas = json_decode($permissoesAtivas);
			$permissoesUsuario = json_decode($permissoesUsuario);

			if($value['super']){
				$permissoesAtivas = $this->detalhesMaster();
			}

			$menu = new Menu();
			$menu = $menu->getMenu();


			for($i=0; $i < sizeof($menu); $i++){
				$temp = false;
				foreach($menu[$i]['subs'] as $s){
					if(in_array($s['rota'], $permissoesAtivas)){
						$temp = true;
					}
				}
				$menu[$i]['ativo'] = $temp;
			}

			$balancasAtivas = BalancaConfig::where('empresa_id', $this->empresa_id)->where('ativo', true)->get();

			return view('usuarios/register')
			->with('usuarioJs', true)
			->with('usuario', $usuario)
			->with('permissoesAtivas', $permissoesAtivas)
			->with('permissoesUsuario', $permissoesUsuario)
			->with('menuAux', $menu)
			->with('balancasAtivas', $balancasAtivas)
			->with('title', 'Editar Usuários');
		}else{
			return redirect('/403');
		}
	}

	private function validaPermissao($request){
		$menu = new Menu();
		$arr = $request->all();
		$arr = (array) ($arr);
		$menu = $menu->getMenu();
		$temp = [];
		foreach($menu as $m){
			foreach($m['subs'] as $s){
				if(isset($arr[$s['rota']])){
					array_push($temp, $s['rota']);
				}
			}
		}

		return $temp;

	}

    public function save(Request $request)
    {
        try {
            $this->_validate($request);

            $permissao = $this->validaPermissao($request);

            $locais = $request->local ? json_encode($request->local) : null;
            if ($request->locais == null) {
                $locais = "[-1]";
            }

            $request->merge([
                'local_padrao' => $request->local_padrao > 0 ? $request->local_padrao : null
            ]);
            // dd($locais);

            // 🔹 Captura os dados antes da criação do usuário
            $dadosAnteriores = [];

            // 🔹 Criação do usuário
            $result = Usuario::create([
                'nome' => $request->nome,
                'login' => $request->login,
                'senha' => md5($request->senha),
                'adm' => $request->adm ? true : false,
                'somente_fiscal' => $request->somente_fiscal ? true : false,
                'caixa_livre' => $request->caixa_livre ? true : false,
                'ativo' => $request->ativo ? true : false,
                'email' => $request->email,
                'local_padrao' => $request->local_padrao,
                'balanca_padrao_id' => $this->resolveBalancaPadraoId($request->balanca_padrao_id),
                'locais' => $locais,
                'menu_representante' => isset($request->menu_representante) ? ($request->menu_representante ? true : false) : false,
                'rota_acesso' => $request->rota_acesso ?? '',
                'permissao' => json_encode($permissao),
                'empresa_id' => $this->empresa_id
            ]);

            $this->criaConfigCaixa($result);

            if ($result) {
                session()->flash("mensagem_sucesso", "Usuário salvo!");

                // 🔹 Obtém a instância correta da model Usuario
                $modelInstance = is_string(Usuario::class) ? app(Usuario::class) : Usuario::class;

                // 🔹 Captura os dados após a criação
                $dadosDepois = $result->toArray();

                // 🔹 Registra log da criação do usuário
                $this->logService->registrar('create', get_class($modelInstance), [
                    'registro_id' => $result->id,
                    'dados_antes' => $dadosAnteriores,
                    'dados_depois' => $dadosDepois,
                ]);

            } else {
                session()->flash('mensagem_erro', 'Erro ao criar usuário!');
            }

            return redirect('/usuarios');

        } catch (\Exception $e) {
            \Log::error('Erro ao salvar usuário', [
                'exception' => $e->getMessage(),
            ]);

            session()->flash('mensagem_erro', 'Erro ao salvar usuário. Tente novamente.');

            return redirect()->back()->withInput();
        }
    }

    public function save_nolog(Request $request){

		$this->_validate($request);

		$permissao = $this->validaPermissao($request);

		$locais = $request->local ? json_encode($request->local) : NULL;
		if($request->locais == null){
			$locais = "[-1]";
		}

		$request->merge([
			'local_padrao' => $request->local_padrao > 0 ? $request->local_padrao : null
		]);
		// dd($locais);
		$result = Usuario::create([
			'nome' => $request->nome,
			'login' => $request->login,
			'senha' => md5($request->senha),
			'adm' => $request->adm ? true : false,
			'somente_fiscal' => $request->somente_fiscal ? true : false,
			'caixa_livre' => $request->caixa_livre ? true : false,
			'ativo' => $request->ativo ? true : false,
			'email' => $request->email,
			'local_padrao' => $request->local_padrao,
			'balanca_padrao_id' => $this->resolveBalancaPadraoId($request->balanca_padrao_id),
			'locais' => $locais,
			'menu_representante' => isset($request->menu_representante) ? ($request->menu_representante ? true : false) : false,
			'rota_acesso' => $request->rota_acesso ?? '',
			'permissao' => json_encode($permissao),
			'empresa_id' => $this->empresa_id
		]);

		$this->criaConfigCaixa($result);

		if($result){
			session()->flash("mensagem_sucesso", "Usuário salvo!");
		}else{
			session()->flash('mensagem_erro', 'Erro ao criar usuário!');
		}

		return redirect('/usuarios');
	}

	private function criaConfigCaixa($usuario){
		$data = [
			'finalizar' => '',
			'reiniciar' => '',
			'editar_desconto' => '',
			'editar_acrescimo' => '',
			'editar_observacao' => '',
			'setar_valor_recebido' => '',
			'forma_pagamento_dinheiro' => '',
			'forma_pagamento_debito' => '',
			'forma_pagamento_credito' => '',
			'setar_quantidade' => '',
			'forma_pagamento_pix' => '',
			'setar_leitor' => '',
			'finalizar_fiscal' => '',
			'finalizar_nao_fiscal' => '',
			'valor_recebido_automatico' => 0,
			'modelo_pdv' => 2,
			'balanca_valor_peso' => 0,
			'balanca_digito_verificador' => 5,
			'valor_recebido_automatico' => 0,
			'impressora_modelo' => 80,
			'cupom_modelo' => 2,
			'usuario_id' => $usuario->id,
			'mercadopago_public_key' => '',
			'mercadopago_access_token' => '',
			'tipos_pagamento' => '["01","02","03","04","05","06","10","11","12","13","14","15","16","17","90","99"]',
			'tipo_pagamento_padrao' => '01'
		];
		ConfigCaixa::create($data);
	}

    public function update(Request $request)
    {
        try {
            $this->_validate($request, true);
            $permissao = $this->validaPermissao($request);

            // 🔹 Busca o usuário antes da atualização para capturar os dados anteriores
            $usr = Usuario::where('id', $request->id)->firstOrFail();
            $dadosAnteriores = $usr->toArray();

            $locais = $request->local ? json_encode($request->local) : null;

            // $request->merge([
            // 	'local_padrao' => $request->local_padrao > 0 ? $request->local_padrao : null
            // ]);

            $usr->nome = $request->nome;
            $usr->login = $request->login;
            $usr->locais = $locais;
            $usr->email = $request->email;
            $usr->local_padrao = $request->local_padrao;
            $usr->balanca_padrao_id = $this->resolveBalancaPadraoId($request->balanca_padrao_id);
            $usr->rota_acesso = $request->rota_acesso ?? '';

            if ($request->senha) {
                $usr->senha = md5($request->senha);
            }

            $usr->adm = $request->adm ? true : false;
            $usr->somente_fiscal = $request->somente_fiscal ? true : false;
            $usr->caixa_livre = $request->caixa_livre ? true : false;
            $usr->permite_desconto = $request->permite_desconto ? true : false;

            if (isset($request->menu_representante)) {
                $usr->menu_representante = $request->menu_representante ? true : false;
            }

            $usr->ativo = $request->ativo ? true : false;
            $usr->permissao = json_encode($permissao);

            // 🔹 Salva as alterações
            $result = $usr->save();

            if ($result) {
                session()->flash("mensagem_sucesso", "Usuário atualizado!");

                // 🔹 Obtém a instância correta da model Usuario
                $modelInstance = is_string(Usuario::class) ? app(Usuario::class) : Usuario::class;

                // 🔹 Captura os dados após a atualização
                $dadosDepois = $usr->toArray();

                // 🔹 Registra log da atualização do usuário
                $this->logService->registrar('update', get_class($modelInstance), [
                    'registro_id' => $usr->id,
                    'dados_antes' => $dadosAnteriores,
                    'dados_depois' => $dadosDepois,
                ]);
            } else {
                session()->flash('mensagem_erro', 'Erro ao atualizar usuário!');
            }

            return redirect('/usuarios');

        } catch (\Exception $e) {
            \Log::error('Erro ao atualizar usuário', [
                'usuario_id' => $request->id,
                'exception' => $e->getMessage(),
            ]);

            session()->flash('mensagem_erro', 'Erro ao atualizar usuário. Tente novamente.');

            return redirect()->back()->withInput();
        }
    }

    public function update_nolog(Request $request){

		$this->_validate($request, true);
		$permissao = $this->validaPermissao($request);

		$usr = Usuario::
		where('id', $request->id)
		->first();

		$locais = $request->local ? json_encode($request->local) : null;

		// $request->merge([
		// 	'local_padrao' => $request->local_padrao > 0 ? $request->local_padrao : null
		// ]);

		$usr->nome = $request->nome;
		$usr->login = $request->login;
		$usr->locais = $locais;
		$usr->email = $request->email;
		$usr->local_padrao = $request->local_padrao;
		$usr->balanca_padrao_id = $this->resolveBalancaPadraoId($request->balanca_padrao_id);
		$usr->rota_acesso = $request->rota_acesso ?? '';
		if($request->senha){
			$usr->senha = md5($request->senha);
		}

		$usr->adm = $request->adm ? true : false;
		$usr->somente_fiscal = $request->somente_fiscal ? true : false;
		$usr->caixa_livre = $request->caixa_livre ? true : false;
		$usr->permite_desconto = $request->permite_desconto ? true : false;
		if(isset($request->menu_representante)){
			$usr->menu_representante = $request->menu_representante ? true : false;
		}
		$usr->ativo = $request->ativo ? true : false;
		$usr->permissao = json_encode($permissao);

		// echo $usr->local_padrao;
		// die;
		$result = $usr->save();
		if($result){
			session()->flash("mensagem_sucesso", "Usuário atualizado!");
		}else{
			session()->flash('mensagem_erro', 'Erro ao atualizar usuário!');
		}

		return redirect('/usuarios');
	}

	public function delete($id){
		$usuario = Usuario::
		where('id', $id)
		->first();

		$usuarios = Usuario::
		where('empresa_id', $this->empresa_id)
		->get();

		if(sizeof($usuarios) == 1){
			session()->flash('mensagem_erro', 'Não é possivel remover o ultimo usuário!');
			return redirect()->back();
		}
		try{
			if(valida_objeto($usuario)){

				$usuario->config()->delete();
				if($usuario->delete()){
					session()->flash("mensagem_sucesso", "Usuário removido!");
				}else{
					session()->flash('mensagem_erro', 'Erro ao remover usuário!');
				}

				return redirect('/usuarios');
			}else{
				return redirect('/403');
			}
		}catch(\Exception $e){
			session()->flash('mensagem_erro', 'Algo deu errado ' . $e->getMessage());
		}
	}


	private function _validate(Request $request, $update = false){
		$rules = [
			'nome' => 'required',
			'email' => 'required|email',
			'login' => ['required', \Illuminate\Validation\Rule::unique('usuarios')->ignore($request->id)],
			'senha' => !$update ? 'required' : '',
		];

		$messages = [
			'nome.required' => 'O campo nome é obrigatório.',
			'email.required' => 'O campo email é obrigatório.',
			'email.email' => 'Email inválido',
			'login.required' => 'O campo login é obrigatório.',
			'senha.required' => 'O campo senha é obrigatório',
			'login.unique' => 'Usuário já cadastrado no sistema.'
		];

		$this->validate($request, $rules, $messages);
	}

	public function setTema(Request $request){
		$tema = $request->tema;
		$tema_menu = $request->tema_menu;
		$tipo_menu = $request->tipo_menu;

		$id = $value = session('user_logged')['id'];
		$usuario = Usuario::find($id);
		$usuario->tema = $tema;
		$usuario->tema_menu = $tema_menu;
		$usuario->tipo_menu = $tipo_menu;
		$usuario->save();
		session()->flash("mensagem_sucesso", "Tema salvo!");
		return redirect()->back();

	}

	public function historico($id){
		$usuario = Usuario::find($id);

		if(valida_objeto($usuario)){

			$acessos = UsuarioAcesso::
			where('usuario_id', $id)
			->paginate(50);

			return view('usuarios/historico')
			->with('usuario', $usuario)
			->with('acessos', $acessos)
			->with('title', 'Histórico de Usuário');
		}else{
			return redirect('/403');
		}
	}

    /**
     * Gera um novo otp_secret para o usuário e retorna o QR Code e a chave em JSON,
     * registrando em log a mudança do campo otp_secret.
     */
    public function generateOtp(Request $request, $id)
    {
        // 🔹 Instância da model e LogService já disponível via middleware
        $modelInstance = is_string(Usuario::class) ? app(Usuario::class) : Usuario::class;

        // 🔹 Busca o usuário e captura os dados antes da alteração
        $usuario = Usuario::findOrFail($id);
        $dadosAnteriores = $usuario->toArray();

        // 🔹 Gera a nova secret
        $otpService = app(\App\Services\OtpService::class);
        $secret = $otpService->generateSecret();

        // 🔹 Atribui e salva (mutator criptografa)
        $usuario->otp_secret = $secret;
        $usuario->save();

        // 🔹 Captura os dados após a alteração
        $dadosDepois = $usuario->toArray();

        // 🔹 Registra log da geração do OTP
        $this->logService->registrar('update', get_class($modelInstance), [
            'registro_id' => $usuario->id,
            'dados_antes'  => array_intersect_key($dadosAnteriores, array_flip(['otp_secret'])),
            'dados_depois' => array_intersect_key($dadosDepois,  array_flip(['otp_secret'])),
        ]);

        // 🔹 Gera o QR Code inline (SVG base64)
        $qrCode = $otpService->getQRCodeInline(
            config('app.name'),
            $usuario->email,
            $secret
        );

        // 🔹 Retorna JSON
        return response()->json([
            'secret' => $secret,
            'qrCode' => $qrCode,
        ], 200);
    }

    /**
     * Gera secret + QR Code (sem salvar) e registra no log a pré-visualização.
     */
    public function previewOtp(Request $request, $id)
    {
        // 🔹 Instância da model e LogService
        $modelInstance = is_string(Usuario::class) ? app(Usuario::class) : Usuario::class;

        // 🔹 Busca o usuário
        $usuario = Usuario::findOrFail($id);

        // 🔹 Gera a chave em claro
        $otpService = app(OtpService::class);
        $secret = $otpService->generateSecret();

        // 🔹 Registra log da pré-visualização do OTP (não altera DB)
        $this->logService->registrar('preview', get_class($modelInstance), [
            'registro_id' => $usuario->id,
            'dados_antes'  => null,
            'dados_depois' => ['preview_secret' => $secret],
        ]);

        // 🔹 Prepara QR Code (app name + nome do usuário)
        $qrCode = $otpService->getQRCodeInline(
            config('app.name'),
            $usuario->nome,
            $secret
        );

        return response()->json([
            'secret' => $secret,
            'qrCode' => $qrCode,
        ], 200);
    }


    /**
     * Recebe secret + código, verifica e então grava no banco,
     * registrando em log a alteração de otp_secret.
     */
    public function saveOtp(Request $request, $id)
    {
        $request->validate([
            'secret' => 'required|string',
            'code'   => 'required|digits:6',
        ]);

        // 🔹 Instância da model e LogService
        $modelInstance = is_string(Usuario::class) ? app(Usuario::class) : Usuario::class;

        // 🔹 Busca o usuário e captura dados antes
        $usuario = Usuario::findOrFail($id);
        $dadosAnteriores = $usuario->toArray();

        // 🔹 Verifica o código contra a chave
        $otpService = app(OtpService::class);
        if (! $otpService->verify($request->secret, $request->code)) {
            return response()->json([
                'error' => 'Código OTP inválido.'
            ], 422);
        }

        // 🔹 Salva a chave (mutator cifra)
        $usuario->otp_secret = $request->secret;
        $usuario->save();

        // 🔹 Captura dados depois
        $dadosDepois = $usuario->toArray();

        // 🔹 Registra log da configuração do OTP
        $this->logService->registrar('update', get_class($modelInstance), [
            'registro_id' => $usuario->id,
            'dados_antes'  => array_intersect_key($dadosAnteriores, array_flip(['otp_secret'])),
            'dados_depois' => array_intersect_key($dadosDepois,  array_flip(['otp_secret'])),
        ]);

        return response()->json([
            'message' => 'OTP configurado com sucesso.'
        ], 200);
    }

    /**
     * Verifica o código atual antes de permitir regenerar o OTP.
     */
    public function verifyCurrentOtp(Request $request, $id)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $usuario = Usuario::findOrFail($id);

        // nota: se você encriptou o secret no model, use o accessor para pegá-lo em claro:
        $secret = $usuario->otp_secret;
        $isValid = app(OtpService::class)->verify($secret, $request->code);

        return response()->json(['valid' => $isValid]);
    }


}
