<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets_pesagem', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id')->index();
            $table->unsignedInteger('usuario_id')->index();
            $table->unsignedInteger('filial_id')->nullable()->index('tickets_pesagem_filial_id_foreign');
            $table->unsignedBigInteger('pesagem_id')->index();
            $table->unsignedInteger('veiculo_id')->index();
            $table->unsignedInteger('motorista_id')->nullable()->index();
            $table->decimal('peso', 18, 6);
            $table->decimal('peso_bag', 10)->default(0);
            $table->unsignedInteger('produto_id')->nullable()->index();
            $table->enum('tipo', ['entrada', 'saida', 'avulsa'])->default('avulsa');
            $table->enum('status', ['em andamento', 'concluído'])->default('em andamento');
            $table->timestamp('inicio')->nullable();
            $table->timestamp('fim')->nullable();
            $table->text('observacoes')->nullable();
            $table->string('token', 100)->unique();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets_pesagem');
    }
};
