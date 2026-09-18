<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traccar_webhook_events', function (Blueprint $table) {
            $table->id();

            // Chave SHA-256 gerada a partir do evento/dispositivo.
            // É a barreira definitiva contra entrega duplicada do Traccar.
            $table->char('dedupe_key', 64)->unique('traccar_webhook_dedupe_uq');

            $table->unsignedBigInteger('traccar_event_id')->nullable()->index();
            $table->unsignedBigInteger('empresa_id')->nullable()->index();
            $table->unsignedBigInteger('movimentacao_id')->nullable()->index();

            $table->string('etapa', 40)->nullable();
            $table->string('event_type', 80)->nullable();
            $table->string('device_id', 100)->nullable();
            $table->string('unique_id', 191)->nullable();
            $table->string('position_id', 100)->nullable();
            $table->string('geofence_id', 100)->nullable();

            $table->dateTime('event_time')->nullable()->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('speed', 12, 4)->nullable();

            $table->string('status', 30)->default('recebido')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->dateTime('next_retry_at')->nullable()->index();
            $table->dateTime('locked_at')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->text('last_error')->nullable();

            // longText em vez de JSON para manter compatibilidade com
            // instalações MySQL/MariaDB antigas; o Model faz cast para array.
            $table->longText('payload');

            $table->timestamps();

            $table->index(
                ['movimentacao_id', 'etapa', 'status', 'event_time'],
                'traccar_webhook_mov_etapa_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traccar_webhook_events');
    }
};
