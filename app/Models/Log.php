<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Filial;

class Log extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'filial_id',
        'acao',
        'modelo',
        'dados_anteriores',
        'dados_depois',
        'ip_address',
        'user_agent',
        'token'
    ];

    protected $casts = [
        // Converte JSON automaticamente para array no PHP
        'dados_anteriores' => 'array',
        'dados_depois' => 'array',
    ];

    // Relacionamento com usuário
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // Relacionamento com empresa (tenant)
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function filial()
    {
        return $this->belongsTo(Filial::class, 'filial_id');
    }
}
