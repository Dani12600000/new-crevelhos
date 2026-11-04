@extends('layouts.site')

@php
    $pecas = $aparicoes->pluck('peca')->unique('id');
    $n = $pecas->count();
@endphp

@section('titulo', $pessoa->nome)
@section('descricao', $pessoa->nome.($pessoa->alcunha ? ' ('.$pessoa->alcunha.')' : '').' aparece em '.$n.' '.($n === 1 ? 'peça' : 'peças').' da Memória Digital de Casais de Revelhos.')

@section('conteudo')
    <div class="contentor">
        <nav class="trilho" aria-label="Está aqui">
            <a href="{{ route('inicio') }}">Início</a> › <a href="{{ route('quem-e-quem') }}">Quem é quem?</a> › {{ $pessoa->nome }}
        </nav>

        <header class="cabeca-pagina">
            <span class="ref">Pessoa</span>
            <h1>{{ $pessoa->nome }}</h1>
            @if ($pessoa->alcunha)
                <p class="introducao">Conhecida por <strong>{{ $pessoa->alcunha }}</strong>.</p>
            @endif
            <p>Aparece em <strong>{{ $n }}</strong> {{ $n === 1 ? 'peça' : 'peças' }} do arquivo.</p>
        </header>

        @if ($aparicoes->isNotEmpty())
            <section aria-labelledby="titulo-anos">
                <h2 id="titulo-anos">Ao longo dos anos</h2>
                <ol class="grelha-caras">
                    @foreach ($aparicoes as $m)
                        <li class="cara">
                            <a href="{{ route('peca', $m->peca) }}">
                                <x-recorte :marcacao="$m" />
                                <p><strong>{{ $m->peca->data_texto ?? 'Sem data' }}</strong><br><span class="ref">{{ $m->peca->ref }} · n.º {{ $m->numero }}</span></p>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </section>

            <section class="seccao-pagina" aria-labelledby="titulo-fotos">
                <h2 id="titulo-fotos">As fotografias</h2>
                <ul class="grelha-pecas">
                    @foreach ($pecas as $peca)
                        <x-cartao-peca :peca="$peca" />
                    @endforeach
                </ul>
            </section>
        @endif

        <p class="seccao-pagina" style="font-size:.95rem">
            É esta pessoa ou um familiar e quer corrigir alguma coisa ou pedir para ser retirado?
            Veja a <a href="{{ route('privacidade') }}">página de privacidade</a>.
        </p>
    </div>
@endsection
