<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SiteViewController extends Controller
{
    public function list(Request $request)
    {
        $siteUrl = $request->get('url'); // Recebe a URL via parâmetro da rota
        $title = '';

        // Valida se a URL é válida
        if (!filter_var($siteUrl, FILTER_VALIDATE_URL)) {
            abort(404, 'URL inválida ou não encontrada.');
        }

        return view('sites_view.list', compact('siteUrl', 'title'));
    }

    public function rastreamento(Request $request)
    {
        // Obtém a URL diretamente do .env ou usa o valor padrão
        $siteUrl = env('RASTREAMENTO_FROTA', 'https://traccar.fersofterp.com.br');
        $title = '';

        // Valida se a URL é válida
        if (!filter_var($siteUrl, FILTER_VALIDATE_URL)) {
            abort(404, 'URL inválida ou não encontrada.');
        }

        // Retorna a view com a URL e o título
        return view('sites_view.list', compact('siteUrl', 'title'));
    }

    public function atendimentoWeb(Request $request)
    {
        // Obtém a URL diretamente do .env ou usa o valor padrão
        $siteUrl = env('WHATSAPP_ATENDIMENTO', 'https://crm.wwsoftwares.com.br');
        $title = '';

        // Valida se a URL é válida
        if (!filter_var($siteUrl, FILTER_VALIDATE_URL)) {
            abort(404, 'URL inválida ou não encontrada.');
        }

        // Retorna a view com a URL e o título
        return view('sites_view.list', compact('siteUrl', 'title'));
    }

}
