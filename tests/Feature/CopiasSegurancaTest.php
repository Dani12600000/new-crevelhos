<?php

namespace Tests\Feature;

use App\Enums\Papel;
use App\Filament\Widgets\UltimaCopiaSeguranca;
use App\Models\User;
use App\Services\CopiasSeguranca;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class CopiasSegurancaTest extends TestCase
{
    use RefreshDatabase;

    private const AVISO = 'É tempo de guardar uma cópia';

    private string $pastaTemporaria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pastaTemporaria = sys_get_temp_dir().'/crevelhos-testes-'.uniqid();
        File::ensureDirectoryExists($this->pastaTemporaria.'/app/copias');
        $this->app->useStoragePath($this->pastaTemporaria);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->pastaTemporaria);

        parent::tearDown();
    }

    private function criarCopia(string $nome = 'crevelhos-2026-11-09-030000.zip'): void
    {
        file_put_contents($this->pastaTemporaria.'/app/copias/'.$nome, 'conteúdo de exemplo');
    }

    private function admin(): User
    {
        return User::factory()->create(['papel' => Papel::Admin]);
    }

    private function editor(): User
    {
        return User::factory()->create(['papel' => Papel::Editor]);
    }

    public function test_admin_descarrega_a_copia_mais_recente_e_fica_registado(): void
    {
        $this->criarCopia('crevelhos-2026-11-08-030000.zip');
        $this->criarCopia('crevelhos-2026-11-09-030000.zip');

        $this->actingAs($this->admin())->get(route('copia-seguranca'))
            ->assertOk()
            ->assertDownload('crevelhos-2026-11-09-030000.zip');

        $this->assertTrue(app(CopiasSeguranca::class)->ultimaDescarga()->isToday());
    }

    public function test_so_o_admin_descarrega(): void
    {
        $this->criarCopia();

        $this->get(route('copia-seguranca'))->assertRedirect('/admin/login');
        $this->actingAs($this->editor())->get(route('copia-seguranca'))->assertForbidden();
        $this->assertNull(app(CopiasSeguranca::class)->ultimaDescarga());
    }

    public function test_sem_copias_nao_ha_descarga(): void
    {
        $this->actingAs($this->admin())->get(route('copia-seguranca'))->assertNotFound();
    }

    public function test_widget_so_aparece_ao_admin(): void
    {
        $this->actingAs($this->editor());
        $this->assertFalse(UltimaCopiaSeguranca::canView());

        $this->actingAs($this->admin());
        $this->assertTrue(UltimaCopiaSeguranca::canView());
    }

    public function test_widget_avisa_se_nunca_houve_descarga(): void
    {
        $this->criarCopia();

        Livewire::actingAs($this->admin())->test(UltimaCopiaSeguranca::class)
            ->assertSee('Descarregar a última cópia')
            ->assertSee('nunca')
            ->assertSee(self::AVISO);
    }

    public function test_widget_avisa_so_depois_de_30_dias_sem_descarga(): void
    {
        $this->criarCopia();
        app(CopiasSeguranca::class)->registarDescarga();

        $this->travel(29)->days();
        Livewire::actingAs($this->admin())->test(UltimaCopiaSeguranca::class)->assertDontSee(self::AVISO);

        $this->travel(2)->days();
        Livewire::actingAs($this->admin())->test(UltimaCopiaSeguranca::class)->assertSee(self::AVISO);
    }

    public function test_widget_sem_copias_nao_mostra_botao(): void
    {
        Livewire::actingAs($this->admin())->test(UltimaCopiaSeguranca::class)
            ->assertSee('Ainda não há cópias')
            ->assertDontSee('Descarregar a última cópia');
    }
}
