<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PontoAjusteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'funcionario_id' => 'required|integer',
            'ponto_marcacao_id' => 'nullable|integer',
            'tipo' => 'required|string|max:60',
            'data_hora_original' => 'nullable|date',
            'data_hora_nova' => 'nullable|date',
            'justificativa' => 'required|string|min:5',
        ];
    }
}
