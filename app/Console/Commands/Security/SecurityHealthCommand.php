<?php

namespace App\Console\Commands\Security;

use App\Models\Empresa;
use App\Services\Security\SecurityHealthService;
use Illuminate\Console\Command;
use Throwable;

class SecurityHealthCommand extends Command
{
    protected $signature = 'seguranca:diagnostico
        {empresa_id? : ID da empresa para diagnóstico detalhado}
        {--all : Exibe diagnóstico detalhado de todas as empresas}
        {--json : Retorna o resultado em JSON}';

    protected $description = 'Exibe diagnóstico da Segurança de Operações por empresa ou global';

    public function handle(SecurityHealthService $service): int
    {
        try {
            $result = $this->resolveResult($service);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if ((bool) $this->option('json')) {
            $this->line(json_encode($this->normalizeForJson($result), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        if (isset($result['global'])) {
            $this->renderGlobal($result['global']);
        }

        if (isset($result['companies'])) {
            foreach ($result['companies'] as $company) {
                $this->renderCompany($company);
            }
        }

        if (isset($result['empresa'])) {
            $this->renderCompany($result);
        }

        return self::SUCCESS;
    }

    private function resolveResult(SecurityHealthService $service): array
    {
        if ((bool) $this->option('all')) {
            $empresas = Empresa::query()->orderBy('id')->pluck('id');
            return [
                'global' => $service->global(),
                'companies' => $empresas->map(fn ($id) => $service->company((int) $id))->all(),
            ];
        }

        $empresaId = $this->argument('empresa_id');
        if ($empresaId && (int) $empresaId > 0) {
            return $service->company((int) $empresaId);
        }

        return ['global' => $service->global()];
    }

    private function renderGlobal(array $global): void
    {
        $summary = $global['summary'] ?? [];
        $this->info('Diagnóstico global da Segurança de Operações');
        $this->table(
            ['Indicador', 'Valor'],
            [
                ['Empresas', $summary['companies_total'] ?? 0],
                ['Segurança habilitada', $summary['tenant_enabled'] ?? 0],
                ['Setup concluído', $summary['setup_completed'] ?? 0],
                ['Enforcement ativo', $summary['enforcement_enabled'] ?? 0],
                ['Senha legada ativa', $summary['legacy_active'] ?? 0],
                ['Pendências críticas', $summary['with_critical_issues'] ?? 0],
                ['Alertas', $summary['with_warnings'] ?? 0],
                ['Saudáveis', $summary['healthy'] ?? 0],
                ['Recursos detectados', $summary['resources_total'] ?? 0],
            ]
        );
    }

    private function renderCompany(array $company): void
    {
        $empresa = $company['empresa'] ?? null;
        $row = $company['row'] ?? [];
        $label = $empresa ? (($empresa->nome ?? $empresa->razao_social ?? 'Empresa') . ' #' . $empresa->id) : 'Empresa';

        $this->newLine();
        $this->info('Diagnóstico: ' . $label);
        $this->table(
            ['Indicador', 'Valor'],
            [
                ['Score', ($row['score'] ?? 0) . '%'],
                ['Nível', $row['health_level'] ?? '-'],
                ['Tenant habilitado', !empty($row['tenant_enabled']) ? 'Sim' : 'Não'],
                ['Setup concluído', !empty($row['setup_completed']) ? 'Sim' : 'Não'],
                ['Enforcement ativo', !empty($row['enforcement_enabled']) ? 'Sim' : 'Não'],
                ['Senha legada ativa', !empty($row['legacy_active']) ? 'Sim' : 'Não'],
                ['Permissões', $row['permissions_count'] ?? 0],
                ['Proteções', $row['protection_count'] ?? 0],
                ['Autorizadores', $row['authorizers_count'] ?? 0],
                ['Tokens ativos', $row['active_tokens_count'] ?? 0],
                ['Políticas auditoria', $row['audit_policy_count'] ?? 0],
            ]
        );

        foreach (($row['issues'] ?? []) as $issue) {
            $this->error('Crítico: ' . $issue);
        }

        foreach (($row['warnings'] ?? []) as $warning) {
            $this->warn('Alerta: ' . $warning);
        }
    }

    private function normalizeForJson(mixed $value): mixed
    {
        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->map(fn ($item) => $this->normalizeForJson($item))->values()->all();
        }

        if ($value instanceof \Illuminate\Database\Eloquent\Model) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->normalizeForJson($item), $value);
        }

        return $value;
    }
}
