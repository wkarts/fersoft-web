<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $this->inheritConnectApiPermission();
    }

    public function down()
    {
        $this->revertInheritedConnectApiPermission();
    }

    private function inheritConnectApiPermission(): void
    {
        if (!Schema::hasTable('empresas') || !Schema::hasColumn('empresas', 'permissao')) {
            return;
        }

        DB::table('empresas')
            ->select(['id', 'permissao'])
            ->whereNotNull('permissao')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $permissions = json_decode((string) $row->permissao, true);

                    if (!is_array($permissions)) {
                        continue;
                    }

                    $hasLegacy = in_array('/evoapi', $permissions, true)
                        || in_array('/evo-instances', $permissions, true);

                    if (!$hasLegacy || in_array('/connect-api', $permissions, true)) {
                        continue;
                    }

                    $permissions[] = '/connect-api';

                    DB::table('empresas')
                        ->where('id', $row->id)
                        ->update([
                            'permissao' => json_encode(
                                array_values(array_unique($permissions)),
                                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            ),
                        ]);
                }
            }, 'id');
    }

    private function revertInheritedConnectApiPermission(): void
    {
        if (!Schema::hasTable('empresas') || !Schema::hasColumn('empresas', 'permissao')) {
            return;
        }

        DB::table('empresas')
            ->select(['id', 'permissao'])
            ->whereNotNull('permissao')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $permissions = json_decode((string) $row->permissao, true);

                    if (!is_array($permissions)) {
                        continue;
                    }

                    $hasLegacy = in_array('/evoapi', $permissions, true)
                        || in_array('/evo-instances', $permissions, true);

                    if (!$hasLegacy || !in_array('/connect-api', $permissions, true)) {
                        continue;
                    }

                    $permissions = array_values(array_filter(
                        $permissions,
                        fn ($permission) => $permission !== '/connect-api'
                    ));

                    DB::table('empresas')
                        ->where('id', $row->id)
                        ->update([
                            'permissao' => json_encode(
                                $permissions,
                                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            ),
                        ]);
                }
            }, 'id');
    }
};
