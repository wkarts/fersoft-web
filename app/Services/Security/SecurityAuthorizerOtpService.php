<?php

namespace App\Services\Security;

use App\Models\Security\SecurityAuthorizer;
use App\Services\OtpService;
use Illuminate\Validation\ValidationException;

class SecurityAuthorizerOtpService
{
    public function __construct(protected OtpService $otpService)
    {
    }

    public function generateSetup(SecurityAuthorizer $authorizer, bool $regenerate = false): array
    {
        $authorizer->loadMissing(['usuario', 'empresa']);

        $secret = $authorizer->operation_otp_secret;

        if ($regenerate || !$secret || !$authorizer->operation_otp_enabled) {
            $secret = $this->otpService->generateSecret();

            $authorizer->operation_otp_secret = $secret;
            $authorizer->operation_otp_enabled = false;
            $authorizer->operation_otp_confirmed_at = null;
            $authorizer->operation_otp_last_used_at = null;
            $authorizer->save();
        }

        return [
            'secret' => $secret,
            'qr_code' => $this->otpService->getQRCodeInline(
                $this->issuerName($authorizer),
                $this->accountName($authorizer),
                $secret
            ),
            'issuer' => $this->issuerName($authorizer),
            'account' => $this->accountName($authorizer),
        ];
    }

    public function confirm(SecurityAuthorizer $authorizer, string $code): SecurityAuthorizer
    {
        $code = trim($code);

        if ($code === '' || strlen($code) !== 6) {
            throw ValidationException::withMessages([
                'code' => ['Informe o código de 6 dígitos gerado pelo aplicativo autenticador.'],
            ]);
        }

        if (!$authorizer->operation_otp_secret) {
            throw ValidationException::withMessages([
                'code' => ['Gere o QR Code antes de confirmar o Google Authenticator.'],
            ]);
        }

        if (!$this->verify($authorizer, $code)) {
            throw ValidationException::withMessages([
                'code' => ['Código inválido. Confira o horário do celular e tente novamente.'],
            ]);
        }

        $authorizer->operation_otp_enabled = true;
        $authorizer->operation_otp_confirmed_at = now();
        $authorizer->save();

        return $authorizer;
    }

    public function disable(SecurityAuthorizer $authorizer): SecurityAuthorizer
    {
        $authorizer->operation_otp_enabled = false;
        $authorizer->operation_otp_secret = null;
        $authorizer->operation_otp_confirmed_at = null;
        $authorizer->operation_otp_last_used_at = null;
        $authorizer->save();

        return $authorizer;
    }

    public function verify(SecurityAuthorizer $authorizer, ?string $code, bool $touchLastUsed = false): bool
    {
        $code = trim((string) $code);

        if ($code === '' || !$authorizer->operation_otp_secret) {
            return false;
        }

        $valid = $this->otpService->verify($authorizer->operation_otp_secret, $code);

        if ($valid && $touchLastUsed) {
            $authorizer->operation_otp_last_used_at = now();
            $authorizer->save();
        }

        return $valid;
    }

    protected function issuerName(SecurityAuthorizer $authorizer): string
    {
        $empresa = optional($authorizer->empresa)->nome ?: config('app.name', 'Laravel');
        return trim($empresa . ' - Segurança de Operações');
    }

    protected function accountName(SecurityAuthorizer $authorizer): string
    {
        $usuario = $authorizer->usuario;
        $login = $usuario->email ?: $usuario->login ?: ('usuario-' . $usuario->id);

        return trim($login . ' / autorizador #' . $authorizer->id);
    }
}
