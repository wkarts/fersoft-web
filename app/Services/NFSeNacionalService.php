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
    public function consultar($nsu = 0)
    {
        $tempPem = $this->gerarPem();
        try {
            $url = 'https://adn.nfse.gov.br/contribuintes/DFe/' . max(0, (int)$nsu);

            $response = Http::withOptions([
                'cert' => [$tempPem, $this->senha],
                'ssl_key' => [$tempPem, $this->senha],
                // Mantido por compatibilidade com os servidores atuais. Recomenda-se
                // configurar a cadeia ICP-Brasil no servidor e posteriormente habilitar verify.
                'verify' => false,
                'timeout' => 300,
                'connect_timeout' => 30,
            ])->withHeaders([
                'X-CNPJ-Interessado' => $this->cnpj,
                'Accept' => 'application/json',
            ])->get($url, [
                'cnpjConsulta' => $this->cnpj,
            ]);

            if ($response->status() === 404) {
                return null;
            }

            if ($response->status() === 429) {
                throw new \RuntimeException('Consulta temporariamente bloqueada por excesso de requisições. Aguarde antes de tentar novamente.');
            }

            if ($response->failed()) {
                Log::error('Erro na consulta ADN NFS-e por NSU.', [
                    'cnpj' => $this->cnpj,
                    'nsu' => (int)$nsu,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 2000),
                ]);
                throw new \RuntimeException('Erro na consulta da NFS-e Nacional. Status HTTP ' . $response->status() . '.');
            }

            return $response->json();
        } finally {
            if (File::exists($tempPem)) {
                File::delete($tempPem);
            }
        }
    }

    /**
     * Consulta as notas por período de data de emissão (Opcional/Histórico)
     */
    public function consultarPorPeriodo($dataInicio, $dataFim, $pagina = 1)
    {
        $tempPem = $this->gerarPem();
        try {
            $url = 'https://adn.nfse.gov.br/contribuintes/DFe';

            $response = Http::withOptions([
                'cert' => [$tempPem, $this->senha],
                'ssl_key' => [$tempPem, $this->senha],
                'verify' => false,
                'timeout' => 120,
                'connect_timeout' => 30,
            ])->withHeaders([
                'X-CNPJ-Interessado' => $this->cnpj,
                'Accept' => 'application/json',
            ])->get($url, [
                'cnpjConsulta' => $this->cnpj,
                'dataInicial' => $dataInicio,
                'dataFinal' => $dataFim,
                'pagina' => max(1, (int)$pagina),
            ]);

            if ($response->status() === 404) {
                return null;
            }

            if ($response->status() === 429) {
                throw new \RuntimeException('Consulta temporariamente bloqueada por excesso de requisições. Aguarde antes de tentar novamente.');
            }

            if ($response->failed()) {
                Log::error('Erro na consulta ADN NFS-e por período.', [
                    'cnpj' => $this->cnpj,
                    'data_inicial' => $dataInicio,
                    'data_final' => $dataFim,
                    'pagina' => (int)$pagina,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 2000),
                ]);
                throw new \RuntimeException('Erro na consulta por período da NFS-e Nacional. Status HTTP ' . $response->status() . '.');
            }

            return $response->json();
        } finally {
            if (File::exists($tempPem)) {
                File::delete($tempPem);
            }
        }
    }

    private function gerarPem()
    {
        $certs = [];
        if (!openssl_pkcs12_read($this->certBlob, $certs, $this->senha)) {
            throw new \RuntimeException('Senha do certificado incorreta ou arquivo PKCS#12 corrompido.');
        }

        $directory = storage_path('app/nfse-cert-temp');
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0700, true);
        }

        $path = tempnam($directory, 'nfse_' . substr($this->cnpj, -6) . '_');
        if ($path === false) {
            throw new \RuntimeException('Não foi possível criar o arquivo temporário do certificado.');
        }

        File::put($path, $certs['cert'] . PHP_EOL . $certs['pkey']);
        @chmod($path, 0600);

        return $path;
    }
}