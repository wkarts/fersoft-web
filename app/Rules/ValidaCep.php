<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidaCep implements Rule
{
    /** @var string|null */
    protected $motivo = null;

    /**
     * - Aceita com/sem máscara (XXXXX-XXX)
     * - 8 dígitos válidos
     * - Rejeita sequências repetidas
     */
    public function passes($attribute, $value)
    {
        $cep = preg_replace('/\D/', '', (string) $value ?? '');

        if (strlen($cep) !== 8) {
            $this->motivo = 'tamanho';
            return false;
        }

        if (preg_match('/^(\d)\1{7}$/', $cep)) {
            $this->motivo = 'sequencia';
            return false;
        }

        return true;
    }

    public function message()
    {
        switch ($this->motivo) {
            case 'tamanho':   return 'CEP inválido. Informe 8 dígitos.';
            case 'sequencia': return 'CEP inválido (sequência repetida).';
            default:          return 'CEP inválido.';
        }
    }
}
