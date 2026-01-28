<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venda;
use App\Models\ItemVenda;
use App\Models\VendaCaixa;
use App\Models\ItemVendaCaixa;
use App\Models\Cte;
use App\Models\Filial;
use App\Models\Mdfe;
use App\Models\ConfigNota;
use App\Models\EmailConfig;
use App\Models\Compra;
use App\Models\ManifestaDfe;
use App\Models\Devolucao;
use App\Models\Empresa;
use App\Models\XmlEnviado;
use App\Models\EscritorioContabil;
use App\Models\RemessaNfe;
use Mail;
use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use App\Models\Nfse as NotaServico;

class EnviarXmlController extends Controller
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

	public function index(Request $request){
		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$dataInicial = '';
		$dataFinal = '';
		if(isset($request->data)){
			$mes = (int)date('m');
			$ano = date('Y');

			if($mes == 0){
				$mesAnterior = 12;
				$ano--;
			}else{
				$mesAnterior = $mes-1;
			}
			$mesAnterior = $mesAnterior < 10 ? "0$mesAnterior" : $mesAnterior;

			$mes = '02';   
			$ano = date("Y"); 
			$ultimo_dia = date("t", mktime(0,0,0,$mesAnterior,'01',$ano)); 

			$dataInicial = "01/$mesAnterior/$ano";
			$dataFinal = "$ultimo_dia/$mesAnterior/$ano";
		}

		if($config == null){
			session()->flash('mensagem_erro', 'Configure o emitente primeiro!');
			return redirect('/configNF');
		}
		return view('enviarXml/list')
		->with('contZips', 0)
		->with('dataInicial', $dataInicial)
		->with('dataFinal', $dataFinal)
		->with('title', 'Enviar XML');
	}

	private function getCnpjEmpresa(){
		$filial_id = request()->filial_id;

		if($filial_id > 0){
			$filial = Filial::findOrFail($filial_id);
			$cnpj = preg_replace('/[^0-9]/', '', $filial->cnpj);

		}else{
			$empresa = Empresa::find($this->empresa_id);
			$cnpj = preg_replace('/[^0-9]/', '', $empresa->configNota->cnpj);
		}

		return $cnpj;
	}

	public function filtro(Request $request){
		$filial_id = $request->filial_id;

		$files = glob(public_path('zips')."/*");

		foreach($files as $file){
			if(is_file($file)) {
				unlink($file);
			}
		}
		$contZips = 0;
		$xml = Venda::
		whereBetween('data_emissao', [
			$this->parseDate($request->data_inicial), 
			$this->parseDate($request->data_final, true)])
		->where('empresa_id', $this->empresa_id)
		->when($filial_id > 0, function ($query) use ($filial_id) {
			return $query->where('filial_id', $filial_id);
		});

		$estado = $request->estado;
		if($estado == 1){
			$xml->where('estado', 'APROVADO');
		}else{
			$xml->where('estado', 'CANCELADO');
		}
		$xml = $xml->get();

		$xmlRemessa = RemessaNfe::
		whereBetween('data_emissao', [
			$this->parseDate($request->data_inicial), 
			$this->parseDate($request->data_final, true)])
		->where('empresa_id', $this->empresa_id)
		->when($filial_id > 0, function ($query) use ($filial_id) {
			return $query->where('filial_id', $filial_id);
		});

		$estado = $request->estado;
		if($estado == 1){
			$xmlRemessa->where('estado', 'aprovado');
		}else{
			$xmlRemessa->where('estado', 'cancelado');
		}
		$xmlRemessa = $xmlRemessa->get();

		$temp = [];
		foreach($xml as $x){
			array_push($temp, $x);
		}
		foreach($xmlRemessa as $x){
			array_push($temp, $x);
		}

		$xml = $temp;

		$public = env('SERVIDOR_WEB') ? 'public/' : '';
		$cnpj = $this->getCnpjEmpresa();

		try{
			if(sizeof($xml) > 0){

				// $zip_file = 'zips/xml_'.$cnpj.'.zip';
				$zip_file = public_path('zips') . '/xml-'.$cnpj.'.zip';

				$zip = new \ZipArchive();
				$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

				if($estado == 1){
					foreach($xml as $x){
						if(file_exists(public_path('xml_nfe/').$x->chave. '.xml')){
							$zip->addFile(public_path('xml_nfe/').$x->chave. '.xml', $x->chave. '.xml');
							$contZips++;
						}
					}
				}else{
					foreach($xml as $x){
						if(file_exists(public_path('xml_nfe_cancelada/').$x->chave. '.xml')){
							$zip->addFile(public_path('xml_nfe_cancelada/').$x->chave. '.xml', $x->chave. '.xml');
							$contZips++;
						}
					}
				}
				$zip->close();
			}

			// if(count($xmlRemessa) > 0){

			// 	// $zip_file = 'zips/xml_'.$cnpj.'.zip';
			// 	$zip_file = public_path('zips') . '/xml-'.$cnpj.'.zip';

			// 	$zip = new \ZipArchive();
			// 	$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

			// 	if($estado == 1){
			// 		foreach($xml as $x){
			// 			if(file_exists($public.'xml_nfe/'.$x->chave. '.xml'))
			// 				$zip->addFile($public.'xml_nfe/'.$x->chave. '.xml', $x->path_xml);
			// 		}
			// 	}else{
			// 		foreach($xml as $x){
			// 			if(file_exists($public.'xml_nfe_cancelada/'.$x->chave. '.xml'))
			// 				$zip->addFile($public.'xml_nfe_cancelada/'.$x->chave. '.xml', $x->path_xml);
			// 		}
			// 	}
			// 	$zip->close();
			// }
		}catch(\Exception $e){
		}

		try{
			$xmlCte = Cte::
			whereBetween('created_at', [
				$this->parseDate($request->data_inicial), 
				$this->parseDate($request->data_final, true)])
			->where('empresa_id', $this->empresa_id)
			->when($filial_id > 0, function ($query) use ($filial_id) {
				return $query->where('filial_id', $filial_id);
			});

			$estado = $request->estado;
			if($estado == 1){
				$xmlCte->where('estado', 'APROVADO');
			}else{
				$xmlCte->where('estado', 'CANCELADO');
			}
			$xmlCte = $xmlCte->get();

			if(count($xmlCte) > 0){

				$zip_file = public_path('zips') . '/xmlcte-'.$cnpj.'.zip';

				$zip = new \ZipArchive();
				$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

				if($estado == 1){
					foreach($xmlCte as $x){
						if(file_exists(public_path('xml_cte/').$x->chave. '.xml')){
							$zip->addFile(public_path('xml_cte/').$x->chave. '.xml', $x->chave. '.xml');
							$contZips++;
						}
					}
				}else{
					foreach($xmlCte as $x){
						if(file_exists(public_path('xml_cte_cancelada/').$x->chave. '.xml')){
							$zip->addFile(public_path('xml_cte_cancelada/').$x->chave. '.xml', $x->chave. '.xml');
							$contZips++;
						}
					}
				}
				$zip->close();


			}
		}catch(\Exception $e){

		}

		try{
			$xmlNfce = VendaCaixa::
			whereBetween('created_at', [
				$this->parseDate($request->data_inicial), 
				$this->parseDate($request->data_final, true)])
			->where('empresa_id', $this->empresa_id)
			->when($filial_id > 0, function ($query) use ($filial_id) {
				return $query->where('filial_id', $filial_id);
			});

			if($estado == 1){
				$xmlNfce->where('estado', 'APROVADO');
			}else{
				$xmlNfce->where('estado', 'CANCELADO');
			}
			$xmlNfce = $xmlNfce->get();

			if(sizeof($xmlNfce) > 0){

				$zip_file = public_path('zips') . '/xmlnfce-'.$cnpj.'.zip';

				$zip = new \ZipArchive();
				$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

				if($estado == 1){
					foreach($xmlNfce as $x){
						if(file_exists(public_path('xml_nfce/').$x->chave. '.xml')){
							$zip->addFile(public_path('xml_nfce/').$x->chave. '.xml', $x->chave. '.xml');
							$contZips++;
						}
					}
				}else{
					foreach($xmlNfce as $x){
						if(file_exists(public_path('xml_nfce_cancelada/').$x->chave. '.xml')){
							$zip->addFile(public_path('xml_nfce_cancelada/').$x->chave. '.xml', $x->chave. '.xml');
							$contZips++;
						}
					}
				}
				$zip->close();
			}
		}catch(\Exception $e){

		}

		$xmlMdfe = Mdfe::
		whereBetween('created_at', [
			$this->parseDate($request->data_inicial), 
			$this->parseDate($request->data_final, true)])
		->where('empresa_id', $this->empresa_id)
		->when($filial_id > 0, function ($query) use ($filial_id) {
			return $query->where('filial_id', $filial_id);
		});

		$estado = $request->estado;
		if($estado == 1){
			$xmlMdfe->where('estado', 'APROVADO');
		}else{
			$xmlMdfe->where('estado', 'CANCELADO');
		}
		$xmlMdfe = $xmlMdfe->get();

		if(count($xmlMdfe) > 0){
			try{

				$zip_file = public_path('zips') . '/xmlmdfe-'.$cnpj.'.zip';

				$zip = new \ZipArchive();
				$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
				if($estado == 1){
					foreach($xmlMdfe as $x){
						if(file_exists(public_path('xml_mdfe/').$x->chave. '.xml')){
							$zip->addFile(public_path('xml_mdfe/').$x->chave. '.xml', $x->chave. '.xml');
							$contZips++;
						}
					}
				}else{
					foreach($xmlMdfe as $x){
						if(file_exists(public_path('xml_mdfe_cancelada/').$x->chave. '.xml')){
							$zip->addFile(public_path('xml_mdfe_cancelada/').$x->chave. '.xml', $x->chave. '.xml');
							$contZips++;
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
		whereBetween('created_at', [
			$this->parseDate($request->data_inicial), 
			$this->parseDate($request->data_final, true)])
		->where('empresa_id', $this->empresa_id)
		->where('numero_emissao', '>', 0)
		->when($filial_id > 0, function ($query) use ($filial_id) {
			return $query->where('filial_id', $filial_id);
		});

		if($estado == 1){
			$xmlEntrada->where('estado', 'APROVADO');
		}else{
			$xmlEntrada->where('estado', 'CANCELADO');
		}
		$xmlEntrada = $xmlEntrada->get();

		if(count($xmlEntrada) > 0){

			try{

				$zip_file = public_path('zips') . '/xmlEntrada-'.$cnpj.'.zip';

				$zip = new \ZipArchive();
				$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

				if($estado == 1){
					foreach($xmlEntrada as $x){
						if(file_exists(public_path('xml_entrada_emitida/').$x->chave. '.xml')){
							$zip->addFile(public_path('xml_entrada_emitida/').$x->chave. '.xml', $x->chave. '.xml');
							$contZips++;
						}
					}
				}else{
					foreach($xmlEntrada as $x){
						if(file_exists(public_path('xml_nfe_entrada_cancelada/').$x->chave. '.xml')){
							$zip->addFile(public_path('xml_nfe_entrada_cancelada/').$x->chave. '.xml', $x->chave. '.xml');
							$contZips++;
						}
					}
				}
				$zip->close();

			}catch(\Exception $e){
				// echo $e->getMessage();
			}

		}

		$xmlNfse = NotaServico::
		whereBetween('created_at', [
			$this->parseDate($request->data_inicial), 
			$this->parseDate($request->data_final, true)])
		->where('empresa_id', $this->empresa_id);
		if($estado == 1){
			$xmlNfse->where('estado', 'aprovado');
		}else{
			$xmlNfse->where('estado', 'cancelado');
		}
		$xmlNfse = $xmlNfse->get();

		if(count($xmlNfse) > 0){
			try{

				$zip_file = public_path('zips') . '/xmlNfse-'.$cnpj.'.zip';

				$zip = new \ZipArchive();
				$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);


				foreach($xmlNfse as $x){

					if(file_exists(public_path('nfse_doc/').$x->uuid. '.xml')){
						$zip->addFile(public_path('nfse_doc/').$x->uuid. '.xml', $x->uuid. '.xml');
						$contZips++;
					}
				}

				$zip->close();

			}catch(\Exception $e){
				// echo $e->getMessage();
			}

		}

		$xmlDevolucao = Devolucao::
		whereBetween('created_at', [
			$this->parseDate($request->data_inicial), 
			$this->parseDate($request->data_final, true)])
		->where('empresa_id', $this->empresa_id)
		->when($filial_id > 0, function ($query) use ($filial_id) {
			return $query->where('filial_id', $filial_id);
		});
		// 1- Aprovado, 3 - Cancelado
		if($estado == 1){
			$xmlDevolucao->where('estado', 1);
		}else{
			$xmlDevolucao->where('estado', 3);
		}
		$xmlDevolucao = $xmlDevolucao->get();

		if(count($xmlDevolucao) > 0){

			try{

				$zip_file = public_path('zips') . '/xmlDevolucao-'.$cnpj.'.zip';

				$zip = new \ZipArchive();
				$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

				if($estado == 1){
					foreach($xmlDevolucao as $x){
						if(file_exists(public_path('xml_devolucao/').$x->chave_gerada. '.xml')){
							$zip->addFile(public_path('xml_devolucao/').$x->chave_gerada. '.xml', $x->chave_gerada. '.xml');
							$contZips++;
						}
					}
				}else{
					foreach($xmlDevolucao as $x){
						if(file_exists(public_path('xml_devolucao_cancelada/').$x->chave_gerada. '.xml')){
							$zip->addFile(public_path('xml_devolucao_cancelada/').$x->chave_gerada. '.xml', $x->chave_gerada. '.xml');
							$contZips++;
						}
					}
				}
				$zip->close();

			}catch(\Exception $e){
				// echo $e->getMessage();
			}

		}

		//Entrada Dfe e Compra fiscal

		$xmlCompraFiscal = Compra::
		whereBetween('data_emissao', [
			$this->parseDate($request->data_inicial), 
			$this->parseDate($request->data_final, true)])
		->where('empresa_id', $this->empresa_id)
		->when($filial_id > 0, function ($query) use ($filial_id) {
			return $query->where('filial_id', $filial_id);
		})
		->where('nf', '>', 0)->get();


		$xmlDfe = ManifestaDfe::
		whereBetween('data_emissao', [
			$this->parseDate($request->data_inicial), 
			$this->parseDate($request->data_final, true)])
		->where('empresa_id', $this->empresa_id)
		->when($filial_id > 0, function ($query) use ($filial_id) {
			return $query->where('filial_id', $filial_id);
		})
		->get();

		$xmlFiscalCompra = [];

		if(sizeof($xmlCompraFiscal) > 0 || sizeof($xmlDfe) > 0){

			try{

				$zip_file = public_path('zips') . '/xmlCompraFiscal-'.$cnpj.'.zip';

				$zip = new \ZipArchive();
				$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

				foreach($xmlCompraFiscal as $x){
					if(file_exists(public_path('xml_entrada/').$x->chave)){
						$zip->addFile(public_path('xml_entrada/').$x->chave, $x->chave.'.xml');
						$data = [
							'id' => $x->id,
							'chave' => $x->chave,
							'data_emissao' => $x->data_emissao,
						];
						array_push($xmlFiscalCompra, $data);
					}
				}

				foreach($xmlDfe as $x){
					if(file_exists(public_path('xml_dfe/').$x->chave.'.xml')){
						$zip->addFile(public_path('xml_dfe/').$x->chave.'.xml', $x->chave.'.xml');
						$data = [
							'id' => $x->id,
							'chave' => $x->chave,
							'data_emissao' => $x->data_emissao,
						];
						array_push($xmlFiscalCompra, $data);
					}
				}

				$zip->close();

			}catch(\Exception $e){
				// echo $e->getMessage();
			}

			// foreach($xmlCompraFiscal as $x){
			// 	$data = [
			// 		'id' => $x->id,
			// 		'chave' => $x->chave,
			// 		'data_emissao' => $x->data_emissao,
			// 	];
			// 	array_push($xmlFiscalCompra, $data);
			// }

			// foreach($xmlDfe as $x){
			// 	$data = [
			// 		'id' => $x->id,
			// 		'chave' => $x->chave,
			// 		'data_emissao' => $x->data_emissao,
			// 	];
			// 	array_push($xmlFiscalCompra, $data);
			// }

		}

		//Fim entrada Dfe e Compra fiscal

		$dataInicial = str_replace("/", "-", $request->data_inicial);
		$dataFinal = str_replace("/", "-", $request->data_final);

		return view('enviarXml/list')
		->with('xml', $xml)
		->with('xmlNfce', $xmlNfce)
		->with('xmlCte', $xmlCte)
		->with('contZips', $contZips)
		->with('xmlMdfe', $xmlMdfe)
		->with('filial_id', $filial_id)
		->with('estado', $request->estado)
		->with('xmlEntrada', $xmlEntrada)
		->with('xmlNfse', $xmlNfse)
		->with('xmlDevolucao', $xmlDevolucao)
		->with('dataInicial', $dataInicial)
		->with('dataFinal', $dataFinal)
		->with('xmlCompraFiscal', $xmlFiscalCompra)
		->with('title', 'Enviar XML');
	}

	private function getFileXml($path){
		$cnpj = $this->getCnpjEmpresa();

		$file = public_path('zips') . "/$path-$cnpj.zip";

		if(file_exists($file)){
			return [
				'file' => $file,
				'cnpj' => $cnpj
			];
		}

		$empresa = Empresa::find($this->empresa_id);
		$filiais = Filial::where('empresa_id', $this->empresa_id)->get();
		foreach($filiais as $f){
			$cnpj = preg_replace('/[^0-9]/', '', $f->cnpj);

			$file = public_path('zips') . "/$path-$cnpj.zip";
			if(file_exists($file)){
				return [
					'file' => $file,
					'cnpj' => $cnpj
				];
			}
		}
		return [];
	}

	public function download(){
		// $public = env('SERVIDOR_WEB') ? 'public/' : '';

		$file = $this->getFileXml("xml");
		if(isset($file['file'])){
			$this->xmlEnviado('nfe');

			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="xmls_nfe_'.$file['cnpj'].'.zip"');
			readfile($file['file']);
		}else{
			echo "Arquivo não encontrado!";
		}

	}

	private function xmlEnviado($tipo){
		XmlEnviado::create([
			'empresa_id' => $this->empresa_id,
			'tipo' => $tipo
		]);
	}

	public function downloadEntrada(){

		$file = $this->getFileXml("xmlEntrada");
		if(isset($file['file'])){

			$this->xmlEnviado('nfe');
			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="xmls_entrada_'.$file['cnpj'].'.zip"');
			readfile($file['file']);
		}else{
			echo "Arquivo não encontrado!";
		}

	}

	public function downloadDevolucao(){

		$file = $this->getFileXml("xmlDevolucao");
		if(isset($file['file'])){
			$this->xmlEnviado('devolucao');

			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="xmls_devolucao_'.$file['cnpj'].'.zip"');
			readfile($file['file']);
		}else{
			echo "Arquivo não encontrado!";
		}
	}

	public function downloadNfse(){
		$cnpj = $this->getCnpjEmpresa();

		$file = public_path('zips') . "/xmlNfse-$cnpj.zip";

		if(isset($file)){
			$this->xmlEnviado('devolucao');

			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="xmls_nfse_'.$cnpj.'.zip"');
			readfile($file);
		}else{
			echo "Arquivo não encontrado!";
		}
	}

	public function downloadNfce(){

		$file = $this->getFileXml("xmlnfce");
		if(isset($file['file'])){
			$this->xmlEnviado('nfce');

			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="xmls_nfce_'.$file['cnpj'].'.zip"');
			readfile($file['file']);
		}else{
			echo "Arquivo não encontrado!";
		}
	}

	public function downloadCte(){

		$file = $this->getFileXml("xmlcte");
		if(isset($file['file'])){
			$this->xmlEnviado('cte');

			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="xmls_cte_'.$file['cnpj'].'.zip"');
			readfile($file['file']);
		}else{
			echo "Arquivo não encontrado!";
		}
	}

	public function downloadCompraFiscal(){
		$file = $this->getFileXml("xmlCompraFiscal");
		if(isset($file['file'])){
			$this->xmlEnviado('nfe');

			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="xmls_comprafiscal_'.$file['cnpj'].'.zip"');
			readfile($file['file']);
		}else{
			echo "Arquivo não encontrado!";
		}

	}

	public function downloadMdfe(){

		$file = $this->getFileXml("xmlmdfe");
		if(isset($file['file'])){
			$this->xmlEnviado('mdfe');

			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="xmls_mdfe_'.$file['cnpj'].'.zip"');
			readfile($file['file']);
		}else{
			echo "Arquivo não encontrado!";
		}
	}

	private function parseDate($date, $plusDay = false){
		if($plusDay == false)
			return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
		else
			return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
	}

	public function email($dataInicial, $dataFinal){

		$empresa = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		if($empresa->usar_email_proprio){

			$file = $this->getFileXml("xml");
			$fileDir = public_path('zips') . '/xml-'.$file['cnpj'].'.zip';
			$this->xmlEnviado('nfe');

			$subject = 'XML de NFe';
			$body = '<h1>Envio de XML</h1>';
			$body .= '<h3>Empresa: '.$empresa->razao_social.'</h3>';
			$body .= '<h3>CNPJ: '.$empresa->cnpj.'</h3>';
			$body .= '<h4>Período: ' . $dataInicial . ' - ' . $dataFinal . '</h4>';
			$send = $this->enviaEmailPHPMailer($fileDir, $subject, $body);
			if(!isset($send['erro'])){
				session()->flash('mensagem_sucesso', 'Email enviado');
			}else{
				session()->flash('mensagem_erro', $send['erro']);
			}

			return redirect()->back();

		}else{
			$res = Mail::send('mail.xml', ['data_inicial' => $dataInicial, 'data_final' => $dataFinal,
				'empresa' => $empresa->razao_social, 'cnpj' => $empresa->cnpj, 'tipo' => 'NFe'], function($m){

					$file = $this->getFileXml("xml");
					if(!isset($file['cnpj'])){
						session()->flash('mensagem_erro', 'Arquivo não encontrado!');
						return redirect()->back();
					}
					$fileDir = public_path('zips') . '/xml-'.$file['cnpj'].'.zip';
					$this->xmlEnviado('nfe');

					$escritorio = EscritorioContabil::
					where('empresa_id', $this->empresa_id)
					->first();

					if($escritorio == null){
						echo "<h1>Configure o email do escritório <a target='_blank' href='/escritorio'>aqui</a></h1>";
						die();
					}
					$nomeEmail = env('MAIL_NAME');
					$nomeEmail = str_replace("_", " ", $nomeEmail);
					$m->from(env('MAIL_USERNAME'), $nomeEmail);
					$m->subject('Envio de XML');
					$m->attach($fileDir);
					$m->to($escritorio->email);
					return 1;
				});
			if($res){
				session()->flash('mensagem_sucesso', 'Email enviado');
				return redirect()->back();
			}
		}
	}

	public function emailEntrada($dataInicial, $dataFinal){

		$empresa = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		if($empresa->usar_email_proprio){

			$file = $this->getFileXml("xmlEntrada");
			$fileDir = public_path('zips') . '/xmlEntrada-'.$file['cnpj'].'.zip';
			$this->xmlEnviado('nfe');

			$subject = 'XML de NFe Entrada';
			$body = '<h1>Envio de XML</h1>';
			$body .= '<h3>Empresa: '.$empresa->razao_social.'</h3>';
			$body .= '<h3>CNPJ: '.$empresa->cnpj.'</h3>';
			$body .= '<h4>Período: ' . $dataInicial . ' - ' . $dataFinal . '</h4>';
			$send = $this->enviaEmailPHPMailer($fileDir, $subject, $body);
			if(!isset($send['erro'])){
				session()->flash('mensagem_sucesso', 'Email enviado');
			}else{
				session()->flash('mensagem_erro', $send['erro']);
			}

			return redirect()->back();

		}else{
			Mail::send('mail.xml', ['data_inicial' => $dataInicial, 'data_final' => $dataFinal,
				'empresa' => $empresa->razao_social, 'cnpj' => $empresa->cnpj, 'tipo' => 'NFe Entrada'], function($m){
				// $public = env('SERVIDOR_WEB') ? 'public/' : '';
					$file = $this->getFileXml("xmlEntrada");
					$fileDir = public_path('zips') . '/xmlEntrada-'.$file['cnpj'].'.zip';

					$escritorio = EscritorioContabil::
					where('empresa_id', $this->empresa_id)
					->first();
					$this->xmlEnviado('nfe');

					if($escritorio == null){
						echo "<h1>Configure o email do escritório <a target='_blank' href='/escritorio'>aqui</a></h1>";
						die();
					}
					$nomeEmail = env('MAIL_NAME');
					$nomeEmail = str_replace("_", " ", $nomeEmail);
					$m->from(env('MAIL_USERNAME'), $nomeEmail);
					$m->subject('Envio de XML');
					$m->attach($fileDir);
					$m->to($escritorio->email);
				});

			session()->flash('mensagem_sucesso', 'Email enviado');
			return redirect()->back();

		}
	}

	public function emailNfse($dataInicial, $dataFinal){

		$empresa = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		if($empresa->usar_email_proprio){
			$cnpj = $this->getCnpjEmpresa();

			$fileDir = public_path('zips') . '/xmlNfse-'.$cnpj.'.zip';
			$this->xmlEnviado('nfse');

			$subject = 'XML de NFe Devolução';
			$body = '<h1>Envio de XML</h1>';
			$body .= '<h3>Empresa: '.$empresa->razao_social.'</h3>';
			$body .= '<h3>CNPJ: '.$empresa->cnpj.'</h3>';
			$body .= '<h4>Período: ' . $dataInicial . ' - ' . $dataFinal . '</h4>';
			$send = $this->enviaEmailPHPMailer($fileDir, $subject, $body);
			if(!isset($send['erro'])){
				session()->flash('mensagem_sucesso', 'Email enviado');
			}else{
				session()->flash('mensagem_erro', $send['erro']);
			}

			return redirect()->back();

		}else{
			Mail::send('mail.xml', ['data_inicial' => $dataInicial, 'data_final' => $dataFinal,
				'empresa' => $empresa->razao_social, 'cnpj' => $empresa->cnpj, 'tipo' => 'NFe Devolução'], function($m){
				// $public = env('SERVIDOR_WEB') ? 'public/' : '';
					$cnpj = $this->getCnpjEmpresa();

					$fileDir = public_path('zips') . '/xmlNfse-'.$cnpj.'.zip';

					$escritorio = EscritorioContabil::
					where('empresa_id', $this->empresa_id)
					->first();
					$this->xmlEnviado('nfse');

					if($escritorio == null){
						echo "<h1>Configure o email do escritório <a target='_blank' href='/escritorio'>aqui</a></h1>";
						die();
					}
					$nomeEmail = env('MAIL_NAME');
					$nomeEmail = str_replace("_", " ", $nomeEmail);
					$m->from(env('MAIL_USERNAME'), $nomeEmail);
					$m->subject('Envio de XML');
					$m->attach($fileDir);
					$m->to($escritorio->email);
				});
			session()->flash('mensagem_sucesso', 'Email enviado');
			return redirect()->back();

		}
	}

	public function emailDevolucao($dataInicial, $dataFinal){

		$empresa = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		if($empresa->usar_email_proprio){
			$file = $this->getFileXml("xmlDevolucao");
			$fileDir = public_path('zips') . '/xmlDevolucao-'.$file['cnpj'].'.zip';
			$this->xmlEnviado('devolucao');

			$subject = 'XML de NFe Devolução';
			$body = '<h1>Envio de XML</h1>';
			$body .= '<h3>Empresa: '.$empresa->razao_social.'</h3>';
			$body .= '<h3>CNPJ: '.$empresa->cnpj.'</h3>';
			$body .= '<h4>Período: ' . $dataInicial . ' - ' . $dataFinal . '</h4>';
			$send = $this->enviaEmailPHPMailer($fileDir, $subject, $body);
			if(!isset($send['erro'])){
				session()->flash('mensagem_sucesso', 'Email enviado');
			}else{
				session()->flash('mensagem_erro', $send['erro']);
			}

			return redirect()->back();

		}else{
			$res = Mail::send('mail.xml', ['data_inicial' => $dataInicial, 'data_final' => $dataFinal,
				'empresa' => $empresa->razao_social, 'cnpj' => $empresa->cnpj, 'tipo' => 'NFe Devolução'], function($m){
				// $public = env('SERVIDOR_WEB') ? 'public/' : '';
					$file = $this->getFileXml("xmlDevolucao");
					$fileDir = public_path('zips') . '/xmlDevolucao-'.$file['cnpj'].'.zip';

					$escritorio = EscritorioContabil::
					where('empresa_id', $this->empresa_id)
					->first();
					$this->xmlEnviado('devolucao');

					if($escritorio == null){
						echo "<h1>Configure o email do escritório <a target='_blank' href='/escritorio'>aqui</a></h1>";
						die();
					}
					$nomeEmail = env('MAIL_NAME');
					$nomeEmail = str_replace("_", " ", $nomeEmail);
					$m->from(env('MAIL_USERNAME'), $nomeEmail);
					$m->subject('Envio de XML');
					$m->attach($fileDir);
					$m->to($escritorio->email);
					return true;
				});
			if($res){
				session()->flash('mensagem_sucesso', 'Email enviado');
				return redirect()->back();
			}
		}
	}

	public function emailCompraFiscal($dataInicial, $dataFinal){

		$empresa = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		if($empresa->usar_email_proprio){

			$file = $this->getFileXml("xmlCompraFiscal");
			$fileDir = public_path('zips') . '/xmlCompraFiscal-'.$file['cnpj'].'.zip';
			$this->xmlEnviado('nfe');

			$subject = 'XML de Compra Fiscal';
			$body = '<h1>Envio de XML</h1>';
			$body .= '<h3>Empresa: '.$empresa->razao_social.'</h3>';
			$body .= '<h3>CNPJ: '.$empresa->cnpj.'</h3>';
			$body .= '<h4>Período: ' . $dataInicial . ' - ' . $dataFinal . '</h4>';
			$send = $this->enviaEmailPHPMailer($fileDir, $subject, $body);
			if(!isset($send['erro'])){
				session()->flash('mensagem_sucesso', 'Email enviado');
			}else{
				session()->flash('mensagem_erro', $send['erro']);
			}

			return redirect()->back();

		}else{
			$res = Mail::send('mail.xml', ['data_inicial' => $dataInicial, 'data_final' => $dataFinal,
				'empresa' => $empresa->razao_social, 'cnpj' => $empresa->cnpj, 'tipo' => 'Xml Compra fiscal'], function($m){
				// $public = env('SERVIDOR_WEB') ? 'public/' : '';
					$file = $this->getFileXml("xmlCompraFiscal");
					$fileDir = public_path('zips') . '/xmlCompraFiscal-'.$file['cnpj'].'.zip';

					$escritorio = EscritorioContabil::
					where('empresa_id', $this->empresa_id)
					->first();
					$this->xmlEnviado('nfe');

					if($escritorio == null){
						echo "<h1>Configure o email do escritório <a target='_blank' href='/escritorio'>aqui</a></h1>";
						die();
					}
					$nomeEmail = env('MAIL_NAME');
					$nomeEmail = str_replace("_", " ", $nomeEmail);
					$m->from(env('MAIL_USERNAME'), $nomeEmail);
					$m->subject('Envio de XML');
					$m->attach($fileDir);
					$m->to($escritorio->email);
					return true;
				});
			if($res){
				session()->flash('mensagem_sucesso', 'Email enviado');
				return redirect()->back();
			}
		}
	}

	private function enviaEmailPHPMailer($fileDir, $subject, $body){
		$emailConfig = EmailConfig::
		where('empresa_id', $this->empresa_id)
		->first();

		if($emailConfig == null){
			return [
				'erro' => 'Primeiramente configure seu email'
			];
		}

		$escritorio = EscritorioContabil::
		where('empresa_id', $this->empresa_id)
		->first();

		$mail = new PHPMailer(true);

		try {
			if($emailConfig->smtp_debug){
				$mail->SMTPDebug = SMTP::DEBUG_SERVER;   
			}                   
			$mail->isSMTP();                                            
			$mail->Host = $emailConfig->host;                     
			$mail->SMTPAuth = $emailConfig->smtp_auth;                                   
			$mail->Username = $emailConfig->email;                     
			$mail->Password = $emailConfig->senha;                               
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            
			$mail->Port = $emailConfig->porta; 

			$mail->setFrom($emailConfig->email, $emailConfig->nome); 
			$mail->addAddress($escritorio->email); 

			$mail->addAttachment($fileDir); 

			$mail->isHTML(true);
			$mail->CharSet = 'UTF-8';

			$mail->Subject = $subject;
			$mail->Body = $body;
			$mail->send();
			return [
				'sucesso' => true
			];
		} catch (Exception $e) {
			return [
				'erro' => $mail->ErrorInfo
			];
			// echo "Message could; not be sent. Mailer Error: {$mail->ErrorInfo}";
		}
	}

	public function emailNfce($dataInicial, $dataFinal){

		$empresa = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		if($empresa->usar_email_proprio){

			$file = $this->getFileXml("xmlnfce");

			$fileDir = public_path('zips') . '/xmlnfce-'.$file['cnpj'].'.zip';

			$this->xmlEnviado('nfce');

			$subject = 'XML de NFCe';
			$body = '<h1>Envio de XML</h1>';
			$body .= '<h3>Empresa: '.$empresa->razao_social.'</h3>';
			$body .= '<h3>CNPJ: '.$empresa->cnpj.'</h3>';
			$body .= '<h4>Período: ' . $dataInicial . ' - ' . $dataFinal . '</h4>';
			$send = $this->enviaEmailPHPMailer($fileDir, $subject, $body);
			if(!isset($send['erro'])){
				session()->flash('mensagem_sucesso', 'Email enviado');
			}else{
				session()->flash('mensagem_erro', $send['erro']);
			}

			return redirect()->back();

		}else{

			$res = Mail::send('mail.xml', ['data_inicial' => $dataInicial, 'data_final' => $dataFinal,
				'empresa' => $empresa->razao_social, 'cnpj' => $empresa->cnpj, 'tipo' => 'NFCe'], function($m){
					$escritorio = EscritorioContabil::
					where('empresa_id', $this->empresa_id)
					->first();
					if($escritorio == null){
						echo "<h1>Configure o email do escritório <a target='_blank' href='/escritorio'>aqui</a></h1>";
						die();
					}
				// $public = env('SERVIDOR_WEB') ? 'public/' : '';
					$file = $this->getFileXml("xmlnfce");

					$fileDir = public_path('zips') . '/xmlnfce-'.$file['cnpj'].'.zip';
					$this->xmlEnviado('nfce');

					$nomeEmail = env('MAIL_NAME');
					$nomeEmail = str_replace("_", " ", $nomeEmail);
					$m->from(env('MAIL_USERNAME'), $nomeEmail);
					$m->subject('Envio de XML');
					$m->attach($fileDir);
					$m->to($escritorio->email);
					return true;
				});
			if($res){
				session()->flash('mensagem_sucesso', 'Email enviado');
				return redirect()->back();
			}
		}

	}

	public function emailCte($dataInicial, $dataFinal){

		$empresa = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		if($empresa->usar_email_proprio){

			$file = $this->getFileXml("xmlcte");
			$fileDir = public_path('zips') . '/xmlcte-'.$file['cnpj'].'.zip';
			$this->xmlEnviado('cte');

			$subject = 'XML de CTe';
			$body = '<h1>Envio de XML</h1>';
			$body .= '<h3>Empresa: '.$empresa->razao_social.'</h3>';
			$body .= '<h3>CNPJ: '.$empresa->cnpj.'</h3>';
			$body .= '<h4>Período: ' . $dataInicial . ' - ' . $dataFinal . '</h4>';
			$send = $this->enviaEmailPHPMailer($fileDir, $subject, $body);
			if(!isset($send['erro'])){
				session()->flash('mensagem_sucesso', 'Email enviado');
			}else{
				session()->flash('mensagem_erro', $send['erro']);
			}

			return redirect()->back();

		}else{
			$res = Mail::send('mail.xml', ['data_inicial' => $dataInicial, 'data_final' => $dataFinal,
				'empresa' => $empresa->razao_social, 'cnpj' => $empresa->cnpj, 'tipo' => 'CTe'], function($m){
					$escritorio = EscritorioContabil::
					where('empresa_id', $this->empresa_id)
					->first();

					if($escritorio == null){
						echo "<h1>Configure o email do escritório <a target='_blank' href='/escritorio'>aqui</a></h1>";
						die();
					}
				// $public = env('SERVIDOR_WEB') ? 'public/' : '';
					$this->xmlEnviado('cte');

					$file = $this->getFileXml("xmlcte");
					$fileDir = public_path('zips') . '/xmlcte-'.$file['cnpj'].'.zip';

					$nomeEmail = env('MAIL_NAME');
					$nomeEmail = str_replace("_", " ", $nomeEmail);
					$m->from(env('MAIL_USERNAME'), $nomeEmail);
					$m->subject('Envio de XML');
					$m->attach($fileDir);
					$m->to($escritorio->email);
					return true;
				});
			if($res){
				session()->flash('mensagem_sucesso', 'Email enviado');
				return redirect()->back();
			}
		}

	}

	public function emailMdfe($dataInicial, $dataFinal){

		$empresa = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		if($empresa->usar_email_proprio){

			$file = $this->getFileXml("xmlmdfe");
			$fileDir = public_path('zips') . '/xmlmdfe-'.$file['cnpj'].'.zip';
			$this->xmlEnviado('mdfe');

			$subject = 'XML de MDFe';
			$body = '<h1>Envio de XML</h1>';
			$body .= '<h3>Empresa: '.$empresa->razao_social.'</h3>';
			$body .= '<h3>CNPJ: '.$empresa->cnpj.'</h3>';
			$body .= '<h4>Período: ' . $dataInicial . ' - ' . $dataFinal . '</h4>';
			$send = $this->enviaEmailPHPMailer($fileDir, $subject, $body);
			if(!isset($send['erro'])){
				session()->flash('mensagem_sucesso', 'Email enviado');
			}else{
				session()->flash('mensagem_erro', $send['erro']);
			}

			return redirect()->back();

		}else{
			$res = Mail::send('mail.xml', ['data_inicial' => $dataInicial, 'data_final' => $dataFinal,
				'empresa' => $empresa->razao_social, 'cnpj' => $empresa->cnpj, 'tipo' => 'MDFe'], function($m){
					$escritorio = EscritorioContabil::
					where('empresa_id', $this->empresa_id)
					->first();
					if($escritorio == null){
						echo "<h1>Configure o email do escritório <a target='_blank' href='/escritorio'>aqui</a></h1>";
						die();
					}
				// $public = env('SERVIDOR_WEB') ? 'public/' : '';
					$file = $this->getFileXml("xmlmdfe");
					$fileDir = public_path('zips') . '/xmlmdfe-'.$file['cnpj'].'.zip';
					$this->xmlEnviado('mdfe');

					$nomeEmail = env('MAIL_NAME');
					$nomeEmail = str_replace("_", " ", $nomeEmail);
					$m->from(env('MAIL_USERNAME'), $nomeEmail);
					$m->subject('Envio de XML');
					$m->attach($fileDir);
					$m->to($escritorio->email);
					return true;
				});
			if($res){
				session()->flash('mensagem_sucesso', 'Email enviado');
				return redirect()->back();
			}
		}

	}

	public function filtroCfop(Request $request){
		return view('enviarXml/filtro_cfop')
		->with('title', 'Filtro');

	}

	public function filtroCfopGet(Request $request){
		if($request->data_inicial && $request->data_final){
			$somaTotalVendas = 0;
			$cfop = $request->cfop;
			if(strlen($cfop) == 4){
				$itensVenda = ItemVenda::
			// select('item_vendas.id', \DB\Raw('sum(quantidade)'))
				selectRaw('sum(quantidade) AS qtd, sum(quantidade*valor) AS total, item_vendas.*')
				->join('vendas', 'vendas.id', '=', 'item_vendas.venda_id')
				->where('vendas.empresa_id', $this->empresa_id)
				->where('vendas.estado', 'APROVADO')
				->where('item_vendas.cfop', $cfop)
				->whereBetween('item_vendas.created_at', [
					$this->parseDate($request->data_inicial) . " 00:00:00", 
					$this->parseDate($request->data_final) . " 23:59:59", 
				])
				->groupBy('item_vendas.produto_id')
				->get();

				$itensVendaCaixa = ItemVendaCaixa::
			// select('item_vendas.id', \DB\Raw('sum(quantidade)'))
				selectRaw('sum(quantidade) AS qtd, sum(quantidade*valor) AS total, item_venda_caixas.*')
				->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
				->where('venda_caixas.empresa_id', $this->empresa_id)
				->where('venda_caixas.estado', 'APROVADO')
				->where('item_venda_caixas.cfop', $cfop)
				->whereBetween('item_venda_caixas.created_at', [
					$this->parseDate($request->data_inicial) . " 00:00:00", 
					$this->parseDate($request->data_final) . " 23:59:59", 
				])
				->groupBy('item_venda_caixas.produto_id')
				->get();


				$itens = $this->uneObjetos($itensVenda, $itensVendaCaixa);
				$somaTotalVendas = $this->somaTotalVendas($this->parseDate($request->data_inicial), $this->parseDate($request->data_final));

				// $somaTotalVendas = 0;
				return view('enviarXml/filtro_cfop')
				->with('itens', $itens)
				->with('somaTotalVendas', $somaTotalVendas)
				->with('cfop', $request->cfop)
				->with('dataInicial', $request->data_inicial)
				->with('dataFinal', $request->data_final)
				->with('title', 'Filtro');
			}else{
				//agrupar CFOP
				$cfops = $this->getCfops($this->parseDate($request->data_inicial) . " 00:00:00",
					$this->parseDate($request->data_final) . " 23:59:59");
				$itens = [];
				foreach($cfops as $cfop){
					$itensVenda = ItemVenda::
					selectRaw('sum(quantidade) AS qtd, sum(quantidade*valor) AS total, item_vendas.*')
					->join('vendas', 'vendas.id', '=', 'item_vendas.venda_id')
					->where('vendas.empresa_id', $this->empresa_id)
					->where('vendas.estado', 'APROVADO')
					->where('item_vendas.cfop', $cfop)
					->whereBetween('item_vendas.created_at', [
						$this->parseDate($request->data_inicial) . " 00:00:00", 
						$this->parseDate($request->data_final) . " 23:59:59", 
					])
					->groupBy('item_vendas.produto_id')
					->get();

					$itensVendaCaixa = ItemVendaCaixa::
					selectRaw('sum(quantidade) AS qtd, sum(quantidade*valor) AS total, item_venda_caixas.*')
					->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
					->where('venda_caixas.empresa_id', $this->empresa_id)
					->where('venda_caixas.estado', 'APROVADO')
					->where('item_venda_caixas.cfop', $cfop)
					->whereBetween('item_venda_caixas.created_at', [
						$this->parseDate($request->data_inicial) . " 00:00:00", 
						$this->parseDate($request->data_final) . " 23:59:59", 
					])
					->groupBy('item_venda_caixas.produto_id')
					->get();

					$temp = $this->uneObjetos($itensVenda, $itensVendaCaixa);

					array_push($itens, [
						'cfop' => $cfop,
						'itens' => $temp
					]);


				}

				$somaTotalVendas = $this->somaTotalVendas($this->parseDate($request->data_inicial), $this->parseDate($request->data_final));

				return view('enviarXml/filtro_cfop_group')
				->with('itens', $itens)
				->with('somaTotalVendas', $somaTotalVendas)
				->with('cfop', $request->cfop)
				->with('dataInicial', $request->data_inicial)
				->with('dataFinal', $request->data_final)
				->with('title', 'Filtro');
			}



		}else{
			session()->flash('mensagem_erro', 'Informe data inicial e final');

			return redirect('enviarXml/filtroCfop');
		}
	}

	private function getCfops($dataInicial, $dataFinal){
		$cfops = [];

		$itensVenda = ItemVenda::
		selectRaw('distinct(item_vendas.cfop) as cfop')
		->join('vendas', 'vendas.id', '=', 'item_vendas.venda_id')
		->where('vendas.empresa_id', $this->empresa_id)
		->where('vendas.estado', 'APROVADO')
		->whereBetween('item_vendas.created_at', [
			$dataInicial, 
			$dataFinal, 
		])
		->get();

		$itensVendaCaixa = ItemVendaCaixa::
		selectRaw('distinct(item_venda_caixas.cfop) as cfop')
		->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
		->where('venda_caixas.empresa_id', $this->empresa_id)
		->where('venda_caixas.estado', 'APROVADO')
		->whereBetween('item_venda_caixas.created_at', [
			$dataInicial, 
			$dataFinal, 
		])
		->groupBy('item_venda_caixas.produto_id')
		->get();

		foreach($itensVenda as $i){
			if($i->cfop != "0"){
				if(!in_array($i->cfop, $cfops)){
					array_push($cfops, $i->cfop);
				}
			}
		}

		foreach($itensVendaCaixa as $i){
			if($i->cfop != "0"){
				if(!in_array($i->cfop, $cfops)){
					array_push($cfops, $i->cfop);
				}
			}
		}

		return $cfops;
	}

	private function somaTotalVendas($dataInicial, $dataFinal){

		$vendas = Venda::
			// select('item_vendas.id', \DB\Raw('sum(quantidade)'))
		selectRaw('sum(valor_total) AS soma')
		->where('empresa_id', $this->empresa_id)
		->where('estado', 'APROVADO')
		->whereBetween('created_at', [
			$dataInicial . " 00:00:00", 
			$dataFinal . " 23:59:59", 
		])
		->first();

		$vendasCaixa = VendaCaixa::
			// select('item_vendas.id', \DB\Raw('sum(quantidade)'))
		selectRaw('sum(valor_total) AS soma')
		->where('empresa_id', $this->empresa_id)
		->where('estado', 'APROVADO')
		->whereBetween('created_at', [
			$dataInicial . " 00:00:00", 
			$dataFinal . " 23:59:59", 
		])
		->first();

		return $vendas->soma + $vendasCaixa->soma;
	}

	private function uneObjetos($vendas, $vendaCaixas){
		$temp = [];
		foreach($vendas as $v){
			array_push($temp, $v);
		}

		foreach($vendaCaixas as $v){
			$inserido = false;
			for($i=0; $i<sizeof($temp); $i++){
				if($v->produto_id == $temp[$i]->produto_id){
					$temp[$i]->qtd += $v->qtd;
					$temp[$i]->total += $v->total;
					$inserido = true;
				}
			}

			if($inserido == false){
				array_push($temp, $v);
			}
		}
		return $temp;
	}

	public function filtroCfopImprimir(Request $request){
		$dataInicial = $request->dataInicial;
		$dataFinal = $request->dataFinal;
		$cfop = $request->cfop;
		$percentual = $request->percentual;
		$somaTotalVendas = $request->somaTotalVendas;

		$itensVenda = ItemVenda::
			// select('item_vendas.id', \DB\Raw('sum(quantidade)'))
		selectRaw('sum(quantidade) AS qtd, sum(quantidade*valor) AS total, item_vendas.*')
		->join('vendas', 'vendas.id', '=', 'item_vendas.venda_id')
		->where('vendas.empresa_id', $this->empresa_id)
		->where('vendas.estado', 'APROVADO')
		->where('item_vendas.cfop', $cfop)
		->whereBetween('item_vendas.created_at', [
			$this->parseDate($dataInicial) . " 00:00:00", 
			$this->parseDate($dataFinal) . " 23:59:59", 
		])
		->groupBy('item_vendas.produto_id')
		->get();

		$itensVendaCaixa = ItemVendaCaixa::
			// select('item_vendas.id', \DB\Raw('sum(quantidade)'))
		selectRaw('sum(quantidade) AS qtd, sum(quantidade*valor) AS total, item_venda_caixas.*')
		->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
		->where('venda_caixas.empresa_id', $this->empresa_id)
		->where('venda_caixas.estado', 'APROVADO')
		->where('item_venda_caixas.cfop', $cfop)
		->whereBetween('item_venda_caixas.created_at', [
			$this->parseDate($dataInicial) . " 00:00:00", 
			$this->parseDate($dataFinal) . " 23:59:59", 
		])
		->groupBy('item_venda_caixas.produto_id')
		->get();

		$itens = $this->uneObjetos($itensVenda, $itensVendaCaixa);

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$p = view('enviarXml/print')
		->with('objeto', $itens)
		->with('dataInicial', $dataInicial)
		->with('dataFinal', $dataFinal)
		->with('cfop', $cfop)
		->with('percentual', $percentual)
		->with('somaTotalVendas', $somaTotalVendas)

		->with('config', $config);

		// return $p;

		$options = new Options();
		$options->set('isRemoteEnabled', TRUE);
		$domPdf = new Dompdf($options);

		$domPdf->loadHtml($p);

		$domPdf->setPaper("A4");
		$domPdf->render();
			// $domPdf->stream("orcamento.pdf", ["Attachment" => false]);
		$domPdf->stream("relatorio_$cfop.pdf");
	}

	public function filtroCfopImprimirGroup(Request $request){
		$dataInicial = $request->dataInicial;
		$dataFinal = $request->dataFinal;
		$cfop = $request->cfop;
		$percentual = $request->percentual;
		$somaTotalVendas = $request->somaTotalVendas;
		$itens = [];

		$cfops = $this->getCfops($this->parseDate($dataInicial) . " 00:00:00",
			$this->parseDate($dataFinal) . " 23:59:59");

		$itens = [];
		foreach($cfops as $cfop){
			$itensVenda = ItemVenda::
			selectRaw('sum(quantidade) AS qtd, sum(quantidade*valor) AS total, item_vendas.*')
			->join('vendas', 'vendas.id', '=', 'item_vendas.venda_id')
			->where('vendas.empresa_id', $this->empresa_id)
			->where('vendas.estado', 'APROVADO')
			->where('item_vendas.cfop', $cfop)
			->whereBetween('item_vendas.created_at', [
				$this->parseDate($dataInicial) . " 00:00:00", 
				$this->parseDate($dataFinal) . " 23:59:59", 
			])
			->groupBy('item_vendas.produto_id')
			->get();

			$itensVendaCaixa = ItemVendaCaixa::
			selectRaw('sum(quantidade) AS qtd, sum(quantidade*valor) AS total, item_venda_caixas.*')
			->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
			->where('venda_caixas.empresa_id', $this->empresa_id)
			->where('venda_caixas.estado', 'APROVADO')
			->where('item_venda_caixas.cfop', $cfop)
			->whereBetween('item_venda_caixas.created_at', [
				$this->parseDate($dataInicial) . " 00:00:00", 
				$this->parseDate($dataFinal) . " 23:59:59", 
			])
			->groupBy('item_venda_caixas.produto_id')
			->get();


			$temp = $this->uneObjetos($itensVenda, $itensVendaCaixa);

			array_push($itens, [
				'cfop' => $cfop,
				'itens' => $temp
			]);

		}

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$p = view('enviarXml/print_group')
		->with('objeto', $itens)
		->with('dataInicial', $dataInicial)
		->with('dataFinal', $dataFinal)
		->with('cfop', $cfop)
		->with('cfop', $cfop)
		->with('percentual', $percentual)
		->with('somaTotalVendas', $somaTotalVendas)
		->with('config', $config);

		// return $p;

		$options = new Options();
		$options->set('isRemoteEnabled', TRUE);
		$domPdf = new Dompdf($options);

		$domPdf->loadHtml($p);

		$domPdf->setPaper("A4");
		$domPdf->render();
			// $domPdf->stream("orcamento.pdf", ["Attachment" => false]);
		$domPdf->stream("relatorio_$cfop.pdf");
	}

	public function downloadAll(){
		$cnpj = $this->getCnpjEmpresa();
		// echo $cnpj;

		$fileList = glob(public_path("zips/*")); 
		$files = [];
		$zip = new \ZipArchive();
		$zip_file = public_path('zips') . '/zip-'.$cnpj.'.zip';

		$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

		foreach($fileList as $filename){
			if(is_file($filename)){
				if (str_contains($filename, $cnpj)) {
					if(file_exists($filename)){

						$fileStr = explode("zips", $filename);
						$fileStr = $fileStr[1];
						$fileStr = str_replace("/", "", $fileStr);

						$f = file_get_contents($filename);
						// $zip->addFile(public_path('zips'), $f);
						$zip->addFile($filename, $fileStr);

					}
				}
			}   
		}
		$zip->close();
		return response()->download(public_path('zips') . '/zip-'.$cnpj.'.zip');

	}

	public function sendAll(){
		$cnpj = $this->getCnpjEmpresa();
		// echo $cnpj;

		$fileList = glob(public_path("zips/*")); 
		$files = [];
		$zip = new \ZipArchive();
		$fileDir = public_path('zips') . '/zip-'.$cnpj.'.zip';
		$zip_file = public_path('zips') . '/zip-'.$cnpj.'.zip';

		$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

		foreach($fileList as $filename){
			if(is_file($filename)){
				if (str_contains($filename, $cnpj)) {
					if(file_exists($filename)){

						$fileStr = explode("zips", $filename);
						$fileStr = $fileStr[1];
						$fileStr = str_replace("/", "", $fileStr);

						$f = file_get_contents($filename);
						// $zip->addFile(public_path('zips'), $f);
						$zip->addFile($filename, $fileStr);

					}
				}
			}   
		}
		$zip->close();

		$empresa = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		if($empresa->usar_email_proprio){


			$subject = 'XML de NFe';
			$body = '<h1>Envio de XML</h1>';
			$body .= '<h3>Empresa: '.$empresa->razao_social.'</h3>';
			$body .= '<h3>CNPJ: '.$empresa->cnpj.'</h3>';
			$body .= '<h4>Período: ' . $dataInicial . ' - ' . $dataFinal . '</h4>';
			$send = $this->enviaEmailPHPMailer($fileDir, $subject, $body);
			if(!isset($send['erro'])){
				session()->flash('mensagem_sucesso', 'Email enviado');
			}else{
				session()->flash('mensagem_erro', $send['erro']);
			}

			return redirect()->back();

		}else{

			Mail::send('mail.xml_all', [ 'empresa' => $empresa->razao_social, 'cnpj' => $empresa->cnpj], function($m) 
				use($fileDir){

				// $public = env('SERVIDOR_WEB') ? 'public/' : '';
					// $file = $this->getFileXml("xmlNfse");
					// if(!isset($file['cnpj'])){
					// 	session()->flash('mensagem_erro', 'Arquivo não encontrado!');
					// 	return redirect()->back();
					// }

					$escritorio = EscritorioContabil::
					where('empresa_id', $this->empresa_id)
					->first();

					if($escritorio == null){
						echo "<h1>Configure o email do escritório <a target='_blank' href='/escritorio'>aqui</a></h1>";
						die();
					}

					$nomeEmail = env('MAIL_NAME');
					$nomeEmail = str_replace("_", " ", $nomeEmail);
					$m->from(env('MAIL_USERNAME'), $nomeEmail);
					$m->subject('Envio de XML');
					$m->attach($fileDir);
					$m->to($escritorio->email);
				});
			echo '<h1>Email enviado</h1>';
		}


	}

	// public function emailCompraFiscal($dataInicial, $dataFinal)
 //    {
 //        $empresa = ConfigNota::where('empresa_id', $this->empresa_id)
 //        ->first();
 //        if ($empresa->usar_email_proprio) {
 //            die;
 //            $file = $this->getFileXml("xmlCompraFiscal");
 //            $fileDir = public_path('zips') . '/xmlnfce-' . $file['cnpj'] . '.zip';
 //            $this->xmlEnviado('nfce');
 //            $subject = 'XML de Compras';
 //            $body = '<h1>Envio de XML</h1>';
 //            $body .= '<h3>Empresa: ' . $empresa->razao_social . '</h3>';
 //            $body .= '<h3>CNPJ: ' . $empresa->cnpj . '</h3>';
 //            $body .= '<h4>Período: ' . $dataInicial . ' - ' . $dataFinal . '</h4>';
 //            $send = $this->enviaEmailPHPMailer($fileDir, $subject, $body);
 //            if (!isset($send['erro'])) {
 //                session()->flash('flash_sucesso', 'Email enviado');
 //            } else {
 //                session()->flash('flash_erro', $send['erro']);
 //            }
 //            return redirect()->back();
 //        } else {
 //            Mail::send('mail.xml', [
 //                'data_inicial' => $dataInicial, 'data_final' => $dataFinal,
 //                'empresa' => $empresa->razao_social, 'cnpj' => $empresa->cnpj, 'tipo' => 'NFCe'
 //            ], function ($m) {
 //                $escritorio = EscritorioContabil::where('empresa_id', $this->empresa_id)
 //                ->first();
 //                if ($escritorio == null) {
 //                    echo "<h1>Configure o email do escritório <a target='_blank' href='/escritorio'>aqui</a></h1>";
 //                    die();
 //                }
 //                // $public = getenv('SERVIDOR_WEB') ? 'public/' : '';
 //                $file = $this->getFileXml("xmlCompraFiscal");
 //                $fileDir = public_path('zips') . '/xmlCompraFiscal-' . $file['cnpj'] . '.zip';
 //                $this->xmlEnviado('nfce');
 //                $nomeEmail = getenv('MAIL_NAME');
 //                $nomeEmail = str_replace("_", " ", $nomeEmail);
 //                $emailEnvio = getenv('MAIL_USERNAME');
 //                $m->from($emailEnvio, $nomeEmail);

 //                $m->from(getenv('MAIL_USERNAME'), $nomeEmail);
 //                $m->subject('Envio de XML');
 //                $m->attach($fileDir);
 //                $m->to($escritorio->email);
 //            });
 //            echo '<h1>Email enviado</h1>';
 //        }
 //    }

}
