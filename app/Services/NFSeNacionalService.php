<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

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
        $tempPem = $this->gerarPem();
        try {
            // URL OFICIAL ADN - Onde ficam as notas tomadas por NSU
            $url = "https://adn.nfse.gov.br/contribuintes/DFe/" . $nsu;
            
            $response = Http::withOptions([
                'cert' => [$tempPem, $this->senha],
                'ssl_key' => [$tempPem, $this->senha],
                'verify' => false,
                'timeout' => 300
            ])->get($url, [
                'cnpjConsulta' => $this->cnpj
            ]);

            if ($response->status() == 404) {
                return null;
            }

            if ($response->failed()) {
                throw new \Exception("Erro na Receita: " . $response->status());
            }
            
            return $response->json();
        } finally {
            if (File::exists($tempPem)) File::delete($tempPem);
        }
    }

    /**
     * Consulta as notas por período de data de emissão (Opcional/Histórico)
     */
    public function consultarPorPeriodo($dataInicio, $dataFim, $pagina = 1) {
        $tempPem = $this->gerarPem();
        try {
            $url = "https://adn.nfse.gov.br/contribuintes/DFe";
            
            $response = Http::withOptions([
                'cert' => [$tempPem, $this->senha],
                'ssl_key' => [$tempPem, $this->senha],
                'verify' => false,
                'timeout' => 60
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
            if (File::exists($tempPem)) File::delete($tempPem);
        }
    }

    private function gerarPem() {
        $certs = [];
        if (!openssl_pkcs12_read($this->certBlob, $certs, $this->senha)) {
            throw new \Exception("Senha do certificado incorreta no cadastro da empresa.");
        }
        $path = storage_path("app/temp_{$this->cnpj}.pem");
        File::put($path, $certs['cert'] . "\n" . $certs['pkey']);
        return $path;
    }
}