<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagamentoCobranca extends Model
{
    protected $table = 'pagamento_cobrancas';
    protected $guarded = ['id'];

    protected $casts = ['url_estorno' => 'encrypted'];

    public function conexao()
    {
        return $this->belongsTo(PagamentoConexao::class, 'conexao_id');
    }
}
