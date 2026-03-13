<?php
namespace App\Services;

use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use App\Models\Transferencia;
use App\Models\ConfigNota;
use App\Models\Certificado;
use NFePHP\NFe\Complements;
use App\Models\Tributacao;
use App\Models\Difal;
use App\Models\IBPT;
use App\Models\Filial;
use NFePHP\Common\Soap\SoapCurl;
use NFePHP\NFe\Factories\Contingency;
use App\Models\Contigencia;

error_reporting(E_ALL);
ini_set('display_errors', 'On');

class TransferenciaService{

	private $config;
	private $tools;
	protected $empresa_id = null;
    protected $empresa_uf = null;

	public function __construct($config, $empresa_id = null){
		if($empresa_id == null){
			$value = session('user_logged');
			$this->empresa_id = $value['empresa'];
		}else{
			$this->empresa_id = $empresa_id;
		}
		if(isset($config['is_filial']) && $config['is_filial']){
			$certificado = Filial::findOrFail($config['is_filial']);

			$this->tools = new Tools(json_encode($config), Certificate::readPfx($certificado->arquivo_certificado, $certificado->senha_certificado));
		}else{
			$certificado = Certificado::
			where('empresa_id', $this->empresa_id)
			->first();

			$this->tools = new Tools(json_encode($config), Certificate::readPfx($certificado->arquivo, $certificado->senha));
		}
		$soapCurl = new SoapCurl();
		$soapCurl->httpVersion('1.1');
		$this->tools->loadSoapClass($soapCurl);

		$contigencia = $this->getContigencia();

		if($contigencia != null){
			$contingency = new Contingency($contigencia->status_retorno);
			$this->tools->contingency = $contingency;
		}

		$this->config = $config;
		$this->tools->model(55);
	}

	private function getContigencia(){
		$active = Contigencia::
		where('empresa_id', $this->empresa_id)
		->where('status', 1)
		->where('documento', 'NFe')
		->first();
		return $active;
	}

    private function getCUF($uf)
    {
        $ufs = [
            'RO' => '11',
            'AC' => '12',
            'AM' => '13',
            'RR' => '14',
            'PA' => '15',
            'AP' => '16',
            'TO' => '17',
            'MA' => '21',
            'PI' => '22',
            'CE' => '23',
            'RN' => '24',
            'PB' => '25',
            'PE' => '26',
            'AL' => '27',
            'SE' => '28',
            'BA' => '29',
            'MG' => '31',
            'ES' => '32',
            'RJ' => '33',
            'SP' => '35',
            'PR' => '41',
            'SC' => '42',
            'RS' => '43',
            'MS' => '50',
            'MT' => '51',
            'GO' => '52',
            'DF' => '53',
        ];

        return $ufs[$uf] ?? null;
    }

    public function gerarNFe($transferencia){

        $config = (object) $this->config['emitente']; // ← agora usa emitente vindo do Controller
        $destinatario = (object) $this->config['destinatario']; // ← e destinatario também

        $tributacao = Tributacao::where('empresa_id', $this->empresa_id)->first();

        $nfe = new Make();
        $stdInNFe = new \stdClass();
        $stdInNFe->versao = $this->config['versao'] ?? '4.00';
        $stdInNFe->Id = null;
        $stdInNFe->pk_nItem = '';
        $infNFe = $nfe->taginfNFe($stdInNFe);

        //$lastNumero = $this->config['ultimo_numero_nfe'] ?? 0;

        $stdIde = new \stdClass();
        $stdIde->cUF = $this->getCUF($config->UF);
        $stdIde->cNF = rand(11111,99999);
        $stdIde->natOp = $transferencia->natureza->natureza;
        $stdIde->mod = 55;
        //$stdIde->serie = $this->config['numero_serie_nfe'] ?? '1';
        //$stdIde->nNF = (int) $lastNumero + 1;
        $stdIde->serie = (int) $transferencia->serie;
        $stdIde->nNF = (int) $transferencia->numero_nfe;
        $stdIde->dhEmi = date("Y-m-d\TH:i:sP");
        $stdIde->dhSaiEnt = date("Y-m-d\TH:i:sP");
        $stdIde->tpNF = $transferencia->tpNF;
        $stdIde->idDest = $config->UF != $destinatario->UF ? 2 : 1;
        $stdIde->cMunFG = $config->codMun;
        $stdIde->tpImp = $this->config['tipo_impressao_danfe'] ?? 1;
        $stdIde->tpEmis = 1;
        $stdIde->cDV = 0;
        $stdIde->tpAmb = $this->config['tpAmb'] ?? 2;
        $stdIde->finNFe = $transferencia->finNFe;
        $stdIde->indFinal = 0;
        $stdIde->indPres = 1;
        $stdIde->procEmi = '0';
        $stdIde->verProc = '3.10.31';
        $nfe->tagide($stdIde);

        // Emitente
        $stdEmit = new \stdClass();
        $stdEmit->xNome = $config->razao_social;
        $stdEmit->xFant = $config->nome_fantasia ?? '';
        $stdEmit->IE = preg_replace('/[^0-9]/', '', $config->ie ?? '');
        $stdEmit->CRT = ($tributacao->regime == 0 || $tributacao->regime == 2) ? 1 : 3;
        $cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);
        if (strlen($cnpj) == 14) {
            $stdEmit->CNPJ = $cnpj;
        } else {
            $stdEmit->CPF = $cnpj;
        }
        $nfe->tagemit($stdEmit);

