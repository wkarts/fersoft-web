<?php

namespace App\Services\Ponto;

use App\Models\PontoMarcacao;
use App\Models\Funcionario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PontoCalculoService
{
    public function calcularDia(int $funcionarioId, string $data): void
    {
        $funcionario = Funcionario::find($funcionarioId);
        if (!$funcionario) return;

        // Busca todas as marcações válidas do dia
        $marcacoes = PontoMarcacao::where('funcionario_id', $funcionarioId)
            ->whereDate('data_hora_marcacao', $data)
            ->where('status', '!=', 'pendente_localizacao')
            ->where('status', '!=', 'desconsiderada')
            ->orderBy('data_hora_marcacao', 'asc')
            ->get();

        $dadosEtapas = [
            'entrada'           => null,
            'inicio_almoco'     => null,
            'fim_almoco'        => null,
            'saida'             => null,
            'inicio_hora_extra' => null,
            'fim_hora_extra'    => null,
        ];

        foreach ($marcacoes as $m) {
            $horaMinuto = Carbon::parse($m->data_hora_marcacao)->format('H:i');
            if (array_key_exists($m->tipo_marcacao, $dadosEtapas)) {
                $dadosEtapas[$m->tipo_marcacao] = $horaMinuto;
            }
        }

        $diaSemana = Carbon::parse($data)->dayOfWeekIso; // 1 = Segunda ... 6 = Sábado, 7 = Domingo
        $minutosPrevistos = 480; // 8 horas padrão
        $tolerancia = 10;

        // 1. Identifica a escala associada ao colaborador
        $escala = null;
        if (!empty($funcionario->ponto_escala_id)) {
            $escala = DB::table('ponto_escalas')
                ->where('id', $funcionario->ponto_escala_id)
                ->where('ativo', 1)
                ->first();
        }

        // 2. Aplica as regras da escala ou adota o padrão CLT
        if ($escala) {
            $regras = json_decode($escala->regras ?? '{}', true);
            $tolerancia = (int) ($regras['tolerancia_minutos'] ?? 10);

            if ($escala->tipo === '12x36') {
                $trabalhouOntem = DB::table('ponto_resumos_diarios')
                    ->where('funcionario_id', $funcionarioId)
                    ->where('data', Carbon::parse($data)->subDay()->toDateString())
                    ->where('horas_trabalhadas', '!=', '00:00')
                    ->exists();

                $minutosPrevistos = $trabalhouOntem ? 0 : (int) ($regras['duracao_plantao_minutos'] ?? 720);
            } elseif (isset($regras['dias'][$diaSemana])) {
                $minutosPrevistos = (int) ($regras['dias'][$diaSemana]['previsto_minutos'] ?? 0);
            }
        } else {
            if ($diaSemana == 6) {
                $minutosPrevistos = 240; // 4 horas no sábado
            } elseif ($diaSemana == 7) {
                $minutosPrevistos = 0;   // DSR (domingo)
            }
        }

        $minutosTrabalhados = 0;
        $status = 'normal';

        // Par 1: Entrada até Saída Almoço
        if ($dadosEtapas['entrada'] && $dadosEtapas['inicio_almoco']) {
            $minutosTrabalhados += Carbon::parse($data . ' ' . $dadosEtapas['entrada'])
                ->diffInMinutes(Carbon::parse($data . ' ' . $dadosEtapas['inicio_almoco']));
        }

        // Par 2: Retorno Almoço até Saída
        if ($dadosEtapas['fim_almoco'] && $dadosEtapas['saida']) {
            $minutosTrabalhados += Carbon::parse($data . ' ' . $dadosEtapas['fim_almoco'])
                ->diffInMinutes(Carbon::parse($data . ' ' . $dadosEtapas['saida']));
        }

        // Par 3: Início Extra até Fim Extra
        if ($dadosEtapas['inicio_hora_extra'] && $dadosEtapas['fim_hora_extra']) {
            $minutosTrabalhados += Carbon::parse($data . ' ' . $dadosEtapas['inicio_hora_extra'])
                ->diffInMinutes(Carbon::parse($data . ' ' . $dadosEtapas['fim_hora_extra']));
        }

        $minutosExtras = 0;
        $minutosAtraso = 0;

        if ($marcacoes->isEmpty()) {
            $status = ($diaSemana == 7) ? 'folga' : 'falta';
            $minutosAtraso = ($status == 'falta') ? $minutosPrevistos : 0;
        } else {
            $saldoBruto = $minutosTrabalhados - $minutosPrevistos;
            $minutosExtras = 0;
            $minutosAtraso = 0;

            if (abs($saldoBruto) > $tolerancia) {
                if ($saldoBruto > 0) {
                    $minutosExtras = $saldoBruto;
                } else {
                    $minutosAtraso = abs($saldoBruto);
                }
            }

            if (
                ($dadosEtapas['entrada'] && !$dadosEtapas['saida'] && !$dadosEtapas['inicio_almoco']) ||
                ($dadosEtapas['inicio_almoco'] && !$dadosEtapas['fim_almoco']) ||
                ($dadosEtapas['inicio_hora_extra'] && !$dadosEtapas['fim_hora_extra'])
            ) {
                $status = 'incompleto';
            }
        }

        $formataHoras = function (int $minutos): string {
            $h = floor($minutos / 60);
            $m = $minutos % 60;
            return sprintf('%02d:%02d', $h, $m);
        };

        DB::table('ponto_resumos_diarios')->updateOrInsert(
            [
                'funcionario_id' => $funcionarioId,
                'data'           => $data,
            ],
            [
                'empresa_id'        => $funcionario->empresa_id,
                'horas_previstas'   => $formataHoras($minutosPrevistos),
                'horas_trabalhadas' => $formataHoras($minutosTrabalhados),
                'horas_extras'      => $formataHoras($minutosExtras),
                'horas_atraso'      => $formataHoras($minutosAtraso),
                'horas_banco'       => $formataHoras($minutosExtras - $minutosAtraso),
                'status'            => $status,
                'updated_at'        => now(),
            ]
        );
    }
}
