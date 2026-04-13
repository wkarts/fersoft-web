<?php

namespace App\Support;

class AuditContext
{
    /**
     * Quando true, o próximo log automático do BaseModel será ignorado.
     */
    protected static bool $skipNextModelAudit = false;

    /**
     * Permite que controller/service marquem explicitamente
     * que já executaram auditoria manual.
     * Mantido por compatibilidade futura.
     */
    protected static bool $manualAuditExecuted = false;

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

    public static function reset(): void
    {
        static::$skipNextModelAudit = false;
        static::$manualAuditExecuted = false;
    }
}
