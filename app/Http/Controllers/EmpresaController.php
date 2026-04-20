<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empresa;
use App\Models\Contador;
use App\Models\EmpresaLogada;
use App\Models\ConfigNota;
use App\Models\RepresentanteEmpresa;
use App\Models\FinanceiroRepresentante;
use App\Models\Usuario;
use App\Models\Representante;
use App\Models\AberturaCaixa;
use App\Models\CategoriaConta;
use App\Models\FormaPagamento;
use App\Models\Plano;
use App\Models\PlanoEmpresa;
use App\Models\UsuarioAcesso;
use App\Models\CancelamentoLicenca;
use App\Helpers\Menu;
use Dompdf\Dompdf;
use App\Models\Contrato;
use App\Models\ConfigCaixa;
use App\Models\Servico;
use App\Models\EmpresaContrato;
use App\Models\PerfilAcesso;
use App\Models\Certificado;
use App\Models\Venda;
use App\Models\Cte;
use App\Models\Mdfe;
use App\Models\Devolucao;
use App\Models\VendaCaixa;
use App\Models\Compra;
use App\Models\ItemVendaCaixa;
use App\Models\Produto;
use App\Models\Payment;
use App\Models\Cidade;
use NFePHP\Common\Certificate;
use App\Models\NaturezaOperacao;
use App\Models\Tributacao;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\EscritorioContabil;
use Illuminate\Support\Str;
use App\Models\Orcamento;
use App\Models\FaturaFrenteCaixa;
use App\Models\PlanoConta;
use App\Utils\PlanoContaUtil;
use Mail;

class EmpresaController extends Controller
{
	protected $util;

	public function __construct(PlanoContaUtil $util){
		$this->util = $util;
		$this->middleware(function ($request, $next) {
			$value = session('user_logged');
			if(!$value){
				return redirect("/login");
			}

			if(!$value['super']){
				return redirect('/graficos');
			}
			return $next($request);
		});
	}

	public function desativadas(){
		$empresas = Empresa::where('status', 0)->get();
		echo "Total de registros: <span style='color: red'>" .sizeof($empresas) . "</span><br><br>Todas as empresas desativadas<br>";

		$cont = 0;
		foreach($empresas as $e){
			$ultimoAcesso = $e->ultimoLogin2($e->id);
			$dif = null;
			if($ultimoAcesso != null){
				$dif = strtotime(date("Y-m-d")) - strtotime($ultimoAcesso->created_at);
			}
			echo $e->nome . " - ultimo acesso: <span style='color: red'>" . ($ultimoAcesso != null ? __date($ultimoAcesso->created_at) : 'nenhum acesso') . "</span>, dias sem acesso : " . ($dif != null ? "<span style='color: purple'>" . (int)($dif/86400) . " dias</span>" : "<span style='color: purple'>*</span>") . "<br>";

			if($ultimoAcesso != null){
				if((int)($dif/86400) > 180) $cont++;
			}else{
				$cont++;
			}
		}

		echo "<br><br>Total de empresa com mais de 180 dias sem acesso: <span style='color: red'>" . $cont . "</span><br><br>";

	}

	public function buscarEmpresas(Request $request){
		$data = Empresa::where('nome', 'like', "%$request->pesquisa%")
		->orWhere('nome_fantasia', 'like', "%$request->pesquisa%")
		->get();

		return response()->json($data, 200);
	}

	public function online(){
		$empresas = Empresa::
		orderBy('id', 'desc')
		->get();

		$minutos = env("MINUTOS_ONLINE");
		$online = [];

		foreach($empresas as $e){
			$ult = $e->ultimoLogin2($e->id);
			if($ult != null){
				$strValidade = strtotime($ult->updated_at);
				$strHoje = strtotime(date('Y-m-d H:i:s'));
				$dif = $strHoje - $strValidade;
				$dif = $dif/60;

				if((int) $dif <= $minutos && $e->usuarios[0]->login != env("USERMASTER")){
					array_push($online, $e);
				}
			}
		}

		return view('empresas/online')
		->with('empresas', $online)
		->with('title', 'Empresas Online');
	}

	public function alterarStatus($empresa_id){
		$empresa = Empresa::find($empresa_id);

		$empresa->status = !$empresa->status;
		$empresa->save();

		if($empresa->status){
			session()->flash("mensagem_sucesso", "Empresa habilitada!");
		}else{
			// session()->flash("mensagem_erro", "Empresa desabilitada!");

			return redirect('/empresas/mensagemBloqueio/'.$empresa_id);
		}

		return redirect()->back();

	}

	public function mensagemBloqueio($empresa_id){
		$empresa = Empresa::find($empresa_id);

		return view('empresas/mensagem_bloqueio')
		->with('empresa', $empresa)
		->with('title', 'Mensagem de bloqueio');

	}

	public function salvarMensagemBloqueio(Request $request){
		$empresa = Empresa::find($request->id);

		$empresa->mensagem_bloqueio = $request->mensgem ?? '';
		$empresa->save();
		session()->flash("mensagem_erro", "Empresa desabilitada!");
		return redirect('/empresas');
	}

	private function altDebug()
	{
		if(env("APP_DEBUG") == 'false'){
			safe_file_put_contents(app()->environmentFilePath(), str_replace(
				"APP_DEBUG=false",
				"APP_DEBUG=true",
				safe_file_get_contents(app()->environmentFilePath())
			));
		}else{
			safe_file_put_contents(app()->environmentFilePath(), str_replace(
				"APP_DEBUG=true",
				"APP_DEBUG=false",
				safe_file_get_contents(app()->environmentFilePath())
			));
		}
	}

	public function alteraDebug(){
		$this->altDebug();
		if(env("APP_DEBUG") != 'false'){
			echo "debug desativado <a href='/empresas'>retornar para empresas</a>";
		}else{
			echo "debug ativado <a href='/empresas'>retornar para empresas</a>";
		}
		// sleep(2);
		// return redirect('empresas');
	}

