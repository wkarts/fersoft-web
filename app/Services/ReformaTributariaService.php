<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use App\Models\Referencias\Anp;
use App\Models\ReformaTributaria\ClassTribIbsCbs;

class ReformaTributariaService
{
    public const RT_ALIQ_CBS_FIXA = 0.9000;
    public const RT_ALIQ_IBS_UF_FIXA = 0.1000;
    public const RT_ALIQ_IBS_MUN_FIXA = 0.0000;

    /**
     * Contexto (sem persistir nada do usuário).
     * Usado para preencher automaticamente empresa/usuário/filial quando não vierem na requisição.
     */
    protected ?int $ctxEmpresaId = null;
    protected ?int $ctxUserId = null;
    protected ?int $ctxFilialId = null;

    protected function ctxEmpresaId(?int $empresaId = null): int
    {
        $empresaId = (int)($empresaId ?? 0);
        if ($empresaId > 0) {
            $this->ctxEmpresaId = $empresaId;
            return $empresaId;
        }

        // 1) request
        try {
            $req = request();
            $v = $req ? ($req->get('empresa_id') ?? $req->get('empresaId') ?? $req->get('empresa')) : null;
            if (is_numeric($v) && (int)$v > 0) {
                $this->ctxEmpresaId = (int)$v;
                return $this->ctxEmpresaId;
            }
        } catch (\Throwable $e) {}

        // 2) sessão padrão do sistema
        try {
            $ul = session('user_logged');
            if (is_array($ul)) {
                $v = $ul['empresa_id'] ?? $ul['empresaId'] ?? $ul['empresa'] ?? null;
                if (is_numeric($v) && (int)$v > 0) {
                    $this->ctxEmpresaId = (int)$v;
                    return $this->ctxEmpresaId;
                }
            }
        } catch (\Throwable $e) {}

        // 3) Auth::user()
        try {
            $u = Auth::user();
            if ($u) {
                $v = $u->empresa_id ?? $u->empresaId ?? null;
                if (is_numeric($v) && (int)$v > 0) {
                    $this->ctxEmpresaId = (int)$v;
                    return $this->ctxEmpresaId;
                }
            }
        } catch (\Throwable $e) {}

        // 4) sessão direta (fallback)
        try {
            $v = session('empresa_id');
            if (is_numeric($v) && (int)$v > 0) {
                $this->ctxEmpresaId = (int)$v;
                return $this->ctxEmpresaId;
            }
        } catch (\Throwable $e) {}

        return 0;
    }

    protected function ctxUserId(?int $userId = null): int
    {
        $userId = (int)($userId ?? 0);
        if ($userId > 0) {
            $this->ctxUserId = $userId;
            return $userId;
        }

        try {
            $req = request();
            $v = $req ? ($req->get('user_id') ?? $req->get('usuario_id') ?? $req->get('id_usuario') ?? $req->get('idUser')) : null;
            if (is_numeric($v) && (int)$v > 0) {
                $this->ctxUserId = (int)$v;
                return $this->ctxUserId;
            }
        } catch (\Throwable $e) {}

        try {
            $ul = session('user_logged');
            if (is_array($ul)) {
                $v = $ul['id'] ?? $ul['user_id'] ?? $ul['usuario_id'] ?? null;
                if (is_numeric($v) && (int)$v > 0) {
                    $this->ctxUserId = (int)$v;
                    return $this->ctxUserId;
                }
            }
        } catch (\Throwable $e) {}

        try {
            $id = Auth::id();
            if (is_numeric($id) && (int)$id > 0) {
                $this->ctxUserId = (int)$id;
                return $this->ctxUserId;
            }
        } catch (\Throwable $e) {}

        return 0;
    }

    protected function ctxFilialId(?int $filialId = null): int
    {
        $filialId = (int)($filialId ?? 0);
        if ($filialId !== 0) {
            $this->ctxFilialId = $filialId;
            return $filialId;
        }

        try {
            $req = request();
            $v = $req ? ($req->get('filial_id') ?? $req->get('filialId') ?? $req->get('local_id') ?? $req->get('local')) : null;
            if ($v !== null && $v !== '') {
                $this->ctxFilialId = (int)$v;
                return $this->ctxFilialId;
            }
        } catch (\Throwable $e) {}

        // BaseController: usa local_padrao de user_logged como filial_id padrão
        try {
            $ul = session('user_logged');
            if (is_array($ul)) {
                $v = $ul['local_padrao'] ?? $ul['filial_id'] ?? $ul['filialId'] ?? null;
                if ($v !== null && $v !== '') {
                    $this->ctxFilialId = (int)$v;
                    return $this->ctxFilialId;
                }
            }
        } catch (\Throwable $e) {}

        try {
            $v = session('filial_id');
            if ($v !== null && $v !== '') {
                $this->ctxFilialId = (int)$v;
                return $this->ctxFilialId;
            }
        } catch (\Throwable $e) {}

        return 0;
    }

