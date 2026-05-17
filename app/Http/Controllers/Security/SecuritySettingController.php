<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\SecurityFeatureService;
use Illuminate\Http\Request;

class SecuritySettingController extends Controller
{
    public function index(SecurityFeatureService $featureService)
    {
        return view('security.settings.index', [
            'title' => 'Configurações de Segurança',
            'setting' => $featureService->getOrCreateSetting(),
            'isSuper' => $featureService->isSuperAdmin(),
        ]);
    }

    public function update(Request $request, SecurityFeatureService $featureService)
    {
        $setting = $featureService->getOrCreateSetting();

        $setting->fill([
            'tenant_enabled' => $request->boolean('tenant_enabled'),
            'enforcement_enabled' => $request->boolean('enforcement_enabled'),
            'legacy_password_disabled' => $request->boolean('legacy_password_disabled'),
            'google_auth_required' => $request->boolean('google_auth_required'),
            'audit_sensitive_export_enabled' => $request->boolean('audit_sensitive_export_enabled'),
            'restore_from_audit_enabled' => $request->boolean('restore_from_audit_enabled'),
            'setup_completed_at' => $request->boolean('setup_completed') ? ($setting->setup_completed_at ?: now()) : null,
        ]);

        if ($setting->tenant_enabled && !$setting->enabled_at) {
            $setting->enabled_at = now();
            $setting->enabled_by = $featureService->currentUserId();
        }

        $setting->save();

        session()->flash('mensagem_sucesso', 'Configurações de Segurança atualizadas com sucesso.');
        return redirect('/seguranca/configuracoes');
    }
}
