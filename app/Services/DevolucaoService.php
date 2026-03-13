<?php

namespace App\Services;
use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use App\Models\Venda;
use App\Models\ConfigNota;
use App\Models\Certificado;
use NFePHP\NFe\Complements;
//use NFePHP\DA\NFe\Danfe;
use App\Services\CustomDanfe as Danfe;
use NFePHP\DA\Legacy\FilesFolders;
use NFePHP\Common\Soap\SoapCurl;
use App\Models\Tributacao;
use App\Models\Filial;

error_reporting(E_ALL);
ini_set('display_errors', 'On');

class DevolucaoService{

	private $config;
	private $tools;
	protected $empresa_id = null;

    public function __construct(array $config, int $modelo)
    {
        // 1) determina empresa
        $value = session('user_logged');
        $this->empresa_id = $value['empresa'];

        // 2) escolhe PFX de filial ou da matriz
        if (!empty($config['is_filial'])) {
            $filial   = Filial::findOrFail($config['is_filial']);
            $pfxFile  = $filial->arquivo_certificado;
            $pfxPass  = $filial->senha_certificado;
        } else {
            $cert     = Certificado::where('empresa_id', $this->empresa_id)->firstOrFail();
            $pfxFile  = $cert->arquivo;
            $pfxPass  = $cert->senha;
        }

        // 3) instancia o NFePHP Tools com o PFX correto
        $this->config = $config;
        $this->tools  = new Tools(
            json_encode($config),
            Certificate::readPfx($pfxFile, $pfxPass)
        );

        // 4) mantém SoapCurl e model
        $soapCurl = new SoapCurl();
        $soapCurl->httpVersion('1.1');
        $this->tools->loadSoapClass($soapCurl);
        $this->tools->model($modelo);
    }

    /*
	public function __construct($config, $modelo){
		//$json = json_encode($config);
		$value = session('user_logged');
		$this->empresa_id = $value['empresa'];
		$certificado = Certificado::
		where('empresa_id', $this->empresa_id)
		->first();
		// print_r($certificado->arquivo);
		$this->config = $config;
		$this->tools = new Tools(json_encode($config), Certificate::readPfx($certificado->arquivo, $certificado->senha));
		$soapCurl = new SoapCurl();
		$soapCurl->httpVersion('1.1');
		$this->tools->loadSoapClass($soapCurl);
		$this->tools->model($modelo);

	}
    */

	private function validate_EAN13Barcode($ean)
	{

		$sumEvenIndexes = 0;
		$sumOddIndexes  = 0;

		$eanAsArray = array_map('intval', str_split($ean));

		if(substr($ean, 0, 1) == 1){
			return false;
		}

		if(strlen($ean) == 14){
			return true;
		}

		if (!$this->has13Numbers($eanAsArray)) {
			return false;
		};

		for ($i = 0; $i < count($eanAsArray)-1; $i++) {
			if ($i % 2 === 0) {
				$sumOddIndexes  += $eanAsArray[$i];
			} else {
				$sumEvenIndexes += $eanAsArray[$i];
			}
		}

		$rest = ($sumOddIndexes + (3 * $sumEvenIndexes)) % 10;

		if ($rest !== 0) {
			$rest = 10 - $rest;
		}

		return $rest === $eanAsArray[12];
	}

	private function has13Numbers(array $ean)
	{
		return count($ean) === 13;
	}

	public function gerarDevolucao($devolucao){

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first(); // iniciando os dados do emitente NF

        if ($devolucao->filial_id) {
            // mantém casas decimais da matriz
            $casas = $config->casas_decimais;
            $config = Filial::findOrFail($devolucao->filial_id);
            $config->casas_decimais = $casas;
        }

		$tributacao = Tributacao::
		where('empresa_id', $this->empresa_id)
		->first(); // iniciando tributos

		$nfe = new Make();
		$stdInNFe = new \stdClass();
		$stdInNFe->versao = '4.00';
		$stdInNFe->Id = null;
		$stdInNFe->pk_nItem = '';

		$infNFe = $nfe->taginfNFe($stdInNFe);


        // obtém último número usado
        $vendaLast = Venda::lastNF();
        if ($devolucao->filial_id) {
            // se for filial, a série/número vem do próprio cadastro de filial
            $vendaLast = $config->ultimo_numero_nfe;
        }

		$lastNumero = $vendaLast > 0 ? $vendaLast : 0;

		$stdIde = new \stdClass();
		$stdIde->cUF = $config->cUF;
		$stdIde->cNF = rand(11111,99999);
		// $stdIde->natOp = $venda->natureza->natureza;
		$stdIde->natOp = $devolucao->natureza->natureza;

		// $stdIde->indPag = 1; //NÃO EXISTE MAIS NA VERSÃO 4.00 // forma de pagamento

		$stdIde->mod = 55;
		$stdIde->serie = $config->numero_serie_nfe;
		$stdIde->nNF = (int)$lastNumero+1;
        $config->ultimo_numero_nfe = $stdIde->nNF;
        $config->save();
		$stdIde->dhEmi = date("Y-m-d\TH:i:sP");
		$stdIde->dhSaiEnt = date("Y-m-d\TH:i:sP");
		$stdIde->tpNF = $devolucao->tipo;
		$stdIde->idDest = $config->UF != $devolucao->fornecedor->cidade->uf ? 2 : 1;
		$stdIde->cMunFG = $config->codMun;
		// $stdIde->tpImp = 1;
		$stdIde->tpImp = $config->tipo_impressao_danfe;
		$stdIde->tpEmis = 1;
		$stdIde->cDV = 0;
		$stdIde->tpAmb = $config->ambiente;
		$stdIde->finNFe = $devolucao->natureza->finNFe; // 4 - devolucao
		$stdIde->indFinal = 1;
		$stdIde->indPres = 1;
		if($config->ambiente == 2){
			$stdIde->indIntermed = 0;
		}
		$stdIde->procEmi = '0';
		$stdIde->verProc = '3.10.31';

		$tagide = $nfe->tagide($stdIde);

		$stdEmit = new \stdClass();
		$stdEmit->xNome = $config->razao_social;
		$stdEmit->xFant = $config->nome_fantasia;

		$ie = preg_replace('/[^0-9]/', '', $config->ie);
		$stdEmit->IE = $ie;
		// $stdEmit->CRT = $tributacao->regime == 0 ? 1 : 3;
		// $stdEmit->CRT = ($tributacao->regime == 0 || $tributacao->regime == 2) ? 1 : 3;
		$regime = $devolucao->itens[0]->cst_csosn >= 101 ? 1 : 3;

		$stdEmit->CRT = $regime;

		$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);
		if(strlen($cnpj) == 14){
			$stdEmit->CNPJ = $cnpj;
		}else{
			$stdEmit->CPF = $cnpj;
		}

