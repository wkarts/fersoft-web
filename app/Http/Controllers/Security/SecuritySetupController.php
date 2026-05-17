<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\SecurityFeatureService;
use App\Services\Security\SecuritySetupService;
use Illuminate\Http\Request;

class SecuritySetupController extends Controller
{
    public function index(SecuritySetupService $setupService)
    {
        return view('security.setup.index', [
            'title' => 'Assistente de Implantação da Segurança',
            'checklist' => $setupService->checklist(),
        ]);
    }

    public function enable(Request $request, SecuritySetupService $setupService, SecurityFeatureService $featureService)
    {
        $setupService->enableTenant($featureService->currentEmpresaId(), $featureService->currentUserId());

        session()->flash('mensagem_sucesso', 'Segurança de Operações habilitada em modo de configuração. Nenhuma regra foi aplicada aos CRUDs ainda.');
        return redirect('/seguranca/setup');
    }

    public function syncResources(SecuritySetupService $setupService)
    {
        $stats = $setupService->syncResources();

        session()->flash('mensagem_sucesso', "Sincronização concluída. Lidas: {$stats['scanned']}; sincronizadas: {$stats['synced']}; ignoradas: {$stats['skipped']}.");
        return redirect('/seguranca/setup');
    }

    public function generatePermissions(SecuritySetupService $setupService)
    {
        $count = $setupService->generateDefaultPermissions();

        session()->flash('mensagem_sucesso', "Permissões CRUD padrão geradas/atualizadas para {$count} recursos.");
        return redirect('/seguranca/setup');
    }

    public function generateAuditPolicies(SecuritySetupService $setupService)
    {
        $count = $setupService->generateDefaultAuditPolicies();

        session()->flash('mensagem_sucesso', "Políticas de auditoria geradas/atualizadas para {$count} recursos.");
        return redirect('/seguranca/setup');
    }

    public function generateProtectionDrafts(SecuritySetupService $setupService)
    {
        $count = $setupService->generateSafeProtectionDrafts();

        session()->flash('mensagem_sucesso', "Rascunhos seguros de proteção gerados/atualizados para {$count} ações críticas. Revise antes de ativar o enforcement.");
        return redirect('/seguranca/setup');
    }

    public function complete(SecuritySetupService $setupService)
    {
        try {
            $setupService->markSetupCompleted();
            session()->flash('mensagem_sucesso', 'Configuração inicial marcada como concluída. Agora a aplicação das regras pode ser ativada quando desejar.');
        } catch (\Throwable $e) {
            session()->flash('mensagem_erro', $e->getMessage());
        }

        return redirect('/seguranca/setup');
    }

    public function enableEnforcement(SecuritySetupService $setupService)
    {
        try {
            $setupService->enableEnforcement();
            session()->flash('mensagem_sucesso', 'Aplicação das regras ativada. A partir de agora, CRUDs cobertos pelo mecanismo passam a respeitar permissões/proteções configuradas.');
        } catch (\Throwable $e) {
            session()->flash('mensagem_erro', $e->getMessage());
        }

        return redirect('/seguranca/setup');
    }

    public function disableLegacyPassword(SecuritySetupService $setupService)
    {
        try {
            $setupService->disableLegacyPassword();
            session()->flash('mensagem_sucesso', 'Senha legada marcada como desabilitada nas telas antigas. Mantenha os campos antigos no banco até concluir a migração de todos os tenants.');
        } catch (\Throwable $e) {
            session()->flash('mensagem_erro', $e->getMessage());
        }

        return redirect('/seguranca/setup');
    }
}
