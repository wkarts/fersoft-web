<?php

namespace App\Services\Sped;

/**
 * Leitor streaming simples de SPED (linha a linha).
 * Para cada linha, expõe:
 *  - $reg : string do registro, ex.: '0000','C100','C190','9900',...
 *  - $cols: array com as colunas (sem o pipe inicial e sem o pipe final).
 */
class SpedStreamReader
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @return \Generator<object{reg:string, cols:string[]}>
     */
    public function lines(): \Generator
    {
        $h = fopen($this->path, 'r');
        if (!$h) {
            throw new \RuntimeException("Não foi possível abrir arquivo SPED: {$this->path}");
        }

        while (($line = fgets($h)) !== false) {
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                continue;
            }
            // linha SPED: |REG|col1|col2|...|
            // removemos o primeiro pipe e quebramos
            if ($line[0] === '|') $line = substr($line, 1);
            $parts = explode('|', $line);
            if (count($parts) === 0) continue;

            $reg = $parts[0];
            $cols = array_slice($parts, 1);

            yield (object)[
                'reg'  => $reg,
                'cols' => $cols
            ];
        }

        fclose($h);
    }
}
