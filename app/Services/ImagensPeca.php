<?php

namespace App\Services;

use App\Models\Peca;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Guarda o ficheiro original de uma peça em pecas/{ref}/ e gera:
 *  - grande.webp     (para a página da peça)
 *  - miniatura.webp  (para grelhas e listas)
 * Usa GD, que existe em qualquer alojamento partilhado.
 */
class ImagensPeca
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * Copia um ficheiro (caminho absoluto) para a pasta da peça e gera as versões.
     * Não grava a peça: só preenche ficheiro, ficheiro_hash, largura e altura.
     */
    public function guardar(Peca $peca, string $origem): void
    {
        $disco = Storage::disk('public');
        $extensao = strtolower(pathinfo($origem, PATHINFO_EXTENSION)) ?: 'jpg';
        $extensao = $extensao === 'jpeg' ? 'jpg' : $extensao;
        $destino = $peca->pasta().'/original.'.$extensao;

        // Se mudou a extensão, apaga o original antigo.
        foreach ($disco->files($peca->pasta()) as $antigo) {
            if (str_starts_with(basename($antigo), 'original.') && $antigo !== $destino) {
                $disco->delete($antigo);
            }
        }

        $disco->makeDirectory($peca->pasta());
        if (realpath($origem) !== realpath($disco->path($destino))) {
            copy($origem, $disco->path($destino));
        }

        $peca->ficheiro = $destino;
        $peca->ficheiro_hash = sha1_file($origem);

        $this->gerarVersoes($peca);
    }

    /** (Re)gera grande.webp e miniatura.webp a partir do original. */
    public function gerarVersoes(Peca $peca): void
    {
        $disco = Storage::disk('public');
        $original = $disco->path($peca->ficheiro);

        $imagem = $this->manager->read($original)->orient();
        $peca->largura = $imagem->width();
        $peca->altura = $imagem->height();

        $qualidade = config('crevelhos.imagens.qualidade');

        $imagem->scaleDown(config('crevelhos.imagens.grande'), config('crevelhos.imagens.grande'))
            ->toWebp($qualidade)
            ->save($disco->path($peca->pasta().'/grande.webp'));

        $this->manager->read($original)->orient()
            ->scaleDown(config('crevelhos.imagens.miniatura'), config('crevelhos.imagens.miniatura'))
            ->toWebp($qualidade)
            ->save($disco->path($peca->pasta().'/miniatura.webp'));
    }

    /**
     * Depois de um upload no backoffice o ficheiro fica em pecas/envios/...;
     * isto move-o para a pasta da peça e gera as versões.
     */
    public function processarEnvio(Peca $peca): void
    {
        if (! $peca->ficheiro || str_starts_with($peca->ficheiro, $peca->pasta().'/')) {
            return;
        }

        $disco = Storage::disk('public');
        $enviado = $disco->path($peca->ficheiro);
        $caminhoEnviado = $peca->ficheiro;

        $this->guardar($peca, $enviado);
        $disco->delete($caminhoEnviado);
        $peca->saveQuietly();
    }
}
