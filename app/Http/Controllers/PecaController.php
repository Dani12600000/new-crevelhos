<?php

namespace App\Http\Controllers;

use App\Models\Peca;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PecaController extends Controller
{
    public function show(Peca $peca): View
    {
        // Peças por publicar só se veem com sessão iniciada no backoffice (pré-visualização).
        abort_unless($peca->publicado || auth()->check(), 404);

        $peca->load([
            'temas' => fn ($q) => $q->orderBy('nome'),
            'acervo',
            'marcacoes' => fn ($q) => $q->publicas()->with('pessoa'),
            'comentarios' => fn ($q) => $q->publicados()->orderBy('data')->orderBy('id'),
        ]);

        $tipos = $peca->tipo->seccao()->tipos();
        $anterior = Peca::publicadas()->dosTipos($tipos)->where('ref', '<', $peca->ref)->orderByDesc('ref')->first();
        $seguinte = Peca::publicadas()->dosTipos($tipos)->where('ref', '>', $peca->ref)->orderBy('ref')->first();

        return view('pecas.show', compact('peca', 'anterior', 'seguinte'));
    }

    public function aoAcaso(): RedirectResponse
    {
        $peca = Peca::publicadas()->whereNotNull('ficheiro')->inRandomOrder()->first();

        return $peca ? redirect()->route('peca', $peca) : redirect()->route('arquivo');
    }
}
