<?php

namespace App\Models;

use Illuminate\Support\Str;
use Carbon\Carbon;

class MovimentacaoVeiculo extends BaseModel
{
    protected $table = 'movimentacoes_veiculos';

    protected $fillable = [
        'empresa_id',
        'filial_id',
        'veiculo_id',
        'tipo_movimentacao_id',
        'motorista_id',
        'ajudante_id',
        'cliente_id',
        'fornecedor_id',
        'km_inicial',
        'km_final',
        'data_hora_saida',
        'data_hora_chegada',
        'status',
        'produto_id',
        'quantidade_combustivel',
        'valor_combustivel',
        'arla_id',
        'quantidade_arla',
        'valor_arla',
        'checklist_obrigatorio',
        'tipo_partida',
        'bater_ponto_whatsapp',
        'tipo_abastecimento',
        'destino',
        'observacao',
        'latitude_destino',
        'longitude_destino',
        'data_hora_chegada_cliente',
        'data_hora_saida_real',
        'data_hora_saida_cliente',
        'motivo_cancelamento',
        'bater_ponto_whatsapp',
        'data_cancelamento',
    ];

    /* Relacionamentos */

    public function veiculo()
    {
        return $this->belongsTo(Veiculo::class, 'veiculo_id');
    }

    public function motorista()
    {
        return $this->belongsTo(Funcionario::class, 'motorista_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function tipoMovimentacao()
    {
        return $this->belongsTo(
            TipoMovimentacao::class,
            'tipo_movimentacao_id'
        );
    }

    /*
     * Relacionamento com múltiplos ajudantes.
     *
     * O campo ajudante_id pode continuar existindo normalmente
     * para compatibilidade, ajudante principal ou implementação legada.
     *
     * Este relacionamento utiliza a tabela pivô movimentacao_ajudantes.
     */
    public function ajudantes()
    {
        return $this->belongsToMany(
            Funcionario::class,
            'movimentacao_ajudantes',
            'movimentacao_id',
            'funcionario_id'
        );
    }

    // Relacionamento 1 para Muitos
    public function abastecimentos()
    {
        return $this->hasMany(
            AbastecimentoMovimentacao::class,
            'movimentacao_id'
        );
    }

    public function despesas()
    {
        return $this->hasMany(
            DespesaMovimentacao::class,
            'movimentacao_id'
        );
    }

    /* --- Accessors (Formatadores para a Lista e Agenda) --- */

    // 1. Status Formatado
    public function getStatusFormatadoAttribute()
    {
        if ($this->status == 'cancelado') {
            return '<span class="badge badge-danger">Cancelado</span>';
        }

        if ($this->status == 'agendado') {
            return '<span class="badge badge-info">Agendado</span>';
        }

        if (
            $this->status == 'finalizado'
            || $this->status == 'concluida'
            || !empty($this->km_final)
        ) {
            return '<span class="badge badge-success">Finalizado</span>';
        }

        return '<span class="badge badge-warning">Em curso</span>';
    }

    // 2. Data de Saída
    public function getDataSaidaFormatadaAttribute()
    {
        // Adicionado o /Y para mostrar o ano
        return $this->data_hora_saida
            ? Carbon::parse($this->data_hora_saida)->format('d/m/Y H:i')
            : '---';
    }

    // 3. Data de Chegada
    public function getDataChegadaFormatadaAttribute()
    {
        // Adicionado o /Y para mostrar o ano
        return $this->data_hora_chegada
            ? Carbon::parse($this->data_hora_chegada)->format('d/m/Y H:i')
            : '---';
    }

    // Data/Hora de Chegada no Cliente
    public function getDataChegadaClienteFormatadaAttribute()
    {
        return $this->data_hora_chegada_cliente
            ? Carbon::parse($this->data_hora_chegada_cliente)->format('d/m/Y H:i')
            : '---';
    }

    // Data/Hora de Saída do Cliente
    public function getDataSaidaClienteFormatadaAttribute()
    {
        return $this->data_hora_saida_cliente
            ? Carbon::parse($this->data_hora_saida_cliente)->format('d/m/Y H:i')
            : '---';
    }

    // Data/Hora de Saída Real
    public function getDataSaidaRealFormatadaAttribute()
    {
        return $this->data_hora_saida_real
            ? Carbon::parse($this->data_hora_saida_real)->format('d/m/Y H:i')
            : '---';
    }

    // 4. Nome do Cliente/Fornecedor ou Destino
    public function getEntidadeNomeAttribute()
    {
        $nome = '---';

        if ($this->cliente) {
            $nome = 'Cli: ' . (
                    $this->cliente->razao_social
                    ?? $this->cliente->nome
                );
        } elseif ($this->fornecedor) {
            $nome = 'For: ' . (
                    $this->fornecedor->razao_social
                    ?? $this->fornecedor->nome
                );
        } elseif ($this->destino) {
            $nome = $this->destino;
        }

        return Str::limit($nome, 25);
    }

    // 5. KM Inicial Formatado (remove os .00 e coloca ponto de milhar)
    public function getKmInicialFormatadoAttribute()
    {
        return number_format(
            $this->km_inicial,
            0,
            ',',
            '.'
        );
    }

    // 6. KM Final Formatado
    public function getKmFinalFormatadoAttribute()
    {
        return $this->km_final > 0
            ? number_format($this->km_final, 0, ',', '.')
            : '---';
    }

    // 7. Valor Total Geral Formatado (Abastecimentos + Despesas)
    public function getValorTotalGeralFormatadoAttribute()
    {
        // 1. Somamos os abastecimentos (usando a relação no plural)
        // Se não houver nada, ele assume 0
        $somaAbastecimento =
            $this->abastecimentos()->sum('valor_total') ?? 0;

        // 2. Somamos as despesas (usando a relação no plural)
        // Se não houver nada, ele assume 0
        $somaDespesas =
            $this->despesas()->sum('valor') ?? 0;

        // 3. Somamos os dois resultados
        $totalGeral =
            $somaAbastecimento
            + $somaDespesas;

        // 4. Retornamos formatado para o padrão brasileiro
        return 'R$ ' . number_format(
                $totalGeral,
                2,
                ',',
                '.'
            );
    }

    /*
     * Alias mantido para retrocompatibilidade caso algum
     * Controller/View utilize esse nome.
     */
    public function getValorTotalAbastecimentosFormatadoAttribute()
    {
        return $this->getValorTotalGeralFormatadoAttribute();
    }

    /*
     * Comparador:
     *
     * Avalia se a saída aconteceu:
     *
     * - no horário;
     * - antecipada;
     * - atrasada.
     */
    public function getStatusPontualidadeSaidaAttribute()
    {
        if (!$this->data_hora_saida_real) {
            return '<span class="badge badge-secondary">Pendente</span>';
        }

        if (!$this->data_hora_saida) {
            return '<span class="badge badge-secondary">Sem horário previsto</span>';
        }

        try {
            $previsto = Carbon::parse(
                $this->data_hora_saida
            );

            $real = Carbon::parse(
                $this->data_hora_saida_real
            );
        } catch (\Throwable $e) {
            return '<span class="badge badge-secondary">Data inválida</span>';
        }

        /*
         * Diferença em minutos.
         *
         * positivo = saiu depois;
         * negativo = saiu antes.
         */
        $diferencaMinutos = $previsto->diffInMinutes(
            $real,
            false
        );

        /*
         * Margem de tolerância:
         *
         * 15 minutos para mais ou para menos.
         */
        if ($diferencaMinutos > 15) {
            return '<span class="badge badge-danger" title="Atraso de '
                . $diferencaMinutos
                . ' min">'
                . '<i class="la la-clock text-white"></i> '
                . 'Saída com Atraso ('
                . $diferencaMinutos
                . 'm)</span>';
        }

        if ($diferencaMinutos < -15) {
            $antecipado = abs(
                $diferencaMinutos
            );

            return '<span class="badge badge-info" title="Adiantado '
                . $antecipado
                . ' min">'
                . '<i class="la la-clock text-white"></i> '
                . 'Saída Antecipada ('
                . $antecipado
                . 'm)</span>';
        }

        return '<span class="badge badge-success">'
            . '<i class="la la-check text-white"></i> '
            . 'No Horário'
            . '</span>';
    }
}
