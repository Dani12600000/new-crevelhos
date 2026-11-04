@php $marcacao = $getRecord()->marcacao; @endphp
<div style="position:relative;width:56px;height:56px;border-radius:50%;overflow:hidden;background:#ddd;margin:.25rem 0">
    <img src="{{ $marcacao->peca->urlImagem() }}" alt="" loading="lazy" style="position:absolute;max-width:none;{{ $marcacao->estiloRecorte() }}">
</div>
