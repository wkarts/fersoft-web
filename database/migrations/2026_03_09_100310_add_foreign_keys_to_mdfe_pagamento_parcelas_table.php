<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mdfe_pagamento_parcelas') && Schema::hasTable('mdfe_pagamentos')) {
            Schema::table('mdfe_pagamento_parcelas', function (Blueprint $table) {
                $table->foreign(['mdfe_pagamento_id'], 'mdfe_pagamento_parcelas_pagamento_id_foreign')
                    ->references(['id'])
                    ->on('mdfe_pagamentos')
                    ->onUpdate('no action')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mdfe_pagamento_parcelas')) {
            Schema::table('mdfe_pagamento_parcelas', function (Blueprint $table) {
                $table->dropForeign('mdfe_pagamento_parcelas_pagamento_id_foreign');
            });
        }
    }
};
