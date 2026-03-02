<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PontoEscalaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:120',
            'tipo' => 'required|string|max:40',
            'vigencia_inicio' => 'nullable|date',
            'vigencia_fim' => 'nullable|date|after_or_equal:vigencia_inicio',
            'regras' => 'nullable|array',
            'ativo' => 'nullable|boolean',
        ];
    }
}
