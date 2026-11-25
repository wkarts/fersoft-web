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
        'solicitar_abastecimento',
        'gerar_mtr',
        'passar_balanca',
        'pesagem_id',
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

    // NOVOS RELACIONAMENTOS (Multi-Stop e Linha do Tempo)
    public function destinos()
    {
        return $this->hasMany(MovimentacaoDestino::class, 'movimentacao_veiculo_id')
            ->orderBy('ordem', 'asc');
    }

    public function historicoParadas()
    {
        return $this->hasMany(MovimentacaoHistoricoParada::class, 'movimentacao_veiculo_id')
            ->orderBy('data_hora_inicio', 'asc');
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

        if ($this->status == 'iniciado') {
            return '<span class="badge badge-warning">Em curso</span>';
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
        // Se a viagem tiver destinos cadastrados na nova tabela, exibe a contagem
        if ($this->destinos && $this->destinos->count() > 0) {
            $qtd = $this->destinos->count();
            return "Múltiplos Destinos ({$qtd})";
        }

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

    public function pesagem()
    {
        // Ajuste 'Pesagem::class' para o nome exato do seu Model de balança, se for diferente
        return $this->belongsTo(\App\Models\Pesagem::class, 'pesagem_id');
    }


    public function gerarPrePesagemAutomatica()
    {
        // 1. Condição básica: Só gera se a viagem estiver finalizada e a flag de balança estiver ativa
        if ($this->status !== 'finalizado' || empty($this->passar_balanca)) {
            return;
        }

        // 2. Prevenção de duplicidade: verifica se já existe uma pesagem vinculada
        if (\App\Models\Pesagem::where('movimentacao_id', $this->id)->exists() || !empty($this->pesagem_id)) {
            return;
        }

        $clienteId = null;
        $fornecedorId = null;

        // 3. Captura o destino principal da viagem
        if ($this->destinos && $this->destinos->count() > 0) {
            $primeiroDestino = $this->destinos->first();
            $clienteId = $primeiroDestino->cliente_id;
            $fornecedorId = $primeiroDestino->fornecedor_id;
        } else {
            $clienteId = $this->cliente_id;
            $fornecedorId = $this->fornecedor_id;
        }

        // 4. Inteligência: A balança DEVE ser Compra.
        // Se a logística enviou para um "Cliente", busca o Fornecedor Equivalente pelo CNPJ limpo
        if (!empty($clienteId) && empty($fornecedorId)) {
            $cliente = \App\Models\Cliente::find($clienteId);

            if ($cliente && !empty($cliente->cpf_cnpj)) {
                $cnpjLimpo = preg_replace('/[^0-9]/', '', $cliente->cpf_cnpj);

                // Procura um fornecedor com o exato mesmo CNPJ (ignorando pontos e traços)
                $fornecedorEquivalente = \App\Models\Fornecedor::where('empresa_id', $this->empresa_id)
                    ->whereRaw("REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '/', ''), '-', '') = ?", [$cnpjLimpo])
                    ->first();

                if ($fornecedorEquivalente) {
                    $fornecedorId = $fornecedorEquivalente->id;
                    $clienteId = null; // Zera para não dar conflito na balança
                }
            }
        }

        // Se mesmo assim não achou fornecedor, zera o cliente para forçar que a balança classifique como Compra (e o balanceiro escolhe)
        if (!empty($clienteId) && empty($fornecedorId)) {
            $clienteId = null;
        }

        // 5. Cria a pré-pesagem na balança como COMPRA obrigatória
        try {
            $pesagem = \App\Models\Pesagem::create([
                'empresa_id'         => $this->empresa_id,
                'filial_id'          => $this->filial_id,
                'usuario_id'         => $this->usuario_id ?? 1,
                'veiculo_id'         => $this->veiculo_id,
                'motorista_id'       => $this->motorista_id,
                'placa_veiculo'      => $this->veiculo->placa ?? null,
                'motorista_nome'     => $this->motorista->nome ?? ($this->terceiro_nome ?? null),
                'movimentacao_id'    => $this->id,
                'status'             => 'em andamento',
                'tipo'               => 'compra', // 🚀 Sempre COMPRA!
                'cliente_id'         => null,
                'fornecedor_id'      => $fornecedorId,
                'peso'               => 0,
                'peso_liquido_bruto' => 0,
                'peso_final'         => 0,
                'dt_registro'        => now()->toDateString(),
                'token'              => md5(uniqid(rand(), true)),
                'observacoes'        => "Pré-pesagem de COMPRA gerada automaticamente pelo retorno da Viagem #{$this->id}",
            ]);

            // Atualiza a viagem com o ID da pesagem que acabou de ser criada para criar a ponte perfeita!
            $this->pesagem_id = $pesagem->id;
            $this->save();

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Falha ao gerar pré-pesagem auto da viagem #{$this->id}: " . $e->getMessage());
        }
    }

    // =========================================================================
    // 🚀 NOVO: Indicador Visual de Documentos Fiscais (NF-e e MTR)
    // =========================================================================
    public function getDocumentosFiscaisFormatadoAttribute()
    {
        // Se a viagem não terminou, não exige documentação
        if (!in_array(strtolower($this->status), ['finalizado', 'concluida'])) {
            return '<span class="text-muted" style="font-size: 10px;">Aguardando<br>Conclusão</span>';
        }

        $html = '';
        $nfeEncontrada = null;

        // --- 1. BUSCA DA NF-e ---
        // A lógica do sistema: A viagem gera uma pesagem -> A pesagem gera uma Compra -> A Compra tem a NFe
        if ($this->pesagem_id) {
            // Tenta encontrar a Compra gerada a partir da pesagem desta viagem
            $compra = \Illuminate\Support\Facades\DB::table('compras')
                ->where('empresa_id', $this->empresa_id)
                ->whereExists(function ($query) {
                    $query->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('pesagens')
                        ->whereColumn('pesagens.compra_id', 'compras.id')
                        ->where('pesagens.id', $this->pesagem_id);
                })
                ->first();

            if ($compra && !empty($compra->nf)) {
                $nfeEncontrada = $compra->nf;
            } else {
                // Tenta ver se a NF-e foi colada diretamente na pesagem (botão de Vincular Docs que fizemos antes)
                $pesagem = \Illuminate\Support\Facades\DB::table('pesagens')->where('id', $this->pesagem_id)->first();
                if ($pesagem && !empty($pesagem->nfe_entrada)) {
                    $nfeEncontrada = $pesagem->nfe_entrada;
                }
            }
        }

        if (!empty($nfeEncontrada)) {
            $html .= '<span class="badge badge-success badge-fixed-width-doc mb-1" title="NF-e Vinculada">NF-e: ' . mb_strimwidth($nfeEncontrada, 0, 9, '..') . '</span><br>';
        } else {
            // Para não apitar erro em viagens que só levam caçamba e não trazem material (não geram compra),
            // ele avisa que está "Sem NF-e" em amarelo/vermelho.
            $html .= '<span class="badge badge-danger badge-fixed-width-doc mb-1 text-white" title="NF-e Pendente"><i class="fa fa-exclamation-triangle text-white" style="font-size: 9px;"></i> Sem NF-e</span><br>';
        }

        // --- 2. BUSCA DO MTR ---
        $mtrEncontrado = null;

        if (!empty($this->mtr_numero)) {
            $mtrEncontrado = $this->mtr_numero;
        } else {
            // Tenta ver se tem MTR nos destinos (Multi-Stop)
            if ($this->destinos && $this->destinos->count() > 0) {
                $destinoComMtr = $this->destinos->whereNotNull('mtr_numero')->first();
                if ($destinoComMtr) {
                    $mtrEncontrado = $destinoComMtr->mtr_numero;
                }
            }

            // Tenta ver se o MTR foi amarrado na pesagem (via botão Vincular Docs)
            if (empty($mtrEncontrado) && $this->pesagem_id) {
                $pesagem = \Illuminate\Support\Facades\DB::table('pesagens')->where('id', $this->pesagem_id)->first();
                if ($pesagem && !empty($pesagem->mtr_entrada)) {
                    $mtrEncontrado = $pesagem->mtr_entrada;
                }
            }
        }

        if (!empty($mtrEncontrado)) {
            $html .= '<span class="badge badge-primary badge-fixed-width-doc text-white" title="MTR Vinculado">MTR: ' . mb_strimwidth($mtrEncontrado, 0, 9, '..') . '</span>';
        } else {
            $html .= '<span class="badge badge-warning badge-fixed-width-doc text-dark" title="MTR Pendente"><i class="fa fa-exclamation-triangle text-dark" style="font-size: 9px;"></i> Sem MTR</span>';
        }

        return $html;
    }
}
