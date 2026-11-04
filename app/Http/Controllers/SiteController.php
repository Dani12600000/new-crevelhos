<?php

namespace App\Http\Controllers;

use App\Enums\Seccao;
use App\Models\Marcacao;
use App\Models\Peca;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function inicio(): View
    {
        $seccoes = collect(Seccao::cases())->map(fn (Seccao $seccao) => (object) [
            'seccao' => $seccao,
            'total' => Peca::publicadas()->dosTipos($seccao->tipos())->count(),
            'capa' => Peca::publicadas()->dosTipos($seccao->tipos())->whereNotNull('ficheiro')
                ->orderByDesc('destaque')->orderBy('ref')->first(),
        ]);

        // Destaques escolhidos no backoffice; sem destaques, uma seleção que muda todos os dias.
        $destaques = Peca::publicadas()->where('destaque', true)->whereNotNull('ficheiro')->inRandomOrder()->limit(8)->get();
        if ($destaques->isEmpty()) {
            $destaques = Peca::publicadas()->whereNotNull('ficheiro')->inRandomOrder(now()->format('Ymd'))->limit(8)->get();
        }

        $porIdentificar = Marcacao::publicas()->whereNull('pessoa_id')
            ->whereHas('peca', fn ($q) => $q->publicadas())->count();

        return view('site.inicio', compact('seccoes', 'destaques', 'porIdentificar'));
    }

    public function sobre(): View
    {
        return view('site.sobre');
    }

    public function privacidade(): View
    {
        return view('site.privacidade');
    }

    public function linhaDoTempo(): View
    {
        $totais = Peca::publicadas()->whereNotNull('decada')
            ->select('decada', DB::raw('count(*) as total'))
            ->groupBy('decada')->orderBy('decada')
            ->pluck('total', 'decada');

        $decadas = $totais->map(fn ($total, $decada) => (object) [
            'decada' => (int) $decada,
            'total' => $total,
            'pecas' => Peca::publicadas()->where('decada', $decada)->orderBy('data_texto')->orderBy('ref')->limit(8)->get(),
        ])->values();

        $semData = Peca::publicadas()->whereNull('decada')->count();

        return view('site.linha-do-tempo', compact('decadas', 'semData'));
    }

    public function quemEQuem(): View
    {
        $marcacoes = Marcacao::publicas()
            ->whereNull('pessoa_id')
            ->whereHas('peca', fn ($q) => $q->publicadas()->whereNotNull('ficheiro'))
            ->with('peca')
            ->join('pecas', 'pecas.id', '=', 'marcacoes.peca_id')
            ->orderBy('pecas.ref')->orderBy('marcacoes.numero')
            ->select('marcacoes.*')
            ->paginate(48);

        return view('site.quem-e-quem', compact('marcacoes'));
    }
}
