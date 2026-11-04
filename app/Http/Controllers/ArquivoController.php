<?php

namespace App\Http\Controllers;

use App\Enums\Seccao;
use App\Support\PesquisaPecas;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArquivoController extends Controller
{
    /** /arquivo?q=...&tipo=foto&decada=1950&tema=aldeia */
    public function index(Request $request): View
    {
        return $this->mostrar(PesquisaPecas::doPedido($request));
    }

    /** /fototeca, /imprensa... (o mesmo arquivo, limitado a uma secção) */
    public function seccao(Request $request, Seccao $seccao): View
    {
        return $this->mostrar(PesquisaPecas::doPedido($request, $seccao));
    }

    private function mostrar(PesquisaPecas $pesquisa): View
    {
        return view('arquivo.index', [
            'pesquisa' => $pesquisa,
            'seccao' => $pesquisa->seccao,
            'pecas' => $pesquisa->resultados(),
            'decadas' => $pesquisa->decadas(),
            'temas' => $pesquisa->temas(),
        ]);
    }
}
