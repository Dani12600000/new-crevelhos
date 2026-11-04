@extends('layouts.site')

@php use App\Models\Texto; @endphp

@section('titulo', 'Sobre o projeto')
@section('descricao', \App\Support\Limpeza::semHtml(Texto::valor('sobre', Texto::valor('inicio'))))

@section('conteudo')
    <div class="estreito">
        <header class="cabeca-pagina">
            <h1>Sobre o projeto</h1>
        </header>

        <div class="texto-corrido">
            {!! Texto::valor('sobre', Texto::valor('inicio')) !!}
        </div>

        @if ($ficha = Texto::valor('ficha_tecnica'))
            <section class="seccao-pagina ficha" aria-labelledby="titulo-ficha">
                <h2 id="titulo-ficha">Ficha técnica</h2>
                <div class="texto-corrido">{!! $ficha !!}</div>
            </section>
        @endif
    </div>
@endsection