    /**
     * Exposição opcional (útil para controllers): retorna contexto resolvido.
     */
    public function getContext(): array
    {
        return [
            'empresa_id' => $this->ctxEmpresaId($this->ctxEmpresaId),
            'user_id'    => $this->ctxUserId($this->ctxUserId),
            'filial_id'  => $this->ctxFilialId($this->ctxFilialId),
        ];
    }


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

    public function shouldApply(?int $empresaId = null): bool
    {
        $empresaId = $this->ctxEmpresaId($empresaId);
        if ($empresaId <= 0) {
            return false;
        }

        // 1) Sinalização explícita no banco tem prioridade.
        $enabled = $this->readRtEnableFlag($empresaId);
        if ($enabled === true) {
            return true;
        }
        if ($enabled === false) {
            return false;
        }

        // 2) Fallback operacional: se os campos da RT já estiverem parametrizados,
        // considera habilitado para não zerar cálculo nem XML por regra excessiva.
        $hasRtFields = $this->hasRtFieldsFilled($empresaId);
        if ($hasRtFields) {
            return true;
        }

        // 3) Compatibilidade com a regra ambiente/regime.
        $ambiente = $this->getAmbienteEmpresa($empresaId); // 1/2 default 1
        $regime   = $this->getRegimeEmpresa($empresaId);   // 0/1/2 ou null

        if ((int)$ambiente === 2) {
            return true;
        }

        if ((int)$ambiente === 1 && (int)($regime ?? -1) === 1) {
            return true;
        }

        // 4) Último fallback por ENV, preservando versões anteriores.
        return (int) env('REFORMA_TRIBUTARIA', 0) === 1;
    }

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
        if (Schema::hasColumn($table, 'id')) return 'id';
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

            if (!Schema::hasColumn($c['table'], $c['col'])) {
                continue;
            }

            $whereCol = $this->resolveEmpresaWhereColumn($c['table']);
            if ($whereCol === null) {
                continue;
            }

            $val = DB::table($c['table'])->where($whereCol, $empresaId)->value($c['col']);
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

        if (!Schema::hasColumn('config_notas', 'ambiente')) return 1;

        $whereCol = $this->resolveEmpresaWhereColumn('config_notas');
        if ($whereCol === null) return 1;

        $val = DB::table('config_notas')->where($whereCol, $empresaId)->value('ambiente');
        $amb = (int)$val;

