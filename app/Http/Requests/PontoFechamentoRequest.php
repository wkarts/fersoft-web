<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PontoFechamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'competencia' => 'required|date_format:Y-m',
            'observacoes' => 'nullable|string',
        ];
    }
}
