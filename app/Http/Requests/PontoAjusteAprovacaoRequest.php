<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PontoAjusteAprovacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ponto_ajuste_id' => 'required|integer',
            'status' => 'required|in:aprovado,reprovado',
            'parecer' => 'nullable|string',
            'nivel' => 'nullable|integer|min:1|max:5',
        ];
    }
}
