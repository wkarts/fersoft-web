<?php

namespace App\Helpers;

class EncryptionHelper
{
    private static $key;
    private static $cipher = 'AES-256-CBC';

    /**
     * Inicializa a chave de criptografia a partir do .env ou variáveis de ambiente.
     *
     * @return void
     * @throws \Exception
     */
    public static function initialize()
    {
        // env() já lê do .env e também das variáveis de ambiente do processo (CI).
        $key = env('ENCRYPTION_KEY');

        // Fallback mínimo (sem mudar estrutura): caso o env() não resolva por algum motivo.
        if (!$key) {
            $key = getenv('ENCRYPTION_KEY') ?: null;
        }

        // Depuração: Verifica o valor bruto da chave
        //echo "Valor bruto da chave: $key\n";

        if (!$key) {
            throw new \Exception('ENCRYPTION_KEY não definido nas variáveis de ambiente (.env ou Secrets do CI).');
        }

        // Depuração: Verifica se a chave começa com "base64:"
        if (!str_starts_with($key, 'base64:')) {
            throw new \Exception('ENCRYPTION_KEY deve começar com "base64:".');
        }

        // Remove o prefixo "base64:" antes de decodificar
        $base64Key = substr($key, 7);

        // Depuração: Mostra o valor após a remoção do prefixo
        //echo "Chave após remover prefixo base64: $base64Key\n";

        $decodedKey = base64_decode($base64Key, true);

        // Depuração: Verifica se a decodificação foi bem-sucedida
        if (!$decodedKey) {
            throw new \Exception('Falha na decodificação da chave ENCRYPTION_KEY.');
        }

        // Depuração: Verifica o tamanho da chave decodificada
        $keyLength = strlen($decodedKey);

        //echo "Tamanho da chave decodificada: $keyLength\n";

        if ($keyLength !== 32) {
            throw new \Exception('ENCRYPTION_KEY deve ser uma string base64 válida com 32 bytes.');
        }

        self::$key = $decodedKey;
    }

    /**
     * Criptografa um valor.
     *
     * @param string $value
     * @return string
     * @throws \Exception
     */
    public static function encrypt($value)
    {
        if (!self::$key) {
            throw new \Exception('EncryptionHelper não foi inicializado. Chame EncryptionHelper::initialize() primeiro.');
        }

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$cipher));
        $encrypted = openssl_encrypt($value, self::$cipher, self::$key, 0, $iv);

        if ($encrypted === false) {
            throw new \Exception('Erro ao criptografar o valor.');
        }

        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Descriptografa um valor.
     *
     * @param string $value
     * @return string|null
     * @throws \Exception
     */
    public static function decrypt($value)
    {
        if (!self::$key) {
            throw new \Exception('EncryptionHelper não foi inicializado. Chame EncryptionHelper::initialize() primeiro.');
        }

        $data = explode('::', base64_decode($value), 2);

        if (count($data) !== 2) {
            return null;
        }

        [$encryptedData, $iv] = $data;
        $decrypted = openssl_decrypt($encryptedData, self::$cipher, self::$key, 0, $iv);

        if ($decrypted === false) {
            throw new \Exception('Erro ao descriptografar o valor.');
        }

        return $decrypted;
    }
}
