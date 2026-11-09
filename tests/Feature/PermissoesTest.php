<?php

namespace Tests\Feature;

use App\Enums\Papel;
use App\Filament\Resources\Pecas\Pages\EditPeca;
use App\Filament\Resources\Pecas\Pages\ListPecas;
use App\Filament\Resources\Textos\Pages\EditTexto;
use App\Filament\Resources\Textos\Pages\ListTextos;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Peca;
use App\Models\Texto;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** O que cada papel pode apagar no backoffice (ver app/Policies). */
class PermissoesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['papel' => Papel::Admin]);
    }

    private function editor(): User
    {
        return User::factory()->create(['papel' => Papel::Editor]);
    }

    public function test_editor_nao_apaga_pecas(): void
    {
        $peca = Peca::factory()->create();
        $this->actingAs($this->editor());

        Livewire::test(EditPeca::class, ['record' => $peca->ref])->assertActionHidden(DeleteAction::class);
        Livewire::test(ListPecas::class)->assertActionHidden(TestAction::make('delete')->table()->bulk());
        $this->assertTrue(auth()->user()->cannot('delete', $peca));
    }

    public function test_admin_apaga_pecas(): void
    {
        $peca = Peca::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(EditPeca::class, ['record' => $peca->ref])->assertActionVisible(DeleteAction::class);
        Livewire::test(ListPecas::class)->assertActionVisible(TestAction::make('delete')->table()->bulk());
    }

    public function test_admin_apaga_outros_utilizadores_mas_nao_a_propria_conta(): void
    {
        $admin = $this->admin();
        $outro = $this->editor();
        $this->actingAs($admin);

        Livewire::test(ManageUsers::class)
            ->assertActionHidden(TestAction::make('delete')->table($admin))
            ->assertActionVisible(TestAction::make('delete')->table($outro));
    }

    public function test_textos_do_site_nao_se_criam_nem_apagam(): void
    {
        $texto = Texto::create(['chave' => 'rodape', 'valor' => 'Texto de exemplo']);
        $this->actingAs($this->admin());

        Livewire::test(ListTextos::class)->assertActionHidden(CreateAction::class);
        Livewire::test(EditTexto::class, ['record' => $texto->getRouteKey()])->assertActionHidden(DeleteAction::class);
    }
}
