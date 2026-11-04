<?php

namespace Tests\Feature;

use App\Enums\Papel;
use App\Filament\Resources\Pecas\Pages\MarcacoesPeca;
use App\Models\Marcacao;
use App\Models\Peca;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BackofficeTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::factory()->create(['papel' => Papel::Editor]);
    }

    public function test_sem_sessao_vai_para_o_login(): void
    {
        $this->get('/admin/pecas')->assertRedirect('/admin/login');
        $this->get(route('folha-cafe', Peca::factory()->create()))->assertRedirect('/admin/login');
    }

    public function test_paginas_do_backoffice_abrem(): void
    {
        Storage::fake('public');
        $peca = Peca::factory()->create();
        $this->actingAs($this->editor());

        foreach (['/admin', '/admin/pecas', '/admin/pecas/create', "/admin/pecas/{$peca->ref}/edit", '/admin/pessoas',
            '/admin/sugestoes', '/admin/comentarios', '/admin/temas', '/admin/acervos', '/admin/textos'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get(route('folha-cafe', $peca))->assertOk()->assertSee($peca->ref)->assertSee('<svg', false);
    }

    public function test_so_o_admin_gere_utilizadores(): void
    {
        $this->actingAs($this->editor())->get('/admin/utilizadores')->assertForbidden();
        $this->actingAs(User::factory()->create(['papel' => Papel::Admin]))->get('/admin/utilizadores')->assertOk();
    }

    public function test_peca_por_publicar_tem_pre_visualizacao_com_sessao(): void
    {
        $peca = Peca::factory()->porPublicar()->create();

        $this->get(route('peca', $peca))->assertNotFound();
        $this->actingAs($this->editor())->get(route('peca', $peca))->assertOk()->assertSee('Pré-visualização');
    }

    public function test_editor_de_marcacoes_cria_move_e_liga_pessoa(): void
    {
        Storage::fake('public');
        $peca = Peca::factory()->create(['ficheiro' => 'pecas/exemplo/original.jpg']);
        $pessoa = Pessoa::factory()->create();
        $this->actingAs($this->editor());

        $pagina = Livewire::test(MarcacoesPeca::class, ['record' => $peca->ref])
            ->call('adicionar', 25.5, 40.25)
            ->call('adicionar', 150, -3); // fora da imagem: fica no limite

        $um = Marcacao::where('peca_id', $peca->id)->where('numero', 1)->first();
        $this->assertEquals([25.5, 40.25], [$um->x, $um->y]);
        $this->assertEquals([100, 0], [Marcacao::where('numero', 2)->value('x'), Marcacao::where('numero', 2)->value('y')]);

        $pagina->call('mover', $um->id, 30, 35);
        $this->assertEquals([30, 35], [$um->fresh()->x, $um->fresh()->y]);

        $pagina->callAction('ligarPessoa', ['pessoa_id' => $pessoa->id], ['marcacao' => $um->id]);
        $this->assertSame($pessoa->id, $um->fresh()->pessoa_id);

        $pagina->callAction('sugestao', ['nome_sugerido' => 'Sugestão de exemplo', 'fonte' => 'folha'], ['marcacao' => $um->id]);
        $this->assertSame(1, $um->sugestoes()->count());
    }

    public function test_fotografia_sem_imagem_nao_tem_editor(): void
    {
        $peca = Peca::factory()->create();
        $this->actingAs($this->editor())->get("/admin/pecas/{$peca->ref}/marcacoes")->assertNotFound();
    }
}
