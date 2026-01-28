<?php

namespace App\Services;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use App\Models\Certificado;
use App\Models\Filial;
use NFePHP\NFe\Common\Standardize;
use NFePHP\Common\Soap\SoapCurl;

error_reporting(E_ALL);
ini_set('display_errors', 'On');

class DFeService{

	private $config; 
	private $tools;
	protected $empresa_id = null;
	public function __construct($config, $modelo, $empresa_id = null){
		if($empresa_id == null){
			$value = session('user_logged');
			$this->empresa_id = $value['empresa'];
		}else{
			$this->empresa_id = $empresa_id;
		}
		$this->config = $config;
		
		if(isset($config['is_filial']) && $config['is_filial']){
			$certificado = Filial::findOrFail($config['is_filial']);

			$this->tools = new Tools(json_encode($config), Certificate::readPfx($certificado->arquivo_certificado, $certificado->senha_certificado));
		}else{
			$certificado = Certificado::
			where('empresa_id', $this->empresa_id)
			->first();
			$this->tools = new Tools(json_encode($config), Certificate::readPfx($certificado->arquivo, $certificado->senha));
		}
		$this->tools->model($modelo);
		// $soapCurl = new SoapCurl();
		// $soapCurl->httpVersion('1.1');
		// $this->tools->loadSoapClass($soapCurl);
		$this->tools->setEnvironment(1);
	}

	public function novaConsulta($nsu, $empresa_id = null){

		if($empresa_id != null){
			$this->empresa_id = $empresa_id;
		}
		$ultNSU = $nsu;
		$maxNSU = $ultNSU;
		$loopLimit = 5;
		$iCount = 0;
		//executa a busca de DFe em loop
		$last = "";
		$imprime = false;
		$arrayDocs = [];
		$respostas = [];
		while ($ultNSU <= $maxNSU) {
			$iCount++;
			if ($iCount >= $loopLimit) {
				break;
			}
			try {

				$resp = $this->tools->sefazDistDFe($ultNSU);

				// echo "<pre>";
				// print_r($resp);
				// echo "</pre>";

				array_push($respostas, $resp);
				
				$dom = new \DOMDocument();
				$dom->loadXML($resp);

				$node = $dom->getElementsByTagName('retDistDFeInt')->item(0);
				$tpAmb = $node->getElementsByTagName('tpAmb')->item(0)->nodeValue;
				$verAplic = $node->getElementsByTagName('verAplic')->item(0)->nodeValue;
				$cStat = $node->getElementsByTagName('cStat')->item(0)->nodeValue;
				$xMotivo = $node->getElementsByTagName('xMotivo')->item(0)->nodeValue;
				$dhResp = $node->getElementsByTagName('dhResp')->item(0)->nodeValue;
				$ultNSU = $node->getElementsByTagName('ultNSU')->item(0)->nodeValue;
				$maxNSU = $node->getElementsByTagName('maxNSU')->item(0)->nodeValue;
				$lote = $node->getElementsByTagName('loteDistDFeInt')->item(0);


				if (empty($lote)) {
        //lote vazio
					continue;
				}
				if($last != $ultNSU){
					
					$last = $ultNSU;
					if (empty($lote)) {
        			//lote vazio
						continue;
					}
    				//essas tags irão conter os documentos zipados
					$docs = $lote->getElementsByTagName('docZip');

					foreach ($docs as $doc) {

						$numnsu = $doc->getAttribute('NSU');
						$schema = $doc->getAttribute('schema');

						$content = gzdecode(base64_decode($doc->nodeValue));
						$xml = simplexml_load_string($content);
						// dd($xml);
						$temp = [
							'documento' => $xml->CNPJ,
							'nome' => $xml->xNome,
							'data_emissao' => $xml->dhEmi,
							'valor' => $xml->vNF,
							'num_prot' => $xml->nProt,
							'chave' => $xml->chNFe,
							'nNf' => $xml->nNF,
							'nsu' => $ultNSU,
							'tipo' => 0,
							'fatura_salva' => false,
							'sequencia_evento' => 0,
							'empresa_id' => $this->empresa_id
						];
						
						array_push($arrayDocs, $temp);
						
						$tipo = substr($schema, 0, 6);

					}
					sleep(2);
				}
			} catch (\Exception $e) {
				// echo "service: " . $e->getMessage();
				return [
					"erro" => 1,
					"message" => $e->getMessage()
				];
			}

		}

		// return [];
		if(sizeof($arrayDocs) > 0){
			return $arrayDocs;
		}else{

			$search1 = 'Consumo Indevido';
			$xMotivo = "";
			// return $respostas;
				// $search2 = 'Rejeicao';
			foreach($respostas as $resp){
				try{
					if(preg_match("/{$search1}/i", $resp)) {
						$dom = new \DOMDocument();
						$dom->loadXML($resp);
						$xMotivo = $dom->getElementsByTagName('xMotivo')->item(0)->nodeValue;

						return [
							"erro" => 1,
							"message" => $xMotivo
						];
					}else{
						$dom = new \DOMDocument();
						$dom->loadXML($resp);
						$xMotivo = $dom->getElementsByTagName('xMotivo')->item(0)->nodeValue;
					}
				}catch(\Exception $e){

				}
			}

			return [
				"erro" => 1,
				"message" => $xMotivo
			];
		}

	}

