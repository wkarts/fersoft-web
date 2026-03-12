<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ProdutoIbsCbsUpdater
 *
 * Atualiza IBS/CBS/IS em produtos, baseado na tabela de TIPI import, casando por NCM.
 */
class ProdutoIbsCbsUpdater
{
    /** @var string[] */
    private array $produtoTableCandidates = ['produtos', 'tbproduto'];
    /** @var string[] */
    private array $tipiTableCandidates    = ['tbtipi_import', 'tbtipi_imports'];

    /**
     * Execução principal.
     *
     * $options:
     * - on_updated: callable(array $info): void
     * - on_no_tipi: callable(array $info): void
     */
    public function exec(?int $empresaId = null, bool $onlyMissing = true, int $chunkSize = 500, array $options = []): array
    {
        $t0 = microtime(true);

        $onUpdated = isset($options['on_updated']) && is_callable($options['on_updated'])
            ? $options['on_updated']
            : null;

        $onNoTipi = isset($options['on_no_tipi']) && is_callable($options['on_no_tipi'])
            ? $options['on_no_tipi']
            : null;

        $produtoTable = $this->resolveTable($this->produtoTableCandidates, 'produtos');
        $tipiTable    = $this->resolveTable($this->tipiTableCandidates, 'tbtipi_import');

        // Resolve colunas (produtos)
        $produtoPk   = $this->resolveRequiredColumn($produtoTable, ['id', 'prod_idproduto']);
        $produtoNcm  = $this->resolveRequiredColumn($produtoTable, ['ncm', 'prod_codncm']);

        // colunas usuais para relatório (opcionais)
        $colCodigo    = $this->resolveOptionalColumn($produtoTable, ['codigo', 'prod_codigo']);
        $colReferencia= $this->resolveOptionalColumn($produtoTable, ['referencia', 'prod_referencia']);
        $colDescricao = $this->resolveOptionalColumn($produtoTable, ['nome', 'descricao', 'prod_descricao']);

        $colCstIbs    = $this->resolveRequiredColumn($produtoTable, ['cst_ibs_cbs']);
        $colClassTrib = $this->resolveRequiredColumn($produtoTable, ['class_trib_ibs_cbs']);
        $colRedIbs    = $this->resolveRequiredColumn($produtoTable, ['reducao_ibs']);
        $colRedCbs    = $this->resolveRequiredColumn($produtoTable, ['reducao_cbs']);
        $colFlagIs    = $this->resolveRequiredColumn($produtoTable, ['flag_is']);
        $colCstIs     = $this->resolveRequiredColumn($produtoTable, ['cst_is']);
        $colAliqIs    = $this->resolveRequiredColumn($produtoTable, ['aliq_is']);

        $colProdutoEmpresa = $this->resolveOptionalColumn($produtoTable, ['empresa_id']);
        $colProdutoDeleted = $this->resolveOptionalColumn($produtoTable, ['deleted_at']);
        $colProdutoUpdated = $this->resolveOptionalColumn($produtoTable, ['updated_at']);

        // Resolve colunas (TIPI)
        $tipiNcm     = $this->resolveRequiredColumn($tipiTable, ['ncm']);
        $tipiCst     = $this->resolveOptionalColumn($tipiTable, ['cst_ibs_cbs']);
        $tipiClass   = $this->resolveOptionalColumn($tipiTable, ['cclasstrib']);
        $tipiTipoRed = $this->resolveOptionalColumn($tipiTable, ['tipo_reducao']);

        $tipiId      = $this->resolveRequiredColumn($tipiTable, ['id']);
        $tipiUpd     = $this->resolveOptionalColumn($tipiTable, ['updated_at']);
        $tipiCre     = $this->resolveOptionalColumn($tipiTable, ['created_at']);
        $tipiDeleted = $this->resolveOptionalColumn($tipiTable, ['deleted_at']);
        $tipiEmpresa = $this->resolveOptionalColumn($tipiTable, ['empresa_id']);

        // Carrega TIPI em memória (map por NCM normalizado, pegando a linha mais "recente")
        $tipiMap = $this->loadTipiMap(
            table: $tipiTable,
            colNcm: $tipiNcm,
            colId: $tipiId,
            colCst: $tipiCst,
            colClass: $tipiClass,
            colTipoReducao: $tipiTipoRed,
            colUpdatedAt: $tipiUpd,
            colCreatedAt: $tipiCre,
            colDeletedAt: $tipiDeleted,
            colEmpresaId: $tipiEmpresa,
            empresaId: $empresaId
        );

        $stats = [
            'ok' => true,
            'empresa_id' => $empresaId,
            'only_missing' => $onlyMissing,
            'chunk_size' => $chunkSize,
            'total_scanned' => 0,
            'total_targets' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'no_tipi' => 0,
            'duration_ms' => 0,
        ];

        $selectCols = [
            $produtoPk,
            $produtoNcm,
            $colCstIbs,
            $colClassTrib,
            $colRedIbs,
            $colRedCbs,
            $colFlagIs,
            $colCstIs,
            $colAliqIs,
        ];

        if ($colCodigo)     $selectCols[] = $colCodigo;
        if ($colReferencia) $selectCols[] = $colReferencia;
        if ($colDescricao)  $selectCols[] = $colDescricao;

        $qry = DB::table($produtoTable)->select($selectCols);

        if ($colProdutoDeleted) {
            $qry->whereNull($colProdutoDeleted);
        }

        if ($empresaId !== null && $colProdutoEmpresa) {
            $qry->where($colProdutoEmpresa, '=', $empresaId);
        }

        if ($onlyMissing) {
            $qry->where(function ($q) use ($colCstIbs, $colClassTrib, $colRedIbs, $colRedCbs, $colFlagIs, $colCstIs, $colAliqIs) {
                $q->whereNull($colRedIbs)
                    ->orWhereNull($colRedCbs)
                    ->orWhereNull($colCstIs)
                    ->orWhereNull($colAliqIs)
                    ->orWhereRaw("COALESCE(TRIM({$colCstIbs}), '') = ''")
                    ->orWhereRaw("COALESCE(TRIM({$colClassTrib}), '') = ''")
                    ->orWhereRaw("COALESCE(TRIM({$colFlagIs}), '') = ''");
            });
        }

        $now = Carbon::now();

        $qry->orderBy($produtoPk)
            ->chunkById(max(1, $chunkSize), function ($rows) use (
                &$stats,
                $produtoTable,
                $produtoPk,
                $produtoNcm,
                $colCodigo,
                $colReferencia,
                $colDescricao,
                $colCstIbs,
                $colClassTrib,
                $colRedIbs,
                $colRedCbs,
                $colFlagIs,
                $colCstIs,
                $colAliqIs,
                $colProdutoUpdated,
                $now,
                $onlyMissing,
                $tipiMap,
                $onUpdated,
                $onNoTipi
            ) {
                $pendingUpdated = [];
                $pendingNoTipi  = [];

                DB::beginTransaction();
                try {
                    foreach ($rows as $p) {
                        $stats['total_scanned']++;

                        $rawNcm = (string)($p->{$produtoNcm} ?? '');
                        $key = $this->normalizeNcm($rawNcm);

                        $infoBase = [
                            'id' => $p->{$produtoPk} ?? null,
                            'codigo' => $colCodigo ? (string)($p->{$colCodigo} ?? '') : '',
                            'referencia' => $colReferencia ? (string)($p->{$colReferencia} ?? '') : '',
                            'descricao' => $colDescricao ? (string)($p->{$colDescricao} ?? '') : '',
                            'ncm' => $rawNcm,
                        ];

                        if ($key === '' || !isset($tipiMap[$key])) {
                            $stats['no_tipi']++;
                            if ($onNoTipi) {
                                $pendingNoTipi[] = $infoBase + ['reason' => 'Sem correspondência na TIPI (NCM)'];
                            }
                            continue;
                        }

                        $stats['total_targets']++;
                        $tip = $tipiMap[$key];

                        // atuais
                        $currCst = trim((string)($p->{$colCstIbs} ?? ''));
                        $currCls = trim((string)($p->{$colClassTrib} ?? ''));
                        $currFlag= trim((string)($p->{$colFlagIs} ?? ''));

                        $currRedIbs = $p->{$colRedIbs} ?? null;
                        $currRedCbs = $p->{$colRedCbs} ?? null;
                        $currCstIs  = $p->{$colCstIs} ?? null;
                        $currAliqIs = $p->{$colAliqIs} ?? null;

                        // valores calculados
                        $valCst   = ($onlyMissing && $currCst !== '') ? $currCst : ($tip['cst'] !== '' ? $tip['cst'] : 'N');
                        $valCls   = ($onlyMissing && $currCls !== '') ? $currCls : $tip['class'];

                        $valRed   = $tip['reducao'];
                        $valRedI  = ($onlyMissing && $currRedIbs !== null) ? (float)$currRedIbs : $valRed;
                        $valRedC  = ($onlyMissing && $currRedCbs !== null) ? (float)$currRedCbs : $valRed;

                        $valFlag  = ($onlyMissing && $currFlag !== '') ? $currFlag : 'N';
                        $valCstIs = ($onlyMissing && $currCstIs !== null) ? (float)$currCstIs : 0.0;
                        $valAliq  = ($onlyMissing && $currAliqIs !== null) ? (float)$currAliqIs : 0.0;

                        // update idempotente
                        $upd = [];
                        $changes = [];

                        if ($currCst !== $valCst) { $upd[$colCstIbs] = $valCst; $changes[] = 'CST IBS/CBS'; }
                        if ($currCls !== $valCls) { $upd[$colClassTrib] = $valCls; $changes[] = 'Class. Trib IBS/CBS'; }

                        if ($currRedIbs === null || (float)$currRedIbs !== (float)$valRedI) { $upd[$colRedIbs] = $valRedI; $changes[] = 'Redução IBS'; }
                        if ($currRedCbs === null || (float)$currRedCbs !== (float)$valRedC) { $upd[$colRedCbs] = $valRedC; $changes[] = 'Redução CBS'; }

                        if ($currFlag !== $valFlag) { $upd[$colFlagIs] = $valFlag; $changes[] = 'IS (Flag)'; }
                        if ($currCstIs === null || (float)$currCstIs !== (float)$valCstIs) { $upd[$colCstIs] = $valCstIs; $changes[] = 'CST IS'; }
                        if ($currAliqIs === null || (float)$currAliqIs !== (float)$valAliq) { $upd[$colAliqIs] = $valAliq; $changes[] = 'Alíquota IS'; }

                        if ($colProdutoUpdated) {
                            $upd[$colProdutoUpdated] = $now;
                        }

                        if (empty($upd)) {
                            $stats['unchanged']++;
                            continue;
                        }

                        DB::table($produtoTable)
                            ->where($produtoPk, '=', $p->{$produtoPk})
                            ->update($upd);

                        $stats['updated']++;

                        if ($onUpdated) {
                            $pendingUpdated[] = $infoBase + [
                                    'changes' => $changes,
                                    'after' => [
                                        'cst_ibs_cbs' => $valCst,
                                        'class_trib_ibs_cbs' => $valCls,
                                        'reducao_ibs' => $valRedI,
                                        'reducao_cbs' => $valRedC,
                                        'flag_is' => $valFlag,
                                        'cst_is' => $valCstIs,
                                        'aliq_is' => $valAliq,
                                    ],
                                ];
                        }
                    }

                    DB::commit();

                    // Só registra em relatório após commit
                    if ($onUpdated && !empty($pendingUpdated)) {
                        foreach ($pendingUpdated as $i) $onUpdated($i);
                    }
                    if ($onNoTipi && !empty($pendingNoTipi)) {
                        foreach ($pendingNoTipi as $i) $onNoTipi($i);
                    }

                } catch (\Throwable $e) {
                    DB::rollBack();
                    throw $e;
                }
            }, $produtoPk);

        $stats['duration_ms'] = (int)round((microtime(true) - $t0) * 1000);

        return $stats;
    }

