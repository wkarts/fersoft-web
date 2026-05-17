<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Models\Security\SecurityAuthorizer;
use App\Models\Security\SecurityCrudProtectionRule;
use App\Models\Security\SecurityCrudResource;
use App\Services\Security\SecurityFeatureService;
use Illuminate\Http\Request;

class SecurityDashboardController extends Controller
{
    public function index(SecurityFeatureService $featureService)
    {
        $empresaId = $featureService->currentEmpresaId();
        $isSuper = $featureService->isSuperAdmin();
        $setting = $featureService->getOrCreateSetting($empresaId);

        if (!$featureService->migrationsReady()) {
            return view('security.dashboard.index', [
                'title' => 'Painel de Segurança',
                'setting' => $setting,
                'isSuper' => $isSuper,
                'resourcesCount' => 0,
                'authorizersCount' => 0,
                'rulesCount' => 0,
                'logsCount' => 0,
                'migrationsReady' => false,
                'missingTables' => $featureService->missingMigrationTables(),
            ]);
        }

        $resourcesCount = SecurityCrudResource::query()
            ->when(!$isSuper, fn ($query) => $query->where('tenant_visible', true)->where('super_admin_only', false))
            ->count();

        $authorizersCount = SecurityAuthorizer::query()
            ->when(!$isSuper, fn ($query) => $query->where('empresa_id', $empresaId))
            ->count();

        $rulesCount = SecurityCrudProtectionRule::query()
            ->when(!$isSuper, fn ($query) => $query->where('empresa_id', $empresaId))
            ->count();

        $logsCount = Log::query()
            ->when(!$isSuper, fn ($query) => $query->where('empresa_id', $empresaId))
            ->count();

        return view('security.dashboard.index', [
            'title' => 'Painel de Segurança',
            'setting' => $setting,
            'isSuper' => $isSuper,
            'resourcesCount' => $resourcesCount,
            'authorizersCount' => $authorizersCount,
            'rulesCount' => $rulesCount,
            'logsCount' => $logsCount,
            'migrationsReady' => true,
            'missingTables' => [],
        ]);
    }

    public function enable(Request $request, SecurityFeatureService $featureService)
    {
        if (!$featureService->migrationsReady()) {
            session()->flash('mensagem_erro', 'As migrations da Segurança de Operações ainda não foram executadas. Rode php artisan migrate antes de habilitar o recurso.');
            return redirect('/seguranca');
        }

        $featureService->enableForTenant(
            $featureService->currentEmpresaId(),
            $featureService->currentUserId()
        );

        session()->flash('mensagem_sucesso', 'Segurança de Operações habilitada em modo de configuração. Nenhum CRUD será bloqueado até ativar a aplicação das regras.');

        return redirect('/seguranca/setup');
    }
}