		$emit = $nfe->tagemit($stdEmit);

		// ENDERECO EMITENTE
		$stdEnderEmit = new \stdClass();
		$stdEnderEmit->xLgr = $config->logradouro;
		$stdEnderEmit->nro = $config->numero;
		$stdEnderEmit->xCpl = "";
		$stdEnderEmit->xBairro = $config->bairro;
		$stdEnderEmit->cMun = $config->codMun;
		$stdEnderEmit->xMun = $config->municipio;
		$stdEnderEmit->UF = $config->UF;

		$cep = preg_replace('/[^0-9]/', '', $config->cep);
		$stdEnderEmit->CEP = $cep;
		$stdEnderEmit->cPais = $config->codPais;
		$stdEnderEmit->xPais = $config->pais;

		$enderEmit = $nfe->tagenderEmit($stdEnderEmit);

		// DESTINATARIO
		$stdDest = new \stdClass();
		$stdDest->xNome = $devolucao->fornecedor->razao_social;

		if($devolucao->fornecedor->ie_rg == 'ISENTO'){
			$stdDest->indIEDest = "2";
		}else{
			$stdDest->indIEDest = "1";
		}

		$cnpj_cpf = preg_replace('/[^0-9]/', '', $devolucao->fornecedor->cpf_cnpj);

		if(strlen($cnpj_cpf) == 14){
			$stdDest->CNPJ = $cnpj_cpf;
			$ie = preg_replace('/[^0-9]/', '', $devolucao->fornecedor->ie_rg);
			$stdDest->IE = $ie;
		}else{
			$stdDest->CPF = $cnpj_cpf;
			if($devolucao->fornecedor->ie_rg != 'ISENTO'){
				$stdDest->IE = $devolucao->fornecedor->ie_rg;
			}
		}

		$dest = $nfe->tagdest($stdDest);

		$stdEnderDest = new \stdClass();
		$stdEnderDest->xLgr = $devolucao->fornecedor->rua;
		$stdEnderDest->nro = $devolucao->fornecedor->numero;
		$stdEnderDest->xCpl = "";
		$stdEnderDest->xBairro = $devolucao->fornecedor->bairro;
		$stdEnderDest->cMun = $devolucao->fornecedor->cidade->codigo;
		$stdEnderDest->xMun = strtoupper($this->retiraAcentos($devolucao->fornecedor->cidade->nome));
		$stdEnderDest->UF = $devolucao->fornecedor->cidade->uf;

		$cep = str_replace("-", "", $devolucao->fornecedor->cep);
		$cep = str_replace(".", "", $cep);
		$stdEnderDest->CEP = $cep;
		$stdEnderDest->cPais = "1058";
		$stdEnderDest->xPais = "BRASIL";

		$enderDest = $nfe->tagenderDest($stdEnderDest);

		$somaProdutos = 0;
		$somaICMS = 0;
		$somaIPI = 0;
		$somaST = 0;
		//PRODUTOS
		$itemCont = 0;

		$totalItens = count($devolucao->itens);
		$somaFrete = 0;
		$somaAcrescimo = 0;

		$std = new \stdClass();
		$std->refNFe = $devolucao->chave_nf_entrada;
		$nfe->tagrefNFe($std);

		$VBC = 0;
		$somaDesconto = 0;

