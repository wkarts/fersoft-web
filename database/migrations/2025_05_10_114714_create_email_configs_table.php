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
        Schema::create('email_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('email_configs_empresa_id_foreign');
            $table->string('host', 50);
            $table->string('email', 50);
            $table->string('senha', 50);
            $table->string('nome', 50);
            $table->string('porta', 10);
            $table->enum('cripitografia', ['ssl', 'tls']);
            $table->boolean('smtp_auth');
            $table->boolean('smtp_debug');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_configs');
    }
};
