<?php

namespace App\Models;

use App\Helpers\EncryptionHelper;
use App\Support\BinaryPayloadSanitizer;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class MtrConfig extends BaseModel
{
    protected $table = 'mtr_configs';

    protected $fillable = [
        'empresa_id', 'filial_id', 'usuario_id', 'orgao', 'cpf_cnpj',
        'cpf_usuario', 'senha', 'unidade_id', 'perfil', 'descricao',
        'ambiente', 'ativo',
    ];

    protected $hidden = ['senha'];

    protected $casts = [
        'ativo' => 'boolean',
        'empresa_id' => 'integer',
        'filial_id' => 'integer',
        'usuario_id' => 'integer',
    ];

    public function filial()
    {
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function getFilialNomeAttribute(): ?string
    {
        return $this->filial?->descricao
            ?? $this->filial?->nome;
    }

    /**
     * Persiste a credencial criptografada e a entrega em texto legível
     * somente para o backend autorizado. O formulário não a recebe automaticamente.
     */
    protected function senha(): Attribute
    {
        return Attribute::make(
            get: static function ($value) {
                if ($value === null || $value === '') {
                    return null;
                }

                $credencial = (string) $value;

                // Até três camadas para recuperar registros eventualmente
                // criptografados mais de uma vez em versões anteriores.
                for ($camada = 0; $camada <= 3; $camada++) {
                    $laravel = self::isLaravelEncrypted($credencial);
                    $legado = self::isLegacyEncrypted($credencial);

                    if (!$laravel && !$legado) {
                        // Compatibilidade com registros antigos em texto puro.
                        return $credencial;
                    }

                    if ($camada === 3) {
                        throw new \RuntimeException('Credencial MTR com camadas de criptografia inesperadas. Atualize o token.');
                    }

                    try {
                        if ($laravel) {
                            $credencial = Crypt::decryptString($credencial);
                        } else {
                            EncryptionHelper::initialize();
                            $credencial = EncryptionHelper::decrypt($credencial);
                            if (!is_string($credencial)) {
                                throw new \RuntimeException('Formato legado de credencial inválido.');
                            }
                        }
                    } catch (\Throwable $e) {
                        // Nunca devolver o ciphertext como se fosse um token válido.
                        $chave = $laravel ? 'APP_KEY' : 'ENCRYPTION_KEY';
                        throw new \RuntimeException(
                            "Não foi possível descriptografar a credencial MTR. Verifique a {$chave} original ou atualize o token.",
                            0,
                            $e
                        );
                    }
                }

                throw new \RuntimeException('Falha ao interpretar a credencial MTR.');
            },
            set: static function ($value) {
                if ($value === null || $value === '') {
                    return null;
                }

                return Crypt::encryptString((string) $value);
            }
        );
    }

    private static function isLaravelEncrypted(string $value): bool
    {
        // O prefixo identifica também payloads truncados pelo VARCHAR(500)
        // legado: eles precisam falhar explicitamente, nunca virar senhas.
        if (str_starts_with($value, 'eyJpdiI6')) {
            return true;
        }

        $json = base64_decode($value, true);
        $payload = $json !== false ? json_decode($json, true) : null;

        return is_array($payload)
            && isset($payload['iv'], $payload['value'], $payload['mac']);
    }

    private static function isLegacyEncrypted(string $value): bool
    {
        // EncryptionHelper histórico: base64(base64(ciphertext) . '::' . IV de 16 bytes).
        $decoded = base64_decode($value, true);

        return $decoded !== false
            && strlen($decoded) > 18
            && substr($decoded, -18, 2) === '::'
            && base64_decode(substr($decoded, 0, -18), true) !== false;
    }

    /**
     * A auditoria automática do BaseModel não deve gravar tokens, nem mesmo
     * como ciphertext. Preserva os demais dados e eventos de auditoria.
     */
    protected function auditSnapshot(): array
    {
        return self::ocultarSenhaAuditoria(parent::auditSnapshot());
    }

    protected function auditSnapshotOriginal(): array
    {
        // Não chamar getOriginal('senha'): pode estar ilegível por rotação da chave.
        $original = $this->getRawOriginal();
        $original = BinaryPayloadSanitizer::sanitize($original);

        return self::ocultarSenhaAuditoria(is_array($original) ? $original : []);
    }

    protected function auditChangedSnapshot(): array
    {
        if (!array_key_exists('senha', $this->getDirty())) {
            return parent::auditChangedSnapshot();
        }

        // Segue o snapshot enxuto do BaseModel, sem tentar descriptografar
        // a credencial antiga durante uma substituição corretiva.
        $dirty = $this->getDirty();
        unset($dirty['updated_at']);
        if ($dirty === []) {
            return [[], []];
        }

        $before = [];
        $after = [];
        foreach (array_keys($dirty) as $attribute) {
            if ($attribute === 'senha') {
                $before['senha'] = '[CREDENCIAL_PROTEGIDA]';
                $after['senha'] = '[CREDENCIAL_PROTEGIDA]';
                continue;
            }

            $before[$attribute] = $this->getOriginal($attribute);
            $after[$attribute] = $this->getAttribute($attribute);
        }

        $keyName = $this->getKeyName();
        $keyValue = $this->getKey();
        if ($keyValue !== null && $keyName !== '') {
            $before[$keyName] = $this->getOriginal($keyName) ?? $keyValue;
            $after[$keyName] = $keyValue;
        }

        $before = BinaryPayloadSanitizer::sanitize($before);
        $after = BinaryPayloadSanitizer::sanitize($after);

        return [
            is_array($before) ? $before : [],
            is_array($after) ? $after : [],
        ];
    }

    private static function ocultarSenhaAuditoria(array $dados): array
    {
        if (array_key_exists('senha', $dados)) {
            $dados['senha'] = '[CREDENCIAL_PROTEGIDA]';
        }

        return $dados;
    }

    public static function securityResource(): array
    {
        return array_replace_recursive(parent::securityResource(), [
            'module' => 'MTR',
            'name' => 'Credencial MTR',
            'plural_name' => 'Credenciais MTR',
            'route_prefix' => 'mtr/unidades',
            'sensitive' => true,
            'tenant_visible' => true,
        ]);
    }
}