	public function consulta($data_inicial, $data_final){
		$ultNSU = 0;
		$maxNSU = $ultNSU;
		$loopLimit = 10;
		$iCount = 0;
		//executa a busca de DFe em loop
		$last = "";
		$imprime = false;
		$arrayDocs = [];
		while ($ultNSU <= $maxNSU) {
			$iCount++;
			if ($iCount >= $loopLimit) {
				break;
			}
			try {

				$resp = $this->tools->sefazDistDFe($ultNSU);
				$dom = new \DOMDocument();
				$dom->loadXML($resp);

				$node = $dom->getElementsByTagName('retDistDFeInt')->item(0);
				$tpAmb = $node->getElementsByTagName('tpAmb')->item(0)->nodeValue;
				$verAplic = $node->getElementsByTagName('verAplic')->item(0)->nodeValue;
				$cStat = $node->getElementsByTagName('cStat')->item(0)->nodeValue;
				$xMotivo = $node->getElementsByTagName('xMotivo')->item(0)->nodeValue;
				$dhResp = $node->getElementsByTagName('dhResp')->item(0)->nodeValue;
				$ultNSU = $node->getElementsByTagName('ultNSU')->item(0)->nodeValue;
				$maxNSU = $node->getElementsByTagName('maxNSU')->item(0)->nodeValue;
				$lote = $node->getElementsByTagName('loteDistDFeInt')->item(0);
				if (empty($lote)) {
        //lote vazio
					continue;
				}
				if($last != $ultNSU){
					
					$last = $ultNSU;
					if (empty($lote)) {
        			//lote vazio
						continue;
					}
    				//essas tags irão conter os documentos zipados
					$docs = $lote->getElementsByTagName('docZip');

					

					foreach ($docs as $doc) {

						$numnsu = $doc->getAttribute('NSU');
						$schema = $doc->getAttribute('schema');

						$content = gzdecode(base64_decode($doc->nodeValue));
						$xml = simplexml_load_string($content);
						// print_r($xml);
						// print_r($xml->chNFe);
						$temp = [
							'documento' => $xml->CNPJ,
							'nome' => $xml->xNome,
							'data_emissao' => $xml->dhEmi,
							'valor' => $xml->vNF,
							'num_prot' => $xml->nProt,
							'chave' => $xml->chNFe
						];
						$data_dfe = \Carbon\Carbon::parse($xml->dhEmi)->format('Y-m-d');
						if(strtotime($data_dfe) >= strtotime($data_inicial) && strtotime($data_dfe) <= strtotime($data_final)){
							array_push($arrayDocs, $temp);
						}

						$tipo = substr($schema, 0, 6);

					}
					sleep(2);
				}
			} catch (\Exception $e) {
				return $e->getMessage();
			}

		}
		return $arrayDocs;

	}

	public function manifesta($chave, $nSeqEvento){
		try {

			$chNFe = $chave;
			$tpEvento = '210210'; 
			$xJust = ''; 
			$nSeqEvento = $nSeqEvento;

			$response = $this->tools->sefazManifesta($chNFe, $tpEvento, $xJust = '', $nSeqEvento);

			$st = new Standardize($response);

			$arr = $st->toArray();

			return $arr;

		} catch (\Exception $e) {
			echo $e->getMessage();
		}
	}

	public function download($chave){
		try {

			$this->tools->setEnvironment(1);
			
			$response = $this->tools->sefazDownload($chave);
			return $response;

		} catch (\Exception $e) {
			echo str_replace("\n", "<br/>", $e->getMessage());
		}
	}

	public function confirmacao($chave, $nSeqEvento){
		try {

			$chNFe = $chave;
			$tpEvento = '210200'; 
			$xJust = ''; 
			$nSeqEvento = $nSeqEvento;

			$response = $this->tools->sefazManifesta($chNFe, $tpEvento, $xJust = '', $nSeqEvento);

			$st = new Standardize($response);

			$arr = $st->toArray();

			return $arr;

		} catch (\Exception $e) {
			echo $e->getMessage();
		}
	}

	public function desconhecimento($chave, $nSeqEvento, $justificativa){
		try {

			$chNFe = $chave;
			$tpEvento = '210220'; 
			$xJust = $justificativa; 
			$nSeqEvento = $nSeqEvento;

			$response = $this->tools->sefazManifesta($chNFe, $tpEvento, $xJust, $nSeqEvento);

			$st = new Standardize($response);

			$arr = $st->toArray();

			return $arr;

		} catch (\Exception $e) {
			echo $e->getMessage();
		}
	}

	public function operacaoNaoRealizada($chave, $nSeqEvento, $justificativa){
		try {

			$chNFe = $chave;
			$tpEvento = '210240'; 
			$xJust = $justificativa; 
			$nSeqEvento = $nSeqEvento;

			$response = $this->tools->sefazManifesta($chNFe, $tpEvento, $xJust, $nSeqEvento);

			$st = new Standardize($response);

			$arr = $st->toArray();

			return $arr;

		} catch (\Exception $e) {
			echo $e->getMessage();
		}
	}

	
}