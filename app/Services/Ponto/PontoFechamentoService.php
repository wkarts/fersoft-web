<?php

namespace App\Services\Ponto;

use App\Models\PontoAjuste;
use App\Models\PontoFechamento;

class PontoFechamentoService
{
    public function fechar(int $empresaId, string $competencia, int $usuarioId, ?string $observacoes = null): PontoFechamento
    {
        $inicio = $competencia . '-01';
        $fim = date('Y-m-t', strtotime($inicio));

        $pendencias = PontoAjuste::where('empresa_id', $empresaId)
            ->where('status', 'pendente')
            ->whereBetween('created_at', [$inicio . ' 00:00:00', $fim . ' 23:59:59'])
            ->count();

        if ($pendencias > 0) {
            throw new \RuntimeException('Existem ajustes pendentes para a competência selecionada.');
        }

        return PontoFechamento::updateOrCreate([
            'empresa_id' => $empresaId,
            'competencia' => $competencia,
        ], [
            'status' => 'fechado',
            'fechado_por' => $usuarioId,
            'fechado_em' => now(),
            'observacoes' => $observacoes,
            'versao' => 1,
        ]);
    }

    public function reabrir(int $empresaId, string $competencia, int $usuarioId): PontoFechamento
    {
        $item = PontoFechamento::where('empresa_id', $empresaId)
            ->where('competencia', $competencia)
            ->firstOrFail();

        $item->status = 'aberto';
        $item->reaberto_por = $usuarioId;
        $item->reaberto_em = now();
        $item->versao = ((int)$item->versao) + 1;
        $item->save();

        return $item;
    }
}
