<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW `vw_nfe_numeracao_gaps` AS
WITH `docs` AS
(
    SELECT
        `v`.`empresa_id` AS `empresa_id`,
        `v`.`filial_id`  AS `filial_id`,
        `v`.`nSerie`     AS `serie`,
        `v`.`NfNumero`   AS `numero`,
        'V'              AS `origem`,
        `v`.`id`         AS `origem_id`,
        `v`.`data_emissao` AS `data_doc`,
        `v`.`chave`      AS `chave_nfe`,
        `v`.`estado`     AS `status_nfe`
    FROM `vendas` `v`
    WHERE
        `v`.`NfNumero` > 0
        AND `v`.`nSerie` > 0

    UNION ALL

    SELECT
        `c`.`empresa_id`        AS `empresa_id`,
        `c`.`filial_id`         AS `filial_id`,
        `cn`.`numero_serie_nfe` AS `serie`,
        `c`.`numero_emissao`    AS `numero`,
        'C'                     AS `origem`,
        `c`.`id`                AS `origem_id`,
        `c`.`data_emissao`      AS `data_doc`,
        `c`.`chave`             AS `chave_nfe`,
        `c`.`estado`            AS `status_nfe`
    FROM `compras` `c`
    LEFT JOIN `config_notas` `cn`
        ON `cn`.`empresa_id` = `c`.`empresa_id`
    WHERE
        `c`.`numero_emissao` > 0
),
`ordenado` AS
(
    SELECT
        `d`.`empresa_id`  AS `empresa_id`,
        `d`.`filial_id`   AS `filial_id`,
        `d`.`serie`       AS `serie`,
        `d`.`numero`      AS `numero`,
        `d`.`origem`      AS `origem`,
        `d`.`origem_id`   AS `origem_id`,
        `d`.`data_doc`    AS `data_doc`,
        `d`.`chave_nfe`   AS `chave_nfe`,
        `d`.`status_nfe`  AS `status_nfe`,
        COALESCE(`d`.`filial_id`, 0) AS `filial_grp`,
        CASE
            WHEN `d`.`filial_id` IS NULL THEN 'MATRIZ'
            ELSE CONCAT('FILIAL ', `d`.`filial_id`)
        END AS `filial_label`,
        LAG(`d`.`numero`) OVER (
            PARTITION BY `d`.`empresa_id`, COALESCE(`d`.`filial_id`, 0), `d`.`serie`
            ORDER BY `d`.`numero`
        ) AS `numero_anterior`,
        LAG(`d`.`origem`) OVER (
            PARTITION BY `d`.`empresa_id`, COALESCE(`d`.`filial_id`, 0), `d`.`serie`
            ORDER BY `d`.`numero`
        ) AS `origem_anterior`,
        LAG(`d`.`origem_id`) OVER (
            PARTITION BY `d`.`empresa_id`, COALESCE(`d`.`filial_id`, 0), `d`.`serie`
            ORDER BY `d`.`numero`
        ) AS `origem_id_anterior`,
        LAG(`d`.`data_doc`) OVER (
            PARTITION BY `d`.`empresa_id`, COALESCE(`d`.`filial_id`, 0), `d`.`serie`
            ORDER BY `d`.`numero`
        ) AS `data_doc_anterior`,
        LAG(`d`.`chave_nfe`) OVER (
            PARTITION BY `d`.`empresa_id`, COALESCE(`d`.`filial_id`, 0), `d`.`serie`
            ORDER BY `d`.`numero`
        ) AS `chave_nfe_anterior`,
        LAG(`d`.`status_nfe`) OVER (
            PARTITION BY `d`.`empresa_id`, COALESCE(`d`.`filial_id`, 0), `d`.`serie`
            ORDER BY `d`.`numero`
        ) AS `status_nfe_anterior`
    FROM `docs` `d`
)
SELECT
    `ordenado`.`empresa_id`          AS `empresa_id`,
    `ordenado`.`filial_id`           AS `filial_id`,
    `ordenado`.`filial_label`        AS `filial_label`,
    `ordenado`.`serie`               AS `serie`,
    `ordenado`.`numero_anterior`     AS `numero_usado_anterior`,
    `ordenado`.`origem_anterior`     AS `origem_usado_anterior`,
    `ordenado`.`origem_id_anterior`  AS `origem_id_anterior`,
    `ordenado`.`data_doc_anterior`   AS `data_doc_anterior`,
    `ordenado`.`chave_nfe_anterior`  AS `chave_nfe_anterior`,
    `ordenado`.`status_nfe_anterior` AS `status_nfe_anterior`,
    CASE
        WHEN `ordenado`.`numero_anterior` IS NOT NULL
             AND (`ordenado`.`chave_nfe_anterior` IS NULL OR `ordenado`.`chave_nfe_anterior` = '')
        THEN 'SIM'
        ELSE 'NÃO'
    END AS `anterior_sem_chave`,
    `ordenado`.`numero`              AS `numero_usado_atual`,
    `ordenado`.`origem`              AS `origem_usado_atual`,
    `ordenado`.`origem_id`           AS `origem_id_atual`,
    `ordenado`.`data_doc`            AS `data_doc_atual`,
    `ordenado`.`chave_nfe`           AS `chave_nfe_atual`,
    `ordenado`.`status_nfe`          AS `status_nfe_atual`,
    CASE
        WHEN `ordenado`.`numero` IS NOT NULL
             AND (`ordenado`.`chave_nfe` IS NULL OR `ordenado`.`chave_nfe` = '')
        THEN 'SIM'
        ELSE 'NÃO'
    END AS `atual_sem_chave`,
    (`ordenado`.`numero_anterior` + 1) AS `numero_inicial_pulado`,
    (`ordenado`.`numero` - 1)          AS `numero_final_pulado`,
    ((`ordenado`.`numero` - `ordenado`.`numero_anterior`) - 1) AS `quantidade_pulada`
