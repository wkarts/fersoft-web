<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Security\SecurityAuthorizer;
use App\Models\Security\SecurityAuthorizerToken;
use App\Services\Security\SecurityAuthorizerService;
use App\Services\Security\SecurityFeatureService;
use Illuminate\Http\Request;

class SecurityAuthorizerTokenController extends Controller
{
    public function index(Request $request, SecurityFeatureService $featureService)
    {
        $isSuper = $featureService->isSuperAdmin();
        $empresaId = $isSuper && $request->filled('empresa_id') ? (int) $request->empresa_id : $featureService->currentEmpresaId();

        $authorizers = SecurityAuthorizer::query()
            ->with('usuario')
            ->where('enabled', true)
            ->when(!$isSuper, function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->when($isSuper && $request->filled('empresa_id'), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->orderBy('id')
            ->get();

        $tokens = SecurityAuthorizerToken::query()
            ->with(['authorizer.usuario', 'empresa'])
            ->when(!$isSuper, function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->when($isSuper && $request->filled('empresa_id'), function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $empresas = $isSuper ? Empresa::query()->orderBy('nome')->get(['id', 'nome', 'cnpj']) : collect();

        return view('security.tokens.index', [
            'title' => 'Tokens de Liberação',
            'isSuper' => $isSuper,
            'empresaId' => $empresaId,
            'authorizers' => $authorizers,
            'tokens' => $tokens,
            'empresas' => $empresas,
            'plainToken' => session('security_plain_token'),
        ]);
    }

    public function store(Request $request, SecurityFeatureService $featureService, SecurityAuthorizerService $authorizerService)
    {
        $authorizer = SecurityAuthorizer::findOrFail((int) $request->security_authorizer_id);

        if (!$featureService->isSuperAdmin() && (int) $authorizer->empresa_id !== (int) $featureService->currentEmpresaId()) {
            session()->flash('mensagem_erro', 'Autorizador não pertence à empresa atual.');
            return redirect('/seguranca/tokens');
        }

        $result = $authorizerService->createToken($authorizer, $request->all());

        session()->flash('mensagem_sucesso', 'Token criado com sucesso. Copie o token agora, ele não será exibido novamente.');
        session()->flash('security_plain_token', $result['plain_token']);

        return redirect('/seguranca/tokens' . ($featureService->isSuperAdmin() && $authorizer->empresa_id ? '?empresa_id=' . $authorizer->empresa_id : ''));
    }

    public function toggle($id)
    {
        $token = SecurityAuthorizerToken::findOrFail($id);
        $token->enabled = !$token->enabled;
        $token->save();

        session()->flash('mensagem_sucesso', 'Status do token atualizado com sucesso.');
        return redirect()->back();
    }

    public function destroy($id)
    {
        $token = SecurityAuthorizerToken::findOrFail($id);
        $token->delete();

        session()->flash('mensagem_sucesso', 'Token removido com sucesso.');
        return redirect()->back();
    }
}
