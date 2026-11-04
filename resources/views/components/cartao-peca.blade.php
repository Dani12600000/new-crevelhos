@props(['peca', 'nivel' => 'h3'])

{{-- Uma peça como prova em papel: imagem, referência, título e data. --}}
<li class="cartao-peca">
    <div class="prova">
        <div class="imagem">
            @if ($peca->temImagem())
                <img src="{{ $peca->urlMiniatura() }}" alt="" loading="lazy" decoding="async">
            @else
                <span class="sem-imagem">{{ $peca->tipo->getLabel() }}</span>
            @endif
        </div>
        <div class="ficha-curta">
            <span class="ref">{{ $peca->ref }}</span>
            <{{ $nivel }} class="titulo">{{ $peca->titulo_exibicao }}</{{ $nivel }}>
            <p class="meta">
                {{ $peca->tipo->getLabel() }}@if ($peca->data_texto) · {{ $peca->data_texto }}@endif
            </p>
        </div>
    </div>
    <a class="ligacao" href="{{ route('peca', $peca) }}"><span class="sr-only">Ver {{ $peca->ref }}: {{ $peca->titulo_exibicao }}</span></a>
</li>
