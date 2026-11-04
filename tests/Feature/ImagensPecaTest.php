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
}
