<?php

namespace App\Services;

use App\Support\FiscalDateHelper;
use NFePHP\CTe\Make;
use NFePHP\CTe\Tools;
use NFePHP\CTe\Complements;
use NFePHP\CTe\Common\Standardize;
use NFePHP\Common\Certificate;
use NFePHP\Common\Soap\SoapCurl;
use App\Models\ConfigNota;
use App\Models\Cte;
use App\Models\Certificado;
use App\Models\Tributacao;
use App\Models\Filial;
use NFePHP\CTe\MakeCTe;

error_reporting(E_ALL);
ini_set('display_errors', 'On');
class CTeService{

	private $config; 
	private $tools;
	protected $empresa_id = null;
	public function __construct($config, $modelo){
		$value = session('user_logged');
		$this->empresa_id = $value['empresa'];

		if(isset($config['is_filial']) && $config['is_filial']){
			$certificado = Filial::findOrFail($config['is_filial']);
			$this->tools = new Tools(json_encode($config), Certificate::readPfx($certificado->arquivo_certificado, $certificado->senha_certificado));
		}else{
			$certificado = Certificado::
			where('empresa_id', $this->empresa_id)
			->first();
			$this->config = $config;
			$this->tools = new Tools(json_encode($config), Certificate::readPfx($certificado->arquivo, $certificado->senha));
		}
		$this->tools->model('57');
		
	}

