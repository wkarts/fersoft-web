<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class ContasPagarExport implements FromView
{
    protected $contas;

    /**
     * Recebe a coleção de contas a pagar.
     *
     * @param \Illuminate\Support\Collection|array $contas
     */
    public function __construct($contas)
    {
        // garante que sempre seja Collection
        $this->contas = $contas instanceof Collection ? $contas : collect($contas);
    }

    /**
     * Retorna a view que será utilizada para gerar o Excel.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function view(): View
    {
        // detecta se a coluna "data_emissao_nfe" está presente no dataset
        $hasDataEmissaoNfe = false;
        $first = $this->contas->first();
        if ($first && (is_array($first) || is_object($first))) {
            $hasDataEmissaoNfe = isset($first->data_emissao_nfe) || (is_array($first) && array_key_exists('data_emissao_nfe', $first));
        }

        return view('exports.contas_pagar', [
            'contas' => $this->contas,
            'hasDataEmissaoNfe' => $hasDataEmissaoNfe, // opcional pra condicionar header/coluna
        ]);
    }
}
