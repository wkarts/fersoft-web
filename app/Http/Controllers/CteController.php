<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CTeService;
use App\Models\ConfigNota;
use App\Models\Cte;
use App\Models\Cidade;
use App\Models\Cliente;
use App\Models\Certificado;
use App\Models\MedidaCte;
use App\Models\ComponenteCte;
use App\Models\Veiculo;
use App\Models\CategoriaDespesaCte;
use App\Models\NaturezaOperacao;
use App\Models\DespesaCte;
use App\Models\ReceitaCte;
use App\Models\FaturaCte;
use App\Models\ContaReceber;
use App\Models\FaturaDocCte;
use App\Models\Tributacao;
use App\Models\CategoriaConta;

use Dompdf\Dompdf;
use Illuminate\Support\Facades\DB;

class CteController extends Controller
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

	public function index(){
		$permissaoAcesso = __getLocaisUsarioLogado();

		$local_padrao = __get_local_padrao();
		if($local_padrao == -1){
			$local_padrao = null;
		}
		$ctes = Cte::
		where('estado', 'DISPONIVEL')
		->where('empresa_id', $this->empresa_id)
		->where(function($query) use ($permissaoAcesso){
			if($permissaoAcesso != null){
				foreach ($permissaoAcesso as $value) {
					if($value == -1){
						$value = null;
					}
					$query->orWhere('filial_id', $value);
				}
			}
		})
		->when($local_padrao != NULL, function ($query) use ($local_padrao) {
			$query->where('filial_id', $local_padrao);
		})
		->orderBy('id', 'desc')
		->paginate(30);

		$menos30 = $this->menos30Dias();
		$date = date('d/m/Y');

		$grupos = Cte::gruposCte();
		$certificado = Certificado::
		where('empresa_id', $this->empresa_id)
		->first();
		return view("cte/list")
		->with('ctes', $ctes)
		->with('cteEnvioJs', true)
		->with('links', true)
		->with('dataInicial', $menos30)
		->with('grupos', $grupos)
		->with('certificado', $certificado)
		->with('dataFinal', $date)
		->with('title', "Lista de CTe");

	}

	public function imprimir(Request $request){

		$dataInicial = $request->data_inicial;
		$dataFinal = $request->data_final;
		$estado = $request->estado;
		$tipo_pesquisa = $request->tipo_pesquisa;
		$pesquisa = strtolower($request->pesquisa);
		$ctes = null;

		$grupos = Cte::gruposCte();
		$certificado = Certificado::
		where('empresa_id', $this->empresa_id)
		->first();

		$ctes = Cte::select('ctes.*')
		->where('empresa_id', $this->empresa_id);

		if(isset($dataInicial) && isset($dataFinal)){
			$ctes->whereBetween('data_registro', [
				$this->parseDate($dataInicial),
				$this->parseDate($dataFinal, true)
			]);
		}

		if($estado != 'TODOS') $ctes->where('ctes.estado', $estado);

		$ctes = $ctes->get();

		$temp = [];

		if($pesquisa){
			foreach($ctes as $c){
				$tomador = $c->getTomadorFull();

				if($tipo_pesquisa == 'razao_social' && str_contains(strtolower($tomador->razao_social), $pesquisa)){
					array_push($temp, $c);
				}else if($tipo_pesquisa == 'nome_fantasia' && str_contains(strtolower($tomador->nome_fantasia), $pesquisa)){
					array_push($temp, $c);
				}else if($tipo_pesquisa == 'telefone' && str_contains(strtolower($tomador->telefone), $pesquisa)){
					array_push($temp, $c);
				}
			}

			$ctes = $temp;
		}


		$p = view('cte/print')
		->with('title', 'Lista de CTe')
		->with('data_inicial', $dataInicial)
		->with('data_final', $dataFinal)
		->with('ctes', $ctes);
		// return $p;

		$domPdf = new Dompdf(["enable_remote" => true]);
		$domPdf->loadHtml($p);

		$pdf = ob_get_clean();

		$domPdf->setPaper("A4",  "landscape");
		$domPdf->render();
		$domPdf->stream("Lista de CTe");
	}

	public function filtro(Request $request){

		$dataInicial = $request->data_inicial;
		$dataFinal = $request->data_final;
		$cliente = $request->cliente;
		$estado = $request->estado;
		$filial_id = $request->filial_id;
		$tipo_pesquisa = $request->tipo_pesquisa;
		$pesquisa = strtolower($request->pesquisa);
		$ctes = null;

		$grupos = Cte::gruposCte();
		$certificado = Certificado::
		where('empresa_id', $this->empresa_id)
		->first();

		$permissaoAcesso = __getLocaisUsarioLogado();

		$ctes = Cte::select('ctes.*')
		->when($filial_id, function ($query) use ($filial_id) {
			$filial_id = $filial_id == -1 ? null : $filial_id;
			return $query->where('filial_id', $filial_id);
		})
		->where(function($query) use ($permissaoAcesso){
			if($permissaoAcesso != null){
				foreach ($permissaoAcesso as $value) {
					if($value == -1){
						$value = null;
					}
					$query->orWhere('filial_id', $value);
				}
			}
		})
		->orderBy('id', 'desc')
		->where('empresa_id', $this->empresa_id);

		if(isset($dataInicial) && isset($dataFinal)){
			$ctes->whereBetween('data_registro', [
				$this->parseDate($dataInicial),
				$this->parseDate($dataFinal, true)
			]);
		}

		if($estado != 'TODOS'){
			$ctes->where('ctes.estado', $estado);
		}

		$ctes = $ctes->get();

		$temp = [];

		if($pesquisa){
			foreach($ctes as $c){
				$tomador = $c->getTomadorFull();

				if($tipo_pesquisa == 'razao_social' && str_contains(strtolower($tomador->razao_social), $pesquisa)){
					array_push($temp, $c);
				}else if($tipo_pesquisa == 'nome_fantasia' && str_contains(strtolower($tomador->nome_fantasia), $pesquisa)){
					array_push($temp, $c);
				}else if($tipo_pesquisa == 'telefone' && str_contains(strtolower($tomador->telefone), $pesquisa)){
					array_push($temp, $c);
				}
			}

			$ctes = $temp;
		}

		return view("cte/list")
		->with('ctes', $ctes)
		->with('cteEnvioJs', true)
		->with('grupos', $grupos)
		->with('cliente', $cliente)
		->with('filial_id', $filial_id)
		->with('certificado', $certificado)
		->with('dataInicial', $dataInicial)
		->with('dataFinal', $dataFinal)
		->with('estado', $estado)
		->with('tipo_pesquisa', $tipo_pesquisa)
		->with('pesquisa', $pesquisa)
		->with('paraImprimir', true)
		->with('title', "Filtro de Cte");
	}

	public function nova(){
		$lastCte = Cte::lastCTe();
		$unidadesMedida = Cte::unidadesMedida();
		$tiposMedida = Cte::tiposMedida();
		$tiposTomador = Cte::tiposTomador();
		$naturezas = NaturezaOperacao::
		where('empresa_id', $this->empresa_id)
		->get();

		$modals = Cte::modals();
		$veiculos = Veiculo::
		where('empresa_id', $this->empresa_id)
		->get();

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$clientes = Cliente::
		where('empresa_id', $this->empresa_id)
		->where('inativo', false)
		->with('cidade')
		->orderBy('razao_social')
		->get();

		$tributacao = Tributacao::
		where('empresa_id', $this->empresa_id)
		->first();

		$cidades = Cidade::all();
		if(count($naturezas) == 0 || count($veiculos) == 0 || $config == null){
			return view("cte/erro")
			->with('veiculos', $veiculos)
			->with('naturezas', $naturezas)
			->with('config', $config)
			->with('title', "Validação para Emitir");

		}else{
			return view("cte/register")
			->with('naturezas', $naturezas)
			->with('cteJs', true)
			->with('unidadesMedida', $unidadesMedida)
			->with('tiposMedida', $tiposMedida)
			->with('tiposTomador', $tiposTomador)
			->with('modals', $modals)
			->with('veiculos', $veiculos)
			->with('clientes', $clientes)
			->with('cidades', $cidades)
			->with('tributacao', $tributacao)
			->with('config', $config)
			->with('lastCte', $lastCte)
			->with('title', "Nova CTe");
		}
	}

	private function criarLog($objeto, $tipo = 'criar'){
		if(isset(session('user_logged')['log_id'])){
			$record = [
				'tipo' => $tipo,
				'usuario_log_id' => session('user_logged')['log_id'],
				'tabela' => 'ctes',
				'registro_id' => $objeto->id,
				'empresa_id' => $this->empresa_id
			];
			__saveLog($record);
		}
	}

	public function salvar(Request $request){
		try{
			$result = DB::transaction(function () use ($request) {
				$cte = $request->data;

				$municipio_envio = (int) explode("-", $cte['municipio_envio'])[0];
				$municipio_fim = (int) explode("-", $cte['municipio_fim'])[0];
				$municipio_inicio = (int) explode("-", $cte['municipio_inicio'])[0];
				$municipio_tomador = (int) explode("-", $cte['municipio_tomador'])[0];

				$result = Cte::create([
					'chave_nfe' => $cte['chave_nfe'] ?? '',
					'remetente_id' => $cte['remetente'],
					'destinatario_id' => $cte['destinatario'],
					'expedidor_id' => $cte['expedidor'],
					'recebedor_id' => $cte['recebedor'],
					'usuario_id' => get_id_user(),
					'natureza_id' => $cte['natureza'],
					'tomador' => $cte['tomador'],
					'municipio_envio' => $municipio_envio,
					'municipio_inicio' => $municipio_inicio,
					'municipio_fim' => $municipio_fim,
					'logradouro_tomador' => $cte['logradouro_tomador'],
					'numero_tomador' => $cte['numero_tomador'],
					'bairro_tomador' => $cte['bairro_tomador'],
					'cep_tomador' => $cte['cep_tomador'],
					'municipio_fim' => $municipio_fim,
					'municipio_tomador' => $municipio_tomador,
					'observacao' => $cte['obs'] ?? '',
					'data_previsata_entrega' => $this->parseDate($cte['data_prevista_entrega']),
					'produto_predominante' => $cte['produto_predominante'],
					'cte_numero' => 0,
					'sequencia_cce' => 0,
					'chave' => '',
					'path_xml' => '',
					'estado' => 'DISPONIVEL',

					'valor_transporte' => str_replace(",", ".", $cte['valor_transporte']),
					'valor_receber' => str_replace(",", ".", $cte['valor_receber']),
					'valor_carga' => str_replace(",", ".", $cte['valor_carga']),

					'retira' => $cte['retira'],
					'globalizado' => $cte['globalizado'],
					'tipo_servico' => $cte['tipo_servico'],
					'detalhes_retira' => $cte['detalhes_retira'] ?? '',
					'modal' => $cte['modal'],
					'veiculo_id' => $cte['veiculo_id'],
					'tpDoc' => $cte['tpDoc'] ?? '',
					'descOutros' => $cte['descOutros'] ?? '',
					'nDoc' => $cte['nDoc'] ?? 0,
					'vDocFisc' => $cte['vDocFisc'] ?? 0,
					'empresa_id' => $this->empresa_id,
					'cst' => $cte['cst'],
					'perc_icms' => $cte['perc_icms'] ?? 0,
					'pRedBC' => $cte['pRedBC'] ?? 0,
					'filial_id' => $cte['filial_id'] != -1 ? $cte['filial_id'] : null
				]);

				$this->criarLog($result);
				if(isset($cte['medidias'])){
					foreach($cte['medidias'] as $c){
						$medida = MedidaCte::create([
							'cod_unidade' => explode("-", $c['unidade_medida'])[0],
							'tipo_medida'=> $c['tipo_medida'],
							'quantidade_carga' => str_replace(",", ".", $c['quantidade']),
							'cte_id' => $result->id
						]);
					}
				}

				if(isset($cte['componentes'])){
					foreach($cte['componentes'] as $c){
						$medida = ComponenteCte::create([
							'nome' => $c['nome'],
							'valor' => str_replace(",", ".", $c['valor']),
							'cte_id' => $result->id
						]);
					}
				}
				return $result;
			});
			return response()->json($result, 200);

		}catch(\Exception $e){
			__saveError($e, $this->empresa_id);
			return response()->json($e->getMessage(), 400);
		}
	}

	public function detalhar($id){
		$cte = Cte::
		where('id', $id)
		->first();
		if(valida_objeto($cte)){

			$value = session('user_logged');

			return view("cte/detalhe")
			->with('adm', $value['adm'])
			->with('cte', $cte)
			->with('title', "Detalhe de CTe $id");
		}else{
			return redirect('/403');
		}
	}

	public function custos($id){
		$categorias = CategoriaDespesaCte::
		where('empresa_id', $this->empresa_id)
		->get();

		$cte = Cte::
		where('id', $id)
		->first();

		if(valida_objeto($cte)){
			return view("cte/custos")
			->with('cte', $cte)
			->with('categorias', $categorias)
			->with('title', "Custos Cte $id");
		}else{
			return redirect('/403');
		}
	}

	public function saveReceita(Request $request){
		$result = ReceitaCte::create([
			'descricao' => $request->descricao,
			'valor' => str_replace(",", ".", $request->valor),
			'cte_id' => $request->cte_id
		]);

		if($result){
			session()->flash('mensagem_sucesso', 'Receita adicionada!');
		}else{
			session()->flash('mensagem_erro', 'Erro!');
		}
		return redirect('cte/custos/'.$request->cte_id);
	}

	public function saveDespesa(Request $request){
		$result = DespesaCte::create([
			'descricao' => $request->descricao,
			'categoria_id' => $request->categoria_id,
			'valor' => str_replace(",", ".", $request->valor),
			'cte_id' => $request->cte_id
		]);

		if($result){
			session()->flash('mensagem_sucesso', 'Despesa adicionada!');
		}else{
			session()->flash('mensagem_erro', 'Erro!');
		}
		return redirect('cte/custos/'.$request->cte_id);
	}

	public function chaveNfeDuplicada(Request $request){
		$res = Cte::
		where('chave_nfe', 'LIKE', "%$request->chave%")
		->where('empresa_id', $this->empresa_id)
		->first();
		if($res != null){
			echo true;
		}else{
			echo false;
		}
	}

	public function delete($id){
		$despesa = Cte::
		where('id', $id)
		->first();

		$this->criarLog($despesa, 'deletar');

		if(valida_objeto($despesa)){
			if($despesa->delete()){
				session()->flash('mensagem_sucesso', 'CTe removida!');
			}else{
				session()->flash('mensagem_erro', 'Erro!');
			}
			return redirect('cte');
		}else{
			return redirect('/403');
		}
	}

	public function deleteDespesa($id){
		$despesa = DespesaCte::
		where('id', $id)
		->first();

		if($despesa->delete()){
			session()->flash('mensagem_sucesso', 'Despesa removida!');
		}else{
			session()->flash('mensagem_erro', 'Erro!');
		}
		return redirect('cte/custos/'.$despesa->cte->id);
	}

	public function deleteReceita($id){
		$receita = ReceitaCte::
		where('id', $id)
		->first();

		if($receita->delete()){
			session()->flash('mensagem_sucesso', 'Receita removida!');
		}else{
			session()->flash('mensagem_erro', 'Erro!');
		}
		return redirect('cte/custos/'.$receita->cte->id);
	}

	private function menos30Dias(){
		return date('d/m/Y', strtotime("-30 days",strtotime(str_replace("/", "-",
			date('Y-m-d')))));
	}

	private function parseDate($date, $plusDay = false){
		if($plusDay == false)
			return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
		else
			return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
	}

	public function importarXml(Request $request){

		if ($request->hasFile('file')){
			$arquivo = $request->hasFile('file');
			$xml = simplexml_load_file($request->file);

			$cidade = Cidade::getCidadeCod($xml->NFe->infNFe->emit->enderEmit->cMun);
			$dadosEmitente = [
				'cpf' => $xml->NFe->infNFe->emit->CPF,
				'cnpj' => $xml->NFe->infNFe->emit->CNPJ,
				'razaoSocial' => $xml->NFe->infNFe->emit->xNome,
				'nomeFantasia' => $xml->NFe->infNFe->emit->xFant,
				'logradouro' => $xml->NFe->infNFe->emit->enderEmit->xLgr,
				'numero' => $xml->NFe->infNFe->emit->enderEmit->nro,
				'bairro' => $xml->NFe->infNFe->emit->enderEmit->xBairro,
				'cep' => $xml->NFe->infNFe->emit->enderEmit->CEP,
				'fone' => $xml->NFe->infNFe->emit->enderEmit->fone,
				'ie' => $xml->NFe->infNFe->emit->IE,
				'cidade_id' => $cidade->id,
				'empresa_id' => $this->empresa_id
			];

			$emitente = $this->verificaClienteCadastrado($dadosEmitente);

			$cidade = Cidade::getCidadeCod($xml->NFe->infNFe->dest->enderDest->cMun);
			$dadosDestinatario = [
				'cpf' => $xml->NFe->infNFe->dest->CPF,
				'cnpj' => $xml->NFe->infNFe->dest->CNPJ,
				'razaoSocial' => $xml->NFe->infNFe->dest->xNome,
				'nomeFantasia' => $xml->NFe->infNFe->dest->xFant,
				'logradouro' => $xml->NFe->infNFe->dest->enderDest->xLgr,
				'numero' => $xml->NFe->infNFe->dest->enderDest->nro,
				'bairro' => $xml->NFe->infNFe->dest->enderDest->xBairro,
				'cep' => $xml->NFe->infNFe->dest->enderDest->CEP,
				'fone' => $xml->NFe->infNFe->dest->enderDest->fone,
				'ie' => $xml->NFe->infNFe->dest->IE,
				'cidade_id' => $cidade->id,
				'empresa_id' => $this->empresa_id
			];

			$destinatario = $this->verificaClienteCadastrado($dadosDestinatario);

			$chave = substr($xml->NFe->infNFe->attributes()->Id, 3, 44);

			$somaQuantidade = 0;
			foreach($xml->NFe->infNFe->det as $item) {
				$somaQuantidade += $item->prod->qCom;
			}

			$unidade = $xml->NFe->infNFe->det[0]->prod->uCom;
			if($unidade == 'M2'){
				$unidade = '04';
			}else if($unidade == 'M3'){
				$unidade = '00';
			}else if($unidade == 'KG'){
				$unidade = '01';
			}else if($unidade == 'UNID'){
				$unidade = '03';
			}else if($unidade == 'TON'){
				$unidade = '02';
			}


			$dadosDaNFe = [
				'remetente' => $emitente->id,
				'destinatario' => $destinatario->id,
				'chave' => $chave,
				'produto_predominante' => $xml->NFe->infNFe->det[0]->prod->xProd,
				'unidade' => $unidade,
				'valor_carga' => $xml->NFe->infNFe->total->ICMSTot->vProd,
				'munipio_envio' => $emitente->cidade->id . " - " . $emitente->cidade->nome . "(" .$emitente->cidade->uf . ")",
				'munipio_final' => $destinatario->cidade->id . " - " . $destinatario->cidade->nome . "(" .$destinatario->cidade->uf . ")",
				'componente' => 'Transporte',
				'valor_frete' => $xml->NFe->infNFe->total->ICMSTot->vFrete,
				'quantidade' => number_format($somaQuantidade, 4),
				'data_entrega' => date('d/m/Y')
			];

			// echo "<pre>";
			// print_r($dadosDaNFe);
			// echo "</pre>";

			$lastCte = Cte::lastCTe();
			$unidadesMedida = Cte::unidadesMedida();
			$tiposMedida = Cte::tiposMedida();
			$tiposTomador = Cte::tiposTomador();
			$naturezas = NaturezaOperacao::
			where('empresa_id', $this->empresa_id)
			->get();

			$modals = Cte::modals();
			$veiculos = Veiculo::
			where('empresa_id', $this->empresa_id)
			->get();

			$config = ConfigNota::
			where('empresa_id', $this->empresa_id)
			->first();

			$clienteCadastrado = Cliente::
			where('empresa_id', $this->empresa_id)
			->first();

			$clientes = Cliente::
			where('empresa_id', $this->empresa_id)
			->where('inativo', false)
			->orderBy('razao_social')->get();
			foreach($clientes as $c){
				$c->cidade;
			}
			$cidades = Cidade::all();

			$tributacao = Tributacao::
			where('empresa_id', $this->empresa_id)
			->first();

			return view("cte/register_xml")
			->with('naturezas', $naturezas)
			->with('cteJs', true)
			->with('unidadesMedida', $unidadesMedida)
			->with('tiposMedida', $tiposMedida)
			->with('tiposTomador', $tiposTomador)
			->with('modals', $modals)
			->with('tributacao', $tributacao)
			->with('veiculos', $veiculos)
			->with('cidades', $cidades)
			->with('config', $config)
			->with('lastCte', $lastCte)
			->with('clientes', $clientes)
			->with('dadosDaNFe', $dadosDaNFe)
			->with('emitente', $emitente)
			->with('destinatario', $destinatario)
			->with('title', "Nova CTe");

		}

	}

	private function verificaClienteCadastrado($cliente){

		if($cliente['cnpj'] != ''){
			$cli = Cliente::
			where('empresa_id', $this->empresa_id)
			->where('cpf_cnpj', $this->formataCnpj($cliente['cnpj']))->first();
		}else{
			$cli = Cliente::
			where('empresa_id', $this->empresa_id)
			->where('cpf_cnpj', $cliente['cpf'])->first();
		}
		if($cli == null){
			$result = Cliente::create(
				[
					'razao_social' => $cliente['razaoSocial'],
					'nome_fantasia' => $cliente['nomeFantasia'] != '' ? $cliente['nomeFantasia'] : $cliente['razaoSocial'],
					'bairro' => $cliente['bairro'],
					'numero' => $cliente['numero'],
					'rua' => $cliente['logradouro'],
					'cpf_cnpj' => $cliente['cnpj'] ? $this->formataCnpj($cliente['cnpj']) : $cliente['cpf'],
					'telefone' => $cliente['razaoSocial'],
					'celular' => '',
					'email' => 'teste@teste.com',
					'cep' => $cliente['cep'],
					'ie_rg' => $cliente['ie'],
					'consumidor_final' => 0,
					'limite_venda' => 0,
					'cidade_id' => $cliente['cidade_id'],
					'contribuinte' => 1,
					'rua_cobranca' => '',
					'numero_cobranca' => '',
					'bairro_cobranca' => '',
					'cep_cobranca' => '',
					'cidade_cobranca_id' => NULL,
					'empresa_id' => $this->empresa_id
				]
			);
			$cliente = Cliente::find($result->id);
			return $cliente;
		}
		return $cli;
	}

	private function formataCnpj($cnpj){
		$temp = substr($cnpj, 0, 2);
		$temp .= ".".substr($cnpj, 2, 3);
		$temp .= ".".substr($cnpj, 5, 3);
		$temp .= "/".substr($cnpj, 8, 4);
		$temp .= "-".substr($cnpj, 12, 2);
		return $temp;
	}

	public function edit($id){
		$cte = Cte::find($id);
		$lastCte = Cte::lastCTe();
		$unidadesMedida = Cte::unidadesMedida();
		$tiposMedida = Cte::tiposMedida();
		$tiposTomador = Cte::tiposTomador();

		$modals = Cte::modals();

		$cidades = Cidade::all();

		$naturezas = NaturezaOperacao::
		where('empresa_id', $this->empresa_id)
		->get();

		$veiculos = Veiculo::
		where('empresa_id', $this->empresa_id)
		->get();

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$clienteCadastrado = Cliente::
		where('empresa_id', $this->empresa_id)
		->first();

		$clientes = Cliente::
		where('empresa_id', $this->empresa_id)
		->where('inativo', false)
		->with('cidade')
		->orderBy('razao_social')
		->get();

		$tributacao = Tributacao::
		where('empresa_id', $this->empresa_id)
		->first();

		return view("cte/register")
		->with('naturezas', $naturezas)
		->with('cteJs', true)
		->with('unidadesMedida', $unidadesMedida)
		->with('tiposMedida', $tiposMedida)
		->with('tiposTomador', $tiposTomador)
		->with('modals', $modals)
		->with('veiculos', $veiculos)
		->with('clientes', $clientes)
		->with('cidades', $cidades)
		->with('config', $config)
		->with('tributacao', $tributacao)
		->with('cte', $cte)
		->with('lastCte', $lastCte)
		->with('title', "Editar CTe");

	}

	public function update(Request $request){
		try{
			$result = DB::transaction(function () use ($request) {
				$data = $request->data;

				$cte_id = $data['cte_id'];
				$chave_nfe = $data['chave_nfe'] ?? '';
				$remetente = $data['remetente'];
				$destinatario = $data['destinatario'];
				$expedidor = $data['expedidor'];
				$recebedor = $data['recebedor'];
				$tomador = $data['tomador'];
				$municipio_envio = $data['municipio_envio'];
				$municipio_inicio = $data['municipio_inicio'];
				$municipio_fim = $data['municipio_fim'];
				$numero_tomador = $data['numero_tomador'];
				$bairro_tomador = $data['bairro_tomador'];
				$municipio_tomador = $data['municipio_tomador'];
				$logradouro_tomador = $data['logradouro_tomador'];
				$cep_tomador = $data['cep_tomador'];
				$valor_carga = $data['valor_carga'];
				$valor_receber = $data['valor_receber'];
				$filial_id = $data['filial_id'] != -1 ? $data['filial_id'] : null;

				$valor_transporte = $data['valor_transporte'];
				$produto_predominante = $data['produto_predominante'];
				$globalizado = $data['globalizado'];
				$tipo_servico = $data['tipo_servico'];
				$detalhes_retira = $data['detalhes_retira'] ?? '';
				$data_previsata_entrega = \Carbon\Carbon::parse(str_replace("/", "-", $data['data_prevista_entrega']))->format('Y-m-d');

				$tpDoc = $data['tpDoc'];
				$vDocFisc = $data['vDocFisc'];
				$nDoc = $data['nDoc'];
				$descOutros = $data['descOutros'];

				$natureza = $data['natureza'];

				$cst = $data['cst'];
				$percIcms = $data['perc_icms'] ?? 0;
				$pRedBC = $data['pRedBC'] ?? 0;

				$veiculo_id = $data['veiculo_id'];
				$obs = $data['obs'] ?? '';

				$medidas = $data['medidias'];
				$componentes = $data['componentes'];

				$cte = Cte::find($cte_id);

				$cte->chave_nfe = $chave_nfe;
				$cte->remetente_id = $remetente;
				$cte->destinatario_id = $destinatario;
				$cte->recebedor_id = $recebedor;
				$cte->expedidor_id = $expedidor;
				$cte->tomador = $tomador;
				$cte->municipio_envio = $municipio_envio;
				$cte->municipio_inicio = $municipio_inicio;
				$cte->numero_tomador = $numero_tomador;
				$cte->bairro_tomador = $bairro_tomador;
				$cte->globalizado = $globalizado;
				$cte->tipo_servico = $tipo_servico;
				$cte->municipio_tomador = $municipio_tomador;
				$cte->municipio_fim = $municipio_fim;
				$cte->logradouro_tomador = $logradouro_tomador;
				$cte->cep_tomador = $cep_tomador;
				$cte->valor_carga = str_replace(",", ".", $valor_carga);
				$cte->valor_receber = str_replace(",", ".", $valor_receber);
				$cte->valor_transporte = str_replace(",", ".", $valor_transporte);
				$cte->produto_predominante = $produto_predominante;
				$cte->data_previsata_entrega = $data_previsata_entrega;
				$cte->detalhes_retira = $detalhes_retira;
				$cte->tpDoc = $tpDoc;
				$cte->vDocFisc = $vDocFisc;
				$cte->nDoc = $nDoc;
				$cte->descOutros = $descOutros;
				$cte->natureza_id = $natureza;
				$cte->veiculo_id = $veiculo_id;
				$cte->observacao = $obs;
				$cte->filial_id = $filial_id;
				$cte->cst = $cst;
				$cte->perc_icms = $percIcms;
				$cte->pRedBC = $pRedBC;
				$this->criarLog($cte, 'atualizar');

				$cte->save();

				MedidaCte::where('cte_id', $cte_id)->delete();
			// return response()->json($medidas, 404);

				if($medidas){

					foreach($medidas as $c){
					// $medida = MedidaCte::create([
					// 	'cod_unidade' => explode("-", $c['cod_unidade'])[0],
					// 	'tipo_medida'=> $c['tipo_medida'],
					// 	'quantidade_carga' => str_replace(",", ".", $c['quantidade_carga']),
					// 	'cte_id' => $cte_id
					// ]);

						$medida = MedidaCte::create([
							'cod_unidade' => explode("-", $c['unidade_medida'])[0],
							'tipo_medida'=> $c['tipo_medida'],
							'quantidade_carga' => str_replace(",", ".", $c['quantidade']),
							'cte_id' => $cte_id
						]);
					}
				}

				ComponenteCte::where('cte_id', $cte_id)->delete();

				if($componentes){
					foreach($componentes as $c){
						$medida = ComponenteCte::create([
							'nome' => $c['nome'],
							'valor' => str_replace(",", ".", $c['valor']),
							'cte_id' => $cte_id
						]);
					}
				}
				return $cte;
			});
return response()->json($result, 200);

}catch(\Exception $e){
	__saveError($e, $this->empresa_id);
	return response()->json($e->getMessage(), 400);
}
}

