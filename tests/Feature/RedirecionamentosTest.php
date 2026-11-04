<?php

namespace Tests\Feature;

use App\Models\Peca;
use App\Models\Tema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Os URLs do site de 2017 (e os QR codes já impressos) continuam a funcionar. */
class RedirecionamentosTest extends TestCase
{
    use RefreshDatabase;

    public function test_seccao_antiga(): void
    {
        $this->get('/?pagina=fototeca')->assertRedirect(route('seccao', 'fototeca'))->assertStatus(301);
        $this->get('/?pagina=mapas_e_plantas')->assertRedirect(route('seccao', 'mapas-e-plantas'));
    }

    public function test_categoria_antiga_vai_para_o_tema_novo(): void
    {
        $tema = Tema::create(['nome' => 'Aldeia']);
        DB::table('temas_legado')->insert(['legado_id' => 48, 'tema_id' => $tema->id, 'nome_original' => 'Aldeia', 'destino' => 2]);

        $this->get('/?pagina=fototeca&categoria=48')
            ->assertRedirect(route('seccao', ['seccao' => 'fototeca', 'tema' => 'aldeia']));
    }

    public function test_peca_antiga_pelo_id(): void
    {
        $peca = Peca::factory()->create(['legado_tabela' => 'imprensa', 'legado_id' => 2]);

        $this->get('/?pagina=imprensa&id=2')->assertRedirect(route('peca', $peca));
    }

    public function test_outras_paginas_antigas(): void
    {
        $this->get('/?pagina=ficha_tecnica')->assertRedirect(route('sobre'));
        $this->get('/?pagina=pesquisa')->assertRedirect(route('arquivo'));
        $this->get('/?pagina=inicio')->assertRedirect(route('inicio'));
        $this->get('/?pagina=qualquer-coisa')->assertRedirect(route('inicio'));
    }

    public function test_inicio_normal_nao_redireciona(): void
    {
        $this->get('/')->assertOk();
    }
}
