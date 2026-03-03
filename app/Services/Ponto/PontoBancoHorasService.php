<?php

namespace App\Services\Ponto;

use App\Models\PontoBancoHora;
use App\Models\PontoOcorrencia;
use Illuminate\Support\Facades\DB;

class PontoBancoHorasService
{
    public function recalcular(int $empresaId, int $funcionarioId, string $inicio, string $fim): int
    {
        return DB::transaction(function () use ($empresaId, $funcionarioId, $inicio, $fim) {
            PontoBancoHora::where('empresa_id', $empresaId)
                ->where('funcionario_id', $funcionarioId)
                ->whereBetween('data_referencia', [$inicio, $fim])
                ->delete();

            $ocorrencias = PontoOcorrencia::where('empresa_id', $empresaId)
                ->where('funcionario_id', $funcionarioId)
                ->whereBetween('data_referencia', [$inicio, $fim])
                ->whereIn('tipo', ['hora_extra', 'debito_banco_horas'])
                ->get()
                ->groupBy('data_referencia');

            $saldo = 0;
            foreach ($ocorrencias as $data => $itens) {
                $credito = (int)$itens->where('tipo', 'hora_extra')->sum('minutos');
                $debito = (int)$itens->where('tipo', 'debito_banco_horas')->sum('minutos');
                $saldo += ($credito - $debito);

                PontoBancoHora::create([
                    'empresa_id' => $empresaId,
                    'funcionario_id' => $funcionarioId,
                    'data_referencia' => $data,
                    'minutos_credito' => $credito,
                    'minutos_debito' => $debito,
                    'saldo_minutos' => $saldo,
                    'origem' => 'tratamento_jornada',
                ]);
            }

            return $saldo;
        });
    }
}
