<?php

namespace App\Services;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tenancy;

class OtpService
{
    /**
     * Instância do Google2FA resolvida pelo container
     *
     * @var \PragmaRX\Google2FA\Google2FA
     */
    protected $google2fa;

    /**
     * Injeta o serviço de Google2FA via container
     */
    public function __construct()
    {
        $this->google2fa = app('pragmarx.google2fa');
    }

    /**
     * Gera uma nova chave secreta OTP.
     *
     * @return string
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Gera o QR Code inline (SVG + base64) para configuração no app.
     *
     * @param  string  $company
     * @param  string  $userEmail
     * @param  string  $secret
     * @return string  Data URI com SVG do QR Code
     */
    public function getQRCodeInline(string $company, string $userEmail, string $secret): string
    {
        // Cria a URL no formato OTPAuth://
        $otpAuthUrl = $this->google2fa->getQRCodeUrl($company, $userEmail, $secret);

        // Renderiza SVG via BaconQrCode
        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(200),
                new SvgImageBackEnd()
            )
        );
        $svgString = $writer->writeString($otpAuthUrl);

        return 'data:image/svg+xml;base64,' . base64_encode($svgString);
    }

    /**
     * Verifica se o código informado corresponde ao secret.
     *
     * @param  string  $secret
     * @param  string  $code
     * @return bool
     */
    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Verifica OTP a partir de um model Usuario.
     */
    public function verifyForUser(Usuario $user, string $code): bool
    {
        return $this->verify($user->otp_secret, $code);
    }

    /**
     * Verifica OTP pelo ID de usuário, opcionalmente em outro tenant.
     *
     * @param  int    $userId
     * @param  string $code
     * @param  mixed  $tenant  (opcional) identificação do tenant
     * @return bool
     */
    public function verifyById(int $userId, string $code, $tenant = null): bool
    {
        if ($tenant) {
            // inicializa contexto do tenant — depende do seu pacote de tenancy
            Tenancy::initialize($tenant);
        }

        $user = Usuario::findOrFail($userId);
        return $this->verifyForUser($user, $code);
    }

}
