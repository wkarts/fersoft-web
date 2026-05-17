<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Schema;

class SecurityMigrationStateService
{
    protected array $requiredTables = [
        'empresa_security_settings',
        'security_crud_resources',
        'security_crud_permissions',
        'security_crud_protection_rules',
        'security_authorizers',
        'security_authorizer_resources',
        'security_authorizer_tokens',
        'security_operation_authorizations',
        'security_audit_policies',
    ];

    public function requiredTables(): array
    {
        return $this->requiredTables;
    }

    public function isReady(): bool
    {
        try {
            foreach ($this->requiredTables as $table) {
                if (!Schema::hasTable($table)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function missingTables(): array
    {
        $missing = [];

        foreach ($this->requiredTables as $table) {
            try {
                if (!Schema::hasTable($table)) {
                    $missing[] = $table;
                }
            } catch (\Throwable $e) {
                $missing[] = $table;
            }
        }

        return $missing;
    }
}
