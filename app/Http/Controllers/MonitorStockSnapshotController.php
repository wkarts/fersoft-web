<?php

namespace App\Http\Controllers;

use App\Models\StockDailyAggregate;
use Illuminate\Http\Request;

class MonitorStockSnapshotController extends BaseController
{
    public function snapshot(Request $request)
    {
        $data = $request->input('date', now()->toDateString());
        $filialId = $request->filled('filial_id') ? (int) $request->input('filial_id') : null;

        $query = StockDailyAggregate::with('produto')
            ->where('empresa_id', $this->empresa_id)
            ->whereDate('data_ref', $data);

        if ($filialId) {
            $query->where('filial_id', $filialId);
        }

        $rows = $query->get();

        $totais = $rows
            ->groupBy('contexto')
            ->map(function ($itens, $contexto) {
                return [
                    'contexto' => $contexto,
                    'entrada' => (float) $itens->sum('entrada'),
                    'saida' => (float) $itens->sum('saida'),
                    'saldo' => (float) $itens->sum('saldo'),
                    'valor_entrada' => (float) $itens->sum('valor_entrada'),
                    'valor_saida' => (float) $itens->sum('valor_saida'),
                ];
            })
            ->values();

        $grid = $rows->map(function (StockDailyAggregate $row) {
            return [
                'produto_id' => $row->produto_id,
                'produto_nome' => $row->produto->nome ?? '—',
                'contexto' => $row->contexto,
                'entrada' => (float) $row->entrada,
                'saida' => (float) $row->saida,
                'saldo' => (float) $row->saldo,
                'custo_medio' => $row->custo_medio !== null ? (float) $row->custo_medio : null,
            ];
        })->values();

        return response()->json([
            'date' => $data,
            'totais' => $totais,
            'grid' => $grid,
        ]);
    }
}
