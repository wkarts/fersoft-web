<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mdfe_pagamentos')) {
            Schema::create('mdfe_pagamentos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('mdfe_id')->index('mdfe_pagamentos_mdfe_id_foreign');
                $table->string('tipo_doc_pagador', 4)->nullable();
                $table->string('cpf_cnpj_pagador', 18)->nullable();
                $table->string('nome_pagador', 60)->nullable();
                $table->string('forma_pagamento', 2)->nullable();
                $table->decimal('valor_pagamento', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mdfe_pagamentos')) {
            Schema::dropIfExists('mdfe_pagamentos');
        }
    }
};
