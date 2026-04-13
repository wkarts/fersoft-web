<?php

namespace App\Traits;

use Illuminate\Support\Facades\Session;
use App\Services\LogService;

trait TenantInjectable
{
    public static function bootTenantInjectable()
    {
        static::creating(function ($model) {
            static::injectTenantContextOnCreate($model);
        });

        static::updating(function ($model) {
            static::normalizeTenantContextOnUpdate($model);
        });
    }

    /**
     * Injeta contexto tenant apenas na criação.
     * Não sobrescreve valores válidos já definidos.
     */
    protected static function injectTenantContextOnCreate($model): void
    {
        try {
            $sessao = Session::get('user_logged', []);

            $empresaId = $sessao['empresa'] ?? null;
            $usuarioId = $sessao['id'] ?? null;
            $filialId  = $sessao['local_padrao'] ?? null;

            $empresaId = static::normalizeNullableInt($empresaId);
            $usuarioId = static::normalizeNullableInt($usuarioId);
            $filialId  = static::normalizeNullableInt($filialId);

            $fillable = $model->getFillable();

            // empresa_id
            if (in_array('empresa_id', $fillable, true)) {
                if (isset($model->empresa_id)) {
                    $model->empresa_id = static::normalizeNullableInt($model->empresa_id);
                }

                if (empty($model->empresa_id) && !empty($empresaId)) {
                    $model->empresa_id = $empresaId;
                }
            }

            // usuario_id
            if (in_array('usuario_id', $fillable, true)) {
                if (isset($model->usuario_id)) {
                    $model->usuario_id = static::normalizeNullableInt($model->usuario_id);
                }

                if (empty($model->usuario_id) && !empty($usuarioId)) {
                    $model->usuario_id = $usuarioId;
                }
            }

            // filial_id
            if (in_array('filial_id', $fillable, true)) {
                if (isset($model->filial_id)) {
                    $model->filial_id = static::normalizeNullableInt($model->filial_id);
                }

                if (empty($model->filial_id) && $filialId !== null) {
                    $model->filial_id = $filialId;
                }
            }
        } catch (\Throwable $e) {
            static::registerTenantInjectableError($model, 'creating', $e);
        }
    }

    /**
     * No update não reinjeta contexto.
     * Apenas normaliza campos tenant quando vierem inválidos.
     */
    protected static function normalizeTenantContextOnUpdate($model): void
    {
        try {
            $fillable = $model->getFillable();

            if (in_array('empresa_id', $fillable, true) && isset($model->empresa_id)) {
                $model->empresa_id = static::normalizeNullableInt($model->empresa_id);
            }

            if (in_array('usuario_id', $fillable, true) && isset($model->usuario_id)) {
                $model->usuario_id = static::normalizeNullableInt($model->usuario_id);
            }

            if (in_array('filial_id', $fillable, true) && isset($model->filial_id)) {
                $model->filial_id = static::normalizeNullableInt($model->filial_id);
            }
        } catch (\Throwable $e) {
            static::registerTenantInjectableError($model, 'updating', $e);
        }
    }

    /**
     * Normaliza inteiro nullable:
     * - null, '', 'null', <= 0 => null
     * - > 0 => int
     */
    protected static function normalizeNullableInt($value): ?int
    {
        if ($value === null || $value === '' || $value === 'null') {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    /**
     * Registra erro técnico do trait.
     */
    protected static function registerTenantInjectableError($model, string $action, \Throwable $e): void
    {
        \Log::warning(
            'Falha ao processar contexto tenant em ' . get_class($model) . ': ' . $e->getMessage()
        );

        try {
            $sessao = Session::get('user_logged', []);

            $empresaId = $sessao['empresa'] ?? null;
            $usuarioId = $sessao['id'] ?? null;
            $filialId  = $sessao['local_padrao'] ?? null;

            $logService = new LogService(
                static::normalizeNullableInt($empresaId),
                static::normalizeNullableInt($usuarioId),
                static::normalizeNullableInt($filialId)
            );

            $dadosErro = [
                'mensagem_erro' => $e->getMessage(),
                'modelo' => get_class($model),
                'trace' => $e->getTraceAsString(),
                'empresa_id_model' => $model->empresa_id ?? null,
                'usuario_id_model' => $model->usuario_id ?? null,
                'filial_id_model' => $model->filial_id ?? null,
                'acao' => $action,
            ];

            $logService->registrar(
                'error',
                'TenantInjectable::' . $action,
                [
                    'dados_antes' => null,
                    'dados_depois' => $dadosErro,
                ]
            );
        } catch (\Throwable $inner) {
            \Log::error(
                'Falha ao registrar erro em LogService (TenantInjectable::' . $action . '): '
                . $inner->getMessage()
            );
        }
    }
}