	public function index(){

		$empresas = Empresa::
		orderBy('id', 'desc')
		->where('tipo_representante', 0)
		->where('tipo_contador', 0)
		->paginate(30);

		foreach($empresas as $e){
			if($e->planoEmpresa){
				$expiracao = $e->planoEmpresa->expiracao;

				$strValidade = strtotime($expiracao);
				$strHoje = strtotime(date('Y-m-d'));
				$dif = $strValidade - $strHoje;
				$dif = $dif/24/60/60;

				$e->tempo_expira = $dif;
			}
		}
		$planos = Plano::all();

		return view('empresas/list')
		->with('empresas', $empresas)
		->with('planos', $planos)
		->with('links', 1)
		->with('title', 'SUPER');
	}

	public function filtro(Request $request){
		$tipo = $request->tipo;
		$emp_id = $request->emp_id;

		$emp = null;
		if($emp_id){
			$emp = Empresa::findOrFail($emp_id);
		}

		$empresas = Empresa::orderBy('nome', 'asc')
		->when($emp_id, function ($q) use ($emp_id) {
			return $q->where('id', $emp_id);
		});
		// if($request->nome){
		// 	$empresas->where('nome', 'LIKE', "%$request->nome%");
		// }

		// if($request->nome_fantasia){
		// 	$empresas->where('nome_fantasia', 'LIKE', "%$request->nome_fantasia%");
		// }

		if($request->cpf_cnpj){
			$empresas->where('cnpj', $request->cpf_cnpj);
		}

		if($tipo == 'empresa'){
			$empresas->where('tipo_representante', 0)
			->where('tipo_contador', 0);
		}else if($tipo == 'contador'){
			$empresas->where('tipo_representante', 0)
			->where('tipo_contador', 1);
		}else if($tipo == 'representante'){
			$empresas->where('tipo_representante', 1)
			->where('tipo_contador', 0);
		}

		$empresas = $empresas->get();

		foreach($empresas as $e){
			if($e->planoEmpresa){
				$expiracao = $e->planoEmpresa->expiracao;

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
				if($e->status() == $request->status){
					array_push($temp, $e);
				}

				if($request->status == 2){
					if(!$e->planoEmpresa){
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
				if($e->planoEmpresa){
					$exp = $e->planoEmpresa->expiracao;
					$dif = strtotime($exp) - strtotime($hoje);
					$planoExpiradoDias = $dif/60/60/24;

					if($request->dias_expirar == $planoExpiradoDias){
						array_push($temp, $e);
					}
				}
			}
			$empresas = $temp;
		}

		if($request->plano != 'TODOS'){
			$temp = [];
			$hoje = date('Y-m-d');

			foreach($empresas as $e){
				if($e->planoEmpresa){

					if($e->planoEmpresa->plano_id == $request->plano){
						array_push($temp, $e);
					}
				}
			}
			$empresas = $temp;
		}

		$planos = Plano::all();

		return view('empresas/list')
		->with('empresas', $empresas)
		->with('tipo', $tipo)
		->with('emp', $emp)
		->with('planos', $planos)
		->with('status', $request->status)
		->with('plano', $request->plano)
		->with('dias_expirar', $request->dias_expirar)
		->with('nome', $request->nome)
		->with('nome_fantasia', $request->nome_fantasia)
		->with('cpf_cnpj', $request->cpf_cnpj)
		->with('filtro', true)
		->with('paraImprimir', true)
		->with('title', 'SUPER');
	}

	public function relatorio(Request $request){
		$tipo = $request->tipo;
		$emp_id = $request->emp_id;

		$empresas = Empresa::
		when($emp_id, function ($q) use ($emp_id) {
			return $q->where('id', $emp_id);
		});
		// where('nome', 'LIKE', "%$request->nome%")
		// ->where('nome_fantasia', 'LIKE', "%$request->nome_fantasia%");

		if($request->cpf_cnpj){
			$empresas->where('cnpj', $request->cpf_cnpj);
		}
		if($tipo == 'empresa'){
			$empresas->where('tipo_representante', 0)
			->where('tipo_contador', 0);
		}else if($tipo == 'contador'){
			$empresas->where('tipo_representante', 0)
			->where('tipo_contador', 1);
		}else if($tipo == 'representante'){
			$empresas->where('tipo_representante', 1)
			->where('tipo_contador', 0);
		}
		$empresas = $empresas->get();

		if($request->status != 'TODOS'){
			$temp = [];
			foreach($empresas as $e){
				if($e->status() == $request->status){
					array_push($temp, $e);
				}
			}
			$empresas = $temp;
		}

		if(sizeof($empresas) == 0){
			session()->flash("mensagem_erro", "Relatório sem resultados!");
			return redirect()->back();
		}
		$p = view('empresas/relatorio')
		->with('title', 'Relatório de empresas')
		->with('empresas', $empresas);
		// return $p;

		$domPdf = new Dompdf(["enable_remote" => true]);
		$domPdf->loadHtml($p);

		$pdf = ob_get_clean();

		$domPdf->setPaper("A4", "");
		$domPdf->render();
		$domPdf->stream("Relatório de empresas.pdf", array("Attachment" => false));
	}

	public function nova(){
		$perfis = PerfilAcesso::all();

		$contadores = Contador::all();
		return view('empresas/register')
		->with('empresaJs', true)
		->with('contadores', $contadores)
		->with('perfis', $perfis)
		->with('title', 'SUPER');
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
				// echo $s['rota'] . "<br>";

				if(isset($arr[$s['rota']])){
					array_push($temp, $s['rota']);
				}

				if(strlen($s['rota']) > 60){
					$rt = str_replace(".", "_", $s['rota']);
					// $rt = str_replace(":", "_", $s['rota']);
					// echo $rt . "<br>";


					foreach($arr as $key => $a){
						if($key == $rt){
							array_push($temp, $rt);
						}
					}
				}
			}
		}

		return $temp;
	}