public function estadoFiscal($id){
	$cte = Cte::findOrFail($id);

	if(valida_objeto($cte)){

		$value = session('user_logged');

		return view("cte/alterar_estado_fiscal")
		->with('adm', $value['adm'])
		->with('cte', $cte)
		->with('title', "Detalhe de CTe $id");
	}else{
		return redirect('/403');
	}
}

public function estadoFiscalStore(Request $request){
	try{
		$cte = Cte::findOrFail($request->cte_id);

		$estado = $request->estado;

		$cte->estado = $estado;
		if ($request->hasFile('file')){
			$public = env('SERVIDOR_WEB') ? 'public/' : '';

			$xml = simplexml_load_file($request->file);

			$chave = substr($xml->CTe->infCte->attributes()->Id, 3, 44);
			$file = $request->file;
			$file->move(public_path('xml_cte'), $chave.'.xml');
			$cte->chave = $chave;
			$cte->cte_numero = $xml->CTe->infCte->ide->nCT;

		}

		$cte->save();
		session()->flash("mensagem_sucesso", "Estado alterado");

	}catch(\Exception $e){
		session()->flash("mensagem_erro", "Erro: " . $e->getMessage());

	}
	return redirect()->back();
}

public function alterarStatus($id){
	$item = Cte::findOrFail($id);
	$item->status_pagamento = 1;
	$item->save();
	session()->flash('mensagem_sucesso', 'CTe marcada como paga/finalizada!');
	return redirect()->back();
}