		foreach($devolucao->itens as $i){
			$itemCont++;

			$stdProd = new \stdClass();
			$stdProd->item = $itemCont;

			$cod = $this->validate_EAN13Barcode($i->codBarras);

			// $stdProd->cEAN = $i->codBarras;
			// $stdProd->cEANTrib = $i->codBarras;
			$stdProd->cEAN = $cod ? $i->codBarras : 'SEM GTIN';
			$stdProd->cEANTrib = $cod ? $i->codBarras : 'SEM GTIN';

			$stdProd->cProd = $i->cod;
			$stdProd->xProd = $i->nome;

			if($i->cBenef){
				$stdProd->cBenef = $i->cBenef;
			}

			$ncm = $i->ncm;
			$ncm = str_replace(".", "", $ncm);
			$stdProd->NCM = $ncm;
			if($devolucao->tipo == 1){
				$stdProd->CFOP = $config->UF != $devolucao->fornecedor->cidade->uf ?
				$devolucao->natureza->CFOP_saida_inter_estadual : $devolucao->natureza->CFOP_saida_estadual;
			}else{
				$stdProd->CFOP = $config->UF != $devolucao->fornecedor->cidade->uf ?
				$devolucao->natureza->CFOP_entrada_inter_estadual : $devolucao->natureza->CFOP_entrada_estadual;
			}
			$stdProd->uCom = $i->unidade_medida;
			$stdProd->qCom = $i->quantidade;
			$stdProd->vUnCom = ($i->valor_unit);
			$stdProd->vProd = $this->format(($i->quantidade * $i->valor_unit));
			$stdProd->uTrib = $i->unidade_tributavel == "" ? $i->unidade_medida : $i->unidade_tributavel;
			$stdProd->qTrib = $i->quantidade_tributavel == 0 ? $i->quantidade : $i->quantidade_tributavel;
			$stdProd->vUnTrib = ($i->valor_unit);
			if($i->vFrete > 0){
				$somaFrete += $stdProd->vFrete = $this->format($i->vFrete);
			}else{
				if($devolucao->vFrete > 0){
					if($itemCont < sizeof($devolucao->itens)){
						$somaFrete += $vFt =
						$this->format($devolucao->vFrete/$totalItens, 2);
						$stdProd->vFrete = $this->format($vFt);
					}else{
						$stdProd->vFrete = $this->format(($devolucao->vFrete-$somaFrete), 2);
					}
				}
			}

			$stdProd->indTot = 1;
			$somaProdutos += ($i->quantidade * $i->valor_unit);

			if($devolucao->despesa_acessorias > 0){
				if($itemCont < sizeof($devolucao->itens)){
					$totalVenda = $devolucao->valor_devolvido;

					$media = (((($stdProd->vProd-$totalVenda)/$totalVenda))*100);
					$media = 100 - ($media * -1);

					$tempAcrescimo = ($devolucao->despesa_acessorias*$media)/100;
					$somaAcrescimo += $this->format($tempAcrescimo);
					if($tempAcrescimo > 0.1)
						$stdProd->vOutro = $this->format($tempAcrescimo);
				}else{
					if($devolucao->despesa_acessorias - $somaAcrescimo > 0.1)
						$stdProd->vOutro = $this->format($devolucao->despesa_acessorias - $somaAcrescimo);
				}
			}

			// if($devolucao->vDesc > 0){
			// 	$stdProd->vDesc = $this->format($devolucao->vDesc/$totalItens);
			// }

			// if($venda->frete){
			// 	if($venda->frete->valor > 0){
			// 		$somaFrete += $vFt = $venda->frete->valor/$totalItens;
			// 		$stdProd->vFrete = $this->format($vFt);
			// 	}
			// }

			$vDesc = 0;

			if($devolucao->vDesc > 0.01 && $somaDesconto < $devolucao->vDesc){

				if($itemCont < sizeof($devolucao->itens)){
					$totalVenda = $devolucao->valor_devolvido;

					$media = (((($stdProd->vProd - $totalVenda)/$totalVenda))*100);
					$media = 100 - ($media * -1);

					$tempDesc = $this->format(($devolucao->vDesc*$media)/100);

					if($tempDesc > 0.01){
						$somaDesconto += $tempDesc;
						$stdProd->vDesc = $this->format($tempDesc, $config->casas_decimais);
					}else{
						$somaDesconto = $devolucao->vDesc;
						$stdProd->vDesc = $this->format($somaDesconto, $config->casas_decimais);
					}

				}else{
					if(($devolucao->vDesc - $somaDesconto) > 0.01){
						$stdProd->vDesc = $this->format($devolucao->vDesc - $somaDesconto, $config->casas_decimais);
					}
				}
			}

			$prod = $nfe->tagprod($stdProd);

		//TAG IMPOSTO

			$stdImposto = new \stdClass();
			$stdImposto->item = $itemCont;

			$imposto = $nfe->tagimposto($stdImposto);

			// ICMS
			if($regime == 3){ // regime normal

				//$venda->produto->CST
				$stdICMS = new \stdClass();
				$stdICMS->item = $itemCont;
				$stdICMS->orig = $i->orig;
				$stdICMS->CST = $i->cst_csosn;

				$stdICMS->modBC = 0;
				if($i->pRedBC == 0){

					if(strlen($i->codigo_anp) > 2){

						$stdICMS->vBCSTDest = 0;
						$stdICMS->vICMSSTDest = 0;
						$stdICMS->pRedBCEfet = 0;
						$stdICMS->vBCEfet = 0;
						$stdICMS->pICMSEfet = 0;
						$stdICMS->vICMSEfet = 0;
						$stdICMS->vICMSSTRet = $this->format($i->vICMSSTRet);
						$stdICMS->vBCSTRet = $this->format($i->vBCSTRet);
					}

					if($i->cst_csosn == 61){
						$stdICMS->qBCMonoRet = $this->format($i->qBCMonoRet);
						$stdICMS->vICMSMonoRet = $this->format($i->vICMSMonoRet);
						$stdICMS->adRemICMSRet = $this->format($i->adRemICMSRet);

					}

					if($i->cst_csosn == 60){

						if($devolucao->altera_manual == 0){
							if($i->perc_icms == 0){
								$VBC += $stdICMS->vBC = 0;
							}else{
								$VBC += $stdICMS->vBC = $stdProd->vProd + $stdProd->vOutro + $i->vFrete - $stdProd->vDesc;
							}
						}else{
							$VBC += $i->vbc_manual;
						}
						$stdICMS->pST = $this->format($i->pST);
						$stdICMS->vBCSTRet = $this->format($i->vBCSTRet);
						$stdICMS->vICMSSubstituto = $this->format($i->vICMSSubstituto);
						$stdICMS->vICMSSTRet = $this->format($i->vICMSSTRet);


						// $stdICMS->vBCEfet = $stdProd->vProd;
						// $stdICMS->pICMSEfet = 17;
						// $stdICMS->vICMSEfet = 4088.50;
					}else{
						if($devolucao->altera_manual == 0){
							$stdICMS->vBC = $stdProd->vProd + $stdProd->vOutro + $i->vFrete - $stdProd->vDesc;
						}else{
							$stdICMS->vBC = $i->vbc_manual;
						}

						if($i->cst_csosn == 61){
							$stdICMS->vBC = 0;
						}

						if($i->cst_csosn == 40 || $i->cst_csosn == 41){
							$stdICMS->vBCSTRet = 0;
							$stdICMS->vICMSSTRet = $this->format($i->vICMSSTRet);
							// $stdICMS->vBCSTDest = 0;
							// $stdICMS->vICMSSTDest = 0;
						}else{
							if($devolucao->altera_manual == 0){
								$VBC += $stdICMS->vBC;
							}else{
								$VBC += $i->vbc_manual;
							}
						}
					}

					if($config->UF != $devolucao->fornecedor->cidade->uf){
						$stdICMS->pST = $this->format($i->pST);
						$stdICMS->vBCSTRet = 0;
						$stdICMS->vICMSSubstituto = $this->format($i->vICMSSubstituto);
						$stdICMS->vICMSSTRet = $this->format($i->vICMSSTRet);
						// $stdICMS->vBCSTDest = 0;
						// $stdICMS->vICMSSTDest = 0;
						// $stdICMS->pRedBCEfet = 0;
					}

					$stdICMS->pICMS = $this->format($i->perc_icms);

					if($devolucao->altera_manual == 0){
						$stdICMS->vICMS = $stdICMS->vBC * ($stdICMS->pICMS/100);
					}else{
						$stdICMS->vICMS = $i->vicms_manual;
					}

					$stdICMS->pRedBC = $this->format($i->pRedBC);

					if($i->cst_csosn == 10){
						$stdICMS->modBCST = (int)$i->modBCST;
						$stdICMS->vBCST = $i->vBCST;

						$stdICMS->pICMSST = $i->pICMSST;
						$somaST += $stdICMS->vICMSST = $i->vICMSST;
						$stdICMS->pMVAST = $i->pMVAST;
					}

					if($stdICMS->CST == 41 && $i->vBCSTRet > 0 || strlen($i->codigo_anp) > 2 && $i->cst_csosn != 61){
						$ICMS = $nfe->tagICMSST($stdICMS);
					}else{
						$ICMS = $nfe->tagICMS($stdICMS);
					}
				}else{

					$tempB = 100-$i->pRedBC;
					$valorTemp = $stdProd->vProd-$stdProd->vDesc;
					$valorTemp = $this->format($valorTemp - ($valorTemp * ($tempB/100)));

					if($devolucao->altera_manual == 0){
						$VBC += $stdICMS->vBC = $valorTemp;
					}else{
						$VBC += $stdICMS->vBC = $i->vbc_manual;
					}

					$stdICMS->pICMS = $this->format($i->perc_icms);
					$stdICMS->vICMS = $stdICMS->vBC * ($stdICMS->pICMS/100);
					$stdICMS->pRedBC = $this->format($i->pRedBC);
					$ICMS = $nfe->tagICMS($stdICMS);

				}

				if($i->cst_csosn == '61'){

					$stdICMS->CST = $i->cst_csosn;
					$stdICMS->qBCMonoRet = $this->format($i->qBCMonoRet);
					$stdICMS->adRemICMSRet = $this->format($i->adRemICMSRet, 4);
					$stdICMS->vICMSMonoRet = $this->format($i->vICMSMonoRet, 4);
					$ICMS = $nfe->tagICMS($stdICMS);
				}

				if($i->cst_csosn != 40){
					$somaICMS += $this->format($stdICMS->vICMS);
				}

			}else{ // regime simples

				//$venda->produto->CST CSOSN

				$stdICMS = new \stdClass();

				$stdICMS->item = $itemCont;
				$stdICMS->orig = $i->orig;
				$stdICMS->CSOSN = $i->cst_csosn;
				$stdICMS->pCredSN = $this->format($i->perc_icms);
				$stdICMS->vCredICMSSN = $this->format($i->perc_icms);

				if($i->cst_csosn == 900){
					if($devolucao->altera_manual == 0){

						$stdICMS->pRedBC = $this->format($i->pRedBC);

						$tempB = 100-$i->pRedBC;
						$VBC += $stdICMS->vBC = (($stdProd->vProd-$stdProd->vDesc) * ($tempB/100));
						$stdICMS->pICMS = $this->format($i->perc_icms);
						$stdICMS->modBC = 0;

						$stdICMS->vICMS = (($stdProd->vProd-$stdProd->vDesc) * ($tempB/100)) * ($stdICMS->pICMS/100);
						$somaICMS += $this->format($stdICMS->vICMS);
					}else{
						$stdICMS->pRedBC = $this->format($i->pRedBC);

						$tempB = 100-$i->pRedBC;
						$VBC += $stdICMS->vBC = $i->vbc_manual;
						$stdICMS->pICMS = $this->format($i->perc_icms);
						$stdICMS->modBC = 0;

						$stdICMS->vICMS = $i->vicms_manual;
						$somaICMS += $this->format($stdICMS->vICMS);
					}
				}

				if($i->cst_csosn == '61'){
					$stdICMS->CST = $i->produto->CST_CSOSN;
					$stdICMS->qBCMonoRet = $this->format($i->qBCMonoRet);
					$stdICMS->adRemICMSRet = $this->format($i->adRemICMSRet, 4);
					$stdICMS->vICMSMonoRet = $this->format($i->vICMSMonoRet, 4);
					$ICMS = $nfe->tagICMS($stdICMS);
				}
				$ICMS = $nfe->tagICMSSN($stdICMS);

			}


			$stdPIS = new \stdClass();//PIS
			$stdPIS->item = $itemCont;
			$stdPIS->CST = $i->cst_pis;
			if($devolucao->altera_manual == 0){
				$stdPIS->vBC = $i->perc_pis > 0 ? (($stdProd->vProd-$stdProd->vDesc) + $stdProd->vOutro) : 0.00;
				$stdPIS->pPIS = $this->format($i->perc_pis);
				$stdPIS->vPIS = $this->format($stdPIS->vBC *
					($i->perc_pis/100));
			}else{
				$stdPIS->vBC = $i->vbc_manual;
				$stdPIS->pPIS = $this->format($i->perc_pis);
				$stdPIS->vPIS = $i->vpis_manual;
			}
			$PIS = $nfe->tagPIS($stdPIS);

			$stdCOFINS = new \stdClass();//COFINS
			$stdCOFINS->item = $itemCont;
			$stdCOFINS->CST = $i->cst_cofins;
			if($devolucao->altera_manual == 0){
				$stdCOFINS->vBC = $i->perc_cofins > 0 ? (($stdProd->vProd-$stdProd->vDesc) + $stdProd->vOutro) : 0.00;
				$stdCOFINS->pCOFINS = $this->format($i->perc_cofins);
				$stdCOFINS->vCOFINS = $this->format($stdCOFINS->vBC *
					($i->perc_cofins/100));
			}else{
				$stdCOFINS->vBC = $i->vbc_manual;
				$stdCOFINS->pCOFINS = $this->format($i->perc_cofins);
				$stdCOFINS->vCOFINS = $i->vcofins_manual;
			}
			$COFINS = $nfe->tagCOFINS($stdCOFINS);

			if($i->perc_ipi > 0){
				$std = new \stdClass();
				$std->item = $itemCont;
				$std->clEnq = null;
				$std->CNPJProd = null;
				$std->cSelo = null;
				$std->qSelo = null;
				$std->cEnq = '999';
				$std->CST = $i->cst_ipi;
				if($devolucao->altera_manual == 0){
					$std->vBC = $this->format($stdProd->vProd + $stdProd->vOutro + $i->vFrete);
					$std->pIPI = $this->format($i->perc_ipi);
					$somaIPI += $std->vIPI = $this->format($std->vBC * ($i->perc_ipi/100));
				}else{
					$std->vBC = $i->vbc_manual;
					$std->pIPI = $this->format($i->perc_ipi);
					$somaIPI += $std->vIPI = $i->vipi_manual;
				}
				$std->qUnid = null;
				$std->vUnid = null;

				$nfe->tagIPI($std);
			}

			if(strlen($i->codigo_anp) > 2){

				$stdComb = new \stdClass();
				$stdComb->item = $itemCont;
				$stdComb->cProdANP = $i->codigo_anp;
				$stdComb->descANP = $i->descricao_anp;

				if($i->perc_glp > 0){
					$stdComb->pGLP = $this->format($i->perc_glp);
				}

				if($i->perc_gnn > 0){
					$stdComb->pGNn = $this->format($i->perc_gnn);
				}

				if($i->perc_gni > 0){
					$stdComb->pGNi = $this->format($i->perc_gni);
				}

				$stdComb->vPart = $this->format($i->valor_partida);


				$stdComb->UFCons = $i->uf_cons;
				$nfe->tagcomb($stdComb);
			}

			$cest = $i->cest;
			$cest = str_replace(".", "", $cest);
			if(strlen($cest) > 0){
				$std = new \stdClass();
				$std->item = $itemCont;
				$std->CEST = $cest;
				$nfe->tagCEST($std);
			}

			// $stdComb = new \stdClass();
			// $stdComb->item = $itemCont;
			// $stdComb->cProdANP = '820101013';
			// $stdComb->descANP = 'OLEO DIESEL B S500 ADITIVADO';
			// $stdComb->UFCons = 'MT';
			// $nfe->tagcomb($stdComb);

		}

