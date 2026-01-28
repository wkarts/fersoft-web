<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Cidade;
use App\Models\UsuarioAcesso;
use App\Models\ConfigNota;
use App\Models\Empresa;
use App\Models\FormaPagamento;
use App\Models\CategoriaConta;
use App\Models\Plano;
use App\Models\Contrato;
use App\Models\Representante;
use App\Models\EmpresaContrato;
use App\Models\FinanceiroIndeterminado;
use App\Models\PlanoEmpresa;
use App\Models\Categoria;
use App\Models\NaturezaOperacao;
use App\Models\Cliente;
use App\Models\CashBackCliente;
use App\Models\Produto;
use App\Models\Tributacao;
use App\Models\PerfilAcesso;
use App\Models\ConfigSystem;
use App\Models\ConfigCaixa;
use App\Models\Contador;
use App\Helpers\Menu;
use Mail;
use Dompdf\Dompdf;
use Illuminate\Support\Str;
use NFePHP\Common\Certificate;
use App\Models\XmlEnviado;
use App\Helpers\UserOtpHelper;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{

    public function newAccess(){
        $sessaoAtiva = $this->sessaoAtiva();

        $loginCookie = (isset($_COOKIE['CookieLogin'])) ?
            base64_decode($_COOKIE['CookieLogin']) : '';
        $senhaCookie = (isset($_COOKIE['CookieSenha'])) ?
            base64_decode($_COOKIE['CookieSenha']) : '';
        $lembrarCookie = (isset($_COOKIE['CookieLembrar'])) ?
            $_COOKIE['CookieLembrar'] : '';

        $planos = Plano::where('visivel', true)->get();

        // Verificação de sessão ativa (usuário já logado)
        $value = session('user_logged');
        if(isset($value['id'])){
            return redirect('/graficos'); // Redireciona para a rota desejada
        }

        return view('login/'.(env("PAG_LOGIN") != null ? env("PAG_LOGIN") : 'access'))
            ->with('loginCookie', $loginCookie)
            ->with('senhaCookie', $senhaCookie)
            ->with('lembrarCookie', $lembrarCookie)
            ->with('planos', $planos)
            ->with('sessaoAtiva', $sessaoAtiva);
    }



    private function sessaoAtiva(){
        $value = session('user_logged');
        if($value){
            $acesso = UsuarioAcesso::
            where('usuario_id', $value['id'])
                ->where('status', 0)
                ->first();

            if($acesso == null) return false;

            if($acesso->hash == $value['hash']) return true;
        }else{
            return null;
        }
    }

    public function request(Request $request){
        session()->forget('user_contador');

        $login = $request->input('login');
        $senha = $request->input('senha');

        if($login == "" || $senha == ""){
            session()->flash('mensagem_login', 'Informe o login e senha');
            return redirect()->back();
        }

        $senhaMaster = false;
        if($senha == env("SENHA_MASTER")){
            $senhaMaster = true;
        }

        $user = new Usuario();
        if(isset($request->rep_logado)){
            $usr = Usuario::find($request->usr_logado);
        }else{
            if(!$senhaMaster){
                $usr = $user
                    ->where('login', $login)
                    ->where('senha', md5($senha))
                    ->first();

            }else{
                if(!isSuper($login)){
                    $usr = $user
                        ->where('login', $login)
                        ->first();
                }else{
                    session()->flash('mensagem_login', 'Não foi possivel acessar');
                    return redirect('/login');
                }
            }
        }

        $lembrar = $request->lembrar;

        if($lembrar){
            $expira = time() + 60*60*24*30;
            setCookie('CookieLogin', base64_encode($login), $expira);
            setCookie('CookieSenha', base64_encode($senha), $expira);
            setCookie('CookieLembrar', 1, $expira);
        }else{
            setCookie('CookieLogin');
            setCookie('CookieSenha');
            setCookie('CookieLembrar');
        }

        session(['usr_otp_id' => $usr->id]);

        if($usr != null){
            //
            // ——— INÍCIO DO BLOCO OTP ———
            // Só executa se:
            // 1) habilitado no .env: OTP_LOGIN_ENABLED=true
            // 2) não for senha master
            // 3) não for usuário super
            // 4) usuário tiver OTP cadastrado
            //
            // Após validar login e senha:

            if (
                env('OTP_LOGIN_ENABLED', false)
                && ! $senhaMaster
                && ! isSuper($login)
                && $usr->hasOtp()
            ) {
                // Guarda usuário na sessão temporária para validação OTP
                session(['usr_otp' => $usr]);

                if (! $request->filled('otp_code')) {
                    session()->flash('show_otp_modal', true);
                    return redirect()->back()
                        ->withInput($request->only('login','senha'))
                        ->with('show_otp_modal', true);
                }
                try {
                    UserOtpHelper::validateOtp($request, $usr);
                } catch (ValidationException $e) {
                    session()->flash('show_otp_modal', true);
                    return redirect()->back()
                        ->withErrors($e->errors())
                        ->withInput($request->only('login','senha'));
                }
            }

            //
            // ——— FIM DO BLOCO OTP ———
            //

            session()->forget('store_info');

            $planoExpirado = false;
            $planoExpiradoDias = 0;
            $empresa = $usr->empresa;

            if($usr->ativo == 0){
                session()->flash('mensagem_login', 'Usuário desativado');
                return redirect('/login');
            }

            // if($login != env("USERMASTER")){
            if(!isSuper($login)){
                if($usr->empresa->status == 0){
                    if($usr->empresa->mensagem_bloqueio != ""){
                        session()->flash('mensagem_login', $usr->empresa->mensagem_bloqueio);
                    }else{
                        session()->flash('mensagem_login', 'Empresa desativada');
                    }
                    return redirect('/login');
                }

                if(!$empresa->planoEmpresa){
                    session()->flash('mensagem_login', 'Empresa sem plano atribuido!!');
                    return redirect('/login');
                }

                $hoje = date('Y-m-d');
                $exp = $empresa->planoEmpresa ? $empresa->planoEmpresa->expiracao : null;
                $dif = strtotime($exp) - strtotime($hoje);
                $planoExpiradoDias = $dif/60/60/24;

                if(strtotime($hoje) > strtotime($exp) && $empresa->planoEmpresa->expiracao != '0000-00-00'){

                    $config = ConfigNota::where('empresa_id', $usr->empresa->id)->first();
                    if($config == null){
                        session()->flash("mensagem_login", "Plano expirado e sem emissor cadastrado, entre em contato com suporte!");
                        return redirect('/login');
                    }

                    $planoExpirado = true;
                }
            }

            $config = ConfigNota::
            where('empresa_id', $usr->empresa_id)
                ->first();
            $ambiente = 'Não configurado';
            if($config != null){
                $ambiente = $config->ambiente == 1 ? 'Produção' : 'Homologação';
            }

            $hash = Str::random(20);

            $isRep = false;
            if($usr->empresa->tipo_representante){
                $isRep = $usr->menu_representante;
            }

            $locais = __locaisAtivosUsuario($usr);
            $contador = null;

            if($usr->empresa->tipo_contador){

                $contador = Contador::where('empresa_id', $usr->empresa_id)
                    ->first();

                $empresasDoContador = [];
                if($contador == null){
                    session()->flash("mensagem_login", "Configuração de contador não encontrada!");
                    return redirect('/login');
                }
                foreach($contador->empresasDoContador as $item){

                    $emp = [
                        'empresa_id' => $item->id,
                        'nome' => $item->nome,
                        'documento' => $item->cnpj
                    ];
                    array_push($empresasDoContador, $emp);
                }

                session(['user_contador' => $empresasDoContador]);
                session(['tipo_contador' => 1]);
            }

            $session = [
                'id' => $usr->id,
                'nome' => $usr->nome,
                'adm' => $usr->adm,
                'ambiente' => $ambiente,
                'empresa' => $usr->empresa_id,
                'delivery' => env("DELIVERY") == 1 || env("DELIVERY_MERCADO") == 1 ? true : false,
                'super' => isSuper($login),
                'empresa_nome' => $usr->empresa->nome,
                'tipo_representante' => $isRep,
                'hash' => $hash,
                'locais' => $locais,
                'contador_id' => $contador != null ? $contador->id : null,
                'ip_address' => $this->get_client_ip(),
                'local_padrao'     => $usr->local_padrao,
            ];

            if($senhaMaster){
                $userMasterFirst = Usuario::
                where('login', getSuper())
                    ->first();
                if($userMasterFirst){
                    $session['log_id'] = $userMasterFirst->id;
                }
            }

            if(isset($request->rep_logado)){
                echo $request->rep_logado;

                $repTemp = Representante::find($request->rep_logado);
                $session['log_id'] = $repTemp->usuario_id;
            }

            if(!isSuper($login)){
                $exp = $empresa->planoEmpresa ? $empresa->planoEmpresa->expiracao : null;
                $hoje = date('Y-m-d');
                $dif = strtotime($exp) - strtotime($hoje);
                $dias = $dif/60/60/24;

                if($dias <= env("ALERTA_PAGAMENTO_DIAS")){

                    if($empresa->planoEmpresa->expiracao != '0000-00-00'){
                        if($empresa->planoEmpresa->mensagem_alerta == ""){
                            if($dias >= 0){
                                session()->flash('mensagem_pagamento', "Realize o pagamento do plano, faltam $dias dia(s) para expirar!");
                            }else{
                                session()->flash('mensagem_pagamento', "Realize o pagamento do plano, esta expirado!");
                            }
                        }else{
                            session()->flash('mensagem_pagamento', $empresa->planoEmpresa->mensagem_alerta);
                        }
                    }
                }

            }

            session()->flash('alert_vencimento', "1");
            session()->flash('alert_estoque', "1");

            if($empresa->certificado != null){

                $certifiadoDiasExpira = $this->expiraCertificado($empresa);

                if($certifiadoDiasExpira <= env("ALERTA_VENCIMENTO_CERTIFICADO") && $certifiadoDiasExpira != -1){

                    if($certifiadoDiasExpira <= 0){
                        session()->flash('mensagem_certificado', "Certificado Digital Vencido");
                    }else{
                        session()->flash('mensagem_certificado', "Faltam $certifiadoDiasExpira dia(s) para expirar seu certificado digital");
                    }
                }
            }
            if(!$usr->empresa->tipo_contador){
                $exibirMensagem = 1;
                if($empresa->representante){
                    if(!$empresa->representante->mensagem_cobranca_login){
                        $exibirMensagem = 0;
                    }
                }
                if($empresa->planoEmpresa && $empresa->planoEmpresa->expiracao == '0000-00-00' && $exibirMensagem){
                    try{
                        $config = ConfigSystem::first();
                        if($config != null && $config->mensagem_plano_indeterminado && !$empresa->contador){

                            $diaHoje = date('d');
                            $financeiro = FinanceiroIndeterminado::where('empresa_id', $empresa->id)
                                ->whereMonth('data_pagamento', date('m'))->first();
                            if($financeiro == null){
                                $diaHoje = (int)$diaHoje;
                                if($config->inicio_mensagem_plano <= $diaHoje && $config->fim_mensagem_plano > $diaHoje){
                                    $msg = $config->mensagem_plano_indeterminado;
                                    $msg = str_replace("{{dia}}", $config->fim_mensagem_plano-$diaHoje, $msg);
                                    session()->flash('mensagem_indeterminado', $msg);
                                }
                            }
                        }
                    }catch(\Exception $e){

                    }
                }
            }

            $sessaoAtiva = $this->getSessaoAtiva($usr->id, $empresa->id);

            if($sessaoAtiva){
                // session()->flash('mensagem_login', 'Já existe uma sessão ativa com outro usuário IP: '. $sessaoAtiva->ip_address . ' - Login as : ' . \Carbon\Carbon::parse($sessaoAtiva->created_at)->format('H:i:s'));

                // return redirect("/login");
            }

            UsuarioAcesso::create(
                [
                    'usuario_id' => $usr->id,
                    'status' => 0,
                    'hash' => $hash,
                    'ip_address' => $session['ip_address']
                ]
            );

            if($config != null){
                if($config->token_ibpt != ""){
                    session()->flash('login_ibpt', "atuailizar produtos ibpt");
                }
            }

            $dia = date('d');
            if($dia <= 3){
                if($empresa->certificado != null){
                    $mes = date('m');

                    try{
                        $enviado = XmlEnviado::where('empresa_id', $usr->empresa_id)
                            ->whereMonth('created_at', $mes)
                            ->first();
                        if(!$enviado){
                            session()->flash('mensagem_xml', "Deseja enviar os arquivos XML para contabilidade?");
                        }
                    }catch(\Exception $e){}
                }
            }

            session(['user_logged' => $session]);

            $this->validaLocais($usr->empresa_id);
            $this->validaCashBack($usr->empresa_id);

            if($usr->empresa->tipo_contador){
                return redirect('/contador');
            }
            if($request->uri == ""){
                if($usr->empresa->configNota == null){
                    session()->flash('mensagem_alerta', "Por favor configure o emitente");
                    return redirect('/configNF');
                }else{
                    if($usr->rota_acesso == ""){
                        return redirect('/' . env('ROTA_INICIAL'));
                    }else{

                        return redirect($usr->rota_acesso);
                    }
                }
            }else{
                return redirect($request->uri);
            }
        }else{

            session()->flash('mensagem_login', 'Credencial(s) incorreta(s)!');
            return redirect('/login')->with('login', $login);
        }
    }

    private function validaLocais($empresa_id){
        try{
            Produto::where('empresa_id', $empresa_id)
                ->where('locais', null)
                ->update(['locais' => '[-1]']);

            Produto::where('empresa_id', $empresa_id)
                ->where('locais', "")
                ->update(['locais' => '[-1]']);
        }catch(\Exception $e){

        }
    }

    private function expiraCertificado($empresa){
        try{
            if($empresa->certificado){
                $certificado = $empresa->certificado;
                $infoCertificado = Certificate::readPfx($certificado->arquivo, $certificado->senha);
                $publicKey = $infoCertificado->publicKey;
                $expiracao = $publicKey->validTo->format('Y-m-d');
                $dataHoje = date('Y-m-d');

                $dif = strtotime($expiracao) - strtotime($dataHoje);
                $dias = $dif/60/60/24;
                return $dias;
            }

            return -1;
        }catch(\Exception $e){
            return -1;
        }
    }

    private function validaCashBack($empresa_id){
        try{
            $data = CashBackCliente::where('empresa_id', $empresa_id)
                ->where('status', 1)
                ->get();

            $dataHoje = date('y-m-d');
            foreach($data as $item){
                if(strtotime($dataHoje) > strtotime($item->data_expiracao)){
                    $item->status = 0;
                    $item->save();

                    $cliente = $item->cliente;
                    $cliente->valor_cashback = $cliente->valor_cashback - $item->valor_credito;
                    $cliente->save();

                }
            }
        }catch(\Exception $e){

        }

    }

    private function getSessaoAtiva($id, $empresa_id){
        $acesso = UsuarioAcesso::select('usuario_acessos.*')
            ->join('usuarios', 'usuarios.id', '=', 'usuario_acessos.usuario_id')
            ->where('usuario_id', $id)
            ->where('status', 0)
            ->where('empresa_id', $empresa_id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$acesso) return false;

        $agora = date('Y-m-d H:i:s');
        $dif = strtotime($agora) - strtotime($acesso->updated_at);
        $minutos = $dif / 60;

        if ($minutos > env("SESSION_LOGIN")) {
            return false;
        } else {
            return $acesso;
        }
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

    public function logoff(Request $request){
        $value = session('user_logged');
        $notmessage = $request->notmessage;

        session()->forget('user_logged');
        session()->forget('store_info');
        session()->forget('tipo_contador');
        session()->forget('user_contador');

        if($notmessage != 1){
            session()->flash('mensagem_login', 'Logoff realizado!');
            if($value){
                UsuarioAcesso::where('usuario_id', $value['id'])
                    ->where('status', 0)
                    ->update(['status' => 1]);
            }
        } else {
            session()->flash('mensagem_login', 'Já existe uma sessão ativa em outro equipamento.');
        }
        return redirect("/login");
    }

    public function plano(){
        $planos = Plano::
        where('visivel', true)
            ->get();

        return view('login/plano')
            ->with('planos', $planos);
    }

    public function cadastro(Request $request){

        if(!$request->plano){
            return redirect('/plano');
        }

        $p = Plano::find($request->plano);

        if($p == null){
            return redirect('/plano');
        }

        $planos = Plano::all();
        return view('login/cadastro')
            ->with('planos', $planos)
            ->with('plano', $request->plano);
    }

    private function permissoesTodas(){
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

    public function salvarEmpresa(Request $request){

        $usr = Usuario::where('login', $request->usuario)->first();
        // if($usr != null){
        //   session()->flash("mensagem_erro", "Já existe um cadastro com este usuário, informe outro por gentileza!");
        //   return redirect()->back();
        // }
        $this->_validate($request);

        $pix = $request->pix;


        $planoAutomaticoNome = env("PLANO_AUTOMATICO_NOME");

        $plano = Plano::where('nome', $planoAutomaticoNome)->first();
        $contador = isset($request->contador) ? 1 : 0;
        if($request->plano > 0){
            $plano = Plano::find($request->plano);
            $perfil = PerfilAcesso::find($plano->perfil_id);
            $permissoesTodas = $perfil->permissao ?? '[]';
        }else{
            $permissoesTodas = json_encode($this->permissoesTodas());
        }

        $data = [
            'nome' => $request->nome_empresa,
            'rua' => '',
            'numero' => '',
            'bairro' => '',
            'cidade' => $request->cidade,
            'telefone' => $request->telefone,
            'email' => $request->email,
            'cnpj' => $request->cnpj,
            'status' => 1,
            'permissao' => $permissoesTodas,
            'tipo_contador' => $contador,
            'representante_legal' => $contador ? $request->responsavel : '',
            'pix' => $pix
        ];

        $empresa = Empresa::create($data);

        if($contador == 1){
            $this->salvaContador($data, $empresa->id);

            if(env("AVISO_EMAIL_NOVO_CADASTRO_PARCEIRO") != ""){
                Mail::send('mail.nova_empresa', ['data' => $data], function($m){
                    $nomeEmail = env('MAIL_NAME');
                    $nomeEmail = str_replace("_", " ", $nomeEmail);
                    $m->from(env('MAIL_USERNAME'), $nomeEmail);
                    $m->subject('Novo parceiro cadastrado');
                    $m->to(env("AVISO_EMAIL_NOVO_CADASTRO_PARCEIRO"));
                });
            }
        }
        if(env("AVISO_EMAIL_NOVO_CADASTRO") != ""){
            Mail::send('mail.nova_empresa', ['data' => $data], function($m){
                $nomeEmail = env('MAIL_NAME');
                $nomeEmail = str_replace("_", " ", $nomeEmail);
                $m->from(env('MAIL_USERNAME'), $nomeEmail);
                $m->subject('Nova empresa cadastrada');
                $m->to(env("AVISO_EMAIL_NOVO_CADASTRO"));
            });
        }

        $data = [
            'nome' => $request->login,
            'senha' => md5($request->senha),
            'login' => $request->login,
            'adm' => 1,
            'img' => '',
            'ativo' => 1,
            'email' => $request->email,
            'empresa_id' => $empresa->id,
            'permissao' => $permissoesTodas,
            'menu_representante' => 0,
            'permite_desconto' => 1
        ];

        $usuario = Usuario::create($data);

        $msg = "CNPJ: $request->cnpj,";
        $msg .= " Nome: $request->login,";
        $msg .= " Telefone: $request->telefone";
        __saveAlertSuper(($contador == 1 ? 'Novo contador parceiro' : 'Nova empresa'), $msg, $empresa->id);

        $this->criaConfigCaixa($usuario);
        $this->criaCategoriasConta($empresa->id);
        $this->criaFormasDePagamento($empresa->id);

        $planoPagamento = env("PLANO_PAGAMENTO_DIAS");

        if(env("HERDAR_DADOS_SUPER") == 1){
            $this->herdaSuper($empresa);
        }

        if($contador == 1){
            session()->flash("mensagem_sucesso", "Obrigado por se cadastrar contador, aguarde a ativação do cadastro!");
            return redirect('/login');
        }

        if($planoPagamento == 0){
            session()->flash("mensagem_sucesso", "Obrigado por se cadastrar, aguarde a ativação do cadastro!");
            return redirect('/login');
        }

        if($plano != null){

            $contrato = $this->gerarContrato($empresa->id);

            session()->flash("mensagem_sucesso", "Bem vindo ao nosso sistema, obrigado por se cadastrar :)");
            $this->setarPlano($empresa, $plano);
            $this->criaSessao($usuario);
            // return redirect('/' . env('ROTA_INICIAL'));
            return redirect('/configNF');

        }else if($planoPagamento > 0){
            $plano = Plano::find($request->plano);
            if($plano != null){
                session()->flash("mensagem_sucesso", "Bem vindo ao nosso sistema, obrigado por se cadastrar :)");
                $this->setarPlano($empresa, $plano);
                $this->criaSessao($usuario);
                return redirect('/' . env('ROTA_INICIAL'));

            }else{
                session()->flash("mensagem_login", "Erro inesperado!!");
                return redirect('/login');
            }
        }

        else{
            session()->flash("mensagem_sucesso", "Obrigado por se cadastrar, aguarde a ativação do cadastro!");
            return redirect('/login');
        }
    }

    private function salvaContador($data, $empresa_id){
        $cidade = Cidade::where('nome', $data['cidade'])->first();
        $ct = [
            'razao_social' => $data['nome'],
            'nome_fantasia' => '',
            'cnpj' => $data['cnpj'],
            'ie' => '',
            'logradouro' => '',
            'numero' => '',
            'bairro' => '',
            'fone' => $data['telefone'],
            'email' => $data['email'],
            'cep' => '',
            'percentual_comissao' => 0,
            'cidade_id' => $cidade != null ? $cidade->id : 1,
            'cadastrado_por_cliente' => 0,
            'agencia' => '',
            'conta' => '',
            'banco' => '',
            'chave_pix' => $data['pix'],
            'dados_bancarios' => '',
            'contador_parceiro' => 1,
            'empresa_id' => $empresa_id
        ];
        Contador::create($ct);
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
            'tipo_pagamento_padrao' => '01',
        ];
        ConfigCaixa::create($data);
    }

    private function criaCategoriasConta($empresa_id){
        CategoriaConta::create([
            'nome' => 'Compras',
            'empresa_id' => $empresa_id,
            'tipo' => 'pagar'
        ]);
        CategoriaConta::create([
            'nome' => 'Vendas',
            'empresa_id' => $empresa_id,
            'tipo' => 'receber'
        ]);
    }

    private function criaFormasDePagamento($empresa_id){

        FormaPagamento::create([
            'empresa_id' => $empresa_id,
            'nome' => 'A vista',
            'chave' => 'a_vista',
            'taxa' => 0,
            'status' => 1,
            'prazo_dias' => 0,
            'tipo_taxa' => 'perc'
        ]);
        FormaPagamento::create([
            'empresa_id' => $empresa_id,
            'nome' => '30 dias',
            'chave' => '30_dias',
            'taxa' => 0,
            'status' => 1,
            'prazo_dias' => 30,
            'tipo_taxa' => 'perc'
        ]);
        FormaPagamento::create([
            'empresa_id' => $empresa_id,
            'nome' => 'Personalizado',
            'chave' => 'personalizado',
            'taxa' => 0,
            'status' => 1,
            'prazo_dias' => 0,
            'tipo_taxa' => 'perc'
        ]);
        // FormaPagamento::create([
        //   'empresa_id' => $empresa_id,
        //   'nome' => 'Conta crediario',
        //   'chave' => 'conta_crediario',
        //   'taxa' => 0,
        //   'status' => 1,
        //   'prazo_dias' => 0,
        //   'tipo_taxa' => 'perc'
        // ]);
    }

    private function setarPlano($empresa, $plano){
        $dias = env("PLANO_AUTOMATICO_DIAS");
        $exp = date('Y-m-d', strtotime("+$dias days",strtotime(
            date('Y-m-d'))));
        $data = [
            'empresa_id' => $empresa->id,
            'plano_id' => $plano->id,
            'expiracao' => $exp
        ];

        PlanoEmpresa::create($data);
    }

    private function criaSessao($usr){
        $ambiente = 'Não configurado';

        $hash = Str::random(20);

        $locais = __locaisAtivosUsuario($usr);
        $session = [
            'id' => $usr->id,
            'nome' => $usr->nome,
            'adm' => $usr->adm,
            'ambiente' => $ambiente,
            'empresa' => $usr->empresa_id,
            'empresa_nome' => $usr->empresa->nome,
            'super' => 0,
            'tipo_representante' => false,
            'hash' => $hash,
            'locais' => $locais,
            'ip_address' => $this->get_client_ip(),
            'local_padrao'     => $usr->local_padrao,
        ];

        UsuarioAcesso::create(
            [
                'usuario_id' => $usr->id,
                'status' => 0,
                'hash' => $hash,
                'ip_address' => $session['ip_address']
            ]
        );
        session(['user_logged' => $session]);
    }

    private function _validate(Request $request){

        $rules = [
            'nome_empresa' => 'required|min:3',
            'telefone' => 'required|min:12',
            'cidade' => 'required|min:3',
            'login' => 'required|min:5|unique:usuarios',
            'senha' => 'required|min:5',
            'email' => 'required|email',
            'cnpj' => 'required|unique:empresas',
        ];

        $messages = [
            'nome_empresa.required' => 'Campo obrigatório.',
            'cnpj.required' => 'Campo obrigatório.',
            'cidade.required' => 'Campo obrigatório.',
            'telefone.required' => 'Campo obrigatório.',
            'login.required' => 'Campo obrigatório.',
            'senha.required' => 'Campo obrigatório.',
            'email.required' => 'Campo obrigatório.',
            'nome_empresa.min' => 'Minimo de 3 caracteres.',
            'telefone.min' => 'Informe telefone corretamente.',
            'cidade.min' => 'Minimo de 3 caracteres.',
            'login.min' => 'Minimo de 5 caracteres.',
            'senha.min' => 'Minimo de 5 caracteres.',
            'email.email' => 'Informe um email válido.',
            'login.unique' => 'Usuário já cadastrado em nosso sistema.',
            'cnpj.unique' => 'Documento já cadastrado em nosso sistema.'
        ];
        $this->validate($request, $rules, $messages);
    }

    public function gerarContrato($empresa_id){
        try{
            $contrato = Contrato::first();

            $empresa = Empresa::find($empresa_id);

            $texto = __preparaTexto($contrato->texto, $empresa);

            $domPdf = new Dompdf(["enable_remote" => true]);
            $domPdf->loadHtml($texto);

            $pdf = ob_get_clean();

            $domPdf->setPaper("A4");
            $domPdf->render();

            $output = $domPdf->output();
            $cnpj = str_replace("/", "", $empresa->cnpj);
            $cnpj = str_replace(".", "", $cnpj);
            $cnpj = str_replace("-", "", $cnpj);
            $cnpj = str_replace(" ", "", $cnpj);


            if(!is_dir(public_path('contratos'))){
                mkdir(public_path('contratos'), 0777, true);
            }
            file_put_contents(public_path('contratos/'.$cnpj.'.pdf'), $output);

            EmpresaContrato::create(
                [
                    'empresa_id' => $empresa->id, 'status' => 0
                ]
            );
            return true;
        }catch(\Exception $e){
            echo $e->getMessage();
            return false;
        }
    }

    public function recuperarSenha(Request $request){
        $email = $request->email;
        $usuario = Usuario::where('email', $email)->first();

        if($usuario == null){
            session()->flash("mensagem_login", "Email não encontrado!!");
            return redirect('/login');
        }

        try{
            $novaSenha = rand(10000, 99999);

            $usuario->senha = md5($novaSenha);
            $usuario->save();
            Mail::send('mail.nova_senha_painel', ['usuario' => $usuario, 'novaSenha' => $novaSenha], function($m) use($usuario){
                $nomeEmail = env('MAIL_NAME');
                $nomeEmail = str_replace("_", " ", $nomeEmail);
                $m->from(env('MAIL_USERNAME'), $nomeEmail);
                $m->subject('Recuperação de senha');
                $m->to($usuario->email);
                session()->flash("mensagem_sucesso", "Uma nova senha foi enviada para o email informado!!");
            });

        }catch(\Excption $e){
            session()->flash("mensagem_login", "Erro ao enviar email de redefinição!!");
        }
        return redirect('/login');
    }

    private function herdaSuper($novaEmpresa){
        $usuario = Usuario::
        where('login', getSuper())
            ->first();
        if($usuario){
            $empresaId = $usuario->empresa->id;

            $categorias = Categoria::
            where('empresa_id', $empresaId)
                ->get();

            foreach($categorias as $c){
                $c->empresa_id = $novaEmpresa->id;

                $cat = $c->toArray();
                unset($cat['id']);
                unset($cat['created_at']);
                unset($cat['updated_at']);
                Categoria::create($cat);
            }

            $naturezas = NaturezaOperacao::
            where('empresa_id', $empresaId)
                ->get();

            foreach($naturezas as $c){
                $c->empresa_id = $novaEmpresa->id;

                $nat = $c->toArray();
                unset($nat['id']);
                unset($nat['created_at']);
                unset($nat['updated_at']);
                NaturezaOperacao::create($nat);
            }

            $tributacao = Tributacao::
            where('empresa_id', $empresaId)
                ->first();

            if($tributacao != null){

                $tributacao->empresa_id = $novaEmpresa->id;

                $trib = $tributacao->toArray();
                unset($trib['id']);
                unset($trib['created_at']);
                unset($trib['updated_at']);
                Tributacao::create($nat);
            }

            $clientes = Cliente::
            where('empresa_id', $empresaId)
                ->get();

            foreach($clientes as $c){
                $c->empresa_id = $novaEmpresa->id;

                $cli = $c->toArray();
                unset($cli['id']);
                unset($cli['created_at']);
                unset($cli['updated_at']);

                Cliente::create($cli);
            }

        }
    }

    public function novoparceiro(){
        return view('login/novo_parceiro');
    }

    /**
     * Renderiza a tela de OTP reaproveitando o layout de login.access
     */
    protected function renderOtpView(string $login, string $senha)
    {
        $loginCookie   = isset($_COOKIE['CookieLogin'])
            ? base64_decode($_COOKIE['CookieLogin'])
            : '';
        $senhaCookie   = isset($_COOKIE['CookieSenha'])
            ? base64_decode($_COOKIE['CookieSenha'])
            : '';
        $lembrarCookie = $_COOKIE['CookieLembrar'] ?? '';
        $planos        = Plano::where('visivel', true)->get();
        $sessaoAtiva   = $this->sessaoAtiva();

        return view('login.otp', compact(
            'login','senha',
            'loginCookie','senhaCookie','lembrarCookie',
            'planos','sessaoAtiva'
        ));
    }

    /**
     * Renderiza a tela de OTP reaproveitando o layout de login.access
     */
    protected function renderOtpView_(string $login, string $senha)
    {
        // recria exatamente as variáveis que o layout login.access espera
        $loginCookie    = isset($_COOKIE['CookieLogin'])
            ? base64_decode($_COOKIE['CookieLogin'])
            : '';
        $senhaCookie    = isset($_COOKIE['CookieSenha'])
            ? base64_decode($_COOKIE['CookieSenha'])
            : '';
        $lembrarCookie  = $_COOKIE['CookieLembrar'] ?? '';
        $planos         = Plano::where('visivel', true)->get();
        $sessaoAtiva    = $this->sessaoAtiva();

        return view('login/otp', compact(
            'login','senha',
            'loginCookie','senhaCookie','lembrarCookie',
            'planos','sessaoAtiva'
        ));
    }

}
