<?php
namespace App\Services;

use App\Support\FiscalDateHelper;

use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use App\Models\VendaCaixa;
use App\Models\ConfigNota;
use App\Models\Certificado;
use NFePHP\NFe\Complements;
use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\Legacy\FilesFolders;
use NFePHP\Common\Soap\SoapCurl;
use App\Models\Tributacao;
use App\Models\PedidoDelivery;
use App\Models\IBPT;
use App\Models\Filial;
use App\Models\Contigencia;
use NFePHP\NFe\Factories\Contingency;
use App\Services\ReformaTributariaService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\Fiscal\EmissionLogger;
use App\Models\FiscalEmissionLog;
use Illuminate\Support\Facades\Storage;

error_reporting(E_ALL);
ini_set('display_errors', 'On');

class NFCeService{

    private $config;
    private $tools;
    protected $empresa_id = null;

    public function __construct($config, $empresa_id = null){
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
        $soapCurl = new SoapCurl();
        $soapCurl->httpVersion('1.1');
        $contigencia = $this->getContigencia();

        if($contigencia != null){
            $contingency = new Contingency($contigencia->status_retorno);
            $this->tools->contingency = $contingency;
        }
        $this->tools->loadSoapClass($soapCurl);
        $this->tools->model(65);

    }

    private function onlyDigits($value): string
    {
        return preg_replace('/\D+/', '', (string)$value);
    }


    private function normalizeTwoDigitCst($cst): string
    {
        $cst = preg_replace('/\D+/', '', (string)$cst);
        if ($cst === '' || $cst === null) {
            Log::warning('NFC-e: CST PIS/COFINS inválido. Aplicado CST 99.', ['origem' => (string)$cst]);
            return '99';
        }

        return str_pad(substr($cst, -2), 2, '0', STR_PAD_LEFT);
    }

    private function isPisCofinsCstSemIncidencia(string $cst): bool
    {
        return in_array($cst, ['04', '05', '06', '07', '08', '09'], true);
    }

    private function deveCalcularPisCofins(string $cst, $aliquota): bool
    {
        return !$this->isPisCofinsCstSemIncidencia($cst) && (float) $aliquota > 0;
    }

    private function calculaBasePisCofinsItem($stdProd, ?float $baseIcmsItem): float
    {
        $baseItem = (float) ($baseIcmsItem ?? 0);
        if ($baseItem <= 0) {
            $baseItem = ((float) ($stdProd->vProd ?? 0))
                + ((float) ($stdProd->vFrete ?? 0))
                + ((float) ($stdProd->vOutro ?? 0))
                - ((float) ($stdProd->vDesc ?? 0));
        }

        if ($baseItem < 0) {
            $baseItem = 0;
        }

        return (float) $this->format($baseItem);
    }

    private function getNumericFromAny($source, array $fields, float $default = 0.0): float
    {
        foreach ($fields as $field) {
            if (is_object($source) && isset($source->{$field}) && $source->{$field} !== null && $source->{$field} !== '') {
                return (float) $source->{$field};
            }
            if (is_array($source) && array_key_exists($field, $source) && $source[$field] !== null && $source[$field] !== '') {
                return (float) $source[$field];
            }
        }
        return $default;
    }

    private function persistTotaisDefensivo($documento, array $totais, string $documentoTipo): void
    {
        if (!$documento || !method_exists($documento, 'getAttributes')) {
            return;
        }

        $attributes = $documento->getAttributes();
        $changed = false;
        $persistidos = [];
        foreach ($totais as $campo => $valor) {
            if (!array_key_exists($campo, $attributes)) {
                continue;
            }
            $valorFormatado = $this->format((float) $valor);
            if ((float) ($documento->{$campo} ?? 0) !== (float) $valorFormatado) {
                $documento->{$campo} = $valorFormatado;
                $changed = true;
                $persistidos[$campo] = $valorFormatado;
            }
        }

        if ($changed) {
            $documento->save();
            Log::info('NFC-e: totais fiscais persistidos defensivamente.', [
                'empresa_id' => $this->empresa_id,
                'documento_tipo' => $documentoTipo,
                'documento_id' => (int) ($documento->id ?? 0),
                'campos' => $persistidos,
            ]);
        }
    }

    private function montarResumoPisCofinsObservacao(float $somaBasePIS, float $somaPIS, float $somaBaseCOFINS, float $somaCOFINS): string
    {
        if ($somaPIS <= 0 && $somaCOFINS <= 0) {
            Log::info('NFC-e: resumo PIS/COFINS não adicionado em infCpl por ausência de valores positivos.', [
                'empresa_id' => $this->empresa_id,
                'soma_pis' => $somaPIS,
                'soma_cofins' => $somaCOFINS,
            ]);
            return '';
        }

        $resumo = 'PIS: BC=' . number_format($somaBasePIS, 2, ',', '.') . ' Valor=' . number_format($somaPIS, 2, ',', '.');
        $resumo .= ' | COFINS: BC=' . number_format($somaBaseCOFINS, 2, ',', '.') . ' Valor=' . number_format($somaCOFINS, 2, ',', '.');

        Log::info('NFC-e: resumo PIS/COFINS adicionado em infCpl.', [
            'empresa_id' => $this->empresa_id,
            'soma_base_pis' => $somaBasePIS,
            'soma_pis' => $somaPIS,
            'soma_base_cofins' => $somaBaseCOFINS,
            'soma_cofins' => $somaCOFINS,
        ]);

        return $resumo;
    }

    private function tryAttachReformaItemTag($nfe, int $itemCont, $item): bool
    {
        $rt = app(ReformaTributariaService::class);

        $norm = $this->normalizeReformaItemValues($item);
        $base = $norm['vBC'];
        $vIbs = $norm['vIBS'];
        $vCbs = $norm['vCBS'];
        $vIs  = $norm['vIS'];
        if ($base <= 0 && $vIbs <= 0 && $vCbs <= 0 && $vIs <= 0) {
            return false;
        }

        if (!$rt->shouldApply((int)($this->empresa_id ?? 0))) {
            Log::info('RT: grupo IBS/CBS não será anexado ao XML da NFC-e porque shouldApply() retornou false.', [
                'empresa_id' => (int)($this->empresa_id ?? 0),
                'item_id' => (int)($item->id ?? 0),
            ]);
            return false;
        }

        $std = new \stdClass();
        $std->item = $itemCont;
        $std->CST = $norm['CST'];
        $std->cClassTrib = $norm['cClassTrib'];
        $std->vBC = $this->format($norm['vBC']);
        $std->pIBSUF = $this->format($norm['pIBSUF'], 4);
        $std->vIBSUF = $this->format($norm['vIBSUF']);
        $std->pIBSMun = $this->format($norm['pIBSMun'], 4);
        $std->vIBSMun = $this->format($norm['vIBSMun']);
        $std->vIBS = $this->format($norm['vIBS']);
        $std->pCBS = $this->format($norm['pCBS'], 4);
        $std->vCBS = $this->format($norm['vCBS']);
        $std->vIS = $this->format($norm['vIS']);

        $methods = ['tagIBSCBS', 'tagImpostoIBSCBS', 'tagIBS'];
        foreach ($methods as $method) {
            if (!method_exists($nfe, $method)) {
                continue;
            }
            try {
                $nfe->{$method}($std);
                return true;
            } catch (\Throwable $e) {
                Log::warning('NFC-e: falha ao anexar grupo estruturado de Reforma Tributária.', [
                    'metodo' => $method,
                    'venda_item_id' => (int)($item->id ?? 0),
                    'mensagem' => $e->getMessage(),
                ]);
                continue;
            }
        }

        Log::warning('NFC-e: nenhum método disponível conseguiu anexar grupo estruturado de Reforma Tributária.', [
            'venda_item_id' => (int)($item->id ?? 0),
            'empresa_id' => $this->empresa_id,
        ]);
        return false;
    }

    private function normalizeReformaItemValues($item): array
    {
        $rt = app(ReformaTributariaService::class);
        if (is_object($item) || is_array($item)) {
            $rt->applyAliquotasFixas($item);
        }

        $base = max(0, $this->getNumericFromAny($item, ['bc_ibs_cbs', 'base_ibs_cbs', 'bc_rt']));
        $fixas = $rt->aliquotasFixas();

        $pIbsUf = (float) $fixas['ibs_uf'];
        $pIbsMun = (float) $fixas['ibs_mun'];
        $pCbs = (float) $fixas['cbs'];
        $pIs = max(0, (float)($item->is_aliq ?? 0));

        $vIbsUf = max(0, (float)($item->valor_ibs_uf ?? 0));
        $vIbsMun = max(0, (float)($item->valor_ibs_mun ?? 0));
        $vCbs = max(0, $this->getNumericFromAny($item, ['valor_cbs', 'cbs_valor']));
        $vIs = max(0, $this->getNumericFromAny($item, ['valor_is', 'is_valor']));

        if ($base > 0) {
            $vIbsUf = round(($base * $pIbsUf) / 100, 2);
            $vIbsMun = round(($base * $pIbsMun) / 100, 2);
            $vCbs = round(($base * $pCbs) / 100, 2);
            if ($pIs > 0 && $vIs <= 0) {
                $vIs = round(($base * $pIs) / 100, 2);
            }
        }

        $vIbs = round($vIbsUf + $vIbsMun, 2);

        return [
            'CST' => str_pad((string)($item->cst_ibs_cbs ?? $item->ibs_cbs_cst ?? '000'), 3, '0', STR_PAD_LEFT),
            'cClassTrib' => str_pad((string)($item->class_trib_ibs_cbs ?? $item->class_trib_rt ?? '000000'), 6, '0', STR_PAD_LEFT),
            'vBC' => $base,
            'pIBSUF' => $pIbsUf,
            'vIBSUF' => $vIbsUf,
            'pIBSMun' => $pIbsMun,
            'vIBSMun' => $vIbsMun,
            'vIBS' => $vIbs,
            'pCBS' => $pCbs,
            'vCBS' => $vCbs,
            'pIS' => $pIs,
            'vIS' => $vIs,
        ];
    }


    private function appendNsNode(\DOMDocument $dom, \DOMElement $parent, string $name, ?string $value = null): \DOMElement
    {
        $node = $dom->createElementNS('http://www.portalfiscal.inf.br/nfe', $name);
        if ($value !== null) {
            $node->appendChild($dom->createTextNode($value));
        }
        $parent->appendChild($node);
        return $node;
    }

