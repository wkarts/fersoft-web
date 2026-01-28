<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Empresa;
use App\Models\Cidade;
//use App\Models\BaseModel;

class EvoApiInstance extends Model
{
    use SoftDeletes;

    protected $table = 'evo_api_instances';

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'filial_id',
        'name',
        'api_key',
        'instance_id',
        'base_url',
        'ddi',
        'ddd',
        'version',
        'is_blocked',
    ];

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Empresa::class);
    }

}
