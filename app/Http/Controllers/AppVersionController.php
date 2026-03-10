<?php

namespace App\Http\Controllers;

use App\Models\AppVersion;

class AppVersionController extends Controller
{
    public function index()
    {
        $currentVersion = AppVersion::query()
            ->where('is_current', true)
            ->orderByDesc('installed_at')
            ->orderByDesc('id')
            ->first();

        $versions = AppVersion::query()
            ->orderByDesc('installed_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('app_versions.index', compact('currentVersion', 'versions'))
            ->with('title', 'Controle Interno de Versões');
    }
}
