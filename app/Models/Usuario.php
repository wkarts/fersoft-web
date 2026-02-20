<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\UsuarioAcesso;
use App\Helpers\EncryptionHelper;

class Usuario extends Authenticatable
{
    use Notifiable;
    protected $table = 'usuarios';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'nome',
        'senha',
        'login',
        'adm',
        'ativo',
        'img',
        'empresa_id',
        'permissao',
        'email',
        'somente_fiscal',
        'rota_acesso',
        'caixa_livre',
        'permite_desconto',
        'tipo_menu',
        'menu_representante',
        'locais',
        'local_padrao',
        'balanca_padrao_id',
        'otp_secret',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'senha',
        'otp_secret',
    ];

    public function empresa(){
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function funcionario(){
        return $this->hasOne('App\Models\Funcionario', 'usuario_id', 'id');
    }

    public function representante(){
        return $this->hasOne('App\Models\Representante', 'usuario_id');
    }

    public function config(){
        return $this->hasOne('App\Models\ConfigCaixa', 'usuario_id', 'id');
    }

    public function acessos(){
        return $this->hasMany('App\Models\UsuarioAcesso', 'usuario_id', 'id');
    }

    public function ultimoAcesso(){
        $acesso = UsuarioAcesso::
        where('usuario_id', $this->id)
        ->where('status', 1)
        ->orderBy('id', 'desc')
        ->first();

        return $acesso;
    }

    public function acesso(){
        $acesso = UsuarioAcesso::
        where('usuario_id', $this->id)
        ->where('status', 0)
        ->orderBy('id', 'desc')
        ->first();

        return $acesso;
    }

    /**
     * Mutator: ao atribuir $usuario->otp_secret = $plainSecret,
     * ciframos automaticamente antes de salvar no banco.
     */
    public function setOtpSecretAttribute(?string $value): void
    {
        // inicializa a chave
        EncryptionHelper::initialize();

        if ($value) {
            $this->attributes['otp_secret'] = EncryptionHelper::encrypt($value);
        } else {
            $this->attributes['otp_secret'] = null;
        }
    }

    /**
     * Accessor: ao ler $usuario->otp_secret,
     * descriptografa e retorna o valor em claro.
     */
    public function getOtpSecretAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        EncryptionHelper::initialize();

        return EncryptionHelper::decrypt($value);
    }

    /**
     * Conveniência: verifica se o usuário já configurou OTP.
     */
    public function hasOtp(): bool
    {
        return ! empty($this->otp_secret);
    }

    public function getAuthPassword()
    {
        return $this->senha; // IMPORTANTÍSSIMO: Laravel Auth procura "password" por padrão
    }
}
