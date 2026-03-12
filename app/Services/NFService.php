<?php
namespace App\Services;

use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use App\Models\Venda;
use App\Models\Difal;
use App\Models\Contigencia;
use App\Models\RemessaNfe;
use App\Models\ConfigNota;
use App\Models\Certificado;
use NFePHP\NFe\Complements;
use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\Legacy\FilesFolders;
use NFePHP\Common\Soap\SoapCurl;
use App\Models\Tributacao;
use App\Models\NFeReferecia;
use App\Models\IBPT;
use App\Models\Filial;
use App\Models\FiscalEmissionLog;
use App\Services\Fiscal\EmissionLogger;
use App\Services\Fiscal\TransmissaoResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use NFePHP\NFe\Factories\Contingency;
use App\Services\ReformaTributariaService;

error_reporting(E_ALL);
ini_set('display_errors', 'On');

class NFService{

	private $config;
	private $tools;
	protected $empresa_id = null;

	private function normalizeIcmsCst(?string $cst, int $vendaId, int $itemId): string
	{
		$cst = trim((string)$cst);
		$validCst = ['00', '10', '20', '30', '40', '41', '50', '51', '60', '61', '70', '90'];

		if (in_array($cst, $validCst, true)) {
			return $cst;
		}

		$mapCsosnToCst = [
			'101' => '00',
			'102' => '00',
			'103' => '40',
			'201' => '10',
			'202' => '10',
			'203' => '30',
			'300' => '40',
			'400' => '41',
			'500' => '60',
			'900' => '90',
		];

		if (isset($mapCsosnToCst[$cst])) {
			Log::warning('NF-e: CSOSN informado em emissor de regime normal. Aplicado fallback para CST.', [
				'venda_id' => $vendaId,
				'item' => $itemId,
				'origem' => $cst,
				'cst_aplicado' => $mapCsosnToCst[$cst],
			]);

			return $mapCsosnToCst[$cst];
		}

		Log::warning('NF-e: CST/CSOSN inválido para regime normal. Aplicado CST 90 para evitar falha na geração do XML.', [
			'venda_id' => $vendaId,
			'item' => $itemId,
			'origem' => $cst,
		]);

		return '90';
	}

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

  private function tryAttachReformaItemTag($nfe, int $itemCont, $item): bool
	{
		$rt = app(ReformaTributariaService::class);
		if (!$rt->shouldApply((int)($this->empresa_id ?? 0))) {
			return false;
		}

		$base = (float)($item->bc_ibs_cbs ?? 0);
		$vIbs = (float)($item->valor_ibs ?? 0);
		$vCbs = (float)($item->valor_cbs ?? 0);
		$vIs  = (float)($item->is_valor ?? 0);
		if ($base <= 0 && $vIbs <= 0 && $vCbs <= 0 && $vIs <= 0) {
			return false;
		}

		$std = new \stdClass();
		$std->item = $itemCont;
		$std->CST = (string)($item->cst_ibs_cbs ?? '');
		$std->cClassTrib = (string)($item->class_trib_ibs_cbs ?? '');
		$std->vBC = $this->format($base);
		$std->pIBSUF = $this->format((float)($item->aliq_ibs_uf ?? 0), 4);
		$std->vIBSUF = $this->format((float)($item->valor_ibs_uf ?? 0));
		$std->pIBSMun = $this->format((float)($item->aliq_ibs_mun ?? 0), 4);
		$std->vIBSMun = $this->format((float)($item->valor_ibs_mun ?? 0));
		$std->vIBS = $this->format($vIbs);
		$std->pCBS = $this->format((float)($item->aliq_cbs ?? 0), 4);
		$std->vCBS = $this->format($vCbs);
		$std->vIS = $this->format($vIs);

		$methods = ['tagIBSCBS', 'tagImpostoIBSCBS', 'tagIBS'];
		foreach ($methods as $method) {
			if (!method_exists($nfe, $method)) {
				continue;
			}
			try {
				$nfe->{$method}($std);
				return true;
			} catch (\Throwable $e) {
				continue;
			}
		}

		return false;
	}

	private function appendReformaObservacao(string $obs, $venda): string
	{
		$rt = app(ReformaTributariaService::class);
		if (!$rt->shouldApply((int)($this->empresa_id ?? 0))) {
			return $obs;
		}

		$tBase = (float)($venda->total_bc_ibs_cbs ?? 0);
		$tIbs = (float)($venda->total_ibs ?? 0);
		$tCbs = (float)($venda->total_cbs ?? 0);
		$tIs = (float)($venda->total_is ?? 0);

		if ($tBase <= 0 && $tIbs <= 0 && $tCbs <= 0 && $tIs <= 0) {
			return $obs;
		}

		$obs .= " | RT IBS/CBS/IS: BC=" . number_format($tBase, 2, ',', '.')
			. " IBS=" . number_format($tIbs, 2, ',', '.')
			. " CBS=" . number_format($tCbs, 2, ',', '.')
			. " IS=" . number_format($tIs, 2, ',', '.');

		return $obs;
	}
	private function getContigencia(){
		$active = Contigencia::
		where('empresa_id', $this->empresa_id)
		->where('status', 1)
		->where('documento', 'NFe')
		->first();
		return $active;
	}

