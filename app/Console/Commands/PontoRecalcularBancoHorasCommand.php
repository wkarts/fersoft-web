<?php

namespace App\Console\Commands;

use App\Services\Ponto\PontoBancoHorasService;
use Illuminate\Console\Command;

class PontoRecalcularBancoHorasCommand extends Command
{
    protected $signature = 'ponto:recalcular-banco-horas
                            {empresa_id : ID da empresa}
                            {funcionario_id : ID do funcionário}
                            {data_inicio : Data inicial (Y-m-d)}
                            {data_fim : Data final (Y-m-d)}';

    protected $description = 'Recalcula banco de horas para um funcionário em um período';

    public function handle(PontoBancoHorasService $service): int
    {
        $empresaId = (int) $this->argument('empresa_id');
        $funcionarioId = (int) $this->argument('funcionario_id');
        $dataInicio = (string) $this->argument('data_inicio');
        $dataFim = (string) $this->argument('data_fim');

        if (!$this->validarData($dataInicio) || !$this->validarData($dataFim)) {
            $this->error('Formato de data inválido. Use Y-m-d.');
            return self::FAILURE;
        }

        if ($dataFim < $dataInicio) {
            $this->error('A data final não pode ser menor que a data inicial.');
            return self::FAILURE;
        }

        $saldo = $service->recalcular($empresaId, $funcionarioId, $dataInicio, $dataFim);

        $this->info('Recalculo finalizado com sucesso.');
        $this->line('Saldo final (min): ' . $saldo);

        return self::SUCCESS;
    }

    private function validarData(string $data): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $data);

        return $dt && $dt->format('Y-m-d') === $data;
    }
}
