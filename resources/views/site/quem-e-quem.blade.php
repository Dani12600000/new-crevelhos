@extends('layouts.site')

@php $email = \App\Models\Texto::valor('email_contacto', 's.r.casaisrevelhos@gmail.com'); @endphp

@section('titulo', 'Quem é quem?')
@section('descricao', 'Ajude a identificar as pessoas que aparecem nas fotografias antigas de Casais de Revelhos.')

@section('conteudo')
    <div class="contentor">
        <header class="cabeca-pagina">
            <h1>Quem é quem?</h1>
            <div class="introducao texto-corrido">
                {!! \App\Models\Texto::valor('quem_e_quem', '<p>Estas pessoas ainda estão por identificar. Se reconhecer alguém, diga-nos o número e a referência da fotografia: na folha do café ou por email.</p>') !!}
            </div>
        </header>

        @if ($marcacoes->isEmpty())
            <div class="vazio">
                <p>Neste momento não há ninguém por identificar. Obrigado a todos os que ajudaram!</p>
            </div>
        @else
            <ul class="grelha-caras">
                @foreach ($marcacoes as $m)
                    <li class="cara">
                        <a href="{{ route('peca', $m->peca) }}">
                            <x-recorte :marcacao="$m" />
                            <span class="numero-cara" aria-hidden="true">{{ $m->numero }}</span>
                            <p><span class="ref">{{ $m->peca->ref }}</span><br>Número {{ $m->numero }}</p>
                        </a>
                        <p><a href="mailto:{{ $email }}?subject={{ rawurlencode('Reconheço o número '.$m->numero.' da '.$m->peca->ref) }}">Sei quem é</a></p>
                    </li>
                @endforeach
            </ul>
            {{ $marcacoes->links('partials.paginacao') }}
        @endif
    </div>
@endsection
