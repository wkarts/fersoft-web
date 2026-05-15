<?php

namespace App\Models;

// Aqui mudamos a importação para puxar o seu BaseModel
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankStatementTransaction extends BaseModel
{
    use HasFactory;
    
    // ATENÇÃO: Removemos o trait SoftDeletes e o $fillable, 
    // pois o seu BaseModel já resolve tudo isso!

    // Nome da tabela no banco
    protected $table = 'bank_statement_transactions';

    // Ocultar campos desnecessários em retornos JSON
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Relação: Uma transação pertence a uma conta bancária
    public function contaBancaria()
	{
    return $this->belongsTo(ContaEmpresa::class, 'conta_bancaria_id');
	}

    // Relação: Uma transação pode estar vinculada a uma movimentação financeira (contas a pagar/receber)
    public function movimentacaoFinanceira()
    {
        return $this->belongsTo(MovimentacaoFinanceira::class, 'movimentacao_financeira_id'); 
    }
  
}