<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { if (!Schema::hasTable('balanca_config_cameras')) { Schema::create('balanca_config_cameras', function (Blueprint $t) { $t->id(); $t->unsignedInteger('empresa_id')->index(); $t->unsignedInteger('balanca_config_id')->index(); $t->string('camera_uuid'); $t->string('camera_nome')->nullable(); $t->string('camera_driver')->nullable(); $t->string('camera_protocol')->nullable(); $t->integer('ordem')->default(1); $t->boolean('ativo')->default(true); $t->json('metadata_json')->nullable(); $t->softDeletes(); $t->timestamps(); }); } }
 public function down(): void { if (Schema::hasTable('balanca_config_cameras')) Schema::drop('balanca_config_cameras'); }
};