		$stdICMSTot = new \stdClass();
		$stdICMSTot->vBC = $this->format($VBC);
		$stdICMSTot->vICMS = $this->format($somaICMS);
		$stdICMSTot->vICMSDeson = 0.00;
		$stdICMSTot->vBCST = 0.00;
		$stdICMSTot->vST = $this->format($somaST);
		// $stdICMSTot->vProd = $this->format($VBC-$devolucao->despesa_acessorias);


		$stdICMSTot->vFrete = $devolucao->vFrete > 0 ? $devolucao->vFrete : $somaFrete;

		$stdICMSTot->vSeg = 0.00;
		// $stdICMSTot->vDesc = 0.00;
		$stdICMSTot->vDesc = $this->format($devolucao->vDesc);
		$stdICMSTot->vII = 0.00;
		$stdICMSTot->vIPI = 0.00;
		$stdICMSTot->vPIS = 0.00;
		$stdICMSTot->vCOFINS = 0.00;
		$stdICMSTot->vOutro = $this->format($devolucao->despesa_acessorias);

		if($stdICMSTot->vFrete > 0){
			$stdICMSTot->vNF =
			$this->format(($somaProdutos + $somaIPI + $devolucao->despesa_acessorias) + $stdICMSTot->vFrete+$stdICMSTot->vST - $devolucao->vDesc);
		}
		else{
			$stdICMSTot->vNF = $this->format($somaProdutos - $devolucao->vDesc + $somaIPI+$stdICMSTot->vST +
				$devolucao->despesa_acessorias);
		}