	public function save(Request $request){
		$permissao = $this->validaPermissao($request);

		$perfilId = 0;

		if(isset($request->perfil_id) && $request->perfil_id != '0'){
			$tp = json_decode($request->perfil_id);
			$perfilId = $tp->id;
		}

		$this->_validate($request);
		$data = [
			'nome' => $request->nome,
			'nome_fantasia' => $request->nome_fantasia ?? '',
			'rua' => $request->rua,
			'numero' => $request->numero,
			'bairro' => $request->bairro,
			'cidade' => $request->cidade,
			'telefone' => $request->telefone,
			'email' => $request->email ?? '',
			'info_contador' => $request->info_contador ?? '',
			'cnpj' => $request->cnpj,
			'perfil_id' => $perfilId,
			'contador_id' => $request->contador_id ?? 0,
			'status' => 1,
			'tipo_representante' => $request->tipo_representante ? true : false,
			'permissao' => json_encode($permissao),

			'uf' => $request->uf ?? '',
			'cep' => $request->cep ?? '',
			'representante_legal' => $request->representante_legalrepresentante_legal ?? '',
			'cpf_representante_legal' => $request->cpf_representante_legal ?? '',
		];

		$empresa = Empresa::create($data);
		if($empresa){

			$data = [
				'nome' => $request->nome_usuario,
				'senha' => md5($request->senha),
				'login' => $request->login,
				'adm' => 1,
				'ativo' => 1,
				'permissao' => json_encode($permissao),
				'img' => '',
				'empresa_id' => $empresa->id,
				'menu_representante' => $request->tipo_representante ? 1 : 0,
				'permite_desconto' => 1
			];
			$usuario = Usuario::create($data);

			if($request->tipo_representante){

				Representante::create(
					[
						'nome' => $request->nome_usuario,
						'rua' => $request->rua,
						'telefone' => $request->telefone,
						'email' => $request->email ?? '',
						'numero' => $request->numero,
						'bairro' => $request->bairro,
						'cidade' => $request->cidade,
						'cpf_cnpj' => $request->cnpj,
						'comissao' => __replace($request->comissao),
						'usuario_id' => $usuario->id,
						'acesso_xml' => $request->acesso_xml ? true : false,
						'bloquear_empresa' => $request->bloquear_empresa ? true : false,
						'limite_cadastros' => $request->limite_cadastros ?? 0
					]
				);
			}

			$this->criaConfigCaixa($usuario);
			$this->criaCategoriasConta($empresa->id);
			$this->criaFormasDePagamento($empresa->id);

			if($empresa->escritorio == null && $request->contador_id > 0){
				// $this->insertEscritorio($request->contador_id, $empresa->id);
			}

			$contrato = $this->gerarContrato($empresa->id);

			try{
				$this->enviaEmailCadastro($request->nome_usuario, $request->login, $request->senha, $request->email);
			}catch(\Exception $e){
			}

			if(env("HERDAR_DADOS_SUPER") == 1){
				//adiciona categoria, tributação, natureza e produto do super
				$this->herdaSuper($empresa);
			}
			session()->flash("mensagem_sucesso", "Empresa cadastrada!");
			return redirect('/empresas');

		}

	}

	private function enviaEmailCadastro($nome, $login, $senha, $email){
		if($email != ''){
			try{
				Mail::send('mail.novo_suario', ['nome' => $nome, 'login' => $login, 'senha' => $senha],
					function($m) use ($email){
						$nomeEmail = env('MAIL_NAME');
						$nomeEmail = str_replace("_", " ", $nomeEmail);
						$m->from(env('MAIL_USERNAME'), $nomeEmail);
						$m->subject('Acesso ' . env("APP_NAME"));
						$m->to($email);
					});
				return 1;
			}catch(\Exception $e){
				// echo $e->getMessage();
				return 1;
				// die;
			}
		}
	}

