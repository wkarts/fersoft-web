<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class SpedNfceConsolidado extends BaseModel
{
    use SoftDeletes;

    protected $table = 'sped_nfce_consolidados';

    protected $fillable = [
        'empresa_id',
        'filial_id',
        'usuario_id',
        'cnpj',
        'periodo_inicio',
        'periodo_fim',
        'input_filename',
        'input_path',
        'input_size',
        'input_sha1',
        'output_filename',
        'output_path',
        'output_size',
        'output_sha1',
        'total_linhas_original',
        'total_linhas_consolidado',
        'total_nfce_original',
        'total_grupos_consolidados',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'periodo_inicio' => 'date',
        'periodo_fim' => 'date',
    ];

    // Helpers para gerar URL pública
    public function getInputUrlAttribute(): string
    {
        return asset($this->input_path);
    }

    public function getOutputUrlAttribute(): string
    {
        return asset($this->output_path);
    }
}