		$stdICMSTot->vTotTrib = 0.00;
		$ICMSTot = $nfe->tagICMSTot($stdICMSTot);


		$stdTransp = new \stdClass();
		$stdTransp->modFrete = $devolucao->frete_tipo;

		$transp = $nfe->tagtransp($stdTransp);


		// if($devolucao->transportadora_nome != ''){
		// 	$std = new \stdClass();

		// 	$std->xNome = $devolucao->transportadora_nome;

		// 	$std->xEnder = $devolucao->transportadora_endereco;
		// 	$std->xMun = $devolucao->transportadora_cidade;
		// 	$std->UF = $devolucao->transportadora_uf;


		// 	$cnpj_cpf = $devolucao->transportadora_cpf_cnpj;
		// 	$cnpj_cpf = str_replace(".", "", $cnpj_cpf);
		// 	$cnpj_cpf = str_replace("/", "", $cnpj_cpf);
		// 	$cnpj_cpf = str_replace("-", "", $cnpj_cpf);

		// 	if(strlen($cnpj_cpf) == 14) $std->CNPJ = $cnpj_cpf;
		// 	else $std->CPF = $cnpj_cpf;

		// 	$nfe->tagtransporta($std);
		// }

		if($devolucao->transportadora_id != null){
			$std = new \stdClass();

			$std->xNome = $devolucao->transportadora->razao_social;

			$std->xEnder = $devolucao->transportadora->logradouro;
			$std->xMun = $this->retiraAcentos($devolucao->transportadora->cidade->nome);
			$std->UF = $devolucao->transportadora->cidade->uf;


			$cnpj_cpf = $devolucao->transportadora->cnpj_cpf;
			$cnpj_cpf = str_replace(".", "", $cnpj_cpf);
			$cnpj_cpf = str_replace("/", "", $cnpj_cpf);
			$cnpj_cpf = str_replace("-", "", $cnpj_cpf);

			if(strlen($cnpj_cpf) == 14) $std->CNPJ = $cnpj_cpf;
			else $std->CPF = $cnpj_cpf;

			$nfe->tagtransporta($std);
		}

