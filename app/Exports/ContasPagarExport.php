<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize; // Para ajuste automático de colunas
use Maatwebsite\Excel\Concerns\WithStyles;     // Para aplicar estilos (negrito)
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet; // IMPORTANTE: Para o tipo Worksheet

class ContasPagarExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $contas;

    public function __construct($contas)
    {
        // Garante que sempre seja uma Collection
        $this->contas = $contas instanceof Collection ? $contas : collect($contas);
    }

    // Aplica negrito na primeira linha (cabeçalho)
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function view(): View
    {
        $hasDataEmissaoNfe = false;
        $first = $this->contas->first();
        
        if ($first && (is_array($first) || is_object($first))) {
            $hasDataEmissaoNfe = isset($first->data_emissao_nfe) || 
                                (is_array($first) && array_key_exists('data_emissao_nfe', $first));
        }

        return view('exports.contas_pagar', [
            'contas' => $this->contas,
            'hasDataEmissaoNfe' => $hasDataEmissaoNfe,
        ]);
    }
}