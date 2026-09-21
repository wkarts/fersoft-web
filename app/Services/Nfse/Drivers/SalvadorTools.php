<?php

namespace App\Services\Nfse\Drivers;

use App\Services\Nfse\NfseEmissorInterface;
use Illuminate\Support\Facades\Log;
use NFePHP\Common\Certificate;

class SalvadorTools implements NfseEmissorInterface
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
        if (!class_exists(\SoapClient::class)) {
            throw new \RuntimeException(
                'A extensão SOAP do PHP é necessária para a integração NFS-e de Salvador.'
            );
        }

        return $this->transmitirSoap($this->gerarXmlSalvador($dpsData));
    }

    public function cancelar(string $chave, string $motivo = 'Cancelamento')
    {
        throw new \RuntimeException(
            'Cancelamento da integração municipal de Salvador ainda não possui implementação validada neste driver.'
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

    private function gerarXmlSalvador(array $dpsData): string
    {
        $inf = $dpsData['infDPS'] ?? [];
        $serv = $inf['serv'] ?? [];
        $toma = $inf['toma'] ?? [];
        $prest = $inf['prest'] ?? [];

        $cnpj = preg_replace(
            '/\D+/',
            '',
            (string) ($this->config['cnpj'] ?? $prest['CNPJ'] ?? '')
        );
        $inscricao = preg_replace(
            '/\D+/',
            '',
            (string) (
                $this->config['inscricao_municipal']
                ?? $this->config['im']
                ?? ''
            )
        );

        if ($cnpj === '' || $inscricao === '') {
            throw new \RuntimeException(
                'CNPJ e inscrição municipal são obrigatórios para NFS-e de Salvador.'
            );
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        $pedido = $dom->createElementNS(
            'https://nfse.salvador.ba.gov.br/nfts',
            'PedidoEnvioNFTS'
        );
        $dom->appendChild($pedido);

        $cabecalho = $dom->createElement('Cabecalho');
        $cabecalho->setAttribute('Versao', '1');
        $pedido->appendChild($cabecalho);

        $remetente = $dom->createElement('Remetente');
        $cabecalho->appendChild($remetente);
        $docRemetente = $dom->createElement('CPFCNPJ');
        $remetente->appendChild($docRemetente);
        $docRemetente->appendChild($dom->createElement('CNPJ', $cnpj));

        $nfts = $dom->createElement('NFTS');
        $pedido->appendChild($nfts);
        $nfts->appendChild($dom->createElement('TipoDocumento', '02'));

        $chaveDocumento = $dom->createElement('ChaveDocumento');
        $nfts->appendChild($chaveDocumento);
        $chaveDocumento->appendChild(
            $dom->createElement('InscricaoMunicipal', substr($inscricao, 0, 11))
        );
        $chaveDocumento->appendChild(
            $dom->createElement('SerieNFTS', (string) ($inf['serie'] ?? '1'))
        );
        $chaveDocumento->appendChild(
            $dom->createElement('NumeroDocumento', (string) ($inf['nDPS'] ?? '1'))
        );

        $nfts->appendChild(
            $dom->createElement(
                'DataPrestacao',
                (string) ($inf['dCompet'] ?? date('Y-m-d'))
            )
        );
        $nfts->appendChild($dom->createElement('StatusNFTS', 'N'));
        $nfts->appendChild($dom->createElement('TributacaoNFTS', 'T'));
        $nfts->appendChild(
            $dom->createElement(
                'ValorServicos',
                number_format(
                    (float) ($inf['valores']['vServPrest']['vServ'] ?? 0),
                    2,
                    '.',
                    ''
                )
            )
        );
        $nfts->appendChild($dom->createElement('ValorDeducoes', '0.00'));

        $codigoServico = preg_replace(
            '/\D+/',
            '',
            (string) ($serv['cServ']['cTribNac'] ?? '')
        );
        $nfts->appendChild(
            $dom->createElement('CodigoServico', $codigoServico)
        );

        $codigoTributacao = preg_replace(
            '/\D+/',
            '',
            (string) (
                $this->config['codigo_tributacao_municipio']
                ?? $codigoServico
            )
        );
        $nfts->appendChild(
            $dom->createElement(
                'CodigoTributacaoMunicipio',
                $codigoTributacao ?: $codigoServico
            )
        );

        $aliquota = (float) (
            $inf['valores']['trib']['tribMun']['pAliq'] ?? 3
        );
        $nfts->appendChild(
            $dom->createElement(
                'AliquotaServicos',
                number_format($aliquota, 4, '.', '')
            )
        );
        $nfts->appendChild(
            $dom->createElement(
                'ISSRetidoTomador',
                (int) ($inf['valores']['trib']['tribMun']['tpRetISSQN'] ?? 1) === 2
                    ? 'true'
                    : 'false'
            )
        );

        $prestador = $dom->createElement('Prestador');
        $nfts->appendChild($prestador);
        $docPrestador = $dom->createElement('CPFCNPJ');
        $prestador->appendChild($docPrestador);
        $docPrestador->appendChild($dom->createElement('CNPJ', $cnpj));
        $prestador->appendChild(
            $dom->createElement(
                'RazaoSocialPrestador',
                (string) ($this->config['razao_social'] ?? '')
            )
        );

        $nfts->appendChild($dom->createElement('RegimeTributacao', '0'));
        $nfts->appendChild(
            $dom->createElement(
                'Discriminacao',
                (string) ($serv['cServ']['xDescServ'] ?? 'PRESTAÇÃO DE SERVIÇO')
            )
        );
        $nfts->appendChild($dom->createElement('TipoNFTS', '1'));

        $tomador = $dom->createElement('Tomador');
        $nfts->appendChild($tomador);

        $documento = preg_replace(
            '/\D+/',
            '',
            (string) ($toma['CNPJ'] ?? $toma['CPF'] ?? '')
        );
        $docTomador = $dom->createElement('CPFCNPJ');
        $tomador->appendChild($docTomador);
        $docTomador->appendChild(
            $dom->createElement(
                strlen($documento) === 11 ? 'CPF' : 'CNPJ',
                $documento
            )
        );
        $tomador->appendChild(
            $dom->createElement(
                'RazaoSocial',
                (string) ($toma['xNome'] ?? 'TOMADOR')
            )
        );

        return $dom->saveXML();
    }

    private function transmitirSoap(string $xml): array
    {
        $certificado = (string) (
            $this->certificate->publicKey
            ?? $this->certificate->certificate
            ?? ''
        );
        $chave = (string) ($this->certificate->privateKey ?? '');

        if ($certificado === '' || $chave === '') {
            throw new \RuntimeException(
                'Certificado A1 inválido para integração NFS-e de Salvador.'
            );
        }

        $pem = tempnam(sys_get_temp_dir(), 'nfse_salvador_');
        file_put_contents($pem, $certificado . PHP_EOL . $chave);

        try {
            $context = stream_context_create([
                'ssl' => [
                    'local_cert' => $pem,
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);

            $client = new \SoapClient(
                'https://nfse.salvador.ba.gov.br/ws/LoteNFTS.asmx?WSDL',
                [
                    'stream_context' => $context,
                    'trace' => true,
                    'exceptions' => true,
                    'cache_wsdl' => WSDL_CACHE_NONE,
                ]
            );

            $client->EnvioNFTS(['MensagemXML' => $xml]);
            $body = (string) $client->__getLastResponse();

            $dom = new \DOMDocument();
            if (!$dom->loadXML($body)) {
                throw new \RuntimeException(
                    'Retorno XML inválido da Prefeitura de Salvador.'
                );
            }

            $erros = $dom->getElementsByTagName('MensagemRetorno');
            if ($erros->length > 0) {
                $mensagem = trim((string) $erros->item(0)->textContent);
                if ($mensagem !== '') {
                    throw new \RuntimeException(
                        'Rejeição da Prefeitura de Salvador: ' . $mensagem
                    );
                }
            }

            $numero = $dom->getElementsByTagName('NumeroNFTS');
            $numeroNfse = $numero->length > 0
                ? trim((string) $numero->item(0)->textContent)
                : null;

            $this->protocolo = 'PR_SALV_' . date('YmdHis');
            $this->chave = $numeroNfse
                ? 'NFTS_SALV_' . $numeroNfse
                : 'NFTS_SALV_' . date('YmdHis');

            Log::info('Emissão NFS-e/NFTS Salvador concluída.', [
                'numero_nfse' => $numeroNfse,
            ]);

            return [
                'xml_retorno' => $body,
                'numero_nfse' => $numeroNfse,
            ];
        } finally {
            @unlink($pem);
        }
    }
}
