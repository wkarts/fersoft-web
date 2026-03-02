<?php

namespace App\Services\Ponto;

class PontoAfdParserService
{
    public function parseLine(string $line): array
    {
        $line = trim($line);

        $pis = $this->extractDigits($line, '/\b\d{11}\b/');
        $cpf = $this->extractCpf($line);
        $matricula = $this->extractMatricula($line);
        $dataHora = $this->extractDateTime($line);

        return [
            'tipo_registro' => strlen($line) > 0 ? substr($line, 0, 1) : null,
            'pis' => $pis,
            'cpf' => $cpf,
            'matricula' => $matricula,
            'data_hora_marcacao' => $dataHora,
            'inconsistente' => $dataHora === null,
            'motivo_inconsistencia' => $dataHora === null ? 'Data/hora de marcação não identificada na linha AFD' : null,
            'dados_parseados' => [
                'tamanho_linha' => strlen($line),
            ],
            'linha_bruta' => $line,
        ];
    }

    private function extractDigits(string $line, string $pattern): ?string
    {
        if (preg_match($pattern, $line, $matches)) {
            return $matches[0];
        }

        return null;
    }

    private function extractCpf(string $line): ?string
    {
        if (preg_match('/\b\d{3}\.\d{3}\.\d{3}\-\d{2}\b/', $line, $matches)) {
            return preg_replace('/\D/', '', $matches[0]);
        }

        return null;
    }

    private function extractMatricula(string $line): ?string
    {
        if (preg_match('/MAT[:=\s]+([A-Z0-9\-\/]+)/i', $line, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractDateTime(string $line): ?string
    {
        $patterns = [
            '/(^|\D)(\d{2})(\d{2})(\d{4})(\d{2})(\d{2})(\d{2})(\D|$)/',
            '/\b(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2})\:(\d{2})(?:\:(\d{2}))?\b/',
            '/\b(\d{4})\-(\d{2})\-(\d{2})\s+(\d{2})\:(\d{2})(?:\:(\d{2}))?\b/',
        ];

        foreach ($patterns as $index => $pattern) {
            if (!preg_match($pattern, $line, $matches)) {
                continue;
            }

            if ($index === 0) {
                return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $matches[4], $matches[3], $matches[2], $matches[5], $matches[6], $matches[7]);
            }

            if ($index === 1) {
                $second = $matches[6] ?? '00';
                return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $matches[3], $matches[2], $matches[1], $matches[4], $matches[5], $second);
            }

            $second = $matches[6] ?? '00';
            return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $second);
        }

        return null;
    }
}
