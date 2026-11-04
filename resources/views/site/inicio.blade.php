@extends('layouts.site')

@section('descricao', \App\Support\Limpeza::semHtml(\App\Models\Texto::valor('inicio')))

@section('conteudo')
    <div class="contentor">
        <section class="cabeca-pagina">
            <span class="ref">Museu digital · Abrantes</span>
            <h1>Memória Digital de Casais de Revelhos</h1>
            <div class="introducao texto-corrido">
                {!! \App\Models\Texto::valor('inicio', '<p>Fotografias, cartazes, recortes de imprensa e memórias da aldeia de Casais de Revelhos.</p>') !!}
            </div>
            <p style="margin-top:1.5rem;display:flex;flex-wrap:wrap;gap:.75rem">
                <a class="botao" href="{{ route('arquivo') }}">Explorar o arquivo</a>
                <a class="botao secundario" href="{{ route('peca.acaso') }}">Uma peça ao acaso</a>
            </p>
        </section>

        <form class="pesquisa-grande" action="{{ route('arquivo') }}" method="get" role="search">
            <label class="sr-only" for="q">Pesquisar no arquivo</label>
            <input type="search" id="q" name="q" placeholder="Procurar uma pessoa, um lugar, uma festa…">
            <button class="botao" type="submit">Procurar</button>
        </form>

        <section class="seccao-pagina" aria-labelledby="titulo-seccoes">
            <h2 id="titulo-seccoes">As salas do museu</h2>
            <div class="grelha-seccoes">
                @foreach ($seccoes as $s)
                    <a class="cartao-seccao" href="{{ route('seccao', $s->seccao) }}">
                        <span class="miniatura" aria-hidden="true">
                            @if ($s->capa)
                                <img src="{{ $s->capa->urlMiniatura() }}" alt="" loading="lazy">
                            @else
                                {{ mb_substr($s->seccao->nome(), 0, 1) }}
                            @endif
                        </span>
                        <span>
                            <h3>{{ $s->seccao->nome() }}</h3>
                            <p>{{ $s->seccao->resumo() }}</p>
                            <span class="contagem">{{ $s->total === 0 ? 'Ainda sem peças' : $s->total.' '.($s->total === 1 ? 'peça' : 'peças') }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        @if ($destaques->isNotEmpty())
            <section class="seccao-pagina" aria-labelledby="titulo-destaques">
                <h2 id="titulo-destaques">Do arquivo</h2>
                <ul class="grelha-pecas">
                    @foreach ($destaques as $peca)
                        <x-cartao-peca :peca="$peca" />
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="seccao-pagina caixa" aria-labelledby="titulo-ajuda">
            <h2 id="titulo-ajuda">Reconhece alguém?</h2>
            <p>As fotografias estão expostas no café da aldeia, cada uma com uma folha onde pode escrever quem lá aparece.
                Cada pessoa tem um número; com a ajuda de todos, as fotografias vão ficando identificadas.</p>
            @if ($porIdentificar > 0)
                <p><strong>{{ $porIdentificar }}</strong> {{ $porIdentificar === 1 ? 'pessoa ainda está' : 'pessoas ainda estão' }} por identificar.</p>
            @endif
            <a class="botao secundario" href="{{ route('quem-e-quem') }}">Ver quem falta identificar</a>
        </section>
    </div>
@endsection
