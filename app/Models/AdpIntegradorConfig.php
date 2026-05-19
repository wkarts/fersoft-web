<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    public static function gerarTokenSeguro(int $size = 64): string
    {
        return Str::random($size);
    }

    public function garantirTokenGlobal(): bool
    {
        if (!empty($this->global_token)) {
            return false;
        }

        $this->global_token = self::gerarTokenSeguro();
        $this->global_token_enabled = true;
        $this->global_token_type = $this->global_token_type ?: 'x_adp_api_token';
        $this->global_token_header = $this->global_token_header ?: 'X-ADP-API-TOKEN';
        $this->save();

        Log::info('Token global ADP gerado automaticamente.', [
            'empresa_id' => $this->empresa_id,
            'integrador_config_id' => $this->id,
        ]);

        return true;
    }
}
