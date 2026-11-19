<?php

namespace App\Console\Commands;

use App\Models\Peca;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Cria as marcações iniciais (só os números, sem pessoa) a partir de um ficheiro de dados.
 * Uma peça que já tenha marcações nunca é alterada, por isso pode correr várias vezes.
 */
#[Signature('crevelhos:marcar-caras {--ficheiro= : Ficheiro de dados (por defeito database/data/marcacoes_iniciais.php)}')]
#[Description('Marca as caras das fotografias que ainda não têm marcações, sem identificar ninguém')]
class MarcarCaras extends Command
{
    public function handle(): int
    {
        $ficheiro = $this->option('ficheiro') ?: database_path('data/marcacoes_iniciais.php');

        if (! is_file($ficheiro)) {
            $this->error("Ficheiro não encontrado: {$ficheiro}");

            return self::FAILURE;
        }

        /** @var array<string, list<array{0: float, 1: float, 2: float}>> $dados */
        $dados = require $ficheiro;

        $marcadas = 0;
        $criadas = 0;

        foreach ($dados as $ref => $caras) {
            $peca = Peca::where('ref', $ref)->first();

            if (! $peca) {
                $this->warn("{$ref}: peça não encontrada");

                continue;
            }

            if ($peca->marcacoes()->exists()) {
                $this->line("{$ref}: já tem marcações, fica como está");

                continue;
            }

            DB::transaction(function () use ($peca, $caras) {
                foreach (array_values($caras) as $i => [$x, $y, $raio]) {
                    $peca->marcacoes()->create([
                        'numero' => $i + 1,
                        'x' => $x,
                        'y' => $y,
                        'raio' => $raio,
                    ]);
                }
            });

            $marcadas++;
            $criadas += count($caras);
        }

        $this->info("Fotografias marcadas: {$marcadas}. Números criados: {$criadas}.");

        return self::SUCCESS;
    }
}
