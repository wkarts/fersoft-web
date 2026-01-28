<?php

namespace App\Exports;

use App\Models\ContaReceber;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ContasReceberExport implements FromView
{
    protected $contas;

    public function __construct($contas)
    {
        $this->contas = $contas;
    }

    public function view(): View
    {
        return view('exports.contas_receber', [
            'contas' => $this->contas
        ]);
    }
}
