<?php

namespace App\Services\Sped;

use DateTimeImmutable;
use Illuminate\Support\Str;

/**
 * Consolida SOMENTE SAÍDAS (IND_OPER=1) do modelo 65 (NFC-e; COD_SIT=00).
 * - Remove C100/filhos das NFC-e de saída
 * - Agrega C190 por DIA/CFOP/CST/ALIQ e injeta C100/C190 sintéticos
 * - Reconstrói X990 e Bloco 9 (via EfdBuilderLite::closeBlock/finalize)
 */
final class ConsolidarSomenteSaidaNfceService
{
    public function processar(string $uploadedTmp, string $origFilename, array $ctx): array
    {
        // 1) Cabeçalho para CNPJ/periodicidade
        $header = $this->read0000Header($uploadedTmp);
        $cnpj   = $header['cnpj'] ?: '00000000000000';

        // 2) Pasta + prefixo
        $folder = public_path('sped_nfce_consolidado');
        if (!is_dir($folder)) @mkdir($folder, 0775, true);

        $stamp  = date('Ymd_His');
        $token  = Str::lower(Str::random(8));
        $prefix = "{$cnpj}_sped_nfce_consolidado_{$stamp}_{$token}";

        // 3) Salvar original
        $inputFilename = "{$prefix}_original.txt";
        $inputFullPath = $folder . DIRECTORY_SEPARATOR . $inputFilename;
        if (!@copy($uploadedTmp, $inputFullPath)) {
            @rename($uploadedTmp, $inputFullPath);
        }

        // 4) Consolidar
        $result = $this->consolidarSomenteSaidaNfce($inputFullPath, $header);

        // 5) Gravar consolidado
        $outputFilename = "{$prefix}_consolidado.txt";
        $outputFullPath = $folder . DIRECTORY_SEPARATOR . $outputFilename;
        safe_file_put_contents($outputFullPath, $result['txt']);

        return [
            'empresa_id' => $ctx['empresa_id'],
            'filial_id'  => $ctx['filial_id'] ?? null,
            'usuario_id' => $ctx['usuario_id'],

            'cnpj' => $cnpj,
            'periodo_inicio' => $result['dtIni']?->format('Y-m-d'),
            'periodo_fim'    => $result['dtFin']?->format('Y-m-d'),

            'input_filename' => $origFilename,
            'input_path'     => $this->toPublicRelative($inputFullPath),
            'input_size'     => @filesize($inputFullPath) ?: null,
            'input_sha1'     => @sha1_file($inputFullPath) ?: null,

            'output_filename'=> $outputFilename,
            'output_path'    => $this->toPublicRelative($outputFullPath),
            'output_size'    => @filesize($outputFullPath) ?: null,
            'output_sha1'    => @sha1_file($outputFullPath) ?: null,

            'total_linhas_original'    => $result['totalLinhasIn'],
            'total_linhas_consolidado' => $result['totalLinhasOut'],
            'total_nfce_original'      => $result['totalNfceSaida'],
            'total_grupos_consolidados'=> $result['totalGroups'],

            'status' => 'concluido',
            'meta' => [
                'empresa_nome' => $header['nome'] ?? null,
                'uf'       => $header['uf'] ?? null,
                'cod_mun'  => $header['cod_mun'] ?? null,
                'ie'       => $header['ie'] ?? null,
                'im'       => $header['im'] ?? null,
                'suframa'  => $header['suframa'] ?? null,
            ],
        ];
    }

    // --- Núcleo ---

