@props(['marcacao'])

{{-- Recorte redondo da cara à volta de um número (a imagem é deslocada por CSS, sem gerar ficheiros). --}}
<div class="recorte">
    <img src="{{ $marcacao->peca->urlImagem() }}" alt="" loading="lazy" style="{{ $marcacao->estiloRecorte() }}">
</div>
