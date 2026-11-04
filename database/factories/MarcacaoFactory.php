<?php

namespace Database\Factories;

use App\Models\Marcacao;
use App\Models\Peca;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Marcacao>
 */
class MarcacaoFactory extends Factory
{
    protected $model = Marcacao::class;

    public function definition(): array
    {
        return [
            'peca_id' => Peca::factory(),
            'numero' => fn (array $atributos) => Marcacao::proximoNumero(Peca::find($atributos['peca_id'])),
            'x' => fake()->randomFloat(3, 5, 95),
            'y' => fake()->randomFloat(3, 5, 95),
        ];
    }
}
