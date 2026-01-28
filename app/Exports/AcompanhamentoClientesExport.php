<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class AcompanhamentoClientesExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected array $filtros;
    protected $query;

    public function __construct(array $filtros, $query)
    {
        $this->filtros = $filtros;
        $this->query   = $query;
    }

    public function collection()
    {
        /** @var \Illuminate\Support\Collection $dados */
        $dados = $this->query->get();

        $linhas = new Collection();

        $dataCriacao = Carbon::now()->format('d/m/Y');
        $dataInicio  = Carbon::parse($this->filtros['data_inicio'])->format('d/m/Y H:i:s');
        $dataFim     = Carbon::parse($this->filtros['data_fim'])->format('d/m/Y H:i:s');

        // Linha "data de criação da pesagem"
        $linhas->push([
            'LABEL'      => 'data de criação da pesagem',
            'DATA'       => $dataCriacao,
            'CLIENTES'   => null,
            'ID'         => null,
            'MATERIAL'   => null,
            'PESO'       => null,
            'IMPUREZA'   => null,
            'LIQUIDO'    => null,
            'VALOR'      => null,
            'VALOR_TOTAL'=> null,
            'PLACA'      => null,
            'OBSERVACAO' => null,
        ]);

        // Linha "aqui é datahora inicio >>>"
        $linhas->push([
            'LABEL'      => 'aqui é datahora inicio >>>',
            'DATA'       => $dataInicio,
            'CLIENTES'   => null,
            'ID'         => null,
            'MATERIAL'   => null,
            'PESO'       => null,
            'IMPUREZA'   => null,
            'LIQUIDO'    => null,
            'VALOR'      => null,
            'VALOR_TOTAL'=> null,
            'PLACA'      => null,
            'OBSERVACAO' => null,
        ]);

        // Linha "aqui é datahora fim >>>"
        $linhas->push([
            'LABEL'      => 'aqui é datahora fim >>>',
            'DATA'       => $dataFim,
            'CLIENTES'   => null,
            'ID'         => null,
            'MATERIAL'   => null,
            'PESO'       => null,
            'IMPUREZA'   => null,
            'LIQUIDO'    => null,
            'VALOR'      => null,
            'VALOR_TOTAL'=> null,
            'PLACA'      => null,
            'OBSERVACAO' => null,
        ]);

        // Linha em branco antes dos dados
        $linhas->push([
            'LABEL'      => null,
            'DATA'       => null,
            'CLIENTES'   => null,
            'ID'         => null,
            'MATERIAL'   => null,
            'PESO'       => null,
            'IMPUREZA'   => null,
            'LIQUIDO'    => null,
            'VALOR'      => null,
            'VALOR_TOTAL'=> null,
            'PLACA'      => null,
            'OBSERVACAO' => null,
        ]);

        // Dados das pesagens
        foreach ($dados as $pesagem) {
            $linhas->push([
                'LABEL'      => null,
                'DATA'       => optional($pesagem->data ?? $pesagem->created_at)->format('d/m/Y'),
                'CLIENTES'   => optional($pesagem->cliente)->razao_social ?? $pesagem->cliente_nome ?? '',
                'ID'         => $pesagem->id,
                'MATERIAL'   => optional($pesagem->material)->nome ?? $pesagem->material ?? '',
                'PESO'       => number_format($pesagem->peso_bruto ?? $pesagem->peso ?? 0, 2, ',', '.'),
                'IMPUREZA'   => number_format($pesagem->impureza ?? 0, 2, ',', '.'),
                'LIQUIDO'    => number_format($pesagem->peso_liquido ?? 0, 2, ',', '.'),
                'VALOR'      => number_format($pesagem->valor_unitario ?? 0, 2, ',', '.'),
                'VALOR_TOTAL'=> number_format($pesagem->valor_total ?? 0, 2, ',', '.'),
                'PLACA'      => optional($pesagem->veiculo)->placa ?? $pesagem->placa ?? '',
                'OBSERVACAO' => $pesagem->observacao ?? '',
            ]);
        }

        return $linhas;
    }

    public function headings(): array
    {
        // Cabeçalho de colunas (a linha com LABEL fica "oculta" visualmente via estilo)
        return [
            'LABEL',
            'DATA',
            'CLIENTES',
            'ID',
            'MATERIAL',
            'PESO',
            'IMPUREZA',
            'LIQUIDO',
            'VALOR (R$)',
            'VALOR TOTAL (R$)',
            'PLACA',
            'OBSERVAÇÃO',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Título "Acompanhamento de Clientes" e "outubro 2025" serão aplicados no evento AfterSheet.
        // Aqui dá pra ajustar fontes padrões etc.
        return [
            // Cabeçalho das colunas
            5 => [
                'font' => ['bold' => true],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Título principal na linha 1
                $sheet->setCellValue('B1', 'Acompanhamento de Clientes');
                $sheet->mergeCells('B1:L1');
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(18);
                $sheet->getRowDimension(1)->setRowHeight(25);

                // Título do período (ex.: "outubro 2025") na linha 1 (coluna I, por exemplo)
                $periodo = $this->montarTituloPeriodo();
                $sheet->setCellValue('I1', $periodo);
                $sheet->mergeCells('I1:L1');
                $sheet->getStyle('I1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('B1:L1')->getAlignment()->setHorizontal('center');

                // Ajusta largura das colunas
                foreach (range('A', 'L') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Dá um destaque leve na linha de cabeçalho de dados (que será a linha 5)
                $sheet->getStyle('A5:L5')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A5:L5')->getBorders()->getBottom()->setBorderStyle('thin');
            },
        ];
    }

    protected function montarTituloPeriodo(): string
    {
        $inicio = Carbon::parse($this->filtros['data_inicio']);
        $fim    = Carbon::parse($this->filtros['data_fim']);

        if ($inicio->isSameMonth($fim)) {
            return mb_strtolower($inicio->translatedFormat('F Y'));
        }

        return $inicio->format('d/m/Y') . ' a ' . $fim->format('d/m/Y');
    }
}
