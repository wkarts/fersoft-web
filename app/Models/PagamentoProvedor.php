<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagamentoProvedor extends Model
{
    protected $table = 'pagamento_provedores';
    protected $fillable = ['empresa_id', 'local', 'provedor'];
}
