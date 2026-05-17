<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Services\Security\SecurityFeatureService;
use App\Services\Security\SecurityReportExportService;
use Illuminate\Http\Request;

class SecurityReportController extends Controller
{
    public function index(SecurityFeatureService $featureService, SecurityReportExportService $reportService)
    {
        $isSuperAdmin = $featureService->isSuperAdmin();

        return view('security.reports.index', [
            'title' => 'Relatórios da Segurança de Operações',
            'reports' => $reportService->availableReports($isSuperAdmin),
            'empresas' => $isSuperAdmin ? Empresa::query()->orderBy('nome')->get() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function download(Request $request, SecurityFeatureService $featureService, SecurityReportExportService $reportService)
    {
        $isSuperAdmin = $featureService->isSuperAdmin();
        $report = (string) $request->get('report', 'health');
        $format = strtolower((string) $request->get('format', 'csv'));
        $empresaId = $this->resolveEmpresaId($request, $featureService);

        if (!array_key_exists($report, $reportService->availableReports($isSuperAdmin))) {
            abort(404, 'Relatório de segurança não encontrado ou não disponível para seu usuário.');
        }

        if (!in_array($format, ['csv', 'json'], true)) {
            abort(422, 'Formato inválido. Use csv ou json.');
        }

        $rows = $reportService->build($report, $empresaId);
        $filename = $reportService->filename($report, $format, $empresaId);

        if ($format === 'json') {
            return response()->json([
                'report' => $report,
                'empresa_id' => $empresaId,
                'generated_at' => now()->toDateTimeString(),
                'rows' => $rows,
            ])->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        return response($reportService->toCsv($rows), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function resolveEmpresaId(Request $request, SecurityFeatureService $featureService): ?int
    {
        if ($featureService->isSuperAdmin()) {
            $empresaId = $request->get('empresa_id');
            return $empresaId ? (int) $empresaId : null;
        }

        $value = session('user_logged');
        if (is_array($value) && !empty($value['empresa'])) {
            return (int) $value['empresa'];
        }

        if ($request->session()->has('empresa_id')) {
            return (int) $request->session()->get('empresa_id');
        }

        abort(403, 'Empresa não identificada para exportação.');
    }
}
