<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pesagem_ticket_imagens')) {
            return;
        }

        $indexes = $this->indexes('pesagem_ticket_imagens');

        if (!in_array('pti_ticket_ativo_deleted_id_idx', $indexes, true)) {
            DB::statement('CREATE INDEX pti_ticket_ativo_deleted_id_idx ON pesagem_ticket_imagens (ticket_pesagem_id, ativo, deleted_at, id)');
        }

        if (!in_array('pti_empresa_pesagem_ticket_idx', $indexes, true)) {
            DB::statement('CREATE INDEX pti_empresa_pesagem_ticket_idx ON pesagem_ticket_imagens (empresa_id, pesagem_id, ticket_pesagem_id)');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('pesagem_ticket_imagens')) {
            return;
        }

        $indexes = $this->indexes('pesagem_ticket_imagens');

        if (in_array('pti_ticket_ativo_deleted_id_idx', $indexes, true)) {
            DB::statement('DROP INDEX pti_ticket_ativo_deleted_id_idx ON pesagem_ticket_imagens');
        }

        if (in_array('pti_empresa_pesagem_ticket_idx', $indexes, true)) {
            DB::statement('DROP INDEX pti_empresa_pesagem_ticket_idx ON pesagem_ticket_imagens');
        }
    }

    private function indexes(string $table): array
    {
        $database = DB::getDatabaseName();
        return collect(DB::select(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$database, $table]
        ))->pluck('INDEX_NAME')->map(fn ($name) => (string) $name)->values()->all();
    }
};
