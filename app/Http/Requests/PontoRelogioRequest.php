<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PontoRelogioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:100',
            'fabricante' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'local' => 'nullable|string|max:120',
            'tipo_origem' => 'required|string|max:50',
            'ativo' => 'nullable|boolean',
            'observacoes' => 'nullable|string',
        ];
    }
}