    private function consolidarSomenteSaidaNfce(string $inputPath, array $header0000): array
    {
        $r = new SpedStreamReader($inputPath);
        $b = new EfdBuilderLite();

        $skipNfce = false;
        $currentSkipDateYmd = null;

        // agregação por data
        $byDate = []; // [YYYYMMDD => [ "CFOPxxxx|CSTyy|ALIQzz,zz" => totals ]]
        $totalNfceSaida = 0;
        $dtMin = null; $dtMax = null;

        $totalLinhasIn = 0;

        foreach ($r->lines() as $ln) {
            $totalLinhasIn++;
            $reg = $ln->reg;
            $blk = substr($reg, 0, 1);

            // ignorar bloco 9 (será remontado)
            if ($blk === '9') continue;

            // ignorar X990 originais; emitir agregados antes do C990
            if (preg_match('/^[0CDEGHK1]990$/', $reg)) {
                if ($reg === 'C990' && !empty($byDate)) {
                    $this->emitirAgregados($b, $byDate, $totalGroups);
                    // limpa buffers/flags para não reemitir depois
                    $byDate = [];
                    $skipNfce = false;
                    $currentSkipDateYmd = null;
                }
                $b->closeBlock($blk);
                continue;
            }

            // BLOCO 0: passa direto
            if ($blk === '0') {
                $b->add($reg, $ln->cols);
                continue;
            }

            // BLOCO C: intercepta NFC-e saída
            if ($blk === 'C') {
                if ($reg === 'C001') {
                    $b->add('C001', ['0']);
                    $skipNfce = false;
                    $currentSkipDateYmd = null;
                    continue;
                }

                if ($reg === 'C100') {
                    // layout comum (016):
                    // [0]=IND_OPER (1=saída), [2]=COD_MOD (65=NFC-e), [3]=COD_SIT, [7]=DT_DOC (DDMMAAAA)
                    $ind_oper = $ln->cols[0] ?? '';
                    $cod_mod  = $ln->cols[2] ?? '';
                    $cod_sit  = $ln->cols[3] ?? '';
                    $dt_doc   = $ln->cols[7] ?? '';

                    if ($ind_oper === '1' && $cod_mod === '65' && $cod_sit === '00') {
                        $skipNfce = true;
                        $currentSkipDateYmd = $this->ddmmyyyyToYmd($dt_doc);

                        if ($currentSkipDateYmd) {
                            $dt = new DateTimeImmutable($currentSkipDateYmd);
                            $dtMin = $dtMin ? min($dtMin, $dt) : $dt;
                            $dtMax = $dtMax ? max($dtMax, $dt) : $dt;
                        }
                        $totalNfceSaida++;
                        continue; // não escreve este C100
                    } else {
                        // documentos que não são NFC-e de saída -> passa direto
                        $skipNfce = false;
                        $currentSkipDateYmd = null;
                        $b->add('C100', $ln->cols);
                        continue;
                    }
                }

                if ($skipNfce) {
                    // agrega apenas C190; demais filhos são ignorados
                    if ($reg === 'C190') {
                        $cst     = $ln->cols[0] ?? '';
                        $cfop    = $ln->cols[1] ?? '';
                        $aliq    = $ln->cols[2] ?? ''; // já vem com vírgula normalmente
                        $vl_opr  = $this->toFloat($ln->cols[3] ?? '0');
                        $vl_bc   = $this->toFloat($ln->cols[4] ?? '0');
                        $vl_icms = $this->toFloat($ln->cols[5] ?? '0');

                        if ($currentSkipDateYmd) {
                            $gkey = implode('|', ['CFOP'.$cfop,'CST'.$cst,'ALIQ'.$aliq]);
                            if (!isset($byDate[$currentSkipDateYmd][$gkey])) {
                                $byDate[$currentSkipDateYmd][$gkey] = [
                                    'cfop'=>$cfop,'cst'=>$cst,'aliq'=>$aliq,
                                    'vl_opr'=>0.0,'vl_bc'=>0.0,'vl_icms'=>0.0
                                ];
                            }
                            $byDate[$currentSkipDateYmd][$gkey]['vl_opr']  += $vl_opr;
                            $byDate[$currentSkipDateYmd][$gkey]['vl_bc']   += $vl_bc;
                            $byDate[$currentSkipDateYmd][$gkey]['vl_icms'] += $vl_icms;
                        }
                    }
                    continue;
                }

                // fora do modo skip, repassa
                $b->add($reg, $ln->cols);
                continue;
            }

            // Demais blocos: passar direto
            $b->add($reg, $ln->cols);
        }

        // Se terminou sem vir C990 e ainda há agregados pendentes, emite e fecha C
        if (!empty($byDate)) {
            $this->emitirAgregados($b, $byDate, $totalGroups);
            $byDate = [];
            $b->closeBlock('C');
        }

        // Bloco 9
        $b->finalize();

        $txt = $b->toTxt();

        // período
        $dtIni = $dtMin ?: $this->parseDdmmaaaaOrNull($header0000['dt_ini'] ?? null);
        $dtFin = $dtMax ?: $this->parseDdmmaaaaOrNull($header0000['dt_fin'] ?? null);

        return [
            'txt' => $txt,
            'dtIni' => $dtIni,
            'dtFin' => $dtFin,
            'totalLinhasIn'  => $totalLinhasIn,
            'totalLinhasOut' => substr_count($txt, "\n"),
            'totalNfceSaida' => $totalNfceSaida,
            'totalGroups'    => $totalGroups ?? 0,
        ];
    }