    /**
     * Carrega a TIPI em memória e resolve "a melhor linha" para cada NCM.
     */
    private function loadTipiMap(
        string $table,
        string $colNcm,
        string $colId,
        ?string $colCst,
        ?string $colClass,
        ?string $colTipoReducao,
        ?string $colUpdatedAt,
        ?string $colCreatedAt,
        ?string $colDeletedAt,
        ?string $colEmpresaId,
        ?int $empresaId
    ): array {
        $q = DB::table($table)->select([$colNcm, $colId]);

        if ($colCst) $q->addSelect($colCst);
        if ($colClass) $q->addSelect($colClass);
        if ($colTipoReducao) $q->addSelect($colTipoReducao);
        if ($colUpdatedAt) $q->addSelect($colUpdatedAt);
        if ($colCreatedAt) $q->addSelect($colCreatedAt);

        if ($colDeletedAt) {
            $q->whereNull($colDeletedAt);
        }

        if ($empresaId !== null && $colEmpresaId) {
            $q->where($colEmpresaId, '=', $empresaId);
        }

        $map = [];
        foreach ($q->cursor() as $row) {
            $key = $this->normalizeNcm((string)($row->{$colNcm} ?? ''));
            if ($key === '') continue;

            $stamp = null;
            if ($colUpdatedAt && !empty($row->{$colUpdatedAt})) $stamp = $this->safeCarbon($row->{$colUpdatedAt});
            if ($stamp === null && $colCreatedAt && !empty($row->{$colCreatedAt})) $stamp = $this->safeCarbon($row->{$colCreatedAt});
            if ($stamp === null) $stamp = Carbon::createFromTimestamp(0);

            $idVal = (int)($row->{$colId} ?? 0);

            $cst = $colCst ? trim((string)($row->{$colCst} ?? '')) : 'N';
            if ($cst === '') $cst = 'N';

            $cls = $colClass ? trim((string)($row->{$colClass} ?? '')) : '';
            if (strlen($cls) > 8) $cls = substr($cls, 0, 8);

            $tipoRed = $colTipoReducao ? trim((string)($row->{$colTipoReducao} ?? '')) : '';
            $red = $this->parseReducaoPercent($tipoRed);

            $info = [
                'cst' => $cst,
                'class' => $cls,
                'reducao' => (float)$red,
                'stamp' => $stamp,
                'id' => $idVal,
            ];

            if (!isset($map[$key])) {
                $map[$key] = $info;
                continue;
            }

            $old = $map[$key];
            /** @var Carbon $oldStamp */
            $oldStamp = $old['stamp'];

            if ($stamp->gt($oldStamp) || ($stamp->equalTo($oldStamp) && $idVal > (int)$old['id'])) {
                $map[$key] = $info;
            }
        }

        return $map;
    }

