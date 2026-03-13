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
}
