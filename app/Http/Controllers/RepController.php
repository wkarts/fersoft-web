<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Representante;
use App\Models\RepresentanteEmpresa;
use App\Models\PerfilAcesso;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Models\ConfigCaixa;
use App\Models\CategoriaConta;
use App\Models\Contrato;
use App\Models\EmpresaContrato;
use App\Models\Plano;
use App\Models\Venda;
use App\Models\FormaPagamento;
use App\Models\VendaCaixa;
use App\Models\Cte;
use App\Models\Contador;
use App\Models\Mdfe;
use App\Models\Compra;
use App\Models\Cidade;
use App\Models\Devolucao;
use App\Models\PlanoEmpresa;
use App\Models\NaturezaOperacao;
use App\Models\PlanoEmpresaRepresentante;
use App\Models\ConfigNota;
use App\Models\Certificado;
use App\Models\FinanceiroRepresentante;
use App\Helpers\Menu;
use Dompdf\Dompdf;
use NFePHP\Common\Certificate;

class RepController extends Controller
{
	protected $usuario_id = null;

	public function __construct(){
		$this->middleware(function ($request, $next) {

			$value = session('user_logged');
			if(!$value){
				return redirect("/login");
			}

			if(!$value['super'] && !$value['tipo_representante']){
				return redirect('/graficos');
			}

			$this->usuario_id = $value['id'];
			return $next($request);
		});
	}

	public function index(){
		
		$representante = Representante::
		where('usuario_id', $this->usuario_id)
		->first();

		if($representante == null){
			session()->flash("mensagem_erro", "Erro ao encontrar empresas!");
			return redirect()->back();
		}

		$planos = Plano::where('visivel_representante', 1)->get();

		return view('rep/empresas')
		->with('representante', $representante)
		->with('planos', $planos)
		->with('empresas', $representante->empresas)
		->with('title', 'Empresas');
	}

	public function filtro(Request $request){
		
		$representante = Representante::
		where('usuario_id', $this->usuario_id)
		->first();

		$empresas = RepresentanteEmpresa::
		select('representante_empresas.*')
		->join('empresas', 'empresas.id', '=', 
			'representante_empresas.empresa_id')
		->where('representante_empresas.representante_id', $representante->id)
		->where('empresas.nome', 'LIKE', "%$request->nome%");

		if($request->cpf_cnpj){
			$empresas->where('empresas.cnpj', $request->cpf_cnpj);
		}

		$empresas = $empresas->get();

		foreach($empresas as $e){
			if($e->empresa->planoEmpresa){
				$expiracao = $e->empresa->planoEmpresa->expiracao;

				$strValidade = strtotime($expiracao);
				$strHoje = strtotime(date('Y-m-d'));
				$dif = $strValidade - $strHoje;
				$dif = $dif/24/60/60;

				$e->tempo_expira = $dif;
			}
		}

		if($request->status != 'TODOS'){
			$temp = [];
			foreach($empresas as $e){
				if($e->empresa->status() == $request->status){
					array_push($temp, $e);
				}
				
				if($request->status == 2){
					if(!$e->empresa->planoEmpresa){
						array_push($temp, $e);	
					}
				}

			}
			$empresas = $temp;
		}

		if($request->dias_expirar){

			$temp = [];
			$hoje = date('Y-m-d');

			foreach($empresas as $e){
				if($e->empresa->planoEmpresa){
					$exp = $e->empresa->planoEmpresa->expiracao;
					$dif = strtotime($exp) - strtotime($hoje);
					$planoExpiradoDias = $dif/60/60/24;

					if($request->dias_expirar == $planoExpiradoDias){
						array_push($temp, $e);	
					}
				}
			}
			$empresas = $temp;
		}

		$planos = Plano::where('visivel_representante', 1)->get();

		return view('rep/empresas')
		->with('empresas', $empresas)
		->with('status', $request->status)
		->with('nome', $request->nome)
		->with('nome', $request->nome)
		->with('filtro', true)
		->with('cpf_cnpj', $request->cpf_cnpj)
		->with('planos', $planos)
		->with('representante', $representante)

		->with('paraImprimir', true)
		->with('title', 'Empresas Filtro');
	}

	public function detalhes($id){

		$hoje = date('Y-m-d');
		$planoExpirado = false;

		$empresa = Empresa::find($id);

		$permissoesAtivas = $empresa->permissao;
		$permissoesAtivas = json_decode($permissoesAtivas);

		$value = session('user_logged');

		if($empresa->planoEmpresa){
			$exp = $empresa->planoEmpresa->expiracao;
			if(strtotime($hoje) > strtotime($exp)){
				$planoExpirado = true;
			}
		}

		if($value['super'] && $value['id'] == $id){
			$permissoesAtivas = $this->detalhesMaster();
		}
		$perfis = PerfilAcesso::all();

		$rep = Representante::find($empresa->representante->representante_id);
		$bloquear_empresa = 0;
		if($rep){
			$bloquear_empresa = $rep->bloquear_empresa;
		}

		return view('rep/detalhes')
		->with('empresa', $empresa)
		->with('perfis', $perfis)
		->with('certificado', $empresa->certificado)
		->with('planoExpirado', $planoExpirado)
		->with('permissoesAtivas', $permissoesAtivas)
		->with('bloquear_empresa', $bloquear_empresa)
		->with('empresaJs', true)
		->with('title', 'Detalhes empresa');

	}

	public function update(Request $request){
		$empresa = Empresa::find($request->id);

		// if($request){
		// 	$permissao = $this->validaPermissao($request);
		// 	$empresa->permissao = $permissao;
		// }

		$empresa->nome = $request->nome;
		$empresa->rua = $request->rua;
		$empresa->numero = $request->numero;
		$empresa->representante_legal = $request->representante_legal ?? '';
		$empresa->cpf_representante_legal = $request->cpf_representante_legal ?? '';
		$empresa->cep = $request->cep ?? '';
		$empresa->uf = $request->uf ?? '';
		$empresa->bairro = $request->bairro;
		$empresa->cidade = $request->cidade;
		$empresa->telefone = $request->telefone;
		$empresa->email = $request->email;
		$empresa->cnpj = $request->cnpj;
		$empresa->status = $request->status ? 1 : 0;

		$empresa->save();
		// $this->percorreUsuariosEmpresa($empresa, $permissao);

		session()->flash("mensagem_sucesso", "Dados atualizados!");
		return redirect()->back();
	}