    private function normalizeNcm(string $s): string
    {
        $u = trim($s);
        if ($u === '') return '';
        $digits = preg_replace('/\D+/', '', $u);
        return $digits ?? '';
    }

    private function parseReducaoPercent(string $s): float
    {
        $u = strtoupper(trim($s));
        if ($u === '') return 0.0;

        if (str_contains($u, 'TRIBUT') && str_contains($u, 'INTEGRAL')) return 0.0;
        if (str_contains($u, 'REDUÇÃO A ZERO') || str_contains($u, 'REDUCAO A ZERO')) return 0.0;

        $buf = '';
        $len = strlen($u);
        for ($i = 0; $i < $len; $i++) {
            $ch = $u[$i];
            if (($ch >= '0' && $ch <= '9') || $ch === ',' || $ch === '.') {
                $buf .= $ch;
            } elseif ($buf !== '') {
                break;
            }
        }

        $buf = trim($buf);
        if ($buf === '') return 0.0;
        $buf = str_replace(',', '.', $buf);

        return is_numeric($buf) ? (float)$buf : 0.0;
    }

    private function safeCarbon(mixed $v): ?Carbon
    {
        try {
            return Carbon::parse($v);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveTable(array $candidates, string $fallback): string
    {
        foreach ($candidates as $t) {
            if (Schema::hasTable($t)) return $t;
        }
        return $fallback;
    }

    private function resolveRequiredColumn(string $table, array $candidates): string
    {
        foreach ($candidates as $c) {
            if (Schema::hasColumn($table, $c)) return $c;
        }

        throw new \RuntimeException(
            "Coluna obrigatória não encontrada em '{$table}'. Tentativas: " . implode(', ', $candidates)
        );
    }

    private function resolveOptionalColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (Schema::hasColumn($table, $c)) return $c;
        }
        return null;
    }
}
