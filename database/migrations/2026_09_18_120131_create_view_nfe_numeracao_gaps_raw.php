<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MIGRATION_NAME = '2026_09_18_120131_create_view_nfe_numeracao_gaps_raw';
    private const VIEW_NAME = 'vw_nfe_numeracao_gaps_raw';

    public function up()
    {
        $this->withViewLock(function () {
            $record = $this->audit();
            $existing = $this->viewInfo();
            if ($record !== null) {
                if ($record['state'] === 'applied' && $existing == $record['after']) { return; }
                if ($record['state'] !== 'pending' || $existing !== null) {
                    throw new \RuntimeException('Criação de view interrompida ou alterada externamente. Não presumir ownership.');
                }
            }
            $sql = $this->createSql();
            if ($existing !== null) {
                $query = preg_replace('/^CREATE\s+ALGORITHM=\w+\s+SQL\s+SECURITY\s+INVOKER\s+VIEW\s+`[^`]+`\s+AS\s+/i', '', rtrim($sql, ';'));
                if ($existing['security_type'] !== 'INVOKER' || $existing['check_option'] !== 'NONE'
                    || $this->sqlTokens($existing['view_definition']) !== $this->sqlTokens($query)) {
                    throw new \RuntimeException('A view raw preexistente tem definição diferente (ou não comprovadamente equivalente). Preserve-a para revisão; este up não substitui views existentes.');
                }
                return; // Não pertence a esta migration; down não a removerá.
            }
            $collision = DB::selectOne('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [DB::getDatabaseName(), self::VIEW_NAME]);
            if ($collision !== null) { throw new \RuntimeException('O nome da view pertence a uma tabela.'); }
            foreach (['vendas'=>['empresa_id','filial_id','nSerie','NfNumero','id','data_emissao','chave','estado'],
                      'compras'=>['empresa_id','filial_id','numero_emissao','id','data_emissao','chave','estado'],
                      'config_notas'=>['empresa_id','numero_serie_nfe']] as $table => $columns) {
                if (!Schema::hasTable($table)) { throw new \RuntimeException('Dependência da view ausente: ' . $table); }
                foreach ($columns as $column) {
                    if (!Schema::hasColumn($table, $column)) { throw new \RuntimeException('Coluna da view ausente: ' . $table . '.' . $column); }
                }
            }
            $this->writeAudit(['state'=>'pending', 'before'=>null, 'after'=>null]);
            DB::unprepared($sql);
            $after = $this->viewInfo();
            if ($after === null) { throw new \RuntimeException('A view não foi criada.'); }
            $this->writeAudit(['state'=>'applied', 'before'=>null, 'after'=>$after]);
        });
    }

    public function down()
    {
        $this->withViewLock(function () {
            $record = $this->audit();
            if ($record === null) { return; }
            $existing = $this->viewInfo();
            if ($record['state'] === 'reverting' && $existing === null) { $this->forgetCreated(); return; }
            if (!in_array($record['state'], ['applied','reverting'], true) || $existing != $record['after'] || $existing === null) {
                throw new \RuntimeException('Rollback bloqueado: não há comprovação da definição criada por esta migration.');
            }
            $dependent = DB::selectOne('SELECT VIEW_NAME AS name FROM information_schema.VIEW_TABLE_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? LIMIT 1', [DB::getDatabaseName(), self::VIEW_NAME]);
            if ($dependent !== null) { throw new \RuntimeException('Reverta primeiro a view dependente: ' . $dependent->name); }
            $record['state'] = 'reverting'; $this->writeAudit($record);
            DB::statement('DROP VIEW ' . $this->quoteIdentifier(self::VIEW_NAME));
            $this->forgetCreated();
        });
    }

    private function createSql(): string
    {
        return <<<'SQL'
CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vw_nfe_numeracao_gaps_raw` AS WITH   `docs` as (select `v`.`empresa_id` AS `empresa_id`,`v`.`filial_id` AS `filial_id`,`v`.`nSerie` AS `serie`,`v`.`NfNumero` AS `numero`,'V' AS `origem`,`v`.`id` AS `origem_id`,`v`.`data_emissao` AS `data_doc`,`v`.`chave` AS `chave_nfe`,`v`.`estado` AS `status_nfe` from `vendas` `v` where ((`v`.`NfNumero` > 0) and (`v`.`nSerie` > 0)) union all select `c`.`empresa_id` AS `empresa_id`,`c`.`filial_id` AS `filial_id`,`cn`.`numero_serie_nfe` AS `serie`,`c`.`numero_emissao` AS `numero`,'C' AS `origem`,`c`.`id` AS `origem_id`,`c`.`data_emissao` AS `data_doc`,`c`.`chave` AS `chave_nfe`,`c`.`estado` AS `status_nfe` from (`compras` `c` left join `config_notas` `cn` on((`cn`.`empresa_id` = `c`.`empresa_id`))) where (`c`.`numero_emissao` > 0)), `ordenado` as (select `d`.`empresa_id` AS `empresa_id`,`d`.`filial_id` AS `filial_id`,`d`.`serie` AS `serie`,`d`.`numero` AS `numero`,`d`.`origem` AS `origem`,`d`.`origem_id` AS `origem_id`,`d`.`data_doc` AS `data_doc`,`d`.`chave_nfe` AS `chave_nfe`,`d`.`status_nfe` AS `status_nfe`,coalesce(`d`.`filial_id`,0) AS `filial_grp`,(case when (`d`.`filial_id` is null) then 'MATRIZ' else concat('FILIAL ',`d`.`filial_id`) end) AS `filial_label`,lag(`d`.`numero`) OVER (PARTITION BY `d`.`empresa_id`,coalesce(`d`.`filial_id`,0),`d`.`serie` ORDER BY `d`.`numero` )  AS `numero_anterior`,lag(`d`.`origem`) OVER (PARTITION BY `d`.`empresa_id`,coalesce(`d`.`filial_id`,0),`d`.`serie` ORDER BY `d`.`numero` )  AS `origem_anterior`,lag(`d`.`origem_id`) OVER (PARTITION BY `d`.`empresa_id`,coalesce(`d`.`filial_id`,0),`d`.`serie` ORDER BY `d`.`numero` )  AS `origem_id_anterior`,lag(`d`.`data_doc`) OVER (PARTITION BY `d`.`empresa_id`,coalesce(`d`.`filial_id`,0),`d`.`serie` ORDER BY `d`.`numero` )  AS `data_doc_anterior`,lag(`d`.`chave_nfe`) OVER (PARTITION BY `d`.`empresa_id`,coalesce(`d`.`filial_id`,0),`d`.`serie` ORDER BY `d`.`numero` )  AS `chave_nfe_anterior`,lag(`d`.`status_nfe`) OVER (PARTITION BY `d`.`empresa_id`,coalesce(`d`.`filial_id`,0),`d`.`serie` ORDER BY `d`.`numero` )  AS `status_nfe_anterior` from `docs` `d`) select `ordenado`.`empresa_id` AS `empresa_id`,`ordenado`.`filial_id` AS `filial_id`,`ordenado`.`filial_label` AS `filial_label`,`ordenado`.`serie` AS `serie`,`ordenado`.`numero_anterior` AS `numero_usado_anterior`,`ordenado`.`origem_anterior` AS `origem_usado_anterior`,`ordenado`.`origem_id_anterior` AS `origem_id_anterior`,`ordenado`.`data_doc_anterior` AS `data_doc_anterior`,`ordenado`.`chave_nfe_anterior` AS `chave_nfe_anterior`,`ordenado`.`status_nfe_anterior` AS `status_nfe_anterior`,(case when ((`ordenado`.`numero_anterior` is not null) and ((`ordenado`.`chave_nfe_anterior` is null) or (`ordenado`.`chave_nfe_anterior` = ''))) then 'SIM' else 'NÃO' end) AS `anterior_sem_chave`,`ordenado`.`numero` AS `numero_usado_atual`,`ordenado`.`origem` AS `origem_usado_atual`,`ordenado`.`origem_id` AS `origem_id_atual`,`ordenado`.`data_doc` AS `data_doc_atual`,`ordenado`.`chave_nfe` AS `chave_nfe_atual`,`ordenado`.`status_nfe` AS `status_nfe_atual`,(case when ((`ordenado`.`numero` is not null) and ((`ordenado`.`chave_nfe` is null) or (`ordenado`.`chave_nfe` = ''))) then 'SIM' else 'NÃO' end) AS `atual_sem_chave`,(`ordenado`.`numero_anterior` + 1) AS `numero_inicial_pulado`,(`ordenado`.`numero` - 1) AS `numero_final_pulado`,((`ordenado`.`numero` - `ordenado`.`numero_anterior`) - 1) AS `quantidade_pulada` from `ordenado` where ((`ordenado`.`numero_anterior` is not null) and (`ordenado`.`numero` > (`ordenado`.`numero_anterior` + 1))) order by `ordenado`.`empresa_id`,`ordenado`.`filial_grp`,`ordenado`.`serie`,(`ordenado`.`numero_anterior` + 1)  ;
SQL;
    }

    private function quoteIdentifier(string $name): string { return '`' . str_replace('`', '``', $name) . '`'; }

    private function viewInfo(): ?array
    {
        $row = DB::selectOne('SELECT VIEW_DEFINITION AS view_definition, CHECK_OPTION AS check_option, SECURITY_TYPE AS security_type, DEFINER AS definer, CHARACTER_SET_CLIENT AS character_set_client, COLLATION_CONNECTION AS collation_connection FROM information_schema.VIEWS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [DB::getDatabaseName(), self::VIEW_NAME]);
        return $row === null ? null : (array) $row;
    }

    private function sqlTokens(string $sql): array
    {
        preg_match_all('/\x27(?:[^\x27\\\\]|\\\\.|\x27\x27)*\x27|`(?:[^`]|``)*`|[a-zA-Z_][a-zA-Z0-9_]*|[0-9]+|<=>|<>|>=|<=|[^\s]/u', rtrim($sql, "; \r\n\t"), $matches);
        $tokens = [];
        $schema = $this->quoteIdentifier(DB::getDatabaseName());
        for ($i = 0; $i < count($matches[0]); $i++) {
            $token = $matches[0][$i];
            if ($token === $schema && ($matches[0][$i + 1] ?? null) === '.') { $i++; continue; }
            $tokens[] = ($token[0] === "'" || $token[0] === '`') ? $token : strtolower($token);
        }
        return $tokens; // Equivalência conservadora; não elimina filtros, parênteses ou literais.
    }

    private function audit(): ?array
    {
        $row = DB::table('migration_merge_audit')->where('migration_name', self::MIGRATION_NAME)->where('object_type','view')->where('object_name',self::VIEW_NAME)->first();
        if ($row === null) { return null; }
        if ($row->metadata_json === null) { throw new \RuntimeException('Auditoria da view sem metadados.'); }
        return json_decode($row->metadata_json, true, 512, JSON_THROW_ON_ERROR);
    }

    private function writeAudit(array $metadata): void
    {
        DB::table('migration_merge_audit')->updateOrInsert(
            ['migration_name'=>self::MIGRATION_NAME,'object_type'=>'view','object_name'=>self::VIEW_NAME],
            ['metadata_json'=>json_encode($metadata, JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),'created_at'=>now(),'updated_at'=>now()]
        );
    }

    private function forgetCreated(): void
    {
        DB::table('migration_merge_audit')->where('migration_name',self::MIGRATION_NAME)->where('object_type','view')->where('object_name',self::VIEW_NAME)->delete();
    }

    private function withViewLock(\Closure $operation): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') { throw new \RuntimeException('MySQL 8.0 é necessário.'); }
        $version = DB::selectOne('SELECT VERSION() AS version')->version;
        if (!preg_match('/^8\.0\./', $version) || stripos($version, 'mariadb') !== false) { throw new \RuntimeException('MySQL 8.0.x é necessário.'); }
        if (!Schema::hasTable('migration_merge_audit') || !Schema::hasColumn('migration_merge_audit','metadata_json')) { throw new \RuntimeException('Execute antes a migration de auditoria.'); }
        $lock = 'fersoft:ddl:' . substr(hash('sha256',DB::getDatabaseName()),0,40);
        $acquired = DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired',[$lock]);
        if ((int) ($acquired->acquired ?? 0) !== 1) { throw new \RuntimeException('Outra execução detém o lock.'); }
        try { $operation(); } finally { DB::selectOne('SELECT RELEASE_LOCK(?) AS released',[$lock]); }
    }
};
