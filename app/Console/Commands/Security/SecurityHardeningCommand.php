<?php

namespace App\Console\Commands\Security;

use App\Services\Security\SecurityHardeningService;
use Illuminate\Console\Command;

class SecurityHardeningCommand extends Command
{
    protected $signature = 'seguranca:hardening {--json : Retorna o diagnóstico em JSON}';

    protected $description = 'Executa uma revisão técnica rápida da integração da Segurança de Operações';

    public function handle(SecurityHardeningService $service): int
    {
        $report = $service->report();

        if ((bool) $this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return $report['ok'] ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Hardening da Segurança de Operações');
        $this->table(
            ['Check', 'Status', 'Mensagem'],
            collect($report['checks'])->map(fn ($check) => [
                $check['key'],
                $check['ok'] ? 'OK' : 'FALHA',
                $check['message'],
            ])->all()
        );

        if (!$report['ok']) {
            $this->warn('Foram encontradas pendências técnicas. Corrija antes de ativar enforcement em produção.');
            return self::FAILURE;
        }

        $this->info('Hardening aprovado.');
        return self::SUCCESS;
    }
}
