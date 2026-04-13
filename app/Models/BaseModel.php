<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use App\Services\LogService;
use App\Support\AuditContext;
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
    protected array $auditDeleteValues = [];
    protected array $auditRestoreValues = [];

    protected static array $deletedAtSupportCache = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->auditOldValues = [];
        });

        static::created(function ($model) {
            $model->writeAuditLog('create', null, $model->toArray());
        });

        static::updating(function ($model) {
            $model->auditOldValues = $model->getOriginal();
        });

        static::updated(function ($model) {
            $model->writeAuditLog(
                'update',
                $model->auditOldValues,
                $model->fresh()?->toArray() ?? $model->toArray()
            );
        });

        static::deleting(function ($model) {
            $model->auditDeleteValues = $model->toArray();

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

    /**
     * Marca exclusão física intencional.
     */
    protected bool $forceDeletingFlag = false;

    public function forceDeleteSmart(): bool
    {
        $this->forceDeletingFlag = true;

        try {
            return (bool) parent::delete();
        } finally {
            $this->forceDeletingFlag = false;
        }
    }

    protected function isForceDeleting(): bool
    {
        return $this->forceDeletingFlag === true;
    }

    /**
     * Restauração custom para tabelas com deleted_at.
     */
    public function restoreSmart(): bool
    {
        if (!$this->supportsSoftDeleteColumn()) {
            return false;
        }

        $this->auditRestoreValues = $this->toArray();

        $this->setAttribute('deleted_at', null);

        $result = $this->saveQuietly();

        if ($result) {
            $this->writeAuditLog(
                'restore',
                $this->auditRestoreValues,
                $this->fresh()?->toArray() ?? $this->toArray()
            );
        }

        return $result;
    }

    /**
     * Verifica se o registro está "soft deleted".
     */
    public function trashedSmart(): bool
    {
        if (!$this->supportsSoftDeleteColumn()) {
            return false;
        }

        return !empty($this->getAttribute('deleted_at'));
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

    public function disableAuditForThisInstance(): self
    {
        $this->auditEnabled = false;
        return $this;
    }

    protected function shouldWriteAuditLog(): bool
    {
        if (!$this->auditEnabled) {
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

            $sessao = Session::get('user_logged', []);

            // Usa campos do model se existirem, senão usa sessão
            $empresaId = $this->getAttributeIfExists('empresa_id') ?? ($sessao['empresa'] ?? null);
            $usuarioId = $sessao['id'] ?? $this->getAttributeIfExists('usuario_id');
            $filialId  = $this->getAttributeIfExists('filial_id') ?? ($sessao['local_padrao'] ?? null);

            if (in_array($filialId, [null, 'null', -1, '-1', 0, '0'], true)) {
                $filialId = null;
            }

            $logService = new LogService(
                $this->normalizeNullableInt($empresaId),
                $this->normalizeNullableInt($usuarioId),
                $this->normalizeNullableInt($filialId)
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

    protected function normalizeNullableInt($value): ?int
    {
        if ($value === null || $value === '' || $value === 'null') {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }
}
