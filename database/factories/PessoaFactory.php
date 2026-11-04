<?php

namespace Database\Factories;

use App\Models\Pessoa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Pessoas de EXEMPLO, só para testes: nunca nomes inventados em fotografias reais.
 *
 * @extends Factory<Pessoa>
 */
class PessoaFactory extends Factory
{
    protected $model = Pessoa::class;

    public function definition(): array
    {
        return [
            'nome' => 'Pessoa de exemplo '.fake()->unique()->numberBetween(1, 99999),
        ];
    }

    public function oculta(): static
    {
        return $this->state(['oculta' => true]);
    }
}
