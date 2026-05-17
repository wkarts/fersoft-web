<?php

namespace App\Console\Commands\Security;

use App\Models\Empresa;
use App\Services\Security\SecuritySetupService;
use Illuminate\Console\Command;
use Throwable;

class SecurityTenantSetupCommand extends Command
{
    protected $signature = 'seguranca:setup-tenant
        {empresa_id? : ID da empresa/tenant. Obrigatório se --all não for usado}
        {--all : Executa para todas as empresas}
        {--enable : Habilita a Segurança de Operações em modo configuração}
        {--sync-resources : Sincroniza recursos do sistema antes das ações do tenant}
        {--permissions : Gera permissões CRUD padrão}
        {--audit-policies : Gera políticas de auditoria padrão}
        {--protections : Gera proteções críticas iniciais}
        {--complete : Marca setup como concluído, se checklist permitir}
        {--enforce : Ativa aplicação das regras, se checklist permitir}
        {--disable-legacy : Desabilita senha legada, se checklist permitir}
        {--bootstrap : Executa enable + sync-resources + permissions + audit-policies + protections}
        {--dry-run : Apenas exibe o que seria executado}
        {--json : Retorna resumo em JSON}';

    protected $description = 'Executa implantação assistida da Segurança de Operações por tenant via linha de comando';

    public function handle(SecuritySetupService $service): int
    {
        if ((bool) $this->option('bootstrap')) {
            $_SERVER['argv'][] = '--enable';
        }

        $empresaIds = $this->resolveEmpresaIds();
        if ($empresaIds === false) {
            return self::FAILURE;
        }

        $actions = $this->resolveActions();
        if (empty($actions)) {
            $this->warn('Nenhuma ação informada. Use --bootstrap ou opções como --enable, --permissions, --audit-policies, --protections.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $results = [];

        foreach ($empresaIds as $empresaId) {
            $results[] = $this->runForEmpresa($service, (int) $empresaId, $actions, $dryRun);
        }

        if ((bool) $this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $hasFailure = collect($results)->contains(fn ($row) => !empty($row['failed']));
        return $hasFailure ? self::FAILURE : self::SUCCESS;
    }

    private function resolveEmpresaIds(): array|false
    {
        if ((bool) $this->option('all')) {
            return Empresa::query()->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $empresaId = $this->argument('empresa_id');
        if (!$empresaId || (int) $empresaId <= 0) {
            $this->error('Informe empresa_id ou use --all.');
            return false;
        }

        if (!Empresa::query()->where('id', (int) $empresaId)->exists()) {
            $this->error('Empresa não encontrada: ' . $empresaId);
            return false;
        }

        return [(int) $empresaId];
    }

    private function resolveActions(): array
    {
        $actions = [];

        if ((bool) $this->option('bootstrap')) {
            $actions = ['enable', 'sync-resources', 'permissions', 'audit-policies', 'protections'];
        }

        foreach (['enable', 'sync-resources', 'permissions', 'audit-policies', 'protections', 'complete', 'enforce', 'disable-legacy'] as $action) {
            if ((bool) $this->option($action) && !in_array($action, $actions, true)) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    private function runForEmpresa(SecuritySetupService $service, int $empresaId, array $actions, bool $dryRun): array
    {
        $empresa = Empresa::find($empresaId);
        $label = $empresa ? ($empresa->nome ?? $empresa->razao_social ?? ('Empresa #' . $empresaId)) : ('Empresa #' . $empresaId);

        $this->newLine();
        $this->info('Empresa: ' . $empresaId . ' - ' . $label);

        $row = [
            'empresa_id' => $empresaId,
            'empresa' => $label,
            'actions' => [],
            'failed' => false,
        ];

        foreach ($actions as $action) {
            if ($dryRun) {
                $this->line('[dry-run] ' . $action);
                $row['actions'][$action] = 'dry-run';
                continue;
            }

            try {
                $result = match ($action) {
                    'enable' => $service->enableTenant($empresaId),
                    'sync-resources' => $service->syncResources(),
                    'permissions' => $service->generateDefaultPermissions($empresaId),
                    'audit-policies' => $service->generateDefaultAuditPolicies($empresaId),
                    'protections' => $service->generateSafeProtectionDrafts($empresaId),
                    'complete' => $service->markSetupCompleted($empresaId),
                    'enforce' => $service->enableEnforcement($empresaId),
                    'disable-legacy' => $service->disableLegacyPassword($empresaId),
                    default => null,
                };

                $summary = $this->summarizeResult($result);
                $this->line('OK: ' . $action . ($summary !== '' ? ' - ' . $summary : ''));
                $row['actions'][$action] = ['status' => 'ok', 'result' => $summary];
            } catch (Throwable $e) {
                $this->error('Falha em ' . $action . ': ' . $e->getMessage());
                $row['actions'][$action] = ['status' => 'error', 'message' => $e->getMessage()];
                $row['failed'] = true;
            }
        }

        try {
            $checklist = $service->checklist($empresaId);
            $this->line('Conclusão: ' . (int) ($checklist['completion_percent'] ?? 0) . '%');
            $row['completion_percent'] = (int) ($checklist['completion_percent'] ?? 0);
            $row['warnings'] = $checklist['warnings'] ?? [];
        } catch (Throwable $e) {
            $row['checklist_error'] = $e->getMessage();
        }

        return $row;
    }

    private function summarizeResult(mixed $result): string
    {
        if (is_int($result)) {
            return $result . ' registro(s) processado(s)';
        }

        if (is_array($result)) {
            if (isset($result['synced'])) {
                return ((int) $result['synced']) . ' recurso(s) sincronizado(s)';
            }

            return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        if (is_object($result) && isset($result->id)) {
            return 'registro #' . $result->id;
        }

        return '';
    }
}
