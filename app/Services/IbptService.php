<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class IbptService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('IBPT_URL'), '/') . '/';
    }

    /**
     * Baixa o CSV da API IBPT baseado na UF.
     *
     * @param string $uf
     * @return string|bool Retorna o caminho do arquivo salvo ou false em caso de falha.
     */
    public function baixarCsvPorUf($uf)
    {
        $uf = strtolower($uf);
        $url = $this->baseUrl . $uf;

        try {
            $response = Http::get($url);
            if ($response->successful()) {
                $csrfToken = md5(uniqid(rand(), true)) . session()->token();
                $filePath = public_path("tmp_files/RL{$csrfToken}_{$uf}.csv");

                safe_file_put_contents($filePath, $response->body());
                return $filePath;
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * Lê o CSV e retorna os dados como array.
     *
     * @param string $filePath
     * @return array
     */
    public function lerCsv($filePath)
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $dados = [];
        if (($handle = fopen($filePath, "r")) !== false) {
            $header = fgetcsv($handle, 1000, ";"); // Ignorar cabeçalho
            while (($line = fgetcsv($handle, 1000, ";")) !== false) {
                $dados[] = [
                    'codigo' => $line[0],
                    'descricao' => $line[3],
                    'nacional_federal' => (float)$line[4],
                    'importado_federal' => (float)$line[5],
                    'estadual' => (float)$line[6],
                    'municipal' => (float)$line[7],
                    'vigencia_inicio' => $line[8],
                    'vigencia_fim' => $line[9],
                    'chave' => $line[10],
                    'versao' => $line[11],
                    'fonte' => $line[12]
                ];
            }
            fclose($handle);
        }

        return $dados;
    }

    /**
     * Realiza a consulta de tributos compatível com a chamada antiga.
     *
     * @param array $data
     * @return object|null
     */
    public function consulta($data)
    {
        $uf = $data['uf'] ?? null;
        $ncmBuscado = $data['ncm'] ?? null;

        if (!$uf || !$ncmBuscado) {
            return null;
        }

        // Baixa o CSV correspondente à UF
        $filePath = $this->baixarCsvPorUf($uf);
        if (!$filePath) {
            return null;
        }

        // Lê os dados do CSV
        $registros = $this->lerCsv($filePath);

        // Remove o arquivo temporário após a leitura
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        // Procura o NCM correspondente nos dados do CSV
        foreach ($registros as $row) {
            // Limpa pontos ou formatações do NCM se necessário
            $codigoLimpo = preg_replace('/[^0-9]/', '', $row['codigo']);
            if ($codigoLimpo === $ncmBuscado) {
                return (object) [
                    'Codigo' => $row['codigo'],
                    'UF' => strtoupper($uf),
                    'Descricao' => $row['descricao'],
                    'Nacional' => $row['nacional_federal'],
                    'Estadual' => $row['estadual'],
                    'Importado' => $row['importado_federal'],
                    'Municipal' => $row['municipal'],
                    'VigenciaInicio' => $row['vigencia_inicio'],
                    'VigenciaFim' => $row['vigencia_fim'],
                    'Chave' => $row['chave'],
                    'Versao' => $row['versao'],
                    'Fonte' => $row['fonte']
                ];
            }
        }

        return null;
    }
}
