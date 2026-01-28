<?php
namespace App\Exports;

use App\Models\Produto;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class RelatorioLucroAnaliticoExport implements FromView
{	
	protected $data;
	public function __construct($data)
    {
        $this->data = $data;
    }
    public function view(): View
    {
        return view('exports.relatorio_analitico', [
            'data' => $this->data
        ]);
    }
}
