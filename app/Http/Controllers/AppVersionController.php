<?php

namespace App\Http\Controllers;

use App\Models\AppVersion;
use App\Services\AppVersionService;
use Illuminate\Http\Response;

class AppVersionController extends Controller
{
    public function __construct(private AppVersionService $appVersionService)
    {
    }

    public function index()
    {
        $currentVersion = $this->appVersionService->currentOrFallback();
        $groupedVersions = $this->appVersionService->groupedHistoryByMajor();

        return view('app_versions.index', compact('currentVersion', 'groupedVersions'))
            ->with('title', 'Controle Interno de Versões');
    }

    public function downloadPdf(AppVersion $appVersion, string $type = 'cumulative')
    {
        $path = $type === 'current'
            ? $appVersion->release_notes_current_pdf_path
            : $appVersion->release_notes_cumulative_pdf_path;

        if (!$path || !file_exists(storage_path('app/'.$path))) {
            abort(404, 'Arquivo PDF não encontrado para esta versão.');
        }

        return response()->download(
            storage_path('app/'.$path),
            "release-notes-{$type}-{$appVersion->version}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }

    public function html(AppVersion $appVersion, string $type = 'current')
    {
        $content = $type === 'cumulative'
            ? $appVersion->release_notes_cumulative_html
            : $appVersion->release_notes_current_html;

        if (!$content) {
            abort(404, 'Release note HTML não encontrada para esta versão.');
        }

        return new Response($content, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
