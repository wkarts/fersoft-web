<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class NFSeNacionalService {
    protected $cnpj;
    protected $certBlob;
    protected $senha;

    public function __construct($cnpj, $certBlob, $senha) {
        $this->cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        $this->certBlob = $certBlob;
        $this->senha = $senha;
    }

    /**
     * Consulta oficial por NSU (Fila Sequencial do ADN)
     */
    public function consultar($nsu = 0) {
        $arquivos = $this->gerarArquivosCertificado();
        try {
            $url = "https://adn.nfse.gov.br/contribuintes/DFe/" . $nsu;

            // Requisição com retry para absorver instabilidades transitórias de rede
            $response = Http::retry(2, 500)
                ->withOptions([
                    'cert'            => $arquivos['cert'],
                    'ssl_key'         => $arquivos['pkey'],
                    'verify'          => false,
                    'timeout'         => 60,
                    'connect_timeout' => 20,
                    'curl'            => [
                        CURLOPT_SSLVERSION      => CURL_SSLVERSION_TLSv1_2,
                        CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
                        CURLOPT_FORBID_REUSE    => true, // Evita reutilizar socket derrubado pelo gateway
                        CURLOPT_FRESH_CONNECT   => true,
                    ]
                ])
                ->withHeaders([
                    'X-CNPJ-Interessado' => $this->cnpj,
                    'Accept'             => 'application/json',
                    'User-Agent'         => 'Mozilla/5.0 (compatible; ERP-NFSe/1.0)',
                    'Connection'         => 'close'
                ])
                ->get($url, [
                    'cnpjConsulta' => $this->cnpj
                ]);

            if ($response->status() == 404) {
                return null;
            }

            if ($response->status() == 429) {
                throw new \Exception("A Receita Federal bloqueou a consulta por excesso de tentativas (Consumo Indevido). Aguarde alguns minutos.");
            }

            if ($response->failed()) {
                Log::error("Erro na API ADN NFS-e: Status " . $response->status() . " - Retorno: " . $response->body());
                throw new \Exception("Erro na Receita Federal (Status " . $response->status() . "). Tente novamente mais tarde.");
            }

            return $response->json();
        } finally {
            $this->removerArquivosTemporarios($arquivos);
        }
    }

    /**
     * Consulta as notas por período de data de emissão
     */
    public function consultarPorPeriodo($dataInicio, $dataFim, $pagina = 1) {
        $arquivos = $this->gerarArquivosCertificado();
        try {
            $url = "https://adn.nfse.gov.br/contribuintes/DFe";

            $response = Http::retry(2, 500)
                ->withOptions([
                    'cert'            => $arquivos['cert'],
                    'ssl_key'         => $arquivos['pkey'],
                    'verify'          => false,
                    'timeout'         => 60,
                    'connect_timeout' => 20,
                    'curl'            => [
                        CURLOPT_SSLVERSION      => CURL_SSLVERSION_TLSv1_2,
                        CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
                        CURLOPT_FORBID_REUSE    => true,
                        CURLOPT_FRESH_CONNECT   => true,
                    ]
                ])->withHeaders([
                    'X-CNPJ-Interessado' => $this->cnpj,
                    'Accept'             => 'application/json',
                    'User-Agent'         => 'Mozilla/5.0 (compatible; ERP-NFSe/1.0)',
                    'Connection'         => 'close'
                ])->get($url, [
                    'cnpjConsulta' => $this->cnpj,
                    'dataInicial'  => $dataInicio,
                    'dataFinal'    => $dataFim,
                    'pagina'       => $pagina
                ]);

            if ($response->status() == 404 || $response->failed()) {
                return null;
            }

            return $response->json();
        } finally {
            $this->removerArquivosTemporarios($arquivos);
        }
    }

    /**
     * Extrai e separa a chave privada e o certificado em arquivos dedicados
     */
    private function gerarArquivosCertificado() {
        $certs = [];
        if (!openssl_pkcs12_read($this->certBlob, $certs, $this->senha)) {
            throw new \Exception("Senha do certificado incorreta ou arquivo corrompido no cadastro da empresa.");
        }

        $random = bin2hex(random_bytes(6));
        $certPath = storage_path("app/cert_{$this->cnpj}_{$random}.pem");
        $pkeyPath = storage_path("app/key_{$this->cnpj}_{$random}.pem");

        File::put($certPath, $certs['cert']);
        File::put($pkeyPath, $certs['pkey']);

        return [
            'cert' => $certPath,
            'pkey' => $pkeyPath
        ];
    }

    private function removerArquivosTemporarios(array $arquivos) {
        if (!empty($arquivos['cert']) && File::exists($arquivos['cert'])) {
            File::delete($arquivos['cert']);
        }
        if (!empty($arquivos['pkey']) && File::exists($arquivos['pkey'])) {
            File::delete($arquivos['pkey']);
        }
    }
}
