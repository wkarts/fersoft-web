<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PontoAfdImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'arquivo_afd' => 'required|file|mimes:txt,afd,dat,csv',
            'ponto_relogio_id' => 'nullable|integer',
        ];
    }
}