	public function percorreUsuariosEmpresa($empresa, $permissao){

		foreach($empresa->usuarios as $e){
			$temp = [];
			$permissaoAntiga = json_decode($e->permissao);
			foreach($permissao as $p){
				array_push($temp, $p);
			}
			$e->permissao = json_encode($temp);
			$e->save();
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
				// $nome = str_replace("", "_", $s['rota']);
				if(isset($arr[$s['rota']])){
					array_push($temp, $s['rota']);
				}
			}
		}
		return $temp;
	}

	public function alterarSenha($id){
		$empresa = Empresa::find($id);
		return view('rep/alterar_senha')
		->with('empresa', $empresa)
		->with('title', 'Alteração de senha');
	}

	public function alterarSenhaPost(Request $request){
		$empresa = Empresa::find($request->id);
		$senha = $request->senha;

		foreach($empresa->usuarios as $u){
			$u->senha = md5($senha);
			$u->save();
		}

		session()->flash("mensagem_sucesso", "Senhas alteradas!");
		return redirect()->back();
	}

	public function financeiro($id){
		// $representanteEmpresa = RepresentanteEmpresa::find($id);
		$empresa = Empresa::find($id);

		return view('rep/financeiro')
		->with('empresa', $empresa)
		->with('rep_id', $empresa->representante->id)
		->with('title', 'Financeiro');
	}

	private function setarLicenca($planoEmpresa){
		$plano = $planoEmpresa->plano;
		$exp = date('Y-m-d', strtotime("+$plano->intervalo_dias days",strtotime( 
			date('Y-m-d'))));

		$planoEmpresa->expiracao = $this->parseDate($exp);
		$planoEmpresa->save();
	}

	public function salvarPagamento(Request $request){

		try{

			$rep = RepresentanteEmpresa::find($request->rep_id);
			// $intervalo = 30;
			// if($rep->empresa->planoEmpresa){
			// 	$intervalo = $rep->empresa->planoEmpresa->plano->intervalo_dias;
			// }
			$this->setarLicenca($rep->empresa->planoEmpresa);

			FinanceiroRepresentante::create(
				[
					'representante_empresa_id' => $request->rep_id,
					'forma_pagamento' => $request->forma_pagamento,
					'valor' => __replace($request->valor)
				]
			);

			session()->flash("mensagem_sucesso", "Pagamento adicionado");
		}catch(\Exception $e){
			session()->flash("mensagem_erro", "Erro ao adicionar pagamento: " . 
				$e->getMessage());
		}
		return redirect()->back();
	}

	private function setPagamentoRepresentante($config, $valor, $formaPagamento){

		$rep = RepresentanteEmpresa::
		where('empresa_id', $config->empresa_id)
		->first();

		if($rep != null){

			$percComissao = $rep->representante->comissao;
			$valorComissao = $valor*($percComissao/100);

			FinanceiroRepresentante::create(
				[
					'representante_empresa_id' => $rep->id,
					'forma_pagamento' => $formaPagamento,
					'valor' => $valor
				]
			);
		}
	}

	public function verPagamentos($id){
		$representanteEmpresa = RepresentanteEmpresa::find($id);
		return view('rep/pagamentos')
		->with('representanteEmpresa', $representanteEmpresa)
		->with('title', 'Pagamentos');
	}

	private function validaLimiteCadastros(){
		$representante = Representante::
		where('usuario_id', get_id_user())
		->first();

		if(sizeof($representante->empresas)+1 > $representante->limite_cadastros){
			return false;
		} 
		return true;
	}

	public function novaEmpresa(){
		$perfis = PerfilAcesso::all();

		$valid = $this->validaLimiteCadastros();
		$planos = Plano::where('visivel_representante', 1)
		->orderBy('nome', 'asc')
		->get();
		if($valid){

			$contadores = Contador::where('representante_id', $this->usuario_id)->get();

			return view('rep/register_empresa')
			->with('empresaJs', true)
			->with('contadores', $contadores)
			->with('perfis', $perfis)
			->with('planos', $planos)
			->with('title', 'Nova Empresa');
		}else{
			session()->flash("mensagem_erro", "Limite de cadastros de empresa atingido!");
			return redirect()->back();
		}
	}

	public function saveEmpresa(Request $request){
		// $permissao = $this->validaPermissao($request);

		$plano = Plano::findOrFail($request->plano_id);
		$permissao = $plano->perfil->permissao;
		$perfilId = 0;

		if(isset($request->perfil_id) && $request->perfil_id != '0'){
			$tp = json_decode($request->perfil_id);
			$perfilId = $tp->id;
		}

		$this->_validateEmpresa($request);
		$data = [
			'nome' => $request->nome,
			'nome_fantasia' => $request->nome_fantasia ?? '',
			'representante_legal' => $request->representante_legal ?? '',
			'cpf_representante_legal' => $request->cpf_representante_legal ?? '',
			'uf' => $request->uf ?? '',
			'cep' => $request->cep ?? '',
			'rua' => $request->rua,
			'numero' => $request->numero,
			'bairro' => $request->bairro,
			'cidade' => $request->cidade,
			'telefone' => $request->telefone,
			'email' => $request->email,
			'cnpj' => $request->cnpj,
			'contador_id' => $request->contador_id ?? 0,
			// 'perfil_id' => $perfilId,
			'status' => 1,
			'tipo_representante' => 0,
			'permissao' => ($permissao)
		];

		$empresa = Empresa::create($data);
		$rep = null;

		if($empresa){

			$data = [
				'nome' => $request->nome_usuario, 
				'senha' => md5($request->senha),
				'login' => $request->login,
				'adm' => 1,
				'ativo' => 1,
				'permissao' => ($permissao),
				'img' => '',
				'menu_representante' => 0,
				'empresa_id' => $empresa->id
			];

			$usuario = Usuario::create($data);

			// $rep = Representante::create(
			// 	[
			// 		'nome' => $request->nome_usuario,
			// 		'rua' => $request->rua,
			// 		'telefone' => $request->telefone,
			// 		'email' => $request->email,
			// 		'numero' => $request->numero,
			// 		'bairro' => $request->bairro,
			// 		'cidade' => $request->cidade,
			// 		'cpf_cnpj' => $request->cnpj, 
			// 		'comissao' => __replace($request->comissao),
			// 		'usuario_id' => $usuario->id
			// 	]
			// );

			CategoriaConta::create([
				'nome' => 'Compras',
				'empresa_id' => $empresa->id,
				'tipo' => 'pagar'
			]);
			CategoriaConta::create([
				'nome' => 'Vendas',
				'empresa_id' => $empresa->id,
				'tipo' => 'receber'
			]);

			$plano_id = $request->plano_id;
			$representante = Representante::
			where('usuario_id', $this->usuario_id)
			->first();

			// $user = Usuario::findOrFail($this->usuario_id);

			$plano = Plano::findOrFail($plano_id);

			if($plano->perfil){
				$empresa->permissao = $plano->perfil->permissao;
				$empresa->save();
			}

			$expiracao = date('Y-m-d', strtotime(date('Y-m-d'). "+$plano->intervalo_dias days"));

			$representante = Representante::
			where('usuario_id', get_id_user())
			->first();

			$data = [
				'empresa_id' => $empresa->id,
				'plano_id' => $plano_id,
				'expiracao' => $expiracao,
				'representante_id' => $representante->id
			];

			PlanoEmpresaRepresentante::create($data);

			$this->criaFormasDePagamento($empresa->id);
			$this->criaConfigCaixa($usuario);

			$contrato = $this->gerarContrato($empresa->id);

			RepresentanteEmpresa::create(
				[
					'representante_id' => $representante->id,
					'empresa_id' => $empresa->id
				]
			);

			__saveAlertSuper('Ativação de plano', 'Ativação de plano do representante ' . $representante->nome, $empresa->id);

			session()->flash("mensagem_sucesso", "Plano atribuido, aguarde liberação do SUPER!");
			return redirect('/rep');
		}
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
			'cupom_modelo' => 2,
			'balanca_valor_peso' => 0,
			'balanca_digito_verificador' => 5,
			'valor_recebido_automatico' => 0,
			'impressora_modelo' => 80,
			'usuario_id' => $usuario->id,
			'mercadopago_public_key' => '',
			'mercadopago_access_token' => '',
			'tipos_pagamento' => '["01","02","03","04","05","06","10","11","12","13","14","15","16","17","90","99"]',
			'tipo_pagamento_padrao' => '01'
		];
		ConfigCaixa::create($data);
	}

	private function _validateEmpresa(Request $request){
		$rules = [
			'nome' => 'required',
			'representante_legal' => 'required',
			'cpf_representante_legal' => 'required',
			'cnpj' => 'required',
			'rua' => 'required',
			'numero' => 'required',
			'bairro' => 'required',
			'bairro' => 'required',
			'cidade' => 'required',
			'login' => 'required|unique:usuarios',
			'senha' => 'required',
			'telefone' => 'required',
			'email' => 'required',
			'nome_usuario' => 'required',
			'comissao' => $request->tipo_representante ? 'required' : ''
		];

		$messages = [
			'nome.required' => 'Campo obrigatório.',
			'representante_legal.required' => 'Campo obrigatório.',
			'cpf_representante_legal.required' => 'Campo obrigatório.',
			'cnpj.required' => 'Campo obrigatório.',
			'rua.required' => 'Campo obrigatório.',
			'numero.required' => 'Campo obrigatório.',
			'bairro.required' => 'Campo obrigatório.',
			'cidade.required' => 'Campo obrigatório.',
			'login.required' => 'Campo obrigatório.',
			'telefone.required' => 'Campo obrigatório.',
			'email.required' => 'Campo obrigatório.',
			'senha.required' => 'Campo obrigatório.',
			'nome_usuario.required' => 'Campo obrigatório.',
			'login.unique' => 'Usuário já cadastrado no sistema.',
			'comissao.required' => 'Informe a comissão.',

		];

		$this->validate($request, $rules, $messages);
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
		FormaPagamento::create([
			'empresa_id' => $empresa_id,
			'nome' => 'Conta crediario',
			'chave' => 'conta_crediario',
			'taxa' => 0,
			'status' => 1,
			'prazo_dias' => 0,
			'tipo_taxa' => 'perc'
		]);
	}

	public function gerarContrato($empresa_id){
		try{
			$contrato = Contrato::first();

			if($contrato == null) return false;
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
			return false;
		}
	}

	public function setarPlano($id){
		$empresa = Empresa::find($id);
		$planos = Plano::where('visivel_representante', 1)->get();

		if(sizeof($planos) == 0){
			session()->flash("mensagem_erro", "Cadastre um plano primeiramente");
			return redirect('/planos');
		}
		$p = $planos[0];

		$exp = date('d/m/Y', strtotime("+$p->intervalo_dias days",strtotime(str_replace("/", "-", 
			date('Y-m-d')))));

		return view('rep/setar_plano')
		->with('empresa', $empresa)
		->with('planos', $planos)
		->with('exp', $exp)
		->with('title', 'Setar Plano');
	}

	public function setarPlanoPost(Request $request){
		$empresa = Empresa::find($request->id);
		$plano = $empresa->planoEmpresa;
		if($plano != null){
			$plano->delete();
		}

		$plano = Plano::findOrFail($request->plano);
		if($plano->perfil){
			$empresa->permissao = $plano->perfil->permissao;
			$empresa->save();

			foreach($empresa->usuarios as $e){
				$e->permissao = $plano->perfil->permissao;
				$e->save();
			}
		}

		if($request->indeterminado){
			$expiracao = '0000-00-00';
		}else{
			$expiracao = $this->parseDate($request->expiracao);
		}

		$representante = Representante::
		where('usuario_id', $this->usuario_id)
		->first();

		$permissao = $plano->perfil->permissao;
		
		$data = [
			'empresa_id' => $empresa->id,
			'plano_id' => $plano->id,
			'expiracao' => $expiracao,
			'representante_id' => $representante->id
		];

		PlanoEmpresaRepresentante::create($data);

		__saveAlertSuper('Ativação de plano', 'Ativação de plano do representante ' . $representante->nome, $empresa->id);

		session()->flash("mensagem_sucesso", "Plano atribuido, aguarde liberação do SUPER!");

		return redirect('/rep/detalhes/'. $empresa->id);
	}

	private function parseDate($date){
		return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
	}

	public function arquivosXml($empresa_id){
		$empresa = Empresa::find($empresa_id);

		$representante = Representante::
		where('usuario_id', get_id_user())
		->first();

		if(!$representante->acesso_xml){
			session()->flash("mensagem_erro", "Sem permissão de acesso!");
			return redirect()->back();
		}
		return view('rep/enviarXml')
		->with('empresa', $empresa)
		->with('title', 'Enviar XML');

	}

	public function filtroXml(Request $request){
		$empresa = Empresa::find($request->empresa_filtro_id);
		$cnpj = $this->getCnpjEmpresa($empresa);
		$xml = Venda::
		whereBetween('updated_at', [
			$this->parseDate($request->data_inicial), 
			$this->parseDate($request->data_final, true)])
		->where('empresa_id', $request->empresa_filtro_id);

		$estado = $request->estado;
		if($estado == 1){
			$xml->where('estado', 'APROVADO');
		}else{
			$xml->where('estado', 'CANCELADO');
		}
		$xml = $xml->get();

		$public = env('SERVIDOR_WEB') ? 'public/' : '';

		try{
			if(count($xml) > 0){

				// $zip_file = 'zips/xml_'.$cnpj.'.zip';
				$zip_file = public_path('zips') . '/xml_'.$cnpj.'.zip';

				$zip = new \ZipArchive();
				$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

				if($estado == 1){
					foreach($xml as $x){
						if(file_exists($public.'xml_nfe/'.$x->chave. '.xml'))
							$zip->addFile($public.'xml_nfe/'.$x->chave. '.xml', $x->path_xml);
					}
				}else{
					foreach($xml as $x){
						if(file_exists($public.'xml_nfe_cancelada/'.$x->chave. '.xml'))
							$zip->addFile($public.'xml_nfe_cancelada/'.$x->chave. '.xml', $x->path_xml);
					}
				}
				$zip->close();
			}
		}catch(\Exception $e){
		}

		try{
			$xmlCte = Cte::
			whereBetween('updated_at', [
				$this->parseDate($request->data_inicial), 
				$this->parseDate($request->data_final, true)])
			->where('empresa_id', $request->empresa_filtro_id);

			$estado = $request->estado;
			if($estado == 1){
				$xmlCte->where('estado', 'APROVADO');
			}else{
				$xmlCte->where('estado', 'CANCELADO');
			}
			$xmlCte = $xmlCte->get();

			if(count($xmlCte) > 0){

				// $zip_file = $public.'xmlcte.zip';
				// $zip_file = 'zips/xmlcte_'.$cnpj.'.zip';
				$zip_file = public_path('zips') . '/xmlcte_'.$cnpj.'.zip';


				$zip = new \ZipArchive();
				$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

				if($estado == 1){
					foreach($xmlCte as $x){
						if(file_exists($public.'xml_cte/'.$x->chave. '.xml'))
							$zip->addFile($public.'xml_cte/'.$x->chave. '.xml', $x->path_xml);
					}
				}else{
					foreach($xmlCte as $x){
						if(file_exists($public.'xml_cte_cancelada/'.$x->chave. '.xml'))
							$zip->addFile($public.'xml_cte_cancelada/'.$x->chave. '.xml', $x->path_xml);
					}
				}
				$zip->close();


			}
		}catch(\Exception $e){

		}

		try{
			$xmlNfce = VendaCaixa::
			whereBetween('updated_at', [
				$this->parseDate($request->data_inicial), 
				$this->parseDate($request->data_final, true)])
			->where('empresa_id', $request->empresa_filtro_id);

			if($estado == 1){
				$xmlNfce->where('estado', 'APROVADO');
			}else{
				$xmlNfce->where('estado', 'CANCELADO');
			}
			$xmlNfce = $xmlNfce->get();

			if(sizeof($xmlNfce) > 0){

				// $zip_file = 'zips/xmlnfce_'.$cnpj.'.zip';
				$zip_file = public_path('zips') . '/xmlnfce_'.$cnpj.'.zip';

				$zip = new \ZipArchive();
				$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

				if($estado == 1){
					foreach($xmlNfce as $x){
						if(file_exists($public.'xml_nfce/'.$x->chave. '.xml'))
							$zip->addFile($public.'xml_nfce/'.$x->chave. '.xml', $x->chave. '.xml');
					}
				}else{
					foreach($xmlNfce as $x){
						if(file_exists($public.'xml_nfce_cancelada/'.$x->chave. '.xml'))
							$zip->addFile($public.'xml_nfce_cancelada/'.$x->chave. '.xml', $x->chave. '.xml');
					}
				}
				$zip->close();
			}
		}catch(\Exception $e){

		}

		$xmlMdfe = Mdfe::
		whereBetween('updated_at', [
			$this->parseDate($request->data_inicial), 
			$this->parseDate($request->data_final, true)])
		->where('empresa_id', $request->empresa_filtro_id);

		$estado = $request->estado;
		if($estado == 1){
			$xmlMdfe->where('estado', 'APROVADO');
		}else{
			$xmlMdfe->where('estado', 'CANCELADO');
		}
		$xmlMdfe = $xmlMdfe->get();

		if(count($xmlMdfe) > 0){
			try{

				// $zip_file = 'zips/xmlmdfe_'.$cnpj.'.zip';
				$zip_file = public_path('zips') . '/xmlmdfe_'.$cnpj.'.zip';


				$zip = new \ZipArchive();
				$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
				if($estado == 1){
					foreach($xmlMdfe as $x){
						if(file_exists($public.'xml_mdfe/'.$x->chave. '.xml')){
							$zip->addFile($public.'xml_mdfe/'.$x->chave. '.xml', $x->chave. '.xml');
						}
					}
				}else{
					foreach($xmlMdfe as $x){
						if(file_exists($public.'xml_mdfe_cancelada/'.$x->chave. '.xml')){
							$zip->addFile($public.'xml_mdfe_cancelada/'.$x->chave. '.xml', $x->chave. '.xml');
						}
					}
				}
				$zip->close();

			}catch(\Exception $e){
				// echo $e->getMessage();
			}

		}

		//nfe entrada
		$xmlEntrada = Compra::
		whereBetween('updated_at', [
			$this->parseDate($request->data_inicial), 
			$this->parseDate($request->data_final, true)])
		->where('empresa_id', $request->empresa_filtro_id);

		if($estado == 1){
			$xmlEntrada->where('estado', 'APROVADO');
		}else{
			$xmlEntrada->where('estado', 'CANCELADO');
		}
		$xmlEntrada = $xmlEntrada->get();

		if(count($xmlEntrada) > 0){

			try{
				// $zip_file = 'zips/xmlmdfe_'.$cnpj.'.zip';
				$zip_file = public_path('zips') . '/xmlEntrada_'.$cnpj.'.zip';


				$zip = new \ZipArchive();
				$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

				if($estado == 1){
					foreach($xmlEntrada as $x){
						if(file_exists($public.'xml_entrada_emitida/'.$x->chave. '.xml')){
							$zip->addFile($public.'xml_entrada_emitida/'.$x->chave. '.xml', $x->chave. '.xml');
						}
					}
				}else{
					foreach($xmlEntrada as $x){
						if(file_exists($public.'xml_nfe_entrada_cancelada/'.$x->chave. '.xml')){
							$zip->addFile($public.'xml_nfe_entrada_cancelada/'.$x->chave. '.xml', $x->chave. '.xml');
						}
					}
				}
				$zip->close();

			}catch(\Exception $e){
				// echo $e->getMessage();
			}

		}

		$xmlDevolucao = Devolucao::
		whereBetween('updated_at', [
			$this->parseDate($request->data_inicial), 
			$this->parseDate($request->data_final, true)])
		->where('empresa_id', $request->empresa_filtro_id);
		// 1- Aprovado, 3 - Cancelado
		if($estado == 1){
			$xmlDevolucao->where('estado', 1);
		}else{
			$xmlDevolucao->where('estado', 3);
		}
		$xmlDevolucao = $xmlDevolucao->get();

		if(count($xmlDevolucao) > 0){

			try{

				// $zip_file = $public.'xmlmdfe.zip';

				// $zip_file = 'zips/xmlmdfe_'.$cnpj.'.zip';
				$zip_file = public_path('zips') . '/xmlDevolucao_'.$cnpj.'.zip';


				$zip = new \ZipArchive();
				$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

				if($estado == 1){
					foreach($xmlDevolucao as $x){
						if(file_exists($public.'xml_devolucao/'.$x->chave_gerada. '.xml')){
							$zip->addFile($public.'xml_devolucao/'.$x->chave_gerada. '.xml', $x->chave_gerada. '.xml');
						}
					}
				}else{
					foreach($xmlDevolucao as $x){
						if(file_exists($public.'xml_devolucao_cancelada/'.$x->chave_gerada. '.xml')){
							$zip->addFile($public.'xml_devolucao_cancelada/'.$x->chave_gerada. '.xml', $x->chave_gerada. '.xml');
						}
					}
				}
				$zip->close();

			}catch(\Exception $e){
				// echo $e->getMessage();
			}

		}

		$dataInicial = str_replace("/", "-", $request->data_inicial);
		$dataFinal = str_replace("/", "-", $request->data_final);

		return view('rep/enviarXml')
		->with('xml', $xml)
		->with('xmlNfce', $xmlNfce)
		->with('xmlCte', $xmlCte)
		->with('xmlMdfe', $xmlMdfe)
		->with('empresa', $empresa)
		->with('estado', $request->estado)
		->with('xmlEntrada', $xmlEntrada)
		->with('xmlDevolucao', $xmlDevolucao)
		->with('dataInicial', $dataInicial)
		->with('dataFinal', $dataFinal)
		->with('title', 'Enviar XML');
	}

	private function getCnpjEmpresa($empresa){
		$empresa = Empresa::find($empresa->id);
		$cnpj = $empresa->configNota->cnpj;

		$cnpj = str_replace(".", "", $cnpj);
		$cnpj = str_replace("/", "", $cnpj);
		$cnpj = str_replace("-", "", $cnpj);
		$cnpj = str_replace(" ", "", $cnpj);

		return $cnpj;
	}

	public function downloadXml($empresa_id){
		// $public = env('SERVIDOR_WEB') ? 'public/' : '';
		$empresa = Empresa::find($empresa_id);
		$cnpj = $this->getCnpjEmpresa($empresa);
		$file = public_path('zips') . '/xml_'.$cnpj.'.zip';

		// $file = $public."zips/xml_".$this->empresa_id.".zip";

		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="xmls_nfe_'.$cnpj.'.zip"');
		readfile($file);

		// return redirect('/enviarXml');

	}

	public function downloadEntrada($empresa_id){
		// $public = env('SERVIDOR_WEB') ? 'public/' : '';
		// $file = $public."xmlnfce.zip";
		// $file = $public."zips/xmlnfce_".$this->empresa_id.".zip";
		$empresa = Empresa::find($empresa_id);
		$cnpj = $this->getCnpjEmpresa($empresa);
		$file = public_path('zips') . '/xmlEntrada_'.$cnpj.'.zip';

		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="xml_entrada_'.$cnpj.'.zip"');
		readfile($file);

		// return redirect('/enviarXml');
	}

	public function downloadDevolucao($empresa_id){
		// $public = env('SERVIDOR_WEB') ? 'public/' : '';
		// $file = $public."xmlnfce.zip";
		// $file = $public."zips/xmlnfce_".$this->empresa_id.".zip";
		$empresa = Empresa::find($empresa_id);
		$cnpj = $this->getCnpjEmpresa($empresa);
		$file = public_path('zips') . '/xmlDevolucao_'.$cnpj.'.zip';

		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="xml_entrada_'.$cnpj.'.zip"');
		readfile($file);

		// return redirect('/enviarXml');
	}

	public function downloadNfce($empresa_id){

		$empresa = Empresa::find($empresa_id);
		$cnpj = $this->getCnpjEmpresa($empresa);
		$file = public_path('zips') . '/xmlnfce_'.$cnpj.'.zip';

		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="xmls_nfce_'.$cnpj.'.zip"');
		readfile($file);

		// return redirect('/enviarXml');
	}

	public function downloadCte($empresa_id){
		$empresa = Empresa::find($empresa_id);
		$cnpj = $this->getCnpjEmpresa($empresa);
		$file = public_path('zips') . '/xmlcte_'.$cnpj.'.zip';

		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="xmls_cte_'.$cnpj.'.zip"');
		readfile($file);

		// return redirect('/enviarXml');
	}

	public function downloadMdfe($empresa_id){
		$empresa = Empresa::find($empresa_id);
		$cnpj = $this->getCnpjEmpresa($empresa);
		$file = public_path('zips') . '/xmlmdfe_'.$cnpj.'.zip';

		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="xmls_mdfe_'.$cnpj.'.zip"');
		readfile($file);

		// return redirect('/enviarXml');
	}

	public function configEmitente($empresa_id){
		$empresa = Empresa::find($empresa_id);
		$config = $empresa->configNota;

		try{
			$naturezas = NaturezaOperacao::
			where('empresa_id', $empresa_id)
			->get();
			$tiposPagamento = ConfigNota::tiposPagamento();
			$tiposFrete = ConfigNota::tiposFrete();
			$listaCSTCSOSN = ConfigNota::listaCST();
			$listaCSTPISCOFINS = ConfigNota::listaCST_PIS_COFINS();
			$listaCSTIPI = ConfigNota::listaCST_IPI();

			$config = ConfigNota::
			where('empresa_id', $empresa_id)
			->first();

			$certificado = Certificado::
			where('empresa_id', $empresa_id)
			->first();

			$cUF = ConfigNota::estados();

			$infoCertificado = null;
			if($certificado != null){
				$infoCertificado = $this->getInfoCertificado($certificado);
			}

			$soapDesativado = !extension_loaded('soap');
			$cidades = Cidade::all();	

			return view('rep/config_emitente')
			->with('config', $config)
			->with('empresa', $empresa)
			->with('naturezas', $naturezas)
			->with('tiposPagamento', $tiposPagamento)
			->with('tiposFrete', $tiposFrete)
			->with('infoCertificado', $infoCertificado)
			->with('soapDesativado', $soapDesativado)
			->with('listaCSTCSOSN', $listaCSTCSOSN)
			->with('listaCSTPISCOFINS', $listaCSTPISCOFINS)
			->with('listaCSTIPI', $listaCSTIPI)
			->with('cUF', $cUF)
			->with('cidades', $cidades)
			->with('testeJs', true)
			->with('configJs', true)
			->with('certificado', $certificado)
			->with('title', 'Configurar Emitente');
		}catch(\Exception $e){
			echo $e->getMessage();
			echo "<br><a href='/configNF/deleteCertificado'>Remover Certificado</a>";
		}

	}

	private function getInfoCertificado($certificado){

		$infoCertificado = Certificate::readPfx($certificado->arquivo, $certificado->senha);

		$publicKey = $infoCertificado->publicKey;

		$inicio =  $publicKey->validFrom->format('Y-m-d H:i:s');
		$expiracao =  $publicKey->validTo->format('Y-m-d H:i:s');

		return [
			'serial' => $publicKey->serialNumber,
			'inicio' => \Carbon\Carbon::parse($inicio)->format('d-m-Y H:i'),
			'expiracao' => \Carbon\Carbon::parse($expiracao)->format('d-m-Y H:i'),
			'id' => $publicKey->commonName
		];

	}

	function sanitizeString($str){
		return preg_replace('{\W}', ' ', preg_replace('{ +}', ' ', strtr(
			utf8_decode(html_entity_decode($str)),
			utf8_decode('ÀÁÃÂÉÊÍÓÕÔÚÜÇÑàáãâéêíóõôúüçñ'),
			'AAAAEEIOOOUUCNaaaaeeiooouucn')));
	}

	public function saveConfig(Request $request){
		$this->_validateConfig($request);
		$uf = $request->uf;

		$nomeImagem = "";

		if($request->hasFile('file')){
			$file = $request->file('file');

			$extensao = $file->getClientOriginalExtension();
			$rand = rand(0, 999999);
			$nomeImagem = md5($file->getClientOriginalName()).$rand.".".$extensao;
			$upload = $file->move(public_path('logos'), $nomeImagem);
		}

		$cidade = Cidade::find($request->cidade);
		$codMun = $cidade->codigo;
		$uf = $cidade->uf;
		$cUF = ConfigNota::getCodUF($uf);
		$municipio = $cidade->nome;

		$request->merge([
			'senha_remover' => trim($request->senha_remover)
		]);
		if($request->id == 0){

			$result = ConfigNota::create([
				'razao_social' => strtoupper($this->sanitizeString($request->razao_social)),
				'nome_fantasia' => strtoupper($this->sanitizeString($request->nome_fantasia)),
				'cnpj' => $request->cnpj,
				'ie' => $request->ie,
				'logradouro' => strtoupper($this->sanitizeString($request->logradouro)),
				'numero' => strtoupper($this->sanitizeString($request->numero)),
				'bairro' => strtoupper($this->sanitizeString($request->bairro)),
				'cep' => $request->cep,
				'email' => $request->email,
				'municipio' => strtoupper($municipio),
				'codMun' => $codMun,
				'codPais' => '1058',
				'UF' => $uf,
				'pais' => 'BRASIL',
				'fone' => $this->sanitizeString($request->fone),
				'CST_CSOSN_padrao' => $request->CST_CSOSN_padrao, 
				'CST_COFINS_padrao' => $request->CST_COFINS_padrao, 
				'CST_PIS_padrao' => $request->CST_PIS_padrao, 
				'CST_IPI_padrao' => $request->CST_IPI_padrao, 
				'frete_padrao' => $request->frete_padrao, 
				'tipo_pagamento_padrao' => $request->tipo_pagamento_padrao, 
				'nat_op_padrao' => $request->nat_op_padrao ?? 0, 
				'ambiente' => $request->ambiente, 
				'cUF' => $cUF,
				'ultimo_numero_nfe' => $request->ultimo_numero_nfe, 
				'ultimo_numero_nfce' => $request->ultimo_numero_nfce, 
				'ultimo_numero_cte' => $request->ultimo_numero_cte, 
				'ultimo_numero_mdfe' => $request->ultimo_numero_mdfe,
				'numero_serie_nfe' => $request->numero_serie_nfe,
				'numero_serie_nfce' => $request->numero_serie_nfce,
				'numero_serie_cte' => $request->numero_serie_cte,
				'csc' => $request->csc,
				'csc_id' => $request->csc_id,
				'certificado_a3' => $request->certificado_a3 ? true: false,
				'empresa_id' => $request->empresaId,
				'inscricao_municipal' => $request->inscricao_municipal ?? '',
				'aut_xml' => $request->aut_xml ?? '',
				'logo' => $nomeImagem,
				'campo_obs_nfe' => $request->campo_obs_nfe ?? '',
				'senha_remover' => '',
				'gerenciar_comissao_usuario_logado' => 1
			]);
		}else{
			$config = ConfigNota::
			where('empresa_id', $request->empresaId)
			->first();

			$config->razao_social = strtoupper($this->sanitizeString($request->razao_social));
			$config->nome_fantasia = strtoupper($this->sanitizeString($request->nome_fantasia));
			$config->cnpj = $this->sanitizeString($request->cnpj);
			$config->ie = $this->sanitizeString($request->ie);
			$config->logradouro = strtoupper($this->sanitizeString($request->logradouro));
			$config->numero = strtoupper($this->sanitizeString($request->numero));
			$config->bairro = strtoupper($this->sanitizeString($request->bairro));
			$config->cep = $request->cep;
			$config->municipio = strtoupper($this->sanitizeString($municipio));
			$config->codMun = $codMun;
			$config->UF = $uf;
			$config->fone = $request->fone;
			$config->email = $request->email;

			$config->CST_CSOSN_padrao = $request->CST_CSOSN_padrao;
			$config->CST_COFINS_padrao = $request->CST_COFINS_padrao;
			$config->CST_PIS_padrao = $request->CST_PIS_padrao;
			$config->CST_IPI_padrao = $request->CST_IPI_padrao;

			$config->frete_padrao = $request->frete_padrao;
			$config->tipo_pagamento_padrao = $request->tipo_pagamento_padrao;
			$config->nat_op_padrao = $request->nat_op_padrao ?? 0;
			$config->ambiente = $request->ambiente;
			$config->cUF = $cUF;
			$config->ultimo_numero_nfe = $request->ultimo_numero_nfe;
			$config->ultimo_numero_nfce = $request->ultimo_numero_nfce; 
			$config->ultimo_numero_cte = $request->ultimo_numero_cte;
			$config->ultimo_numero_mdfe = $request->ultimo_numero_mdfe;
			$config->numero_serie_nfe = $request->numero_serie_nfe;
			$config->numero_serie_nfce = $request->numero_serie_nfce;
			$config->numero_serie_cte = $request->numero_serie_cte;
			$config->csc = $request->csc;
			$config->csc_id = $request->csc_id;
			$config->campo_obs_nfe = $request->campo_obs_nfe ?? '';
			if($request->senha_remover != ""){
				$config->senha_remover = md5($request->senha_remover);
			}
			$config->casas_decimais = $request->casas_decimais;
			$config->certificado_a3 = $request->certificado_a3 ? true : false;

			$config->inscricao_municipal = $request->inscricao_municipal;
			$config->aut_xml = $request->aut_xml;
			if($request->hasFile('file')){
				$config->logo = $nomeImagem;
			}


			$result = $config->save();
		}

		$value = session('user_logged');

		$value['ambiente'] = $request->ambiente == 1 ? 'Produção' : 'Homologação';

		session()->put('user_logged', $value);

		if($result){
			session()->flash("mensagem_sucesso", "Configurado com sucesso!");
		}else{
			session()->flash('mensagem_erro', 'Erro ao configurar!');
		}

		return redirect()->back();
	}

	private function _validateConfig(Request $request){
		$rules = [
			'razao_social' => 'required|max:60',
			'nome_fantasia' => 'required|max:60',
			'cnpj' => 'required',
			'ie' => 'required',
			'logradouro' => 'required|max:80',
			'numero' => 'required|max:10',
			'bairro' => 'required|max:50',
			'fone' => 'required|max:20',
			'email' => 'required|email|max:60',
			'cep' => 'required',
			// 'municipio' => 'required',
			// 'codMun' => 'required',
			// 'uf' => 'required|max:2|min:2',
			'ultimo_numero_nfe' => 'required',
			'ultimo_numero_nfce' => 'required',
			'ultimo_numero_cte' => 'required',
			'ultimo_numero_mdfe' => 'required',
			'numero_serie_nfe' => 'required|max:3',
			'numero_serie_nfce' => 'required|max:3',
			'numero_serie_cte' => 'required|max:3',
			'csc' => 'required',
			'csc_id' => 'required',
			'file' => 'max:2000',
		];

		$messages = [
			'razao_social.required' => 'O Razão social nome é obrigatório.',
			'razao_social.max' => '60 caracteres maximos permitidos.',
			'nome_fantasia.required' => 'O campo Nome Fantasia é obrigatório.',
			'nome_fantasia.max' => '60 caracteres maximos permitidos.',
			'cnpj.required' => 'O campo CNPJ é obrigatório.',
			'logradouro.required' => 'O campo Logradouro é obrigatório.',
			'ie.required' => 'O campo Inscrição Estadual é obrigatório.',
			'logradouro.max' => '80 caracteres maximos permitidos.',
			'numero.required' => 'O campo Numero é obrigatório.',
			'cep.required' => 'O campo CEP é obrigatório.',
			'municipio.required' => 'O campo Municipio é obrigatório.',
			'numero.max' => '10 caracteres maximos permitidos.',
			'bairro.required' => 'O campo Bairro é obrigatório.',
			'bairro.max' => '50 caracteres maximos permitidos.',
			'fone.required' => 'O campo Telefone é obrigatório.',
			'fone.max' => '20 caracteres maximos permitidos.',

			'uf.required' => 'O campo UF é obrigatório.',
			'uf.max' => 'UF inválida.',
			'uf.min' => 'UF inválida.',

			'pais.required' => 'O campo Pais é obrigatório.',
			'codPais.required' => 'O campo Código do Pais é obrigatório.',
			'codMun.required' => 'O campo Código do Municipio é obrigatório.',
			'rntrc.max' => '12 caracteres maximos permitidos.',
			'ultimo_numero_nfe.required' => 'Campo obrigatório.',
			'ultimo_numero_nfe.required' => 'Campo obrigatório.',
			'ultimo_numero_nfce.required' => 'Campo obrigatório.',
			'ultimo_numero_cte.required' => 'Campo obrigatório.',
			'ultimo_numero_mdfe.required' => 'Campo obrigatório.',
			'numero_serie_nfe.required' => 'Campo obrigatório.',
			'numero_serie_nfe.max' => 'Maximo de 3 Digitos.',
			'numero_serie_nfce.required' => 'Campo obrigatório.',
			'numero_serie_nfce.max' => 'Maximo de 3 Digitos.',
			'numero_serie_cte.required' => 'Campo obrigatório.',
			'numero_serie_cte.max' => 'Maximo de 3 Digitos.',
			'csc.required' => 'O CSC é obrigatório.',
			'csc_id.required' => 'O CSCID é obrigatório.',
			'file.max' => 'Upload de até 2000KB.',

			'email.required' => 'Campo obrigatório.',
			'email.max' => 'Máximo de 60caracteres.',
			'email.email' => 'Email inválido.',

		];

		$this->validate($request, $rules, $messages);
	}

	public function deleteCertificado($empresa_id){
		Certificado::
		where('empresa_id', $empresa_id)
		->delete();
		session()->flash("mensagem_sucesso", "Certificado Removido!");
		return redirect()->back();
	}

	public function uploadCertificado($empresa_id){
		$empresa = Empresa::find($empresa_id);
		return view('rep/upload_certificado')
		->with('empresa', $empresa)
		->with('title', 'Upload de Certificado');
	}

	public function saveCertificado(Request $request){

		if($request->hasFile('file') && strlen($request->senha) > 0){
			$file = $request->file('file');
			$temp = safe_file_get_contents($file);

			$extensao = $file->getClientOriginalExtension();

			$config = ConfigNota::
			where('empresa_id', $request->empresaId)
			->first();

			$cnpj = str_replace(" ", "", $config->cnpj);
			$cnpj = str_replace(".", "", $cnpj);
			$cnpj = str_replace("-", "", $cnpj);
			$cnpj = str_replace("/", "", $cnpj);
			$fileName = "$cnpj.$extensao";

			$res = Certificado::create([
				'senha' => $request->senha,
				'arquivo' => $temp,
				'empresa_id' => $request->empresaId
			]);

			if(env("CERTIFICADO_ARQUIVO") == 1){
				$file->move(public_path('certificados'), $fileName);
			}

			if($res){
				session()->flash("mensagem_sucesso", "Upload de certificado realizado!");
				return redirect('/rep/configEmitente/'.$request->empresaId);

			}
		}else{
			session()->flash("mensagem_erro", "Envie o arquivo e senha por favor!");
			return redirect('/rep/configEmitente/'.$request->empresaId);
		}
	}

	public function removeLogo($empresaId){
		$empresa = Empresa::find($empresaId);
		$config = $empresa->configNota;

		$config->logo = '';
		$config->save();
		session()->flash("mensagem_sucesso", "Logo removida!");
		return redirect()->back();
	}

	public function download($id){
		$config = ConfigNota::
		where('empresa_id', $id)
		->first();

		if($config == null){
			session()->flash("mensagem_erro", "Nenhum certificado!");
			return redirect()->back();
		}

		$cnpj = str_replace(".", "", $config->cnpj);
		$cnpj = str_replace("-", "", $cnpj);
		$cnpj = str_replace("/", "", $cnpj);
		$cnpj = str_replace(" ", "", $cnpj);

		try{
			return response()->download(public_path('certificados/').$cnpj. '.pfx');
		}catch(\Exception $e){
			echo $e->getMessage();
		}

	}

	public function storeContador(Request $request){
		$data = (object)$request->data;
		$user = Usuario::where('login', $data->login)->first();
		if($user != null){
			return response()->json("Já existe um usuário com esse login", 403);
		}
		$permissoesTodas = json_encode($this->permissoesTodas());
		$cidade = Cidade::findOrFail($data->cidade_id);
		$arr = [
			'nome' => $data->razao_social,
			'rua' => '',
			'numero' => '',
			'bairro' => '',
			'cidade' => $cidade->nome,
			'telefone' => $data->celular,
			'email' => $data->email,
			'cnpj' => $data->doc,
			'status' => 1,
			'permissao' => $permissoesTodas,
			'tipo_contador' => 1,
			'representante_legal' => $data->responsavel,
			'pix' => $data->pix
		];

		$empresa = Empresa::create($arr);

		$arr = [
			'nome' => $data->login, 
			'senha' => md5($data->senha),
			'login' => $data->login,
			'adm' => 1,
			'img' => '',
			'ativo' => 1,
			'email' => $data->email,
			'menu_representante' => 0,
			'empresa_id' => $empresa->id,
			'permissao' => $permissoesTodas,
			'permite_desconto' => 1
		];

		$usuario = Usuario::create($arr);

		$contador = $this->salvaContador($data, $empresa->id);


		return response()->json($contador, 200);;
	}

	private function salvaContador($data, $empresa_id){
		$ct = [
			'razao_social' => $data->razao_social,
			'nome_fantasia' => '',
			'cnpj' => $data->doc,
			'ie' => '',
			'logradouro' => '',
			'numero' => '',
			'bairro' => '',
			'fone' => $data->celular,
			'email' => $data->email,
			'cep' => '',
			'percentual_comissao' => 0,
			'cidade_id' => $data->cidade_id,
			'cadastrado_por_cliente' => 0,
			'agencia' => '',
			'conta' => '',
			'banco' => '',
			'chave_pix' => $data->pix ?? '',
			'dados_bancarios' => '',
			'contador_parceiro' => 1,
			'representante_id' => request()->empresa_id,
			'empresa_id' => $empresa_id
		];
		return Contador::create($ct);
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

}
