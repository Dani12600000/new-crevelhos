@extends('layouts.site')

@section('titulo', 'Linha do tempo')
@section('descricao', 'A história de Casais de Revelhos década a década, em fotografias, cartazes e recortes de imprensa.')

@section('conteudo')
    <div class="contentor">
        <header class="cabeca-pagina">
            <h1>Linha do tempo</h1>
            <p class="introducao">A aldeia década a década. Cada peça entra na década do ano que tem na data.</p>
        </header>

        @if ($decadas->isEmpty())
            <div class="vazio"><p>Ainda não há peças com data.</p></div>
        @else
            <nav aria-label="Saltar para uma década" class="saltos-decada">
                @foreach ($decadas as $d)
                    <a class="etiqueta" href="#anos-{{ $d->decada }}">{{ $d->decada }}</a>
                @endforeach
            </nav>

            <ol class="linha-tempo">
                @foreach ($decadas as $d)
                    <li class="decada" id="anos-{{ $d->decada }}">
                        <h2>Anos {{ $d->decada }}</h2>
                        <p class="contagem">{{ $d->total }} {{ $d->total === 1 ? 'peça' : 'peças' }}</p>
                        <ul class="grelha-pecas">
                            @foreach ($d->pecas as $peca)
                                <x-cartao-peca :peca="$peca" />
                            @endforeach
                        </ul>
                        @if ($d->total > $d->pecas->count())
                            <p style="margin-top:1rem"><a href="{{ route('arquivo', ['decada' => $d->decada]) }}">Ver as {{ $d->total }} peças dos anos {{ $d->decada }}</a></p>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif

        @if ($semData > 0)
            <p class="caixa">
                Há ainda <strong>{{ $semData }}</strong> {{ $semData === 1 ? 'peça' : 'peças' }} sem data conhecida.
                <a href="{{ route('arquivo', ['decada' => 'sem']) }}">Sabe de quando são?</a>
            </p>
        @endif
    </div>
@endsection
