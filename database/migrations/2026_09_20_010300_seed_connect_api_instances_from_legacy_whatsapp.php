<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('connect_api_instances') || !Schema::hasTable('evo_api_instances')) {
            return;
        }

        $legacy = DB::table('evo_api_instances');

        if (Schema::hasColumn('evo_api_instances', 'deleted_at')) {
            $legacy->whereNull('deleted_at');
        }

        $legacy
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    if (DB::table('connect_api_instances')->where('empresa_id', $row->empresa_id)->exists()) {
                        continue;
                    }

                    DB::table('connect_api_instances')->insert([
                        'empresa_id' => $row->empresa_id,
                        'usuario_id' => $row->usuario_id ?? null,
                        'filial_id' => $row->filial_id ?? null,
                        'instance_name' => $row->name,
                        'remote_instance_id' => null,
                        'instance_token' => null,
                        'connection_status' => 'awaiting_provisioning',
                        'is_blocked' => (bool) ($row->is_blocked ?? false),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }, 'id');
    }

    public function down()
    {
        // Migração de transição somente evolutiva.
        // Não remove registros Connect|API para evitar perda de pareamentos posteriores.
        return;
    }
};
