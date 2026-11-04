<?php

namespace App\Http\Controllers;

use App\Models\Marcacao;
use App\Models\Pessoa;
use Illuminate\View\View;

class PessoaController extends Controller
{
    public function show(Pessoa $pessoa): View
    {
        abort_if($pessoa->oculta, 404);

        // Cada aparição, por ordem cronológica (as sem data no fim).
        $aparicoes = Marcacao::publicas()
            ->where('pessoa_id', $pessoa->id)
            ->whereHas('peca', fn ($q) => $q->publicadas())
            ->with('peca')
            ->get()
            ->sortBy(fn (Marcacao $m) => [$m->peca->decada ?? 9999, $m->peca->ref])
            ->values();

        abort_if($aparicoes->isEmpty() && ! auth()->check(), 404);

        return view('pessoas.show', compact('pessoa', 'aparicoes'));
    }
}
