<?php

namespace App\Http\Middleware;

use App\Enums\Seccao;
use App\Models\Peca;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * O site de 2017 usava URLs como /?pagina=fototeca&categoria=48.
 * Este middleware envia-os (301) para os URLs novos, para não se perderem
 * ligações antigas nem QR codes já impressos.
 */
class RedirecionarSiteAntigo
{
    /** Secção nova → tabela antiga, para ?pagina=fototeca&id=3. */
    private const TABELAS = [
        'fototeca' => 'fototeca',
        'imprensa' => 'imprensa',
        'cartazes' => 'cartazes',
        'mapas-e-plantas' => 'mapaplanta',
        'videoteca' => 'videoteca',
        'biblioteca' => 'biblioteca',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->query->has('pagina') && in_array($request->path(), ['/', 'index.php'], true)) {
            return redirect()->to($this->destino($request), 301);
        }

        if ($request->path() === 'index.php') {
            return redirect()->to(route('inicio'), 301);
        }

        return $next($request);
    }

    public function destino(Request $request): string
    {
        $pagina = (string) $request->query('pagina');
        $seccao = Seccao::daPaginaLegado($pagina);

        if ($seccao) {
            $id = (int) $request->query('id');
            if ($id && $ref = Peca::where('legado_tabela', self::TABELAS[$seccao->value])->where('legado_id', $id)->value('ref')) {
                return route('peca', $ref);
            }

            $categoria = (int) $request->query('categoria');
            $tema = $categoria
                ? DB::table('temas_legado')->join('temas', 'temas.id', '=', 'temas_legado.tema_id')
                    ->where('temas_legado.legado_id', $categoria)->value('temas.slug')
                : null;

            return route('seccao', array_filter(['seccao' => $seccao->value, 'tema' => $tema]));
        }

        return match ($pagina) {
            'ficha_tecnica' => route('sobre'),
            'pesquisa' => route('arquivo', array_filter(['q' => $request->query('pesquisa') ?? $request->query('q')])),
            default => route('inicio'),
        };
    }
}
