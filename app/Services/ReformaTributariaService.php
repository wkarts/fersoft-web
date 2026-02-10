<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Referencias\Anp;
use App\Models\ReformaTributaria\ClassTribIbsCbs;

class ReformaTributariaService
{
    /**
     * Regra oficial:
     * - Produção (config_notas.ambiente=1): aplica SOMENTE se tributacaos.regime = 1 (NORMAL)
     * - Homologação (config_notas.ambiente=2): sempre aplica (independe de regime: 0/1/2)
     *
     * Regime:
     * 0 = Simples Nacional
     * 1 = Normal
     * 2 = MEI
     */

    public function shouldApply(int $empresaId): bool
    {
        $ambiente = $this->getAmbienteEmpresa($empresaId); // 1/2 default 1
        $regime   = $this->getRegimeEmpresa($empresaId);   // 0/1/2 ou null

        // Homologação: sempre aplica
        if ((int)$ambiente === 2) {
            return true;
        }

        // Produção: apenas regime NORMAL (1)
        if ((int)$ambiente === 1) {
            return ((int)($regime ?? -1) === 1);
        }

        return false;
    }

    /*
    public function shouldApply(int $empresaId): bool
    {
        // Mantém a prioridade de flag explícita (se você quiser manter isso, ok)
        // Se quiser ignorar completamente flags, é só remover esse bloco.
        $explicit = $this->readRtEnableFlag($empresaId);
        if ($explicit !== null) {
            return $explicit;
        }

        // SOMENTE config_notas.ambiente
        $ambiente = $this->getAmbienteEmpresa($empresaId); // default 1

        // SOMENTE tributacaos.regime
        $regime = $this->getRegimeEmpresa($empresaId); // null se não achar (ou não existir)

        // Homologação: sempre aplica
        if ((int)$ambiente === 2) {
            return true;
        }

        // Produção: aplica apenas se regime == 1 (NORMAL)
        if ((int)$ambiente === 1) {
            return ((int)($regime ?? -1) === 1);
        }

        // fallback seguro
        return false;
    }
    */

    // ---------------------------------------------------------------------
    // Helpers (parse seguro)
    // ---------------------------------------------------------------------
    public function toInt($v, int $default = 0): int
    {
        if ($v === null) return $default;
        $s = trim((string)$v);
        if ($s === '') return $default;
        return (int)preg_replace('/\D+/', '', $s) ?: $default;
    }

    public function toFloat($v, float $default = 0.0): float
    {
        if ($v === null) return $default;

        $s = trim((string)$v);
        if ($s === '') return $default;

        $s = preg_replace('/\s+/', '', $s);

        if (preg_match('/^\d{1,3}(\.\d{3})+,\d+$/', $s)) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else if (preg_match('/^\d{1,3}(,\d{3})+\.\d+$/', $s)) {
            $s = str_replace(',', '', $s);
        } else if (substr_count($s, ',') === 1 && substr_count($s, '.') === 0) {
            $s = str_replace(',', '.', $s);
        }

        if (!is_numeric($s)) return $default;
        return (float)$s;
    }

    public function round2(float $v): float
    {
        return round($v, 2);
    }

    public function fmtCst3($v): string
    {
        $n = $this->toInt($v, 0);
        return str_pad((string)$n, 3, '0', STR_PAD_LEFT);
    }