	public function gerarCTe($cteEmit){

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		if($cteEmit->filial_id != null){
			$config = Filial::findOrFail($cteEmit->filial_id);
		}

		// $cteEmit = Cte::where('id', $id)
		// ->first();

		$cte = new MakeCTe();
		$dhEmi = FiscalDateHelper::nowXml();
		$lastCte = Cte::lastCTe();
		$numeroCTE = $lastCte;
		if($cteEmit->filial_id != null){
			$numeroCTE = $config->ultimo_numero_cte;
		}
		$numeroCTE++;

		$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

		$chave = $this->montaChave(
			$config->cUF, date('y', strtotime($dhEmi)), date('m', strtotime($dhEmi)), $cnpj, $this->tools->model(), '1', $numeroCTE, '1', '10'
		);
		$infCte = new \stdClass();
		$infCte->Id = "";
		$infCte->versao = "4.00";
		$cte->taginfCTe($infCte);

		$cDV = substr($chave, -1);      
		$ide = new \stdClass();

		$ide->cUF = $config->cUF; 
		$ide->cCT = rand(11111111, 99999999); 
		$ide->CFOP = $cteEmit->remetente->cidade->uf != $cteEmit->destinatario->cidade->uf ?
		$cteEmit->natureza->CFOP_saida_inter_estadual : $cteEmit->natureza->CFOP_saida_estadual;
		$ide->natOp = $cteEmit->natureza->natureza;
		$ide->mod = '57'; 
		$ide->serie = $config->numero_serie_cte; 
		$nCte = $ide->nCT = $numeroCTE; 
		$ide->dhEmi = $dhEmi; 
		$ide->tpImp = '1'; 
		$ide->tpEmis = '1'; 
		$ide->cDV = $cDV; 
		$ide->tpAmb = (int)$config->ambiente; 
		$ide->tpCTe = '0'; 

		// 0- CT-e Normal; 1 - CT-e de Complemento de Valores;
// 2 -CT-e de Anulação; 3 - CT-e Substituto

		$ide->procEmi = '0'; 
		$ide->verProc = app(\App\Support\FiscalProcessVersion::class)->verProc(); 
		$ide->indGlobalizado = $cteEmit->globalizado == 1 ? '1' : '';

		$ide->cMunEnv = $cteEmit->municipioEnvio->codigo; 
		$ide->xMunEnv = strtoupper($cteEmit->municipioEnvio->nome); 
		$ide->UFEnv = $cteEmit->municipioEnvio->uf; 
		$ide->modal = $cteEmit->modal; 
		$ide->tpServ = $cteEmit->tipo_servico; 

		$ide->cMunIni = $cteEmit->municipioInicio->codigo; 
		$ide->xMunIni = strtoupper($cteEmit->municipioInicio->nome); 
		$ide->UFIni = $cteEmit->municipioInicio->uf; 
		$ide->cMunFim = $cteEmit->municipioFim->codigo; 
		$ide->xMunFim = strtoupper($cteEmit->municipioFim->nome); 
		$ide->UFFim = $cteEmit->municipioFim->uf; 
		$ide->retira = $cteEmit->retira ? 0 : 1;
		$ide->xDetRetira = $cteEmit->detalhes_retira;

		if($cteEmit->tomador == 0){
			if($cteEmit->remetente->contribuinete){
				if($cteEmit->remetente->ie_rg == 'ISENTO'){
					$ide->indIEToma = '2';
				}else{
					$ide->indIEToma = '1';
				}
			}else{
				$ide->indIEToma = '9';
			}
		}else if($cteEmit->tomador == 1){
			if($cteEmit->expedidor){
				if($cteEmit->expedidor->contribuinete){
					if($cteEmit->expedidor->ie_rg == 'ISENTO'){
						$ide->indIEToma = '2';
					}else{
						$ide->indIEToma = '1';
					}
				}else{
					$ide->indIEToma = '9';
				}
			}
		}else if($cteEmit->tomador == 2){
			if($cteEmit->recebedor){
				if($cteEmit->recebedor->contribuinete){
					if($cteEmit->recebedor->ie_rg == 'ISENTO'){
						$ide->indIEToma = '2';
					}else{
						$ide->indIEToma = '1';
					}
				}else{
					$ide->indIEToma = '9';
				}
			}
		}else if($cteEmit->tomador == 3){
			if($cteEmit->destinatario->contribuinete){
				if($cteEmit->destinatario->ie_rg == 'ISENTO'){
					$ide->indIEToma = '2';
				}else{
					$ide->indIEToma = '1';
				}
			}else{
				$ide->indIEToma = '9';
			}
		}
		// $ide->indIEToma = $cteEmit->destinatario;
		$ide->dhCont = ''; 
		$ide->xJust = '';

		$cte->tagide($ide);
// Indica o "papel" do tomador: 0-Remetente; 1-Expedidor; 2-Recebedor; 3-Destinatário
		$toma3 = new \stdClass();
		$toma3->toma = $cteEmit->tomador;
		$cte->tagtoma3($toma3);

		$enderToma = new \stdClass();
		$enderToma->xLgr = $cteEmit->logradouro_tomador;
		$enderToma->nro = $cteEmit->numero_tomador; 
		$enderToma->xCpl = ''; 
		$enderToma->xBairro = $cteEmit->bairro_tomador; 
		$enderToma->cMun = $cteEmit->municipioTomador->codigo; 
		$enderToma->xMun = $cteEmit->municipioTomador->nome; 
		$enderToma->CEP = $cteEmit->cep_tomador; 
		$enderToma->UF = $cteEmit->municipioTomador->uf; 
		$enderToma->cPais = '1058'; 
		$enderToma->xPais = 'Brasil';                   
		$cte->tagenderToma($enderToma);   

		$emit = new \stdClass();
		
		$emit->CNPJ = $cnpj; 

		$ie = str_replace(".", "", $config->ie);
		$ie = str_replace("/", "", $ie);
		$ie = str_replace("-", "", $ie);
		$emit->IE = $ie; 
		$emit->IEST = "";
		$emit->xNome = $config->razao_social; 
		$emit->xFant = $config->nome_fantasia; 
		// $emit->CRT = $config->regime == 'Regime Normal' ? 3 : 1;
		$tributacao = Tributacao::
		where('empresa_id', $this->empresa_id)
		->first();
		$emit->CRT = ($tributacao->regime == 0 || $tributacao->regime == 2) ? 1 : 3;
		$cte->tagemit($emit); 

		$enderEmit = new \stdClass();
		$enderEmit->xLgr = $config->logradouro; 
		$enderEmit->nro = $config->numero; 
		$enderEmit->xCpl = '';
		$enderEmit->xBairro = $config->bairro; 
		$enderEmit->cMun = $config->codMun;
		$enderEmit->xMun = $config->municipio; 

		$cep = str_replace("-", "", $config->cep);
		$cep = str_replace(".", "", $cep);
		$enderEmit->CEP = $cep; 
		$enderEmit->UF = $config->UF; 

		$fone = str_replace(" ", "", $config->fone);
		$fone = str_replace("-", "", $fone);
		$enderEmit->fone = $fone; 
		$cte->tagenderEmit($enderEmit);

		$rem = new \stdClass();

		$cnpjRemente = preg_replace('/[^0-9]/', '', $cteEmit->remetente->cpf_cnpj);

		if(strlen($cnpjRemente) == 14){
			$rem->CNPJ = $cnpjRemente; 

			$ieRemetente = preg_replace('/[^0-9]/', '', $cteEmit->remetente->ie_rg);

			$rem->IE = $ieRemetente;
		}
		else{
			$rem->CPF = $cnpjRemente; 
		}

		$rem->xNome = $cteEmit->remetente->razao_social;
		if($cteEmit->remetente->nome_fantasia) $rem->xFant = $cteEmit->remetente->nome_fantasia; 
		$rem->fone = ''; 
		$rem->email = ''; 
		$cte->tagrem($rem);

		$enderReme = new \stdClass();
		$enderReme->xLgr = $cteEmit->remetente->rua; 
		$enderReme->nro = $cteEmit->remetente->numero; 
		$enderReme->xCpl = ''; 
		$enderReme->xBairro = $cteEmit->remetente->bairro; 
		$enderReme->cMun = $cteEmit->remetente->cidade->codigo; 
		$enderReme->xMun = strtoupper($cteEmit->remetente->cidade->nome); 
		$cepRemetente = str_replace("-", "", $cteEmit->remetente->cep);
		$enderReme->CEP = $cepRemetente; 
		$enderReme->UF = $cteEmit->remetente->cidade->uf; 
		$enderReme->cPais = '1058'; 
		$enderReme->xPais = 'Brasil'; 
		$cte->tagenderReme($enderReme);

		$dest = new \stdClass();

		$cnpjDestinatario = preg_replace('/[^0-9]/', '', $cteEmit->destinatario->cpf_cnpj);

		if(strlen($cnpjDestinatario) == 14){
			$dest->CNPJ = $cnpjDestinatario; 

			$ieDestinatario = preg_replace('/[^0-9]/', '', $cteEmit->destinatario->ie_rg);

			$dest->IE = $ieDestinatario;
		}
		else{
			$dest->CPF = $cnpjDestinatario; 
		}
		
		$dest->xNome = $cteEmit->destinatario->razao_social;
		$dest->fone = ''; 
		$dest->ISUF = ''; 
		$dest->email = ''; 
		$cte->tagdest($dest);

		$enderDest = new \stdClass();
		$enderDest->xLgr = $cteEmit->destinatario->rua; 
		$enderDest->nro = $cteEmit->destinatario->numero; 
		$enderDest->xCpl = ''; 
		$enderDest->xBairro = $cteEmit->destinatario->bairro; 
		$enderDest->cMun = $cteEmit->destinatario->cidade->codigo; 
		$enderDest->xMun = strtoupper($cteEmit->destinatario->cidade->nome); 

		$cepDest = str_replace("-", "", $cteEmit->destinatario->cep);
		$enderDest->CEP = $cepDest; 
		$enderDest->UF = $cteEmit->destinatario->cidade->uf; 
		$enderDest->cPais = '1058'; 
		$enderDest->xPais = 'Brasil'; 
		$cte->tagenderDest($enderDest);

		if($cteEmit->expedidor){

			$exped = new \stdClass();
			$cnpjDestinatario = preg_replace('/[^0-9]/', '', $cteEmit->expedidor->cpf_cnpj);

			if(strlen($cnpjDestinatario) == 14){
				$exped->CNPJ = $cnpjDestinatario; 
				$ieDestinatario = preg_replace('/[^0-9]/', '', $cteEmit->expedidor->ie_rg);

				$exped->IE = $ieDestinatario;
			}
			else{
				$exped->CPF = $cnpjDestinatario; 
			}

			$exped->xNome = $cteEmit->expedidor->razao_social;
			$exped->fone = ''; 
			$exped->email = ''; 
			$cte->tagexped($exped);

			$enderExped = new \stdClass();
			$enderExped->xLgr = $cteEmit->expedidor->rua; 
			$enderExped->nro = $cteEmit->expedidor->numero; 
			$enderExped->xCpl = ''; 
			$enderExped->xBairro = $cteEmit->expedidor->bairro; 
			$enderExped->cMun = $cteEmit->expedidor->cidade->codigo; 
			$enderExped->xMun = strtoupper($cteEmit->expedidor->cidade->nome); 

			$cepDest = str_replace("-", "", $cteEmit->expedidor->cep);
			$enderExped->CEP = $cepDest; 
			$enderExped->UF = $cteEmit->expedidor->cidade->uf; 
			$enderExped->cPais = '1058'; 
			$enderExped->xPais = 'Brasil'; 
			$cte->tagenderExped($enderExped);
		}

		if($cteEmit->recebedor){

			$receb = new \stdClass();
			$cnpjDestinatario = preg_replace('/[^0-9]/', '', $cteEmit->recebedor->cpf_cnpj);

			if(strlen($cnpjDestinatario) == 14){
				$receb->CNPJ = $cnpjDestinatario; 
				$ieDestinatario = preg_replace('/[^0-9]/', '', $cteEmit->recebedor->ie_rg);

				$receb->IE = $ieDestinatario;
			}
			else{
				$receb->CPF = $cnpjDestinatario; 
			}

			$receb->xNome = $cteEmit->recebedor->razao_social;
			$receb->fone = ''; 
			$receb->email = ''; 
			$cte->tagreceb($receb);

			$enderReceb = new \stdClass();
			$enderReceb->xLgr = $cteEmit->recebedor->rua; 
			$enderReceb->nro = $cteEmit->recebedor->numero; 
			$enderReceb->xCpl = ''; 
			$enderReceb->xBairro = $cteEmit->recebedor->bairro; 
			$enderReceb->cMun = $cteEmit->recebedor->cidade->codigo; 
			$enderReceb->xMun = strtoupper($cteEmit->recebedor->cidade->nome); 

			$cepDest = str_replace("-", "", $cteEmit->recebedor->cep);
			$enderReceb->CEP = $cepDest; 
			$enderReceb->UF = $cteEmit->recebedor->cidade->uf; 
			$enderReceb->cPais = '1058'; 
			$enderReceb->xPais = 'Brasil'; 
			$cte->tagenderReceb($enderReceb);
		}


		$vPrest = new \stdClass();
		$vPrest->vTPrest = $this->format($cteEmit->valor_transporte); 
		$vPrest->vRec = $this->format($cteEmit->valor_receber);      
		$cte->tagvPrest($vPrest);

		$somaVBC = 0;
		foreach($cteEmit->componentes as $c){
			$comp = new \stdClass();
			$comp->xNome = $c->nome; 
			$comp->vComp = $this->format($c->valor);  
			$cte->tagComp($comp);

			if($cteEmit->perc_icms > 0){
				$somaVBC += $c->valor;
			}
		}
		// $cteEmit->cst = '00';
		if($cteEmit->cst == 'SN'){
			$icms = new \stdClass();
			$icms->cst = $cteEmit->cst;
			$icms->pICMS = $this->format($cteEmit->perc_icms);

			$icms->vBC = $this->format(0); 
			$icms->vICMS = $this->format(0); 
			$cte->tagicms($icms);

		}else{
			$icms = new \stdClass();
			$icms->cst = $cteEmit->cst;

			$tempB = 100-$cteEmit->pRedBC;
			$v = $somaVBC * ($tempB/100);

			$icms->pRedBC = $this->format($cteEmit->pRedBC); 
			$icms->vBC = $this->format($v); 
			$icms->pICMS = $this->format($cteEmit->perc_icms);
			if($cteEmit->perc_icms > 0){ 
				$icms->vICMS = $this->format($v * ($cteEmit->perc_icms/100)); 
			}else{
				$icms->vICMS = 0;
			}

			$icms->vBCUFFim = 0.00; 
			$icms->pFCPUFFim = 0.00; 
			$icms->pICMSUFFim = 0.00; 
			$icms->pICMSInter = 0.00; 
			$icms->vFCPUFFim = 0.00; 

			$icms->vBCSTRet = ''; 
			$icms->vICMSSTRet = ''; 
			$icms->pICMSSTRet = ''; 
			$icms->vCred = ''; 
			$icms->vTotTrib = 0.00; 
			$icms->outraUF = false;    
			$icms->vICMSUFIni = 0;  
			$icms->vICMSUFFim = 0;
			$icms->infAdFisco = '';
			$cte->tagicms($icms);
		}

		$cte->taginfCTeNorm();              // Grupo de informações do CT-e Normal e Substituto
		
		$infCarga = new \stdClass();
		$infCarga->vCarga = $this->format($cteEmit->valor_carga);
		$infCarga->proPred = $cteEmit->produto_predominante; 
		$infCarga->xOutCat = 0.00; 
		// $infCarga->vCargaAverb = 1.99;
		$cte->taginfCarga($infCarga);

		foreach($cteEmit->medidas as $m){
			$infQ = new \stdClass();
			$infQ->cUnid = $m->cod_unidade; 
			$infQ->tpMed = $m->tipo_medida; 
			$infQ->qCarga = $m->quantidade_carga;  
			$cte->taginfQ($infQ);
		}

		if(strlen($cteEmit->chave_nfe) > 0){
			$chaves = explode(";", $cteEmit->chave_nfe);

			foreach($chaves as $chave){
				$infNFe = new \stdClass();
				$infNFe->chave = $chave; 
				$infNFe->PIN = ''; 
				$infNFe->dPrev = $cteEmit->data_previsata_entrega;                                       
				$cte->taginfNFe($infNFe);
			}
		}else{

			$infOut = new \stdClass();

			$infOut->tpDoc = $cteEmit->tpDoc;     
			$infOut->descOutros = $cteEmit->descOutros;     
			$infOut->nDoc = $cteEmit->nDoc;     
			$infOut->dEmi = date('Y-m-d');     
			$infOut->vDocFisc = $this->format($cteEmit->vDocFisc);     
			$infOut->dPrev = $cteEmit->data_previsata_entrega;     
			$cte->taginfOutros($infOut);
		}

		$infModal = new \stdClass();
		$infModal->versaoModal = '4.00';
		$cte->taginfModal($infModal);

		$rodo = new \stdClass();
		if($cteEmit->veiculo->rntrc != ""){
			$rodo->RNTRC = $cteEmit->veiculo->rntrc;
		}else{
			$rodo->RNTRC = "ISENTO";
		}
		$cte->tagrodo($rodo);

		$aereo = new \stdClass();
		$aereo->nMinu = '123'; 
		$aereo->nOCA = '';
 // Número Operacional do Conhecimento Aéreo
		$aereo->dPrevAereo = date('Y-m-d');
		$aereo->natCarga_xDime = ''; 
		$aereo->natCarga_cInfManu = [  ]; 
		$aereo->tarifa_CL = 'G';
		$aereo->tarifa_cTar = ''; 
		$aereo->tarifa_vTar = 100.00; 
		$cte->tagaereo($aereo);

		$stdCompl = new \stdClass();
		$stdCompl->xObs = $this->retiraAcentos($cteEmit->observacao);
		$cte->tagcompl($stdCompl);

// 		$autXML = new \stdClass();
// 		// $cnpj = str_replace(".", "", $config->cnpj);
// 		// $cnpj = str_replace("/", "", $cnpj);
// 		// $cnpj = str_replace("-", "", $cnpj);
// 		// $cnpj = str_replace(" ", "", $cnpj);
// 		$autXML->CNPJ = '36518327000105'; 
// // CPF ou CNPJ dos autorizados para download do XML
// 		$cte->tagautXML($autXML);


		try{
			$cte->montaCTe();
			$chave = $cte->chCTe;
			$xml = $cte->getXML();
			$arr = [
				'chave' => $chave,
				'xml' => $xml,
				'nCte' => $nCte
			];
			return $arr;
		}catch(\Exception $e){
			return [
				'erros_xml' => $cte->getErrors()
			];
		}
	}

