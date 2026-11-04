<?php

namespace App\Console\Commands;

use App\Importacao\Importador;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('crevelhos:importar
    {--pasta= : Pasta com as imagens antigas (por omissão, CREVELHOS_LEGADO_PATH)}
    {--sem-ficheiros : Não copia imagens nem gera WebP/miniaturas (mais rápido)}
    {--forcar-textos : Substitui os textos das páginas mesmo que já tenham sido editados}')]
#[Description('Importa a base de dados antiga (conexão "legado") e gera o relatório de revisão')]
class ImportarCrevelhos extends Command
{
    public function handle(Importador $importador): int
    {
        $pasta = $this->option('pasta') ?: config('crevelhos.legado_path');

        try {
            DB::connection('legado')->getPdo();
        } catch (Throwable $e) {
            $this->error('Não consegui ligar à base de dados antiga (conexão "legado"): '.$e->getMessage());
            $this->line('Importe primeiro o crevelhos.sql para a base crevelhos_antigo (ver README).');

            return self::FAILURE;
        }

        if (! $this->option('sem-ficheiros') && ! is_dir($pasta)) {
            $this->error("A pasta dos ficheiros antigos não existe: {$pasta}");

            return self::FAILURE;
        }

        $this->info('A importar de '.DB::connection('legado')->getDatabaseName().'...');

        $relatorio = $importador
            ->aoProgredir(fn (string $msg) => $this->output->isVerbose() ? $this->line("  {$msg}") : null)
            ->executar($pasta, ! $this->option('sem-ficheiros'), (bool) $this->option('forcar-textos'));

        $caminho = storage_path('app/importacao/relatorio.md');
        $relatorio->guardar($caminho);

        $this->table(['', 'Total'], $relatorio->resumo());
        $this->info("Relatório: {$caminho}");

        return self::SUCCESS;
    }
}
