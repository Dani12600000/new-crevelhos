<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Memórias transcritas da folha do café (ou recebidas por email, conversa...).
        Schema::create('comentarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peca_id')->constrained('pecas')->cascadeOnDelete();
            $table->string('autor')->nullable();
            $table->text('texto');
            $table->string('origem', 20)->default('folha'); // folha, email, conversa, outra
            $table->date('data')->nullable();
            $table->string('foto_folha')->nullable();
            $table->boolean('publicado')->default(false)->index();
            $table->timestamps();
        });

        // Textos editáveis do site (início, sobre, intro das secções, rodapé...).
        Schema::create('textos', function (Blueprint $table) {
            $table->id();
            $table->string('chave')->unique();
            $table->longText('valor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('textos');
        Schema::dropIfExists('comentarios');
    }
};
