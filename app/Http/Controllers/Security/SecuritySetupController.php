<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\SecurityFeatureService;
use App\Services\Security\SecuritySetupService;
use Illuminate\Http\Request;

class SecuritySetupController extends Controller
{
    public function index(Request $request, SecuritySetupService $setupService, SecurityFeatureService $featureService)
    {
        $empresaId = $this->resolveEmpresaId($request, $featureService);

        return view('security.setup.index', [
            'title' => 'Assistente de Implantação da Segurança',
            'checklist' => $setupService->checklist($empresaId),
            'empresaId' => $empresaId,
            'isSuper' => $featureService->isSuperAdmin(),
        ]);
    }

    public function enable(Request $request, SecuritySetupService $setupService, SecurityFeatureService $featureService)
    {
        $empresaId = $this->resolveEmpresaId($request, $featureService);
        $setupService->enableTenant($empresaId, $featureService->currentUserId());

        session()->flash('mensagem_sucesso', 'Segurança de Operações habilitada em modo de configuração. Nenhuma regra foi aplicada aos CRUDs ainda.');
        return redirect('/seguranca/setup' . ($request->filled('empresa_id') ? '?empresa_id=' . (int) $request->empresa_id : ''));
    }

    public function syncResources(Request $request, SecuritySetupService $setupService)
    {
        $stats = $setupService->syncResources();

        session()->flash('mensagem_sucesso', "Sincronização concluída. Lidas: {$stats['scanned']}; sincronizadas: {$stats['synced']}; ignoradas: {$stats['skipped']}.");
        return redirect('/seguranca/setup' . ($request->filled('empresa_id') ? '?empresa_id=' . (int) $request->empresa_id : ''));
    }

    public function generatePermissions(Request $request, SecuritySetupService $setupService, SecurityFeatureService $featureService)
    {
        $empresaId = $this->resolveEmpresaId($request, $featureService);
        $count = $setupService->generateDefaultPermissions($empresaId, 'auto');

        session()->flash('mensagem_sucesso', "Permissões CRUD padrão geradas/atualizadas para {$count} recursos.");
        return redirect('/seguranca/setup' . ($request->filled('empresa_id') ? '?empresa_id=' . (int) $request->empresa_id : ''));
    }

    public function generateAuditPolicies(Request $request, SecuritySetupService $setupService, SecurityFeatureService $featureService)
    {
        $empresaId = $this->resolveEmpresaId($request, $featureService);
        $count = $setupService->generateDefaultAuditPolicies($empresaId);

        session()->flash('mensagem_sucesso', "Políticas de auditoria geradas/atualizadas para {$count} recursos.");
        return redirect('/seguranca/setup' . ($request->filled('empresa_id') ? '?empresa_id=' . (int) $request->empresa_id : ''));
    }

    public function generateProtectionDrafts(Request $request, SecuritySetupService $setupService, SecurityFeatureService $featureService)
    {
        $empresaId = $this->resolveEmpresaId($request, $featureService);
        $count = $setupService->generateSafeProtectionDrafts($empresaId, 'auto');

        session()->flash('mensagem_sucesso', "Rascunhos seguros de proteção gerados/atualizados para {$count} ações críticas. Revise antes de ativar o enforcement.");
        return redirect('/seguranca/setup' . ($request->filled('empresa_id') ? '?empresa_id=' . (int) $request->empresa_id : ''));
    }

    public function complete(Request $request, SecuritySetupService $setupService, SecurityFeatureService $featureService)
    {
        try {
            $empresaId = $this->resolveEmpresaId($request, $featureService);
            $setupService->markSetupCompleted($empresaId);
            session()->flash('mensagem_sucesso', 'Configuração inicial marcada como concluída. Agora a aplicação das regras pode ser ativada quando desejar.');
        } catch (\Throwable $e) {
            session()->flash('mensagem_erro', $e->getMessage());
        }

        return redirect('/seguranca/setup' . ($request->filled('empresa_id') ? '?empresa_id=' . (int) $request->empresa_id : ''));
    }

    public function enableEnforcement(Request $request, SecuritySetupService $setupService, SecurityFeatureService $featureService)
    {
        try {
            $empresaId = $this->resolveEmpresaId($request, $featureService);
            $setupService->enableEnforcement($empresaId);
            session()->flash('mensagem_sucesso', 'Aplicação das regras ativada. A partir de agora, CRUDs cobertos pelo mecanismo passam a respeitar permissões/proteções configuradas.');
        } catch (\Throwable $e) {
            session()->flash('mensagem_erro', $e->getMessage());
        }

        return redirect('/seguranca/setup' . ($request->filled('empresa_id') ? '?empresa_id=' . (int) $request->empresa_id : ''));
    }

    public function disableLegacyPassword(Request $request, SecuritySetupService $setupService, SecurityFeatureService $featureService)
    {
        try {
            $empresaId = $this->resolveEmpresaId($request, $featureService);
            $setupService->disableLegacyPassword($empresaId);
            session()->flash('mensagem_sucesso', 'Senha legada marcada como desabilitada nas telas antigas. Mantenha os campos antigos no banco até concluir a migração de todos os tenants.');
        } catch (\Throwable $e) {
            session()->flash('mensagem_erro', $e->getMessage());
        }

        return redirect('/seguranca/setup' . ($request->filled('empresa_id') ? '?empresa_id=' . (int) $request->empresa_id : ''));
    }
    private function resolveEmpresaId(Request $request, SecurityFeatureService $featureService): ?int
    {
        if ($featureService->isSuperAdmin() && $request->filled('empresa_id')) {
            return (int) $request->empresa_id;
        }

        return $featureService->currentEmpresaId();
    }
}
