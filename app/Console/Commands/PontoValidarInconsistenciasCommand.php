<?php

namespace App\Console\Commands;

use App\Models\PontoAfdRegistro;
use App\Models\PontoMarcacao;
use App\Models\PontoOcorrencia;
use Illuminate\Console\Command;

class PontoValidarInconsistenciasCommand extends Command
{
    protected $signature = 'ponto:validar-inconsistencias {empresa_id : ID da empresa} {--data_inicio=} {--data_fim=}';

    protected $description = 'Valida inconsistências de registros AFD, marcações e ocorrências no período';

    public function handle(): int
    {
        $empresaId = (int) $this->argument('empresa_id');
        $dataInicio = $this->option('data_inicio');
        $dataFim = $this->option('data_fim');

        $afd = PontoAfdRegistro::where('empresa_id', $empresaId)
            ->where('inconsistente', true)
            ->when($dataInicio && $dataFim, fn($q) => $q->whereBetween('created_at', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']))
            ->count();

        $marcacoes = PontoMarcacao::where('empresa_id', $empresaId)
            ->where('inconsistente', true)
            ->when($dataInicio && $dataFim, fn($q) => $q->whereBetween('data_hora_marcacao', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']))
            ->count();

        $ocorrencias = PontoOcorrencia::where('empresa_id', $empresaId)
            ->whereIn('tipo', ['falta', 'sem_regra'])
            ->when($dataInicio && $dataFim, fn($q) => $q->whereBetween('data_referencia', [$dataInicio, $dataFim]))
            ->count();

        $this->info('Validação de inconsistências concluída.');
        $this->line('AFD inconsistentes: ' . $afd);
        $this->line('Marcações inconsistentes: ' . $marcacoes);
        $this->line('Ocorrências críticas: ' . $ocorrencias);

        if (($afd + $marcacoes + $ocorrencias) > 0) {
            $this->warn('Foram encontradas inconsistências para análise operacional.');
        }

        return self::SUCCESS;
    }
}