        // Endereço Emitente
        $stdEnderEmit = new \stdClass();
        $stdEnderEmit->xLgr = $this->retiraAcentos($config->logradouro);
        $stdEnderEmit->nro = $config->numero;
        $stdEnderEmit->xCpl = $this->retiraAcentos($config->complemento ?? '');
        $stdEnderEmit->xBairro = $this->retiraAcentos($config->bairro);
        $stdEnderEmit->cMun = $config->codMun;
        $stdEnderEmit->xMun = $this->retiraAcentos($config->municipio);
        $stdEnderEmit->UF = $config->UF;
        $stdEnderEmit->CEP = preg_replace('/[^0-9]/', '', $config->cep);
        $stdEnderEmit->cPais = $config->codPais ?? 1058;
        $stdEnderEmit->xPais = $config->pais ?? 'BRASIL';
        $telefone = preg_replace('/[^0-9]/', '', $config->fone ?? '');
        $stdEnderEmit->fone = substr($telefone, 0, 11);
        $nfe->tagenderEmit($stdEnderEmit);

        // Destinatário
        $stdDest = new \stdClass();
        $stdDest->xNome = $this->retiraAcentos($destinatario->razao_social);
        if (strtoupper($destinatario->ie) == 'ISENTO') {
            $stdDest->indIEDest = '2';
        } else {
            $stdDest->indIEDest = '1';
        }
        $destCnpjCpf = preg_replace('/[^0-9]/', '', $destinatario->cnpj);
        if (strlen($destCnpjCpf) == 14) {
            $stdDest->CNPJ = $destCnpjCpf;
            $stdDest->IE = preg_replace('/[^0-9]/', '', $destinatario->ie ?? '');
        } else {
            $stdDest->CPF = $destCnpjCpf;
        }
        $nfe->tagdest($stdDest);

        // Endereço Destinatário
        $stdEnderDest = new \stdClass();
        $stdEnderDest->xLgr = $this->retiraAcentos($destinatario->logradouro);
        $stdEnderDest->nro = $destinatario->numero;
        $stdEnderDest->xCpl = $this->retiraAcentos($destinatario->complemento ?? '');
        $stdEnderDest->xBairro = $this->retiraAcentos($destinatario->bairro);
        $stdEnderDest->cMun = $destinatario->codMun;
        $stdEnderDest->xMun = $this->retiraAcentos($destinatario->municipio);
        $stdEnderDest->UF = $destinatario->UF;
        $stdEnderDest->CEP = preg_replace('/[^0-9]/', '', $destinatario->cep);
        $stdEnderDest->cPais = $destinatario->codPais ?? 1058;
        $stdEnderDest->xPais = $destinatario->pais ?? 'BRASIL';
        $telefoneDest = preg_replace('/[^0-9]/', '', $destinatario->fone ?? '');
        $stdEnderDest->fone = substr($telefoneDest, 0, 11);
        $nfe->tagenderDest($stdEnderDest);

        $somaProdutos = 0;
        $somaICMS = 0;
        $somaIPI = 0;
        //PRODUTOS
        $itemCont = 0;

        $totalItens = count($transferencia->itens);
        $somaFrete = 0;
        $somaDesconto = 0;
        $somaAcrescimo = 0;
        $somaISS = 0;
        $somaServico = 0;

        $VBC = 0;
        $somaFederal = 0;
        $somaEstadual = 0;
        $somaMunicipal = 0;

        $p = null;

        $nfesRef = "";

        $somaApCredito = 0;
        $somaVICMSST = 0;

