@extends('layouts.site')

@php $email = \App\Models\Texto::valor('email_contacto', 's.r.casaisrevelhos@gmail.com'); @endphp

@section('titulo', 'Privacidade')
@section('descricao', 'Como tratamos os nomes e as imagens das pessoas no arquivo, e como pedir para retirar alguém de uma fotografia.')

@section('conteudo')
    <div class="estreito">
        <header class="cabeca-pagina">
            <h1>Privacidade e pedidos de remoção</h1>
        </header>

        <div class="texto-corrido">
            {!! \App\Models\Texto::valor('privacidade', '<p>Este arquivo tem fotografias e nomes de pessoas reais, algumas ainda vivas.</p>') !!}
        </div>

        <div class="caixa seccao-pagina">
            <h2>Pedir para retirar ou corrigir</h2>
            <p>Diga-nos a referência da peça (por exemplo <span class="mono">CRV-F-0001</span>) e o número da pessoa. Escondemos o nome ou a marcação sem demora.</p>
            <a class="botao" href="mailto:{{ $email }}?subject={{ rawurlencode('Pedido de remoção ou correção') }}">Escrever para {{ $email }}</a>
        </div>
    </div>
@endsection
