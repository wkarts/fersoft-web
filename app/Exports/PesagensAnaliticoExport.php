<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PesagensAnaliticoExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Data/Hora',
            'Tipo Operação',
            'Peso Bruto',
            'Tara',
            'Peso Líquido',
            'Status',
            'Venda',
            'Compra',
            'Placa',
            'Veículo',
            'Motorista',
            'Documento Motorista',
            'Filial',
        ];
    }

    public function map($pesagem): array
    {
        // Data/hora: preferencialmente dt_entrada; se não houver, created_at
        $dataHora = $pesagem->dt_entrada ?? $pesagem->created_at;

        // Tipo de operação: mapeado a partir de 'tipo' (compra/venda)
        $tipoOperacao = '';
        if ($pesagem->tipo === 'compra') {
            $tipoOperacao = 'Entrada';
        } elseif ($pesagem->tipo === 'venda') {
            $tipoOperacao = 'Saída';
        }

        // Cálculos de peso usando accessors existentes no Model
        $pesoBruto   = (float) ($pesagem->peso_bruto ?? 0);
        $pesoLiquido = (float) ($pesagem->peso_liquido_real ?? 0);
        $tara        = max(0, $pesoBruto - $pesoLiquido);

        return [
            $dataHora ? $dataHora->format('d/m/Y H:i') : '',
            $tipoOperacao,
            number_format($pesoBruto, 3, ',', '.'),
            number_format($tara, 3, ',', '.'),
            number_format($pesoLiquido, 3, ',', '.'),
            ucfirst($pesagem->status ?? ''),
            optional($pesagem->venda)->id ?? '',
            optional($pesagem->compra)->id ?? '',
            // Placa: usa placa_veiculo da pesagem ou a placa do relacionamento
            $pesagem->placa_veiculo ?: (optional($pesagem->veiculo)->placa ?? ''),
            optional($pesagem->veiculo)->descricao ?? '',
            // Motorista: relacionamento ou fallback do campo motorista_nome
            optional($pesagem->motorista)->nome ?? $pesagem->motorista_nome ?? '',
            optional($pesagem->motorista)->cpf ?? '',
            // Filial: usa nome_fantasia
            optional($pesagem->filial)->nome_fantasia ?? '',
        ];
    }
}