        $obsIbpt = "";
        foreach($transferencia->itens as $i){

            $p = $i;
            $ncm = $i->produto->NCM;
            $ncm = str_replace(".", "", $ncm);

            $ibpt = IBPT::getIBPT($config->UF, $ncm);

            $itemCont++;

            $stdProd = new \stdClass();
            $stdProd->item = $itemCont;

            $cod = $this->validate_EAN13Barcode($i->produto->codBarras);

            $stdProd->cEAN = $cod ? $i->produto->codBarras : 'SEM GTIN';
            $stdProd->cEANTrib = $cod ? $i->produto->codBarras : 'SEM GTIN';
            // $stdProd->cEAN = $i->produto->codBarras;
            // $stdProd->cEANTrib = $i->produto->codBarras;
            $stdProd->cProd = $i->produto->id;
            if($i->produto->referencia != ''){
                $stdProd->cProd = $i->produto->referencia;
            }

            if($i->produto_nome != null){
                $nomeProduto = $i->produto_nome;
            }else{
                $nomeProduto = $i->produto->nome;
                if($i->produto->grade){
                    $nomeProduto .= " ". $i->produto->str_grade;
                }
            }

            if($i->produto->grade){
                $nomeProduto .= " ". $i->produto->str_grade;
            }

            if($i->produto->lote){
                $nomeProduto .= " | LOTE: ". $i->produto->lote;
            }
            if($i->produto->vencimento){
                $nomeProduto .= ", VENCIMENTO: ". $i->produto->vencimento;
            }
            $stdProd->xProd = $this->retiraAcentos($nomeProduto);

            if($i->produto->cBenef){
                $stdProd->cBenef = $i->produto->cBenef;
            }

            if($i->produto->perc_iss > 0){
                $stdProd->NCM = '00';
            }else{
                $stdProd->NCM = $ncm;
            }
            /*
            if($transferencia->natureza->sobrescreve_cfop == 0){
                $stdProd->CFOP = $config->UF != $destinatario->UF ?
                    $i->produto->CFOP_saida_inter_estadual : $i->produto->CFOP_saida_estadual;
            }else{
                $stdProd->CFOP = $config->UF != $destinatario->UF ?
                    $transferencia->natureza->CFOP_saida_inter_estadual : $transferencia->natureza->CFOP_saida_estadual;
            }
            */
            if($transferencia->natureza->sobrescreve_cfop == 0){
                if ($transferencia->tpNF == 1) { // Saída
                    $stdProd->CFOP = $config->UF != $destinatario->UF ?
                        $i->produto->CFOP_saida_inter_estadual : $i->produto->CFOP_saida_estadual;
                } else { // Entrada
                    $stdProd->CFOP = $config->UF != $destinatario->UF ?
                        $i->produto->CFOP_entrada_inter_estadual : $i->produto->CFOP_entrada_estadual;
                }
            } else {
                if ($transferencia->tpNF == 1) { // Saída
                    $stdProd->CFOP = $config->UF != $destinatario->UF ?
                        $transferencia->natureza->CFOP_saida_inter_estadual : $transferencia->natureza->CFOP_saida_estadual;
                } else { // Entrada
                    $stdProd->CFOP = $config->UF != $destinatario->UF ?
                        $transferencia->natureza->CFOP_entrada_inter_estadual : $transferencia->natureza->CFOP_entrada_estadual;
                }
            }

            $stdProd->uCom = $i->produto->unidade_venda;
            $stdProd->qCom = $i->quantidade;
            $stdProd->vUnCom = $this->format($i->valor_unitario, $config->casas_decimais);
            $stdProd->vProd = $this->format(($i->sub_total), $config->casas_decimais);

            if($i->produto->unidade_tributavel == ''){
                $stdProd->uTrib = $i->produto->unidade_venda;
            }else{
                $stdProd->uTrib = $i->produto->unidade_tributavel;
            }

            // $stdProd->qTrib = $i->quantidade;
            if($i->produto->quantidade_tributavel == 0){
                $stdProd->qTrib = $i->quantidade;
            }else{
                $stdProd->qTrib = $i->produto->quantidade_tributavel * $i->quantidade;
            }
            $stdProd->vUnTrib = $this->format($i->valor_unitario, $config->casas_decimais);
            if($i->produto->quantidade_tributavel > 0){
                $stdProd->vUnTrib = $stdProd->vProd/$stdProd->qTrib;
            }
            $stdProd->indTot = $i->produto->perc_iss > 0 ? 0 : 1;
            $somaProdutos += $stdProd->vProd;

            $vDesc = 0;
            $vBCST = 0;

            $prod = $nfe->tagprod($stdProd);

            //TAG IMPOSTO

            $stdImposto = new \stdClass();
            $stdImposto->item = $itemCont;
            if($i->produto->perc_iss > 0){
                $stdImposto->vTotTrib = 0.00;
            }

            if($stdProd->CFOP != '6909' && $stdProd->CFOP != '5909'){
                if($i->produto->ibpt){
                    $vProd = $stdProd->vProd;
                    if($i->produto->origem == 1 || $i->produto->origem == 2){
                        $federal = $this->format(($vProd*($i->produto->ibpt->importado/100)), 2);
                    }else{
                        $federal = $this->format(($vProd*($i->produto->ibpt->nacional/100)), 2);
                    }
                    $somaFederal += $federal;

                    $estadual = $this->format(($vProd*($i->produto->ibpt->estadual/100)), 2);
                    $somaEstadual += $estadual;

                    $municipal = $this->format(($vProd*($i->produto->ibpt->municipal/100)), 2);
                    $somaMunicipal += $municipal;

                    $soma = $federal + $estadual + $municipal;
                    $stdImposto->vTotTrib = $soma;

                    $obsIbpt = " FONTE: " . $i->produto->ibpt->fonte ?? '';
                    $obsIbpt .= " VERSAO: " . $i->produto->ibpt->versao ?? '';
                    $obsIbpt .= " | ";

                }else{
                    if($ibpt != null){

                        $vProd = $stdProd->vProd;

                        if($i->produto->origem == 1 || $i->produto->origem == 2){
                            $federal = $this->format(($vProd*($ibpt->importado_federal/100)), 2);

                        }else{
                            $federal = $this->format(($vProd*($ibpt->nacional_federal/100)), 2);
                        }
                        $somaFederal += $federal;

                        $estadual = $this->format(($vProd*($ibpt->estadual/100)), 2);
                        $somaEstadual += $estadual;

                        $municipal = $this->format(($vProd*($ibpt->municipal/100)), 2);
                        $somaMunicipal += $municipal;

                        $soma = $federal + $estadual + $municipal;
                        $stdImposto->vTotTrib = $soma;

                        $obsIbpt = " FONTE: " . $ibpt->versao ?? '';
                        $obsIbpt .= " | ";
                    }
                }
            }

            $imposto = $nfe->tagimposto($stdImposto);

            if($transferencia->natureza->CST_CSOSN){
                $i->cst_csosn = $transferencia->natureza->CST_CSOSN;
            }

            // ICMS


            if($tributacao->regime == 1){
                $i->cst_csosn = $i->produto->CST_CSOSN;
                //$venda->produto->CST  CST

                $stdICMS = new \stdClass();

                $stdICMS->pICMS = $this->format($i->perc_icms);

                $stdICMS->item = $itemCont;
                $stdICMS->orig = $i->produto->origem;

                if($i->cst_csosn == '10'){
                    $stdICMS->modBCST = (int)$i->produto->modBCST;
                    $stdICMS->vBCST = $i->vBCST;
                    $stdICMS->pICMSST = $this->format($i->pICMSST);
                    $somaVICMSST += $stdICMS->vICMSST = $stdICMS->vBCST * ($stdICMS->pICMSST/100);
                }

                $stdICMS->CST = $i->cst_csosn;

                // $stdICMS->modBC = 0;
                $stdICMS->modBC = $i->produto->modBC;
                $stdICMS->vBC = $this->format($i->vbc_icms);
                $stdICMS->vICMS = $this->format($i->valor_icms);

                if($i->pRedBC == 0){
                    if($stdICMS->CST == '500'){
                        $stdICMS->pRedBCEfet = 0.00;
                        $stdICMS->vBCEfet = 0.00;
                        $stdICMS->pICMSEfet = 0.00;
                        $stdICMS->vICMSEfet = 0.00;
                    }else if($stdICMS->CST == '60'){
                        $stdICMS->vBCSTRet = 0.00;
                        $stdICMS->vICMSSTRet = 0.00;
                        $stdICMS->vBCSTDest = 0.00;
                        $stdICMS->vICMSSTDest = 0.00;

                    }else if($stdICMS->CST == '40' || $stdICMS->CST == '41' || $stdICMS->CST == '51'){
                        $stdICMS->vICMS = 0;
                        $stdICMS->vBC = 0;
                    }else{
                        if($stdICMS->pICMS > 0){
                            $VBC += $stdICMS->vBC;
                            $somaICMS += $stdICMS->vICMS;
                        }else{
                            $stdICMS->vBC = 0;
                        }
                    }
                }else{

                    $tempB = 100-$i->pRedBC;
                    $VBC += $stdICMS->vBC;

                    $v = $stdProd->vProd * ($tempB/100);

                    // $VBC += $stdICMS->vBC = number_format($v,2,'.','');
                    $stdICMS->pICMS = $this->format($i->perc_icms);
                    $somaICMS += $stdICMS->vICMS = $this->format($i->valor_icms);
                    $stdICMS->pRedBC = $this->format($i->pRedBC);
                }

                if($i->cst_csosn == '61'){
                    $stdICMS->qBCMonoRet = $this->format($stdProd->qTrib);
                    $stdICMS->adRemICMSRet = $this->format($i->produto->adRemICMSRet, 4);
                    $stdICMS->vICMSMonoRet = $this->format($i->produto->adRemICMSRet*$stdProd->qTrib, 4);
                }
                if($i->cst_csosn == '60'){
                    $ICMS = $nfe->tagICMSST($stdICMS);
                }else{
                    $ICMS = $nfe->tagICMS($stdICMS);
                }
                // regime simples
            }else{
                //$venda->produto->CST CSOSN
                $stdICMS = new \stdClass();

                $stdICMS->item = $itemCont;
                $stdICMS->orig = $i->produto->origem;

                $stdICMS->CSOSN = $i->cst_csosn;

                // if($i->cst_csosn == '10'){
                // 	$stdICMS->modBCST = (int)$i->produto->modBCST;
                // 	$stdICMS->vBCST = $i->vBCST;
                // 	$stdICMS->pICMSST = $this->format($i->pICMSST);
                // 	$somaVICMSST += $stdICMS->vICMSST = $stdICMS->vBCST * ($stdICMS->pICMSST/100);
                // }

                if($stdICMS->CSOSN == '500'){
                    $stdICMS->modBCST = (int)$i->produto->modBCST;
                    $vBCST = $stdICMS->vBCSTRet = $i->vBCST;
                    $stdICMS->pST = $this->format($i->pICMSST);
                    $somaVICMSST += $stdICMS->vICMSSTRet = $stdICMS->vBCSTRet * ($stdICMS->pST/100);
                }

                if($stdICMS->CSOSN == '201'){

                    $stdICMS->modBCST = (int)$i->modBCST;
                    $stdICMS->vBCST = $i->vBCST;
                    $stdICMS->pICMSST = $this->format($i->pICMSST);
                    $somaVICMSST += $stdICMS->vICMSST = $this->format($i->vICMSST);
                }

                $stdICMS->modBC = $i->produto->modBC;

                $stdICMS->vBC = $this->format($i->vbc_icms);
                $stdICMS->pICMS = $this->format($i->perc_icms);
                $stdICMS->vICMS = $this->format($i->valor_icms);

                if($tributacao->perc_ap_cred > 0 && $stdICMS->CSOSN == 101){
                    $stdICMS->pCredSN = $this->format($tributacao->perc_ap_cred);
                    $somaApCredito += $stdICMS->vCredICMSSN = $this->format($stdProd->vProd*($tributacao->perc_ap_cred/100));
                }else{
                    $stdICMS->pCredSN = 0;
                    $stdICMS->vCredICMSSN = 0;
                }

                if($i->produto->CST_CSOSN == '61'){
                    $stdICMS->CST = $i->produto->CST_CSOSN;
                    $stdICMS->qBCMonoRet = $this->format($stdProd->qTrib);
                    $stdICMS->adRemICMSRet = $this->format($i->produto->adRemICMSRet, 4);
                    $stdICMS->vICMSMonoRet = $this->format($i->produto->adRemICMSRet*$stdProd->qTrib, 4);
                    $ICMS = $nfe->tagICMS($stdICMS);
                }else{
                    $ICMS = $nfe->tagICMSSN($stdICMS);
                }


                if($stdICMS->vICMS > 0 && $stdICMS->CSOSN == 900){
                    $VBC += $stdICMS->vBC;
                    $somaICMS += $stdICMS->vICMS;
                }


                // $VBC = 0;
                // $somaICMS = 0;
            }

            //PIS

            $vbcPis = $stdICMS->vBC;
            if($tributacao->exclusao_icms_pis_cofins){
                $vbcPis -= $stdICMS->vICMS;
            }
            $stdPIS = new \stdClass();
            $stdPIS->item = $itemCont;
            $stdPIS->CST = $i->produto->CST_PIS;
            $stdPIS->vBC = $this->format($i->produto->perc_pis) > 0 ? $vbcPis : 0.00;
            $stdPIS->pPIS = $this->format($i->produto->perc_pis);
            $stdPIS->vPIS = $this->format(($vbcPis) *
                ($i->produto->perc_pis/100));
            $PIS = $nfe->tagPIS($stdPIS);

            //COFINS
            $vbcCofins = $stdICMS->vBC;
            if($tributacao->exclusao_icms_pis_cofins){
                $vbcCofins -= $stdICMS->vICMS;
            }
            $stdCOFINS = new \stdClass();
            $stdCOFINS->item = $itemCont;
            $stdCOFINS->CST = $i->produto->CST_COFINS;
            $stdCOFINS->vBC = $this->format($i->produto->perc_cofins) > 0 ? $vbcCofins : 0.00;
            $stdCOFINS->pCOFINS = $this->format($i->produto->perc_cofins);
            $stdCOFINS->vCOFINS = $this->format(($vbcCofins) *
                ($i->produto->perc_cofins/100));

            $COFINS = $nfe->tagCOFINS($stdCOFINS);

            //IPI

            $std = new \stdClass();
            $std->item = $itemCont;
            //999 – para tributação normal IPI
            $std->cEnq = $i->produto->cenq_ipi ?? '999';
            $std->CST = $i->produto->CST_IPI;
            $std->vBC = $this->format($i->produto->perc_ipi) > 0 ? $stdProd->vProd : 0.00;
            $std->pIPI = $this->format($i->produto->perc_ipi);
            $somaIPI += $std->vIPI = $this->format($std->vBC * ($std->pIPI/100));

            $nfe->tagIPI($std);

            if(strlen($i->produto->codigo_anp) > 2){
                $stdComb = new \stdClass();
                $stdComb->item = $itemCont;
                $stdComb->cProdANP = $i->produto->codigo_anp;
                $stdComb->descANP = $i->produto->getDescricaoAnp();

                if($i->produto->perc_glp > 0){
                    $stdComb->pGLP = $this->format($i->produto->perc_glp);
                }

                if($i->produto->perc_gnn > 0){
                    $stdComb->pGNn = $this->format($i->produto->perc_gnn);
                }

                if($i->produto->perc_gni > 0){
                    $stdComb->pGNi = $this->format($i->produto->perc_gni);
                }

                $stdComb->vPart = $this->format($i->produto->valor_partida);

                $stdComb->UFCons = $venda->cliente ? $venda->cliente->cidade->uf : $config->UF;
                if($i->produto->pBio > 0){
                    $stdComb->pBio = $i->produto->pBio;
                }
                $nfe->tagcomb($stdComb);
            }

            if($stdIde->indFinal == 0 && strlen($i->produto->codigo_anp) > 2){
                $stdOrigComb = new \stdClass();

                $stdOrigComb->item = $itemCont;
                $stdOrigComb->indImport = $i->produto->indImport;
                $stdOrigComb->cUFOrig = $i->produto->cUFOrig;
                $stdOrigComb->pOrig = $i->produto->pOrig;
                $nfe->tagorigComb($stdOrigComb);
            }

            $cest = $i->cest;
            $cest = str_replace(".", "", $cest);
            if(strlen($cest) > 0){
                $std = new \stdClass();
                $std->item = $itemCont;
                $std->CEST = $cest;
                $nfe->tagCEST($std);
            }

            if($stdIde->idDest == 2 && $stdIde->indFinal == 1 && $tributacao->regime == 1){

                // $difal = Difal::where('cfop', $stdProd->CFOP)
                // ->where('uf', $stdEnderDest->UF)->first();
                $difal = Difal::where('cfop', $stdProd->CFOP)
                    ->where('empresa_id', $this->empresa_id)
                    ->where('uf', $stdEnderDest->UF)->first();

                if($difal){

                    $std = new \stdClass();
                    $std->item = $itemCont;
                    $std->vBCUFDest = $stdICMS->vBC;
                    // $std->vBCUFDest = $stdICMS->vBC;
                    $std->vBCFCPUFDest = $stdICMS->vBC;
                    // $std->vBCFCPUFDest = $stdICMS->vBC;
                    $std->pFCPUFDest = $this->format($difal->pFCPUFDest);
                    $std->pICMSUFDest = $this->format($difal->pICMSUFDest);

                    $std->pICMSInter = $this->format($difal->pICMSInter);
                    $std->pICMSInterPart = $this->format($difal->pICMSInterPart);
                    // $std->vFCPUFDest = $this->format($stdProd->vProd * ($i->produto->perc_fcp_interestadual/100));
                    $std->vFCPUFDest = $this->format($std->vBCUFDest * ($std->pFCPUFDest/100));
                    // $std->vICMSUFDest = $this->format($stdProd->vProd * ($i->produto->perc_icms_interestadual/100));

                    $vICMSUFDest = $std->vBCFCPUFDest * ($std->pICMSInter/100);
                    $vICMSUFDestAux = $stdICMS->vBC * ($std->pICMSUFDest/100);
                    $std->vICMSUFDest = $this->format($vICMSUFDestAux-$vICMSUFDest);
                    // $std->vICMSUFDest = $this->format($stdICMS->vBC * ($i->produto->perc_icms_interestadual/100));
                    $std->vICMSUFRemet = $this->format($vICMSUFDestAux-$vICMSUFDest) - $std->vICMSUFDest;

                    $nfe->tagICMSUFDest($std);
                }
            }

        }

