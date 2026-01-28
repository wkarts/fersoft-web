<?php

namespace App\Observers;

use App\Models\EvoApiInstance;
use App\Models\Empresa;
use App\Services\EvoApiService;
use App\Utils\WhatsTokenGenerator;
use Illuminate\Support\Str;

class EvoApiInstanceObserver
{
// app/Observers/EvoApiInstanceObserver.php
    public function creating(EvoApiInstance $inst)
    {
        $empresa = Empresa::findOrFail($inst->empresa_id);
        $cnpjClean = preg_replace('/\D+/', '', $empresa->cnpj);
        $slug = Str::slug($empresa->nome_fantasia, '_');

        if (empty($inst->name)) {
            $inst->name = strtoupper("{$cnpjClean}-{$slug}");
        }

        if (empty($inst->api_key)) {
            $inst->api_key = WhatsTokenGenerator::generateSecretKey();
        }
    }


    public function created(EvoApiInstance $inst)
    {
        $service = app(EvoApiService::class);

        // cria ou recupera lá na EvoAPI
        $res = $service->fetchOrCreateInstance(
            $inst->name,
            $inst->api_key,
            [],                // opções extras
            $inst->base_url
        );

        if ($res['success']) {
            $data = $res['data']['instance'];
            $inst->instance_id = $data['instanceId'] ?? null;
            // pode vir apikey atualizada
            $inst->api_key     = $res['data']['hash']['apikey'] ?? $inst->api_key;
            $inst->saveQuietly();
        } else {
            \Log::error("EvoAPI create failed: ".$res['error']);
        }
    }

    public function deleting(EvoApiInstance $inst)
    {
        if ($inst->instance_id) {
            app(EvoApiService::class)
                ->deleteInstance($inst->instance_id);
        }
    }

    public function updating(EvoApiInstance $inst)
    {
        if ($inst->is_blocked && ! $inst->getOriginal('is_blocked')) {
            app(EvoApiService::class)
                ->disconnectInstance($inst->instance_id);
        }
    }
}
