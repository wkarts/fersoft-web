<?php

namespace App\Support;

class AuditContext
{
    /**
     * Quando true, o próximo log automático do BaseModel será ignorado.
     * Mantido por compatibilidade com fluxos antigos.
     */
    protected static bool $skipNextModelAudit = false;

    /**
     * Permite que controller/service marquem explicitamente que já executaram
     * auditoria manual. Mantido por compatibilidade com código legado.
     */
    protected static bool $manualAuditExecuted = false;

    /**
     * Supressão escopada da auditoria automática do BaseModel.
     * Diferente de saveQuietly(), não desliga os demais eventos Eloquent.
     */
    protected static int $modelAuditSuppressionDepth = 0;

    public static function skipNextModelAudit(): void
    {
        static::$skipNextModelAudit = true;
    }

    public static function shouldSkipNextModelAudit(): bool
    {
        return static::$skipNextModelAudit;
    }

    public static function consumeSkipNextModelAudit(): bool
    {
        $value = static::$skipNextModelAudit;
        static::$skipNextModelAudit = false;

        return $value;
    }

    public static function markManualAuditExecuted(): void
    {
        static::$manualAuditExecuted = true;
    }

    public static function consumeManualAuditExecuted(): bool
    {
        $value = static::$manualAuditExecuted;
        static::$manualAuditExecuted = false;

        return $value;
    }

    public static function isModelAuditSuppressed(): bool
    {
        return static::$modelAuditSuppressionDepth > 0;
    }

    /**
     * Executa apenas a operação informada sem auditoria automática do BaseModel.
     * Outros observers/listeners Eloquent continuam funcionando normalmente.
     */
    public static function withoutModelAudit(callable $callback)
    {
        static::$modelAuditSuppressionDepth++;

        try {
            return $callback();
        } finally {
            static::$modelAuditSuppressionDepth = max(0, static::$modelAuditSuppressionDepth - 1);
        }
    }

    public static function reset(): void
    {
        static::$skipNextModelAudit = false;
        static::$manualAuditExecuted = false;
        static::$modelAuditSuppressionDepth = 0;
    }
}
