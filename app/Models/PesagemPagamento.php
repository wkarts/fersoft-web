<?php
namespace App\Models;

class PesagemPagamento extends BaseModel
{
    protected $table = 'pesagem_pagamentos';
    
    // O BaseModel já usa protected $guarded = ['id'], então não precisamos do $fillable extenso
    // Ele também já cuida do empresa_id e usuario_id sozinho pelo auth da sessão.

    public function pesagem() { 
        return $this->belongsTo(Pesagem::class, 'pesagem_id'); 
    }
    
    public function fornecedor() { 
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id'); 
    }
}