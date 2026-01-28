<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\MDFeService;
use App\Models\ConfigNota;
use App\Models\Mdfe;
use App\Models\Filial;
use App\Models\Certificado;
use NFePHP\DA\MDFe\Damdfe;
use Mail;
use App\Models\EscritorioContabil;

class EmiteMdfeController extends Controller
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

	public function enviar(Request $request){

		$mdfe = Mdfe::where('id', $request->id)
		->first();

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$isFilial = $mdfe->filial_id;
		if($mdfe->filial_id != null){
			$config = Filial::findOrFail($mdfe->filial_id);
			if($config->arquivo_certificado == null){
				echo "Necessário o certificado para realizar esta ação!";
				die;
			}
		}

		$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

		$mdfe_service = new MDFeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => (int)$config->ambiente,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->UF,
			"cnpj" => $cnpj,
			"inscricaomunicipal" => $config->inscricao_municipal,
			"codigomunicipio" => $config->codMun,
			"schemes" => "PL_MDFe_300a",
			"is_filial" => $isFilial,
			"versao" => '3.00'
		]);

		// $xml = $mdfe_service->gerar($mdfe);
		$resultado = false;

		if($mdfe->estado == 'NOVO' || $mdfe->estado == 'REJEITADO'){
			header('Content-type: text/html; charset=UTF-8');
			$xml = $mdfe_service->gerar($mdfe);
			if(!isset($xml['erros_xml'])){

				$signed = $mdfe_service->sign($xml['xml']);

				$resultado = $mdfe_service->transmitir($signed);

				if(!isset($resultado['erro'])){
					$mdfe->chave = $resultado['chave'];
					$mdfe->protocolo = $resultado['protocolo'];

					$mdfe->estado = 'APROVADO';

					$mdfe->mdfe_numero = $xml['numero'];
					$mdfe->save();

					$config->ultimo_numero_mdfe = $xml['numero'];
					$config->save();

					$this->enviarEmailAutomatico($mdfe);
					return response()->json($resultado, 200);
				}else{
					$mdfe->estado = 'REJEITADO';
					$mdfe->save();

					return response()->json($resultado, 403);
				}
				echo json_encode($resultado);
			}else{
				return response()->json($xml['erros_xml'], 404);

			}
		}else{
			return response()->json("erro", 401);
		}
	}

	public function xmlTemp($id){

		$mdfe = Mdfe::where('id', $id)
		->first();

		if($mdfe == null){
			session()->flash("mensagem_erro", "Selecione um documento na lista!");
			return redirect()->back();
		}

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$isFilial = $mdfe->filial_id;
		if($mdfe->filial_id != null){
			$config = Filial::findOrFail($mdfe->filial_id);
			if($config->arquivo_certificado == null){
				echo "Necessário o certificado para realizar esta ação!";
				die;
			}
		}

		$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

		$mdfe_service = new MDFeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => (int)$config->ambiente,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->UF,
			"cnpj" => $cnpj,
			"is_filial" => $isFilial,
			"inscricaomunicipal" => $config->inscricao_municipal,
			"codigomunicipio" => $config->codMun,
			"schemes" => "PL_MDFe_300a",
			"versao" => '3.00'
		]);

		$xml = $mdfe_service->gerar($mdfe);
		if(!isset($xml['erros_xml'])){

			$xml = $xml['xml'];
			return response($xml)
			->header('Content-Type', 'application/xml');
		}else{
			foreach($xml['erros_xml'] as $e){
				echo $e;
			}
		}

	}

	public function naoEncerrados(){

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$certificado = Certificado::
		where('empresa_id', $this->empresa_id)
		->first();

		if($certificado == null){
			session()->flash("mensagem_erro", "Configure o certificado!");
			return redirect()->back();
		}

		$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

		$mdfe_service = new MDFeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => (int)$config->ambiente,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->UF,
			"cnpj" => $cnpj,
			"inscricaomunicipal" => $config->inscricao_municipal,
			"codigomunicipio" => $config->codMun,
			"schemes" => "PL_MDFe_300a",
			"versao" => '3.00',
			"is_filial" => null
		]);

		$resultados = $mdfe_service->naoEncerrados();
		// echo '<pre>';
		// dd($resultados);
		// echo "</pre>";
		// die;

		$naoEncerrados = [];

		if($resultados['xMotivo'] != 'Consulta não encerrados não localizou MDF-e nessa situação'){

			
			if(isset($resultados['infMDFe'])){
				
				// if(sizeof($resultados['infMDFe']) == 2){
				if(!isset($resultados['infMDFe'][1])){
					$array = [
						'chave' => $resultados['infMDFe']['chMDFe'],
						'protocolo' => $resultados['infMDFe']['nProt'],
						'numero' => 0,
						'data' => '',
						'local' => ''
					];
					array_push($naoEncerrados, $array);
				}else{
					foreach($resultados['infMDFe'] as $inf){

						$array = [
							'chave' => $inf['chMDFe'],
							'protocolo' => $inf['nProt'],
							'numero' => 0,
							'data' => '',
							'local' => ''
						];
						array_push($naoEncerrados, $array);

					}
				}


			}
		}

		$filiais = Filial::where('empresa_id', $this->empresa_id)
		->where('status',1)->get();

		foreach($filiais as $config){
			$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

			$mdfe_service = new MDFeService([
				"atualizacao" => date('Y-m-d h:i:s'),
				"tpAmb" => (int)$config->ambiente,
				"razaosocial" => $config->razao_social,
				"siglaUF" => $config->UF,
				"cnpj" => $cnpj,
				"inscricaomunicipal" => $config->inscricao_municipal,
				"codigomunicipio" => $config->codMun,
				"schemes" => "PL_MDFe_300a",
				"versao" => '3.00',
				"is_filial" => $config->id
			]);

			$resultados = $mdfe_service->naoEncerrados();
			if($resultados['xMotivo'] != 'Consulta não encerrados não localizou MDF-e nessa situação'){


				if(isset($resultados['infMDFe'])){

					if(!isset($resultados['infMDFe'][1])){
						$array = [
							'chave' => $resultados['infMDFe']['chMDFe'],
							'protocolo' => $resultados['infMDFe']['nProt'],
							'numero' => 0,
							'data' => '',
							'local' => $config->descricao
						];
						array_push($naoEncerrados, $array);
					}else{
						foreach($resultados['infMDFe'] as $inf){

							$array = [
								'chave' => $inf['chMDFe'],
								'protocolo' => $inf['nProt'],
								'numero' => 0,
								'data' => '',
								'local' => $config->descricao
							];
							array_push($naoEncerrados, $array);

						}
					}
				}
			}
		}

		$naoEncerrados = $this->percorreDatabaseNaoEncerrados($naoEncerrados);

		return view('mdfe/naoEncerrados')
		->with('title', 'MDFe não encerrados')
		->with('naoEncerradosMDFeJS', true)
		->with('mdfes', $naoEncerrados);

	}

	private function percorreDatabaseNaoEncerrados($naoEncerrados){
		for($aux = 0; $aux < count($naoEncerrados); $aux++){
			$mdfe = Mdfe::
			where('chave', $naoEncerrados[$aux]['chave'])
			->where('empresa_id', $this->empresa_id)
			->first();

			if($mdfe != null){

				$naoEncerrados[$aux]['data'] = $mdfe->created_at;
				$naoEncerrados[$aux]['numero'] = $mdfe->mdfe_numero;
				$naoEncerrados[$aux]['local'] = $mdfe->filial ? $mdfe->filial->descricao : 'Matriz';
			}

		}
		return $naoEncerrados;
	}

	public function encerrar(Request $request){
		$docs = $request->data;

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();
		$isFilial = false;
		$filial = Filial::where('descricao', $docs[0]['local'])->first();
		if($filial != null){
			$isFilial = $filial->id;
			$config = $filial;
		}
		$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

		$mdfe_service = new MDFeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => (int)$config->ambiente,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->UF,
			"cnpj" => $cnpj,
			"is_filial" => $isFilial,
			"inscricaomunicipal" => $config->inscricao_municipal,
			"codigomunicipio" => $config->codMun,
			"schemes" => "PL_MDFe_300a",
			"versao" => '3.00'
		]);
		$resp = null;
		foreach($docs as $d){
			$mdfe = Mdfe::
			where('chave', $d['chave'])
			->where('empresa_id', $this->empresa_id)
			->first();

			$resp = $mdfe_service->encerrar($d['chave'], $d['protocolo']);
			if($resp->infEvento->cStat != 631){
				if($mdfe != null){
					$mdfe->encerrado = true;
					$mdfe->save();
				}
			}else{
				return response()->json($resp->infEvento->xMotivo, 401);

			}

		}

		return response()->json($resp, 200);

	}

	public function imprimir($id){

		$mdfe = Mdfe::find($id);
		if(valida_objeto($mdfe)){
			if(file_exists(public_path('xml_mdfe/').$mdfe->chave.'.xml')){
				$xml = file_get_contents(public_path('xml_mdfe/').$mdfe->chave.'.xml');

				$config = ConfigNota::
				where('empresa_id', $this->empresa_id)
				->first();

				if($config->logo){
					$logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(public_path('logos/') . $config->logo));
				}else{
					$logo = null;
				}

				try {
					$damdfe = new Damdfe($xml);
					$damdfe->debugMode(true);
					// $damdfe->creditsIntegratorFooter('WEBNFe Sistemas - http://www.webenf.com.br');
					$pdf = $damdfe->render($logo);
					header('Content-Type: application/pdf');
					return response($pdf)
					->header('Content-Type', 'application/pdf');
				} catch (Exception $e) {
					echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
				} 
			}else{
				echo "Arquivo XML não encontrado!";
			}
		}else{
			return redirect('/403');
		}
	}

	public function baixarXml($id){

		$mdfe = Mdfe::find($id);
		if(valida_objeto($mdfe)){

			if(file_exists(public_path('xml_mdfe/').$mdfe->chave.'.xml')){
				return response()->download(public_path('xml_mdfe/').$mdfe->chave.'.xml');
			}else{
				echo "Arquivo XML não encontrado!!";
			}
		}else{
			return redirect('/403');
		}

	}

	public function consultar(Request $request){
		$mdfe = Mdfe::find($request->id);

		if($mdfe->estado == 'APROVADO' || $mdfe->estado == 'CANCELADO'){

			$config = ConfigNota::
			where('empresa_id', $this->empresa_id)
			->first();

			$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

			$isFilial = $mdfe->filial_id;
			if($mdfe->filial_id != null){
				$config = Filial::findOrFail($mdfe->filial_id);
				if($config->arquivo_certificado == null){
					echo "Necessário o certificado para realizar esta ação!";
					die;
				}
			}

			$mdfe_service = new MDFeService([
				"atualizacao" => date('Y-m-d h:i:s'),
				"tpAmb" => (int)$config->ambiente,
				"razaosocial" => $config->razao_social,
				"siglaUF" => $config->UF,
				"cnpj" => $cnpj,
				"inscricaomunicipal" => $config->inscricao_municipal,
				"codigomunicipio" => $config->codMun,
				"is_filial" => $isFilial,
				"schemes" => "PL_MDFe_300a",
				"versao" => '3.00'
			]);

			$mdfe = Mdfe::find($request->id);
			$result = $mdfe_service->consultar($mdfe->chave);

			return response()->json($result, 200);
		}else{
			return response()->json("Erro ao consultar", 404);
		}
	}

	public function cancelar(Request $request){
		$mdfe = Mdfe::find($request->id);

		if($mdfe->estado == 'APROVADO'){
			$config = ConfigNota::
			where('empresa_id', $this->empresa_id)
			->first();

			$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);
			$isFilial = $mdfe->filial_id;
			if($mdfe->filial_id != null){
				$config = Filial::findOrFail($mdfe->filial_id);
				if($config->arquivo_certificado == null){
					echo "Necessário o certificado para realizar esta ação!";
					die;
				}
			}

			$mdfe_service = new MDFeService([
				"atualizacao" => date('Y-m-d h:i:s'),
				"tpAmb" => (int)$config->ambiente,
				"razaosocial" => $config->razao_social,
				"siglaUF" => $config->UF,
				"cnpj" => $cnpj,
				"inscricaomunicipal" => $config->inscricao_municipal,
				"codigomunicipio" => $config->codMun,
				"schemes" => "PL_MDFe_300a",
				"is_filial" => $isFilial,
				"versao" => '3.00'
			]);

			$mdfe = Mdfe::find($request->id);
			$result = $mdfe_service->cancelar($mdfe->chave, $mdfe->protocolo, $request->justificativa);

			if($result->infEvento->cStat == '101' || $result->infEvento->cStat == '135' || $result->infEvento->cStat == '155'){
				$mdfe->estado = 'CANCELADO';
				$mdfe->save();
				return response()->json($result, 200);

			}else{

				return response()->json($result, 401);
			}
		}else{
			return response()->json("Erro a MDF-e precisa estar atutorizada para cancelar", 404);
		}
	}

	public function enviarXml(Request $request){

		$email = $request->email;
		$id = $request->id;
		$mdfe = Mdfe::find($id);
		$this->criarPdfParaEnvio($mdfe);
		$value = session('user_logged');
		Mail::send('mail.xml_send_mdfe', ['emissao' => $mdfe->created_at, 'mdfe' => $mdfe->numero, 'usuario' => $value['nome']], function($m) use ($mdfe, $email){
			$public = env('SERVIDOR_WEB') ? 'public/' : '';
			$nomeEmpresa = env('MAIL_NAME');
			$nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
			$nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
			$emailEnvio = env('MAIL_USERNAME');

			$m->from($emailEnvio, $nomeEmpresa);
			$m->subject('Envio de XML MDF-e ' . $mdfe->numero);
			$m->attach($public.'xml_mdfe/'.$mdfe->chave.'.xml');
			$m->attach($public.'pdf/MDFe.pdf');
			$m->to($email);
		});
		return "ok";

	}

	private function criarPdfParaEnvio($mdfe){
		$public = env('SERVIDOR_WEB') ? 'public/' : '';
		$xml = file_get_contents($public.'xml_mdfe/'.$mdfe->chave.'.xml');
		$logo = 'data://text/plain;base64,'. base64_encode(file_get_contents($public.'imgs/logo.jpg'));
		// $docxml = FilesFolders::readFile($xml);

		try {

			$damdfe = new Damdfe($xml);
			$damdfe->debugMode(true);
			$damdfe->creditsIntegratorFooter('WEBNFe Sistemas - http://www.webenf.com.br');
			$pdf = $damdfe->render($logo);
			header('Content-Type: application/pdf');
			file_put_contents($public.'pdf/MDFe.pdf',$pdf);

		} catch (InvalidArgumentException $e) {
			echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
		}  
	}

	// public function imprimirCancela($id){
	// 	$mdfe = Mdfe::find($id);
	// 	echo $mdfe;
	// }

	private function enviarEmailAutomatico($mdfe){
		$escritorio = EscritorioContabil::
		where('empresa_id', $this->empresa_id)
		->first();

		if($escritorio != null && $escritorio->envio_automatico_xml_contador){
			$email = $escritorio->email;
			Mail::send('mail.xml_automatico', ['descricao' => 'Envio de MDF-e'], function($m) use ($email, $mdfe){
				$nomeEmpresa = env('MAIL_NAME');
				$nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
				$nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
				$emailEnvio = env('MAIL_USERNAME');

				$m->from($emailEnvio, $nomeEmpresa);
				$m->subject('Envio de XML Automático');

				$m->attach(public_path('xml_mdfe/'.$mdfe->chave.'.xml'));
				$m->to($email);
			});
		}
	}

	public function teste($id){
		$mdfe = Mdfe::findOrFail($id);
		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$isFilial = $mdfe->filial_id;
		if($mdfe->filial_id != null){
			$config = Filial::findOrFail($mdfe->filial_id);
			if($config->arquivo_certificado == null){
				echo "Necessário o certificado para realizar esta ação!";
				die;
			}
		}

		$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

		$mdfe_service = new MDFeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => (int)$config->ambiente,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->UF,
			"cnpj" => $cnpj,
			"is_filial" => $isFilial,
			"inscricaomunicipal" => $config->inscricao_municipal,
			"codigomunicipio" => $config->codMun,
			"schemes" => "PL_MDFe_300a",
			"versao" => '3.00'
		]);

		$chave = $mdfe->chave;
		$resp = $mdfe_service->consultar($chave);

		dd($resp);
		$nProt = (string)$resp->protMDFe->infProt->nProt;
		$rec = $mdfe_service->consultaRecibo($nProt);
		dd($rec);

	}

}
