<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MovimentacaoVeiculo extends Model
{
    protected $table = 'movimentacoes_veiculos';

    protected $fillable = [
        'empresa_id', 'filial_id', 'veiculo_id', 'tipo_movimentacao_id',
        'motorista_id', 'ajudante_id', 'cliente_id', 'fornecedor_id',
        'km_inicial', 'km_final', 'data_hora_saida', 'data_hora_chegada', 
        'status', 'produto_id', 'quantidade_combustivel', 'valor_combustivel', 
        'arla_id', 'quantidade_arla', 'valor_arla',
        'tipo_abastecimento', 'destino', 'observacao'
    ];

    /* Relacionamentos */
    public function veiculo() { return $this->belongsTo(Veiculo::class, 'veiculo_id'); }
    public function motorista() { return $this->belongsTo(Funcionario::class, 'motorista_id'); }
    public function cliente() { return $this->belongsTo(Cliente::class, 'cliente_id'); }
    public function fornecedor() { return $this->belongsTo(Fornecedor::class, 'fornecedor_id'); }
    public function tipoMovimentacao() { return $this->belongsTo(TipoMovimentacao::class, 'tipo_movimentacao_id'); }

    /* --- Accessors (Formatadores para a Lista) --- */

    // 1. Status Formatado (Unificado e sem erro de duplicidade)
    public function getStatusFormatadoAttribute()
    {
        if ($this->status == 'finalizado' || !empty($this->km_final)) {
            return '<span class="badge badge-success">Finalizado</span>';
        }
        
        return '<span class="badge badge-warning">Em curso</span>';
    }

    // 2. Data de Saída
    public function getDataSaidaFormatadaAttribute()
   {
          // Adicionado o /Y para mostrar o ano
          return $this->data_hora_saida ? \Carbon\Carbon::parse($this->data_hora_saida)->format('d/m/Y H:i') : '---';
    }

    // 3. Data de Chegada
   public function getDataChegadaFormatadaAttribute()
    {
    // Adicionado o /Y para mostrar o ano
    return $this->data_hora_chegada ? \Carbon\Carbon::parse($this->data_hora_chegada)->format('d/m/Y H:i') : '---';
   }

    // 4. Nome do Cliente/Fornecedor ou Destino
    public function getEntidadeNomeAttribute()
    {
        $nome = '---';
        if ($this->cliente) {
            $nome = "Cli: " . ($this->cliente->razao_social ?? $this->cliente->nome);
        } elseif ($this->fornecedor) {
            $nome = "For: " . ($this->fornecedor->razao_social ?? $this->fornecedor->nome);
        } elseif ($this->destino) {
            $nome = $this->destino;
        }

        return Str::limit($nome, 25);
    }
  // 5. KM Inicial Formatado (remove os .00 e coloca ponto de milhar)
    public function getKmInicialFormatadoAttribute()
    {
        return number_format($this->km_inicial, 0, ',', '.');
    }

    // 6. KM Final Formatado
    public function getKmFinalFormatadoAttribute()
    {
        return $this->km_final > 0 ? number_format($this->km_final, 0, ',', '.') : '---';
    }
 
   // Relacionamento 1 para Muitos
    public function abastecimentos()
    {
        return $this->hasMany(AbastecimentoMovimentacao::class, 'movimentacao_id');
    }

    // ALTERE O NOME DESTA FUNÇÃO: Adicione "Formatado" para bater com o Controller
    public function getValorTotalGeralFormatadoAttribute() 
{
    // 1. Somamos os abastecimentos (usando a relação no plural)
    // Se não houver nada, ele assume 0
    $somaAbastecimento = $this->abastecimentos()->sum('valor_total') ?? 0;

    // 2. Somamos as despesas (usando a relação no plural)
    // Se não houver nada, ele assume 0
    $somaDespesas = $this->despesas()->sum('valor') ?? 0;

    // 3. Somamos os dois resultados
    $totalGeral = $somaAbastecimento + $somaDespesas;

    // 4. Retornamos formatado para o padrão brasileiro
    return 'R$ ' . number_format($totalGeral, 2, ',', '.');
}
    public function despesas() {
    return $this->hasMany(DespesaMovimentacao::class, 'movimentacao_id');
    }
}