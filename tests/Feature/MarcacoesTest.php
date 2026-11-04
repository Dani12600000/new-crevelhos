<?php

namespace Tests\Feature;

use App\Enums\EstadoSugestao;
use App\Enums\Origem;
use App\Models\Marcacao;
use App\Models\Peca;
use App\Models\Pessoa;
use App\Models\Sugestao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Marcações, sugestões e as regras de RGPD. Só dados de exemplo. */
class MarcacoesTest extends TestCase
{
    use RefreshDatabase;

    public function test_numeros_sao_sequenciais_por_peca_e_nao_se_reaproveitam(): void
    {
        $peca = Peca::factory()->create();
        $um = Marcacao::factory()->for($peca)->create();
        $dois = Marcacao::factory()->for($peca)->create();
        $this->assertSame([1, 2], [$um->numero, $dois->numero]);

        // Remover o 1 não muda o 2 (a folha do café pode já estar impressa).
        $um->delete();
        $this->assertSame(3, Marcacao::proximoNumero($peca));

        $outra = Peca::factory()->create();
        $this->assertSame(1, Marcacao::proximoNumero($outra));
    }

    public function test_aceitar_sugestao_cria_pessoa_e_rejeita_as_outras(): void
    {
        $marcacao = Marcacao::factory()->create();
        $boa = $marcacao->sugestoes()->create(['nome_sugerido' => 'Pessoa de exemplo A', 'fonte' => Origem::Folha]);
        $outra = $marcacao->sugestoes()->create(['nome_sugerido' => 'Pessoa de exemplo B', 'fonte' => Origem::Email]);

        $this->assertTrue($marcacao->temSugestoesEmConflito());

        $pessoa = $boa->aceitar();

        $this->assertSame('Pessoa de exemplo A', $pessoa->nome);
        $this->assertSame($pessoa->id, $marcacao->fresh()->pessoa_id);
        $this->assertSame(EstadoSugestao::Aceite, $boa->fresh()->estado);
        $this->assertSame(EstadoSugestao::Rejeitada, $outra->fresh()->estado);
        $this->assertFalse($marcacao->fresh()->temSugestoesEmConflito());
    }

    public function test_aceitar_sugestao_pode_ligar_a_pessoa_existente(): void
    {
        $existente = Pessoa::factory()->create();
        $marcacao = Marcacao::factory()->create();
        $sugestao = $marcacao->sugestoes()->create(['nome_sugerido' => 'nome com gralha', 'fonte' => Origem::Conversa]);

        $sugestao->aceitar($existente);

        $this->assertSame($existente->id, $marcacao->fresh()->pessoa_id);
        $this->assertSame(1, Pessoa::count());
    }

    public function test_sugestoes_iguais_nao_sao_conflito(): void
    {
        $marcacao = Marcacao::factory()->create();
        $marcacao->sugestoes()->createMany([
            ['nome_sugerido' => 'Pessoa de exemplo', 'fonte' => Origem::Folha],
            ['nome_sugerido' => ' pessoa de EXEMPLO ', 'fonte' => Origem::Folha],
        ]);

        $this->assertFalse($marcacao->temSugestoesEmConflito());
    }

    public function test_marcacao_ou_pessoa_ocultas_nao_aparecem_no_site(): void
    {
        $peca = Peca::factory()->create();
        $visivel = Marcacao::factory()->for($peca)->for(Pessoa::factory())->create();
        Marcacao::factory()->for($peca)->create(['oculta' => true]);
        $pessoaOculta = Pessoa::factory()->oculta()->create();
        Marcacao::factory()->for($peca)->for($pessoaOculta)->create();
        $porIdentificar = Marcacao::factory()->for($peca)->create();

        $this->assertEqualsCanonicalizing([$visivel->id, $porIdentificar->id], Marcacao::publicas()->pluck('id')->all());

        $this->get(route('peca', $peca))
            ->assertOk()
            ->assertSee($visivel->pessoa->nome)
            ->assertDontSee($pessoaOculta->nome);

        $this->get(route('pessoa', $pessoaOculta))->assertNotFound();
    }

    public function test_pagina_da_pessoa_mostra_as_aparicoes(): void
    {
        $pessoa = Pessoa::factory()->create();
        $pecas = Peca::factory()->count(2)->create();
        foreach ($pecas as $peca) {
            Marcacao::factory()->for($peca)->for($pessoa)->create();
        }
        Marcacao::factory()->for(Peca::factory()->porPublicar())->for($pessoa)->create();

        $this->get(route('pessoa', $pessoa))
            ->assertOk()
            ->assertSee('Aparece em <strong>2</strong> peças', false)
            ->assertSee($pecas[0]->ref);
    }

    public function test_quem_e_quem_lista_os_numeros_por_identificar(): void
    {
        Storage::fake('public');
        $peca = Peca::factory()->create(['ficheiro' => 'pecas/exemplo/original.jpg']);
        Marcacao::factory()->for($peca)->create();
        Marcacao::factory()->for($peca)->for(Pessoa::factory())->create();

        $this->get(route('quem-e-quem'))
            ->assertOk()
            ->assertSee('Número 1')
            ->assertDontSee('Número 2');
    }

    public function test_slug_da_pessoa_e_unico(): void
    {
        $a = Pessoa::create(['nome' => 'Pessoa de Exemplo']);
        $b = Pessoa::create(['nome' => 'Pessoa de Exemplo']);

        $this->assertSame('pessoa-de-exemplo', $a->slug);
        $this->assertSame('pessoa-de-exemplo-2', $b->slug);
    }

    public function test_sugestao_tem_valores_por_omissao(): void
    {
        $sugestao = Sugestao::create(['marcacao_id' => Marcacao::factory()->create()->id, 'nome_sugerido' => 'Exemplo']);

        $this->assertSame(EstadoSugestao::Pendente, $sugestao->fresh()->estado);
        $this->assertSame(Origem::Folha, $sugestao->fresh()->fonte);
    }
}
