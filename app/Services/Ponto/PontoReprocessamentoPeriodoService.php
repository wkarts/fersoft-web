<?php

namespace App\Services\Ponto;

use App\Models\Funcionario;
use Illuminate\Support\Facades\DB;

class PontoReprocessamentoPeriodoService
{
    public function __construct(
        private PontoJornadaTratamentoService $tratamentoService,
        private PontoBancoHorasService $bancoHorasService
    ) {
    }

    public function reprocessar(int $empresaId, string $dataInicio, string $dataFim, ?int $funcionarioId = null): array
    {
        $query = Funcionario::where('empresa_id', $empresaId)
            ->when($funcionarioId, fn($q) => $q->where('id', $funcionarioId));

        $funcionarios = $query->get(['id']);

        $resumo = [
            'funcionarios' => 0,
            'dias' => 0,
            'ocorrencias' => 0,
            'saldo_total_minutos' => 0,
        ];

        DB::transaction(function () use ($empresaId, $dataInicio, $dataFim, $funcionarios, &$resumo) {
            foreach ($funcionarios as $funcionario) {
                $resultadoTratamento = $this->tratamentoService->tratarPeriodo(
                    $empresaId,
                    (int) $funcionario->id,
                    $dataInicio,
                    $dataFim
                );

                $saldo = $this->bancoHorasService->recalcular(
                    $empresaId,
                    (int) $funcionario->id,
                    $dataInicio,
                    $dataFim
                );

                $resumo['funcionarios']++;
                $resumo['dias'] += (int) ($resultadoTratamento['dias'] ?? 0);
                $resumo['ocorrencias'] += (int) ($resultadoTratamento['ocorrencias'] ?? 0);
                $resumo['saldo_total_minutos'] += (int) $saldo;
            }
        });

        return $resumo;
    }
}
