<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { if (!Schema::hasTable('balanca_configs')) return; Schema::table('balanca_configs', function (Blueprint $t) {
  foreach(['driver','integrador','backend_server_address','porta_serial','adp_scale_uuid','connection_token','connection_token_type','connection_token_header','adp_camera_uuids'] as $c){ if(!Schema::hasColumn('balanca_configs',$c)) $t->string($c)->nullable(); }
  foreach(['baud_rate','timeout_ms','read_interval_ms','quantidade_cameras'] as $c){ if(!Schema::hasColumn('balanca_configs',$c)) $t->integer($c)->nullable(); }
  foreach(['connection_token_enabled','usa_cameras','captura_snapshot_automatica','snapshot_retorno_base64','snapshot_baixa_visibilidade','exigir_peso_estavel'] as $c){ if(!Schema::hasColumn('balanca_configs',$c)) $t->boolean($c)->default(false); }
 }); }
 public function down(): void {}
};
