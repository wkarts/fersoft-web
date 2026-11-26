<?php

namespace App\Models;

use Argws\ElectronicPayments\Account;
use Argws\ElectronicPayments\Environment;
use Illuminate\Database\Eloquent\Model;

class PagamentoConexao extends Model
{
    protected $table = 'pagamento_conexoes';
    protected $guarded = ['id'];
    protected $hidden = ['credencial', 'segredo', 'webhook_hash'];
    protected $casts = ['credencial' => 'encrypted', 'segredo' => 'encrypted', 'selecionada' => 'boolean', 'metodos' => 'array'];

    protected static function booted(): void
    {
        static::saving(function (self $conexao) {
            if ($conexao->isDirty('segredo')) {
                $conexao->webhook_hash = \Argws\ElectronicPayments\Asaas\AsaasProvider::webhookTokenHash($conexao->segredo);
            }
        });
    }

    public const METODOS = ['pix' => 'Pix', 'boleto' => 'Boleto', 'credit_card' => 'Cartão de crédito'];

    public function metodosAtivos(): array
    {
        return array_intersect_key(self::METODOS, array_flip($this->metodos ?? array_keys(self::METODOS)));
    }

    public function account(): Account
    {
        return new Account((int) $this->empresa_id, $this->local ? (int) $this->local : null,
            $this->webhook_id, Environment::from($this->ambiente));
    }
}