FROM `ordenado`
WHERE
    `ordenado`.`numero_anterior` IS NOT NULL
    AND `ordenado`.`numero` > (`ordenado`.`numero_anterior` + 1)
    AND NOT EXISTS (
        SELECT 1
        FROM `nfe_inutilizacoes` `ni`
        WHERE
            `ni`.`empresa_id`     = `ordenado`.`empresa_id`
            AND `ni`.`modelo`     = '55'
            AND `ni`.`filial_id` <=> `ordenado`.`filial_id`
            AND `ni`.`serie`      = `ordenado`.`serie`
            AND `ni`.`numero_inicial` <= (`ordenado`.`numero_anterior` + 1)
            AND `ni`.`numero_final`   >= (`ordenado`.`numero` - 1)
            AND (`ni`.`status` = 'autorizada' OR `ni`.`ignorar_gaps` = 1)
    )
ORDER BY
    `ordenado`.`empresa_id`,
    `ordenado`.`filial_grp`,
    `ordenado`.`serie`,
    (`ordenado`.`numero_anterior` + 1);
SQL
        );
    }

    public function down(): void
    {
        // Volta a VIEW para a versão ORIGINAL (sem ignorar_gaps / status),
        // exatamente como estava antes desta migration.
        DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW `vw_nfe_numeracao_gaps` AS
WITH `docs`
AS
(SELECT
      `v`.`empresa_id` AS `empresa_id`,
      `v`.`filial_id` AS `filial_id`,
      `v`.`nSerie` AS `serie`,
      `v`.`NfNumero` AS `numero`,
      'V' AS `origem`,
      `v`.`id` AS `origem_id`,
      `v`.`data_emissao` AS `data_doc`,
      `v`.`chave` AS `chave_nfe`,
      `v`.`estado` AS `status_nfe`
    FROM `vendas` `v`
    WHERE ((`v`.`NfNumero` > 0)
    AND (`v`.`nSerie` > 0))
    UNION ALL
    SELECT
      `c`.`empresa_id` AS `empresa_id`,
      `c`.`filial_id` AS `filial_id`,
      `cn`.`numero_serie_nfe` AS `serie`,
      `c`.`numero_emissao` AS `numero`,
      'C' AS `origem`,
      `c`.`id` AS `origem_id`,
      `c`.`data_emissao` AS `data_doc`,
      `c`.`chave` AS `chave_nfe`,
      `c`.`estado` AS `status_nfe`
    FROM (`compras` `c`
      LEFT JOIN `config_notas` `cn`
        ON ((`cn`.`empresa_id` = `c`.`empresa_id`)))
    WHERE (`c`.`numero_emissao` > 0)), `ordenado`
AS
(SELECT
      `d`.`empresa_id` AS `empresa_id`,
      `d`.`filial_id` AS `filial_id`,
      `d`.`serie` AS `serie`,
      `d`.`numero` AS `numero`,
      `d`.`origem` AS `origem`,
      `d`.`origem_id` AS `origem_id`,
      `d`.`data_doc` AS `data_doc`,
      `d`.`chave_nfe` AS `chave_nfe`,
      `d`.`status_nfe` AS `status_nfe`,
      COALESCE(`d`.`filial_id`, 0) AS `filial_grp`,
      (CASE WHEN (`d`.`filial_id` IS NULL) THEN 'MATRIZ' ELSE CONCAT('FILIAL ', `d`.`filial_id`) END) AS `filial_label`,
      LAG(`d`.`numero`) OVER (PARTITION BY `d`.`empresa_id`, COALESCE(`d`.`filial_id`, 0), `d`.`serie`
      ORDER BY `d`.`numero`) AS `numero_anterior`,
      LAG(`d`.`origem`) OVER (PARTITION BY `d`.`empresa_id`, COALESCE(`d`.`filial_id`, 0), `d`.`serie`
      ORDER BY `d`.`numero`) AS `origem_anterior`,
      LAG(`d`.`origem_id`) OVER (PARTITION BY `d`.`empresa_id`, COALESCE(`d`.`filial_id`, 0), `d`.`serie`
      ORDER BY `d`.`numero`) AS `origem_id_anterior`,
      LAG(`d`.`data_doc`) OVER (PARTITION BY `d`.`empresa_id`, COALESCE(`d`.`filial_id`, 0), `d`.`serie`
      ORDER BY `d`.`numero`) AS `data_doc_anterior`,
      LAG(`d`.`chave_nfe`) OVER (PARTITION BY `d`.`empresa_id`, COALESCE(`d`.`filial_id`, 0), `d`.`serie`
      ORDER BY `d`.`numero`) AS `chave_nfe_anterior`,
      LAG(`d`.`status_nfe`) OVER (PARTITION BY `d`.`empresa_id`, COALESCE(`d`.`filial_id`, 0), `d`.`serie`
      ORDER BY `d`.`numero`) AS `status_nfe_anterior`
    FROM `docs` `d`)
SELECT
  `ordenado`.`empresa_id` AS `empresa_id`,
  `ordenado`.`filial_id` AS `filial_id`,
  `ordenado`.`filial_label` AS `filial_label`,
  `ordenado`.`serie` AS `serie`,
  `ordenado`.`numero_anterior` AS `numero_usado_anterior`,
  `ordenado`.`origem_anterior` AS `origem_usado_anterior`,
  `ordenado`.`origem_id_anterior` AS `origem_id_anterior`,
  `ordenado`.`data_doc_anterior` AS `data_doc_anterior`,
  `ordenado`.`chave_nfe_anterior` AS `chave_nfe_anterior`,
  `ordenado`.`status_nfe_anterior` AS `status_nfe_anterior`,
  (CASE WHEN ((`ordenado`.`numero_anterior` IS NOT NULL) AND
      ((`ordenado`.`chave_nfe_anterior` IS NULL) OR
      (`ordenado`.`chave_nfe_anterior` = ''))) THEN 'SIM' ELSE 'NÃO' END) AS `anterior_sem_chave`,
  `ordenado`.`numero` AS `numero_usado_atual`,
  `ordenado`.`origem` AS `origem_usado_atual`,
  `ordenado`.`origem_id` AS `origem_id_atual`,
  `ordenado`.`data_doc` AS `data_doc_atual`,
  `ordenado`.`chave_nfe` AS `chave_nfe_atual`,
  `ordenado`.`status_nfe` AS `status_nfe_atual`,
  (CASE WHEN ((`ordenado`.`numero` IS NOT NULL) AND
      ((`ordenado`.`chave_nfe` IS NULL) OR
      (`ordenado`.`chave_nfe` = ''))) THEN 'SIM' ELSE 'NÃO' END) AS `atual_sem_chave`,
  (`ordenado`.`numero_anterior` + 1) AS `numero_inicial_pulado`,
  (`ordenado`.`numero` - 1) AS `numero_final_pulado`,
  ((`ordenado`.`numero` - `ordenado`.`numero_anterior`) - 1) AS `quantidade_pulada`
FROM `ordenado`
WHERE ((`ordenado`.`numero_anterior` IS NOT NULL)
AND (`ordenado`.`numero` > (`ordenado`.`numero_anterior` + 1))
AND EXISTS (SELECT
    1
  FROM `nfe_inutilizacoes` `ni`
  WHERE ((`ni`.`empresa_id` = `ordenado`.`empresa_id`)
  AND (`ni`.`filial_id` <=> `ordenado`.`filial_id`)
  AND (`ni`.`serie` = `ordenado`.`serie`)
  AND (`ni`.`numero_inicial` <= (`ordenado`.`numero_anterior` + 1))
  AND (`ni`.`numero_final` >= (`ordenado`.`numero` - 1))
  AND (`ni`.`protocolo` IS NOT NULL)
  AND (`ni`.`protocolo` <> ''))) IS FALSE)
ORDER BY `ordenado`.`empresa_id`, `ordenado`.`filial_grp`, `ordenado`.`serie`, (`ordenado`.`numero_anterior` + 1);
SQL
        );
    }
};