    private function finalizeReformaTributariaXml(string $xml, $venda): array
    {
        if (trim($xml) === '' || !$venda) {
            return ['xml' => $xml, 'structured' => false];
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (!@$dom->loadXML($xml)) {
            return ['xml' => $xml, 'structured' => false];
        }
        $xp = new \DOMXPath($dom);
        $xp->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
		$rt = app(ReformaTributariaService::class);
		if (!$rt->shouldApply((int)($this->empresa_id ?? 0))) {
			foreach ($xp->query('//nfe:IBSCBS | //nfe:IS | //nfe:IBSCBSTot | //nfe:ISTot') as $node) {
				$node->parentNode?->removeChild($node);
			}
			return ['xml' => $dom->saveXML(), 'structured' => false];
		}

        $detNodes = $xp->query('//nfe:det');
        $items = $venda->itens ?? [];
        $structured = false;
        foreach ($detNodes as $idx => $det) {
            $item = $items[$idx] ?? null;
            if (!$item) continue;
            $norm = $this->normalizeReformaItemValues($item);
            $imposto = $xp->query('./nfe:imposto', $det)->item(0);
            if (!$imposto) continue;
            if ($xp->query('./nfe:IS', $imposto)->length === 0 && $norm['vIS'] > 0) {
                $is = $this->appendNsNode($dom, $imposto, 'IS');
                $this->appendNsNode($dom, $is, 'vIS', $this->format($norm['vIS']));
                $structured = true;
            }
            if ($xp->query('./nfe:IBSCBS', $imposto)->length === 0 && ($norm['vBC'] > 0 || $norm['vIBS'] > 0 || $norm['vCBS'] > 0)) {
                $ibscbs = $this->appendNsNode($dom, $imposto, 'IBSCBS');
                $this->appendNsNode($dom, $ibscbs, 'CST', $norm['CST']);
                $this->appendNsNode($dom, $ibscbs, 'cClassTrib', $norm['cClassTrib']);
                $g = $this->appendNsNode($dom, $ibscbs, 'gIBSCBS');
                $this->appendNsNode($dom, $g, 'vBC', $this->format($norm['vBC']));
                $gUf = $this->appendNsNode($dom, $g, 'gIBSUF');
                $this->appendNsNode($dom, $gUf, 'pIBSUF', $this->format($norm['pIBSUF'], 4));
                $this->appendNsNode($dom, $gUf, 'vIBSUF', $this->format($norm['vIBSUF']));
                $gMun = $this->appendNsNode($dom, $g, 'gIBSMun');
                $this->appendNsNode($dom, $gMun, 'pIBSMun', $this->format($norm['pIBSMun'], 4));
                $this->appendNsNode($dom, $gMun, 'vIBSMun', $this->format($norm['vIBSMun']));
                $this->appendNsNode($dom, $g, 'vIBS', $this->format($norm['vIBS']));
                $gCbs = $this->appendNsNode($dom, $g, 'gCBS');
                $this->appendNsNode($dom, $gCbs, 'pCBS', $this->format($norm['pCBS'], 4));
                $this->appendNsNode($dom, $gCbs, 'vCBS', $this->format($norm['vCBS']));
                $structured = true;
            }
        }
        $totalNode = $xp->query('//nfe:total')->item(0);
        if ($totalNode) {
            $tBase = max(0, (float)($venda->total_bc_ibs_cbs ?? 0));
            $tIbsUf = max(0, (float)($venda->total_ibs_uf ?? 0));
            $tIbsMun = max(0, (float)($venda->total_ibs_mun ?? 0));
            $tIbs = max(0, (float)($venda->total_ibs ?? ($tIbsUf + $tIbsMun)));
            $tCbs = max(0, (float)($venda->total_cbs ?? 0));
            $tIs = max(0, (float)($venda->total_is ?? 0));
            if (($tBase > 0 || $tIbs > 0 || $tCbs > 0) && $xp->query('./nfe:IBSCBSTot', $totalNode)->length === 0) {
                $tot = $this->appendNsNode($dom, $totalNode, 'IBSCBSTot');
                $this->appendNsNode($dom, $tot, 'vBCIBSCBS', $this->format($tBase));
                $gIbs = $this->appendNsNode($dom, $tot, 'gIBS');
                $gUf = $this->appendNsNode($dom, $gIbs, 'gIBSUF');
                $this->appendNsNode($dom, $gUf, 'vDif', $this->format((float)($venda->total_ibs_uf_dif ?? 0)));
                $this->appendNsNode($dom, $gUf, 'vDevTrib', $this->format((float)($venda->total_ibs_uf_dev_trib ?? 0)));
                $this->appendNsNode($dom, $gUf, 'vIBSUF', $this->format($tIbsUf));
                $gMun = $this->appendNsNode($dom, $gIbs, 'gIBSMun');
                $this->appendNsNode($dom, $gMun, 'vDif', $this->format((float)($venda->total_ibs_mun_dif ?? 0)));
                $this->appendNsNode($dom, $gMun, 'vDevTrib', $this->format((float)($venda->total_ibs_mun_dev_trib ?? 0)));
                $this->appendNsNode($dom, $gMun, 'vIBSMun', $this->format($tIbsMun));
                $this->appendNsNode($dom, $gIbs, 'vIBS', $this->format($tIbs));
                $this->appendNsNode($dom, $gIbs, 'vCredPres', $this->format((float)($venda->total_ibs_cred_pres ?? 0)));
                $this->appendNsNode($dom, $gIbs, 'vCredPresCondSus', $this->format((float)($venda->total_ibs_cred_pres_cond_sus ?? 0)));
                $gCbs = $this->appendNsNode($dom, $tot, 'gCBS');
                $this->appendNsNode($dom, $gCbs, 'vDif', $this->format((float)($venda->total_cbs_dif ?? 0)));
                $this->appendNsNode($dom, $gCbs, 'vDevTrib', $this->format((float)($venda->total_cbs_dev_trib ?? 0)));
                $this->appendNsNode($dom, $gCbs, 'vCBS', $this->format($tCbs));
                $this->appendNsNode($dom, $gCbs, 'vCredPres', $this->format((float)($venda->total_cbs_cred_pres ?? 0)));
                $this->appendNsNode($dom, $gCbs, 'vCredPresCondSus', $this->format((float)($venda->total_cbs_cred_pres_cond_sus ?? 0)));
                $structured = true;
            }
            if ($tIs > 0 && $xp->query('./nfe:ISTot', $totalNode)->length === 0) {
                $isTot = $this->appendNsNode($dom, $totalNode, 'ISTot');
                $this->appendNsNode($dom, $isTot, 'vIS', $this->format($tIs));
                $structured = true;
            }
        }
        return ['xml' => $dom->saveXML(), 'structured' => $structured];
    }

    private function montarReformaObservacao($venda, bool $reformaEstruturadaNoXml = false): string
    {
        $rt = app(ReformaTributariaService::class);

        $tBase = (float)($venda->total_bc_ibs_cbs ?? 0);
        $tIbs = (float)($venda->total_ibs ?? 0);
        $tCbs = (float)($venda->total_cbs ?? 0);
        $tIs = (float)($venda->total_is ?? 0);

        if ($tBase <= 0 && $tIbs <= 0 && $tCbs <= 0 && $tIs <= 0) {
            return '';
        }

        if (!$reformaEstruturadaNoXml) {
            Log::warning('NFC-e: totais de Reforma Tributária calculados, mas grupo estruturado não foi anexado ao XML. Texto complementar suprimido para evitar divergência DANFE x XML.', [
                'empresa_id' => $this->empresa_id,
                'venda_id' => (int)($venda->id ?? 0),
                'total_bc_ibs_cbs' => $tBase,
                'total_ibs' => $tIbs,
                'total_cbs' => $tCbs,
                'total_is' => $tIs,
            ]);
            return '';
        }

        return "RT IBS/CBS/IS: BC=" . number_format($tBase, 2, ',', '.')
            . " IBS=" . number_format($tIbs, 2, ',', '.')
            . " CBS=" . number_format($tCbs, 2, ',', '.')
            . " IS=" . number_format($tIs, 2, ',', '.');
    }

    private function montarTributosAproximadosObservacao(float $somaEstadual, float $somaFederal, float $somaMunicipal, string $obsIbpt): string
    {
        if ($somaEstadual <= 0 && $somaFederal <= 0 && $somaMunicipal <= 0) {
            return '';
        }

        $partes = [];
        if ($somaFederal > 0) $partes[] = "R$ " . number_format($somaFederal, 2, ',', '.') . " Federal";
        if ($somaEstadual > 0) $partes[] = "R$ " . number_format($somaEstadual, 2, ',', '.') . " Estadual";
        if ($somaMunicipal > 0) $partes[] = "R$ " . number_format($somaMunicipal, 2, ',', '.') . " Municipal";

        $texto = 'Trib. aprox. ' . implode(', ', $partes);
        $obsIbpt = trim((string) $obsIbpt);
        if ($obsIbpt !== '') {
            $texto .= ' ' . $obsIbpt;
        }
        return trim($texto);
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

    private function resolveInfCplFlag($config, ?ConfigNota $matriz, string $campo, int $default = 0): int
    {
        if (isset($config->{$campo}) && $config->{$campo} !== null && $config->{$campo} !== '') {
            return (int)$config->{$campo};
        }

        if ($matriz && isset($matriz->{$campo}) && $matriz->{$campo} !== null && $matriz->{$campo} !== '') {
            return (int)$matriz->{$campo};
        }

        return $default;
    }

    private function emitentePermiteIsencao($tributacao): bool
    {
        if (!$tributacao) {
            return false;
        }

        return in_array((int)$tributacao->regime, [0, 2], true);
    }

    /**
     * Em uma NFC-e rejeitada a identidade fiscal já foi definida.
     * A chave e o XML original fornecem série, número, cNF e data.
     *
     * Falha de forma segura caso qualquer informação esteja ausente ou
     * inconsistente; jamais reserva novo número ou sorteia outro cNF.
     */
    private function identidadeOriginalNFCe(VendaCaixa $venda, $config): array
    {
        $chave = trim((string) $venda->chave);
        if (!preg_match('/^[0-9]{44}$/D', $chave) || substr($chave, 20, 2) !== '65') {
            throw new \DomainException('NFC-e rejeitada sem chave de acesso válida. Não é seguro gerar outra chave ao retransmitir.');
        }

        $numero = (int) substr($chave, 25, 9);
        if ($numero <= 0 || (int) $venda->NFcNumero !== $numero) {
            throw new \DomainException('Número da NFC-e rejeitada diverge da chave original. Corrija a inconsistência antes de retransmitir.');
        }

        if (substr($chave, 6, 14) !== $this->onlyDigits($config->cnpj)
            || (int) substr($chave, 0, 2) !== (int) $config->cUF) {
            throw new \DomainException('Emitente da NFC-e rejeitada diverge da chave original. Retransmissão bloqueada.');
        }

        $assinado = trim((string) $venda->signed_xml);
        if ($assinado === '') {
            throw new \DomainException('XML assinado original da NFC-e rejeitada indisponível. Não é seguro trocar a data ou a chave.');
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        if (!@$dom->loadXML($assinado, LIBXML_NONET)) {
            throw new \DomainException('XML assinado original inválido. Retransmissão bloqueada para preservar a chave.');
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
        $infNFe = $xpath->query('//nfe:infNFe')->item(0);

        if (!$infNFe || $infNFe->getAttribute('Id') !== 'NFe' . $chave) {
            throw new \DomainException('A chave do XML assinado não coincide com a chave da venda. Retransmissão bloqueada.');
        }

        $dhEmi = trim((string) $xpath->evaluate('string(./nfe:ide/nfe:dhEmi)', $infNFe));
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(?:\+|-)[0-9]{2}:[0-9]{2}$/D', $dhEmi)
            || substr($dhEmi, 2, 2) . substr($dhEmi, 5, 2) !== substr($chave, 2, 4)) {
            throw new \DomainException('Data original da NFC-e incompatível com a chave. Não será gerada outra identificação.');
        }

        $dhSaiEnt = trim((string) $xpath->evaluate('string(./nfe:ide/nfe:dhSaiEnt)', $infNFe));
        if ($dhSaiEnt !== '' && !preg_match(
            '/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(?:\+|-)[0-9]{2}:[0-9]{2}$/D',
            $dhSaiEnt
        )) {
            throw new \DomainException('Data original de saída/movimentação inválida. Retransmissão bloqueada para não trocar o horário do documento.');
        }

        $tpEmisXml = (int) $xpath->evaluate('string(./nfe:ide/nfe:tpEmis)', $infNFe);
        if ($tpEmisXml !== (int) $chave[34]) {
            throw new \DomainException('Tipo de emissão do XML diverge da chave original. Retransmissão bloqueada.');
        }

        return [
            'chave' => $chave,
            'cUF' => (int) substr($chave, 0, 2),
            'cNF' => substr($chave, 35, 8),
            'serie' => (int) substr($chave, 22, 3),
            'nNF' => $numero,
            'tpEmis' => (int) substr($chave, 34, 1),
            'dhEmi' => $dhEmi,
            'dhSaiEnt' => $dhSaiEnt !== '' ? $dhSaiEnt : null,
        ];
    }


    /**
     * Localiza a última rejeição 704 desta NFC-e para permitir uma nova assinatura
     * do XML arquivado depois de a consulta confirmar que a chave não consta na SEFAZ.
     * Nenhuma data ou outro campo fiscal é alterado.
     */
    public function dadosReassinaturaNfce704(VendaCaixa $venda, $config): ?array
    {
        if ($venda->estado !== 'REJEITADO'
            || (int) $venda->empresa_id !== (int) $this->empresa_id) {
            return null;
        }

        $ultimoLog = FiscalEmissionLog::where('empresa_id', $this->empresa_id)
            ->where('document_type', 'NFCe')
            ->where('document_id', $venda->id)
            ->orderBy('id', 'desc')
            ->first(['id', 'status', 'retorno_codigo', 'chave', 'filial_id', 'xml_envio_path']);

        if (!$ultimoLog
            || $ultimoLog->status !== 'rejeitado'
            || (int) $ultimoLog->retorno_codigo !== 704
            || trim((string) $ultimoLog->chave) !== trim((string) $venda->chave)) {
            return null;
        }

        if ($ultimoLog->filial_id !== null
            && (int) $ultimoLog->filial_id !== (int) $venda->filial_id) {
            return [
                'permitido' => false,
                'motivo' => 'A rejeição 704 registrada pertence a outra filial. A retransmissão foi bloqueada.',
            ];
        }

        $pathXmlAnterior = trim((string) $ultimoLog->xml_envio_path);
        if (!preg_match(
            '~^fiscal_emissions/[0-9]{4}/[0-9]{2}/[0-9]{2}/[A-Za-z0-9]+_envio_[A-Za-z0-9]+\\.xml$~D',
            $pathXmlAnterior
        ) || !Storage::disk('local')->exists($pathXmlAnterior)) {
            return [
                'permitido' => false,
                'motivo' => 'O XML original da tentativa 704 não está disponível no histórico fiscal. A retransmissão foi bloqueada.',
            ];
        }

        $identidade = $this->identidadeOriginalNFCe($venda, $config);
        if ((int) $identidade['tpEmis'] !== 1) {
            return [
                'permitido' => false,
                'motivo' => 'O XML da rejeição 704 não está em emissão normal (tpEmis=1). A retransmissão automática foi bloqueada.',
            ];
        }

        return [
            'permitido' => true,
            'motivo' => null,
            'chave' => $identidade['chave'],
            'nNF' => $identidade['nNF'],
            'tpEmis' => $identidade['tpEmis'],
            'dhEmi_original' => $identidade['dhEmi'],
            'dhSaiEnt_original' => $identidade['dhSaiEnt'],
            'fiscal_emission_log_id' => (int) $ultimoLog->id,
            'xml_envio_path' => $pathXmlAnterior,
        ];
    }

    /**
     * Reutiliza o XML arquivado e remove apenas a assinatura digital anterior.
     * O infNFe completo, inclusive dhEmi e dhSaiEnt, deve permanecer idêntico.
     */
    public function prepararReassinaturaNfce704(VendaCaixa $venda, $config, array $dados): array
    {
        if ($venda->estado !== 'REJEITADO'
            || (int) $venda->empresa_id !== (int) $this->empresa_id
            || empty($dados['permitido'])) {
            throw new \DomainException('A NFC-e não está elegível para retransmissão da mesma identidade fiscal.');
        }

        $log = FiscalEmissionLog::where('empresa_id', $this->empresa_id)
            ->where('document_type', 'NFCe')
            ->where('document_id', $venda->id)
            ->orderBy('id', 'desc')
            ->first(['id', 'status', 'retorno_codigo', 'chave', 'filial_id', 'xml_envio_path']);

        if (!$log
            || $log->status !== 'rejeitado'
            || (int) $log->retorno_codigo !== 704
            || trim((string) $log->chave) !== trim((string) $venda->chave)
            || (int) $log->id !== (int) ($dados['fiscal_emission_log_id'] ?? 0)) {
            throw new \DomainException('A última tentativa fiscal já não corresponde à rejeição 704 validada.');
        }

        if ($log->filial_id !== null
            && (int) $log->filial_id !== (int) $venda->filial_id) {
            throw new \DomainException('O XML da rejeição 704 pertence a outra filial.');
        }

        $path = trim((string) $log->xml_envio_path);
        if ($path === ''
            || $path !== (string) ($dados['xml_envio_path'] ?? '')
            || !preg_match(
                '~^fiscal_emissions/[0-9]{4}/[0-9]{2}/[0-9]{2}/[A-Za-z0-9]+_envio_[A-Za-z0-9]+\\.xml$~D',
                $path
            )
            || !Storage::disk('local')->exists($path)) {
            throw new \DomainException('O XML original da tentativa 704 não está disponível no histórico fiscal.');
        }

        $xmlOriginal = (string) Storage::disk('local')->get($path);
        $identidade = $this->identidadeNfceDeXmlAssinado($venda, $config, $xmlOriginal);
        $chave = trim((string) ($dados['chave'] ?? ''));
        $dhEmiOriginal = trim((string) ($dados['dhEmi_original'] ?? ''));
        $dhSaiEntOriginal = $dados['dhSaiEnt_original'] ?? null;

        if ($chave === ''
            || !hash_equals($identidade['chave'], $chave)
            || !hash_equals(trim((string) $venda->chave), $chave)
            || (int) ($dados['nNF'] ?? 0) !== (int) $identidade['numero']
            || !hash_equals($identidade['dhEmi'], $dhEmiOriginal)
            || ($identidade['dhSaiEnt'] ?? null) !== $dhSaiEntOriginal
            || (int) $identidade['tpEmis'] !== 1
            || (int) ($dados['tpEmis'] ?? 0) !== 1) {
            throw new \DomainException('A identidade ou as datas do XML arquivado divergiram da NFC-e rejeitada.');
        }

        $xmlSemAssinatura = $this->removerAssinaturaXmlNfce704(
            $xmlOriginal,
            $chave,
            $dhEmiOriginal
        );

        return [
            'xml' => $xmlSemAssinatura['xml'],
            'chave' => $chave,
            'nNf' => (int) $identidade['numero'],
            'modelo' => 65,
            'dhEmi' => $identidade['dhEmi'],
            'dhSaiEnt' => $identidade['dhSaiEnt'],
            'tpEmis' => $identidade['tpEmis'],
            'sha256_xml_original' => hash('sha256', $xmlOriginal),
            'fiscal_emission_log_id' => (int) $log->id,
            'fingerprint_xml_sem_assinatura' => $xmlSemAssinatura['fingerprint_xml_sem_assinatura'],
        ];
    }

    /**
     * Remove apenas ds:Signature da tentativa anterior e compara todo o XML
     * fiscal sem assinatura por canonicalização, antes e depois.
     */
    private function removerAssinaturaXmlNfce704(
        string $xmlOriginal,
        string $chave,
        string $dhEmiOriginal
    ): array {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        if (!@$dom->loadXML($xmlOriginal, LIBXML_NONET)) {
            throw new \DomainException('O XML original da tentativa 704 não pôde ser interpretado.');
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $infNFe = $xpath->query('//nfe:infNFe');
        $dhEmi = $xpath->query('//nfe:infNFe/nfe:ide/nfe:dhEmi');
        $dhSaiEnt = $xpath->query('//nfe:infNFe/nfe:ide/nfe:dhSaiEnt');
        $assinaturas = $xpath->query('//ds:Signature');
        if ($infNFe->length !== 1
            || $dhEmi->length !== 1
            || $dhSaiEnt->length > 1
            || $assinaturas->length !== 1) {
            throw new \DomainException('Não é seguro reaproveitar o XML sem uma única infNFe, dhEmi e assinatura.');
        }

        $info = $infNFe->item(0);
        $dhEmiValor = trim((string) $dhEmi->item(0)->textContent);
        if ($info->getAttribute('Id') !== 'NFe' . $chave
            || !hash_equals($dhEmiOriginal, $dhEmiValor)
            || substr($dhEmiValor, 2, 2) . substr($dhEmiValor, 5, 2) !== substr($chave, 2, 4)) {
            throw new \DomainException('A chave ou dhEmi do XML não corresponde à tentativa rejeitada.');
        }

        $fingerprintOriginal = $this->fingerprintXmlFiscalSemAssinatura($xmlOriginal);
        $assinatura = $assinaturas->item(0);
        $assinatura->parentNode?->removeChild($assinatura);
        $xmlSemAssinatura = $dom->saveXML();
        if (!is_string($xmlSemAssinatura) || $xmlSemAssinatura === '') {
            throw new \DomainException('Não foi possível preparar o XML original para nova assinatura.');
        }

        $fingerprintSemAssinatura = $this->fingerprintXmlFiscalSemAssinatura($xmlSemAssinatura);
        if (!hash_equals($fingerprintOriginal, $fingerprintSemAssinatura)) {
            throw new \DomainException('A remoção da assinatura alterou campos fiscais da NFC-e. Retransmissão bloqueada.');
        }

        return [
            'xml' => $xmlSemAssinatura,
            'fingerprint_xml_sem_assinatura' => $fingerprintOriginal,
        ];
    }

    private function fingerprintXmlFiscalSemAssinatura(string $xml): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;
        if (!@$dom->loadXML($xml, LIBXML_NONET)) {
            throw new \DomainException('Não foi possível validar a integridade do XML fiscal.');
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $assinaturas = $xpath->query('//ds:Signature');
        foreach ($assinaturas as $assinatura) {
            $assinatura->parentNode?->removeChild($assinatura);
        }

        $raiz = $dom->documentElement;
        $canonical = $raiz ? $raiz->C14N() : false;
        if (!is_string($canonical) || $canonical === '') {
            throw new \DomainException('Não foi possível canonicalizar o XML fiscal sem assinatura.');
        }

        return hash('sha256', $canonical);
    }

    public function validarXmlReassinadoNfce704(array $proposta, string $xmlAssinado): void
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        if (!@$dom->loadXML($xmlAssinado, LIBXML_NONET)) {
            throw new \DomainException('A nova assinatura não produziu um XML legível.');
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $infNFe = $xpath->query('//nfe:infNFe');
        $assinaturas = $xpath->query('//ds:Signature');
        if ($infNFe->length !== 1 || $assinaturas->length !== 1) {
            throw new \DomainException('A nova assinatura não contém uma única NFC-e identificável.');
        }

        $info = $infNFe->item(0);
        $chave = trim((string) ($proposta['chave'] ?? ''));
        $dhEmi = trim((string) $xpath->evaluate('string(./nfe:ide/nfe:dhEmi)', $info));
        $dhSaiEnt = trim((string) $xpath->evaluate('string(./nfe:ide/nfe:dhSaiEnt)', $info));
        $dhSaiEnt = $dhSaiEnt !== '' ? $dhSaiEnt : null;
        $tpEmis = (int) $xpath->evaluate('string(./nfe:ide/nfe:tpEmis)', $info);
        $fingerprint = $this->fingerprintXmlFiscalSemAssinatura($xmlAssinado);
        if ($info->getAttribute('Id') !== 'NFe' . $chave
            || !hash_equals((string) ($proposta['dhEmi'] ?? ''), $dhEmi)
            || $dhSaiEnt !== ($proposta['dhSaiEnt'] ?? null)
            || $tpEmis !== (int) ($proposta['tpEmis'] ?? 0)
            || !hash_equals((string) ($proposta['fingerprint_xml_sem_assinatura'] ?? ''), $fingerprint)) {
            throw new \DomainException('A nova assinatura alterou a chave ou algum campo fiscal original da NFC-e.');
        }
    }

    /**
     * Lê uma identidade fiscal somente de XML com assinatura já armazenado.
     * Não gera número, cNF ou chave e não realiza chamada à SEFAZ.
     */
    private function identidadeNfceDeXmlAssinado(VendaCaixa $venda, $config, string $xml): array
    {
        if ($xml === '' || strlen($xml) > 5 * 1024 * 1024) {
            throw new \DomainException('XML fiscal original ausente ou excessivamente grande.');
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        if (!@$dom->loadXML($xml, LIBXML_NONET)) {
            throw new \DomainException('O XML fiscal original não pôde ser interpretado.');
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
        $info = $xpath->query('//nfe:infNFe')->item(0);
        if (!$info || $xpath->query('//*[local-name()="Signature" and namespace-uri()="http://www.w3.org/2000/09/xmldsig#"]')->length === 0) {
            throw new \DomainException('Não foi encontrado um XML NFC-e assinado e identificável.');
        }

        $id = (string) $info->getAttribute('Id');
        $chave = str_starts_with($id, 'NFe') ? substr($id, 3) : '';
        if (!preg_match('/^[0-9]{44}$/D', $chave) || substr($chave, 20, 2) !== '65') {
            throw new \DomainException('A identificação fiscal no XML original é inválida.');
        }

        // Confere o dígito verificador para evitar recuperar uma chave truncada
        // ou inválida de uma cópia histórica.
        $peso = 2;
        $soma = 0;
        for ($i = 42; $i >= 0; $i--) {
            $soma += (int) $chave[$i] * $peso;
            $peso = $peso === 9 ? 2 : $peso + 1;
        }
        $dv = 11 - $soma % 11;
        if ($dv >= 10) {
            $dv = 0;
        }
        if ((int) $chave[43] !== $dv) {
            throw new \DomainException('Dígito verificador da chave recuperada não confere.');
        }

        $numero = (int) $xpath->evaluate('string(./nfe:ide/nfe:nNF)', $info);
        $serie = (int) $xpath->evaluate('string(./nfe:ide/nfe:serie)', $info);
        $cnf = (string) $xpath->evaluate('string(./nfe:ide/nfe:cNF)', $info);
        $tpEmis = (int) $xpath->evaluate('string(./nfe:ide/nfe:tpEmis)', $info);
        $modelo = (int) $xpath->evaluate('string(./nfe:ide/nfe:mod)', $info);
        $cuf = (int) $xpath->evaluate('string(./nfe:ide/nfe:cUF)', $info);
        $emitenteXml = preg_replace(
            '/\D+/', '', (string) $xpath->evaluate('string(./nfe:emit/nfe:CNPJ)', $info)
        );

        if ($modelo !== 65 || $numero <= 0
            || $numero !== (int) substr($chave, 25, 9)
            || $serie !== (int) substr($chave, 22, 3)
            || !preg_match('/^[0-9]{1,8}$/D', $cnf)
            || str_pad($cnf, 8, '0', STR_PAD_LEFT) !== substr($chave, 35, 8)
            || $tpEmis !== (int) $chave[34]
            || $cuf !== (int) substr($chave, 0, 2)
            || $emitenteXml !== substr($chave, 6, 14)) {
            throw new \DomainException('Número, série, cNF, emitente ou modelo do XML divergente da chave fiscal.');
        }

        // Reaproveita todas as verificações existentes de tenant e dhEmi.
        // O clone NÃO é salvo; serve apenas para validar os metadados da fonte.
        $candidato = clone $venda;
        $candidato->chave = $chave;
        $candidato->NFcNumero = $numero;
        $candidato->signed_xml = $xml;
        $identidade = $this->identidadeOriginalNFCe($candidato, $config);

        return [
            'chave' => $identidade['chave'],
            'numero' => $numero,
            'signed_xml' => $xml,
        ];
    }

    /**
     * Recupera chave/número perdidos usando provas fiscais já persistidas.
     * Se não houver documento original assinado, NÃO presume que uma
     * venda marcada REJEITADO jamais foi enviada à SEFAZ.
     *
     * O método retorna a proposta de recuperação, sem salvar o model.
     */
    public function recuperarIdentidadeNfceRejeitada(VendaCaixa $venda, $config): array
    {
        if ($venda->estado !== 'REJEITADO' || (int) $venda->empresa_id !== (int) $this->empresa_id) {
            throw new \DomainException('Recuperação permitida somente para NFC-e rejeitada da empresa autenticada.');
        }

        $candidatos = [];
        $chavesEmLogs = [];
        $xmlDaVenda = trim((string) $venda->signed_xml);

        if ($xmlDaVenda !== '') {
            // Inconsistência no XML principal nunca é ignorada silenciosamente.
            $candidatos[] = $this->identidadeNfceDeXmlAssinado($venda, $config, $xmlDaVenda)
                + ['origem' => 'venda_caixas.signed_xml'];
        }

        try {
            $logs = FiscalEmissionLog::where('empresa_id', $this->empresa_id)
                ->where('document_type', 'NFCe')
                ->where('document_id', $venda->id)
                ->orderBy('id', 'desc')
                ->limit(51)
                ->get(['id', 'chave', 'numero', 'filial_id', 'xml_envio_path']);
        } catch (\Throwable $e) {
            Log::error('NFC-e: falha na leitura da auditoria fiscal para recuperar identificação.', [
                'empresa_id' => $this->empresa_id,
                'venda_id' => $venda->id,
                'exception' => $e,
            ]);
            if ($candidatos === []) {
                throw new \DomainException('Não foi possível consultar o histórico fiscal da nota. Emissão bloqueada.');
            }
            $logs = collect();
        }

        if ($logs->count() > 50) {
            throw new \DomainException('Histórico fiscal extenso requer conciliação manual antes da retransmissão.');
        }

        foreach ($logs as $log) {
            if ($log->filial_id !== null && (int) $log->filial_id !== (int) $venda->filial_id) {
                throw new \DomainException('Há emissão associada a outra filial. Recuperação automática bloqueada.');
            }

            $chaveLog = trim((string) $log->chave);
            if (preg_match('/^[0-9]{44}$/D', $chaveLog)) {
                $chavesEmLogs[$chaveLog] = true;
            }

            $path = (string) $log->xml_envio_path;
            if ($path === '' || !preg_match(
                '~^fiscal_emissions/[0-9]{4}/[0-9]{2}/[0-9]{2}/[A-Za-z0-9]+_envio_[A-Za-z0-9]+\.xml$~D',
                $path
            )) {
                continue;
            }

            try {
                if (!Storage::disk('local')->exists($path)) {
                    continue;
                }
                $xml = (string) Storage::disk('local')->get($path);
                $identidade = $this->identidadeNfceDeXmlAssinado($venda, $config, $xml);
                if ($chaveLog !== '' && $chaveLog !== $identidade['chave']) {
                    throw new \DomainException('Chave do registro de auditoria diverge do XML que ele referencia.');
                }
                if ((int) $log->numero > 0 && (int) $log->numero !== $identidade['numero']) {
                    throw new \DomainException('Número do registro de auditoria diverge do XML original.');
                }
                $candidatos[] = $identidade + ['origem' => 'fiscal_emission_logs#' . $log->id];
            } catch (\DomainException $e) {
                // Um vínculo contraditório com chave declarada não pode ser
                // ignorado escolhendo outro XML de uma tentativa posterior.
                if ($chaveLog !== '') {
                    throw $e;
                }
                Log::warning('NFC-e: documento antigo de auditoria não permite recuperar a chave.', [
                    'empresa_id' => $this->empresa_id,
                    'venda_id' => $venda->id,
                    'fiscal_emission_log_id' => $log->id,
                    'motivo' => $e->getMessage(),
                ]);
            } catch (\Throwable $e) {
                Log::error('NFC-e: erro ao ler XML assinado na auditoria fiscal.', [
                    'empresa_id' => $this->empresa_id,
                    'venda_id' => $venda->id,
                    'fiscal_emission_log_id' => $log->id,
                    'exception' => $e,
                ]);
                throw new \DomainException('Não foi possível validar o XML do histórico fiscal. Retransmissão bloqueada.');
            }
        }

        if ($candidatos === []) {
            throw new \DomainException(
                'Venda marcada REJEITADO sem chave/número recuperáveis de um XML assinado. '
                . 'Não é possível confirmar que a emissão nunca chegou à SEFAZ. '
                . 'Confira o histórico fiscal desta venda antes de qualquer nova emissão.'
            );
        }

        $chaves = array_unique(array_merge(
            array_column($candidatos, 'chave'), array_keys($chavesEmLogs)
        ));
        if (count($chaves) !== 1) {
            throw new \DomainException('Foram encontradas chaves fiscais diferentes para a mesma venda. Conciliação manual obrigatória.');
        }

        $escolhida = $candidatos[0];
        $chaveSalva = trim((string) $venda->chave);
        $numeroSalvo = (int) $venda->NFcNumero;
        if (($chaveSalva !== '' && !hash_equals($chaveSalva, $escolhida['chave']))
            || ($numeroSalvo > 0 && $numeroSalvo !== $escolhida['numero'])) {
            throw new \DomainException('A identidade recuperada diverge dos dados existentes na venda. Nenhum campo foi alterado.');
        }

        return $escolhida;
    }

    /**
     * A falha de comunicação na transmissão não é rejeição comprovada.
     * Antes de reenviar consulta a mesma chave na SEFAZ para evitar
     * duplicidades quando a autorização ocorreu apesar do timeout.
     */
    public function consultarSituacaoDaChave(string $chave): array
    {
        if (!preg_match('/^[0-9]{44}$/D', $chave) || substr($chave, 20, 2) !== '65') {
            throw new \DomainException('Chave de NFC-e inválida para consulta.');
        }

        $resposta = $this->tools->sefazConsultaChave($chave);
        $dados = (new Standardize($resposta))->toArray();
        if (!is_array($dados)) {
            throw new \RuntimeException('A SEFAZ não retornou uma situação fiscal interpretável.');
        }

        $codigo = (int) ($dados['cStat'] ?? 0);
        $protocolo = $dados['protNFe']['infProt'] ?? [];
        $statusProtocolo = (int) ($protocolo['cStat'] ?? 0);
        $motivo = (string) ($dados['xMotivo'] ?? $protocolo['xMotivo'] ?? 'Situação desconhecida');

        if (in_array($codigo, [100, 150], true)
            || in_array($statusProtocolo, [100, 150], true)) {
            $chaveAutorizada = (string) ($protocolo['chNFe'] ?? '');
            if (!in_array($statusProtocolo, [100, 150], true)
                || !hash_equals($chave, $chaveAutorizada)) {
                throw new \DomainException('A SEFAZ indicou autorização sem protocolo compatível com a chave consultada.');
            }

            return [
                'situacao' => 'autorizada',
                'codigo' => $statusProtocolo,
                'motivo' => $motivo,
                'resposta' => $resposta,
            ];
        }

        if ($codigo === 217) {
            return [
                'situacao' => 'nao_consta',
                'codigo' => 217,
                'motivo' => $motivo,
            ];
        }

        return [
            'situacao' => 'indeterminada',
            'codigo' => $codigo ?: $statusProtocolo,
            'motivo' => $motivo,
        ];
    }

    /**
     * Se a SEFAZ já autorizou durante um timeout, reconcilia o status
     * sem alterar número, série, chave nem contador do emitente.
     */
    public function conciliarAutorizacaoConsultada(VendaCaixa $venda, array $situacao): void
    {
        if (($situacao['situacao'] ?? null) !== 'autorizada') {
            throw new \LogicException('Reconciliação exige autorização fiscal confirmada.');
        }

        $chave = trim((string) $venda->chave);
        $numero = (int) $venda->NFcNumero;
        if (!preg_match('/^[0-9]{44}$/D', $chave)
            || $numero <= 0 || (int) substr($chave, 25, 9) !== $numero) {
            throw new \DomainException('A chave e o número originais estão inconsistentes. Nenhuma alteração foi feita.');
        }

        // O protocolo só será anexado ao XML que foi efetivamente assinado.
        try {
            $xmlAssinado = trim((string) $venda->signed_xml);
            if ($xmlAssinado !== '') {
                $xmlAutorizado = Complements::toAuthorize($xmlAssinado, (string) $situacao['resposta']);
                safe_file_put_contents(public_path('xml_nfce/' . $chave . '.xml'), $xmlAutorizado);
                $venda->path_xml = $chave . '.xml';
            }
        } catch (\Throwable $e) {
            Log::warning('NFC-e autorizada mas houve falha ao compor XML protocolado da consulta.', [
                'empresa_id' => $this->empresa_id,
                'venda_id' => $venda->id,
                'motivo' => $e->getMessage(),
            ]);
        }

        $venda->estado = 'APROVADO';
        $venda->save();
    }

    private function reservarNumeroNFCe(VendaCaixa $venda, $config): int
    {
        if ($venda->NFcNumero && $venda->NFcNumero > 0) {
            return (int) $venda->NFcNumero;
        }

        return DB::transaction(function () use ($venda, $config) {
            if ($venda->filial_id) {
                $filial = Filial::where('id', $venda->filial_id)->lockForUpdate()->firstOrFail();
                $numero = (int) (($filial->ultimo_numero_nfce ?? 0) + 1);
                $filial->ultimo_numero_nfce = $numero;
                $filial->save();
            } else {
                $configEmitente = ConfigNota::where('empresa_id', $this->empresa_id)->lockForUpdate()->firstOrFail();
                $numero = (int) (($configEmitente->ultimo_numero_nfce ?? 0) + 1);
                $configEmitente->ultimo_numero_nfce = $numero;
                $configEmitente->save();
            }

            $venda->NFcNumero = $numero;
            $venda->save();

            return (int) $numero;
        }, 3);
    }

    private function getContigencia(){
        $active = Contigencia::
        where('empresa_id', $this->empresa_id)
            ->where('status', 1)
            ->where('documento', 'NFCe')
            ->first();
        return $active;
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

    /**
     * Helper de compatibilidade: sua versão do Make NÃO TEM monta().
     * - Prioriza getXML() (que chama render() internamente se necessário)
     * - fallback para montaNFe() em algumas versões
     * - fallback final para monta() se existir (versões antigas)
     */
    private function montarXmlMake(Make $nfe): string
    {
        // 1) API atual/segura: getXML() monta via render() se necessário
        if (method_exists($nfe, 'getXML')) {
            $xml = $nfe->getXML();
            if (is_string($xml) && trim($xml) !== '') {
                return $xml;
            }
        }

        // 2) Algumas versões expõem montaNFe()
        if (method_exists($nfe, 'montaNFe')) {
            $xml = $nfe->montaNFe();
            if (is_string($xml) && trim($xml) !== '') {
                return $xml;
            }
        }

        // 3) Versões antigas expõem monta()
        if (method_exists($nfe, 'monta')) {
            $xml = $nfe->monta();
            if (is_string($xml) && trim($xml) !== '') {
                return $xml;
            }
            // se monta() retornar bool, tenta getXML
            if (method_exists($nfe, 'getXML')) {
                $xml2 = $this->finalizeReformaTributariaXml($nfe->getXML(), $venda)['xml'];
                if (is_string($xml2) && trim($xml2) !== '') {
                    return $xml2;
                }
            }
        }

        throw new \RuntimeException('Não foi possível montar o XML da NFC-e (getXML/montaNFe/monta indisponíveis ou retornaram vazio).');
    }

    public function gerarNFCe($idVenda){
        $venda = VendaCaixa::
        where('id', $idVenda)
            ->first();

        $configMatriz = ConfigNota::
        where('empresa_id', $this->empresa_id)
            ->first();
        $config = $configMatriz;

        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
            ->first();

        if($venda->filial_id != null){
            $casas_decimais = $configMatriz->casas_decimais;
            $config = Filial::findOrFail($venda->filial_id);
            $config->casas_decimais = $casas_decimais;

        }

        $identidadeOriginal = null;
        if ($venda->estado === 'REJEITADO') {
            try {
                $identidadeOriginal = $this->identidadeOriginalNFCe($venda, $config);
            } catch (\DomainException $e) {
                return ['erros_xml' => [$e->getMessage()]];
            }
        }

        $nfe = new Make();
        $stdInNFe = new \stdClass();
        $stdInNFe->versao = '4.00'; //versão do layout
        $stdInNFe->Id = null; //se o Id de 44 digitos não for passado será gerado automaticamente
        $stdInNFe->pk_nItem = null; //deixe essa variavel sempre como NULL (corrigido)

        $infNFe = $nfe->taginfNFe($stdInNFe);

        //IDE
        $stdIde = new \stdClass();
        $stdIde->cUF = $identidadeOriginal['cUF'] ?? $config->cUF;
        $stdIde->cNF = $identidadeOriginal['cNF'] ?? rand(11111111, 99999999);
        $stdIde->natOp = $config->natureza->natureza;

        // $stdIde->indPag = 1; //NÃO EXISTE MAIS NA VERSÃO 4.00 // forma de pagamento

        $stdIde->mod = 65;
        $stdIde->serie = $identidadeOriginal['serie'] ?? $config->numero_serie_nfce;
        $stdIde->nNF = $identidadeOriginal['nNF'] ?? $this->reservarNumeroNFCe($venda, $config);
        $stdIde->dhEmi = $identidadeOriginal['dhEmi'] ?? FiscalDateHelper::nowXml();
        if ($identidadeOriginal !== null) {
            // Em retransmissões, dhSaiEnt descreve o documento original e
            // não deve avançar junto com o relógio da nova tentativa.
            if ($identidadeOriginal['dhSaiEnt'] !== null) {
                $stdIde->dhSaiEnt = $identidadeOriginal['dhSaiEnt'];
            }
        } else {
            $stdIde->dhSaiEnt = FiscalDateHelper::nowXml();
        }
        $stdIde->tpNF = 1;
        $stdIde->idDest = 1;
        $stdIde->cMunFG = $config->codMun;
        $stdIde->tpImp = 4;
        $stdIde->tpEmis = $identidadeOriginal['tpEmis'] ?? 1;
        $stdIde->cDV = 0;
        $stdIde->tpAmb = (int)$config->ambiente;
        $stdIde->finNFe = 1;
        $stdIde->indFinal = 1;
        $stdIde->indPres = 1;
        if($config->ambiente == 2){
            $stdIde->indIntermed = 0;
        }
        $stdIde->procEmi = '0';
        $stdIde->verProc = app(\App\Support\FiscalProcessVersion::class)->verProc();
        //
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
                'erros_xml' => ['Inscrição estadual do emitente não configurada. Atualize o cadastro do emitente e tente novamente.'],
            ];
            }
        }

        $stdEmit->IE = $ie;
        $stdEmit->CRT = ($tributacao->regime == 0 || $tributacao->regime == 2) ? 1 : 3;

        /*
        $cnpj = str_replace(".", "", $config->cnpj);
        $cnpj = str_replace("/", "", $cnpj);
        $cnpj = str_replace("-", "", $cnpj);
        $stdEmit->CNPJ = $cnpj;
        */

        $stdEmit->CNPJ = $this->onlyDigits($config->cnpj);

        if (strlen($stdEmit->CNPJ) != 14) {
            return [
                'erros_xml' => ['CNPJ emitente inválido: ['.$stdEmit->CNPJ.']'],
            ];
        }

        $emit = $nfe->tagemit($stdEmit);

        // ENDERECO EMITENTE
        $stdEnderEmit = new \stdClass();
        $stdEnderEmit->xLgr = $config->logradouro;
        $stdEnderEmit->nro = $config->numero;
        $stdEnderEmit->xCpl = $config->complemento;
        $stdEnderEmit->xBairro = $config->bairro;
        $stdEnderEmit->cMun = $config->codMun;
        $stdEnderEmit->xMun = $config->municipio;
        $stdEnderEmit->UF = $config->UF;

        $cep = str_replace("-", "", $config->cep);
        $stdEnderEmit->CEP = $cep;
        $stdEnderEmit->cPais = $config->codPais;
        $stdEnderEmit->xPais = $config->pais;

        $fone = str_replace(" ", "", $config->fone);
        $fone = str_replace("-", "", $fone);
        $stdEnderEmit->fone = $fone;

        $enderEmit = $nfe->tagenderEmit($stdEnderEmit);

        // DESTINATARIO

        if($venda->cliente_id != null || $venda->cpf != null){
            $stdDest = new \stdClass();
            if($venda->cliente_id != null){
                $stdDest->xNome = $venda->cliente->razao_social;
                $stdDest->indIEDest = "1";

                /*
                $cnpj_cpf = str_replace(".", "", $venda->cliente->cpf_cnpj);
                $cnpj_cpf = str_replace("/", "", $cnpj_cpf);
                $cnpj_cpf = str_replace("-", "", $cnpj_cpf);
                */

                $cnpj_cpf = $this->onlyDigits($venda->cliente->cpf_cnpj);

                if(strlen($cnpj_cpf) == 14) $stdDest->CNPJ = $cnpj_cpf;
                else $stdDest->CPF = $cnpj_cpf;

                $dest = $nfe->tagdest($stdDest);

                $stdEnderDest = new \stdClass();
                $stdEnderDest->xLgr = $venda->cliente->rua;
                $stdEnderDest->nro = $venda->cliente->numero;
                $stdEnderDest->xCpl = "";
                $stdEnderDest->xBairro = $venda->cliente->bairro;
                $stdEnderDest->cMun = $venda->cliente->cidade->codigo;
                $stdEnderDest->xMun = strtoupper($venda->cliente->cidade->nome);
                $stdEnderDest->UF = $venda->cliente->cidade->uf;

                $cep = str_replace("-", "", $venda->cliente->cep);
                $stdEnderDest->CEP = $cep;
                $stdEnderDest->cPais = "1058";
                $stdEnderDest->xPais = "BRASIL";
                $enderDest = $nfe->tagenderDest($stdEnderDest);

            }
            if($venda->cpf != null){

                /*
                $cpf = str_replace(".", "", $venda->cpf);
                $cpf = str_replace("/", "", $cpf);
                $cpf = str_replace("-", "", $cpf);
                $cpf = str_replace(" ", "", $cpf);
                */

                $cpf = $this->onlyDigits($venda->cpf);

                if($venda->nome) $stdDest->xNome = $venda->nome;
                $stdDest->indIEDest = "9";
                // $stdDest->CPF = $cpf;
                if(strlen($cpf) == 14) $stdDest->CNPJ = $cpf;
                else $stdDest->CPF = $cpf;
                $dest = $nfe->tagdest($stdDest);
            }

        }


        $somaProdutos = 0;
        $somaICMS = 0;
        $somaBasePIS = 0;
        $somaBaseCOFINS = 0;
        $somaPIS = 0;
        $somaCOFINS = 0;
        $somaBaseRT = 0;
        $somaValorIBS = 0;
        $somaValorCBS = 0;
        $somaValorIS = 0;
        $reformaEstruturadaNoXml = false;
        //PRODUTOS
        $itemCont = 0;
        $somaDesconto = 0;
        $totalItens = count($venda->itens);
        $somaAcrescimo = 0;
        $VBC = 0;

        $somaFederal = 0;
        $somaEstadual = 0;
        $somaMunicipal = 0;

        $obsIbpt = "";

        foreach($venda->itens as $i){
            $itemCont++;

            $stdProd = new \stdClass();
            $stdProd->item = $itemCont;

            $cod = $this->validate_EAN13Barcode($i->produto->codBarras);

            $stdProd->cEAN = $cod ? $i->produto->codBarras : 'SEM GTIN';
            $stdProd->cEANTrib = $cod ? $i->produto->codBarras : 'SEM GTIN';
            $stdProd->cProd = $i->produto->id;
            if($i->produto->referencia != ''){
                $stdProd->cProd = $i->produto->referencia;
            }

            $stdProd->xProd = $i->produto->nome;
            if($i->produto->CST_CSOSN != '60'){

                if($i->produto->cBenef){
                    $stdProd->cBenef = $i->produto->cBenef;
                }
            }

            $ncm = $i->produto->NCM;
            $ncm = str_replace(".", "", $ncm);
            $stdProd->NCM = $ncm;
            $ibpt = IBPT::getIBPT($config->UF, $ncm);

            $stdProd->CFOP = $i->produto->CFOP_saida_estadual;

            if($config->natureza->sobrescreve_cfop == 0){
                $stdProd->CFOP = $i->produto->CFOP_saida_estadual;
            }else{
                $stdProd->CFOP = $config->natureza->CFOP_saida_estadual;
            }

            $cest = $i->produto->CEST;
            $cest = str_replace(".", "", $cest);
            $stdProd->CEST = $cest;
            $stdProd->uCom = $i->produto->unidade_venda;
            $stdProd->qCom = $i->quantidade;
            $stdProd->vUnCom = $this->format($i->valor, $config->casas_decimais);
            $stdProd->vProd = $this->format($i->quantidade * $i->valor, $config->casas_decimais);
            // $stdProd->uTrib = $i->produto->unidade_venda;
            // $stdProd->qTrib = $i->quantidade;
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
            $stdProd->vUnTrib = $this->format($i->valor, $config->casas_decimais);
            if($i->produto->quantidade_tributavel > 0){
                $stdProd->vUnTrib = $stdProd->vProd/$stdProd->qTrib;
            }
            $stdProd->indTot = 1;

            //calculo media prod

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


            if($venda->pedido_delivery_id > 0){
                $pedido = PedidoDelivery::find($venda->pedido_delivery_id);
                $somaItens = $pedido->somaItensSemFrete();
                $totalVenda = $venda->valor_total;
                if($somaItens < $totalVenda){
                    $vAcr = $totalVenda - $somaItens;

                    if($itemCont < sizeof($venda->itens)){

                        $media = (((($stdProd->vProd-$totalVenda)/$totalVenda))*100);
                        $media = 100 - ($media * -1);

                        $tempAcrescimo = ($vAcr*$media)/100;
                        $somaAcrescimo+=$tempAcrescimo;
                        if($tempAcrescimo > 0.1)
                            $stdProd->vOutro = $this->format($tempAcrescimo);
                    }else{
                        if($vAcr - $somaAcrescimo > 0.1)
                            $stdProd->vOutro = $this->format($vAcr - $somaAcrescimo);
                    }

                }
            }
            // fim calculo

            if($venda->desconto > 0.01 && $somaDesconto < $venda->desconto){
                if($itemCont < sizeof($venda->itens)){
                    $totalVenda = $venda->valor_total + $venda->desconto;

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

            $somaProdutos += $i->quantidade * $i->valor;

            $prod = $nfe->tagprod($stdProd);

            $stdImposto = new \stdClass();
            $stdImposto->item = $itemCont;

            if($i->produto->ibpt){
                $vProd = $stdProd->vProd;
                if($i->produto->origem == 1 || $i->produto->origem == 2){
                    $federal = $this->format(($vProd*($i->produto->ibpt->federal/100)), 2);
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

            $imposto = $nfe->tagimposto($stdImposto);
            if ($this->tryAttachReformaItemTag($nfe, (int)$itemCont, $i)) {
                $reformaEstruturadaNoXml = true;
            }
            $somaBaseRT += $this->getNumericFromAny($i, ['bc_ibs_cbs', 'base_ibs_cbs', 'bc_rt']);
            $somaValorIBS += $this->getNumericFromAny($i, ['valor_ibs', 'ibs_valor']);
            $somaValorCBS += $this->getNumericFromAny($i, ['valor_cbs', 'cbs_valor']);
            $somaValorIS += $this->getNumericFromAny($i, ['valor_is', 'is_valor']);

            if($config->sobrescrita_csonn_consumidor_final != ""){
                $i->produto->CST_CSOSN = $config->sobrescrita_csonn_consumidor_final;
            }

            if($tributacao->regime == 1){ // regime normal

                $stdICMS = new \stdClass();
                $stdICMS->item = $itemCont;
                $stdICMS->orig = 0;
                $stdICMS->CST = $i->produto->CST_CSOSN;
                $stdICMS->modBC = 0;
                $stdICMS->vBC = $this->format($i->valor * $i->quantidade);
                $stdICMS->pICMS = $this->format($i->produto->perc_icms);
                $stdICMS->vICMS = $stdICMS->vBC * ($stdICMS->pICMS/100);

                if($i->produto->CST_CSOSN == '500' || $i->produto->CST_CSOSN == '60'){
                    $stdICMS->pRedBCEfet = 0.00;
                    $stdICMS->vBCEfet = 0.00;
                    $stdICMS->pICMSEfet = 0.00;
                    $stdICMS->vICMSEfet = 0.00;

                }
                if($i->produto->CST_CSOSN == '61'){
                    $stdICMS->qBCMonoRet = $this->format($i->produto->valor_venda);
                    $stdICMS->vICMSMonoRet = $this->format($i->produto->adRemICMSRet*$i->quantidade);
                    $stdICMS->adRemICMSRet = $this->format($i->produto->adRemICMSRet);
                }

                if($i->produto->pRedBC > 0){
                    $tempB = 100-$i->produto->pRedBC;

                    $v = $stdProd->vProd * ($tempB/100);

                    $v += $stdProd->vFrete;
                    if($i->produto->CST_CSOSN != '61'){
                        $VBC += $stdICMS->vBC = number_format($v,2,'.','');
                        $stdICMS->pICMS = $this->format($i->produto->perc_icms);
                        $somaICMS += $stdICMS->vICMS = ($stdProd->vProd * ($tempB/100)) * ($stdICMS->pICMS/100);
                        $stdICMS->pRedBC = $this->format($i->produto->pRedBC);
                    }
                }else{
                    if($i->produto->CST_CSOSN != '61' && $i->produto->CST_CSOSN != '40' && $i->produto->CST_CSOSN != '60'){
                        $VBC += $stdProd->vProd;
                        $somaICMS += $stdICMS->vICMS;
                    }
                }

                $ICMS = $nfe->tagICMS($stdICMS);

            }else{ // regime simples

                $stdICMS = new \stdClass();

                $stdICMS->item = $itemCont;
                $stdICMS->orig = 0;
                $stdICMS->CSOSN = $i->produto->CST_CSOSN;
                $stdICMS->pCredSN = $this->format($i->produto->perc_icms);
                $stdICMS->vCredICMSSN = $this->format($i->produto->perc_icms);

                if($i->produto->CST_CSOSN == '61'){
                    $stdICMS->CST = $i->produto->CST_CSOSN;
                    $stdICMS->qBCMonoRet = $this->format($stdProd->qTrib);
                    $stdICMS->adRemICMSRet = $this->format($i->produto->adRemICMSRet, 4);
                    $stdICMS->vICMSMonoRet = $this->format($i->produto->adRemICMSRet*$stdProd->qTrib, 4);
                    $ICMS = $nfe->tagICMS($stdICMS);
                }else{
                    $ICMS = $nfe->tagICMSSN($stdICMS);
                }
                $somaICMS = 0;
            }

            $basePisCofinsItem = $this->calculaBasePisCofinsItem($stdProd, isset($stdICMS) ? (float) ($stdICMS->vBC ?? 0) : null);
            $vbcPis = $basePisCofinsItem;
            if($tributacao->exclusao_icms_pis_cofins){
                $vbcPis -= (float) ($stdICMS->vICMS ?? 0);
            }
            $vbcPis = max(0, (float) $this->format($vbcPis));
            $stdPIS = new \stdClass();
            $stdPIS->item = $itemCont;
            $cstPis = $this->normalizeTwoDigitCst($i->produto->CST_PIS);
            $calculaPis = $this->deveCalcularPisCofins($cstPis, $i->produto->perc_pis);
            $stdPIS->CST = $cstPis;
            $stdPIS->vBC = $calculaPis ? $vbcPis : 0.00;
            $somaBasePIS += (float) ($stdPIS->vBC ?? 0);
            $stdPIS->pPIS = $this->format($i->produto->perc_pis);
            $stdPIS->vPIS = $calculaPis
                ? $this->format(($vbcPis) * ($i->produto->perc_pis/100))
                : 0.00;
            $PIS = $nfe->tagPIS($stdPIS);
            $somaPIS += (float) ($stdPIS->vPIS ?? 0);

            //COFINS
            $vbcCofins = $basePisCofinsItem;
            if($tributacao->exclusao_icms_pis_cofins){
                $vbcCofins -= (float) ($stdICMS->vICMS ?? 0);
            }
            $vbcCofins = max(0, (float) $this->format($vbcCofins));
            $stdCOFINS = new \stdClass();
            $stdCOFINS->item = $itemCont;
            $cstCofins = $this->normalizeTwoDigitCst($i->produto->CST_COFINS);
            $calculaCofins = $this->deveCalcularPisCofins($cstCofins, $i->produto->perc_cofins);
            $stdCOFINS->CST = $cstCofins;
            $stdCOFINS->vBC = $calculaCofins ? $vbcCofins : 0.00;
            $somaBaseCOFINS += (float) ($stdCOFINS->vBC ?? 0);
            $stdCOFINS->pCOFINS = $this->format($i->produto->perc_cofins);
            $stdCOFINS->vCOFINS = $calculaCofins
                ? $this->format(($vbcCofins) * ($i->produto->perc_cofins/100))
                : 0.00;
            $COFINS = $nfe->tagCOFINS($stdCOFINS);
            $somaCOFINS += (float) ($stdCOFINS->vCOFINS ?? 0);

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

            $cest = $i->produto->CEST;
            $cest = str_replace(".", "", $cest);
            $stdProd->CEST = $cest;
            if(strlen($cest) > 0){
                $std = new \stdClass();
                $std->item = $itemCont;
                $std->CEST = $cest;
                $nfe->tagCEST($std);
            }
        }

        //ICMS TOTAL
        $stdICMSTot = new \stdClass();
        $stdICMSTot->vBC = $this->format($VBC);
        $stdICMSTot->vICMS = $this->format($somaICMS);
        $stdICMSTot->vICMSDeson = 0.00;
        $stdICMSTot->vBCST = 0.00;
        $stdICMSTot->vST = 0.00;
        $stdICMSTot->vProd = $this->format($somaProdutos);

        $stdICMSTot->vFrete = 0.00;

        $stdICMSTot->vSeg = 0.00;
        $stdICMSTot->vDesc = $this->format($venda->desconto);
        $stdICMSTot->vII = 0.00;
        $stdICMSTot->vIPI = 0.00;
        $stdICMSTot->vPIS = $this->format($somaPIS);
        $stdICMSTot->vCOFINS = $this->format($somaCOFINS);
        $stdICMSTot->vOutro = $this->format($venda->acrescimo);
        $stdICMSTot->vNF = $this->format($venda->valor_total);

        $ICMSTot = $nfe->tagICMSTot($stdICMSTot);
        $this->persistTotaisDefensivo($venda, [
            'total_bc_pis' => $somaBasePIS,
            'total_pis' => $somaPIS,
            'total_bc_cofins' => $somaBaseCOFINS,
            'total_cofins' => $somaCOFINS,
        ], 'venda_caixa');
        $this->persistTotaisDefensivo($venda, [
            'total_bc_ibs_cbs' => $somaBaseRT,
            'total_ibs' => $somaValorIBS,
            'total_cbs' => $somaValorCBS,
            'total_is' => $somaValorIS,
        ], 'venda_caixa');

        //TRANSPORTADORA
        $stdTransp = new \stdClass();
        $stdTransp->modFrete = 9;

        $transp = $nfe->tagtransp($stdTransp);

        $stdPag = new \stdClass();

        if ($venda->tipo_pagamento != '99') {
            if($venda->tipo_pagamento == '01'){
                $stdPag->vTroco = $this->format($venda->troco);
            }

            if($venda->troco == 0 && ($venda->valor_total != $venda->dinheiro_recebido)){
                if($venda->tipo_pagamento == '01'){
                    if($venda->dinheiro_recebido - $venda->valor_total > 0)
                        $stdPag->vTroco = $this->format($venda->dinheiro_recebido - $venda->valor_total);
                }
            }
        }

        $pag = $nfe->tagpag($stdPag);

        //Resp Tecnico
        $stdResp = new \stdClass();
        //$stdResp->CNPJ = env('RESP_CNPJ');
        $stdResp->CNPJ = $this->onlyDigits(env('RESP_CNPJ'));
        $stdResp->xContato= env('RESP_NOME');
        $stdResp->email = env('RESP_EMAIL');
        $stdResp->fone = env('RESP_FONE');

        $nfe->taginfRespTec($stdResp);

        //DETALHE PAGAMENTO
        if ($venda->tipo_pagamento != '99') {

            $stdDetPag = new \stdClass();
            $stdDetPag->tPag = $venda->tipo_pagamento;
            if($venda->tipo_pagamento == '06'){
                $stdDetPag->tPag = '05';
            }

            if($venda->tipo_pagamento == '03' || $venda->tipo_pagamento == '04' || $venda->tipo_pagamento == '17'){
                $stdDetPag->tBand = $venda->bandeira_cartao;
                if($venda->cAut_cartao != ""){
                    $stdDetPag->cAut = $venda->cAut_cartao;
                }
                if($venda->cnpj_cartao != ""){
                    /*
                    $cnpj = str_replace(".", "", $venda->cnpj_cartao);
                    $cnpj = str_replace("/", "", $cnpj);
                    $cnpj = str_replace("-", "", $cnpj);
                    $stdDetPag->CNPJ = $cnpj;
                    */
                    $stdDetPag->CNPJ = $this->onlyDigits($venda->cnpj_cartao);

                }

                $stdDetPag->tpIntegra = 2;
                $stdDetPag->vPag = $this->format($venda->valor_total);

            }else{
                if($venda->tipo_pagamento == '01'){
                    $stdDetPag->vPag = $this->format($venda->dinheiro_recebido);
                }else{
                    $stdDetPag->vPag = $this->format($venda->valor_total);
                }
            }

            $detPag = $nfe->tagdetPag($stdDetPag);
        }
        else {

            if(sizeof($venda->fatura) > 0){
                foreach($venda->fatura as $f){

                    $stdDetPag = new \stdClass();
                    $stdDetPag->tPag = $f->forma_pagamento;

                    if($f->forma_pagamento == '06'){
                        $stdDetPag->tPag = '05';
                    }

                    $stdDetPag->vPag = $this->format($f->valor);
                    if($f->forma_pagamento == '03' || $f->forma_pagamento == '04' || $f->forma_pagamento == '17'){
                        $stdDetPag->tBand = '99';
                        $stdDetPag->tpIntegra = 2;
                    }
                    if($venda->descricao_pag_outros != "" && $f->forma_pagamento == '99'){
                        $stdDetPag->xPag = $venda->descricao_pag_outros;
                    }
                    $detPag = $nfe->tagdetPag($stdDetPag);
                }
            }else{
                $stdDetPag = new \stdClass();
                $stdDetPag->tPag = $venda->tipo_pagamento;
                if($venda->descricao_pag_outros != "" && $venda->tipo_pagamento == '99'){
                    $stdDetPag->xPag = $venda->descricao_pag_outros;
                }
                $stdDetPag->vPag = $this->format($venda->valor_total);

                $detPag = $nfe->tagdetPag($stdDetPag);

            }
        }

        //INFO ADICIONAL
        $stdInfoAdic = new \stdClass();
        $obsPartes = [];
        $obsInicial = trim((string) $venda->observacao);
        if ($obsInicial !== '') $obsPartes[] = $obsInicial;

        if($this->resolveInfCplFlag($config, $configMatriz, 'exibir_deolho_imposto_inf_cpl', 1) === 1){
            $tribAprox = $this->montarTributosAproximadosObservacao($somaEstadual, $somaFederal, $somaMunicipal, $obsIbpt);
            if ($tribAprox !== '') $obsPartes[] = $tribAprox;
        }

        if($this->resolveInfCplFlag($config, $configMatriz, 'exibir_piscofins_inf_cpl', 0) === 1){
            $resumoPisCofins = $this->montarResumoPisCofinsObservacao($somaBasePIS, $somaPIS, $somaBaseCOFINS, $somaCOFINS);
            if($resumoPisCofins !== '') $obsPartes[] = $resumoPisCofins;
        }

        if($this->resolveInfCplFlag($config, $configMatriz, 'exibir_ibscbs_inf_cpl', 0) === 1){
            $resumoRt = $this->montarReformaObservacao($venda, $reformaEstruturadaNoXml);
            if($resumoRt !== '') $obsPartes[] = $resumoRt;
        }

        $obs = implode(' | ', array_values(array_filter(array_map(function ($item) {
            $item = trim((string)$item);
            return $item === '' ? null : preg_replace('/\s+/', ' ', $item);
        }, $obsPartes))));
        $stdInfoAdic->infCpl = $obs;
        $infoAdic = $nfe->taginfAdic($stdInfoAdic);

        // ✅ CORREÇÃO AQUI: sem monta() (não existe na sua versão)
        try{
            $xml = $this->montarXmlMake($nfe);

            $errs = $nfe->getErrors();
            if (!empty($errs) || empty(trim($xml))) {
                return [
                    'erros_xml' => $errs,
                    'xml' => $xml
                ];
            }

            $chaveGerada = (string) $nfe->getChave();
            if ($identidadeOriginal !== null && !hash_equals($identidadeOriginal['chave'], $chaveGerada)) {
                return [
                    'erros_xml' => ['A NFC-e gerada mudou a chave original. Transmissão bloqueada; nenhum novo número foi reservado.'],
                ];
            }

            $xmlFinal = $this->finalizeReformaTributariaXml($xml, $venda)['xml'];
            if ($identidadeOriginal !== null) {
                $xmlValidacao = new \DOMDocument('1.0', 'UTF-8');
                if (!@$xmlValidacao->loadXML($xmlFinal, LIBXML_NONET)
                    || !$xmlValidacao->getElementsByTagName('infNFe')->item(0)
                    || $xmlValidacao->getElementsByTagName('infNFe')->item(0)->getAttribute('Id') !== 'NFe' . $chaveGerada) {
                    return [
                        'erros_xml' => ['A chave do XML reconstruído diverge da chave original. Transmissão bloqueada.'],
                    ];
                }
            }

            $arr = [
                'chave' => $chaveGerada,
                'xml' => $xmlFinal,
                'nNf' => $stdIde->nNF,
                'modelo' => $nfe->getModelo(),
                'dhEmi' => $stdIde->dhEmi,
                'tpEmis' => (int) $stdIde->tpEmis
            ];
            return $arr;
        }catch(\Throwable $e){
            return [
                'erros_xml' => $nfe->getErrors(),
                'exception' => $e->getMessage()
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

    public function sign($xml){

        return $this->tools->signNFe($xml);
    }

    /**
     * Resultado técnico da última tentativa para não classificar
     * timeouts SOAP/SSL como rejeições fiscais confirmadas.
     */
    private string $resultadoUltimaTransmissao = 'indeterminado';

    public function tipoUltimoResultadoTransmissao(): string
    {
        return $this->resultadoUltimaTransmissao;
    }

    /**
     * Registra cada tentativa na auditoria fiscal já existente, com XML de
     * envio/retorno no disco privado. Falha de auditoria nunca altera uma
     * resposta fiscal nem transforma comunicação em autorização.
     */
    private function concluirLogTransmissao(
        ?EmissionLogger $logger,
        ?FiscalEmissionLog $registro,
        string $status,
        ?string $codigo,
        string $mensagem,
        ?string $retorno = null,
        array $diagnostico = []
    ): void {
        if (!$logger || !$registro) {
            return;
        }

        try {
            $logger->finish($registro, [
                'status' => $status,
                'retorno_codigo' => $codigo,
                'retorno_mensagem' => $mensagem,
            ], $retorno, array_merge([
                'cStat' => $codigo,
                'xMotivo' => $mensagem,
            ], $diagnostico));
        } catch (\Throwable $e) {
            Log::error('NFC-e: falha ao finalizar auditoria da transmissão.', [
                'empresa_id' => $this->empresa_id,
                'fiscal_emission_log_id' => $registro->id,
                'exception' => $e,
            ]);
        }
    }


    private function diagnosticoTemporalXmlNfce(string $xml): array
    {
        $dados = [
            'dhEmi_xml' => null,
            'dhSaiEnt_xml' => null,
            'tpEmis_xml' => null,
            'dhCont_xml' => null,
            'xJust_contingencia_informado' => false,
            'xml_temporal_legivel' => false,
        ];

        if ($xml === '' || strlen($xml) > 5 * 1024 * 1024) {
            return $dados;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        if (!@$dom->loadXML($xml, LIBXML_NONET)) {
            return $dados;
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
        $dhEmi = trim((string) $xpath->evaluate('string(//nfe:ide/nfe:dhEmi)'));
        $dhSaiEnt = trim((string) $xpath->evaluate('string(//nfe:ide/nfe:dhSaiEnt)'));
        $tpEmis = trim((string) $xpath->evaluate('string(//nfe:ide/nfe:tpEmis)'));
        $dhCont = trim((string) $xpath->evaluate('string(//nfe:ide/nfe:dhCont)'));
        $xJust = trim((string) $xpath->evaluate('string(//nfe:ide/nfe:xJust)'));

        return [
            'dhEmi_xml' => $dhEmi !== '' ? $dhEmi : null,
            'dhSaiEnt_xml' => $dhSaiEnt !== '' ? $dhSaiEnt : null,
            'tpEmis_xml' => $tpEmis !== '' ? (int) $tpEmis : null,
            'dhCont_xml' => $dhCont !== '' ? $dhCont : null,
            'xJust_contingencia_informado' => $xJust !== '',
            'xml_temporal_legivel' => true,
        ];
    }

    private function diferencaTemporalEmSegundos(?string $inicio, ?string $fim): ?int
    {
        if (!$inicio || !$fim) {
            return null;
        }

        try {
            return (new \DateTimeImmutable($fim))->getTimestamp()
                - (new \DateTimeImmutable($inicio))->getTimestamp();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function duracaoLegivel(?int $segundos): ?string
    {
        if ($segundos === null) {
            return null;
        }

        $sinal = $segundos < 0 ? '-' : '';
        $valor = abs($segundos);
        $horas = intdiv($valor, 3600);
        $minutos = intdiv($valor % 3600, 60);
        $restoSegundos = $valor % 60;

        return $sinal . $horas . 'h ' . $minutos . 'min ' . $restoSegundos . 's';
    }

    private function mensagemRejeicao704(string $motivo, array $diagnostico): string
    {
        $dhEmi = $diagnostico['dhEmi_xml'] ?? 'não disponível no XML';
        $dhSaiEnt = $diagnostico['dhSaiEnt_xml'] ?? 'não disponível no XML';
        $tpEmis = $diagnostico['tpEmis_xml'] ?? 'não disponível';
        $registroVenda = $diagnostico['venda_created_at_db'] ?? null;
        $dataEmissaoBanco = $diagnostico['venda_data_emissao_db'] ?? null;
        $timezone = $diagnostico['timezone_aplicacao'] ?? 'não informado';
        $inicioEnvio = $diagnostico['transmissao_iniciada_em'] ?? 'não registrado';
        $dhRecebimento = $diagnostico['dhRecbto_sefaz'] ?? null;
        // Só compara dhEmi com o recebimento efetivamente informado pela SEFAZ.
        // O início do envio é um instante local diferente e não substitui dhRecbto.
        $atraso = $dhRecebimento
            ? $this->diferencaTemporalEmSegundos($dhEmi, $dhRecebimento)
            : null;
        $tipoEmissao = (int) $tpEmis === 1
            ? '1 (normal)'
            : ((int) $tpEmis === 9 ? '9 (contingência offline)' : (string) $tpEmis);

        $mensagem = "Erro: [704] - " . $motivo . "\n\n";
        $mensagem .= "Horários, cada um em seu campo:\n";
        $mensagem .= "Registro da venda (created_at bruto no banco; app.timezone=" . $timezone . "): " . ($registroVenda ?: 'não disponível') . "\n";
        $mensagem .= "Campo venda.data_emissao no banco: " . ($dataEmissaoBanco ?: 'NULL/não preenchido') . "\n";
        $mensagem .= "Emissão fiscal do XML (dhEmi): " . $dhEmi . "\n";
        $mensagem .= "Saída/movimentação do XML (dhSaiEnt): " . $dhSaiEnt . "\n";
        $mensagem .= "Tipo de emissão no XML (tpEmis): " . $tipoEmissao . "\n";
        $mensagem .= "Início do envio pelo sistema: " . $inicioEnvio . "\n";
        if ($dhRecebimento) {
            $mensagem .= "Recebimento informado pela SEFAZ (dhRecbto): " . $dhRecebimento . "\n";
            if ($atraso !== null) {
                $mensagem .= "Diferença de dhEmi até dhRecbto: " . $this->duracaoLegivel($atraso) . "\n";
            }
        } else {
            $mensagem .= "dhRecbto não veio no protocolo; não usei o início do envio como se fosse o recebimento da SEFAZ.\n";
        }
        $mensagem .= "\nA rejeição 704 valida dhEmi em relação ao recebimento da SEFAZ. Registro da venda, data_emissao no banco, dhSaiEnt e início da transmissão são horários distintos.\n";
        if ((int) $tpEmis === 1) {
            $mensagem .= "Esta NFC-e está marcada como tpEmis=1 (normal). Uma retransmissão simples preserva dhEmi; eventual correção depende de consulta que confirme cStat=217 e confirmação explícita do operador.";
        } else {
            $mensagem .= "O XML não está marcado como emissão normal. Confira o tipo de emissão e o procedimento fiscal antes de retransmitir.";
        }

        return $mensagem;
    }

    public function transmitirNfce($signXml, $chave, $venda_id = null){
        $this->resultadoUltimaTransmissao = 'indeterminado';

        $venda = $venda_id !== null
            ? VendaCaixa::where('empresa_id', $this->empresa_id)->find($venda_id)
            : null;

        $diagnosticoXml = $this->diagnosticoTemporalXmlNfce((string) $signXml);
        $contexto = [
            'empresa_id' => $this->empresa_id,
            'filial_id' => $venda?->filial_id,
            'venda_id' => $venda_id,
            'numero_nfce' => $venda?->NFcNumero,
            'chave' => $chave,
            ...$diagnosticoXml,
            'sha256_xml_assinado' => hash('sha256', (string) $signXml),
            'timezone_aplicacao' => (string) config('app.timezone'),
            'venda_created_at_db' => $venda ? trim((string) ($venda->created_at ?? '')) ?: null : null,
            'venda_data_emissao_db' => $venda ? trim((string) ($venda->data_emissao ?? '')) ?: null : null,
        ];
        $contextoTransmissao = $contexto;
        $transmissaoRespostaEm = null;

        $logger = null;
        $registro = null;
        try {
            $logger = new EmissionLogger((int) $this->empresa_id);
            $registro = $logger->start([
                'usuario_id' => session('user_logged')['id'] ?? null,
                'filial_id' => $venda?->filial_id,
                'document_type' => 'NFCe',
                'document_id' => $venda_id,
                'document_reference' => $venda_id !== null ? (string) $venda_id : null,
                'numero' => $venda?->NFcNumero,
                'serie' => strlen((string) $chave) === 44 ? substr($chave, 22, 3) : null,
                'ambiente' => (int) ($this->config['tpAmb'] ?? 0) ?: null,
            ], (string) $signXml, $chave);
        } catch (\Throwable $e) {
            Log::error('NFC-e: falha ao iniciar registro de auditoria fiscal.', [
                ...$contexto,
                'exception' => $e,
            ]);
        }

        Log::info('NFC-e: solicitação de emissão iniciada.', array_merge($contexto, [
            'solicitacao_iniciada_em' => FiscalDateHelper::nowXml(),
        ]));

        try {
            $idLote = str_pad(100, 15, '0', STR_PAD_LEFT);

            // Mantém o comportamento de contingência do emissor existente.
            if (isset($this->tools->contingency)
                && isset($this->tools->contingency->type)
                && $this->tools->contingency->type == 'OFFLINE') {
                $this->resultadoUltimaTransmissao = 'contingencia';
                $this->concluirLogTransmissao(
                    $logger, $registro, 'contingencia', null, 'Emissão em contingência offline.'
                );
                Log::info('NFC-e: emissão em contingência offline.', $contexto);
                return null;
            }

            $transmissaoIniciadaEm = FiscalDateHelper::nowXml();
            $contextoTransmissao = array_merge($contexto, [
                'transmissao_iniciada_em' => $transmissaoIniciadaEm,
                'segundos_dhEmi_ate_inicio_transmissao' => $this->diferencaTemporalEmSegundos(
                    $diagnosticoXml['dhEmi_xml'] ?? null,
                    $transmissaoIniciadaEm
                ),
            ]);
            Log::info('NFC-e: envio ao autorizador iniciado.', $contextoTransmissao);

            $resp = $this->tools->sefazEnviaLote([$signXml], $idLote, 1);
            $transmissaoRespostaEm = FiscalDateHelper::nowXml();
            sleep(7);
            $st = new Standardize();
            $std = $st->toStd($resp);
            $codigo = isset($std->cStat) ? (string) $std->cStat : null;
            $motivo = (string) ($std->xMotivo ?? 'Resposta SEFAZ sem motivo informado.');
            $contextoRespostaLote = array_merge($contextoTransmissao, [
                'transmissao_resposta_obtida_em' => $transmissaoRespostaEm,
                'cStat_lote' => $codigo,
                'xMotivo_lote' => $motivo,
            ]);

            if ($std->cStat != 103 && $std->cStat != 104) {
                $this->resultadoUltimaTransmissao = in_array((int)$std->cStat, [105, 656], true)
                    ? 'em_processamento' : 'rejeicao_sefaz';

                $this->concluirLogTransmissao(
                    $logger, $registro,
                    $this->resultadoUltimaTransmissao === 'rejeicao_sefaz' ? 'rejeitado' : 'em_processamento',
                    $codigo, $motivo, $resp, $contextoRespostaLote
                );
                Log::log(
                    $this->resultadoUltimaTransmissao === 'rejeicao_sefaz' ? 'error' : 'warning',
                    'NFC-e: SEFAZ retornou código de não autorização.', [
                    ...$contextoRespostaLote,
                    'cStat' => $codigo,
                    'xMotivo' => $motivo,
                    'resultado' => $this->resultadoUltimaTransmissao,
                ]);
                return "Erro: [$std->cStat] - $std->xMotivo";
            }

            // No retorno síncrono, o código real da NFC-e fica em
            // protNFe/infProt; cStat do envelope pode ser apenas 104.
            // Registrar 704 e demais rejeições com o código verdadeiro.
            $protocoloRetorno = $std->protNFe->infProt ?? null;
            $codigoNota = (int) ($protocoloRetorno->cStat ?? 0);
            $motivoNota = (string) ($protocoloRetorno->xMotivo ?? $motivo);
            $dhRecebimentoSefaz = trim((string) ($protocoloRetorno->dhRecbto ?? ''));
            $contextoResposta = array_merge($contextoRespostaLote, [
                'dhRecbto_sefaz' => $dhRecebimentoSefaz !== '' ? $dhRecebimentoSefaz : null,
                'segundos_dhEmi_ate_dhRecbto_sefaz' => $this->diferencaTemporalEmSegundos(
                    $diagnosticoXml['dhEmi_xml'] ?? null,
                    $dhRecebimentoSefaz !== '' ? $dhRecebimentoSefaz : null
                ),
            ]);

            if ($codigoNota > 0 && !in_array($codigoNota, [100, 150], true)) {
                $this->resultadoUltimaTransmissao = 'rejeicao_sefaz';
                $this->concluirLogTransmissao(
                    $logger, $registro, 'rejeitado',
                    (string) $codigoNota, $motivoNota, $resp, $contextoResposta
                );
                Log::error('NFC-e: rejeição de documento informada pela SEFAZ.', [
                    ...$contextoResposta,
                    'cStat_lote' => $codigo,
                    'cStat' => $codigoNota,
                    'xMotivo' => $motivoNota,
                ]);
                if ($codigoNota === 704) {
                    return $this->mensagemRejeicao704($motivoNota, $contextoResposta);
                }
                return "Erro: [$codigoNota] - $motivoNota";
            }

            if($venda != null && $venda->recibo == null){
                $venda->recibo = $resp;
                $venda->save();
            }

            try {
                $protocolo = (string) ($std->protNFe->infProt->nProt ?? '');
                $codigoAutorizacao = (int) ($std->protNFe->infProt->cStat ?? 0);
                $chaveAutorizada = (string) ($std->protNFe->infProt->chNFe ?? '');

                // Antes de gravar no diretório de XML autorizado, confirmar
                // a chave e o protocolo realmente recebidos da SEFAZ.
                if (!in_array($codigoAutorizacao, [100, 150], true)
                    || $protocolo === '' || !hash_equals((string) $chave, $chaveAutorizada)) {
                    throw new \RuntimeException('Retorno sem autorização compatível com a chave da NFC-e.');
                }

                $xml = Complements::toAuthorize($signXml, $resp);
                safe_file_put_contents(public_path('xml_nfce/').$chave.'.xml', $xml);
                $this->resultadoUltimaTransmissao = 'autorizada';
                $this->concluirLogTransmissao(
                    $logger, $registro, 'aprovado',
                    (string)$codigoAutorizacao, 'Autorização confirmada pela SEFAZ.', $resp, $contextoResposta
                );
                Log::info('NFC-e: transmissão autorizada.', [
                    ...$contextoResposta,
                    'cStat' => $codigoAutorizacao,
                    'protocolo' => $protocolo,
                ]);
                return $protocolo;
            } catch (\Throwable $e) {
                $this->resultadoUltimaTransmissao = 'indeterminado';
                $this->concluirLogTransmissao(
                    $logger, $registro, 'erro_tecnico', $codigo, $e->getMessage(), $resp, $contextoResposta
                );
                Log::error('NFC-e: falha ao processar autorização retornada pela SEFAZ.', [
                    ...$contextoResposta,
                    'cStat' => $codigo,
                    'xMotivo' => $motivo,
                    'exception' => $e,
                ]);
                return "Erro: " . $st->toJson($resp);
            }
        } catch (\Throwable $e) {
            // SSL timeout e falhas de rede são resultado INDETERMINADO.
            $this->resultadoUltimaTransmissao = 'indeterminado';
            $this->concluirLogTransmissao(
                $logger, $registro, 'erro_tecnico', null, $e->getMessage(),
                null, $contextoTransmissao
            );
            Log::error('NFC-e: falha técnica/comunicação SOAP-SSL.', [
                ...$contextoTransmissao,
                'exception' => $e,
            ]);
            return "Erro: " . $e->getMessage();
        }
    }

    public function consultarNFCe($venda){
        try {

            $this->tools->model('65');

            $chave = $venda->chave;
            $response = $this->tools->sefazConsultaChave($chave);

            $stdCl = new Standardize($response);
            $arr = $stdCl->toArray();

            if($arr['xMotivo'] == 'Autorizado o uso da NFC-e'){
                if($venda->estado != 'APROVADO'){

                    $chaveRecebida = (string) ($arr['protNFe']['infProt']['chNFe'] ?? '');
                    $chaveOriginal = trim((string) $venda->chave);
                    $numeroOriginal = (int) $venda->NFcNumero;
                    $numeroNaChave = strlen($chaveOriginal) === 44
                        ? (int) substr($chaveOriginal, 25, 9) : 0;

                    // Consultar a situação na SEFAZ não é emitir outra nota.
                    // Jamais trocar a chave ou incrementar o número por aqui.
                    if ($chaveRecebida === '' || !hash_equals($chaveOriginal, $chaveRecebida)
                        || $numeroOriginal <= 0 || $numeroOriginal !== $numeroNaChave) {
                        Log::warning('NFC-e: retorno de consulta com identidade fiscal inconsistente.', [
                            'venda_id' => $venda->id,
                            'empresa_id' => $this->empresa_id,
                        ]);
                        return json_encode([
                            'erro' => 'Consulta bloqueada: chave ou número fiscal divergente. Nenhuma numeração foi alterada.',
                        ]);
                    }

                    $nRec = $venda->recibo;
                    $venda->estado = 'APROVADO';
                    $venda->save();
                    try{
                        $xml = Complements::toAuthorize($venda->signed_xml, $nRec);
                        safe_file_put_contents(public_path('xml_nfce/').$chave.'.xml',$xml);
                    }catch(\Exception $e){

                    }

                }
            }

            return json_encode($arr);

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function cancelarNFCe($vendaId, $justificativa){
        try {
            $venda = VendaCaixa::
            where('id', $vendaId)
                ->first();

            $chave = $venda->chave;
            $response = $this->tools->sefazConsultaChave($chave);
            sleep(1);
            $stdCl = new Standardize($response);
            $arr = $stdCl->toArray();
            $xJust = $justificativa;

            $nProt = $arr['protNFe']['infProt']['nProt'];
            sleep(1);

            $response = $this->tools->sefazCancela($chave, $xJust, $nProt);

            $stdCl = new Standardize($response);
            $std = $stdCl->toStd();
            $arr = $stdCl->toArray();
            $json = $stdCl->toJson();

            $public = env('SERVIDOR_WEB') ? 'public/' : '';
            if ($std->cStat != 128) {

            } else {
                $cStat = $std->retEvento->infEvento->cStat;
                if ($cStat == '101' || $cStat == '135' || $cStat == '155' ) {
                    $xml = Complements::toAuthorize($this->tools->lastRequest, $response);
                    safe_file_put_contents(public_path('xml_nfce_cancelada/').$chave.'.xml',$xml);

                    return $arr;
                } else {
                    return $arr;
                }
            }

        } catch (\Exception $e) {
            return
                [
                    'mensagem' => $e->getMessage(),
                    'erro' => true
                ];
        }
    }

    public function format($number, $dec = 2){
        return number_format((float) $number, $dec, ".", "");
    }

    public function inutilizar($config, $nInicio, $nFinal, $justificativa, $nSerie){
        try{

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

}
