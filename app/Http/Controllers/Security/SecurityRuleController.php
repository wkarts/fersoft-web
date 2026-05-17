<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\SecurityRuleMatrixService;
use Illuminate\Http\Request;

class SecurityRuleController extends Controller
{
    public function index(Request $request, SecurityRuleMatrixService $service)
    {
        return view('security.rules.index', $service->pageData($request));
    }

    public function store(Request $request, SecurityRuleMatrixService $service)
    {
        try {
            $result = $service->save($request);
            session()->flash('mensagem_sucesso', "Regra unificada salva. Permissão atualizada e {$result['protections']} proteções revisadas.");
        } catch (\Throwable $e) {
            session()->flash('mensagem_erro', 'Não foi possível salvar a regra: ' . $e->getMessage());
        }

        return redirect()->back()->withInput($request->except(['_token']));
    }

    public function destroyPermission($id, Request $request, SecurityRuleMatrixService $service)
    {
        try {
            $service->deletePermission((int) $id, $request);
            session()->flash('mensagem_sucesso', 'Permissão CRUD removida com sucesso.');
        } catch (\Throwable $e) {
            session()->flash('mensagem_erro', 'Não foi possível remover a permissão: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function destroyProtection($id, Request $request, SecurityRuleMatrixService $service)
    {
        try {
            $service->deleteProtection((int) $id, $request);
            session()->flash('mensagem_sucesso', 'Proteção de operação removida com sucesso.');
        } catch (\Throwable $e) {
            session()->flash('mensagem_erro', 'Não foi possível remover a proteção: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function cleanup(Request $request, SecurityRuleMatrixService $service)
    {
        try {
            $result = $service->cleanup($request);
            session()->flash('mensagem_sucesso', "Limpeza concluída. Permissões removidas: {$result['permissions']}. Proteções removidas: {$result['protections']}.");
        } catch (\Throwable $e) {
            session()->flash('mensagem_erro', 'Não foi possível limpar as regras: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function recreateDefaults(Request $request, SecurityRuleMatrixService $service)
    {
        try {
            $result = $service->recreateDefaults($request);
            session()->flash('mensagem_sucesso', "Regras padrão recriadas. Permissões: {$result['permissions']}. Proteções: {$result['protections']}.");
        } catch (\Throwable $e) {
            session()->flash('mensagem_erro', 'Não foi possível recriar as regras padrão: ' . $e->getMessage());
        }

        return redirect()->back();
    }
}