        $stdICMSTot = new \stdClass();
        $stdICMSTot->vProd = $this->format($somaProdutos, $config->casas_decimais);
        $stdICMSTot->vBC = $this->format($VBC);
        $stdICMSTot->vICMS = $this->format($somaICMS);

        $stdICMSTot->vICMSDeson = 0.00;
        $stdICMSTot->vBCST = $this->format($vBCST);
        $stdICMSTot->vST = $this->format($somaVICMSST);
        $stdICMSTot->vFrete = 0.00;

        $stdICMSTot->vSeg = 0.00;
        $stdICMSTot->vDesc = 0.00;
        $stdICMSTot->vII = 0.00;
        $stdICMSTot->vIPI = 0.00;
        $stdICMSTot->vPIS = 0.00;
        $stdICMSTot->vCOFINS = 0.00;
        $stdICMSTot->vOutro = 0.00;

        $stdICMSTot->vNF = $this->format($somaProdutos+$stdICMSTot->vFrete+$somaIPI+$somaVICMSST);

        $stdICMSTot->vTotTrib = 0.00;
        $ICMSTot = $nfe->tagICMSTot($stdICMSTot);

        //inicio totalizao issqn

        if($somaISS > 0){
            $std = new \stdClass();
            $std->vServ = $this->format($somaServico + $venda->desconto);
            $std->vBC = $this->format($somaServico);
            $std->vISS = $this->format($somaISS);
            $std->dCompet = date('Y-m-d');

            $std->cRegTrib = 6;

            $nfe->tagISSQNTot($std);
        }

