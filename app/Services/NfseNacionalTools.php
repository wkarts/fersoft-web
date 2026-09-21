<?php

namespace App\Services;

use App\Services\Nfse\NfseEmissorInterface;
use GuzzleHttp\Client;
use NFePHP\Common\Certificate;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class NfseNacionalTools implements NfseEmissorInterface
{
    protected array $config;
    protected Certificate $certificate;
    protected ?string $chave = null;
    protected ?string $protocolo = null;
    protected ?string $xmlNfseAutorizada = null;

    public function __construct($config, Certificate $certificate)
    {
        $decoded = is_string($config) ? json_decode($config, true) : $config;

        $this->config = is_array($decoded) ? $decoded : [];
        $this->certificate = $certificate;
    }

    public function emitir(array $dpsData)
    {
        $erros = $this->validarDps($dpsData);
        if ($erros !== []) {
            throw new \RuntimeException(
                'Validação interna da DPS: ' . implode(' | ', $erros)
            );
        }

        $xml = $this->gerarDpsXml($dpsData);
        $xmlAssinado = $this->assinarXml($xml, 'infDPS');

        $response = $this->request(
            'POST',
            $this->baseUrl() . '/nfse',
            ['dpsXmlGZipB64' => base64_encode(gzencode($xmlAssinado))]
        );

        $this->hidratarRetorno($response);

        return [
            'xml_retorno' => $this->xmlNfseAutorizada ?: $xmlAssinado,
            'numero_nfse' => $this->numeroNfseDoXml($this->xmlNfseAutorizada),
            'chave' => $this->chave,
            'protocolo' => $this->protocolo,
            'response' => $response,
        ];
    }

    public function consultar(string $chave): array
    {
        $response = $this->request(
            'GET',
            $this->baseUrl() . '/nfse/' . rawurlencode($chave)
        );

        $this->hidratarRetorno($response);

        return $response;
    }

    public function cancelar(string $chave, string $motivo = 'Cancelamento')
    {
        if ($chave === '') {
            throw new \InvalidArgumentException('Informe a chave da NFS-e para cancelamento.');
        }

        $cnpj = preg_replace('/\D+/', '', (string) ($this->config['cnpj'] ?? ''));
        $idEvento = 'PRE' . $chave . '101101';

        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><pedRegEvento xmlns="http://www.sped.fazenda.gov.br/nfse"/>'
        );
        $xml->addAttribute('versao', '1.00');

        $inf = $xml->addChild('infPedReg');
        $inf->addAttribute('Id', $idEvento);
        $inf->addChild('tpAmb', (string) ($this->config['tpAmb'] ?? 1));
        $inf->addChild('verAplic', 'FersoftERP');
        $inf->addChild('dhEvento', date('Y-m-d\TH:i:sP'));
        $inf->addChild('CNPJAutor', $cnpj);
        $inf->addChild('chNFSe', $chave);

        $evento = $inf->addChild('e101101');
        $evento->addChild('xDesc', 'Cancelamento de NFS-e');
        $evento->addChild('cMotivo', '1');
        $evento->addChild('xMotivo', htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8'));

        $assinado = $this->assinarXml($xml->asXML(), 'infPedReg');

        return $this->request(
            'POST',
            $this->baseUrl() . '/nfse/' . rawurlencode($chave) . '/eventos',
            [
                'pedidoRegistroEventoXmlGZipB64' =>
                    base64_encode(gzencode($assinado)),
            ]
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

    private function validarDps(array $dpsData): array
    {
        $inf = $dpsData['infDPS'] ?? [];
        $serv = $inf['serv'] ?? [];
        $codigo = preg_replace(
            '/\D+/',
            '',
            (string) ($serv['cServ']['cTribNac'] ?? '')
        );

        $erros = [];

        if (empty($inf['prest']['CNPJ'])) {
            $erros[] = 'CNPJ do prestador não informado.';
        }

        if (
            empty($inf['toma']['CNPJ'])
            && empty($inf['toma']['CPF'])
        ) {
            $erros[] = 'Documento do tomador não informado.';
        }

        if ($codigo === '') {
            $erros[] = 'Código de tributação nacional do serviço não informado.';
        }

        $constructionCodes = [
            '070201', '070202', '070401', '070501', '070502',
            '070601', '070602', '070701', '070801', '071701', '071901',
        ];

        if (
            in_array($codigo, $constructionCodes, true)
            && empty($serv['obra']['cObra'])
        ) {
            $erros[] = 'Código da obra/ART é obrigatório para o serviço informado.';
        }

        $valor = (float) ($inf['valores']['vServPrest']['vServ'] ?? 0);
        if ($valor <= 0) {
            $erros[] = 'Valor do serviço deve ser maior que zero.';
        }

        return $erros;
    }

    private function gerarDpsXml(array $dpsData): string
    {
        $inf = $dpsData['infDPS'] ?? [];

        $cnpj = preg_replace(
            '/\D+/',
            '',
            (string) ($this->config['cnpj'] ?? $inf['prest']['CNPJ'] ?? '')
        );

        $codigoMunicipio = str_pad(
            preg_replace('/\D+/', '', (string) ($inf['cLocEmi'] ?? '')),
            7,
            '0',
            STR_PAD_LEFT
        );

        $serie = (string) ($inf['serie'] ?? $this->config['serie_dps'] ?? '1');
        $numero = (string) ($inf['nDPS'] ?? $this->config['numero_dps'] ?? '1');

        $idDps = 'DPS'
            . '1'
            . $cnpj
            . $codigoMunicipio
            . str_pad($serie, 5, '0', STR_PAD_LEFT)
            . str_pad($numero, 15, '0', STR_PAD_LEFT);

        $this->chave = $idDps;

        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><DPS xmlns="http://www.sped.fazenda.gov.br/nfse"/>'
        );
        $xml->addAttribute('versao', '1.01');

        $node = $xml->addChild('infDPS');
        $node->addAttribute('Id', $idDps);
        $node->addChild('tpAmb', (string) ($this->config['tpAmb'] ?? 1));
        $node->addChild('dhEmi', (string) ($inf['dhEmi'] ?? date('Y-m-d\TH:i:sP')));
        $node->addChild('verAplic', 'FersoftERP');
        $node->addChild('serie', $serie);
        $node->addChild('nDPS', $numero);
        $node->addChild('dCompet', (string) ($inf['dCompet'] ?? date('Y-m-d')));
        $node->addChild('tpEmit', (string) ($inf['tpEmit'] ?? 1));
        $node->addChild('cLocEmi', $codigoMunicipio);

        $prest = $node->addChild('prest');
        $prest->addChild('CNPJ', $cnpj);

        $regTrib = $prest->addChild('regTrib');
        $regTrib->addChild(
            'opSimpNac',
            (string) ($inf['prest']['regTrib']['opSimpNac'] ?? 1)
        );
        $regTrib->addChild(
            'regApTribSN',
            (string) ($inf['prest']['regTrib']['regApTribSN'] ?? 1)
        );
        $regTrib->addChild(
            'regEspTrib',
            (string) ($inf['prest']['regTrib']['regEspTrib'] ?? 0)
        );

        $tomador = $node->addChild('toma');
        if (!empty($inf['toma']['CNPJ'])) {
            $tomador->addChild(
                'CNPJ',
                preg_replace('/\D+/', '', (string) $inf['toma']['CNPJ'])
            );
        } else {
            $tomador->addChild(
                'CPF',
                preg_replace('/\D+/', '', (string) ($inf['toma']['CPF'] ?? ''))
            );
        }

        $tomador->addChild(
            'xNome',
            htmlspecialchars((string) ($inf['toma']['xNome'] ?? ''), ENT_QUOTES, 'UTF-8')
        );

        if (!empty($inf['toma']['ender'])) {
            $ender = $tomador->addChild('end');
            $enderNac = $ender->addChild('endNac');
            $enderNac->addChild(
                'cMun',
                preg_replace('/\D+/', '', (string) ($inf['toma']['ender']['cMun'] ?? ''))
            );
            $enderNac->addChild(
                'CEP',
                preg_replace('/\D+/', '', (string) ($inf['toma']['ender']['CEP'] ?? ''))
            );

            $ender->addChild(
                'xLgr',
                htmlspecialchars((string) ($inf['toma']['ender']['xLgr'] ?? ''), ENT_QUOTES, 'UTF-8')
            );
            $ender->addChild('nro', (string) ($inf['toma']['ender']['nro'] ?? 'S/N'));
            $ender->addChild(
                'xBairro',
                htmlspecialchars((string) ($inf['toma']['ender']['xBairro'] ?? ''), ENT_QUOTES, 'UTF-8')
            );
        }

        $serv = $node->addChild('serv');
        $locPrest = $serv->addChild('locPrest');
        $locPrest->addChild(
            'cLocPrestacao',
            preg_replace(
                '/\D+/',
                '',
                (string) ($inf['serv']['locPrest']['cLocPrest'] ?? $codigoMunicipio)
            )
        );

        $cServ = $serv->addChild('cServ');
        $cServ->addChild(
            'cTribNac',
            preg_replace(
                '/\D+/',
                '',
                (string) ($inf['serv']['cServ']['cTribNac'] ?? '')
            )
        );
        $cServ->addChild(
            'xDescServ',
            htmlspecialchars(
                (string) ($inf['serv']['cServ']['xDescServ'] ?? ''),
                ENT_QUOTES,
                'UTF-8'
            )
        );

        if (!empty($inf['serv']['cServ']['cNBS'])) {
            $cServ->addChild(
                'cNBS',
                preg_replace('/\D+/', '', (string) $inf['serv']['cServ']['cNBS'])
            );
        }

        if (!empty($inf['serv']['obra']['cObra'])) {
            $obra = $serv->addChild('obra');
            $obra->addChild('cObra', (string) $inf['serv']['obra']['cObra']);
        }

        $valores = $node->addChild('valores');
        $vServPrest = $valores->addChild('vServPrest');
        $vServPrest->addChild(
            'vServ',
            number_format(
                (float) ($inf['valores']['vServPrest']['vServ'] ?? 0),
                2,
                '.',
                ''
            )
        );

        $trib = $valores->addChild('trib');
        $tribMun = $trib->addChild('tribMun');
        $tribMun->addChild(
            'tribISSQN',
            (string) ($inf['valores']['trib']['tribMun']['tribISSQN'] ?? 1)
        );
        $tribMun->addChild(
            'tpRetISSQN',
            (string) ($inf['valores']['trib']['tribMun']['tpRetISSQN'] ?? 1)
        );

        if (isset($inf['valores']['trib']['tribMun']['pAliq'])) {
            $tribMun->addChild(
                'pAliq',
                number_format(
                    (float) $inf['valores']['trib']['tribMun']['pAliq'],
                    2,
                    '.',
                    ''
                )
            );
        }

        if (!empty($inf['valores']['trib']['tribFed'])) {
            $tribFed = $trib->addChild('tribFed');
            $piscofins = $tribFed->addChild('piscofins');
            $piscofins->addChild(
                'CST',
                (string) ($inf['valores']['trib']['tribFed']['CST'] ?? '07')
            );

            foreach (['vRetCP', 'vRetIRRF', 'vRetCSLL'] as $campo) {
                $tribFed->addChild(
                    $campo,
                    number_format(
                        (float) ($inf['valores']['trib']['tribFed'][$campo] ?? 0),
                        2,
                        '.',
                        ''
                    )
                );
            }
        }

        return $xml->asXML();
    }

    private function assinarXml(string $xmlString, string $tagName): string
    {
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;

        if (!$doc->loadXML($xmlString)) {
            throw new \RuntimeException('Não foi possível carregar o XML para assinatura.');
        }

        $node = $doc->getElementsByTagName($tagName)->item(0);
        if (!$node || !$node->hasAttribute('Id')) {
            throw new \RuntimeException(
                "Elemento {$tagName} com atributo Id não encontrado para assinatura."
            );
        }

        $node->setIdAttribute('Id', true);
        $id = $node->getAttribute('Id');

        $signature = new XMLSecurityDSig('');
        $signature->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        $signature->addReference(
            $node,
            XMLSecurityDSig::SHA1,
            [
                'http://www.w3.org/2000/09/xmldsig#enveloped-signature',
                XMLSecurityDSig::EXC_C14N,
            ],
            [
                'force_uri' => true,
                'uri' => '#' . $id,
                'overwrite' => false,
            ]
        );

        $key = new XMLSecurityKey(
            XMLSecurityKey::RSA_SHA1,
            ['type' => 'private']
        );
        $key->loadKey((string) ($this->certificate->privateKey ?? ''));
        $signature->sign($key);
        $signature->add509Cert(
            (string) (
                $this->certificate->publicKey
                ?? $this->certificate->certificate
                ?? ''
            )
        );

        $signature->appendSignature($doc->documentElement);

        return $doc->saveXML();
    }

    private function request(
        string $method,
        string $url,
        ?array $json = null
    ): array {
        [$certPath, $keyPath] = $this->temporaryCertificateFiles();

        try {
            $client = new Client([
                'timeout' => (int) ($this->config['timeout_nfse'] ?? 45),
                'connect_timeout' => 15,
                'verify' => true,
                'cert' => $certPath,
                'ssl_key' => $keyPath,
                'http_errors' => false,
            ]);

            $options = [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json; charset=utf-8',
                ],
            ];

            if ($json !== null) {
                $options['json'] = $json;
            }

            $response = $client->request($method, $url, $options);
            $status = $response->getStatusCode();
            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if ($status >= 200 && $status < 300) {
                return is_array($data) ? $data : ['raw' => $body];
            }

            $mensagens = [];
            if (is_array($data)) {
                foreach ((array) ($data['erros'] ?? []) as $erro) {
                    $mensagens[] = '[' . ($erro['codigo'] ?? 'ERRO') . '] '
                        . ($erro['descricao'] ?? $erro['mensagem'] ?? '');
                }

                if ($mensagens === [] && isset($data['erro'])) {
                    $mensagens[] = is_array($data['erro'])
                        ? (string) ($data['erro']['descricao'] ?? $data['erro']['mensagem'] ?? '')
                        : (string) $data['erro'];
                }
            }

            $motivo = $mensagens !== []
                ? implode(' | ', $mensagens)
                : trim(strip_tags($body));

            throw new \RuntimeException(
                "Rejeição NFS-e (HTTP {$status}): {$motivo}"
            );
        } finally {
            @unlink($certPath);
            @unlink($keyPath);
        }
    }

    private function temporaryCertificateFiles(): array
    {
        $cert = (string) (
            $this->certificate->publicKey
            ?? $this->certificate->certificate
            ?? ''
        );
        $key = (string) ($this->certificate->privateKey ?? '');

        if ($cert === '' || $key === '') {
            throw new \RuntimeException(
                'Certificado A1 não disponibilizou certificado/chave privada em PEM.'
            );
        }

        $certPath = tempnam(sys_get_temp_dir(), 'nfse_cert_');
        $keyPath = tempnam(sys_get_temp_dir(), 'nfse_key_');

        file_put_contents($certPath, $cert);
        file_put_contents($keyPath, $key);

        return [$certPath, $keyPath];
    }

    private function baseUrl(): string
    {
        return (int) ($this->config['tpAmb'] ?? 1) === 1
            ? 'https://sefin.nfse.gov.br/SefinNacional'
            : 'https://sefin.producaorestrita.nfse.gov.br/API/SefinNacional';
    }

    private function hidratarRetorno(array $data): void
    {
        $this->chave = (string) (
            $data['chaveAcesso']
            ?? $data['chave']
            ?? $this->chave
            ?? ''
        );

        $this->protocolo = (string) (
            $data['idDps']
            ?? $data['protocolo']
            ?? $this->protocolo
            ?? ''
        );

        $encoded = $data['nfseXmlGZipB64'] ?? null;
        if ($encoded) {
            $decoded = base64_decode((string) $encoded, true);
            if ($decoded !== false) {
                $xml = @gzdecode($decoded);
                if ($xml !== false) {
                    $this->xmlNfseAutorizada = $xml;
                }
            }
        }

        if (!$this->xmlNfseAutorizada && isset($data['raw'])) {
            $raw = (string) $data['raw'];
            if (str_contains($raw, '<NFSe')) {
                $this->xmlNfseAutorizada = $raw;
            }
        }
    }

    private function numeroNfseDoXml(?string $xml): ?string
    {
        if (!$xml) {
            return null;
        }

        try {
            $document = new \SimpleXMLElement($xml);
            $nodes = $document->xpath('//*[local-name()="nNFSe"]');

            if ($nodes && isset($nodes[0])) {
                return trim((string) $nodes[0]);
            }
        } catch (\Throwable $e) {
            // Número pode não estar presente no retorno; o controller mantém
            // o identificador local sem invalidar a emissão autorizada.
        }

        return null;
    }
}
