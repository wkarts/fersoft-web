<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesagemAvulsa extends Model
{
    protected $table = 'pesagens_avulsas';

    protected $fillable = [
        'empresa_id',
        'filial_id',
        'usuario_id',
        'cliente_id',
        'com_itens',
        'motorista_nome',
        'placa_veiculo',
        'placa_carreta',
        'peso_bruto',
        'tara',
        'peso_liquido',
        'valor_servico',
        'observacao',
        'status',
        'conta_receber_id',
        'balanca_evidence_json',
        'camera_snapshots_json',
        'token',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function itens()
    {
        return $this->hasMany(PesagemAvulsaItem::class, 'pesagem_avulsa_id')->orderBy('id', 'asc');
    }
}