	private function retiraAcentos($texto){
		return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/", "/(ç)/"),explode(" ","a A e E i I o O u U n N c"),$texto);
	}

	public function sign($xml){
		return $this->tools->signCTe($xml);
	}

	public function transmitir($signXml, $chave){
		try {
			$idLote = substr(str_replace(',', '', number_format(microtime(true) * 1000000, 0)), 0, 15);
			// $resp = $this->tools->sefazEnviaLote([$signXml], $idLote);
			$resp = $this->tools->sefazEnviaCTe($signXml);
			$st = new Standardize($resp);
			sleep(4);

			$std = $st->toStd();

			if ($std->cStat != 100) {
				// erro
				// return "[$std->cStat] - $std->xMotivo";
				// return [
				// 	'erro' => 1,
				// 	'error' => "[$std->cStat] - $std->xMotivo"
				// ];
				return "Erro: [$std->cStat] - $std->xMotivo";
			}
			// $recibo = $std->infRec->nRec;
			$recibo = $std->protCTe->infProt->nProt;
			// $protocolo = $this->tools->sefazConsultaRecibo($recibo);
				// return "Erro: teste";

			try {
				// $xml = Complements::toAuthorize($signXml, $recibo);
				// $xml = Complements::toAuthorize($this->tools->lastRequest, $std->protCTe->infProt);
				$xml = Complements::toAuthorize($signXml, $resp);

				safe_file_put_contents(public_path('xml_cte/') . $chave . '.xml', $xml);

				return $recibo;
				// return [
				// 	'erro' => 0,
				// 	'success' => $recibo
				// ];
				// $this->printDanfe($xml);
			} catch (\Exception $e) {
				return "Erro: " . $st->toJson($recibo);
				// return [
				// 	'erro' => 1,
				// 	'error' => "algo deu errado"
				// ];
			}
		} catch (\Exception $e) {
			return "Erro: ".$e->getMessage() ;
			// return [
			// 	'erro' => 1,
			// 	'error' => $e->getMessage()
			// ];
		}

	}		

