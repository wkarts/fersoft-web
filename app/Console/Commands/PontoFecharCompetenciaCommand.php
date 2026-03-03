<?php

namespace App\Console\Commands;

use App\Services\Ponto\PontoFechamentoService;
use Illuminate\Console\Command;

class PontoFecharCompetenciaCommand extends Command
{
    protected $signature = 'ponto:fechar-competencia
                            {empresa_id : ID da empresa}
                            {competencia : Competência no formato YYYY-MM}
                            {usuario_id : ID do usuário responsável}
                            {--observacoes=}';

    protected $description = 'Fecha competência mensal do módulo de ponto';

    public function handle(PontoFechamentoService $service): int
    {
        $empresaId = (int) $this->argument('empresa_id');
        $competencia = (string) $this->argument('competencia');
        $usuarioId = (int) $this->argument('usuario_id');

        if (!preg_match('/^\d{4}\-(0[1-9]|1[0-2])$/', $competencia)) {
            $this->error('Competência inválida. Use YYYY-MM.');
            return self::FAILURE;
        }

        try {
            $fechamento = $service->fechar($empresaId, $competencia, $usuarioId, $this->option('observacoes'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Competência fechada com sucesso.');
        $this->line('ID fechamento: ' . $fechamento->id);
        $this->line('Versão: ' . $fechamento->versao);

        return self::SUCCESS;
    }
}