        //fim totalizao issqn

        $stdTransp = new \stdClass();
        $stdTransp->modFrete = '9';

        $transp = $nfe->tagtransp($stdTransp);

        if($transferencia->transportadora){
            $std = new \stdClass();
            $std->xNome = $transferencia->transportadora->razao_social;

            $std->xEnder = $transferencia->transportadora->logradouro;
            $std->xMun = $this->retiraAcentos($transferencia->transportadora->cidade->nome);
            $std->UF = $transferencia->transportadora->cidade->uf;

            $cnpj_cpf = preg_replace('/[^0-9]/', '', $transferencia->transportadora->cnpj_cpf);

            if(strlen($cnpj_cpf) == 14) $std->CNPJ = $cnpj_cpf;
            else $std->CPF = $cnpj_cpf;

            $nfe->tagtransporta($std);
        }

        if($stdTransp->modFrete != 9){

            $std = new \stdClass();
            $placa = str_replace("-", "", $venda->placa);
            $std->placa = strtoupper($placa);
            $std->UF = $venda->uf;

            // if($config->UF == $venda->cliente->cidade->uf){
            if($venda->placa != "" && $venda->uf){
                $nfe->tagveicTransp($std);
            }

            if($venda->qtd_volumes > 0 || $venda->peso_liquido > 0
                || $venda->peso_bruto > 0 || $venda->especie){
                $stdVol = new \stdClass();
                $stdVol->item = 1;
                $stdVol->qVol = $venda->qtd_volumes;
                $stdVol->esp = $venda->especie;

                $stdVol->nVol = $venda->numeracao_volumes;
                $stdVol->pesoL = $venda->peso_liquido;
                $stdVol->pesoB = $venda->peso_bruto;
                $vol = $nfe->tagvol($stdVol);
            }
        }


