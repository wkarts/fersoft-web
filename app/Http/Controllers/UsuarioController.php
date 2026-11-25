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

            $this->filial_id = $request->get('filial_id')
                ?? session('user_logged.local_padrao')
                ?? null;

            $this->logService = new LogService($this->empresa_id, $this->usuario_id, $this->filial_id);

            return $next($request);
        });
    }

    public function setLocation_(Request $request)
    {
        try {
            $modelInstance = is_string(Usuario::class) ? app(Usuario::class) : Usuario::class;

            $usuario = Usuario::findOrFail(get_id_user());
            $dadosAnteriores = $usuario->toArray();

            $filialId = $request->filial_id;
            if ($filialId === '-1' || $filialId === -1) {
                $filialId = null;
            }

            $usuario->local_padrao = $filialId;
            $usuario->save();

            $dadosDepois = $usuario->toArray();

            $user_logged = session('user_logged');
            $user_logged['local_padrao'] = $usuario->local_padrao;
            $user_logged['id'] = $usuario->id;
            $user_logged['empresa'] = $usuario->empresa_id;
            session(['user_logged' => $user_logged]);
            session(['empresa_id' => $usuario->empresa_id]);

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
            $modelInstance = is_string(Usuario::class) ? app(Usuario::class) : Usuario::class;

            $usuario = Usuario::findOrFail(get_id_user());
            $dadosAnteriores = $usuario->toArray();

            $usuario->local_padrao = $request->filial_id;
            $usuario->save();

            $dadosDepois = $usuario->toArray();

            $user_logged = session('user_logged');
            $user_logged['local_padrao'] = $usuario->local_padrao;
            session(['user_logged' => $user_logged]);

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
            $value = session('user_logged');
            $usuario = Usuario::findOrFail($value['id']);

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

            $dadosDepois = [
                'permissoesAtivas' => $permissoesAtivas,
                'menu' => $menu
            ];

            $modelInstance = is_string(Usuario::class) ? app(Usuario::class) : Usuario::class;

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

            $dadosAnteriores = [];

            $cpfMtr = preg_replace('/\D+/', '', (string) $request->input('cpf_mtr', ''));
            $tokenMtr = trim((string) $request->input('token_mtr', ''));

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
                'empresa_id' => $this->empresa_id,
                'permissao_nfe_whatsapp' => $request->permissao_nfe_whatsapp ? true : false,
                'cpf_mtr' => $cpfMtr !== '' ? $cpfMtr : null,
                'token_mtr' => $tokenMtr !== '' ? $tokenMtr : null,
            ]);

            $this->criaConfigCaixa($result);

            if ($result) {
                session()->flash("mensagem_sucesso", "Usuário salvo!");

                $modelInstance = is_string(Usuario::class) ? app(Usuario::class) : Usuario::class;

                $dadosDepois = $result->toArray();

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

        $cpfMtr = preg_replace('/\D+/', '', (string) $request->input('cpf_mtr', ''));
        $tokenMtr = trim((string) $request->input('token_mtr', ''));

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
            'empresa_id' => $this->empresa_id,
            'permissao_nfe_whatsapp' => $request->permissao_nfe_whatsapp ? true : false,
            'cpf_mtr' => $cpfMtr !== '' ? $cpfMtr : null,
            'token_mtr' => $tokenMtr !== '' ? $tokenMtr : null,
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

            $usr = Usuario::where('id', $request->id)->firstOrFail();
            $dadosAnteriores = $usr->toArray();

            $locais = $request->local ? json_encode($request->local) : null;

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

            $usr->permissao_nfe_whatsapp = $request->permissao_nfe_whatsapp ? true : false;

            // Credenciais individuais SINIR / MTR
            $cpfMtr = preg_replace('/\D+/', '', (string) $request->input('cpf_mtr', ''));
            $usr->cpf_mtr = $cpfMtr !== '' ? $cpfMtr : null;

            if ($request->boolean('remover_token_mtr')) {
                $usr->setRawAttributes(array_merge($usr->getAttributes(), ['token_mtr' => null]));
            } elseif ($request->filled('token_mtr')) {
                $usr->token_mtr = trim((string) $request->token_mtr);
            }

            $result = $usr->save();

            if ($result) {
                session()->flash("mensagem_sucesso", "Usuário atualizado!");

                $modelInstance = is_string(Usuario::class) ? app(Usuario::class) : Usuario::class;

                $dadosDepois = $usr->toArray();

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

        $usr->permissao_nfe_whatsapp = $request->permissao_nfe_whatsapp ? true : false;

        // Credenciais individuais SINIR / MTR
        $cpfMtr = preg_replace('/\D+/', '', (string) $request->input('cpf_mtr', ''));
        $usr->cpf_mtr = $cpfMtr !== '' ? $cpfMtr : null;

        if ($request->boolean('remover_token_mtr')) {
            $usr->setRawAttributes(array_merge($usr->getAttributes(), ['token_mtr' => null]));
        } elseif ($request->filled('token_mtr')) {
            $usr->token_mtr = trim((string) $request->token_mtr);
        }

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


    private function resolveBalancaPadraoId($balancaPadraoId)
    {
        $balancaPadraoId = (int) $balancaPadraoId;
        if ($balancaPadraoId <= 0) {
            return null;
        }

        $balancaValida = BalancaConfig::where('empresa_id', $this->empresa_id)
            ->where('ativo', true)
            ->where('id', $balancaPadraoId)
            ->exists();

        return $balancaValida ? $balancaPadraoId : null;
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

            $acessos = \Illuminate\Support\Facades\DB::table('logs')
                ->where('usuario_id', $id)
                ->orderBy('created_at', 'desc')
                ->paginate(50);

            return view('usuarios/historico')
                ->with('usuario', $usuario)
                ->with('acessos', $acessos)
                ->with('title', 'Histórico e Auditoria do Usuário');
        }else{
            return redirect('/403');
        }
    }

    public function generateOtp(Request $request, $id)
    {
        $modelInstance = is_string(Usuario::class) ? app(Usuario::class) : Usuario::class;

        $usuario = Usuario::findOrFail($id);
        $dadosAnteriores = $usuario->toArray();

        $otpService = app(\App\Services\OtpService::class);
        $secret = $otpService->generateSecret();

        $usuario->otp_secret = $secret;
        $usuario->save();

        $dadosDepois = $usuario->toArray();

        $this->logService->registrar('update', get_class($modelInstance), [
            'registro_id' => $usuario->id,
            'dados_antes'  => array_intersect_key($dadosAnteriores, array_flip(['otp_secret'])),
            'dados_depois' => array_intersect_key($dadosDepois,  array_flip(['otp_secret'])),
        ]);

        $qrCode = $otpService->getQRCodeInline(
            config('app.name'),
            $usuario->email,
            $secret
        );

        return response()->json([
            'secret' => $secret,
            'qrCode' => $qrCode,
        ], 200);
    }

    public function previewOtp(Request $request, $id)
    {
        $modelInstance = is_string(Usuario::class) ? app(Usuario::class) : Usuario::class;

        $usuario = Usuario::findOrFail($id);

        $otpService = app(OtpService::class);
        $secret = $otpService->generateSecret();

        $this->logService->registrar('preview', get_class($modelInstance), [
            'registro_id' => $usuario->id,
            'dados_antes'  => null,
            'dados_depois' => ['preview_secret' => $secret],
        ]);

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

    public function saveOtp(Request $request, $id)
    {
        $request->validate([
            'secret' => 'required|string',
            'code'   => 'required|digits:6',
        ]);

        $modelInstance = is_string(Usuario::class) ? app(Usuario::class) : Usuario::class;

        $usuario = Usuario::findOrFail($id);
        $dadosAnteriores = $usuario->toArray();

        $otpService = app(OtpService::class);
        if (! $otpService->verify($request->secret, $request->code)) {
            return response()->json([
                'error' => 'Código OTP inválido.'
            ], 422);
        }

        $usuario->otp_secret = $request->secret;
        $usuario->save();

        $dadosDepois = $usuario->toArray();

        $this->logService->registrar('update', get_class($modelInstance), [
            'registro_id' => $usuario->id,
            'dados_antes'  => array_intersect_key($dadosAnteriores, array_flip(['otp_secret'])),
            'dados_depois' => array_intersect_key($dadosDepois,  array_flip(['otp_secret'])),
        ]);

        return response()->json([
            'message' => 'OTP configurado com sucesso.'
        ], 200);
    }

    public function verifyCurrentOtp(Request $request, $id)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $usuario = Usuario::findOrFail($id);

        $secret = $usuario->otp_secret;
        $isValid = app(OtpService::class)->verify($secret, $request->code);

        return response()->json(['valid' => $isValid]);
    }
}
