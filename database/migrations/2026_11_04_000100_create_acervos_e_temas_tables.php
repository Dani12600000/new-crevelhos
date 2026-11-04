<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acervos', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->timestamps();
        });

        // Um conjunto único de temas para todo o site.
        Schema::create('temas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Mapa dos temas antigos (tabela `temas` do site de 2017) para os novos.
        // Serve ao importador e ao redirecionamento de ?categoria=48.
        Schema::create('temas_legado', function (Blueprint $table) {
            $table->unsignedInteger('legado_id')->primary();
            $table->foreignId('tema_id')->constrained('temas')->cascadeOnDelete();
            $table->string('nome_original');
            $table->unsignedTinyInteger('destino');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temas_legado');
        Schema::dropIfExists('temas');
        Schema::dropIfExists('acervos');
    }
};
