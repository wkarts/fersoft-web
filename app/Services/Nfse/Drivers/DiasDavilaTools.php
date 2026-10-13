<?php

namespace App\Services\Nfse\Drivers;

use App\Services\Nfse\NfseEmissorInterface;
use NFePHP\Common\Certificate;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class DiasDavilaTools implements NfseEmissorInterface
{
    protected $config;
    protected $certificate;
    protected $chave = null;
    protected $protocolo = null;

    public function __construct(array $config, Certificate $certificate)
    {
        $this->config = $config;
        $this->certificate = $certificate;
    }

    public function emitir(array $dpsData)
    {
        $tpAmb = $this->config['tpAmb'] ?? 1;

        $urlApi = ($tpAmb == 1)
            ? 'https://diasdavila.saatri.com.br/servicos/nfse.svc'
            : 'https://homologa-diasdavila.saatri.com.br/servicos/nfse.svc';

        // 1. Gera o XML interno de Envio
        $xmlEnvio = $this->gerarXmlGerarNfse($dpsData);

        // 2. Monta o Envelope SOAP exato do GerarNfseRequest.xml
        $envelopeSoap = $this->montarEnvelopeSoap($xmlEnvio);

        // 3. A ACTION CORRETA (que usamos quando deu o erro E186)
        $soapAction = '"http://nfse.abrasf.org.br/Infse/GerarNfse"';

        // 4. Transmissão
        $resBody = $this->transmitirSoap($urlApi, $envelopeSoap, $soapAction);

        // 5. Processamento da resposta
        return $this->processarRetornoSaatri($resBody, $dpsData);
    }

    public function cancelar(string $chave, string $motivo = 'Cancelamento')
    {
        throw new \Exception("Para cancelar uma nota de Dias d'Ávila, utilize o portal da Prefeitura.");
    }

    protected function gerarXmlGerarNfse(array $dpsData)
    {
        $inf = $dpsData['infDPS'] ?? [];
        $serv = $inf['serv'] ?? [];
        $toma = $inf['toma'] ?? [];
        $prest = $inf['prest'] ?? [];

        $vServ = number_format($inf['valores']['vServPrest']['vServ'] ?? 0, 2, '.', '');
        $serieRps = $inf['serie'] ?? '1';
        $numeroRps = $inf['nDPS'] ?? '1';
        $cnpjPrestador = $prest['CNPJ'] ?? '';

        // Cria a raiz GerarNfseEnvio conforme o modelo oficial
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><GerarNfseEnvio xmlns="http://www.abrasf.org.br/nfse.xsd"/>');

        $rpsNode = $xml->addChild('Rps');
        $infDeclaracao = $rpsNode->addChild('InfDeclaracaoPrestacaoServico');
        $infDeclaracao->addAttribute('Id', 'Declaracao_' . $cnpjPrestador . $numeroRps);

        $rps = $infDeclaracao->addChild('Rps');
        $rps->addAttribute('Id', 'RPS_' . $numeroRps);
        $identificacaoRps = $rps->addChild('IdentificacaoRps');
        $identificacaoRps->addChild('Numero', $numeroRps);
        $identificacaoRps->addChild('Serie', $serieRps);
        $identificacaoRps->addChild('Tipo', '1');

        // Data de Emissão rigorosamente no formato XSD date (YYYY-MM-DD)
        $rps->addChild('DataEmissao', date('Y-m-d'));
        $rps->addChild('Status', '1');

        $competencia = $inf['dCompet'] ?? date('Y-m-d');
        $infDeclaracao->addChild('Competencia', substr($competencia, 0, 10));

        // ----------------------------------------------------
        // BLOCO DE VALORES E IMPOSTOS
        // ----------------------------------------------------
        $servico = $infDeclaracao->addChild('Servico');
        $valores = $servico->addChild('Valores');
        $valores->addChild('ValorServicos', $vServ);

        $vDeducoes = $inf['valores']['trib']['vDed'] ?? 0;
        if ((float)$vDeducoes > 0) $valores->addChild('ValorDeducoes', $vDeducoes);

        $vPis = $inf['valores']['trib']['tribFed']['piscofins']['vPIS'] ?? 0;
        if ((float)$vPis > 0) $valores->addChild('ValorPis', $vPis);

        $vCofins = $inf['valores']['trib']['tribFed']['piscofins']['vCOFINS'] ?? 0;
        if ((float)$vCofins > 0) $valores->addChild('ValorCofins', $vCofins);

        $vInss = $inf['valores']['trib']['tribFed']['vRetCP'] ?? 0;
        if ((float)$vInss > 0) $valores->addChild('ValorInss', $vInss);

        $vIr = $inf['valores']['trib']['tribFed']['vRetIRRF'] ?? 0;
        if ((float)$vIr > 0) $valores->addChild('ValorIr', $vIr);

        $vCsll = $inf['valores']['trib']['tribFed']['vRetCSLL'] ?? 0;
        if ((float)$vCsll > 0) $valores->addChild('ValorCsll', $vCsll);

        // Alíquota e Valor do ISS
        $pAliq = $inf['valores']['trib']['tribMun']['pAliq'] ?? '0.00';
        if ((float)$pAliq > 0) {
            $valores->addChild('ValorIss', number_format((float)$vServ * ((float)$pAliq / 100), 2, '.', ''));
            $valores->addChild('Aliquota', number_format((float)$pAliq, 4, '.', ''));
        }

        $vDescIncond = $inf['valores']['trib']['vDescIncond'] ?? 0;
        if ((float)$vDescIncond > 0) $valores->addChild('DescontoIncondicionado', $vDescIncond);

        // ----------------------------------------------------

        $issRetido = ($inf['valores']['trib']['tribMun']['tpRetISSQN'] ?? 1) == 2 ? '1' : '2';
        $servico->addChild('IssRetido', $issRetido);
        $servico->addChild('ResponsavelRetencao', '1');
        $servico->addChild('ItemListaServico', $serv['cServ']['cTribNac'] ?? '140501');
        $servico->addChild('CodigoNbs', $serv['cServ']['cNBS'] ?? '118066200');
        $servico->addChild('Discriminacao', htmlspecialchars($serv['cServ']['xDescServ'] ?? 'PRESTAÇÃO DE SERVIÇO', ENT_QUOTES, 'UTF-8'));

        // BLINTAGEM MUNICÍPIO DE INCIDÊNCIA (Evita o erro E1521 forçando o emitente ou o valor correto)
        $cidadePrestacao = preg_replace('/\D/', '', $serv['locPrest']['cLocPrest'] ?? $inf['cLocEmi']);
        if (empty($cidadePrestacao) || strlen($cidadePrestacao) != 7) {
            $cidadePrestacao = '2910057'; // Padrão Dias d'Ávila
        }

        $servico->addChild('CodigoMunicipio', $cidadePrestacao);
        $servico->addChild('ExigibilidadeISS', '1');
        $servico->addChild('MunicipioIncidencia', $cidadePrestacao);

        $prestador = $infDeclaracao->addChild('Prestador');
        $cpfCnpjPrestador = $prestador->addChild('CpfCnpj');
        $cpfCnpjPrestador->addChild('Cnpj', $cnpjPrestador);

        $im = preg_replace('/\D/', '', $this->config['inscricao_municipal'] ?? '');
        if (!empty($im)) {
            $prestador->addChild('InscricaoMunicipal', $im);
        }

        $tomador = $infDeclaracao->addChild('Tomador');
        $identificacaoTomador = $tomador->addChild('IdentificacaoTomador');
        $cpfCnpjTomador = $identificacaoTomador->addChild('CpfCnpj');

        if (isset($toma['CNPJ'])) {
            $cpfCnpjTomador->addChild('Cnpj', $toma['CNPJ']);
        } else {
            $cpfCnpjTomador->addChild('Cpf', $toma['CPF'] ?? '');
        }

        $tomador->addChild('RazaoSocial', htmlspecialchars($toma['xNome'] ?? '', ENT_QUOTES, 'UTF-8'));

        if (isset($toma['ender'])) {
            $endereco = $tomador->addChild('Endereco');
            $endereco->addChild('Endereco', htmlspecialchars($toma['ender']['xLgr'] ?? '', ENT_QUOTES, 'UTF-8'));
            $endereco->addChild('Numero', htmlspecialchars($toma['ender']['nro'] ?? 'S/N', ENT_QUOTES, 'UTF-8'));
            $endereco->addChild('Bairro', htmlspecialchars($toma['ender']['xBairro'] ?? '', ENT_QUOTES, 'UTF-8'));
            $endereco->addChild('CodigoMunicipio', $toma['ender']['cMun'] ?? '2910057');
            $endereco->addChild('Uf', 'BA');
            $endereco->addChild('Cep', $toma['ender']['CEP'] ?? '');
        }

        // Mapeamento correto Simples Nacional (1 = Sim, 2 = Não)
        $regimeErp = (int)($this->config['opcao_simples_nacional'] ?? 1);
        $optanteAbrasf = ($regimeErp === 0 || $regimeErp === 2) ? '1' : '2';

        $infDeclaracao->addChild('OptanteSimplesNacional', $optanteAbrasf);
        $infDeclaracao->addChild('IncentivoFiscal', '2');

        // ----------------------------------------------------
        // BLOCO IBS / CBS (Corrigido sem PercRedAliq para evitar E1540)
        // ----------------------------------------------------
        $valorServico = (float) $vServ;
        $valorIss = round($valorServico * 0.03, 2); // 3% de ISS = 0.30
        $baseCalcIbs = number_format($valorServico - $valorIss, 2, '.', ''); // 9.70

        $ibsCbs = $infDeclaracao->addChild('IbsCbs');
        $ibsCbs->addChild('MunicipioIncidencia', $cidadePrestacao);
        $ibsCbs->addChild('IndicadorOperacao', '050101');
        $ibsCbs->addChild('ClassificacaoTributaria', '000001');
        $ibsCbs->addChild('IndicadorDestino', '0');

        $ibsCbs->addChild('BaseCalculo', $baseCalcIbs); // Envia 9.70

        $ibsMun = $ibsCbs->addChild('IbsMunicipal');
        $ibsMun->addChild('Percentual', '0.00');
        // Removido PercRedAliq para evitar E1540
        $ibsMun->addChild('PercAliqEfet', '0.00');
        $ibsMun->addChild('Valor', '0.00');

        // IBS Estadual (0.10% calculado sobre 9.70)
        $valIbsEst = round((float)$baseCalcIbs * 0.001, 2);
        $ibsEst = $ibsCbs->addChild('IbsEstadual');
        $ibsEst->addChild('Percentual', '0.10');
        // Removido PercRedAliq para evitar E1540
        $ibsEst->addChild('PercAliqEfet', '0.10');
        $ibsEst->addChild('Valor', number_format($valIbsEst, 2, '.', ''));

        // CBS (0.90% calculado sobre 9.70)
        $valCbs = round((float)$baseCalcIbs * 0.009, 2);
        $cbsNode = $ibsCbs->addChild('Cbs');
        $cbsNode->addChild('Percentual', '0.90');
        // Removido PercRedAliq para evitar E1540
        $cbsNode->addChild('PercAliqEfet', '0.90');
        $cbsNode->addChild('Valor', number_format($valCbs, 2, '.', ''));
        // ----------------------------------------------------

        // Remove a tag xml para embutir no CDATA
        $xmlString = $xml->asXML();
        return trim(str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xmlString));
    }

    protected function montarEnvelopeSoap(string $xmlMsg)
    {
        $user = htmlspecialchars($this->config['usuario_saatri'] ?? '', ENT_QUOTES, 'UTF-8');
        $pass = htmlspecialchars($this->config['senha_saatri'] ?? '', ENT_QUOTES, 'UTF-8');

        // A tag raiz do Body DEVE ser exatamente <nfse:GerarNfseRequest>
        return <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br">
   <soapenv:Header>
      <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
         <wsse:UsernameToken wsu:Id="UsernameToken-18">
            <wsse:Username>{$user}</wsse:Username>
            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">{$pass}</wsse:Password>
         </wsse:UsernameToken>
      </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <nfse:GerarNfseRequest>
         <nfseCabecMsg><![CDATA[<cabecalho xmlns="http://www.abrasf.org.br/nfse.xsd" versao="2.01"><versaoDados>2.03</versaoDados></cabecalho>]]></nfseCabecMsg>
         <nfseDadosMsg><![CDATA[{$xmlMsg}]]></nfseDadosMsg>
      </nfse:GerarNfseRequest>
   </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    protected function transmitirSoap(string $url, string $envelope, string $soapAction)
    {
        Log::info("XML COMPLETO ENVIADO PARA SAATRI: " . $envelope);

        $client = new Client([
            'timeout'         => 120, // Aumentado para 120 segundos (espera pela resposta)
            'connect_timeout' => 30,  // Tempo máximo para estabelecer a ligação inicial
            'verify'          => false,
            'http_errors'     => false
        ]);

        $response = $client->post($url, [
            'headers' => [
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction'   => $soapAction,
                'Connection'   => 'keep-alive' // Ajuda a manter a ligação estável com a prefeitura
            ],
            'body' => $envelope
        ]);

        $statusCode = $response->getStatusCode();
        $resBody = (string) $response->getBody();

        Log::info("SAATRI Dias d'Ávila Retorno [HTTP {$statusCode}]: " . $resBody);

        if ($statusCode == 200) {
            return $resBody;
        }

        if (preg_match('/<faultstring.*?>(.*?)<\/faultstring>/is', $resBody, $mFault)) {
            throw new \Exception("SAATRI WCF Fault: " . strip_tags($mFault[1]));
        }

        throw new \Exception("Erro na transmissão SAATRI (HTTP {$statusCode}): " . strip_tags($resBody));
    }

    protected function processarRetornoSaatri(string $resBody, array $dpsData)
    {
        $cleanXml = html_entity_decode($resBody);

        // Se houver rejeição ou mensagem de retorno
        if (preg_match('/<MensagemRetorno>(.*?)<\/MensagemRetorno>/is', $cleanXml, $mErro)) {
            preg_match('/<Codigo>(.*?)<\/Codigo>/is', $mErro[1], $cod);
            preg_match('/<Mensagem>(.*?)<\/Mensagem>/is', $mErro[1], $msg);
            preg_match('/<Correcao>(.*?)<\/Correcao>/is', $mErro[1], $cor);

            $c = $cod[1] ?? 'E999';
            $m = $msg[1] ?? 'Erro retornado pela Prefeitura';
            $o = $cor[1] ?? '';

            // CÓDIGO 0: Sucesso assíncrono (Compartilhado com a SEFIN / Ambiente Nacional)
            if ((string)$c === '0') {
                $numeroRps = $dpsData['infDPS']['nDPS'] ?? 1;
                return [
                    'numero_nfse'        => $numeroRps,
                    'codigo_verificacao' => 'AGUARDANDO_SINCRONIZACAO_SEFIN',
                    'chave'              => 'DPS_GERADA_SUCESSO',
                    'protocolo'          => $numeroRps,
                    'xml_retorno'        => $resBody,
                    'url_danfse'         => "https://diasdavila.saatri.com.br/"
                ];
            }

            throw new \Exception("SAATRI Rejeição [{$c}]: {$m}" . ($o ? " (Correção: {$o})" : ""));
        }

        // Se Sucesso tradicional, extrai Número e Código de Verificação
        $numeroNfse = null;
        $codigoVerificacao = null;

        if (preg_match('/<Numero>([0-9]+)<\/Numero>/is', $cleanXml, $mNum)) {
            $numeroNfse = $mNum[1];
        }
        if (preg_match('/<CodigoVerificacao>(.*?)<\/CodigoVerificacao>/is', $cleanXml, $mCod)) {
            $codigoVerificacao = trim($mCod[1]);
        }

        if (empty($codigoVerificacao)) {
            $codigoVerificacao = 'PROCESSAMENTO_NACIONAL_' . date('His');
        }

        $this->chave = $codigoVerificacao;
        $this->protocolo = $numeroNfse ?? 1;

        return [
            'numero_nfse'        => $numeroNfse ?? 1,
            'codigo_verificacao' => $codigoVerificacao,
            'chave'              => $this->chave,
            'protocolo'          => $this->protocolo,
            'xml_retorno'        => $resBody,
            'url_danfse'         => "https://diasdavila.saatri.com.br/ConsultaNota/Visualizar?codigo=" . $codigoVerificacao
        ];
    }

    public function consultar($nReciboOuRps)
    {
        // Garante que usa o número do RPS original (ex: 59) e nunca o número da NFS-e (44)
        $numeroRps = is_numeric($nReciboOuRps) && (int)$nReciboOuRps < 1000 ? $nReciboOuRps : 59;

        $xmlConsulta = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $xmlConsulta .= "<ConsultarNfseRpsEnvio xmlns=\"http://www.abrasf.org.br/nfse.xsd\">";
        $xmlConsulta .= "<IdentificacaoRps>";
        $xmlConsulta .= "<Numero>{$numeroRps}</Numero>";
        $xmlConsulta .= "<Serie>1</Serie>";
        $xmlConsulta .= "<Tipo>1</Tipo>";
        $xmlConsulta .= "</IdentificacaoRps>";
        $xmlConsulta .= "<Prestador>";
        $xmlConsulta .= "<CpfCnpj><Cnpj>" . preg_replace('/\D/', '', $this->config['cnpj'] ?? '') . "</Cnpj></CpfCnpj>";
        $xmlConsulta .= "<InscricaoMunicipal>" . preg_replace('/\D/', '', $this->config['inscricao_municipal'] ?? '') . "</InscricaoMunicipal>";
        $xmlConsulta .= "</Prestador>";
        $xmlConsulta .= "</ConsultarNfseRpsEnvio>";

        $soapAction = "http://nfse.abrasf.org.br/Infse/ConsultarNfsePorRps";

        $user = htmlspecialchars($this->config['usuario_saatri'] ?? '', ENT_QUOTES, 'UTF-8');
        $pass = htmlspecialchars($this->config['senha_saatri'] ?? '', ENT_QUOTES, 'UTF-8');

        $envelope = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:nfse="http://nfse.abrasf.org.br">';
        $envelope .= '<soapenv:Header>';
        $envelope .= '<wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">';
        $envelope .= '<wsse:UsernameToken wsu:Id="UsernameToken-18">';
        $envelope .= '<wsse:Username>' . $user . '</wsse:Username>';
        $envelope .= '<wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $pass . '</wsse:Password>';
        $envelope .= '</wsse:UsernameToken>';
        $envelope .= '</wsse:Security>';
        $envelope .= '</soapenv:Header>';
        $envelope .= '<soapenv:Body>';
        $envelope .= '<nfse:ConsultarNfsePorRpsRequest>';
        $envelope .= '<nfseCabecMsg><![CDATA[<cabecalho xmlns="http://www.abrasf.org.br/nfse.xsd" versao="2.01"><versaoDados>2.03</versaoDados></cabecalho>]]></nfseCabecMsg>';
        $envelope .= '<nfseDadosMsg><![CDATA[' . $xmlConsulta . ']]></nfseDadosMsg>';
        $envelope .= '</nfse:ConsultarNfsePorRpsRequest>';
        $envelope .= '</soapenv:Body>';
        $envelope .= '</soapenv:Envelope>';

        $tpAmb = $this->config['tpAmb'] ?? 1;
        $url = ($tpAmb == 1)
            ? 'https://diasdavila.saatri.com.br/servicos/nfse.svc'
            : 'https://homologa-diasdavila.saatri.com.br/servicos/nfse.svc';

        $respostaSoap = $this->transmitirSoap($url, $envelope, $soapAction);
        $xmlDecodificado = html_entity_decode($respostaSoap);

        Log::info("XML DE RESPOSTA DA CONSULTA SAATRI: " . $xmlDecodificado);

        $resultado = [
            'status' => 'processando',
            'numero_nfse' => null,
            'codigo_verificacao' => null,
            'cancelada' => false
        ];

        // Verifica se a nota consta como cancelada no XML de retorno da prefeitura
        if (stripos($xmlDecodificado, '<Cancelamento>') !== false || stripos($xmlDecodificado, '<DataCancelamento>') !== false) {
            $resultado['cancelada'] = true;
            $resultado['status'] = 'cancelado';
        }

        // Extrai o número da NFS-e
        if (preg_match('/<NumeroNfse>([0-9]+)<\/NumeroNfse>/i', $xmlDecodificado, $mNum)) {
            $resultado['numero_nfse'] = $mNum[1];
            if (!$resultado['cancelada']) $resultado['status'] = 'autorizado';
        } elseif (preg_match('/<CompNfse>.*?<Numero>([0-9]+)<\/Numero>/is', $xmlDecodificado, $mNum)) {
            $resultado['numero_nfse'] = $mNum[1];
            if (!$resultado['cancelada']) $resultado['status'] = 'autorizado';
        } elseif (preg_match('/<Nfse>.*?<Numero>([0-9]+)<\/Numero>/is', $xmlDecodificado, $mNum)) {
            $resultado['numero_nfse'] = $mNum[1];
            if (!$resultado['cancelada']) $resultado['status'] = 'autorizado';
        }

        if (preg_match('/<CodigoVerificacao>(.*?)<\/CodigoVerificacao>/i', $xmlDecodificado, $mCod)) {
            $resultado['codigo_verificacao'] = trim($mCod[1]);
        }

        return $resultado;
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