    // class trib com 6 dígitos (conforme pedido)
    public function fmtClassTrib6($v): string
    {
        $n = $this->toInt($v, 0);
        return str_pad((string)$n, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Set tolerante: se campo não existir no case informado,
     * tenta lowercase (padrão novo do seu banco).
     * Para array, grava também a chave lowercase (pra bater com create()).
     */
    protected function setIfExists(&$modelOrArray, string $field, $value): void
    {
        // ARRAY: grava também em lowercase
        if (is_array($modelOrArray)) {

            foreach (array_keys($modelOrArray) as $k) {
                if (strcasecmp($k, $field) === 0) {
                    $modelOrArray[$k] = $value;
                    return;
                }
            }

            $modelOrArray[$field] = $value;

            $lower = strtolower($field);
            if ($lower !== $field) {
                $modelOrArray[$lower] = $value;
            }

            return;
        }

        if (!is_object($modelOrArray)) return;

        $targetField = $field;

        if (method_exists($modelOrArray, 'getTable')) {
            $table = $modelOrArray->getTable();

            if (!Schema::hasColumn($table, $targetField)) {
                $lower = strtolower($targetField);
                if (Schema::hasColumn($table, $lower)) {
                    $targetField = $lower;
                } else {
                    return;
                }
            }
        }

        try {
            $modelOrArray->$targetField = $value;
        } catch (\Throwable $e) {
            // ignora
        }
    }

    protected function readField($item, string $field)
    {
        if (is_array($item)) {
            if (array_key_exists($field, $item)) return $item[$field];
            $lc = strtolower($field);
            if (array_key_exists($lc, $item)) return $item[$lc];
            return null;
        }

        if (is_object($item)) {
            try {
                $v = $item->$field ?? $item->getAttribute($field);
                if ($v !== null) return $v;
            } catch (\Throwable $e) {}

            $lc = strtolower($field);
            try {
                $v = $item->$lc ?? $item->getAttribute($lc);
                if ($v !== null) return $v;
            } catch (\Throwable $e) {}

            return null;
        }

        return null;
    }

    protected function getNum($modelOrArray, array $candidates, float $default = 0.0): float
    {
        foreach ($candidates as $f) {
            $val = null;

            if (is_array($modelOrArray)) {
                if (array_key_exists($f, $modelOrArray)) $val = $modelOrArray[$f];
                else {
                    $lc = strtolower($f);
                    if (array_key_exists($lc, $modelOrArray)) $val = $modelOrArray[$lc];
                }
            }

            if (is_object($modelOrArray)) {
                try { $val = $modelOrArray->$f ?? ($modelOrArray->getAttribute($f) ?? null); } catch (\Throwable $e) {}
                if ($val === null) {
                    $lc = strtolower($f);
                    try { $val = $modelOrArray->$lc ?? ($modelOrArray->getAttribute($lc) ?? null); } catch (\Throwable $e) {}
                }
            }

            if ($val !== null && trim((string)$val) !== '') return $this->toFloat($val, $default);
        }

        return $default;
    }

    protected function resolveEmpresaWhereColumn(string $table): ?string
    {
        if (Schema::hasColumn($table, 'empresa_id')) return 'empresa_id';
        if (Schema::hasColumn($table, 'EMPRESA_ID')) return 'EMPRESA_ID';
        if (Schema::hasColumn($table, 'id')) return 'id';
        if (Schema::hasColumn($table, 'ID')) return 'ID';
        return null;
    }

    protected function readRtEnableFlag(int $empresaId): ?bool
    {
        $candidates = [
            ['table' => 'empresas', 'col' => 'reforma_tributaria'],
            ['table' => 'empresas', 'col' => 'calcula_reforma_tributaria'],
            ['table' => 'config_notas', 'col' => 'reforma_tributaria'],
            ['table' => 'tributacaos', 'col' => 'reforma_tributaria'],
            ['table' => 'tributacoes', 'col' => 'reforma_tributaria'],
        ];

        foreach ($candidates as $c) {
            if (!Schema::hasTable($c['table'])) {
                continue;
            }

            $col = null;
            if (Schema::hasColumn($c['table'], $c['col'])) {
                $col = $c['col'];
            } else if (Schema::hasColumn($c['table'], strtoupper($c['col']))) {
                $col = strtoupper($c['col']);
            }

            if ($col === null) {
                continue;
            }

            $whereCol = $this->resolveEmpresaWhereColumn($c['table']);
            if ($whereCol === null) {
                continue;
            }

            $val = DB::table($c['table'])->where($whereCol, $empresaId)->value($col);
            if ($val === null) {
                continue;
            }

            $str = strtoupper(trim((string)$val));
            if ($str === '') {
                continue;
            }

            return ($str === 'S' || $str === '1' || $str === 'SIM' || $val === true);
        }

        return null;
    }

    protected function rowVal(?object $row, array $cands)
    {
        if (!$row) return null;

        foreach ($cands as $c) {
            if (property_exists($row, $c)) {
                $v = $row->{$c};
                if ($v !== null && trim((string)$v) !== '') return $v;
            }

            $lc = strtolower($c);
            if (property_exists($row, $lc)) {
                $v = $row->{$lc};
                if ($v !== null && trim((string)$v) !== '') return $v;
            }

            $uc = strtoupper($c);
            if (property_exists($row, $uc)) {
                $v = $row->{$uc};
                if ($v !== null && trim((string)$v) !== '') return $v;
            }
        }

        return null;
    }

    // ---------------------------------------------------------------------
    // Ambientes / REGIME / Parametrização
    // ---------------------------------------------------------------------
    protected function getAmbienteEmpresa(int $empresaId): int
    {
        if (!Schema::hasTable('config_notas')) return 1;

        $col = Schema::hasColumn('config_notas', 'ambiente') ? 'ambiente'
            : (Schema::hasColumn('config_notas', 'AMBIENTE') ? 'AMBIENTE' : null);

        if ($col === null) return 1;

        $whereCol = $this->resolveEmpresaWhereColumn('config_notas');
        if ($whereCol === null) return 1;

        $val = DB::table('config_notas')->where($whereCol, $empresaId)->value($col);
        $amb = (int)$val;

        return ($amb === 2) ? 2 : 1; // só 1 ou 2
    }

    protected function getRegimeEmpresa(int $empresaId): ?int
    {
        if (!Schema::hasTable('tributacaos')) return null;

        $col = Schema::hasColumn('tributacaos', 'regime') ? 'regime'
            : (Schema::hasColumn('tributacaos', 'REGIME') ? 'REGIME' : null);

        if ($col === null) return null;

        $whereCol = $this->resolveEmpresaWhereColumn('tributacaos');
        if ($whereCol === null) return null;

        $val = DB::table('tributacaos')->where($whereCol, $empresaId)->value($col);
        if ($val === null) return null;

        $s = trim((string)$val);
        if ($s === '') return null;

        // pega só dígitos (caso venha "1", " 1 ", "REGIME=1", etc)
        $n = (int)preg_replace('/\D+/', '', $s);

        // regime permitido: 0/1/2
        if (!in_array($n, [0, 1, 2], true)) return null;

        return $n;
    }

    /*
    protected function getAmbienteEmpresa(int $empresaId): int
    {
        // config_notas.ambiente: 1 produção, 2 homologação
        if (!Schema::hasTable('config_notas')) return 1;
        if (!Schema::hasColumn('config_notas', 'ambiente')) {
            // tenta variações caso tenha sido padronizado
            if (!Schema::hasColumn('config_notas', 'AMBIENTE')) return 1;
            $col = 'AMBIENTE';
        } else {
            $col = 'ambiente';
        }

        $whereCol = $this->resolveEmpresaWhereColumn('config_notas');
        if ($whereCol === null) return 1;

        $val = DB::table('config_notas')->where($whereCol, $empresaId)->value($col);
        if ($val === null || trim((string)$val) === '') return 1;

        return (int)$val;
    }

    protected function getRegimeEmpresa(int $empresaId): ?int
    {
        if (!Schema::hasTable('tributacaos')) return null;

        $col = null;
        if (Schema::hasColumn('tributacaos', 'regime')) $col = 'regime';
        else if (Schema::hasColumn('tributacaos', 'REGIME')) $col = 'REGIME';

        if ($col === null) return null;

        $whereCol = $this->resolveEmpresaWhereColumn('tributacaos');
        if ($whereCol === null) return null;

        $val = DB::table('tributacaos')->where($whereCol, $empresaId)->value($col);
        if ($val === null || trim((string)$val) === '') return null;

        return (int)$val;
    }
    */

    /**
     * Em homologação: aplica se os campos RT estiverem preenchidos na tributacaos.
     */
    protected function hasRtFieldsFilled(int $empresaId): bool
    {
        if (!Schema::hasTable('tributacaos')) return false;

        $whereCol = $this->resolveEmpresaWhereColumn('tributacaos');
        if ($whereCol === null) return false;

        $cols = [
            'aliq_cbs',
            'aliq_ibs_uf',
            'aliq_ibs_mun',
            'cst_ibs_cbs',
            'class_trib_ibs_cbs',
        ];

        $select = [];
        foreach ($cols as $c) {
            if (Schema::hasColumn('tributacaos', $c)) $select[] = $c;
            else if (Schema::hasColumn('tributacaos', strtoupper($c))) $select[] = strtoupper($c);
        }

        if (!$select) return false;

        $row = DB::table('tributacaos')->where($whereCol, $empresaId)->select($select)->first();
        if (!$row) return false;

        $arr = (array)$row;

        // precisa estar preenchido de forma consistente
        $aliqCbs = $this->toFloat($arr['aliq_cbs'] ?? ($arr['ALIQ_CBS'] ?? null), 0.0);
        $aliqUf  = $this->toFloat($arr['aliq_ibs_uf'] ?? ($arr['ALIQ_IBS_UF'] ?? null), 0.0);
        $aliqMun = $this->toFloat($arr['aliq_ibs_mun'] ?? ($arr['ALIQ_IBS_MUN'] ?? null), 0.0);

        $cst = trim((string)($arr['cst_ibs_cbs'] ?? ($arr['CST_IBS_CBS'] ?? '')));
        $ct  = trim((string)($arr['class_trib_ibs_cbs'] ?? ($arr['CLASS_TRIB_IBS_CBS'] ?? '')));

        return ($aliqCbs > 0 || $aliqUf > 0 || $aliqMun > 0) && ($cst !== '' || $ct !== '');
    }

    // ---------------------------------------------------------------------
    // Defaults (buscaProduto), mas configuráveis por ENV/colunas.
    // ---------------------------------------------------------------------

    protected function aliquotaPadrao(int $empresaId, string $tipo): float
    {
        $tipo = strtoupper(trim($tipo));

        // defaults corretos conforme pedido
        $defaultsEnv = [
            'CBS'     => $this->toFloat(env('REFORMA_ALIQ_CBS', 0.9000), 0.9000),
            'IBS_UF'  => $this->toFloat(env('REFORMA_ALIQ_IBS_UF', 0.1000), 0.1000),
            'IBS_MUN' => $this->toFloat(env('REFORMA_ALIQ_IBS_MUN', 0.0500), 0.0500),
        ];

        $default = $defaultsEnv[$tipo] ?? 0.0;

        // Prioridade: tributacaos (conforme sua regra)
        $map = [
            'CBS' => [
                ['table' => 'tributacaos', 'col' => 'aliq_cbs'],
                ['table' => 'tributacaos', 'col' => 'ALIQ_CBS'],
            ],
            'IBS_UF' => [
                ['table' => 'tributacaos', 'col' => 'aliq_ibs_uf'],
                ['table' => 'tributacaos', 'col' => 'ALIQ_IBS_UF'],
            ],
            'IBS_MUN' => [
                ['table' => 'tributacaos', 'col' => 'aliq_ibs_mun'],
                ['table' => 'tributacaos', 'col' => 'ALIQ_IBS_MUN'],
            ],
        ];

        foreach (($map[$tipo] ?? []) as $c) {
            if (!Schema::hasTable($c['table'])) continue;
            if (!Schema::hasColumn($c['table'], $c['col'])) continue;

            $whereCol = $this->resolveEmpresaWhereColumn($c['table']);
            if ($whereCol === null) continue;

            $val = DB::table($c['table'])
                ->where($whereCol, $empresaId)
                ->value($c['col']);

            if ($val !== null && trim((string)$val) !== '') {
                return $this->toFloat($val, $default);
            }
        }

        return $default;
    }

    // ---------------------------------------------------------------------
    // 2) Preencher defaults do item (somente tabelas)
    // ---------------------------------------------------------------------

    /**
     * Puxa dados tributários do produto e preenche campos do item.
     * OBS: aqui não tenta VIEW; usa somente tabela produtos.
     */
    public function fillItemFromProdutoAliquota(int $empresaId, int $produtoId, $item): void
    {
        $row = null;

        if (Schema::hasTable('produtos')) {
            $q = DB::table('produtos')->where('id', $produtoId);

            if (Schema::hasColumn('produtos', 'empresa_id')) {
                $q->where('empresa_id', $empresaId);
            }

            $row = $q->first();
        }

        if (!$row) return;

        $cst       = $this->rowVal($row, ['CST_IBS_CBS','cst_ibs_cbs']);
        $classTrib = $this->rowVal($row, ['CLASS_TRIB_IBS_CBS','class_trib_ibs_cbs']);

        // reduções (aceita também perc_red_ibs/perc_red_cbs)
        $redIbs = $this->rowVal($row, ['perc_red_ibs','PERC_RED_IBS','REDUCAO_IBS','reducao_ibs']);
        $redCbs = $this->rowVal($row, ['perc_red_cbs','PERC_RED_CBS','REDUCAO_CBS','reducao_cbs']);

        $flagIs = $this->rowVal($row, ['FLAG_IS','flag_is']);
        $aliqIs = $this->rowVal($row, ['ALIQ_IS','aliq_is']);

        $anp = $this->rowVal($row, ['codigo_anp','CODIGO_ANP','PROD_CPRODANP','cProdAnp','ANP']);

        // mantém seus nomes atuais (setIfExists resolve o case)
        $this->setIfExists($item, 'IS_ALIQ', $this->toFloat($aliqIs, 0.0));
        $this->setIfExists($item, 'CST_IBS_CBS', $this->fmtCst3($cst ?? ''));
        $this->setIfExists($item, 'CLASS_TRIB_IBS_CBS', $this->fmtClassTrib6($classTrib ?? ''));

        $this->setIfExists($item, 'PERC_RED_ALIQ_UF',      $this->toFloat($redIbs, 0.0));
        $this->setIfExists($item, 'PERC_RED_ALIQ_IBS_MUN', $this->toFloat($redIbs, 0.0));
        $this->setIfExists($item, 'PERC_RED_ALIQ_CBS',     $this->toFloat($redCbs, 0.0));

        $this->setIfExists($item, 'FLAG_IS', strtoupper(trim((string)($flagIs ?? 'N'))));

        $anp = trim((string)($anp ?? ''));
        $this->setIfExists($item, 'ANP', $anp);

        if ($anp !== '') {
            $this->setIfExists($item, 'FLAG_COMBUSTIVEL', 'S');
        }

        // >>> DEFAULTS <<<
        $this->setIfExists($item, 'ALIQ_CBS',     $this->aliquotaPadrao($empresaId, 'CBS'));     // 0.9000
        $this->setIfExists($item, 'ALIQ_IBS_UF',  $this->aliquotaPadrao($empresaId, 'IBS_UF'));  // 0.1000
        $this->setIfExists($item, 'ALIQ_IBS_MUN', $this->aliquotaPadrao($empresaId, 'IBS_MUN')); // 0.0500

        // Defaults “zerados” (valores/base/resultados) — NÃO zera alíquotas!
        $zeroFloat = [
            'IS_BC','IS_ALIQ_ESPEC','IS_QTD_TRIB','IS_VALOR',
            'BC_IBS_CBS','VALOR_IBS','VALOR_IBS_UF','PERC_DIF_IBS_UF','VALOR_DIF_IBS_UF','VALOR_DIF_IBS_UF_DEVTRIB','ALIQ_EFET_IBS_UF',
            'VALOR_IBS_MUN','PERC_DIF_IBS_MUN','VALOR_DIF_IBS_MUN','VALOR_DIF_IBS_MUN_TRIB','ALIQ_EFET_IBS_MUN',
            'VALOR_CBS','PERC_DIF_CBS','VALOR_DIF_CBS','VALOR_DIF_CBS_DEVTRIB','ALIQ_EFET_CBS',
            'TRIB_REG_ALIQ_EFET_IBS_UF','TRIB_REG_VALOR_IBS_UF',
            'TRIB_REG_ALIQ_EFET_IBS_MUN','TRIB_REG_VALOR_IBS_MUN',
            'TRIB_REG_ALIQ_EFET_CBS','TRIB_REG_VALOR_CBS',
            'PERC_CRED_PRES_IBS','VALOR_CRED_PRES_IBS','VALOR_CRED_PRES_COND_SUS_IBS',
            'PERC_CRED_PRES_CBS','VALOR_CRED_PRES_CBS','VALOR_CRED_PRES_COND_SUS_CBS',
            'QBCMONO_IBS_CBS','VALOR_IBS_MONO','VALOR_CBS_MONO',
            'QBCMONORETEN_IBS_CBS','ADREM_IBS_RETEN','ADREM_CBS_RETEN','VALOR_IBS_RETEN','VALOR_CBS_RETEN',
            'QBCMONORET_IBS_CBS','ADREM_IBS_RET','ADREM_CBS_RET','VALOR_IBS_RET','VALOR_CBS_RET',
            'ADREM_IBS','ADREM_CBS'
        ];
        foreach ($zeroFloat as $f) $this->setIfExists($item, $f, 0.0);

        $zeroInt = ['CRED_PRES_COD_IBS','CRED_PRES_COD_CBS'];
        foreach ($zeroInt as $f) $this->setIfExists($item, $f, 0);

        if ($anp !== '') {
            $adrem = $this->aliquotaAnp($anp);
            $this->setIfExists($item, 'ADREM_IBS', $adrem);
            $this->setIfExists($item, 'ADREM_CBS', $adrem);
        }
    }

    // ---------------------------------------------------------------------
    // 3) Cálculo do item
    // ---------------------------------------------------------------------

    public function calcularItem($item, int $empresaId, ?string $cfopDescricao = null): void
    {
        $vProdTotal = $this->getNum($item, ['NFSI_VLRTOTAL','vlr_total','valor_total','valor_total_item'], 0.0);

        $qtd = $this->getNum($item, ['NFSI_QUANTIDADE','quantidade','qtd'], 0.0);

        $vUnit = $this->getNum($item, ['valor_unitario','valor_unit','vlr_unitario'], 0.0);
        if ($vUnit <= 0) {
            $vUnit = $this->getNum($item, ['valor'], 0.0);
        }

        $vProd = $vProdTotal;
        if ($vProd <= 0 && $qtd > 0 && $vUnit > 0) {
            $vProd = $this->round2($qtd * $vUnit);
        }

        if ($vProd <= 0) {
            $vProd = $this->getNum($item, ['NFSI_VLRTOTAL','vlr_total','valor_total','valor'], 0.0);
        }

        $vSeg    = $this->getNum($item, ['NFSI_SEGURO','seguro']);
        $vFrete  = $this->getNum($item, ['NFSI_FRETE','frete']);
        $vOutro  = $this->getNum($item, ['NFSI_DESPESAS','despesas','outros']);
        $vDesc   = $this->getNum($item, ['NFSI_VLRDESCONTO','vlr_desconto','desconto']);
        $vPis    = $this->getNum($item, ['NFSI_PIS_VLRIMPOSTO','pis_valor','pis_vlr_imposto']);
        $vCof    = $this->getNum($item, ['NFSI_COFINS_VLRIMPOSTO','cofins_valor','cofins_vlr_imposto']);
        $vIcms   = $this->getNum($item, ['NFSI_VLRICMS','icms_valor','valor_icms']);
        $vUFDest = $this->getNum($item, ['NFSI_VALOR_ICMS_DESTINO','icms_ufdest_valor']);
        $vFcp    = $this->getNum($item, ['NSFI_VALOR_ICMS_FCP','valor_fcp']);
        $vFcpSt  = $this->getNum($item, ['NSFI_VALOR_FCP_ST','valor_fcp_st']);
        $vMono   = $this->getNum($item, ['NFSI_VICMSMONO','icms_mono_valor']);
        $vIS     = $this->getNum($item, ['IS_VALOR','is_valor']);

        $bc = $this->round2(($vProd + $vSeg + $vFrete + $vOutro - $vDesc - $vPis - $vCof - $vIcms - $vUFDest - $vFcp - $vFcpSt - $vMono + $vIS));
        $this->setIfExists($item, 'BC_IBS_CBS', $bc);

        $desc = strtoupper((string)($cfopDescricao ?? ''));
        if ($desc !== '' && (str_contains($desc, 'REMESSA') || str_contains($desc, 'RETORNO'))) {
            $this->zeroCamposReforma($item);
            $this->calcAliqEfetivasSeCst200($item);
            $this->calcValorIbsTotal($item);
            return;
        }

        $flagIs = strtoupper(trim((string)($this->readField($item, 'FLAG_IS') ?? '')));
        if ($flagIs === 'S') {
            $this->setIfExists($item, 'IS_BC', $vProd);
            $aliqIs = $this->getNum($item, ['IS_ALIQ','is_aliq'], 0);
            $this->setIfExists($item, 'IS_VALOR', $this->round2(($vProd * $aliqIs) / 100));
        } else {
            $this->setIfExists($item, 'IS_BC', 0.0);
            $this->setIfExists($item, 'IS_VALOR', 0.0);
            $this->setIfExists($item, 'IS_ALIQ', 0.0);
        }

        $cst = trim((string)($this->readField($item, 'CST_IBS_CBS') ?? ''));
        $cst = $this->fmtCst3($cst);

        if (in_array($cst, ['000','200','220','510'], true)) {
            if ($cst === '000') $this->calcIntegral($item);
            if ($cst === '200') $this->calcReduzido($item);
        }

        if ($cst === '620') $this->calcMono($item);

        if (in_array($cst, ['400','410'], true)) {
            $this->zeroCamposReforma($item);
        }

        $this->calcValorIbsTotal($item);
    }

    protected function zeroCamposReforma($item): void
    {
        $zeroFloat = [
            'BC_IBS_CBS','VALOR_IBS','VALOR_IBS_UF','ALIQ_EFET_IBS_UF','PERC_DIF_IBS_UF','VALOR_DIF_IBS_UF','VALOR_DIF_IBS_UF_DEVTRIB',
            'VALOR_IBS_MUN','PERC_DIF_IBS_MUN','VALOR_DIF_IBS_MUN','VALOR_DIF_IBS_MUN_TRIB','ALIQ_EFET_IBS_MUN',
            'VALOR_CBS','PERC_DIF_CBS','VALOR_DIF_CBS','VALOR_DIF_CBS_DEVTRIB','ALIQ_EFET_CBS',
            'TRIB_REG_ALIQ_EFET_IBS_UF','TRIB_REG_VALOR_IBS_UF',
            'TRIB_REG_ALIQ_EFET_IBS_MUN','TRIB_REG_VALOR_IBS_MUN',
            'TRIB_REG_ALIQ_EFET_CBS','TRIB_REG_VALOR_CBS',
            'PERC_CRED_PRES_IBS','VALOR_CRED_PRES_IBS','VALOR_CRED_PRES_COND_SUS_IBS',
            'PERC_CRED_PRES_CBS','VALOR_CRED_PRES_CBS','VALOR_CRED_PRES_COND_SUS_CBS',
        ];
        foreach ($zeroFloat as $f) $this->setIfExists($item, $f, 0.0);

        $zeroInt = ['CRED_PRES_COD_IBS','CRED_PRES_COD_CBS'];
        foreach ($zeroInt as $f) $this->setIfExists($item, $f, 0);
    }

    protected function calcAliqEfetivasSeCst200($item): void
    {
        $cst = $this->fmtCst3($this->readField($item, 'CST_IBS_CBS') ?? '');
        if ($cst !== '200') return;

        $aUf  = $this->getNum($item, ['ALIQ_IBS_UF','aliq_ibs_uf'], 0);
        $aMun = $this->getNum($item, ['ALIQ_IBS_MUN','aliq_ibs_mun'], 0);
        $aCbs = $this->getNum($item, ['ALIQ_CBS','aliq_cbs'], 0);

        $rUf  = $this->getNum($item, ['PERC_RED_ALIQ_UF','perc_red_aliq_uf'], 0);
        $rMun = $this->getNum($item, ['PERC_RED_ALIQ_IBS_MUN','perc_red_aliq_ibs_mun'], 0);
        $rCbs = $this->getNum($item, ['PERC_RED_ALIQ_CBS','perc_red_aliq_cbs'], 0);

        if ($rUf > 0)  $this->setIfExists($item, 'ALIQ_EFET_IBS_UF',  $aUf  * (1 - ($rUf/100)));
        if ($rMun > 0) $this->setIfExists($item, 'ALIQ_EFET_IBS_MUN', $aMun * (1 - ($rMun/100)));
        if ($rCbs > 0) $this->setIfExists($item, 'ALIQ_EFET_CBS',     $aCbs * (1 - ($rCbs/100)));
    }

    protected function calcIntegral($item): void
    {
        $bc = $this->getNum($item, ['BC_IBS_CBS','bc_ibs_cbs'], 0);

        $aUf  = $this->getNum($item, ['ALIQ_IBS_UF','aliq_ibs_uf'], 0);
        $aMun = $this->getNum($item, ['ALIQ_IBS_MUN','aliq_ibs_mun'], 0);
        $aCbs = $this->getNum($item, ['ALIQ_CBS','aliq_cbs'], 0);

        $this->setIfExists($item, 'ALIQ_EFET_IBS_UF', $aUf);
        $this->setIfExists($item, 'VALOR_IBS_UF', $this->round2(($bc * $aUf) / 100));

        $this->setIfExists($item, 'ALIQ_EFET_IBS_MUN', $aMun);
        $this->setIfExists($item, 'VALOR_IBS_MUN', $this->round2(($bc * $aMun) / 100));

        $this->setIfExists($item, 'ALIQ_EFET_CBS', $aCbs);
        $this->setIfExists($item, 'VALOR_CBS', $this->round2(($bc * $aCbs) / 100));
    }

    protected function calcReduzido($item): void
    {
        $bc = $this->getNum($item, ['BC_IBS_CBS','bc_ibs_cbs'], 0);

        $aUf  = $this->getNum($item, ['ALIQ_IBS_UF','aliq_ibs_uf'], 0);
        $aMun = $this->getNum($item, ['ALIQ_IBS_MUN','aliq_ibs_mun'], 0);
        $aCbs = $this->getNum($item, ['ALIQ_CBS','aliq_cbs'], 0);

        $rUf  = $this->getNum($item, ['PERC_RED_ALIQ_UF','perc_red_aliq_uf'], 0);
        $rMun = $this->getNum($item, ['PERC_RED_ALIQ_IBS_MUN','perc_red_aliq_ibs_mun'], 0);
        $rCbs = $this->getNum($item, ['PERC_RED_ALIQ_CBS','perc_red_aliq_cbs'], 0);

        $efUf = ($rUf > 0) ? $aUf * (1 - ($rUf/100)) : $aUf;
        $this->setIfExists($item, 'ALIQ_EFET_IBS_UF', $efUf);
        $this->setIfExists($item, 'VALOR_IBS_UF', $this->round2(($bc * $efUf) / 100));

        $efMun = ($rMun > 0) ? $aMun * (1 - ($rMun/100)) : $aMun;
        $this->setIfExists($item, 'ALIQ_EFET_IBS_MUN', $efMun);
        $this->setIfExists($item, 'VALOR_IBS_MUN', $this->round2(($bc * $efMun) / 100));

        $efCbs = ($rCbs > 0) ? $aCbs * (1 - ($rCbs/100)) : $aCbs;
        $this->setIfExists($item, 'ALIQ_EFET_CBS', $efCbs);
        $this->setIfExists($item, 'VALOR_CBS', $this->round2(($bc * $efCbs) / 100));
    }

    protected function calcMono($item): void
    {
        $this->setIfExists($item, 'BC_IBS_CBS', 0.0);

        $this->calcIntegral($item);

        $flagComb = strtoupper(trim((string)($this->readField($item, 'FLAG_COMBUSTIVEL') ?? '')));
        $anp = trim((string)($this->readField($item, 'ANP') ?? ''));

        if ($flagComb !== 'S' || $anp === '') return;
        if (strtoupper((string)$this->consultaAnp($anp, 'MONOFASICO')) !== 'S') return;

        $classTrib = trim((string)($this->readField($item, 'CLASS_TRIB_IBS_CBS') ?? ''));
        $indMono = $this->buscarIndMonoClassTrib($classTrib);
        if (strtoupper($indMono) === 'N') {
            $this->setIfExists($item, 'ADREM_IBS', 0.0);
            $this->setIfExists($item, 'ADREM_CBS', 0.0);
            $this->setIfExists($item, 'VALOR_IBS_MONO', 0.0);
            $this->setIfExists($item, 'VALOR_CBS_MONO', 0.0);
            return;
        }

        $qtd = $this->getNum($item, ['NFSI_QUANTIDADE','quantidade'], 0);
        $adrem = $this->aliquotaAnp($anp);

        if ($classTrib === '620001') {
            $this->setIfExists($item, 'QBCMONO_IBS_CBS', $qtd);
            $this->setIfExists($item, 'VALOR_IBS_MONO', $this->round2($qtd * $this->getNum($item, ['ADREM_IBS'], $adrem)));
            $this->setIfExists($item, 'VALOR_CBS_MONO', $this->round2($qtd * $this->getNum($item, ['ADREM_CBS'], $adrem)));
        }

        if ($classTrib === '620002') {
            $this->setIfExists($item, 'QBCMONO_IBS_CBS', $qtd);
            $this->setIfExists($item, 'QBCMONORETEN_IBS_CBS', $qtd);
            $this->setIfExists($item, 'ADREM_IBS_RETEN', $adrem);
            $this->setIfExists($item, 'ADREM_CBS_RETEN', $adrem);
            $this->setIfExists($item, 'VALOR_IBS_MONO', $this->round2($qtd * $adrem));
            $this->setIfExists($item, 'VALOR_CBS_MONO', $this->round2($qtd * $adrem));
        }

        if ($classTrib === '620006') {
            $this->setIfExists($item, 'QBCMONO_IBS_CBS', $qtd);
            $this->setIfExists($item, 'QBCMONORET_IBS_CBS', $qtd);
            $this->setIfExists($item, 'ADREM_IBS_RET', $adrem);
            $this->setIfExists($item, 'ADREM_CBS_RET', $adrem);
            $this->setIfExists($item, 'VALOR_IBS_RET', $this->round2($qtd * $adrem));
            $this->setIfExists($item, 'VALOR_CBS_RET', $this->round2($qtd * $adrem));
        }
    }

    protected function calcValorIbsTotal($item): void
    {
        $uf  = $this->getNum($item, ['VALOR_IBS_UF','valor_ibs_uf'], 0);
        $mun = $this->getNum($item, ['VALOR_IBS_MUN','valor_ibs_mun'], 0);
        $this->setIfExists($item, 'VALOR_IBS', $this->round2($uf + $mun));
    }

    // ---------------------------------------------------------------------
    // 4) Totais (cabeçalho) por venda
    // ---------------------------------------------------------------------

    public function calcularTotaisVenda(int $empresaId, int $vendaId, string $itensTable = 'item_vendas'): array
    {
        if (!Schema::hasTable($itensTable)) return [];

        $sumCols = [
            'BC_IBS_CBS'               => 'TOTAL_BC_IBS_CBS',
            'VALOR_DIF_IBS_UF'         => 'TOTAL_IBS_UF_DIF',
            'VALOR_DIF_IBS_UF_DEVTRIB' => 'TOTAL_IBS_UF_DEV_TRIB',
            'VALOR_IBS_UF'             => 'TOTAL_IBS_UF',
            'VALOR_DIF_IBS_MUN'        => 'TOTAL_IBS_MUN_DIF',
            'VALOR_DIF_IBS_MUN_TRIB'   => 'TOTAL_IBS_MUN_DEV_TRIB',
            'VALOR_IBS_MUN'            => 'TOTAL_IBS_MUN',
            'VALOR_DIF_CBS'            => 'TOTAL_CBS_DIF',
            'VALOR_DIF_CBS_DEVTRIB'    => 'TOTAL_CBS_DEV_TRIB',
            'VALOR_CBS'                => 'TOTAL_CBS',
            'VALOR_CRED_PRES_IBS'      => 'TOTAL_IBS_CRED_PRES',
            'VALOR_CRED_PRES_COND_SUS_IBS' => 'TOTAL_IBS_CRED_PRES_COND_SUS',
            'VALOR_CRED_PRES_CBS'      => 'TOTAL_CBS_CRED_PRES',
            'VALOR_CRED_PRES_COND_SUS_CBS' => 'TOTAL_CBS_CRED_PRES_COND_SUS',
            'IS_VALOR'                 => 'TOTAL_IS',
            'VALOR_IBS_MONO'           => 'TOTAL_IBS_MONO',
            'VALOR_CBS_MONO'           => 'TOTAL_CBS_MONO',
            'VALOR_IBS_RETEN'          => 'TOTAL_IBS_MONO_RETEN',
            'VALOR_CBS_RETEN'          => 'TOTAL_CBS_MONO_RETEN',
            'VALOR_IBS_RET'            => 'TOTAL_IBS_MONO_RET',
            'VALOR_CBS_RET'            => 'TOTAL_CBS_MONO_RET',
        ];

        $selects = [];
        foreach ($sumCols as $col => $alias) {

            $realCol = null;

            if (Schema::hasColumn($itensTable, $col)) {
                $realCol = $col;
            } else {
                $lc = strtolower($col);
                if (Schema::hasColumn($itensTable, $lc)) {
                    $realCol = $lc;
                }
            }

            if ($realCol) {
                $selects[] = "COALESCE(SUM($realCol),0) as $alias";
            }
        }

        if (!$selects) return [];

        $row = DB::table($itensTable)
            ->where('venda_id', $vendaId)
            ->selectRaw(implode(",\n", $selects))
            ->first();

        if (!$row) return [];

        $t = (array)$row;

        $totalIbs = (float)($t['TOTAL_IBS_UF'] ?? 0) + (float)($t['TOTAL_IBS_MUN'] ?? 0);
        $totalIbsCbs = $totalIbs + (float)($t['TOTAL_CBS'] ?? 0);
        $totalNfIbcCbsIs = (float)($t['TOTAL_BC_IBS_CBS'] ?? 0) + (float)($t['TOTAL_IS'] ?? 0);

        $t['TOTAL_IBS'] = $this->round2($totalIbs);
        $t['TOTAL_IBS_CBS'] = $this->round2($totalIbsCbs);
        $t['TOTAL_NF_IBC_CBS_IS'] = $this->round2($totalNfIbcCbsIs);

        return $t;
    }

    public function applyTotaisToVenda($vendaModel, array $totais): void
    {
        if (!$vendaModel || !$totais) return;

        foreach ($totais as $k => $v) {
            $this->setIfExists($vendaModel, $k, $v);
        }

        if (isset($totais['TOTAL_IBS'])) $this->setIfExists($vendaModel, 'TOTAL_IBS', $totais['TOTAL_IBS']);
        if (isset($totais['TOTAL_IBS_CBS'])) $this->setIfExists($vendaModel, 'TOTAL_IBS_CBS', $totais['TOTAL_IBS_CBS']);

        try { $vendaModel->save(); } catch (\Throwable $e) {}
    }

    // ---------------------------------------------------------------------
    // 4B) Totais (cabeçalho) por compra
    // ---------------------------------------------------------------------

    public function calcularTotaisCompra(int $empresaId, int $compraId, string $itensTable = 'item_compras'): array
    {
        if (!Schema::hasTable($itensTable)) return [];

        $sumCols = [
            'BC_IBS_CBS'               => 'TOTAL_BC_IBS_CBS',
            'VALOR_DIF_IBS_UF'         => 'TOTAL_IBS_UF_DIF',
            'VALOR_DIF_IBS_UF_DEVTRIB' => 'TOTAL_IBS_UF_DEV_TRIB',
            'VALOR_IBS_UF'             => 'TOTAL_IBS_UF',
            'VALOR_DIF_IBS_MUN'        => 'TOTAL_IBS_MUN_DIF',
            'VALOR_DIF_IBS_MUN_TRIB'   => 'TOTAL_IBS_MUN_DEV_TRIB',
            'VALOR_IBS_MUN'            => 'TOTAL_IBS_MUN',
            'VALOR_DIF_CBS'            => 'TOTAL_CBS_DIF',
            'VALOR_DIF_CBS_DEVTRIB'    => 'TOTAL_CBS_DEV_TRIB',
            'VALOR_CBS'                => 'TOTAL_CBS',
            'IS_VALOR'                 => 'TOTAL_IS',
            'VALOR_IBS_MONO'           => 'TOTAL_IBS_MONO',
            'VALOR_CBS_MONO'           => 'TOTAL_CBS_MONO',
            'VALOR_IBS_RETEN'          => 'TOTAL_IBS_MONO_RETEN',
            'VALOR_CBS_RETEN'          => 'TOTAL_CBS_MONO_RETEN',
            'VALOR_IBS_RET'            => 'TOTAL_IBS_MONO_RET',
            'VALOR_CBS_RET'            => 'TOTAL_CBS_MONO_RET',
        ];

        $selects = [];
        foreach ($sumCols as $col => $alias) {

            $realCol = null;

            if (Schema::hasColumn($itensTable, $col)) {
                $realCol = $col;
            } else {
                $lc = strtolower($col);
                if (Schema::hasColumn($itensTable, $lc)) {
                    $realCol = $lc;
                }
            }

            if ($realCol) {
                $selects[] = "COALESCE(SUM($realCol),0) as $alias";
            }
        }

        if (!$selects) return [];

        $row = DB::table($itensTable)
            ->where('compra_id', $compraId)
            ->selectRaw(implode(",\n", $selects))
            ->first();

        if (!$row) return [];

        $t = (array)$row;

        $totalIbs = (float)($t['TOTAL_IBS_UF'] ?? 0) + (float)($t['TOTAL_IBS_MUN'] ?? 0);
        $totalIbsCbs = $totalIbs + (float)($t['TOTAL_CBS'] ?? 0);

        $t['TOTAL_IBS'] = $this->round2($totalIbs);
        $t['TOTAL_IBS_CBS'] = $this->round2($totalIbsCbs);

        return $t;
    }

    public function applyTotaisToCompra($compraModel, array $totais): void
    {
        if (!$compraModel || !$totais) return;

        foreach ($totais as $k => $v) {
            $this->setIfExists($compraModel, $k, $v);
        }

        if (isset($totais['TOTAL_IBS'])) $this->setIfExists($compraModel, 'TOTAL_IBS', $totais['TOTAL_IBS']);
        if (isset($totais['TOTAL_IBS_CBS'])) $this->setIfExists($compraModel, 'TOTAL_IBS_CBS', $totais['TOTAL_IBS_CBS']);

        try { $compraModel->save(); } catch (\Throwable $e) {}
    }

    // ---------------------------------------------------------------------
    // ANP / CLASS TRIB
    // ---------------------------------------------------------------------

    protected function aliquotaAnp(string $cProdAnp): float
    {
        $codigo = $this->toInt($cProdAnp, 0);
        if ($codigo <= 0) return 0.0;

        static $cache = [];
        if (array_key_exists($codigo, $cache)) return $cache[$codigo];

        if (!Schema::hasTable('anp') && !Schema::hasTable('ANP')) return $cache[$codigo] = 0.0;

        // tenta tabela/model do jeito que você já usa
        $val = Anp::query()
            ->where('CODIGO', $codigo)
            ->value('ADREMICMS');

        $cache[$codigo] = $this->toFloat($val, 0.0);
        return $cache[$codigo];
    }

    protected function consultaAnp(string $cProdAnp, string $campo): string
    {
        $codigo = $this->toInt($cProdAnp, 0);
        if ($codigo <= 0) return '';

        $campo = strtoupper(trim((string)$campo));
        if ($campo === '') return '';

        if (!Schema::hasTable('ANP') && !Schema::hasTable('anp')) return '';

        $table = Schema::hasTable('ANP') ? 'ANP' : 'anp';

        $col = $campo;
        if (!Schema::hasColumn($table, $col)) {
            $col2 = strtolower($campo);
            if (!Schema::hasColumn($table, $col2)) return '';
            $col = $col2;
        }

        static $cache = [];
        $key = $codigo . '|' . $table . '|' . $col;
        if (array_key_exists($key, $cache)) return $cache[$key];

        $val = DB::table($table)->where('CODIGO', $codigo)->value($col);
        if ($val === null) $val = '';

        $cache[$key] = trim((string)$val);
        return $cache[$key];
    }

    protected function buscarIndMonoClassTrib(string $classTrib): string
    {
        $classTrib = trim((string)$classTrib);
        if ($classTrib === '') return 'S';

        static $cache = [];
        if (array_key_exists($classTrib, $cache)) return $cache[$classTrib];

        if (!Schema::hasTable('CLASS_TRIB_IBS_CBS') && !Schema::hasTable('class_trib_ibs_cbs')) {
            return $cache[$classTrib] = 'S';
        }

        $today = now()->toDateString();

        $q = ClassTribIbsCbs::query()->where('CCLASSTRIB', $classTrib);

        $table = 'CLASS_TRIB_IBS_CBS';
        if (!Schema::hasTable($table) && Schema::hasTable('class_trib_ibs_cbs')) $table = 'class_trib_ibs_cbs';

        if (Schema::hasColumn($table, 'DINIVIG') || Schema::hasColumn($table, 'dinivig')) {
            $q->where(function ($qq) use ($today) {
                $qq->whereNull('DINIVIG')->orWhere('DINIVIG', '<=', $today)
                    ->orWhereNull('dinivig')->orWhere('dinivig', '<=', $today);
            });
        }

        if (Schema::hasColumn($table, 'DFIMVIG') || Schema::hasColumn($table, 'dfimvig')) {
            $q->where(function ($qq) use ($today) {
                $qq->whereNull('DFIMVIG')->orWhere('DFIMVIG', '>=', $today)
                    ->orWhereNull('dfimvig')->orWhere('dfimvig', '>=', $today);
            });
        }

        if (Schema::hasColumn($table, 'DINIVIG') || Schema::hasColumn($table, 'dinivig')) {
            $q->orderByRaw("COALESCE(DINIVIG, dinivig, '1900-01-01') DESC");
        }

        if (Schema::hasColumn($table, 'DATAATUALIZACAO') || Schema::hasColumn($table, 'dataatualizacao')) {
            $q->orderByDesc(Schema::hasColumn($table, 'DATAATUALIZACAO') ? 'DATAATUALIZACAO' : 'dataatualizacao');
        }

        $row = $q->first();

        $ind = $row ? (string)($row->INDMONO ?? '') : '';
        $ind = strtoupper(trim($ind));
        if ($ind === '') $ind = 'S';

        return $cache[$classTrib] = $ind;
    }
}
