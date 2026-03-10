<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mdves')) {
            Schema::table('mdves', function (Blueprint $table) {
                if (!Schema::hasColumn('mdves', 'ind_pagamento')) {
                    $table->string('ind_pagamento', 1)->nullable()->after('tp_transp');
                }

                if (!Schema::hasColumn('mdves', 'valor_contrato_pagamento')) {
                    $table->decimal('valor_contrato_pagamento', 15, 2)->nullable()->after('ind_pagamento');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mdves')) {
            Schema::table('mdves', function (Blueprint $table) {
                if (Schema::hasColumn('mdves', 'valor_contrato_pagamento')) {
                    $table->dropColumn('valor_contrato_pagamento');
                }

                if (Schema::hasColumn('mdves', 'ind_pagamento')) {
                    $table->dropColumn('ind_pagamento');
                }
            });
        }
    }
};
