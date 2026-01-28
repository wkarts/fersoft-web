<?php
namespace App\Utils;

use Illuminate\Support\Facades\Log;
class WhatsTokenGenerator
{
    /**
     * Gera secret key Base32 + prefix + cinco paddings fixos.
     *
     * @param  int  $length  Quantidade de bytes aleatórios (default 32)
     * @return string
     */
    public static function generateSecretKey(int $length = 32): string
    {
        // gera $length bytes aleatórios...
        $bytes = random_bytes($length);

        // ...e converte para HEX (0–9,A–F)
        $hex = strtoupper(bin2hex($bytes));

        // prefixo do .env, ex: "$wwtk_"
        $prefix = config('evoapi.token_prefix', '$wwtk_');

        // 5 paddings fixos
        return $prefix . $hex . str_repeat('=', 5);
    }

    public static function generateSecretKey_(int $length = 32): string
    {
        // 1) Bytes random
        $bytes = random_bytes($length);

        // 2) Alfabeto fixo do config
        $alphabet = str_split(config('evoapi.token_alphabet'));

        // 3) Base32 encode
        $b32 = self::base32Encode($bytes, $alphabet);

        // 4) Prefixo do env
        $prefix = config('evoapi.token_prefix', '$wwtk_');

        // 5) Cinco '=' no fim
        return $prefix . $b32 . str_repeat('=', 5);
    }

    /**
     * Base32 customizado: pega cada 5 bits e mapeia no alfabeto,
     * sem inserir "=" no meio, apenas no final para ajuste de bloco.
     */
    protected static function base32Encode(string $data, array $alphabet): string
    {
        $binary = '';
        foreach (str_split($data) as $c) {
            $binary .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($binary, 5) as $chunk) {
            $i = bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT));
            $encoded .= $alphabet[$i] ?? '';
        }

        return $encoded;
    }

};
