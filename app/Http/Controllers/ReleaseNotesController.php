<?php

namespace App\Http\Controllers;

use App\Services\Updates\ReleaseNotesService;

class ReleaseNotesController extends Controller
{
    public function index(ReleaseNotesService $releaseNotesService)
    {
        $installedRelease = $releaseNotesService->installedRelease();
        $releases = $releaseNotesService->releases();

        usort($releases, function (array $a, array $b) {
            return version_compare((string) ($b['version'] ?? '0.0.0'), (string) ($a['version'] ?? '0.0.0'));
        });

        return view('release_notes.index', compact('installedRelease', 'releases'))
            ->with('title', 'Controle de Release Notes');
    }
}
