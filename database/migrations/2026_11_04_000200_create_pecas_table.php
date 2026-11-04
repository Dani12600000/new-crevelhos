<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pecas', function (Blueprint $table) {
            $table->id();
            $table->string('ref', 20)->unique(); // ex.: CRV-F-0001
            $table->string('tipo', 20)->index(); // App\Enums\TipoPeca
            $table->string('titulo')->nullable();
            $table->text('descricao')->nullable(); // HTML limpo
            $table->string('data_texto')->nullable();
            $table->unsignedSmallInteger('decada')->nullable()->index();
            $table->string('autor')->nullable();
            $table->string('cedido_por')->nullable();
            $table->string('local')->nullable();
            $table->foreignId('acervo_id')->nullable()->constrained('acervos')->nullOnDelete();

            // Ficheiro original e dados calculados no upload
            $table->string('ficheiro')->nullable();
            $table->char('ficheiro_hash', 40)->nullable()->index(); // SHA-1
            $table->unsignedInteger('largura')->nullable();
            $table->unsignedInteger('altura')->nullable();
            $table->string('video_url')->nullable();

            // Só imprensa
            $table->string('jornal')->nullable();
            $table->date('data_publicacao')->nullable();
            $table->string('url_original', 500)->nullable();

            // Só livros (biblioteca)
            $table->string('edicao')->nullable();
            $table->string('editora')->nullable();
            $table->string('data_edicao')->nullable();
            $table->string('paginas')->nullable();

            $table->text('notas_internas')->nullable(); // nunca aparece no site
            $table->boolean('publicado')->default(false)->index();
            $table->boolean('destaque')->default(false);

            $table->string('legado_tabela', 30)->nullable();
            $table->unsignedInteger('legado_id')->nullable();
            $table->unique(['legado_tabela', 'legado_id']);

            $table->timestamps();
        });

        Schema::create('peca_tema', function (Blueprint $table) {
            $table->foreignId('peca_id')->constrained('pecas')->cascadeOnDelete();
            $table->foreignId('tema_id')->constrained('temas')->cascadeOnDelete();
            $table->primary(['peca_id', 'tema_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peca_tema');
        Schema::dropIfExists('pecas');
    }
};
