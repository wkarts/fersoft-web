<?php

namespace App\Traits;

use Illuminate\Support\Facades\Session;
use App\Services\LogService;

trait MultiEmpresaTrait
{
    public static function bootFilialInjectable()
    {
        // Antes de criar (create)
        static::creating(function ($model) {
            try {
                if (in_array('filial_id', $model->getFillable())) {
                    // ─── 1) NORMALIZAÇÃO do valor que veio no próprio model ───
                    if (isset($model->filial_id) && (int) $model->filial_id <= 0) {
                        $model->filial_id = null;
                    }

                    // ─── 2) BUSCA e NORMALIZAÇÃO do padrão na sessão ───
                    $padrao = Session::get('user_logged.local_padrao', null);
                    if (isset($padrao) && (int) $padrao <= 0) {
                        $padrao = null;
                    }

                    // ─── 3) INJEÇÃO do padrão (se ainda estiver “vazio”) ───
                    if (empty($model->filial_id) && $padrao !== null) {
                        $model->filial_id = $padrao;
                    }
                }
            } catch (\Throwable $e) {
                // 1) Log de advertência no log padrão do Laravel
                \Log::warning(
                    "Falha ao injetar filial_id em " . get_class($model) . ": " . $e->getMessage()
                );

                // 2) Também registrar via LogService no banco (se o serviço estiver disponível)
                try {
                    // Pegar contexto da sessão
                    $sessao    = Session::get('user_logged', []);
                    $empresaId = $sessao['empresa']        ?? null;
                    $usuarioId = $sessao['id']             ?? null;
                    $filialId  = $sessao['local_padrao']   ?? null;

                    // Instanciar LogService
                    $logService = new LogService($empresaId, $usuarioId, $filialId);

                    // Preparar dados do erro para o log
                    $dadosErro = [
                        'mensagem_erro'   => $e->getMessage(),
                        'modelo'          => get_class($model),
                        'trace'           => $e->getTraceAsString(),
                        'filial_atributo' => $model->filial_id ?? null,
                    ];

                    // Registrar no banco via LogService
                    $logService->registrar(
                        'error',                        // ação
                        'FilialInjectable::creating',   // contexto/modelo
                        [
                            'dados_antes'  => null,
                            'dados_depois' => $dadosErro,
                        ]
                    );
                } catch (\Throwable $inner) {
                    // Se falhar ao instanciar LogService ou gravar no banco, escrevemos também no log padrão
                    \Log::error(
                        "Falha ao registrar erro em LogService (FilialInjectable::creating): "
                        . $inner->getMessage()
                    );
                }
            }
        });

        // Antes de atualizar (update)
        static::updating(function ($model) {
            try {
                if (in_array('filial_id', $model->getFillable())) {
                    // ─── 1) NORMALIZAÇÃO do valor atribuído no update ───
                    if (isset($model->filial_id) && (int) $model->filial_id <= 0) {
                        $model->filial_id = null;
                    }

                    // Se desejar, pode inserir novamente o bloco de busca/do padrão e injeção:
                    /*
                    $padrao = Session::get('user_logged.local_padrao', null);
                    if (isset($padrao) && (int) $padrao <= 0) {
                        $padrao = null;
                    }
                    if (empty($model->filial_id) && $padrao !== null) {
                        $model->filial_id = $padrao;
                    }
                    */
                }
            } catch (\Throwable $e) {
                // 1) Log de advertência no log padrão do Laravel
                \Log::warning(
                    "Falha ao injetar filial_id em " . get_class($model) . ": " . $e->getMessage()
                );

                // 2) Registrar via LogService no banco
                try {
                    $sessao    = Session::get('user_logged', []);
                    $empresaId = $sessao['empresa']        ?? null;
                    $usuarioId = $sessao['id']             ?? null;
                    $filialId  = $sessao['local_padrao']   ?? null;

                    $logService = new LogService($empresaId, $usuarioId, $filialId);

                    $dadosErro = [
                        'mensagem_erro'   => $e->getMessage(),
                        'modelo'          => get_class($model),
                        'trace'           => $e->getTraceAsString(),
                        'filial_atributo' => $model->filial_id ?? null,
                    ];

                    $logService->registrar(
                        'error',
                        'FilialInjectable::updating',
                        [
                            'dados_antes'  => null,
                            'dados_depois' => $dadosErro,
                        ]
                    );
                } catch (\Throwable $inner) {
                    \Log::error(
                        "Falha ao registrar erro em LogService (FilialInjectable::updating): "
                        . $inner->getMessage()
                    );
                }
            }
        });
    }
}
