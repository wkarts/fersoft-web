<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use App\Services\LogService;
use App\Support\AuditContext;
use App\Support\BinaryPayloadSanitizer;
use App\Traits\TenantInjectable;

abstract class BaseModel extends Model
{
    use TenantInjectable;

    protected $guarded = ['id'];

    public $timestamps = true;

    /**
     * Habilita/Desabilita auditoria automática por model.
     */
    protected bool $auditEnabled = true;

    /**
     * Armazena snapshots temporários para log.
     */
    protected array $auditOldValues = [];
    protected array $auditNewValues = [];
    protected array $auditDeleteValues = [];
    protected array $auditRestoreValues = [];

    protected static array $deletedAtSupportCache = [];

    /**
     * Marca exclusão física intencional.
     */
    protected bool $forceDeletingFlag = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->auditOldValues = [];
        });

        static::created(function ($model) {
            $model->writeAuditLog('create', null, $model->auditSnapshot());
        });

        static::updating(function ($model) {
            [$model->auditOldValues, $model->auditNewValues] = $model->auditChangedSnapshot();
        });

        static::updated(function ($model) {
            // Evita gravar um evento de update sem mudança funcional real
            // (por exemplo, apenas updated_at).
            if (empty($model->auditOldValues) && empty($model->auditNewValues)) {
                return;
            }

            $model->writeAuditLog(
                'update',
                $model->auditOldValues,
                $model->auditNewValues
            );
        });

        static::deleting(function ($model) {
            $model->auditDeleteValues = $model->auditSnapshot();

            // Se a tabela suporta deleted_at, converte exclusão em soft delete custom
            if ($model->supportsSoftDeleteColumn() && !$model->isForceDeleting()) {
                $model->setAttribute('deleted_at', now());

                // evita loop de eventos e salva direto
                $model->saveQuietly();

                $model->writeAuditLog('delete', $model->auditDeleteValues, null);

                return false;
            }
        });

        static::deleted(function ($model) {
            // Só grava aqui se foi delete físico
            if (!$model->supportsSoftDeleteColumn() || $model->isForceDeleting()) {
                $model->writeAuditLog('delete', $model->auditDeleteValues, null);
            }
        });
    }

    public function forceDeleteSmart()
    {
        $this->forceDeletingFlag = true;

        try {
            return parent::delete();
        } finally {
            $this->forceDeletingFlag = false;
        }
    }

    /**
     * Compatibilidade com API padrão do Laravel.
     */
    public function forceDelete()
    {
        return $this->forceDeleteSmart();
    }

    /**
     * Compatível com SoftDeletes do Laravel.
     * Não usar tipagem rígida aqui.
     */
    public function isForceDeleting()
    {
        return $this->forceDeletingFlag === true;
    }

    /**
     * Restauração custom para tabelas com deleted_at.
     */
    public function restoreSmart()
    {
        if (!$this->supportsSoftDeleteColumn()) {
            return false;
        }

        $this->auditRestoreValues = $this->auditSnapshot();

        $this->setAttribute('deleted_at', null);

        $result = $this->saveQuietly();

        if ($result) {
            $this->writeAuditLog(
                'restore',
                $this->auditRestoreValues,
                $this->fresh()?->auditSnapshot() ?? $this->auditSnapshot()
            );
        }

        return $result;
    }

    /**
     * Compatibilidade com API padrão do Laravel.
     */
    public function restore()
    {
        return $this->restoreSmart();
    }

    /**
     * Verifica se o registro está "soft deleted".
     */
    public function trashedSmart()
    {
        if (!$this->supportsSoftDeleteColumn()) {
            return false;
        }

        return !empty($this->getAttribute('deleted_at'));
    }

    /**
     * Compatibilidade com API padrão do Laravel.
     */
    public function trashed()
    {
        return $this->trashedSmart();
    }

    /**
     * Escopo manual para ignorar excluídos quando houver deleted_at.
     */
    public function scopeWithoutDeleted($query)
    {
        if ($this->supportsSoftDeleteColumn()) {
            $query->whereNull($this->getTable() . '.deleted_at');
        }

        return $query;
    }

    /**
     * Escopo manual para incluir excluídos.
     */
    public function scopeWithDeleted($query)
    {
        return $query;
    }

    /**
     * Escopo manual para somente excluídos.
     */
    public function scopeOnlyDeleted($query)
    {
        if ($this->supportsSoftDeleteColumn()) {
            $query->whereNotNull($this->getTable() . '.deleted_at');
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Compatibilidade com API padrão do Laravel.
     * Assinatura alinhada ao SoftDeletes.
     */
    public function scopeWithTrashed($query, $withTrashed = true)
    {
        if ($withTrashed === false) {
            return $this->scopeWithoutDeleted($query);
        }

        return $this->scopeWithDeleted($query);
    }

    /**
     * Compatibilidade com API padrão do Laravel.
     */
    public function scopeOnlyTrashed($query)
    {
        return $this->scopeOnlyDeleted($query);
    }

    /**
     * Compatibilidade com API padrão do Laravel.
     */
    public function scopeWithoutTrashed($query)
    {
        return $this->scopeWithoutDeleted($query);
    }



    /**
     * Identidade padrão de segurança do recurso.
     *
     * Este método é apenas metadado consultivo para o scanner de Segurança.
     * Não executa bloqueio, validação, permissão ou regra de CRUD.
     */
    public static function securityResource(): array
    {
        $modelClass = static::class;
        $shortName = class_basename($modelClass);
        $instance = new static();

        return [
            'module' => 'Geral',
            'name' => static::securityHumanModelName($shortName),
            'plural_name' => static::securityHumanPluralModelName($shortName),
            'description' => 'Recurso do sistema.',
            'route_prefix' => static::securityDefaultRoutePrefix($shortName),
            'icon' => 'default',
            'sensitive' => false,
            'tenant_visible' => true,
            'super_admin_only' => false,
            'table_name' => $instance->getTable(),
            'actions' => static::securityDefaultActions(),
        ];
    }

    protected static function securityDefaultActions(): array
    {
        return [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'restore' => false,
            'export' => true,
            'print' => true,
        ];
    }

    protected static function securityHumanModelName(string $name): string
    {
        return trim((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $name));
    }

    protected static function securityHumanPluralModelName(string $name): string
    {
        return \Illuminate\Support\Str::plural(static::securityHumanModelName($name));
    }

    protected static function securityDefaultRoutePrefix(string $name): string
    {
        return \Illuminate\Support\Str::kebab(\Illuminate\Support\Str::pluralStudly($name));
    }

    public static function supportsSoftDelete(): bool
    {
        $instance = new static();
        return $instance->supportsSoftDeleteColumn();
    }

    public function disableAuditForThisInstance(): self
    {
        $this->auditEnabled = false;
        return $this;
    }

    protected function shouldWriteAuditLog(): bool
    {
        if (!$this->auditEnabled || AuditContext::isModelAuditSuppressed()) {
            return false;
        }

        if (AuditContext::consumeSkipNextModelAudit()) {
            return false;
        }

        if (AuditContext::consumeManualAuditExecuted()) {
            return false;
        }

        return true;
    }

    protected function writeAuditLog(string $acao, $dadosAntes = null, $dadosDepois = null): void
    {
        try {
            if (!$this->shouldWriteAuditLog()) {
                return;
            }

            // Barreira final da auditoria: mesmo chamadas manuais/legadas que
            // tragam snapshots, JSON aninhado ou Base64 nunca persistem binário.
            $dadosAntes = BinaryPayloadSanitizer::sanitize($dadosAntes);
            $dadosDepois = BinaryPayloadSanitizer::sanitize($dadosDepois);

            $sessao = Session::get('user_logged', []);

            // Usa campos do model se existirem, senão usa sessão
            $empresaId = $this->getAttributeIfExists('empresa_id') ?? ($sessao['empresa'] ?? null);
            $usuarioId = $sessao['id'] ?? $this->getAttributeIfExists('usuario_id');
            $filialId  = $this->getAttributeIfExists('filial_id') ?? ($sessao['local_padrao'] ?? null);

            if (in_array($filialId, [null, 'null', -1, '-1', 0, '0'], true)) {
                $filialId = null;
            }

            $logService = new LogService(
                static::normalizeNullableInt($empresaId),
                static::normalizeNullableInt($usuarioId),
                static::normalizeNullableInt($filialId)
            );

            $logService->registrar($acao, get_class($this), [
                'registro_id' => $this->id ?? null,
                'dados_antes' => $dadosAntes,
                'dados_depois' => $dadosDepois,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Erro ao registrar auditoria automática no BaseModel', [
                'modelo' => get_class($this),
                'acao' => $acao,
                'erro' => $e->getMessage(),
            ]);
        }
    }


    /**
     * Snapshot de auditoria sem relações Eloquent carregadas.
     *
     * getAttributes() preserva os atributos persistidos do próprio model e
     * evita que toArray() replique relações (tickets/imagens) dentro do log.
     * Campos JSON/string continuam sendo sanitizados recursivamente.
     */
    protected function auditSnapshot(): array
    {
        $snapshot = BinaryPayloadSanitizer::sanitize($this->getAttributes());

        return is_array($snapshot) ? $snapshot : [];
    }

    /**
     * Snapshot enxuto somente dos atributos realmente alterados.
     *
     * Antes, cada update copiava o registro inteiro para dados_anteriores e
     * dados_depois. Em models de pesagem isso multiplicava JSON e aumentava
     * drasticamente o volume da tabela logs. Aqui preservamos a conformidade:
     * cada campo alterado continua auditado com valor anterior e novo, mas sem
     * repetir atributos que não participaram da operação.
     */
    protected function auditChangedSnapshot(): array
    {
        $dirty = $this->getDirty();

        // Timestamp técnico não deve, sozinho, gerar evento de negócio.
        unset($dirty['updated_at']);

        if (empty($dirty)) {
            return [[], []];
        }

        $before = [];
        $after = [];

        foreach (array_keys($dirty) as $attribute) {
            $before[$attribute] = $this->getOriginal($attribute);
            $after[$attribute] = $this->getAttribute($attribute);
        }

        // Mantém a identidade do registro em snapshots enxutos. A tabela logs
        // atual não possui coluna registro_id; por isso o ID precisa continuar
        // presente nos próprios JSONs de auditoria para rastreabilidade.
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

    /**
     * Estado original do model, também livre de relações e conteúdo binário.
     */
    protected function auditSnapshotOriginal(): array
    {
        $snapshot = BinaryPayloadSanitizer::sanitize($this->getOriginal());

        return is_array($snapshot) ? $snapshot : [];
    }

    protected function supportsSoftDeleteColumn(): bool
    {
        $class = static::class;

        if (array_key_exists($class, static::$deletedAtSupportCache)) {
            return static::$deletedAtSupportCache[$class];
        }

        try {
            $supports = Schema::hasColumn($this->getTable(), 'deleted_at');
        } catch (\Throwable $e) {
            $supports = false;
        }

        static::$deletedAtSupportCache[$class] = $supports;

        return $supports;
    }

    protected function getAttributeIfExists(string $attribute)
    {
        $attributes = $this->getAttributes();

        if (array_key_exists($attribute, $attributes)) {
            return $attributes[$attribute];
        }

        return null;
    }

    protected static function normalizeNullableInt($value): ?int
    {
        if ($value === null || $value === '' || $value === 'null') {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }
}
