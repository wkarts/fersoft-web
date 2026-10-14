<?php

namespace App\Models;

/**
 * Catálogo único de referência MTR, independente de empresa, filial ou usuário.
 * As colunas de vínculo existem apenas por compatibilidade com a migration
 * histórica. Nenhuma operação deste model pode preenchê-las.
 */
class MtrResiduo extends BaseModel
{
    protected $table = 'mtr_residuos';

    protected $fillable = [
        'res_codigo',
        'res_codigo_ibama',
        'res_descricao',
        'grr_descricao',
        'grr_representacao',
    ];

    protected static function booted(): void
    {
        static::saving(static function (self $residuo): void {
            // Proteção para gravações explícitas; o TenantInjectable também
            // deixa de injetar IDs porque eles não fazem parte do fillable.
            $residuo->empresa_id = null;
            $residuo->filial_id = null;
            $residuo->usuario_id = null;
        });
    }
}
