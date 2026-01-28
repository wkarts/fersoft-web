<?php

namespace App\Services\Sped;

/**
 * Builder mínimo para remontar o arquivo EFD:
 *  - add($reg, $cols): adiciona um registro
 *  - closeBlock($blk): fecha blocos 0/C/D/E/G/H/K/1 emitindo X990 automaticamente
 *  - finalize(): reconstrói o Bloco 9 exatamente como seu SpedController::totalize()
 */
class EfdBuilderLite
{
    private array $lines = [];        // linhas prontas (strings com pipes e \r\n)
    private array $regCounts = [];    // contagem por REG (para montar 9900)
    private array $blockOpen = [];    // flags de bloco aberto (0,C,D,E,G,H,K,1)
    public  int   $totalLines = 0;    // total de linhas (para 9999)

    public function __construct()
    {
        $this->blockOpen = [
            '0' => false,
            'C' => false,
            'D' => false,
            'E' => false,
            'G' => false,
            'H' => false,
            'K' => false,
            '1' => false,
        ];
    }

    public static function fmtDec($v, int $dec = 2): string
    {
        // SPED usa vírgula
        return number_format((float)$v, $dec, ',', '');
    }

    public function add(string $reg, array $cols = []): void
    {
        // marca bloco aberto
        $blk = substr($reg, 0, 1);
        if (isset($this->blockOpen[$blk]) && $reg !== $blk.'990') {
            $this->blockOpen[$blk] = true;
        }

        // contabiliza
        if (!isset($this->regCounts[$reg])) $this->regCounts[$reg] = 0;
        $this->regCounts[$reg]++;

        // monta a linha (como no SPED)
        $line = '|' . $reg . '|';
        foreach ($cols as $c) {
            $line .= (string)$c . '|';
        }
        $this->lines[] = $line . "\r\n";
        $this->totalLines++;
    }

    /**
     * Fecha um bloco emitindo X990 com a contagem correta daquele bloco.
     * A contagem considera TODAS as linhas do bloco + a própria X990 (por isso +1).
     */
    public function closeBlock(string $blk): void
    {
        // se bloco não está aberto ou já tem X990 no regCounts, não duplique
        if (!isset($this->blockOpen[$blk]) || $this->blockOpen[$blk] === false) {
            return;
        }

        // conta as linhas do bloco pelo primeiro dígito do REG
        $qt = 0;
        foreach ($this->regCounts as $reg => $count) {
            if (substr($reg, 0, 1) === $blk) {
                $qt += $count;
            }
        }
        // +1 da própria X990
        $this->add($blk.'990', [ (string)($qt + 1) ]);

        // marca como fechado
        $this->blockOpen[$blk] = false;
    }

    /**
     * Reconstrói o Bloco 9 com a MESMA lógica do seu SpedController::totalize():
     *  - |9001|0|
     *  - |9900|REG|QTD| ... para todos REGs já emitidos
     *  - |9900|9001|1|
     *  - |9900|9900|N|  (N = quantidade de linhas 9900 emitidas até aqui + 3)
     *  - |9900|9990|1|
     *  - |9900|9999|1|
     *  - |9990|M|
     *  - |9999|TOTAL_LINHAS|
     */
    public function finalize(): void
    {
        // fecha quaisquer blocos que restaram abertos
        foreach (array_keys($this->blockOpen) as $blk) {
            if ($this->blockOpen[$blk]) {
                $this->closeBlock($blk);
            }
        }

        // |9001|0|
        $this->add('9001', ['0']);

        // contar quantas 9900 serão emitidas a partir dos REGs já existentes
        // preserva a "ordem de primeira aparição" que já está em regCounts
        $n = 0;
        foreach ($this->regCounts as $reg => $count) {
            // pula os próprios registros do bloco 9 que serão regenerados
            if ($reg === '9001' || $reg === '9900' || $reg === '9990' || $reg === '9999') {
                continue;
            }
            $this->add('9900', [$reg, (string)$count]);
            $n++;
        }

        // +1 para 9001 (consta nas 9900):
        $this->add('9900', ['9001', '1']);
        $n++;

        // |9900|9900|N+3|
        // (N 9900 já emitidas + as 3 a seguir: '9001','9990','9999' + ela mesma '9900')
        $this->add('9900', ['9900', (string)($n + 3)]);
        $n++;

        // |9900|9990|1|
        $this->add('9900', ['9990', '1']); $n++;

        // |9900|9999|1|
        $this->add('9900', ['9999', '1']); $n++;

        // |9990| (linhas do bloco 9) = 9001(1) + todas 9900($n) + 9990(1)
        $this->add('9990', [(string)(1 + $n + 1)]);

        // |9999| total de linhas (todas as linhas + esta 9999)
        $this->add('9999', [(string)($this->totalLines + 1)]);
    }

    public function toTxt(): string
    {
        return implode('', $this->lines);
    }
}
