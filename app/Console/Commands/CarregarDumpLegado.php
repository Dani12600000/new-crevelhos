<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Só para desenvolvimento: cria as bases `crevelhos` e `crevelhos_antigo`
 * (se não existirem) e carrega o crevelhos.sql antigo tal como está.
 * Num cPanel as bases criam-se no painel e o dump importa-se no phpMyAdmin.
 */
#[Signature('crevelhos:carregar-dump {ficheiro? : Caminho do crevelhos.sql (por omissão, na pasta CREVELHOS_LEGADO_PATH)}')]
#[Description('Cria as bases de dados locais e carrega o dump antigo para a conexão "legado"')]
class CarregarDumpLegado extends Command
{
    public function handle(): int
    {
        $ficheiro = $this->argument('ficheiro') ?: rtrim(config('crevelhos.legado_path'), '\\/').'/crevelhos.sql';
        if (! is_file($ficheiro)) {
            $this->error("Não encontrei o dump: {$ficheiro}");

            return self::FAILURE;
        }

        $novo = config('database.connections.mysql.database');
        $antigo = config('database.connections.legado.database');

        // Liga ao servidor sem escolher base, para as poder criar.
        config(['database.connections.servidor' => array_merge(config('database.connections.mysql'), ['database' => null])]);
        $servidor = DB::connection('servidor');

        $servidor->statement("CREATE DATABASE IF NOT EXISTS `{$novo}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->info("Base `{$novo}` pronta.");

        $servidor->statement("DROP DATABASE IF EXISTS `{$antigo}`");
        $servidor->statement("CREATE DATABASE `{$antigo}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        DB::purge('legado');
        DB::connection('legado')->unprepared(file_get_contents($ficheiro));

        $tabelas = collect(DB::connection('legado')->select('SHOW TABLES'))->map(fn ($t) => array_values((array) $t)[0]);
        $this->table(['Tabela', 'Registos'], $tabelas->map(fn ($t) => [$t, DB::connection('legado')->table($t)->count()]));
        $this->info("Dump carregado em `{$antigo}`.");

        return self::SUCCESS;
    }
}