	private function format($number, $dec = 2){
		return number_format((float) $number, $dec, ".", "");
	}

	private function montaChave($cUF, $ano, $mes, $cnpj, $mod, $serie, 
		$numero, $tpEmis, $codigo = ''){
		if ($codigo == '') {
			$codigo = $numero;
		}
		$forma = "%02d%02d%02d%s%02d%03d%09d%01d%08d";
		$chave = sprintf(
			$forma, $cUF, $ano, $mes, $cnpj, $mod, $serie, $numero, $tpEmis, $codigo
		);
		return $chave . $this->calculaDV($chave);
	}

	private function calculaDV($chave43){
		$multiplicadores = array(2, 3, 4, 5, 6, 7, 8, 9);
		$iCount = 42;
		$somaPonderada = 0;
		while ($iCount >= 0) {
			for ($mCount = 0; $mCount < count($multiplicadores) && $iCount >= 0; $mCount++) {
				$num = (int) substr($chave43, $iCount, 1);
				$peso = (int) $multiplicadores[$mCount];
				$somaPonderada += $num * $peso;
				$iCount--;
			}
		}
		$resto = $somaPonderada % 11;
		if ($resto == '0' || $resto == '1') {
			$cDV = 0;
		} else {
			$cDV = 11 - $resto;
		}
		return (string) $cDV;
	}


