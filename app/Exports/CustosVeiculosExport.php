<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CustosVeiculosExport implements FromView, ShouldAutoSize
{
    protected $dados;
    protected $tipoVisao;
    protected $regime;
    protected $dataInicio;
    protected $dataFim;

    public function __construct($dados, $tipoVisao, $regime, $dataInicio, $dataFim)
    {
        $this->dados      = $dados;
        $this->tipoVisao  = $tipoVisao;
        $this->regime     = $regime;
        $this->dataInicio = $dataInicio;
        $this->dataFim    = $dataFim;
    }

    public function view(): View
    {
        return view('relatorios.custos_veiculos.excel', [
            'dados'      => $this->dados,
            'tipoVisao'  => $this->tipoVisao,
            'regime'     => $this->regime,
            'dataInicio' => $this->dataInicio,
            'dataFim'    => $this->dataFim
        ]);
    }
}
