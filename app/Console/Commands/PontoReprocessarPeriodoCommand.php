<?php

namespace App\Console\Commands;

use App\Services\Ponto\PontoReprocessamentoPeriodoService;
use Illuminate\Console\Command;

class PontoReprocessarPeriodoCommand extends Command
{
    protected $signature = 'ponto:reprocessar-periodo
                            {empresa_id : ID da empresa}
                            {data_inicio : Data inicial (Y-m-d)}
                            {data_fim : Data final (Y-m-d)}
                            {--funcionario_id= : Reprocessar um único funcionário}';

    protected $description = 'Reprocessa tratamento de jornada e banco de horas de um período';

    public function handle(PontoReprocessamentoPeriodoService $service): int
    {
        $empresaId = (int) $this->argument('empresa_id');
        $dataInicio = (string) $this->argument('data_inicio');
        $dataFim = (string) $this->argument('data_fim');
        $funcionarioId = $this->option('funcionario_id') ? (int) $this->option('funcionario_id') : null;

        if (!$this->validarData($dataInicio) || !$this->validarData($dataFim)) {
            $this->error('Formato de data inválido. Use Y-m-d.');
            return self::FAILURE;
        }

        if ($dataFim < $dataInicio) {
            $this->error('A data final não pode ser menor que a data inicial.');
            return self::FAILURE;
        }

        $resumo = $service->reprocessar($empresaId, $dataInicio, $dataFim, $funcionarioId);

        $this->info('Reprocessamento concluído.');
        $this->line('Funcionários processados: ' . $resumo['funcionarios']);
        $this->line('Dias processados: ' . $resumo['dias']);
        $this->line('Ocorrências geradas: ' . $resumo['ocorrencias']);
        $this->line('Saldo total (min): ' . $resumo['saldo_total_minutos']);

        return self::SUCCESS;
    }

    private function validarData(string $data): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $data);

        return $dt && $dt->format('Y-m-d') === $data;
    }
}
