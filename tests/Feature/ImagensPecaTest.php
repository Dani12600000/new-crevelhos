<?php

namespace Tests\Feature;

use App\Models\Peca;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Um ficheiro enviado pelo backoffice vai para pecas/{ref}/ e ganha WebP e miniatura. */
class ImagensPecaTest extends TestCase
{
    use RefreshDatabase;

    public function test_envio_e_movido_para_a_pasta_da_peca(): void
    {
        Storage::fake('public');
        $enviado = UploadedFile::fake()->image('exemplo.jpg', 1200, 900)->store('pecas/envios', 'public');

        $peca = Peca::factory()->create(['ficheiro' => $enviado]);

        $this->assertSame('pecas/'.$peca->ref.'/original.jpg', $peca->ficheiro);
        Storage::disk('public')->assertMissing($enviado);
        Storage::disk('public')->assertExists('pecas/'.$peca->ref.'/grande.webp');
        Storage::disk('public')->assertExists('pecas/'.$peca->ref.'/miniatura.webp');
        $this->assertSame([1200, 900], [$peca->largura, $peca->altura]);
        $this->assertSame(40, strlen($peca->ficheiro_hash));
    }

    public function test_ficheiro_em_falta_nao_impede_gravar(): void
    {
        Storage::fake('public');

        $peca = Peca::factory()->create(['ficheiro' => 'pecas/envios/nao-existe.jpg']);

        $this->assertTrue($peca->exists);
    }

    public function test_webp_fica_mais_pequeno_do_que_um_original_muito_comprimido(): void
    {
        Storage::fake('public');
        $disco = Storage::disk('public');

        // Sem poder baixar a qualidade, o WebP fica maior do que o original.
        config(['crevelhos.imagens.qualidade' => 100, 'crevelhos.imagens.qualidade_minima' => 100]);
        $sem = Peca::factory()->create(['ficheiro' => $this->jpegComprimido('pecas/envios/a.jpg')]);
        $this->assertGreaterThan($disco->size($sem->ficheiro), $disco->size($sem->pasta().'/grande.webp'));

        config(['crevelhos.imagens.qualidade_minima' => 40]);
        $com = Peca::factory()->create(['ficheiro' => $this->jpegComprimido('pecas/envios/b.jpg')]);
        $this->assertLessThan($disco->size($com->ficheiro), $disco->size($com->pasta().'/grande.webp'));
    }

    public function test_regenerar_imagens_refaz_as_versoes_e_avisa_de_ficheiros_em_falta(): void
    {
        Storage::fake('public');
        $peca = Peca::factory()->create([
            'ficheiro' => UploadedFile::fake()->image('exemplo.jpg', 800, 600)->store('pecas/envios', 'public'),
        ]);
        $falta = Peca::factory()->create();
        $falta->updateQuietly(['ficheiro' => 'pecas/'.$falta->ref.'/original.jpg']);
        $disco = Storage::disk('public');
        $disco->delete($peca->pasta().'/grande.webp');

        $this->artisan('crevelhos:regenerar-imagens')
            ->expectsOutputToContain($falta->ref)
            ->assertFailed();

        $disco->assertExists($peca->pasta().'/grande.webp');
    }

    private function jpegComprimido(string $caminho): string
    {
        mt_srand(1962);
        $imagem = imagecreatetruecolor(400, 300);
        for ($x = 0; $x < 400; $x += 2) {
            for ($y = 0; $y < 300; $y += 2) {
                imagefilledrectangle($imagem, $x, $y, $x + 1, $y + 1, mt_rand(0, 0xFFFFFF));
            }
        }

        ob_start();
        imagejpeg($imagem, null, 60);
        Storage::disk('public')->put($caminho, ob_get_clean());

        return $caminho;
    }
}
