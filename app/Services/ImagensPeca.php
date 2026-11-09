<?php

namespace App\Services;

use App\Models\Peca;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Guarda o ficheiro original de uma peça em pecas/{ref}/ e gera:
 *  - grande.webp     (para a página da peça)
 *  - miniatura.webp  (para grelhas e listas)
 *  - partilha.jpg    (para as redes sociais, que nem sempre mostram WebP)
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

    /** (Re)gera grande.webp, miniatura.webp e partilha.jpg a partir do original. */
    public function gerarVersoes(Peca $peca): void
    {
        $disco = Storage::disk('public');
        $original = $disco->path($peca->ficheiro);

        $imagem = $this->manager->read($original)->orient();
        $peca->largura = $imagem->width();
        $peca->altura = $imagem->height();

        $this->gravarWebp($imagem, $original, config('crevelhos.imagens.grande'), $disco->path($peca->pasta().'/grande.webp'));

        $this->gravarWebp(
            $this->manager->read($original)->orient(),
            $original,
            config('crevelhos.imagens.miniatura'),
            $disco->path($peca->pasta().'/miniatura.webp'),
        );

        $this->manager->read($original)->orient()
            ->scaleDown(config('crevelhos.imagens.partilha'), config('crevelhos.imagens.partilha'))
            ->toJpeg(config('crevelhos.imagens.qualidade'))
            ->save($disco->path($peca->pasta().'/partilha.jpg'));
    }

    /**
     * Reduz a imagem ao tamanho máximo e grava-a em WebP. Se o original já couber nesse
     * tamanho, a versão só compensa se for mais pequena: baixa-se a qualidade até o ser
     * (sem passar da qualidade mínima, para o texto dos recortes continuar legível).
     */
    private function gravarWebp(ImageInterface $imagem, string $original, int $maximo, string $destino): void
    {
        $originalCabe = max($imagem->width(), $imagem->height()) <= $maximo;
        $bytesOriginal = filesize($original);
        $qualidade = config('crevelhos.imagens.qualidade');
        $minima = config('crevelhos.imagens.qualidade_minima');

        $imagem->scaleDown($maximo, $maximo);
        $webp = $imagem->toWebp($qualidade);

        while ($originalCabe && $webp->size() >= $bytesOriginal && $qualidade > $minima) {
            $qualidade = max($minima, $qualidade - 10);
            $webp = $imagem->toWebp($qualidade);
        }

        $webp->save($destino);
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
        if (! $disco->exists($peca->ficheiro)) {
            return;
        }

        $enviado = $disco->path($peca->ficheiro);
        $caminhoEnviado = $peca->ficheiro;

        $this->guardar($peca, $enviado);
        $disco->delete($caminhoEnviado);
        $peca->saveQuietly();
    }
}
