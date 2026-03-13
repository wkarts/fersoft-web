<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfigNota;
use App\Models\Certificado;
use App\Models\Empresa;
use App\Models\Cidade;
use App\Models\CashBackConfig;
use App\Models\CashBackCliente;
use App\Models\EscritorioContabil;
use App\Models\NaturezaOperacao;
use App\Models\BalancaConfig;
use App\Services\NFService;
use NFePHP\Common\Certificate;
use Mail;

class ConfigNotaController extends Controller
{
	protected $empresa_id = null;
	public function __construct(){
		$this->middleware(function ($request, $next) {
			$this->empresa_id = $request->empresa_id;

			$value = session('user_logged');
			if(!$value){
				return redirect("/login");
			}
			return $next($request);
		});
	}

	function sanitizeString($str){
		return preg_replace('{\W}', ' ', preg_replace('{ +}', ' ', strtr(
			utf8_decode(html_entity_decode($str)),
			utf8_decode('ÀÁÃÂÉÊÍÓÕÔÚÜÇÑàáãâéêíóõôúüçñ'),
			'AAAAEEIOOOUUCNaaaaeeiooouucn')));
	}

	public function index(){
		try{
			$naturezas = NaturezaOperacao::
			where('empresa_id', $this->empresa_id)
			->get();
			$tiposPagamento = ConfigNota::tiposPagamento();
			$tiposFrete = ConfigNota::tiposFrete();
			$listaCSTCSOSN = ConfigNota::listaCST();
			$listaCSTPISCOFINS = ConfigNota::listaCST_PIS_COFINS();
			$listaCSTIPI = ConfigNota::listaCST_IPI();

			$config = ConfigNota::
			where('empresa_id', $this->empresa_id)
			->first();
			$certificado = Certificado::
			where('empresa_id', $this->empresa_id)
			->first();

			$cUF = ConfigNota::estados();

			$infoCertificado = null;
			if($certificado != null){
				$infoCertificado = $this->getInfoCertificado($certificado);
			}

			$soapDesativado = !extension_loaded('soap');

			$cidades = Cidade::all();

			if($config != null){
				$config->graficos_dash = $config->graficos_dash ? json_decode($config->graficos_dash) : [];
			}

			$empresa = Empresa::findOrFail($this->empresa_id);
			$cnpj = $empresa->cnpj;
			$balancasAtivas = BalancaConfig::where('empresa_id', $this->empresa_id)->where('ativo', true)->get();

			return view('configNota/index')
			->with('config', $config)
			->with('cnpj', $cnpj)
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
			->with('balancasAtivas', $balancasAtivas)
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

	public function save(Request $request){
		$this->_validate($request);

		$tentouDesabilitarProdutoReferenciado = false;

		$request->merge([
			'bloquear_pesagem_manual_balanca' => $request->boolean('bloquear_pesagem_manual_balanca'),
			'usar_valores_ticket_pesagem' => $request->boolean('usar_valores_ticket_pesagem'),
			'usa_produto_referenciado_pesagem' => $request->boolean('usa_produto_referenciado_pesagem'),
			'exibir_valores_ticket_pesagem_grid' => $request->boolean('exibir_valores_ticket_pesagem_grid'),
			'desbloquear_campo_peso_bag_ticket' => $request->boolean('desbloquear_campo_peso_bag_ticket'),
			'conectar_automaticamente_balanca_padrao_usuario' => $request->boolean('conectar_automaticamente_balanca_padrao_usuario'),
			'conectar_automaticamente_balanca_ao_selecionar' => $request->boolean('conectar_automaticamente_balanca_ao_selecionar'),
		]);

		if ((int) $request->id > 0) {
			$tentouDesabilitarProdutoReferenciado = !$request->boolean('usa_produto_referenciado_pesagem');
			$configExistente = ConfigNota::where('empresa_id', $this->empresa_id)->first();

			if ($configExistente && (bool) ($configExistente->usa_produto_referenciado_pesagem ?? false)) {
				$request->merge(['usa_produto_referenciado_pesagem' => true]);
			}
		}

		if ($request->bloquear_pesagem_manual_balanca) {
			$validacaoBalanca = $this->validarAtivacaoBalancaPadrao($request);
			if ($validacaoBalanca !== true) {
				session()->flash('mensagem_erro', $validacaoBalanca);
				return redirect()->back()->withInput();
			}
		}
		$uf = $request->uf;

		$nomeImagem = "";

		$empresa = Empresa::find($this->empresa_id);

		$cnpjEmitente = preg_replace('/[^0-9]/', '', $empresa->cnpj);
		$cnpjEmpresa = preg_replace('/[^0-9]/', '', $request->cnpj);
		$value = session('user_logged');
		if($cnpjEmitente != $cnpjEmpresa && !$value['super']){
			session()->flash('mensagem_erro', 'É necessário informar o mesmo CNPJ/CPF do cadastro de empresa!');
			return redirect()->back();
		}

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

		if(!isset($request->graficos_dash)){
			$request->graficos_dash = '[]';
		}else{
			$request->graficos_dash = json_encode($request->graficos_dash);
		}
		if($request->id == 0){

			$result = ConfigNota::create([
				'razao_social' => strtoupper($this->sanitizeString($request->razao_social)),
				'nome_fantasia' => strtoupper($this->sanitizeString($request->nome_fantasia)),
				'cnpj' => $request->cnpj,
				'ie' => $request->ie,
				'logradouro' => strtoupper($this->sanitizeString($request->logradouro)),
				'complemento' => strtoupper($this->sanitizeString($request->complemento)),
				'numero' => strtoupper($this->sanitizeString($request->numero)),
				'bairro' => strtoupper($this->sanitizeString($request->bairro)),
				'cep' => $request->cep,
				'email' => $request->email ?? '',
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
				'busca_documento_automatico' => $request->busca_documento_automatico,
				'frete_padrao' => $request->frete_padrao,
				'tipo_impressao_danfe' => $request->tipo_impressao_danfe,
				'tipo_pagamento_padrao' => $request->tipo_pagamento_padrao,
				'nat_op_padrao' => $request->nat_op_padrao ?? 0,
				'cBenef_padrao' => $request->cBenef_padrao ?? '',
				'validade_orcamento' => $request->validade_orcamento ?? 0,
				'ambiente' => env("APP_ENV") == "demo" ? 2 : $request->ambiente,
				'cUF' => $cUF,
				'ultimo_numero_nfe' => $request->ultimo_numero_nfe,
				'ultimo_numero_nfce' => $request->ultimo_numero_nfce,
				'ultimo_numero_cte' => $request->ultimo_numero_cte ?? 0,
				'ultimo_numero_mdfe' => $request->ultimo_numero_mdfe ?? 0,
				'ultimo_numero_nfse' => $request->ultimo_numero_nfse ?? 0,
				'numero_serie_nfe' => $request->numero_serie_nfe,
				'numero_serie_nfce' => $request->numero_serie_nfce,
				'numero_serie_cte' => $request->numero_serie_cte ?? 0,
				'numero_serie_mdfe' => $request->numero_serie_mdfe ?? 0,
				'numero_serie_nfse' => $request->numero_serie_nfse ?? '0',
				'percentual_max_desconto' => $request->percentual_max_desconto ?? 0,
				'csc' => $request->csc,
				'csc_id' => $request->csc_id,
				'certificado_a3' => $request->certificado_a3 ? true: false,
				'gerenciar_estoque_produto' => $request->gerenciar_estoque_produto,
				'gerenciar_comissao_usuario_logado' => $request->gerenciar_comissao_usuario_logado,
				'empresa_id' => $request->empresa_id,
				'inscricao_municipal' => $request->inscricao_municipal ?? '',
				'alerta_sonoro' => $request->alerta_sonoro ?? '',
				'aut_xml' => $request->aut_xml ?? '',
				'logo' => $nomeImagem,
				'campo_obs_nfe' => $request->campo_obs_nfe ?? '',
				'usar_email_proprio' => $request->usar_email_proprio,
				'campo_obs_pedido' => $request->campo_obs_pedido ?? '',
				'token_ibpt' => $request->token_ibpt ?? '',
				'token_nfse' => $request->token_nfse ?? '',
				'integracao_nfse' => $request->integracao_nfse ?? '',
				'token_whatsapp' => $request->token_whatsapp ?? '',
                'token_sync' => $request->token_sync ?? '',
                'whatsapp_technology' => $request->whatsapp_technology ?? '',
				'codigo_tributacao_municipio' => $request->codigo_tributacao_municipio ?? '',
				'casas_decimais' => $request->casas_decimais,
				'casas_decimais_qtd' => $request->casas_decimais_qtd,
				'sobrescrita_csonn_consumidor_final' => $request->sobrescrita_csonn_consumidor_final ?? '',
				'percentual_lucro_padrao' => $request->percentual_lucro_padrao ?? 0,
				'parcelamento_maximo' => $request->parcelamento_maximo ?? 12,
				'caixa_por_usuario' => $request->caixa_por_usuario,
				'juro_padrao' => $request->juro_padrao ? __replace($request->juro_padrao) : 0,
				'multa_padrao' => $request->multa_padrao ? __replace($request->multa_padrao) : 0,
				'graficos_dash' => $request->graficos_dash,
				'senha_remover' => trim($request->senha_remover) != '' ? md5($request->senha_remover) : '',
				'bloquear_pesagem_manual_balanca' => $request->bloquear_pesagem_manual_balanca,
				'usar_valores_ticket_pesagem' => $request->usar_valores_ticket_pesagem,
				'usa_produto_referenciado_pesagem' => $request->usa_produto_referenciado_pesagem,
				'exibir_valores_ticket_pesagem_grid' => $request->exibir_valores_ticket_pesagem_grid,
				'desbloquear_campo_peso_bag_ticket' => $request->desbloquear_campo_peso_bag_ticket,
				'conectar_automaticamente_balanca_padrao_usuario' => $request->conectar_automaticamente_balanca_padrao_usuario,
				'conectar_automaticamente_balanca_ao_selecionar' => $request->conectar_automaticamente_balanca_ao_selecionar,
			]);
		}else{
			$config = ConfigNota::
			where('empresa_id', $this->empresa_id)
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
			$config->email = $request->email ?? '';
			$config->alerta_sonoro = $request->alerta_sonoro ?? '';

			$config->CST_CSOSN_padrao = $request->CST_CSOSN_padrao;
			$config->CST_COFINS_padrao = $request->CST_COFINS_padrao;
			$config->CST_PIS_padrao = $request->CST_PIS_padrao;
			$config->CST_IPI_padrao = $request->CST_IPI_padrao;
			$config->cBenef_padrao = $request->cBenef_padrao ?? '';
			$config->busca_documento_automatico = $request->busca_documento_automatico;

			$config->frete_padrao = $request->frete_padrao;
			$config->tipo_pagamento_padrao = $request->tipo_pagamento_padrao;
			$config->nat_op_padrao = $request->nat_op_padrao ?? 0;
			$config->percentual_lucro_padrao = $request->percentual_lucro_padrao ?? 0;
			$config->ambiente = env("APP_ENV") == "demo" ? 2 : $request->ambiente;
			$config->caixa_por_usuario = $request->caixa_por_usuario;
			$config->cUF = $cUF;
			$config->ultimo_numero_nfe = $request->ultimo_numero_nfe;
			$config->ultimo_numero_nfce = $request->ultimo_numero_nfce;
			$config->tipo_impressao_danfe = $request->tipo_impressao_danfe;
			$config->ultimo_numero_cte = $request->ultimo_numero_cte ?? 0;
			$config->ultimo_numero_nfse = $request->ultimo_numero_nfse ?? 0;
			$config->parcelamento_maximo = $request->parcelamento_maximo ?? 0;
			$config->ultimo_numero_mdfe = $request->ultimo_numero_mdfe ?? 0;
			$config->validade_orcamento = $request->validade_orcamento ?? 0;
			$config->numero_serie_nfe = $request->numero_serie_nfe;
			$config->numero_serie_nfce = $request->numero_serie_nfce;
			$config->numero_serie_cte = $request->numero_serie_cte ?? 0;
			$config->numero_serie_mdfe = $request->numero_serie_mdfe ?? 0;
			$config->numero_serie_nfse = $request->numero_serie_nfse ?? '0';
			$config->juro_padrao = $request->juro_padrao ? __replace($request->juro_padrao) : 0;
			$config->multa_padrao = $request->multa_padrao ? __replace($request->multa_padrao) : 0;
			$config->percentual_max_desconto = $request->percentual_max_desconto ?? 0;
			$config->csc = $request->csc;
			$config->csc_id = $request->csc_id;
			$config->campo_obs_nfe = $request->campo_obs_nfe ?? '';
			$config->campo_obs_pedido = $request->campo_obs_pedido ?? '';
			$config->token_ibpt = $request->token_ibpt ?? '';
			$config->token_nfse = $request->token_nfse ?? '';
			$config->integracao_nfse = $request->integracao_nfse ?? '';
			$config->token_whatsapp = $request->token_whatsapp ?? '';
            $config->token_sync = $request->token_sync ?? '';
            $config->whatsapp_technology = $request->whatsapp_technology ?? '';
			$config->codigo_tributacao_municipio = $request->codigo_tributacao_municipio ?? '';
			$config->complemento = $request->complemento ?? '';
			$config->sobrescrita_csonn_consumidor_final = $request->sobrescrita_csonn_consumidor_final ?? '';
			if(trim($request->senha_remover) != ""){
				$config->senha_remover = md5($request->senha_remover);
			}
			$config->casas_decimais = $request->casas_decimais;
			$config->casas_decimais_qtd = $request->casas_decimais_qtd;
			$config->certificado_a3 = $request->certificado_a3 ? true : false;
			$config->gerenciar_estoque_produto = $request->gerenciar_estoque_produto;
			$config->gerenciar_comissao_usuario_logado = $request->gerenciar_comissao_usuario_logado;
			$config->usar_email_proprio = $request->usar_email_proprio;
			$config->graficos_dash = $request->graficos_dash;
			$config->bloquear_pesagem_manual_balanca = $request->bloquear_pesagem_manual_balanca;
			$config->usar_valores_ticket_pesagem = $request->usar_valores_ticket_pesagem;
			$config->usa_produto_referenciado_pesagem = $request->usa_produto_referenciado_pesagem;
			$config->exibir_valores_ticket_pesagem_grid = $request->exibir_valores_ticket_pesagem_grid;
			$config->desbloquear_campo_peso_bag_ticket = $request->desbloquear_campo_peso_bag_ticket;
			$config->conectar_automaticamente_balanca_padrao_usuario = $request->conectar_automaticamente_balanca_padrao_usuario;
			$config->conectar_automaticamente_balanca_ao_selecionar = $request->conectar_automaticamente_balanca_ao_selecionar;

			$config->inscricao_municipal = $request->inscricao_municipal ?? '';
			$config->aut_xml = $request->aut_xml ?? '';
			if($request->hasFile('file')){
				$config->logo = $nomeImagem;
			}

			$result = $config->save();

			if ($result && (bool) ($config->usa_produto_referenciado_pesagem ?? false) && $tentouDesabilitarProdutoReferenciado) {
				session()->flash('mensagem_erro', 'A configuração de produto referenciado da pesagem já está ativa e permanece bloqueada para desativação.');
			}
		}

		$value = session('user_logged');

		$value['ambiente'] = $request->ambiente == 1 ? 'Produção' : 'Homologação';

		session()->put('user_logged', $value);

		if($result){
			session()->flash("mensagem_sucesso", "Configurado com sucesso!");
		}else{
			session()->flash('mensagem_erro', 'Erro ao configurar!');
		}
		return redirect('/configNF');
	}



	private function validarAtivacaoBalancaPadrao(Request $request)
	{
		$balancasAtivas = BalancaConfig::where('empresa_id', $this->empresa_id)
			->where('ativo', true)
			->count();

		if ($balancasAtivas <= 0) {
			return 'Não é possível ativar o bloqueio de pesagem manual sem balança cadastrada/ativa.';
		}

		return true;
	}

	private function _validate(Request $request){
		$rules = [
			'razao_social' => 'required|max:60',
			'nome_fantasia' => 'required|max:60',
			'cnpj' => 'required',
			'ie' => 'required',
			'logradouro' => 'required|max:80',
			'numero' => 'required|max:10',
			'bairro' => 'required|max:50',
			'fone' => 'required|max:20',
			'email' => 'max:60',
			'cep' => 'required',
			'file' => 'max:300',
			// 'municipio' => 'required',
			// 'codMun' => 'required',
			// 'uf' => 'required|max:2|min:2',
			'ultimo_numero_nfe' => 'required',
			'ultimo_numero_nfce' => 'required',
			// 'ultimo_numero_cte' => 'required',
			// 'ultimo_numero_mdfe' => 'required',
			'numero_serie_nfe' => 'required|max:3',
			'numero_serie_nfce' => 'required|max:3',
			// 'numero_serie_cte' => 'required|max:3',
			// 'numero_serie_mdfe' => 'required|max:3',
			'csc' => 'required',
			'csc_id' => 'required',
			// 'file' => 'max:2000',
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
			'numero_serie_mdfe.required' => 'Campo obrigatório.',
			'numero_serie_mdfe.max' => 'Maximo de 3 Digitos.',
			'csc.required' => 'O CSC é obrigatório.',
			'csc_id.required' => 'O CSCID é obrigatório.',
			'file.max' => 'Upload de até 300KB.',
			'email.required' => 'Campo obrigatório.',
			'email.max' => 'Máximo de 60caracteres.',
			'email.email' => 'Email inválido.',
		];

		$this->validate($request, $rules, $messages);
	}

	public function certificado(){
		$escritorio = EscritorioContabil::
		where('empresa_id', $this->empresa_id)
		->first();
		return view('configNota/upload', compact('escritorio'))
		->with('title', 'Upload de Certificado');
	}

	public function download(){
		$certificado = Certificado::
		where('empresa_id', $this->empresa_id)
		->first();
		// echo "Senha: " . $certificado->senha;
		try{
			safe_file_put_contents(public_path('cd.bin'), $certificado->arquivo);
			return response()->download(public_path('cd.bin'));
		}catch(\Exception $e){
			echo $e->getMessage();
		}
	}

	public function senha(){
		$certificado = Certificado::
		where('empresa_id', $this->empresa_id)
		->first();
		echo "Senha: " . $certificado->senha;

	}

	public function saveCertificado(Request $request){

		if($request->hasFile('file') && strlen($request->senha) > 0){

			$enviarCertificado = $request->enviar_certificado_contabilidade;

			$file = $request->file('file');
			$temp = safe_file_get_contents($file);

			$extensao = $file->getClientOriginalExtension();

			$config = ConfigNota::
			where('empresa_id', $request->empresa_id)
			->first();

			$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

			$fileName = "$cnpj.$extensao";

			$file->move(public_path('certificados'), $fileName);

			$res = Certificado::create([
				'senha' => $request->senha,
				'arquivo' => $temp,
				'file_name' => $fileName,
				'empresa_id' => $request->empresa_id
			]);

			if($enviarCertificado){
				$this->enviarCertificadoEmail($fileName, $request->senha);
			}

			if($res){
				session()->flash("mensagem_sucesso", "Upload de certificado realizado!");
				return redirect('/configNF');
			}
		}else{
			session()->flash("mensagem_erro", "Envie o arquivo e senha por favor!");
			return redirect('/configNF/certificado');
		}
	}

	public function enviarCertificado(){
		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

		$files = array_diff(scandir(public_path('certificados')), array('.', '..'));
		$certificados = [];
		foreach ($files as $file) {
			$name_file = explode(".", $file);
			if($name_file[0] == $cnpj){
				array_push($certificados, $file);
			}
		}

		// if(file_exists(public_path('certificados/').$cnpj. '.p12')){
		// 	$fileName = $cnpj. '.p12';
		// }
		// elseif(file_exists(public_path('certificados/').$cnpj. '.pfx')){
		// 	$fileName = $cnpj. '.pfx';
		// }
		// elseif(file_exists(public_path('certificados/').$cnpj. '.bin')){
		// 	$fileName = $cnpj. '.bin';
		// }else{
		// 	echo "Nenhum arquivo encontrado!";
		// 	die;
		// }

		$certificado = Certificado::
		where('empresa_id', $this->empresa_id)
		->first();

		$fileName = $certificado->file_name;

		if($fileName == ''){
			session()->flash("mensagem_erro", "Certificado sem caminho definido, faça o upload novamente!");
			return redirect()->back();
		}

		$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

		$this->enviarCertificadoEmail($fileName, $certificado->senha);
		session()->flash("mensagem_sucesso", "Certificado enviado!");
		return redirect()->back();
	}

	private function enviarCertificadoEmail($fileName, $senha){
		$empresa = Empresa::findOrFail($this->empresa_id);
		$escritorio = EscritorioContabil::
		where('empresa_id', $this->empresa_id)
		->first();
		if($escritorio == null){
			session()->flash("mensagem_erro", "Escritório não configurado!");
			return redirect()->back();
		}
		$email = $escritorio->email;
		Mail::send('mail.certificado', ['senha' => $senha, 'empresa' => $empresa->nome], function($m) use ($email, $fileName){

			$nomeEmpresa = env('MAIL_NAME');
			$nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
			$nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
			$emailEnvio = env('MAIL_USERNAME');

			$m->from($emailEnvio, $nomeEmpresa);
			$m->subject("Envio de Certificado");
			$m->attach(public_path('certificados/').$fileName);

			$m->to($email);
		});
	}

	public function deleteCertificado(){
		Certificado::
		where('empresa_id', $this->empresa_id)
		->delete();
		session()->flash("mensagem_sucesso", "Certificado Removido!");
		return redirect('configNF');
	}

	public function teste(){

		$config = CashBackConfig::where('empresa_id', $this->empresa_id)
		->first();

		$cashBackCliente = CashBackCliente::first();
		$number = $cashBackCliente->cliente->celular;
		$number = preg_replace('/[^0-9]/', '', $cashBackCliente->cliente->celular);
		$message = $config->mensagem_padrao_whatsapp;

		$message = str_replace("{credito}", moeda($cashBackCliente->valor_credito), $message);
		$message = str_replace("{expiracao}", __date($cashBackCliente->data_expiracao, 0), $message);
		$message = str_replace("{nome}", $cashBackCliente->cliente->razao_social, $message);

		$configNota = ConfigNota::where('empresa_id', $this->empresa_id)
		->first();

		$nodeurl = 'https://api.criarwhats.com/send';

		$data = [
			'receiver'  => '55'.$number,
			'msgtext'   => $message,
			'token'     => $configNota->token_whatsapp,
		];

        // 'mediaurl'  => $mediaurl

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_URL, $nodeurl);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$response = curl_exec($ch);
		curl_close($ch);

		echo $response;

	}

	public function testeEmail(){

		$mailDriver = env("MAIL_HOST");
		$mailHost = env("MAIL_DRIVER");
		$mailPort = env("MAIL_PORT");
		$mailUsername = env("MAIL_USERNAME");
		$mailPass = env("MAIL_PASSWORD");
		$mailCpt = env("MAIL_ENCRYPTION");
		$mailName = env("MAIL_NAME");

		if($mailDriver == '') return response()->json("Configure no .env MAIL_HOST", 403);
		if($mailHost == '') return response()->json("Configure no .env MAIL_DRIVER", 403);
		if($mailPort == '') return response()->json("Configure no .env MAIL_PORT", 403);
		if($mailUsername == '') return response()->json("Configure no .env MAIL_USERNAME", 403);
		if($mailPass == '') return response()->json("Configure no .env MAIL_PASSWORD", 403);
		if($mailCpt == '') return response()->json("Configure no .env MAIL_ENCRYPTION", 403);
		if($mailName == '') return response()->json("Configure no .env MAIL_NAME", 403);

		try{
			Mail::send('mail.teste', [], function($m){
				$nomeEmail = env("MAIL_NAME");
				$mail = env("MAIL_USERNAME");
				$nomeEmail = str_replace("_", " ", $nomeEmail);
				$m->from(env('MAIL_USERNAME'), $nomeEmail);
				$m->subject('Teste de email');
				$m->to($mail);
			});
		}catch(\Exception $e){
			return response()->json($e->getMessage(), 403);
		}

	}

	public function removeLogo($id){
		$config = ConfigNota::find($id);

		if($config->logo != ''){
			if(file_exists(public_path('logos/').$config->logo)){
				unlink(public_path('logos/').$config->logo);
			}
		}
		$config->logo = '';
		$config->save();
		session()->flash("mensagem_sucesso", "Logo removida!");
		return redirect('/configNF');
	}

	public function removeSenha($id){
		$config = ConfigNota::find($id);

		$config->senha_remover = '';
		$config->save();
		session()->flash("mensagem_sucesso", "Senha removida!");
		return redirect('/configNF');
	}

	public function verificaSenha(Request $request){

		$config = ConfigNota::
		where('senha_remover', md5($request->senha))
		->where('empresa_id', $this->empresa_id)
		->first();

		if($config != null){
			return response()->json("ok", 200);
		}else{
			return response()->json("", 401);
		}
	}

	public function verificaSenhaAcesso(){
		try{
			$config = ConfigNota::
			where('empresa_id', $this->empresa_id)
			->first();

			if($config->senha_remover != null && $config->senha_remover != ''){
				return response()->json("sim", 200);
			}
			return response()->json(null, 404);
		}catch(\Exception $e){
			return response()->json($e->getMessage(), 401);
		}
	}

}
