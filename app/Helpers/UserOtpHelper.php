<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
use App\Models\Usuario;

class UserOtpHelper
{
    /**
     * Se o recurso OTP estiver ativo no .env **e**
     * o usuário (login) tiver OTP cadastrado, retorna true.
     */
    public static function shouldUseOtp(string $login): bool
    {
        if (!env('OTP_LOGIN_ENABLED', false)) {
            return false;
        }

        $user = Usuario::where('login', $login)->first();
        return $user && $user->hasOtp();
    }

    /**
     * Valida o código OTP para o usuário de login informado.
     * Lança ValidationException em caso de erro.
     */
    public static function validateOtp(Request $request, Usuario $user): void
    {
        $request->validate([
            'otp_code' => 'required|digits:6',
        ]);
        if (!$user->hasOtp()) {
            throw ValidationException::withMessages([
                'otp_code' => ['Usuário não possui 2FA cadastrado.'],
            ]);
        }
        $secret = $user->otp_secret;
        $code = $request->input('otp_code');
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        if (!$google2fa->verifyKey($secret, $code, 1)) {
            throw ValidationException::withMessages([
                'otp_code' => ['Código OTP inválido.'],
            ]);
        }
    }

    public static function validateOtp__(Request $request, Usuario $user): void
    {
        $request->validate([
            'otp_code' => 'required|digits:6',
        ]);
        if (! $user->hasOtp()) {
            throw ValidationException::withMessages([
                'otp_code' => ['Usuário não possui 2FA cadastrado.'],
            ]);
        }
        $secret = $user->otp_secret; // já descriptografado!
        $code = $request->input('otp_code');
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        if (! $google2fa->verifyKey($secret, $code, 1)) {
            throw ValidationException::withMessages([
                'otp_code' => ['Código OTP inválido.'],
            ]);
        }
        // OK!
    }

    public static function validateOtp_(Request $request, string $login): void
    {
        // 1) formato
        $request->validate([
            'otp_code' => 'required|digits:6',
        ]);

        // 2) busca usuário pelo campo login (case-sensitive)
        $user = Usuario::where('login', $login)->firstOrFail();

        // 3) tem OTP?
        if (! $user->hasOtp()) {
            throw ValidationException::withMessages([
                'otp_code' => ['Usuário não possui 2FA cadastrado.'],
            ]);
        }

        // 4) verifica chave TOTP
        $google2fa = app(Google2FA::class);
        $secret    = $user->otp_secret; // acessor já descriptografa
        $code      = $request->input('otp_code');

        if (! $google2fa->verifyKey($secret, $code, 1)) {
            throw ValidationException::withMessages([
                'otp_code' => ['Código OTP inválido.'],
            ]);
        }

        // se passar aqui, tudo OK
    }
}