	private function insertEscritorio($contador_id, $empresa_id){

		$contador = Contador::find($contador_id);
		EscritorioContabil::create([
			'razao_social' => $contador->razao_social,
			'nome_fantasia' => $contador->nome_fantasia,
			'cnpj' => $contador->cnpj,
			'ie' => $contador->ie,
			'fone' => $contador->fone,
			'logradouro' => $contador->logradouro,
			'numero' => $contador->numero,
			'bairro' => $contador->bairro,
			'cep' => $contador->cep,
			'email' => $contador->email,
			'envio_automatico_xml_contador' => false,
			'token_sieg' => '',
			'empresa_id' => $empresa_id
		]);
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

	private function _validate(Request $request){
		$rules = [
			'nome' => 'required',
			'cnpj' => 'required|unique:empresas',
			'rua' => 'required',
			'numero' => 'required',
			'bairro' => 'required',
			'cidade' => 'required',
			'login' => 'required|unique:usuarios',
			'senha' => 'required',
			'telefone' => 'required',
			'nome_usuario' => 'required',
			'comissao' => $request->tipo_representante ? 'required' : '',
			'limite_cadastros' => $request->limite_cadastros ? 'required' : '',
		];

		$messages = [
			'nome.required' => 'Campo obrigatório.',
			'cnpj.required' => 'Campo obrigatório.',
			'rua.required' => 'Campo obrigatório.',
			'cnpj.unique' => 'Documento já cadastrado em nosso sistema.',
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
			'limite_cadastros.required' => 'Informe o limite.',

		];

		$this->validate($request, $rules, $messages);
	}

	public function verDelete($id){
		if(env("APP_ENV") == "demo"){
			session()->flash("mensagem_erro", "Esta tela não é acessível em modo demonstração!");
			return redirect('/empresas');
		}
		$empresa = Empresa::find($id);

		return view('empresas/ver_delete')
		->with('empresa', $empresa)
		->with('title', 'Remover empresa');
	}

	public function delete($id){

		Venda::
		where('empresa_id', $id)
		->delete();

		$compras = Compra::
		where('empresa_id', $id)
		->get();

		foreach($compras as $c){
			foreach($c->itens as $i){
				$i->delete();
			}
			$c->delete();
		}

		\App\Models\ContaReceber::
		where('empresa_id', $id)
		->delete();

		\App\Models\ContaPagar::
		where('empresa_id', $id)
		->delete();

		VendaCaixa::
		where('empresa_id', $id)
		->delete();

		AberturaCaixa::
		where('empresa_id', $id)
		->delete();

		\App\Models\TrocaVenda::
		where('empresa_id', $id)
		->delete();

		\App\Models\ItemPedido::
		select('item_pedidos.*')
		->join('pedidos', 'pedidos.id', '=', 'item_pedidos.pedido_id')
		->where('pedidos.empresa_id', $id)
		->delete();

		\App\Models\ProdutoOs::
		select('produto_os.*')
		->join('ordem_servicos', 'ordem_servicos.id', '=', 'produto_os.ordem_servico_id')
		->where('ordem_servicos.empresa_id', $id)
		->delete();

		\App\Models\ItemInventario::
		select('item_inventarios.*')
		->join('inventarios', 'inventarios.id', '=', 'item_inventarios.inventario_id')
		->where('inventarios.empresa_id', $id)
		->delete();

		\App\Models\Inventario::
		where('empresa_id', $id)
		->delete();

		\App\Models\ItemRemessaNfe::
		select('item_remessa_nves.*')
		->join('remessa_nves', 'remessa_nves.id', '=', 'item_remessa_nves.remessa_id')
		->where('remessa_nves.empresa_id', $id)
		->delete();

		\App\Models\ItemVendaBalcao::
		select('item_venda_balcaos.*')
		->join('venda_balcaos', 'venda_balcaos.id', '=', 'item_venda_balcaos.venda_balcao_id')
		->where('venda_balcaos.empresa_id', $id)
		->delete();

		$prevendas = \App\Models\VendaCaixaPreVenda::
		where('empresa_id', $id)
		->get();

		foreach($prevendas as $v){
			$v->itens()->delete();
			$v->delete();
		}

		\App\Models\TrocaVendaItem::
		select('troca_venda_items.*')
		->join('troca_vendas', 'troca_vendas.id', '=', 'troca_venda_items.troca_id')
		->where('troca_vendas.empresa_id', $id)
		->delete();

		\App\Models\NuvemShopItemPedido::
		select('nuvem_shop_item_pedidos.*')
		->join('nuvem_shop_pedidos', 'nuvem_shop_pedidos.id', '=', 'nuvem_shop_item_pedidos.pedido_id')
		->where('nuvem_shop_pedidos.empresa_id', $id)
		->delete();

		\App\Models\ServicoOs::
		select('servico_os.*')
		->join('ordem_servicos', 'ordem_servicos.id', '=', 'servico_os.ordem_servico_id')
		->where('ordem_servicos.empresa_id', $id)
		->delete();

		\App\Models\FuncionarioOs::
		select('funcionario_os.*')
		->join('ordem_servicos', 'ordem_servicos.id', '=', 'funcionario_os.ordem_servico_id')
		->where('ordem_servicos.empresa_id', $id)
		->delete();

		$usuarios = Usuario::
		where('empresa_id', $id)
		->get();

		foreach($usuarios as $u){
			ConfigCaixa::where('usuario_id', $u->id)->delete();
		}

		Orcamento::
		where('empresa_id', $id)
		->delete();

		\App\Models\RemessaNfe::
		where('empresa_id', $id)
		->delete();

		\App\Models\Pedido::
		where('empresa_id', $id)
		->delete();

		\App\Models\VendaBalcao::
		where('empresa_id', $id)
		->delete();

		Produto::
		where('empresa_id', $id)
		->delete();

		Servico::
		where('empresa_id', $id)
		->delete();

		$empresa = Empresa::find($id);

		if($empresa != null){
			$empresa->delete();
		}
		session()->flash("mensagem_sucesso", "Empresa removida!");
		return redirect('/empresas');
	}

	public function detalhes($id){

		if(env("APP_ENV") == "demo"){
			session()->flash("mensagem_erro", "Esta tela não é acessível em modo demonstração!");
			return redirect('/empresas');
		}

		$empresa = Empresa::find($id);
		$hoje = date('Y-m-d');
		$planoExpirado = false;

		$permissoesAtivas = $empresa->permissao;
		// print_r($permissoesAtivas);
		// die;
		//$permissoesAtivas = json_decode($permissoesAtivas);
        $permissoesAtivas = json_decode($permissoesAtivas, true) ?? [];

        if($empresa->planoEmpresa){
			$exp = $empresa->planoEmpresa->expiracao;
			if(strtotime($hoje) > strtotime($exp)){
				$planoExpirado = true;
			}
		}

		$value = session('user_logged');

		if($value['super'] && $value['id'] == $id){
			$permissoesAtivas = $this->detalhesMaster();
		}

		$perfis = PerfilAcesso::all();
		$contadores = Contador::all();
		$representantes = Representante::orderBy('nome', 'asc')->get();

		$contador = null;
		if($empresa->tipo_contador == 1){
			$contador = Contador::where('empresa_id', $id)->first();
		}

		return view('empresas.detalhes')
		->with('empresa', $empresa)
		->with('perfis', $perfis)
		->with('contador', $contador)
		->with('contadores', $contadores)
		->with('representantes', $representantes)
		->with('certificado', $empresa->certificado)
		->with('planoExpirado', $planoExpirado)
		->with('permissoesAtivas', $permissoesAtivas)
		->with('empresaJs', true)
		->with('title', 'Detalhes');
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

	public function alterarSenha($id){
		$empresa = Empresa::find($id);
		return view('empresas.alterar_senha')
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
		return redirect('/empresas/detalhes/' . $empresa->id);
	}

	public function cancelamentos(){
		$data = CancelamentoLicenca::orderBy('id', 'desc')->get();
		return view('empresas.cancelamentos', compact('data'))
		->with('title', 'Cancelamentos');
	}

	public function cancelarBloqueio($id){
		$empresa = Empresa::find($id);

		$empresa->status = 1;
		$empresa->save();

		session()->flash("mensagem_sucesso", "Bloqueio cancelado!");

		return redirect('/online');
	}

	public function update(Request $request){
		$empresa = Empresa::find($request->id);

		$permissao = $this->validaPermissao($request);

		$empresa->nome = $request->nome;
		$empresa->nome_fantasia = $request->nome_fantasia ?? '';
		$empresa->rua = $request->rua;
		$empresa->numero = $request->numero;
		$empresa->bairro = $request->bairro;
		$empresa->cidade = $request->cidade;
		$empresa->telefone = $request->telefone;
		$empresa->email = $request->email;
		$empresa->cnpj = $request->cnpj;
		$empresa->contador_id = $request->contador_id ?? 0;
		$empresa->status = $request->status ? 1 : 0;
		$empresa->permissao = json_encode($permissao);

		$empresa->uf = $request->uf ?? '';
		$empresa->cep = $request->cep ?? '';
		$empresa->representante_legal = $request->representante_legal ?? '';
		$empresa->cpf_representante_legal = $request->cpf_representante_legal ?? '';

		// $empresa->acesso_xml = $request->acesso_xml ? true : false;
		// $empresa->limite_cadastros = $request->limite_cadastros ?? 0;

		$perfilId = 0;

		if(isset($request->perfil_id) && $request->perfil_id != '0'){
			$tp = json_decode($request->perfil_id);
			$empresa->perfil_id = $tp->id;
		}

		if($empresa->escritorio == null && $request->contador_id > 0){
			// $this->insertEscritorio($request->contador_id, $empresa->id);
		}

		$empresa->save();

		if(isset($request->representante_id)){

			$contador = Contador::where('empresa_id', $empresa->id)->first();
			if($contador){
				$contador->representante_id = $request->representante_id;
				$contador->save();
			}
			// echo $contador;
			// die;
		}
		$this->percorreUsuariosEmpresa($empresa, $permissao);

		session()->flash("mensagem_sucesso", "Dados atualizados!");
		return redirect()->back();
	}

	public function percorreUsuariosEmpresa($empresa, $permissao){

		foreach($empresa->usuarios as $e){
			$temp = [];
			$permissaoAntiga = json_decode($e->permissao);
			foreach($permissao as $p){
				// if(in_array($p, $permissaoAntiga)){
				array_push($temp, $p);
				// }
			}

			// print_r($temp);
			// die();

			$e->permissao = json_encode($temp);
			$e->save();
		}
	}

	private function validaPermissaodelete($request){
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

	public function setarPlano($id){
		$empresa = Empresa::find($id);
		$planos = Plano::all();

		if(sizeof($planos) == 0){
			session()->flash("mensagem_erro", "Cadastre um plano primeiramente");
			return redirect('/planos');
		}
		$p = $planos[0];

		$exp = date('d/m/Y', strtotime("+$p->intervalo_dias days",strtotime(str_replace("/", "-",
			date('Y-m-d')))));

		return view('empresas/setar_plano')
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

		$plano = $request->plano;
		$p = Plano::findOrFail($plano);
		if($p){
			if($p->perfil){
				$perfil = $p->perfil;
				$empresa->permissao = $perfil->permissao;
				$empresa->perfil_id = $p->perfil;

				foreach($empresa->usuarios as $u){
					$u->permissao = $perfil->permissao;
					$u->save();
				}
				$empresa->save();
			}
		}
		if($request->indeterminado){
			$expiracao = '0000-00-00';
		}else{
			$expiracao = $this->parseDate($request->expiracao);
		}

		$data = [
			'empresa_id' => $empresa->id,
			'plano_id' => $plano,
			'expiracao' => $expiracao,
			'valor' => __replace($request->valor),
			'mensagem_alerta' => $request->mensagem_alerta ?? ''
		];

		$pl = PlanoEmpresa::create($data);

		$this->setPagamento($request, $pl);

		session()->flash("mensagem_sucesso", "Plano atribuido!");

		return redirect('/empresas/detalhes/'. $empresa->id);
	}

	private function setPagamento($request, $pl){

		$data = [
			'empresa_id' => $request->id,
			'plano_id' => $pl->id,
			'valor' => (float)$request->valor,
			'transacao_id' => '',
			'status' => 'approved',
			'forma_pagamento' => $request->forma_pagamento,
			'link_boleto' => '',
			'status_detalhe' => '',
			'descricao' => '',
			'qr_code_base64' => '',
			'qr_code' => '',
		];
		// dd($data);
		$this->setPagamentoRepresentante($request->id, (float)$request->valor, $request->forma_pagamento);

		Payment::create($data);
	}

	private function setPagamentoRepresentante($empresa_id, $valor, $formaPagamento){

		$rep = RepresentanteEmpresa::
		where('empresa_id', $empresa_id)
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

	private function parseDate($date, $plusDay = false){
		if($plusDay == false)
			return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
		else
			return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
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

			$cnpj = preg_replace('/[^0-9]/', '', $empresa->cnpj);


			if(!is_dir(public_path('contratos'))){
				mkdir(public_path('contratos'), 0777, true);
			}
			safe_file_put_contents(public_path('contratos/'.$cnpj.'.pdf'), $output);

			EmpresaContrato::create(
				[
					'empresa_id' => $empresa->id,
					'status' => 0,
					'cpf_cnpj' => $empresa->cnpj
				]
			);

			return true;
		}catch(\Exception $e){
			return false;
		}
	}

	public function download($id){
		$config = ConfigNota::
		where('empresa_id', $id)
		->first();

		if($config == null){
			session()->flash("mensagem_erro", "Nenhum certificado!");
			return redirect()->back();
		}

		$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

		$files = array_diff(scandir(public_path('certificados')), array('.', '..'));
		$certificados = [];
		foreach ($files as $file) {
			$name_file = explode(".", $file);
			if($name_file[0] == $cnpj){
				array_push($certificados, $file);
			}
		}

		if(sizeof($certificados) > 1){
			return view('empresas.certificados', compact('certificados'))
			->with('title', 'Certificados da empresa');
		}

		try{
			if(file_exists(public_path('certificados/').$cnpj. '.p12')){
				return response()->download(public_path('certificados/').$cnpj. '.p12');
			}
			elseif(file_exists(public_path('certificados/').$cnpj. '.pfx')){
				return response()->download(public_path('certificados/').$cnpj. '.pfx');
			}
			elseif(file_exists(public_path('certificados/').$cnpj. '.bin')){
				return response()->download(public_path('certificados/').$cnpj. '.bin');
			}else{
				echo "Nenhum arquivo encontrado!";
			}

		}catch(\Exception $e){
			echo $e->getMessage();
		}

	}

	public function download_file($file_name){
		return response()->download(public_path('certificados/').$file_name);
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

	public function arquivosXml($empresa_id){
		$empresa = Empresa::find($empresa_id);

		return view('empresas/enviarXml')
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

		return view('empresas/enviarXml')
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

		$cnpj = preg_replace('/[^0-9]/', '', $cnpj);
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

			if($config != null){
				$config->graficos_dash = $config->graficos_dash ? json_decode($config->graficos_dash) : [];
			}

			return view('empresas/config_emitente')
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
		if(!isset($request->graficos_dash)){
			$request->graficos_dash = '[]';
		}else{
			$request->graficos_dash = json_encode($request->graficos_dash);
		}

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
				'frete_padrao' => $request->frete_padrao,
				'tipo_pagamento_padrao' => $request->tipo_pagamento_padrao,
				'nat_op_padrao' => $request->nat_op_padrao ?? 0,
				'validade_orcamento' => $request->validade_orcamento ?? 0,
				'ambiente' => env("APP_ENV") == "demo" ? 2 : $request->ambiente,
				'cUF' => $cUF,
				'ultimo_numero_nfe' => $request->ultimo_numero_nfe,
				'ultimo_numero_nfce' => $request->ultimo_numero_nfce,
				'ultimo_numero_cte' => $request->ultimo_numero_cte ?? 0,
				'ultimo_numero_mdfe' => $request->ultimo_numero_mdfe ?? 0,
				'numero_serie_nfe' => $request->numero_serie_nfe,
				'numero_serie_nfce' => $request->numero_serie_nfce,
				'numero_serie_cte' => $request->numero_serie_cte ?? 0,
				'numero_serie_mdfe' => $request->numero_serie_mdfe ?? 0,
				'percentual_max_desconto' => $request->percentual_max_desconto ?? 0,
				'csc' => $request->csc,
				'csc_id' => $request->csc_id,
				'certificado_a3' => $request->certificado_a3 ? true: false,
				'gerenciar_estoque_produto' => $request->gerenciar_estoque_produto,
				'empresa_id' => $request->empresaId,
				'inscricao_municipal' => $request->inscricao_municipal ?? '',
				'alerta_sonoro' => $request->alerta_sonoro ?? '',
				'aut_xml' => $request->aut_xml ?? '',
				'logo' => $nomeImagem,
				'campo_obs_nfe' => $request->campo_obs_nfe ?? '',
				'exibir_ibscbs_inf_cpl' => $request->exibir_ibscbs_inf_cpl ? 1 : 0,
				'exibir_piscofins_inf_cpl' => $request->exibir_piscofins_inf_cpl ? 1 : 0,
				'exibir_deolho_imposto_inf_cpl' => $request->exibir_deolho_imposto_inf_cpl ? 1 : 0,
				'usar_email_proprio' => $request->usar_email_proprio,
				'campo_obs_pedido' => $request->campo_obs_pedido ?? '',
				'token_ibpt' => $request->token_ibpt ?? '',
				'token_nfse' => $request->token_nfse ?? '',
				'casas_decimais' => $request->casas_decimais,
				'casas_decimais_qtd' => $request->casas_decimais_qtd,
				'sobrescrita_csonn_consumidor_final' => $request->sobrescrita_csonn_consumidor_final ?? '',
				'percentual_lucro_padrao' => $request->percentual_lucro_padrao ?? 0,
				'parcelamento_maximo' => $request->parcelamento_maximo ?? 12,
				'caixa_por_usuario' => $request->caixa_por_usuario,
				'graficos_dash' => $request->graficos_dash,
				'senha_remover' => trim($request->senha_remover) != '' ? md5($request->senha_remover) : '',
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
			$config->email = $request->email ?? '';
			$config->alerta_sonoro = $request->alerta_sonoro ?? '';

			$config->CST_CSOSN_padrao = $request->CST_CSOSN_padrao;
			$config->CST_COFINS_padrao = $request->CST_COFINS_padrao;
			$config->CST_PIS_padrao = $request->CST_PIS_padrao;
			$config->CST_IPI_padrao = $request->CST_IPI_padrao;

			$config->frete_padrao = $request->frete_padrao;
			$config->tipo_pagamento_padrao = $request->tipo_pagamento_padrao;
			$config->nat_op_padrao = $request->nat_op_padrao ?? 0;
			$config->percentual_lucro_padrao = $request->percentual_lucro_padrao ?? 0;
			$config->ambiente = env("APP_ENV") == "demo" ? 2 : $request->ambiente;
			$config->caixa_por_usuario = $request->caixa_por_usuario;
			$config->cUF = $cUF;
			$config->ultimo_numero_nfe = $request->ultimo_numero_nfe;
			$config->ultimo_numero_nfce = $request->ultimo_numero_nfce;
			$config->ultimo_numero_cte = $request->ultimo_numero_cte ?? 0;
			$config->parcelamento_maximo = $request->parcelamento_maximo ?? 0;
			$config->ultimo_numero_mdfe = $request->ultimo_numero_mdfe ?? 0;
			$config->validade_orcamento = $request->validade_orcamento ?? 0;
			$config->numero_serie_nfe = $request->numero_serie_nfe;
			$config->numero_serie_nfce = $request->numero_serie_nfce;
			$config->numero_serie_cte = $request->numero_serie_cte ?? 0;
			$config->numero_serie_mdfe = $request->numero_serie_mdfe ?? 0;
			$config->percentual_max_desconto = $request->percentual_max_desconto ?? 0;
			$config->csc = $request->csc;
			$config->csc_id = $request->csc_id;
			$config->campo_obs_nfe = $request->campo_obs_nfe ?? '';
			$config->exibir_ibscbs_inf_cpl = $request->exibir_ibscbs_inf_cpl ? 1 : 0;
			$config->exibir_piscofins_inf_cpl = $request->exibir_piscofins_inf_cpl ? 1 : 0;
			$config->exibir_deolho_imposto_inf_cpl = $request->exibir_deolho_imposto_inf_cpl ? 1 : 0;
			$config->campo_obs_pedido = $request->campo_obs_pedido ?? '';
			$config->token_ibpt = $request->token_ibpt ?? '';
			$config->token_nfse = $request->token_nfse ?? '';
			$config->complemento = $request->complemento ?? '';
			$config->sobrescrita_csonn_consumidor_final = $request->sobrescrita_csonn_consumidor_final ?? '';
			if(trim($request->senha_remover) != ""){
				$config->senha_remover = md5($request->senha_remover);
			}
			$config->casas_decimais = $request->casas_decimais;
			$config->casas_decimais_qtd = $request->casas_decimais_qtd;
			$config->certificado_a3 = $request->certificado_a3 ? true : false;
			$config->gerenciar_estoque_produto = $request->gerenciar_estoque_produto;
			$config->usar_email_proprio = $request->usar_email_proprio;
			$config->graficos_dash = $request->graficos_dash;

			$config->inscricao_municipal = $request->inscricao_municipal;
			$config->aut_xml = $request->aut_xml;
			if($request->hasFile('file')){
				$config->logo = $nomeImagem;
			}

			$result = $config->save();
		}

		// $value = session('user_logged');
		// $value['ambiente'] = $request->ambiente == 1 ? 'Produção' : 'Homologação';
		// session()->put('user_logged', $value);

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
			'numero_serie_mdfe' => 'required|max:3',
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
		return view('empresas/upload_certificado')
		->with('empresa', $empresa)
		->with('title', 'Upload de Certificado');
	}

    public function saveCertificado(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pfx,p12|max:2048',
            'senha' => 'required|string',
        ], [
            'file.required' => 'O arquivo é obrigatório.',
            'file.mimes' => 'O arquivo deve ser um certificado válido (.pfx ou .p12).',
            'file.max' => 'O arquivo deve ter no máximo 2MB.',
            'senha.required' => 'A senha do certificado é obrigatória.',
        ]);

        try {
            $file = $request->file('file');
            $empresaId = $request->empresaId;
            $config = ConfigNota::where('empresa_id', $empresaId)->first();

            if (!$config) {
                return redirect()->back()->withErrors('Configuração da empresa não encontrada.');
            }

            $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);
            $fileName = "$cnpj." . $file->getClientOriginalExtension();
            $filePath = public_path("certificados/$fileName");

            Certificado::create([
                'senha' => bcrypt($request->senha),
                'arquivo' => safe_file_get_contents($file),
                'empresa_id' => $empresaId,
            ]);

            $file->move(public_path('certificados'), $fileName);

            session()->flash('mensagem_sucesso', 'Upload de certificado realizado com sucesso!');
            return redirect("/empresas/configEmitente/$empresaId");
        } catch (\Exception $e) {
            \Log::error("Erro no upload de certificado: {$e->getMessage()}");
            return redirect()->back()->withErrors('Erro ao salvar o certificado. Verifique os dados.');
        }
    }

