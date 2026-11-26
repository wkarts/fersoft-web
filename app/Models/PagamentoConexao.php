<?php

namespace App\Models;

use Argws\ElectronicPayments\Account;
use Argws\ElectronicPayments\Environment;
use Illuminate\Database\Eloquent\Model;

class PagamentoConexao extends Model
{
    protected $table = 'pagamento_conexoes';
    protected $guarded = ['id'];
    protected $hidden = ['credencial', 'segredo'];
    protected $casts = ['credencial' => 'encrypted', 'segredo' => 'encrypted', 'selecionada' => 'boolean'];

    public function account(): Account
    {
        return new Account((int) $this->empresa_id, $this->local ? (int) $this->local : null,
            $this->webhook_id, Environment::from($this->ambiente));
    }
}
