<?php

namespace App\Support;

class NFeResponseFormatter
{
    public static function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_XML1, 'UTF-8');

        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        }

        return trim($value);
    }

    public static function format(array $data): array
    {
        $data['xMotivo'] = self::normalizeText($data['xMotivo'] ?? null);

        $cStat = $data['cStat'] ?? null;
        $xMotivo = $data['xMotivo'] ?? '';

        $data['mensagem'] = $cStat !== null
            ? sprintf('[%s] - %s', $cStat, $xMotivo)
            : $xMotivo;

        if (!empty($data['payload']) && is_array($data['payload'])) {
            foreach ($data['payload'] as $k => $row) {
                if (isset($row['fase'])) {
                    $data['payload'][$k]['fase'] = self::normalizeText($row['fase']);
                }
                if (isset($row['conteudo'])) {
                    $data['payload'][$k]['conteudo'] = self::normalizeText($row['conteudo']);
                }
            }
        }

        return $data;
    }
}
