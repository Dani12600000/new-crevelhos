<?php

namespace Tests\Feature;

use App\Enums\Papel;
use App\Filament\Resources\Pecas\Pages\ListPecas;
use App\Models\Acervo;
use App\Models\Peca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Ferramentas da lista de peças usadas para catalogar o arquivo. */
class CatalogacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['papel' => Papel::Editor]));
    }

    /** @return array<string, array{string, string}> */
    public static function filtros(): array
    {
        return [
            'sem data' => ['sem_data', 'decada'],
            'sem descrição' => ['sem_descricao', 'descricao'],
            'sem acervo' => ['sem_acervo', 'acervo_id'],
        ];
    }

    #[DataProvider('filtros')]
    public function test_filtros_do_que_falta_catalogar(string $filtro, string $campo): void
    {
        $completa = Peca::factory()->create([
            'data_texto' => '1962',
            'descricao' => '<p>Texto de exemplo</p>',
            'acervo_id' => Acervo::create(['nome' => 'Acervo de exemplo'])->id,
        ]);
        $incompleta = $completa->replicate(['ref']);
        $incompleta->ref = 'CRV-F-9999';
        $incompleta->save();
        // Sem eventos: a década seria recalculada ao gravar.
        Peca::whereKey($incompleta->id)->update([$campo => null]);

        Livewire::test(ListPecas::class)
            ->filterTable($filtro, true)
            ->assertCanSeeTableRecords([$incompleta])
            ->assertCanNotSeeTableRecords([$completa])
            ->filterTable($filtro, false)
            ->assertCanSeeTableRecords([$completa])
            ->assertCanNotSeeTableRecords([$incompleta]);
    }
}
