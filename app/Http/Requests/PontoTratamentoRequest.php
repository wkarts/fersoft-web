<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PontoTratamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'funcionario_id' => 'required|integer',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
        ];
    }
}
