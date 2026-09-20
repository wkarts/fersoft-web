<?php

namespace App\Services\ConnectApi;

use App\Models\ConnectApiInstance;

class ConnectApiIntegrationResolver
{
    public function forEmpresa(int $empresaId): ConnectApiInstance
    {
        $instance = ConnectApiInstance::query()
            ->where('empresa_id', $empresaId)
            ->whereNull('deleted_at')
            ->first();

        if (!$instance) {
            throw new \RuntimeException("Connect|API não configurada para empresa_id {$empresaId}.");
        }

        if ($instance->is_blocked) {
            throw new \RuntimeException('A integração Connect|API desta empresa está bloqueada.');
        }

        return $instance;
    }

    public function byInstanceName(string $instanceName): ?ConnectApiInstance
    {
        return ConnectApiInstance::query()
            ->where('instance_name', $instanceName)
            ->whereNull('deleted_at')
            ->first();
    }
}
