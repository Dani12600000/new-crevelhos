<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pessoas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('alcunha')->nullable();
            $table->text('notas')->nullable(); // internas
            $table->boolean('oculta')->default(false); // RGPD: esconder sem apagar
            $table->timestamps();
        });

        // Cada número (①②③) desenhado por cima de uma fotografia.
        Schema::create('marcacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peca_id')->constrained('pecas')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->decimal('x', 6, 3); // % da largura
            $table->decimal('y', 6, 3); // % da altura
            $table->decimal('raio', 5, 2)->default(6); // % da largura, para o recorte da cara
            $table->foreignId('pessoa_id')->nullable()->constrained('pessoas')->nullOnDelete();
            $table->boolean('oculta')->default(false); // RGPD
            $table->timestamps();
            $table->unique(['peca_id', 'numero']);
        });

        Schema::create('sugestoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marcacao_id')->constrained('marcacoes')->cascadeOnDelete();
            $table->string('nome_sugerido');
            $table->string('fonte', 20)->default('folha'); // folha, email, conversa, outra
            $table->date('data')->nullable();
            $table->string('estado', 20)->default('pendente')->index(); // pendente, aceite, rejeitada
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sugestoes');
        Schema::dropIfExists('marcacoes');
        Schema::dropIfExists('pessoas');
    }
};
