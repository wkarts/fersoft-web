<?php

namespace App\Services\Ponto;

use App\Models\Funcionario;
use App\Models\PontoJornada;
use App\Models\PontoMarcacao;
use App\Models\PontoOcorrencia;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class PontoJornadaTratamentoService
{
    public function tratarPeriodo(int $empresaId, int $funcionarioId, string $dataInicio, string $dataFim): array
    {
        $funcionario = Funcionario::where('empresa_id', $empresaId)->findOrFail($funcionarioId);
        $jornada = $funcionario->jornada_padrao_id
            ? PontoJornada::where('empresa_id', $empresaId)->find($funcionario->jornada_padrao_id)
            : null;

        $periodo = CarbonPeriod::create($dataInicio, $dataFim);

        return DB::transaction(function () use ($empresaId, $funcionarioId, $periodo, $jornada) {
            PontoOcorrencia::where('empresa_id', $empresaId)
                ->where('funcionario_id', $funcionarioId)
                ->whereBetween('data_referencia', [$periodo->getStartDate()->toDateString(), $periodo->getEndDate()->toDateString()])
                ->where('origem', 'tratamento_jornada')
                ->delete();

            $resumo = ['dias' => 0, 'ocorrencias' => 0];

            foreach ($periodo as $dia) {
                $resumo['dias']++;
                $data = $dia->toDateString();

                $marcacoes = PontoMarcacao::where('empresa_id', $empresaId)
                    ->where('funcionario_id', $funcionarioId)
                    ->whereDate('data_hora_marcacao', $data)
                    ->orderBy('data_hora_marcacao')
                    ->get();

                $regra = $this->getRegraDia($jornada, $dia->dayOfWeekIso);
                $avaliacao = $this->avaliarDia($regra, $marcacoes->pluck('data_hora_marcacao')->map(fn($d) => Carbon::parse($d))->all(), $jornada?->tolerancia_atraso_min ?? 0, $jornada?->tolerancia_extra_min ?? 0);

                foreach ($avaliacao as $item) {
                    PontoOcorrencia::create([
                        'empresa_id' => $empresaId,
                        'funcionario_id' => $funcionarioId,
                        'data_referencia' => $data,
                        'tipo' => $item['tipo'],
                        'origem' => 'tratamento_jornada',
                        'minutos' => $item['minutos'],
                        'descricao' => $item['descricao'],
                        'dados' => $item['dados'] ?? null,
                    ]);
                    $resumo['ocorrencias']++;
                }
            }

            return $resumo;
        });
    }

    public function avaliarDia(?array $regra, array $marcacoes, int $toleranciaAtraso = 0, int $toleranciaExtra = 0): array
    {
        if (empty($marcacoes)) {
            return [[
                'tipo' => 'falta',
                'minutos' => 0,
                'descricao' => 'Dia sem marcações',
            ]];
        }

        if (!$regra || empty($regra['entrada']) || empty($regra['saida'])) {
            return [[
                'tipo' => 'sem_regra',
                'minutos' => 0,
                'descricao' => 'Jornada sem regra configurada para o dia',
            ]];
        }

        $primeira = $marcacoes[0];
        $ultima = $marcacoes[count($marcacoes) - 1];

        $entradaEsperada = Carbon::parse($primeira->toDateString() . ' ' . $regra['entrada']);
        $saidaEsperada = Carbon::parse($ultima->toDateString() . ' ' . $regra['saida']);
        if ($saidaEsperada->lt($entradaEsperada)) {
            $saidaEsperada->addDay();
        }

        $ocorrencias = [];

        $minAtraso = $entradaEsperada->diffInMinutes($primeira, false);
        if ($minAtraso > $toleranciaAtraso) {
            $ocorrencias[] = [
                'tipo' => 'atraso',
                'minutos' => $minAtraso,
                'descricao' => 'Entrada após horário previsto',
                'dados' => ['entrada_prevista' => $entradaEsperada->toDateTimeString(), 'entrada_real' => $primeira->toDateTimeString()],
            ];
        }

        $minSaida = $ultima->diffInMinutes($saidaEsperada, false);
        if ($minSaida < (-1 * $toleranciaAtraso)) {
            $ocorrencias[] = [
                'tipo' => 'saida_antecipada',
                'minutos' => abs($minSaida),
                'descricao' => 'Saída antes do horário previsto',
                'dados' => ['saida_prevista' => $saidaEsperada->toDateTimeString(), 'saida_real' => $ultima->toDateTimeString()],
            ];
        }

        $worked = $primeira->diffInMinutes($ultima);
        $expected = $entradaEsperada->diffInMinutes($saidaEsperada);
        $saldo = $worked - $expected;

        if ($saldo > $toleranciaExtra) {
            $ocorrencias[] = [
                'tipo' => 'hora_extra',
                'minutos' => $saldo,
                'descricao' => 'Trabalho além da jornada prevista',
            ];
        }

        if ($saldo < 0) {
            $ocorrencias[] = [
                'tipo' => 'debito_banco_horas',
                'minutos' => abs($saldo),
                'descricao' => 'Débito de banco de horas do dia',
            ];
        }

        return empty($ocorrencias) ? [[
            'tipo' => 'jornada_regular',
            'minutos' => 0,
            'descricao' => 'Jornada tratada sem inconsistências',
        ]] : $ocorrencias;
    }

    private function getRegraDia(?PontoJornada $jornada, int $dayOfWeekIso): ?array
    {
        if (!$jornada || !is_array($jornada->regras_semana)) {
            return null;
        }

        return $jornada->regras_semana[(string)$dayOfWeekIso] ?? null;
    }
}
