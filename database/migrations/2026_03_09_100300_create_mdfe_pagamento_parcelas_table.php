<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mdfe_pagamento_parcelas')) {
            Schema::create('mdfe_pagamento_parcelas', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('mdfe_pagamento_id')->index('mdfe_pagamento_parcelas_pagamento_id_foreign');
                $table->string('numero_parcela', 20);
                $table->date('data_vencimento');
                $table->decimal('valor', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mdfe_pagamento_parcelas')) {
            Schema::dropIfExists('mdfe_pagamento_parcelas');
        }
    }
};
