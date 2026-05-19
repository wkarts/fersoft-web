<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { if (!Schema::hasTable('adp_integrador_configs')) { Schema::create('adp_integrador_configs', function (Blueprint $t) { $t->id(); $t->unsignedInteger('empresa_id')->index(); $t->string('descricao')->nullable(); $t->string('base_url')->nullable(); $t->text('global_token')->nullable(); $t->boolean('global_token_enabled')->default(false); $t->string('global_token_type')->default('none'); $t->string('global_token_header')->nullable(); $t->integer('timeout_ms')->default(5000); $t->boolean('ativo')->default(true); $t->softDeletes(); $t->timestamps(); }); } }
 public function down(): void { if (Schema::hasTable('adp_integrador_configs')) Schema::drop('adp_integrador_configs'); }
};