        $stdPag = new \stdClass();
        $pag = $nfe->tagpag($stdPag);


        $stdDetPag = new \stdClass();

        $stdDetPag->tPag = 90;
        $stdDetPag->vPag = 0;
        $detPag = $nfe->tagdetPag($stdDetPag);


        $stdInfoAdic = new \stdClass();

        $obs = " " . $transferencia->observacao;

        if($nfesRef != ""){
            $obs .= " Chaves referênciadas: " . $nfesRef;
        }

        if($somaEstadual > 0 || $somaFederal > 0 || $somaMunicipal > 0){
            $obs .= " Trib. aprox. ";
            if($somaFederal > 0){
                $obs .= "R$ " . number_format($somaFederal, 2, ',', '.') ." Federal";
            }
            if($somaEstadual > 0){
                $obs .= ", R$ ".number_format($somaEstadual, 2, ',', '.')." Estadual";
            }
            if($somaMunicipal > 0){
                $obs .= ", R$ ".number_format($somaMunicipal, 2, ',', '.')." Municipal";
            }
            // $ibpt = IBPT::where('uf', $config->UF)->first();

            $obs .= $obsIbpt;
        }
        // $stdInfoAdic->infCpl = $obs;
        if($p->produto->renavam != ''){
            $veiCpl = ' | RENAVAM ' . $p->produto->renavam;
            if($p->produto->placa != '') $veiCpl .= ', PLACA ' . $p->produto->placa;
            if($p->produto->chassi != '') $veiCpl .= ', CHASSI ' . $p->produto->chassi;
            if($p->produto->combustivel != '') $veiCpl .= ', COMBUSTÍVEL ' . $p->produto->combustivel;
            if($p->produto->ano_modelo != '') $veiCpl .= ', ANO/MODELO ' . $p->produto->ano_modelo;
            if($p->produto->cor_veiculo != '') $veiCpl .= ', COR ' . $p->produto->cor_veiculo;

            $obs .= $veiCpl;
        }

        if($somaApCredito > 0){
            if($config->campo_obs_nfe != ""){
                $msg = $config->campo_obs_nfe;
                $msg = str_replace("%", number_format($tributacao->perc_ap_cred, 2, ",",  ".") . "%", $msg);
                $msg = str_replace('R$', 'R$ ' . number_format($somaApCredito, 2, ",",  "."), $msg);
                $obs .= $msg;
            }
        }elseif($config->campo_obs_nfe != ""){
            $obs .= " ".$config->campo_obs_nfe;
        }

        $stdInfoAdic->infCpl = $this->retiraAcentos($obs);

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

