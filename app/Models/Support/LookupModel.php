<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class LookupModel extends Model
{
    use SoftDeletes;

    // Colunas padrão do seu padrão
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const DELETED_AT = 'deleted_at';

    /**
     * Como essas tabelas são de consulta e geralmente são populadas via seed/import,
     * não deixe o Eloquent tentar gerenciar timestamps automaticamente.
     */
    public $timestamps = false;

    /**
     * Cast comum (pode ser sobrescrito em cada model).
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'eloquent_uuid' => 'string',
    ];

    /**
     * Helper padrão para alimentar selects:
     * Ex.: BancoRef::options('DESCRICAO', 'CODIGO');
     */
    public static function options(string $labelColumn, ?string $keyColumn = null, bool $orderByLabel = true): array
    {
        $keyColumn = $keyColumn ?: (new static)->getKeyName();

        $q = static::query()->select([$keyColumn, $labelColumn]);

        if ($orderByLabel) {
            $q->orderBy($labelColumn);
        }

        return $q->pluck($labelColumn, $keyColumn)->toArray();
    }
}
