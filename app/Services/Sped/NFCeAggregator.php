<?php

namespace App\Services\Sped;

use DateTimeImmutable;

final class NFCeAggregator
{
    public function __construct(
        private readonly bool $onlyAuthorized = true, // COD_SIT=00
        private readonly string $codVer = '016',
        private readonly string $codFin = '0',
    ) {}

    /**
     * @return array{txt:string, dtIni:\DateTimeImmutable, dtFin:\DateTimeImmutable,
     *               totalLinhas:int, totalNfce:int, totalGroups:int}
     */
    public function aggregateWithStats(string $inputPath, array $header0000): array
    {
        $reader = new SpedStreamReader($inputPath);
        $byDate = [];
        $totalLinhas = 0;
        $totalNfce = 0;

        $currentNote = null;

        foreach ($reader->lines() as $ln) {
            $totalLinhas++;

            switch ($ln->reg) {
                case 'C100':
                    // índices baseados no layout (mais comuns):
                    $cod_mod = $ln->cols[2] ?? ''; // 65 NFC-e
                    $cod_sit = $ln->cols[3] ?? ''; // 00 regular
                    $dt_doc  = $ln->cols[7] ?? ''; // DDMMAAAA
                    if ($cod_mod === '65' && (!$this->onlyAuthorized || $cod_sit === '00')) {
                        $ymd = $this->ddmmyyyyToYmd($dt_doc);
                        $currentNote = ['date' => $ymd, 'cod_sit' => $cod_sit];
                        $totalNfce++;
                    } else {
                        $currentNote = null;
                    }
                    break;

                case 'C190':
                    if (!$currentNote) break;
                    $cst     = $ln->cols[0] ?? '';
                    $cfop    = $ln->cols[1] ?? '';
                    $aliq    = $ln->cols[2] ?? '';
                    $vl_opr  = $this->toFloat($ln->cols[3] ?? '0');
                    $vl_bc   = $this->toFloat($ln->cols[4] ?? '0');
                    $vl_icms = $this->toFloat($ln->cols[5] ?? '0');

                    $groupKey = implode('|', ['CFOP'.$cfop, 'CST'.$cst, 'ALIQ'.$aliq]);

                    $d = $currentNote['date'];

                    if (!isset($byDate[$d])) $byDate[$d] = [];
                    if (!isset($byDate[$d][$groupKey])) {
                        $byDate[$d][$groupKey] = [
                            'cfop'    => $cfop,
                            'cst'     => $cst,
                            'aliq'    => $aliq,
                            'vl_opr'  => 0.0,
                            'vl_bc'   => 0.0,
                            'vl_icms' => 0.0,
                        ];
                    }
                    $byDate[$d][$groupKey]['vl_opr']  += $vl_opr;
                    $byDate[$d][$groupKey]['vl_bc']   += $vl_bc;
                    $byDate[$d][$groupKey]['vl_icms'] += $vl_icms;
                    break;
            }
        }

        // constrói novo TXT
        $b = new EfdBuilderLite();

        // Bloco 0 com dados do 0000 original (quando disponível)
        $dtIni = $this->bestRangeStart(array_keys($byDate));
        $dtFin = $this->bestRangeEnd(array_keys($byDate));

        $b->add('0000', [
            $this->codVer,
            $this->codFin,
            $dtIni->format('dmY'),
            $dtFin->format('dmY'),
            $header0000['nome'] ?? 'ARQUIVO INTERNO AGREGADO NFC-E',
            $header0000['cnpj'] ?? '00000000000000',
            $header0000['uf']   ?? 'SP',
            $header0000['cod_mun'] ?? '3550308',
            $header0000['ie'] ?? 'ISENTO',
            $header0000['im'] ?? '',
            $header0000['suframa'] ?? '0',
        ]);
        $b->add('0001', ['0']);
        $b->closeBlock('0');

        // Bloco C
        ksort($byDate);
        $b->add('C001', ['0']);

        $totalGroups = 0;

        foreach ($byDate as $ymd => $groups) {
            $date = new DateTimeImmutable($ymd);
            $ddmmyyyy = $date->format('dmY');
            $sumDia   = array_reduce($groups, fn($c,$g)=>$c+$g['vl_opr'], 0.0);

            // C100 sintético por dia
            $b->add('C100', [
                '1','0','65','00','', 'S'.$date->format('Ymd'), '', // SER, NUM_DOC, CHV
                $ddmmyyyy,$ddmmyyyy,
                EfdBuilderLite::fmtDec($sumDia,2),
                '01','0','0','0','0','0','0','0','0','0','','','','','',
            ]);

            ksort($groups);
            foreach ($groups as $g) {
                $b->add('C190', [
                    $g['cst'],
                    $g['cfop'],
                    str_replace('.', ',', (string)$g['aliq']),
                    EfdBuilderLite::fmtDec($g['vl_opr'],2),
                    EfdBuilderLite::fmtDec($g['vl_bc'],2),
                    EfdBuilderLite::fmtDec($g['vl_icms'],2),
                    '0','0','0','0','',
                ]);
                $totalGroups++;
            }
        }
        $b->closeBlock('C');

        // Bloco 9
        $b->finalize();

        return [
            'txt' => $b->toTxt(),
            'dtIni' => $dtIni,
            'dtFin' => $dtFin,
            'totalLinhas' => $totalLinhas,
            'totalNfce' => $totalNfce,
            'totalGroups' => $totalGroups,
        ];
    }

    private function ddmmyyyyToYmd(string $ddmmyyyy): string
    {
        if (strlen($ddmmyyyy) !== 8) return '19700101';
        return substr($ddmmyyyy, 4, 4) . substr($ddmmyyyy, 2, 2) . substr($ddmmyyyy, 0, 2);
    }

    private function toFloat(string $v): float
    {
        $v = str_replace(['.', ' '], ['', ''], $v);
        $v = str_replace(',', '.', $v);
        if ($v === '' || $v === null) return 0.0;
        return (float)$v;
    }

    private function bestRangeStart(array $ymds): \DateTimeImmutable
    {
        sort($ymds);
        return new \DateTimeImmutable($ymds[0] ?? 'first day of this month');
    }

    private function bestRangeEnd(array $ymds): \DateTimeImmutable
    {
        rsort($ymds);
        return new \DateTimeImmutable($ymds[0] ?? 'last day of this month');
    }
}