        try {
            $nfe->montaNFe();
            return [
                'chave' => $nfe->getChave(),
                'xml' => $nfe->getXML(),
                'nNf' => $stdIde->nNF
            ];
        } catch (\Exception $e) {
            return [
                'erros_xml' => $nfe->getErrors()
            ];
        }
    }

	private function validate_EAN13Barcode($ean)
	{

		$sumEvenIndexes = 0;
		$sumOddIndexes  = 0;

		$eanAsArray = array_map('intval', str_split($ean));

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

	private function retiraAcentos($texto){
		return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/", "/(ç)/"),explode(" ","a A e E i I o O u U n N c"),$texto);
	}

	public function format($number, $dec = 2){
		return number_format((float) $number, $dec, ".", "");
	}

	public function consultaCadastro($cnpj, $uf){
		try {

			$iest = '';
			$cpf = '';
			$response = $this->tools->sefazCadastro($uf, $cnpj, $iest, $cpf);

			$stdCl = new Standardize($response);

			$std = $stdCl->toStd();

			$arr = $stdCl->toArray();

			$json = $stdCl->toJson();

			return [
				'erro' => false,
				'json' => $json
			];

		} catch (\Exception $e) {
			return [
				'erro' => true,
				'json' => $e->getMessage()
			];

		}
	}

	public function consultaChave($chave){
		$response = $this->tools->sefazConsultaChave($chave);

		$stdCl = new Standardize($response);
		$arr = $stdCl->toArray();
		return $arr;
	}

	public function consultar($item){
		try {
			$this->tools->model('55');

			$chave = $item->chave;
			$response = $this->tools->sefazConsultaChave($chave);

			$stdCl = new Standardize($response);
			$arr = $stdCl->toArray();

			if($arr['xMotivo'] == 'Autorizado o uso da NF-e'){
				if($item->estado != 'aprovado'){

					$config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

					// $nRec = $arr['protNFe']['infProt']['nProt'];
					$chave = $arr['protNFe']['infProt']['chNFe'];
					$nRec = $item->recibo;
					$protocolo = $this->tools->sefazConsultaRecibo($nRec);
					sleep(3);
					$st = new Standardize();
					$std = $st->toStd($protocolo);
					// return $std;
					if($std->protNFe->infProt->cStat == 100){
						// $venda->chave = $chave;
						$item->estado = 'aprovado';
						$item->numero_nfe = $config->ultimo_numero_nfe+1;
						$item->save();

						$config->ultimo_numero_nfe = $config->ultimo_numero_nfe+1;
						$config->save();
						$xml = Complements::toAuthorize($item->signed_xml, $protocolo);
						safe_file_put_contents(public_path('xml_nfe/').$chave.'.xml',$xml);
						// return $xml;
					}
				}
			}
			return json_encode($arr);

		} catch (\Exception $e) {
			echo $e->getMessage();
		}
	}

	public function inutilizar($config, $nInicio, $nFinal, $justificativa){
		try{

			$nSerie = $config->numero_serie_nfe;
			$nIni = $nInicio;
			$nFin = $nFinal;
			$xJust = $justificativa;
			$response = $this->tools->sefazInutiliza($nSerie, $nIni, $nFin, $xJust);

			$stdCl = new Standardize($response);
			$std = $stdCl->toStd();
			$arr = $stdCl->toArray();
			$json = $stdCl->toJson();

			return $arr;

		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

    public function cancelar($item, $justificativa)
    {
        try {
            if (!isset($item->chave)) {
                return ['erro' => true, 'data' => 'Chave da NFe não informada.', 'status' => 422];
            }

            $chave = $item->chave;

            // Consulta para buscar nProt com tentativas
            $nProt = null;
            $tentativas = 10;

            while ($tentativas > 0) {
                $responseConsulta = $this->tools->sefazConsultaChave($chave);
                $stdClConsulta = new Standardize($responseConsulta);
                $arrConsulta = $stdClConsulta->toArray();
                $nProt = $arrConsulta['protNFe']['infProt']['nProt'] ?? null;

                if (!empty($nProt)) {
                    break;
                }

                $tentativas--;
                sleep(2); // Aguarda 2 segundos antes de tentar novamente
            }

            if (empty($nProt)) {
                return [
                    'erro' => true,
                    'data' => 'Não foi possível localizar o protocolo de autorização (nProt) para cancelamento após múltiplas tentativas.',
                    'status' => 422,
                ];
            }

            $responseCancelamento = $this->tools->sefazCancela($chave, $justificativa, $nProt);
            sleep(2);

            $stdCl = new Standardize($responseCancelamento);
            $std = $stdCl->toStd();
            $arr = $stdCl->toArray();

            if (!isset($std->retEvento->infEvento->cStat)) {
                return ['erro' => true, 'data' => 'Resposta inválida da SEFAZ.', 'status' => 422];
            }

            $cStat = $std->retEvento->infEvento->cStat;

            if (in_array((string)$cStat, ['101', '135', '155', '573'])) {
                $xml = Complements::toAuthorize($this->tools->lastRequest, $responseCancelamento);

                $pathCancelada = public_path('xml_nfe_cancelada/');
                if (!file_exists($pathCancelada)) {
                    mkdir($pathCancelada, 0777, true);
                }

                safe_file_put_contents($pathCancelada . $chave . '.xml', $xml);

                $item->estado = 'cancelado';
                $item->motivo_cancelamento = $justificativa;
                $item->data_cancelamento = now();
                $item->cancelamento_xml = $xml;
                $item->save();

                return [
                    'success' => true,
                    'data' => $arr['retEvento']['infEvento']['xMotivo'] ?? 'Cancelamento realizado com sucesso.',
                    'status' => 200,
                ];
            } else {
                return ['erro' => true, 'data' => $arr, 'status' => 422];
            }
        } catch (\Exception $e) {
            return ['erro' => true, 'data' => $e->getMessage(), 'status' => 500];
        }
    }

    public function cartaCorrecao($item, $correcao)
    {
        try {
            $chave = $item->chave;
            $xCorrecao = $correcao;
            $nSeqEvento = ($item->sequencia_cce ?? 0) + 1;

            $response = $this->tools->sefazCCe($chave, $xCorrecao, $nSeqEvento);
            sleep(2);

            $stdCl = new Standardize($response);
            $std = $stdCl->toStd();
            $arr = $stdCl->toArray();
            $json = $stdCl->toJson();

            if ($std->cStat != 128) {
                return [
                    'erro' => true,
                    'data' => $arr,
                    'status' => 402
                ];
            } else {
                $cStatEvento = (string) ($std->retEvento->infEvento->cStat ?? '');

                if (in_array($cStatEvento, ['135', '136'])) {
                    // Sucesso: carta de correção processada
                    $xml = Complements::toAuthorize($this->tools->lastRequest, $response);

                    // Diretório para salvar
                    $path = public_path('xml_nfe_correcao/');
                    if (!file_exists($path)) {
                        mkdir($path, 0777, true);
                    }

                    // Salvar XML da correção
                    safe_file_put_contents($path . $chave . '.xml', $xml);

                    // Atualizar número da sequência da correção
                    $item->carta_correcao_xml = $xml;
                    $item->carta_correcao_mensagem = $xCorrecao;
                    $item->data_carta_correcao = now();
                    $item->numero_carta_correcao = $nSeqEvento;
                    $item->sequencia_cce = $nSeqEvento;
                    $item->save();

                    return [
                        'success' => true,
                        'json' => $json,
                        'mensagem' => 'Carta de Correção enviada e salva com sucesso.',
                        'status' => 200
                    ];
                } else {
                    return [
                        'erro' => true,
                        'data' => $arr,
                        'status' => 402
                    ];
                }
            }
        } catch (\Exception $e) {
            return [
                'erro' => true,
                'data' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

	public function sign($xml){
		return $this->tools->signNFe($xml);
	}

    public function transmitir($signXml, $chave, $transferencia_id = null)
    {
        try {
            $idLote = str_pad(100, 15, '0', STR_PAD_LEFT);
            $indSinc = 1; // síncrono, uma NFe por vez
            $resp = $this->tools->sefazEnviaLote([$signXml], $idLote, $indSinc);
            //$resp = $this->tools->sefazEnviaLote([$signXml], $idLote);

            $st = new Standardize();
            $std = $st->toStd($resp);

            sleep(2);

            if ($std->cStat != 103) {
                return "Erro: [$std->cStat] - $std->xMotivo";
            }

            $recibo = $std->infRec->nRec;

            if ($transferencia_id != null) {
                $transferencia = Transferencia::find($transferencia_id);

                if ($transferencia != null) {
                    $transferencia->recibo = $recibo;
                    if ($transferencia->signed_xml == null) {
                        $transferencia->signed_xml = $signXml;
                    }
                    $transferencia->save();
                }
            }

            sleep(5);

            // Consulta o recibo
            $protocolo = $this->tools->sefazConsultaRecibo($recibo);

            $stdProtocolo = new Standardize();
            $std = $stdProtocolo->toStd($protocolo);

            if (!isset($std->protNFe) || !isset($std->protNFe->infProt)) {
                return "Erro: Retorno inválido da SEFAZ ao consultar recibo.";
            }

            $cStat = (int) $std->protNFe->infProt->cStat;

            if ($cStat == 100) {
                // Nota autorizada com sucesso
                if (isset($transferencia)) {
                    $transferencia->estado = 'aprovado';
                    $transferencia->data_emissao = date('Y-m-d H:i:s');

                    if (!$transferencia->chave) {
                        $transferencia->chave = $std->protNFe->infProt->chNFe ?? $chave;
                    }

                    // Atualiza número da NFe se ainda não foi salvo
                    if (empty($transferencia->numero_nfe)) {
                        $transferencia->numero_nfe = $this->buscarProximoNumeroNFe($transferencia);
                    }

                    $transferencia->save();
                }

                // Gera e salva o XML autorizado
                $xmlAutorizado = Complements::toAuthorize($signXml, $protocolo);
                safe_file_put_contents(public_path('xml_nfe/') . $chave . '.xml', $xmlAutorizado);

                return "Sucesso: NF-e transmitida e autorizada!";
            } else {
                // Nota rejeitada
                if (isset($transferencia)) {
                    $transferencia->estado = 'rejeitado';
                    $transferencia->save();
                }

                return "Rejeição: [$cStat] - {$std->protNFe->infProt->xMotivo}";
            }
        } catch (\Exception $e) {
            return "Erro: " . $e->getMessage();
        }
    }

    private function buscarProximoNumeroNFe($transferencia)
    {
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        return ($config->ultimo_numero_nfe ?? 0) + 1;
    }

    public function consultarSituacao($item)
    {
        try {
            $chave = $item->chave;

            $response = $this->tools->sefazConsultaChave($chave);
            $std = (new Standardize($response))->toStd();

            if (isset($std->protNFe->infProt->cStat)) {
                $cStat = (string) $std->protNFe->infProt->cStat;
                $xMotivo = (string) ($std->protNFe->infProt->xMotivo ?? 'Motivo não informado');

                $folder = 'xml_nfe_rejeitada'; // Padrão

                // 🔵 Correção lógica: priorizar cancelamento
                if (in_array($cStat, ['101', '151'])) {
                    $item->estado = 'cancelado';
                    $folder = 'xml_nfe_cancelada';
                }
                elseif ($cStat == '100') {
                    $item->estado = 'aprovado';
                    $folder = 'xml_nfe';
                }
                elseif (in_array($cStat, ['110', '301', '302'])) {
                    $item->estado = 'rejeitado'; // Denegada tratada como rejeição
                    $folder = 'xml_nfe_denegada';
                }
                else {
                    $item->estado = 'rejeitado'; // Outras rejeições
                    $folder = 'xml_nfe_rejeitada';
                }

                $item->save();

                // 🔵 Corrigir: Salvar XML de forma correta
                if (!empty($response)) {
                    $path = public_path($folder . '/');

                    if (!file_exists($path)) {
                        mkdir($path, 0777, true);
                    }

                    // 🔵 Salva corretamente o XML
                    $xmlFinal = Complements::toAuthorize(
                        $item->signed_xml ?? $response, // se não tiver signed_xml, salva o response puro
                        $response
                    );

                    safe_file_put_contents($path . $item->chave . '.xml', $xmlFinal);
                }

                return [
                    'success' => true,
                    'mensagem' => "Situação atual: {$xMotivo} (Código: {$cStat})",
                    'estado' => $item->estado,
                ];
            } else {
                return [
                    'success' => false,
                    'mensagem' => 'Resposta inválida da SEFAZ.'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'mensagem' => $e->getMessage()
            ];
        }
    }

}
