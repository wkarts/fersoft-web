<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidaDocumento implements Rule
{
    /** @var 'cpf'|'cnpj'|null */
    protected $tipo = null;

    /** @var string|null */
    protected $motivo = null;

    public function __construct()
    {
        //
    }

    public function passes($attribute, $value)
    {
        // Exceção para exterior (CNPJ zerado), com ou sem máscara
        if ($value === '00.000.000/0000-00') return true;
        if (preg_replace('/\D/', '', (string)$value) === '00000000000000') return true;

        $doc = preg_replace('/\D/', '', (string)$value);

        if (strlen($doc) < 11) {
            $this->tipo = 'cpf';
            $this->motivo = 'tamanho';
            return false;
        }

        if (strlen($doc) === 11) {
            $this->tipo = 'cpf';
            // Sequência repetida
            if (preg_match('/^(\d)\1{10}$/', $doc)) {
                $this->motivo = 'sequencia';
                return false;
            }
            // Dígitos verificadores
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $doc[$c] * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($doc[$c] != $d) {
                    $this->motivo = 'digitos';
                    return false;
                }
            }
            return true;
        }

        // CNPJ
        $this->tipo = 'cnpj';
        if (strlen($doc) !== 14) {
            $this->motivo = 'tamanho';
            return false;
        }
        if (preg_match('/^(\d)\1{13}$/', $doc)) {
            $this->motivo = 'sequencia';
            return false;
        }

        // Primeiro DV
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
            $soma += $doc[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        if ($doc[12] != ($resto < 2 ? 0 : 11 - $resto)) {
            $this->motivo = 'digitos';
            return false;
        }

        // Segundo DV
        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
            $soma += $doc[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;

        if ($doc[13] != ($resto < 2 ? 0 : 11 - $resto)) {
            $this->motivo = 'digitos';
            return false;
        }

        return true;
    }

    public function message()
    {
        // Mensagens específicas por tipo e motivo
        if ($this->tipo === 'cpf') {
            switch ($this->motivo) {
                case 'tamanho':   return 'CPF inválido. Informe 11 dígitos.';
                case 'sequencia': return 'CPF inválido (sequência repetida).';
                case 'digitos':   return 'CPF inválido (dígitos verificadores).';
                default:          return 'CPF inválido.';
            }
        }
        if ($this->tipo === 'cnpj') {
            switch ($this->motivo) {
                case 'tamanho':   return 'CNPJ inválido. Informe 14 dígitos.';
                case 'sequencia': return 'CNPJ inválido (sequência repetida).';
                case 'digitos':   return 'CNPJ inválido (dígitos verificadores).';
                default:          return 'CNPJ inválido.';
            }
        }
        return 'Documento inválido.';
    }
}
