<?php

namespace App\Services\Sped;

final class Line
{
    public string $reg;
    /** @var string[] */
    public array $cols;

    public function __construct(string $line)
    {
        $parts = explode('|', rtrim($line, "\r\n"));
        if (count($parts) > 0 && $parts[0] === '') array_shift($parts);
        if (end($parts) === '') array_pop($parts);
        $this->reg  = $parts[0] ?? '';
        $this->cols = array_slice($parts, 1);
    }
}