public function fatura($ids){
	$ids = explode(",", $ids);
	$data = [];
	$remetente_id = null;

	foreach($ids as $id){

		$cte = Cte::findOrFail($id);
		if($cte->fatura){
			session()->flash('mensagem_erro', "CTe #$cte->id já está incluída na fatura " . $cte->fatura->fatura_id);
			return redirect()->back();
		}
		if($remetente_id == null){
			$remetente_id = $cte->remetente_id;
		}

		if(valida_objeto($cte)){

			array_push($data, $cte);
		}else{
			return redirect('/403');
		}

		if($remetente_id != $cte->remetente_id){
			session()->flash('mensagem_erro', 'Selecione o mesmo remetente!');
			return redirect()->back();
		}
	}

	$lastFatura = FaturaCte::where('empresa_id', $this->empresa_id)
	->orderBy('id', 'desc')->first();

	$ultimoNumeroFatura = $lastFatura != null ? $lastFatura->numero_fatura : 0;

	return view('cte.criar_fatura', compact('data', 'ultimoNumeroFatura'))
	->with('title', 'Fatura de CTe');
}

public function salvarFatura(Request $request){
	try{
		$result = DB::transaction(function () use ($request) {
			// 	echo "<pre>";
			// 	print_r($request->valor_total_frete);
			// 	echo "</pre>";
			// die;


			$dataFatura = [
				'numero_fatura' => $request->numero_fatura,
				'vencimento' => $request->vencimento,
				'valor_total' => __replace($request->valor_total_frete),
				'desconto' => $request->desconto ? __replace($request->desconto) : 0,
				'empresa_id' => $this->empresa_id,
				'remetente_id' => $request->remetente_id[0],
				'observacao' => $request->observacao ?? ''
			];

			$fat = FaturaCte::create($dataFatura);
			$cte = null;
			$cliente = null;
			for ($i = 0; $i < sizeof($request->cte_id); $i++) {
				$cte = Cte::findOrFail($request->cte_id[$i]);

				if($cliente == null){
					if($cte->tomador == 0){
						$cliente = $cte->remetente;
					}else if($cte->tomador == 1){
						$cliente = $cte->expedidor;
					}else if($cte->tomador == 2){
						$cliente = $cte->recebedor;
					}else{
						$cliente = $cte->destinatario;
					}
				}

				$dataDoc = [
					'fatura_id' => $fat->id,
					'cte_id' => $request->cte_id[$i],
					'unidade' => $request->unidade[$i] ?? '',
					'cte_numero' => $request->cte_numero[$i] ?? 0,
					'chave_nfe' => $request->chave_nfe[$i] ?? '',
					'valor_mercadoria' => __replace($request->valor_mercadoria[$i]),
					'peso' => __replace($request->peso[$i]),
					'frete' => __replace($request->frete[$i])
				];
				FaturaDocCte::create($dataDoc);
			}

			if($request->gerar_conta_receber){

				$conta = ContaReceber::create([
					'data_vencimento' => $request->vencimento,
					'data_recebimento' => $request->vencimento,
					'valor_integral' => __replace($request->valor_total_frete),
					'valor_recebido' => 0,
					'status' => false,
					'referencia' => "Fatura CTe",
					'categoria_id' => CategoriaConta::where('empresa_id', $this->empresa_id)->first()->id,
					'empresa_id' => $this->empresa_id,
					'filial_id' => $cte->filial_id,
					'cliente_id' => $cliente != null ? $cliente->id : $request->remetente_id[0]
				]);
				$fat->conta_receber_id = $conta->id;
				$fat->save();
			}
		});

		session()->flash('mensagem_sucesso', 'Fatura gerada');
		return redirect('/cte/faturas');

	}catch(\Exception $e){
		__saveError($e, $this->empresa_id);
		session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
		return redirect()->back();
	}
}

