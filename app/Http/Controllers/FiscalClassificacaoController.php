<?php

namespace App\Http\Controllers;

use App\Helpers\Menu;
use App\Models\ReformaTributaria\ClassTribIbsCbs;
use Illuminate\Support\Facades\Schema;

class FiscalClassificacaoController extends Controller
{
    // The legacy migration may find an existing table and only add its
    // standard metadata columns. Query nothing until the full lookup schema
    // required by both the view and Eloquent's SoftDeletes is present.
    private const CATALOG_COLUMNS = [
        'id', 'cclasstrib', 'nome_cclasstrib', 'descricao_cclasstrib',
        'cst_ibs_cbs', 'descricao_cst_ibs_cbs', 'dinivig', 'dfimvig',
        'predibs', 'predcbs', 'deleted_at',
    ];

    public function index()
    {
        // Keep access aligned with the visible ClassTrib item. The legacy
        // validaAcesso middleware only checks the first URL segment and does
        // not recognize this existing menu item's /list suffix.
        $session = session('user_logged');
        abort_unless($session && ($session['super'] ?? false || $this->canViewMenuItem()), 403);

        $catalogInstalled = Schema::hasTable('class_trib_ibs_cbs');
        $catalogComplete = false;
        $classificacoes = null;

        if ($catalogInstalled) {
            $availableColumns = array_map('strtolower', Schema::getColumnListing('class_trib_ibs_cbs'));
            $catalogComplete = count(array_diff(self::CATALOG_COLUMNS, $availableColumns)) === 0;
        }

        if ($catalogComplete) {
            $classificacoes = ClassTribIbsCbs::query()
                ->select([
                    'id', 'cclasstrib', 'nome_cclasstrib', 'descricao_cclasstrib',
                    'cst_ibs_cbs', 'descricao_cst_ibs_cbs', 'dinivig', 'dfimvig',
                    'predibs', 'predcbs',
                ])
                ->orderBy('cclasstrib')
                ->paginate(25);
        }

        return view('fiscalClassificacoes.list', compact('classificacoes', 'catalogInstalled', 'catalogComplete'))
            ->with('title', 'ClassTrib — Cadastros');
    }

    private function canViewMenuItem(): bool
    {
        foreach ((new Menu())->preparaMenu() as $group) {
            if (($group['titulo'] ?? '') !== 'ClassTrib' || !($group['ativo'] ?? true)) {
                continue;
            }
            foreach ($group['subs'] ?? [] as $item) {
                if (($item['rota'] ?? '') === '/fiscal-classificacoes/list'
                    && !array_key_exists('rota_ativa', $item)) {
                    return true;
                }
            }
        }
        return false;
    }
}
