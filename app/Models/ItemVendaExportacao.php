<?php

namespace App\Models;

class ItemVendaExportacao extends BaseModel
{
    protected $table = 'item_venda_exportacoes';

    protected $fillable = [
        'item_venda_id',
        'empresa_id',
        'filial_id',
        'nRE',
        'chNFe',
        'qExport'
    ];

    protected $casts = [
        'qExport' => 'decimal:4',
    ];

    public function itemVenda()
    {
        return $this->belongsTo(ItemVenda::class, 'item_venda_id');
    }
}
