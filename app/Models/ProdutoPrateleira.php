<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\FilialInjectable;

class ProdutoPrateleira extends BaseModel
{
    use SoftDeletes, FilialInjectable;

    protected $table = 'produto_prateleiras';

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'filial_id',
        'identificacao',
        'descricao',
        'posicao',
        'localizacao',
        'observacao',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at', // para soft deletes
    ];

    /**
     * Normaliza a identificação em maiúsculas e sem espaços extras.
     */
    public function setIdentificacaoAttribute($value)
    {
        $this->attributes['identificacao'] = strtoupper(trim($value));
    }

    // Relacionamentos (se necessário)
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function filial()
    {
        return $this->belongsTo(Filial::class, 'filial_id');
    }
}
