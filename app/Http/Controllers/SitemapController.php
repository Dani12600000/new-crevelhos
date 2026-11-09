<?php

namespace App\Http\Controllers;

use App\Enums\Seccao;
use App\Models\Peca;
use App\Models\Pessoa;
use Illuminate\Http\Response;

/** O sitemap e o robots dependem do domínio, por isso são gerados e não ficheiros em public/. */
class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        $paginas = collect(['inicio', 'arquivo', 'linha-do-tempo', 'quem-e-quem', 'sobre', 'privacidade'])
            ->map(fn (string $rota) => ['loc' => route($rota), 'lastmod' => null]);

        $seccoes = collect(Seccao::cases())
            ->map(fn (Seccao $seccao) => ['loc' => route('seccao', $seccao->value), 'lastmod' => null]);

        $pecas = Peca::publicadas()->orderBy('ref')->get()
            ->map(fn (Peca $peca) => ['loc' => route('peca', $peca), 'lastmod' => $peca->updated_at]);

        // Como na página da pessoa: só as visíveis e com alguma aparição pública.
        $pessoas = Pessoa::visiveis()
            ->whereHas('marcacoes', fn ($marcacoes) => $marcacoes->publicas()->whereHas('peca', fn ($peca) => $peca->publicadas()))
            ->orderBy('slug')
            ->get()
            ->map(fn (Pessoa $pessoa) => ['loc' => route('pessoa', $pessoa), 'lastmod' => $pessoa->updated_at]);

        return response()
            ->view('sitemap', ['urls' => $paginas->concat($seccoes)->concat($pecas)->concat($pessoas)])
            ->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $linhas = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /peca-ao-acaso',
            '',
            'Sitemap: '.route('sitemap'),
            '',
        ];

        return response(implode("\n", $linhas))->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
