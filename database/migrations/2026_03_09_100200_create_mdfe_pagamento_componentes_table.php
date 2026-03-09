<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mdfe_pagamento_componentes')) {
            Schema::create('mdfe_pagamento_componentes', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('mdfe_pagamento_id')->index('mdfe_pagamento_componentes_pagamento_id_foreign');
                $table->string('tipo_componente', 2);
                $table->string('descricao', 60)->nullable();
                $table->decimal('valor', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mdfe_pagamento_componentes')) {
            Schema::dropIfExists('mdfe_pagamento_componentes');
        }
    }
};
