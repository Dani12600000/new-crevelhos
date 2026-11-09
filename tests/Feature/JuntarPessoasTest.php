<?php

namespace Tests\Feature;

use App\Enums\Papel;
use App\Filament\Resources\Pessoas\Pages\ListPessoas;
use App\Models\Marcacao;
use App\Models\Peca;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class JuntarPessoasTest extends TestCase
{
    use RefreshDatabase;

    private function marcacao(Pessoa $pessoa, int $numero = 1): Marcacao
    {
        return Marcacao::factory()->create([
            'peca_id' => Peca::factory()->create()->id,
            'numero' => $numero,
            'pessoa_id' => $pessoa->id,
        ]);
    }

    public function test_passa_as_marcacoes_junta_os_dados_e_apaga_a_duplicada(): void
    {
        $fica = Pessoa::factory()->create(['nome' => 'Exemplo Fica', 'alcunha' => 'Alcunha A', 'notas' => 'Nota de quem fica']);
        $duplicada = Pessoa::factory()->create(['nome' => 'Exemplo Duplicada', 'alcunha' => 'Alcunha B', 'notas' => 'Nota da duplicada']);
        $primeira = $this->marcacao($duplicada, 1);
        $segunda = $this->marcacao($duplicada, 2);
        $jaEra = $this->marcacao($fica, 3);

        $duplicada->juntarEm($fica);

        $this->assertModelMissing($duplicada);
        $this->assertSame([$fica->id, $fica->id, $fica->id], [$primeira->refresh()->pessoa_id, $segunda->refresh()->pessoa_id, $jaEra->refresh()->pessoa_id]);

        $fica->refresh();
        $this->assertSame('Alcunha A / Alcunha B', $fica->alcunha);
        $this->assertStringContainsString('Nota de quem fica', $fica->notas);
        $this->assertStringContainsString('Nota da duplicada', $fica->notas);
        $this->assertStringContainsString('Nome antigo: Exemplo Duplicada', $fica->notas);
    }

    public function test_alcunha_igual_nao_se_repete_e_alcunha_em_falta_e_herdada(): void
    {
        $fica = Pessoa::factory()->create(['alcunha' => 'Alcunha A']);
        Pessoa::factory()->create(['alcunha' => 'alcunha a '])->juntarEm($fica);
        $this->assertSame('Alcunha A', $fica->refresh()->alcunha);

        $semAlcunha = Pessoa::factory()->create(['alcunha' => null]);
        Pessoa::factory()->create(['alcunha' => 'Alcunha B'])->juntarEm($semAlcunha);
        $this->assertSame('Alcunha B', $semAlcunha->refresh()->alcunha);
    }

    public function test_alcunha_que_nao_cabe_vai_para_as_notas(): void
    {
        $fica = Pessoa::factory()->create(['alcunha' => str_repeat('a', 200)]);
        Pessoa::factory()->create(['alcunha' => str_repeat('b', 200)])->juntarEm($fica);

        $fica->refresh();
        $this->assertSame(str_repeat('a', 200), $fica->alcunha);
        $this->assertStringContainsString(str_repeat('b', 200), $fica->notas);
    }

    public function test_pessoa_escondida_continua_escondida_depois_de_juntar(): void
    {
        $fica = Pessoa::factory()->create(['oculta' => false]);
        Pessoa::factory()->create(['oculta' => true])->juntarEm($fica);

        $this->assertTrue($fica->refresh()->oculta);
    }

    public function test_erro_a_meio_nao_deixa_nada_feito(): void
    {
        $fica = Pessoa::factory()->create(['notas' => null]);
        $duplicada = Pessoa::factory()->create();
        $marcacao = $this->marcacao($duplicada);
        Pessoa::deleting(fn () => throw new RuntimeException('falha de exemplo'));

        try {
            $duplicada->juntarEm($fica);
            $this->fail('Devia ter lançado a exceção.');
        } catch (RuntimeException) {
            // esperado
        }

        $this->assertModelExists($duplicada);
        $this->assertSame($duplicada->id, $marcacao->refresh()->pessoa_id);
        $this->assertNull($fica->refresh()->notas);
    }

    public function test_nao_junta_uma_pessoa_consigo_propria(): void
    {
        $pessoa = Pessoa::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $pessoa->juntarEm($pessoa);
    }

    public function test_acao_na_lista_de_pessoas(): void
    {
        $this->actingAs(User::factory()->create(['papel' => Papel::Editor]));
        $fica = Pessoa::factory()->create();
        $duplicada = Pessoa::factory()->create();
        $marcacao = $this->marcacao($duplicada);

        Livewire::test(ListPessoas::class)
            ->callTableAction('juntar', $duplicada, ['pessoa_id' => $fica->id])
            ->assertHasNoTableActionErrors();

        $this->assertModelMissing($duplicada);
        $this->assertSame($fica->id, $marcacao->refresh()->pessoa_id);
    }
}
