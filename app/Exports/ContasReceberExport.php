<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContasReceberExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $contas;

    public function __construct($contas)
    {
        $this->contas = $contas instanceof Collection ? $contas : collect($contas);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // Cabeçalho em Negrito
        ];
    }

    public function view(): View
    {
        return view('exports.contas_receber', [
            'contas' => $this->contas
        ]);
    }
}