	public function gerarNFe($idVenda){
		$venda = Venda::
		where('id', $idVenda)
		->first();

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first(); // iniciando os dados do emitente NF

		if($venda->filial_id != null){
			$casas_decimais = $config->casas_decimais;
			$config = Filial::findOrFail($venda->filial_id);
			$config->casas_decimais = $casas_decimais;
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

		$vendaLast = Venda::lastNF($this->empresa_id);
		$nNfeRemessa = RemessaNfe::lastNFe($this->empresa_id);

		if($nNfeRemessa > $vendaLast){
			$vendaLast = $nNfeRemessa;
		}

		if($venda->filial_id != null){
			$vendaLast = $config->ultimo_numero_nfe;
		}
		$lastNumero = $vendaLast;

                $stdIde = new \stdClass();
                $stdIde->cUF = $config->cUF;
                $stdIde->cNF = rand(11111,99999);
                // $stdIde->natOp = $venda->natureza->natureza;
                $stdIde->natOp = $venda->natureza->natureza;

                // $stdIde->indPag = 1; //NÃO EXISTE MAIS NA VERSÃO 4.00 // forma de pagamento

                $stdIde->mod = 55;

                // trava a série correta e reserva o número antes de prosseguir
                $stdIde->nNF = $this->reservarNumeroNFe($venda, $config);
                $stdIde->serie = $venda->serie ?? $config->numero_serie_nfe;
                if($venda->data_retroativa){
                        $stdIde->dhEmi = $venda->data_retroativa.date("\TH:i:sP");
		}else{
			$stdIde->dhEmi = date("Y-m-d\TH:i:sP");
		}

		if($venda->data_saida){
			$stdIde->dhSaiEnt = $venda->data_saida.date("\TH:i:sP");
		}else{
			$stdIde->dhSaiEnt = date("Y-m-d\TH:i:sP");
		}
		// $stdIde->dhSaiEnt = date("Y-m-d\TH:i:sP");
		$stdIde->tpNF = 1;

		if($venda->cliente->cod_pais == 1058){
			$stdIde->idDest = $config->UF != $venda->cliente->cidade->uf ? 2 : 1;
		}else{
			$stdIde->idDest = 3;
		}

		$stdIde->cMunFG = $config->codMun;
		$stdIde->tpImp = isset($config->tipo_impressao_danfe) ? $config->tipo_impressao_danfe : 1;
		$stdIde->tpEmis = 1;
		$stdIde->cDV = 0;
		$stdIde->tpAmb = $config->ambiente;
		$stdIde->finNFe = $venda->natureza->finNFe;
		if($venda->pedido_nuvemshop_id > 0){
			$stdIde->indFinal = 1;
		}else{
			$stdIde->indFinal = $venda->cliente->consumidor_final;
		}
		$stdIde->indPres = 1;

		if($config->ambiente == 2){
			if($venda->pedido_ecommerce_id > 0){
				$stdIde->indIntermed = 1;
			}else{
				$stdIde->indIntermed = 0;
			}
		}
		$stdIde->procEmi = '0';
		$stdIde->verProc = '3.10.31';

		$tagide = $nfe->tagide($stdIde);

                $stdEmit = new \stdClass();
                $stdEmit->xNome = $config->razao_social;
                $stdEmit->xFant = $config->nome_fantasia;

                $ie = $this->resolveEmitenteIE($config);

                if ($ie === '') {
                        if ($this->emitentePermiteIsencao($tributacao)) {
                                $ie = 'ISENTO';
                        } else {
                                return [
                                        'erros_xml' => [
                                                'Inscrição estadual do emitente não configurada. Atualize o cadastro do emitente e tente novamente.'
                                        ]
                                ];
                        }
                }

                $stdEmit->IE = $ie;
		// $stdEmit->CRT = $tributacao->regime == 0 ? 1 : 3;
		$stdEmit->CRT = ($tributacao->regime == 0 || $tributacao->regime == 2) ? 1 : 3;

		$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

		if(strlen($cnpj) == 14){
			$stdEmit->CNPJ = $cnpj;
		}else{
			$stdEmit->CPF = $cnpj;
		}
		// $stdEmit->IM = $ie;

		$emit = $nfe->tagemit($stdEmit);

		// ENDERECO EMITENTE
		$stdEnderEmit = new \stdClass();
		$stdEnderEmit->xLgr = $this->retiraAcentos($config->logradouro);
		$stdEnderEmit->nro = $config->numero;
		$stdEnderEmit->xCpl = $this->retiraAcentos($config->complemento);

		$stdEnderEmit->xBairro = $this->retiraAcentos($config->bairro);
		$stdEnderEmit->cMun = $config->codMun;
		$stdEnderEmit->xMun = $this->retiraAcentos($config->municipio);
		$stdEnderEmit->UF = $config->UF;

		$telefone = $config->fone;
		if(substr($telefone, 0, 3) == '+55'){
			$telefone = substr($telefone, 3, strlen($telefone));
		}
		$telefone = preg_replace('/[^0-9]/', '', $telefone);

		$stdEnderEmit->fone = $telefone;

		$cep = preg_replace('/[^0-9]/', '', $config->cep);

		$stdEnderEmit->CEP = $cep;
		$stdEnderEmit->cPais = '1058';
		$stdEnderEmit->xPais = 'BRASIL';

		$enderEmit = $nfe->tagenderEmit($stdEnderEmit);

		// DESTINATARIO
		$stdDest = new \stdClass();
		$pFisica = false;
		$stdDest->xNome = $this->retiraAcentos($venda->cliente->razao_social);

		if($venda->cliente->cod_pais != 1058){
			$stdDest->indIEDest = "9";
			$stdDest->idEstrangeiro = $venda->cliente->id_estrangeiro;
		}else{
			if($venda->cliente->contribuinte){
				if($venda->cliente->ie_rg == 'ISENTO'){
					$stdDest->indIEDest = "2";
				}else{
					$stdDest->indIEDest = "1";
				}

			}else{
				$stdDest->indIEDest = "9";
			}

			$cnpj_cpf = preg_replace('/[^0-9]/', '', $venda->cliente->cpf_cnpj);

			if(strlen($cnpj_cpf) == 14){
				$stdDest->CNPJ = $cnpj_cpf;
				$ie = preg_replace('/[^0-9]/', '', $venda->cliente->ie_rg);
				$stdDest->IE = $ie;
			}
			else{
			// $stdDest->CPF = $cnpj_cpf;
				$stdDest->CPF = $cnpj_cpf;
				$ie = preg_replace('/[^0-9]/', '', $venda->cliente->ie_rg);

				if(strtolower($ie) != "isento" && $venda->cliente->contribuinte)
					$stdDest->IE = $ie;
				$pFisica = true;

			}
		}

		$dest = $nfe->tagdest($stdDest);

		$stdEnderDest = new \stdClass();
		$stdEnderDest->xLgr = $this->retiraAcentos($venda->cliente->rua);
		$stdEnderDest->nro = $this->retiraAcentos($venda->cliente->numero);
		$stdEnderDest->xCpl = $this->retiraAcentos($venda->cliente->complemento);
		$stdEnderDest->xBairro = $this->retiraAcentos($venda->cliente->bairro);

		$telefone = $venda->cliente->telefone;
		$telefone = preg_replace('/[^0-9]/', '', $telefone);

		if(substr($telefone, 0, 3) == '+55'){
			$telefone = substr($telefone, 3, strlen($telefone));
		}
		$stdEnderDest->fone = $telefone;

		if($venda->cliente->cod_pais == 1058){

			$stdEnderDest->cMun = $venda->cliente->cidade->codigo;
			$stdEnderDest->xMun = strtoupper($this->retiraAcentos($venda->cliente->cidade->nome));
			$stdEnderDest->UF = $venda->cliente->cidade->uf;

			$cep = preg_replace('/[^0-9]/', '', $venda->cliente->cep);

			$stdEnderDest->CEP = $cep;
			$stdEnderDest->cPais = "1058";
			$stdEnderDest->xPais = "BRASIL";
		}else{
			$stdEnderDest->cMun = 9999999;
			$stdEnderDest->xMun = "EXTERIOR";
			$stdEnderDest->UF = "EX";
			$stdEnderDest->cPais = $venda->cliente->cod_pais;
			$stdEnderDest->xPais = $venda->cliente->getPais();
		}

		$enderDest = $nfe->tagenderDest($stdEnderDest);

		if($venda->cliente->rua_entrega != ""){
			$stdEnderDestEntrega = new \stdClass();

			$stdEnderDestEntrega->xLgr = $this->retiraAcentos($venda->cliente->rua_entrega);
			$stdEnderDestEntrega->nro = $this->retiraAcentos($venda->cliente->numero_entrega);
			$stdEnderDestEntrega->xBairro = $this->retiraAcentos($venda->cliente->bairro_entrega);

			$stdEnderDestEntrega->cMun = $venda->cliente->cidadeEntrega->codigo;
			$stdEnderDestEntrega->xMun = strtoupper($this->retiraAcentos($venda->cliente->cidadeEntrega->nome));
			$stdEnderDestEntrega->UF = $venda->cliente->cidade->uf;

			$cep = preg_replace('/[^0-9]/', '', $venda->cliente->cep_entrega);

			$stdEnderDestEntrega->CEP = $cep;
			$stdEnderDestEntrega->cPais = "1058";
			$stdEnderDestEntrega->xPais = "BRASIL";

			$cnpj_cpf = preg_replace('/[^0-9]/', '', $venda->cliente->cpf_cnpj_entrega);
			$stdEnderDestEntrega->xNome = $venda->cliente->nome_entrega;

			if(strlen($cnpj_cpf) == 14){
				$stdEnderDestEntrega->CNPJ = $cnpj_cpf;
			}
			else{
				$stdEnderDestEntrega->CPF = $cnpj_cpf;
			}

			$enderDestEntrega = $nfe->tagentrega($stdEnderDestEntrega);

		}

                $somaProdutos = 0;
                $somaICMS = 0;
                $icmsBases = [];
                $icmsItens = [];
                $somaIPI = 0;
                $somaPIS = 0;
                $somaCOFINS = 0;
		//PRODUTOS
		$itemCont = 0;

		$totalItens = count($venda->itens);
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
		$anp = false;

		$nfesRef = "";
		foreach($venda->referencias as $r){
			$std = new \stdClass();
			$std->refNFe = $r->chave;
			$nfe->tagrefNFe($std);

			$nfesRef .= " $r->chave ";
		}

		$somaApCredito = 0;
		$somaVICMSST = 0;

		$obsIbpt = "";
		foreach($venda->itens as $i){

			$p = $i;
			$ncm = $i->produto->NCM;
			$ncm = str_replace(".", "", $ncm);

			$ibpt = IBPT::getIBPT($config->UF, $ncm);

                        $itemCont++;

                        $stdProd = new \stdClass();
                        $stdProd->item = $itemCont;
                        $stdProd->vFrete = 0.00;
                        $stdProd->vOutro = 0.00;
                        $stdProd->vDesc = 0.00;

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

			if($i->produto->lote){
				$nomeProduto .= " | LOTE: ". $i->produto->lote;
			}
			if($i->produto->vencimento){
				$nomeProduto .= ", VENCIMENTO: ". $i->produto->vencimento;
			}
			$stdProd->xProd = $this->retiraAcentos($nomeProduto);

			// if($i->produto->CST_CSOSN == '500' || $i->produto->CST_CSOSN == '60'){
			// 	$stdProd->cBenef = 'SEM CBENEF';
			// }

			if($i->produto->cBenef){
				$stdProd->cBenef = $i->produto->cBenef;
			}

			$i->produto->perc_iss = 0;
			if($i->produto->perc_iss > 0){
				$stdProd->NCM = '00';
			}else{
				$stdProd->NCM = $ncm;
			}

			if($venda->natureza->sobrescreve_cfop == 0){
				$stdProd->CFOP = $config->UF != $venda->cliente->cidade->uf ?
				$i->produto->CFOP_saida_inter_estadual : $i->produto->CFOP_saida_estadual;
			}else{
				$stdProd->CFOP = $config->UF != $venda->cliente->cidade->uf ?
				$venda->natureza->CFOP_saida_inter_estadual : $venda->natureza->CFOP_saida_estadual;
			}
			$stdProd->uCom = $i->produto->unidade_venda;
			$stdProd->qCom = $i->quantidade;
			$stdProd->vUnCom = $this->format($i->valor, $config->casas_decimais);

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

			$stdProd->vProd = $this->format(($i->quantidade * $i->valor), $config->casas_decimais);

			$stdProd->vUnTrib = $this->format($i->valor, $config->casas_decimais);
			if($i->produto->quantidade_tributavel > 0){
				$stdProd->vUnTrib = $stdProd->vProd/$stdProd->qTrib;
			}
			$stdProd->indTot = $i->produto->perc_iss > 0 ? 0 : 1;
			$somaProdutos += $stdProd->vProd;

			$vDesc = 0;
			if($venda->desconto > 0.01 && $somaDesconto < $venda->desconto){

				if($itemCont < sizeof($venda->itens)){
					$totalVenda = $venda->valor_total;

					$media = (((($stdProd->vProd - $totalVenda)/$totalVenda))*100);
					$media = 100 - ($media * -1);

					$tempDesc = ($venda->desconto*$media)/100;
					$tempDesc -= 0.01;
					if($tempDesc > 0.01){
						$somaDesconto += $this->format($tempDesc);
						$stdProd->vDesc = $this->format($tempDesc);
					}else{
						if(sizeof($venda->itens) > 1){
							$somaDesconto += 0.01;
							$stdProd->vDesc = $this->format(0.01);
						}else{
							$somaDesconto = $venda->desconto;
							$stdProd->vDesc = $this->format($somaDesconto);
						}
					}

				}else{
					if(($venda->desconto - $somaDesconto) > 0.01){
						$stdProd->vDesc = $this->format($venda->desconto - $somaDesconto, $config->casas_decimais);
					}
				}

			}
			if($venda->acrescimo > 0.01 && $somaAcrescimo < $venda->acrescimo){

				if($itemCont < sizeof($venda->itens)){
					$totalVenda = $venda->valor_total;

					$media = (((($stdProd->vProd - $totalVenda)/$totalVenda))*100);
					$media = 100 - ($media * -1);

					$tempDesc = ($venda->acrescimo*$media)/100;
					$tempDesc -= 0.01;
					if($tempDesc > 0.01){
						$somaAcrescimo += $this->format($tempDesc);
						$stdProd->vOutro = $this->format($tempDesc);
					}else{
						if(sizeof($venda->itens) > 1){
							$somaAcrescimo += 0.01;
							$stdProd->vOutro = $this->format(0.01);
						}else{
							$somaAcrescimo = $venda->acrescimo;
							$stdProd->vOutro = $this->format($somaAcrescimo);
						}
					}

				}else{
					if(($venda->acrescimo - $somaAcrescimo) > 0.01){
						$stdProd->vOutro = $this->format($venda->acrescimo - $somaAcrescimo, $config->casas_decimais);
					}
				}
			}

			// if($venda->frete){
			// 	if($venda->frete->valor > 0){
			// 		$somaFrete += $vFt = $venda->frete->valor/$totalItens;
			// 		$stdProd->vFrete = $this->format($vFt);
			// 	}
			// }
			if($venda->frete){
				if($venda->frete->valor > 0){
					if($itemCont < sizeof($venda->itens)){
						$somaFrete += $vFt =
						$this->format($venda->frete->valor/$totalItens, 2);
						$stdProd->vFrete = $this->format($vFt);
					}else{
						$stdProd->vFrete = $this->format(($venda->frete->valor-$somaFrete), 2);
					}
				}
			}

			if($i->x_pedido != ""){
				$stdProd->xPed = substr($i->x_pedido, 0, 15);
			}
			if($i->num_item_pedido != ""){
				$stdProd->nItemPed = substr($i->num_item_pedido, 0, 15);
			}
                        // Valores numéricos preservados para cálculos, mas só enviamos ao XML se forem positivos
                        $valorFreteItem = (float) ($stdProd->vFrete ?? 0);
                        $valorOutroItem = (float) ($stdProd->vOutro ?? 0);
                        $valorDescItem  = (float) ($stdProd->vDesc  ?? 0);

                        $stdProdTag = clone $stdProd;
                        $stdProdTag->vFrete = $valorFreteItem > 0 ? $this->format($valorFreteItem) : null;
                        $stdProdTag->vOutro = $valorOutroItem > 0 ? $this->format($valorOutroItem) : null;
                        $stdProdTag->vDesc  = $valorDescItem  > 0 ? $this->format($valorDescItem)  : null;

                        $prod = $nfe->tagprod($stdProdTag);

			if(strlen(trim($i->produto->info_adicional_item)) > 1){
				$std = new \stdClass();
				$std->item = $itemCont;
				$std->infAdProd = $i->produto->info_adicional_item;
				$nfe->taginfAdProd($std);
			}

		//TAG IMPOSTO

			$stdImposto = new \stdClass();
			$stdImposto->item = $itemCont;
			if($i->produto->perc_iss > 0){
				$stdImposto->vTotTrib = 0.00;
			}

			// if($ibpt != null){
			// 	// $vProd = $stdProd->vProd;
			// 	// $somaFederal = ($vProd*($ibpt->nacional_federal/100));
			// 	// $somaEstadual += ($vProd*($ibpt->estadual/100));
			// 	// $somaMunicipal += ($vProd*($ibpt->municipal/100));
			// 	// $soma = $somaFederal + $somaEstadual + $somaMunicipal;
			// 	// $stdImposto->vTotTrib = $soma;

			// 	$vProd = $stdProd->vProd;

			// 	$federal = ($vProd*($ibpt->nacional_federal/100));
			// 	$somaFederal += $federal;

			// 	$estadual = ($vProd*($ibpt->estadual/100));
			// 	$somaEstadual += $estadual;

			// 	$municipal = ($vProd*($ibpt->municipal/100));
			// 	$somaMunicipal += $municipal;
			// 	$soma = $federal + $estadual + $municipal;

			// 	$stdImposto->vTotTrib = $soma;
			// }
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
			$this->tryAttachReformaItemTag($nfe, (int)$itemCont, $i);

			if($venda->natureza->CST_CSOSN){
				$i->produto->CST_CSOSN = $venda->natureza->CST_CSOSN;
			}

			// ICMS
			if($i->produto->perc_iss == 0){
				// regime normal
				if($tributacao->regime == 1){
				//$venda->produto->CST  CST

					$percentualUf = $i->percentualUf($venda->cliente->cidade->uf);

					$stdICMS = new \stdClass();
					if($stdIde->idDest == 2 && $stdIde->indFinal == 1 && $tributacao->regime == 1){
						$difal = Difal::where('cfop', $stdProd->CFOP)
						->where('empresa_id', $this->empresa_id)
						->where('uf', $stdEnderDest->UF)->first();

						if($difal){
							$i->produto->perc_icms = $difal->pICMSInter;
						}
					}
					if($percentualUf == null){
						$stdICMS->pICMS = $this->format($i->produto->perc_icms);
					}else{
						//aqui se tem percentual do estado do cliente
						$stdICMS->pICMS = $this->format($percentualUf->percentual_icms);
						if($percentualUf->percentual_red_bc > 0){
							$i->produto->pRedBC = $percentualUf->percentual_red_bc;
						}
					}

					$stdICMS->item = $itemCont;
					$stdICMS->orig = $i->produto->origem;
					if($i->produto->CST_CSOSN == '10'){
						$stdICMS->modBCST = $i->produto->modBCST;
						$stdICMS->vBCST = $stdProd->vProd;
						$stdICMS->pICMSST = $this->format($i->produto->pICMSST);
						$somaVICMSST += $stdICMS->vICMSST = $stdICMS->vBCST * ($stdICMS->pICMSST/100);
					}

					if($venda->cliente->consumidor_final){
						if($venda->cliente->cod_pais == 1058){
							if($config->sobrescrita_csonn_consumidor_final != ""){
								$stdICMS->CST = $config->sobrescrita_csonn_consumidor_final;
							}else{
								$stdICMS->CST = $i->produto->CST_CSOSN;
							}
						}else{
							$stdICMS->CST = $i->produto->CST_CSOSN_EXP;
						}
					}else{
						if($venda->cliente->cod_pais == 1058){
							$stdICMS->CST = $i->produto->CST_CSOSN;
						}else{
							$stdICMS->CST = $i->produto->CST_CSOSN_EXP;
						}
					}

					$stdICMS->CST = $this->normalizeIcmsCst(
						$stdICMS->CST ?? null,
						(int)($venda->id ?? 0),
						$itemCont
					);
					// $stdICMS->modBC = 0;
					$stdICMS->modBC = $i->produto->modBC;
                                        $baseIcms = (float) $this->format(
                                                ($stdProd->vProd ?? 0) + ($stdProd->vFrete ?? 0) + ($stdProd->vOutro ?? 0) - ($stdProd->vDesc ?? 0)
                                        );

                                        $stdICMS->vBC = $this->format($baseIcms);
                                        $valorIcmsItem = (float) $this->format($baseIcms * ((float) $stdICMS->pICMS/100));
                                        $stdICMS->vICMS = $this->format($valorIcmsItem);

                                        $pRedBC = (float) ($i->produto->pRedBC ?? 0);

                                        if($pRedBC == 0){
						if($i->produto->CST_CSOSN == '500'){
							$stdICMS->pRedBCEfet = 0.00;
							$stdICMS->vBCEfet = 0.00;
							$stdICMS->pICMSEfet = 0.00;
							$stdICMS->vICMSEfet = 0.00;
						}else if($i->produto->CST_CSOSN == '60'){
							$stdICMS->vBCSTRet = 0.00;
							$stdICMS->vICMSSTRet = 0.00;
							$stdICMS->vBCSTDest = 0.00;
							$stdICMS->vICMSSTDest = 0.00;

						}else if($i->produto->CST_CSOSN == '40' || $i->produto->CST_CSOSN == '41' || $i->produto->CST_CSOSN == '51'){
                                                        $stdICMS->vICMS = 0;
                                                        $stdICMS->vBC = 0;
						}else{
							if($i->produto->CST_CSOSN != '61'){

                                                               if($stdICMS->pICMS > 0){
                                                                       $VBC = round($VBC + (float) $stdICMS->vBC, 2);
                                                                       $somaICMS = round($somaICMS + (float) $stdICMS->vICMS, 2);
								}else{
									$stdICMS->vBC = 0;
								}
							}
						}
                                        }else{

                                                $tempB = 100-$pRedBC;
						// $tempB = $i->produto->pRedBC;

						$v = $stdProd->vProd * ($tempB/100);
						$v += $stdProd->vFrete;
						if($i->produto->CST_CSOSN != '61'){
							// echo $this->format($v);
							// die;
                                                $stdICMS->vBC = $this->format($v);
                                                $VBC = round($VBC + (float) $stdICMS->vBC, 2);
                                                $stdICMS->pICMS = $this->format($i->produto->perc_icms);
                                                $valorIcmsReduzido = (float) $this->format(((float) $stdProd->vProd * ($tempB/100)) * ((float) $stdICMS->pICMS/100));
                                                $stdICMS->vICMS = $this->format($valorIcmsReduzido);
                                                $somaICMS = round($somaICMS + (float) $valorIcmsReduzido, 2);
                                                        $stdICMS->pRedBC = $this->format($pRedBC);
						}

					}

					if($i->produto->CST_CSOSN == '61'){
						$stdICMS->qBCMonoRet = $this->format($stdProd->qTrib);
						$stdICMS->adRemICMSRet = $this->format($i->produto->adRemICMSRet, 4);
						$stdICMS->vICMSMonoRet = $this->format($i->produto->adRemICMSRet*$stdProd->qTrib, 4);
					}
                                        if($i->produto->CST_CSOSN == '60'){
                                                $ICMS = $nfe->tagICMSST($stdICMS);
                                        }else{
                                                $ICMS = $nfe->tagICMS($stdICMS);
                                        }

                                        $stdICMS->vBC = $stdICMS->vBC ?? 0;
                                        $icmsBases[] = (float) $stdICMS->vBC;
                                        $icmsItens[] = (float) $stdICMS->vICMS;
                                        // regime simples
				}else{
				//$venda->produto->CST CSOSN
					$stdICMS = new \stdClass();

					$stdICMS->item = $itemCont;
					$stdICMS->orig = $i->produto->origem;
					// $stdICMS->CSOSN = $i->produto->CST_CSOSN;
					if($venda->cliente->consumidor_final){
						if($venda->cliente->cod_pais == 1058){
							if($config->sobrescrita_csonn_consumidor_final != ""){
								$stdICMS->CSOSN = $config->sobrescrita_csonn_consumidor_final;
							}else{
								$stdICMS->CSOSN = $i->produto->CST_CSOSN;
							}
						}else{
							$stdICMS->CSOSN = $i->produto->CST_CSOSN_EXP;
						}
					}else{
						if($venda->cliente->cod_pais == 1058){
							$stdICMS->CSOSN = $i->produto->CST_CSOSN;
						}else{
							$stdICMS->CSOSN = $i->produto->CST_CSOSN_EXP;
						}
					}

					if($i->produto->CST_CSOSN == '500'){
						$stdICMS->vBCSTRet = 0.00;
						$stdICMS->pST = 0.00;
						$stdICMS->vICMSSTRet = 0.00;
					}

					$stdICMS->modBC = $i->produto->modBC;

					if($i->produto->perc_icms > 0){
						$stdICMS->vBC = $stdProd->vProd + $stdProd->vFrete + $stdProd->vOutro - $stdProd->vDesc;
					}else{
						$stdICMS->vBC = 0;
					}

					if($i->produto->CST_CSOSN == '201'){
						if($i->produto->perc_mva > 0){
							$stdICMS->pMVAST= $this->format($i->produto->perc_mva);
						}
						$stdICMS->modBCST = $i->produto->modBCST;
						$stdICMS->vBCST = $stdICMS->vBC * ($i->produto->perc_mva/100);
						$stdICMS->pICMSST = $this->format($i->produto->pICMSST);
						$somaVICMSST += $stdICMS->vICMSST = $stdICMS->vBCST * ($stdICMS->pICMSST/100);
					}

                                       $stdICMS->pICMS = $this->format($i->produto->perc_icms);
                                       $valorIcmsSn = (float) $this->format(((float) $stdICMS->vBC) * ((float) $stdICMS->pICMS/100));
                                       $stdICMS->vICMS = $this->format($valorIcmsSn);

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

                                               if($i->produto->perc_icms > 0 && $stdICMS->CSOSN == 900){
                                               $VBC = round($VBC + (float) (($stdProd->vProd ?? 0) + ($stdProd->vFrete ?? 0)), 2);
                                               $somaICMS = round($somaICMS + (float) $stdICMS->vICMS, 2);

                                        }

                                        $stdICMS->vBC = $stdICMS->vBC ?? 0;
                                        $stdICMS->vICMS = $stdICMS->vICMS ?? 0;
                                        $icmsBases[] = (float) $stdICMS->vBC;
                                        $icmsItens[] = (float) $stdICMS->vICMS;

                                        // $VBC = 0;
                                        // $somaICMS = 0;
                                }
			} else {

				$valorIss = ($i->valor * $i->quantidade * $i->quantidade_dimensao) - $vDesc;
				$somaServico += $valorIss;
				$valorIss = $valorIss * ($i->produto->perc_iss/100);
				$somaISS += $valorIss;

				$std = new \stdClass();
				$std->item = $itemCont;
				$std->vBC = $stdProd->vProd;
				$std->vAliq = $i->produto->perc_iss;
				$std->vISSQN = $this->format($valorIss);
				$std->cMunFG = $config->codMun;
				$std->cListServ = $i->produto->cListServ;
				$std->indISS = 1;
				$std->indIncentivo = 1;

				$nfe->tagISSQN($std);
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
                        $somaPIS += (float) ($stdPIS->vPIS ?? 0);

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
                        $somaCOFINS += (float) ($stdCOFINS->vCOFINS ?? 0);

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



			//TAG ANP

			// if(strlen($i->produto->descricao_anp) > 5){
			// 	$stdComb = new \stdClass();
			// 	$stdComb->item = $itemCont;
			// 	$stdComb->cProdANP = $i->produto->codigo_anp;
			// 	$stdComb->descANP = $i->produto->descricao_anp;
			// 	$stdComb->UFCons = $venda->cliente->cidade->uf;

			// 	$nfe->tagcomb($stdComb);
			// }


			if(strlen($i->produto->codigo_anp) > 2){
				$anp = true;
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

			$cest = $i->produto->CEST;
			$cest = str_replace(".", "", $cest);
			$stdProd->CEST = $cest;
			if(strlen($cest) > 0){
				$std = new \stdClass();
				$std->item = $itemCont;
				$std->CEST = $cest;
				$nfe->tagCEST($std);
			}

			if($stdIde->idDest == 2 && $stdIde->indFinal == 1 && $tributacao->regime == 1){
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
					// $std->vICMSUFDest = $this->format($vICMSUFDestAux-$vICMSUFDest);
					// $std->vICMSUFDest = $this->format($stdICMS->vBC * ($i->produto->perc_icms_interestadual/100));
					$std->vICMSUFRemet = $this->format($vICMSUFDestAux-$vICMSUFDest) - $std->vICMSUFDest;

					$nfe->tagICMSUFDest($std);
				}
			}

		}

                $VBC = array_sum($icmsBases);
                $somaICMS = array_sum($icmsItens);

                $stdICMSTot = new \stdClass();
                $stdICMSTot->vProd = $this->format($somaProdutos, $config->casas_decimais);
                $stdICMSTot->vBC = $this->format($VBC);
                $stdICMSTot->vICMS = $this->format($somaICMS);

		$stdICMSTot->vICMSDeson = 0.00;
		$stdICMSTot->vBCST = 0.00;
		$stdICMSTot->vST = 0.00;

		if($venda->frete) $stdICMSTot->vFrete = $this->format($venda->frete->valor);
		else $stdICMSTot->vFrete = 0.00;

                $stdICMSTot->vSeg = 0.00;
                $stdICMSTot->vDesc = $this->format($venda->desconto);
                $stdICMSTot->vII = 0.00;
                $stdICMSTot->vIPI = $this->format($somaIPI);
                $stdICMSTot->vPIS = $this->format($somaPIS);
                $stdICMSTot->vCOFINS = $this->format($somaCOFINS);
		$stdICMSTot->vOutro = $this->format($venda->acrescimo);

		if($anp){
		}

		$stdICMSTot->vNF = $this->format($somaProdutos+$stdICMSTot->vFrete+$somaIPI-$venda->desconto+$venda->acrescimo+$somaVICMSST);

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
		$stdTransp->modFrete = $venda->frete->tipo ?? '9';

		$transp = $nfe->tagtransp($stdTransp);

		if($venda->transportadora){
			$std = new \stdClass();
			$std->xNome = $venda->transportadora->razao_social;

			$std->xEnder = $venda->transportadora->logradouro;
			$std->xMun = $this->retiraAcentos($venda->transportadora->cidade->nome);
			$std->UF = $venda->transportadora->cidade->uf;

			$cnpj_cpf = preg_replace('/[^0-9]/', '', $venda->transportadora->cnpj_cpf);

			if(strlen($cnpj_cpf) == 14) $std->CNPJ = $cnpj_cpf;
			else $std->CPF = $cnpj_cpf;

			$nfe->tagtransporta($std);
		}

		if($venda->frete != null){

			$std = new \stdClass();


			$placa = str_replace("-", "", $venda->frete->placa);
			$std->placa = strtoupper($placa);
			$std->UF = $venda->frete->uf;

			// if($config->UF == $venda->cliente->cidade->uf){
			if($venda->frete->placa != "" && $venda->frete->uf){
				$nfe->tagveicTransp($std);
			}

			if($venda->frete->qtdVolumes > 0 || $venda->frete->peso_liquido > 0
				|| $venda->frete->peso_bruto > 0){
				$stdVol = new \stdClass();
				$stdVol->item = 1;
				$stdVol->qVol = $venda->frete->qtdVolumes;
				$stdVol->esp = $venda->frete->especie;

				$stdVol->nVol = $venda->frete->numeracaoVolumes;
				$stdVol->pesoL = $venda->frete->peso_liquido;
				$stdVol->pesoB = $venda->frete->peso_bruto;
				$vol = $nfe->tagvol($stdVol);
			}
		}

		if($venda->cliente->cod_pais != 1058){
			$std = new \stdClass();
			$std->UFSaidaPais = $config->UF;
			$std->xLocExporta = $config->municipio;
			// $std->xLocDespacho = 'Informação do Recinto Alfandegado';

			$nfe->tagexporta($std);
		}

	//Fatura
		if($somaISS == 0 && $venda->natureza->CFOP_saida_estadual != '5915' && $venda->natureza->CFOP_saida_inter_estadual != '6915'){
			$stdFat = new \stdClass();
			$stdFat->nFat = (int)$lastNumero+1;
			$stdFat->vOrig = $this->format($somaProdutos+$venda->acrescimo+$stdICMSTot->vFrete);
			$stdFat->vDesc = $this->format($venda->desconto);
			// $stdFat->vOutro = $this->format($venda->acrescimo);
			// $stdFat->vLiq = $this->format($somaProdutos-$venda->desconto+$venda->acrescimo);
			$stdFat->vLiq = $this->format($somaProdutos-$venda->desconto+$venda->acrescimo+$stdICMSTot->vFrete);
			// $stdFat->vLiq = $this->format($somaProdutos-$venda->desconto);
			if($venda->tipo_pagamento != '90'){
				$fatura = $nfe->tagfat($stdFat);
			}
		}

                $formaPagamento = strtolower($venda->forma_pagamento ?? '');
                $pagamentoAVista = in_array($formaPagamento, ['a_vista', 'avista', 'à vista', 'a vista'])
                        || in_array($venda->tipo_pagamento, ['01', '1']);

                //Duplicata
                if($venda->tipo_pagamento != '90'){
                        if($somaISS == 0 && $venda->natureza->CFOP_saida_estadual != '5915' && $venda->natureza->CFOP_saida_inter_estadual != '6915'){
                                if(count($venda->duplicatas) > 0){
                                        $contFatura = 1;
                                        foreach($venda->duplicatas as $ft){
                                                $stdDup = new \stdClass();
                                                if($contFatura < 10){
                                                        $stdDup->nDup = "00".$contFatura;
                                                }else{
                                                        $stdDup->nDup = "0".$contFatura;
                                                }
                                                $stdDup->dVenc = substr($ft->data_vencimento, 0, 10);
                                                $stdDup->vDup = $this->format($ft->valor_integral);

                                                $nfe->tagdup($stdDup);
                                                $contFatura++;
                                        }
                                }else{

                                        if(!$pagamentoAVista){
                                                $stdDup = new \stdClass();
                                                $stdDup->nDup = '001';
                                                $stdDup->dVenc = Date('Y-m-d');
                                                $stdDup->vDup = $this->format($venda->valor_total-$venda->desconto+$venda->acrescimo, 4);

                                                $nfe->tagdup($stdDup);
                                        }
                                }
                        }
                }

		$stdPag = new \stdClass();
		$pag = $nfe->tagpag($stdPag);

		if(sizeof($venda->duplicatas) > 0){
			foreach($venda->duplicatas as $d){

				$tipo_pagamento = Venda::getTipoPagamentoNFe($d->tipo_pagamento);

				$stdDetPag = new \stdClass();

				$stdDetPag->tPag = $tipo_pagamento;

				if($tipo_pagamento == '06'){
					$stdDetPag->tPag = '05';
				}

				$stdDetPag->vPag = $tipo_pagamento != '90' ? $this->format($d->valor_integral, $config->casas_decimais) :
				0.00;
				if($tipo_pagamento == '03' || $tipo_pagamento == '04' || $tipo_pagamento == '17'){

					if($venda->cAut_cartao != ""){
						$stdDetPag->cAut = $venda->cAut_cartao;
					}
					if($venda->cnpj_cartao != ""){
						$cnpj = preg_replace('/[^0-9]/', '', $venda->cnpj_cartao);

						$stdDetPag->CNPJ = $cnpj;
					}
					$stdDetPag->tBand = $venda->bandeira_cartao;

					$stdDetPag->tpIntegra = 2;
				}

                                $stdDetPag->indPag = $pagamentoAVista ? 0 : 1;

				$detPag = $nfe->tagdetPag($stdDetPag);
			}
		}else{

			$stdDetPag = new \stdClass();

			$stdDetPag->tPag = $venda->tipo_pagamento;
			$stdDetPag->vPag = $venda->tipo_pagamento != '90' ? $this->format($venda->valor_total -
				$venda->desconto + $venda->acrescimo, $config->casas_decimais) : 0.00;

			if($venda->descricao_pag_outros != "" && $venda->tipo_pagamento == '90'){
				$stdDetPag->xPag = $venda->descricao_pag_outros;
			}

			if($venda->tipo_pagamento == '03' || $venda->tipo_pagamento == '04' || $venda->tipo_pagamento == '17'){
				if($venda->cAut_cartao != ""){
					$stdDetPag->cAut = $venda->cAut_cartao;
				}
				if($venda->cnpj_cartao != ""){
					$cnpj = preg_replace('/[^0-9]/', '', $venda->cnpj_cartao);

					$stdDetPag->CNPJ = $cnpj;
				}
				$stdDetPag->tBand = $venda->bandeira_cartao;

				$stdDetPag->tpIntegra = 2;
			}

                        $stdDetPag->indPag = $pagamentoAVista ? 0 : 1;

			$detPag = $nfe->tagdetPag($stdDetPag);
		}


		if($config->ambiente == 2){
			if($venda->pedido_ecommerce_id > 0){
				$stdPag = new \stdClass();
				$stdPag->CNPJ = env("RESP_CNPJ");
				$stdPag->idCadIntTran = env("RESP_NOME");
				$detInf = $nfe->tagIntermed($stdPag);
			}
		}

		$stdInfoAdic = new \stdClass();

		$obs = " " . $venda->observacao;

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

		if($venda->vendedor_setado && $venda->vendedor_setado->funcionario){
			$obs .= " | vendedor " . $venda->vendedor_setado->funcionario->nome . " ";
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

		if($venda->getFormaPagamento($venda->empresa_id) != null){
			$obs .= "Inf. adicional de pagamento: " . $venda->getFormaPagamento($venda->empresa_id)->infos;
		}

		$obs = $this->appendReformaObservacao($obs, $venda);
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

	private function validate_EAN13Barcode($ean)
	{

		$sumEvenIndexes = 0;
		$sumOddIndexes  = 0;

		$eanAsArray = array_map('intval', str_split($ean));

		if(strlen($ean) == 14){
			return true;
		}

		if (!$this->has13Numbers($eanAsArray) ) {
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
		return count($ean) === 13 || count($ean) === 14;
	}

        private function resolveEmitenteIE($config): string
        {
                $candidates = [];

                if (isset($config->ie)) {
                        $candidates[] = $config->ie;
                }

                if ($config instanceof Filial && isset($config->empresa_id)) {
                        $matriz = ConfigNota::where('empresa_id', $config->empresa_id)->first();
                        if ($matriz && isset($matriz->ie)) {
                                $candidates[] = $matriz->ie;
                        }
                }

                foreach ($candidates as $value) {
                        $value = trim((string)$value);

                        if ($value === '') {
                                continue;
                        }

                        if (strcasecmp($value, 'ISENTO') === 0) {
                                return 'ISENTO';
                        }

                        $digits = preg_replace('/[^0-9]/', '', $value);
                        if ($digits !== '') {
                                return $digits;
                        }
                }

                return '';
        }

        private function emitentePermiteIsencao($tributacao): bool
        {
                if (!$tributacao) {
                        return false;
                }

                return in_array((int)$tributacao->regime, [0, 2], true);
        }

        private function retiraAcentos($texto){
                return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/", "/(ç)/"),explode(" ","a A e E i I o O u U n N c"),$texto);
        }

        private function acumulaIcms(float $total, $valor, int $decimais = 2): float
        {
                if ($valor === null || $valor === '') {
                        return $total;
                }

                $fator = (int) pow(10, $decimais);

                $totalInteiro = (int) round($total * $fator);
                $valorInteiro = (int) round(((float) $valor) * $fator);

                return ($totalInteiro + $valorInteiro) / $fator;
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

	public function cancelar($vendaId, $justificativa){
		try {
			$venda = Venda::
			where('id', $vendaId)
			->first();

			$chave = $venda->chave;
			$response = $this->tools->sefazConsultaChave($chave);
			$stdCl = new Standardize($response);
			$arr = $stdCl->toArray();
			sleep(1);
				// return $arr;
			$xJust = $justificativa;

			if(!isset($arr['protNFe'])){
				return ['erro' => true, 'data' => $arr['cStat'] . ' - ' . $arr['xMotivo'], 'status' => 402];
			}
			$nProt = $arr['protNFe']['infProt']['nProt'];

			$response = $this->tools->sefazCancela($chave, $xJust, $nProt);
			sleep(2);
			$stdCl = new Standardize($response);
			$std = $stdCl->toStd();
			$arr = $stdCl->toArray();
			$json = $stdCl->toJson();

			if ($std->cStat != 128) {
        //TRATAR
			} else {
				$cStat = $std->retEvento->infEvento->cStat;
				// $public = env('SERVIDOR_WEB') ? 'public/' : '';
				if ($cStat == '101' || $cStat == '135' || $cStat == '155' ) {
            //SUCESSO PROTOCOLAR A SOLICITAÇÂO ANTES DE GUARDAR
					$xml = Complements::toAuthorize($this->tools->lastRequest, $response);
					file_put_contents(public_path('xml_nfe_cancelada/').$chave.'.xml',$xml);

					return $json;
				} else {

					return ['erro' => true, 'data' => $arr, 'status' => 402];
				}
			}
		} catch (\Exception $e) {
			// echo $e->getMessage();
			return ['erro' => true, 'data' => $e->getMessage(), 'status' => 402];
    //TRATAR
		}
	}

	public function cartaCorrecao($id, $correcao){
		try {

			$venda = Venda::
			where('id', $id)
			->first();

			$chave = $venda->chave;
			$xCorrecao = $correcao;
			$nSeqEvento = $venda->sequencia_cce+1;
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
					// $public = env('SERVIDOR_WEB') ? 'public/' : '';
            //SUCESSO PROTOCOLAR A SOLICITAÇÂO ANTES DE GUARDAR
					$xml = Complements::toAuthorize($this->tools->lastRequest, $response);
					file_put_contents(public_path('xml_nfe_correcao/').$chave.'.xml',$xml);

					$venda->sequencia_cce = $venda->sequencia_cce + 1;
					$venda->save();
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

	public function sign($xml){
		try{
			return $this->tools->signNFe($xml);
		}catch(\Exception $e){
			echo $e->getMessage() . "<br>";
			echo $e->getLine();
			die;
		}
	}

	public function consultaStatus($tpAmb, $uf){
		try{
			$response = $this->tools->sefazStatus($uf, $tpAmb);
			$stdCl = new Standardize($response);
			$arr = $stdCl->toArray();
			return $arr;
		} catch (\Exception $e) {
			echo $e->getMessage();
		}
	}

        public function transmitir($signXml, $chave, $context = null)
        {
                $contextData = $this->normalizeContext($context);
                $logger = new EmissionLogger($this->empresa_id);
                $log = $logger->start($contextData, $signXml, $chave);

                $payloads = [];
                $venda = $contextData['entity'] ?? null;

                try {
                        $idLote = str_pad(100, 15, '0', STR_PAD_LEFT);
                        $indSinc = 1; // síncrono, uma NFe por vez
                        $resp = $this->tools->sefazEnviaLote([$signXml], $idLote, $indSinc);

                        $payloads[] = ['fase' => 'envio', 'conteudo' => $resp];

                        $st = new Standardize();
                        $std = $st->toStd($resp);

                        // Alguns servidores retornam 104 (síncrono), outros 103 (assíncrono)
                        if (isset($std->protNFe) && isset($std->protNFe->infProt)) {
                                $result = $this->handleProtocolResponse(
                                        $std->protNFe->infProt,
                                        $signXml,
                                        $chave,
                                        $payloads,
                                        $logger,
                                        $log,
                                        $contextData,
                                        $resp
                                );

                                if ($venda && $result->getRecibo()) {
                                        $venda->recibo = $result->getRecibo();
                                        if ($venda->signed_xml === null) {
                                                $venda->signed_xml = $signXml;
                                        }
                                        $venda->save();
                                }

                                return $result;
                        }

                        if (!isset($std->cStat)) {
                                $logger->finish($log, [
                                        'status' => 'erro_tecnico',
                                        'retorno_mensagem' => 'Retorno indefinido da SEFAZ.',
                                ], $resp, $payloads);

                                return new TransmissaoResult([
                                        'success' => false,
                                        'status' => 'erro_tecnico',
                                        'mensagem' => 'Retorno indefinido da SEFAZ.',
                                        'payload' => $payloads,
                                        'context' => $this->contextForResult($contextData),
                                ]);
                        }

                        if ((int)$std->cStat !== 103 || !isset($std->infRec->nRec)) {
                                // 105/656 = lote em processamento: devolve status dedicado para que o transmissor reavalie
                                if (in_array((int)$std->cStat, [105, 656], true)) {
                                        $mensagem = "[{$std->cStat}] - " . ($std->xMotivo ?? 'Lote em processamento.');
                                        $logger->finish($log, [
                                                'status' => 'em_processamento',
                                                'retorno_codigo' => $std->cStat,
                                                'retorno_mensagem' => $mensagem,
                                        ], $resp, $payloads);

                                        return new TransmissaoResult([
                                                'success' => false,
                                                'status' => 'em_processamento',
                                                'cStat' => (int)$std->cStat,
                                                'xMotivo' => $std->xMotivo ?? 'Lote em processamento.',
                                                'mensagem' => $mensagem,
                                                'payload' => $payloads,
                                                'context' => $this->contextForResult($contextData),
                                        ]);
                                }

                                // 204/539 = duplicidade de NF-e: mantém número reservado e retorna como rejeição detalhada
                                if (in_array((int)$std->cStat, [204, 539], true)) {
                                        $mensagem = "[{$std->cStat}] - " . ($std->xMotivo ?? 'Duplicidade de NF-e.');
                                        $logger->finish($log, [
                                                'status' => 'duplicidade',
                                                'retorno_codigo' => $std->cStat,
                                                'retorno_mensagem' => $mensagem,
                                        ], $resp, $payloads);

                                        return new TransmissaoResult([
                                                'success' => false,
                                                'status' => 'duplicidade',
                                                'cStat' => (int)$std->cStat,
                                                'xMotivo' => $std->xMotivo ?? 'Duplicidade de NF-e.',
                                                'mensagem' => $mensagem,
                                                'payload' => $payloads,
                                                'context' => $this->contextForResult($contextData),
                                        ]);
                                }

                                $mensagem = "[{$std->cStat}] - " . ($std->xMotivo ?? 'Retorno não informado.');
                                $logger->finish($log, [
                                        'status' => 'rejeitado',
                                        'retorno_codigo' => $std->cStat,
                                        'retorno_mensagem' => $mensagem,
                                ], $resp, $payloads);

                                return new TransmissaoResult([
                                        'success' => false,
                                        'status' => 'rejeitado',
                                        'cStat' => (int)$std->cStat,
                                        'xMotivo' => $std->xMotivo ?? 'Retorno não informado.',
                                        'mensagem' => $mensagem,
                                        'payload' => $payloads,
                                        'context' => $this->contextForResult($contextData),
                                ]);
                        }

                        $recibo = $std->infRec->nRec;
                        $payloads[] = ['fase' => 'recibo', 'conteudo' => $recibo];
                        if ($venda && $venda->recibo === null) {
                                $venda->recibo = $recibo;
                                if ($venda->signed_xml === null) {
                                        $venda->signed_xml = $signXml;
                                }
                                $venda->save();
                        }

                        sleep(5);
                        $protocolo = $this->tools->sefazConsultaRecibo($recibo);
                        $payloads[] = ['fase' => 'consulta', 'conteudo' => $protocolo];

                        $stdProtocolo = $st->toStd($protocolo);
                        if (!isset($stdProtocolo->protNFe) || !isset($stdProtocolo->protNFe->infProt)) {
                                $logger->finish($log, [
                                        'status' => 'erro_tecnico',
                                        'retorno_mensagem' => 'Retorno inválido da SEFAZ ao consultar recibo.',
                                        'recibo' => $recibo,
                                ], $protocolo, $payloads);

                                return new TransmissaoResult([
                                        'success' => false,
                                        'status' => 'erro_tecnico',
                                        'mensagem' => 'Retorno inválido da SEFAZ ao consultar recibo.',
                                        'recibo' => $recibo,
                                        'payload' => $payloads,
                                        'context' => $this->contextForResult($contextData),
                                ]);
                        }

                        $result = $this->handleProtocolResponse(
                                $stdProtocolo->protNFe->infProt,
                                $signXml,
                                $chave,
                                $payloads,
                                $logger,
                                $log,
                                $contextData,
                                $protocolo,
                                $recibo
                        );

                        return $result;

                } catch (\Throwable $e) {
                        $logger->finish($log, [
                                'status' => 'erro_tecnico',
                                'retorno_mensagem' => $e->getMessage(),
                        ], null, $payloads);

                        return new TransmissaoResult([
                                'success' => false,
                                'status' => 'erro_tecnico',
                                'mensagem' => $e->getMessage(),
                                'payload' => $payloads,
                                'context' => $this->contextForResult($contextData),
                        ]);
                }
        }

        private function handleProtocolResponse(
                $infProt,
                string $signXml,
                string $chave,
                array $payloads,
                EmissionLogger $logger,
                FiscalEmissionLog $log,
                array $contextData,
                ?string $rawResponse = null,
                ?string $recibo = null
        ): TransmissaoResult {
                $cStat = (int)($infProt->cStat ?? 0);
                $xMotivo = (string)($infProt->xMotivo ?? '');

                $attributes = [
                        'retorno_codigo' => $cStat,
                        'retorno_mensagem' => $xMotivo,
                        'recibo' => $recibo,
                ];

                if (in_array($cStat, [100, 150], true)) {
                        $xml = Complements::toAuthorize($signXml, $rawResponse ?? json_encode($infProt));
                        file_put_contents(public_path('xml_nfe/') . $chave . '.xml', $xml);

                        $attributes['status'] = 'autorizado';

                        $logger->finish($log, $attributes, $rawResponse, $payloads);

                        return new TransmissaoResult([
                                'success' => true,
                                'status' => 'autorizado',
                                'cStat' => $cStat,
                                'xMotivo' => $xMotivo,
                                'mensagem' => '[' . $cStat . '] - ' . $xMotivo,
                                'protocolo' => $infProt->nProt ?? null,
                                'recibo' => $recibo,
                                'xmlAutorizadoPath' => public_path('xml_nfe/' . $chave . '.xml'),
                                'payload' => $payloads,
                                'context' => $this->contextForResult($contextData),
                        ]);
                }

                if (in_array($cStat, [110, 301, 302], true)) {
                        $attributes['status'] = 'denegado';
                        $logger->finish($log, $attributes, $rawResponse, $payloads);

                        return new TransmissaoResult([
                                'success' => true,
                                'status' => 'denegado',
                                'cStat' => $cStat,
                                'xMotivo' => $xMotivo,
                                'mensagem' => '[' . $cStat . '] - ' . $xMotivo,
                                'recibo' => $recibo,
                                'payload' => $payloads,
                                'context' => $this->contextForResult($contextData),
                        ]);
                }

                if (in_array($cStat, [204, 539], true)) {
                        $attributes['status'] = 'duplicidade';
                        $logger->finish($log, $attributes, $rawResponse, $payloads);

                        return new TransmissaoResult([
                                'success' => false,
                                'status' => 'duplicidade',
                                'cStat' => $cStat,
                                'xMotivo' => $xMotivo,
                                'mensagem' => '[' . $cStat . '] - ' . $xMotivo,
                                'recibo' => $recibo,
                                'payload' => $payloads,
                                'context' => $this->contextForResult($contextData),
                        ]);
                }

                if (in_array($cStat, [105, 656], true)) {
                        $attributes['status'] = 'em_processamento';
                        $logger->finish($log, $attributes, $rawResponse, $payloads);

                        return new TransmissaoResult([
                                'success' => false,
                                'status' => 'em_processamento',
                                'cStat' => $cStat,
                                'xMotivo' => $xMotivo,
                                'mensagem' => '[' . $cStat . '] - ' . $xMotivo,
                                'recibo' => $recibo,
                                'payload' => $payloads,
                                'context' => $this->contextForResult($contextData),
                        ]);
                }

                $attributes['status'] = 'rejeitado';
                $logger->finish($log, $attributes, $rawResponse, $payloads);

                return new TransmissaoResult([
                        'success' => false,
                        'status' => 'rejeitado',
                        'cStat' => $cStat,
                        'xMotivo' => $xMotivo,
                        'mensagem' => '[' . $cStat . '] - ' . $xMotivo,
                        'recibo' => $recibo,
                        'payload' => $payloads,
                        'context' => $this->contextForResult($contextData),
                ]);
        }

        private function reservarNumeroNFe(Venda $venda, $config): int
        {
                if ($venda->NfNumero && $venda->NfNumero > 0) {
                        return (int) $venda->NfNumero;
                }

                return DB::transaction(function () use ($venda, $config) {
                        if ($venda->filial_id) {
                                $filial = Filial::where('id', $venda->filial_id)->lockForUpdate()->firstOrFail();
                                $numero = (int) (($filial->ultimo_numero_nfe ?? 0) + 1);
                                $filial->ultimo_numero_nfe = $numero;
                                $filial->save();
                                $serie = $filial->numero_serie_nfe;
                        } else {
                                $configEmitente = ConfigNota::where('empresa_id', $this->empresa_id)->lockForUpdate()->firstOrFail();
                                $numero = (int) (($configEmitente->ultimo_numero_nfe ?? 0) + 1);
                                $configEmitente->ultimo_numero_nfe = $numero;
                                $configEmitente->save();
                                $serie = $configEmitente->numero_serie_nfe;
                        }

                        $venda->NfNumero = $numero;
                        $venda->nSerie = $serie;
                        if (property_exists($venda, 'serie') || isset($venda->serie)) {
                                $venda->serie = $serie;
                        }
                        $venda->save();

                        return (int) $numero;
                }, 3);
        }

        private function normalizeContext($context): array
        {
                $data = [];

                if (is_int($context)) {
                        $data['document_id'] = $context;
                        $data['document_type'] = 'NFe';
                        $data['entity'] = Venda::find($context);
                } elseif ($context instanceof Venda) {
                        $data['document_id'] = $context->id;
                        $data['document_type'] = 'NFe';
                        $data['entity'] = $context;
                } elseif (is_array($context)) {
                        $data = $context;
                        if (($context['entity'] ?? null) instanceof Venda) {
                                $data['document_id'] = $context['entity']->id;
                        }
                }

                if (!isset($data['usuario_id'])) {
                        $data['usuario_id'] = $this->resolveUsuarioId();
                }

                if (!isset($data['document_type'])) {
                        $data['document_type'] = 'NFe';
                }

                if (!isset($data['ambiente']) && isset($this->config['tpAmb'])) {
                        $data['ambiente'] = (int)$this->config['tpAmb'];
                }

                return $data;
        }

        private function resolveUsuarioId(): ?int
        {
                $sessionUser = session('user_logged');
                return is_array($sessionUser) && isset($sessionUser['id']) ? (int)$sessionUser['id'] : null;
        }

        private function contextForResult(array $context): array
        {
                $result = $context;
                unset($result['entity']);

                return $result;
        }

	public function consultar($vendaId){
		try {
			$venda = Venda::
			where('id', $vendaId)
			->first();
			$this->tools->model('55');

			$chave = $venda->chave;

			$response = $this->tools->sefazConsultaChave($chave);

			$stdCl = new Standardize($response);
			$arr = $stdCl->toArray();

                        if($arr['xMotivo'] == 'Autorizado o uso da NF-e'){
                                if($venda->estado != 'APROVADO'){

                                        $chave = $arr['protNFe']['infProt']['chNFe'];
                                        $nRec = $venda->recibo;
                                        if (!$nRec) {
                                                $venda->estado = 'APROVADO';
                                                $venda->save();
                                                return json_encode($arr);
                                        }

                                        $protocolo = $this->tools->sefazConsultaRecibo($nRec);
                                        sleep(3);
                                        $st = new Standardize();
                                        $std = $st->toStd($protocolo);
                                        // return $std;
                                        if($std->protNFe->infProt->cStat == 100){
                                                // $venda->chave = $chave;
                                                $venda->estado = 'APROVADO';
                                                $venda->save();

                                                $xml = Complements::toAuthorize($venda->signed_xml, $protocolo);
                                                file_put_contents(public_path('xml_nfe/').$chave.'.xml',$xml);
                                                // return $xml;
                                        }
                                }
                        }

			return json_encode($arr);

		} catch (\Exception $e) {
			echo $e->getMessage();
		}
	}


}
