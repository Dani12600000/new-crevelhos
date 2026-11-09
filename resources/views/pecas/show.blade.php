@extends('layouts.site')

@php
    use App\Enums\TipoPeca;
    $seccao = $peca->tipo->seccao();
    $marcacoes = $peca->marcacoes;
    $email = \App\Models\Texto::valor('email_contacto', 's.r.casaisrevelhos@gmail.com');
    $assunto = rawurlencode('Sobre a peça '.$peca->ref);
@endphp

@section('titulo', $peca->titulo_exibicao.' ('.$peca->ref.')')
@section('descricao', \App\Support\Limpeza::semHtml($peca->descricao) ?: $peca->tipo->getLabel().' do arquivo da Memória Digital de Casais de Revelhos.')
@if ($peca->temImagem())
    @section('imagem', url($peca->urlPartilha()))
@endif

@section('conteudo')
    <div class="contentor">
        <nav class="trilho" aria-label="Está aqui">
            <a href="{{ route('inicio') }}">Início</a> ›
            <a href="{{ route('arquivo') }}">Arquivo</a> ›
            <a href="{{ route('seccao', $seccao) }}">{{ $seccao->nome() }}</a> ›
            {{ $peca->ref }}
        </nav>

        @unless ($peca->publicado)
            <p class="caixa" role="status"><strong>Pré-visualização:</strong> esta peça não está publicada e só a vê quem tem sessão no backoffice.</p>
        @endunless

        <header class="cabeca-pagina">
            <span class="ref">{{ $peca->ref }} · {{ $peca->tipo->getLabel() }}</span>
            <h1>{{ $peca->titulo_exibicao }}</h1>
        </header>

        <div class="peca">
            <div>
                <figure class="figura-peca">
                    @if ($peca->tipo === TipoPeca::Video && $peca->urlIncorporacao())
                        <iframe class="video" src="{{ $peca->urlIncorporacao() }}" title="Vídeo: {{ $peca->titulo_exibicao }}" allowfullscreen loading="lazy"></iframe>
                    @elseif ($peca->temImagem())
                        <div class="prova">
                            <div class="imagem-marcada" id="imagem-{{ $peca->ref }}" data-imagem-marcada>
                                <img src="{{ $peca->urlImagem() }}"
                                     alt="{{ $peca->tipo->getLabel() }}: {{ $peca->titulo_exibicao }}"
                                     @if ($peca->largura) width="{{ $peca->largura }}" height="{{ $peca->altura }}" @endif>
                                @foreach ($marcacoes as $m)
                                    <button type="button" class="marca-numero {{ $m->pessoa ? '' : 'por-identificar' }}"
                                            style="left: {{ $m->x }}%; top: {{ $m->y }}%"
                                            data-numero="{{ $m->numero }}" aria-expanded="false"
                                            aria-label="Número {{ $m->numero }}: {{ $m->pessoa?->nome ?? 'por identificar' }}">
                                        {{ $m->numero }}
                                        <span class="balao" aria-hidden="true">{{ $m->pessoa?->nome ?? 'Por identificar' }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <figcaption class="legenda-peca">
                                {{ $peca->ref }}@if ($peca->data_texto) · {{ $peca->data_texto }}@endif @if ($peca->local) · {{ $peca->local }}@endif
                            </figcaption>
                        </div>
                    @else
                        <div class="vazio"><p>Esta peça ainda não tem imagem.</p></div>
                    @endif
                </figure>

                @if ($peca->temImagem())
                    <div class="barra-imagem">
                        @if ($marcacoes->isNotEmpty())
                            <button type="button" class="botao secundario pequeno" data-alternar-numeros aria-controls="imagem-{{ $peca->ref }}" aria-pressed="false">Esconder os números</button>
                        @else
                            <span></span>
                        @endif
                        <a class="botao secundario pequeno" href="{{ $peca->urlOriginal() }}">Ver o original</a>
                    </div>
                @endif

                @if ($peca->descricao)
                    <div class="descricao texto-corrido seccao-pagina" style="margin-top:2rem">{!! $peca->descricao !!}</div>
                @endif

                @if ($peca->tipo->permiteMarcacoes() && $marcacoes->isNotEmpty())
                    <section class="seccao-pagina" aria-labelledby="titulo-quem">
                        <h2 id="titulo-quem">Quem está na fotografia</h2>
                        <ol class="lista-pessoas">
                            @foreach ($marcacoes as $m)
                                <li data-linha-numero="{{ $m->numero }}">
                                    <span class="numero {{ $m->pessoa ? '' : 'por-identificar' }}" aria-hidden="true">{{ $m->numero }}</span>
                                    @if ($m->pessoa)
                                        <a href="{{ route('pessoa', $m->pessoa) }}">{{ $m->pessoa->nome }}</a>
                                        @if ($m->pessoa->alcunha)<span class="ref">({{ $m->pessoa->alcunha }})</span>@endif
                                    @else
                                        <span>Por identificar. <a href="mailto:{{ $email }}?subject={{ $assunto }}%20%E2%80%94%20n%C3%BAmero%20{{ $m->numero }}">Sabe quem é?</a></span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                <section class="seccao-pagina" aria-labelledby="titulo-memorias">
                    <h2 id="titulo-memorias">Memórias</h2>
                    @if ($peca->comentarios->isEmpty())
                        <p>Ainda não há memórias sobre esta peça. Lembra-se de alguma coisa? Escreva na folha do café ou para <a href="mailto:{{ $email }}?subject={{ $assunto }}">{{ $email }}</a>.</p>
                    @else
                        <ul class="memorias">
                            @foreach ($peca->comentarios as $c)
                                <li class="memoria">
                                    <blockquote>{!! nl2br(e($c->texto)) !!}</blockquote>
                                    <footer>
                                        {{ $c->autor ?: 'Anónimo' }} · {{ $c->origem->getLabel() }}@if ($c->data), {{ $c->data->translatedFormat('j \d\e F \d\e Y') }}@endif
                                    </footer>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>

            <aside class="ficha" aria-label="Ficha da peça">
                <h2 class="sr-only">Ficha</h2>
                <dl>
                    <dt>Ref.</dt><dd class="mono">{{ $peca->ref }}</dd>
                    <dt>Tipo</dt><dd>{{ $peca->tipo->getLabel() }}</dd>
                    @if ($peca->jornal)<dt>Jornal</dt><dd>{{ $peca->jornal }}</dd>@endif
                    <dt>Data</dt>
                    <dd>
                        @if ($peca->data_publicacao)
                            {{ $peca->data_publicacao->translatedFormat('j \d\e F \d\e Y') }}
                        @else
                            {{ $peca->data_texto ?? 'Desconhecida' }}
                        @endif
                        @if ($peca->decada)<br><a href="{{ route('arquivo', ['decada' => $peca->decada]) }}">Anos {{ $peca->decada }}</a>@endif
                    </dd>
                    @if ($peca->local)<dt>Local</dt><dd>{{ $peca->local }}</dd>@endif
                    @if ($peca->temas->isNotEmpty())
                        <dt>{{ $peca->temas->count() === 1 ? 'Tema' : 'Temas' }}</dt>
                        <dd>
                            <ul class="etiquetas">
                                @foreach ($peca->temas as $tema)
                                    <li><a class="etiqueta" href="{{ route('arquivo', ['tema' => $tema->slug]) }}">{{ $tema->nome }}</a></li>
                                @endforeach
                            </ul>
                        </dd>
                    @endif
                    <dt>Autor</dt><dd>{{ $peca->autor ?? 'Desconhecido' }}</dd>
                    @if ($peca->cedido_por)<dt>Cedido por</dt><dd>{{ $peca->cedido_por }}</dd>@endif
                    @if ($peca->acervo)<dt>Acervo</dt><dd>{{ $peca->acervo->nome }}</dd>@endif
                    @if ($peca->editora)<dt>Editora</dt><dd>{{ $peca->editora }}</dd>@endif
                    @if ($peca->edicao)<dt>Edição</dt><dd>{{ $peca->edicao }}</dd>@endif
                    @if ($peca->paginas)<dt>Páginas</dt><dd>{{ $peca->paginas }}</dd>@endif
                    @if ($peca->url_original)<dt>Original</dt><dd><a href="{{ $peca->url_original }}" rel="noopener">Ver notícia na fonte</a></dd>@endif
                </dl>
                <p style="margin:1.25rem 0 0;font-size:.95rem">
                    Tem uma correção ou mais informação?<br>
                    <a href="mailto:{{ $email }}?subject={{ $assunto }}">Escreva-nos sobre a {{ $peca->ref }}</a>.
                </p>
            </aside>
        </div>

        <nav class="anterior-seguinte" aria-label="Outras peças de {{ $seccao->nome() }}">
            @if ($anterior)
                <a class="botao secundario" href="{{ route('peca', $anterior) }}" rel="prev">‹ {{ $anterior->ref }}</a>
            @else
                <span></span>
            @endif
            <a href="{{ route('seccao', $seccao) }}">Voltar a {{ $seccao->nome() }}</a>
            @if ($seguinte)
                <a class="botao secundario" href="{{ route('peca', $seguinte) }}" rel="next">{{ $seguinte->ref }} ›</a>
            @else
                <span></span>
            @endif
        </nav>
    </div>
@endsection
