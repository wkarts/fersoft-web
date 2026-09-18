<?php

namespace App\Models;

//use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\SoftDeletes;

class GrupoCategoria extends BaseModel
{
    //use SoftDeletes;

    protected $table = 'grupo_categorias';

    protected $fillable = [
        'nome',
        'descricao',
        'empresa_id',
        'filial_id',
        'usuario_id',
    ];

    /**
     * Categorias vinculadas ao grupo
     */
    public function categorias()
    {
        return $table = $this->belongsToMany(
            CategoriaConta::class,
            'categoria_conta_grupo',
            'grupo_categoria_id',
            'categoria_conta_id'
        )->withTimestamps();
    }
}