    private function emitirAgregados(EfdBuilderLite $b, array $byDate, ?int &$totalGroups): void
    {
        $totalGroups = 0;
        ksort($byDate); // por data crescente

        foreach ($byDate as $ymd => $groups) {
            $date = new DateTimeImmutable($ymd);
            $ddmmyyyy = $date->format('dmY');

            $sumDia = array_reduce($groups, fn($c,$g)=>$c+$g['vl_opr'], 0.0);

            // C100 sintético (saída)
            $b->add('C100', [
                '1','0','65','00','', 'S'.$date->format('Ymd'), '', // SER, NUM_DOC, CHV
                $ddmmyyyy,$ddmmyyyy,
                EfdBuilderLite::fmtDec($sumDia,2),
                '01','0','0','0','0','0','0','0','0','0','','','','','',
            ]);

            ksort($groups); // ordena chaves CFOP/CST/ALIQ
            foreach ($groups as $g) {
                $b->add('C190', [
                    $g['cst'],
                    $g['cfop'],
                    (string)$g['aliq'], // já em vírgula (mantém)
                    EfdBuilderLite::fmtDec($g['vl_opr'],2),
                    EfdBuilderLite::fmtDec($g['vl_bc'],2),
                    EfdBuilderLite::fmtDec($g['vl_icms'],2),
                    '0','0','0','0','',
                ]);
                $totalGroups++;
            }
        }
    }

    // --- Utilitários ---

    private function read0000Header(string $inputPath): array
    {
        $reader = new SpedStreamReader($inputPath);
        foreach ($reader->lines() as $ln) {
            if ($ln->reg === '0000') {
                return [
                    // COD_VER|COD_FIN|DT_INI|DT_FIN|NOME|CNPJ|UF|COD_MUN|IE|IM|SUFRAMA
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
        return ['cnpj'=>'00000000000000'];
    }

    private function ddmmyyyyToYmd(?string $ddmmyyyy): ?string
    {
        if (!$ddmmyyyy || strlen($ddmmyyyy) !== 8) return null;
        return substr($ddmmyyyy, 4, 4) . substr($ddmmyyyy, 2, 2) . substr($ddmmyyyy, 0, 2);
    }

    private function parseDdmmaaaaOrNull(?string $ddmmyyyy): ?DateTimeImmutable
    {
        $ymd = $this->ddmmyyyyToYmd($ddmmyyyy);
        return $ymd ? new DateTimeImmutable($ymd) : null;
    }

    private function toFloat(string $v): float
    {
        $v = str_replace(['.', ' '], ['', ''], $v);
        $v = str_replace(',', '.', $v);
        if ($v === '' || $v === null) return 0.0;
        return (float)$v;
    }

    private function toPublicRelative(string $abs): string
    {
        $pub = public_path();
        $rel = ltrim(str_replace($pub, '', $abs), DIRECTORY_SEPARATOR);
        return str_replace(DIRECTORY_SEPARATOR, '/', $rel);
    }
}