    /*
	public function saveCertificado(Request $request){

		if($request->hasFile('file') && strlen($request->senha) > 0){
			$file = $request->file('file');
			$temp = safe_file_get_contents($file);

			$extensao = $file->getClientOriginalExtension();

			$config = ConfigNota::
			where('empresa_id', $request->empresaId)
			->first();

			$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);
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
				return redirect('/empresas/configEmitente/'.$request->empresaId);
			}
		}else{
			session()->flash("mensagem_erro", "Envie o arquivo e senha por favor!");
			return redirect('/empresas/configEmitente/'.$request->empresaId);
		}
	}
    */

	public function removeLogo($empresaId){
		$empresa = Empresa::find($empresaId);
		$config = $empresa->configNota;

		$config->logo = '';
		$config->save();
		session()->flash("mensagem_sucesso", "Logo removida!");
		return redirect()->back();
	}

	public function login($id){
		$empresa = Empresa::findOrFail($id);
		$hash = Str::random(20);

		$usr = $empresa->usuarioFirst;

		if($usr == null){
			session()->flash("mensagem_erro", "Empresa sem usuário!");
			return redirect()->back();
		}

		$usrLog = Usuario::findOrFail(get_id_user());

		$config = $empresa->configNota;
		$ambiente = 'Não configurado';
		if($config != null){
			$ambiente = $config->ambiente == 1 ? 'Produção' : 'Homologação';
		}
		$locais = __locaisAtivosUsuario($usr);

		$session = [
			'id' => $usr->id,
			'nome' => $usr->nome,
			'adm' => $usr->adm,
			'ambiente' => $ambiente,
			'empresa' => $empresa->id,
			'delivery' => 0,
			'super' => 0,
			'empresa_nome' => $usr->empresa->nome,
			'tipo_representante' => 0,
			'hash' => $hash,
			'log_id' => $usrLog->id,
			'locais' => $locais,
			'log_nome' => $usrLog->nome,
			'ip_address' => $this->get_client_ip()
		];

		$value = session('user_logged');

		if($value){
			$usuarioSessao = UsuarioAcesso::
			where('usuario_id', $value['id'])
			->where('status', 0)
			->get();

			foreach($usuarioSessao as $u){
				$u->status = 1;
				$u->save();
			}

			$usuarioSessao = UsuarioAcesso::
			where('usuario_id', $usr->id)
			->where('status', 0)
			->get();

			foreach($usuarioSessao as $u){
				$u->status = 1;
				$u->save();
			}
		}

		UsuarioAcesso::create(
			[
				'usuario_id' => $usr->id,
				'status' => 0,
				'hash' => $hash,
				'ip_address' => $session['ip_address']
			]
		);

		session()->forget('user_logged');
		session()->forget('store_info');
		session(['user_logged' => $session]);

		session()->flash("mensagem_sucesso", "Troca de usuário realizada!");
		return redirect('/graficos');

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

	public function cacheClear(){
		\Artisan::call('cache:clear');
		\Artisan::call('view:clear');
		\Artisan::call('route:clear');
	}

	public function setpdv(){
		$empresas = Empresa::all();
		foreach($empresas as $key => $e){
			if($key > 0){
				$permissaoAntiga = json_decode($e->permissao);
				array_push($permissaoAntiga, "/frenteCaixa/list");

				$e->permissao = json_encode($permissaoAntiga);
				$e->save();
				foreach($e->usuarios as $e){
					$temp = [];
					foreach($permissaoAntiga as $p){
						array_push($temp, $p);
					}
					$e->permissao = json_encode($temp);
					$e->save();
				}
			}
		}
	}

	public function acessosDiarios(){

		$arr = $this->getArrayHour();
		$dataHoje = date('Y-m-d');
		for($i=0; $i<sizeof($arr); $i++){

			$hrPrev = ($i < 10) ? ("0".$i) : $i;
			$hrNext = (($i+1) < 10) ? ("0".($i+1)) : ($i+1);
			$item = EmpresaLogada::whereBetween('created_at', [
				$dataHoje . " " . $hrPrev,
				$dataHoje . " " . $hrNext,
			])->select('total')
			->orderBy('total', 'desc')
			->first();

			if($item != null){
				$arr[$i] = $item->total;
			}
		}

		$ultimaSemana = $this->ultimaSemana();
		$ultimoMes = $this->ultimoMes();

		return view('empresas/acessos_diarios')
		->with('data', $arr)
		->with('ultimaSemana', $ultimaSemana)
		->with('ultimoMes', $ultimoMes)
		->with('title', 'Acessos');
	}

	private function ultimaSemana(){
		$dataHoje = date('Y-m-d');
		$data = $dataHoje;
		$arr = [];
		for($i=0; $i<7; $i++){
			$t = [];
			$item = EmpresaLogada::whereDate('created_at', $data)->select('total')
			->orderBy('total', 'desc')
			->first();

			$temp['dia'] = \Carbon\Carbon::parse($data)->format('d/m');
			$temp['valor'] = $item != null ? $item->total : 0;
			array_push($arr, $temp);
			$data = date('Y-m-d', strtotime("-".($i)." days",strtotime($dataHoje)));

		}


		return $arr;
	}

	private function ultimoMes(){
		$dataHoje = date('Y-m-d');
		$data = $dataHoje;
		$arr = [];
		for($i=0; $i<30; $i++){
			$t = [];
			$item = EmpresaLogada::whereDate('created_at', $data)->select('total')
			->orderBy('total', 'desc')
			->first();

			$temp['dia'] = \Carbon\Carbon::parse($data)->format('d/m');
			$temp['valor'] = $item != null ? $item->total : 0;
			array_push($arr, $temp);
			$data = date('Y-m-d', strtotime("-".($i)." days",strtotime($dataHoje)));

		}

		return $arr;
	}

	private function getArrayHour(){
		$data = [];
		for($i=0; $i<24; $i++){
			$data[$i] = 0;
		}
		return $data;
	}

	public function autocomplete(Request $request){
		$data = Empresa::where('contador_id', 0)
		->where('tipo_contador', 0)
		->orderBy('nome', 'asc')->get();
		return response()->json($data, 200);
	}

	public function ajuste(){
		$empresas = Empresa::all();
		foreach($empresas as $item){
			$config = ConfigNota::
			where('empresa_id', $item->id)
			->first();

			if($config != null){

				$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

				$files = array_diff(scandir(public_path('certificados')), array('.', '..'));
				$certificados = [];
				$certificado = null;
				$time = null;
				foreach ($files as $file) {
					$name_file = explode(".", $file);
					if($name_file[0] == $cnpj){
						$fileInfo = filemtime(public_path('certificados/').$file);

						if($certificado != null){
							if($fileInfo > $time){
								$time = $fileInfo;
								$certificado = $file;
							}
						}else{
							$time = $fileInfo;
							$certificado = $file;
						}

					}
				}
				$cert = $item->certificado;
				if($cert){
					$cert->file_name = $certificado;
					$cert->save();
				}
			}
		}
		echo "ok";

		// $data = FaturaFrenteCaixa::where('forma_pagamento', '14')->get();
		// foreach($data as $i){
		// 	// echo $i->venda;
		// 	// die;
		// 	$categoria = $this->categoriaCrediario($i->venda->empresa_id);

		// 	$conta = [
		// 		'venda_caixa_id' => NULL,
		// 		'venda_id' => NULL,
		// 		'data_vencimento' => $i->data_vencimento,
		// 		'data_recebimento' => $i->data_vencimento,
		// 		'valor_integral' => $i->valor,
		// 		'valor_recebido' => 0,
		// 		'status' => false,
		// 		'referencia' => "Venda PDV " . $i->venda->id,
		// 		'categoria_id' => $categoria,
		// 		'empresa_id' => $i->venda->empresa_id,
		// 		'cliente_id' => $i->venda->cliente_id
		// 	];
		// 	dd($conta);
		// }
	}

	public function cancelamentoDelete($id){
		$item = CancelamentoLicenca::findOrFail($id);
		$item->delete();
		session()->flash("mensagem_sucesso", "Registro removido!");
		return redirect()->back();
	}

	private function categoriaCrediario($empresa_id){
		$cat = CategoriaConta::
		where('empresa_id', $empresa_id)
		->where('nome', 'Crediário')
		->first();
		if($cat != null) return $cat->id;
		$cat = CategoriaConta::create([
			'nome' => 'Crediário',
			'empresa_id' => $empresa_id,
			'tipo'=> 'receber'
		]);
		return $cat->id;
	}

	public function planoContas(){
		$empresa_id = 1;
		$empresa = Empresa::find($empresa_id);
		$this->util->criaPlanoDeContas($empresa_id);

	}

    /**
     * Search tenants for Select2
     *
     * Recebe o parâmetro term e devolve JSON no formato [{ id, text }]
     */
    public function searchTenants(Request $request)
    {
        $term = $request->get('term', '');

        $tenants = Empresa::query()
            ->where('tipo_representante', 0)
            ->where('tipo_contador', 0)
            ->where('status', 1)
            ->when($term, fn($q) => $q
                ->where('nome','like',"%{$term}%")
                ->orWhere('nome_fantasia','like',"%{$term}%")
            )
            ->orderBy('nome')
            ->get([
                'id',
                \DB::raw('nome as text'),
                'razao_social',
                'nome_fantasia',
                'cnpj',
                'telefone',
                'cidade'
            ]);

        return response()->json($tenants);
    }

    public function searchTenants_(Request $request)
    {
        $term = $request->get('term', '');

        $tenants = Empresa::query()
            // somente “empresas” (nem contador, nem representante)
            ->where('tipo_representante', 0)
            ->where('tipo_contador', 0)
            // somente ativas, se quiser
            ->where('status', 1)
            // filtra por nome ou nome fantasia
            ->when($term, function ($q, $term) {
                $q->where('nome', 'like', "%{$term}%")
                    ->orWhere('nome_fantasia', 'like', "%{$term}%");
            })
            ->orderBy('nome')
            // entrega só id e text (o Select2 exige estes campos)
            ->get(['id', \DB::raw('nome as text')]);

        return response()->json($tenants);
    }

}
