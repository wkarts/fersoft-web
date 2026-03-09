<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mdfe_pagamento_componentes') && Schema::hasTable('mdfe_pagamentos')) {
            Schema::table('mdfe_pagamento_componentes', function (Blueprint $table) {
                $table->foreign(['mdfe_pagamento_id'], 'mdfe_pagamento_componentes_pagamento_id_foreign')
                    ->references(['id'])
                    ->on('mdfe_pagamentos')
                    ->onUpdate('no action')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mdfe_pagamento_componentes')) {
            Schema::table('mdfe_pagamento_componentes', function (Blueprint $table) {
                $table->dropForeign('mdfe_pagamento_componentes_pagamento_id_foreign');
            });
        }
    }
};
