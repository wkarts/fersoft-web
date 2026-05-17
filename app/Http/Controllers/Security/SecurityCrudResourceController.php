<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Security\SecurityCrudResource;
use App\Services\Security\SecurityCrudResourceScannerService;
use App\Services\Security\SecurityFeatureService;
use Illuminate\Http\Request;

class SecurityCrudResourceController extends Controller
{
    public function index(Request $request, SecurityFeatureService $featureService)
    {
        $isSuper = $featureService->isSuperAdmin();

        $resources = SecurityCrudResource::query()
            ->when(!$isSuper, function ($query) {
                $query->where('tenant_visible', true)
                    ->where('super_admin_only', false);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . trim($request->search) . '%';
                $query->where(function ($builder) use ($search) {
                    $builder->where('display_name', 'like', $search)
                        ->orWhere('plural_display_name', 'like', $search)
                        ->orWhere('module', 'like', $search)
                        ->orWhere('model_class', 'like', $search)
                        ->orWhere('route_prefix', 'like', $search);
                });
            })
            ->orderBy('module')
            ->orderBy('display_name')
            ->paginate(25)
            ->withQueryString();

        return view('security.resources.index', [
            'title' => 'Recursos do Sistema',
            'resources' => $resources,
            'isSuper' => $isSuper,
        ]);
    }

    public function sync(SecurityCrudResourceScannerService $scanner, SecurityFeatureService $featureService)
    {
        if (!$featureService->isSuperAdmin()) {
            session()->flash('mensagem_erro', 'Somente o SuperAdmin pode sincronizar todos os recursos do sistema.');
            return redirect('/seguranca/recursos');
        }

        $stats = $scanner->sync();

        session()->flash('mensagem_sucesso', "Sincronização concluída. Lidas: {$stats['scanned']}, sincronizadas: {$stats['synced']}, ignoradas: {$stats['skipped']}.");

        if (!empty($stats['errors'])) {
            session()->flash('mensagem_erro', 'Algumas models não puderam ser sincronizadas. Verifique os logs da aplicação.');
            \Log::warning('Falhas na sincronização de recursos de segurança', $stats);
        }

        return redirect('/seguranca/recursos');
    }
}
