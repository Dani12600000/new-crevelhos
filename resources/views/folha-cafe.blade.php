<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <title>Folha do café · {{ $peca->ref }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A5 portrait; margin: 9mm; }
        body { background: #fff; color: #111; display: block; min-height: 0; }
        .folha { width: 130mm; margin: 0 auto; font-size: 11pt; line-height: 1.35; }
        .topo { display: flex; justify-content: space-between; align-items: flex-start; gap: 6mm; border-bottom: 2px solid #1d4f9e; padding-bottom: 3mm; }
        .topo h1 { font-size: 16pt; margin: 1mm 0 0; }
        .topo .ref { font-size: 10pt; color: #333; }
        .qr { width: 26mm; flex: none; text-align: center; font-size: 7pt; }
        .qr svg { width: 26mm; height: 26mm; display: block; }
        .imagem { margin: 4mm 0; }
        .imagem .imagem-marcada img { max-height: 85mm; width: auto; margin: 0 auto; }
        .imagem-marcada { width: fit-content; margin: 0 auto; }
        .marca-numero { width: 6mm; height: 6mm; font-size: 8pt; border-width: 1px; }
        .marca-numero .balao { display: none; }
        .frase { font-weight: 700; font-size: 12pt; margin: 2mm 0 3mm; }
        .linhas { list-style: none; padding: 0; margin: 0; }
        .linhas li { border-bottom: 1px solid #999; height: 8.5mm; display: flex; align-items: flex-end; gap: 2mm; font-size: 9pt; color: #555; }
        .rodape-folha { margin-top: 3mm; font-size: 8pt; color: #444; display: flex; justify-content: space-between; }
        .acoes { text-align: center; margin: 1rem; }
        @media print { .acoes { display: none; } }
    </style>
</head>
<body>
    <div class="acoes">
        <button class="botao" type="button" onclick="window.print()">Imprimir (A5)</button>
        <a href="{{ url()->previous() }}">Voltar</a>
    </div>

    <article class="folha">
        <header class="topo">
            <div>
                <span class="ref">Memória Digital de Casais de Revelhos · {{ $peca->ref }}</span>
                <h1 class="titulo">{{ $peca->titulo_exibicao }}</h1>
                @if ($peca->data_texto)<span class="ref">{{ $peca->data_texto }}</span>@endif
            </div>
            <div class="qr">
                {!! $qr !!}
                Veja no telemóvel
            </div>
        </header>

        @if ($peca->temImagem())
            <div class="imagem">
                <div class="imagem-marcada">
                    <img src="{{ $peca->urlImagem() }}" alt="">
                    @foreach ($peca->marcacoes as $m)
                        <span class="marca-numero {{ $m->pessoa ? '' : 'por-identificar' }}" style="left: {{ $m->x }}%; top: {{ $m->y }}%">{{ $m->numero }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <p class="frase">{{ $frase }}</p>

        <ol class="linhas">
            @for ($i = 0; $i < 9; $i++)
                <li>{{ $i < 2 ? 'N.º ____ :' : '' }}</li>
            @endfor
        </ol>

        <footer class="rodape-folha">
            <span>{{ preg_replace('~^https?://~', '', $url) }}</span>
            <span>Obrigado por ajudar a guardar a memória da aldeia.</span>
        </footer>
    </article>
</body>
</html>