	public function cancelar($cteId, $justificativa){

		try {
			$cte = Cte::
			where('id', $cteId)
			->first();
				// $this->tools->model('55');

			$chave = $cte->chave;
			$response = $this->tools->sefazConsultaChave($chave);
			$stdCl = new Standardize($response);
			$arr = $stdCl->toArray();
			$js = $stdCl->toJson();
			sleep(1);
			$xJust = $justificativa;

			if(!isset($arr['protCTe'])){
				return [
					'erro' => 1,
					'mensagem' => $arr['xMotivo']
				];
			}

			$nProt = $arr['protCTe']['infProt']['nProt'];


			$response = $this->tools->sefazCancela($chave, $xJust, $nProt);

			$stdCl = new Standardize($response);
			$std = $stdCl->toStd();
			$arr = $stdCl->toArray();
			$json = $stdCl->toJson();
			// return $json;
			$cStat = $std->infEvento->cStat;

			$public = env('SERVIDOR_WEB') ? 'public/' : '';
			if ($cStat == '101' || $cStat == '135' || $cStat == '155') {
				$xml = Complements::toAuthorize($this->tools->lastRequest, $response);
				// header('Content-type: text/xml; charset=UTF-8');
				safe_file_put_contents(public_path('xml_cte_cancelada/').$chave.'.xml',$xml);
				return $json;
			}else{
				return $json;
			}

		} catch (\Exception $e) {
			return $e->getMessage();
    //TRATAR
		}
	}

