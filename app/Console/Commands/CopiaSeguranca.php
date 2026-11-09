<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use ZipArchive;

/**
 * Cópia de segurança para alojamento partilhado: base de dados em SQL + ficheiros
 * (imagens das peças e fotos das folhas) num .zip em storage/app/copias/.
 * Não precisa de mysqldump: o SQL é escrito em PHP. Corre todas as noites pelo cron.
 */
#[Signature('crevelhos:copia-seguranca {--manter=14 : Quantas cópias guardar}')]
#[Description('Faz uma cópia de segurança da base de dados e dos ficheiros')]
class CopiaSeguranca extends Command
{
    public function handle(): int
    {
        $pasta = storage_path('app/copias');
        if (! is_dir($pasta)) {
            mkdir($pasta, 0775, true);
        }

        $nome = 'crevelhos-'.now()->format('Y-m-d-His');
        $zipCaminho = "{$pasta}/{$nome}.zip";

        $zip = new ZipArchive;
        if ($zip->open($zipCaminho, ZipArchive::CREATE) !== true) {
            throw new RuntimeException("Não consegui criar {$zipCaminho}");
        }

        $sql = "{$pasta}/{$nome}.sql";
        $this->escreverSql($sql);
        $zip->addFile($sql, 'base-de-dados.sql');

        $total = 0;
        foreach (['app/public/pecas' => 'ficheiros/pecas', 'app/private/folhas' => 'ficheiros/folhas'] as $origem => $destino) {
            $total += $this->juntarPasta($zip, storage_path($origem), $destino);
        }

        $zip->close();
        unlink($sql);

        $this->apagarAntigas($pasta, (int) $this->option('manter'));
        $this->info(sprintf('Cópia criada: %s (%d ficheiros, %.1f MB)', $zipCaminho, $total, filesize($zipCaminho) / 1048576));

        return self::SUCCESS;
    }

    private function escreverSql(string $caminho): void
    {
        $db = DB::connection();
        $pdo = $db->getPdo();
        $f = fopen($caminho, 'w');
        fwrite($f, "-- Cópia de segurança de {$db->getDatabaseName()} em ".now()->toDateTimeString()."\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($db->getSchemaBuilder()->getTableListing(schema: $db->getDatabaseName(), schemaQualified: false) as $tabela) {
            $criar = (array) $db->selectOne("SHOW CREATE TABLE `{$tabela}`");
            fwrite($f, "DROP TABLE IF EXISTS `{$tabela}`;\n".array_values($criar)[1].";\n\n");

            foreach ($db->table($tabela)->cursor() as $linha) {
                $valores = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), (array) $linha);
                fwrite($f, "INSERT INTO `{$tabela}` VALUES (".implode(',', $valores).");\n");
            }
            fwrite($f, "\n");
        }

        fwrite($f, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($f);
    }

    private function juntarPasta(ZipArchive $zip, string $origem, string $destino): int
    {
        if (! is_dir($origem)) {
            return 0;
        }

        $n = 0;
        $ficheiros = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($origem, \FilesystemIterator::SKIP_DOTS));
        foreach ($ficheiros as $ficheiro) {
            $relativo = str_replace('\\', '/', substr($ficheiro->getPathname(), strlen($origem) + 1));
            // As versões geradas voltam a fazer-se a partir do original: não vão na cópia.
            if (! str_ends_with($relativo, '.webp') && basename($relativo) !== 'partilha.jpg') {
                $zip->addFile($ficheiro->getPathname(), "{$destino}/{$relativo}");
                $n++;
            }
        }

        return $n;
    }

    private function apagarAntigas(string $pasta, int $manter): void
    {
        $copias = glob("{$pasta}/crevelhos-*.zip");
        rsort($copias);
        foreach (array_slice($copias, max(1, $manter)) as $antiga) {
            unlink($antiga);
        }
    }
}
