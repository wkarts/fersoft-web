<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Funcionario;
use App\Models\Veiculo;
use App\Models\Cliente;
use App\Models\Fornecedor;
use App\Models\Venda;
use App\Models\Compra;
use App\Models\TicketPesagem;
use App\Traits\FilialInjectable;

class Pesagem extends BaseModel
{
    use HasFactory, SoftDeletes, FilialInjectable;

    protected $table = 'pesagens';

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'filial_id',
        'veiculo_id',
        'motorista_id',
        'cliente_id',
        'fornecedor_id',
        'peso',
        'placa_veiculo',
        'placa_carreta',
        'motorista_nome',
        'nf_numero',
        'nf_peso',
        'observacoes',
        'camera_snapshots',
        'camera_snapshot_at',
        'nf_data',
        'dt_entrada',
        'dt_saida',
        'status',
        'dt_registro',
        'danificado',
        'quebrado',
        'esverdeado',
        'ardido',
        'secagem',
        'umidade_desconto',
        'impureza_desconto',
        'danificado_desconto',
        'quebrado_desconto',
        'esverdeado_desconto',
        'ardido_desconto',
        'secagem_desconto',
        'token',
        'tipo',
        'peso_liquido_bruto',
        'peso_final',
        'view_public',
        'venda_id',
        'compra_id'
    ];

    protected $casts = [
        'camera_snapshots' => 'array',
        'camera_snapshot_at' => 'datetime',
    ];

    // Define um valor padrão caso não seja enviado
    protected static function booted()
    {
        static::creating(function ($pesagem) {
            if (empty($pesagem->dt_registro)) {
                $pesagem->dt_registro = Carbon::today()->format('Y-m-d');
            }
        });
    }

    // Relacionamentos
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function filial()
    {
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function veiculo()
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
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

    public function tickets()
    {
        return $this->hasMany(TicketPesagem::class, 'pesagem_id');
    }

    public function venda()
    {
        return $this->belongsTo(Venda::class, 'venda_id');
    }

    public function compra()
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    // Mutator para formatar o status
    public function getStatusLabelAttribute()
    {
        return ucfirst($this->status);
    }

    // Mutator para calcular peso líquido com base nos tickets
    public function getPesoLiquidoAttribute()
    {
        $entradas = $this->tickets->where('tipo', 'entrada')->sum('peso');
        $saidas = $this->tickets->where('tipo', 'saida')->sum('peso');
        return abs($entradas - $saidas);
    }

    public function getPesoLiquidoRealAttribute()
    {
        $entradas = $this->tickets->where('tipo', 'entrada')->sum('peso');
        $avulsas = $this->tickets->where('tipo', 'avulsa')->sum('peso');
        $saidas = $this->tickets->where('tipo', 'saida')->sum('peso');
        $peso_bag = $this->tickets->sum('peso_bag');

        return max(0, ($entradas + $avulsas) - $saidas - $peso_bag);
    }

    public function getPesoLiquidoReal2Attribute()
    {
        // Soma das entradas menos peso_bag
        $entradas = $this->tickets->where('tipo', 'entrada')->sum(function($ticket) {
            return $ticket->peso - $ticket->peso_bag;
        });

        // Soma das saídas menos peso_bag
        $saidas = $this->tickets->where('tipo', 'saida')->sum(function($ticket) {
            return $ticket->peso - $ticket->peso_bag;
        });

        return max(0, $entradas - $saidas);
    }

    public function getPesoBrutoAttribute()
    {
        $entradas = $this->tickets->where('tipo', 'entrada')->sum('peso');
        $saidas = $this->tickets->where('tipo', 'saida')->sum('peso');
        $avulsas = $this->tickets->where('tipo', 'avulsa')->sum('peso');

        //return $entradas + $avulsas + $saidas;
        return $entradas + $avulsas;
    }

    public function getPesoFinalCalculadoAttribute()
    {
        $pesoLiquido = $this->peso_liquido_real;

        $descontos = 0;
        if ($this->danificado) $descontos += $pesoLiquido * ($this->danificado_desconto / 100);
        if ($this->quebrado)   $descontos += $pesoLiquido * ($this->quebrado_desconto / 100);
        if ($this->esverdeado) $descontos += $pesoLiquido * ($this->esverdeado_desconto / 100);
        if ($this->ardido)     $descontos += $pesoLiquido * ($this->ardido_desconto / 100);
        if ($this->secagem)    $descontos += $pesoLiquido * ($this->secagem_desconto / 100);

        $descontos += $pesoLiquido * ($this->umidade_desconto / 100);
        $descontos += $pesoLiquido * ($this->impureza_desconto / 100);

        return max(0, $pesoLiquido - $descontos);
    }

    /**
     * ====== CAMPOS VIRTUAIS PARA O RELATÓRIO ANALÍTICO ======
     */

    /**
     * data_pesagem (virtual)
     * Usado para exibição no relatório (Data/Hora).
     *
     * Regra:
     * - Se existir dt_entrada -> usa dt_entrada
     * - Senão, se existir dt_saida -> usa dt_saida
     * - Senão, usa dt_registro (como data) às 00:00
     * - Por fim, fallback em created_at
     */
    public function getDataPesagemAttribute()
    {
        if (!empty($this->dt_entrada)) {
            return Carbon::parse($this->dt_entrada);
        }

        if (!empty($this->dt_saida)) {
            return Carbon::parse($this->dt_saida);
        }

        if (!empty($this->dt_registro)) {
            return Carbon::parse($this->dt_registro . ' 00:00:00');
        }

        return $this->created_at;
    }

    /**
     * tipo_operacao (virtual)
     * Mapeia o campo 'tipo' (compra|venda) para entrada/saida.
     *
     * - tipo = 'compra' => 'entrada'
     * - tipo = 'venda'  => 'saida'
     */
    public function getTipoOperacaoAttribute()
    {
        if ($this->tipo === 'compra') {
            return 'entrada';
        }

        if ($this->tipo === 'venda') {
            return 'saida';
        }

        return null;
    }

    /**
     * tara (virtual)
     * Tara = Peso Bruto - Peso Líquido (não negativa).
     */
    public function getTaraAttribute()
    {
        $bruto    = (float) ($this->peso_bruto ?? 0);
        $liquido  = (float) ($this->peso_liquido ?? 0);
        $taraCalc = $bruto - $liquido;

        return $taraCalc < 0 ? 0 : $taraCalc;
    }
}
