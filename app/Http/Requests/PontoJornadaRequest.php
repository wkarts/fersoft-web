<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PontoJornadaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:120',
            'regras_semana' => 'nullable|array',
            'tolerancia_atraso_min' => 'nullable|integer|min:0|max:240',
            'tolerancia_extra_min' => 'nullable|integer|min:0|max:240',
            'ativo' => 'nullable|boolean',
        ];
    }
}
