<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\Security\SecurityAuthorizer;
use App\Services\Security\SecurityAuthorizerOtpService;
use App\Services\Security\SecurityFeatureService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SecurityAuthorizerOtpController extends Controller
{
    public function setup($id, Request $request, SecurityFeatureService $featureService, SecurityAuthorizerOtpService $otpService)
    {
        $authorizer = $this->findAuthorizer($id, $featureService);
        $setup = $otpService->generateSetup($authorizer, $request->boolean('regenerate'));

        return view('security.authorizers.otp', [
            'title' => 'Google Authenticator do Autorizador',
            'authorizer' => $authorizer->load(['usuario', 'empresa']),
            'setup' => $setup,
            'isSuper' => $featureService->isSuperAdmin(),
        ]);
    }

    public function confirm($id, Request $request, SecurityFeatureService $featureService, SecurityAuthorizerOtpService $otpService)
    {
        $authorizer = $this->findAuthorizer($id, $featureService);

        try {
            $otpService->confirm($authorizer, (string) $request->code);
            session()->flash('mensagem_sucesso', 'Google Authenticator do autorizador confirmado com sucesso.');
            return redirect('/seguranca/autorizadores');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
    }

    public function disable($id, SecurityFeatureService $featureService, SecurityAuthorizerOtpService $otpService)
    {
        $authorizer = $this->findAuthorizer($id, $featureService);
        $otpService->disable($authorizer);

        session()->flash('mensagem_sucesso', 'Google Authenticator do autorizador desativado com sucesso.');
        return redirect()->back();
    }

    protected function findAuthorizer($id, SecurityFeatureService $featureService): SecurityAuthorizer
    {
        $authorizer = SecurityAuthorizer::query()
            ->with(['usuario', 'empresa'])
            ->findOrFail((int) $id);

        if (!$featureService->isSuperAdmin() && (int) $authorizer->empresa_id !== (int) $featureService->currentEmpresaId()) {
            abort(403, 'Autorizador não pertence à empresa atual.');
        }

        return $authorizer;
    }
}
