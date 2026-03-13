<?php

namespace App\Services\Sped;

use Illuminate\Support\Str;

final class ConsolidarNfceFromSpedService
{
    public function read0000Header(string $inputPath): array
    {
        $reader = new SpedStreamReader($inputPath);
        foreach ($reader->lines() as $ln) {
            if ($ln->reg === '0000') {
                return [
                    'cod_ver' => $ln->cols[0] ?? '',
                    'cod_fin' => $ln->cols[1] ?? '',
                    'dt_ini'  => $ln->cols[2] ?? '',
                    'dt_fin'  => $ln->cols[3] ?? '',
                    'nome'    => $ln->cols[4] ?? '',
                    'cnpj'    => preg_replace('/\D+/', '', ($ln->cols[5] ?? '')),
                    'uf'      => $ln->cols[6] ?? '',
                    'cod_mun' => $ln->cols[7] ?? '',
                    'ie'      => $ln->cols[8] ?? '',
                    'im'      => $ln->cols[9] ?? '',
                    'suframa' => $ln->cols[10] ?? '',
                ];
            }
            static $guard = 0; $guard++;
            if ($guard > 200) break;
        }
        return [
            'cnpj' => '00000000000000',
            'uf'   => 'SP',
            'nome' => 'DESCONHECIDO',
            'cod_mun' => '3550308',
            'ie' => 'ISENTO',
            'im' => '',
            'suframa' => '0',
        ];
    }

    /**
     * Processa o upload, agrega NFC-e e grava os dois arquivos na pasta pública única.
     * @param string $uploadedTmp Caminho absoluto do arquivo temporário (upload)
     * @param string $origFilename Nome original do upload (para histórico)
     * @param array  $ctx ['empresa_id','usuario_id','filial_id']
     * @return array payload para persistência
     */
    public function processar(string $uploadedTmp, string $origFilename, array $ctx): array
    {
        // 1) Extrai cabeçalho 0000 para obter CNPJ
        $header = $this->read0000Header($uploadedTmp);
        $cnpj   = $header['cnpj'] ?: '00000000000000';

        // 2) Pasta única
        $folder = public_path('sped_nfce_consolidado');
        if (!is_dir($folder)) {
            @mkdir($folder, 0775, true);
        }

        // 3) Prefixo e token
        $stamp = date('Ymd_His');
        $token = Str::lower(Str::random(8)); // ex.: 8 chars

        $prefix = "{$cnpj}_sped_nfce_consolidado_{$stamp}_{$token}";

        // 4) Salva cópia do original
        $inputFilename = "{$prefix}_original.txt";
        $inputFullPath = $folder . DIRECTORY_SEPARATOR . $inputFilename;

        if (!@copy($uploadedTmp, $inputFullPath)) {
            @rename($uploadedTmp, $inputFullPath);
        }

        // 5) Agrega NFC-e
        $agg = new NFCeAggregator(onlyAuthorized: true);
        $result = $agg->aggregateWithStats($inputFullPath, $header);

        // 6) Grava consolidado
        $outputFilename = "{$prefix}_consolidado.txt";
        $outputFullPath = $folder . DIRECTORY_SEPARATOR . $outputFilename;
        safe_file_put_contents($outputFullPath, $result['txt']);

        // 7) Métricas e hashes
        $inputSize  = @filesize($inputFullPath) ?: null;
        $outputSize = @filesize($outputFullPath) ?: null;
        $inputSha1  = @sha1_file($inputFullPath) ?: null;
        $outputSha1 = @sha1_file($outputFullPath) ?: null;

        return [
            'empresa_id' => $ctx['empresa_id'],
            'filial_id'  => $ctx['filial_id'] ?? null,
            'usuario_id' => $ctx['usuario_id'],

            'cnpj' => $cnpj,
            'periodo_inicio' => $result['dtIni']->format('Y-m-d'),
            'periodo_fim'    => $result['dtFin']->format('Y-m-d'),

            // banco guarda o nome original do upload (informativo) + paths reais
            'input_filename' => $origFilename,
            'input_path'     => $this->toPublicRelative($inputFullPath),
            'input_size'     => $inputSize,
            'input_sha1'     => $inputSha1,

            'output_filename'=> $outputFilename,
            'output_path'    => $this->toPublicRelative($outputFullPath),
            'output_size'    => $outputSize,
            'output_sha1'    => $outputSha1,

            'total_linhas_original'    => $result['totalLinhas'],
            'total_linhas_consolidado' => $this->countLinesFast($outputFullPath),
            'total_nfce_original'      => $result['totalNfce'],
            'total_grupos_consolidados'=> $result['totalGroups'],

            'status' => 'concluido',
            'meta' => [
                'arquivo_prefix' => $prefix,
                'empresa_nome' => $header['nome'] ?? null,
                'uf'       => $header['uf'] ?? null,
                'cod_mun'  => $header['cod_mun'] ?? null,
                'ie'       => $header['ie'] ?? null,
                'im'       => $header['im'] ?? null,
                'suframa'  => $header['suframa'] ?? null,
            ],
        ];
    }

    private function toPublicRelative(string $abs): string
    {
        $pub = public_path();
        $rel = ltrim(str_replace($pub, '', $abs), DIRECTORY_SEPARATOR);
        return str_replace(DIRECTORY_SEPARATOR, '/', $rel);
        // ex.: sped_nfce_consolidado/123_sped_nfce_consolidado_20251018_141150_abcd1234_consolidado.txt
    }

    private function countLinesFast(string $path): int
    {
        $f = @fopen($path, 'r');
        if (!$f) return 0;
        $count = 0;
        while (!feof($f)) {
            $buf = fread($f, 1024 * 1024);
            $count += substr_count($buf, "\n");
        }
        fclose($f);
        return $count;
    }
}
