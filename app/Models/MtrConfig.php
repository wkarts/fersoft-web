<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class MtrConfig extends BaseModel
{
    protected $table = 'mtr_configs';

    protected $fillable = [
        'empresa_id', 'filial_id', 'usuario_id', 'orgao', 'cpf_cnpj',
        'cpf_usuario', 'senha', 'unidade_id', 'perfil', 'descricao',
        'ambiente', 'ativo',
    ];

    protected $hidden = ['senha'];

    protected $casts = [
        'ativo' => 'boolean',
        'empresa_id' => 'integer',
        'filial_id' => 'integer',
        'usuario_id' => 'integer',
    ];

    public function filial()
    {
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function getFilialNomeAttribute(): ?string
    {
        return $this->filial?->descricao
            ?? $this->filial?->nome;
    }

    protected function senha(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value === null || $value === '') {
                    return null;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (\Throwable $e) {
                    // Compatibilidade com credenciais gravadas antes da criptografia.
                    return $value;
                }
            },
            set: function ($value) {
                if ($value === null || $value === '') {
                    return null;
                }

                return Crypt::encryptString((string) $value);
            }
        );
    }

    public static function securityResource(): array
    {
        return array_replace_recursive(parent::securityResource(), [
            'module' => 'MTR',
            'name' => 'Credencial MTR',
            'plural_name' => 'Credenciais MTR',
            'route_prefix' => 'mtr/unidades',
            'sensitive' => true,
            'tenant_visible' => true,
        ]);
    }
}
