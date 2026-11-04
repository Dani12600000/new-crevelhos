<?php

namespace Tests\Feature;

use App\Enums\TipoPeca;
use App\Models\Marcacao;
use App\Models\Peca;
use App\Models\Pessoa;
use App\Models\Tema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesquisaTest extends TestCase
{
    use RefreshDatabase;

    public function test_procura_no_titulo_e_na_descricao(): void
    {
        $sim = Peca::factory()->create(['titulo' => 'Festa de exemplo no largo']);
        $nao = Peca::factory()->create(['titulo' => 'Outra coisa']);

        $this->get(route('arquivo', ['q' => 'largo']))
            ->assertOk()
            ->assertSee($sim->ref)
            ->assertDontSee($nao->ref);
    }

    public function test_procura_nos_nomes_das_pessoas_visiveis(): void
    {
        $peca = Peca::factory()->create();
        Marcacao::factory()->for($peca)->for(Pessoa::factory()->state(['nome' => 'Exemplonia Testalves']))->create();
        $escondida = Peca::factory()->create();
        Marcacao::factory()->for($escondida)->for(Pessoa::factory()->oculta()->state(['nome' => 'Ocultina Testalves']))->create();

        $this->get(route('arquivo', ['q' => 'Exemplonia']))->assertSee($peca->ref);
        $this->get(route('arquivo', ['q' => 'Ocultina']))->assertDontSee($escondida->ref);
    }

    public function test_filtra_por_tipo_decada_e_tema(): void
    {
        $tema = Tema::create(['nome' => 'Tema de exemplo']);
        $foto50 = Peca::factory()->create(['data_texto' => '1955']);
        $foto50->temas()->attach($tema);
        $foto60 = Peca::factory()->create(['data_texto' => '1962']);
        $cartaz = Peca::factory()->tipo(TipoPeca::Cartaz)->create(['data_texto' => '1957']);

        $this->get(route('arquivo', ['decada' => 1950]))->assertSee($foto50->ref)->assertSee($cartaz->ref)->assertDontSee($foto60->ref);
        $this->get(route('arquivo', ['tipo' => 'cartaz']))->assertSee($cartaz->ref)->assertDontSee($foto50->ref);
        $this->get(route('arquivo', ['tema' => $tema->slug]))->assertSee($foto50->ref)->assertDontSee($cartaz->ref);
        $this->get(route('seccao', ['seccao' => 'cartazes']))->assertSee($cartaz->ref)->assertDontSee($foto50->ref);
    }

    public function test_pecas_por_publicar_nao_aparecem(): void
    {
        $peca = Peca::factory()->porPublicar()->create(['titulo' => 'Escondida de exemplo']);

        $this->get(route('arquivo'))->assertDontSee($peca->ref);
        $this->get(route('peca', $peca))->assertNotFound();
    }

    public function test_seccao_vazia_aparece_com_convite(): void
    {
        $this->get(route('seccao', ['seccao' => 'biblioteca']))
            ->assertOk()
            ->assertSee('Esta sala ainda está vazia');
    }

    public function test_paginas_publicas_respondem(): void
    {
        $peca = Peca::factory()->create();

        foreach (['inicio', 'arquivo', 'linha-do-tempo', 'quem-e-quem', 'sobre', 'privacidade'] as $rota) {
            $this->get(route($rota))->assertOk();
        }
        $this->get(route('peca', $peca))->assertOk()->assertSee($peca->ref);
        $this->get('/nao-existe')->assertNotFound();
    }

    public function test_ref_sequencial_por_tipo(): void
    {
        $foto = Peca::factory()->create();
        $postal = Peca::factory()->tipo(TipoPeca::Postal)->create();
        $cartaz = Peca::factory()->tipo(TipoPeca::Cartaz)->create();

        $this->assertSame('CRV-F-0001', $foto->ref);
        $this->assertSame('CRV-F-0002', $postal->ref); // fotos e postais partilham o F
        $this->assertSame('CRV-C-0001', $cartaz->ref);
    }

    public function test_decada_calculada_e_descricao_limpa_ao_gravar(): void
    {
        $peca = Peca::factory()->create(['data_texto' => 'Outubro de 1967', 'descricao' => '<p style="x">Olá</p><script>x</script>']);

        $this->assertSame(1960, $peca->decada);
        $this->assertSame('<p>Olá</p>', $peca->descricao);
    }
}
