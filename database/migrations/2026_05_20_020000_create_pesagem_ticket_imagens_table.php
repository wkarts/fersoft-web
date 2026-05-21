<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pesagem_ticket_imagens')) {
            Schema::create('pesagem_ticket_imagens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa_id')->index();
                $table->unsignedBigInteger('pesagem_id')->nullable()->index();
                $table->unsignedBigInteger('ticket_pesagem_id')->nullable()->index();
                $table->unsignedBigInteger('balanca_config_id')->nullable()->index();
                $table->unsignedBigInteger('adp_camera_id')->nullable()->index();
                $table->string('camera_uuid', 120)->nullable()->index();
                $table->string('camera_descricao', 180)->nullable();
                $table->unsignedInteger('ordem')->default(1);
                $table->string('arquivo_path', 600)->nullable();
                $table->string('thumb_path', 600)->nullable();
                $table->string('arquivo_url', 1000)->nullable();
                $table->string('mime_type', 80)->nullable();
                $table->unsignedBigInteger('tamanho_bytes')->nullable();
                $table->unsignedInteger('largura')->nullable();
                $table->unsignedInteger('altura')->nullable();
                $table->dateTime('capturado_em')->nullable();
                $table->json('metadata_json')->nullable();
                $table->boolean('ativo')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['empresa_id', 'pesagem_id']);
                $table->index(['empresa_id', 'ticket_pesagem_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pesagem_ticket_imagens');
    }
};