public function faturas(){
	$data = FaturaCte::where('empresa_id', $this->empresa_id)
	->orderBy('id', 'desc')->get();

	$clientes = Cliente::where('empresa_id', $this->empresa_id)
	->where('inativo', false)
	->get();

	return view('cte.faturas', compact('data', 'clientes'))
	->with('title', 'Faturas de CTe');
}

public function deleteFatura($id){
	$item = FaturaCte::findOrFail($id);

	if(valida_objeto($item)){
		if($item->conta_receber_id){
			ContaReceber::where('id', $item->conta_receber_id)->delete();
		}
		if($item->delete()){
			session()->flash('mensagem_sucesso', 'Fatura removida!');
		}else{
			session()->flash('mensagem_erro', 'Erro!');
		}
		return redirect()->back();
	}else{
		return redirect('/403');
	}
}

public function filtroFatura(Request $request){

	$dataInicial = $request->data_inicial;
	$dataFinal = $request->data_final;
	$remetente_id = $request->remetente_id;
	$data = FaturaCte::where('empresa_id', $this->empresa_id)
	->when($dataInicial && $dataFinal, function ($q) use ($dataInicial, $dataFinal) {
		return $q->whereBetween('created_at', [
			$this->parseDate($dataInicial),
			$this->parseDate($dataFinal, true)
		]);
	})
	->when($remetente_id, function ($q) use ($remetente_id) {
		return $q->where('remetente_id', $remetente_id);
	})
	->orderBy('id', 'desc')->get();

	$clientes = Cliente::where('empresa_id', $this->empresa_id)
	->where('inativo', false)
	->get();

	return view('cte.faturas', compact('data', 'clientes', 'remetente_id', 'dataInicial', 'dataFinal'))
	->with('title', 'Faturas de CTe');
}

public function imprimirFatura($fat_id){
	$item = FaturaCte::findOrFail($fat_id);

	$config = ConfigNota::
	where('empresa_id', $this->empresa_id)
	->first();

	$p = view('cte/print_fatura')
	->with('title', 'Fatura de CTe')
	->with('config', $config)
	->with('item', $item);
		// return $p;

	$domPdf = new Dompdf(["enable_remote" => true]);
	$domPdf->loadHtml($p);

	$pdf = ob_get_clean();

	$domPdf->setPaper("A4",  "landscape");
	$domPdf->render();
	$domPdf->stream("Lista de CTe", array("Attachment" => false));

}
}
