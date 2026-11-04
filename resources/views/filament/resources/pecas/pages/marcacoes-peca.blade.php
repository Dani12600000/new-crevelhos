<x-filament-panels::page>
    @php
        $peca = $this->record;
        $marcacoes = $this->marcacoes;
    @endphp

    <style>
        .crv-editor { display: grid; gap: 1.5rem; }
        @media (min-width: 1100px) { .crv-editor { grid-template-columns: minmax(0, 1.5fr) minmax(340px, 1fr); align-items: start; } }
        .crv-imagem { position: relative; cursor: crosshair; user-select: none; touch-action: none; border-radius: .5rem; overflow: hidden; }
        .crv-imagem img { width: 100%; display: block; pointer-events: none; }
        .crv-num {
            position: absolute; transform: translate(-50%, -50%); width: 30px; height: 30px; border-radius: 50%;
            background: #1d4f9e; color: #fff; border: 2px solid #fff; font-weight: 700; font-size: 14px;
            display: grid; place-items: center; cursor: grab; box-shadow: 0 1px 4px rgb(0 0 0 / .6); z-index: 2;
        }
        .crv-num.vazio { background: #fff; color: #1d4f9e; border-color: #1d4f9e; }
        .crv-num.oculta { opacity: .45; border-style: dashed; }
        .crv-num.ativa { outline: 3px solid #f59e0b; outline-offset: 2px; z-index: 3; }
        .crv-raio { position: absolute; transform: translate(-50%, -50%); border: 2px dashed #f59e0b; border-radius: 50%; pointer-events: none; }
        .crv-lista { display: grid; gap: .75rem; }
        .crv-cartao { border: 1px solid rgb(0 0 0 / .1); border-radius: .75rem; padding: .9rem; background: rgb(255 255 255 / .02); }
        .crv-cartao.ativa { border-color: #f59e0b; box-shadow: 0 0 0 1px #f59e0b; }
        .crv-topo { display: flex; gap: .75rem; align-items: center; }
        .crv-recorte { position: relative; width: 64px; height: 64px; flex: none; border-radius: 50%; overflow: hidden; background: #ddd; }
        .crv-recorte img { position: absolute; max-width: none; }
        .crv-acoes { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .7rem; }
        .crv-sug { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; font-size: .875rem; padding: .35rem 0; border-top: 1px dashed rgb(0 0 0 / .1); }
        .crv-sug .nome { font-weight: 600; }
        .crv-ajuda { font-size: .875rem; opacity: .8; }
        .crv-aviso { color: #b45309; font-size: .8rem; font-weight: 600; }
    </style>

    <div class="crv-editor">
        <div>
            <p class="crv-ajuda" style="margin-bottom:.75rem">
                <strong>Clique na cara</strong> para pôr o próximo número ({{ \App\Models\Marcacao::proximoNumero($peca) }}).
                Arraste um número para o mudar de sítio. O círculo tracejado é o recorte da cara usado no site.
            </p>

            <div class="crv-imagem"
                 x-data="{
                    posicao(e) {
                        const r = this.$el.getBoundingClientRect();
                        return [(e.clientX - r.left) / r.width * 100, (e.clientY - r.top) / r.height * 100];
                    },
                 }"
                 x-on:click="const [x, y] = posicao($event); $wire.adicionar(x, y)">
                <img src="{{ $peca->urlImagem() }}" alt="{{ $peca->titulo_exibicao }}">

                @foreach ($marcacoes as $m)
                    @if ($this->selecionada === $m->id)
                        <span class="crv-raio" style="left: {{ $m->x }}%; top: {{ $m->y }}%; width: {{ $m->raio * 2 }}%; aspect-ratio: 1;"></span>
                    @endif
                    <button type="button" wire:key="num-{{ $m->id }}-{{ $m->x }}-{{ $m->y }}"
                            class="crv-num {{ $m->pessoa ? '' : 'vazio' }} {{ $m->oculta ? 'oculta' : '' }} {{ $this->selecionada === $m->id ? 'ativa' : '' }}"
                            style="left: {{ $m->x }}%; top: {{ $m->y }}%"
                            title="{{ $m->pessoa?->nome ?? 'Por identificar' }}"
                            x-data="{ inicio: null, mexeu: false }"
                            x-on:click.stop
                            x-on:pointerdown.stop="inicio = [$event.clientX, $event.clientY]; mexeu = false; $el.setPointerCapture($event.pointerId)"
                            x-on:pointermove="if (inicio) {
                                if (Math.abs($event.clientX - inicio[0]) + Math.abs($event.clientY - inicio[1]) > 4) mexeu = true;
                                if (mexeu) { const r = $el.parentElement.getBoundingClientRect();
                                    $el.style.left = (($event.clientX - r.left) / r.width * 100) + '%';
                                    $el.style.top = (($event.clientY - r.top) / r.height * 100) + '%'; }
                            }"
                            x-on:pointerup="if (inicio) {
                                const r = $el.parentElement.getBoundingClientRect();
                                if (mexeu) { $wire.mover({{ $m->id }}, ($event.clientX - r.left) / r.width * 100, ($event.clientY - r.top) / r.height * 100) }
                                else { $wire.selecionar({{ $m->id }}) }
                                inicio = null;
                            }">
                        {{ $m->numero }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="crv-lista">
            @forelse ($marcacoes as $m)
                @php
                    $pendentes = $m->sugestoes->where('estado', $this->estadoPendente());
                    $conflito = $pendentes->map(fn ($s) => mb_strtolower(trim($s->nome_sugerido)))->unique()->count() > 1;
                @endphp
                <div class="crv-cartao {{ $this->selecionada === $m->id ? 'ativa' : '' }}" wire:key="cartao-{{ $m->id }}">
                    <div class="crv-topo">
                        <div class="crv-recorte">
                            <img src="{{ $peca->urlImagem() }}" alt="" style="{{ $m->estiloRecorte() }}">
                        </div>
                        <div style="flex:1">
                            <div style="font-weight:700;font-size:1.05rem">
                                N.º {{ $m->numero }}:
                                @if ($m->pessoa)
                                    {{ $m->pessoa->nome }}
                                @else
                                    <span style="opacity:.7">por identificar</span>
                                @endif
                            </div>
                            @if ($m->oculta)
                                <div class="crv-aviso">Escondida no site</div>
                            @endif
                            @if ($m->pessoa?->oculta)
                                <div class="crv-aviso">Pessoa escondida no site</div>
                            @endif
                            @if ($conflito)
                                <div class="crv-aviso">⚠ Há sugestões diferentes para este número</div>
                            @endif
                            @if ($this->selecionada === $m->id)
                                <label class="crv-ajuda" style="display:flex;gap:.5rem;align-items:center;margin-top:.3rem">
                                    Tamanho do recorte
                                    <input type="range" min="2" max="30" step="0.5" value="{{ $m->raio }}"
                                           wire:change="alterarRaio({{ $m->id }}, $event.target.value)">
                                </label>
                            @endif
                        </div>
                    </div>

                    <div class="crv-acoes">
                        {{ ($this->ligarPessoaAction)(['marcacao' => $m->id]) }}
                        @if ($m->pessoa)
                            {{ ($this->desligarPessoaAction)(['marcacao' => $m->id]) }}
                        @endif
                        {{ ($this->sugestaoAction)(['marcacao' => $m->id]) }}
                        {{ ($this->alternarOcultaAction)(['marcacao' => $m->id]) }}
                        {{ ($this->removerAction)(['marcacao' => $m->id]) }}
                    </div>

                    @foreach ($m->sugestoes as $s)
                        <div class="crv-sug" wire:key="sug-{{ $s->id }}">
                            <x-filament::badge :color="$s->estado->getColor()" size="sm">{{ $s->estado->getLabel() }}</x-filament::badge>
                            <span class="nome">{{ $s->nome_sugerido }}</span>
                            <span style="opacity:.7">{{ $s->fonte->getLabel() }}@if ($s->data), {{ $s->data->format('d/m/Y') }}@endif</span>
                            @if ($s->estado === $this->estadoPendente())
                                <span style="margin-left:auto;display:flex;gap:.3rem">
                                    {{ ($this->aceitarSugestaoAction)(['sugestao' => $s->id]) }}
                                    {{ ($this->rejeitarSugestaoAction)(['sugestao' => $s->id]) }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @empty
                <x-filament::section>
                    Ainda não há números nesta fotografia. Clique numa cara para começar.
                </x-filament::section>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