	public function consultar($id){
		try {
			$cte = Cte::
			where('id', $id)
			->first();

			$chave = $cte->chave;
			$response = $this->tools->sefazConsultaChave($chave);

			// return $response;
			$stdCl = new Standardize($response);
			$arr = $stdCl->toArray();

			// $arr = json_decode($json);
			return json_encode($arr);

		} catch (\Exception $e) {
			echo $e->getMessage();
		}
	}

	public function inutilizar($nInicio, $nFinal, $justificativa){
		try{

			$nSerie = '1';
			$nIni = $nInicio;
			$nFin = $nFinal;
			$xJust = $justificativa;
			$tpAmb = 2;
			$response = $this->tools->sefazInutiliza($nSerie, $nIni, $nFin, $xJust, $tpAmb);

			$stdCl = new Standardize($response);

			$std = $stdCl->toStd();

			$arr = $stdCl->toArray();

			$json = $stdCl->toJson();

			return $arr;

		} catch (\Exception $e) {
			echo $e->getMessage();
		}
	}

	public function cartaCorrecao($id, $grupo, $campo, $valor){
		try {

			$cte = Cte::
			where('id', $id)
			->first();

			$chave = $cte->chave;

			$nSeqEvento = $cte->sequencia_cce+1;
			$infCorrecao[] = [
				'grupoAlterado' => $grupo,
				'campoAlterado' => $campo,
				'valorAlterado' => $valor,
				'nroItemAlterado' => '01'
			];
			$response = $this->tools->sefazCCe($chave, $infCorrecao, $nSeqEvento);
			sleep(2);

			$stdCl = new Standardize($response);
			$std = $stdCl->toStd();
			$arr = $stdCl->toArray();
			$json = $stdCl->toJson();
			$cStat = $std->infEvento->cStat;
			$public = env('SERVIDOR_WEB') ? 'public/' : '';
			if ($cStat == '101' || $cStat == '135' || $cStat == '155') {
				$xml = Complements::toAuthorize($this->tools->lastRequest, $response);
				safe_file_put_contents(public_path('xml_cte_correcao/').$chave.'.xml',$xml);
				$cte->sequencia_cce = $cte->sequencia_cce + 1;
				$cte->save();
				return $json;
			}else{
				 //houve alguma falha no evento 
				return $json;
			}

		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	public function getXml($chave){
		// $resp = safe_file_get_contents('ctes.xml');
		try{
			$resp = $this->tools->sefazDistDFe(0,0);
			// safe_file_put_contents("ctes.xml", $resp);

			$dom = new \DOMDocument();
			$dom->loadXML($resp);
			$xMotivo = $dom->getElementsByTagName('xMotivo')->item(0)->nodeValue;

			if($xMotivo == 'Rejeicao: Consumo indevido'){
				echo $xMotivo;
				die;
			}

			$arrayDocs = [];
			$dom = new \DOMDocument();
			$dom->loadXML($resp);
			$node = $dom->getElementsByTagName('retDistDFeInt')->item(0);
			$lote = $node->getElementsByTagName('loteDistDFeInt')->item(0);

			$docs = $lote->getElementsByTagName('docZip');
			foreach ($docs as $doc) {
				$content = gzdecode(base64_decode($doc->nodeValue));
				$xml = simplexml_load_string($content);

				$temp = $xml->CTe->infCte;

				if(isset($temp->emit)){

					$chaveTemp = substr((string)$temp['Id'], 3, strlen((string)$temp['Id']));
					if($chaveTemp == $chave){
						return $content;
					}
				}
			}
		}catch(\Exception $e){
			echo "Erro: " . $e->getMessage();
		}
	}

	public function download($chave){
		try {

			$this->tools->setEnvironment(1);
			$chave = $chave;
			$response = $this->tools->sefazDownload($chave);
			return $response;

		} catch (\Exception $e) {
			echo str_replace("\n", "<br/>", $e->getMessage());
		}
	}

	public function consultaDocumentos(){
		$resp = $this->tools->sefazDistDFe(0,0);
		// safe_file_put_contents("ctes.xml", $resp);
		// $resp = safe_file_get_contents('ctes.xml');
		$dom = new \DOMDocument();
		$dom->loadXML($resp);
		$xMotivo = $dom->getElementsByTagName('xMotivo')->item(0)->nodeValue;
		
		if($xMotivo == 'Rejeicao: Consumo indevido'){
			return [
				'erro' => $xMotivo
			];
		}

		$arrayDocs = [];
		$dom = new \DOMDocument();
		$dom->loadXML($resp);
		$node = $dom->getElementsByTagName('retDistDFeInt')->item(0);
		$lote = $node->getElementsByTagName('loteDistDFeInt')->item(0);
		if(!$lote){
			return [
				'erro' => 'Lote não disponível, ou nenhum registro encontrado!'
			];
		}
		$docs = $lote->getElementsByTagName('docZip');

		foreach ($docs as $doc) {
			$content = gzdecode(base64_decode($doc->nodeValue));
			$xml = simplexml_load_string($content);

			$xml = $xml->CTe->infCte;

			if(isset($xml->emit)){
				
				$chave = substr((string)$xml['Id'], 3, strlen((string)$xml['Id']));
				$temp = [
					'documento' => (int)$xml->emit->CNPJ,
					'nome' => (string)$xml->emit->xNome,
					'data_emissao' => (string)$xml->ide->dhEmi,
					'valor' => (float)$xml->vPrest->vTPrest,
					'chave' => $chave,
					'tipo' => 0,
					'sequencia_evento' => 0,
					'empresa_id' => $this->empresa_id
				];

				array_push($arrayDocs, $temp);

			}
		}

		return $arrayDocs;
	}

	public function desacordo($chave, $nSeqEvento, $xJust, $uf){
		try {
			$chNFe = $chave;
			$tpEvento = '610110'; 
			$nSeqEvento = $nSeqEvento;


			$response = $this->tools->sefazManifesta($chNFe, $tpEvento, $xJust, $nSeqEvento, 
				$uf);

			$st = new Standardize($response);

			$arr = $st->toArray();

			return $arr;

		} catch (\Exception $e) {
			echo $e->getMessage();
		}
	}
}