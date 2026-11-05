@php
    $nomeSite = 'Memória Digital de Casais de Revelhos';
    $tituloPagina = trim($__env->yieldContent('titulo'));
    $descricao = trim($__env->yieldContent('descricao')) ?: 'Museu digital da aldeia de Casais de Revelhos (Abrantes): fotografias, cartazes, recortes de imprensa e memórias da comunidade.';
    $email = \App\Models\Texto::valor('email_contacto', 's.r.casaisrevelhos@gmail.com');
    $menu = [
        'inicio' => 'Início',
        'arquivo' => 'Arquivo',
        'linha-do-tempo' => 'Linha do tempo',
        'quem-e-quem' => 'Quem é quem?',
        'sobre' => 'Sobre',
    ];
@endphp
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tituloPagina ? $tituloPagina.' · '.$nomeSite : $nomeSite }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($descricao, 160) }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $nomeSite }}">
    <meta property="og:title" content="{{ $tituloPagina ?: $nomeSite }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($descricao, 200) }}">
    @hasSection('imagem')
        <meta property="og:image" content="@yield('imagem')">
    @endif
    <meta name="theme-color" content="#1d4f9e">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script>
        // Aplica o tema guardado antes de desenhar a página (evita o "flash").
        // A classe "js" permite fechar o menu do telemóvel só quando há JavaScript.
        document.documentElement.classList.add('js');
        try { var t = localStorage.getItem('crevelhos-tema'); if (t === 'claro' || t === 'escuro') document.documentElement.setAttribute('data-tema', t); } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="saltar" href="#conteudo">Saltar para o conteúdo</a>

    <div class="faixa-azulejo" aria-hidden="true"></div>
    <header class="cabecalho">
        <div class="contentor">
            <a class="marca" href="{{ route('inicio') }}">
                <span class="titulo">Casais de Revelhos</span>
                <span class="subtitulo">Memória digital da aldeia</span>
            </a>

            {{-- No telemóvel o menu abre com este botão; sem JavaScript fica sempre aberto. --}}
            <button type="button" class="botao-menu" data-botao-menu aria-expanded="false" aria-controls="menu-principal">
                <span class="icone-menu" aria-hidden="true"></span>
                <span data-texto-menu>Menu</span>
            </button>

            <div class="menu-principal" id="menu-principal">
                <div class="menu-interior">
                <nav class="navegacao" aria-label="Principal">
                    <ul>
                        @foreach ($menu as $rota => $nome)
                            <li>
                                <a href="{{ route($rota) }}" @if (request()->routeIs($rota) || ($rota === 'arquivo' && request()->routeIs('seccao', 'peca'))) aria-current="page" @endif>{{ $nome }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="ferramentas">
                    <button type="button" class="botao-tema" data-botao-tema>Tema: automático</button>
                </div>
                </div>
            </div>
        </div>
    </header>

    <main id="conteudo">
        @yield('conteudo')
    </main>

    <footer class="rodape">
        <div class="contentor">
            <div>
                <p class="titulo" style="font-size:1.15rem;color:var(--tinta)">{{ $nomeSite }}</p>
                <p>{{ \App\Models\Texto::valor('rodape', 'Sociedade Recreativa Pró Casais de Revelhos, Câmara Municipal de Abrantes (Finaabrantes) e Centro de Informática e Sistemas do IPT.') }}</p>
            </div>
            <div>
                <p><strong>Tem fotografias ou memórias da aldeia?</strong><br>
                    Escreva para <a href="mailto:{{ $email }}">{{ $email }}</a>.</p>
            </div>
            <ul>
                <li><a href="{{ route('arquivo') }}">Arquivo completo</a></li>
                <li><a href="{{ route('peca.acaso') }}">Uma peça ao acaso</a></li>
                <li><a href="{{ route('sobre') }}">Sobre e ficha técnica</a></li>
                <li><a href="{{ route('privacidade') }}">Privacidade e pedidos de remoção</a></li>
            </ul>
        </div>
    </footer>
</body>
</html>
