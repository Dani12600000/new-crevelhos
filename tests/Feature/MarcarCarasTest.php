<?php

namespace Tests\Feature;

use App\Models\Marcacao;
use App\Models\Peca;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Comando crevelhos:marcar-caras. Só peças de exemplo. */
class MarcarCarasTest extends TestCase
{
    use RefreshDatabase;

    private function ficheiroDados(array $dados): string
    {
        $caminho = tempnam(sys_get_temp_dir(), 'caras').'.php';
        file_put_contents($caminho, '<?php return '.var_export($dados, true).';');

        return $caminho;
    }

    public function test_marca_so_as_pecas_sem_marcacoes_e_sem_identificar_ninguem(): void
    {
        $vazia = Peca::factory()->create();
        $jaMarcada = Peca::factory()->create();
        $existente = Marcacao::factory()->for($jaMarcada)->create(['x' => 10, 'y' => 10]);

        $ficheiro = $this->ficheiroDados([
            $vazia->ref => [[20.0, 30.0, 4.0], [60.5, 35.2, 4.0]],
            $jaMarcada->ref => [[50.0, 50.0, 5.0]],
            'CRV-F-9999' => [[1.0, 1.0, 2.0]],
        ]);

        $this->artisan('crevelhos:marcar-caras', ['--ficheiro' => $ficheiro])->assertSuccessful();

        $marcacoes = $vazia->marcacoes()->orderBy('numero')->get();
        $this->assertSame([1, 2], $marcacoes->pluck('numero')->all());
        $this->assertEqualsWithDelta(60.5, $marcacoes[1]->x, 0.001);
        $this->assertTrue($marcacoes->every(fn ($m) => $m->pessoa_id === null && ! $m->oculta));

        // A que já tinha marcações fica igual.
        $this->assertSame([$existente->id], $jaMarcada->marcacoes()->pluck('id')->all());

        // Correr outra vez não duplica nada.
        $this->artisan('crevelhos:marcar-caras', ['--ficheiro' => $ficheiro])->assertSuccessful();
        $this->assertSame(3, Marcacao::count());
    }

    public function test_ficheiro_de_dados_real_e_valido(): void
    {
        $dados = require database_path('data/marcacoes_iniciais.php');

        $this->assertNotEmpty($dados);
        foreach ($dados as $ref => $caras) {
            $this->assertMatchesRegularExpression('/^CRV-F-\d{4}$/', $ref);
            $this->assertNotEmpty($caras, $ref);
            foreach ($caras as [$x, $y, $raio]) {
                $this->assertTrue($x >= 0 && $x <= 100 && $y >= 0 && $y <= 100, "{$ref}: posição fora da imagem");
                $this->assertGreaterThan(0, $raio);
            }
        }
    }
}
