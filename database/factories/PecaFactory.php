<?php

namespace Database\Factories;

use App\Enums\TipoPeca;
use App\Models\Peca;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Peças de EXEMPLO, só para testes. Nunca usar com fotografias reais.
 *
 * @extends Factory<Peca>
 */
class PecaFactory extends Factory
{
    protected $model = Peca::class;

    public function definition(): array
    {
        $ano = fake()->numberBetween(1900, 1990);

        return [
            'tipo' => TipoPeca::Foto,
            'titulo' => 'Peça de exemplo '.fake()->unique()->numberBetween(1, 99999),
            'descricao' => '<p>Descrição de exemplo para testes.</p>',
            'data_texto' => (string) $ano,
            'local' => 'Local de exemplo',
            'publicado' => true,
            'largura' => 800,
            'altura' => 600,
        ];
    }

    public function porPublicar(): static
    {
        return $this->state(['publicado' => false]);
    }

    public function tipo(TipoPeca $tipo): static
    {
        return $this->state(['tipo' => $tipo]);
    }
}