		if($devolucao->veiculo_uf != '' && $devolucao->veiculo_placa != ''){
			$std = new \stdClass();

			$placa = str_replace("-", "", $devolucao->veiculo_placa);
			$std->placa = strtoupper($placa);
			$std->UF = $devolucao->veiculo_uf;

			$nfe->tagveicTransp($std);
		}


		if($devolucao->frete_peso_bruto > 0 && $devolucao->frete_peso_liquido > 0){

			$stdVol = new \stdClass();
			$stdVol->item = 1;
			$stdVol->qVol = $devolucao->frete_quantidade;
			$stdVol->esp = $devolucao->frete_especie;

			$stdVol->nVol = $devolucao->frete_numero;
			$stdVol->pesoL = $devolucao->frete_peso_liquido;
			$stdVol->pesoB = $devolucao->frete_peso_bruto;
			$vol = $nfe->tagvol($stdVol);
		}




		// $stdResp = new \stdClass();
		// $stdResp->CNPJ = '36518327000105';
		// $stdResp->xContato= 'WWSoftwares';
		// $stdResp->email = 'wkarts@gmail.com';
		// $stdResp->fone = '75988449231';

		// $nfe->taginfRespTec($stdResp);


	//Fatura

		// $stdFat = new \stdClass();
		// $stdFat->nFat = (int)$lastNumero+1;
		// $stdFat->vOrig = $this->format($somaProdutos);
		// $stdFat->vDesc = $this->format($venda->desconto);
		// $stdFat->vLiq = $this->format($somaProdutos-$venda->desconto);

