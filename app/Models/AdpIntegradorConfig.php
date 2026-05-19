<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdpIntegradorConfig extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'adp_integrador_configs';

    protected $fillable = [
        'empresa_id', 'descricao', 'base_url', 'global_token', 'global_token_enabled',
        'global_token_type', 'global_token_header', 'timeout_ms', 'ativo',
    ];

    protected $casts = [
        'global_token' => 'encrypted',
        'global_token_enabled' => 'boolean',
        'ativo' => 'boolean',
    ];

    public function tokenMascarado(): ?string
    {
        if (!$this->global_token) return null;
        return str_repeat('*', 8) . substr($this->global_token, -4);
    }
}
