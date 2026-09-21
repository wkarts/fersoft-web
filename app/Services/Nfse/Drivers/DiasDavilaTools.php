<?php

namespace App\Services\Nfse\Drivers;

use App\Services\Nfse\NfseEmissorInterface;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use NFePHP\Common\Certificate;

class DiasDavilaTools implements NfseEmissorInterface
{
    protected array $config;
    protected Certificate $certificate;
    protected ?string $chave = null;
    protected ?string $protocolo = null;

    public function __construct(array $config, Certificate $certificate)
    {
        $this->config = $config;
        $this->certificate = $certificate;
    }

    public function emitir(array $dpsData)
    {
        $usuario = trim((string) ($this->config['usuario_saatri'] ?? ''));
        $senha = (string) ($this->config['senha_saatri'] ?? '');

        if ($usuario === '' || $senha === '') {
            throw new \RuntimeException(
                'Credenciais SAATRI de Dias d\'Ávila não configuradas no ambiente.'
            );
        }

        $url = (int) ($this->config['tpAmb'] ?? 1) === 1
            ? 'https://diasdavila.saatri.com.br/servicos/nfse.svc'
            : 'https://homologa-diasdavila.saatri.com.br/servicos/nfse.svc';

        $xmlRps = $this->gerarXmlRps($dpsData);
        $xmlLote = $this->gerarXmlLote($xmlRps, $dpsData);
        $envelope = $this->montarEnvelopeSoap('RecepcionarLoteRps', $xmlLote);

        return $this->transmitirSoap(
            $url,
            $envelope,
            'http://nfse.abrasf.org.br/RecepcionarLoteRps'
        );
    }

    public function cancelar(string $chave, string $motivo = 'Cancelamento')
    {
        throw new \RuntimeException(
            'Cancelamento SAATRI ainda não possui implementação validada neste driver.'
        );
    }

    public function getChave()
    {
        return $this->chave;
    }

    public function getProtocolo()
    {
        return $this->protocolo;
    }

    protected function gerarXmlRps(array $dpsData): string
    {
        $inf = $dpsData['infDPS'] ?? [];
        $serv = $inf['serv'] ?? [];
        $toma = $inf['toma'] ?? [];
        $prest = $inf['prest'] ?? [];

        $valor = number_format(
            (float) ($inf['valores']['vServPrest']['vServ'] ?? 0),
            2,
            '.',
            ''
        );
        $serie = (string) ($inf['serie'] ?? '1');
        $numero = (string) ($inf['nDPS'] ?? '1');

        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><Rps xmlns="http://www.abrasf.org.br/nfse.xsd"/>'
        );

        $declaracao = $xml->addChild('InfDeclaracaoPrestacaoServico');
        $declaracao->addAttribute('Id', 'RPS_' . $numero);

        $rps = $declaracao->addChild('Rps');
        $identificacao = $rps->addChild('IdentificacaoRps');
        $identificacao->addChild('Numero', $numero);
        $identificacao->addChild('Serie', $serie);
        $identificacao->addChild('Tipo', '1');
        $rps->addChild('DataEmissao', date('Y-m-d\TH:i:s'));
        $rps->addChild('Status', '1');

        $declaracao->addChild(
            'Competencia',
            (string) ($inf['dCompet'] ?? date('Y-m-d'))
        );

        $servico = $declaracao->addChild('Servico');
        $valores = $servico->addChild('Valores');
        $valores->addChild('ValorServicos', $valor);
        $servico->addChild(
            'IssRetido',
            (int) ($inf['valores']['trib']['tribMun']['tpRetISSQN'] ?? 1) === 2
                ? '1'
                : '2'
        );
        $servico->addChild(
            'ItemListaServico',
            preg_replace(
                '/\D+/',
                '',
                (string) ($serv['cServ']['cTribNac'] ?? '')
            )
        );
        $servico->addChild(
            'Discriminacao',
            htmlspecialchars(
                (string) ($serv['cServ']['xDescServ'] ?? 'PRESTAÇÃO DE SERVIÇO'),
                ENT_QUOTES,
                'UTF-8'
            )
        );
        $servico->addChild(
            'CodigoMunicipio',
            preg_replace('/\D+/', '', (string) ($inf['cLocEmi'] ?? '2910057'))
        );
        $servico->addChild('ExigibilidadeISS', '1');

        $prestador = $declaracao->addChild('Prestador');
        $prestador->addChild(
            'Cnpj',
            preg_replace('/\D+/', '', (string) ($prest['CNPJ'] ?? ''))
        );

        $tomador = $declaracao->addChild('TomadorServico');
        $identificacaoTomador = $tomador->addChild('IdentificacaoTomador');
        $cpfCnpj = $identificacaoTomador->addChild('CpfCnpj');

        if (!empty($toma['CNPJ'])) {
            $cpfCnpj->addChild(
                'Cnpj',
                preg_replace('/\D+/', '', (string) $toma['CNPJ'])
            );
        } else {
            $cpfCnpj->addChild(
                'Cpf',
                preg_replace('/\D+/', '', (string) ($toma['CPF'] ?? ''))
            );
        }

        $tomador->addChild(
            'RazaoSocial',
            htmlspecialchars((string) ($toma['xNome'] ?? ''), ENT_QUOTES, 'UTF-8')
        );