		// $fatura = $nfe->tagfat($stdFat);


	//Duplicata

		// if(count($venda->duplicatas) > 0){
		// 	$contFatura = 1;
		// 	foreach($venda->duplicatas as $ft){
		// 		$stdDup = new \stdClass();
		// 		$stdDup->nDup = "00".$contFatura;
		// 		$stdDup->dVenc = substr($ft->data_vencimento, 0, 10);
		// 		$stdDup->vDup = $this->format($ft->valor_integral);

		// 		$nfe->tagdup($stdDup);
		// 		$contFatura++;
		// 	}
		// }else{
		// 	$stdDup = new \stdClass();
		// 	$stdDup->nDup = '001';
		// 	$stdDup->dVenc = Date('Y-m-d');
		// 	$stdDup->vDup =  $this->format($somaProdutos-$venda->desconto);

		// 	$nfe->tagdup($stdDup);
		// }

		$stdPag = new \stdClass();
		$pag = $nfe->tagpag($stdPag);

		$stdDetPag = new \stdClass();


		$stdDetPag->tPag = '90';
		$stdDetPag->vPag = 0.00;

		$stdDetPag->indPag = '0'; // sem pagamento

		$detPag = $nfe->tagdetPag($stdDetPag);

		$stdInfoAdic = new \stdClass();
		$stdInfoAdic->infCpl = $this->retiraAcentos($devolucao->observacao);

		$infoAdic = $nfe->taginfAdic($stdInfoAdic);

		if($config->aut_xml != ''){

			$std = new \stdClass();
			$cnpj = preg_replace('/[^0-9]/', '', $config->aut_xml);
			$std->CNPJ = $cnpj;
			$aut = $nfe->tagautXML($std);
		}

		$std = new \stdClass();
		$std->CNPJ = env('RESP_CNPJ'); //CNPJ da pessoa jurídica responsável pelo sistema utilizado na emissão do documento fiscal eletrônico
		$std->xContato= env('RESP_NOME'); //Nome da pessoa a ser contatada
		$std->email = env('RESP_EMAIL'); //E-mail da pessoa jurídica a ser contatada
		$std->fone = env('RESP_FONE'); //Telefone da pessoa jurídica/física a ser contatada


		$nfe->taginfRespTec($std);

		// if($nfe->montaNFe()){
		// 	$arr = [
		// 		'chave' => $nfe->getChave(),
		// 		'xml' => $nfe->getXML(),
		// 		'nNf' => $stdIde->nNF
		// 	];
		// 	return $arr;
		// } else {
		// 	throw new Exception("Erro ao gerar NFe");
		// }

