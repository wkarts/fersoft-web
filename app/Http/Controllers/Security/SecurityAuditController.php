<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Models\Usuario;
use App\Models\Filial;
use App\Models\Empresa;
use App\Services\Security\AuditPrivacyService;
use App\Services\Security\SecurityAuditRestoreService;
use App\Services\Security\SecurityFeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SecurityAuditController extends Controller
{
    protected $listView = 'security.audit.index';
    protected $formTitle = 'Auditoria de Segurança';
    protected $redirectPage = '/seguranca/auditoria';

    public function list(Request $request)
    {
        $featureService = app(SecurityFeatureService::class);
        $privacyService = app(AuditPrivacyService::class);
        $isSuper = $featureService->isSuperAdmin();
        $empresaId = $featureService->currentEmpresaId();

        $query = Log::query();

        if (!$isSuper) {
            $query->where('empresa_id', $empresaId);
        } elseif ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        $query = $privacyService->applyTenantVisibility($query, $isSuper, $empresaId);

        if ($request->filled('filial_id')) {
            if ($request->filial_id === 'null') {
                $query->whereNull('filial_id');
            } else {
                $query->where('filial_id', $request->filial_id);
            }
        }

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('data_inicial')) {
            $query->whereDate('created_at', '>=', $request->data_inicial);
        }

        if ($request->filled('data_final')) {
            $query->whereDate('created_at', '<=', $request->data_final);
        }

        if ($request->filled('modelo')) {
            $modeloBusca = strtolower(trim($request->modelo));
            $query->whereRaw('INSTR(LOWER(modelo), ?) > 0', [$modeloBusca]);
        }

        if ($request->filled('acao')) {
            $query->where('acao', $request->acao);
        }

        $idsPaginator = (clone $query)
            ->select('id')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $ids = $idsPaginator->pluck('id')->all();
        $logsById = empty($ids)
            ? collect()
            : Log::whereIn('id', $ids)->get()->keyBy('id');

        $logs = $idsPaginator->setCollection(
            collect($ids)->map(fn ($id) => $logsById->get($id))->filter()->values()
        );

        $usuarios = Usuario::query()
            ->when(!$isSuper, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('nome')
            ->get();

        $filiais = Filial::query()
            ->when(!$isSuper, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('nome_fantasia')
            ->get();

        $acoes = Log::query()
            ->when(!$isSuper, fn ($q) => $q->where('empresa_id', $empresaId))
            ->select('acao')
            ->distinct()
            ->orderBy('acao')
            ->pluck('acao')
            ->toArray();

        $empresas = $isSuper ? Empresa::query()->orderBy('nome')->get(['id', 'nome', 'cnpj']) : collect();

        return view($this->listView, [
            'title' => $this->formTitle,
            'logs' => $logs,
            'usuarios' => $usuarios,
            'filiais' => $filiais,
            'acoes' => $acoes,
            'isSuper' => $isSuper,
            'empresas' => $empresas,
            'totalFiltrado' => $logs->total(),
        ]);
    }

    public function show(int $id)
    {
        [$log, $isSuper, $empresaId, $setting] = $this->resolveVisibleLog($id);
        $privacyService = app(AuditPrivacyService::class);
        $payload = $privacyService->sanitizedPayload($log, $isSuper, $empresaId);
        $canViewJson = $privacyService->canViewJson($log, $isSuper, $empresaId);
        $canExportJson = $privacyService->canExportJson($log, $isSuper, $empresaId, (bool) optional($setting)->audit_sensitive_export_enabled);
        $canRestore = $privacyService->canRestore($log, $isSuper, $empresaId, (bool) optional($setting)->restore_from_audit_enabled);

        return view('security.audit.show', [
            'title' => 'Detalhes do Log de Auditoria',
            'log' => $log,
            'payload' => $payload,
            'isSuper' => $isSuper,
            'canViewJson' => $canViewJson,
            'canExportJson' => $canExportJson,
            'canRestore' => $canRestore,
        ]);
    }

    public function json(int $id)
    {
        [$log, $isSuper, $empresaId, $setting] = $this->resolveVisibleLog($id);
        $privacyService = app(AuditPrivacyService::class);

        if (!$privacyService->canViewJson($log, $isSuper, $empresaId)) {
            abort(403, 'Você não possui permissão para visualizar o JSON deste log.');
        }

        return response()->json($privacyService->exportPayload($log, $isSuper, $empresaId));
    }

    public function exportJson(Request $request)
    {
        $featureService = app(SecurityFeatureService::class);
        $privacyService = app(AuditPrivacyService::class);
        $isSuper = $featureService->isSuperAdmin();
        $empresaId = $featureService->currentEmpresaId();
        $setting = $featureService->getOrCreateSetting($empresaId);

        $query = Log::query();
        if (!$isSuper) {
            $query->where('empresa_id', $empresaId);
        } elseif ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        $query = $privacyService->applyTenantVisibility($query, $isSuper, $empresaId);

        if ($request->filled('log_id')) {
            $query->where('id', $request->log_id);
        }
        if ($request->filled('data_inicial')) {
            $query->whereDate('created_at', '>=', $request->data_inicial);
        }
        if ($request->filled('data_final')) {
            $query->whereDate('created_at', '<=', $request->data_final);
        }
        if ($request->filled('modelo')) {
            $query->whereRaw('INSTR(LOWER(modelo), ?) > 0', [strtolower(trim($request->modelo))]);
        }

        $logs = $query->orderByDesc('id')->limit(500)->get();
        $items = [];

        foreach ($logs as $log) {
            if ($privacyService->canExportJson($log, $isSuper, $empresaId, (bool) optional($setting)->audit_sensitive_export_enabled)) {
                $items[] = $privacyService->exportPayload($log, $isSuper, $empresaId);
            }
        }

        if (empty($items)) {
            return back()->with('mensagem_erro', 'Nenhum log exportável foi encontrado ou a exportação JSON sensível não está habilitada.');
        }

        $fileName = 'auditoria-' . now()->format('Ymd-His') . '.json';

        return response()->streamDownload(function () use ($items) {
            echo json_encode([
                'exported_at' => now()->toDateTimeString(),
                'total' => count($items),
                'items' => $items,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $fileName, ['Content-Type' => 'application/json']);
    }

    public function restoreIndex(Request $request)
    {
        $featureService = app(SecurityFeatureService::class);
        $privacyService = app(AuditPrivacyService::class);
        $isSuper = $featureService->isSuperAdmin();
        $empresaId = $featureService->currentEmpresaId();
        $setting = $featureService->getOrCreateSetting($empresaId);

        $logs = Log::query()
            ->when(!$isSuper, fn ($query) => $query->where('empresa_id', $empresaId))
            ->when($request->filled('log_id'), fn ($query) => $query->where('id', $request->log_id))
            ->whereIn('acao', ['update', 'delete', 'create'])
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->filter(fn ($log) => $privacyService->canRestore($log, $isSuper, $empresaId, (bool) optional($setting)->restore_from_audit_enabled));

        return view('security.audit.restore', [
            'title' => 'Restauração por Auditoria',
            'logs' => $logs,
            'isSuper' => $isSuper,
        ]);
    }

    public function previewRestore(Request $request, int $id)
    {
        [$log, $isSuper, $empresaId, $setting] = $this->resolveVisibleLog($id);
        $privacyService = app(AuditPrivacyService::class);

        if (!$privacyService->canRestore($log, $isSuper, $empresaId, (bool) optional($setting)->restore_from_audit_enabled)) {
            abort(403, 'Você não possui permissão para restaurar este log.');
        }

        $mode = $request->get('mode', 'auto');
        $preview = app(SecurityAuditRestoreService::class)->preview($log, $mode);

        return view('security.audit.restore_preview', [
            'title' => 'Pré-visualização da Restauração',
            'log' => $log,
            'preview' => $preview,
            'mode' => $mode,
        ]);
    }

    public function restore(Request $request, int $id)
    {
        [$log, $isSuper, $empresaId, $setting] = $this->resolveVisibleLog($id);
        $privacyService = app(AuditPrivacyService::class);

        if (!$privacyService->canRestore($log, $isSuper, $empresaId, (bool) optional($setting)->restore_from_audit_enabled)) {
            abort(403, 'Você não possui permissão para restaurar este log.');
        }

        $mode = $request->input('mode', 'auto');
        $restored = app(SecurityAuditRestoreService::class)->restore($log, $mode);

        return redirect('/seguranca/auditoria/' . $log->id)
            ->with('mensagem_sucesso', 'Restauração executada com sucesso. Registro afetado: ' . $restored->getKey());
    }

    protected function resolveVisibleLog(int $id): array
    {
        $featureService = app(SecurityFeatureService::class);
        $privacyService = app(AuditPrivacyService::class);
        $isSuper = $featureService->isSuperAdmin();
        $empresaId = $featureService->currentEmpresaId();
        $setting = $featureService->getOrCreateSetting($empresaId);

        $log = Log::findOrFail($id);

        if (!$privacyService->canView($log, $isSuper, $empresaId)) {
            abort(403, 'Você não possui permissão para visualizar este log.');
        }

        return [$log, $isSuper, $empresaId, $setting];
    }
}
