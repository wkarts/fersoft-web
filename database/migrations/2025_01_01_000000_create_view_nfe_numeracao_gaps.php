<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('DROP VIEW IF EXISTS vw_nfe_numeracao_gaps');
            DB::statement(<<<'SQL'
CREATE VIEW vw_nfe_numeracao_gaps AS
SELECT
    NULL AS empresa_id,
    NULL AS filial_id,
    NULL AS filial_label,
    NULL AS serie,
    NULL AS numero_usado_anterior,
    NULL AS origem_usado_anterior,
    NULL AS origem_id_anterior,
    NULL AS data_doc_anterior,
    NULL AS chave_nfe_anterior,
    NULL AS status_nfe_anterior,
    NULL AS anterior_sem_chave,
    NULL AS numero_usado_atual,
    NULL AS origem_usado_atual,
    NULL AS origem_id_atual,
    NULL AS data_doc_atual,
    NULL AS chave_nfe_atual,
    NULL AS status_nfe_atual,
    NULL AS atual_sem_chave,
    NULL AS numero_inicial_pulado,
    NULL AS numero_final_pulado,
    NULL AS quantidade_pulada
WHERE 1 = 0;
SQL);
            return;
        }

        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW vw_nfe_numeracao_gaps AS
WITH docs AS (
    -- NF-e de SAÍDA (vendas)
    SELECT
        v.empresa_id,
        v.filial_id,
        v.nSerie               AS serie,
        v.NfNumero             AS numero,
        'V'                    AS origem,      -- Venda
        v.id                   AS origem_id,
        v.data_emissao         AS data_doc,
        v.chave                AS chave_nfe,   -- coluna de chave NF-e
        v.estado               AS status_nfe   -- status da NF-e (aprovado, rejeitado, etc.)
    FROM vendas v
    WHERE v.NfNumero > 0
      AND v.nSerie  > 0

    UNION ALL

    -- NF-e de ENTRADA EMITIDA pelo próprio tenant
    -- usa a mesma sequência, controlada em compras.numero_emissao
    SELECT
        c.empresa_id,
        c.filial_id,
        cn.numero_serie_nfe    AS serie,
        c.numero_emissao       AS numero,
        'C'                    AS origem,      -- Compra (entrada emitida)
        c.id                   AS origem_id,
        c.data_emissao         AS data_doc,
        c.chave                AS chave_nfe,
        c.estado               AS status_nfe
    FROM compras c
    LEFT JOIN config_notas cn
           ON cn.empresa_id = c.empresa_id
    WHERE c.numero_emissao > 0
),
ordenado AS (
    SELECT
        d.*,
        COALESCE(d.filial_id, 0) AS filial_grp, -- para particionar/ordenar
        CASE
            WHEN d.filial_id IS NULL THEN 'MATRIZ'
            ELSE CONCAT('FILIAL ', d.filial_id)
        END AS filial_label,

        -- último número usado na mesma empresa/filial/série
        LAG(d.numero) OVER (
            PARTITION BY d.empresa_id, COALESCE(d.filial_id, 0), d.serie
            ORDER BY d.numero
        ) AS numero_anterior,

        -- de onde veio esse número anterior (V ou C)
        LAG(d.origem) OVER (
            PARTITION BY d.empresa_id, COALESCE(d.filial_id, 0), d.serie
            ORDER BY d.numero
        ) AS origem_anterior,

        -- ID da origem anterior (id da venda ou compra)
        LAG(d.origem_id) OVER (
            PARTITION BY d.empresa_id, COALESCE(d.filial_id, 0), d.serie
            ORDER BY d.numero
        ) AS origem_id_anterior,

        -- data do doc anterior
        LAG(d.data_doc) OVER (
            PARTITION BY d.empresa_id, COALESCE(d.filial_id, 0), d.serie
            ORDER BY d.numero
        ) AS data_doc_anterior,

        -- chave NF-e do doc anterior
        LAG(d.chave_nfe) OVER (
            PARTITION BY d.empresa_id, COALESCE(d.filial_id, 0), d.serie
            ORDER BY d.numero
        ) AS chave_nfe_anterior,

        -- status NF-e do doc anterior
        LAG(d.status_nfe) OVER (
            PARTITION BY d.empresa_id, COALESCE(d.filial_id, 0), d.serie
            ORDER BY d.numero
        ) AS status_nfe_anterior
    FROM docs d
)
SELECT
    empresa_id,
    filial_id,
    filial_label,                    -- MATRIZ / FILIAL X
    serie,

    -- documento ANTERIOR (onde a numeração foi usada por último)
    numero_anterior           AS numero_usado_anterior,
    origem_anterior           AS origem_usado_anterior,        -- 'V' venda, 'C' compra
    origem_id_anterior        AS origem_id_anterior,
    data_doc_anterior         AS data_doc_anterior,
    chave_nfe_anterior        AS chave_nfe_anterior,
    status_nfe_anterior       AS status_nfe_anterior,

    -- indicador: documento anterior tem número mas está sem chave?
    CASE
        WHEN numero_anterior IS NOT NULL
         AND (chave_nfe_anterior IS NULL OR chave_nfe_anterior = '')
        THEN 'SIM'
        ELSE 'NÃO'
    END AS anterior_sem_chave,

    -- documento ATUAL (onde a numeração voltou a ser usada)
    numero                    AS numero_usado_atual,
    origem                    AS origem_usado_atual,
    origem_id                 AS origem_id_atual,
    data_doc                  AS data_doc_atual,
    chave_nfe                 AS chave_nfe_atual,
    status_nfe                AS status_nfe_atual,

    -- indicador: documento atual tem número mas está sem chave?
    CASE
        WHEN numero IS NOT NULL
         AND (chave_nfe IS NULL OR chave_nfe = '')
        THEN 'SIM'
        ELSE 'NÃO'
    END AS atual_sem_chave,

    -- faixa pulada entre um doc e outro
    numero_anterior + 1       AS numero_inicial_pulado,
    numero - 1                AS numero_final_pulado,
    (numero - numero_anterior - 1) AS quantidade_pulada
FROM ordenado
WHERE numero_anterior IS NOT NULL
  AND numero > numero_anterior + 1
ORDER BY
    empresa_id,
    filial_grp,
    serie,
    numero_inicial_pulado;
SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('DROP VIEW IF EXISTS vw_nfe_numeracao_gaps');
            return;
        }

        DB::statement('DROP VIEW IF EXISTS vw_nfe_numeracao_gaps');
    }
};