        $ender = $toma['ender'] ?? [];
        if ($ender !== []) {
            $endereco = $tomador->addChild('Endereco');
            $endereco->addChild('Endereco', (string) ($ender['xLgr'] ?? ''));
            $endereco->addChild('Numero', (string) ($ender['nro'] ?? 'S/N'));
            $endereco->addChild('Bairro', (string) ($ender['xBairro'] ?? ''));
            $endereco->addChild(
                'CodigoMunicipio',
                preg_replace('/\D+/', '', (string) ($ender['cMun'] ?? ''))
            );
            $endereco->addChild('Uf', (string) ($ender['UF'] ?? 'BA'));
            $endereco->addChild(
                'Cep',
                preg_replace('/\D+/', '', (string) ($ender['CEP'] ?? ''))
            );
        }

        // Bloco IBS/CBS existente na implementação histórica SAATRI.
        $ibsCbs = $declaracao->addChild('IbsCbs');
        $ibsCbs->addChild(
            'MunicipioIncidencia',
            preg_replace('/\D+/', '', (string) ($inf['cLocEmi'] ?? '2910057'))
        );
        $ibsCbs->addChild(
            'IndicadorOperacao',
            (string) ($inf['IBSCBS']['cIndOp'] ?? '000001')
        );
        $ibsCbs->addChild(
            'ClassificacaoTributaria',
            (string) ($inf['IBSCBS']['cClassTrib'] ?? '000001')
        );
        $ibsCbs->addChild(
            'IndicadorDestino',
            (string) ($inf['IBSCBS']['indDest'] ?? '0')
        );
        $ibsCbs->addChild('BaseCalculo', $valor);

        return $xml->asXML();
    }

    protected function gerarXmlLote(string $xmlRps, array $dpsData): string
    {
        $inf = $dpsData['infDPS'] ?? [];
        $cnpj = preg_replace(
            '/\D+/',
            '',
            (string) ($inf['prest']['CNPJ'] ?? '')
        );
        $numero = htmlspecialchars(
            (string) ($inf['nDPS'] ?? '1'),
            ENT_QUOTES,
            'UTF-8'
        );

        $rps = preg_replace('/<\?xml[^>]+\?>/', '', $xmlRps);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<EnviarLoteRpsEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">'
            . '<LoteRps Id="LOTE_' . $numero . '">'
            . '<NumeroLote>' . $numero . '</NumeroLote>'
            . '<Cnpj>' . $cnpj . '</Cnpj>'
            . '<QuantidadeRps>1</QuantidadeRps>'
            . '<ListaRps>' . $rps . '</ListaRps>'
            . '</LoteRps>'
            . '</EnviarLoteRpsEnvio>';
    }

    protected function montarEnvelopeSoap(string $metodo, string $xml): string
    {
        $usuario = htmlspecialchars(
            (string) $this->config['usuario_saatri'],
            ENT_QUOTES,
            'UTF-8'
        );
        $senha = htmlspecialchars(
            (string) $this->config['senha_saatri'],
            ENT_QUOTES,
            'UTF-8'
        );

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" '
            . 'xmlns:nfse="http://nfse.abrasf.org.br" '
            . 'xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">'
            . '<soapenv:Header><wsse:Security><wsse:UsernameToken>'
            . '<wsse:Username>' . $usuario . '</wsse:Username>'
            . '<wsse:Password>' . $senha . '</wsse:Password>'
            . '</wsse:UsernameToken></wsse:Security></soapenv:Header>'
            . '<soapenv:Body><nfse:' . $metodo . '>'
            . '<nfse:xmlEnvio><![CDATA[' . $xml . ']]></nfse:xmlEnvio>'
            . '</nfse:' . $metodo . '></soapenv:Body></soapenv:Envelope>';
    }

    protected function transmitirSoap(
        string $url,
        string $envelope,
        string $soapAction
    ) {
        $client = new Client([
            'timeout' => 45,
            'connect_timeout' => 15,
            'verify' => true,
            'http_errors' => false,
        ]);

        $response = $client->post($url, [
            'headers' => [
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => $soapAction,
            ],
            'body' => $envelope,
        ]);

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException(
                "Erro na transmissão para Dias d'Ávila (HTTP {$status}): "
                . trim(strip_tags($body))
            );
        }

        if (
            preg_match('/<(?:MensagemRetorno|Mensagem)>(.*?)<\/(?:MensagemRetorno|Mensagem)>/si', $body, $match)
            && trim(strip_tags($match[1])) !== ''
        ) {
            $mensagem = trim(strip_tags($match[1]));
            if (stripos($mensagem, 'sucesso') === false) {
                throw new \RuntimeException(
                    "Retorno SAATRI Dias d'Ávila: {$mensagem}"
                );
            }
        }

        $this->protocolo = $this->extrairTag($body, ['Protocolo', 'NumeroLote'])
            ?: 'LOTE_' . date('YmdHis');
        $this->chave = $this->extrairTag(
            $body,
            ['CodigoVerificacao', 'NumeroNfse', 'NumeroNFS-e']
        ) ?: 'RPS_DIAS_DAVILA_' . date('YmdHis');

        Log::info('Emissão NFS-e SAATRI concluída.', [
            'http_status' => $status,
            'protocolo' => $this->protocolo,
        ]);

        return [
            'xml_retorno' => $body,
            'numero_nfse' => $this->extrairTag(
                $body,
                ['NumeroNfse', 'NumeroNFS-e']
            ),
        ];
    }

    private function extrairTag(string $xml, array $tags): ?string
    {
        foreach ($tags as $tag) {
            if (preg_match(
                '/<' . preg_quote($tag, '/') . '[^>]*>(.*?)<\/' . preg_quote($tag, '/') . '>/si',
                $xml,
                $match
            )) {
                return trim(strip_tags($match[1]));
            }
        }

        return null;
    }
}