        return ($amb === 2) ? 2 : 1; // só 1 ou 2
    }

    protected function getRegimeEmpresa(int $empresaId): ?int
    {
        if (!Schema::hasTable('tributacaos')) return null;
        if (!Schema::hasColumn('tributacaos', 'regime')) return null;

        $whereCol = $this->resolveEmpresaWhereColumn('tributacaos');
        if ($whereCol === null) return null;

        $val = DB::table('tributacaos')->where($whereCol, $empresaId)->value('regime');
        if ($val === null) return null;

        $raw = trim((string)$val);
        if ($raw === '') return null;

        if (preg_match('/^\d+$/', $raw)) {
            $n = (int)$raw;
            if (in_array($n, [0, 1, 2], true)) {
                return $n;
            }
        }

        $s = function_exists('mb_strtolower')
            ? mb_strtolower($raw, 'UTF-8')
            : strtolower($raw);

        $s = str_replace(
            ['á','à','ã','â','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç'],
            ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c'],
            $s
        );

        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
        $s = trim((string)$s);

        if ($s === '0' || $s === 'simples' || $s === 'simples nacional' || $s === 'sn' || str_contains($s, 'simples nacional')) {
            return 0;
        }

        if ($s === '1' || $s === 'normal' || $s === 'regime normal' || $s === 'lucro real' || $s === 'lucro presumido' || str_contains($s, 'normal')) {
            return 1;
        }

        if ($s === '2' || $s === 'mei' || $s === 'microempreendedor individual' || str_contains($s, 'mei')) {
            return 2;
        }

        if (preg_match('/\b([012])\b/', $s, $m)) {
            $n = (int)$m[1];
            if (in_array($n, [0, 1, 2], true)) {
                return $n;
            }
        }

        return null;
    }

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
        }

        if (!$select) return false;

        $row = DB::table('tributacaos')->where($whereCol, $empresaId)->select($select)->first();
        if (!$row) return false;

        $arr = (array)$row;

        // precisa estar preenchido de forma consistente
        $aliqCbs = $this->toFloat($arr['aliq_cbs'] ?? null, 0.0);
        $aliqUf  = $this->toFloat($arr['aliq_ibs_uf'] ?? null, 0.0);
        $aliqMun = $this->toFloat($arr['aliq_ibs_mun'] ?? null, 0.0);

        $cst = trim((string)($arr['cst_ibs_cbs'] ?? ''));
        $ct  = trim((string)($arr['class_trib_ibs_cbs'] ?? ''));

        return ($aliqCbs > 0 || $aliqUf > 0 || $aliqMun > 0) && ($cst !== '' || $ct !== '');
    }

    // ---------------------------------------------------------------------
    // Defaults (buscaProduto), mas configuráveis por ENV/colunas.
    // ---------------------------------------------------------------------

    public function aliquotasFixas(): array
    {
        return [
            'cbs' => self::RT_ALIQ_CBS_FIXA,
            'ibs_uf' => self::RT_ALIQ_IBS_UF_FIXA,
            'ibs_mun' => self::RT_ALIQ_IBS_MUN_FIXA,
        ];
    }

    public function aliquotaPadrao(int $empresaId, string $tipo): float
    {
        $tipo = strtolower(trim($tipo));
        $fixas = $this->aliquotasFixas();
        return (float) ($fixas[$tipo] ?? 0.0);
    }

    public function applyAliquotasFixas(&$item): void
    {
        $fixas = $this->aliquotasFixas();
        $this->setIfExists($item, 'aliq_cbs', $fixas['cbs']);
        $this->setIfExists($item, 'aliq_ibs_uf', $fixas['ibs_uf']);
        $this->setIfExists($item, 'aliq_ibs_mun', $fixas['ibs_mun']);
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

        $cst       = $this->rowVal($row, ['cst_ibs_cbs']);
        $classTrib = $this->rowVal($row, ['class_trib_ibs_cbs']);

        // reduções (aceita também perc_red_ibs/perc_red_cbs)
        $redIbs = $this->rowVal($row, ['perc_red_ibs','reducao_ibs']);
        $redCbs = $this->rowVal($row, ['perc_red_cbs','reducao_cbs']);

        $flagIs = $this->rowVal($row, ['flag_is']);
        $aliqIs = $this->rowVal($row, ['aliq_is']);

        $anp = $this->rowVal($row, ['codigo_anp']);

        // mantém seus nomes atuais (setIfExists resolve o case)
        $this->setIfExists($item, 'is_aliq', $this->toFloat($aliqIs, 0.0));
        $this->setIfExists($item, 'cst_ibs_cbs', $this->fmtCst3($cst ?? ''));
        $this->setIfExists($item, 'class_trib_ibs_cbs', $this->fmtClassTrib6($classTrib ?? ''));

        $this->setIfExists($item, 'perc_red_aliq_uf',      $this->toFloat($redIbs, 0.0));
        $this->setIfExists($item, 'perc_red_aliq_ibs_mun', $this->toFloat($redIbs, 0.0));
        $this->setIfExists($item, 'perc_red_aliq_cbs',     $this->toFloat($redCbs, 0.0));

        $this->setIfExists($item, 'flag_is', strtoupper(trim((string)($flagIs ?? 'N'))));

        $anp = trim((string)($anp ?? ''));
        $this->setIfExists($item, 'anp', $anp);

        if ($anp !== '') {
            $this->setIfExists($item, 'flag_combustivel', 'S');
        }

        // >>> DEFAULTS <<<
        $this->setIfExists($item, 'aliq_cbs',     $this->aliquotaPadrao($empresaId, 'cbs'));     // 0.9000
        $this->setIfExists($item, 'aliq_ibs_uf',  $this->aliquotaPadrao($empresaId, 'ibs_uf'));  // 0.1000
        $this->setIfExists($item, 'aliq_ibs_mun', $this->aliquotaPadrao($empresaId, 'ibs_mun')); // 0.0000

        // Defaults “zerados” (valores/base/resultados) — NÃO zera alíquotas!
        $zeroFloat = [
            'is_bc','is_aliq_espec','is_qtd_trib','is_valor',
            'bc_ibs_cbs','valor_ibs','valor_ibs_uf','perc_dif_ibs_uf','valor_dif_ibs_uf','valor_dif_ibs_uf_devtrib','aliq_efet_ibs_uf',
            'valor_ibs_mun','perc_dif_ibs_mun','valor_dif_ibs_mun','valor_dif_ibs_mun_trib','aliq_efet_ibs_mun',
            'valor_cbs','perc_dif_cbs','valor_dif_cbs','valor_dif_cbs_devtrib','aliq_efet_cbs',
            'trib_reg_aliq_efet_ibs_uf','trib_reg_valor_ibs_uf',
            'trib_reg_aliq_efet_ibs_mun','trib_reg_valor_ibs_mun',
            'trib_reg_aliq_efet_cbs','trib_reg_valor_cbs',
            'perc_cred_pres_ibs','valor_cred_pres_ibs','valor_cred_pres_cond_sus_ibs',
            'perc_cred_pres_cbs','valor_cred_pres_cbs','valor_cred_pres_cond_sus_cbs',
            'qbcmono_ibs_cbs','valor_ibs_mono','valor_cbs_mono',
            'qbcmonoreten_ibs_cbs','adrem_ibs_reten','adrem_cbs_reten','valor_ibs_reten','valor_cbs_reten',
            'qbcmonoret_ibs_cbs','adrem_ibs_ret','adrem_cbs_ret','valor_ibs_ret','valor_cbs_ret',
            'adrem_ibs','adrem_cbs'
        ];
        foreach ($zeroFloat as $f) $this->setIfExists($item, $f, 0.0);

        $zeroInt = ['cred_pres_cod_ibs','cred_pres_cod_cbs'];
        foreach ($zeroInt as $f) $this->setIfExists($item, $f, 0);

        if ($anp !== '') {
            $adrem = $this->aliquotaAnp($anp);
            $this->setIfExists($item, 'adrem_ibs', $adrem);
            $this->setIfExists($item, 'adrem_cbs', $adrem);
        }
    }

    // ---------------------------------------------------------------------
    // 3) Cálculo do item
    // ---------------------------------------------------------------------

    public function calcularItem($item, int $empresaId, ?string $cfopDescricao = null): void
    {
        $this->applyAliquotasFixas($item);

        $vProdTotal = $this->getNum($item, ['nfsi_vlrtotal','vlr_total','valor_total','valor_total_item'], 0.0);

        $qtd = $this->getNum($item, ['nfsi_quantidade','quantidade','qtd'], 0.0);

        $vUnit = $this->getNum($item, ['valor_unitario','valor_unit','vlr_unitario'], 0.0);
        if ($vUnit <= 0) {
            $vUnit = $this->getNum($item, ['valor'], 0.0);
        }

        $vProd = $vProdTotal;
        if ($vProd <= 0 && $qtd > 0 && $vUnit > 0) {
            $vProd = $this->round2($qtd * $vUnit);
        }

        if ($vProd <= 0) {
            $vProd = $this->getNum($item, ['nfsi_vlrtotal','vlr_total','valor_total','valor'], 0.0);
        }

        $vSeg    = $this->getNum($item, ['nfsi_seguro','seguro']);
        $vFrete  = $this->getNum($item, ['nfsi_frete','frete']);
        $vOutro  = $this->getNum($item, ['nfsi_despesas','despesas','outros']);
        $vDesc   = $this->getNum($item, ['nfsi_vlrdesconto','vlr_desconto','desconto']);
        $vPis    = $this->getNum($item, ['nfsi_pis_vlrimposto','pis_valor','pis_vlr_imposto']);
        $vCof    = $this->getNum($item, ['nfsi_cofins_vlrimposto','cofins_valor','cofins_vlr_imposto']);
        $vIcms   = $this->getNum($item, ['nfsi_vlricms','icms_valor','valor_icms']);
        $vUFDest = $this->getNum($item, ['nfsi_valor_icms_destino','icms_ufdest_valor']);
        $vFcp    = $this->getNum($item, ['nsfi_valor_icms_fcp','valor_fcp']);
        $vFcpSt  = $this->getNum($item, ['nsfi_valor_fcp_st','valor_fcp_st']);
        $vMono   = $this->getNum($item, ['nfsi_vicmsmono','icms_mono_valor']);
        $vIS     = $this->getNum($item, ['is_valor']);

        $bc = $this->round2(($vProd + $vSeg + $vFrete + $vOutro - $vDesc - $vPis - $vCof - $vIcms - $vUFDest - $vFcp - $vFcpSt - $vMono + $vIS));
        $this->setIfExists($item, 'bc_ibs_cbs', $bc);

        $desc = strtoupper((string)($cfopDescricao ?? ''));
        if ($desc !== '' && (str_contains($desc, 'REMESSA') || str_contains($desc, 'RETORNO'))) {
            $this->zeroCamposReforma($item);
            $this->calcAliqEfetivasSeCst200($item);
            $this->calcValorIbsTotal($item);
            return;
        }

        $flagIs = strtoupper(trim((string)($this->readField($item, 'flag_is') ?? '')));
        if ($flagIs === 'S') {
            $this->setIfExists($item, 'is_bc', $vProd);
            $aliqIs = $this->getNum($item, ['is_aliq','is_aliq'], 0);
            $this->setIfExists($item, 'is_valor', $this->round2(($vProd * $aliqIs) / 100));
        } else {
            $this->setIfExists($item, 'is_bc', 0.0);
            $this->setIfExists($item, 'is_valor', 0.0);
            $this->setIfExists($item, 'is_aliq', 0.0);
        }

        $cst = trim((string)($this->readField($item, 'cst_ibs_cbs') ?? ''));
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
            'bc_ibs_cbs','valor_ibs','valor_ibs_uf','aliq_efet_ibs_uf','perc_dif_ibs_uf','valor_dif_ibs_uf','valor_dif_ibs_uf_devtrib',
            'valor_ibs_mun','perc_dif_ibs_mun','valor_dif_ibs_mun','valor_dif_ibs_mun_trib','aliq_efet_ibs_mun',
            'valor_cbs','perc_dif_cbs','valor_dif_cbs','valor_dif_cbs_devtrib','aliq_efet_cbs',
            'trib_reg_aliq_efet_ibs_uf','trib_reg_valor_ibs_uf',
            'trib_reg_aliq_efet_ibs_mun','trib_reg_valor_ibs_mun',
            'trib_reg_aliq_efet_cbs','trib_reg_valor_cbs',
            'perc_cred_pres_ibs','valor_cred_pres_ibs','valor_cred_pres_cond_sus_ibs',
            'perc_cred_pres_cbs','valor_cred_pres_cbs','valor_cred_pres_cond_sus_cbs',
        ];
        foreach ($zeroFloat as $f) $this->setIfExists($item, $f, 0.0);

        $zeroInt = ['cred_pres_cod_ibs','cred_pres_cod_cbs'];
        foreach ($zeroInt as $f) $this->setIfExists($item, $f, 0);
    }

    protected function calcAliqEfetivasSeCst200($item): void
    {
        $cst = $this->fmtCst3($this->readField($item, 'cst_ibs_cbs') ?? '');
        if ($cst !== '200') return;

        $aUf  = $this->getNum($item, ['aliq_ibs_uf'], 0);
        $aMun = $this->getNum($item, ['aliq_ibs_mun'], 0);
        $aCbs = $this->getNum($item, ['aliq_cbs'], 0);

        $rUf  = $this->getNum($item, ['perc_red_aliq_uf'], 0);
        $rMun = $this->getNum($item, ['perc_red_aliq_ibs_mun'], 0);
        $rCbs = $this->getNum($item, ['perc_red_aliq_cbs'], 0);

        if ($rUf > 0)  $this->setIfExists($item, 'aliq_efet_ibs_uf',  $aUf  * (1 - ($rUf/100)));
        if ($rMun > 0) $this->setIfExists($item, 'aliq_efet_ibs_mun', $aMun * (1 - ($rMun/100)));
        if ($rCbs > 0) $this->setIfExists($item, 'aliq_efet_cbs',     $aCbs * (1 - ($rCbs/100)));
    }

    protected function calcIntegral($item): void
    {
        $bc = $this->getNum($item, ['bc_ibs_cbs'], 0);

        $aUf  = $this->getNum($item, ['aliq_ibs_uf'], 0);
        $aMun = $this->getNum($item, ['aliq_ibs_mun'], 0);
        $aCbs = $this->getNum($item, ['aliq_cbs'], 0);

        $this->setIfExists($item, 'aliq_efet_ibs_uf', $aUf);
        $this->setIfExists($item, 'valor_ibs_uf', $this->round2(($bc * $aUf) / 100));

        $this->setIfExists($item, 'aliq_efet_ibs_mun', $aMun);
        $this->setIfExists($item, 'valor_ibs_mun', $this->round2(($bc * $aMun) / 100));

        $this->setIfExists($item, 'aliq_efet_cbs', $aCbs);
        $this->setIfExists($item, 'valor_cbs', $this->round2(($bc * $aCbs) / 100));
    }

    protected function calcReduzido($item): void
    {
        $bc = $this->getNum($item, ['bc_ibs_cbs'], 0);

        $aUf  = $this->getNum($item, ['aliq_ibs_uf'], 0);
        $aMun = $this->getNum($item, ['aliq_ibs_mun'], 0);
        $aCbs = $this->getNum($item, ['aliq_cbs'], 0);

        $rUf  = $this->getNum($item, ['perc_red_aliq_uf'], 0);
        $rMun = $this->getNum($item, ['perc_red_aliq_ibs_mun'], 0);
        $rCbs = $this->getNum($item, ['perc_red_aliq_cbs'], 0);

        $efUf = ($rUf > 0) ? $aUf * (1 - ($rUf/100)) : $aUf;
        $this->setIfExists($item, 'aliq_efet_ibs_uf', $efUf);
        $this->setIfExists($item, 'valor_ibs_uf', $this->round2(($bc * $efUf) / 100));

        $efMun = ($rMun > 0) ? $aMun * (1 - ($rMun/100)) : $aMun;
        $this->setIfExists($item, 'aliq_efet_ibs_mun', $efMun);
        $this->setIfExists($item, 'valor_ibs_mun', $this->round2(($bc * $efMun) / 100));

        $efCbs = ($rCbs > 0) ? $aCbs * (1 - ($rCbs/100)) : $aCbs;
        $this->setIfExists($item, 'aliq_efet_cbs', $efCbs);
        $this->setIfExists($item, 'valor_cbs', $this->round2(($bc * $efCbs) / 100));
    }

    protected function calcMono($item): void
    {
        $this->setIfExists($item, 'bc_ibs_cbs', 0.0);

        $this->calcIntegral($item);

        $flagComb = strtoupper(trim((string)($this->readField($item, 'flag_combustivel') ?? '')));
        $anp = trim((string)($this->readField($item, 'anp') ?? ''));

        if ($flagComb !== 'S' || $anp === '') return;
        if (strtoupper((string)$this->consultaAnp($anp, 'monofasico')) !== 'S') return;

        $classTrib = trim((string)($this->readField($item, 'class_trib_ibs_cbs') ?? ''));
        $indMono = $this->buscarIndMonoClassTrib($classTrib);
        if (strtoupper($indMono) === 'N') {
            $this->setIfExists($item, 'adrem_ibs', 0.0);
            $this->setIfExists($item, 'adrem_cbs', 0.0);
            $this->setIfExists($item, 'valor_ibs_mono', 0.0);
            $this->setIfExists($item, 'valor_cbs_mono', 0.0);
            return;
        }

        $qtd = $this->getNum($item, ['nfsi_quantidade','quantidade'], 0);
        $adrem = $this->aliquotaAnp($anp);

        if ($classTrib === '620001') {
            $this->setIfExists($item, 'qbcmono_ibs_cbs', $qtd);
            $this->setIfExists($item, 'valor_ibs_mono', $this->round2($qtd * $this->getNum($item, ['adrem_ibs'], $adrem)));
            $this->setIfExists($item, 'valor_cbs_mono', $this->round2($qtd * $this->getNum($item, ['adrem_cbs'], $adrem)));
        }

        if ($classTrib === '620002') {
            $this->setIfExists($item, 'qbcmono_ibs_cbs', $qtd);
            $this->setIfExists($item, 'qbcmonoreten_ibs_cbs', $qtd);
            $this->setIfExists($item, 'adrem_ibs_reten', $adrem);
            $this->setIfExists($item, 'adrem_cbs_reten', $adrem);
            $this->setIfExists($item, 'valor_ibs_mono', $this->round2($qtd * $adrem));
            $this->setIfExists($item, 'valor_cbs_mono', $this->round2($qtd * $adrem));
        }

        if ($classTrib === '620006') {
            $this->setIfExists($item, 'qbcmono_ibs_cbs', $qtd);
            $this->setIfExists($item, 'qbcmonoret_ibs_cbs', $qtd);
            $this->setIfExists($item, 'adrem_ibs_ret', $adrem);
            $this->setIfExists($item, 'adrem_cbs_ret', $adrem);
            $this->setIfExists($item, 'valor_ibs_ret', $this->round2($qtd * $adrem));
            $this->setIfExists($item, 'valor_cbs_ret', $this->round2($qtd * $adrem));
        }
    }

    protected function calcValorIbsTotal($item): void
    {
        $uf  = $this->getNum($item, ['valor_ibs_uf','valor_ibs_uf'], 0);
        $mun = $this->getNum($item, ['valor_ibs_mun','valor_ibs_mun'], 0);
        $this->setIfExists($item, 'valor_ibs', $this->round2($uf + $mun));
    }

    // ---------------------------------------------------------------------
    // 4) Totais (cabeçalho) por venda
    // ---------------------------------------------------------------------

    public function calcularTotaisVenda(?int $empresaId, int $vendaId, string $itensTable = 'item_vendas', string $foreignKey = 'venda_id'): array
    {
        $empresaId = $this->ctxEmpresaId($empresaId);
        if (!Schema::hasTable($itensTable)) return [];
        if (!Schema::hasColumn($itensTable, $foreignKey)) return [];

        $sumCols = [
            'bc_ibs_cbs'               => 'total_bc_ibs_cbs',
            'valor_dif_ibs_uf'         => 'total_ibs_uf_dif',
            'valor_dif_ibs_uf_devtrib' => 'total_ibs_uf_dev_trib',
            'valor_ibs_uf'             => 'total_ibs_uf',
            'valor_dif_ibs_mun'        => 'total_ibs_mun_dif',
            'valor_dif_ibs_mun_trib'   => 'total_ibs_mun_dev_trib',
            'valor_ibs_mun'            => 'total_ibs_mun',
            'valor_dif_cbs'            => 'total_cbs_dif',
            'valor_dif_cbs_devtrib'    => 'total_cbs_dev_trib',
            'valor_cbs'                => 'total_cbs',
            'valor_cred_pres_ibs'      => 'total_ibs_cred_pres',
            'valor_cred_pres_cond_sus_ibs' => 'total_ibs_cred_pres_cond_sus',
            'valor_cred_pres_cbs'      => 'total_cbs_cred_pres',
            'valor_cred_pres_cond_sus_cbs' => 'total_cbs_cred_pres_cond_sus',
            'is_valor'                 => 'total_is',
            'valor_ibs_mono'           => 'total_ibs_mono',
            'valor_cbs_mono'           => 'total_cbs_mono',
            'valor_ibs_reten'          => 'total_ibs_mono_reten',
            'valor_cbs_reten'          => 'total_cbs_mono_reten',
            'valor_ibs_ret'            => 'total_ibs_mono_ret',
            'valor_cbs_ret'            => 'total_cbs_mono_ret',
        ];

        $selects = [];
        foreach ($sumCols as $col => $alias) {
            if (Schema::hasColumn($itensTable, $col)) {
                $selects[] = "COALESCE(SUM($col),0) as $alias";
            }
        }

        if (!$selects) return [];

        $row = DB::table($itensTable)
            ->where($foreignKey, $vendaId)
            ->selectRaw(implode(",", $selects))
            ->first();

        if (!$row) return [];

        $t = (array)$row;

        $totalIbs = (float)($t['total_ibs_uf'] ?? 0) + (float)($t['total_ibs_mun'] ?? 0);
        $totalIbsCbs = $totalIbs + (float)($t['total_cbs'] ?? 0);
        $totalNfIbcCbsIs = (float)($t['total_bc_ibs_cbs'] ?? 0) + (float)($t['total_is'] ?? 0);

        $t['total_ibs'] = $this->round2($totalIbs);
        $t['total_ibs_cbs'] = $this->round2($totalIbsCbs);
        $t['total_nf_ibc_cbs_is'] = $this->round2($totalNfIbcCbsIs);

        return $t;
    }

    public function applyTotaisToVenda($vendaModel, array $totais): void
    {
        if (!$vendaModel || !$totais) return;

        foreach ($totais as $k => $v) {
            $this->setIfExists($vendaModel, $k, $v);
        }

        if (isset($totais['total_ibs'])) $this->setIfExists($vendaModel, 'total_ibs', $totais['total_ibs']);
        if (isset($totais['total_ibs_cbs'])) $this->setIfExists($vendaModel, 'total_ibs_cbs', $totais['total_ibs_cbs']);

        try { $vendaModel->save(); } catch (\Throwable $e) {}
    }

    // ---------------------------------------------------------------------
    // 4B) Totais (cabeçalho) por compra
    // ---------------------------------------------------------------------

    public function calcularTotaisCompra(?int $empresaId, int $compraId, string $itensTable = 'item_compras'): array
    {
        $empresaId = $this->ctxEmpresaId($empresaId);
        if (!Schema::hasTable($itensTable)) return [];

        $sumCols = [
            'bc_ibs_cbs'               => 'total_bc_ibs_cbs',
            'valor_dif_ibs_uf'         => 'total_ibs_uf_dif',
            'valor_dif_ibs_uf_devtrib' => 'total_ibs_uf_dev_trib',
            'valor_ibs_uf'             => 'total_ibs_uf',
            'valor_dif_ibs_mun'        => 'total_ibs_mun_dif',
            'valor_dif_ibs_mun_trib'   => 'total_ibs_mun_dev_trib',
            'valor_ibs_mun'            => 'total_ibs_mun',
            'valor_dif_cbs'            => 'total_cbs_dif',
            'valor_dif_cbs_devtrib'    => 'total_cbs_dev_trib',
            'valor_cbs'                => 'total_cbs',
            'is_valor'                 => 'total_is',
            'valor_ibs_mono'           => 'total_ibs_mono',
            'valor_cbs_mono'           => 'total_cbs_mono',
            'valor_ibs_reten'          => 'total_ibs_mono_reten',
            'valor_cbs_reten'          => 'total_cbs_mono_reten',
            'valor_ibs_ret'            => 'total_ibs_mono_ret',
            'valor_cbs_ret'            => 'total_cbs_mono_ret',
        ];

        $selects = [];
        foreach ($sumCols as $col => $alias) {

            if (Schema::hasColumn($itensTable, $col)) {
                $selects[] = "COALESCE(SUM($col),0) as $alias";
            }
        }

        if (!$selects) return [];

        $row = DB::table($itensTable)
            ->where('compra_id', $compraId)
            ->selectRaw(implode(",\n", $selects))
            ->first();

        if (!$row) return [];

        $t = (array)$row;

        $totalIbs = (float)($t['total_ibs_uf'] ?? 0) + (float)($t['total_ibs_mun'] ?? 0);
        $totalIbsCbs = $totalIbs + (float)($t['total_cbs'] ?? 0);

        $t['total_ibs'] = $this->round2($totalIbs);
        $t['total_ibs_cbs'] = $this->round2($totalIbsCbs);

        return $t;
    }

    public function applyTotaisToCompra($compraModel, array $totais): void
    {
        if (!$compraModel || !$totais) return;

        foreach ($totais as $k => $v) {
            $this->setIfExists($compraModel, $k, $v);
        }

        if (isset($totais['total_ibs'])) $this->setIfExists($compraModel, 'total_ibs', $totais['total_ibs']);
        if (isset($totais['total_ibs_cbs'])) $this->setIfExists($compraModel, 'total_ibs_cbs', $totais['total_ibs_cbs']);

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

        if (!Schema::hasTable('anp')) return $cache[$codigo] = 0.0;

        if (!Schema::hasColumn('anp', 'codigo')) return $cache[$codigo] = 0.0;
        if (!Schema::hasColumn('anp', 'adremicms')) return $cache[$codigo] = 0.0;

        $val = Anp::query()
            ->where('codigo', $codigo)
            ->value('adremicms');

        $cache[$codigo] = $this->toFloat($val, 0.0);
        return $cache[$codigo];
    }

    protected function consultaAnp(string $cProdAnp, string $campo): string
    {
        $codigo = $this->toInt($cProdAnp, 0);
        if ($codigo <= 0) return '';

        $campo = trim((string)$campo);
        if ($campo === '') return '';

        if (!Schema::hasTable('anp')) return '';

        $col = strtolower($campo);
        if (!Schema::hasColumn('anp', $col)) return '';

        static $cache = [];
        $key = $codigo . '|anp|' . $col;
        if (array_key_exists($key, $cache)) return $cache[$key];

        if (!Schema::hasColumn('anp', 'codigo')) return '';

        $val = DB::table('anp')->where('codigo', $codigo)->value($col);
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

        if (!Schema::hasTable('class_trib_ibs_cbs')) {
            return $cache[$classTrib] = 'S';
        }

        if (!Schema::hasColumn('class_trib_ibs_cbs', 'cclasstrib')) {
            return $cache[$classTrib] = 'S';
        }

        $today = now()->toDateString();

        $q = ClassTribIbsCbs::query()->where('cclasstrib', $classTrib);

        if (Schema::hasColumn('class_trib_ibs_cbs', 'dinivig')) {
            $q->where(function ($qq) use ($today) {
                $qq->whereNull('dinivig')->orWhere('dinivig', '<=', $today);
            });
            $q->orderByDesc('dinivig');
        }

        if (Schema::hasColumn('class_trib_ibs_cbs', 'dfimvig')) {
            $q->where(function ($qq) use ($today) {
                $qq->whereNull('dfimvig')->orWhere('dfimvig', '>=', $today);
            });
        }

        if (Schema::hasColumn('class_trib_ibs_cbs', 'dataatualizacao')) {
            $q->orderByDesc('dataatualizacao');
        }

        $row = $q->first();

        $ind = $row ? (string)($row->indmono ?? '') : '';
        $ind = strtoupper(trim($ind));
        if ($ind === '') $ind = 'S';

        return $cache[$classTrib] = $ind;
    }
}
