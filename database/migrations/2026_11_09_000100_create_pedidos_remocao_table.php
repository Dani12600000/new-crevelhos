<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // RGPD: registo de cada pedido para retirar alguém do arquivo e do que se fez.
        Schema::create('pedidos_remocao', function (Blueprint $table) {
            $table->id();
            $table->string('requerente');
            $table->string('contacto')->nullable();
            $table->date('data_pedido');
            // O registo mantém-se mesmo que a ficha ou o número venham a ser apagados.
            $table->foreignId('pessoa_id')->nullable()->constrained('pessoas')->nullOnDelete();
            $table->foreignId('marcacao_id')->nullable()->constrained('marcacoes')->nullOnDelete();
            $table->text('pedido');
            $table->text('acao_tomada')->nullable();
            $table->date('data_resolucao')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_remocao');
    }
};
