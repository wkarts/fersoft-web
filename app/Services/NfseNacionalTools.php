<?php

namespace App\Services;

use App\Services\Nfse\NfseEmissorInterface;
use NFePHP\Common\Certificate;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class NfseNacionalTools implements NfseEmissorInterface
{
    protected $config;
    protected $certificate;
    protected $chave = null;
    protected $protocolo = null;
    protected $xmlNfseAutorizada = null;

    public function __construct($configJson, Certificate $certificate)
    {
        $this->config = is_string($configJson) ? json_decode($configJson, true) : $configJson;
        $this->certificate = $certificate;
    }

    /**
     * Validação prévia de regras de negócio antes de assinar/transmitir
     */
    public function validarDps(array $dpsData): array
    {
        $erros = [];
        $infDPS = $dpsData['infDPS'] ?? [];
        $serv   = $infDPS['serv'] ?? [];
        $cTribNac = $serv['cServ']['cTribNac'] ?? null;

        $vServ = (float)($infDPS['valores']['vServPrest']['vServ'] ?? 0);
        $vDescIncond = (float)($infDPS['valores']['trib']['vDescIncond'] ?? 0);

        // Regra 260: Grupo de obra é obrigatório para serviços de construção civil
        $constructionCodes = [
            '070201', '070202', '070401', '070501', '070502',
            '070601', '070602', '070701', '070801', '071701', '071901'
        ];
        if (in_array($cTribNac, $constructionCodes) && empty($serv['obra']['cObra'])) {
            $erros[] = "O grupo de informações de obra (cObra) é obrigatório para o serviço de construção civil informado ({$cTribNac}).";
        }

        // Regra 324/542: cNBS é obrigatório quando informadas tags de IBS/CBS
        if (isset($infDPS['valores']['trib']['tribFed']['pIBS']) && empty($serv['cServ']['cNBS'])) {
            $erros[] = "O código NBS (cNBS) é obrigatório quando o grupo de tributação IBS/CBS for informado.";
        }

        // Regra 307: O desconto incondicionado deve ser menor que o valor do serviço
        if ($vDescIncond > 0 && $vDescIncond >= $vServ) {
            $erros[] = "O valor do desconto incondicionado deve ser estritamente menor que o valor do serviço.";
        }

        return $erros;
    }

    /**
     * Emissão da NFS-e no Portal Nacional
     */
    public function emitir(array $dpsData)
    {
        // 1. Executa a validação prévia de regras de negócio
        $errosValidacao = $this->validarDps($dpsData);
        if (!empty($errosValidacao)) {
            throw new \Exception("Validação Interna DPS: " . implode(' | ', $errosValidacao));
        }

        $infDPS = $dpsData['infDPS'] ?? [];

        // Trata os documentos e identificadores
        $cnpjPrestador = preg_replace('/\D/', '', $this->config['cnpj'] ?? ($infDPS['prest']['CNPJ'] ?? ''));
        $tipoInscricao = strlen($cnpjPrestador) > 11 ? '2' : '1'; // 1 = CPF, 2 = CNPJ
        $cnpjFormatado = str_pad($cnpjPrestador, 14, '0', STR_PAD_LEFT);

        // Garante o código IBGE com 7 dígitos (ex: Lauro de Freitas 2919207)
        $cLocEmi = str_pad(preg_replace('/\D/', '', $infDPS['cLocEmi'] ?? ($this->config['codMun'] ?? '2919207')), 7, '0', STR_PAD_LEFT);

        // Pega série e número
        $serieNota  = $this->config['serie_dps'] ?? ($infDPS['serie'] ?? '1');
        $numeroNota = $this->config['numero_dps'] ?? ($infDPS['nDPS'] ?? '1');

        $serieFormatada  = str_pad(preg_replace('/\D/', '', (string)$serieNota), 5, '0', STR_PAD_LEFT);
        $numeroFormatado = str_pad(preg_replace('/\D/', '', (string)$numeroNota), 15, '0', STR_PAD_LEFT);

        // PADRÃO EXATO TSIdDPS: 'DPS' (3) + IBGE (7) + Tipo (1) + CNPJ (14) + Série (5) + Número (15) = 45 caracteres
        $this->chave = 'DPS' . $cLocEmi . $tipoInscricao . $cnpjFormatado . $serieFormatada . $numeroFormatado;

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><DPS xmlns="http://www.sped.fazenda.gov.br/nfse"/>');
        $xml->addAttribute('versao', '1.01');

        $nodeInf = $xml->addChild('infDPS');
        $nodeInf->addAttribute('Id', $this->chave);

        // Cabeçalho da DPS (ordem exigida pelo XSD Nacional)
        $nodeInf->addChild('tpAmb', (string) ($this->config['tpAmb'] ?? 1));
        $nodeInf->addChild('dhEmi', $infDPS['dhEmi'] ?? date('Y-m-d\TH:i:sP'));
        $nodeInf->addChild('verAplic', $infDPS['verAplic'] ?? 'FersoftERP_1.0');
        $nodeInf->addChild('serie', (string)$serieNota);
        $nodeInf->addChild('nDPS', (string)$numeroNota);
        $nodeInf->addChild('dCompet', $infDPS['dCompet'] ?? date('Y-m-d'));
        $nodeInf->addChild('tpEmit', (string) ($infDPS['tpEmit'] ?? 1));
        $nodeInf->addChild('cLocEmi', $cLocEmi);

        // =========================================================================
        // 1. PRESTADOR (CORREÇÃO DO ERRO E1235)
        // =========================================================================
        $prest = $nodeInf->addChild('prest');
        if (strlen($cnpjPrestador) > 11) {
            $prest->addChild('CNPJ', $cnpjPrestador);
        } else {
            $prest->addChild('CPF', $cnpjPrestador);
        }

        // Inscrição Municipal (opcional, só adiciona se preenchida e apenas números)
        $imPrestador = preg_replace('/\D/', '', $infDPS['prest']['IM'] ?? ($this->config['inscricao_municipal'] ?? $this->config['im'] ?? ''));
        if (!empty($imPrestador)) {
            $prest->addChild('IM', $imPrestador);
        }

        // Grupo regTrib é OBRIGATÓRIO no XSD da DPS Nacional
        $regTribData = $infDPS['prest']['regTrib'] ?? [];
        $opSimpNac   = (int) ($regTribData['opSimpNac'] ?? ($this->config['opcao_simples_nacional'] ?? 1)); // 1=Normal, 2=Simples, 3=MEI
        $regApTribSN = (int) ($regTribData['regApTribSN'] ?? ($this->config['regime_apuracao_sn'] ?? 1));
        $regEspTrib  = (int) ($regTribData['regEspTrib'] ?? ($this->config['regime_especial_tributacao'] ?? 0));

        $regTrib = $prest->addChild('regTrib');
        $regTrib->addChild('opSimpNac', (string) $opSimpNac);

        // Se optante pelo Simples Nacional ou MEI, regApTribSN deve ser enviado
        if (in_array($opSimpNac, [2, 3])) {
            $regTrib->addChild('regApTribSN', (string) ($regApTribSN ?: 1));
        }
        $regTrib->addChild('regEspTrib', (string) $regEspTrib);

        // =========================================================================
        // 2. TOMADOR
        // =========================================================================
        if (isset($infDPS['toma'])) {
            $toma = $nodeInf->addChild('toma');
            $docTomador = preg_replace('/\D/', '', $infDPS['toma']['CNPJ'] ?? ($infDPS['toma']['CPF'] ?? ''));
            if (strlen($docTomador) > 11) {
                $toma->addChild('CNPJ', $docTomador);
            } elseif (!empty($docTomador)) {
                $toma->addChild('CPF', $docTomador);
            }

            $toma->addChild('xNome', htmlspecialchars($infDPS['toma']['xNome'] ?? 'CONSUMIDOR', ENT_QUOTES, 'UTF-8'));

            if (isset($infDPS['toma']['ender'])) {
                $end = $toma->addChild('end');

                $cMunTomador = preg_replace('/\D/', '', $infDPS['toma']['ender']['cMun'] ?? $cLocEmi);
                $cepTomador  = str_pad(preg_replace('/\D/', '', $infDPS['toma']['ender']['CEP'] ?? '40000000'), 8, '0', STR_PAD_LEFT);

                $endNac = $end->addChild('endNac');
                $endNac->addChild('cMun', $cMunTomador);
                $endNac->addChild('CEP', $cepTomador);

                $end->addChild('xLgr', htmlspecialchars($infDPS['toma']['ender']['xLgr'] ?? 'Logradouro', ENT_QUOTES, 'UTF-8'));
                $end->addChild('nro', htmlspecialchars($infDPS['toma']['ender']['nro'] ?? 'S/N', ENT_QUOTES, 'UTF-8'));
                $end->addChild('xBairro', htmlspecialchars($infDPS['toma']['ender']['xBairro'] ?? 'Centro', ENT_QUOTES, 'UTF-8'));
            }
        }

        // =========================================================================
        // 3. SERVIÇO E OBRA
        // =========================================================================
        if (isset($infDPS['serv'])) {
            $serv = $nodeInf->addChild('serv');
            if (isset($infDPS['serv']['locPrest'])) {
                $loc = $serv->addChild('locPrest');
                $cLocPrest = preg_replace('/\D/', '', $infDPS['serv']['locPrest']['cLocPrest'] ?? $cLocEmi);
                $loc->addChild('cLocPrestacao', str_pad($cLocPrest, 7, '0', STR_PAD_LEFT));
            }

            if (isset($infDPS['serv']['cServ'])) {
                $cServ = $serv->addChild('cServ');
                $cTribNac = preg_replace('/\D/', '', $infDPS['serv']['cServ']['cTribNac'] ?? '140501');
                $cServ->addChild('cTribNac', str_pad($cTribNac, 6, '0', STR_PAD_LEFT));
                $cServ->addChild('xDescServ', htmlspecialchars($infDPS['serv']['cServ']['xDescServ'] ?? 'PRESTACAO DE SERVICO', ENT_QUOTES, 'UTF-8'));

                $cNBS = preg_replace('/\D/', '', $infDPS['serv']['cServ']['cNBS'] ?? '101010100');
                $cServ->addChild('cNBS', str_pad($cNBS, 9, '0', STR_PAD_LEFT));
            }

            if (!empty($infDPS['serv']['obra']['cObra'])) {
                $obra = $serv->addChild('obra');
                $obra->addChild('cObra', $infDPS['serv']['obra']['cObra']);
            }
        }

        // =========================================================================
        // 4. VALORES E IMPOSTOS
        // =========================================================================
        if (isset($infDPS['valores'])) {
            $valores = $nodeInf->addChild('valores');
            if (isset($infDPS['valores']['vServPrest'])) {
                $vServ = $valores->addChild('vServPrest');
                $vServFloat = (float)($infDPS['valores']['vServPrest']['vServ'] ?? 0);
                $vServ->addChild('vServ', number_format($vServFloat, 2, '.', ''));
            }

            // Descontos e deduções (se informados)
            if (isset($infDPS['valores']['trib']['vDescIncond']) && (float)$infDPS['valores']['trib']['vDescIncond'] > 0) {
                $valores->addChild('vDescIncond', number_format((float)$infDPS['valores']['trib']['vDescIncond'], 2, '.', ''));
            }
            if (isset($infDPS['valores']['trib']['vDed']) && (float)$infDPS['valores']['trib']['vDed'] > 0) {
                $valores->addChild('vDed', number_format((float)$infDPS['valores']['trib']['vDed'], 2, '.', ''));
            }

            if (isset($infDPS['valores']['trib'])) {
                $trib = $valores->addChild('trib');

                // ISSQN Municipal
                // Correção E0617: Só envia pAliq se for optante do Simples Nacional
                if (isset($infDPS['valores']['trib']['tribMun'])) {
                    $tribMun = $trib->addChild('tribMun');
                    $tribMun->addChild('tribISSQN', (string) ($infDPS['valores']['trib']['tribMun']['tribISSQN'] ?? 1));
                    $tribMun->addChild('tpRetISSQN', (string) ($infDPS['valores']['trib']['tribMun']['tpRetISSQN'] ?? 1));

                    // Regra E0617: opSimpNac = 1 (Normal) não pode ter pAliq em município ativo
                    if ((int)$opSimpNac !== 1 && isset($infDPS['valores']['trib']['tribMun']['pAliq'])) {
                        $tribMun->addChild('pAliq', number_format((float)($infDPS['valores']['trib']['tribMun']['pAliq']), 2, '.', ''));
                    }
                }

                // Tributos Federais e Retenções
                if (isset($infDPS['valores']['trib']['tribFed'])) {
                    $tribFedData = $infDPS['valores']['trib']['tribFed'];
                    $tribFed = $trib->addChild('tribFed');

                    // PIS / COFINS
                    $piscofins = $tribFed->addChild('piscofins');
                    $cstPis = $tribFedData['piscofins']['CST'] ?? ($opSimpNac === 1 ? '01' : '07');
                    $piscofins->addChild('CST', (string) $cstPis);

                    // Retenções na fonte (INSS, IRRF, CSLL)
                    if (isset($tribFedData['vRetCP']) && (float)$tribFedData['vRetCP'] > 0) {
                        $tribFed->addChild('vRetCP', number_format((float)$tribFedData['vRetCP'], 2, '.', ''));
                    }
                    if (isset($tribFedData['vRetIRRF']) && (float)$tribFedData['vRetIRRF'] > 0) {
                        $tribFed->addChild('vRetIRRF', number_format((float)$tribFedData['vRetIRRF'], 2, '.', ''));
                    }
                    if (isset($tribFedData['vRetCSLL']) && (float)$tribFedData['vRetCSLL'] > 0) {
                        $tribFed->addChild('vRetCSLL', number_format((float)$tribFedData['vRetCSLL'], 2, '.', ''));
                    }
                }

                // Totalizador informativo de tributos (Lei da Transparência)
                $totTrib = $trib->addChild('totTrib');
                $vTotTrib = $totTrib->addChild('vTotTrib');
                $vTotTrib->addChild('vTotTribFed', '0.00');
                $vTotTrib->addChild('vTotTribEst', '0.00');
                $vTotTrib->addChild('vTotTribMun', '0.00');
            }
        }

        // =========================================================================
        // 5. GRUPO IBS/CBS (REFORMA TRIBUTÁRIA)
        // =========================================================================
        if (!empty($infDPS['ibscbs']['cIndOp'])) {
            $ibscbs = $nodeInf->addChild('IBSCBS');
            $ibscbs->addChild('finNFSe', (string) ($infDPS['ibscbs']['finNFSe'] ?? '0'));
            $ibscbs->addChild('cIndOp', (string) $infDPS['ibscbs']['cIndOp']);
            $ibscbs->addChild('indDest', (string) ($infDPS['ibscbs']['indDest'] ?? '1'));

            $ibscbsValores = $ibscbs->addChild('valores');
            $ibscbsTrib = $ibscbsValores->addChild('trib');
            $ibscbsG = $ibscbsTrib->addChild('gIBSCBS');
            $ibscbsG->addChild('CST', str_pad($infDPS['ibscbs']['CST'] ?? '000', 3, '0', STR_PAD_LEFT));
            $ibscbsG->addChild('cClassTrib', str_pad($infDPS['ibscbs']['cClassTrib'] ?? '000001', 6, '0', STR_PAD_LEFT));
        }

        Log::info("XML DPS Gerado (Antes de Assinar): " . $xml->asXML());

        // 6. ASSINATURA E TRANSMISSÃO
        $xmlAssinado = $this->assinarXml($xml->asXML(), 'infDPS');
        $resSefaz = $this->transmitirParaSefaz($xmlAssinado);

        return [
            'numero_nfse'    => $resSefaz['numeroNfse'] ?? ($resSefaz['numeroNFSe'] ?? $numeroNota),
            'chave'          => $this->chave,
            'protocolo'      => $this->protocolo,
            'xml_retorno'    => $this->xmlNfseAutorizada ?? $xmlAssinado,
            'resposta_sefaz' => $resSefaz
        ];
    }

    /**
     * Transmite o Evento 101101 de Cancelamento
     */
    public function cancelar(string $chave, string $motivo = 'Cancelamento')
    {
        $codigoMotivo = '1';
        $cnpj = preg_replace('/\D/', '', $this->config['cnpj'] ?? '');
        $idEvento = "PRE" . $chave . "101101";

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><pedRegEvento xmlns="http://www.sped.fazenda.gov.br/nfse"/>');
        $xml->addAttribute('versao', '1.00');

        $inf = $xml->addChild('infPedReg');
        $inf->addAttribute('Id', $idEvento);
        $inf->addChild('tpAmb', $this->config['tpAmb'] ?? 1);
        $inf->addChild('verAplic', 'FersoftERP_1.0');
        $inf->addChild('dhEvento', date('Y-m-d\TH:i:sP'));
        $inf->addChild('CNPJAutor', $cnpj);
        $inf->addChild('chNFSe', $chave);

        $e101101 = $inf->addChild('e101101');
        $e101101->addChild('xDesc', 'Cancelamento de NFS-e');
        $e101101->addChild('cMotivo', $codigoMotivo);
        $e101101->addChild('xMotivo', htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8'));

        $xmlAssinado = $this->assinarXml($xml->asXML(), 'infPedReg');

        return $this->transmitirEventoParaSefaz($chave, $xmlAssinado);
    }

    protected function transmitirParaSefaz($xmlSigned)
    {
        $tpAmb = $this->config['tpAmb'] ?? 1;
        $urlApi = ($tpAmb == 1)
            ? 'https://sefin.nfse.gov.br/SefinNacional/nfse'
            : 'https://sefin.homologacao.nfse.gov.br/SefinNacional/nfse';

        return $this->executarPostSefaz($urlApi, ['dpsXmlGZipB64' => base64_encode(gzencode($xmlSigned))]);
    }

    protected function transmitirEventoParaSefaz(string $chaveNfse, string $xmlSigned)
    {
        $tpAmb = $this->config['tpAmb'] ?? 1;
        $urlApi = ($tpAmb == 1)
            ? "https://sefin.nfse.gov.br/SefinNacional/nfse/{$chaveNfse}/eventos"
            : "https://sefin.homologacao.nfse.gov.br/SefinNacional/nfse/{$chaveNfse}/eventos";

        return $this->executarPostSefaz($urlApi, ['pedidoRegistroEventoXmlGZipB64' => base64_encode(gzencode($xmlSigned))]);
    }

    protected function executarPostSefaz(string $urlApi, array $jsonPayload)
    {
        $certPem = $this->certificate->publicKey ?? $this->certificate->certificate ?? '';
        $keyPem  = $this->certificate->privateKey ?? '';

        $tempCert = tempnam(sys_get_temp_dir(), 'cert_');
        $tempKey  = tempnam(sys_get_temp_dir(), 'key_');

        file_put_contents($tempCert, $certPem);
        file_put_contents($tempKey, $keyPem);

        try {
            $client = new Client([
                'timeout'     => 30,
                'verify'      => false,
                'cert'        => $tempCert,
                'ssl_key'     => $tempKey,
                'http_errors' => false,
            ]);

            $response = $client->post($urlApi, [
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept'       => 'application/json'
                ],
                'json' => $jsonPayload
            ]);

            $statusCode = $response->getStatusCode();
            $resBody    = (string) $response->getBody();

            Log::info("NFS-e SefinNacional Retorno [HTTP {$statusCode}]: " . $resBody);

            $resData = json_decode($resBody, true);

            if ($statusCode == 200 || $statusCode == 201) {
                $this->chave = $resData['chaveAcesso'] ?? $this->chave;
                $this->protocolo = $resData['idDps'] ?? ('PR' . date('YmdHis'));

                if (!empty($resData['nfseXmlGZipB64'])) {
                    $this->xmlNfseAutorizada = gzdecode(base64_decode($resData['nfseXmlGZipB64']));
                }

                return $resData;
            } else {
                // TRATAMENTO ROBUSTO DE ERRO COM INICIAIS MAIÚSCULAS OU MINÚSCULAS
                $erros = [];
                if (!empty($resData['erros'])) {
                    foreach ($resData['erros'] as $err) {
                        $cod  = $err['Codigo'] ?? $err['codigo'] ?? 'ERRO';
                        $desc = $err['Descricao'] ?? $err['descricao'] ?? ($err['mensagem'] ?? '');
                        $comp = $err['Complemento'] ?? $err['complemento'] ?? '';

                        $linhaErro = "[{$cod}] {$desc}";
                        if (!empty($comp)) {
                            $linhaErro .= " ({$comp})";
                        }
                        $erros[] = $linhaErro;
                    }
                }

                $motivo = !empty($erros) ? implode(' | ', $erros) : ($resData['erro']['descricao'] ?? strip_tags($resBody));

                throw new \Exception("Rejeição SEFAZ (HTTP {$statusCode}): " . trim($motivo));
            }

        } finally {
            @unlink($tempCert);
            @unlink($tempKey);
        }
    }

    protected function assinarXml($xmlString, $tagName = 'infDPS')
    {
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        $doc->loadXML($xmlString);

        $nodeInf = $doc->getElementsByTagName($tagName)->item(0);
        if ($nodeInf && $nodeInf->hasAttribute('Id')) {
            $nodeInf->setIdAttribute('Id', true);
        }

        $idNode = $nodeInf->getAttribute('Id');

        $objDSig = new XMLSecurityDSig('');
        $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

        $objDSig->addReference(
            $nodeInf,
            XMLSecurityDSig::SHA1,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N],
            [
                'force_uri' => true,
                'uri'       => '#' . $idNode,
                'overwrite' => false
            ]
        );

        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);

        $privateKeyPem = $this->certificate->privateKey ?? '';
        $publicKeyPem  = $this->certificate->publicKey ?? $this->certificate->certificate ?? '';

        $objKey->loadKey($privateKeyPem);

        $objDSig->sign($objKey);
        $objDSig->add509Cert($publicKeyPem);

        $rootTag = $doc->getElementsByTagName('DPS')->item(0);
        if (!$rootTag) {
            $rootTag = $doc->documentElement;
        }
        $objDSig->appendSignature($rootTag);

        return $doc->saveXML();
    }

    public function getChave()
    {
        return $this->chave;
    }

    public function getProtocolo()
    {
        return $this->protocolo;
    }
}
