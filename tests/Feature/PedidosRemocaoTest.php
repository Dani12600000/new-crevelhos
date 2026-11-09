<?php

namespace Tests\Feature;

use App\Enums\Papel;
use App\Filament\Resources\PedidosRemocao\Pages\CreatePedidoRemocao;
use App\Filament\Resources\PedidosRemocao\Pages\EditPedidoRemocao;
use App\Filament\Resources\PedidosRemocao\Pages\ListPedidosRemocao;
use App\Models\Marcacao;
use App\Models\Peca;
use App\Models\PedidoRemocao;
use App\Models\Pessoa;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PedidosRemocaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['papel' => Papel::Editor]));
    }

    private function pedido(array $atributos = []): PedidoRemocao
    {
        return PedidoRemocao::create($atributos + [
            'requerente' => 'Requerente de exemplo',
            'data_pedido' => '2026-11-01',
            'pedido' => 'Pedido de exemplo',
        ]);
    }

    public function test_paginas_abrem(): void
    {
        $pedido = $this->pedido();

        $this->get('/admin/pedidos-remocao')->assertOk()->assertSee('Requerente de exemplo');
        $this->get('/admin/pedidos-remocao/create')->assertOk();
        $this->get("/admin/pedidos-remocao/{$pedido->id}/edit")->assertOk();
    }

    public function test_regista_quem_pediu_sobre_quem_e_o_que_se_fez(): void
    {
        $pessoa = Pessoa::factory()->create();
        $marcacao = Marcacao::factory()->create(['peca_id' => Peca::factory()->create()->id, 'pessoa_id' => $pessoa->id]);

        Livewire::test(CreatePedidoRemocao::class)
            ->fillForm([
                'requerente' => 'Requerente de exemplo',
                'contacto' => 'exemplo@exemplo.pt',
                'data_pedido' => '2026-11-01',
                'pessoa_id' => $pessoa->id,
                'marcacao_id' => $marcacao->id,
                'pedido' => 'Retirar o nome da fotografia.',
                'acao_tomada' => 'Número escondido.',
                'data_resolucao' => '2026-11-03',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $pedido = PedidoRemocao::sole();
        $this->assertSame([$pessoa->id, $marcacao->id], [$pedido->pessoa_id, $pedido->marcacao_id]);
        $this->assertSame('Número escondido.', $pedido->acao_tomada);
        $this->assertSame('2026-11-03', $pedido->data_resolucao->format('Y-m-d'));
    }

    public function test_resolucao_nao_pode_ser_anterior_ao_pedido(): void
    {
        Livewire::test(CreatePedidoRemocao::class)
            ->fillForm([
                'requerente' => 'Requerente de exemplo',
                'data_pedido' => '2026-11-05',
                'pedido' => 'Pedido de exemplo',
                'data_resolucao' => '2026-11-01',
            ])
            ->call('create')
            ->assertHasFormErrors(['data_resolucao']);
    }

    public function test_filtro_por_resolver(): void
    {
        $porResolver = $this->pedido();
        $resolvido = $this->pedido(['data_resolucao' => '2026-11-03']);

        Livewire::test(ListPedidosRemocao::class)
            ->filterTable('resolvido', false)
            ->assertCanSeeTableRecords([$porResolver])
            ->assertCanNotSeeTableRecords([$resolvido]);
    }

    public function test_registo_fica_quando_a_pessoa_e_apagada(): void
    {
        $pessoa = Pessoa::factory()->create();
        $pedido = $this->pedido(['pessoa_id' => $pessoa->id]);

        $pessoa->delete();

        $this->assertNull($pedido->refresh()->pessoa_id);
        $this->assertSame('Pedido de exemplo', $pedido->pedido);
    }

    public function test_ao_juntar_pessoas_o_pedido_acompanha_a_que_fica(): void
    {
        $fica = Pessoa::factory()->create();
        $duplicada = Pessoa::factory()->create();
        $pedido = $this->pedido(['pessoa_id' => $duplicada->id]);

        $duplicada->juntarEm($fica);

        $this->assertSame($fica->id, $pedido->refresh()->pessoa_id);
    }

    public function test_so_o_admin_apaga_o_registo(): void
    {
        $pedido = $this->pedido();

        Livewire::test(EditPedidoRemocao::class, ['record' => $pedido->id])->assertActionHidden(DeleteAction::class);

        $this->actingAs(User::factory()->create(['papel' => Papel::Admin]));
        Livewire::test(EditPedidoRemocao::class, ['record' => $pedido->id])->assertActionVisible(DeleteAction::class);
    }
}
