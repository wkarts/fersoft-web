<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OtpService;
use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use App\Helpers\UserOtpHelper;

class OtpController extends Controller
{
    protected $otp;

    public function __construct(OtpService $otp)
    {
        $this->otp = $otp;
    }

    /**
     * Gera / exibe o setup para o usuário logado.
     */
    public function showSetup()
    {
        $user = Auth::user();

        if (! $user->otp_secret) {
            $secret = $this->otp->generateSecret();
            $user->otp_secret = $secret;
            $user->save();
        } else {
            $secret = $user->otp_secret;
        }

        $qrCode = $this->otp->getQRCodeInline(
            config('app.name'),
            $user->email,
            $secret
        );

        return view('otp.setup', compact('qrCode', 'secret'));
    }

    /**
     * Verifica o código OTP para o usuário {id} ou para o logado.
     * Usa o método global verifyById, que já roda em qualquer tenant.
     */
    public function verify(Request $request, $id = null)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        // escolha do usuário: o passado na URL ou o autenticado
        $userId = $id ?: Auth::id();

        // se você tiver contexto de tenancy, pegue aqui
        $tenant = null;

        $isValid = $this->otp
            ->verifyById($userId, $request->code, $tenant);

        return response()->json(['valid' => $isValid]);
    }

    /**
     * Verifica o código OTP.
     * Se {id} for passado, usa aquele usuário; senão, Auth::user().
     */
    public function verify_(Request $request, $id = null)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        if ($id) {
            $usuario = Usuario::findOrFail($id);
        } else {
            $usuario = Auth::user();
        }

        $isValid = $this->otp->verify($usuario->otp_secret, $request->code);

        return response()->json(['valid' => $isValid]);
    }

    public function verifyLoginAjax(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|digits:6',
        ]);

        // Recupera o usuário autenticado temporário (depois de login/senha validados)
        $usr = session('usr_otp');
        if (! $usr || ! $usr->hasOtp()) {
            return response()->json(['valid' => false, 'error' => 'Usuário não encontrado ou não possui OTP.'], 422);
        }

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $secret = $usr->otp_secret;
        $code = $request->input('otp_code');

        if ($google2fa->verifyKey($secret, $code, 1)) {
            return response()->json(['valid' => true]);
        } else {
            return response()->json(['valid' => false, 'error' => 'Código OTP inválido.'], 422);
        }
    }

    public function verifyLoginAjax__(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|digits:6',
        ]);

        $usr = session('usr_otp');
        if (! $usr || ! $usr->hasOtp()) {
            return response()->json(['valid' => false, 'error' => 'Usuário não encontrado ou não possui OTP.'], 422);
        }

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $secret = $usr->otp_secret;
        $code = $request->input('otp_code');

        if ($google2fa->verifyKey($secret, $code, 1)) {
            // Agora você pode autenticar o usuário de vez
            Auth::loginUsingId($usr->id); // exemplo
            session()->forget('usr_otp'); // limpa a sessão temporária!
            return response()->json(['valid' => true]);
        } else {
            return response()->json(['valid' => false, 'error' => 'Código OTP inválido.'], 422);
        }
    }

    public function verifyLoginAjax_(Request $request)
    {
        $request->validate([
            'otp_code'  => 'required|digits:6',
        ]);
        try {
            \App\Helpers\UserOtpHelper::validateOtp($request, $request->login); // pelo campo login!
            return response()->json(['valid' => true]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $msg = isset($errors['otp_code']) ? $errors['otp_code'][0] : 'Código OTP inválido.';
            return response()->json(['valid' => false, 'error' => $msg], 422);
        }
    }


    public function verifyUserGlobal(Request $request, $id = null)
    {
        $request->validate(['code' => 'required|digits:6']);

        // se não passar $id, usa o auth()->user()->id
        $userId = $id ?: auth()->id();
        $tenant = null;
        // opcional: se você sabe o tenant do usuário, passe aqui

        $valid = app(\App\Services\OtpService::class)
            ->verifyById($userId, $request->code, $tenant);

        return response()->json(['valid' => $valid]);
    }


}
