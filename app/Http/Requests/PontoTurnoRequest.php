<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PontoTurnoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:120',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i',
            'intervalo_minutos' => 'nullable|integer|min:0|max:600',
            'cruza_meia_noite' => 'nullable|boolean',
            'ativo' => 'nullable|boolean',
        ];
    }
}