		try{
			$nfe->montaNFe();
			$arr = [
				'chave' => $nfe->getChave(),
				'xml' => $nfe->getXML(),
				'nNf' => $stdIde->nNF
			];
			return $arr;
		}catch(\Exception $e){
			return [
				'erros_xml' => $nfe->getErrors()
			];
		}

	}

	private function retiraAcentos($texto){
		return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/", "/(ç)/", "/(Ç)/"),explode(" ","a A e E i I o O u U n N c C"),$texto);
	}

	public function sign($xml){
		return $this->tools->signNFe($xml);
	}

    public function transmitir($signXml, $chave){

        try {
            $idLote  = str_pad(100, 15, '0', STR_PAD_LEFT);
            $indSinc = 1; // síncrono, uma NFe por vez
            $resp    = $this->tools->sefazEnviaLote([$signXml], $idLote, $indSinc);

            $st  = new Standardize();
            $std = $st->toStd($resp);

            // em síncrono o código de autorização vem em protNFe->infProt->cStat == 100
            if ($std->protNFe->infProt->cStat != 100) {
                return "[{$std->protNFe->infProt->cStat}] - {$std->protNFe->infProt->xMotivo}";
            }

            // monta e salva o XML já autorizado (já temos protocolo dentro de $resp)
            $xml = Complements::toAuthorize($signXml, $resp);
            //$public = env('SERVIDOR_WEB') ? 'public/' : '';
            //safe_file_put_contents( public_path('xml_devolucao/').$chave.'.xml', $xml);

            $folder = public_path('xml_devolucao');
            if (! is_dir($folder)) {
                mkdir($folder, 0755, true);
            }
            safe_file_put_contents($folder . DIRECTORY_SEPARATOR . $chave . '.xml', $xml);


            // retorno = número do protocolo
            return $std->protNFe->infProt->nProt;
        } catch (\Exception $e) {
            return "Erro: ".$e->getMessage();
        }

    }

	public function transmitir_($signXml, $chave){
		try{
			$idLote = str_pad(100, 15, '0', STR_PAD_LEFT);
			//$resp = $this->tools->sefazEnviaLote([$signXml], $idLote);

            $indSinc = 0; // síncrono, uma NFe por vez
            $resp    = $this->tools->sefazEnviaLote([$signXml], $idLote, $indSinc);

			$st = new Standardize();
			$std = $st->toStd($resp);
			sleep(3);

			if ($std->cStat != 103) {

				return "[$std->cStat] - $std->xMotivo";
			}
			// sleep(3);

			$recibo = $std->infRec->nRec;
			$protocolo = $this->tools->sefazConsultaRecibo($recibo);
			sleep(3);
			//return $protocolo;
			//$public = env('SERVIDOR_WEB') ? 'public/' : '';
			try {
				$xml = Complements::toAuthorize($signXml, $protocolo);
				header('Content-type: text/xml; charset=UTF-8');
				//safe_file_put_contents($public.'xml_devolucao/'.$chave.'.xml',$xml);
                safe_file_put_contents( public_path('xml_devolucao/').$chave.'.xml', $xml);
				return $recibo;
				// $this->printDanfe($xml);
			} catch (\Exception $e) {
				return "Erro: " . $st->toJson($protocolo);
			}

		} catch(\Exception $e){
			return "Erro: ".$e->getMessage() ;
		}

	}

    public function consultar_($devolucao){
        try {
            $this->tools->model('55');

            $chave    = $devolucao->chave_gerada;
            $response = $this->tools->sefazConsultaChave($chave);

            $stdCl = new Standardize($response);
            $arr   = $stdCl->toArray();

            // se estiver autorizado, monta e salva o XML (se ainda não tiver sido feito)
            if ($arr['xMotivo'] == 'Autorizado o uso da NF-e') {
                $std = $stdCl->toStd();
                if ($std->protNFe->infProt->cStat == 100) {
                    $xml = Complements::toAuthorize($devolucao->signed_xml, $response);
                    $public = env('SERVIDOR_WEB') ? 'public/' : '';
                    safe_file_put_contents( public_path('xml_devolucao/').$chave.'.xml', $xml);
                    // opcional: marcar $devolucao->estado = 'aprovado' e salvar
                }
            }

            return json_encode($arr);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

	public function consultar($devolucao){
		try {

			$this->tools->model('55');

			$chave = $devolucao->chave_gerada;
			$response = $this->tools->sefazConsultaChave($chave);

			$stdCl = new Standardize($response);
			$arr = $stdCl->toArray();

			// $arr = json_decode($json);
			return json_encode($arr);

		} catch (\Exception $e) {
			echo $e->getMessage();
		}
	}

	public function format($number, $dec = 2){
		$number = (float) $number;
		return number_format($number, $dec, ".", "");
	}

	public function cancelar($devolucao, $justificativa){
		try {

			$chave = $devolucao->chave_gerada;
			$response = $this->tools->sefazConsultaChave($chave);
			$stdCl = new Standardize($response);
			$arr = $stdCl->toArray();
			sleep(2);
				// return $arr;
			$xJust = $justificativa;

			$nProt = $arr['protNFe']['infProt']['nProt'];

			$response = $this->tools->sefazCancela($chave, $xJust, $nProt);
			sleep(3);
			$stdCl = new Standardize($response);
			$std = $stdCl->toStd();
			$arr = $stdCl->toArray();
			$json = $stdCl->toJson();

			if ($std->cStat != 128) {
        //TRATAR
			} else {
				$cStat = $std->retEvento->infEvento->cStat;
				$public = env('SERVIDOR_WEB') ? 'public/' : '';
				if ($cStat == '101' || $cStat == '135' || $cStat == '155' ) {
            //SUCESSO PROTOCOLAR A SOLICITAÇÂO ANTES DE GUARDAR
					$xml = Complements::toAuthorize($this->tools->lastRequest, $response);
					safe_file_put_contents(public_path('xml_devolucao_cancelada/').$chave.'.xml',$xml);

					return $json;
				} else {
            //houve alguma falha no evento
            //TRATAR
					return $json;
				}
			}
		} catch (\Exception $e) {
			return $e->getMessage();
    //TRATAR
		}
	}

	public function cartaCorrecao($devolucao, $correcao){

		try {

			$chave = $devolucao->chave_gerada;
			$xCorrecao = $correcao;
			$nSeqEvento = $devolucao->sequencia_cce+1;
			$response = $this->tools->sefazCCe($chave, $xCorrecao, $nSeqEvento);
			sleep(2);

			$stdCl = new Standardize($response);

			$std = $stdCl->toStd();

			$arr = $stdCl->toArray();

			$json = $stdCl->toJson();

			if ($std->cStat != 128) {
        //TRATAR
			} else {
				$cStat = $std->retEvento->infEvento->cStat;
				if ($cStat == '135' || $cStat == '136') {
					$public = env('SERVIDOR_WEB') ? 'public/' : '';
            //SUCESSO PROTOCOLAR A SOLICITAÇÂO ANTES DE GUARDAR
					$xml = Complements::toAuthorize($this->tools->lastRequest, $response);
					safe_file_put_contents( public_path('xml_devolucao_correcao/').$chave.'.xml',$xml);

					$devolucao->sequencia_cce = $devolucao->sequencia_cce + 1;
					$devolucao->save();
					return $json;

				} else {
            //houve alguma falha no evento
					return ['erro' => true, 'data' => $arr, 'status' => 402];
            //TRATAR
				}
			}
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

}
