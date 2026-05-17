<?php

namespace App\Services\Security;

use App\Models\Log;
use App\Models\Security\SecurityAuditPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class AuditPrivacyService
{
    protected array $defaultSensitiveFields = [
        'senha',
        'password',
        'senha_remover',
        'token',
        'token_sync',
        'token_whatsapp',
        'token_nfse',
        'token_ibpt',
        'access_token',
        'refresh_token',
        'client_secret',
        'secret',
        'otp_secret',
        'csc',
        'certificado',
        'certificate',
        'private_key',
        'api_key',
    ];

    public function applyTenantVisibility(Builder $query, bool $isSuperAdmin, ?int $empresaId = null): Builder
    {
        if ($isSuperAdmin) {
            return $query;
        }

        $blockedModels = SecurityAuditPolicy::query()
            ->where(function ($builder) use ($empresaId) {
                $builder->whereNull('empresa_id');
                if ($empresaId) {
                    $builder->orWhere('empresa_id', $empresaId);
                }
            })
            ->where('enabled', true)
            ->where(function ($builder) {
                $builder->where('super_admin_only', true)
                    ->orWhere('tenant_can_view', false);
            })
            ->pluck('model_class')
            ->filter()
            ->values()
            ->all();

        if (!empty($blockedModels)) {
            $query->whereNotIn('modelo', $blockedModels);
        }

        return $query;
    }

    public function policyForLog(Log $log, ?int $empresaId = null): ?SecurityAuditPolicy
    {
        if (!$log->modelo) {
            return null;
        }

        $empresaId = $empresaId ?: ($log->empresa_id ? (int) $log->empresa_id : null);

        return SecurityAuditPolicy::query()
            ->where('model_class', $log->modelo)
            ->where('enabled', true)
            ->where(function ($builder) use ($empresaId) {
                $builder->whereNull('empresa_id');
                if ($empresaId) {
                    $builder->orWhere('empresa_id', $empresaId);
                }
            })
            ->orderByRaw('CASE WHEN empresa_id IS NOT NULL THEN 1 ELSE 0 END DESC')
            ->first();
    }

    public function canView(Log $log, bool $isSuperAdmin, ?int $empresaId = null): bool
    {
        if ($isSuperAdmin) {
            return true;
        }

        if ($empresaId && (int) $log->empresa_id !== (int) $empresaId) {
            return false;
        }

        $policy = $this->policyForLog($log, $empresaId);

        if (!$policy) {
            return true;
        }

        if ($policy->super_admin_only) {
            return false;
        }

        return (bool) $policy->tenant_can_view;
    }

    public function canViewJson(Log $log, bool $isSuperAdmin, ?int $empresaId = null): bool
    {
        if ($isSuperAdmin) {
            return true;
        }

        if (!$this->canView($log, false, $empresaId)) {
            return false;
        }

        $policy = $this->policyForLog($log, $empresaId);

        return $policy ? (bool) $policy->tenant_can_view_json : false;
    }

    public function canExportJson(Log $log, bool $isSuperAdmin, ?int $empresaId = null, bool $tenantSettingAllows = false): bool
    {
        if ($isSuperAdmin) {
            return true;
        }

        if (!$tenantSettingAllows || !$this->canViewJson($log, false, $empresaId)) {
            return false;
        }

        $policy = $this->policyForLog($log, $empresaId);

        return $policy ? (bool) $policy->tenant_can_export_json : false;
    }

    public function canRestore(Log $log, bool $isSuperAdmin, ?int $empresaId = null, bool $tenantSettingAllows = false): bool
    {
        if ($isSuperAdmin) {
            return true;
        }

        if (!$tenantSettingAllows || !$this->canView($log, false, $empresaId)) {
            return false;
        }

        $policy = $this->policyForLog($log, $empresaId);

        return $policy ? (bool) $policy->tenant_can_restore : false;
    }

    public function sanitizedPayload(Log $log, bool $isSuperAdmin, ?int $empresaId = null): array
    {
        $policy = $this->policyForLog($log, $empresaId);
        $fields = $this->sanitizeFields($policy);

        $before = $this->normalizePayload($log->dados_anteriores);
        $after = $this->normalizePayload($log->dados_depois);

        if (!$isSuperAdmin) {
            $before = $this->maskFields($before, $fields);
            $after = $this->maskFields($after, $fields);
        }

        return [
            'dados_anteriores' => $before,
            'dados_depois' => $after,
        ];
    }

    public function exportPayload(Log $log, bool $isSuperAdmin, ?int $empresaId = null): array
    {
        $payload = $this->sanitizedPayload($log, $isSuperAdmin, $empresaId);

        return [
            'log' => [
                'id' => $log->id,
                'empresa_id' => $log->empresa_id,
                'usuario_id' => $log->usuario_id,
                'filial_id' => $log->filial_id,
                'acao' => $log->acao,
                'modelo' => $log->modelo,
                'registro_id' => $log->registro_id,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'token' => $isSuperAdmin ? $log->token : null,
                'created_at' => optional($log->created_at)->toDateTimeString(),
                'updated_at' => optional($log->updated_at)->toDateTimeString(),
            ],
            'dados_anteriores' => $payload['dados_anteriores'],
            'dados_depois' => $payload['dados_depois'],
        ];
    }

    public function normalizePayload($payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (is_string($payload) && trim($payload) !== '') {
            $decoded = json_decode($payload, true);
            return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected function sanitizeFields(?SecurityAuditPolicy $policy): array
    {
        $fields = $this->defaultSensitiveFields;

        if ($policy && is_array($policy->sanitize_fields)) {
            $fields = array_merge($fields, $policy->sanitize_fields);
        }

        return array_values(array_unique(array_filter(array_map('strval', $fields))));
    }

    protected function maskFields(array $payload, array $fields): array
    {
        foreach ($payload as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if ($this->isSensitiveKey($normalizedKey, $fields)) {
                $payload[$key] = '***';
                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->maskFields($value, $fields);
            }
        }

        return $payload;
    }

    protected function isSensitiveKey(string $key, array $fields): bool
    {
        foreach ($fields as $field) {
            $field = strtolower($field);
            if ($field !== '' && ($key === $field || str_contains($key, $field))) {
                return true;
            }
        }

        return false;
    }
}
