<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Produto;
use App\Traits\FilialInjectable;

class TicketPesagem extends BaseModel
{
    use HasFactory, SoftDeletes, FilialInjectable;

    protected $table = 'tickets_pesagem';

    protected $fillable = [
        'pesagem_id',
        'empresa_id',
        'usuario_id',
        'filial_id',
        'veiculo_id',
        'motorista_id',
        'balanca_config_id',
        'produto_id',
        'peso',
        'peso_bag',
        'peso_origem',
        'valor_unitario',
        'valor_total',
        'valor_origem',
        'tipo',
        'status',
        'inicio',
        'fim',
        'observacoes',
        'token',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'fim'    => 'datetime',
    ];

    // Relacionamentos
    public function pesagem()
    {
        return $this->belongsTo(Pesagem::class, 'pesagem_id');
    }

    public function veiculo()
    {
        return $this->belongsTo(Veiculo::class, 'veiculo_id');
    }

    public function motorista()
    {
        return $this->belongsTo(Funcionario::class, 'motorista_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function filial(){
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    // Mutator para formatar o status
    public function getStatusLabelAttribute()
    {
        return ucfirst($this->status);
    }
}
