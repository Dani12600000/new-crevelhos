<?php

namespace App\Console\Commands;

use App\Models\Peca;
use App\Services\ImagensPeca;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/** Refaz as versões de todas as peças a partir dos originais, que nunca são alterados. */
#[Signature('crevelhos:regenerar-imagens')]
#[Description('Refaz as versões geradas das imagens de todas as peças')]
class RegenerarImagens extends Command
{
    public function handle(ImagensPeca $imagens): int
    {
        $refeitas = 0;
        $problemas = 0;

        foreach (Peca::whereNotNull('ficheiro')->orderBy('ref')->cursor() as $peca) {
            if (! Storage::disk('public')->exists($peca->ficheiro)) {
                $this->warn("{$peca->ref}: ficheiro em falta ({$peca->ficheiro})");
                $problemas++;

                continue;
            }

            try {
                $imagens->gerarVersoes($peca);
                $peca->saveQuietly();
                $refeitas++;
            } catch (\Throwable $e) {
                $this->warn("{$peca->ref}: {$e->getMessage()}");
                $problemas++;
            }
        }

        $this->info("Peças refeitas: {$refeitas}. Com problemas: {$problemas}.");

        return $problemas === 0 ? self::SUCCESS : self::FAILURE;
    }
}
