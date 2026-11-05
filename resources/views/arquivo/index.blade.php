@extends('layouts.site')

@php
    use App\Models\Texto;
    $rota = $seccao ? fn (array $p = []) => route('seccao', ['seccao' => $seccao->value] + $p) : fn (array $p = []) => route('arquivo', $p);
    $titulo = $seccao ? $seccao->nome() : 'Arquivo';
    $email = Texto::valor('email_contacto', 's.r.casaisrevelhos@gmail.com');
    $nomeDecada = fn ($d) => $d === 'sem' ? 'Sem data' : 'Anos '.$d;
@endphp

@section('titulo', $titulo.($pesquisa->tema ? ': '.$pesquisa->tema->nome : '').($pesquisa->decada ? ' · '.$nomeDecada($pesquisa->decada) : ''))
@section('descricao', $seccao ? $seccao->resumo().' '.\App\Support\Limpeza::semHtml(Texto::valor($seccao->chaveTexto())) : 'Todo o arquivo da Memória Digital de Casais de Revelhos, com filtros por tipo, década e tema.')

@section('conteudo')
    <div class="contentor">
        <nav class="trilho" aria-label="Está aqui">
            <a href="{{ route('inicio') }}">Início</a> ›
            @if ($seccao)
                <a href="{{ route('arquivo') }}">Arquivo</a> › {{ $seccao->nome() }}
            @else
                Arquivo
            @endif
        </nav>

        <header class="cabeca-pagina">
            <h1>{{ $titulo }}</h1>
            @if ($seccao && ($intro = Texto::valor($seccao->chaveTexto())))
                <div class="introducao texto-corrido">{!! $intro !!}</div>
            @elseif (! $seccao)
                <p class="introducao">Todas as peças do museu. Use os filtros para procurar por tipo, década ou tema; a pesquisa procura também os nomes das pessoas identificadas nas fotografias.</p>
                <ul class="etiquetas" aria-label="Salas do museu">
                    @foreach (\App\Enums\Seccao::cases() as $s)
                        <li><a class="etiqueta" href="{{ route('seccao', $s) }}">{{ $s->nome() }}</a></li>
                    @endforeach
                </ul>
            @endif
        </header>

        @if ($seccao && $pecas->total() === 0 && ! $pesquisa->temFiltros())
            {{-- Secção vazia: fica visível, com convite a contribuir. --}}
            <div class="vazio">
                <h2>Esta sala ainda está vazia</h2>
                <p class="introducao">Ainda não há peças em {{ mb_strtolower($seccao->nome()) }}. Tem alguma coisa que possa partilhar com a aldeia?</p>
                <p><a class="botao" href="mailto:{{ $email }}?subject={{ rawurlencode('Contributo para '.$seccao->nome()) }}">Escrever para {{ $email }}</a></p>
            </div>
        @else
            <div class="disposicao-arquivo">
                <form class="filtros caixa" method="get" action="{{ $rota() }}" aria-label="Filtros">
                    <div>
                        <label for="q">Procurar</label>
                        <input type="search" id="q" name="q" value="{{ $pesquisa->texto }}" placeholder="Nome, lugar, assunto…">
                    </div>

                    {{-- No telemóvel estes campos ficam num bloco que se abre (o JS fecha-o se não houver filtros ativos). --}}
                    <details class="mais-filtros" open data-mais-filtros @if ($pesquisa->tipo || $pesquisa->decada || $pesquisa->tema) data-ativos @endif>
                        <summary>Tipo, década e tema</summary>
                        <div class="campos-filtros">

                    @php $tipos = $seccao ? $seccao->tipos() : \App\Enums\TipoPeca::cases(); @endphp
                    @if (count($tipos) > 1)
                        <div>
                            <label for="tipo">Tipo</label>
                            <select id="tipo" name="tipo">
                                <option value="">Todos</option>
                                @foreach ($tipos as $tipo)
                                    <option value="{{ $tipo->value }}" @selected($pesquisa->tipo === $tipo)>{{ $tipo->getLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label for="decada">Década</label>
                        <select id="decada" name="decada">
                            <option value="">Todas</option>
                            @foreach ($decadas as $decada => $total)
                                <option value="{{ $decada }}" @selected((string) $pesquisa->decada === (string) $decada)>{{ $nomeDecada($decada) }} ({{ $total }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tema">Tema</label>
                        <select id="tema" name="tema">
                            <option value="">Todos</option>
                            @foreach ($temas as $tema)
                                <option value="{{ $tema->slug }}" @selected($pesquisa->tema?->is($tema))>{{ $tema->nome }} ({{ $tema->pecas_count }})</option>
                            @endforeach
                        </select>
                    </div>

                        </div>
                    </details>

                    <button class="botao" type="submit">Aplicar filtros</button>
                    @if ($pesquisa->temFiltros())
                        <a href="{{ $rota() }}">Limpar filtros</a>
                    @endif
                </form>

                <section aria-labelledby="titulo-resultados">
                    <div class="resultados-cabeca">
                        <h2 id="titulo-resultados" style="margin:0">
                            {{ $pecas->total() }} {{ $pecas->total() === 1 ? 'peça' : 'peças' }}
                        </h2>
                        @if ($pesquisa->temFiltros())
                            <ul class="etiquetas" aria-label="Filtros ativos">
                                @if ($pesquisa->texto)<li><a class="etiqueta ativa" href="{{ $rota($pesquisa->parametros(['q' => null])) }}">“{{ $pesquisa->texto }}” ✕</a></li>@endif
                                @if ($pesquisa->tipo)<li><a class="etiqueta ativa" href="{{ $rota($pesquisa->parametros(['tipo' => null])) }}">{{ $pesquisa->tipo->getLabel() }} ✕</a></li>@endif
                                @if ($pesquisa->decada)<li><a class="etiqueta ativa" href="{{ $rota($pesquisa->parametros(['decada' => null])) }}">{{ $nomeDecada($pesquisa->decada) }} ✕</a></li>@endif
                                @if ($pesquisa->tema)<li><a class="etiqueta ativa" href="{{ $rota($pesquisa->parametros(['tema' => null])) }}">{{ $pesquisa->tema->nome }} ✕</a></li>@endif
                            </ul>
                        @endif
                    </div>

                    @if ($pecas->isEmpty())
                        <div class="vazio">
                            <p>Não encontrámos peças com estes filtros.</p>
                            <a href="{{ $rota() }}">Ver tudo</a>
                        </div>
                    @else
                        <ul class="grelha-pecas">
                            @foreach ($pecas as $peca)
                                <x-cartao-peca :peca="$peca" />
                            @endforeach
                        </ul>
                        {{ $pecas->links('partials.paginacao') }}
                    @endif
                </section>
            </div>
        @endif
    </div>
@endsection
