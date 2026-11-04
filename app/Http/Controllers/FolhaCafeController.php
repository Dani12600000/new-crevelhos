<?php

namespace App\Http\Controllers;

use App\Models\Peca;
use App\Models\Texto;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\View\View;

/**
 * Folha A5 para imprimir e pôr na mica, ao lado da fotografia exposta no café:
 * imagem com os números, título, ref, frase da folha, QR code e linhas para escrever.
 */
class FolhaCafeController extends Controller
{
    public function __invoke(Peca $peca): View
    {
        $peca->load(['marcacoes' => fn ($q) => $q->publicas()->with('pessoa')]);

        $url = route('peca', $peca);
        $qr = (new Writer(new ImageRenderer(new RendererStyle(240, 1), new SvgImageBackEnd)))->writeString($url);
        $qr = preg_replace('/^<\?xml[^>]*>\s*/', '', $qr);

        return view('folha-cafe', [
            'peca' => $peca,
            'url' => $url,
            'qr' => $qr,
            'frase' => Texto::valor('frase_folha', 'Reconhece alguém nesta fotografia? Escreva o número e o nome. Conte-nos também o que se lembra.'),
        ]);
    }
}
